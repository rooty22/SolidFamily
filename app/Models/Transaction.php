<?php

namespace App\Models;

use App\Core\Model;

class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function withMember(array $filters = []): array
    {
        $sql = 'SELECT transactions.*, members.name as member_name, admins.name as admin_name
                FROM transactions
                JOIN members ON members.id = transactions.member_id
                LEFT JOIN admins ON admins.id = transactions.recorded_by
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

        return self::raw($sql, $params);
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
