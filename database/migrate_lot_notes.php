<?php

/**
 * A small admin note per share (lot).
 *
 *   php database/migrate_lot_notes.php
 *
 * Adds share_lots.admin_note (written by the admin; the member reads it from the "View note" button on his shares page).
 * A note belongs to the share itself, not to the member, so a member with three shares has three independent notes.
 * Existing lots start without notes. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('SHOW COLUMNS FROM share_lots')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admin_note', $cols, true)) {
    $pdo->exec("ALTER TABLE share_lots ADD COLUMN admin_note VARCHAR(500) NULL COMMENT 'internal note the admin keeps about this share' AFTER merged_into_id");
    echo "- share_lots.admin_note added.\n";
} else {
    echo "- share_lots.admin_note already exists, skipped.\n";
}

echo "Lot-notes migration finished successfully.\n";
