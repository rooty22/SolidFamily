<?php

namespace App\Models;

use App\Core\Model;

/**
 * The administrative and bank expenses log (name, cost, date). Only a record: nothing here changes the ledger or any
 * other total. Reached by the main admin only.
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

    /** The expenses, newest first; $q matches part of the name. */
    public static function search(string $q = ''): array
    {
        if ($q === '') {
            return self::raw('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC');
        }
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        return self::raw('SELECT * FROM expenses WHERE name LIKE ? ORDER BY expense_date DESC, id DESC', [$like]);
    }
}
