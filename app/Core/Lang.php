<?php

namespace App\Core;

class Lang
{
    private static ?string $currentLocale = null;
    private static array $translations = [];

    public static function isAdminContext(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $basePath = function_exists('base_path') ? base_path() : '';
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        if ($uri === '/public' || str_starts_with($uri, '/public/')) {
            $uri = substr($uri, 7);
        }
        if ($uri === '/index.php' || str_starts_with($uri, '/index.php/')) {
            $uri = substr($uri, 10);
        }
        $trimmed = trim($uri, '/');
        return str_starts_with($trimmed, 'admin');
    }

    public static function locale(): string
    {
        if (self::$currentLocale !== null) {
            return self::$currentLocale;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            Session::start();
        }

        // 1. ADMIN CONTEXT
        if (self::isAdminContext()) {
            $adminLang = Session::get('admin_lang');
            if ($adminLang && in_array($adminLang, ['ar', 'en'], true)) {
                self::$currentLocale = $adminLang;
                return self::$currentLocale;
            }

            $adminCookie = $_COOKIE['sandouk_admin_lang'] ?? null;
            if ($adminCookie && in_array($adminCookie, ['ar', 'en'], true)) {
                self::$currentLocale = $adminCookie;
                Session::set('admin_lang', $adminCookie);
                return self::$currentLocale;
            }

            self::$currentLocale = 'ar';
            return self::$currentLocale;
        }

        // 2. SITE CONTEXT: Check if single language mode is enforced
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $langMode = \App\Models\Setting::get('site_language_mode', 'multi');
                if ($langMode === 'single') {
                    $defaultLang = \App\Models\Setting::get('site_default_language', 'ar');
                    self::$currentLocale = in_array($defaultLang, ['ar', 'en'], true) ? $defaultLang : 'ar';
                    return self::$currentLocale;
                }
            }
        } catch (\Throwable $e) {
            // Fallback during setup or database disconnect
        }

        // Multi-language site mode
        $siteLang = Session::get('site_lang') ?? Session::get('lang');
        if ($siteLang && in_array($siteLang, ['ar', 'en'], true)) {
            self::$currentLocale = $siteLang;
            return self::$currentLocale;
        }

        $siteCookie = $_COOKIE['sandouk_site_lang'] ?? ($_COOKIE['sandouk_lang'] ?? null);
        if ($siteCookie && in_array($siteCookie, ['ar', 'en'], true)) {
            self::$currentLocale = $siteCookie;
            Session::set('site_lang', $siteCookie);
            return self::$currentLocale;
        }

        self::$currentLocale = config('app.locale', 'ar');
        return self::$currentLocale;
    }

    public static function setLocale(string $locale): void
    {
        if (self::isAdminContext()) {
            self::setAdminLocale($locale);
        } else {
            self::setSiteLocale($locale);
        }
    }

    public static function setSiteLocale(string $locale): void
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        self::$currentLocale = $locale;
        Session::set('site_lang', $locale);
        Session::set('lang', $locale);

        if (!headers_sent()) {
            setcookie('sandouk_site_lang', $locale, time() + (86400 * 365), '/');
            setcookie('sandouk_lang', $locale, time() + (86400 * 365), '/');
        }
    }

    public static function setAdminLocale(string $locale): void
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        self::$currentLocale = $locale;
        Session::set('admin_lang', $locale);

        if (!headers_sent()) {
            setcookie('sandouk_admin_lang', $locale, time() + (86400 * 365), '/');
        }
    }

    public static function isRtl(): bool
    {
        return self::locale() === 'ar';
    }

    public static function dir(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    public static function get(string $key, array $replace = []): string
    {
        $locale = self::locale();

        if (!isset(self::$translations[$locale])) {
            $path = base_dir() . "/resources/lang/{$locale}.php";
            if (file_exists($path)) {
                self::$translations[$locale] = require $path;
            } else {
                self::$translations[$locale] = [];
            }
        }

        // A text edited from the dashboard (Live Translate) wins over the language file.
        $line = self::override($locale, $key) ?? (self::$translations[$locale][$key] ?? null);

        // Fallback to Arabic if key not found in English
        if ($line === null && $locale !== 'ar') {
            if (!isset(self::$translations['ar'])) {
                $arPath = base_dir() . "/resources/lang/ar.php";
                self::$translations['ar'] = file_exists($arPath) ? require $arPath : [];
            }
            $line = self::override('ar', $key) ?? (self::$translations['ar'][$key] ?? null);
        }

        if ($line === null) {
            $line = $key;
        }

        foreach ($replace as $k => $v) {
            $line = str_replace(':' . $k, (string) $v, $line);
        }

        return $line;
    }

    /**
     * Text of a key saved from the dashboard by Live Translate: the setting "trans_<locale>_<key>"
     * (see App\LiveTranslate\Resolver::overrideKey()). Null when there is none.
     */
    private static function override(string $locale, string $key): ?string
    {
        if (!function_exists('site_setting')) {
            return null;
        }
        try {
            $value = site_setting('trans_' . $locale . '_' . $key, '');
        } catch (\Throwable $e) {
            return null; // before the database exists
        }
        return is_string($value) && $value !== '' ? $value : null;
    }
}
