<?php

namespace App\Core;

/**
 * A small writer of real Excel files (.xlsx), with no library: one sheet with a title block, a styled header row that
 * repeats on every printed page, typed cells (text, text that must stay text such as phone numbers and IDs, integers,
 * money, dates), column widths, zebra rows, a frozen header, a filter on every column, right-to-left reading order and a
 * landscape page that fits the width. Values are written exactly as given: nothing is guessed by Excel (a national ID with
 * a leading zero stays "0123...", a long number is never shown as 1.2E+11, a date is a real date).
 *
 *   $x = new XlsxWriter('المشتركون', [['الاسم', 28, XlsxWriter::TEXT], ['الجوال', 16, XlsxWriter::ID], ...]);
 *   $x->setTitleLines(['Title', 'Report date ...']);
 *   $x->addRow(['علي', '0512345678', ...]);
 *   $x->download('members.xlsx');
 *
 * Needs PHP's zip extension (ZipArchive): check XlsxWriter::available() and fall back to CSV when it is not there.
 */
class XlsxWriter
{
    public const TEXT = 'text';     // wrapped text, aligned to the reading side
    public const ID = 'id';         // text that must stay text (phone, national ID, IBAN, e-mail), centred
    public const INT = 'int';       // whole number
    public const MONEY = 'money';   // number with 2 decimals and thousands separators
    public const DATE = 'date';     // a real Excel date shown as dd/mm/yyyy

    private const TYPES = [self::TEXT, self::ID, self::INT, self::MONEY, self::DATE];

    /** @var array<int,array{0:string,1:float,2:string}> */
    private array $columns;
    private string $sheetTitle;
    private bool $rtl;
    /** @var string[] */
    private array $titleLines = [];
    /** @var array<int,array<int,mixed>> */
    private array $rows = [];

