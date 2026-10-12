<?php

/**
 * Lets a merge request name the specific share lots to merge (instead of always merging every lot).
 *
 *   php database/migrate_merge_lots.php
 *
 * Adds share_requests.lot_ids (comma-separated share_lots ids). NULL means "every active lot", which is what
 * merge requests created before this migration did. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('SHOW COLUMNS FROM share_requests')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('lot_ids', $cols, true)) {
    $pdo->exec("ALTER TABLE share_requests ADD COLUMN lot_ids VARCHAR(255) NULL COMMENT 'merge requests: comma-separated share_lots ids to merge, NULL = every active lot' AFTER shares_count");
    echo "- share_requests.lot_ids added.\n";
} else {
    echo "- share_requests.lot_ids already exists, skipped.\n";
}

echo "Merge-lots migration finished successfully.\n";
