<?php

/**
 * Checks (and optionally repairs) the due dates of ONE member's monthly subscription rows.
 *
 * Every row's due_date should be "its month + the member's due day" (the lot's own day, else the member's day, else the
 * site default). This lists the due day each level holds and every row whose stored due_date disagrees with it.
 *
 *   php database/repair_subscription_due_dates.php --member=12                      report only (dry run)
 *   php database/repair_subscription_due_dates.php --member=12 --set-day=10         also report setting that day on the member + lots
 *   php database/repair_subscription_due_dates.php --member=12 --set-day=10 --apply write it, then realign the rows
 *   php database/repair_subscription_due_dates.php --member=12 --apply              realign rows to the days already configured
 *
 * --member also accepts part of the member's name (it must match exactly one member). Only due_date is rewritten:
 * amounts, payments and grace dates are never touched. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$opts = [];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    }
}
$apply = isset($opts['apply']);
$setDay = isset($opts['set-day']) ? (int) $opts['set-day'] : null;
if ($setDay !== null && ($setDay < 1 || $setDay > 28)) {
    fwrite(STDERR, "--set-day must be between 1 and 28.\n");
    exit(1);
}
if (empty($opts['member']) || $opts['member'] === true) {
    fwrite(STDERR, "Usage: php database/repair_subscription_due_dates.php --member=<id or name> [--set-day=10] [--apply]\n");
    exit(1);
}

if (ctype_digit((string) $opts['member'])) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
    $stmt->execute([(int) $opts['member']]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE name LIKE ?');
    $stmt->execute(['%' . $opts['member'] . '%']);
}
$members = $stmt->fetchAll();
if (count($members) !== 1) {
    fwrite(STDERR, count($members) . " members matched; pass the exact id instead:\n");
    foreach ($members as $m) {
        fwrite(STDERR, "  #{$m['id']} {$m['name']}\n");
    }
    exit(1);
}
$member = $members[0];

$siteDay = (int) ($pdo->query("SELECT value FROM settings WHERE `key` = 'subscription_due_day'")->fetchColumn() ?: 10);
$lots = $pdo->prepare('SELECT * FROM share_lots WHERE member_id = ? ORDER BY created_at, id');
$lots->execute([$member['id']]);
$lots = $lots->fetchAll();

echo "Member #{$member['id']} {$member['name']}\n";
echo "  site default due day : {$siteDay}\n";
echo "  member due day       : " . ($member['subscription_due_day'] ?? 'NULL (follows site default)') . "\n";
foreach ($lots as $l) {
    echo "  lot #{$l['id']} ({$l['status']}, {$l['shares_count']} shares, from " . substr($l['created_at'], 0, 10) . ") due day: "
        . ($l['subscription_due_day'] ?? 'NULL (follows member/site)') . "\n";
}

if ($setDay !== null) {
    echo "\nWill set due day {$setDay} on the member and on " . count($lots) . " lot(s)" . ($apply ? '' : ' (dry run)') . ".\n";
    if ($apply) {
        $pdo->prepare('UPDATE members SET subscription_due_day = ? WHERE id = ?')->execute([$setDay, $member['id']]);
        $pdo->prepare('UPDATE share_lots SET subscription_due_day = ? WHERE member_id = ?')->execute([$setDay, $member['id']]);
        $member['subscription_due_day'] = $setDay;
        foreach ($lots as &$l) {
            $l['subscription_due_day'] = $setDay;
        }
        unset($l);
    } else {
        $member['subscription_due_day'] = $setDay;
        foreach ($lots as &$l) {
            $l['subscription_due_day'] = $setDay;
        }
        unset($l);
    }
}

$lotDay = [];
foreach ($lots as $l) {
    $lotDay[(int) $l['id']] = $l['subscription_due_day'];
}

$rows = $pdo->prepare('SELECT id, lot_id, month, due_date FROM monthly_subscriptions WHERE member_id = ? ORDER BY month, lot_id');
$rows->execute([$member['id']]);
$fix = $pdo->prepare('UPDATE monthly_subscriptions SET due_date = ? WHERE id = ?');

echo "\nRows whose due_date differs from the configured day:\n";
$wrong = 0;
foreach ($rows->fetchAll() as $r) {
    $day = ($r['lot_id'] !== null && ($lotDay[(int) $r['lot_id']] ?? null) !== null)
        ? (int) $lotDay[(int) $r['lot_id']]
        : (int) ($member['subscription_due_day'] ?? $siteDay);
    $first = new DateTimeImmutable($r['month'] . '-01');
    $expected = $first->format('Y-m') . '-' . str_pad((string) max(1, min($day, (int) $first->format('t'))), 2, '0', STR_PAD_LEFT);
    if ($r['due_date'] !== $expected) {
        echo "  row #{$r['id']} {$r['month']} (lot " . ($r['lot_id'] ?? '-') . "): {$r['due_date']} -> {$expected}\n";
        if ($apply) {
            $fix->execute([$expected, $r['id']]);
        }
        $wrong++;
    }
}

echo "{$wrong} row(s) " . ($apply ? 'repaired' : 'differ') . ".\n";
if (!$apply) {
    echo "Dry run only -- re-run with --apply to make these changes.\n";
}
