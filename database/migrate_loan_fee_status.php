<?php

/**
 * Manual management of the loan commission (administrative fee).
 *
 *   php database/migrate_loan_fee_status.php
 *
 * The commission is no longer recorded as received the moment a loan is created: the admin confirms the payment by
 * hand (paid / unpaid + payment date). Adds loans.fee_status, loans.fee_paid_at and loans.fee_confirmed_by.
 *
 * Loans created before this change: the old system had already booked the commission in the ledger
 * (transactions.category = 'loan_admin_fee'). To leave the books exactly as they are, a loan with such an entry
 * starts as "paid" on the date of that entry; every other loan starts as "unpaid". The admin can correct any loan from
 * its page (switching it to unpaid removes the ledger entry). Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('SHOW COLUMNS FROM loans')->fetchAll(PDO::FETCH_COLUMN);
$fresh = !in_array('fee_status', $cols, true);
if ($fresh) {
    $pdo->exec("ALTER TABLE loans
        ADD COLUMN fee_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'the commission is paid only once an admin confirms it' AFTER admin_fee_amount,
        ADD COLUMN fee_paid_at DATE NULL COMMENT 'date the commission was received' AFTER fee_status,
        ADD COLUMN fee_confirmed_by INT UNSIGNED NULL COMMENT 'admin who last confirmed / reverted the commission payment' AFTER fee_paid_at,
        ADD CONSTRAINT fk_loans_fee_admin FOREIGN KEY (fee_confirmed_by) REFERENCES admins(id) ON DELETE SET NULL");
    echo "- loans.fee_status, fee_paid_at, fee_confirmed_by added.\n";

    // Only on the first run: afterwards the admin owns this status and a re-run must never overwrite it.
    $n = $pdo->exec(
        "UPDATE loans l
            SET l.fee_status = 'paid',
                l.fee_paid_at = (SELECT MAX(t.transaction_date) FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id)
          WHERE l.admin_fee_amount > 0
            AND EXISTS (SELECT 1 FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id)"
    );
    echo "- {$n} existing loan(s) with a commission already booked in the ledger start as paid; review them from the loans list (filter: commission).\n";
} else {
    echo "- loans.fee_status already exists, skipped.\n";
}

echo "Loan-fee migration finished successfully.\n";
