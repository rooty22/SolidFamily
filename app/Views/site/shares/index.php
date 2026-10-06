<?php
$shares = (int) $member['shares_count'];
$monthly = $shares * $shareValue;
$currentMonth = date('Y-m');
// Same admin on/off switch as the grace period itself: once off, the note is hidden everywhere, even on rows
// that already carry a (now-irrelevant) grace_until from before it was turned off.
$graceVisible = \App\Models\Setting::get('subscription_grace_enabled', '1') === '1';
$currentRows = [];
$byMonth = [];
foreach ($history as $h) {
    $byMonth[$h['month']][] = $h;
    if ($h['month'] === $currentMonth) {
        $currentRows[] = $h;
    }
}
// Months, not lots: a month with two lots is one month, paid only when everything due that month is collected.
$paidCount = 0;
$lateCount = 0;
foreach ($byMonth as $monthRows) {
    $sum = \App\Models\MonthlySubscription::summarize($monthRows);
    if ($sum['amount_due'] > 0 && $sum['status'] === 'paid') {
        $paidCount++;
    }
    foreach ($monthRows as $r) {
        if ((float) $r['amount_due'] > (float) $r['amount_paid'] && \App\Models\MonthlySubscription::effectiveDue($r) < date('Y-m-d')) {
            $lateCount++;
            break;
        }
    }
}
// The month's badge: partial as soon as part of it is collected but not all (e.g. a share added after paying).
$curSummary = \App\Models\MonthlySubscription::summarize($currentRows);
[$curLabel, $curVariant] = status_badge($currentRows ? $curSummary['status'] : 'paid');
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

