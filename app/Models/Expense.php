<?php

namespace App\Models;

use App\Core\Model;

/**
 * The administrative and bank expenses log. Every entry is a WITHDRAWAL (a deduction from the fund's balance: the
 * expense itself) or a DEPOSIT (an addition to it), with a name, an amount, a date and, optionally, the member it
 * belongs to and notes. The net of the whole log (deposits - withdrawals) counts in the bank balance and in the total
 * fund balance of the dashboard; see net(). The log itself is reached by the main admin only.
 */
class Expense extends Model
{
    protected static string $table = 'expenses';

    public const WITHDRAW = 'withdraw';
    public const DEPOSIT = 'deposit';
    public const TYPES = [self::WITHDRAW, self::DEPOSIT];

    /** Totals of the log: counts, withdrawals, deposits and the net (deposits - withdrawals), for the log page and the dashboard. */
    public static function summary(): array
    {
        $sum = [self::WITHDRAW => ['c' => 0, 's' => 0.0], self::DEPOSIT => ['c' => 0, 's' => 0.0]];
        foreach (self::raw('SELECT type, COUNT(*) AS c, COALESCE(SUM(amount), 0) AS s FROM expenses GROUP BY type') as $row) {
            $sum[$row['type']] = ['c' => (int) $row['c'], 's' => round((float) $row['s'], 2)];
        }
        $w = $sum[self::WITHDRAW]['s'];
        $d = $sum[self::DEPOSIT]['s'];
        return [
            'count' => $sum[self::WITHDRAW]['c'] + $sum[self::DEPOSIT]['c'],
            'total' => $w,                       // what the log has spent (the withdrawals)
            'withdrawals' => $w,
            'deposits' => $d,
            'net' => round($d - $w, 2),          // the effect of the whole log on the fund's balance
            'withdraw_count' => $sum[self::WITHDRAW]['c'],
            'deposit_count' => $sum[self::DEPOSIT]['c'],
        ];
    }

    /**
     * The effect of the whole log on the fund's balance: deposits are added, withdrawals deducted. Used by every figure
     * that is "the bank balance" or "the total fund balance". 0 when the log does not exist yet (its migration has not
     * run), so a page that shows a balance never breaks because of it.
     */
    public static function net(): float
    {
        try {
            $row = self::rawOne("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) AS n FROM expenses");
            return round((float) ($row['n'] ?? 0), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /** The amount of an entry with its sign: positive for a deposit, negative for a withdrawal. */
    public static function signed(array $row): float
    {
        return ($row['type'] ?? self::WITHDRAW) === self::DEPOSIT ? (float) $row['amount'] : -(float) $row['amount'];
    }

    /**
     * The entries with the member each belongs to (if any), newest first. $q matches part of the name, the notes or the
     * member's name; $type limits the list to withdrawals or deposits.
     */
    public static function search(string $q = '', string $type = ''): array
    {
        $sql = 'SELECT e.*, m.name AS member_name, m.serial_no AS member_serial
                FROM expenses e LEFT JOIN members m ON m.id = e.member_id';
        $where = [];
        $params = [];
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(e.name LIKE ? OR e.notes LIKE ? OR m.name LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (in_array($type, self::TYPES, true)) {
            $where[] = 'e.type = ?';
            $params[] = $type;
        }
        return self::raw($sql . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY e.expense_date DESC, e.id DESC', $params);
    }

    /** The members an entry can be linked to (registered ones, not pending / refused registrations), for the picker. */
    public static function memberOptions(): array
    {
        return array_map(fn($m) => [
            'id' => (int) $m['id'],
            'label' => (!empty($m['serial_no']) ? '#' . (int) $m['serial_no'] . ' · ' : '') . $m['name'],
            'sub' => (string) ($m['mobile'] ?? ''),
            'q' => ($m['name'] ?? '') . ' ' . ($m['mobile'] ?? '') . ' ' . ($m['national_id'] ?? '') . ' ' . ($m['uid'] ?? '') . ' #' . ($m['serial_no'] ?? ''),
        ], Member::withoutRegistrations(Member::all('name ASC')));
    }
}
