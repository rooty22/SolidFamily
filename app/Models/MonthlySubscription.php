<?php

namespace App\Models;

use App\Core\Model;

class MonthlySubscription extends Model
{
    protected static string $table = 'monthly_subscriptions';

    public static function forMember(int $memberId): array
    {
        return self::where(['member_id' => $memberId], 'month DESC, lot_id ASC');
    }

    /** The lot's own due day if set, else the member's own override, else the site-wide default. */
    public static function dueDayFor(array $member, ?array $lot = null): int
    {
        if ($lot !== null && $lot['subscription_due_day'] !== null) {
            return (int) $lot['subscription_due_day'];
        }
        return (int) ($member['subscription_due_day'] ?? Setting::get('subscription_due_day', 10));
    }

    /**
     * Ensures every one of the member's active share lots has its own row for $month (unmerged lots
     * bill on their own due date), plus a single lot-less trivial row for a member with no active lots.
     * Returns all of that member's rows for $month.
     *
     * @param bool $applyGracePeriod Whether a just-(re)computed row whose due date has already passed
     *     this month gets waived instead of instantly counted as overdue (see ensureLotMonth()).
     */
    public static function ensureMonthExistsForMember(int $memberId, string $month, bool $applyGracePeriod = true): array
    {
        $member = Member::find($memberId);
        $lots = ShareLot::activeFor($memberId);

        if (empty($lots)) {
            return [self::ensureLotMonth($member, null, 0, null, $month, $applyGracePeriod)];
        }

        // A member who had no lots yet (or had them cancelled to zero) can have a leftover lot-less
        // trivial row from back then. Now that real lots exist for this month, that row is just a stale
        // duplicate -- drop it rather than leave it cluttering the history next to the real ones.
        $stale = self::first(['member_id' => $memberId, 'month' => $month, 'lot_id' => null]);
        if ($stale && (float) $stale['amount_paid'] == 0.0) {
            self::delete($stale['id']);
        }

        $rows = [];
        foreach ($lots as $lot) {
            $rows[] = self::ensureLotMonth($member, (int) $lot['id'], (int) $lot['shares_count'], $lot['subscription_due_day'], $month, $applyGracePeriod);
        }
        return $rows;
    }

    /**
     * Back-compat convenience for callers that only need a single summary row (e.g. "is anything
     * overdue right now"): the earliest-created lot's row, or the lot-less trivial row if there are none.
     */
    public static function ensureMonthExists(int $memberId, string $month, bool $applyGracePeriod = true): array
    {
        return self::ensureMonthExistsForMember($memberId, $month, $applyGracePeriod)[0];
    }

    private static function ensureLotMonth(array $member, ?int $lotId, int $sharesCount, $lotDueDay, string $month, bool $applyGracePeriod): array
    {
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = $lotDueDay !== null ? (int) $lotDueDay : (int) ($member['subscription_due_day'] ?? Setting::get('subscription_due_day', 10));
        $amountDue = round($sharesCount * $shareValue, 2);
        $dueDate = month_due_date($month, $dueDay);

        // A lot approved (or given a new due day) mid-cycle, after this month's due day already passed,
        // shouldn't be flagged overdue the instant its row is created/recalculated. Waive this one period;
        // billing starts cleanly next month with its own (future) due date.
        $waived = $applyGracePeriod && $month === date('Y-m') && $dueDate < date('Y-m-d');
        // A lot with no shares owes nothing this month: that is trivially "paid", not an unpaid/late debt.
        $status = ($amountDue > 0 && !$waived) ? 'unpaid' : 'paid';

        $existing = self::first(['member_id' => $member['id'], 'month' => $month, 'lot_id' => $lotId]);
        if ($existing) {
            // A current/future month nobody has paid anything on follows the lot's latest share count / share value;
            // otherwise a lot resized after the row was created would get that month for free (or stay flagged as
            // owing a debt it no longer has).
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
            'member_id' => $member['id'],
            'lot_id' => $lotId,
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
