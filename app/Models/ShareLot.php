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

    /** The first month (YYYY-MM) a lot bills for: the month it was created. Nothing is owed for earlier months. */
    public static function startMonth(array $lot): string
    {
        return substr((string) $lot['created_at'], 0, 7);
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

    /**
     * Make the active lots add up to members.shares_count (the single total everything else uses). Used when an admin
     * sets a member's shares directly instead of through an approved request: extra shares become a new lot, removed
     * shares are taken from the oldest lots first. Returns the ids of lots that ended up emptied.
     */
    public static function syncToMemberTotal(int $memberId): array
    {
        $member = Member::find($memberId);
        if (!$member) {
            return [];
        }
        $total = (int) $member['shares_count'];
        $active = self::activeFor($memberId);
        $sum = (int) array_sum(array_column($active, 'shares_count'));

        if ($total > $sum) {
            self::create(['member_id' => $memberId, 'shares_count' => $total - $sum]);
            return [];
        }
        if ($total < $sum) {
            $before = array_column($active, 'id');
            self::cancelShares($memberId, $sum - $total);
            $after = array_column(self::activeFor($memberId), 'id');
            return array_values(array_diff($before, $after));
        }
        return [];
    }
}
