<?php
$cases = [
    '/index.php',
    '\\index.php',
    '/sandouk/index.php',
    '\\sandouk\\index.php',
    '/sandouk/public/index.php',
    '\\sandouk\\public\\index.php',
];

foreach ($cases as $scriptName) {
    $clean = str_replace('\\', '/', $scriptName);
    $scriptDir = str_ends_with($clean, '/index.php') ? str_replace('\\', '/', dirname($clean)) : '';
    $path = rtrim($scriptDir, '/\\');
    if ($path === '/.' || $path === '/' || $path === '\\' || $path === '') {
        $path = '';
    }
    if (str_ends_with($path, '/public')) {
        $path = substr($path, 0, -7);
    }
    echo "$scriptName => path: '$path'\n";
    $url = ($path === '' ? '' : $path) . '/assets/site/css/site.css';
    echo "  URL: '$url'\n";
}
