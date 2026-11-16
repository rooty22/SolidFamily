<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Transaction;

class PaymentsController extends Controller
{
    private function getCategoryLabels(): array
    {
        $cats = transaction_categories();
        unset($cats['loan_disbursement']);
        return $cats;
    }

    private function paymentFilters(): array
    {
        $filters = [
            'category' => $this->input('category', ''),
            'search' => $this->input('search', ''),
            'from' => $this->input('from', ''),
            'to' => $this->input('to', ''),
        ];

        $rows = Transaction::withMember($filters);
        return array_values(array_filter($rows, fn($r) => $r['category'] !== 'loan_disbursement'));
    }

    public function index(): void
    {
        $pager = paginate($this->paymentFilters());

        $this->view('admin/payments/index', [
            'pageTitle' => __('payments'),
            'payments' => $pager['items'],
            'pager' => $pager,
            'categoryLabels' => $this->getCategoryLabels(),
            'filters' => $this->all(),
        ], 'admin/layout');
    }

    public function export(): void
    {
        $payments = $this->paymentFilters();
        $labels = $this->getCategoryLabels();
        $ar = is_rtl();
        $format = strtolower(trim((string) $this->input('format', '')));

        if ($format === 'csv') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=payments-' . date('Y-m-d') . '.csv');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            $headers = [$ar ? 'الرقم التسلسلي' : 'Serial', __('member'), __('transaction_category'), $ar ? 'البيان / التفاصيل' : 'Details', __('amount'), __('date'), __('admin_user')];
            csv_put($out, $headers);
            foreach ($payments as $p) {
                csv_put($out, [
                    $p['member_serial'] ?? '',
                    $p['member_name'],
                    $labels[$p['category']] ?? $p['category'],
                    localize_dates($p['display_details'] ?? '-'),
                    $p['amount'],
                    date_ar($p['transaction_date']),
                    $p['admin_name'] ?? '-',
                ]);
            }
            fclose($out);
            exit;
        }

        $X = \App\Core\XlsxReport::class;
        $report = new \App\Core\XlsxReport($ar);

        $notes = [];
        $search = trim((string) $this->input('search', ''));
        $cat = trim((string) $this->input('category', ''));
        $from = trim((string) $this->input('from', ''));
        $to = trim((string) $this->input('to', ''));
        if ($search !== '') {
            $notes[] = ($ar ? 'بحث: ' : 'Search: ') . $search;
        }
        if ($cat !== '') {
            $notes[] = ($ar ? 'النوع: ' : 'Type: ') . ($labels[$cat] ?? $cat);
        }
        if ($from !== '') {
            $notes[] = ($ar ? 'من: ' : 'From: ') . date_ar($from);
        }
        if ($to !== '') {
            $notes[] = ($ar ? 'إلى: ' : 'To: ') . date_ar($to);
        }

        $titleLines = [
            ($ar ? 'سجل المدفوعات - ' : 'Payments Log - ') . site_name(),
            ($ar ? 'تاريخ التقرير: ' : 'Report date: ') . date('d/m/Y H:i') . ' · ' . ($ar ? 'عدد العمليات: ' : 'Payments: ') . count($payments)
                . ($notes ? ' · ' . implode(' · ', $notes) : ''),
        ];

        $report->sheet($ar ? 'المدفوعات' : 'Payments', [
            [$ar ? 'الرقم التسلسلي' : 'Serial', 13, $X::INT],
            [$ar ? 'اسم المشترك' : 'Member', 28, $X::TEXT],
            [$ar ? 'نوع العملية' : 'Payment type', 20, $X::TEXT],
            [$ar ? 'البيان / التفاصيل' : 'Details', 34, $X::TEXT],
            [$ar ? 'المبلغ (ريال)' : 'Amount (SAR)', 18, $X::MONEY],
            [$ar ? 'التاريخ' : 'Date', 15, $X::DATE],
            [$ar ? 'المستخدم' : 'Recorded by', 20, $X::TEXT],
        ], $titleLines, true);

        $byCategory = [];
        $totalAmount = 0.0;
        foreach ($payments as $p) {
            $amt = (float) $p['amount'];
            $totalAmount += $amt;
            $c = (string) $p['category'];
            $byCategory[$c] = $byCategory[$c] ?? ['count' => 0, 'amount' => 0.0];
            $byCategory[$c]['count']++;
            $byCategory[$c]['amount'] += $amt;

            $report->row([
                $p['member_serial'] !== null && $p['member_serial'] !== '' ? (int) $p['member_serial'] : null,
                $p['member_name'],
                $labels[$c] ?? $c,
                localize_dates($p['display_details'] ?? '-'),
                round($amt, 2),
                $p['transaction_date'],
                $p['admin_name'] ?? '-',
            ]);
        }

        if ($payments) {
            $report->sumTotals($ar ? 'الإجمالي' : 'Total', [4], 1, 0, count($payments) . ' ' . ($ar ? 'عملية' : 'items'));
        } else {
            $report->note($ar ? 'لا توجد مدفوعات مطابقة للفلاتر الحالية' : 'No payments match the current filters.');
        }

        // Second sheet: summary grouped by payment type
        $report->sheet($ar ? 'ملخص المدفوعات' : 'Payments Summary', [
            [$ar ? 'نوع العملية' : 'Payment type', 28, $X::TEXT],
            [$ar ? 'عدد العمليات' : 'Operations', 16, $X::INT],
            [$ar ? 'الإجمالي (ريال)' : 'Total (SAR)', 20, $X::MONEY],
            [$ar ? 'النسبة' : 'Share %', 14, $X::PCT],
        ], [
            ($ar ? 'ملخص المدفوعات حسب النوع - ' : 'Payments Summary by Type - ') . site_name(),
            $titleLines[1],
        ]);
        if ($byCategory) {
            uasort($byCategory, fn($a, $b) => $b['amount'] <=> $a['amount']);
            foreach ($byCategory as $catKey => $info) {
                $pct = $totalAmount > 0 ? round($info['amount'] / $totalAmount * 100, 2) : 0.0;
                $report->row([$labels[$catKey] ?? $catKey, $info['count'], round($info['amount'], 2), $pct]);
            }
            $r1 = 5;
            $r2 = 4 + count($byCategory);
            $report->totals([
                [$ar ? 'الإجمالي' : 'Total', $X::TEXT],
                ['formula' => "SUM(B{$r1}:B{$r2})", 'value' => count($payments), 'type' => $X::INT],
                ['formula' => "SUM(C{$r1}:C{$r2})", 'value' => round($totalAmount, 2), 'type' => $X::MONEY],
                ['formula' => "SUM(D{$r1}:D{$r2})", 'value' => 100.0, 'type' => $X::PCT],
            ]);
        } else {
            $report->note($ar ? 'لا توجد بيانات' : 'No data');
        }

        $report->download('payments-' . date('Y-m-d') . '.xlsx');
    }

    /** Printable version of the payments list (browser "Save as PDF"). Same filters as the list and the CSV. */
    public function printReport(): void
    {
        $labels = $this->getCategoryLabels();
        $rows = [];
        foreach ($this->paymentFilters() as $p) {
            $rows[] = [$p['member_serial'] ?? '-', $p['member_name'], $labels[$p['category']] ?? $p['category'], $p['amount'], date_ar($p['transaction_date']), $p['admin_name'] ?? '-'];
        }
        $this->view('admin/reports/print', [
            'title' => __('payments'),
            'headers' => [is_rtl() ? 'الرقم التسلسلي' : 'Serial', __('member'), __('transaction_category'), __('amount'), __('date'), __('admin_user')],
            'rows' => $rows,
        ]);
    }
}
