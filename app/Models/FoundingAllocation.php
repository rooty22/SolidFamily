<?php

namespace App\Models;

use App\Core\Model;

/**
 * Which share (share_lots row, "السهم رقم N") a founding payment was paid for. A founding payment stays one row of
 * founding_payments (and one ledger transaction); its allocations say how much of it covers which share, so the
 * amount paid for share 1 is never spread over the member's other shares.
 */
class FoundingAllocation extends Model
{
    protected static string $table = 'founding_payment_allocations';

    /** [lot id => total allocated to that exact lot] for every lot of the member (merged / cancelled ones included). */
    public static function totalsByLot(int $memberId): array
    {
        $out = [];
        foreach (self::raw(
            'SELECT a.lot_id, SUM(a.amount) AS total FROM founding_payment_allocations a
               JOIN share_lots l ON l.id = a.lot_id WHERE l.member_id = ? GROUP BY a.lot_id',
            [$memberId]
        ) as $r) {
            $out[(int) $r['lot_id']] = round((float) $r['total'], 2);
        }
        return $out;
    }

    /** Allocations of the member with their payment, oldest payment first: used for the per-share payment history. */
    public static function forMember(int $memberId): array
    {
        return self::raw(
            'SELECT a.*, p.payment_date, p.notes AS payment_notes, p.amount AS payment_amount
               FROM founding_payment_allocations a
               JOIN founding_payments p ON p.id = a.payment_id
              WHERE p.member_id = ? ORDER BY p.payment_date ASC, a.id ASC',
            [$memberId]
        );
    }

    /** The member's payments with what is still not assigned to a share: [payment + 'unallocated']. Only those with something left. */
    public static function unallocatedPayments(int $memberId): array
    {
        $rows = self::raw(
            'SELECT p.*, p.amount - COALESCE((SELECT SUM(a.amount) FROM founding_payment_allocations a WHERE a.payment_id = p.id), 0) AS unallocated
               FROM founding_payments p WHERE p.member_id = ? ORDER BY p.payment_date ASC, p.id ASC',
            [$memberId]
        );
        $out = [];
        foreach ($rows as $r) {
            $r['unallocated'] = round((float) $r['unallocated'], 2);
            if ($r['unallocated'] > 0) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /** Total founding money of the member that is not assigned to any share. */
    public static function unallocatedTotal(int $memberId): float
    {
        return round(array_sum(array_column(self::unallocatedPayments($memberId), 'unallocated')), 2);
    }

    /** Records that $amount of the payment was paid for the lot. */
    public static function allocate(int $paymentId, int $lotId, float $amount): int
    {
        return self::create(['payment_id' => $paymentId, 'lot_id' => $lotId, 'amount' => round($amount, 2)]);
    }

    /**
     * Moves what was allocated to a retired merged lot onto the lots it was made from (oldest first, each up to what
     * it still owes; the rest goes to the last one). Used when a merged lot is split back.
     * $owed is [lot id => amount still owed by that lot].
     */
    public static function moveToLots(int $fromLotId, array $owed): void
    {
        $rows = self::where(['lot_id' => $fromLotId], 'id ASC');
        $targets = array_keys($owed);
        if (!$rows || !$targets) {
            return;
        }
        $last = end($targets);
        foreach ($rows as $row) {
            $left = round((float) $row['amount'], 2);
            foreach ($targets as $lotId) {
                if ($left <= 0) {
                    break;
                }
                $take = $lotId === $last ? $left : round(min($left, max(0.0, (float) $owed[$lotId])), 2);
                if ($take <= 0) {
                    continue;
                }
                $owed[$lotId] = round((float) $owed[$lotId] - $take, 2);
                $left = round($left - $take, 2);
                self::allocate((int) $row['payment_id'], (int) $lotId, $take);
            }
            self::delete((int) $row['id']);
        }
    }
}
