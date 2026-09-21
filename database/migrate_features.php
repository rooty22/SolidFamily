<?php

// Idempotent, additive migration for: replies to contact messages + de-duplication of automatic notifications.
// Safe to run on an existing database:  php database/migrate_features.php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for features migration...\n";

$columns = fn(string $table): array => $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);

// ---- contact_messages: admin reply ----
$cols = $columns('contact_messages');
foreach ([
    'reply_text' => 'ALTER TABLE contact_messages ADD COLUMN reply_text TEXT NULL AFTER status',
    'replied_at' => 'ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL AFTER reply_text',
    'replied_by' => 'ALTER TABLE contact_messages ADD COLUMN replied_by INT UNSIGNED NULL AFTER replied_at',
] as $col => $sql) {
    if (!in_array($col, $cols, true)) {
        $pdo->exec($sql);
        echo "- contact_messages.{$col} added.\n";
    }
}

// ---- notifications: dedupe key so reminders are sent once per member/month/installment ----
$cols = $columns('notifications');
if (!in_array('dedupe_key', $cols, true)) {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN dedupe_key VARCHAR(80) NULL AFTER created_by');
    echo "- notifications.dedupe_key added.\n";
}
// SHOW INDEX (not information_schema, which MySQL 8 caches for 24h) so the check always reflects reality.
$hasIndex = count($pdo->query("SHOW INDEX FROM notifications WHERE Key_name = 'uniq_notif_dedupe'")->fetchAll()) > 0;
if (!$hasIndex) {
    $pdo->exec('ALTER TABLE notifications ADD UNIQUE KEY uniq_notif_dedupe (target_member_id, dedupe_key)');
    echo "- unique index uniq_notif_dedupe added.\n";
}

echo "Features migration finished successfully.\n";
