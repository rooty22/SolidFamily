<?php

namespace App\LiveTranslate;

/**
 * Bilingual texts inside JSON values (the sections of a page, the navigation menu, a form configuration):
 * objects with keys such as "title_ar" / "title_en" or "badge" / "badge_en" at any depth.
 */
final class Json
{
    private const MAX_DEPTH = 8;

    /** Flags the project uses when it saves JSON (see ContentController / SettingsController). */
    public const FLAGS = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    /** @return array|null decoded value when the text is a JSON object/list. */
    public static function decode(string $value): ?array
    {
        $value = ltrim($value);
        if ($value === '' || ($value[0] !== '{' && $value[0] !== '[')) {
            return null;
        }
        $data = json_decode($value, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Every bilingual field found in the data.
     *
     * @return array<int, array{path:array,base:string,raw:array<string,string>}>
     */
    public static function leaves(array $data, array $path = [], int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }

        $strings = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $strings[] = $key;
            }
        }

        $out = [];
        foreach (Fields::group($strings) as $base => $byLang) {
            $raw = [];
            foreach ($byLang as $code => $key) {
                $raw[$code] = (string)$data[$key];
            }
            $out[] = ['path' => $path, 'base' => $base, 'raw' => $raw];
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out = array_merge($out, self::leaves($value, array_merge($path, [$key]), $depth + 1));
            }
        }
        return $out;
    }

    public static function getPath(array $data, array $path): ?array
    {
        foreach ($path as $key) {
            if (!is_array($data) || !array_key_exists($key, $data) || !is_array($data[$key])) {
                return null;
            }
            $data = $data[$key];
        }
        return $data;
    }

    public static function setPath(array $data, array $path, array $node): array
    {
        if (!$path) {
            return $node;
        }
        $key = array_shift($path);
        $data[$key] = self::setPath((array)($data[$key] ?? []), $path, $node);
        return $data;
    }

    /** Location of a field inside a ref: base64 of [path, base], URL-safe and without ':'. */
    public static function encodeLocation(array $path, string $base): string
    {
        return rtrim(strtr(base64_encode(json_encode([$path, $base], JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    }

    /** @return array{0:array,1:string}|null */
    public static function decodeLocation(string $encoded): ?array
    {
        $json = base64_decode(strtr($encoded, '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);
        if (!is_array($data) || count($data) !== 2 || !is_array($data[0]) || !is_string($data[1])) {
            return null;
        }
        return [$data[0], $data[1]];
    }

    /**
     * The value of a LIKE pre-filter for JSON: the same text as it is stored (escaped quotes, \uXXXX, \/),
     * so older rows saved without JSON_UNESCAPED_UNICODE are found too.
     */
    public static function escapedNeedle(string $needle): string
    {
        return trim((string)json_encode($needle), '"');
    }
}
