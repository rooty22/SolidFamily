<?php

/**
 * Read-only audit of every total the system shows (dashboard, loans, founding amounts, subscriptions, ledger).
 *
 *   php database/audit_totals.php
 *
 * It changes NOTHING: each figure that is stored (loan paid / remaining, founding paid, commission status, ledger
 * entries ...) is recomputed from the rows it is made of and compared. Exit code 1 when an ERROR is found.
 *   ERROR = the stored figure and the rows behind it disagree (a total on screen would be wrong)
 *   WARN  = something that looks stale or unusual but is repaired by itself / is a business decision
 *   INFO  = figures worth knowing, no action needed
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--database=')) {
        $db['database'] = substr($arg, strlen('--database='));
    }
}

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$errors = 0;
$warns = 0;
function q(string $sql, array $p = []): array
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchAll();
}
function one(string $sql, array $p = [])
{
    $r = q($sql, $p);
    return $r ? array_values($r[0])[0] : null;
}
function report(string $level, string $what, array $rows = [], ?callable $fmt = null): void
{
    global $errors, $warns;
    if (!$rows) {
        return;
    }
    $level === 'ERROR' ? $errors += count($rows) : $warns += count($rows);
    echo "  [{$level}] {$what} (" . count($rows) . ")\n";
    foreach (array_slice($rows, 0, 8) as $r) {
        echo '      - ' . ($fmt ? $fmt($r) : json_encode($r, JSON_UNESCAPED_UNICODE)) . "\n";
    }
    if (count($rows) > 8) {
        echo '      ... +' . (count($rows) - 8) . " more\n";
    }
}
function section(string $t): void
{
    echo "\n== {$t}\n";
}
$hasCol = fn(string $t, string $c): bool => (bool) q("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
$hasTable = fn(string $t): bool => (bool) q("SHOW TABLES LIKE '{$t}'");

echo "Audit of database `{$db['database']}`\n";

// ----------------------------------------------------------------------------------------------------------------
section('Loans: installments, paid, remaining, status');
report('ERROR', 'loan.amount_paid differs from the sum of its installments',
    q('SELECT l.id, l.amount_paid, COALESCE(SUM(i.amount_paid),0) AS installments FROM loans l LEFT JOIN loan_installments i ON i.loan_id = l.id GROUP BY l.id, l.amount_paid HAVING ABS(l.amount_paid - installments) > 0.009'),
    fn($r) => "loan #{$r['id']}: stored paid {$r['amount_paid']} vs installments {$r['installments']}");
report('ERROR', 'loan.amount_remaining is not amount - paid',
    q('SELECT id, amount, amount_paid, amount_remaining FROM loans WHERE ABS(amount_remaining - GREATEST(amount - amount_paid, 0)) > 0.009'),
    fn($r) => "loan #{$r['id']}: amount {$r['amount']} paid {$r['amount_paid']} remaining {$r['amount_remaining']}");
report('ERROR', 'installments do not add up to the loan amount',
    q('SELECT l.id, l.amount, COALESCE(SUM(i.amount),0) AS inst, COUNT(i.id) AS n, l.installments_count FROM loans l LEFT JOIN loan_installments i ON i.loan_id = l.id GROUP BY l.id, l.amount, l.installments_count HAVING ABS(l.amount - inst) > 0.009 OR n <> l.installments_count'),
    fn($r) => "loan #{$r['id']}: amount {$r['amount']} vs installments {$r['inst']} ({$r['n']}/{$r['installments_count']})");
report('ERROR', 'loan status does not match what was paid',
    q("SELECT id, status, amount, amount_paid FROM loans WHERE
        (status = 'active' AND amount_paid > 0.009)
        OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount - 0.009))
        OR (status = 'paid' AND amount_paid < amount - 0.009)
        OR (status = 'closed' AND amount_paid < amount - 0.009)"),
    fn($r) => "loan #{$r['id']}: status {$r['status']}, paid {$r['amount_paid']} of {$r['amount']}");
report('ERROR', 'installment status does not match its paid amount',
    q("SELECT id, loan_id, amount, amount_paid, status FROM loan_installments WHERE
        (status = 'unpaid' AND amount_paid > 0.009) OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount - 0.009)) OR (status = 'paid' AND amount_paid < amount - 0.009)"));

section('Loans: the ledger (transactions) agrees with the loans');
report('ERROR', 'repayments in the ledger differ from what the loan says was repaid',
    q("SELECT l.id, l.amount_paid, COALESCE((SELECT SUM(t.amount) FROM transactions t JOIN loan_installments i ON i.id = t.related_id WHERE t.category = 'loan_installment' AND i.loan_id = l.id), 0) AS ledger FROM loans l HAVING ABS(l.amount_paid - ledger) > 0.009"),
    fn($r) => "loan #{$r['id']}: loan paid {$r['amount_paid']} vs ledger {$r['ledger']}");
report('ERROR', 'a loan without exactly one disbursement entry of its amount',
    q("SELECT l.id, l.amount, (SELECT COUNT(*) FROM transactions t WHERE t.category = 'loan_disbursement' AND t.related_id = l.id) AS n, (SELECT COALESCE(SUM(t.amount),0) FROM transactions t WHERE t.category = 'loan_disbursement' AND t.related_id = l.id) AS s FROM loans l HAVING n <> 1 OR ABS(s - l.amount) > 0.009"),
    fn($r) => "loan #{$r['id']}: amount {$r['amount']}, disbursement entries {$r['n']} totalling {$r['s']}");
report('ERROR', 'ledger rows pointing to a loan / installment / payment that no longer exists',
    q("SELECT t.id, t.category, t.related_id, t.amount FROM transactions t WHERE
        (t.category = 'loan_disbursement' AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.id = t.related_id))
        OR (t.category = 'loan_admin_fee' AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.id = t.related_id))
        OR (t.category = 'loan_installment' AND NOT EXISTS (SELECT 1 FROM loan_installments i WHERE i.id = t.related_id))
        OR (t.category = 'founding' AND NOT EXISTS (SELECT 1 FROM founding_payments p WHERE p.id = t.related_id))"));

if ($hasCol('loans', 'fee_status')) {
    section('Loan commission (administrative fee)');
    report('ERROR', 'commission amount is not amount x percent',
        q('SELECT id, amount, admin_fee_percent, admin_fee_amount FROM loans WHERE ABS(admin_fee_amount - ROUND(amount * admin_fee_percent / 100, 2)) > 0.009'));
    report('ERROR', 'commission marked PAID without exactly one ledger entry of its amount and date',
        q("SELECT l.id, l.admin_fee_amount, l.fee_paid_at, (SELECT COUNT(*) FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id) AS n, (SELECT COALESCE(SUM(t.amount),0) FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id) AS s, (SELECT MAX(t.transaction_date) FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id) AS d FROM loans l WHERE l.fee_status = 'paid' HAVING n <> 1 OR ABS(s - l.admin_fee_amount) > 0.009 OR d <> l.fee_paid_at OR l.fee_paid_at IS NULL"),
        fn($r) => "loan #{$r['id']}: fee {$r['admin_fee_amount']} paid_at {$r['fee_paid_at']}; ledger entries {$r['n']} totalling {$r['s']} dated {$r['d']}");
    report('ERROR', 'commission marked UNPAID but a ledger entry counts it as received',
        q("SELECT l.id, l.admin_fee_amount FROM loans l WHERE l.fee_status = 'unpaid' AND EXISTS (SELECT 1 FROM transactions t WHERE t.category = 'loan_admin_fee' AND t.related_id = l.id)"));
    report('WARN', 'commission payment dated before the loan date',
        q("SELECT id, loan_date, fee_paid_at FROM loans WHERE fee_status = 'paid' AND fee_paid_at < loan_date"));
    $f = q("SELECT COALESCE(SUM(CASE WHEN fee_status='paid' THEN admin_fee_amount END),0) AS paid, COALESCE(SUM(CASE WHEN fee_status<>'paid' THEN admin_fee_amount END),0) AS due FROM loans")[0];
    echo "  [INFO] commissions: received {$f['paid']}  |  still due {$f['due']}  |  total " . ($f['paid'] + $f['due']) . "\n";
}

// ----------------------------------------------------------------------------------------------------------------
section('Founding amounts');
report('ERROR', 'founding.amount_paid differs from the sum of its payments',
    q('SELECT f.member_id, f.amount_paid, COALESCE(SUM(p.amount),0) AS payments FROM founding_amounts f LEFT JOIN founding_payments p ON p.founding_amount_id = f.id GROUP BY f.id, f.member_id, f.amount_paid HAVING ABS(f.amount_paid - payments) > 0.009'),
    fn($r) => "member #{$r['member_id']}: stored paid {$r['amount_paid']} vs payments {$r['payments']}");
report('ERROR', 'founding payments in the ledger differ from the payments',
    q("SELECT p.member_id, SUM(p.amount) AS payments, COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.member_id = p.member_id AND t.category = 'founding'),0) AS ledger FROM founding_payments p GROUP BY p.member_id HAVING ABS(payments - ledger) > 0.009"),
    fn($r) => "member #{$r['member_id']}: payments {$r['payments']} vs ledger {$r['ledger']}");
report('ERROR', 'founding status does not match paid / required',
    q("SELECT member_id, total_required, amount_paid, status FROM founding_amounts WHERE
        (status = 'paid' AND amount_paid < total_required - 0.009 AND total_required > 0)
        OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= total_required - 0.009))
        OR (status = 'unpaid' AND (amount_paid > 0.009 OR total_required <= 0))"));
report('WARN', 'founding row not in step with the member\'s shares (it is recomputed when the member or the founding page is opened)',
    q('SELECT m.id AS member_id, m.shares_count, f.shares_count_linked FROM founding_amounts f JOIN members m ON m.id = f.member_id WHERE f.shares_count_linked <> m.shares_count'));
report('ERROR', 'the active shares of a member do not add up to members.shares_count',
    q("SELECT m.id AS member_id, m.shares_count, COALESCE(SUM(l.shares_count),0) AS in_lots FROM members m LEFT JOIN share_lots l ON l.member_id = m.id AND l.status = 'active' WHERE m.status NOT IN ('pending','rejected') GROUP BY m.id, m.shares_count HAVING m.shares_count <> in_lots"));

if ($hasTable('founding_payment_allocations')) {
    section('Founding amount per share (allocations)');
    report('ERROR', 'a payment allocated to shares for more than it is worth',
        q('SELECT p.id, p.member_id, p.amount, SUM(a.amount) AS allocated FROM founding_payments p JOIN founding_payment_allocations a ON a.payment_id = p.id GROUP BY p.id, p.member_id, p.amount HAVING allocated > p.amount + 0.009'));
    report('ERROR', 'an allocation to a share of another member',
        q('SELECT a.id, a.payment_id, a.lot_id FROM founding_payment_allocations a JOIN founding_payments p ON p.id = a.payment_id JOIN share_lots l ON l.id = a.lot_id WHERE l.member_id <> p.member_id'));
    $un = q('SELECT p.member_id, ROUND(SUM(p.amount - COALESCE((SELECT SUM(a.amount) FROM founding_payment_allocations a WHERE a.payment_id = p.id),0)),2) AS unallocated FROM founding_payments p GROUP BY p.member_id HAVING unallocated > 0.009');
    report('WARN', 'founding money not assigned to a share yet (assign it from the member\'s founding page)', $un, fn($r) => "member #{$r['member_id']}: {$r['unallocated']}");
    $lotSum = q("SELECT COALESCE(SUM(a.amount),0) s FROM founding_payment_allocations a")[0]['s'];
    echo "  [INFO] allocated to shares: {$lotSum}  |  all founding payments: " . one('SELECT COALESCE(SUM(amount),0) FROM founding_payments') . "\n";
}

// ----------------------------------------------------------------------------------------------------------------
section('Monthly subscriptions');
report('ERROR', 'subscription status does not match paid / due',
    q("SELECT id, member_id, month, amount_due, amount_paid, status FROM monthly_subscriptions WHERE
        (status = 'paid' AND amount_paid < amount_due - 0.009)
        OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount_due - 0.009))
        OR (status = 'unpaid' AND amount_paid > 0.009)"),
    fn($r) => "member #{$r['member_id']} {$r['month']}: due {$r['amount_due']} paid {$r['amount_paid']} status {$r['status']}");
report('WARN', 'ledger entries dated in the FUTURE (a payment cannot be received tomorrow: correct the date from the payment\'s Edit button)',
    q("SELECT t.id, t.member_id, t.category, t.amount, t.transaction_date, t.notes FROM transactions t WHERE t.transaction_date > CURDATE() ORDER BY t.transaction_date DESC"),
    fn($r) => "ledger #{$r['id']} member #{$r['member_id']} {$r['category']} {$r['amount']} dated {$r['transaction_date']} ({$r['notes']})");
$adv = q("SELECT COUNT(*) AS n, COALESCE(SUM(amount_paid),0) AS s FROM monthly_subscriptions WHERE month > DATE_FORMAT(CURDATE(), '%Y-%m') AND amount_paid > 0")[0];
if ((int) $adv['n'] > 0) {
    echo "  [INFO] advance subscription payments for months that have not started: {$adv['n']} row(s), {$adv['s']}\n";
}
$sub = q("SELECT COALESCE(SUM(amount_paid),0) AS rows_paid FROM monthly_subscriptions")[0]['rows_paid'];
$subTx = one("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE category = 'subscription'");
echo "  [INFO] subscriptions paid per rows: {$sub}  |  per ledger: {$subTx}" . (abs($sub - $subTx) > 0.009 ? "  (differ by " . round($sub - $subTx, 2) . ": normal when rows were merged/closed/entered before the ledger existed)\n" : "\n");

// ----------------------------------------------------------------------------------------------------------------
section('Dashboard figures, recomputed');
$in = (float) one("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE category IN ('subscription','founding','loan_installment','loan_admin_fee')");
$out = (float) one("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE category = 'loan_disbursement'");
$remaining = (float) one("SELECT COALESCE(SUM(amount_remaining),0) FROM loans WHERE status IN ('active','partial')");
$due = $hasCol('loans', 'fee_status') ? (float) one("SELECT COALESCE(SUM(admin_fee_amount),0) FROM loans WHERE fee_status <> 'paid'") : 0.0;
echo "  [INFO] money received (ledger): {$in}\n  [INFO] loans paid out: {$out}\n  [INFO] bank balance = " . ($in - $out) . "\n  [INFO] loans outstanding (principal) = {$remaining}\n  [INFO] commissions still due = {$due}\n  [INFO] total fund = " . round($in - $out + $remaining + $due, 2) . "\n";

echo "\n----------------------------------------\n";
echo $errors ? "RESULT: {$errors} ERROR(S), {$warns} warning(s) - some stored figure disagrees with its rows.\n" : "RESULT: no errors, {$warns} warning(s) - every stored total agrees with the rows behind it.\n";
exit($errors ? 1 : 0);
