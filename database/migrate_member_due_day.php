<?php

// Idempotent, additive migration for: per-member subscription due day override.
// Safe to run on an existing database:  php database/migrate_member_due_day.php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for member due-day migration...\n";

$columns = $pdo->query("SHOW COLUMNS FROM members")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('subscription_due_day', $columns, true)) {
    $pdo->exec("ALTER TABLE members ADD COLUMN subscription_due_day TINYINT UNSIGNED NULL
        COMMENT 'per-member override of the global subscription due day (1-28); NULL falls back to the site setting'
        AFTER shares_count");
    echo "- members.subscription_due_day added.\n";
} else {
    echo "- members.subscription_due_day already exists, skipped.\n";
}

echo "Member due-day migration finished successfully.\n";
