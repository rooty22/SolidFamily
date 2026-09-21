<?php
$shares = (int) $member['shares_count'];
$monthly = $shares * $shareValue;
$currentMonth = date('Y-m');
$current = null;
$paidCount = 0;
$lateCount = 0;
foreach ($history as $h) {
    if ($h['month'] === $currentMonth) {
        $current = $h;
    }
    if ($h['status'] === 'paid') {
        $paidCount++;
    } elseif ($h['amount_due'] > 0 && $h['due_date'] < date('Y-m-d')) {
        $lateCount++;
    }
}
[$curLabel, $curVariant] = status_badge($current['status'] ?? 'unpaid');
?>
<div class="page-head">
    <div>
        <p><?= __('shares_index_subtitle') ?></p>
    </div>
    <a href="<?= url('share-requests') ?>" class="btn btn-primary"><i class="bi bi-arrow-left-right"></i> <?= __('submit_share_request') ?></a>
</div>

<div class="kpi-grid">
    <div class="stat-card"><div class="stat-icon bg-grad-purple"><i class="bi bi-pie-chart-fill"></i></div>
        <div><div class="stat-label"><?= __('shares_count') ?></div><div class="stat-value font-num"><?= number_format($shares) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-blue"><i class="bi bi-tag-fill"></i></div>
        <div><div class="stat-label"><?= __('single_share_value') ?></div><div class="stat-value font-num"><?= money($shareValue) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-green"><i class="bi bi-calendar2-check-fill"></i></div>
        <div><div class="stat-label"><?= __('monthly_subscription') ?></div><div class="stat-value font-num"><?= money($monthly) ?></div><div class="stat-sub"><?= __('before_day_of_month', ['day' => (int) $dueDay]) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-amber"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div><div class="stat-label"><?= __('month_status_label', ['month' => e($currentMonth)]) ?></div><div class="stat-value" style="font-size:16px;"><span class="badge-status badge-<?= $curVariant ?>"><?= $curLabel ?></span></div>
            <div class="stat-sub font-num"><?= __('paid_and_late_summary', ['paid' => $paidCount, 'late' => '<span class="' . ($lateCount > 0 ? 'text-danger fw-bold' : '') . '">' . $lateCount . '</span>']) ?></div></div></div>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-clock-history"></i> <?= __('monthly_subs_history') ?></h3><span class="text-muted small font-num"><?= count($history) ?></span></div>
        <?php if (empty($history)): ?>
            <div class="empty-state"><i class="bi bi-calendar-x"></i><?= __('no_records_yet') ?></div>
        <?php else: ?>
        <table class="table-modern">
            <thead><tr><th><?= __('month') ?></th><th><?= __('due_date') ?></th><th><?= __('due_amount') ?></th><th><?= __('paid_amount') ?></th><th><?= __('status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): [$l, $v] = status_badge($h['status']); ?>
                <tr>
                    <td class="fw-bold font-num"><?= e($h['month']) ?></td>
                    <td class="font-num"><?= date_ar($h['due_date']) ?></td>
                    <td class="font-num"><?= money($h['amount_due']) ?></td>
                    <td class="font-num"><?= money($h['amount_paid']) ?></td>
                    <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-calculator-fill"></i> <?= __('how_subscription_calculated') ?></h3></div>
            <div class="formula">
                <div class="f-box"><b class="font-num"><?= number_format($shares) ?></b><small><?= __('shares_count') ?></small></div>
                <span class="op">×</span>
                <div class="f-box"><b class="font-num"><?= number_format($shareValue) ?></b><small><?= __('share_value') ?></small></div>
                <span class="op">=</span>
                <div class="f-box f-total"><b class="font-num"><?= number_format($monthly) ?></b><small><?= __('monthly_subscription') ?></small></div>
            </div>
            <div class="info-list mt-2">
                <div class="info-row"><span><?= __('due_time') ?></span><b><?= __('before_day_of_month', ['day' => (int) $dueDay]) ?></b></div>
                <div class="info-row"><span><?= __('paid_months') ?></span><b class="font-num text-success"><?= $paidCount ?></b></div>
                <div class="info-row"><span><?= __('late_months') ?></span><b class="font-num <?= $lateCount > 0 ? 'text-danger' : '' ?>"><?= $lateCount ?></b></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-arrow-left-right"></i> <?= __('adjust_your_shares') ?></h3></div>
            <p class="text-muted small mb-3"><?= __('adjust_shares_desc') ?></p>
            <a href="<?= url('share-requests') ?>" class="btn btn-soft w-100"><?= __('submit_share_request') ?></a>
        </div>
    </div>
</div>
