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

    /**
     * The total of every amount of a result, split into what came in and what went out as loans -- the same figures as the
     * totals card of the page, so the page, the printed page and the PDF report always agree.
     *
     * @return array{total: float, incoming: float, disbursed: float, count: int}
     */
    private function totalsOf(array $transactions): array
    {
        $total = $incoming = $disbursed = 0.0;
        foreach ($transactions as $t) {
            $amount = (float) $t['amount'];
            $total += $amount;
            if ($t['category'] === 'loan_disbursement') {
                $disbursed += $amount;
            } else {
                $incoming += $amount;
            }
        }
        return ['total' => round($total, 2), 'incoming' => round($incoming, 2), 'disbursed' => round($disbursed, 2), 'count' => count($transactions)];
    }

    /** What the user filtered by, as short texts ("بحث: ...", "من: ..."), for the heading of a printed page / PDF. */
    private function filterNotes(): array
    {
        $ar = is_rtl();
        $labels = $this->getCategoryLabels();
        $notes = [];
        $search = trim((string) $this->input('search', ''));
        if ($search !== '') {
            $notes[] = ($ar ? 'بحث: ' : 'Search: ') . $search;
        }
        $category = trim((string) $this->input('category', ''));
        if ($category !== '') {
            $notes[] = ($ar ? 'النوع: ' : 'Type: ') . ($labels[$category] ?? $category);
        }
        $month = trim((string) $this->input('month', ''));
        if ($month !== '') {
            $notes[] = ($ar ? 'الشهر: ' : 'Month: ') . $month;
        }
        $from = trim((string) $this->input('from', ''));
        if ($from !== '') {
            $notes[] = ($ar ? 'من: ' : 'From: ') . date_ar($from);
        }
        $to = trim((string) $this->input('to', ''));
        if ($to !== '') {
            $notes[] = ($ar ? 'إلى: ' : 'To: ') . date_ar($to);
        }
        return $notes;
    }

    /** The totals box printed above the table: the total of all amounts, big, with the incoming / disbursed split. */
    private function totalsBox(array $transactions): array
    {
        $ar = is_rtl();
        $t = $this->totalsOf($transactions);
        return [
            'label' => $ar ? 'إجمالي المبالغ' : 'Total amount',
            'value' => money($t['total']),
            'note' => $ar ? 'مجموع كل المبالغ في ' . $t['count'] . ' معاملة' : 'Sum of every amount in ' . $t['count'] . ' transactions',
            'items' => [
                [$ar ? 'الوارد (اشتراكات وأقساط ورسوم)' : 'Incoming (subscriptions, installments, fees)', money($t['incoming'])],
                [$ar ? 'المصروف (قروض)' : 'Disbursed (loans)', money($t['disbursed'])],
            ],
        ];
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

        // The whole site's ledger is always shown (to match against the bank statement); with a filter applied,
        // the filtered result's own total is shown next to it.
        $grand = Transaction::rawOne("SELECT COUNT(*) AS c, COALESCE(SUM(transactions.amount), 0) AS s,
            COALESCE(SUM(CASE WHEN transactions.category = 'loan_disbursement' THEN 0 ELSE transactions.amount END), 0) AS money_in,
            COALESCE(SUM(CASE WHEN transactions.category = 'loan_disbursement' THEN transactions.amount ELSE 0 END), 0) AS money_out
            FROM transactions JOIN members ON members.id = transactions.member_id");
        $summary = ['filtered' => $filtered, 'grand_total' => (float) $grand['s'], 'bank_balance' => round((float) $grand['money_in'] - (float) $grand['money_out'] + \App\Models\Expense::net(), 2), 'grand_count' => (int) $grand['c'], 'count' => count($transactions), 'total' => 0.0, 'incoming' => 0.0, 'disbursed' => 0.0, 'byCategory' => [], 'shares' => 0, 'members' => 0];
        $sharesByMember = [];
        foreach ($transactions as $t) {
            // Shares of the filtered result: each member appearing in it is counted once, with the shares they hold.
            $sharesByMember[(int) $t['member_id']] = (int) $t['member_shares_count'];
            $summary['byCategory'][$t['category']] = ($summary['byCategory'][$t['category']] ?? 0) + (float) $t['amount'];
        }
        $totals = $this->totalsOf($transactions);
        $summary['total'] = $totals['total'];
        $summary['incoming'] = $totals['incoming'];
        $summary['disbursed'] = $totals['disbursed'];
        arsort($summary['byCategory']);
        $summary['shares'] = array_sum($sharesByMember);
        $summary['members'] = count($sharesByMember);

        $pager = paginate($transactions);

        $this->view('admin/transactions/index', [
            'pageTitle' => __('transactions'),
            'transactions' => $pager['items'],
            'pager' => $pager,
            'summary' => $summary,
            'printFilters' => $this->filterNotes(),
            'printTotals' => $this->totalsBox($transactions),
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
        $headers = [__('date'), __('transaction_category'), is_rtl() ? 'البيان / التفاصيل' : 'Details', is_rtl() ? 'الرقم التسلسلي' : 'Serial', __('member'), __('amount'), __('admin_user')];
        csv_put($out, $headers);
        foreach ($transactions as $t) {
            csv_put($out, [
                date_ar($t['transaction_date']),
                $labels[$t['category']] ?? $t['category'],
                localize_dates($t['display_details'] ?? '-'),
                $t['member_serial'] ?? '',
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
        $transactions = $this->fetch();
        foreach ($transactions as $t) {
            $rows[] = [
                date_ar($t['transaction_date']),
                $labels[$t['category']] ?? $t['category'],
                localize_dates($t['display_details'] ?? '-'),
                $t['member_serial'] ?? '-',
                $t['member_name'],
                $t['amount'],
                $t['admin_name'] ?? '-'
            ];
        }
        $this->view('admin/reports/print', [
            'title' => __('transactions'),
            'headers' => [__('date'), __('transaction_category'), is_rtl() ? 'البيان / التفاصيل' : 'Details', is_rtl() ? 'الرقم التسلسلي' : 'Serial', __('member'), __('amount'), __('admin_user')],
            'rows' => $rows,
            'summary' => $this->totalsBox($transactions),
            'subtitle' => implode(' · ', $this->filterNotes()),
        ]);
    }
}
