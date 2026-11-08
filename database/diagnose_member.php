<?php

/**
 * READ-ONLY accounts check of ONE member: why do his shares / monthly subscription / dues not add up?
 *
 *   php database/diagnose_member.php --member=29           serial number (the "#29" of the lists), User ID, national ID,
 *                                                          mobile, e-mail or id:123
 *   php database/diagnose_member.php --member=29 --months=12   how many months of subscriptions to print (default 6)
 *   php database/diagnose_member.php --member=29 --database=other_db
 *
 * It never changes anything. It prints:
 *   1. the shares: every lot with its status, shares and what it should bill per month (shares x share value), and the
 *      member's expected total monthly subscription (e.g. 6 separate shares x 50 = 300);
 *   2. the monthly subscription rows per month against that expectation, with every kind of EXTRA amount:
 *      rows of lots that are merged / cancelled but still owe money, rows without a lot, duplicated rows of one lot and
 *      month, rows for months before the lot started, rows whose amount differs from shares x share value;
 *   3. previous dues (unpaid / partly paid months) and what is still owed in total;
 *   4. the founding amount against the shares, and payments against their per-share allocations;
 *   5. the ledger: paid subscription money against the transactions that record it.
 * Every finding is printed as [PROBLEM] with the numbers, so the cause can be fixed on purpose instead of guessed.
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
if (empty($opts['member']) || $opts['member'] === true) {
    fwrite(STDERR, "Usage: php database/diagnose_member.php --member=<serial|user id|national id|mobile|email|id:N> [--months=6]\n");
    exit(2);
}
$monthsToShow = max(1, (int) ($opts['months'] ?? 6));

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$has = fn(string $t): bool => in_array($t, $tables, true);
$memberCols = $pdo->query('SHOW COLUMNS FROM members')->fetchAll(PDO::FETCH_COLUMN);
$q = function (string $sql, array $p = []) use ($pdo): array {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
};
$setting = function (string $key, $default = null) use ($q) {
    $r = $q('SELECT value FROM settings WHERE `key` = ?', [$key]);
    return $r ? $r[0]['value'] : $default;
};
$money = fn($v): string => number_format((float) $v, 2);

// ---- the member -------------------------------------------------------------------------------------------------------
$key = (string) $opts['member'];
if (preg_match('/^id:(\d+)$/', $key, $m)) {
    $found = $q('SELECT * FROM members WHERE id = ?', [(int) $m[1]]);
} else {
    $conds = ['national_id = :k', 'mobile = :k', 'LOWER(email) = LOWER(:k)'];
    if (in_array('uid', $memberCols, true)) {
        $conds[] = 'uid = :k';
    }
    if (in_array('serial_no', $memberCols, true) && ctype_digit($key)) {
        $conds[] = 'serial_no = :k';
    }
    $found = $q('SELECT * FROM members WHERE ' . implode(' OR ', $conds), ['k' => $key]);
}
if (count($found) !== 1) {
    fwrite(STDERR, count($found) === 0 ? "No member matches \"$key\".\n" : "More than one member matches \"$key\"; use id:N.\n");
    foreach ($found as $f) {
        fwrite(STDERR, sprintf("  id:%d  #%s  %s\n", $f['id'], $f['serial_no'] ?? '-', $f['name']));
    }
    exit(1);
}
$member = $found[0];
$mid = (int) $member['id'];
$shareValue = (float) $setting('share_value', 0);
$problems = [];
$problem = function (string $msg) use (&$problems): void {
    $problems[] = $msg;
    echo "  [PROBLEM] $msg\n";
};

echo "Database: {$db['database']}\n";
echo sprintf("Member:   id:%d  #%s  %s  status=%s\n", $mid, $member['serial_no'] ?? '-', $member['name'], $member['status']);
echo "Share value (monthly subscription of one share): " . $money($shareValue) . "\n";

// ---- 1. shares ----------------------------------------------------------------------------------------------------------
echo "\n== 1. Shares (share_lots)\n";
$lots = $has('share_lots') ? $q('SELECT * FROM share_lots WHERE member_id = ? ORDER BY created_at, id', [$mid]) : [];
$activeShares = 0;
$activeLots = [];
$lotById = [];
foreach ($lots as $i => $l) {
    $lotById[(int) $l['id']] = $l;
    $isActive = $l['status'] === 'active';
    if ($isActive) {
        $activeShares += (int) $l['shares_count'];
        $activeLots[] = $l;
    }
    printf("  lot %-5d %-9s shares=%-3d bills %8s/month   started %s%s%s\n",
        $l['id'], $l['status'], $l['shares_count'], $isActive ? $money($l['shares_count'] * $shareValue) : '-',
        substr((string) $l['created_at'], 0, 10),
        !empty($l['merged_into_id']) ? '   merged_into=' . $l['merged_into_id'] : '',
        !empty($l['admin_note']) ? '   note: ' . $l['admin_note'] : '');
}
if (!$lots) {
    echo "  (no lots)\n";
}
$expectedMonthly = round($activeShares * $shareValue, 2);
echo sprintf("  ACTIVE: %d lot(s), %d share(s)  =>  expected monthly subscription = %s\n", count($activeLots), $activeShares, $money($expectedMonthly));
if ((int) $member['shares_count'] !== $activeShares) {
    $problem(sprintf('members.shares_count = %d but the ACTIVE lots hold %d share(s).', $member['shares_count'], $activeShares));
}
$multi = array_filter($activeLots, fn($l) => (int) $l['shares_count'] > 1);
if ($multi) {
    echo '  note: ' . count($multi) . " active lot(s) hold more than one share (they bill together as ONE share number): lots " . implode(', ', array_column($multi, 'id')) . "\n";
}

// ---- 2. subscriptions ---------------------------------------------------------------------------------------------------
echo "\n== 2. Monthly subscriptions\n";
$subs = $has('monthly_subscriptions') ? $q('SELECT * FROM monthly_subscriptions WHERE member_id = ? ORDER BY month DESC, lot_id, id', [$mid]) : [];
$byMonth = [];
foreach ($subs as $s) {
    $byMonth[$s['month']][] = $s;
}
$printed = 0;
foreach ($byMonth as $month => $rows) {
    $due = array_sum(array_map(fn($r) => (float) $r['amount_due'], $rows));
    $paid = array_sum(array_map(fn($r) => (float) $r['amount_paid'], $rows));
    if ($printed++ < $monthsToShow) {
        printf("  %s  rows=%d  due %9s  paid %9s%s\n", $month, count($rows), $money($due), $money($paid),
            abs($due - $expectedMonthly) > 0.009 ? '   (expected ' . $money($expectedMonthly) . ')' : '');
        foreach ($rows as $r) {
            $l = $lotById[(int) ($r['lot_id'] ?? 0)] ?? null;
            printf("        row %-6d lot %-5s %-9s due %8s paid %8s %s\n", $r['id'], $r['lot_id'] ?? 'NULL', $l['status'] ?? '-', $money($r['amount_due']), $money($r['amount_paid']), $r['status']);
        }
    }
}
if (!$subs) {
    echo "  (no subscription rows)\n";
}

echo "  -- checks\n";
$seen = [];
foreach ($subs as $s) {
    $lotId = $s['lot_id'] === null ? null : (int) $s['lot_id'];
    $owes = (float) $s['amount_due'] > 0 && (float) $s['amount_paid'] < (float) $s['amount_due'];
    $label = "row {$s['id']} ({$s['month']}, lot " . ($lotId ?? 'NULL') . ", due " . $money($s['amount_due']) . ", paid " . $money($s['amount_paid']) . ")";
    $dupKey = $s['month'] . '|' . ($lotId ?? 'NULL');
    if (isset($seen[$dupKey])) {
        $problem("DUPLICATE: $label repeats the lot/month of row {$seen[$dupKey]} - the month is billed twice.");
    }
    $seen[$dupKey] = $s['id'];
    if ($lotId === null && (float) $s['amount_due'] > 0) {
        $problem("NO LOT: $label bills money but belongs to no share.");
        continue;
    }
    $lot = $lotId ? ($lotById[$lotId] ?? null) : null;
    if ($lotId && !$lot) {
        $problem("ORPHAN: $label belongs to a lot that no longer exists.");
        continue;
    }
    if ($lot && $lot['status'] !== 'active' && $owes) {
        $problem("EXTRA: $label is still owed on a {$lot['status']} lot (it should have been cleared when the lot stopped being active).");
    }
    if ($lot && substr((string) $lot['created_at'], 0, 7) > $s['month'] && (float) $s['amount_due'] > 0) {
        $problem("BEFORE START: $label is for a month before the lot started (" . substr((string) $lot['created_at'], 0, 10) . ').');
    }
    if ($lot && $lot['status'] === 'active' && $s['month'] >= date('Y-m') && abs((float) $s['amount_due'] - $lot['shares_count'] * $shareValue) > 0.009 && (float) $s['amount_paid'] == 0.0) {
        $problem("WRONG AMOUNT: $label should bill " . $money($lot['shares_count'] * $shareValue) . " ({$lot['shares_count']} x " . $money($shareValue) . ').');
    }
}
$current = $byMonth[date('Y-m')] ?? [];
if ($activeLots && $current) {
    $curDue = array_sum(array_map(fn($r) => (float) $r['amount_due'], $current));
    if (abs($curDue - $expectedMonthly) > 0.009) {
        $problem(sprintf('This month (%s) is billed %s but the active shares should bill %s (difference %s).', date('Y-m'), $money($curDue), $money($expectedMonthly), $money($curDue - $expectedMonthly)));
    }
}

// ---- 3. previous dues ---------------------------------------------------------------------------------------------------
echo "\n== 3. Dues\n";
$owed = 0.0;
$owedMonths = [];
foreach ($subs as $s) {
    $left = (float) $s['amount_due'] - (float) $s['amount_paid'];
    $lot = $s['lot_id'] ? ($lotById[(int) $s['lot_id']] ?? null) : null;
    if ($left > 0.009 && (!$lot || $lot['status'] === 'active') && $s['month'] <= date('Y-m')) {
        $owed += $left;
        $owedMonths[$s['month']] = ($owedMonths[$s['month']] ?? 0) + $left;
    }
}
krsort($owedMonths);
echo '  Still owed on ACTIVE shares up to this month: ' . $money($owed) . "\n";
foreach (array_slice($owedMonths, 0, 12, true) as $mo => $v) {
    echo "    $mo  " . $money($v) . "\n";
}

// ---- 4. founding --------------------------------------------------------------------------------------------------------
echo "\n== 4. Founding amount\n";
if ($has('founding_amounts')) {
    $f = $q('SELECT * FROM founding_amounts WHERE member_id = ?', [$mid])[0] ?? null;
    if ($f) {
        $perShare = (int) $f['shares_count_linked'] > 0 ? round((float) $f['total_required'] / (int) $f['shares_count_linked'], 2) : (float) $setting('founding_fee_per_share', 0);
        echo sprintf("  required %s for %d linked share(s) (%s per share), paid %s, status %s\n", $money($f['total_required']), $f['shares_count_linked'], $money($perShare), $money($f['amount_paid']), $f['status']);
        if ((int) $f['shares_count_linked'] !== $activeShares) {
            $problem(sprintf('founding amount is linked to %d share(s) but the member has %d active share(s).', $f['shares_count_linked'], $activeShares));
        }
        $payments = $has('founding_payments') ? (float) $q('SELECT COALESCE(SUM(amount),0) s FROM founding_payments WHERE member_id = ?', [$mid])[0]['s'] : 0.0;
        if (abs($payments - (float) $f['amount_paid']) > 0.009) {
            $problem(sprintf('founding_amounts.amount_paid = %s but the payments add up to %s.', $money($f['amount_paid']), $money($payments)));
        }
        if ($has('founding_payment_allocations')) {
            $alloc = (float) $q('SELECT COALESCE(SUM(a.amount),0) s FROM founding_payment_allocations a JOIN founding_payments p ON p.id = a.payment_id WHERE p.member_id = ?', [$mid])[0]['s'];
            if (abs($payments - $alloc) > 0.009) {
                echo '  note: ' . $money($payments - $alloc) . " of founding money is not assigned to a share yet (assign it from the founding page).\n";
            }
        }
    } else {
        echo "  (no founding record)\n";
    }
}

// ---- 5. ledger ----------------------------------------------------------------------------------------------------------
echo "\n== 5. Ledger (transactions) against what the rows say\n";
if ($has('transactions') && $has('monthly_subscriptions')) {
    $paidRows = (float) $q('SELECT COALESCE(SUM(amount_paid),0) s FROM monthly_subscriptions WHERE member_id = ?', [$mid])[0]['s'];
    $tx = (float) $q("SELECT COALESCE(SUM(amount),0) s FROM transactions WHERE member_id = ? AND category = 'subscription'", [$mid])[0]['s'];
    echo sprintf("  subscriptions paid on the rows: %s   subscription transactions: %s\n", $money($paidRows), $money($tx));
    if (abs($paidRows - $tx) > 0.009) {
        $problem(sprintf('paid on the subscription rows (%s) differs from the ledger (%s) by %s.', $money($paidRows), $money($tx), $money($paidRows - $tx)));
    }
}

echo "\n----------------------------------------------\n";
echo $problems ? count($problems) . " PROBLEM(S) found - send this output so the cause can be fixed on purpose.\n" : "No problem found: shares, monthly subscription, dues, founding and ledger agree.\n";
exit($problems ? 1 : 0);
