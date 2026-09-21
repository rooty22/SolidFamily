<?php
$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("
    INSERT INTO settings (`key`, value) 
    VALUES ('site_favicon', 'uploads/branding/favicon.jpg') 
    ON DUPLICATE KEY UPDATE 
    value = 'uploads/branding/favicon.jpg'
");

echo "Done: favicon.jpg saved in settings table.\n";
