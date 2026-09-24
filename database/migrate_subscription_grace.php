<?php

/**
 * Keeps the admin's due day visible on every subscription row.
 *
 * A share lot billed AFTER its month's due day already passed gets a short grace before it counts as late. An earlier
 * version stored that grace by moving due_date itself (e.g. due day 10 shown as 1 Oct), so screens no longer matched
 * the due day the admin chose. Now due_date always holds the configured date and grace_until holds the grace.
 *
 *   php database/migrate_subscription_grace.php
 *
 * Adds subscription grace_until and repairs rows written by the old behaviour. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "Connecting to MySQL {$db['database']} for subscription-grace migration...\n";

$cols = $pdo->query('SHOW COLUMNS FROM monthly_subscriptions')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('grace_until', $cols, true)) {
    $pdo->exec('ALTER TABLE monthly_subscriptions ADD COLUMN grace_until DATE NULL AFTER due_date');
    echo "- monthly_subscriptions.grace_until added.\n";
}

$siteDay = (int) ($pdo->query("SELECT value FROM settings WHERE `key` = 'subscription_due_day'")->fetchColumn() ?: 10);
$currentMonth = date('Y-m');

// Rows whose due_date was pushed past the configured due date by the old grace logic (only current/future months).
$rows = $pdo->prepare("SELECT s.id, s.month, s.due_date, l.subscription_due_day AS lot_day, m.subscription_due_day AS member_day
    FROM monthly_subscriptions s
    JOIN members m ON m.id = s.member_id
    LEFT JOIN share_lots l ON l.id = s.lot_id
    WHERE s.month >= ? AND s.grace_until IS NULL");
$rows->execute([$currentMonth]);

$fix = $pdo->prepare('UPDATE monthly_subscriptions SET due_date = ?, grace_until = ? WHERE id = ?');
$repaired = 0;
foreach ($rows->fetchAll() as $r) {
    $day = $r['lot_day'] !== null ? (int) $r['lot_day'] : ($r['member_day'] !== null ? (int) $r['member_day'] : $siteDay);
    $first = new DateTimeImmutable($r['month'] . '-01');
    $configured = $first->format('Y-m') . '-' . str_pad((string) max(1, min($day, (int) $first->format('t'))), 2, '0', STR_PAD_LEFT);
    if ($r['due_date'] > $configured) {
        $fix->execute([$configured, $r['due_date'], $r['id']]);
        echo "  row #{$r['id']} ({$r['month']}): due date {$r['due_date']} -> {$configured}, grace until {$r['due_date']}\n";
        $repaired++;
    }
}

echo "{$repaired} row(s) repaired.\nSubscription-grace migration finished successfully.\n";
