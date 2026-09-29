<?php

// Idempotent migration: adds lot_id to loan_requests and loans so members with multiple unmerged
// share lots can select which specific lot to apply their loan against.

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for loan lot_id migration...\n";

// 1. loan_requests.lot_id
$reqCols = $pdo->query("SHOW COLUMNS FROM loan_requests")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('lot_id', $reqCols, true)) {
    $pdo->exec("ALTER TABLE loan_requests ADD COLUMN lot_id INT UNSIGNED NULL AFTER member_id");
    try {
        $pdo->exec("ALTER TABLE loan_requests ADD CONSTRAINT fk_loan_requests_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE SET NULL");
    } catch (\Throwable $e) {
        // Index/FK might already exist
    }
    echo "- Added lot_id column to loan_requests.\n";
} else {
    echo "- loan_requests already has lot_id, skipped.\n";
}

// 2. loans.lot_id
$loanCols = $pdo->query("SHOW COLUMNS FROM loans")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('lot_id', $loanCols, true)) {
    $pdo->exec("ALTER TABLE loans ADD COLUMN lot_id INT UNSIGNED NULL AFTER member_id");
    try {
        $pdo->exec("ALTER TABLE loans ADD CONSTRAINT fk_loans_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE SET NULL");
    } catch (\Throwable $e) {
        // Index/FK might already exist
    }
    echo "- Added lot_id column to loans.\n";
} else {
    echo "- loans already has lot_id, skipped.\n";
}

echo "Migration finished successfully.\n";
