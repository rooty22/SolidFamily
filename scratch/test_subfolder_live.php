<?php
$_SERVER['SCRIPT_NAME'] = '/sandouk/public/index.php';
$_SERVER['REQUEST_URI'] = '/sandouk/shares';

require_once __DIR__ . '/../app/bootstrap.php';

echo "base_path: " . base_path() . "\n";
echo "url('/shares'): " . url('/shares') . "\n";
echo "asset('site/css/site.css'): " . asset('site/css/site.css') . "\n";
echo "current_path(): " . current_path() . "\n";
echo "is_active('/shares'): " . is_active('/shares') . "\n";
