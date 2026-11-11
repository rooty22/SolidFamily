<?php

/**
 * Wipes every ACTION / DATA record of every member and keeps only the members themselves: each member keeps his account
 * (name, serial number, User ID, mobile, e-mail, national ID, bank details, password, status) but starts from zero, as if
 * he had just registered and never done anything in the system.
 *
 *   php database/reset_data.php                              dry run: shows what would be deleted, changes nothing
 *   php database/reset_data.php --yes --confirm=<db name>    does it (the database name must be typed to confirm)
 *   php database/reset_data.php --restore=<backup.json> --yes --confirm=<db name>    puts a wiped state back
 *
 * Deleted: monthly subscriptions, shares (share lots) and their requests / merge log, founding amounts + payments +
 * allocations, loans + installments + loan requests, every ledger transaction, notifications, contact messages and the
 * administrative / bank expenses log.
 * Reset on every member: shares_count = 0 and the personal subscription due day.
 * Kept: members (and their serial / User ID), admins and sub-admins, settings, content pages, translations, OTP and
 * rate-limit tables.
 *
 * Options:
 *   --yes                 really do it (without it nothing is changed)
 *   --confirm=NAME        required with --yes: the name of the database being wiped, typed again on purpose
 *   --no-backup           skip the JSON backup of the deleted rows (default: saved in database/purge_backups/)
 *   --keep-ids            do not restart the auto-numbering of the emptied tables (by default the next loan, share, ...
 *                         starts again from 1)
 *   --restore=FILE        put the rows of a backup file back (only into EMPTY tables, only for members that still exist)
 *   --database=NAME       use another database than config/config.php
 *
 * The wipe runs in ONE transaction: if anything fails nothing is changed. For a belt-and-braces copy also take a normal
 * database dump first (the dry run prints the command).
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
$restoreFile = isset($opts['restore']) && is_string($opts['restore']) ? $opts['restore'] : null;

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Child tables first (the order matters for the restore, which runs the same list backwards). A table that does not
// exist yet (migration not run) is skipped.
$wipe = [
    'founding_payment_allocations', 'founding_payments', 'founding_amounts',
    'loan_installments', 'loans', 'loan_requests',
    'monthly_subscriptions',
    'share_merge_log', 'share_requests', 'share_lots',
    'transactions', 'notifications', 'contact_messages', 'expenses',
];

$existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$count = fn(string $t): int => (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();

function confirm_or_exit(array $opts, string $dbName): void
{
    if (($opts['confirm'] ?? null) !== $dbName) {
        fwrite(STDERR, "Refused: add --confirm={$dbName} (the exact database name) to really do this.\n");
        exit(2);
    }
}

// ---- restore ---------------------------------------------------------------------------------------------------------
if ($restoreFile !== null) {
    if (!is_file($restoreFile)) {
        fwrite(STDERR, "Backup file not found: $restoreFile\n");
        exit(1);
    }
    $data = json_decode((string) file_get_contents($restoreFile), true);
    if (!is_array($data) || empty($data['tables'])) {
        fwrite(STDERR, "Not a backup written by this script.\n");
        exit(1);
    }
    echo "Database: {$db['database']}   restoring from " . basename($restoreFile) . " (taken {$data['taken_at']} from {$data['database']})\n";
    $problems = [];
    foreach ($data['tables'] as $table => $rows) {
        if (!in_array($table, $existing, true)) {
            $problems[] = "table $table does not exist here";
        } elseif ($rows && $count($table) > 0) {
            $problems[] = "table $table is not empty (" . $count($table) . ' rows): a restore only fills empty tables';
        }
    }
    $memberIds = array_flip(array_map('intval', $pdo->query('SELECT id FROM members')->fetchAll(PDO::FETCH_COLUMN)));
    foreach (($data['members'] ?? []) as $mrow) {
        if (!isset($memberIds[(int) $mrow['id']])) {
            $problems[] = 'member id ' . $mrow['id'] . ' no longer exists';
        }
    }
    foreach ($data['tables'] as $table => $rows) {
        foreach ($rows as $row) {
            foreach (['member_id', 'target_member_id'] as $col) {
                if (isset($row[$col]) && $row[$col] !== null && !isset($memberIds[(int) $row[$col]])) {
                    $problems[] = "$table row " . ($row['id'] ?? '?') . " belongs to member {$row[$col]} who no longer exists";
                    break 2;
                }
            }
        }
    }
    foreach (array_slice($problems, 0, 10) as $p) {
        echo "  [PROBLEM] $p\n";
    }
    if ($problems) {
        fwrite(STDERR, count($problems) . " problem(s): nothing restored.\n");
        exit(1);
    }
    foreach (array_reverse($wipe) as $table) {
        printf("%-30s %6d row(s) will be put back\n", $table, count($data['tables'][$table] ?? []));
    }
    if (!$apply) {
        echo "Dry run only, nothing was changed. Add --yes --confirm={$db['database']} to restore.\n";
        exit(0);
    }
    confirm_or_exit($opts, $db['database']);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        $pdo->beginTransaction();
        foreach (array_reverse($wipe) as $table) {
            foreach ($data['tables'][$table] ?? [] as $row) {
                $cols = array_keys($row);
                $st = $pdo->prepare('INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
                $st->execute(array_values($row));
            }
        }
        $upd = $pdo->prepare('UPDATE members SET shares_count = ?, subscription_due_day = ? WHERE id = ?');
        foreach (($data['members'] ?? []) as $mrow) {
            $upd->execute([$mrow['shares_count'], $mrow['subscription_due_day'], $mrow['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        fwrite(STDERR, 'Restore failed, nothing was changed: ' . $e->getMessage() . "\n");
        exit(1);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Restored. Run: php database/audit_totals.php   to confirm every total still agrees.\n";
    exit(0);
}

// ---- wipe ------------------------------------------------------------------------------------------------------------
echo "Database: {$db['database']} @ {$db['host']}\n";
echo str_repeat('-', 58) . "\n";
$members = $count('members');
$withShares = (int) $pdo->query('SELECT COUNT(*) FROM members WHERE shares_count > 0 OR subscription_due_day IS NOT NULL')->fetchColumn();
printf("%-30s %6d  KEPT (%d of them will be reset to 0 shares)\n", 'members', $members, $withShares);
$total = 0;
foreach ($wipe as $table) {
    if (!in_array($table, $existing, true)) {
        printf("%-30s %6s\n", $table, 'n/a');
        continue;
    }
    $c = $count($table);
    $total += $c;
    printf("%-30s %6d  %s\n", $table, $c, $c ? '-> will be deleted' : '');
}
echo str_repeat('-', 58) . "\n";
if (in_array('transactions', $existing, true)) {
    $in = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE category <> 'loan_disbursement'")->fetchColumn();
    $out = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE category = 'loan_disbursement'")->fetchColumn();
    printf("Ledger today: money in %s, money out %s  =>  bank balance %s becomes 0.00\n", number_format($in, 2), number_format($out, 2), number_format($in - $out, 2));
}
echo "Take a normal dump first, e.g.:  mysqldump -u {$db['username']} -p {$db['database']} > before_reset.sql\n";

if (!$apply) {
    echo "Dry run only, nothing was changed. Add --yes --confirm={$db['database']} to apply.\n";
    exit(0);
}
confirm_or_exit($opts, $db['database']);

// backup of the rows that are about to disappear
if (!isset($opts['no-backup'])) {
    $dump = ['database' => $db['database'], 'taken_at' => date('c'), 'members' => [], 'tables' => []];
    foreach ($pdo->query('SELECT id, shares_count, subscription_due_day FROM members')->fetchAll() as $m) {
        $dump['members'][] = $m;
    }
    foreach ($wipe as $table) {
        if (in_array($table, $existing, true)) {
            $dump['tables'][$table] = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        }
    }
    $dir = __DIR__ . '/purge_backups';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        fwrite(STDERR, "Cannot create $dir; nothing was changed. (Use --no-backup to skip the backup.)\n");
        exit(1);
    }
    $file = $dir . '/reset_' . preg_replace('/[^A-Za-z0-9_]/', '', $db['database']) . '_' . date('Ymd_His') . '.json';
    try {
        $json = json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Cannot encode the backup (' . $e->getMessage() . "); nothing was changed.\n");
        exit(1);
    }
    if (file_put_contents($file, $json) !== strlen($json)) {
        fwrite(STDERR, "Cannot write the backup file completely; nothing was changed.\n");
        exit(1);
    }
    // read it back and compare the row counts with the live tables BEFORE anything is deleted
    $check = json_decode((string) file_get_contents($file), true);
    foreach ($dump['tables'] as $table => $rows) {
        if (!is_array($check) || count($check['tables'][$table] ?? []) !== count($rows) || count($rows) !== $count($table)) {
            @unlink($file);
            fwrite(STDERR, "The backup file does not match table $table; nothing was changed.\n");
            exit(1);
        }
    }
    echo "Backup of the rows saved and verified: $file\n";
    echo "  (undo later with: php database/reset_data.php --restore=\"$file\" --yes --confirm={$db['database']})\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    $pdo->beginTransaction();
    foreach ($wipe as $table) {
        if (in_array($table, $existing, true)) {
            $pdo->exec("DELETE FROM `$table`");
        }
    }
    $pdo->exec('UPDATE members SET shares_count = 0, subscription_due_day = NULL');
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, 'Failed, nothing was changed: ' . $e->getMessage() . "\n");
    exit(1);
}
if (!isset($opts['keep-ids'])) {
    // The tables are empty now, so numbering can restart (loan #1, share #1 ...). DDL cannot be part of the transaction.
    try {
        foreach ($wipe as $table) {
            if (in_array($table, $existing, true)) {
                $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
            }
        }
    } catch (Throwable $e) {
        echo 'Note: the data IS cleared, but the numbering could not be restarted (' . $e->getMessage() . "). New records simply continue the old numbers.\n";
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Done. {$members} member(s) kept with their accounts; {$total} data row(s) cleared.\n";
echo "Next: php database/audit_totals.php   (everything should read 0)\n";
