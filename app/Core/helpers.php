<?php

function config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require base_dir() . '/config/config.php';
    }
    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!isset($value[$segment])) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_dir(): string
{
    return dirname(__DIR__, 2);
}

function base_path(): string
{
    static $path = null;
    if ($path === null) {
        $configured = config('app.base_path', '');
        if (!empty($configured)) {
            $path = '/' . trim(str_replace('\\', '/', $configured), '/');
            return $path;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_ends_with($scriptName, '/index.php') ? str_replace('\\', '/', dirname($scriptName)) : '';
        $path = rtrim($scriptDir, '/\\');
        if ($path === '/.' || $path === '/' || $path === '\\' || $path === '') {
            $path = '';
        }
        // Always strip trailing /public so generated links, assets, and routes never leak /public
        if (str_ends_with($path, '/public')) {
            $path = substr($path, 0, -7);
        }
    }
    return $path;
}

function url(string $path = ''): string
{
    $base = base_path();
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

/** URL of a file under public/assets, versioned by its modification time so browsers never keep a stale copy. */
function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $file = base_dir() . '/public/assets/' . $relative;
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return base_path() . '/assets/' . $relative . $version;
}

function view(string $view, array $data = [], ?string $layout = null): void
{
    \App\Core\View::render($view, $data, $layout);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, $default = '')
{
    return e(\App\Core\Session::old($key, $default));
}

function flash(string $key)
{
    return \App\Core\Session::flash($key);
}

function csrf_field(): string
{
    $token = \App\Core\Session::csrfToken();
    return '<input type="hidden" name="_csrf" value="' . $token . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}

function money(?float $amount, ?int $decimals = null): string
{
    $val = (float) $amount;
    if ($decimals === null) {
        $decimals = (floor($val) == $val) ? 0 : 2;
    }
    $curr = is_rtl() ? 'ريال' : 'SAR';
    return number_format($val, $decimals) . ' ' . $curr;
}

function __(string $key, array $replace = []): string
{
    return \App\Core\Lang::get($key, $replace);
}

function current_locale(): string
{
    return \App\Core\Lang::locale();
}

function is_rtl(): bool
{
    return \App\Core\Lang::isRtl();
}

function is_en(): bool
{
    return \App\Core\Lang::locale() === 'en';
}

function dir_attr(): string
{
    return \App\Core\Lang::dir();
}

function money_plain(?float $amount, int $decimals = 2): string
{
    return number_format((float) $amount, $decimals);
}


function date_ar(?string $date, string $format = 'Y-m-d'): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function relative_days_label(int $days): string
{
    if ($days < 0) {
        return __('days_overdue', ['days' => abs($days)]);
    } elseif ($days === 0) {
        return __('today');
    } else {
        return __('in_days', ['days' => $days]);
    }
}

function redirect_to(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function status_badge(string $status): array
{
    $variants = [
        'paid' => 'success',
        'partial' => 'warning',
        'unpaid' => 'danger',
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'danger',
        'closed' => 'secondary',
        'completed' => 'success',
        'sent' => 'success',
        'failed' => 'danger',
        'new' => 'info',
        'read' => 'secondary',
    ];

    $key = 'status_' . $status;
    $label = function_exists('__') ? __($key) : $status;
    if ($label === $key) {
        $arFallbacks = [
            'paid' => 'مسدد',
            'partial' => 'مسدد جزئياً',
            'unpaid' => 'غير مسدد',
            'pending' => 'قيد المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            'active' => 'فعال',
            'inactive' => 'موقوف',
            'closed' => 'مغلق',
            'completed' => 'مكتمل',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال',
            'new' => 'جديد',
            'read' => 'مقروء',
        ];
        $isAr = function_exists('current_locale') ? (current_locale() === 'ar') : true;
        $label = $isAr ? ($arFallbacks[$status] ?? $status) : ucfirst($status);
    }

    return [$label, $variants[$status] ?? 'secondary'];
}

function loan_reasons(): array
{
    return [
        'personal' => function_exists('__') ? __('reason_personal') : 'شخصي',
        'educational' => function_exists('__') ? __('reason_educational') : 'تعليمي',
        'marriage' => function_exists('__') ? __('reason_marriage') : 'زواج',
        'other' => function_exists('__') ? __('reason_other') : 'أخرى',
    ];
}

function transaction_categories(): array
{
    return [
        'subscription' => function_exists('__') ? __('cat_subscription') : 'اشتراك شهري',
        'founding' => function_exists('__') ? __('cat_founding') : 'مبلغ تأسيس',
        'loan_disbursement' => function_exists('__') ? __('cat_loan_disbursement') : 'صرف قرض',
        'loan_installment' => function_exists('__') ? __('cat_loan_installment') : 'قسط قرض',
        'loan_admin_fee' => function_exists('__') ? __('cat_loan_admin_fee') : 'مصاريف إدارية للقرض',
    ];
}

function share_request_types(): array
{
    return [
        'add' => function_exists('__') ? __('share_type_add') : 'إضافة سهم',
        'merge' => function_exists('__') ? __('share_type_merge') : 'دمج الأسهم',
        'cancel' => function_exists('__') ? __('share_type_cancel') : 'إلغاء سهم',
    ];
}

function generate_otp(int $length = 6): string
{
    $min = (int) str_pad('1', $length, '0');
    $max = (int) str_pad('', $length, '9', STR_PAD_LEFT);
    return (string) random_int($min, $max);
}

function client_ip(): string
{
    // REMOTE_ADDR only: X-Forwarded-For is client-controlled and must not be trusted for throttling.
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function is_local_request(): bool
{
    return in_array(client_ip(), ['127.0.0.1', '::1'], true);
}

function otp_mode(): string
{
    return site_setting('otp_mode', config('app.otp_stub', true) ? 'demo' : 'live');
}

function otp_is_demo(): bool
{
    return otp_mode() === 'demo';
}

function otp_is_live(): bool
{
    return otp_mode() === 'live';
}

function otp_is_disabled(): bool
{
    return otp_mode() === 'disabled';
}

function otp_length(): int
{
    return (int) site_setting('otp_length', config('app.otp_length', 4));
}

function otp_resend_seconds(): int
{
    return (int) site_setting('otp_resend_seconds', 50);
}

/**
 * The real number of seconds an OTP resend/verify button must stay disabled for: the longer of the
 * cosmetic UI cooldown and any actual security throttle (wrong-code lock, issue-rate limit) still in effect.
 */
function otp_cooldown_seconds(string $identifier, string $purpose): int
{
    return max(otp_resend_seconds(), \App\Models\OtpCode::retryAfterSeconds($identifier, $purpose));
}

function otp_expiry_minutes(): int
{
    return (int) site_setting('otp_expiry_minutes', config('app.otp_expiry_minutes', 10));
}

function otp_stub_visible(): bool
{
    return otp_is_demo();
}

function otp_notice(string $message, string $code): string
{
    return otp_is_demo() ? "{$message} (وضع تجريبي - الرمز: {$code})" : $message;
}

/** Convert Arabic-Indic / Persian digits to ASCII so numeric fields typed on an Arabic keyboard validate. */
function ascii_digits(string $value): string
{
    return strtr($value, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]);
}

/**
 * Canonical mobile format: "+<country><number>". Saudi local numbers (05xxxxxxxx / 5xxxxxxxx / 9665xxxxxxxx)
 * are converted to +9665xxxxxxxx; anything else must already carry "+" or "00".
 * Returns the cleaned input unchanged when it cannot be normalised (the validator then rejects it).
 */
function normalize_mobile(string $value): string
{
    $v = preg_replace('/[\s\-().]/u', '', ascii_digits(trim($value)));
    if ($v === '' || $v === null) {
        return '';
    }
    if (str_starts_with($v, '00')) {
        $v = '+' . substr($v, 2);
    }
    if (preg_match('/^05\d{8}$/', $v)) {
        return '+966' . substr($v, 1);
    }
    if (preg_match('/^5\d{8}$/', $v)) {
        return '+966' . $v;
    }
    if (preg_match('/^9665\d{8}$/', $v)) {
        return '+' . $v;
    }
    return $v;
}

function current_admin_id(): ?int
{
    $admin = \App\Core\Auth::admin();
    return $admin['id'] ?? null;
}

function is_htmx_or_ajax(): bool
{
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
}

function paginate_params(int $page, int $perPage = 15): array
{
    $page = max(1, $page);
    return [$perPage, ($page - 1) * $perPage];
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $basePath = base_path();
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    if ($uri === '/public' || str_starts_with($uri, '/public/')) {
        $uri = substr($uri, 7);
    }
    if ($uri === '/index.php' || str_starts_with($uri, '/index.php/')) {
        $uri = substr($uri, 10);
    }
    return rtrim($uri, '/') ?: '/';
}

function is_active(string $prefix): string
{
    return str_starts_with(current_path(), $prefix) ? 'active' : '';
}

function site_setting(string $key, $default = '', bool $fresh = false)
{
    static $cache = null;
    if ($cache === null || $fresh) {
        $cache = \App\Models\Setting::getAll();
    }
    return $cache[$key] ?? $default;
}

function site_name(): string
{
    if (is_en() && ($en = site_setting('site_name_en'))) {
        return $en;
    }
    return site_setting('site_name', __('brand_name'));
}

function site_slogan(): string
{
    if (is_en() && ($en = site_setting('site_slogan_en'))) {
        return $en;
    }
    return site_setting('site_slogan', __('brand_sub'));
}

function site_logo(): ?string
{
    $logo = site_setting('site_logo');
    return (!empty($logo)) ? $logo : null;
}

function site_logo_url(): ?string
{
    $logo = site_logo();
    if (!$logo) {
        return null;
    }
    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
        return $logo;
    }
    return url($logo);
}

/** Favicon URL: external links pass through, uploads resolve like the logo (site root, not /assets). */
function site_favicon_url(): ?string
{
    $favicon = site_setting('site_favicon');
    if (empty($favicon)) {
        return null;
    }
    if (str_starts_with($favicon, 'http://') || str_starts_with($favicon, 'https://')) {
        return $favicon;
    }
    return url($favicon);
}

function site_favicon_mime(): string
{
    $ext = strtolower(pathinfo((string) parse_url((string) site_setting('site_favicon'), PHP_URL_PATH), PATHINFO_EXTENSION));
    return [
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ][$ext] ?? 'image/png';
}

function site_phone(): string
{
    return site_setting('official_phone', '+966500000000');
}

function site_email(): string
{
    return site_setting('official_email', 'info@sandouk.local');
}

function site_whatsapp(): string
{
    $wa = site_setting('official_whatsapp');
    return $wa ?: site_phone();
}

function site_whatsapp_link(): string
{
    $wa = preg_replace('/[^0-9]/', '', site_whatsapp());
    return 'https://wa.me/' . $wa;
}

function site_address(): string
{
    if (is_en() && ($en = site_setting('official_address_en'))) {
        return $en;
    }
    return site_setting('official_address', 'المملكة العربية السعودية');
}

function section_enabled(string $name): bool
{
    $val = site_setting('section_' . $name . '_enabled', '1');
    return ($val === '1' || $val === 'true' || $val === true);
}

function site_language_mode(): string
{
    return site_setting('site_language_mode', 'multi');
}

function is_single_language(): bool
{
    return site_language_mode() === 'single';
}

function site_default_language(): string
{
    return site_setting('site_default_language', 'ar');
}

/**
 * Add whole months to a Y-m-d date without overflowing into the next month:
 * 31 Jan + 1 month = 28/29 Feb (PHP's own "+1 month" would give 3 Mar).
 */
function add_months(string $date, int $months): string
{
    $d = new DateTimeImmutable($date);
    $day = (int) $d->format('j');
    $first = $d->modify('first day of this month')->modify("+{$months} months");
    $day = min($day, (int) $first->format('t'));
    return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), $day)->format('Y-m-d');
}

