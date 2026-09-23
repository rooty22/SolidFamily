<?php

namespace App\LiveTranslate;

use App\Core\Database;
use PDO;

/** Database access of the module: table structure, the settings table, LIKE patterns. */
final class Store
{
    private static array $columns = [];
    private static ?array $settingKeys = null;

    public static function pdo(): PDO
    {
        return Database::connection();
    }

    /** @return array[] */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::fetchAll($sql, $params)[0] ?? null;
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): void
    {
        self::pdo()->prepare($sql)->execute($params);
    }

    /** Backtick-quoted identifier; only plain names are accepted (they also come from the config, never from a request). */
    public static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('Bad identifier: ' . $name);
        }
        return '`' . $name . '`';
    }

    /** @return string[] columns of a table, empty when the table does not exist. */
    public static function columns(string $table): array
    {
        if (!isset(self::$columns[$table])) {
            try {
                $rows = self::fetchAll('SHOW COLUMNS FROM ' . self::ident($table));
                self::$columns[$table] = array_map('strval', array_column($rows, 'Field'));
            } catch (\Throwable $e) {
                self::$columns[$table] = [];
            }
        }
        return self::$columns[$table];
    }

    /** @return string[] every key of the settings table. */
    public static function settingKeys(): array
    {
        if (self::$settingKeys === null) {
            self::$settingKeys = array_map('strval', array_column(self::fetchAll('SELECT `key` FROM settings'), 'key'));
        }
        return self::$settingKeys;
    }

    /**
     * @param string[] $keys
     * @return array<string,string> value of each of the keys that exist.
     */
    public static function settings(array $keys): array
    {
        if (!$keys) {
            return [];
        }
        $params = [];
        foreach (array_values($keys) as $i => $key) {
            $params['k' . $i] = $key;
        }
        $rows = self::fetchAll('SELECT `key`, `value` FROM settings WHERE `key` IN (:' . implode(', :', array_keys($params)) . ')', $params);
        $out = [];
        foreach ($rows as $row) {
            $out[(string)$row['key']] = (string)$row['value'];
        }
        return $out;
    }

    /** '%text%' for LIKE ... ESCAPE '!' with the wildcards of the text escaped. */
    public static function like(string $text): string
    {
        return Text::likePattern($text);
    }
}