    /** @param array<int,array{0:string,1:float|int,2:string}> $columns [header, width in characters, type] */
    public function __construct(string $sheetTitle, array $columns, bool $rtl = true)
    {
        // a sheet name: at most 31 characters, none of  [ ] : * ? / \
        $this->sheetTitle = mb_substr(trim(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $sheetTitle)), 0, 31) ?: 'Sheet1';
        $this->columns = array_values($columns);
        $this->rtl = $rtl;
    }

    public static function available(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /** Lines above the table: the first one is the title, the others are small grey notes. */
    public function setTitleLines(array $lines): void
    {
        $this->titleLines = array_values(array_map('strval', $lines));
    }

    /** One row of values in the order of the columns (null / '' leave the cell empty). */
    public function addRow(array $values): void
    {
        $this->rows[] = array_values($values);
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }

    // ---------------------------------------------------------------------------------------------------------------
    public function save(string $path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create the Excel file.');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->close();
    }

    /** Sends the file to the browser as a download and stops. */
    public function download(string $filename): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        try {
            $this->save($tmp);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($tmp);
        } finally {
            @unlink($tmp);
        }
        exit;
    }

    // ---- the parts of the package ----------------------------------------------------------------------------------
    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        $name = $this->x($this->sheetTitle);
        $quoted = "'" . str_replace("'", "''", $this->sheetTitle) . "'";
        $headerRow = $this->headerRow();
        $lastCol = $this->col(count($this->columns));
        $lastRow = max($headerRow, $headerRow + count($this->rows));
        $names = '<definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">' . $this->x($quoted) . '!$A$' . $headerRow . ':$' . $lastCol . '$' . $lastRow . '</definedName>'
            . '<definedName name="_xlnm.Print_Titles" localSheetId="0">' . $this->x($quoted) . '!$' . $headerRow . ':$' . $headerRow . '</definedName>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews>'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames>' . $names . '</definedNames>'
            . '</workbook>';
    }

    /**
     * Styles. cellXfs index: 0 default, 1 header, 2 title, 3 note, then the data cells:
     * 4 + zebra * 5 + type (text, id, int, money, date), zebra 0 = white row, 1 = grey row.
     */
    private function styles(): string
    {
        $font = '<name val="Tahoma"/><family val="2"/>';
        $fonts = '<fonts count="4">'
            . '<font><sz val="10"/>' . $font . '</font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/>' . $font . '</font>'
            . '<font><b/><sz val="15"/><color rgb="FF0F172A"/>' . $font . '</font>'
            . '<font><sz val="10"/><color rgb="FF64748B"/>' . $font . '</font>'
            . '</fonts>';
        $fills = '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>';
        $side = '<color rgb="FFCBD5E1"/>';
        $borders = '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin">' . $side . '</left><right style="thin">' . $side . '</right><top style="thin">' . $side . '</top><bottom style="thin">' . $side . '</bottom><diagonal/></border>'
            . '</borders>';
        $numFmts = '<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/></numFmts>';

        $xf = fn(int $numFmt, int $font, int $fill, int $border, string $align) => '<xf numFmtId="' . $numFmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
            . ($numFmt ? ' applyNumberFormat="1"' : '') . ($font ? ' applyFont="1"' : '') . ($fill ? ' applyFill="1"' : '') . ($border ? ' applyBorder="1"' : '') . ' applyAlignment="1">' . $align . '</xf>';
        $center = '<alignment horizontal="center" vertical="center"/>';
        $side = '<alignment horizontal="' . ($this->rtl ? 'right' : 'left') . '" vertical="center" wrapText="1"/>';
        $xfs = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            $xf(0, 1, 2, 1, '<alignment horizontal="center" vertical="center" wrapText="1"/>'),   // 1 header
            $xf(0, 2, 0, 0, '<alignment horizontal="' . ($this->rtl ? 'right' : 'left') . '" vertical="center"/>'),   // 2 title
            $xf(0, 3, 0, 0, '<alignment horizontal="' . ($this->rtl ? 'right' : 'left') . '" vertical="center"/>'),   // 3 note
        ];
        foreach ([0, 3] as $fill) {
            $xfs[] = $xf(0, 0, $fill, 1, $side);      // text
            $xfs[] = $xf(49, 0, $fill, 1, $center);   // id  (the text format "@")
            $xfs[] = $xf(1, 0, $fill, 1, $center);    // int ("0")
            $xfs[] = $xf(164, 0, $fill, 1, $center);  // money
            $xfs[] = $xf(165, 0, $fill, 1, $center);  // date
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmts . $fonts . $fills . $borders
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function headerRow(): int
    {
        return count($this->titleLines) + ($this->titleLines ? 2 : 1);   // a blank row between the title block and the table
    }

    private function sheet(): string
    {
        $ncols = count($this->columns);
        $lastCol = $this->col($ncols);
        $header = $this->headerRow();
        $first = $header + 1;
        $last = $header + count($this->rows);

        $cols = '';
        foreach ($this->columns as $i => [$h, $w]) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . number_format((float) $w, 2, '.', '') . '" customWidth="1"/>';
        }

        $out = '';
        // the title block
        foreach ($this->titleLines as $i => $line) {
            $r = $i + 1;
            $style = $i === 0 ? 2 : 3;
            $out .= '<row r="' . $r . '"' . ($i === 0 ? ' ht="30" customHeight="1"' : ' ht="18" customHeight="1"') . '>' . $this->textCell('A' . $r, $line, $style) . '</row>';
        }
        // the header
        $out .= '<row r="' . $header . '" ht="30" customHeight="1">';
        foreach ($this->columns as $i => [$h]) {
            $out .= $this->textCell($this->col($i + 1) . $header, (string) $h, 1);
        }
        $out .= '</row>';
        // the data
        foreach ($this->rows as $n => $row) {
            $r = $first + $n;
            $zebra = $n % 2 === 1 ? 1 : 0;
            $out .= '<row r="' . $r . '">';
            foreach ($this->columns as $i => [, , $type]) {
                $ref = $this->col($i + 1) . $r;
                $style = 4 + $zebra * 5 + max(0, array_search($type, self::TYPES, true));
                $out .= $this->dataCell($ref, $row[$i] ?? null, $type, $style);
            }
            $out .= '</row>';
        }

        $merges = '';
        $mergeCount = 0;
        if ($ncols > 1) {
            foreach ($this->titleLines as $i => $_) {
                $merges .= '<mergeCell ref="A' . ($i + 1) . ':' . $lastCol . ($i + 1) . '"/>';
                $mergeCount++;
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:' . $lastCol . max($header, $last) . '"/>'
            . '<sheetViews><sheetView ' . ($this->rtl ? 'rightToLeft="1" ' : '') . 'workbookViewId="0" showGridLines="0">'
            . '<pane ySplit="' . $header . '" topLeftCell="A' . ($header + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A' . ($header + 1) . '" sqref="A' . ($header + 1) . '"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $out . '</sheetData>'
            . '<autoFilter ref="A' . $header . ':' . $lastCol . max($header, $last) . '"/>'
            . ($mergeCount ? '<mergeCells count="' . $mergeCount . '">' . $merges . '</mergeCells>' : '')
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    private function dataCell(string $ref, $value, string $type, int $style): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '<c r="' . $ref . '" s="' . $style . '"/>';
        }
        switch ($type) {
            case self::INT:
            case self::MONEY:
                if (!is_numeric($value)) {
                    return $this->textCell($ref, (string) $value, $style);
                }
                return '<c r="' . $ref . '" s="' . $style . '"><v>' . $this->num($value) . '</v></c>';
            case self::DATE:
                $ts = strtotime(substr((string) $value, 0, 10) . ' 00:00:00 UTC');
                if ($ts === false) {
                    return $this->textCell($ref, (string) $value, $style);
                }
                return '<c r="' . $ref . '" s="' . $style . '"><v>' . (int) ($ts / 86400 + 25569) . '</v></c>';
            default:
                return $this->textCell($ref, (string) $value, $style);
        }
    }

    private function textCell(string $ref, string $text, int $style): string
    {
        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->x($text) . '</t></is></c>';
    }

    private function num($v): string
    {
        $f = (float) $v;
        return $f == floor($f) && abs($f) < 1e15 ? (string) (int) $f : rtrim(rtrim(number_format($f, 10, '.', ''), '0'), '.');
    }

    /** Column letters: 1 => A, 27 => AA. */
    private function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + $n % 26) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    /** XML-safe text: markup escaped and the control characters XML cannot hold removed. */
    private function x(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{FFFE}\x{FFFF}]/u', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
