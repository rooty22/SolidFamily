<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Transaction;

class TransactionsController extends Controller
{
    private function getCategoryLabels(): array
    {
        return transaction_categories();
    }

    private function fetch(): array
    {
        $filters = [
            'category' => $this->input('category', ''),
            'search' => $this->input('search', ''),
            'from' => $this->input('from', ''),
            'to' => $this->input('to', ''),
        ];
        return Transaction::withMember($filters);
    }

    public function index(): void
    {
        $this->view('admin/transactions/index', [
            'pageTitle' => __('transactions'),
            'transactions' => $this->fetch(),
            'categoryLabels' => $this->getCategoryLabels(),
            'filters' => $this->all(),
        ], 'admin/layout');
    }

    public function export(): void
    {
        $transactions = $this->fetch();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=transactions-' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $labels = $this->getCategoryLabels();
        $headers = [__('date'), __('transaction_category'), __('member'), __('amount'), __('admin_user')];
        csv_put($out, $headers);
        foreach ($transactions as $t) {
            csv_put($out, [$t['transaction_date'], $labels[$t['category']] ?? $t['category'], $t['member_name'], $t['amount'], $t['admin_name'] ?? '-']);
        }
        fclose($out);
        exit;
    }

    /** Printable version of the transactions ledger (browser "Save as PDF"). Same filters as the list and the CSV. */
    public function printReport(): void
    {
        $labels = $this->getCategoryLabels();
        $rows = [];
        foreach ($this->fetch() as $t) {
            $rows[] = [$t['transaction_date'], $labels[$t['category']] ?? $t['category'], $t['member_name'], $t['amount'], $t['admin_name'] ?? '-'];
        }
        $this->view('admin/reports/print', [
            'title' => __('transactions'),
            'headers' => [__('date'), __('transaction_category'), __('member'), __('amount'), __('admin_user')],
            'rows' => $rows,
        ]);
    }
}
