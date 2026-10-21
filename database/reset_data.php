<?php

/**
 * Wipes every record except the members themselves: members keep their account (name, ID, mobile, email, password,
 * status) but start from zero, as if they had just registered and never done anything in the system.
 *
 *   php database/reset_data.php            dry run: shows what would be deleted, changes nothing
 *   php database/reset_data.php --yes      does it (cannot be undone: take a database backup first)
 *   php database/reset_data.php --yes --database=other_db   target another database than config/config.php
 *
 * Deleted: subscriptions, shares and their requests / merge log, founding amounts and payments, loans / installments /
 * loan requests, transactions, notifications, contact messages.
 * Reset on every member: shares_count = 0 and the personal subscription due day.
 * Kept: members, admins (and sub-admins), settings, content pages, translations, OTP / rate-limit tables.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$confirmed = in_array('--yes', $argv, true);
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $db['database'] = substr($arg, strlen('--database='));
    }
}

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Child tables first. A table that does not exist yet (migration not run) is skipped.
$wipe = [
    'founding_payments' => 'DELETE FROM founding_payments',
    'founding_amounts' => 'DELETE FROM founding_amounts',
    'loan_installments' => 'DELETE FROM loan_installments',
    'loans' => 'DELETE FROM loans',
    'loan_requests' => 'DELETE FROM loan_requests',
    'monthly_subscriptions' => 'DELETE FROM monthly_subscriptions',
    'share_merge_log' => 'DELETE FROM share_merge_log',
    'share_requests' => 'DELETE FROM share_requests',
    'share_lots' => 'DELETE FROM share_lots',
    'transactions' => 'DELETE FROM transactions',
    'notifications' => 'DELETE FROM notifications',
    'contact_messages' => 'DELETE FROM contact_messages',
];

$existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$count = fn(string $t): int => (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();

echo "Database: {$db['database']} @ {$db['host']}\n";
echo str_repeat('-', 46) . "\n";
$members = $count('members');
$withShares = (int) $pdo->query('SELECT COUNT(*) FROM members WHERE shares_count > 0 OR subscription_due_day IS NOT NULL')->fetchColumn();
printf("%-26s %6d  (kept, %d will be reset to zero shares)\n", 'members', $members, $withShares);
foreach ($wipe as $table => $sql) {
    if (!in_array($table, $existing, true)) {
        printf("%-26s %6s\n", $table, 'n/a');
        continue;
    }
    printf("%-26s %6d  -> will be deleted\n", $table, $count($table));
}
echo str_repeat('-', 46) . "\n";

if (!$confirmed) {
    echo "Dry run only, nothing was changed. Run again with --yes to apply (back up the database first).\n";
    exit(0);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    $pdo->beginTransaction();
    foreach ($wipe as $table => $sql) {
        if (in_array($table, $existing, true)) {
            $pdo->exec($sql);
        }
    }
    $pdo->exec('UPDATE members SET shares_count = 0, subscription_due_day = NULL');
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, "Failed, nothing was changed: " . $e->getMessage() . "\n");
    exit(1);
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Done. Members kept: {$members}. Everything else listed above was cleared.\n";
