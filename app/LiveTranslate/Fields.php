<?php

namespace App\LiveTranslate;

/**
 * Finds the bilingual "fields" among a list of names (columns of a table, keys of the settings table, keys of a
 * JSON object): names that only differ by the language suffix.
 *
 *   title, title_en          => title: [ar => title, en => title_en]      (default language without suffix)
 *   badge_ar, badge_en       => badge: [ar => badge_ar, en => badge_en]   (all suffixed)
 */
final class Fields
{
    /**
     * @param string[] $names
     * @return array<string, array<string,string>> field => [language code => name], fields of 2+ languages only.
     */
    public static function group(array $names): array
    {
        $codes = Languages::codes();
        $default = Languages::defaultCode();
        $set = array_flip($names);

        $groups = [];
        foreach ($names as $name) {
            $name = (string)$name;
            foreach ($codes as $code) {
                $suffix = '_' . $code;
                if (strlen($name) > strlen($suffix) && str_ends_with($name, $suffix)) {
                    $groups[substr($name, 0, -strlen($suffix))][$code] = $name;
                }
            }
        }

        $out = [];
        foreach ($groups as $base => $byLang) {
            $base = (string)$base;
            if (!isset($byLang[$default]) && isset($set[$base])) {
                $byLang[$default] = $base;
            }
            if (count($byLang) < 2) {
                continue;
            }
            $ordered = [];
            foreach ($codes as $code) {
                if (isset($byLang[$code])) {
                    $ordered[$code] = $byLang[$code];
                }
            }
            $out[$base] = $ordered;
        }
        return $out;
    }
}
