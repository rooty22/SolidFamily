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

        // "Month" is a shortcut for the first-to-last day of that month (YYYY-MM); an explicit from/to wins over it.
        $month = (string) $this->input('month', '');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            if ($filters['from'] === '') {
                $filters['from'] = $month . '-01';
            }
            if ($filters['to'] === '') {
                $filters['to'] = date('Y-m-t', strtotime($month . '-01'));
            }
        }
        return Transaction::withMember($filters);
    }

    public function index(): void
    {
        $transactions = $this->fetch();
        $filtered = false;
        foreach (['category', 'search', 'from', 'to', 'month'] as $key) {
            if (trim((string) $this->input($key, '')) !== '') {
                $filtered = true;
                break;
            }
        }

        // Always shown: the total of the filtered result, or of the whole site's ledger when no filter is applied,
        // so it can be matched against the bank statement either way.
        $summary = ['filtered' => $filtered, 'count' => count($transactions), 'total' => 0.0, 'incoming' => 0.0, 'disbursed' => 0.0, 'byCategory' => [], 'shares' => 0, 'members' => 0];
        $sharesByMember = [];
        foreach ($transactions as $t) {
            // Shares of the filtered result: each member appearing in it is counted once, with the shares they hold.
            $sharesByMember[(int) $t['member_id']] = (int) $t['member_shares_count'];
            $amount = (float) $t['amount'];
            $summary['total'] += $amount;
            if ($t['category'] === 'loan_disbursement') {
                $summary['disbursed'] += $amount;
            } else {
                $summary['incoming'] += $amount;
            }
            $summary['byCategory'][$t['category']] = ($summary['byCategory'][$t['category']] ?? 0) + $amount;
        }
        arsort($summary['byCategory']);
        $summary['shares'] = array_sum($sharesByMember);
        $summary['members'] = count($sharesByMember);

        $this->view('admin/transactions/index', [
            'pageTitle' => __('transactions'),
            'transactions' => $transactions,
            'summary' => $summary,
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
        $headers = [__('date'), __('transaction_category'), is_rtl() ? 'البيان / التفاصيل' : 'Details', __('member'), __('amount'), __('admin_user')];
        csv_put($out, $headers);
        foreach ($transactions as $t) {
            csv_put($out, [
                date_ar($t['transaction_date']),
                $labels[$t['category']] ?? $t['category'],
                localize_dates($t['display_details'] ?? '-'),
                $t['member_name'],
                $t['amount'],
                $t['admin_name'] ?? '-'
            ]);
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
            $rows[] = [
                date_ar($t['transaction_date']),
                $labels[$t['category']] ?? $t['category'],
                localize_dates($t['display_details'] ?? '-'),
                $t['member_name'],
                $t['amount'],
                $t['admin_name'] ?? '-'
            ];
        }
        $this->view('admin/reports/print', [
            'title' => __('transactions'),
            'headers' => [__('date'), __('transaction_category'), is_rtl() ? 'البيان / التفاصيل' : 'Details', __('member'), __('amount'), __('admin_user')],
            'rows' => $rows,
        ]);
    }
}
