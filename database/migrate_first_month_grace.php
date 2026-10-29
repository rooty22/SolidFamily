<?php

/**
 * Recomputes the grace period of every stored subscription row from the current grace settings.
 *
 *   php database/migrate_first_month_grace.php
 *
 * Rules (the same ones the system applies from now on, see MonthlySubscription::graceUntilFor()):
 *  - the FIRST month of a share lot that started after its due date gets the grace counted from the day the lot
 *    started (back-dated lots used to get none, so a payment right at the start was flagged "paid late");
 *  - any other month gets a grace from its due date only when the setting "grace for every month"
 *    (subscription_grace_all_months) is on;
 *  - none at all when the grace period is switched off.
 * Only subscription rows' grace_until moves: due dates, amounts and payments are untouched. Idempotent.
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

$all = $enabled && $setting('subscription_grace_all_months', '0') === '1';

$before = (int) $pdo->query("SELECT COUNT(*) FROM monthly_subscriptions WHERE grace_until IS NOT NULL")->fetchColumn();
$stmt = $pdo->prepare(
    "UPDATE monthly_subscriptions s
     LEFT JOIN share_lots l ON l.id = s.lot_id
     SET s.grace_until = CASE
         WHEN ? = 0 THEN NULL
         WHEN l.created_at IS NOT NULL AND s.month = DATE_FORMAT(l.created_at, '%Y-%m') AND s.due_date < DATE(l.created_at)
             THEN DATE_ADD(DATE(l.created_at), INTERVAL ? DAY)
         WHEN ? = 1 THEN DATE_ADD(s.due_date, INTERVAL ? DAY)
         ELSE NULL END"
);
$stmt->execute([$enabled ? 1 : 0, $days, $all ? 1 : 0, $days]);
$changed = $stmt->rowCount();
$after = (int) $pdo->query("SELECT COUNT(*) FROM monthly_subscriptions WHERE grace_until IS NOT NULL")->fetchColumn();

echo "- grace: " . ($enabled ? "on, {$days} days" . ($all ? ', every month' : ', first month of a lot') : 'off') . ".\n";
echo "- rows updated: {$changed}; rows with a grace before: {$before}, after: {$after}.\n";
echo "Grace migration finished successfully.\n";
