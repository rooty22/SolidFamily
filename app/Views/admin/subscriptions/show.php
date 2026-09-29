<?php
// The admin can turn the whole grace-period concept off from Settings; once off, the note is hidden everywhere
// it's shown, even on rows that already carry a (now-irrelevant) grace_until from before it was turned off.
$graceVisible = \App\Models\Setting::get('subscription_grace_enabled', '1') === '1';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e($member['name']) ?> <span class="text-muted" style="font-size:13px;">(<?= number_format($member['shares_count']) ?> سهم × <?= money($shareValue) ?>)</span></h5>
    <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-sm btn-soft">ملف المشترك</a>
</div>

<?php
// Which share lot a payment goes to: shown only when the member has several lots. Lots that are fully paid are left
// out (unless every lot is settled), and each option says which lot it is (#id) and what it still owes.
$lotPicker = function () use ($lots, $payableLots, $allSettled, $member): string {
    if (count($lots) < 2) {
        return '';
    }
    $label = fn($lot) => '#' . $lot['id'] . ' - ' . number_format($lot['shares_count']) . ' سهم - يوم ' . (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot)
        . ' - ' . ($lot['outstanding'] > 0 ? 'متبقي ' . money($lot['outstanding']) : 'مسددة');
    if (count($payableLots) === 1) {
        $only = $payableLots[0];
        return '<input type="hidden" name="lot_id" value="' . (int) $only['id'] . '"><div class="mb-2"><label class="form-label">الدفعة (لوطة الأسهم)</label>'
            . '<div class="form-control bg-light">' . e($label($only)) . '</div></div>';
    }
    $html = '<div class="mb-2"><label class="form-label">الدفعة (لوطة الأسهم)</label><select name="lot_id" class="form-select" required>';
    foreach ($payableLots as $lot) {
        $html .= '<option value="' . (int) $lot['id'] . '">' . e($label($lot)) . '</option>';
    }
    $html .= '</select>' . ($allSettled ? '<div class="form-text">كل الدفعات مسددة حالياً، يمكنك السداد مقدماً.</div>' : '') . '</div>';
    return $html;
};
?>
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-calendar-plus"></i> تسجيل سداد لعدة أشهر</h3></div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="bulk_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">الشهر الأول</label>
                        <input type="month" name="start_month" class="form-control" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">عدد الأشهر</label>
                        <input type="number" name="months_count" class="form-control" value="1" min="1" max="24">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-circle"></i> تسجيل السداد بالكامل</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-cash"></i> تسجيل دفعة جزئية لشهر واحد</h3></div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="partial_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">الشهر</label>
                        <input type="month" name="partial_month" class="form-control" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">قيمة الدفعة</label>
                        <input type="number" step="0.01" min="0.01" name="partial_amount" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-soft w-100 mt-3"><i class="bi bi-cash-stack"></i> تسجيل الدفعة الجزئية</button>
            </form>
        </div>
    </div>
</div>

<div class="card-panel mb-4">
    <div class="panel-head flex-wrap gap-2">
        <div>
            <h3><i class="bi bi-calendar-check-fill text-purple-600"></i> <?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك للأسهم وأهلية القرض' : 'Share Lots Subscription Start Dates & Loan Eligibility' ?></h3>
            <p class="text-xs text-muted mb-0 mt-1">
                <?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك لكل سهم على حدة باليوم والشهر والسنة (لربط شرط استحقاق القرض بمرور 6 أشهر وسداد 500 ريال تأسيس لكل سهم)' : 'Documenting subscription start date per share lot (Day, Month, Year) for loan eligibility (6 months & 500 SAR founding).' ?>
            </p>
        </div>
        <span class="badge-status badge-info"><?= count($lots) ?> <?= is_rtl() ? 'حصص مسجلة' : 'lots' ?></span>
    </div>
    <?php if (empty($lots)): ?>
        <div class="empty-state py-3"><i class="bi bi-pie-chart text-muted mb-2"></i><?= is_rtl() ? 'لا توجد حصص أسهم نشطة' : 'No active share lots' ?></div>
    <?php else: ?>
        <div class="table-lots-box">
            <table class="table-lots-compact">
                <thead>
                    <tr>
                        <th class="col-id">#</th>
                        <th class="col-shares"><?= is_rtl() ? 'عدد الأسهم' : 'Shares' ?></th>
                        <th class="col-date"><?= is_rtl() ? 'تاريخ بداية الاشتراك (باليوم والشهر والسنة)' : 'Start Date (Full Date)' ?></th>
                        <th class="col-due"><?= is_rtl() ? 'يوم الاستحقاق الشهري' : 'Due Day' ?></th>
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
                        <td class="col-id font-numeric font-bold text-slate-500">#<?= (int) ($lot['id'] ?? ($idx + 1)) ?></td>
                        <td class="col-shares font-numeric font-bold text-slate-900"><?= number_format($lot['shares_count']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?></td>
                        <td class="col-date font-numeric font-bold text-purple-700">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="bi bi-calendar3"></i>
                                <?= date_ar($elig['start_date']) ?>
                            </span>
                        </td>
                        <td class="col-due font-numeric text-slate-700"><?= (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot) ?> <?= is_rtl() ? 'من كل شهر' : 'of month' ?></td>
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
                                <span class="badge-status badge-success font-bold">
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

<div class="card-panel">
    <div class="panel-head"><h3><i class="bi bi-clock-history"></i> سجل الاشتراكات</h3></div>
    <table class="table-modern">
        <thead><tr><th>الشهر</th><th>الدفعة</th><th>موعد الاستحقاق</th><th>المستحق</th><th>المسدد</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): [$l, $v] = status_badge($h['status']); ?>
            <tr>
                <td class="fw-bold"><?= e($h['month']) ?></td>
                <td class="text-muted" style="font-size:12.5px;"><?= $h['lot_id'] ? '#' . $h['lot_id'] . ' (' . number_format($h['shares_count_snapshot']) . ' سهم)' : '-' ?>
                    <?php if (!empty($h['lot_status']) && $h['lot_status'] !== 'active'): ?><small class="d-block text-danger">دفعة ملغاة</small><?php endif; ?></td>
                <td><?= date_ar($h['due_date']) ?>
                    <?php if ($graceVisible && !empty($h['grace_until']) && $h['status'] !== 'paid' && $h['grace_until'] >= date('Y-m-d')): ?><small class="d-block text-muted">مهلة حتى <?= date_ar($h['grace_until']) ?></small><?php endif; ?></td>
                <td><?= money($h['amount_due']) ?></td>
                <td><?= money($h['amount_paid']) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($history)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>لا يوجد سجل بعد</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
