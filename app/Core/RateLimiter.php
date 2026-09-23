<?php

namespace App\Core;

/**
 * Small DB-backed sliding-window limiter (table `rate_limits`, see database/migrate_security.php).
 * Timestamps are generated in PHP on both write and read, so PHP/MySQL timezone differences cannot skew the window.
 */
class RateLimiter
{
    private const LOGIN_MAX_PER_ACCOUNT = 5;
    private const LOGIN_MAX_PER_IP = 30;
    private const LOGIN_WINDOW = 900; // 15 minutes

    public static function hit(string $key): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO rate_limits (rkey, created_at) VALUES (?, ?)');
        $stmt->execute([self::hash($key), date('Y-m-d H:i:s')]);

        if (random_int(1, 100) === 1) {
            $purge = Database::connection()->prepare('DELETE FROM rate_limits WHERE created_at < ?');
            $purge->execute([date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public static function attempts(string $key, int $windowSeconds): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM rate_limits WHERE rkey = ? AND created_at >= ?');
        $stmt->execute([self::hash($key), date('Y-m-d H:i:s', time() - $windowSeconds)]);
        return (int) $stmt->fetchColumn();
    }

    public static function tooMany(string $key, int $max, int $windowSeconds): bool
    {
        return self::attempts($key, $windowSeconds) >= $max;
    }

    public static function clear(string $key): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM rate_limits WHERE rkey = ?');
        $stmt->execute([self::hash($key)]);
    }

    /**
     * Seconds until $key drops back under $max hits within the trailing $windowSeconds; 0 when already under it.
     * Used to show a real countdown instead of a fixed UI cooldown that may expire long before the throttle does.
     */
    public static function retryAfter(string $key, int $max, int $windowSeconds): int
    {
        if (!self::tooMany($key, $max, $windowSeconds)) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'SELECT created_at FROM rate_limits WHERE rkey = ? ORDER BY created_at DESC LIMIT 1 OFFSET ?'
        );
        $stmt->execute([self::hash($key), max(0, $max - 1)]);
        $createdAt = $stmt->fetchColumn();
        if (!$createdAt) {
            return 0;
        }

        return max(0, strtotime($createdAt) + $windowSeconds - time());
    }

    // ---- Login throttling: per account and per client IP -------------------------------------------------

    public static function loginBlocked(string $scope, string $identifier): bool
    {
        return self::tooMany(self::accountKey($scope, $identifier), self::LOGIN_MAX_PER_ACCOUNT, self::LOGIN_WINDOW)
            || self::tooMany(self::ipKey($scope), self::LOGIN_MAX_PER_IP, self::LOGIN_WINDOW);
    }

    public static function loginFailed(string $scope, string $identifier): void
    {
        self::hit(self::accountKey($scope, $identifier));
        self::hit(self::ipKey($scope));
    }

    public static function loginSucceeded(string $scope, string $identifier): void
    {
        self::clear(self::accountKey($scope, $identifier));
    }

    private static function accountKey(string $scope, string $identifier): string
    {
        return "login:{$scope}:acct:" . mb_strtolower($identifier);
    }

    private static function ipKey(string $scope): string
    {
        return "login:{$scope}:ip:" . client_ip();
    }

    private static function hash(string $key): string
    {
        return hash('sha256', $key);
    }
}
