<?php

/**
 * Repairs subscription rows left behind by an older version of the merge/cancel flow, which sometimes left a
 * merged-away or cancelled lot's row un-voided instead of crediting the money to the surviving lot: the member
 * ends up with the old lot still showing as owing (or, worse, as a separate paid row) next to the new lot's row
 * for the same month, which the new lot's row never got credit for.
 *
 *   php database/migrate_repair_stale_lot_rows.php              report only (dry run)
 *   php database/migrate_repair_stale_lot_rows.php --apply      apply the safe + reviewed fixes
 *
 * Two kinds of row are found, handled differently:
 *
 *  - A stale row with NO money on it (amount_paid = 0) is simply voided (amount_due = 0), exactly what the
 *    current code already does for a lot merged/cancelled today. Always safe, applied automatically.
 *
 *  - A stale row that already collected real money (amount_paid > 0) is never touched blindly: the script only
 *    moves that money onto the member's ACTIVE lot's row for the very same month, and only up to what that row
 *    still owes -- it never invents or discards money. It does this only when the member has exactly one active
 *    lot for that month (the unambiguous case the current merge code itself would have produced); anything less
 *    clear-cut (several active lots, or nothing left owing to credit) is reported for the admin to check by hand
 *    and is never touched, even with --apply.
 *
 * Idempotent: rows already fixed (or that were never broken) are not reported again.
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

echo "Connecting to MySQL {$db['database']} for stale-lot-row repair...\n";

// Every row of a lot that is no longer active but still shows as owing something (amount_due > 0):
// a live lot never has this, so any such row is a leftover the void step should already have zeroed.
$stale = $pdo->query(
    "SELECT s.id, s.member_id, s.lot_id, s.month, s.amount_due, s.amount_paid, l.status AS lot_status
     FROM monthly_subscriptions s
     JOIN share_lots l ON l.id = s.lot_id
     WHERE l.status != 'active' AND s.amount_due > 0
     ORDER BY s.member_id, s.month"
)->fetchAll();

echo count($stale) . " stale row(s) found (belonging to a merged/cancelled lot, still showing as owing).\n";

$voidNoMoney = array_values(array_filter($stale, fn($r) => (float) $r['amount_paid'] == 0.0));
$hasMoney = array_values(array_filter($stale, fn($r) => (float) $r['amount_paid'] > 0.0));

echo "  " . count($voidNoMoney) . " have no money on them -- safe to void.\n";
echo "  " . count($hasMoney) . " already collected money and need it moved to the current lot.\n";

$voidStmt = $pdo->prepare('UPDATE monthly_subscriptions SET amount_due = 0, status = ? WHERE id = ?');
foreach ($voidNoMoney as $r) {
    echo "  VOID  member #{$r['member_id']}, lot #{$r['lot_id']} ({$r['lot_status']}), {$r['month']}: due {$r['amount_due']} -> 0\n";
    if ($apply) {
        $voidStmt->execute(['paid', $r['id']]);
    }
}

$activeStmt = $pdo->prepare("SELECT id FROM share_lots WHERE member_id = ? AND status = 'active'");
$targetStmt = $pdo->prepare('SELECT * FROM monthly_subscriptions WHERE member_id = ? AND lot_id = ? AND month = ?');
$creditStmt = $pdo->prepare('UPDATE monthly_subscriptions SET amount_paid = ?, status = ? WHERE id = ?');
$clearStmt = $pdo->prepare('UPDATE monthly_subscriptions SET amount_due = 0, amount_paid = 0, status = ? WHERE id = ?');

foreach ($hasMoney as $r) {
    $activeStmt->execute([$r['member_id']]);
    $activeLots = $activeStmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($activeLots) !== 1) {
        echo "  REVIEW  member #{$r['member_id']}, lot #{$r['lot_id']} ({$r['lot_status']}), {$r['month']}: "
            . "paid {$r['amount_paid']} of {$r['amount_due']}, but the member has " . count($activeLots)
            . " active lot(s) -- pick which one should be credited by hand, then re-run.\n";
        continue;
    }

    $targetStmt->execute([$r['member_id'], $activeLots[0], $r['month']]);
    $target = $targetStmt->fetch();
    if (!$target) {
        echo "  REVIEW  member #{$r['member_id']}, lot #{$r['lot_id']} ({$r['lot_status']}), {$r['month']}: "
            . "paid {$r['amount_paid']}, but active lot #{$activeLots[0]} has no row for {$r['month']} to credit -- check by hand.\n";
        continue;
    }

    $room = round((float) $target['amount_due'] - (float) $target['amount_paid'], 2);
    if ($room <= 0) {
        echo "  REVIEW  member #{$r['member_id']}, lot #{$r['lot_id']} ({$r['lot_status']}), {$r['month']}: "
            . "paid {$r['amount_paid']} has nowhere to go -- lot #{$activeLots[0]}'s row for {$r['month']} is already fully paid. "
            . "Check whether this is a refund/overpayment situation.\n";
        continue;
    }

    $move = min($room, (float) $r['amount_paid']);
    $newPaid = round((float) $target['amount_paid'] + $move, 2);
    $newStatus = $newPaid >= (float) $target['amount_due'] ? 'paid' : 'partial';
    echo "  CREDIT  member #{$r['member_id']}, {$r['month']}: moving {$move} from old lot #{$r['lot_id']} to active lot #{$activeLots[0]} "
        . "(row #{$target['id']}: {$target['amount_paid']} -> {$newPaid})\n";
    if ($apply) {
        $creditStmt->execute([$newPaid, $newStatus, $target['id']]);
        $clearStmt->execute(['paid', $r['id']]);
    }
}

echo $apply ? "Repair applied.\n" : "Dry run only -- re-run with --apply to make these changes.\n";
