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
        $payments = $this->paymentFilters();

        $this->view('admin/payments/index', [
            'pageTitle' => __('payments'),
            'payments' => $payments,
            'categoryLabels' => $this->getCategoryLabels(),
            'filters' => $this->all(),
        ], 'admin/layout');
    }

    public function export(): void
    {
        $payments = $this->paymentFilters();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=payments-' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $labels = $this->getCategoryLabels();
        $headers = [__('member'), __('transaction_category'), __('amount'), __('date'), __('admin_user')];
        csv_put($out, $headers);
        foreach ($payments as $p) {
            csv_put($out, [$p['member_name'], $labels[$p['category']] ?? $p['category'], $p['amount'], $p['transaction_date'], $p['admin_name'] ?? '-']);
        }
        fclose($out);
        exit;
    }

    /** Printable version of the payments list (browser "Save as PDF"). Same filters as the list and the CSV. */
    public function printReport(): void
    {
        $labels = $this->getCategoryLabels();
        $rows = [];
        foreach ($this->paymentFilters() as $p) {
            $rows[] = [$p['member_name'], $labels[$p['category']] ?? $p['category'], $p['amount'], $p['transaction_date'], $p['admin_name'] ?? '-'];
        }
        $this->view('admin/reports/print', [
            'title' => __('payments'),
            'headers' => [__('member'), __('transaction_category'), __('amount'), __('date'), __('admin_user')],
            'rows' => $rows,
        ]);
    }
}
