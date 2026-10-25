<?php

namespace App\Models;

use App\Core\Model;

class MonthlySubscription extends Model
{
    protected static string $table = 'monthly_subscriptions';

    /** Days a lot added after this month's due day gets before its first payment counts as late (admin-configurable; 7 if never set). */
    private static function gracePeriodDays(): int
    {
        return (int) Setting::get('subscription_grace_days', 7);
    }

    /** Whether a lot added after the due day gets any grace at all before counting as late (admin-configurable; on by default). */
    private static function graceEnabled(): bool
    {
        return (string) Setting::get('subscription_grace_enabled', '1') === '1';
    }

    /** The date from which an unpaid row counts as LATE: the due date, or the end of its grace when that is later. */
    public static function effectiveDue(array $row): string
    {
        $grace = $row['grace_until'] ?? null;
        return ($grace && $grace > $row['due_date']) ? $grace : $row['due_date'];
    }

    /**
     * Compute dynamic status for a subscription row: 'paid', 'paid_late', 'partial', 'partial_late', 'late', or 'unpaid'.
     */
    public static function statusOf(array $row): string
    {
        $due = (float) ($row['amount_due'] ?? 0);
        $paid = (float) ($row['amount_paid'] ?? 0);
        $effDue = self::effectiveDue($row);
        $payDate = $row['payment_date'] ?? null;

        if ($due <= 0 || $paid >= $due) {
            if ($payDate && $payDate > $effDue) {
                return 'paid_late';
            }
            return 'paid';
        }
        if ($paid > 0) {
            if ($payDate && $payDate > $effDue) {
                return 'partial_late';
            }
            return 'partial';
        }
        if ($effDue < date('Y-m-d')) {
            return 'late';
        }
        return 'unpaid';
    }

    /** Whole days from $from to $to (both Y-m-d); positive when $to is the later date. */
    public static function daysBetween(string $from, string $to): int
    {
        return (int) round((strtotime($to . ' 00:00:00 UTC') - strtotime($from . ' 00:00:00 UTC')) / 86400);
    }

    /**
     * How a row's dates relate, computed from today's date and the real payment date so it is always current:
     *  - 'payment': when money was recorded -> 'early' (before the due date), 'on_time', 'in_grace' (after the due date
     *    but inside its grace) or 'late' (after the grace), with 'days' off the due date (the grace end for 'late');
     *  - 'due': what is still owed -> 'overdue' (days since it fell due), 'today' or 'upcoming' (days left).
     * Either is null when it does not apply (nothing paid yet / nothing left to pay).
     */
    public static function timingOf(array $row): array
    {
        $timing = ['payment' => null, 'due' => null];
        $dueDate = $row['due_date'] ?? null;
        if (!$dueDate) {
            return $timing;
        }
        $effDue = self::effectiveDue($row);
        $payDate = $row['payment_date'] ?? null;
        $paid = (float) ($row['amount_paid'] ?? 0);
        $remaining = round((float) ($row['amount_due'] ?? 0) - $paid, 2);

        if ($paid > 0 && $payDate) {
            if ($payDate < $dueDate) {
                $timing['payment'] = ['type' => 'early', 'days' => self::daysBetween($payDate, $dueDate)];
            } elseif ($payDate === $dueDate) {
                $timing['payment'] = ['type' => 'on_time', 'days' => 0];
            } elseif ($payDate <= $effDue) {
                $timing['payment'] = ['type' => 'in_grace', 'days' => self::daysBetween($dueDate, $payDate)];
            } else {
                $timing['payment'] = ['type' => 'late', 'days' => self::daysBetween($effDue, $payDate)];
            }
        }

        if ($remaining > 0) {
            $today = date('Y-m-d');
            if ($effDue < $today) {
                $timing['due'] = ['type' => 'overdue', 'days' => self::daysBetween($effDue, $today)];
            } elseif ($effDue === $today) {
                $timing['due'] = ['type' => 'today', 'days' => 0];
            } else {
                $timing['due'] = ['type' => 'upcoming', 'days' => self::daysBetween($today, $effDue)];
            }
        }

        return $timing;
    }

