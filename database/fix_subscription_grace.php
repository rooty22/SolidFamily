<?php

/**
 * Applies the grace period (Settings > financial controls) to EVERY recorded subscription, old months included, so no
 * member shows "late" / "paid late" for a payment made inside the grace days.
 *
 *   php database/fix_subscription_grace.php --dry-run      # report only, changes nothing
 *   php database/fix_subscription_grace.php                # apply
 *
 * Options:
 *   --dry-run            show what would change, write nothing
 *   --first-month-only   keep "grace for every month" OFF (by default it is switched ON, as the system now does)
 *   --enable             also switch the grace period ON if it is off
 *   --days=N             also set the grace days to N (0-60)
 *
 * Only monthly_subscriptions.grace_until (and the grace settings) change: due dates, amounts and payments are never
 * touched. Idempotent: safe to run more than once.
 */

$opts = getopt('', ['dry-run', 'first-month-only', 'enable', 'days:']);
$dry = isset($opts['dry-run']);

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$get = function (string $key, ?string $default = null) use ($pdo): ?string {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false || $v === null ? $default : (string) $v;
};
$set = function (string $key, string $value) use ($pdo): void {
    $stmt = $pdo->prepare('UPDATE settings SET value = ? WHERE `key` = ?');
    $stmt->execute([$value, $key]);
    $exists = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
    $exists->execute([$key]);
    if (!(int) $exists->fetchColumn()) {
        $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?)')->execute([$key, $value]);
    }
};

echo "Database: {$db['database']}" . ($dry ? "   (DRY RUN - nothing will be written)" : '') . "\n\n";

// ---- 0. The grace_until column (added by migrate_subscription_grace.php) ----
$cols = $pdo->query('SHOW COLUMNS FROM monthly_subscriptions')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('grace_until', $cols, true)) {
    if ($dry) {
        echo "- column monthly_subscriptions.grace_until is MISSING (it will be added).\n";
        echo "  Run without --dry-run to add it and apply the grace.\n";
        exit(0);
    }
    $pdo->exec('ALTER TABLE monthly_subscriptions ADD COLUMN grace_until DATE NULL AFTER due_date');
    echo "- column monthly_subscriptions.grace_until added.\n";
}

// ---- 1. Settings: current -> target ----
$cur = [
    'enabled' => $get('subscription_grace_enabled', '1') === '1',
    'all' => $get('subscription_grace_all_months', '1') === '1',
    'days' => max(0, (int) $get('subscription_grace_days', '7')),
];
$new = $cur;
if (!isset($opts['first-month-only'])) {
    $new['all'] = true;
} else {
    $new['all'] = false;
}
if (isset($opts['enable'])) {
    $new['enabled'] = true;
}
if (isset($opts['days'])) {
    $d = (int) $opts['days'];
    if ($d < 0 || $d > 60 || !preg_match('/^\d+$/', (string) $opts['days'])) {
        fwrite(STDERR, "--days must be a whole number between 0 and 60.\n");
        exit(1);
    }
    $new['days'] = $d;
}
$describe = fn(array $s) => $s['enabled'] ? "ON, {$s['days']} day(s), " . ($s['all'] ? 'every month' : 'first month of a share only') : 'OFF';
echo "Grace settings now : " . $describe($cur) . "\n";
echo "Grace settings after: " . $describe($new) . "\n";
if (!$new['enabled']) {
    echo "\n!! The grace period is switched OFF: every payment after the due day stays late.\n";
    echo "   Turn it on from Settings > financial controls, or re-run with --enable.\n";
}
echo "\n";

$enabled = $new['enabled'] ? 1 : 0;
$all = ($new['enabled'] && $new['all']) ? 1 : 0;
$days = $new['days'];

// The grace end each row should have (same rules as MonthlySubscription::graceUntilFor()).
$graceExpr = "CASE
    WHEN {$enabled} = 0 THEN NULL
    WHEN l.created_at IS NOT NULL AND s.month = DATE_FORMAT(l.created_at, '%Y-%m') AND s.due_date < DATE(l.created_at)
        THEN DATE_ADD(DATE(l.created_at), INTERVAL {$days} DAY)
    WHEN {$all} = 1 THEN DATE_ADD(s.due_date, INTERVAL {$days} DAY)
    ELSE NULL END";
