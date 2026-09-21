<?php

function calc_base_path($scriptName) {
    $scriptDir = str_replace('\\', '/', dirname($scriptName ?? ''));
    $path = rtrim($scriptDir, '/');
    if ($path === '/.' || $path === '/') {
        $path = '';
    }
    if (str_ends_with($path, '/public')) {
        $path = substr($path, 0, -7);
    }
    return $path;
}

function resolve_route($scriptName, $requestUri, &$redirectUrl = null) {
    $redirectUrl = null;
    $uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';
    $basePath = calc_base_path($scriptName);
    
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    
    if ($uri === '/public' || str_starts_with($uri, '/public/')) {
        $clean = ltrim(substr($uri, 7), '/');
        $redirectUrl = ($basePath !== '' ? $basePath : '') . '/' . $clean;
        return null;
    }
    
    if ($uri === '/index.php' || str_starts_with($uri, '/index.php/')) {
        $uri = substr($uri, 10);
    }
    
    $uri = rtrim($uri, '/');
    return $uri === '' ? '/' : $uri;
}

$tests = [
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/', 'expected' => '/'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/login', 'expected' => '/login'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/admin/dashboard', 'expected' => '/admin/dashboard'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/public', 'expected_redirect' => '/sandouk/'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/public/', 'expected_redirect' => '/sandouk/'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/public/login', 'expected_redirect' => '/sandouk/login'],
    ['script' => '/sandouk/public/index.php', 'uri' => '/sandouk/public/admin/dashboard', 'expected_redirect' => '/sandouk/admin/dashboard'],
    ['script' => '/public/index.php', 'uri' => '/', 'expected' => '/'],
    ['script' => '/public/index.php', 'uri' => '/login', 'expected' => '/login'],
    ['script' => '/public/index.php', 'uri' => '/public/login', 'expected_redirect' => '/login'],
    ['script' => '/index.php', 'uri' => '/login', 'expected' => '/login'],
    ['script' => '/sandouk/index.php', 'uri' => '/sandouk/login', 'expected' => '/login'],
    ['script' => '/sandouk/index.php', 'uri' => '/sandouk/index.php/login', 'expected' => '/login'],
];

$allPassed = true;
foreach ($tests as $i => $t) {
    $redir = null;
    $res = resolve_route($t['script'], $t['uri'], $redir);
    if (isset($t['expected'])) {
        if ($res !== $t['expected']) {
            echo "Test #$i FAILED: got '$res', expected '{$t['expected']}'\n";
            $allPassed = false;
        } else {
            echo "Test #$i PASSED: {$t['uri']} -> '$res'\n";
        }
    } elseif (isset($t['expected_redirect'])) {
        if ($redir !== $t['expected_redirect']) {
            echo "Test #$i FAILED: got redirect '$redir', expected '{$t['expected_redirect']}'\n";
            $allPassed = false;
        } else {
            echo "Test #$i PASSED: {$t['uri']} -> redirect '$redir'\n";
        }
    }
}

if ($allPassed) {
    echo "\n>>> ALL 13 TEST CASES PASSED! <<<\n";
}
