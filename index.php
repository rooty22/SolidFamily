<?php

// Sandouk Root Router for PHP Built-in Server & Apache / Laragon fallbacks
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$publicDir = realpath(__DIR__ . '/public');

// Compute base path if running inside a subfolder
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_ends_with($scriptName, '/index.php') ? str_replace('\\', '/', dirname($scriptName)) : '';
$basePath = rtrim($scriptDir, '/\\');
if ($basePath === '/.' || $basePath === '/' || $basePath === '\\' || $basePath === '') {
    $basePath = '';
}
if (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

// Clean URI relative to project root
$cleanUri = $uri;
if ($basePath !== '' && str_starts_with($cleanUri, $basePath)) {
    $cleanUri = substr($cleanUri, strlen($basePath));
}
if (str_starts_with($cleanUri, '/public/')) {
    $cleanUri = substr($cleanUri, 7);
}

// If requesting a static file from public/ directory (e.g. assets, uploads)
if ($cleanUri !== '/' && $cleanUri !== '') {
    $publicFile = str_contains($cleanUri, chr(0)) ? false : realpath($publicDir . $cleanUri);

    if ($publicFile !== false
        && str_starts_with($publicFile, $publicDir . DIRECTORY_SEPARATOR)
        && is_file($publicFile)
        && strtolower(pathinfo($publicFile, PATHINFO_EXTENSION)) !== 'php') {
        
        $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'json'  => 'application/json',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'otf'   => 'font/otf',
            'pdf'   => 'application/pdf',
            'txt'   => 'text/plain',
        ];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        header('Content-Length: ' . filesize($publicFile));
        header('Cache-Control: public, max-age=86400');
        readfile($publicFile);
        exit;
    }
}

require_once __DIR__ . '/public/index.php';
