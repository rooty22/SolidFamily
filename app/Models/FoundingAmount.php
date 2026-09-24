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

        $update = [
            'shares_count_linked' => $sharesCount,
            'total_required' => $total,
            'status' => $status,
        ];
        // With a payment plan, installments that are already paid keep their amount: only the CHANGE in the total
        // (in either direction) lands on the installments still open, or on a new one when everything was paid.
        if (!empty($row['plan_start'])) {
            $amounts = self::planAmounts($row);
            $update['plan_schedule'] = json_encode(self::adjust($amounts, $paid, $total - array_sum($amounts)));
        }

        self::update($row['id'], $update);

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

        self::update($founding['id'], [
            'plan_months' => $months,
            'plan_start' => $start . '-01',
            'plan_schedule' => json_encode(self::evenSplit((float) $founding['total_required'], $months)),
        ]);
        return self::find($founding['id']);
    }

    /**
     * The installment schedule of the chosen plan (empty until a plan is chosen). Amounts split the total evenly
     * (the last one takes the rounding remainder); everything paid so far is applied to the earliest installments first.
     * Computed, not stored, so it always follows the current total (e.g. after the member's shares change).
     */
    public static function schedule(array $founding, array $member): array
    {
        $total = round((float) $founding['total_required'], 2);
        if (empty($founding['plan_start']) || $total <= 0 || (int) ($founding['plan_months'] ?? 1) < 1) {
            return [];
        }

        $dueDay = MonthlySubscription::dueDayFor($member);
        $paidLeft = (float) $founding['amount_paid'];
        $today = date('Y-m-d');
        $items = [];

        foreach (self::planAmounts($founding) as $k => $amount) {
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

    /** The amount of every installment of the plan: the frozen split when stored, otherwise an even split of the total. */
    public static function planAmounts(array $founding): array
    {
        $total = round((float) $founding['total_required'], 2);
        $stored = json_decode((string) ($founding['plan_schedule'] ?? ''), true);
        if (!is_array($stored) || !$stored) {
            return self::evenSplit($total, max(1, (int) ($founding['plan_months'] ?? 1)));
        }
        $amounts = array_map('floatval', array_values($stored));
        // Safety net: if the total moved without going through syncWithShares(), reconcile the difference.
        $diff = round($total - array_sum($amounts), 2);
        return $diff == 0.0 ? $amounts : self::adjust($amounts, (float) $founding['amount_paid'], $diff);
    }

    /** $total split into $months installments (the last takes the rounding remainder). */
    public static function evenSplit(float $total, int $months): array
    {
        $cents = (int) round($total * 100);
        $base = intdiv($cents, $months);
        $out = array_fill(0, $months, $base);
        $out[$months - 1] += $cents - $base * $months;
        return array_map(fn($c) => $c / 100, $out);
    }

    /**
     * Move the plan by $delta (positive: more is owed, negative: less) without touching what is already paid.
     * More owed: split over the installments not fully paid, or appended as a new installment when all are paid.
     * Less owed: taken evenly from the unpaid part of the installments.
     */
    public static function adjust(array $amounts, float $paid, float $delta): array
    {
        $a = array_map(fn($x) => (int) round($x * 100), array_values($amounts));
        $d = (int) round($delta * 100);
        if ($d === 0 || !$a) {
            return array_map(fn($c) => $c / 100, $a);
        }

        $cover = [];
        $left = (int) round($paid * 100);
        foreach ($a as $x) {
            $t = min($x, max(0, $left));
            $cover[] = $t;
            $left -= $t;
        }

        if ($d > 0) {
            $open = array_keys(array_filter($a, fn($x, $i) => $cover[$i] < $x, ARRAY_FILTER_USE_BOTH));
            if (!$open) {
                $a[] = $d;
            } else {
                $base = intdiv($d, count($open));
                foreach ($open as $i) {
                    $a[$i] += $base;
                }
                $a[end($open)] += $d - $base * count($open);
            }
        } else {
            $need = -$d;
            while ($need > 0) {
                $room = array_keys(array_filter($a, fn($x, $i) => $x - $cover[$i] > 0, ARRAY_FILTER_USE_BOTH));
                if (!$room) {
                    break;
                }
                $share = max(1, intdiv($need, count($room)));
                foreach ($room as $i) {
                    $take = min($share, $a[$i] - $cover[$i], $need);
                    $a[$i] -= $take;
                    $need -= $take;
                }
            }
            $a = array_values(array_filter($a, fn($x) => $x > 0));
        }

        return array_map(fn($c) => $c / 100, $a);
    }

    /**
     * True when the founding amount must be paid in one go: the member never chose a payment plan, or chose "at once".
     * Only a plan of two or more installments allows the admin to record partial payments.
     */
    public static function mustPayInFull(array $founding): bool
    {
        return empty($founding['plan_start']) || (int) ($founding['plan_months'] ?? 1) <= 1;
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
