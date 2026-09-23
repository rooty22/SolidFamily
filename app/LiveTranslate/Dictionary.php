<?php

namespace App\LiveTranslate;

use App\Models\Setting;

/**
 * Site-wide translations for texts that are not stored in the database (typed straight into a view).
 *
 * One entry = the translation of one text in every language, plus the "aliases": the texts that were visible
 * when it was captured. Any visible text equal to an alias or to one of the translations is replaced by the
 * translation of the language of the page, so an entry works in every direction (ar -> en and en -> ar).
 *
 * Stored as JSON in the `settings` table (key lt_dictionary) and applied in the browser by lt-runtime.js.
 */
final class Dictionary
{
    public const SETTING_KEY = 'lt_dictionary';

    /** A TEXT column holds 65,535 bytes; beyond that the settings.value column has to be LONGTEXT. */
    private const TEXT_LIMIT = 60000;

    private static ?self $instance = null;
    private ?array $entries = null;
    private ?array $index = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function entries(): array
    {
        if ($this->entries === null) {
            $raw = function_exists('site_setting') ? (string)site_setting(self::SETTING_KEY, '') : '';
            $this->entries = Json::decode($raw) ?? [];
        }
        return $this->entries;
    }

    public static function entryId(string $norm): string
    {
        return substr(sha1($norm), 0, 16);
    }

    /** @return string[] every text an entry answers to: its aliases and its translations. */
    private static function froms(array $entry): array
    {
        return array_merge((array)($entry['aliases'] ?? []), array_values((array)($entry['values'] ?? [])));
    }

    /** norm(text) => entry id, for every alias and every translation. */
    private function index(): array
    {
        if ($this->index === null) {
            $this->index = [];
            foreach ($this->entries() as $id => $entry) {
                foreach (self::froms((array)$entry) as $from) {
                    $norm = Text::norm((string)$from);
                    if ($norm !== '') {
                        $this->index[$norm] = $id;
                    }
                }
            }
        }
        return $this->index;
    }

    /** @return array|null the entry that a visible text belongs to. */
    public function find(string $text): ?array
    {
        $index = $this->index();
        $norm = Text::norm($text);
        if ($norm === '' || !isset($index[$norm])) {
            return null;
        }
        $entry = (array)$this->entries()[$index[$norm]];
        $entry['id'] = $index[$norm];
        return $entry;
    }

    /**
     * Create or update the entry of a text.
     *
     * @param string   $source visible text the user clicked.
     * @param string[] $values translation per language code (empty ones are ignored).
     * @throws \RuntimeException when the dictionary does not fit in the settings table.
     */
    public function save(string $source, array $values): string
    {
        $entries = $this->entries();
        $norm = Text::norm($source);
        $existing = $this->find($source);
        $id = $existing ? (string)$existing['id'] : self::entryId($norm);

        $entry = $existing ? (array)$entries[$id] : ['source' => $source, 'aliases' => [], 'values' => []];
        $entry['aliases'] = array_values(array_unique(array_merge((array)($entry['aliases'] ?? []), [$norm])));
        foreach ($values as $lang => $value) {
            if (trim((string)$value) !== '') {
                $entry['values'][$lang] = (string)$value;
            }
        }
        $entry['updated'] = time();

        // A text belongs to a single entry: drop the aliases that this one now owns.
        $owned = array_map([Text::class, 'norm'], self::froms($entry));
        foreach ($entries as $otherId => $other) {
            if ((string)$otherId === $id || empty($other['aliases'])) {
                continue;
            }
            $entries[$otherId]['aliases'] = array_values(array_diff((array)$other['aliases'], $owned));
        }

        $entries[$id] = $entry;
        $this->store($entries);
        return $id;
    }

    public function delete(string $id): void
    {
        $entries = $this->entries();
        if (isset($entries[$id])) {
            unset($entries[$id]);
            $this->store($entries);
        }
    }

    /** Add the entries of an export (an entry that is newer here is kept). */
    public function import(array $incoming): int
    {
        $entries = $this->entries();
        $count = 0;
        $codes = Languages::codes();
        foreach ($incoming as $entry) {
            if (!is_array($entry) || !isset($entry['values']) || !is_array($entry['values'])) {
                continue;
            }
            $values = [];
            foreach ($codes as $code) {
                $value = isset($entry['values'][$code]) && is_string($entry['values'][$code]) ? trim(strip_tags($entry['values'][$code])) : '';
                if ($value !== '') {
                    $values[$code] = mb_substr($value, 0, 4000);
                }
            }
            $aliases = [];
            foreach ((array)($entry['aliases'] ?? []) as $alias) {
                $alias = is_string($alias) ? Text::norm(strip_tags($alias)) : '';
                if ($alias !== '') {
                    $aliases[] = mb_substr($alias, 0, 4000);
                }
            }
            $source = $aliases[0] ?? (reset($values) ?: '');
            if (!$values || $source === '') {
                continue;
            }
            $id = self::entryId(Text::norm($source));
            if (isset($entries[$id]) && (int)($entries[$id]['updated'] ?? 0) > (int)($entry['updated'] ?? 0)) {
                continue;
            }
            $entries[$id] = [
                'source' => mb_substr(strip_tags((string)($entry['source'] ?? $source)), 0, 4000),
                'aliases' => array_values(array_unique($aliases)),
                'values' => $values,
                'updated' => (int)($entry['updated'] ?? time()),
            ];
            $count++;
        }
        if ($count) {
            $this->store($entries);
        }
        return $count;
    }

    private function store(array $entries): void
    {
        $json = json_encode($entries, JSON_UNESCAPED_UNICODE);
        if (strlen((string)$json) > self::TEXT_LIMIT && !self::valueColumnIsLong()) {
            throw new \RuntimeException('The site-wide translations no longer fit in the settings table. Run: php database/migrate_live_translate.php');
        }
        Setting::set(self::SETTING_KEY, $json);
        $this->entries = $entries;
        $this->index = null;
    }

    private static function valueColumnIsLong(): bool
    {
        $row = Store::fetchOne("SHOW COLUMNS FROM settings LIKE 'value'");
        $type = strtolower((string)($row['Type'] ?? ''));
        return in_array($type, ['mediumtext', 'longtext'], true);
    }

    /** norm(text) => translation for one language, for the browser side (only what really changes). */
    public function runtimeMap(string $lang): array
    {
        $map = [];
        foreach ($this->entries() as $entry) {
            $entry = (array)$entry;
            $target = trim((string)($entry['values'][$lang] ?? ''));
            if ($target === '') {
                continue;
            }
            foreach (self::froms($entry) as $from) {
                $norm = Text::norm((string)$from);
                if ($norm !== '' && $norm !== Text::norm($target)) {
                    $map[$norm] = $target;
                }
            }
        }
        return $map;
    }
}
