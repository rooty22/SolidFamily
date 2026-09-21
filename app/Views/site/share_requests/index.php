<?php
$pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
?>
<div class="page-head">
    <div>
        <p><?= __('share_requests_subtitle') ?></p>
    </div>
    <a href="<?= url('shares') ?>" class="btn btn-soft btn-sm"><i class="bi bi-pie-chart-fill"></i> <?= __('shares_and_subscriptions') ?></a>
</div>

<div class="kpi-grid">
    <div class="stat-card"><div class="stat-icon bg-grad-purple"><i class="bi bi-pie-chart-fill"></i></div>
        <div><div class="stat-label"><?= __('your_current_shares') ?></div><div class="stat-value font-num"><?= number_format($member['shares_count']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-amber"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="stat-label"><?= __('under_review') ?></div><div class="stat-value font-num"><?= $pending ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-green"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-label"><?= __('approved') ?></div><div class="stat-value font-num"><?= $approved ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-red"><i class="bi bi-x-circle"></i></div>
        <div><div class="stat-label"><?= __('rejected') ?></div><div class="stat-value font-num"><?= $rejected ?></div></div></div>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-list-check"></i> <?= __('my_previous_requests') ?></h3><span class="text-muted small font-num"><?= count($requests) ?></span></div>
        <?php if (empty($requests)): ?>
            <div class="empty-state"><i class="bi bi-arrow-left-right"></i><?= __('no_requests_yet') ?><br><small class="text-muted"><?= __('send_first_request_hint') ?></small></div>
        <?php else: ?>
        <table class="table-modern">
            <thead><tr><th><?= __('type') ?></th><th><?= __('count') ?></th><th><?= __('date') ?></th><th><?= __('status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): [$l, $v] = status_badge($r['status']); ?>
                <tr>
                    <td class="fw-bold"><?= $typeLabels[$r['type']] ?? $r['type'] ?></td>
                    <td class="font-num"><?= $r['shares_count'] ? number_format($r['shares_count']) : '-' ?></td>
                    <td class="font-num"><?= date_ar($r['created_at']) ?></td>
                    <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="stack">
        <div class="card-panel" x-data="{ type: 'add' }">
            <div class="panel-head"><h3><i class="bi bi-send-fill"></i> <?= __('new_request') ?></h3></div>
            <form method="post" action="<?= url('share-requests') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label"><?= __('request_type') ?></label>
                    <select name="type" class="form-select" x-model="type">
                        <option value="add"><?= __('share_type_add') ?></option>
                        <option value="merge"><?= __('share_type_merge') ?></option>
                        <option value="cancel"><?= __('share_type_cancel') ?></option>
                    </select>
                </div>
                <div class="mb-3" x-show="type !== 'merge'">
                    <label class="form-label"><?= __('shares_count') ?></label>
                    <input type="number" name="shares_count" class="form-control font-num" min="1" max="<?= max(1, (int) $member['shares_count']) ?>" value="1" :max="type === 'cancel' ? <?= max(1, (int) $member['shares_count']) ?> : 1000">
                    <div class="small text-muted mt-1" x-show="type === 'cancel'" x-cloak style="display:none;"><?= __('cannot_cancel_more_shares', ['count' => number_format($member['shares_count'])]) ?></div>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send-fill"></i> <?= __('submit_request') ?></button>
            </form>
        </div>
        <div class="tip-box"><i class="bi bi-info-circle-fill"></i> <?= __('shares_review_notice') ?></div>
    </div>
</div>
