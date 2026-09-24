<?php

// Idempotent, additive migration: the payment plan a member chooses for the founding amount
// (one payment, or split over N monthly installments).   php database/migrate_founding_plan.php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for founding-plan migration...\n";

$cols = $pdo->query('SHOW COLUMNS FROM founding_amounts')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('plan_months', $cols, true)) {
    $pdo->exec("ALTER TABLE founding_amounts ADD COLUMN plan_months TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER status");
    echo "- founding_amounts.plan_months added.\n";
}
if (!in_array('plan_start', $cols, true)) {
    $pdo->exec("ALTER TABLE founding_amounts ADD COLUMN plan_start DATE NULL AFTER plan_months");
    echo "- founding_amounts.plan_start added.\n";
}

echo "Founding-plan migration finished successfully.\n";