    /**
     * Month-level view of all of a member's lot rows for one month.
     */
    public static function summarize(array $rows): array
    {
        $due = round(array_sum(array_map('floatval', array_column($rows, 'amount_due'))), 2);
        $paid = round(array_sum(array_map('floatval', array_column($rows, 'amount_paid'))), 2);

        $openDueDates = [];
        $isLate = false;
        $today = date('Y-m-d');
        foreach ($rows as $r) {
            if ((float) ($r['amount_due'] ?? 0) > (float) ($r['amount_paid'] ?? 0)) {
                if (!empty($r['due_date'])) {
                    $openDueDates[] = $r['due_date'];
                }
                if (self::effectiveDue($r) < $today) {
                    $isLate = true;
                }
            }
        }

        if ($due <= 0 || $paid >= $due) {
            $hasPaidLate = false;
            foreach ($rows as $r) {
                if (($r['status'] ?? '') === 'paid_late') {
                    $hasPaidLate = true;
                    break;
                }
            }
            $status = $hasPaidLate ? 'paid_late' : 'paid';
        } elseif ($paid > 0) {
            $hasPartialLate = false;
            foreach ($rows as $r) {
                if (($r['status'] ?? '') === 'partial_late') {
                    $hasPartialLate = true;
                    break;
                }
            }
            $status = $hasPartialLate ? 'partial_late' : 'partial';
        } elseif ($isLate) {
            $status = 'late';
        } else {
            $status = 'unpaid';
        }

        return [
            'status' => $status,
            'amount_due' => $due,
            'amount_paid' => min($paid, $due),
            'remaining' => max(0.0, round($due - $paid, 2)),
            'due_date' => $openDueDates ? min($openDueDates) : ($rows[0]['due_date'] ?? null),
            'is_late' => $isLate,
        ];
    }

    /**
     * A member's standing for one month, computed the same way on every screen (subscriptions page, dashboard, member
     * pages) so their numbers always agree:
     *   'no_shares' (owns no shares: nothing is billed), 'paid', 'partial', 'unpaid' (not due yet) or 'late'
     *   (due date passed with nothing paid). Paid-late / partial-late count as paid / partial.
     */
    public static function memberMonthStatus(array $member, string $month): string
    {
        if ((int) ($member['shares_count'] ?? 0) <= 0) {
            return 'no_shares';
        }
        $rows = array_values(array_filter(
            self::where(['member_id' => $member['id'], 'month' => $month]),
            fn($r) => (float) $r['amount_due'] > 0 || (float) $r['amount_paid'] > 0
        ));
        if (empty($rows)) {
            // Rows are created lazily: a month nobody opened yet is still owed once its due date has passed.
            return month_due_date($month, self::dueDayFor($member)) < date('Y-m-d') ? 'late' : 'unpaid';
        }
        $status = self::summarize($rows)['status'];
        return ['paid_late' => 'paid', 'partial_late' => 'partial'][$status] ?? $status;
    }

