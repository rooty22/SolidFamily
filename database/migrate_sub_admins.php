<?php

/**
 * Sub-admins (المشرفون الفرعيون): a photo, an active / suspended status and per-section permissions on each admin.
 *
 *   php database/migrate_sub_admins.php
 *
 * Adds admins.avatar, admins.status, admins.is_super and admins.permissions (JSON {"section": "view"|"manage"}).
 * Every admin that already exists becomes a main admin (is_super = 1) with full access, so nothing changes for them.
 * Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('avatar', $cols, true)) {
    $pdo->exec("ALTER TABLE admins ADD COLUMN avatar VARCHAR(255) NULL AFTER password");
    echo "- admins.avatar added.\n";
}
if (!in_array('status', $cols, true)) {
    $pdo->exec("ALTER TABLE admins ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER avatar");
    echo "- admins.status added.\n";
}
if (!in_array('permissions', $cols, true)) {
    $pdo->exec("ALTER TABLE admins ADD COLUMN permissions TEXT NULL COMMENT 'JSON {section: view|manage}; ignored for main admins' AFTER status");
    echo "- admins.permissions added.\n";
}
if (!in_array('is_super', $cols, true)) {
    $pdo->exec("ALTER TABLE admins ADD COLUMN is_super TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = main admin: full access and manages the sub-admins' AFTER permissions");
    $n = $pdo->exec('UPDATE admins SET is_super = 1');
    echo "- admins.is_super added; {$n} existing admin(s) are main admins.\n";
}

echo "Sub-admins migration finished successfully.\n";
