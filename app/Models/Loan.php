<?php

namespace App\Models;

use App\Core\Model;

class Loan extends Model
{
    protected static string $table = 'loans';

    public static function withMember(array $conditions = []): array
    {
        [$whereSql, $params] = self::joinWhere($conditions);
        $sql = 'SELECT loans.*, members.name as member_name, members.mobile as member_mobile, ' . self::LATE_COUNT_SQL . ' AS late_installments
                FROM loans JOIN members ON members.id = loans.member_id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY loans.created_at DESC';
        return self::withDisplayStatus(self::raw($sql, $params));
    }

    public static function forMember(int $memberId): array
    {
        return self::withDisplayStatus(self::raw(
            'SELECT loans.*, ' . self::LATE_COUNT_SQL . ' AS late_installments FROM loans WHERE loans.member_id = ? ORDER BY loans.created_at DESC',
            [$memberId]
        ));
    }

    /** Installments of the loan that still owe money although their due date has passed. */
    private const LATE_COUNT_SQL = '(SELECT COUNT(*) FROM loan_installments li
                                     WHERE li.loan_id = loans.id AND li.amount_paid < li.amount AND li.due_date < CURDATE())';

    /**
     * Adds display_status: a running loan (active/partial) with any overdue installment shows as 'late', like an
     * overdue subscription; every other status is shown as stored. `status` itself is left untouched.
     */
    public static function withDisplayStatus(array $loans): array
    {
        foreach ($loans as &$l) {
            $l['display_status'] = ((int) ($l['late_installments'] ?? 0) > 0 && in_array($l['status'], ['active', 'partial'], true))
                ? 'late'
                : $l['status'];
        }
        unset($l);
        return $loans;
    }

    /** display_status for a single loan whose installments are already loaded (see LoanInstallment::forLoan). */
    public static function displayStatusFor(array $loan, array $installments): string
    {
        if (!in_array($loan['status'], ['active', 'partial'], true)) {
            return $loan['status'];
        }
        foreach ($installments as $i) {
            if (LoanInstallment::isOverdue($i)) {
                return 'late';
            }
        }
        return $loan['status'];
    }

    protected static function joinWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $key = str_replace(['.', ' '], '_', $column);
            $parts[] = "$column = :$key";
            $params[$key] = $value;
        }
        return [implode(' AND ', $parts), $params];
    }
}
