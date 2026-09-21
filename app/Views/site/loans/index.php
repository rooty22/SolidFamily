<?php $daysLeft = fn(string $date): int => (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400); ?>
<div class="page-head">
    <div>
        <p><?= __('loans_index_subtitle') ?></p>
    </div>
    <a href="<?= url('loans/request') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> <?= __('new_loan_request') ?></a>
</div>

<div class="kpi-grid">
    <div class="stat-card"><div class="stat-icon bg-grad-purple"><i class="bi bi-cash-coin"></i></div>
        <div><div class="stat-label"><?= __('active_loans') ?></div><div class="stat-value font-num"><?= (int) $runningCount ?></div><div class="stat-sub"><?= __('out_of_total_loans', ['total' => count($loans)]) ?></div></div>
    </div>
    <div class="stat-card"><div class="stat-icon bg-grad-red"><i class="bi bi-wallet2"></i></div>
        <div><div class="stat-label"><?= __('total_remaining') ?></div><div class="stat-value font-num"><?= money($runningRemaining) ?></div><div class="stat-sub"><?= __('on_active_loans') ?></div></div>
    </div>
    <div class="stat-card"><div class="stat-icon bg-grad-amber"><i class="bi bi-calendar-event"></i></div>
        <div>
            <div class="stat-label"><?= __('next_installment') ?></div>
            <?php if ($nextInstallment): $left = $daysLeft($nextInstallment['due_date']); ?>
                <div class="stat-value font-num"><?= money($nextInstallment['amount'] - $nextInstallment['amount_paid']) ?></div>
                <div class="stat-sub <?= $left < 0 ? 'text-danger' : '' ?> font-num"><?= date_ar($nextInstallment['due_date']) ?> · <?= relative_days_label($left) ?></div>
            <?php else: ?>
                <div class="stat-value">—</div><div class="stat-sub"><?= __('no_due_installments') ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card"><div class="stat-icon bg-grad-blue"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="stat-label"><?= __('pending_requests') ?></div><div class="stat-value font-num"><?= (int) $pendingRequests ?></div><div class="stat-sub"><?= __('in_waiting_queue') ?></div></div>
    </div>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-cash-coin"></i> <?= __('my_loans') ?></h3><span class="text-muted small font-num"><?= count($loans) ?></span></div>
        <?php if (empty($loans)): ?>
            <div class="empty-state"><i class="bi bi-cash"></i><?= __('no_loans_yet') ?><br><small class="text-muted"><?= __('no_loans_desc') ?></small></div>
        <?php else: ?>
        <div class="loans-grid">
            <?php foreach ($loans as $l): [$lb, $v] = status_badge($l['status']); $pct = (float) $l['amount'] > 0 ? min(100, round($l['amount_paid'] / $l['amount'] * 100)) : 0; ?>
            <a href="<?= url('loans/' . $l['id']) ?>" class="loan-item text-decoration-none">
                <div class="top">
                    <div>
                        <div class="amount font-num"><?= money($l['amount']) ?></div>
                        <div class="meta"><?= $reasonLabels[$l['reason']] ?? $l['reason'] ?> · <?= date_ar($l['loan_date']) ?></div>
                    </div>
                    <span class="badge-status badge-<?= $v ?>"><?= $lb ?></span>
                </div>
                <div>
                    <div class="progress-modern"><div style="width:<?= $pct ?>%"></div></div>
                    <div class="progress-caption"><span><?= __('repayment_ratio') ?></span><b class="font-num"><?= $pct ?>%</b></div>
                </div>
                <div class="nums">
                    <div><small><?= __('paid_amount') ?></small><span class="text-success font-num"><?= money($l['amount_paid']) ?></span></div>
                    <div><small><?= __('remaining_amount') ?></small><span class="text-danger font-num"><?= money($l['amount_remaining']) ?></span></div>
                    <div><small><?= __('installments') ?></small><span class="font-num"><?= (int) $l['installments_count'] ?></span></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-file-earmark-text-fill"></i> <?= __('loan_requests') ?></h3><span class="text-muted small font-num"><?= count($requests) ?></span></div>
        <?php if (empty($requests)): ?>
            <div class="empty-state"><i class="bi bi-file-earmark-text"></i><?= __('no_loan_requests') ?><br><a href="<?= url('loans/request') ?>" class="btn btn-primary btn-sm mt-3"><?= __('submit_first_loan_request') ?></a></div>
        <?php else: ?>
            <?php foreach ($requests as $r): [$l, $v] = status_badge($r['status']); ?>
            <div class="req-row">
                <div class="r-main">
                    <b class="font-num"><?= money($r['amount_requested']) ?></b>
                    <span><?= $reasonLabels[$r['reason']] ?? $r['reason'] ?> · <?= (int) $r['installments_months'] ?> <?= __('months') ?> · <?= date_ar($r['created_at']) ?></span>
                    <?php if ($r['queue_position']): ?><span class="d-block text-warning fw-bold"><i class="bi bi-people-fill"></i> <?= __('loan_queue_position', ['position' => (int) $r['queue_position']]) ?></span><?php endif; ?>
                </div>
                <span class="badge-status badge-<?= $v ?>"><?= $l ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
