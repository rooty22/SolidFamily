<?php

/**
 * Re-applies the grace period of a share lot's FIRST month from the lot's own start date.
 *
 *   php database/migrate_first_month_grace.php
 *
 * A lot that starts after its first month's due day already passed (the due date is before the lot existed) gets the
 * grace period (settings: subscription_grace_days, subscription_grace_enabled) counted from the day it started. Lots
 * that were back-dated, or added by an admin with an earlier start date, never got it, so a payment made right at the
 * start was flagged "paid late". Only subscription rows' grace_until moves: due dates, amounts and payments are
 * untouched. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$setting = function (string $key, string $default) use ($pdo): string {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false || $v === null ? $default : (string) $v;
};
$enabled = $setting('subscription_grace_enabled', '1') === '1';
$days = max(0, (int) $setting('subscription_grace_days', '7'));

// Every lot's first month row: month = the lot's start month.
$rows = $pdo->query(
    "SELECT s.id, s.due_date, s.grace_until, DATE(l.created_at) AS start_date
       FROM monthly_subscriptions s
       JOIN share_lots l ON l.id = s.lot_id
      WHERE s.month = DATE_FORMAT(l.created_at, '%Y-%m')"
)->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare('UPDATE monthly_subscriptions SET grace_until = ? WHERE id = ?');
$set = 0;
$cleared = 0;
foreach ($rows as $r) {
    $grace = ($enabled && $r['due_date'] < $r['start_date'])
        ? date('Y-m-d', strtotime($r['start_date'] . " +{$days} days"))
        : null;
    if ($grace === $r['grace_until']) {
        continue;
    }
    $update->execute([$grace, $r['id']]);
    $grace === null ? $cleared++ : $set++;
}

echo "- first-month rows checked: " . count($rows) . ", grace set/updated on {$set}, cleared on {$cleared}.\n";
echo "First-month grace migration finished successfully.\n";
