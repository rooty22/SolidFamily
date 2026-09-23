<?php

/**
 * Live Translate: makes settings.value able to hold the site-wide translations (a TEXT column stops at 64 KB).
 * Safe to run more than once. Nothing else is needed: the module stores everything in the settings table.
 *
 *   php database/migrate_live_translate.php
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for the Live Translate migration...\n";

$column = $pdo->query("SHOW COLUMNS FROM settings LIKE 'value'")->fetch(PDO::FETCH_ASSOC);
$type = strtolower((string)($column['Type'] ?? ''));

if (in_array($type, ['mediumtext', 'longtext'], true)) {
    echo "settings.value is already {$type}.\n";
} else {
    echo "Changing settings.value from {$type} to LONGTEXT...\n";
    $pdo->exec('ALTER TABLE settings MODIFY `value` LONGTEXT NULL');
}

$exists = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
$exists->execute(['lt_enabled']);
if ((int)$exists->fetchColumn() === 0) {
    $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?)')->execute(['lt_enabled', '1']);
    echo "Live Translate is turned on (lt_enabled = 1).\n";
} else {
    echo "lt_enabled already set.\n";
}

echo "Live Translate migration done.\n";
