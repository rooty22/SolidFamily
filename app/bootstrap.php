<?php

require_once __DIR__ . '/Core/helpers.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = base_dir() . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

$appConfig = config('app');
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    set_exception_handler(function (\Throwable $e) {
        error_log('[sandouk] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>خطأ</title>'
            . '<body style="font-family:sans-serif;text-align:center;padding:4rem"><h1>حدث خطأ غير متوقع</h1><p>الرجاء المحاولة لاحقاً.</p></body></html>';
    });
}

if (PHP_SAPI !== 'cli') {
    \App\Core\Session::start();
}
