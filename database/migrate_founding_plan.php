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
if (!in_array('plan_schedule', $cols, true)) {
    $pdo->exec("ALTER TABLE founding_amounts ADD COLUMN plan_schedule TEXT NULL AFTER plan_start");
    echo "- founding_amounts.plan_schedule added.\n";
}

// Freeze the installments of plans chosen before this column existed: an even split of the current total, which is
// what those members currently see (later changes in shares then only move the unpaid part).
$rows = $pdo->query("SELECT id, total_required, plan_months FROM founding_amounts WHERE plan_start IS NOT NULL AND plan_schedule IS NULL")->fetchAll(PDO::FETCH_ASSOC);
$freeze = $pdo->prepare('UPDATE founding_amounts SET plan_schedule = ? WHERE id = ?');
foreach ($rows as $r) {
    $months = max(1, (int) $r['plan_months']);
    $cents = (int) round((float) $r['total_required'] * 100);
    $base = intdiv($cents, $months);
    $parts = array_fill(0, $months, $base);
    $parts[$months - 1] += $cents - $base * $months;
    $freeze->execute([json_encode(array_map(fn($c) => $c / 100, $parts)), $r['id']]);
}
echo '- ' . count($rows) . " plan(s) frozen.\n";

echo "Founding-plan migration finished successfully.\n";