<!-- Share Lots Subscription Start Dates & Loan Eligibility Documenting Section -->
<div class="card-panel mb-4">
    <div class="panel-head flex-wrap gap-2">
        <div>
            <h3><i class="bi bi-calendar-check-fill text-brand-600"></i> <?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك للأسهم وشروط استحقاق القرض' : 'Shares Subscription Start Dates & Loan Eligibility' ?></h3>
            <p class="text-xs text-slate-500 mt-1 mb-0">
                <?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك لكل سهم على حدة باليوم والشهر والسنة (لربط شرط استحقاق القرض بمرور 6 أشهر وسداد 500 ريال تأسيس لكل سهم)' : 'Full documentation of subscription start dates (Day, Month, Year) per share lot linked to loan eligibility (6 months & 500 SAR founding paid per share).' ?>
            </p>
        </div>
        <span class="badge-status badge-info"><?= count($lots) ?> <?= is_rtl() ? 'حصة أسهم' : 'lots' ?></span>
    </div>
    <?php if (empty($lots)): ?>
        <div class="empty-state py-4"><i class="bi bi-pie-chart text-2xl text-slate-400 mb-2"></i><?= __('no_records_yet') ?></div>
    <?php else: ?>
    <style>
    .table-lots-box {
        border-radius: 14px;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        background: #fff;
    }
    .table-lots-compact {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .table-lots-compact th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        padding: 10px 14px;
        border-bottom: 1.5px solid #e2e8f0;
        white-space: nowrap;
    }
    .table-lots-compact td {
        padding: 11px 14px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .table-lots-compact tbody tr:last-child td {
        border-bottom: none;
    }
    .table-lots-compact tbody tr:hover {
        background-color: #f8fafc;
    }
    .table-lots-compact .col-id { width: 65px; text-align: center; }
    .table-lots-compact .col-shares { width: 115px; }
    .table-lots-compact .col-date { width: 160px; }
    .table-lots-compact .col-due { width: 125px; }
    .table-lots-compact .col-sixmo { width: 235px; }
    .table-lots-compact .col-found { width: 195px; }
    .table-lots-compact .col-status { width: 140px; text-align: center; }
    .table-lots-compact .badge-status {
        padding: 4px 10px;
        font-size: 11.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 20px;
    }
    </style>
    <div class="table-lots-box">
        <table class="table-lots-compact">
            <thead>
                <tr>
                    <th class="col-id">#</th>
                    <th class="col-shares"><?= is_rtl() ? 'الحصة / السهم' : 'Share / Lot' ?></th>
                    <th class="col-date"><?= is_rtl() ? 'تاريخ بداية الاشتراك' : 'Start Date' ?></th>
                    <th class="col-due"><?= is_rtl() ? 'يوم الاستحقاق' : 'Due Day' ?></th>
                    <th class="col-sixmo"><?= is_rtl() ? 'شرط مرور 6 أشهر' : '6 Months Rule' ?></th>
                    <th class="col-found"><?= is_rtl() ? 'سداد 500 ريال تأسيس' : '500 SAR Founding' ?></th>
                    <th class="col-status"><?= is_rtl() ? 'أهلية القرض' : 'Loan Eligibility' ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lots as $idx => $lot): 
                $elig = \App\Models\ShareLot::eligibilityDetails($lot, $member, $founding ?? null);
            ?>
                <tr>
                    <td class="col-id font-num fw-bold text-slate-700">#<?= $idx + 1 ?></td>
                    <td class="col-shares font-num fw-bold text-slate-900">
                        <span class="inline-flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs fw-bold text-brand-700"><?= is_rtl() ? 'السهم رقم ' . ($idx + 1) : 'Share #' . ($idx + 1) ?></span>
                            <span class="text-[11px] text-slate-500 font-normal">(<?= number_format($lot['shares_count']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?>)</span>
                        </span>
                    </td>
                    <td class="col-date font-num">
                        <span class="inline-flex items-center gap-1.5 fw-bold text-brand-700">
                            <i class="bi bi-calendar-event text-brand-600"></i>
                            <span dir="ltr"><?= date_ar($elig['start_date']) ?></span>
                        </span>
                    </td>
                    <td class="col-due font-num text-slate-700"><?= (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot) ?> <?= is_rtl() ? 'من كل شهر' : 'of month' ?></td>
                    <td class="col-sixmo">
                        <?php if ($elig['six_months_met']): ?>
                            <span class="badge-status badge-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <?= is_rtl() ? "مكتمل ({$elig['months_passed']} شهر)" : "Completed ({$elig['months_passed']} mos)" ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-status badge-warning" title="<?= is_rtl() ? 'يكتمل بتاريخ: ' . date_ar($elig['target_date']) : 'Target: ' . date_ar($elig['target_date']) ?>">
                                <i class="bi bi-hourglass-split"></i>
                                <?= is_rtl() ? "متبقي {$elig['months_remaining_label']} (حتى " . date_ar($elig['target_date']) . ")" : "{$elig['months_remaining']} mos left" ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="col-found">
                        <?php if ($elig['founding_met']): ?>
                            <span class="badge-status badge-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <?= is_rtl() ? 'مستوفى (مسدد بالكامل)' : 'Fulfilled (Paid)' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-status badge-warning">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <?= is_rtl() ? 'مسدد ' . money($elig['founding_paid_per_share']) . ' من 500 ريال' : money($elig['founding_paid_per_share']) . ' of 500' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="col-status">
                        <?php if ($elig['is_eligible']): ?>
                            <span class="badge-status badge-success fw-bold">
                                <i class="bi bi-shield-check"></i>
                                <?= is_rtl() ? 'مؤهل للقرض' : 'Eligible' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-status badge-secondary">
                                <i class="bi bi-clock"></i>
                                <?= is_rtl() ? 'قيد استيفاء الشروط' : 'Pending' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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
                    <td class="font-num"><?= date_ar($h['due_date']) ?>
                        <?php if ($graceVisible && !empty($h['grace_until']) && $h['status'] !== 'paid' && $h['grace_until'] >= date('Y-m-d')): ?><small class="d-block text-muted"><?= __('subscription_grace_until', ['date' => date_ar($h['grace_until'])]) ?></small><?php endif; ?></td>
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
                <?php if (count($lots) > 1): ?>
                    <?php foreach ($lots as $lot): ?>
                        <div class="info-row">
                            <span><?= __('shares_count') ?>: <b class="font-num"><?= number_format($lot['shares_count']) ?></b></span>
                            <b><?= __('before_day_of_month', ['day' => (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot)]) ?></b>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="info-row"><span><?= __('due_time') ?></span><b><?= __('before_day_of_month', ['day' => (int) $dueDay]) ?></b></div>
                <?php endif; ?>
                <div class="info-row"><span><?= __('paid_months') ?></span><b class="font-num text-success"><?= $paidCount ?></b></div>
                <div class="info-row"><span><?= __('late_months') ?></span><b class="font-num <?= $lateCount > 0 ? 'text-danger' : '' ?>"><?= $lateCount ?></b></div>
            </div>
            <?php if (count($lots) > 1): ?>
                <p class="text-muted small mt-2 mb-0"><?= __('unmerged_lots_notice') ?></p>
            <?php endif; ?>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-arrow-left-right"></i> <?= __('adjust_your_shares') ?></h3></div>
            <p class="text-muted small mb-3"><?= __('adjust_shares_desc') ?></p>
            <a href="<?= url('share-requests') ?>" class="btn btn-soft w-100"><?= __('submit_share_request') ?></a>
        </div>
    </div>
</div>
