<?php

namespace App\Models;

use App\Core\Model;

/**
 * Each approved "add" share request creates its own lot (own due day), so unmerged purchases bill
 * separately until a "merge" share request is approved and folds every active lot into one.
 */
class ShareLot extends Model
{
    protected static string $table = 'share_lots';

    public static function activeFor(int $memberId): array
    {
        return self::where(['member_id' => $memberId, 'status' => 'active'], 'created_at ASC');
    }

    /** Reduce active lots oldest-first by $sharesToCancel; a lot emptied to 0 is dropped (merged out). Returns the actual total removed. */
    public static function cancelShares(int $memberId, int $sharesToCancel): int
    {
        $removed = 0;
        foreach (self::activeFor($memberId) as $lot) {
            if ($sharesToCancel <= 0) {
                break;
            }
            $take = min($sharesToCancel, (int) $lot['shares_count']);
            $remaining = (int) $lot['shares_count'] - $take;
            if ($remaining > 0) {
                self::update($lot['id'], ['shares_count' => $remaining]);
            } else {
                self::update($lot['id'], ['status' => 'merged']);
            }
            $sharesToCancel -= $take;
            $removed += $take;
        }
        return $removed;
    }

    /** Combine every active lot into a single new one; the old lots are marked 'merged'. Returns the new lot, or null if there was nothing to merge. */
    public static function mergeAllFor(int $memberId, ?int $dueDay, ?int $sourceRequestId = null): ?array
    {
        $active = self::activeFor($memberId);
        if (count($active) < 2) {
            return $active[0] ?? null;
        }

        $totalShares = array_sum(array_column($active, 'shares_count'));
        foreach ($active as $lot) {
            self::update($lot['id'], ['status' => 'merged']);
        }

        $id = self::create([
            'member_id' => $memberId,
            'shares_count' => $totalShares,
            'subscription_due_day' => $dueDay,
            'status' => 'active',
            'source_request_id' => $sourceRequestId,
        ]);

        return self::find($id);
    }
}
