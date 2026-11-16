<?php

namespace App\Core;

/**
 * Zero-dependency writer of real Excel workbooks (.xlsx): multiple sheets, styled title/filter headers,
 * coloured section bands, typed cells (text, ID/phone text, integers, money, dates, percentages),
 * automatic column widths and row heights, frozen headers, AutoFilter (data rows only), dynamic SUBTOTAL/SUM
 * totals rows, proper RTL/LTR reading order, shared strings table, landscape print setup, and a pure-PHP
 * ZIP fallback so .xlsx exports work on every server even without PHP's zip extension.
 *
 *   $r = new XlsxReport();
 *   $r->sheet('الملخص', [['المؤشر', 50, XlsxReport::TEXT], ['القيمة', 20, XlsxReport::TEXT], ['الوحدة', 12, XlsxReport::ID]], ['Title', 'Date']);
 *   $r->section('الأرصدة');
 *   $r->row(['الرصيد', [1234.5, XlsxReport::MONEY], 'ريال']);
 *   $r->sheet('المدفوعات', [...], [...], true)->row([...])->sumTotals('الإجمالي', [4]);
 *   $r->download('report.xlsx');
 */
class XlsxReport
{
    public const TEXT = 'text';     // wrapped text, aligned to the reading side
    public const ID = 'id';         // text that must stay text (phone, national ID, IBAN, email, ratio), centred LTR
    public const INT = 'int';       // whole number (#,##0)
    public const MONEY = 'money';   // number with 2 decimals and thousands separators (#,##0.00)
    public const DATE = 'date';     // real Excel date shown as dd/mm/yyyy
    public const PCT = 'pct';       // number shown with a percent sign (94.44 => 94.44%)

    private const TYPES = [self::TEXT, self::ID, self::INT, self::MONEY, self::DATE, self::PCT];
    private const BAND_COLOURS = ['FF0F766E', 'FF1D4ED8', 'FF7C3AED', 'FFB45309', 'FFBE123C', 'FF0E7490', 'FF4D7C0F', 'FF475569'];

    private bool $rtl;
    /** @var array<int,array{title:string,columns:array,titleLines:string[],filter:bool,rows:array}> */
    private array $sheets = [];
    private int $bands = 0;

    /** @var array<string,int> */
    private array $stringMap = [];
    /** @var string[] */
    private array $strings = [];
    private int $stringRefs = 0;

    public function __construct(bool $rtl = true)
    {
        $this->rtl = $rtl;
    }

    /** Always true: uses ZipArchive when installed, or the built-in pure-PHP ZIP archive builder otherwise. */
    public static function available(): bool
    {
        return true;
    }

