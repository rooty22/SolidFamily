<?php
[$label, $variant] = status_badge($founding['status']);
$remaining = max(0, $founding['total_required'] - $founding['amount_paid']);
$pct = $founding['total_required'] > 0 ? min(100, round($founding['amount_paid'] / $founding['total_required'] * 100)) : 0;
?>
<div class="page-head">
    <div>
        <h1><?= __('founding_amount') ?> <span class="badge-status badge-<?= $variant ?> align-middle"><?= $label ?></span></h1>
        <p><?= __('founding_subtitle') ?></p>
    </div>
</div>

<div class="kpi-grid">
    <div class="stat-card"><div class="stat-icon bg-grad-blue"><i class="bi bi-bank2"></i></div>
        <div><div class="stat-label"><?= __('total_required') ?></div><div class="stat-value font-num"><?= money($founding['total_required']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-green"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-label"><?= __('paid_amount') ?></div><div class="stat-value font-num"><?= money($founding['amount_paid']) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-red"><i class="bi bi-wallet2"></i></div>
        <div><div class="stat-label"><?= __('remaining_amount') ?></div><div class="stat-value font-num"><?= money($remaining) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon bg-grad-purple"><i class="bi bi-pie-chart-fill"></i></div>
        <div><div class="stat-label"><?= __('linked_shares') ?></div><div class="stat-value font-num"><?= number_format($founding['shares_count_linked']) ?></div></div></div>
</div>

<?php
$chosen = !empty($founding['plan_start']);
$planMonths = (int) $founding['plan_months'];
?>
<div class="split">
    <div class="stack">
        <?php if ($remaining > 0 && (float) $founding['total_required'] > 0): ?>
        <div class="card-panel" x-data="{ months: <?= $chosen ? $planMonths : 1 ?>, total: <?= json_encode((float) $founding['total_required']) ?>, get per() { return this.months > 0 ? this.total / this.months : 0; } }">
            <div class="panel-head">
                <h3><i class="bi bi-calendar2-range"></i> <?= __('founding_plan_title') ?></h3>
                <?php if ($chosen): ?><span class="badge-status badge-info"><?= $planMonths === 1 ? __('founding_plan_once') : __('founding_plan_over_months', ['n' => $planMonths]) ?></span><?php endif; ?>
            </div>
            <p class="text-muted small"><?= __('founding_plan_intro') ?></p>
            <form method="post" action="<?= url('founding/plan') ?>">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label"><?= __('founding_plan_title') ?></label>
                        <select name="plan_months" class="form-select" x-model.number="months">
                            <option value="1"><?= __('founding_plan_once') ?></option>
                            <?php for ($n = 2; $n <= $maxPlanMonths; $n++): ?>
                                <option value="<?= $n ?>"><?= __('founding_plan_over_months', ['n' => $n]) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('founding_plan_per_month') ?></label>
                        <div class="form-control d-flex align-items-center justify-content-between" style="background:var(--body-bg);">
                            <b class="font-num" x-text="per.toLocaleString('en-US', {maximumFractionDigits: 2})">0</b><span class="text-muted small"><?= is_rtl() ? 'ريال' : 'SAR' ?></span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2-circle"></i> <?= __('founding_plan_save') ?></button>
            </form>
            <?php if (!$chosen): ?><div class="tip-box mt-3"><i class="bi bi-info-circle-fill"></i> <?= __('founding_plan_not_chosen') ?></div>
            <?php else: ?><p class="text-muted small mt-3 mb-0"><?= __('founding_plan_note') ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($schedule)): ?>
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-list-ol"></i> <?= __('founding_schedule_title') ?></h3><span class="text-muted small font-num"><?= count($schedule) ?></span></div>
            <table class="table-modern">
                <thead><tr><th>#</th><th><?= __('due_date') ?></th><th><?= __('amount') ?></th><th><?= __('paid_amount') ?></th><th><?= __('status') ?></th></tr></thead>
                <tbody>
                <?php foreach ($schedule as $i): [$sl, $sv] = status_badge($i['late'] ? 'unpaid' : $i['status']); ?>
                    <tr <?= $i['late'] ? 'style="background:#fef2f2;"' : '' ?>>
                        <td class="fw-bold font-num"><?= (int) $i['number'] ?></td>
                        <td class="font-num"><?= date_ar($i['due_date']) ?></td>
                        <td class="font-num"><?= money($i['amount']) ?></td>
                        <td class="font-num"><?= money($i['paid']) ?></td>
                        <td><span class="badge-status badge-<?= $sv ?>"><?= $i['late'] ? $sl : status_badge($i['status'])[0] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-graph-up-arrow"></i> <?= __('progress_ratio') ?></h3><b class="font-num"><?= $pct ?>%</b></div>
            <div class="progress-modern"><div style="width:<?= $pct ?>%"></div></div>
            <div class="progress-caption"><span><?= __('paid_label', ['amount' => '<b class="font-num">' . money($founding['amount_paid']) . '</b>']) ?></span><span><?= __('out_of_total', ['total' => '<b class="font-num">' . money($founding['total_required']) . '</b>']) ?></span></div>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-receipt"></i> <?= __('payments_record') ?></h3><span class="text-muted small font-num"><?= count($payments) ?></span></div>
            <?php if (empty($payments)): ?>
                <div class="empty-state"><i class="bi bi-receipt"></i><?= __('no_payments_recorded_yet') ?></div>
            <?php else: ?>
            <table class="table-modern">
                <thead><tr><th>#</th><th><?= __('amount') ?></th><th><?= __('payment_date') ?></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $n => $p): ?>
                    <tr><td class="text-muted font-num"><?= count($payments) - $n ?></td><td class="fw-bold font-num"><?= money($p['amount']) ?></td><td class="font-num"><?= date_ar($p['payment_date']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-calculator-fill"></i> <?= __('how_founding_calculated') ?></h3></div>
            <div class="formula">
                <div class="f-box"><b class="font-num"><?= number_format($founding['shares_count_linked']) ?></b><small><?= __('shares_count') ?></small></div>
                <span class="op">×</span>
                <div class="f-box"><b class="font-num"><?= number_format($feePerShare) ?></b><small><?= __('fee_per_share') ?></small></div>
                <span class="op">=</span>
                <div class="f-box f-total"><b class="font-num"><?= number_format($founding['total_required']) ?></b><small><?= __('required_amount') ?></small></div>
            </div>
            <p class="text-muted small mt-3 mb-0"><?= __('founding_calc_note') ?></p>
        </div>
        <?php if ($remaining > 0): ?>
        <div class="tip-box"><i class="bi bi-info-circle-fill"></i> <?= __('founding_remaining_tip', ['remaining' => '<b class="font-num">' . money($remaining) . '</b>']) ?></div>
        <?php else: ?>
        <div class="note-box"><i class="bi bi-check-circle-fill"></i> <?= __('founding_fully_paid') ?></div>
        <?php endif; ?>
    </div>
</div>
