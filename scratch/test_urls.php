<?php
$testCases = [
    [
        'desc' => 'Subfolder with Apache rewrite to public/index.php',
        'script_name' => '/sandouk/public/index.php',
        'request_uri' => '/sandouk/login',
    ],
    [
        'desc' => 'Virtualhost root with Apache rewrite to public/index.php',
        'script_name' => '/public/index.php',
        'request_uri' => '/login',
    ],
    [
        'desc' => 'Subfolder direct without rewrite (running root index.php)',
        'script_name' => '/sandouk/index.php',
        'request_uri' => '/sandouk/login',
    ],
    [
        'desc' => 'CLI Server (php -S localhost:8000)',
        'script_name' => '/index.php',
        'request_uri' => '/login',
    ],
    [
        'desc' => 'Old link with /public/ in subfolder',
        'script_name' => '/sandouk/public/index.php',
        'request_uri' => '/sandouk/public/login',
    ],
];

function calc_base_path($scriptName) {
    $scriptDir = str_replace('\\', '/', dirname($scriptName ?? ''));
    $path = rtrim($scriptDir, '/');
    if ($path === '/.' || $path === '/') {
        $path = '';
    }
    // If path ends with /public, strip it
    if (str_ends_with($path, '/public')) {
        $path = substr($path, 0, -7);
    }
    return $path;
}

foreach ($testCases as $tc) {
    echo "=== {$tc['desc']} ===\n";
    $basePath = calc_base_path($tc['script_name']);
    echo "Base Path: '{$basePath}'\n";
    
    $uri = parse_url($tc['request_uri'], PHP_URL_PATH);
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    
    // Check if user visited with /public
    if (str_starts_with($uri, '/public')) {
        $redirectUri = preg_replace('#^/public(?:/|$)#', '/', $uri);
        echo "Needs redirect from '{$uri}' to: '{$redirectUri}'\n";
    }
    
    $routeUri = rtrim($uri, '/') ?: '/';
    echo "Route URI: '{$routeUri}'\n";
    $urlLogin = ($basePath !== '' ? $basePath : '') . '/login';
    echo "Generated Login URL: '{$urlLogin}'\n";
    $assetCss = ($basePath !== '' ? $basePath : '') . '/assets/site/css/site.css';
    echo "Generated Asset URL: '{$assetCss}'\n\n";
}
