<?php

namespace App\Models;

use App\Core\Model;

class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function withMember(array $filters = []): array
    {
        $sql = 'SELECT transactions.*, members.name as member_name, admins.name as admin_name,
                       ms.month as sub_month, ms.lot_id as sub_lot_id, sl.shares_count as lot_shares_count
                FROM transactions
                JOIN members ON members.id = transactions.member_id
                LEFT JOIN admins ON admins.id = transactions.recorded_by
                LEFT JOIN monthly_subscriptions ms ON (transactions.category = "subscription" AND ms.id = transactions.related_id)
                LEFT JOIN share_lots sl ON sl.id = ms.lot_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= ' AND transactions.category = :category';
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['member_id'])) {
            $sql .= ' AND transactions.member_id = :member_id';
            $params['member_id'] = $filters['member_id'];
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND transactions.transaction_date >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND transactions.transaction_date <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND members.name LIKE :search';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY transactions.transaction_date DESC, transactions.id DESC';

        $rows = self::raw($sql, $params);

        // Enrich rows with friendly descriptive details
        $lotsCache = [];
        foreach ($rows as &$r) {
            $mid = (int) $r['member_id'];
            if (!isset($lotsCache[$mid])) {
                $lotsCache[$mid] = ShareLot::activeFor($mid);
            }
            $mActiveLots = $lotsCache[$mid];

            if ($r['category'] === 'subscription') {
                $lotLabel = '';
                if (!empty($r['sub_lot_id'])) {
                    $lotIdx = array_search((int) $r['sub_lot_id'], array_column($mActiveLots, 'id'));
                    $lotLabel = ($lotIdx !== false) ? ('السهم رقم ' . ($lotIdx + 1)) : ('الحصة #' . $r['sub_lot_id']);
                }
                
                $notes = trim((string) ($r['notes'] ?? ''));
                if ($notes !== '' && $notes !== 'اشتراك شهري') {
                    if ($lotLabel && !str_contains($notes, 'السهم') && !str_contains($notes, 'حصة')) {
                        $r['display_details'] = $notes . ' (' . $lotLabel . ')';
                    } else {
                        $r['display_details'] = $notes;
                    }
                } else {
                    $monthPart = !empty($r['sub_month']) ? ' لشهر ' . month_label($r['sub_month']) : '';
                    $r['display_details'] = 'اشتراك شهري' . $monthPart . ($lotLabel ? ' (' . $lotLabel . ')' : '');
                }
            } elseif ($r['category'] === 'founding') {
                $r['display_details'] = !empty($r['notes']) ? $r['notes'] : 'دفعة مبلغ تأسيس';
            } else {
                $r['display_details'] = $r['notes'] ?: '-';
            }
        }
        unset($r);

        return $rows;
    }

    public static function record(int $memberId, string $category, ?int $relatedId, float $amount, ?int $recordedBy, ?string $notes = null, ?string $date = null): int
    {
        return self::create([
            'member_id' => $memberId,
            'category' => $category,
            'related_id' => $relatedId,
            'amount' => $amount,
            'transaction_date' => $date ?: date('Y-m-d'),
            'status' => 'completed',
            'recorded_by' => $recordedBy,
            'notes' => $notes,
        ]);
    }
}
