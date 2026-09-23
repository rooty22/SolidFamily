<?php

namespace App\LiveTranslate;

/**
 * The languages of the module (config/live_translate.php).
 * Every language decision of the module goes through here.
 */
final class Languages
{
    private static ?array $config = null;
    private static ?string $forced = null;

    public static function config(): array
    {
        return self::$config ??= require base_dir() . '/config/live_translate.php';
    }

    /** @return string[] language codes, in the order of the config. */
    public static function codes(): array
    {
        return array_keys(self::config()['languages'] ?? []);
    }

    public static function defaultCode(): string
    {
        $default = (string)(self::config()['default'] ?? 'ar');
        return in_array($default, self::codes(), true) ? $default : (self::codes()[0] ?? 'ar');
    }

    /** An AJAX request has no language of its own: the endpoints pin the language of the page being translated. */
    public static function pin(string $code): void
    {
        self::$forced = in_array($code, self::codes(), true) ? $code : null;
    }

    /** Language of the page being displayed. */
    public static function current(): string
    {
        if (self::$forced !== null) {
            return self::$forced;
        }
        $code = function_exists('current_locale') ? current_locale() : self::defaultCode();
        return in_array($code, self::codes(), true) ? $code : self::defaultCode();
    }

    public static function isRtl(string $code): bool
    {
        return !empty(self::config()['languages'][$code]['rtl']);
    }

    /** @return array[] language descriptors for the popup. */
    public static function describe(): array
    {
        $out = [];
        foreach (self::config()['languages'] ?? [] as $code => $info) {
            $out[] = [
                'code' => $code,
                'name' => $info['name'] ?? $code,
                'flag' => $info['flag'] ?? '',
                'rtl' => self::isRtl($code),
            ];
        }
        return $out;
    }
}
