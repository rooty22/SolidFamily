<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, $default = null)
    {
        $row = self::rawOne('SELECT * FROM settings WHERE `key` = ? LIMIT 1', [$key]);
        return $row ? $row['value'] : $default;
    }

    public static function getAll(): array
    {
        $rows = self::all();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    public static function set(string $key, $value): void
    {
        $existing = self::rawOne('SELECT * FROM settings WHERE `key` = ? LIMIT 1', [$key]);
        if ($existing) {
            self::update($existing['id'], ['value' => $value]);
        } else {
            self::execute('INSERT INTO settings (`key`, value) VALUES (?, ?)', [$key, $value]);
        }
    }
}
