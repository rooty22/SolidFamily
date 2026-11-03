<?php

/**
 * Fixed serial number + User ID for every member.
 *
 *   php database/migrate_member_serial.php
 *
 * - members.serial_no: 1, 2, 3 ... in registration order (oldest member = 1). Existing members are numbered once, by
 *   registration date (members.created_at, then the database id), so their order is kept. Self-registrations that are
 *   still pending or were rejected are not members yet: they get their number the moment an admin approves them.
 *   A number is never reused: the last number handed out is kept in settings.member_serial_seq, so deleting or
 *   suspending a member never frees a number, and editing a member never changes it.
 * - members.uid: an independent User ID ("U" + 7 digits) for every member, including pending ones. The database id
 *   (members.id) is left exactly as it is.
 *
 * Idempotent: members that already have a number / User ID keep it.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$cols = $pdo->query('SHOW COLUMNS FROM members')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('serial_no', $cols, true)) {
    $pdo->exec("ALTER TABLE members ADD COLUMN serial_no INT UNSIGNED NULL COMMENT 'fixed registration-order number, never reused or changed' AFTER id");
    echo "- members.serial_no added.\n";
}
if (!in_array('uid', $cols, true)) {
    $pdo->exec("ALTER TABLE members ADD COLUMN uid VARCHAR(16) NULL COMMENT 'independent User ID, never changed' AFTER serial_no");
    echo "- members.uid added.\n";
}
$indexes = array_column($pdo->query('SHOW INDEX FROM members')->fetchAll(), 'Key_name');
if (!in_array('uniq_members_serial_no', $indexes, true)) {
    $pdo->exec('ALTER TABLE members ADD UNIQUE KEY uniq_members_serial_no (serial_no)');
    echo "- unique index on members.serial_no added.\n";
}
if (!in_array('uniq_members_uid', $indexes, true)) {
    $pdo->exec('ALTER TABLE members ADD UNIQUE KEY uniq_members_uid (uid)');
    echo "- unique index on members.uid added.\n";
}

// The counter: never lower than the biggest number in use, so a number that was handed out and later lost with a deleted member is never given again.
$pdo->exec("INSERT INTO settings (`key`, value) VALUES ('member_serial_seq', '0') ON DUPLICATE KEY UPDATE `key` = `key`");
$seq = (int) $pdo->query("SELECT value FROM settings WHERE `key` = 'member_serial_seq'")->fetchColumn();
$seq = max($seq, (int) $pdo->query('SELECT COALESCE(MAX(serial_no), 0) FROM members')->fetchColumn());

$todo = $pdo->query(
    "SELECT id FROM members WHERE serial_no IS NULL AND status IN ('active', 'inactive') ORDER BY created_at ASC, id ASC"
)->fetchAll(PDO::FETCH_COLUMN);
$set = $pdo->prepare('UPDATE members SET serial_no = ? WHERE id = ? AND serial_no IS NULL');
$pdo->beginTransaction();
foreach ($todo as $id) {
    $set->execute([++$seq, $id]);
}
$pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'member_serial_seq'")->execute([(string) $seq]);
$pdo->commit();
echo '- ' . count($todo) . " member(s) numbered by registration order (last number: {$seq}).\n";

$needUid = $pdo->query('SELECT id FROM members WHERE uid IS NULL ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$used = array_flip($pdo->query('SELECT uid FROM members WHERE uid IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN));
$setUid = $pdo->prepare('UPDATE members SET uid = ? WHERE id = ? AND uid IS NULL');
foreach ($needUid as $id) {
    do {
        $uid = 'U' . random_int(1000000, 9999999);
    } while (isset($used[$uid]));
    $used[$uid] = true;
    $setUid->execute([$uid, $id]);
}
echo '- ' . count($needUid) . " User ID(s) generated.\n";

echo "Member-serial migration finished successfully.\n";
