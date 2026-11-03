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
        return self::where(['member_id' => $memberId, 'status' => 'active'], 'created_at ASC, id ASC');
    }

    /**
     * The "السهم رقم N" number of each of the member's lots, keyed by lot id. Active lots are numbered 1..n oldest first
     * (the same order every screen already uses); lots that were merged or cancelled continue after them, so a lot is
     * never shown by its database id and no two lots of one member share a number.
     */
    public static function numbersFor(int $memberId): array
    {
        $numbers = [];
        $next = 1;
        foreach (self::where(['member_id' => $memberId], "status = 'active' DESC, created_at ASC, id ASC") as $lot) {
            $numbers[(int) $lot['id']] = $next++;
        }
        return $numbers;
    }

    /** "السهم رقم N" / "Share #N" for one lot; $sharesCount, when given, is appended. */
    public static function label(int $memberId, int $lotId, ?int $sharesCount = null): string
    {
        $n = self::numbersFor($memberId)[$lotId] ?? null;
        $text = $n === null
            ? (is_rtl() ? 'سهم غير معروف' : 'Unknown share')
            : (is_rtl() ? 'السهم رقم ' . $n : 'Share #' . $n);
        if ($sharesCount !== null) {
            $text .= is_rtl() ? ' (' . number_format($sharesCount) . ' سهم)' : ' (' . number_format($sharesCount) . ' shares)';
        }
        return $text;
    }

    /** The first month (YYYY-MM) a lot bills for: the month it was created. Nothing is owed for earlier months. */
    public static function startMonth(array $lot): string
    {
        return substr((string) $lot['created_at'], 0, 7);
    }

    /** Full subscription start date (YYYY-MM-DD) containing day, month, and year. */
    public static function startDate(array $lot): string
    {
        return !empty($lot['created_at']) ? date('Y-m-d', strtotime($lot['created_at'])) : '-';
    }

    /** Date when the 6-month threshold is reached (clamped to end of month for short months). */
    public static function sixMonthsTargetDate(array $lot): string
    {
        if (empty($lot['created_at'])) {
            return '-';
        }
        try {
            $d = new \DateTime(substr($lot['created_at'], 0, 10));
            $day = (int) $d->format('j');
            $target = clone $d;
            $target->modify('first day of +6 months');
            $maxDays = (int) $target->format('t');
            $target->setDate((int) $target->format('Y'), (int) $target->format('n'), min($day, $maxDays));
            return $target->format('Y-m-d');
        } catch (\Throwable $e) {
            return date('Y-m-d', strtotime('+6 months', strtotime($lot['created_at'])));
        }
    }

    /** Check if 6 months have passed since this share lot was subscribed (as of $asOf, default today). */
    public static function isSixMonthsPassed(array $lot, ?string $asOf = null): bool
    {
        if (empty($lot['created_at'])) {
            return false;
        }
        $targetStr = self::sixMonthsTargetDate($lot);
        if ($targetStr === '-') {
            return false;
        }
        return ($asOf ?: date('Y-m-d')) >= $targetStr;
    }

    /** Calculate how many full months have passed since this share lot was started (as of $asOf, default today). */
    public static function monthsPassed(array $lot, ?string $asOf = null): int
    {
        if (empty($lot['created_at'])) {
            return 0;
        }
        try {
            $start = new \DateTime(substr($lot['created_at'], 0, 10));
            $now = new \DateTime($asOf ?: date('Y-m-d'));
            if ($now < $start) {
                return 0;
            }
            $diff = $start->diff($now);
            return ($diff->y * 12) + $diff->m;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Whether only ONE loan at a time may be open on a share: a share that still owes a loan (active / partial) cannot
     * take another until it is repaid. Set to false to allow several open loans on the same share.
     */
    public const ONE_OPEN_LOAN_PER_LOT = true;

    /** The lot's id plus the ids of every lot merged into it (directly or through other merges). */
    public static function withMergedChildren(int $lotId): array
    {
        $ids = [$lotId];
        $queue = [$lotId];
        while ($queue) {
            $parent = array_shift($queue);
            foreach (self::where(['merged_into_id' => $parent]) as $child) {
                if (!in_array((int) $child['id'], $ids, true)) {
                    $ids[] = (int) $child['id'];
                    $queue[] = (int) $child['id'];
                }
            }
        }
        return $ids;
    }

    /** A loan of this share (or of a lot merged into it) that is not fully repaid yet, or null. */
    public static function openLoan(int $lotId): ?array
    {
        $ids = self::withMergedChildren($lotId);
        $in = implode(',', array_fill(0, count($ids), '?'));
        return Loan::rawOne("SELECT * FROM loans WHERE lot_id IN ($in) AND status IN ('active', 'partial') ORDER BY id DESC LIMIT 1", $ids);
    }

    /**
     * Complete loan-eligibility evaluation of ONE share (lot) -- never of the member's shares as a whole:
     * 1) the share is active,
     * 2) 6 months passed since THIS share's subscription start date (documented with day, month, year),
     * 3) the founding amount of THIS share is fully paid (what was paid for the member's other shares does not count),
     * 4) this share has no loan that is still being repaid.
     * $asOf (YYYY-MM-DD) evaluates the 6-month rule on another day, e.g. the date of a back-dated loan.
     */
    public static function eligibilityDetails(array $lot, array $member, ?array $founding = null, ?string $asOf = null): array
    {
        $asOf = $asOf ?: date('Y-m-d');
        $startDate = self::startDate($lot);
        $targetDate = self::sixMonthsTargetDate($lot);
        $sixMonthsMet = self::isSixMonthsPassed($lot, $asOf);
        $monthsPassed = self::monthsPassed($lot, $asOf);
        $monthsRemaining = max(0, 6 - $monthsPassed);
        $remLabelAr = $monthsRemaining === 1 ? 'شهر واحد' : ($monthsRemaining === 2 ? 'شهران' : ($monthsRemaining >= 3 && $monthsRemaining <= 10 ? "{$monthsRemaining} أشهر" : "{$monthsRemaining} شهراً"));

        $founding ??= FoundingAmount::ensureForMember((int) $member['id']);
        $isActive = ($lot['status'] ?? 'active') === 'active';

        $row = FoundingAmount::lotBreakdown((int) $member['id'], $founding)[(int) $lot['id']] ?? null;
        if ($row === null) {
            // Not an active share any more (merged / cancelled): it owes nothing and nothing counts for it.
            $row = ['required' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'status' => 'paid', 'met' => true];
        }

        $openLoan = $isActive ? self::openLoan((int) $lot['id']) : null;

        $blockers = [];
        if (!$isActive) {
            $blockers[] = 'inactive';
        }
        if (!$sixMonthsMet) {
            $blockers[] = 'six_months';
        }
        if (!$row['met']) {
            $blockers[] = 'founding';
        }
        if (self::ONE_OPEN_LOAN_PER_LOT && $openLoan !== null) {
            $blockers[] = 'open_loan';
        }

        return [
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'months_passed' => $monthsPassed,
            'months_remaining' => $monthsRemaining,
            'months_remaining_label' => $remLabelAr,
            'six_months_met' => $sixMonthsMet,
            'founding_required' => $row['required'],
            'founding_paid' => $row['paid'],
            'founding_remaining' => $row['remaining'],
            'founding_status' => $row['status'],
            'founding_paid_per_share' => $row['paid'], // kept for older views: now the amount paid for THIS share
            'founding_met' => $row['met'],
            'open_loan' => $openLoan,
            'blockers' => $blockers,
            'is_eligible' => $blockers === [],
        ];
    }

    /** Human-readable (Arabic) reasons a share cannot take a loan, from eligibilityDetails()['blockers']. */
    public static function blockerMessages(array $elig, string $label): array
    {
        $out = [];
        foreach ($elig['blockers'] as $b) {
            $out[] = match ($b) {
                'inactive' => "{$label} ليس سهماً نشطاً.",
                'six_months' => "{$label}: لم تمضِ 6 أشهر على بداية الاشتراك (تاريخ الاستحقاق " . date_ar($elig['target_date']) . ' - متبقي ' . $elig['months_remaining_label'] . ').',
                'founding' => "{$label}: مبلغ التأسيس غير مكتمل (مسدد " . money($elig['founding_paid']) . ' من ' . money($elig['founding_required']) . ').',
                'open_loan' => "{$label}: عليه قرض قائم رقم " . ($elig['open_loan']['id'] ?? '') . ' لم يُسدد بالكامل بعد.',
                default => '',
            };
        }
        return array_values(array_filter($out));
    }

    /** The admin note of a share ('' when none). */
    public static function note(array $lot): string
    {
        return trim((string) ($lot['admin_note'] ?? ''));
    }

    /** The note the MEMBER wrote about this share ('' when none). */
    public static function memberNote(array $lot): string
    {
        return trim((string) ($lot['member_note'] ?? ''));
    }

    /** Saves (or, when empty, deletes) the member's own note of ONE share. */
    public static function setMemberNote(int $lotId, ?string $note): void
    {
        $note = trim((string) $note);
        self::update($lotId, ['member_note' => $note === '' ? null : $note]);
    }

    /** Saves (or, when empty, deletes) the note of ONE share. Notes never touch the member's other shares. */
    public static function setNote(int $lotId, ?string $note): void
    {
        $note = trim((string) $note);
        self::update($lotId, ['admin_note' => $note === '' ? null : $note]);
    }

    /**
     * Reduce active lots oldest-first by $sharesToCancel; a lot emptied to 0 is cancelled (see markCancelled()).
     * Returns the actual total removed. The caller keeps members.shares_count in step.
     */
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
                self::markCancelled($memberId, (int) $lot['id']);
            }
            $sharesToCancel -= $take;
            $removed += $take;
        }
        return $removed;
    }

    /**
     * A cancelled lot owes nothing any more: it gets status 'cancelled' and its unpaid dues are cleared from every month
     * (a month with part of it paid is closed at what was paid). Money really paid on it stays in the history.
     */
    public static function markCancelled(int $memberId, int $lotId): void
    {
        self::update($lotId, ['status' => 'cancelled']);
        MonthlySubscription::clearCancelledLotRows($memberId, $lotId);
    }

    /**
     * Cancel one of the member's active lots (admin "cancel" button): the lot is cancelled, its shares leave the
     * member's total and the founding amount follows. Returns the shares removed, or null when it is not an active
     * lot of this member.
     */
    public static function cancelLot(int $memberId, int $lotId): ?int
    {
        $lot = self::find($lotId);
        if (!$lot || (int) $lot['member_id'] !== $memberId || $lot['status'] !== 'active') {
            return null;
        }
        self::markCancelled($memberId, $lotId);
        $member = Member::find($memberId);
        Member::update($memberId, ['shares_count' => max(0, (int) $member['shares_count'] - (int) $lot['shares_count'])]);
        FoundingAmount::ensureForMember($memberId);
        return (int) $lot['shares_count'];
    }

    /** The member's active lots whose ids are in $lotIds (null = every active lot), oldest first. */
    public static function activeAmong(int $memberId, ?array $lotIds): array
    {
        $active = self::activeFor($memberId);
        if ($lotIds === null) {
            return $active;
        }
        $wanted = array_map('intval', $lotIds);
        return array_values(array_filter($active, fn($l) => in_array((int) $l['id'], $wanted, true)));
    }

    /** Parses share_requests.lot_ids ("3,5") into a list of ids; null (every active lot) when it is empty or unset. */
    public static function parseLotIds(?string $csv): ?array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $csv)), fn($i) => $i > 0)));
        return $ids ?: null;
    }

    /**
     * Combine the given active lots (null = every active lot) into a single new one; those lots are marked 'merged' and
     * any other lot of the member is left untouched. The merged lot is worth a single share (merging two 100 SAR shares
     * leaves one 100 SAR share, not 200), so members.shares_count drops by whatever the merge folded away.
     * Returns the new lot, or null when fewer than two lots were selected.
     */
    public static function mergeLots(int $memberId, ?array $lotIds, ?int $dueDay, ?int $sourceRequestId = null): ?array
    {
        $selected = self::activeAmong($memberId, $lotIds);
        if (count($selected) < 2) {
            return null;
        }

        $removedShares = (int) array_sum(array_column($selected, 'shares_count')) - 1;
        foreach ($selected as $lot) {
            self::update($lot['id'], ['status' => 'merged']);
        }

        $member = Member::find($memberId);
        if ($member && $removedShares > 0) {
            Member::update($memberId, ['shares_count' => max(0, (int) $member['shares_count'] - $removedShares)]);
        }

        // The notes the admin kept on the folded shares are not lost: they continue on the merged share.
        $notes = array_values(array_filter(array_map(fn($l) => self::note($l), $selected)));
        $memberNotes = array_values(array_filter(array_map(fn($l) => self::memberNote($l), $selected)));
        $id = self::create([
            'member_id' => $memberId,
            'shares_count' => 1,
            'subscription_due_day' => $dueDay,
            'status' => 'active',
            'source_request_id' => $sourceRequestId,
            'admin_note' => $notes ? mb_substr(implode(' | ', $notes), 0, 500) : null,
            'member_note' => $memberNotes ? mb_substr(implode(' | ', $memberNotes), 0, 500) : null,
        ]);

        // Remember what each folded lot became, so splitLot() can bring the originals back untouched.
        foreach ($selected as $lot) {
            self::update($lot['id'], ['merged_into_id' => $id]);
        }
        ShareMergeLog::record($memberId, $id, 'merge', $removedShares);

        return self::find($id);
    }

    /** The original lots that were folded into the given merged lot, oldest first. */
    public static function mergedChildren(int $lotId): array
    {
        return self::where(['merged_into_id' => $lotId, 'status' => 'merged'], 'created_at ASC, id ASC');
    }

    /** The member's active lots that came from a merge and can still be split back: [lot + 'children' => original lots]. */
    public static function splittable(int $memberId): array
    {
        $out = [];
        foreach (self::activeFor($memberId) as $lot) {
            $children = self::mergedChildren((int) $lot['id']);
            if (count($children) >= 2) {
                $out[] = $lot + ['children' => $children];
            }
        }
        return $out;
    }

    /**
     * Undo a merge: the merged lot is retired and the lots it was made from become active again with their own share
     * counts (so each is worth what it was before), start dates and due days. members.shares_count is restored to
     * match. What was already paid this month on the merged lot's row is carried onto the restored rows (oldest
     * first, never more than each one owes) and the merged row is voided. Returns the restored lots, or null when
     * the lot is not an active merged lot with its originals on record.
     */
    public static function splitLot(int $memberId, int $lotId): ?array
    {
        $lot = self::find($lotId);
        if (!$lot || (int) $lot['member_id'] !== $memberId || $lot['status'] !== 'active') {
            return null;
        }
        $children = self::mergedChildren($lotId);
        if (count($children) < 2) {
            return null;
        }

        $month = date('Y-m');
        $mergedRow = MonthlySubscription::first(['member_id' => $memberId, 'month' => $month, 'lot_id' => $lotId]);
        $carriedPaid = (float) ($mergedRow['amount_paid'] ?? 0);

        self::update($lotId, ['status' => 'merged']);
        foreach ($children as $child) {
            self::update($child['id'], ['status' => 'active', 'merged_into_id' => null]);
        }

        $member = Member::find($memberId);
        if ($member) {
            $restored = (int) array_sum(array_column($children, 'shares_count'));
            Member::update($memberId, ['shares_count' => max(0, (int) $member['shares_count'] - (int) $lot['shares_count'] + $restored)]);
            ShareMergeLog::record($memberId, $lotId, 'split', $restored - (int) $lot['shares_count']);
        }

        MonthlySubscription::voidRowsOfLots($memberId, [$lotId], true, $month);
        $rowsByLot = [];
        foreach (MonthlySubscription::ensureMonthExistsForMember($memberId, $month) as $row) {
            $rowsByLot[(int) $row['lot_id']] = $row;
        }

        foreach ($children as $child) {
            $row = $rowsByLot[(int) $child['id']] ?? null;
            if ($carriedPaid <= 0 || !$row) {
                continue;
            }
            $paid = min((float) $row['amount_due'], $carriedPaid);
            if ($paid <= 0) {
                continue;
            }
            $carriedPaid -= $paid;
            MonthlySubscription::update($row['id'], [
                'amount_paid' => $paid,
                'status' => $paid >= (float) $row['amount_due'] ? 'paid' : 'partial',
            ]);
        }

        // Founding money paid for the merged share itself (after the merge) goes back to the shares it was made from;
        // what was paid for each original share while it stood alone is already on that share.
        $owed = [];
        foreach (FoundingAmount::lotBreakdown($memberId) as $lotId => $b) {
            if (in_array($lotId, array_map(fn($c) => (int) $c['id'], $children), true)) {
                $owed[$lotId] = $b['remaining'];
            }
        }
        FoundingAllocation::moveToLots($lotId, $owed);

        return $children;
    }

    /**
     * mergeLots() plus the billing side: what was already paid this month on the merged lots' rows is carried onto the
     * new lot's row (never lost, never charged twice) and the old rows are voided. Returns the new lot, or null when
     * fewer than two lots were selected (nothing changes).
     */
    public static function mergeWithBilling(int $memberId, ?array $lotIds, ?int $dueDay, ?int $sourceRequestId = null): ?array
    {
        $month = date('Y-m');
        $selectedIds = array_map(fn($l) => (int) $l['id'], self::activeAmong($memberId, $lotIds));

        $carriedPaid = 0.0;
        foreach ($selectedIds as $oldLotId) {
            $oldRow = MonthlySubscription::first(['member_id' => $memberId, 'month' => $month, 'lot_id' => $oldLotId]);
            $carriedPaid += (float) ($oldRow['amount_paid'] ?? 0);
        }

        $merged = self::mergeLots($memberId, $lotIds, $dueDay, $sourceRequestId);
        if (!$merged) {
            return null;
        }

        MonthlySubscription::voidRowsOfLots($memberId, $selectedIds, true, $month);
        $rows = MonthlySubscription::ensureMonthExistsForMember($memberId, $month);

        if ($carriedPaid > 0) {
            foreach ($rows as $row) {
                if ((int) $row['lot_id'] === (int) $merged['id']) {
                    $paid = min((float) $row['amount_due'], $carriedPaid);
                    MonthlySubscription::update($row['id'], [
                        'amount_paid' => $paid,
                        'status' => $paid >= (float) $row['amount_due'] ? 'paid' : ($paid > 0 ? 'partial' : $row['status']),
                    ]);
                    break;
                }
            }
        }

        return $merged;
    }

    /**
     * Make the active lots add up to members.shares_count (the single total everything else uses). Used when an admin
     * sets a member's shares directly instead of through an approved request: extra shares become a new lot, removed
     * shares are taken from the oldest lots first. Returns the ids of lots that ended up emptied.
     */
    public static function syncToMemberTotal(int $memberId, ?string $createdAt = null): array
    {
        $member = Member::find($memberId);
        if (!$member) {
            return [];
        }
        $total = (int) $member['shares_count'];
        $active = self::activeFor($memberId);
        $sum = (int) array_sum(array_column($active, 'shares_count'));

        if ($total > $sum) {
            $lotData = ['member_id' => $memberId, 'shares_count' => $total - $sum];
            if ($createdAt) {
                $lotData['created_at'] = $createdAt;
            } elseif (count($active) === 0 && !empty($member['created_at'])) {
                // Initial lot creation uses member registration date
                $lotData['created_at'] = $member['created_at'];
            } else {
                // Additional shares added later start at current timestamp
                $lotData['created_at'] = date('Y-m-d H:i:s');
            }
            self::create($lotData);
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
