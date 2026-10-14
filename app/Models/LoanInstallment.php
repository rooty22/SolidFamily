<?php

namespace App\Models;

use App\Core\Model;

class LoanInstallment extends Model
{
    protected static string $table = 'loan_installments';

    /**
     * The loan's installments, each carrying its real payment date (the latest recorded repayment) plus:
     *  - db_status: what is stored ('unpaid' / 'partial' / 'paid'), which the money logic keeps relying on;
     *  - display_status: what to show, worked out from today's date like subscriptions ('paid', 'paid_late',
     *    'partial', 'partial_late', 'late' or 'unpaid').
     * `status` itself is left as stored so existing "is it paid yet" checks keep working.
     */
    public static function forLoan(int $loanId): array
    {
        $rows = self::raw(
            'SELECT li.*,
                    COALESCE((SELECT MAX(t.transaction_date) FROM transactions t
                              WHERE t.category = "loan_installment" AND t.related_id = li.id),
                             DATE(li.paid_at)) AS payment_date
             FROM loan_installments li
             WHERE li.loan_id = ?
             ORDER BY li.installment_number ASC',
            [$loanId]
        );
        foreach ($rows as &$r) {
            $r['db_status'] = $r['status'];
            $r['display_status'] = self::statusOf($r);
        }
        unset($r);
        return $rows;
    }

    /** Same shape as a subscription row so MonthlySubscription's date logic and the shared date cell can be reused. */
    public static function asDueRow(array $row): array
    {
        return $row + ['amount_due' => $row['amount']];
    }

    /** 'paid', 'paid_late', 'partial', 'partial_late', 'late' or 'unpaid' for the installment, from today's date. */
    public static function statusOf(array $row): string
    {
        $due = (string) $row['due_date'];
        $amount = (float) $row['amount'];
        $paid = (float) $row['amount_paid'];
        $payDate = !empty($row['payment_date']) ? (string) $row['payment_date'] : null;
        $overdue = $due < date('Y-m-d');

        if ($paid >= $amount) {
            return ($payDate && $payDate > $due) ? 'paid_late' : 'paid';
        }
        if ($paid > 0) {
            return (($payDate && $payDate > $due) || $overdue) ? 'partial_late' : 'partial';
        }
        return $overdue ? 'late' : 'unpaid';
    }

    /** Whether something is still owed on the installment and its due date has already passed. */
    public static function isOverdue(array $row): bool
    {
        return round((float) $row['amount'] - (float) $row['amount_paid'], 2) > 0 && (string) $row['due_date'] < date('Y-m-d');
    }
}
