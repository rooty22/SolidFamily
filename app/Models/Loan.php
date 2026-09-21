<?php

namespace App\Models;

use App\Core\Model;

class Loan extends Model
{
    protected static string $table = 'loans';

    public static function withMember(array $conditions = []): array
    {
        [$whereSql, $params] = self::joinWhere($conditions);
        $sql = 'SELECT loans.*, members.name as member_name, members.mobile as member_mobile
                FROM loans JOIN members ON members.id = loans.member_id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY loans.created_at DESC';
        return self::raw($sql, $params);
    }

    public static function forMember(int $memberId): array
    {
        return self::where(['member_id' => $memberId], 'created_at DESC');
    }

    protected static function joinWhere(array $conditions): array
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
