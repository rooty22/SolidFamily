<?php

/**
 * A small admin note per share (lot).
 *
 *   php database/migrate_lot_notes.php
 *
 * Adds share_lots.admin_note (written by the admin) and share_lots.member_note (written by the member on their own shares
 * page). A note belongs to the share itself, not to the member, so a member with three shares has three independent
 * notes of each kind. Existing lots start without notes. Idempotent.
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

if (!in_array('member_note', $cols, true)) {
    $pdo->exec("ALTER TABLE share_lots ADD COLUMN member_note VARCHAR(500) NULL COMMENT 'note the MEMBER writes about this share (the admin note is separate)' AFTER admin_note");
    echo "- share_lots.member_note added.\n";
} else {
    echo "- share_lots.member_note already exists, skipped.\n";
}

echo "Lot-notes migration finished successfully.\n";