$paidOn = "(SELECT MAX(t.transaction_date) FROM transactions t WHERE t.category = 'subscription' AND t.related_id = s.id)";

// ---- 2. Report: what the members see now vs after ----
$report = $pdo->query(
    "SELECT
        SUM(NOT (s.grace_until <=> x.g)) AS rows_changed,
        SUM(s.amount_paid > 0 AND x.paid_on IS NOT NULL AND x.paid_on > GREATEST(s.due_date, COALESCE(s.grace_until, s.due_date))) AS paid_late_before,
        SUM(s.amount_paid > 0 AND x.paid_on IS NOT NULL AND x.paid_on > GREATEST(s.due_date, COALESCE(x.g, s.due_date))) AS paid_late_after,
        SUM(s.amount_due > s.amount_paid AND GREATEST(s.due_date, COALESCE(s.grace_until, s.due_date)) < CURDATE()) AS late_before,
        SUM(s.amount_due > s.amount_paid AND GREATEST(s.due_date, COALESCE(x.g, s.due_date)) < CURDATE()) AS late_after,
        COUNT(*) AS total
     FROM monthly_subscriptions s
     JOIN (SELECT s.id, {$graceExpr} AS g, {$paidOn} AS paid_on
             FROM monthly_subscriptions s LEFT JOIN share_lots l ON l.id = s.lot_id) x ON x.id = s.id"
)->fetch();

echo "Subscription rows            : " . (int) $report['total'] . "\n";
echo "Rows whose grace will change : " . (int) $report['rows_changed'] . "\n";
echo "\"Paid late\" rows   before -> after: " . (int) $report['paid_late_before'] . " -> " . (int) $report['paid_late_after'] . "\n";
echo "Unpaid \"late\" rows before -> after: " . (int) $report['late_before'] . " -> " . (int) $report['late_after'] . "\n\n";

// Sample of the rows that stop being "paid late".
$sample = $pdo->query(
    "SELECT m.name, s.month, s.due_date, x.paid_on, s.grace_until AS old_grace, x.g AS new_grace
       FROM monthly_subscriptions s
       JOIN members m ON m.id = s.member_id
       JOIN (SELECT s.id, {$graceExpr} AS g, {$paidOn} AS paid_on
               FROM monthly_subscriptions s LEFT JOIN share_lots l ON l.id = s.lot_id) x ON x.id = s.id
      WHERE s.amount_paid > 0 AND x.paid_on IS NOT NULL
        AND x.paid_on >  GREATEST(s.due_date, COALESCE(s.grace_until, s.due_date))
        AND x.paid_on <= GREATEST(s.due_date, COALESCE(x.g, s.due_date))
      ORDER BY m.name, s.month LIMIT 30"
)->fetchAll();
if ($sample) {
    echo "Rows that will no longer show \"paid late\" (first 30):\n";
    foreach ($sample as $r) {
        printf("  %-30s %s  due %s  paid %s  grace %s -> %s\n",
            mb_substr((string) $r['name'], 0, 30), $r['month'], $r['due_date'], $r['paid_on'], $r['old_grace'] ?? '-', $r['new_grace'] ?? '-');
    }
    echo "\n";
}

if ($dry) {
    echo "Dry run finished: nothing was changed. Run again without --dry-run to apply.\n";
    exit(0);
}

// ---- 3. Apply ----
$pdo->beginTransaction();
try {
    $set('subscription_grace_enabled', $new['enabled'] ? '1' : '0');
    $set('subscription_grace_all_months', $new['all'] ? '1' : '0');
    $set('subscription_grace_days', (string) $new['days']);
    $set('subscription_grace_all_months_upgraded', '1');

    $updated = $pdo->exec("UPDATE monthly_subscriptions s LEFT JOIN share_lots l ON l.id = s.lot_id SET s.grace_until = {$graceExpr}");

    // Same fingerprint MonthlySubscription::ensureGraceSynced() writes, so the app does not redo the work.
    $set('subscription_grace_synced', 'v1|' . $enabled . '|' . $all . '|' . $days);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "FAILED, nothing was changed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Done: {$updated} row(s) updated. Grace is now: " . $describe($new) . ".\n";

