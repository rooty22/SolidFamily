<?php

namespace App\Models;

use App\Core\Model;

class MonthlySubscription extends Model
{
    protected static string $table = 'monthly_subscriptions';

    /** Days a lot added after this month's due day gets before its first payment counts as late. */
    private const MID_CYCLE_GRACE_DAYS = 7;

    /** The date from which an unpaid row counts as LATE: the due date, or the end of its grace when that is later. */
    public static function effectiveDue(array $row): string
    {
        $grace = $row['grace_until'] ?? null;
        return ($grace && $grace > $row['due_date']) ? $grace : $row['due_date'];
    }

    /**
     * Month-level view of all of a member's lot rows for one month. The month is only "paid" when EVERYTHING due is
     * collected, "partial" when some money is in but not all, "unpaid" otherwise (never "the worst row wins").
     */
    public static function summarize(array $rows): array
    {
        $due = round(array_sum(array_map('floatval', array_column($rows, 'amount_due'))), 2);
        $paid = round(array_sum(array_map('floatval', array_column($rows, 'amount_paid'))), 2);
        $status = ($due <= 0 || $paid >= $due) ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        $openDueDates = [];
        foreach ($rows as $r) {
            if ((float) $r['amount_due'] > (float) $r['amount_paid']) {
                $openDueDates[] = $r['due_date'];
            }
        }

        return [
            'status' => $status,
            'amount_due' => $due,
            'amount_paid' => min($paid, $due),
            'remaining' => max(0.0, round($due - $paid, 2)),
            'due_date' => $openDueDates ? min($openDueDates) : ($rows[0]['due_date'] ?? null),
        ];
    }

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
     * @param bool $applyGracePeriod Whether a lot added this month, after its due day already passed, gets a short
     *     grace before it counts as overdue (see ensureLotMonth()). Its amount is still owed, never waived.
     */
    public static function ensureMonthExistsForMember(int $memberId, string $month, bool $applyGracePeriod = true): array
    {
        $member = Member::find($memberId);
        $lots = ShareLot::activeFor($memberId);

        if (empty($lots)) {
            return [self::ensureLotMonth($member, null, 0, null, $month, $applyGracePeriod)];
        }

        // A lot owes nothing for months before it existed, so it gets no row for them (otherwise an admin
        // "paying several months" from an earlier start month would record real payments for a period the
        // member wasn't subscribed to).
        $lots = array_values(array_filter($lots, fn($lot) => ShareLot::startMonth($lot) <= $month));
        if (empty($lots)) {
            return [];
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
            $rows[] = self::ensureLotMonth($member, (int) $lot['id'], (int) $lot['shares_count'], $lot['subscription_due_day'], $month, $applyGracePeriod && ShareLot::startMonth($lot) === $month);
        }
        return $rows;
    }

    /**
     * Back-compat convenience for callers that only need a single summary row (e.g. "is anything
     * overdue right now"): the earliest-created lot's row, or the lot-less trivial row if there are none.
     */
    public static function ensureMonthExists(int $memberId, string $month, bool $applyGracePeriod = true): array
    {
        return self::ensureMonthExistsForMember($memberId, $month, $applyGracePeriod)[0] ?? [];
    }

    private static function ensureLotMonth(array $member, ?int $lotId, int $sharesCount, $lotDueDay, string $month, bool $applyGracePeriod): array
    {
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = $lotDueDay !== null ? (int) $lotDueDay : (int) ($member['subscription_due_day'] ?? Setting::get('subscription_due_day', 10));
        $amountDue = round($sharesCount * $shareValue, 2);
        $dueDate = month_due_date($month, $dueDay);

        // A lot approved mid-cycle, after this month's due day already passed, is NOT overdue the instant it is billed:
        // it falls due a few days later. Its amount is a real debt for this month - the row stays UNPAID until
        // money is actually recorded (marking it "paid" would hide what the member still owes).
        // due_date keeps showing the due day the admin configured; the grace is a separate date that only
        // postpones the moment the row starts counting as late.
        $graceUntil = null;
        if ($applyGracePeriod && $month === date('Y-m') && $dueDate < date('Y-m-d')) {
            $graceUntil = date('Y-m-d', strtotime('+' . self::MID_CYCLE_GRACE_DAYS . ' days'));
        }
        // A lot with no shares owes nothing this month: that is trivially "paid".
        $status = $amountDue > 0 ? 'unpaid' : 'paid';

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
                    'grace_until' => $graceUntil,
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
            'grace_until' => $graceUntil,
        ]);

        return self::find($id);
    }

    /**
     * Void the current-month rows of lots that no longer exist (cancelled to zero / merged into a new lot).
     * A voided row owes nothing any more (amount_due = 0), so every total and status computed from amounts stays true.
     * $force also drops what was collected on it (that money was carried to the merged row); otherwise a row that
     * already has a real payment is left alone - cancelling shares never erases money that was paid.
     */
    public static function voidRowsOfLots(int $memberId, array $lotIds, bool $force, ?string $month = null): void
    {
        $month = $month ?? date('Y-m');
        foreach ($lotIds as $lotId) {
            $row = self::first(['member_id' => $memberId, 'month' => $month, 'lot_id' => $lotId]);
            if (!$row) {
                continue;
            }
            if ($force) {
                self::update($row['id'], ['amount_due' => 0, 'amount_paid' => 0, 'status' => 'paid']);
            } elseif ((float) $row['amount_paid'] == 0.0) {
                self::update($row['id'], ['amount_due' => 0, 'status' => 'paid']);
            }
        }
    }
}
