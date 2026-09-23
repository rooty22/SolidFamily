<?php

namespace App\LiveTranslate;

use App\Models\Setting;

/**
 * Writes the translations of a candidate found by Resolver back where the text is stored.
 *
 * Every write:
 *  - only touches what the ref names (the table has to be listed in config/live_translate.php),
 *  - reads the stored value again and checks it is still the text the user clicked (nobody changed it meanwhile),
 *  - never blanks a language: an empty text in the popup means "leave what is stored".
 */
final class Writer
{
    private const STALE = 'The text was changed by someone else. Reload the page.';

    /**
     * @param string   $ref    reference produced by Resolver.
     * @param string[] $values new text per language code.
     * @param string   $norm   Text::norm() of the text the user clicked, to detect stale writes.
     * @return array{ok:bool,message:string,matched?:string}
     */
    public static function write(string $ref, array $values, string $norm): array
    {
        $parts = explode(':', $ref, 5);
        try {
            switch ($parts[0]) {
                case 'line':
                    return self::writeLine((string)($parts[1] ?? ''), (int)($parts[2] ?? 0), (string)($parts[3] ?? ''), (int)($parts[4] ?? 0), $values, $norm);
                case 'json':
                    return self::writeJson((string)($parts[1] ?? ''), (int)($parts[2] ?? 0), (string)($parts[3] ?? ''), (string)($parts[4] ?? ''), $values, $norm);
                case 'setting':
                    return self::writeSetting((string)($parts[1] ?? ''), $values, $norm);
                case 'jsetting':
                    return self::writeJsonSetting((string)($parts[1] ?? ''), (string)($parts[2] ?? ''), $values, $norm);
                case 'lang':
                    return self::writeLang(substr($ref, 5), $values, $norm);
            }
        } catch (\Throwable $e) {
            error_log('[live-translate] write ' . $ref . ': ' . $e->getMessage());
            return self::fail('Could not save. Check the server log.');
        }
        return self::fail('Unknown target.');
    }

    private static function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    private static function done(string $matched): array
    {
        return ['ok' => true, 'message' => 'ok', 'matched' => $matched];
    }

    /** Text typed in the popup: no markup, not too long. */
    public static function clean(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        return mb_substr(trim(strip_tags($value)), 0, 4000);
    }

    /** Same, on one line (list items and paragraphs: a line break would shift the lines that come after). */
    private static function oneLine(mixed $value): string
    {
        return trim((string)preg_replace('/\s*[\r\n]+\s*/', ' ', self::clean($value)));
    }

    /* ------------------------------------------------------------------ table rows */

