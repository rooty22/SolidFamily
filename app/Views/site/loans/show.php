<?php
[$label, $variant] = status_badge($loan['status']);
$pct = $loan['amount'] > 0 ? min(100, round($loan['amount_paid'] / $loan['amount'] * 100)) : 0;
$remainingCount = count(array_filter($installments, fn($i) => $i['status'] !== 'paid'));
$next = null;
foreach ($installments as $i) {
    if ($i['status'] !== 'paid') {
        $next = $i;
        break;
    }
}
$daysLeft = $next ? (int) floor((strtotime($next['due_date']) - strtotime(date('Y-m-d'))) / 86400) : 0;
?>
<div class="page-head">
    <div>
        <h1><?= __('loan_date_title', ['date' => '<bdi dir="ltr" class="font-num">' . date_ar($loan['loan_date']) . '</bdi>']) ?> <span class="badge-status badge-<?= $variant ?> align-middle"><?= $label ?></span></h1>
        <p><?= $reasonLabels[$loan['reason']] ?? $loan['reason'] ?><?= !empty($loan['reason_other_text']) ? ' - ' . e($loan['reason_other_text']) : '' ?></p>
    </div>
    <a href="<?= url('loans') ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i> <?= __('back_to_loans') ?></a>
</div>

<div class="kpi-grid">
    <div class="stat-card"><div class="stat-icon bg-grad-purple"><i class="bi bi-cash-stack"></i></div>
        <div><div class="stat-label"><?= __('loan_value') ?></div><div class="stat-value font-num"><?= money($loan['amount']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-green"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-label"><?= __('paid_amount') ?></div><div class="stat-value font-num"><?= money($loan['amount_paid']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-red"><i class="bi bi-wallet2"></i></div>
        <div><div class="stat-label"><?= __('remaining_amount') ?></div><div class="stat-value font-num"><?= money($loan['amount_remaining']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-amber"><i class="bi bi-list-ol"></i></div>
        <div><div class="stat-label"><?= __('remaining_installments') ?></div><div class="stat-value font-num"><?= $remainingCount ?><small class="text-muted fs-6"> / <?= (int) $loan['installments_count'] ?></small></div></div></div>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-list-ol"></i> <?= __('installments_schedule') ?></h3></div>
        <table class="table-modern">
            <thead><tr><th>#</th><th><?= __('due_date') ?></th><th><?= __('amount') ?></th><th><?= __('paid_amount') ?></th><th><?= __('status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($installments as $i): [$l, $v] = status_badge($i['status']); $isNext = $next && (int) $next['id'] === (int) $i['id']; ?>
                <tr <?= $isNext ? 'style="background:var(--warning-bg);"' : '' ?>>
                    <td class="fw-bold font-num"><?= $i['installment_number'] ?></td>
                    <td class="font-num"><?= date_ar($i['due_date']) ?><?= $isNext ? ' <span class="badge-status badge-warning">' . __('next') . '</span>' : '' ?></td>
                    <td class="font-num"><?= money($i['amount']) ?></td>
                    <td class="font-num"><?= money($i['amount_paid']) ?></td>
                    <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-graph-up-arrow"></i> <?= __('repayment_ratio') ?></h3></div>
            <div class="progress-modern"><div style="width:<?= $pct ?>%"></div></div>
            <div class="progress-caption"><span><?= __('repayment_ratio') ?></span><b class="font-num"><?= $pct ?>%</b></div>
            <?php if ($next): ?>
            <div class="tip-box mt-3 font-num">
                <i class="bi bi-calendar-event"></i> <?= __('next_installment_tip', ['amount' => '<b class="font-num">' . money($next['amount'] - $next['amount_paid']) . '</b>', 'date' => '<b class="font-num">' . date_ar($next['due_date']) . '</b>']) ?>
                <span class="<?= $daysLeft < 0 ? 'text-danger fw-bold' : '' ?>">(<?= relative_days_label($daysLeft) ?>)</span>
            </div>
            <?php else: ?>
            <div class="note-box mt-3"><i class="bi bi-check-circle-fill"></i> <?= __('all_installments_paid') ?></div>
            <?php endif; ?>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-info-circle-fill"></i> <?= __('loan_details') ?></h3></div>
            <div class="info-list">
                <div class="info-row"><span><?= __('loan_reason') ?></span><b><?= $reasonLabels[$loan['reason']] ?? $loan['reason'] ?></b></div>
                <div class="info-row"><span><?= __('loan_date') ?></span><b class="font-num"><?= date_ar($loan['loan_date']) ?></b></div>
                <div class="info-row"><span><?= __('installments_count') ?></span><b class="font-num"><?= (int) $loan['installments_count'] ?></b></div>
                <div class="info-row"><span><?= __('installment_value') ?></span><b class="font-num"><?= money($loan['installment_value']) ?></b></div>
                <div class="info-row"><span><?= __('admin_fee') ?></span><b class="font-num"><?= money($loan['admin_fee_amount']) ?> <small class="text-muted">(<?= rtrim(rtrim(number_format((float) $loan['admin_fee_percent'], 2), '0'), '.') ?>%)</small></b></div>
            </div>
        </div>
    </div>
</div>
