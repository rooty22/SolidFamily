<?php

namespace App\Models;

use App\Core\Model;

class ShareRequest extends Model
{
    protected static string $table = 'share_requests';

    public static function withMember(array $conditions = [], string $orderBy = 'share_requests.created_at DESC'): array
    {
        [$whereSql, $params] = self::buildWhereForJoin($conditions);
        $sql = 'SELECT share_requests.*, members.name as member_name, members.mobile as member_mobile
                FROM share_requests JOIN members ON members.id = share_requests.member_id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY ' . $orderBy;
        return self::raw($sql, $params);
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