    private static function writeLine(string $table, int $id, string $field, int $index, array $values, string $norm): array
    {
        $byLang = Resolver::fields($table)[$field] ?? null;
        if (!$byLang || $id < 1 || $index < 0) {
            return self::fail('Unknown field.');
        }

        $row = Store::fetchOne(
            'SELECT ' . implode(', ', array_map([Store::class, 'ident'], array_values($byLang))) . ' FROM ' . Store::ident($table) . ' WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
        if (!$row) {
            return self::fail('The record no longer exists.');
        }

        $raw = [];
        foreach ($byLang as $code => $column) {
            $raw[$code] = (string)($row[$column] ?? '');
        }
        $hit = null;
        foreach (Resolver::matchLines($raw, $norm) as $candidate) {
            if ($candidate['index'] === $index) {
                $hit = $candidate;
                break;
            }
        }
        if (!$hit) {
            return self::fail(self::STALE);
        }

        $data = [];
        foreach ($byLang as $code => $column) {
            $value = self::oneLine($values[$code] ?? '');
            if ($value === '') {
                continue;
            }
            $lines = Text::lines($raw[$code]);
            if ($index >= count($lines)) {
                continue; // that language has fewer lines: adding lines is a job for the full editor
            }
            $eol = str_contains($raw[$code], "\r\n") ? "\r\n" : "\n";
            $lines[$index] = Text::forStorage($value, $raw, false);
            $data[$column] = implode($eol, $lines);
        }
        if (!$data) {
            return self::fail('Nothing to save for this record.');
        }

        self::updateRow($table, $id, $data);
        return self::done($hit['matched']);
    }

    private static function updateRow(string $table, int $id, array $data): void
    {
        $sets = [];
        $params = ['__id' => $id];
        $i = 0;
        foreach ($data as $column => $value) {
            $sets[] = Store::ident($column) . ' = :v' . $i;
            $params['v' . $i++] = $value;
        }
        Store::execute('UPDATE ' . Store::ident($table) . ' SET ' . implode(', ', $sets) . ' WHERE `id` = :__id', $params);
    }

    /** @return array{0:array|null,1:string} decoded JSON of a column of a configured table, and its raw text. */
    private static function loadJsonColumn(string $table, int $id, string $column): array
    {
        if (!in_array($column, Resolver::jsonColumns($table), true) || $id < 1) {
            return [null, ''];
        }
        $row = Store::fetchOne('SELECT ' . Store::ident($column) . ' FROM ' . Store::ident($table) . ' WHERE `id` = :id LIMIT 1', ['id' => $id]);
        $raw = (string)($row[$column] ?? '');
        return [Json::decode($raw), $raw];
    }

    private static function writeJson(string $table, int $id, string $column, string $location, array $values, string $norm): array
    {
        [$data] = self::loadJsonColumn($table, $id, $column);
        if ($data === null) {
            return self::fail('Unknown field.');
        }
        $result = self::mergeLeaf($data, $location, $values, $norm);
        if (!$result['ok']) {
            return $result;
        }
        self::updateRow($table, $id, [$column => json_encode($result['data'], Json::FLAGS)]);
        return self::done($result['matched']);
    }

    /* ------------------------------------------------------------------ settings */

    private static function writeSetting(string $field, array $values, string $norm): array
    {
        $byLang = Resolver::settingField($field);
        if (!$byLang) {
            return self::fail('Unknown setting.');
        }
        $raw = Resolver::settingValues($byLang);
        $match = Resolver::matchGroup($raw, $norm);
        if (!$match) {
            return self::fail(self::STALE);
        }

        $written = 0;
        foreach ($byLang as $code => $key) {
            $value = self::clean($values[$code] ?? '');
            if ($value !== '') {
                Setting::set($key, Text::forStorage($value, $raw, false));
                $written++;
            }
        }
        return $written ? self::done($match['matched']) : self::fail('Nothing to save for this setting.');
    }

    private static function writeJsonSetting(string $key, string $location, array $values, string $norm): array
    {
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $key) || !in_array($key, Store::settingKeys(), true)) {
            return self::fail('Unknown setting.');
        }
        $data = Json::decode(Store::settings([$key])[$key] ?? '');
        if ($data === null) {
            return self::fail('Unknown setting.');
        }
        $result = self::mergeLeaf($data, $location, $values, $norm);
        if (!$result['ok']) {
            return $result;
        }
        Setting::set($key, json_encode($result['data'], Json::FLAGS));
        return self::done($result['matched']);
    }

    /**
     * Put the posted texts into the bilingual keys found at a location of decoded JSON.
     *
     * @return array{ok:bool,message?:string,data?:array,matched?:string}
     */
    private static function mergeLeaf(array $data, string $location, array $values, string $norm): array
    {
        $decoded = Json::decodeLocation($location);
        if ($decoded === null) {
            return self::fail('Unknown field.');
        }
        [$path, $base] = $decoded;

        $node = Json::getPath($data, $path);
        $strings = [];
        foreach ($node ?? [] as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $strings[] = $key;
            }
        }
        $byLang = Fields::group($strings)[$base] ?? null;
        if ($node === null || !$byLang) {
            return self::fail(self::STALE);
        }

        $raw = [];
        foreach ($byLang as $code => $key) {
            $raw[$code] = (string)$node[$key];
        }
        $match = Resolver::matchGroup($raw, $norm);
        if (!$match) {
            return self::fail(self::STALE);
        }

        $written = 0;
        foreach ($byLang as $code => $key) {
            $value = self::clean($values[$code] ?? '');
            if ($value !== '') {
                $node[$key] = Text::forStorage($value, $raw, false);
                $written++;
            }
        }
        if (!$written) {
            return self::fail('Nothing to save for this field.');
        }
        return ['ok' => true, 'data' => Json::setPath($data, $path, $node), 'matched' => $match['matched']];
    }

    /* ------------------------------------------------------------------ static texts */

    private static function writeLang(string $key, array $values, string $norm): array
    {
        $current = Resolver::langIndex()[$key] ?? null;
        if (!$current) {
            return self::fail('Unknown text.');
        }
        $match = Resolver::matchGroup($current, $norm);
        if (!$match) {
            return self::fail(self::STALE);
        }

        $written = 0;
        foreach (Languages::codes() as $code) {
            $value = self::clean($values[$code] ?? '');
            if ($value !== '') {
                $settingKey = Resolver::overrideKey($code, $key);
                if (strlen($settingKey) > 100) {
                    return self::fail('The key of this text is too long to be overridden.');
                }
                // Read by Lang::get() before the file of the language.
                Setting::set($settingKey, $value);
                $written++;
            }
        }
        return $written ? self::done($match['matched']) : self::fail('Nothing to save for this text.');
    }
}
