<?php

namespace App\Core;

class Session
{
    /** Input of the previous (failed) form submission; available for exactly one request. */
    private static array $oldInput = [];

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $cfg = config('session');
            session_name($cfg['name']);
            // HttpOnly keeps the cookie away from injected scripts; SameSite=Lax blocks cross-site POSTs.
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => $cfg['lifetime'],
                'path' => '/',
                'secure' => is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();

            // Old input is flash data: read it for this request and drop it, so it never lingers in later visits.
            self::$oldInput = $_SESSION['_old'] ?? [];
            unset($_SESSION['_old']);
        }
    }

    /** Issue a new session id (call right after a successful login to prevent session fixation). */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function old(string $key, $default = '')
    {
        return self::$oldInput[$key] ?? $default;
    }

    public static function setOld(array $data): void
    {
        // Never keep submitted passwords in the session.
        foreach (array_keys($data) as $key) {
            if (stripos((string) $key, 'password') !== false || $key === '_csrf') {
                unset($data[$key]);
            }
        }
        $_SESSION['_old'] = $data;
    }

    public static function clearOld(): void
    {
        self::$oldInput = [];
        unset($_SESSION['_old']);
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string) $token);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
