<?php

namespace App\Models;

use App\Core\Model;

class MonthlySubscription extends Model
{
    protected static string $table = 'monthly_subscriptions';

    public static function forMember(int $memberId): array
    {
        return self::where(['member_id' => $memberId], 'month DESC');
    }

    /** The member's own due day if the admin set one, otherwise the site-wide default. */
    public static function dueDayFor(array $member): int
    {
        return (int) ($member['subscription_due_day'] ?? Setting::get('subscription_due_day', 10));
    }

    /**
     * @param bool $applyGracePeriod Whether a just-(re)computed current-month row whose due date has already
     *     passed gets waived instead of instantly counted as overdue (see below). Callers that are explicitly
     *     recording a payment for a specific month (which may itself already be in the past on purpose) pass
     *     false, since silently marking that row "paid" without an actual payment would make them skip it.
     */
    public static function ensureMonthExists(int $memberId, string $month, bool $applyGracePeriod = true): array
    {
        $member = Member::find($memberId);
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = self::dueDayFor($member);
        $sharesCount = (int) ($member['shares_count'] ?? 0);
        $amountDue = round($sharesCount * $shareValue, 2);
        $dueDate = month_due_date($month, $dueDay);

        // A member approved (or given a new due day) mid-cycle, after this month's due day already passed,
        // shouldn't be flagged overdue the instant their row is created/recalculated. Waive this one period;
        // billing starts cleanly next month with its own (future) due date.
        $waived = $applyGracePeriod && $month === date('Y-m') && $dueDate < date('Y-m-d');
        $status = ($amountDue > 0 && !$waived) ? 'unpaid' : 'paid';

        $existing = self::first(['member_id' => $memberId, 'month' => $month]);
        if ($existing) {
            // A current/future month nobody has paid anything on follows the member's latest share count / share value;
            // otherwise a member who received shares after the row was created would get that month for free (or a
            // member whose shares dropped to zero would stay flagged as owing a debt they no longer have).
            $untouched = (float) $existing['amount_paid'] == 0.0 && $month >= date('Y-m');
            if ($untouched && ((int) $existing['shares_count_snapshot'] !== $sharesCount
                    || (float) $existing['share_value_snapshot'] !== $shareValue
                    || (float) $existing['amount_due'] !== $amountDue)) {
                self::update($existing['id'], [
                    'shares_count_snapshot' => $sharesCount,
                    'share_value_snapshot' => $shareValue,
                    'amount_due' => $amountDue,
                    'status' => $status,
                    'due_date' => $dueDate,
                ]);
                return self::find($existing['id']);
            }
            return $existing;
        }

        $id = self::create([
            'member_id' => $memberId,
            'month' => $month,
            'shares_count_snapshot' => $sharesCount,
            'share_value_snapshot' => $shareValue,
            'amount_due' => $amountDue,
            'amount_paid' => 0,
            'status' => $status,
            'due_date' => $dueDate,
        ]);

        return self::find($id);
    }
}
