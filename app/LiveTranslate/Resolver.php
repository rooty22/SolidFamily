<?php

namespace App\LiveTranslate;

/**
 * Finds where a visible text is really stored, so it can be translated in place.
 *
 * A candidate is a stored value whose text in at least one language is exactly the visible text. Nothing is matched
 * by substring: a text that is only a fragment of a bigger value has no candidate and is handled by the dictionary.
 *
 * Each candidate has an opaque "ref" that App\LiveTranslate\Writer understands:
 *   line:{table}:{id}:{field}:{n}      n-th line of the bilingual columns of a row (a title is its line 0;
 *                                      the paragraphs of "content" / "content_en" are their lines)
 *   json:{table}:{id}:{column}:{loc}   bilingual keys inside a JSON column of a row (page sections, form texts)
 *   setting:{field}                    bilingual settings (site_name + site_name_en, ...)
 *   jsetting:{key}:{loc}               bilingual keys inside a JSON setting (navigation_menu_json)
 *   lang:{key}                         a static text of resources/lang/<code>.php, saved as a "trans_*" override
 */
final class Resolver
{
    private static ?array $langIndex = null;

    public static function config(): array
    {
        return Languages::config();
    }

    /**
     * @param string $text visible text.
     * @param string $path path of the page the text was clicked on, its own row is listed first.
     * @return array[]
     */
    public static function find(string $text, string $path = ''): array
    {
        $norm = Text::norm($text);
        if (!Text::isPlainCandidate($norm)) {
            return [];
        }

        $needle = Text::likeNeedle($norm);
        $patterns = [Store::like($needle)];
        $escaped = Json::escapedNeedle($needle);
        if ($escaped !== $needle) {
            $patterns[] = Store::like($escaped);
        }

        $found = [];
        foreach (['findLang' => [$norm], 'findSettings' => [$norm, $patterns], 'findRows' => [$norm, $patterns, $path]] as $method => $args) {
            try {
                $found = array_merge($found, self::$method(...$args));
            } catch (\Throwable $e) {
                error_log('[live-translate] ' . $method . ': ' . $e->getMessage());
            }
        }

        // The page's own row first, then the places shared by the whole site, then anything else.
        usort($found, static function (array $a, array $b): int {
            if ($a['current'] !== $b['current']) {
                return $a['current'] ? -1 : 1;
            }
            return self::weight($a) <=> self::weight($b);
        });

        return array_slice($found, 0, (int)(self::config()['limit'] ?? 40));
    }

    private static function weight(array $candidate): int
    {
        return ['setting' => 1, 'lang' => 2, 'json' => 3, 'field' => 3, 'line' => 4][$candidate['kind']] ?? 5;
    }

    /**
     * Compare stored raw values (one per language) with the visible text.
     *
     * @param string[] $raw stored value per language code.
     * @return array{values:array,matched:string}|null texts per language and the language that matched.
     */
    public static function matchGroup(array $raw, string $norm): ?array
    {
        foreach (Languages::codes() as $code) {
            $block = $raw[$code] ?? '';
            if (is_string($block) && strlen($block) <= 20000 && Text::isPlainCandidate($block) && Text::norm($block) === $norm) {
                return ['values' => self::decoded($raw), 'matched' => $code];
            }
        }
        return null;
    }

    /** Text of the enabled languages, as typed (stored values may hold entities such as &amp;). */
    private static function decoded(array $raw): array
    {
        $out = [];
        foreach (Languages::codes() as $code) {
            $out[$code] = isset($raw[$code]) && is_string($raw[$code]) ? Text::decode(trim($raw[$code])) : '';
        }
        return $out;
    }

    /* ------------------------------------------------------------------ tables (title + title_en, JSON columns) */

    /** Bilingual fields of a configured table: field => [language code => column]. */
    public static function fields(string $table): array
    {
        if (!isset(self::config()['tables'][$table])) {
            return [];
        }
        $json = (array)(self::config()['tables'][$table]['json'] ?? []);
        return Fields::group(array_values(array_diff(Store::columns($table), $json)));
    }

    /** @return string[] JSON columns of a configured table that exist. */
    public static function jsonColumns(string $table): array
    {
        $configured = (array)(self::config()['tables'][$table]['json'] ?? []);
        return array_values(array_intersect($configured, Store::columns($table)));
    }

