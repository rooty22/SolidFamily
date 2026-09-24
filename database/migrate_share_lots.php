<?php

// Idempotent, additive migration for: share lots (separate share purchases with their own
// subscription due date, merged into one only when a "merge" share request is approved).
// Safe to run on an existing database:  php database/migrate_share_lots.php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for share-lots migration...\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// ---- 1. share_lots table ----
if (!in_array('share_lots', $tables, true)) {
    $pdo->exec("CREATE TABLE share_lots (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        member_id INT UNSIGNED NOT NULL,
        shares_count INT UNSIGNED NOT NULL,
        subscription_due_day TINYINT UNSIGNED NULL COMMENT 'NULL falls back to the site default',
        status ENUM('active','merged') NOT NULL DEFAULT 'active',
        source_request_id INT UNSIGNED NULL COMMENT 'the share_request that created/merged this lot, for traceability',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_lots_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "- share_lots table created.\n";
} else {
    echo "- share_lots table already exists, skipped.\n";
}

// ---- 2. Backfill one lot per member who already has shares, so existing members keep billing seamlessly ----
$backfillCount = (int) $pdo->query("SELECT COUNT(*) FROM share_lots")->fetchColumn();
if ($backfillCount === 0) {
    // created_at = when the member joined, not now: a lot only bills from its creation month onwards, so
    // stamping "today" would block recording payments for the member's genuine earlier months.
    $members = $pdo->query("SELECT id, shares_count, subscription_due_day, created_at FROM members WHERE shares_count > 0")->fetchAll();
    $insert = $pdo->prepare("INSERT INTO share_lots (member_id, shares_count, subscription_due_day, status, created_at) VALUES (?, ?, ?, 'active', ?)");
    foreach ($members as $m) {
        $insert->execute([$m['id'], $m['shares_count'], $m['subscription_due_day'], $m['created_at']]);
    }
    echo '- backfilled ' . count($members) . " lot(s) for existing members with shares.\n";
} else {
    echo "- share_lots already has rows, skipping backfill.\n";
}

// ---- 3. monthly_subscriptions.lot_id ----
$cols = $pdo->query("SHOW COLUMNS FROM monthly_subscriptions")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('lot_id', $cols, true)) {
    $pdo->exec("ALTER TABLE monthly_subscriptions ADD COLUMN lot_id INT UNSIGNED NULL AFTER member_id");
    echo "- monthly_subscriptions.lot_id added.\n";

    // Point every existing row at its member's (just-backfilled) lot, if any.
    $pdo->exec("UPDATE monthly_subscriptions ms
        JOIN share_lots sl ON sl.member_id = ms.member_id AND sl.status = 'active'
        SET ms.lot_id = sl.id
        WHERE ms.lot_id IS NULL");
    echo "- existing monthly_subscriptions rows linked to their member's lot.\n";

    $pdo->exec("ALTER TABLE monthly_subscriptions ADD CONSTRAINT fk_subscriptions_lot
        FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE CASCADE");
    echo "- fk_subscriptions_lot added.\n";
} else {
    echo "- monthly_subscriptions.lot_id already exists, skipped.\n";
}

// ---- 4. Replace the old (member_id, month) unique key with (member_id, month, lot_id) ----
// The new key must be added BEFORE dropping the old one: fk_subscriptions_member needs some
// index with member_id as its leftmost column at all times, or MySQL refuses to drop it.
$hasOldKey = count($pdo->query("SHOW INDEX FROM monthly_subscriptions WHERE Key_name = 'uniq_member_month'")->fetchAll()) > 0;
$hasNewKey = count($pdo->query("SHOW INDEX FROM monthly_subscriptions WHERE Key_name = 'uniq_member_month_lot'")->fetchAll()) > 0;
if (!$hasNewKey) {
    $pdo->exec("ALTER TABLE monthly_subscriptions ADD UNIQUE KEY uniq_member_month_lot (member_id, month, lot_id)");
    echo "- uniq_member_month_lot index added.\n";
}
if ($hasOldKey) {
    $pdo->exec("ALTER TABLE monthly_subscriptions DROP INDEX uniq_member_month");
    echo "- old uniq_member_month index dropped.\n";
}

echo "Share-lots migration finished successfully.\n";
