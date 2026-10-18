<?php

/**
 * Lets an admin split a merged share back into the lots it was made from.
 *
 *   php database/migrate_split_lots.php
 *
 * Adds share_lots.merged_into_id (the merged lot a folded-away lot became). Merges approved before this migration
 * are back-filled when their request named its lots (share_requests.lot_ids); older merges that took "every active
 * lot", and merges made directly by an admin, have no record of their originals and cannot be split. Idempotent.
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('SHOW COLUMNS FROM share_lots')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('merged_into_id', $cols, true)) {
    $pdo->exec("ALTER TABLE share_lots ADD COLUMN merged_into_id INT UNSIGNED NULL COMMENT 'for a lot folded away by a merge: the merged lot it became, so the merge can be split back' AFTER source_request_id");
    echo "- share_lots.merged_into_id added.\n";
} else {
    echo "- share_lots.merged_into_id already exists, skipped.\n";
}

// Back-fill: a merged lot created by an approved merge request that listed its lots.
$linked = 0;
$merges = $pdo->query(
    "SELECT l.id, l.member_id, r.lot_ids
       FROM share_lots l
       JOIN share_requests r ON r.id = l.source_request_id AND r.type = 'merge' AND r.status = 'approved'
      WHERE r.lot_ids IS NOT NULL AND r.lot_ids <> ''"
)->fetchAll(PDO::FETCH_ASSOC);
$link = $pdo->prepare("UPDATE share_lots SET merged_into_id = :into WHERE id = :id AND member_id = :member AND status = 'merged' AND merged_into_id IS NULL AND id <> :self");
foreach ($merges as $merge) {
    foreach (array_filter(array_map('intval', explode(',', $merge['lot_ids']))) as $childId) {
        $link->execute(['into' => $merge['id'], 'self' => $merge['id'], 'id' => $childId, 'member' => $merge['member_id']]);
        $linked += $link->rowCount();
    }
}
echo "- {$linked} earlier merged lot(s) linked to the lot they became.\n";

echo "Split-lots migration finished successfully.\n";