    /** Counts of memberMonthStatus() over the given members: [paid, partial, unpaid (incl. late), late, no_shares]. */
    public static function monthStatusCounts(array $members, string $month): array
    {
        $counts = ['paid' => 0, 'partial' => 0, 'unpaid' => 0, 'late' => 0, 'no_shares' => 0];
        foreach ($members as $m) {
            $status = self::memberMonthStatus($m, $month);
            if ($status === 'late') {
                $counts['late']++;
                $counts['unpaid']++;
            } else {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    /**
     * Purges any unpaid subscription rows that precede the active lots' actual start dates.
     * Prevents phantom historical rows when a lot's start date is moved forward or added later.
     */
    public static function purgeStalePreStartRows(int $memberId): void
    {
        $lots = ShareLot::activeFor($memberId);
        foreach ($lots as $lot) {
            $startMonth = ShareLot::startMonth($lot);
            if ($startMonth) {
                self::raw(
                    "DELETE FROM monthly_subscriptions WHERE member_id = ? AND lot_id = ? AND month < ? AND (amount_paid = 0 OR amount_paid IS NULL)",
                    [$memberId, (int) $lot['id'], $startMonth]
                );
            }
        }
    }

    /**
     * The member's subscription history. Rows that owe nothing and hold no money (voided when a lot was cancelled or merged
     * away, or the placeholder of a member with no shares) are not transactions, so they are left out. A row with money on
     * it stays, flagged with lot_status so the screens can show it belongs to a lot that no longer exists.
     */
    public static function forMember(int $memberId): array
    {
        self::purgeStalePreStartRows($memberId);
        $rows = self::raw(
            'SELECT s.*, l.status AS lot_status,
                    (SELECT t.transaction_date 
                     FROM transactions t 
                     WHERE t.category = "subscription" AND t.related_id = s.id 
                     ORDER BY t.id DESC LIMIT 1) AS payment_date
             FROM monthly_subscriptions s
             LEFT JOIN share_lots l ON l.id = s.lot_id
             WHERE s.member_id = ? AND NOT (s.amount_due = 0 AND s.amount_paid = 0)
             ORDER BY s.month DESC, s.lot_id ASC',
            [$memberId]
        );
        foreach ($rows as &$r) {
            $r['db_status'] = $r['status'];
            $r['status'] = self::statusOf($r);
        }
        unset($r);
        return $rows;
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
        if ($applyGracePeriod && self::graceEnabled() && $month === date('Y-m') && $dueDate < date('Y-m-d')) {
            $graceUntil = date('Y-m-d', strtotime('+' . self::gracePeriodDays() . ' days'));
        }
        // A lot with no shares owes nothing this month: that is trivially "paid".
        $status = $amountDue > 0 ? 'unpaid' : 'paid';

        $existing = self::first(['member_id' => $member['id'], 'month' => $month, 'lot_id' => $lotId]);
        if ($existing) {
            // A current/future month nobody has paid anything on follows the lot's latest share count / share value;
            // otherwise a lot resized after the row was created would get that month for free (or stay flagged as
            // owing a debt it no longer has).
            $untouched = (float) $existing['amount_paid'] == 0.0 && $month >= date('Y-m');
            // The same goes for the due day: an unpaid current/future row follows the day configured now, while a row
            // with money on it or a past month keeps the date it was billed with (history is never rewritten).
            if ($untouched && $existing['due_date'] !== $dueDate) {
                self::update($existing['id'], ['due_date' => $dueDate]);
                $existing['due_date'] = $dueDate;
            }
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
            // Shares cancelled from a lot that already has money on this month's row: the row must stop billing the
            // cancelled shares (what was paid stays on it, it just may now cover the whole month).
            $shrunk = (float) $existing['amount_paid'] > 0 && $month >= date('Y-m') && (int) $existing['shares_count_snapshot'] > $sharesCount;
            if ($shrunk) {
                self::update($existing['id'], [
                    'shares_count_snapshot' => $sharesCount,
                    'share_value_snapshot' => $shareValue,
                    'amount_due' => $amountDue,
                    'status' => (float) $existing['amount_paid'] >= $amountDue ? 'paid' : 'partial',
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
    /**
     * Every month of a cancelled lot: rows nobody paid are removed (they are not owed any more and must not show or
     * count anywhere), a row paid in part is closed at what was paid; fully paid rows stay as they are.
     */
    public static function clearCancelledLotRows(int $memberId, int $lotId): void
    {
        self::raw(
            'DELETE FROM monthly_subscriptions WHERE member_id = ? AND lot_id = ? AND (amount_paid = 0 OR amount_paid IS NULL)',
            [$memberId, $lotId]
        );
        self::raw(
            "UPDATE monthly_subscriptions SET amount_due = amount_paid, status = 'paid'
             WHERE member_id = ? AND lot_id = ? AND amount_paid > 0 AND amount_paid < amount_due",
            [$memberId, $lotId]
        );
    }

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
