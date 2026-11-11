<?php

/**
 * Administrative and bank expenses log (main admin only).
 *
 *   php database/migrate_expenses.php
 *
 * Creates the `expenses` table: one row per expense (name, cost, date). It is only a record: it does not touch the ledger
 * (transactions) or any other total. The total of the log is shown on the admin dashboard, to the main admin only.
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
if ($exists) {
    echo "- expenses already exists, skipped.\n";
} else {
    $pdo->exec("CREATE TABLE expenses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        expense_date DATE NOT NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_expenses_date (expense_date),
        CONSTRAINT fk_expenses_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "- expenses created.\n";
}

echo "Expenses migration finished successfully.\n";
