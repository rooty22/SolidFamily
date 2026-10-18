<?php

/**
 * Log of shares deducted by merges and given back by splits, for the admin dashboard.
 *
 *   php database/migrate_share_merge_log.php
 *
 * Creates share_merge_log. Merges that are still in effect and whose original lots are on record
 * (share_lots.merged_into_id, see migrate_split_lots.php) are back-filled at today's share value. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$exists = $pdo->query("SHOW TABLES LIKE 'share_merge_log'")->fetchColumn();
if ($exists) {
    echo "- share_merge_log already exists, skipped.\n";
} else {
    $pdo->exec("CREATE TABLE share_merge_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        member_id INT UNSIGNED NOT NULL,
        lot_id INT UNSIGNED NULL COMMENT 'the merged lot that was created (merge) or retired (split)',
        action ENUM('merge','split') NOT NULL,
        shares INT UNSIGNED NOT NULL COMMENT 'shares deducted by the merge / restored by the split',
        share_value DECIMAL(12,2) NOT NULL DEFAULT 0,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'shares x share_value',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_merge_log_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "- share_merge_log created.\n";

    $value = (float) ($pdo->query("SELECT value FROM settings WHERE `key` = 'share_value' LIMIT 1")->fetchColumn() ?: 0);
    $merges = $pdo->query(
        "SELECT l.id, l.member_id, l.created_at, l.shares_count,
                (SELECT COALESCE(SUM(c.shares_count), 0) FROM share_lots c WHERE c.merged_into_id = l.id AND c.status = 'merged') AS original_shares
           FROM share_lots l
          WHERE l.status = 'active'
            AND (SELECT COUNT(*) FROM share_lots c WHERE c.merged_into_id = l.id AND c.status = 'merged') >= 2"
    )->fetchAll(PDO::FETCH_ASSOC);
    $insert = $pdo->prepare("INSERT INTO share_merge_log (member_id, lot_id, action, shares, share_value, amount, created_at) VALUES (?, ?, 'merge', ?, ?, ?, ?)");
    $filled = 0;
    foreach ($merges as $m) {
        $shares = (int) $m['original_shares'] - (int) $m['shares_count'];
        if ($shares > 0) {
            $insert->execute([$m['member_id'], $m['id'], $shares, $value, round($shares * $value, 2), $m['created_at']]);
            $filled++;
        }
    }
    echo "- {$filled} existing merge(s) back-filled.\n";
}

echo "Share-merge-log migration finished successfully.\n";
