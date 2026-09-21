<?php

namespace App\Models;

use App\Core\Model;

class LoanRequest extends Model
{
    protected static string $table = 'loan_requests';

    public static function withMember(array $conditions = []): array
    {
        [$whereSql, $params] = self::joinWhere($conditions);
        $sql = 'SELECT loan_requests.*, members.name as member_name, members.mobile as member_mobile
                FROM loan_requests JOIN members ON members.id = loan_requests.member_id'
                . ($whereSql ? " WHERE $whereSql" : '') . ' ORDER BY loan_requests.created_at ASC';
        return self::raw($sql, $params);
    }

    public static function queuePosition(int $requestId): int
    {
        $pending = self::where(['status' => 'pending'], 'created_at ASC');
        foreach ($pending as $index => $request) {
            if ((int) $request['id'] === $requestId) {
                return $index + 1;
            }
        }
        return 0;
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
