<?php

namespace App\Models;

use App\Core\Model;

class ShareRequest extends Model
{
    protected static string $table = 'share_requests';

    public static function withMember(array $conditions = [], string $orderBy = 'share_requests.created_at DESC'): array
    {
        self::repairMissingMergeCounts();

        [$whereSql, $params] = self::buildWhereForJoin($conditions);
        $sql = 'SELECT share_requests.*, members.name as member_name, members.mobile as member_mobile, members.shares_count as member_shares
                FROM share_requests JOIN members ON members.id = share_requests.member_id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY ' . $orderBy;
        return self::raw($sql, $params);
    }

    /**
     * Self-healing fix for legacy merge requests that had shares_count as NULL.
     * Updates shares_count from either the generated share lot or the member's shares balance.
     */
    public static function repairMissingMergeCounts(): void
    {
        try {
            $pdo = \App\Core\Database::connection();
            // 1. Update approved merge requests from their resulting share_lot
            $pdo->exec("
                UPDATE share_requests sr
                JOIN share_lots sl ON sl.source_request_id = sr.id
                SET sr.shares_count = sl.shares_count
                WHERE sr.type = 'merge' AND (sr.shares_count IS NULL OR sr.shares_count = 0)
            ");
            // 2. Update any remaining merge requests from member's current shares_count
            $pdo->exec("
                UPDATE share_requests sr
                JOIN members m ON m.id = sr.member_id
                SET sr.shares_count = m.shares_count
                WHERE sr.type = 'merge' AND (sr.shares_count IS NULL OR sr.shares_count = 0) AND m.shares_count > 0
            ");
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    protected static function buildWhereForJoin(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $key = str_replace(['.', ' '], '_', $column);
            $parts[] = "$column = :$key";
            $params[$key] = $value;
        }
        return [implode(' AND ', $parts), $params];
    }
}
