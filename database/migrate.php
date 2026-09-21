<?php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};charset={$db['charset']}",
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("فشل الاتصال بخادم MySQL: {$e->getMessage()}\n");
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
$lines = explode("\n", str_replace("\r\n", "\n", $sql));
$lines = array_filter($lines, fn($line) => !str_starts_with(trim($line), '--'));
$sql = implode("\n", $lines);

$statements = array_filter(array_map('trim', explode(';', $sql)));

$count = 0;
foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
    $count++;
}

echo "تم تنفيذ {$count} أمر SQL بنجاح. قاعدة البيانات '{$db['database']}' جاهزة.\n";