/** Due date of a "YYYY-MM" month for a configured due day, clamped to the month's length (30/31 in February => last day). */
function month_due_date(string $month, int $dueDay): string
{
    $first = new DateTimeImmutable($month . '-01');
    $day = max(1, min($dueDay, (int) $first->format('t')));
    return $first->format('Y-m') . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
}

/**
 * Redirect to the page the user came from, but ONLY when the Referer is on this very host.
 * The redirect target is rebuilt as a same-origin path, so it can never point to another site.
 */
function redirect_back(string $fallback): void
{
    $parts = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if ($parts && isset($parts['host']) && $host !== '') {
        $refHost = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        if ($refHost === $host) {
            $path = '/' . ltrim(str_replace('\\', '/', $parts['path'] ?? '/'), '/');
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            header('Location: ' . preg_replace('/[\x00-\x1F\x7F]/', '', $path . $query));
            exit;
        }
    }
    redirect_to($fallback);
}

/** Neutralise spreadsheet formula injection: text cells starting with = + - @ are prefixed with a quote. */
function csv_cell($value)
{
    if (is_string($value) && $value !== '' && !is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

function csv_put($handle, array $row): void
{
    fputcsv($handle, array_map('csv_cell', $row));
}

function site_menu_items(): array
{
    $json = site_setting('navigation_menu_json');
    if (empty($json)) {
        return [];
    }
    $items = json_decode($json, true);
    if (!is_array($items)) {
        return [];
    }

    $isEn = is_en();
    $result = [];
    foreach ($items as $item) {
        if (!empty($item['enabled'])) {
            $title = $isEn ? (!empty($item['title_en']) ? $item['title_en'] : $item['title_ar']) : $item['title_ar'];
            $url = $item['url'] ?? '/';
            // If it's a relative internal path that does not start with http/https
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                if (!str_starts_with($url, '#')) {
                    $url = url($url);
                }
            }
            $result[] = [
                'id' => $item['id'] ?? '',
                'title' => $title,
                'title_ar' => $item['title_ar'] ?? '',
                'title_en' => $item['title_en'] ?? '',
                'url' => $url,
                'target' => $item['target'] ?? '_self',
            ];
        }
    }
    return $result;
}