    /**
     * Starts a new sheet (it becomes the current one) with its columns [header, min width in chars, default type].
     * $filter enables AutoFilter on the header row (only emitted when data rows exist, stopping before any totals row).
     *
     * @param array<int,array{0:string,1:float|int,2:string}> $columns
     * @param string[] $titleLines the first one is the title, the others are small grey notes/filters
     */
    public function sheet(string $title, array $columns, array $titleLines = [], bool $filter = false): self
    {
        // Excel sheet names: <= 31 chars, none of [ ] : * ? / \, not empty, not starting/ending with ', unique
        $clean = str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $title);
        $clean = trim(trim($clean), "'");
        $name = mb_substr($clean, 0, 31) ?: 'Sheet' . (count($this->sheets) + 1);
        $base = $name;
        for ($i = 2; in_array($name, array_column($this->sheets, 'title'), true); $i++) {
            $suffix = ' ' . $i;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
        }
        $this->sheets[] = [
            'title' => $name,
            'columns' => array_values($columns),
            'titleLines' => array_values(array_map('strval', array_filter($titleLines, fn($l) => $l !== null && $l !== ''))),
            'filter' => $filter,
            'rows' => [],
        ];
        return $this;
    }

    /** A coloured band across the sheet that starts a group of rows (zebra shading restarts after it). */
    public function section(string $title, ?int $colour = null): self
    {
        $this->current()['rows'][] = ['kind' => 'section', 'title' => $title, 'colour' => ($colour ?? $this->bands++) % count(self::BAND_COLOURS)];
        return $this;
    }

    /** One data row: a value per column, either a scalar (the column's default type) or [value, type]. */
    public function row(array $cells): self
    {
        $this->current()['rows'][] = ['kind' => 'row', 'cells' => array_values($cells)];
        return $this;
    }

    /** A single merged line of small grey text (e.g. "no data"). */
    public function note(string $text): self
    {
        $this->current()['rows'][] = ['kind' => 'note', 'text' => $text];
        return $this;
    }

    /**
     * Appends a styled Totals row. Each cell can be a scalar, [value, type], or
     * ['formula' => 'SUM(B5:B10)', 'value' => 100.0, 'type' => XlsxReport::MONEY].
     */
    public function totals(array $cells): self
    {
        $this->current()['rows'][] = ['kind' => 'totals', 'cells' => array_values($cells)];
        return $this;
    }

    /**
     * Appends a Totals row that automatically sums the given 0-based column indices over the preceding
     * contiguous data rows (using SUBTOTAL(9,...) when AutoFilter is enabled so filtering updates totals live).
     */
    public function sumTotals(string $label, array $sumCols, int $labelCol = 0, ?int $countCol = null, ?string $countText = null): self
    {
        $sheet = &$this->current();
        $header = $this->headerRow($sheet);

        $firstDataRow = null;
        $lastDataRow = null;
        $dataIndices = [];
        $r = $header;
        foreach ($sheet['rows'] as $idx => $item) {
            $r++;
            if ($item['kind'] === 'row') {
                if ($firstDataRow === null) {
                    $firstDataRow = $r;
                }
                $lastDataRow = $r;
                $dataIndices[] = $idx;
            } elseif ($item['kind'] === 'section' || $item['kind'] === 'totals') {
                // Reset block so sums only cover the current table/section block
                $firstDataRow = null;
                $lastDataRow = null;
                $dataIndices = [];
            }
        }
        if ($firstDataRow === null || $lastDataRow === null || !$dataIndices) {
            return $this;
        }

        $ncols = count($sheet['columns']);
        $cells = array_fill(0, $ncols, null);
        $cells[$labelCol] = [$label, self::TEXT];

        if ($countCol !== null && $countCol !== $labelCol && isset($sheet['columns'][$countCol])) {
            if ($countText !== null) {
                $cells[$countCol] = [$countText, self::ID];
            } else {
                $cLetter = self::col($countCol + 1);
                $fn = $sheet['filter'] ? 'SUBTOTAL(3,' : 'COUNTA(';
                $cells[$countCol] = [
                    'formula' => $fn . $cLetter . $firstDataRow . ':' . $cLetter . $lastDataRow . ')',
                    'value' => count($dataIndices),
                    'type' => self::INT,
                ];
            }
        }

        foreach ($sumCols as $colIdx) {
            if (!isset($sheet['columns'][$colIdx])) {
                continue;
            }
            $defaultType = (string) ($sheet['columns'][$colIdx][2] ?? self::MONEY);
            $type = in_array($defaultType, [self::INT, self::MONEY], true) ? $defaultType : self::MONEY;

            $sum = 0.0;
            foreach ($dataIndices as $dIdx) {
                $rawCell = $sheet['rows'][$dIdx]['cells'][$colIdx] ?? null;
                $val = is_array($rawCell) ? ($rawCell[0] ?? null) : $rawCell;
                if (is_numeric($val)) {
                    $sum += (float) $val;
                }
            }
            $sum = $type === self::INT ? (int) round($sum) : round($sum, 2);
            $letter = self::col($colIdx + 1);
            $fn = $sheet['filter'] ? 'SUBTOTAL(9,' : 'SUM(';
            $cells[$colIdx] = [
                'formula' => $fn . $letter . $firstDataRow . ':' . $letter . $lastDataRow . ')',
                'value' => $sum,
                'type' => $type,
            ];
        }

        return $this->totals($cells);
    }

    public function sheetCount(): int
    {
        return count($this->sheets);
    }

    /** Column letter for a 1-based column index (1 => A, 27 => AA). */
    public static function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + $n % 26) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    /** @return array<string,mixed> */
    private function &current(): array
    {
        if (!$this->sheets) {
            throw new \LogicException('Start a sheet first.');
        }
        return $this->sheets[count($this->sheets) - 1];
    }

    // ---------------------------------------------------------------------------------------------------------------

    /** Builds all OOXML files in the workbook package. @return array<string,string> */
    private function buildParts(): array
    {
        if (!$this->sheets) {
            throw new \LogicException('The workbook has no sheet.');
        }

        $this->stringMap = [];
        $this->strings = [];
        $this->stringRefs = 0;

        $sheetParts = [];
        $sheetMeta = [];
        foreach ($this->sheets as $i => $sheet) {
            [$xml, $meta] = $this->sheetXml($sheet, $i === 0);
            $sheetParts['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $xml;
            $sheetMeta[$i] = $meta;
        }

        $parts = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRels(),
            'xl/workbook.xml' => $this->workbook($sheetMeta),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml' => $this->styles(),
            'xl/sharedStrings.xml' => $this->sharedStrings(),
        ];

        return $parts + $sheetParts;
    }

    public function save(string $path): void
    {
        $parts = $this->buildParts();

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($parts as $name => $content) {
                    $zip->addFromString($name, $content);
                }
                if ($zip->close()) {
                    return;
                }
            }
        }

        if (file_put_contents($path, self::buildZipBytes($parts)) === false) {
            throw new \RuntimeException('Cannot create the Excel file.');
        }
    }

    /** Builds the .xlsx binary in memory and streams it directly to the browser. */
    public function download(string $filename): void
    {
        $bytes = null;
        $parts = $this->buildParts();

        if (class_exists(\ZipArchive::class)) {
            $tmp = @tempnam(sys_get_temp_dir(), 'xlsx');
            if ($tmp !== false) {
                try {
                    $zip = new \ZipArchive();
                    if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                        foreach ($parts as $name => $content) {
                            $zip->addFromString($name, $content);
                        }
                        if ($zip->close()) {
                            $read = @file_get_contents($tmp);
                            if ($read !== false && $read !== '') {
                                $bytes = $read;
                            }
                        }
                    }
                } finally {
                    @unlink($tmp);
                }
            }
        }

        if ($bytes === null) {
            $bytes = self::buildZipBytes($parts);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        @ini_set('zlib.output_compression', '0');

        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.xlsx';
        if (!str_ends_with(strtolower($ascii), '.xlsx')) {
            $ascii .= '.xlsx';
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $ascii . '"');
        header('X-Content-Type-Options: nosniff');
        if (!ini_get('zlib.output_compression')) {
            header('Content-Length: ' . strlen($bytes));
        }
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bytes;
        exit;
    }

    /** Pure-PHP PKZIP archive builder (DEFLATE when zlib is present, STORE otherwise). @param array<string,string> $files */
    private static function buildZipBytes(array $files): string
    {
        $dt = getdate();
        $dosTime = (($dt['hours'] & 0x1F) << 11) | (($dt['minutes'] & 0x3F) << 5) | (($dt['seconds'] >> 1) & 0x1F);
        $dosDate = ((max(1980, $dt['year']) - 1980) << 9) | (($dt['mon'] & 0x0F) << 5) | ($dt['mday'] & 0x1F);
        $canDeflate = function_exists('gzdeflate');

        $body = '';
        $central = '';
        $count = 0;

        foreach ($files as $name => $data) {
            $crc = crc32($data);
            $uncompLen = strlen($data);
            if ($canDeflate && $uncompLen > 0) {
                $compressed = gzdeflate($data, 6);
                $method = 8;
            } else {
                $compressed = $data;
                $method = 0;
            }
            $compLen = strlen($compressed);
            $nameLen = strlen($name);
            $offset = strlen($body);

            $body .= pack('VvvvvvVVVvv', 0x04034B50, 20, 0, $method, $dosTime, $dosDate, $crc, $compLen, $uncompLen, $nameLen, 0)
                . $name
                . $compressed;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, $method, $dosTime, $dosDate, $crc, $compLen, $uncompLen, $nameLen, 0, 0, 0, 0, 0, $offset)
                . $name;
            $count++;
        }

        $cdOffset = strlen($body);
        $cdSize = strlen($central);

        return $body . $central . pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, $cdSize, $cdOffset, 0);
    }

    // ---- OOXML package parts ---------------------------------------------------------------------------------------

    private function contentTypes(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $_) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $sheets
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookRels(): string
    {
        $rels = '';
        $n = count($this->sheets);
        foreach ($this->sheets as $i => $_) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $rels .= '<Relationship Id="rId' . ($n + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    /** @param array<int,array{headerRow:int,filterRange:?string}> $sheetMeta */
    private function workbook(array $sheetMeta): string
    {
        $sheets = '';
        $names = '';
        foreach ($this->sheets as $i => $sheet) {
            $sheets .= '<sheet name="' . $this->x($sheet['title']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            $quoted = $this->x("'" . str_replace("'", "''", $sheet['title']) . "'");
            $meta = $sheetMeta[$i];
            if ($meta['filterRange'] !== null) {
                $parts = explode(':', $meta['filterRange']);
                $absRange = '$' . preg_replace('/(\d+)/', '$$1', $parts[0]) . ':$' . preg_replace('/(\d+)/', '$$1', $parts[1]);
                $names .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">' . $quoted . '!' . $absRange . '</definedName>';
            }
            $names .= '<definedName name="_xlnm.Print_Titles" localSheetId="' . $i . '">' . $quoted . '!$' . $meta['headerRow'] . ':$' . $meta['headerRow'] . '</definedName>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<fileVersion appName="xl" lastEdited="6" lowestEdited="6" rupBuild="14420"/>'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000" activeTab="0"/></bookViews>'
            . '<sheets>' . $sheets . '</sheets>'
            . '<definedNames>' . $names . '</definedNames>'
            . '<calcPr calcId="162913" fullCalcOnLoad="1"/>'
            . '</workbook>';
    }

    private function sharedStrings(): string
    {
        $items = '';
        foreach ($this->strings as $s) {
            $items .= '<si><t xml:space="preserve">' . $this->x($s) . '</t></si>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->stringRefs . '" uniqueCount="' . count($this->strings) . '">'
            . $items
            . '</sst>';
    }

    /**
     * Styles (cellXfs indices):
     *   0       default
     *   1       table header (navy, bold white, centred, wrapped)
     *   2       title (bold 15pt, wrapped)
     *   3       note / subtitle (grey 10pt, wrapped)
     *   4..9    data zebra 0 (white)  for types: text, id, int, money, date, pct
     *   10..15  data zebra 1 (tinted) for types: text, id, int, money, date, pct
     *   16..21  totals row (bold, tinted, top/bottom navy borders) for types: text, id, int, money, date, pct
     *   22..29  section bands (8 colours)
     */
    private function styles(): string
    {
        $fontDef = '<name val="Tahoma"/><family val="2"/><charset val="178"/>';
        $fonts = '<fonts count="6">'
            . '<font><sz val="10"/><color rgb="FF0F172A"/>' . $fontDef . '</font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/>' . $fontDef . '</font>'
            . '<font><b/><sz val="15"/><color rgb="FF0F172A"/>' . $fontDef . '</font>'
            . '<font><sz val="10"/><color rgb="FF475569"/>' . $fontDef . '</font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/>' . $fontDef . '</font>'
            . '<font><b/><sz val="10"/><color rgb="FF0F172A"/>' . $fontDef . '</font>'
            . '</fonts>';

        $fillList = [
            0 => '<fill><patternFill patternType="none"/></fill>',
            1 => '<fill><patternFill patternType="gray125"/></fill>',
            2 => '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/><bgColor indexed="64"/></patternFill></fill>',
            3 => '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>',
            4 => '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill>',
        ];
        foreach (self::BAND_COLOURS as $k => $rgb) {
            $fillList[5 + $k] = '<fill><patternFill patternType="solid"><fgColor rgb="' . $rgb . '"/><bgColor indexed="64"/></patternFill></fill>';
        }
        $fills = '<fills count="' . count($fillList) . '">' . implode('', $fillList) . '</fills>';

        $thin = '<color rgb="FFCBD5E1"/>';
        $navy = '<color rgb="FF1E3A5F"/>';
        $borders = '<borders count="3">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin">' . $thin . '</left><right style="thin">' . $thin . '</right><top style="thin">' . $thin . '</top><bottom style="thin">' . $thin . '</bottom><diagonal/></border>'
            . '<border><left style="thin">' . $thin . '</left><right style="thin">' . $thin . '</right><top style="thin">' . $navy . '</top><bottom style="double">' . $navy . '</bottom><diagonal/></border>'
            . '</borders>';

        $numFmts = '<numFmts count="3">'
            . '<numFmt numFmtId="164" formatCode="#,##0.00"/>'
            . '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="166" formatCode="0.00&quot;%&quot;"/>'
            . '</numFmts>';

        $xf = fn(int $numFmt, int $font, int $fill, int $border, string $align) =>
            '<xf numFmtId="' . $numFmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
            . ($numFmt ? ' applyNumberFormat="1"' : '')
            . ($font ? ' applyFont="1"' : '')
            . ($fill ? ' applyFill="1"' : '')
            . ($border ? ' applyBorder="1"' : '')
            . ' applyAlignment="1">' . $align . '</xf>';

        $reading = $this->rtl ? 'right' : 'left';
        $roText = $this->rtl ? '2' : '1';
        $roLtr = '1';

        $wrappedText = '<alignment horizontal="' . $reading . '" vertical="center" wrapText="1" readingOrder="' . $roText . '"/>';
        $idCenter = '<alignment horizontal="center" vertical="center" wrapText="1" readingOrder="' . $roLtr . '"/>';
        $numCenter = '<alignment horizontal="center" vertical="center" readingOrder="' . $roLtr . '"/>';

        $xfs = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            $xf(0, 1, 2, 1, '<alignment horizontal="center" vertical="center" wrapText="1" readingOrder="' . $roText . '"/>'),   // 1 header
            $xf(0, 2, 0, 0, '<alignment horizontal="' . $reading . '" vertical="center" wrapText="1" readingOrder="' . $roText . '"/>'), // 2 title
            $xf(0, 3, 0, 0, '<alignment horizontal="' . $reading . '" vertical="center" wrapText="1" readingOrder="' . $roText . '"/>'), // 3 note/subtitle
        ];

        // 4..15: zebra 0 (fill 0) and zebra 1 (fill 3)
        foreach ([0, 3] as $fill) {
            $xfs[] = $xf(0, 0, $fill, 1, $wrappedText);  // text
            $xfs[] = $xf(49, 0, $fill, 1, $idCenter);    // id ("@")
            $xfs[] = $xf(3, 0, $fill, 1, $numCenter);    // int ("#,##0")
            $xfs[] = $xf(164, 0, $fill, 1, $numCenter);  // money
            $xfs[] = $xf(165, 0, $fill, 1, $numCenter);  // date
            $xfs[] = $xf(166, 0, $fill, 1, $numCenter);  // pct
        }

        // 16..21: totals row (font 5 bold, fill 4, border 2)
        $xfs[] = $xf(0, 5, 4, 2, $wrappedText);          // text
        $xfs[] = $xf(49, 5, 4, 2, $idCenter);            // id
        $xfs[] = $xf(3, 5, 4, 2, $numCenter);            // int
        $xfs[] = $xf(164, 5, 4, 2, $numCenter);          // money
        $xfs[] = $xf(165, 5, 4, 2, $numCenter);          // date
        $xfs[] = $xf(166, 5, 4, 2, $numCenter);          // pct

        // 22..29: section bands
        foreach (self::BAND_COLOURS as $k => $_) {
            $xfs[] = $xf(0, 4, 5 + $k, 0, '<alignment horizontal="' . $reading . '" vertical="center" indent="1" wrapText="1" readingOrder="' . $roText . '"/>');
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmts . $fonts . $fills . $borders
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function headerRow(array $sheet): int
    {
        return count($sheet['titleLines']) + ($sheet['titleLines'] ? 2 : 1);
    }

    /**
     * Computes clean column widths based on configured minimums, header word lengths, filter arrow space,
     * and formatted cell contents, clamped to keep wide text wrapping nicely.
     *
     * @return float[]
     */
    private function computeColumnWidths(array $sheet, bool $hasFilter): array
    {
        $widths = [];
        $ncols = count($sheet['columns']);

        foreach ($sheet['columns'] as $i => $col) {
            $header = (string) ($col[0] ?? '');
            $minW = (float) ($col[1] ?? 14);
            $defaultType = (string) ($col[2] ?? self::TEXT);

            $headerWords = preg_split('/\s+/u', trim($header)) ?: [$header];
            $maxWord = 0;
            foreach ($headerWords as $word) {
                $maxWord = max($maxWord, mb_strlen($word));
            }
            $headerLen = mb_strlen($header);
            $arrowPad = $hasFilter ? 4.5 : 2.0;

            // Ensure single words never split mid-word, and short headers sit comfortably next to the filter arrow
            $w = max($minW, $maxWord + $arrowPad + 1.5);
            if ($headerLen <= 20) {
                $w = max($w, $headerLen * 1.08 + $arrowPad);
            } else {
                $w = max($w, min(26.0, ceil($headerLen * 0.68) + $arrowPad));
            }

            if ($defaultType === self::DATE) {
                $w = max($w, $hasFilter ? 15.5 : 14.0);
            } elseif ($defaultType === self::PCT) {
                $w = max($w, $hasFilter ? 13.5 : 11.5);
            }

            $widths[$i] = $w;
        }

        foreach ($sheet['rows'] as $item) {
            if ($item['kind'] !== 'row' && $item['kind'] !== 'totals') {
                continue;
            }
            for ($i = 0; $i < $ncols; $i++) {
                $colDef = $sheet['columns'][$i];
                $defaultType = (string) ($colDef[2] ?? self::TEXT);
                $cell = $item['cells'][$i] ?? null;

                if (is_array($cell) && array_key_exists('formula', $cell)) {
                    $val = $cell['value'] ?? null;
                    $type = (string) ($cell['type'] ?? $defaultType);
                } elseif (is_array($cell)) {
                    $val = $cell[0] ?? null;
                    $type = (string) ($cell[1] ?? $defaultType);
                } else {
                    $val = $cell;
                    $type = $defaultType;
                }

                if ($val === null || $val === '' || $val === false) {
                    continue;
                }

                $displayLen = $this->estimateDisplayWidth($val, $type);
                if (in_array($type, [self::INT, self::MONEY, self::DATE, self::PCT], true)) {
                    $widths[$i] = max($widths[$i], $displayLen + 4.0);
                } elseif ($type === self::ID) {
                    $widths[$i] = max($widths[$i], min(38.0, $displayLen + 4.0));
                } else {
                    // Text columns expand up to a readable cap (at least the configured min width) and wrap beyond that
                    $cap = max((float) ($colDef[1] ?? 14), 56.0);
                    $widths[$i] = max($widths[$i], min($cap, $displayLen * 1.05 + 3.5));
                }
            }
        }

        return array_map(fn($w) => round(min(68.0, max(10.0, $w)), 2), $widths);
    }

    private function estimateDisplayWidth($val, string $type): float
    {
        if (in_array($type, [self::INT, self::MONEY, self::PCT], true) && is_numeric($val)) {
            $f = (float) $val;
            $formatted = match ($type) {
                self::INT => number_format((int) round($f)),
                self::MONEY => number_format($f, 2),
                self::PCT => number_format($f, 2) . '%',
            };
            return (float) mb_strlen($formatted);
        }
        if ($type === self::DATE && $this->parseDateSerial((string) $val) !== null) {
            return 11.0; // dd/mm/yyyy
        }
        $maxLine = 0;
        foreach (explode("\n", (string) $val) as $line) {
            $maxLine = max($maxLine, mb_strlen(trim($line)));
        }
        return (float) $maxLine;
    }

    private function wrappedLines(string $text, float $colWidth): int
    {
        if ($text === '') {
            return 1;
        }
        $charsPerLine = max(8, (int) floor(($colWidth - 1.5) * 0.94));
        $total = 0;
        foreach (explode("\n", str_replace("\r\n", "\n", $text)) as $paragraph) {
            $len = mb_strlen(trim($paragraph));
            $total += max(1, (int) ceil($len / $charsPerLine));
        }
        return max(1, $total);
    }

    /** @return array{0:string,1:array{headerRow:int,filterRange:?string}} */
    private function sheetXml(array $sheet, bool $isFirst): array
    {
        $ncols = count($sheet['columns']);
        $lastCol = self::col($ncols);
        $header = $this->headerRow($sheet);

        // Count actual data rows and track the last contiguous data row for AutoFilter
        $hasDataRows = false;
        $lastFilterRow = $header;
        $hasSeenTotalsOrNote = false;
        $rTmp = $header;
        foreach ($sheet['rows'] as $item) {
            $rTmp++;
            if ($item['kind'] === 'row') {
                $hasDataRows = true;
                if (!$hasSeenTotalsOrNote) {
                    $lastFilterRow = $rTmp;
                }
            } else {
                $hasSeenTotalsOrNote = true;
            }
        }
        $useFilter = $sheet['filter'] && $hasDataRows && $lastFilterRow > $header;
        $filterRange = $useFilter ? ('A' . $header . ':' . $lastCol . $lastFilterRow) : null;

        $widths = $this->computeColumnWidths($sheet, $useFilter);
        $totalWidth = array_sum($widths);

        $cols = '';
        foreach ($widths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . number_format($w, 2, '.', '') . '" customWidth="1"/>';
        }

        $out = '';
        $merges = [];

        // Title and subtitle lines
        foreach ($sheet['titleLines'] as $i => $line) {
            $r = $i + 1;
            $style = $i === 0 ? 2 : 3;
            $lines = $i === 0
                ? $this->wrappedLines($line, max(20.0, $totalWidth * 0.68))
                : $this->wrappedLines($line, max(24.0, $totalWidth * 0.90));
            $ht = $i === 0 ? max(30, 12 + $lines * 20) : max(20, 6 + $lines * 16);

            $out .= '<row r="' . $r . '" ht="' . $ht . '" customHeight="1">' . $this->textCell('A' . $r, $line, $style);
            for ($c = 2; $c <= $ncols; $c++) {
                $out .= '<c r="' . self::col($c) . $r . '" s="' . $style . '"/>';
            }
            $out .= '</row>';
            if ($ncols > 1) {
                $merges[] = 'A' . $r . ':' . $lastCol . $r;
            }
        }

        // Table header row
        $headerLines = 1;
        foreach ($sheet['columns'] as $i => [$h]) {
            $avail = max(8.0, $widths[$i] - ($useFilter ? 4.0 : 1.5));
            $headerLines = max($headerLines, $this->wrappedLines((string) $h, $avail));
        }
        $headerHt = max(32, 12 + $headerLines * 17);
        $out .= '<row r="' . $header . '" ht="' . $headerHt . '" customHeight="1">';
        foreach ($sheet['columns'] as $i => [$h]) {
            $out .= $this->textCell(self::col($i + 1) . $header, (string) $h, 1);
        }
        $out .= '</row>';

        // Data, section, note, and totals rows
        $r = $header;
        $zebra = 0;
        foreach ($sheet['rows'] as $item) {
            $r++;
            if ($item['kind'] === 'section') {
                $style = 22 + (int) $item['colour'];
                $lines = $this->wrappedLines((string) $item['title'], max(24.0, $totalWidth * 0.85));
                $ht = max(26, 10 + $lines * 17);
                $out .= '<row r="' . $r . '" ht="' . $ht . '" customHeight="1">' . $this->textCell('A' . $r, (string) $item['title'], $style);
                for ($c = 2; $c <= $ncols; $c++) {
                    $out .= '<c r="' . self::col($c) . $r . '" s="' . $style . '"/>';
                }
                $out .= '</row>';
                if ($ncols > 1) {
                    $merges[] = 'A' . $r . ':' . $lastCol . $r;
                }
                $zebra = 0;
            } elseif ($item['kind'] === 'note') {
                $lines = $this->wrappedLines((string) $item['text'], max(24.0, $totalWidth * 0.90));
                $ht = max(24, 8 + $lines * 16);
                $out .= '<row r="' . $r . '" ht="' . $ht . '" customHeight="1">' . $this->textCell('A' . $r, (string) $item['text'], 3);
                for ($c = 2; $c <= $ncols; $c++) {
                    $out .= '<c r="' . self::col($c) . $r . '" s="3"/>';
                }
                $out .= '</row>';
                if ($ncols > 1) {
                    $merges[] = 'A' . $r . ':' . $lastCol . $r;
                }
            } elseif ($item['kind'] === 'totals') {
                $rowCells = '';
                $maxLines = 1;
                foreach ($sheet['columns'] as $i => [, , $defaultType]) {
                    $cell = $item['cells'][$i] ?? null;
                    $ref = self::col($i + 1) . $r;
                    if (is_array($cell) && array_key_exists('formula', $cell)) {
                        $type = (string) ($cell['type'] ?? $defaultType);
                        $style = 16 + max(0, (int) array_search($type, self::TYPES, true));
                        $rowCells .= $this->formulaCell($ref, (string) $cell['formula'], $cell['value'] ?? null, $style);
                    } else {
                        [$value, $type] = is_array($cell) ? [$cell[0] ?? null, $cell[1] ?? $defaultType] : [$cell, $defaultType];
                        $style = 16 + max(0, (int) array_search($type, self::TYPES, true));
                        if ($value !== null && $value !== '' && in_array($type, [self::TEXT, self::ID], true)) {
                            $maxLines = max($maxLines, $this->wrappedLines((string) $value, $widths[$i]));
                        }
                        $rowCells .= $this->dataCell($ref, $value, (string) $type, $style);
                    }
                }
                $ht = max(26, 10 + $maxLines * 16);
                $out .= '<row r="' . $r . '" ht="' . $ht . '" customHeight="1">' . $rowCells . '</row>';
            } else {
                $rowCells = '';
                $maxLines = 1;
                foreach ($sheet['columns'] as $i => [, , $defaultType]) {
                    $cell = $item['cells'][$i] ?? null;
                    $ref = self::col($i + 1) . $r;
                    if (is_array($cell) && array_key_exists('formula', $cell)) {
                        $type = (string) ($cell['type'] ?? $defaultType);
                        $style = 4 + $zebra * 6 + max(0, (int) array_search($type, self::TYPES, true));
                        $rowCells .= $this->formulaCell($ref, (string) $cell['formula'], $cell['value'] ?? null, $style);
                    } else {
                        [$value, $type] = is_array($cell) ? [$cell[0] ?? null, $cell[1] ?? $defaultType] : [$cell, $defaultType];
                        $style = 4 + $zebra * 6 + max(0, (int) array_search($type, self::TYPES, true));
                        if ($value !== null && $value !== '' && ($type === self::TEXT || ($type === self::ID && mb_strlen((string) $value) > $widths[$i] * 0.90))) {
                            $maxLines = max($maxLines, $this->wrappedLines((string) $value, $widths[$i]));
                        }
                        $rowCells .= $this->dataCell($ref, $value, (string) $type, $style);
                    }
                }
                $ht = max(22, 8 + $maxLines * 16);
                $out .= '<row r="' . $r . '" ht="' . $ht . '" customHeight="1">' . $rowCells . '</row>';
                $zebra = 1 - $zebra;
            }
        }

        $lastRow = max($header, $r);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:' . $lastCol . $lastRow . '"/>'
            . '<sheetViews><sheetView ' . ($isFirst ? 'tabSelected="1" ' : '') . ($this->rtl ? 'rightToLeft="1" ' : '') . 'workbookViewId="0" showGridLines="1">'
            . '<pane ySplit="' . $header . '" topLeftCell="A' . ($header + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A' . ($header + 1) . '" sqref="A' . ($header + 1) . '"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="22"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $out . '</sheetData>'
            . ($filterRange !== null ? '<autoFilter ref="' . $filterRange . '"/>' : '')
            . ($merges ? '<mergeCells count="' . count($merges) . '">' . implode('', array_map(fn($m) => '<mergeCell ref="' . $m . '"/>', $merges)) . '</mergeCells>' : '')
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';

        return [$xml, ['headerRow' => $header, 'filterRange' => $filterRange]];
    }

    private function dataCell(string $ref, $value, string $type, int $style): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '<c r="' . $ref . '" s="' . $style . '"/>';
        }
        switch ($type) {
            case self::INT:
            case self::MONEY:
            case self::PCT:
                if (!is_numeric($value)) {
                    return $this->textCell($ref, (string) $value, $style);
                }
                return '<c r="' . $ref . '" s="' . $style . '"><v>' . $this->num($value) . '</v></c>';
            case self::DATE:
                $serial = $this->parseDateSerial((string) $value);
                if ($serial === null) {
                    return $this->textCell($ref, (string) $value, $style);
                }
                return '<c r="' . $ref . '" s="' . $style . '"><v>' . $serial . '</v></c>';
            default:
                return $this->textCell($ref, (string) $value, $style);
        }
    }

    private function formulaCell(string $ref, string $formula, $cachedValue, int $style): string
    {
        $vTag = ($cachedValue !== null && is_numeric($cachedValue)) ? '<v>' . $this->num($cachedValue) . '</v>' : '';
        return '<c r="' . $ref . '" s="' . $style . '"><f>' . $this->x($formula) . '</f>' . $vTag . '</c>';
    }

    private function textCell(string $ref, string $text, int $style): string
    {
        $clean = $this->sanitizeXml($text);
        if (!isset($this->stringMap[$clean])) {
            $this->stringMap[$clean] = count($this->strings);
            $this->strings[] = $clean;
        }
        $this->stringRefs++;
        return '<c r="' . $ref . '" s="' . $style . '" t="s"><v>' . $this->stringMap[$clean] . '</v></c>';
    }

    /** Parses Y-m-d / Y-m-d H:i:s / d/m/Y safely without US m/d/Y ambiguity. */
    private function parseDateSerial(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '0000-00-00' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
        } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $raw, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
        } else {
            $ts = strtotime(substr($raw, 0, 10) . ' 00:00:00 UTC');
            return $ts === false ? null : (int) round($ts / 86400) + 25569;
        }
        if (!checkdate($mo, $d, $y)) {
            return null;
        }
        $ts = gmmktime(0, 0, 0, $mo, $d, $y);
        return (int) round($ts / 86400) + 25569;
    }

    private function num($v): string
    {
        $f = (float) $v;
        return $f == floor($f) && abs($f) < 1e15 ? (string) (int) $f : rtrim(rtrim(number_format($f, 10, '.', ''), '0'), '.');
    }

    private function sanitizeXml(string $s): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{FFFE}\x{FFFF}]/u', '', $s) ?? '';
    }

    /** XML-safe text: control characters removed and XML entities escaped. */
    private function x(string $s): string
    {
        return htmlspecialchars($this->sanitizeXml($s), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
