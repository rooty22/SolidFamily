<?php

namespace App\Core;

/**
 * Single-sheet Excel table writer (.xlsx) backed by XlsxReport so every table export across the platform
 * shares the same clean styling, shared strings, RTL/LTR reading order, auto-fitted widths and row heights,
 * frozen header, AutoFilter, optional totals row, and pure-PHP ZIP fallback when ZipArchive is missing.
 *
 *   $x = new XlsxWriter('المشتركون', [['الاسم', 28, XlsxWriter::TEXT], ['الجوال', 16, XlsxWriter::ID]]);
 *   $x->setTitleLines(['Title', 'Report date ...']);
 *   $x->addRow(['علي', '0512345678']);
 *   $x->setSumTotals('الإجمالي', [2]);
 *   $x->download('members.xlsx');
 */
class XlsxWriter
{
    public const TEXT = XlsxReport::TEXT;
    public const ID = XlsxReport::ID;
    public const INT = XlsxReport::INT;
    public const MONEY = XlsxReport::MONEY;
    public const DATE = XlsxReport::DATE;
    public const PCT = XlsxReport::PCT;

    /** @var array<int,array{0:string,1:float|int,2:string}> */
    private array $columns;
    private string $sheetTitle;
    private bool $rtl;
    /** @var string[] */
    private array $titleLines = [];
    /** @var array<int,array<int,mixed>> */
    private array $rows = [];
    /** @var array{label:string,sumCols:int[],labelCol:int,countCol:?int,countText:?string}|null */
    private ?array $sumTotals = null;
    /** @var array<int,mixed>|null */
    private ?array $customTotals = null;

    /** @param array<int,array{0:string,1:float|int,2:string}> $columns [header, min width in chars, type] */
    public function __construct(string $sheetTitle, array $columns, bool $rtl = true)
    {
        $this->sheetTitle = $sheetTitle;
        $this->columns = array_values($columns);
        $this->rtl = $rtl;
    }

    public static function available(): bool
    {
        return XlsxReport::available();
    }

    /** Lines above the table: the first one is the title, the others are small grey notes/filters. */
    public function setTitleLines(array $lines): void
    {
        $this->titleLines = array_values(array_map('strval', $lines));
    }

    /** One row of values in the order of the columns (null / '' leave the cell empty). */
    public function addRow(array $values): void
    {
        $this->rows[] = array_values($values);
    }

    /** Configures an automatic Totals row at the bottom summing the given 0-based column indices. */
    public function setSumTotals(string $label, array $sumCols, int $labelCol = 0, ?int $countCol = null, ?string $countText = null): self
    {
        $this->sumTotals = compact('label', 'sumCols', 'labelCol', 'countCol', 'countText');
        return $this;
    }

    /** Sets an explicit Totals row at the bottom. */
    public function setTotalsRow(array $cells): self
    {
        $this->customTotals = array_values($cells);
        return $this;
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }

    public function save(string $path): void
    {
        $this->buildReport()->save($path);
    }

    /** Sends the file to the browser as a download and stops. */
    public function download(string $filename): void
    {
        $this->buildReport()->download($filename);
    }

    private function buildReport(): XlsxReport
    {
        $report = new XlsxReport($this->rtl);
        $report->sheet($this->sheetTitle, $this->columns, $this->titleLines, true);
        foreach ($this->rows as $row) {
            $report->row($row);
        }
        if ($this->rows) {
            if ($this->sumTotals !== null) {
                $report->sumTotals(
                    $this->sumTotals['label'],
                    $this->sumTotals['sumCols'],
                    $this->sumTotals['labelCol'],
                    $this->sumTotals['countCol'],
                    $this->sumTotals['countText']
                );
            } elseif ($this->customTotals !== null) {
                $report->totals($this->customTotals);
            }
        } else {
            $report->note($this->rtl ? 'لا توجد سجلات مطابقة' : 'No matching records');
        }
        return $report;
    }
}
