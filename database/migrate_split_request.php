<?php

/**
 * Lets a member request splitting a merged share back into the shares it was made from.
 *
 *   php database/migrate_split_request.php
 *
 * Adds 'split' to share_requests.type (the request's lot_ids holds the merged share to split). Needs
 * migrate_split_lots.php to have run first. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$col = $pdo->query("SHOW COLUMNS FROM share_requests LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
if ($col && !str_contains((string) $col['Type'], "'split'")) {
    $pdo->exec("ALTER TABLE share_requests MODIFY type ENUM('add','merge','cancel','split') NOT NULL");
    $pdo->exec("ALTER TABLE share_requests MODIFY lot_ids VARCHAR(255) NULL COMMENT 'merge requests: comma-separated share_lots ids to merge, NULL = every active lot; split requests: the merged lot id to split'");
    echo "- share_requests.type now accepts split.\n";
} else {
    echo "- share_requests.type already accepts split, skipped.\n";
}

echo "Split-request migration finished successfully.\n";
