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

    public const MAX_PLAN_MONTHS = 12;

    /**
     * The member chooses how to pay the founding amount: at once (1) or split over N monthly installments.
     * The first installment falls due this month if its due date is still ahead, otherwise next month.
     */
    public static function setPlan(int $memberId, int $months): array
    {
        $member = Member::find($memberId);
        $founding = self::ensureForMember($memberId);
        $dueDay = MonthlySubscription::dueDayFor($member);
        $start = month_due_date(date('Y-m'), $dueDay) >= date('Y-m-d')
            ? date('Y-m')
            : date('Y-m', strtotime('first day of next month'));

        self::update($founding['id'], ['plan_months' => $months, 'plan_start' => $start . '-01']);
        return self::find($founding['id']);
    }

    /**
     * The installment schedule of the chosen plan (empty until a plan is chosen). Amounts split the total evenly
     * (the last one takes the rounding remainder); everything paid so far is applied to the earliest installments first.
     * Computed, not stored, so it always follows the current total (e.g. after the member's shares change).
     */
    public static function schedule(array $founding, array $member): array
    {
        $months = (int) ($founding['plan_months'] ?? 1);
        $total = round((float) $founding['total_required'], 2);
        if (empty($founding['plan_start']) || $total <= 0 || $months < 1) {
            return [];
        }

        $dueDay = MonthlySubscription::dueDayFor($member);
        $base = floor($total / $months * 100) / 100;
        $paidLeft = (float) $founding['amount_paid'];
        $today = date('Y-m-d');
        $allocated = 0.0;
        $items = [];

        for ($k = 0; $k < $months; $k++) {
            $amount = $k === $months - 1 ? round($total - $allocated, 2) : $base;
            $allocated = round($allocated + $amount, 2);
            $paid = round(min($amount, max(0.0, $paidLeft)), 2);
            $paidLeft = round($paidLeft - $paid, 2);
            $due = month_due_date(substr(add_months($founding['plan_start'], $k), 0, 7), $dueDay);
            $status = $paid >= $amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $items[] = [
                'number' => $k + 1,
                'due_date' => $due,
                'amount' => $amount,
                'paid' => $paid,
                'remaining' => round($amount - $paid, 2),
                'status' => $status,
                'late' => $status !== 'paid' && $due < $today,
            ];
        }

        return $items;
    }

    /** First installment that is not fully paid, or null. */
    public static function nextInstallment(array $schedule): ?array
    {
        foreach ($schedule as $item) {
            if ($item['status'] !== 'paid') {
                return $item;
            }
        }
        return null;
    }
}