    private static function findRows(string $norm, array $patterns, string $path): array
    {
        $segments = array_map('rawurldecode', array_filter(explode('/', trim($path, '/')), 'strlen'));
        $out = [];

        foreach (self::config()['tables'] ?? [] as $table => $info) {
            $fields = self::fields($table);
            $jsonColumns = self::jsonColumns($table);
            $columns = $fields ? array_merge(...array_values(array_map('array_values', $fields))) : [];
            if (!$columns && !$jsonColumns) {
                continue;
            }
            $hasSlug = in_array('slug', Store::columns($table), true);

            $where = [];
            $params = [];
            $i = 0;
            foreach (array_merge($columns, $jsonColumns) as $column) {
                foreach ($patterns as $pattern) {
                    $where[] = Store::ident($column) . ' LIKE :n' . $i . " ESCAPE '!'";
                    $params['n' . $i++] = $pattern;
                }
            }
            $select = array_merge(['id'], $hasSlug ? ['slug'] : [], $columns, $jsonColumns);
            $rows = Store::fetchAll(
                'SELECT ' . implode(', ', array_map([Store::class, 'ident'], array_unique($select))) . ' FROM ' . Store::ident($table)
                . ' WHERE ' . implode(' OR ', $where) . ' LIMIT 200',
                $params
            );

            foreach ($rows as $row) {
                $current = $hasSlug && !empty($row['slug']) && in_array(rawurldecode((string)$row['slug']), $segments, true);

                foreach ($fields as $field => $byLang) {
                    $raw = [];
                    foreach ($byLang as $code => $column) {
                        $raw[$code] = (string)($row[$column] ?? '');
                    }
                    foreach (self::matchLines($raw, $norm) as $hit) {
                        $out[] = [
                            'ref' => 'line:' . $table . ':' . $row['id'] . ':' . $field . ':' . $hit['index'],
                            'kind' => $hit['single'] ? 'field' : 'line',
                            'label' => self::rowLabel($table, $info, $row, $fields, $field),
                            'edit_url' => self::editUrl($info, $row),
                            'current' => $current,
                            'values' => $hit['values'],
                            'matched' => $hit['matched'],
                        ];
                    }
                }

                foreach ($jsonColumns as $column) {
                    $data = Json::decode((string)($row[$column] ?? ''));
                    foreach ($data ? Json::leaves($data) : [] as $leaf) {
                        $match = self::matchGroup($leaf['raw'], $norm);
                        if (!$match) {
                            continue;
                        }
                        $out[] = [
                            'ref' => 'json:' . $table . ':' . $row['id'] . ':' . $column . ':' . Json::encodeLocation($leaf['path'], $leaf['base']),
                            'kind' => 'json',
                            'label' => self::rowLabel($table, $info, $row, $fields, null) . ' · ' . self::leafLabel($column, $leaf),
                            'edit_url' => self::editUrl($info, $row),
                            'current' => $current,
                            'values' => $match['values'],
                            'matched' => $match['matched'],
                        ];
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Lines of a field (in every language) that are the visible text. A single-line value is its line 0.
     *
     * @param string[] $raw stored value per language code.
     * @return array<int, array{index:int,single:bool,values:array,matched:string}>
     */
    public static function matchLines(array $raw, string $norm): array
    {
        $split = [];
        foreach (Languages::codes() as $code) {
            $split[$code] = isset($raw[$code]) && strlen((string)$raw[$code]) <= 20000 ? Text::lines((string)$raw[$code]) : [''];
        }

        $hits = [];
        foreach ($split as $code => $lines) {
            foreach ($lines as $index => $line) {
                if (isset($hits[$index]) || !Text::isPlainCandidate($line) || Text::norm($line) !== $norm) {
                    continue;
                }
                $values = [];
                foreach ($split as $other => $otherLines) {
                    $values[$other] = Text::decode(trim($otherLines[$index] ?? ''));
                }
                $hits[$index] = ['index' => $index, 'single' => count($lines) === 1, 'values' => $values, 'matched' => $code];
            }
        }
        return array_values($hits);
    }

    private static function rowLabel(string $table, array $info, array $row, array $fields, ?string $field): string
    {
        $lang = Languages::current();
        $label = $info['label'][$lang] ?? $info['label']['en'] ?? $table;
        $name = '';
        foreach ((array)($info['name'] ?? []) as $base) {
            foreach ([$lang, Languages::defaultCode(), 'en'] as $code) {
                $column = $fields[$base][$code] ?? null;
                $value = $column ? trim(strip_tags(Text::decode((string)($row[$column] ?? '')))) : '';
                if ($value !== '') {
                    $name = $value;
                    break 2;
                }
            }
        }
        if (mb_strlen($name) > 48) {
            $name = mb_substr($name, 0, 48) . '…';
        }
        return $label . ($name !== '' ? ': ' . $name : ' #' . $row['id']) . ($field !== null ? ' · ' . $field : '');
    }

    /** "sections_json → 2 / title" */
    private static function leafLabel(string $column, array $leaf): string
    {
        $where = $leaf['path'] ? ' → ' . implode(' / ', $leaf['path']) : '';
        return $column . $where . ' / ' . $leaf['base'];
    }

    private static function editUrl(array $info, array $row): string
    {
        if (empty($info['edit'])) {
            return '';
        }
        return url(str_replace(['{id}', '{slug}'], [(string)$row['id'], rawurlencode((string)($row['slug'] ?? ''))], $info['edit']));
    }

    /* ------------------------------------------------------------------ settings (site_name + site_name_en, JSON) */

    /** Setting key => field, for every key that belongs to a bilingual field. */
    private static function settingFields(): array
    {
        $keys = array_values(array_filter(Store::settingKeys(), static fn(string $k): bool => !str_starts_with($k, 'trans_') && !str_starts_with($k, 'lt_')));
        return Fields::group($keys);
    }

    private static function findSettings(string $norm, array $patterns): array
    {
        $where = [];
        $params = [];
        foreach ($patterns as $i => $pattern) {
            $where[] = "`value` LIKE :n{$i} ESCAPE '!'";
            $params['n' . $i] = $pattern;
        }
        $rows = Store::fetchAll('SELECT `key`, `value` FROM settings WHERE ' . implode(' OR ', $where) . ' LIMIT 400', $params);

        $fields = self::settingFields();
        $keyToField = [];
        foreach ($fields as $field => $byLang) {
            foreach ($byLang as $key) {
                $keyToField[$key] = $field;
            }
        }

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = (string)$row['key'];
            if (str_starts_with($key, 'trans_') || str_starts_with($key, 'lt_')) {
                continue;
            }

            $data = Json::decode((string)$row['value']);
            if ($data !== null) {
                foreach (Json::leaves($data) as $leaf) {
                    $match = self::matchGroup($leaf['raw'], $norm);
                    if ($match) {
                        $out[] = [
                            'ref' => 'jsetting:' . $key . ':' . Json::encodeLocation($leaf['path'], $leaf['base']),
                            'kind' => 'json',
                            'label' => self::leafLabel($key, $leaf),
                            'edit_url' => url(self::config()['settings_edit'] ?? '/admin/settings'),
                            'current' => false,
                            'values' => $match['values'],
                            'matched' => $match['matched'],
                        ];
                    }
                }
                continue;
            }

            $field = $keyToField[$key] ?? null;
            if ($field === null || isset($seen[$field])) {
                continue;
            }
            $seen[$field] = true;
            $match = self::matchGroup(self::settingValues($fields[$field]), $norm);
            if ($match) {
                $out[] = [
                    'ref' => 'setting:' . $field,
                    'kind' => 'setting',
                    'label' => $field,
                    'edit_url' => url(self::config()['settings_edit'] ?? '/admin/settings'),
                    'current' => false,
                    'values' => $match['values'],
                    'matched' => $match['matched'],
                ];
            }
        }
        return $out;
    }

    /** @param array<string,string> $byLang language => setting key. @return array<string,string> language => stored value */
    public static function settingValues(array $byLang): array
    {
        $stored = Store::settings(array_values($byLang));
        $raw = [];
        foreach ($byLang as $code => $key) {
            $raw[$code] = $stored[$key] ?? '';
        }
        return $raw;
    }

    /** @return array<string,string>|null language => setting key of a bilingual setting. */
    public static function settingField(string $field): ?array
    {
        return self::settingFields()[$field] ?? null;
    }

    /* ------------------------------------------------------------------ static texts (resources/lang/<code>.php) */

    /** Name of the settings key that overrides a static text (same one Lang::get() reads). */
    public static function overrideKey(string $code, string $key): string
    {
        return 'trans_' . $code . '_' . $key;
    }

    /**
     * Every static text, with what is displayed now (the override when there is one).
     *
     * @return array<string, array<string,string>> key => [language code => text]
     */
    public static function langIndex(): array
    {
        if (self::$langIndex !== null) {
            return self::$langIndex;
        }

        $index = [];
        foreach (Languages::codes() as $code) {
            $path = base_dir() . '/resources/lang/' . $code . '.php';
            $data = is_file($path) ? require $path : [];
            foreach (is_array($data) ? $data : [] as $key => $value) {
                if (is_string($value)) {
                    $index[(string)$key][$code] = $value;
                }
            }
        }

        $overrides = [];
        foreach (Store::fetchAll("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'trans!_%' ESCAPE '!'") as $row) {
            if (is_string($row['value']) && $row['value'] !== '') {
                $overrides[$row['key']] = $row['value'];
            }
        }
        foreach ($index as $key => $values) {
            foreach (Languages::codes() as $code) {
                $index[$key][$code] = $overrides[self::overrideKey($code, (string)$key)] ?? ($values[$code] ?? '');
            }
        }

        return self::$langIndex = $index;
    }

    private static function findLang(string $norm): array
    {
        $out = [];
        foreach (self::langIndex() as $key => $values) {
            $match = self::matchGroup($values, $norm);
            if ($match) {
                $out[] = [
                    'ref' => 'lang:' . $key,
                    'kind' => 'lang',
                    'label' => (string)$key,
                    'edit_url' => '',
                    'current' => false,
                    'values' => $match['values'],
                    'matched' => $match['matched'],
                ];
            }
        }
        return $out;
    }
}
