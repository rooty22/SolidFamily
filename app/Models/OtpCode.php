<?php

namespace App\Models;

use App\Core\Model;
use App\Core\RateLimiter;

class OtpCode extends Model
{
    protected static string $table = 'otp_codes';

    /** Anti-abuse throttle knobs, editable from Admin Settings > OTP & SMS Gateway; these are just the fallbacks. */
    private const MAX_FAILED_VERIFICATIONS_DEFAULT = 5;
    private const VERIFY_LOCK_MINUTES_DEFAULT = 30;
    private const MAX_ISSUED_PER_IDENTIFIER_DEFAULT = 5;
    private const ISSUE_WINDOW_MINUTES_DEFAULT = 15;
    private const MAX_ISSUED_PER_IP = 15;

    private static function maxFailedVerifications(): int
    {
        return max(1, (int) site_setting('otp_max_failed_attempts', self::MAX_FAILED_VERIFICATIONS_DEFAULT));
    }

    private static function verifyLockSeconds(): int
    {
        return max(60, (int) site_setting('otp_verify_lock_minutes', self::VERIFY_LOCK_MINUTES_DEFAULT) * 60);
    }

    private static function maxIssuedPerIdentifier(): int
    {
        return max(1, (int) site_setting('otp_max_issued_per_identifier', self::MAX_ISSUED_PER_IDENTIFIER_DEFAULT));
    }

    private static function issueWindowSeconds(): int
    {
        return max(60, (int) site_setting('otp_issue_window_minutes', self::ISSUE_WINDOW_MINUTES_DEFAULT) * 60);
    }

    /** Quick format check so obviously malformed input never reaches the database lookup. */
    public static function isWellFormed(string $code): bool
    {
        return (bool) preg_match('/^\d{' . otp_length() . '}$/', $code);
    }

    /** True while too many wrong codes were entered for this identifier: no code can be verified or issued. */
    public static function isLocked(string $identifier, string $purpose): bool
    {
        return RateLimiter::tooMany(self::verifyKey($identifier, $purpose), self::maxFailedVerifications(), self::verifyLockSeconds());
    }

    /**
     * Issue a new code. Returns null (and sends nothing) when the identifier is locked or the request rate is exceeded,
     * so the endpoint cannot be used to spam SMS or to farm fresh codes for guessing.
     */
    public static function generate(string $identifier, string $purpose, array $payload = []): ?string
    {
        $issueKey = "otp_issue:{$purpose}:" . mb_strtolower($identifier);
        $ipKey = 'otp_issue_ip:' . client_ip();
        $issueWindow = self::issueWindowSeconds();

        if (self::isLocked($identifier, $purpose)
            || RateLimiter::tooMany($issueKey, self::maxIssuedPerIdentifier(), $issueWindow)
            || RateLimiter::tooMany($ipKey, self::MAX_ISSUED_PER_IP, $issueWindow)) {
            return null;
        }
        RateLimiter::hit($issueKey);
        RateLimiter::hit($ipKey);

        self::execute(
            'UPDATE otp_codes SET is_used = 1 WHERE identifier = ? AND purpose = ? AND is_used = 0',
            [$identifier, $purpose]
        );

        $code = generate_otp(otp_length());
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . otp_expiry_minutes() . ' minutes'));

        self::create([
            'identifier' => $identifier,
            'code' => $code,
            'purpose' => $purpose,
            'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'expires_at' => $expiresAt,
        ]);

        self::deliver($identifier, $purpose, $code);

        return $code;
    }

    /**
     * Deliver OTP via SMS Gateway in Live mode, or store in session in Demo mode.
     */
    private static function deliver(string $identifier, string $purpose, string $code): void
    {
        if (otp_is_demo()) {
            \App\Core\Session::set('demo_otp_code', $code);
            \App\Core\Session::set('demo_otp_identifier', $identifier);
            return;
        }

        if (otp_is_disabled()) {
            return;
        }

        // Live SMS Gateway Delivery
        $site = site_name();
        $message = "رمز التحقق الخاص بك في {$site} هو: {$code}";
        \App\Core\SmsService::send($identifier, $message);
    }

    public static function verify(string $identifier, string $purpose, string $code): ?array
    {
        $key = self::verifyKey($identifier, $purpose);

        if (self::isLocked($identifier, $purpose)) {
            self::execute('UPDATE otp_codes SET is_used = 1 WHERE identifier = ? AND purpose = ? AND is_used = 0', [$identifier, $purpose]);
            return null;
        }

        $row = self::rawOne(
            'SELECT * FROM otp_codes WHERE identifier = ? AND purpose = ? AND code = ? AND is_used = 0 AND expires_at >= ? ORDER BY id DESC LIMIT 1',
            [$identifier, $purpose, $code, date('Y-m-d H:i:s')]
        );

        if (!$row) {
            RateLimiter::hit($key);
            if (self::isLocked($identifier, $purpose)) {
                self::execute('UPDATE otp_codes SET is_used = 1 WHERE identifier = ? AND purpose = ? AND is_used = 0', [$identifier, $purpose]);
            }
            return null;
        }

        self::update($row['id'], ['is_used' => 1]);
        RateLimiter::clear($key);

        return $row;
    }

    /**
     * Real seconds remaining before this identifier can successfully request or verify a code again,
     * across every throttle that could be blocking it (wrong-code lock, per-identifier and per-IP issue limits).
     * 0 means nothing is blocking it right now.
     */
    public static function retryAfterSeconds(string $identifier, string $purpose): int
    {
        $issueWindow = self::issueWindowSeconds();

        $verifyRetry = RateLimiter::retryAfter(self::verifyKey($identifier, $purpose), self::maxFailedVerifications(), self::verifyLockSeconds());

        $issueKey = "otp_issue:{$purpose}:" . mb_strtolower($identifier);
        $issueRetry = RateLimiter::retryAfter($issueKey, self::maxIssuedPerIdentifier(), $issueWindow);

        $ipKey = 'otp_issue_ip:' . client_ip();
        $ipRetry = RateLimiter::retryAfter($ipKey, self::MAX_ISSUED_PER_IP, $issueWindow);

        return max($verifyRetry, $issueRetry, $ipRetry);
    }

    private static function verifyKey(string $identifier, string $purpose): string
    {
        return "otp_verify:{$purpose}:" . mb_strtolower($identifier);
    }
}
