<?php

/**
 * Deletes everything one member did (or that belongs to him) from the database. For cleaning test accounts / wrong
 * entries; the other members and the fund settings are never touched.
 *
 *   php database/purge_member.php --member=50                      dry run: shows exactly what would be deleted
 *   php database/purge_member.php --member=50 --yes                deletes his ACTIVITY, keeps his account and his shares
 *   php database/purge_member.php --member=50 --with-shares --yes  + deletes his shares too (account kept, 0 shares)
 *   php database/purge_member.php --member=50 --delete-account --yes   deletes everything including the account itself
 *
 * --member accepts the member's serial number (the "#50" shown in the lists), User ID, national ID, mobile, e-mail,
 * or "id:123" for the internal row id. When more than one member matches, nothing is done and the matches are listed.
 *
 * What counts as his activity: monthly subscriptions, founding amount + payments (+ their share allocations), loans with
 * their installments, loan requests, share requests and the merge log, every ledger transaction (subscriptions,
 * founding, loans, installments, commissions), notifications sent to him, his contact messages.
 * Deleting the ledger rows removes his money from the bank balance and from every dashboard total, because all totals
 * are computed from the rows. Run database/audit_totals.php afterwards to confirm the totals still agree.
 *
 * Options:
 *   --yes                 really delete (without it nothing is changed)
 *   --with-shares         also delete his shares (share_lots) and set shares_count to 0
 *   --delete-account      also delete the member row (implies --with-shares)
 *   --allow-admin         allow purging a member that is also an administrator (refused by default)
 *   --no-backup           skip the JSON backup of the deleted rows (default: saved in database/purge_backups/)
 *   --database=name       use another database than config/config.php
 *
 * The delete runs in ONE transaction: if anything fails nothing is changed.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$k, $v] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $opts[$k] = $v;
    }
}
if (isset($opts['database']) && is_string($opts['database'])) {
    $db['database'] = $opts['database'];
}
$apply = isset($opts['yes']);
$deleteAccount = isset($opts['delete-account']);
$withShares = isset($opts['with-shares']) || $deleteAccount;

if (empty($opts['member']) || $opts['member'] === true) {
    fwrite(STDERR, "Usage: php database/purge_member.php --member=<serial|user id|national id|mobile|email|id:N> [--with-shares|--delete-account] [--yes]\n");
    exit(2);
}

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$has = fn(string $t): bool => in_array($t, $tables, true);
$memberCols = $pdo->query('SHOW COLUMNS FROM members')->fetchAll(PDO::FETCH_COLUMN);

// ---- find the member -------------------------------------------------------------------------------------------------
$key = (string) $opts['member'];
if (preg_match('/^id:(\d+)$/', $key, $m)) {
    $found = $pdo->query('SELECT * FROM members WHERE id = ' . (int) $m[1])->fetchAll();
} else {
    $conds = ['national_id = :k', 'mobile = :k', 'LOWER(email) = LOWER(:k)'];
    if (in_array('uid', $memberCols, true)) {
        $conds[] = 'uid = :k';
    }
    if (in_array('serial_no', $memberCols, true) && ctype_digit($key)) {
        $conds[] = 'serial_no = :k';
    }
    $st = $pdo->prepare('SELECT * FROM members WHERE ' . implode(' OR ', $conds));
    $st->execute(['k' => $key]);
    $found = $st->fetchAll();
}
if (count($found) !== 1) {
    fwrite(STDERR, count($found) === 0 ? "No member matches \"$key\".\n" : "More than one member matches \"$key\"; use id:N. Matches:\n");
    foreach ($found as $f) {
        fwrite(STDERR, sprintf("  id:%d  #%s  %s  (%s)\n", $f['id'], $f['serial_no'] ?? '-', $f['name'], $f['national_id']));
    }
    exit(1);
}
$member = $found[0];
$mid = (int) $member['id'];

$isAdmin = !empty($member['is_admin'])
    || ($has('admins') && (int) $pdo->query('SELECT COUNT(*) FROM admins WHERE LOWER(email) = ' . $pdo->quote(mb_strtolower((string) $member['email'])))->fetchColumn() > 0);
if ($isAdmin && !isset($opts['allow-admin'])) {
    fwrite(STDERR, "\"{$member['name']}\" is also an administrator account. Refused (use --allow-admin if you really mean it).\n");
    exit(1);
}

// ---- what belongs to him (child tables first) ------------------------------------------------------------------------
// table => [WHERE clause using :m, only when this level of the purge deletes it]
$scope = [
    'founding_payment_allocations' => ['payment_id IN (SELECT id FROM founding_payments WHERE member_id = :m)', true],
    'founding_payments' => ['member_id = :m', true],
    'founding_amounts' => ['member_id = :m', true],
    'loan_installments' => ['loan_id IN (SELECT id FROM loans WHERE member_id = :m)', true],
    'loans' => ['member_id = :m', true],
    'loan_requests' => ['member_id = :m', true],
    'monthly_subscriptions' => ['member_id = :m', true],
    'transactions' => ['member_id = :m', true],
    'share_requests' => ['member_id = :m', true],
    'share_merge_log' => ['member_id = :m', true],
    'notifications' => ['target_member_id = :m', true],
    'contact_messages' => ['member_id = :m', true],
    'share_lots' => ['member_id = :m', $withShares],
    'members' => ['id = :m', $deleteAccount],
];

$plan = [];
foreach ($scope as $table => [$where, $active]) {
    if (!$has($table)) {
        continue;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");
    $st->execute(['m' => $mid]);
    $plan[$table] = ['where' => $where, 'count' => (int) $st->fetchColumn(), 'delete' => $active];
}
// Allocations / payments of his shares made by anyone else cannot exist (a payment belongs to one member), but make sure
// that deleting his shares would not orphan another member's rows.
if ($withShares && $has('founding_payment_allocations')) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM founding_payment_allocations a JOIN share_lots l ON l.id = a.lot_id
        JOIN founding_payments p ON p.id = a.payment_id WHERE l.member_id = :m AND p.member_id <> :m2');
    $st->execute(['m' => $mid, 'm2' => $mid]);
    if ((int) $st->fetchColumn() > 0) {
        fwrite(STDERR, "Another member's founding payment is allocated to one of his shares; refusing to delete the shares.\n");
        exit(1);
    }
}

$money = ['in' => 0.0, 'out' => 0.0];
if ($has('transactions')) {
    $st = $pdo->prepare('SELECT category, COUNT(*) n, SUM(amount) s FROM transactions WHERE member_id = :m GROUP BY category');
    $st->execute(['m' => $mid]);
    $byCat = $st->fetchAll();
    foreach ($byCat as $row) {
        $money[$row['category'] === 'loan_disbursement' ? 'out' : 'in'] += (float) $row['s'];
    }
}
$openLoans = $has('loans') ? (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE member_id = $mid AND status IN ('active','partial')")->fetchColumn() : 0;

echo "Database: {$db['database']} @ {$db['host']}\n";
echo sprintf("Member:   id:%d  #%s  %s  (national ID %s)\n", $mid, $member['serial_no'] ?? '-', $member['name'], $member['national_id']);
echo 'Mode:     ' . ($deleteAccount ? 'DELETE EVERYTHING INCLUDING THE ACCOUNT' : ($withShares ? 'delete activity + shares (account kept)' : 'delete activity only (account and shares kept)')) . "\n";
echo str_repeat('-', 58) . "\n";
foreach ($plan as $table => $p) {
    printf("%-30s %6d  %s\n", $table, $p['count'], $p['delete'] ? ($p['count'] ? '-> will be deleted' : '') : '(kept)');
}
echo str_repeat('-', 58) . "\n";
printf("Ledger effect: money in %s, money out %s  => bank balance changes by %s\n",
    number_format($money['in'], 2), number_format($money['out'], 2), number_format($money['out'] - $money['in'], 2));
if ($openLoans > 0) {
    echo "NOTE: he has $openLoans open loan(s); they disappear together with their installments.\n";
}

if (!$apply) {
    echo "Dry run only, nothing was changed. Add --yes to delete.\n";
    exit(0);
}

// ---- backup of what is about to disappear ----------------------------------------------------------------------------
if (!isset($opts['no-backup'])) {
    $dump = ['database' => $db['database'], 'member_id' => $mid, 'taken_at' => date('c'), 'mode' => $deleteAccount ? 'delete-account' : ($withShares ? 'with-shares' : 'activity'), 'tables' => []];
    foreach ($plan as $table => $p) {
        if ($p['delete'] && $p['count'] > 0) {
            $st = $pdo->prepare("SELECT * FROM `$table` WHERE {$p['where']}");
            $st->execute(['m' => $mid]);
            $dump['tables'][$table] = $st->fetchAll();
        }
    }
    $dir = __DIR__ . '/purge_backups';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        fwrite(STDERR, "Cannot create $dir; nothing was changed. (Use --no-backup to skip the backup.)\n");
        exit(1);
    }
    $file = $dir . '/member_' . $mid . '_' . date('Ymd_His') . '.json';
    if (file_put_contents($file, json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
        fwrite(STDERR, "Cannot write the backup file; nothing was changed.\n");
        exit(1);
    }
    echo "Backup of the rows saved: $file\n";
}

// ---- delete (single transaction) -------------------------------------------------------------------------------------
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    $pdo->beginTransaction();
    foreach ($plan as $table => $p) {
        if ($p['delete'] && $p['count'] > 0) {
            $st = $pdo->prepare("DELETE FROM `$table` WHERE {$p['where']}");
            $st->execute(['m' => $mid]);
        }
    }
    if ($withShares && !$deleteAccount) {
        $pdo->exec("UPDATE members SET shares_count = 0, subscription_due_day = NULL WHERE id = $mid");
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, 'Failed, nothing was changed: ' . $e->getMessage() . "\n");
    exit(1);
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Done.\n";
echo "Next: php database/audit_totals.php   (confirms every total still agrees with its rows)\n";
