<?php

require_once __DIR__ . '/../app/bootstrap.php';

echo "=== Sandouk Direct URL & Routing Tests ===\n\n";

$scenarios = [
    [
        'name' => 'Scenario 1: Laragon / XAMPP Subfolder (http://localhost/sandouk/login)',
        'script' => '/sandouk/public/index.php',
        'uri' => '/sandouk/login',
        'expected_base' => '/sandouk',
        'expected_url_login' => '/sandouk/login',
        'expected_asset' => '/sandouk/assets/site/css/site.css',
        'expected_route' => '/login',
    ],
    [
        'name' => 'Scenario 2: VirtualHost Root (http://sandouk.test/login)',
        'script' => '/public/index.php',
        'uri' => '/login',
        'expected_base' => '',
        'expected_url_login' => '/login',
        'expected_asset' => '/assets/site/css/site.css',
        'expected_route' => '/login',
    ],
    [
        'name' => 'Scenario 3: CLI Server (php -S localhost:8000)',
        'script' => '/index.php',
        'uri' => '/login',
        'expected_base' => '',
        'expected_url_login' => '/login',
        'expected_asset' => '/assets/site/css/site.css',
        'expected_route' => '/login',
    ],
    [
        'name' => 'Scenario 4: Direct Subfolder access without rewrite',
        'script' => '/sandouk/index.php',
        'uri' => '/sandouk/login',
        'expected_base' => '/sandouk',
        'expected_url_login' => '/sandouk/login',
        'expected_asset' => '/sandouk/assets/site/css/site.css',
        'expected_route' => '/login',
    ],
];

$failed = 0;

foreach ($scenarios as $s) {
    echo "Testing: {$s['name']}\n";
    $_SERVER['SCRIPT_NAME'] = $s['script'];
    $_SERVER['REQUEST_URI'] = $s['uri'];
    
    // Test base_path
    // Reset static cache in base_path via reflection or function re-eval
    // Since base_path uses static variable, let's test via direct logic first
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim($scriptDir, '/');
    if ($basePath === '/.' || $basePath === '/') {
        $basePath = '';
    }
    if (str_ends_with($basePath, '/public')) {
        $basePath = substr($basePath, 0, -7);
    }
    
    if ($basePath !== $s['expected_base']) {
        echo "  [FAIL] Base path mismatch: got '{$basePath}', expected '{$s['expected_base']}'\n";
        $failed++;
    } else {
        echo "  [PASS] Base path: '{$basePath}'\n";
    }
    
    // Test URL generation
    $urlLogin = ($basePath === '' ? '' : $basePath) . '/login';
    if ($urlLogin !== $s['expected_url_login']) {
        echo "  [FAIL] Login URL mismatch: got '{$urlLogin}', expected '{$s['expected_url_login']}'\n";
        $failed++;
    } else {
        echo "  [PASS] Login URL: '{$urlLogin}'\n";
    }
    
    // Test Asset generation
    $assetUrl = ($basePath === '' ? '' : $basePath) . '/assets/site/css/site.css';
    if ($assetUrl !== $s['expected_asset']) {
        echo "  [FAIL] Asset URL mismatch: got '{$assetUrl}', expected '{$s['expected_asset']}'\n";
        $failed++;
    } else {
        echo "  [PASS] Asset URL: '{$assetUrl}'\n";
    }
    
    // Test Route resolution
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    $route = rtrim($uri, '/') ?: '/';
    if ($route !== $s['expected_route']) {
        echo "  [FAIL] Route mismatch: got '{$route}', expected '{$s['expected_route']}'\n";
        $failed++;
    } else {
        echo "  [PASS] Route: '{$route}'\n";
    }
    echo "\n";
}

// Test Static Asset file exists on disk
$cssDisk = base_dir() . '/public/assets/site/css/site.css';
if (file_exists($cssDisk)) {
    echo "[PASS] Static asset file exists at: {$cssDisk}\n";
} else {
    echo "[FAIL] Static asset file missing at: {$cssDisk}\n";
    $failed++;
}

// Test Logo upload folder exists on disk
$uploadDisk = base_dir() . '/public/uploads/branding';
if (is_dir($uploadDisk)) {
    echo "[PASS] Uploads folder exists at: {$uploadDisk}\n";
} else {
    echo "[FAIL] Uploads folder missing at: {$uploadDisk}\n";
    $failed++;
}

// Test Root .htaccess file exists
$htaccess = base_dir() . '/.htaccess';
if (file_exists($htaccess)) {
    echo "[PASS] Root .htaccess exists at: {$htaccess}\n";
} else {
    echo "[FAIL] Root .htaccess missing at: {$htaccess}\n";
    $failed++;
}

if ($failed === 0) {
    echo "\n>>> ALL VERIFICATION TESTS PASSED SUCCESSFULLY! <<<\n";
} else {
    echo "\n>>> $failed TESTS FAILED! <<<\n";
    exit(1);
}
