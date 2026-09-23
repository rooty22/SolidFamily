<?php

namespace App\LiveTranslate;

/**
 * Text helpers shared by the resolver, the writer and the dictionary.
 * Text::norm() must stay in sync with norm() in public/assets/live-translate/lt-runtime.js.
 */
final class Text
{
    /**
     * Comparable form of a visible text: what the browser shows (entities decoded, typographic quotes/dashes made
     * plain, no-break spaces and runs of whitespace collapsed).
     */
    public static function norm(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strtr($text, [
            "\xE2\x80\x98" => "'", "\xE2\x80\x99" => "'", "\xE2\x80\x9C" => '"', "\xE2\x80\x9D" => '"',
            "\xE2\x80\x93" => '-', "\xE2\x80\x94" => '-', "\xE2\x80\xA6" => '...', "\xC2\xA0" => ' ',
        ]);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string)$text);
    }

    /** Text that can be edited as plain text (no markup) and is short enough to be a label/heading/sentence. */
    public static function isPlainCandidate(string $text): bool
    {
        return $text !== ''
            && strlen($text) <= 4000
            && !preg_match('/<\/?[a-z][^>]*>/i', $text)
            && preg_match('/\p{L}/u', $text) === 1;
    }

    /**
     * Longest run of letters/digits/spaces of a text, used as a cheap SQL LIKE pre-filter.
     * The exact comparison is always done afterwards in PHP with norm().
     */
    public static function likeNeedle(string $norm): string
    {
        $best = '';
        foreach ((array)preg_split('/[^\p{L}\p{N} ]+/u', $norm) as $part) {
            $part = trim((string)$part);
            if (mb_strlen($part) > mb_strlen($best)) {
                $best = $part;
            }
        }
        return mb_strlen($best) >= 2 ? $best : $norm;
    }

    /** '%needle%' with the LIKE wildcards escaped; the SQL must say ESCAPE '!' (works on MySQL and SQLite). */
    public static function likePattern(string $needle): string
    {
        return '%' . strtr($needle, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    }

    /** Lines of a stored value (the site prints multi-line texts as list items / paragraphs). */
    public static function lines(string $value): array
    {
        return preg_split('/\r\n|\r|\n/', $value) ?: [''];
    }

    /** Whether a stored value keeps HTML entities (the admin forms save texts with sanitize(): & becomes &amp;). */
    public static function hasEntities(string $stored): bool
    {
        return $stored !== '' && html_entity_decode($stored, ENT_QUOTES | ENT_HTML5, 'UTF-8') !== $stored;
    }

    /**
     * Text to store, in the same style as what is already stored: encoded when the existing values are,
     * otherwise $encodeByDefault decides.
     *
     * @param string[] $existing stored values of the same field, in every language.
     */
    public static function forStorage(string $value, array $existing, bool $encodeByDefault): string
    {
        $encode = $encodeByDefault;
        foreach ($existing as $stored) {
            if (is_string($stored) && self::hasEntities($stored)) {
                $encode = true;
                break;
            }
        }
        return $encode ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
    }

    /** Text as typed by a person, from a stored (possibly entity-encoded) value. */
    public static function decode(string $stored): string
    {
        return html_entity_decode($stored, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
