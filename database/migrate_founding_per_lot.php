<?php

/**
 * Founding amount per share (lot).
 *
 *   php database/migrate_founding_per_lot.php             # create the table and back-fill old payments
 *   php database/migrate_founding_per_lot.php --dry-run   # only print what the back-fill would do, change nothing
 *   php database/migrate_founding_per_lot.php --no-backfill
 *
 * Until now a founding payment belonged to the member as a whole and the loan-eligibility check divided the total
 * paid by the member's number of shares, so one paid share looked "partly paid" on every share. A payment is now
 * allocated to the share (share_lots row, shown as "السهم رقم N") it was paid for, in founding_payment_allocations.
 * founding_payments and transactions are NOT touched: an allocation only says which share a payment (or a part of it)
 * covers, so a payment can still be split over several shares.
 *
 * Back-fill of the payments recorded before this change (never guessed, always reported):
 *   1. A payment whose note starts with "السهم رقم N" (written by the old payment form when the admin picked a share) is
 *      allocated to that share, but only for members whose shares have never been merged or cancelled, because
 *      otherwise the number N may no longer point at the same share.
 *   2. A member whose founding amount is fully paid has every share covered, whatever the payments said: their
 *      payments are allocated to the shares oldest first, exactly up to what each share owes.
 *   3. Anything else stays unallocated and is listed below; the admin assigns it from the member's founding page.
 *
 * Idempotent: payments that already have an allocation are skipped.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$dryRun = in_array('--dry-run', $argv ?? [], true);
$skipBackfill = in_array('--no-backfill', $argv ?? [], true);

if (!$pdo->query("SHOW TABLES LIKE 'founding_payment_allocations'")->fetchColumn()) {
    if ($dryRun) {
        echo "- [dry-run] founding_payment_allocations would be created.\n";
    } else {
        $pdo->exec("CREATE TABLE founding_payment_allocations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payment_id INT UNSIGNED NOT NULL COMMENT 'the founding payment (or the part of it) this row covers',
            lot_id INT UNSIGNED NOT NULL COMMENT 'the share the money was paid for',
            amount DECIMAL(12,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_alloc_lot (lot_id),
            INDEX idx_alloc_payment (payment_id),
            CONSTRAINT fk_alloc_payment FOREIGN KEY (payment_id) REFERENCES founding_payments(id) ON DELETE CASCADE,
            CONSTRAINT fk_alloc_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "- founding_payment_allocations created.\n";
    }
} else {
    echo "- founding_payment_allocations already exists, skipped.\n";
}

// A dry run on a database that does not have the table yet still previews the back-fill (as if nothing was allocated).
$hasAlloc = (bool) $pdo->query("SHOW TABLES LIKE 'founding_payment_allocations'")->fetchColumn();
if ($skipBackfill || (!$hasAlloc && !$dryRun)) {
    echo "Founding-per-lot migration finished" . ($skipBackfill ? " (back-fill skipped)" : "") . ".\n";
    exit(0);
}
$allocated = $hasAlloc
    ? '(SELECT COALESCE(SUM(a.amount), 0) FROM founding_payment_allocations a WHERE a.payment_id = p.id)'
    : '0';

$feeSetting = (float) ($pdo->query("SELECT value FROM settings WHERE `key` = 'founding_fee_per_share' LIMIT 1")->fetchColumn() ?: 0);
$insert = $dryRun ? null : $pdo->prepare('INSERT INTO founding_payment_allocations (payment_id, lot_id, amount) VALUES (?, ?, ?)');

// Same numbering every screen uses: active lots first (oldest first), then merged / cancelled ones.
$lotsOf = function (int $memberId) use ($pdo): array {
    $stmt = $pdo->prepare("SELECT * FROM share_lots WHERE member_id = ? ORDER BY status = 'active' DESC, created_at ASC, id ASC");
    $stmt->execute([$memberId]);
    return $stmt->fetchAll();
};

// Members who still have founding money that is not assigned to a share (a payment may be assigned in parts).
$members = $pdo->query(
    "SELECT DISTINCT p.member_id FROM founding_payments p WHERE p.amount - {$allocated} > 0.004 ORDER BY p.member_id"
)->fetchAll(PDO::FETCH_COLUMN);

$byLabel = 0;
$byFullPay = 0;
$pending = [];

foreach ($members as $memberId) {
    $memberId = (int) $memberId;
    $lots = $lotsOf($memberId);
    $allActive = $lots && count(array_filter($lots, fn($l) => $l['status'] !== 'active')) === 0;

    $stmt = $pdo->prepare(
        "SELECT p.*, p.amount - {$allocated} AS unallocated
           FROM founding_payments p WHERE p.member_id = ? ORDER BY p.payment_date ASC, p.id ASC"
    );
    $stmt->execute([$memberId]);
    $payments = $stmt->fetchAll();

    $planned = []; // [payment_id, lot_id, amount]

    // 1. Payments that name their share in the note.
    if ($allActive) {
        foreach ($payments as &$p) {
            if ((float) $p['unallocated'] <= 0 || !preg_match('/^السهم رقم (\d+)/u', (string) $p['notes'], $m)) {
                continue;
            }
            $lot = $lots[(int) $m[1] - 1] ?? null;
            if ($lot) {
                $planned[] = [(int) $p['id'], (int) $lot['id'], round((float) $p['unallocated'], 2), 'label'];
                $p['unallocated'] = 0;
            }
        }
        unset($p);
    }

    // 2. Fully paid members: cover every active share oldest first, exactly up to what it owes.
    $founding = $pdo->prepare('SELECT * FROM founding_amounts WHERE member_id = ?');
    $founding->execute([$memberId]);
    $founding = $founding->fetch();
    if ($founding && (float) $founding['total_required'] > 0 && (float) $founding['amount_paid'] >= (float) $founding['total_required']) {
        $linked = (int) $founding['shares_count_linked'];
        $fee = $linked > 0 ? (float) $founding['total_required'] / $linked : $feeSetting;

        $covered = []; // lot id => already allocated (existing rows + the ones planned above)
        if ($hasAlloc) {
            $q = $pdo->prepare('SELECT lot_id, SUM(amount) s FROM founding_payment_allocations WHERE lot_id IN (SELECT id FROM share_lots WHERE member_id = ?) GROUP BY lot_id');
            $q->execute([$memberId]);
            foreach ($q->fetchAll() as $r) {
                $covered[(int) $r['lot_id']] = (float) $r['s'];
            }
        }
        foreach ($planned as $pl) {
            $covered[$pl[1]] = ($covered[$pl[1]] ?? 0) + $pl[2];
        }

        foreach ($lots as $lot) {
            if ($lot['status'] !== 'active') {
                continue;
            }
            $need = round((int) $lot['shares_count'] * $fee - ($covered[(int) $lot['id']] ?? 0), 2);
            foreach ($payments as &$p) {
                if ($need <= 0) {
                    break;
                }
                if ((float) $p['unallocated'] <= 0) {
                    continue;
                }
                $take = round(min($need, (float) $p['unallocated']), 2);
                $planned[] = [(int) $p['id'], (int) $lot['id'], $take, 'full'];
                $p['unallocated'] = round((float) $p['unallocated'] - $take, 2);
                $need = round($need - $take, 2);
            }
            unset($p);
        }
    }

    foreach ($planned as [$paymentId, $lotId, $amount, $how]) {
        if ($amount <= 0) {
            continue;
        }
        if (!$dryRun) {
            $insert->execute([$paymentId, $lotId, $amount]);
        }
        $how === 'label' ? $byLabel++ : $byFullPay++;
        echo sprintf("  member %d: payment #%d -> lot #%d, %.2f (%s)\n", $memberId, $paymentId, $lotId, $amount, $how === 'label' ? 'share named in the note' : 'founding fully paid');
    }

    $left = array_sum(array_map(fn($p) => max(0, (float) $p['unallocated']), $payments));
    if ($left > 0.004) {
        $pending[$memberId] = round($left, 2);
    }
}

echo ($dryRun ? '- [dry-run] ' : '- ') . "{$byLabel} allocation(s) from the share named in the payment note, {$byFullPay} from fully paid founding amounts.\n";
if ($pending) {
    echo '- ' . count($pending) . " member(s) still have founding money that is not assigned to a share (assign it from admin > founding amounts > the member):\n";
    foreach ($pending as $memberId => $amount) {
        echo sprintf("    member %d: %.2f SAR\n", $memberId, $amount);
    }
} else {
    echo "- every founding payment is assigned to a share.\n";
}

echo "Founding-per-lot migration finished successfully.\n";
