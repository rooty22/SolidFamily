<?php

/**
 * A cancelled share lot gets its own status ('cancelled') instead of sharing 'merged' with merged lots, and its
 * unpaid dues are cleared so they stop showing as owed / late and stop counting in balances.
 *
 *   php database/migrate_cancelled_lots.php
 *
 * Existing lots: a lot with status 'merged' that was neither folded into another lot (merged_into_id), nor holds
 * other lots, nor was retired by a split (share_merge_log) can only have been cancelled; it becomes 'cancelled' and
 * every month of it nobody paid is removed (a partly paid month is closed at what was paid). Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$col = $pdo->query("SHOW COLUMNS FROM share_lots LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
if ($col && !str_contains((string) $col['Type'], "'cancelled'")) {
    $pdo->exec("ALTER TABLE share_lots MODIFY status ENUM('active','merged','cancelled') NOT NULL DEFAULT 'active'");
    echo "- share_lots.status now accepts cancelled.\n";
} else {
    echo "- share_lots.status already accepts cancelled, skipped.\n";
}

$cols = $pdo->query('SHOW COLUMNS FROM share_lots')->fetchAll(PDO::FETCH_COLUMN);
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('merged_into_id', $cols, true)) {
    echo "- share_lots.merged_into_id is missing: run migrate_split_lots.php first, then this script again.\n";
    exit(1);
}

$notSplit = in_array('share_merge_log', $tables, true)
    ? "AND l.id NOT IN (SELECT lot_id FROM share_merge_log WHERE action = 'split' AND lot_id IS NOT NULL)"
    : '';
$cancelled = $pdo->query(
    "SELECT l.id, l.member_id FROM share_lots l
      WHERE l.status = 'merged' AND l.merged_into_id IS NULL
        AND NOT EXISTS (SELECT 1 FROM share_lots c WHERE c.merged_into_id = l.id)
        $notSplit"
)->fetchAll(PDO::FETCH_ASSOC);

$mark = $pdo->prepare("UPDATE share_lots SET status = 'cancelled' WHERE id = ?");
$dropUnpaid = $pdo->prepare('DELETE FROM monthly_subscriptions WHERE lot_id = ? AND (amount_paid = 0 OR amount_paid IS NULL)');
$closePartial = $pdo->prepare("UPDATE monthly_subscriptions SET amount_due = amount_paid, status = 'paid' WHERE lot_id = ? AND amount_paid > 0 AND amount_paid < amount_due");
$rows = 0;
foreach ($cancelled as $lot) {
    $mark->execute([$lot['id']]);
    $dropUnpaid->execute([$lot['id']]);
    $rows += $dropUnpaid->rowCount();
    $closePartial->execute([$lot['id']]);
}
echo '- ' . count($cancelled) . " cancelled lot(s) marked; {$rows} unpaid month(s) of them removed.\n";

// Lots already marked cancelled (by the new code) never keep unpaid dues either.
$pdo->exec("DELETE s FROM monthly_subscriptions s JOIN share_lots l ON l.id = s.lot_id WHERE l.status = 'cancelled' AND (s.amount_paid = 0 OR s.amount_paid IS NULL)");

echo "Cancelled-lots migration finished successfully.\n";
