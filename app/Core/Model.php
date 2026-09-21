<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::connection();
    }

    public static function find($id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBy(string $column, $value): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE ' . $column . ' = ? LIMIT 1');
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return self::db()->query($sql)->fetchAll();
    }

    public static function where(array $conditions, string $orderBy = '', string $limit = ''): array
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        $sql = 'SELECT * FROM ' . static::$table . ($whereSql ? " WHERE $whereSql" : '');
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function first(array $conditions = [], string $orderBy = ''): ?array
    {
        $rows = self::where($conditions, $orderBy, '1');
        return $rows[0] ?? null;
    }

    public static function count(array $conditions = []): int
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        $sql = 'SELECT COUNT(*) as c FROM ' . static::$table . ($whereSql ? " WHERE $whereSql" : '');
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['c'];
    }

    public static function sum(string $column, array $conditions = []): float
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        $sql = 'SELECT COALESCE(SUM(' . $column . '), 0) as s FROM ' . static::$table . ($whereSql ? " WHERE $whereSql" : '');
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetch()['s'];
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = 'INSERT INTO ' . static::$table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($data);
        return (int) self::db()->lastInsertId();
    }

    public static function update($id, array $data): bool
    {
        $sets = implode(',', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $sql = 'UPDATE ' . static::$table . " SET $sets WHERE " . static::$primaryKey . ' = :__id';
        $data['__id'] = $id;
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($data);
    }

    public static function updateWhere(array $conditions, array $data): bool
    {
        [$whereSql, $whereParams] = self::buildWhere($conditions, 'w_');
        $sets = implode(',', array_map(fn($c) => "$c = :s_$c", array_keys($data)));
        $setParams = [];
        foreach ($data as $k => $v) {
            $setParams["s_$k"] = $v;
        }
        $sql = 'UPDATE ' . static::$table . " SET $sets" . ($whereSql ? " WHERE $whereSql" : '');
        $stmt = self::db()->prepare($sql);
        return $stmt->execute(array_merge($setParams, $whereParams));
    }

    public static function delete($id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?');
        return $stmt->execute([$id]);
    }

    public static function deleteWhere(array $conditions): bool
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        $sql = 'DELETE FROM ' . static::$table . ($whereSql ? " WHERE $whereSql" : '');
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function raw(string $sql, array $params = []): array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function rawOne(string $sql, array $params = [])
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function lastInsertId(): int
    {
        return (int) self::db()->lastInsertId();
    }

    protected static function buildWhere(array $conditions, string $prefix = 'p_'): array
    {
        if (empty($conditions)) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $key = $prefix . str_replace(['.', ' ', '(', ')'], '_', $column);
            if (is_array($value) && isset($value[0]) && in_array(strtoupper($value[0]), ['>', '<', '>=', '<=', '!=', 'LIKE'])) {
                $parts[] = "$column {$value[0]} :$key";
                $params[$key] = $value[1];
            } else {
                $parts[] = "$column = :$key";
                $params[$key] = $value;
            }
        }
        return [implode(' AND ', $parts), $params];
    }
}
