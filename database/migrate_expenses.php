<?php

/**
 * Administrative and bank expenses log (main admin only).
 *
 *   php database/migrate_expenses.php
 *
 * Creates the `expenses` table: one row per expense (name, cost, date, and optionally the member it belongs to and free
 * notes). If the table already exists from an earlier version it only adds what is missing: the optional `member_id`
 * column (a member who is later deleted leaves the expense in the log, as a general one) and the optional `notes`
 * column. It is only a record: it does not touch the ledger (transactions) or
 * any other total. The total of the log is shown on the admin dashboard, to the main admin only.
 * Idempotent: running it again changes nothing.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$exists = (bool) $pdo->query("SHOW TABLES LIKE 'expenses'")->fetchColumn();
if (!$exists) {
    $pdo->exec("CREATE TABLE expenses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        expense_date DATE NOT NULL,
        member_id INT UNSIGNED NULL COMMENT 'optional: the member this expense belongs to',
        notes TEXT NULL COMMENT 'optional: free notes on this expense',
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_expenses_date (expense_date),
        KEY idx_expenses_member (member_id),
        CONSTRAINT fk_expenses_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
        CONSTRAINT fk_expenses_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "- expenses created.\n";
} else {
    echo "- expenses already exists, skipped.\n";
    $cols = $pdo->query('SHOW COLUMNS FROM expenses')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('member_id', $cols, true)) {
        $pdo->exec("ALTER TABLE expenses
            ADD COLUMN member_id INT UNSIGNED NULL COMMENT 'optional: the member this expense belongs to' AFTER expense_date,
            ADD KEY idx_expenses_member (member_id),
            ADD CONSTRAINT fk_expenses_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL");
        echo "- expenses.member_id added.\n";
    } else {
        echo "- expenses.member_id already exists, skipped.\n";
    }
    if (!in_array('notes', $cols, true)) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN notes TEXT NULL COMMENT 'optional: free notes on this expense' AFTER member_id");
        echo "- expenses.notes added.\n";
    } else {
        echo "- expenses.notes already exists, skipped.\n";
    }
}

echo "Expenses migration finished successfully.\n";
