<?php

namespace App\Models;

use App\Core\Model;

/**
 * The administrative and bank expenses log (name, cost, date, and optionally the member it belongs to). Only a record:
 * nothing here changes the ledger or any other total. Reached by the main admin only.
 */
class Expense extends Model
{
    protected static string $table = 'expenses';

    /** Number of expenses and their total cost, for the dashboard and the top of the log page. */
    public static function summary(): array
    {
        $row = self::rawOne('SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS s FROM expenses');
        return ['count' => (int) ($row['c'] ?? 0), 'total' => round((float) ($row['s'] ?? 0), 2)];
    }

    /** The expenses with the member each belongs to (if any), newest first; $q matches part of the expense or the member name. */
    public static function search(string $q = ''): array
    {
        $sql = 'SELECT e.*, m.name AS member_name, m.serial_no AS member_serial
                FROM expenses e LEFT JOIN members m ON m.id = e.member_id';
        $params = [];
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $sql .= ' WHERE e.name LIKE ? OR m.name LIKE ?';
            $params = [$like, $like];
        }
        return self::raw($sql . ' ORDER BY e.expense_date DESC, e.id DESC', $params);
    }

    /** The members an expense can be linked to (registered ones, not pending / refused registrations), for the picker. */
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
