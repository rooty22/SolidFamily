<?php

/**
 * Self-registrations wait for admin approval before the member can log in.
 *
 *   php database/migrate_member_approval.php
 *
 * Adds 'pending' and 'rejected' to members.status. Existing members keep their status (active / inactive). Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$col = $pdo->query("SHOW COLUMNS FROM members LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
if ($col && !str_contains((string) $col['Type'], "'pending'")) {
    $pdo->exec("ALTER TABLE members MODIFY status ENUM('active','inactive','pending','rejected') NOT NULL DEFAULT 'active' COMMENT 'pending/rejected: a self-registration waiting for / refused by an admin; neither can log in'");
    echo "- members.status now accepts pending and rejected.\n";
} else {
    echo "- members.status already accepts pending and rejected, skipped.\n";
}

echo "Member-approval migration finished successfully.\n";
