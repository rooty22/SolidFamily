<?php

/**
 * Read-only report: why do subscriptions show "paid late"?   php database/diagnose_paid_late.php
 *
 * Lists every subscription row that was paid (fully or partly) after its due date + grace, with the facts the status is
 * computed from (lot start date, due date, payment date, grace) and the reason:
 *   - FIRST MONTH, grace missing   : the lot started after that month's due date but the row has no grace
 *                                    -> run  php database/migrate_first_month_grace.php
 *   - LATE (inside the optional grace): paid after the due date but within the grace days; it would not be late if the
 *                                    setting "apply the grace period to every month" were on (Settings > financial)
 *   - LATE                         : paid after the due date AND after any grace: really late
 * Changes nothing.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$setting = function (string $key, string $default) use ($pdo): string {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false || $v === null ? $default : (string) $v;
};
$enabled = $setting('subscription_grace_enabled', '1') === '1';
$days = max(0, (int) $setting('subscription_grace_days', '7'));
$all = $setting('subscription_grace_all_months', '1') === '1';

echo "Database: {$db['database']}\n";
echo 'Grace period: ' . ($enabled ? "ON, {$days} days" . ($all ? ', every month' : ', first month of a lot only') : 'OFF') . "\n";
echo 'Default due day: ' . $setting('subscription_due_day', '10') . "\n\n";

$rows = $pdo->query(
    "SELECT s.id, s.month, s.due_date, s.grace_until, s.amount_due, s.amount_paid, m.name AS member, m.subscription_due_day AS member_due_day,
            l.id AS lot_id, DATE(l.created_at) AS lot_start, l.subscription_due_day AS lot_due_day,
            (SELECT MAX(t.transaction_date) FROM transactions t WHERE t.category = 'subscription' AND t.related_id = s.id) AS paid_on
       FROM monthly_subscriptions s
       JOIN members m ON m.id = s.member_id
       LEFT JOIN share_lots l ON l.id = s.lot_id
      WHERE s.amount_paid > 0
      ORDER BY m.name, s.month, s.lot_id"
)->fetchAll();

$counts = ['first' => 0, 'inside' => 0, 'late' => 0];
$lines = [];
foreach ($rows as $r) {
    if (!$r['paid_on']) {
        continue;
    }
    $eff = ($r['grace_until'] && $r['grace_until'] > $r['due_date']) ? $r['grace_until'] : $r['due_date'];
    if ($r['paid_on'] <= $eff) {
        continue; // on time (or inside its stored grace)
    }
    $lateDays = (int) round((strtotime($r['paid_on']) - strtotime($eff)) / 86400);
    $afterDue = (int) round((strtotime($r['paid_on']) - strtotime($r['due_date'])) / 86400);
    $isFirstMonth = $r['lot_start'] && substr($r['lot_start'], 0, 7) === $r['month'];
    if ($isFirstMonth && $r['due_date'] < $r['lot_start'] && $enabled) {
        $kind = 'first';
        $why = "FIRST MONTH, grace missing (lot started {$r['lot_start']}, after the due date)";
    } elseif ($isFirstMonth && $r['due_date'] < $r['lot_start'] && !$enabled) {
        $kind = 'late';
        $why = "LATE by {$lateDays} day(s): first month (lot started {$r['lot_start']}, after the due date) but the grace period is OFF in the settings";
    } elseif ($enabled && $afterDue <= $days) {
        $kind = 'inside';
        $why = "LATE by {$afterDue} day(s) but inside the {$days}-day grace (covered only by \"grace for every month\")";
    } else {
        $kind = 'late';
        $why = "LATE by {$lateDays} day(s)" . ($isFirstMonth ? " (lot started {$r['lot_start']}, on/before the due date)" : '');
    }
    $counts[$kind]++;
    $lines[] = sprintf("%-28s %s  lot#%s start %s | due %s | paid %s | grace %s | %s",
        mb_substr((string) $r['member'], 0, 28), $r['month'], $r['lot_id'] ?? '-', $r['lot_start'] ?? '-', $r['due_date'], $r['paid_on'], $r['grace_until'] ?? '-', $why);
}

foreach (array_slice($lines, 0, 200) as $line) {
    echo $line . "\n";
}
if (count($lines) > 200) {
    echo '... and ' . (count($lines) - 200) . " more\n";
}
echo "\nSummary: " . count($lines) . " row(s) paid after the due date + grace\n";
echo "  - first month, grace missing (fixed by migrate_first_month_grace.php): {$counts['first']}\n";
echo "  - late but inside the grace days (fixed by turning on \"grace for every month\"): {$counts['inside']}\n";
echo "  - really late: {$counts['late']}\n";
