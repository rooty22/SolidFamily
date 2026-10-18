<?php

namespace App\Models;

use App\Core\Model;

/**
 * One row per merge (shares deducted) or split (shares given back), with what those shares were worth per month
 * when it happened, so the dashboard can show the money moved by merging and splitting.
 */
class ShareMergeLog extends Model
{
    protected static string $table = 'share_merge_log';

    public static function record(int $memberId, ?int $lotId, string $action, int $shares): void
    {
        if ($shares <= 0) {
            return;
        }
        $value = (float) Setting::get('share_value', 0);
        self::create([
            'member_id' => $memberId,
            'lot_id' => $lotId,
            'action' => $action,
            'shares' => $shares,
            'share_value' => $value,
            'amount' => round($shares * $value, 2),
        ]);
    }

    /**
     * Totals for the dashboard: shares and amount deducted by merges, restored by splits, and what is still deducted
     * (merged minus split), plus the latest movements.
     */
    public static function summary(int $recentLimit = 5): array
    {
        $totals = ['merge' => ['count' => 0, 'shares' => 0, 'amount' => 0.0], 'split' => ['count' => 0, 'shares' => 0, 'amount' => 0.0]];
        foreach (self::raw('SELECT action, COUNT(*) AS c, SUM(shares) AS s, SUM(amount) AS a FROM share_merge_log GROUP BY action') as $row) {
            $totals[$row['action']] = ['count' => (int) $row['c'], 'shares' => (int) $row['s'], 'amount' => (float) $row['a']];
        }

        return [
            'merged' => $totals['merge'],
            'split' => $totals['split'],
            'net_shares' => max(0, $totals['merge']['shares'] - $totals['split']['shares']),
            'net_amount' => max(0.0, $totals['merge']['amount'] - $totals['split']['amount']),
            'recent' => self::raw(
                'SELECT l.*, m.name AS member_name FROM share_merge_log l JOIN members m ON m.id = l.member_id ORDER BY l.created_at DESC, l.id DESC LIMIT ' . max(1, $recentLimit)
            ),
        ];
    }
}
