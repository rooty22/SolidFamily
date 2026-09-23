<?php

namespace App\Models;

use App\Core\Model;

class FoundingAmount extends Model
{
    protected static string $table = 'founding_amounts';

    public static function forMember(int $memberId): ?array
    {
        return self::findBy('member_id', $memberId);
    }

    public static function ensureForMember(int $memberId): array
    {
        $member = Member::find($memberId);
        $sharesCount = (int) ($member['shares_count'] ?? 0);

        $existing = self::forMember($memberId);
        if ($existing) {
            // The founding amount is tied to the shares: whenever they changed since the row was last computed, recompute it.
            return (int) $existing['shares_count_linked'] === $sharesCount ? $existing : self::syncWithShares($existing, $sharesCount);
        }

        $feePerShare = (float) Setting::get('founding_fee_per_share', 0);
        $total = round($sharesCount * $feePerShare, 2);
        $id = self::create([
            'member_id' => $memberId,
            'shares_count_linked' => $sharesCount,
            'total_required' => $total,
            'amount_paid' => 0,
            // A member with no shares owes nothing: that is trivially "paid", not an unpaid debt.
            'status' => $total > 0 ? 'unpaid' : 'paid',
        ]);

        return self::find($id);
    }

    private static function syncWithShares(array $row, int $sharesCount): array
    {
        $total = round($sharesCount * (float) Setting::get('founding_fee_per_share', 0), 2);
        $paid = (float) $row['amount_paid'];
        // A member with no shares owes nothing: that is trivially "paid", not an unpaid debt.
        $status = $total <= 0 || $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        self::update($row['id'], [
            'shares_count_linked' => $sharesCount,
            'total_required' => $total,
            'status' => $status,
        ]);

        return self::find($row['id']);
    }
}
