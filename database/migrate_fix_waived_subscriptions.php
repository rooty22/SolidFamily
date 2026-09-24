<?php

/**
 * Repairs subscription rows that an older version of the billing code marked "paid" although nothing (or only
 * part of the amount) was collected: a share lot added AFTER the month's due day used to be "waived" that way,
 * so the month looked complete to the admin and the member was never asked for the new share's amount.
 *
 *   php database/migrate_fix_waived_subscriptions.php              report only (dry run)
 *   php database/migrate_fix_waived_subscriptions.php --apply      current + future months only
 *   php database/migrate_fix_waived_subscriptions.php --apply --all   every month (turns old "paid" history into debts)
 *
 * Affected rows become "unpaid" (nothing collected) or "partial"; a current-month row whose due date already
 * passed gets a 7-day grace so it is not flagged late the moment it is repaired. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$apply = in_array('--apply', $argv ?? [], true);
$all = in_array('--all', $argv ?? [], true);
$currentMonth = date('Y-m');
$graceDate = date('Y-m-d', strtotime('+7 days'));

// Rows of lots that were merged/cancelled are voids by design, not debts: only rows of ACTIVE lots (or lot-less rows) qualify.
$where = "status = 'paid' AND amount_paid < amount_due"
    . " AND (lot_id IS NULL OR lot_id IN (SELECT id FROM share_lots WHERE status = 'active'))"
    . ($all ? '' : " AND month >= '{$currentMonth}'");
$rows = $pdo->query("SELECT id, member_id, month, amount_due, amount_paid, due_date FROM monthly_subscriptions WHERE {$where} ORDER BY month, member_id")->fetchAll();

echo "Connecting to MySQL {$db['database']}...\n";
echo count($rows) . " subscription row(s) are marked paid without being fully collected" . ($all ? '' : " (current/future months)") . ".\n";
foreach ($rows as $r) {
    $new = (float) $r['amount_paid'] > 0 ? 'partial' : 'unpaid';
    echo sprintf("  row #%d member #%d %s: due %s, paid %s -> %s\n", $r['id'], $r['member_id'], $r['month'], $r['amount_due'], $r['amount_paid'], $new);
}

if (!$apply) {
    echo $rows ? "Dry run only. Re-run with --apply to repair them.\n" : "Nothing to repair.\n";
    exit(0);
}

$update = $pdo->prepare('UPDATE monthly_subscriptions SET status = ?, due_date = ? WHERE id = ?');
foreach ($rows as $r) {
    $status = (float) $r['amount_paid'] > 0 ? 'partial' : 'unpaid';
    $due = ($r['month'] === $currentMonth && $r['due_date'] < date('Y-m-d')) ? $graceDate : $r['due_date'];
    $update->execute([$status, $due, $r['id']]);
}
echo count($rows) . " row(s) repaired.\n";
