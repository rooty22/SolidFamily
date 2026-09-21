<?php

// Serve static assets directly if using PHP built-in web server
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
    if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
        return false;
    }
}

require_once __DIR__ . '/../app/bootstrap.php';

$router = new \App\Core\Router();
require base_dir() . '/routes/web.php';
$router->dispatch();

