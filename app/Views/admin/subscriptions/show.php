<?php
// The admin can turn the whole grace-period concept off from Settings; once off, the note is hidden everywhere
// it's shown, even on rows that already carry a (now-irrelevant) grace_until from before it was turned off.
$graceVisible = \App\Models\Setting::get('subscription_grace_enabled', '1') === '1';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-1 text-slate-900 flex items-center gap-2">
            <i class="bi bi-wallet2 text-emerald-600"></i>
            <span><?= e($member['name']) ?></span>
            <span class="text-muted font-normal text-xs font-numeric">(<?= number_format($member['shares_count']) ?> سهم × <?= money($shareValue) ?>)</span>
        </h5>
        <p class="text-xs text-slate-500 mb-0"><?= is_rtl() ? 'إدارة ومتابعة اشتراكات ودفعات المشترك، وحساب المبالغ المستحقة والمدفوعة والمتبقية.' : 'Manage member subscriptions, paid, due, and remaining amounts.' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-sm btn-soft font-bold">
            <i class="bi bi-person-badge me-1"></i>
            <span><?= is_rtl() ? 'ملف المشترك' : 'Member Profile' ?></span>
        </a>
    </div>
</div>

<!-- 4 Executive Financial KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <!-- Total Due -->
    <div class="metric-tile metric-blue">
        <div class="metric-label"><?= is_rtl() ? 'إجمالي المبالغ المستحقة' : 'Total Due' ?></div>
        <div class="metric-value font-numeric text-sky-950"><?= money($totalDue) ?></div>
        <div class="text-[11px] font-bold text-slate-400 mt-0.5"><?= count($history) ?> <?= is_rtl() ? 'دفعات مسجلة' : 'records' ?></div>
    </div>

    <!-- Total Paid -->
    <div class="metric-tile metric-green">
        <div class="metric-label"><?= is_rtl() ? 'إجمالي المبالغ المسددة' : 'Total Paid' ?></div>
        <div class="metric-value font-numeric text-emerald-700"><?= money($totalPaid) ?></div>
        <div class="text-[11px] font-bold text-emerald-600/80 mt-0.5">
            <?= $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) . '% ' . (is_rtl() ? 'نسبة التحصيل' : 'collected') : (is_rtl() ? 'تم التحصيل بالكامل' : '100%') ?>
        </div>
    </div>

    <!-- Total Remaining -->
    <div class="metric-tile <?= $totalRemaining > 0 ? 'metric-amber' : 'metric-slate' ?>">
        <div class="metric-label"><?= is_rtl() ? 'إجمالي المبلغ المتبقي' : 'Total Remaining' ?></div>
        <div class="metric-value font-numeric <?= $totalRemaining > 0 ? 'text-amber-700 font-extrabold' : 'text-slate-700' ?>"><?= money($totalRemaining) ?></div>
        <div class="text-[11px] font-bold <?= $totalRemaining > 0 ? 'text-amber-600' : 'text-slate-400' ?> mt-0.5">
            <?= $totalRemaining > 0 ? (is_rtl() ? 'مستحقات قائمة للسداد' : 'Outstanding Balance') : (is_rtl() ? 'لا توجد مبالغ متبقية' : 'No Balance Due') ?>
        </div>
    </div>

    <!-- Late / Overdue -->
    <div class="metric-tile <?= $lateCount > 0 ? 'metric-rose bg-rose-50/50 border-rose-200' : 'metric-green' ?>">
        <div class="metric-label"><?= is_rtl() ? 'الدفعات المتأخرة' : 'Overdue Payments' ?></div>
        <div class="metric-value font-numeric <?= $lateCount > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
            <?= $lateCount > 0 ? "{$lateCount} " . (is_rtl() ? 'دفعات متأخرة' : 'late') : (is_rtl() ? 'لا توجد متأخرات' : 'None') ?>
        </div>
        <div class="text-[11px] font-bold <?= $lateCount > 0 ? 'text-rose-600' : 'text-emerald-600/80' ?> mt-0.5">
            <?= $lateCount > 0 ? (is_rtl() ? 'المتبقي المتأخر: ' . money($lateAmount) : 'Overdue: ' . money($lateAmount)) : (is_rtl() ? 'سجل السداد منتظم' : 'Up to date') ?>
        </div>
    </div>
</div>

<?php if ($lateCount > 0): ?>
<!-- Prominent Alert for Overdue Payments -->
<div class="bg-rose-50/80 border border-rose-200 text-rose-900 rounded-2xl p-3.5 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <i class="bi bi-exclamation-octagon-fill text-lg"></i>
        </div>
        <div>
            <div class="font-bold text-sm text-rose-950"><?= is_rtl() ? 'تنبيه: يوجد دفعات متأخرة عن موعد استحقاقها' : 'Warning: Overdue Subscriptions Detected' ?></div>
            <div class="text-xs text-rose-700 mt-0.5">
                <?= is_rtl() 
                    ? "لدى هذا المشترك {$lateCount} دفعات اشتراك تجاوزت موعد الاستحقاق الشهري بإجمالي مبالغ متبقية قدرها <strong>" . money($lateAmount) . "</strong>." 
                    : "This member has {$lateCount} overdue monthly subscriptions with a total remaining balance of <strong>" . money($lateAmount) . "</strong>." ?>
            </div>
        </div>
    </div>
    <span class="inline-flex items-center px-3 py-1 rounded-xl bg-rose-600 text-white text-xs font-numeric font-bold shrink-0 shadow-xs">
        <i class="bi bi-clock-history me-1"></i>
        <?= money($lateAmount) ?>
    </span>
</div>
<?php endif; ?>

<?php
// Which share lot a payment goes to: shown only when the member has several lots. Lots that are fully paid are left
// out (unless every lot is settled), and each option says which lot it is (#id) and what it still owes.
$lotPicker = function () use ($lots, $payableLots, $allSettled, $member): string {
    if (count($lots) < 2) {
        return '';
    }
    $label = fn($idx, $lot) => (is_rtl() ? 'السهم رقم ' . ($idx + 1) : 'Share #' . ($idx + 1)) . ' (' . number_format($lot['shares_count']) . ' سهم)'
        . ' - يوم ' . (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot)
        . ' - ' . ($lot['outstanding'] > 0 ? (is_rtl() ? 'متبقي ' . money($lot['outstanding']) : 'due ' . money($lot['outstanding'])) : (is_rtl() ? 'مسددة' : 'settled'));
    
    if (count($payableLots) === 1) {
        $only = $payableLots[0];
        $onlyIdx = array_search($only['id'], array_column($lots, 'id'));
        return '<input type="hidden" name="lot_id" value="' . (int) $only['id'] . '"><div class="mb-2"><label class="form-label text-xs font-bold text-slate-700 mb-1">' . (is_rtl() ? 'الدفعة (لوطة الأسهم)' : 'Share Lot') . '</label>'
            . '<div class="form-control bg-light text-xs font-bold text-slate-800">' . e($label($onlyIdx !== false ? $onlyIdx : 0, $only)) . '</div></div>';
    }
    $html = '<div class="mb-2"><label class="form-label text-xs font-bold text-slate-700 mb-1">' . (is_rtl() ? 'الدفعة (لوطة الأسهم)' : 'Share Lot') . '</label><select name="lot_id" class="form-select text-xs font-bold" required>';
    foreach ($payableLots as $pLot) {
        $pIdx = array_search($pLot['id'], array_column($lots, 'id'));
        $html .= '<option value="' . (int) $pLot['id'] . '">' . e($label($pIdx !== false ? $pIdx : 0, $pLot)) . '</option>';
    }
    $html .= '</select>' . ($allSettled ? '<div class="form-text text-[11px]">' . (is_rtl() ? 'كل الدفعات مسددة حالياً، يمكنك السداد مقدماً.' : 'All lots are settled, you can pay in advance.') . '</div>' : '') . '</div>';
    return $html;
};
?>

<!-- Payment Action Panels (Bulk Pay & Partial Pay) -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <i class="bi bi-calendar-plus text-sky-600"></i>
                    <span><?= is_rtl() ? 'تسجيل سداد لعدة أشهر / بالكامل' : 'Record Full / Multi-Month Payment' ?></span>
                </h3>
            </div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="bulk_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'تاريخ السداد / الشهر الأول (يوم/شهر/سنة)' : 'Start Date (Day/Month/Year)' ?></label>
                        <input type="date" name="start_date" class="form-control font-numeric font-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'عدد الأشهر' : 'Months Count' ?></label>
                        <input type="number" name="months_count" class="form-control font-numeric font-bold" value="1" min="1" max="24">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3 font-bold py-2 rounded-xl">
                    <i class="bi bi-check-circle me-1"></i>
                    <span><?= is_rtl() ? 'تسجيل السداد بالكامل' : 'Record Full Payment' ?></span>
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <i class="bi bi-cash-stack text-amber-600"></i>
                    <span><?= is_rtl() ? 'تسجيل دفعة جزئية لشهر واحد' : 'Record Partial Payment for Month' ?></span>
                </h3>
            </div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="partial_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'تاريخ الدفعة / الشهر (يوم/شهر/سنة)' : 'Payment Date / Month' ?></label>
                        <input type="date" name="partial_date" class="form-control font-numeric font-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'قيمة الدفعة' : 'Amount' ?></label>
                        <input type="number" step="0.01" min="0.01" name="partial_amount" class="form-control font-numeric font-bold" placeholder="0.00" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-soft w-100 mt-3 font-bold py-2 rounded-xl">
                    <i class="bi bi-cash me-1"></i>
                    <span><?= is_rtl() ? 'تسجيل الدفعة الجزئية' : 'Record Partial Payment' ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Share Lots Documentation Section -->
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
                        <th class="col-shares"><?= is_rtl() ? 'الحصة / السهم' : 'Share / Lot' ?></th>
                        <th class="col-date"><?= is_rtl() ? 'تاريخ بداية الاشتراك (باليوم والشهر والسنة)' : 'Start Date (Full Date)' ?></th>
                        <th class="col-due"><?= is_rtl() ? 'يوم الاستحقاق الشهري' : 'Due Day' ?></th>
                        <th class="col-sixmo"><?= is_rtl() ? 'شرط مرور 6 أشهر' : '6 Months Rule' ?></th>
                        <th class="col-found"><?= is_rtl() ? 'سداد 500 ريال تأسيس' : '500 SAR Founding' ?></th>
                        <th class="col-status"><?= is_rtl() ? 'أهلية القرض' : 'Loan Eligibility' ?></th>
                        <th class="col-actions"><?= is_rtl() ? 'الإجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lots as $idx => $lot): 
                    $elig = \App\Models\ShareLot::eligibilityDetails($lot, $member, $founding ?? null);
                ?>
                    <tr>
                        <td class="col-id font-numeric font-bold text-slate-700">#<?= $idx + 1 ?></td>
                        <td class="col-shares font-numeric font-bold text-slate-900">
                            <span class="inline-flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-purple-700"><?= is_rtl() ? 'السهم رقم ' . ($idx + 1) : 'Share #' . ($idx + 1) ?></span>
                                <span class="text-[11px] text-slate-500 font-normal">(<?= number_format($lot['shares_count']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?>)</span>
                            </span>
                        </td>
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
                        <td class="col-actions">
                            <button type="button" class="btn btn-sm btn-outline-purple inline-flex items-center gap-1.5 py-1 px-2.5 rounded-lg text-xs font-bold border border-purple-300 text-purple-700 hover:bg-purple-50 transition-colors shadow-2xs" data-bs-toggle="modal" data-bs-target="#editLotDateModal<?= $lot['id'] ?>">
                                <i class="bi bi-calendar-event"></i>
                                <span><?= is_rtl() ? 'تعديل التاريخ' : 'Edit Date' ?></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php foreach ($lots as $idx => $lot): ?>
        <div class="modal fade" id="editLotDateModal<?= $lot['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-2xl border-0 shadow-2xl">
                    <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                        <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                            <i class="bi bi-calendar2-range text-purple-600"></i>
                            <span><?= is_rtl() ? "تعديل تاريخ بداية اشتراك السهم رقم " . ($idx + 1) . " ({$lot['shares_count']} سهم)" : "Edit Start Date for Share #" . ($idx + 1) ?></span>
                        </h5>
                        <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/lot/' . $lot['id'] . '/date') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect_to" value="admin/subscriptions/<?= $member['id'] ?>">
                        <div class="modal-body p-4 space-y-3">
                            <div class="p-3 bg-purple-50/70 border border-purple-200/80 rounded-xl text-xs text-purple-900 leading-relaxed">
                                <i class="bi bi-info-circle-fill text-purple-600 me-1"></i>
                                <?= is_rtl() 
                                    ? 'تعديل تاريخ بداية الاشتراك باليوم والشهر والسنة يؤثر مباشرة على حساب شرط مرور 6 أشهر لأهلية القرض، وكذلك يسمح بتسجيل سداد الاشتراكات ابتداءً من هذا التاريخ.' 
                                    : 'Editing the subscription start date impacts the 6-month loan eligibility rule and allows recording payments starting from this date.' ?>
                            </div>
                            <div>
                                <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'تاريخ بداية الاشتراك الجديد (اليوم / الشهر / السنة)' : 'New Start Date' ?></label>
                                <input type="date" name="start_date" class="form-control text-sm font-numeric font-bold" value="<?= !empty($lot['created_at']) ? date('Y-m-d', strtotime($lot['created_at'])) : date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <?php if ($idx === 0): ?>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="sync_member" value="1" id="syncMemberCheck<?= $lot['id'] ?>" checked>
                                <label class="form-check-label text-xs font-bold text-slate-700" for="syncMemberCheck<?= $lot['id'] ?>">
                                    <?= is_rtl() ? 'تحديث تاريخ تسجيل المشترك في الصندوق ليطابق هذا التاريخ أيضاً' : 'Also sync member registration date to match this date' ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer border-t border-slate-100 p-3 bg-slate-50/50 flex justify-between">
                            <button type="button" class="btn btn-light text-xs font-bold px-3 py-2 rounded-xl" data-bs-dismiss="modal"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                            <button type="submit" class="btn btn-primary text-xs font-bold px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 border-0 shadow-md shadow-purple-600/20">
                                <i class="bi bi-check-lg me-1"></i>
                                <?= is_rtl() ? 'حفظ التاريخ' : 'Save Date' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Compute Balances: Overall and Per-Share Lot
$lotStats = [];
foreach ($lots as $idx => $lot) {
    $lid = (int) $lot['id'];
    $lotRows = array_filter($history, fn($r) => (int) ($r['lot_id'] ?? 0) === $lid);
    $dueSum = round(array_sum(array_map('floatval', array_column($lotRows, 'amount_due'))), 2);
    $paidSum = round(array_sum(array_map('floatval', array_column($lotRows, 'amount_paid'))), 2);
    $remSum = max(0.0, round($dueSum - $paidSum, 2));
    $lotStats[$lid] = [
        'id' => $lid,
        'idx' => $idx + 1,
        'label' => is_rtl() ? ('السهم رقم ' . ($idx + 1)) : ('Share #' . ($idx + 1)),
        'shares_count' => (int) $lot['shares_count'],
        'total_due' => $dueSum,
        'total_paid' => $paidSum,
        'total_remaining' => $remSum,
        'rows_count' => count($lotRows),
    ];
}
$totalAllDue = round(array_sum(array_map('floatval', array_column($history, 'amount_due'))), 2);
$totalAllPaid = round(array_sum(array_map('floatval', array_column($history, 'amount_paid'))), 2);
$totalAllRem = max(0.0, round($totalAllDue - $totalAllPaid, 2));
?>

<!-- Subscriptions Ledger Table (سجل الاشتراكات والدفعات) -->
<div class="card-panel">
    <div class="panel-head flex items-center justify-between flex-wrap gap-2">
        <h3 class="flex items-center gap-2">
            <i class="bi bi-clock-history text-emerald-600"></i>
            <span><?= is_rtl() ? 'سجل الاشتراكات وحركة الدفعات' : 'Subscriptions & Payment Ledger' ?></span>
        </h3>
        <span class="badge-status badge-info font-numeric font-bold" id="adminVisibleRowsBadge"><?= count($history) ?> <?= is_rtl() ? 'سجلات' : 'records' ?></span>
    </div>

    <!-- Overall Balances Overview Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200/80 mb-4">
        <div class="text-center p-2.5 rounded-xl bg-white border border-slate-100 shadow-xs">
            <div class="text-xs text-slate-500 mb-0.5"><?= is_rtl() ? 'إجمالي المبالغ المستحقة' : 'Total Amount Due' ?></div>
            <div class="font-numeric font-bold text-slate-900 text-lg"><?= money($totalAllDue) ?></div>
        </div>
        <div class="text-center p-2.5 rounded-xl bg-white border border-slate-100 shadow-xs">
            <div class="text-xs text-slate-500 mb-0.5"><?= is_rtl() ? 'إجمالي المبالغ المسددة' : 'Total Amount Paid' ?></div>
            <div class="font-numeric font-bold text-emerald-600 text-lg"><?= money($totalAllPaid) ?></div>
        </div>
        <div class="text-center p-2.5 rounded-xl bg-white border border-slate-100 shadow-xs">
            <div class="text-xs text-slate-500 mb-0.5"><?= is_rtl() ? 'إجمالي المبالغ المتبقية (الرصيد)' : 'Total Remaining Balance' ?></div>
            <div class="font-numeric font-bold <?= $totalAllRem > 0 ? 'text-rose-600' : 'text-emerald-700' ?> text-lg"><?= money($totalAllRem) ?></div>
        </div>
    </div>

    <!-- Per-Lot Balances Overview (if 2+ lots) -->
    <?php if (count($lots) > 1): ?>
    <div class="mb-4">
        <div class="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1.5">
            <i class="bi bi-pie-chart text-purple-600"></i>
            <span><?= is_rtl() ? 'أرصدة كل سهم على حدة:' : 'Balances Per Share Lot:' ?></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <?php foreach ($lotStats as $lid => $ls): ?>
            <div class="p-3 bg-purple-50/40 rounded-xl border border-purple-100 flex items-center justify-between">
                <div>
                    <div class="font-bold text-xs text-purple-900 flex items-center gap-1">
                        <span><?= e($ls['label']) ?></span>
                        <span class="text-[11px] text-purple-600 font-normal">(<?= number_format($ls['shares_count']) ?> سهم)</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                        <?= is_rtl() ? 'مستحق: ' . money($ls['total_due']) . ' | مسدد: ' . money($ls['total_paid']) : 'Due: ' . money($ls['total_due']) ?>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge font-numeric <?= $ls['total_remaining'] > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-800' ?> text-xs font-bold px-2.5 py-1 rounded-lg">
                        <?= is_rtl() ? 'متبقي ' . money($ls['total_remaining']) : money($ls['total_remaining']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lot Filter Tabs / Buttons -->
    <?php if (count($lots) > 1): ?>
    <div class="flex items-center gap-1.5 flex-wrap pb-3 mb-3 border-b border-slate-100">
        <span class="text-xs font-bold text-slate-500 me-1"><i class="bi bi-funnel"></i> <?= is_rtl() ? 'تصفية حسب السهم:' : 'Filter Lot:' ?></span>
        <button type="button" class="btn btn-xs btn-admin-lot-tab active" data-lot-id="all" onclick="filterAdminLots('all', this)">
            <?= is_rtl() ? 'جميع الأسهم (الكل)' : 'All Shares' ?>
        </button>
        <?php foreach ($lotStats as $lid => $ls): ?>
        <button type="button" class="btn btn-xs btn-admin-lot-tab" data-lot-id="<?= $lid ?>" onclick="filterAdminLots('<?= $lid ?>', this)">
            <?= e($ls['label']) ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table-modern" id="adminSubsTable">
            <thead>
                <tr>
                    <th><?= is_rtl() ? 'الشهر' : 'Month' ?></th>
                    <th><?= is_rtl() ? 'الحصة / السهم' : 'Share / Lot' ?></th>
                    <th><?= is_rtl() ? 'تاريخ السداد / الاستحقاق' : 'Payment / Due Date' ?></th>
                    <th><?= is_rtl() ? 'المبلغ المستحق' : 'Amount Due' ?></th>
                    <th><?= is_rtl() ? 'المبلغ المدفوع' : 'Amount Paid' ?></th>
                    <th><?= is_rtl() ? 'المبلغ المتبقي' : 'Amount Remaining' ?></th>
                    <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                    <th class="text-center"><?= is_rtl() ? 'الإجراءات' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $h): 
                [$l, $v] = status_badge($h['status']);
                $dueVal = (float) $h['amount_due'];
                $paidVal = (float) $h['amount_paid'];
                $remVal = max(0.0, round($dueVal - $paidVal, 2));
                $isOverdue = ($remVal > 0 && \App\Models\MonthlySubscription::effectiveDue($h) < date('Y-m-d'));
                
                // Determine lot label
                $lotIdx = false;
                if (!empty($h['lot_id'])) {
                    $lotIdx = array_search((int)$h['lot_id'], array_column($lots, 'id'));
                }
            ?>
                <tr class="admin-sub-row <?= $isOverdue ? 'bg-rose-50/30' : '' ?>" data-lot-id="<?= (int)($h['lot_id'] ?? 0) ?>">
                    <td class="fw-bold font-numeric text-slate-900"><?= e(month_label($h['month'])) ?></td>
                    <td class="text-slate-600 text-xs">
                        <?php if ($lotIdx !== false): ?>
                            <span class="font-bold text-purple-700"><?= is_rtl() ? 'السهم رقم ' . ($lotIdx + 1) : 'Share #' . ($lotIdx + 1) ?></span>
                            <span class="text-slate-400 font-numeric">(<?= number_format($h['shares_count_snapshot']) ?>)</span>
                        <?php elseif ($h['lot_id']): ?>
                            <span class="font-bold">#<?= (int)$h['lot_id'] ?></span>
                            <span class="text-slate-400 font-numeric">(<?= number_format($h['shares_count_snapshot']) ?>)</span>
                        <?php else: ?>
                            <span class="text-slate-400">-</span>
                        <?php endif; ?>
                        <?php if (!empty($h['lot_status']) && $h['lot_status'] !== 'active'): ?>
                            <small class="d-block text-danger font-bold"><?= is_rtl() ? 'دفعة ملغاة' : 'Cancelled Lot' ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="font-numeric text-slate-700">
                        <?php if ($paidVal > 0 && !empty($h['payment_date'])): ?>
                            <div class="font-bold text-slate-900">
                                <i class="bi bi-calendar2-check text-emerald-600 me-1"></i>
                                <bdi dir="ltr"><?= date_ar($h['payment_date']) ?></bdi>
                            </div>
                            <div class="text-[11px] text-slate-400"><?= is_rtl() ? 'تاريخ السداد' : 'Payment Date' ?></div>
                        <?php else: ?>
                            <div class="font-bold text-slate-700">
                                <i class="bi bi-calendar-event text-slate-400 me-1"></i>
                                <bdi dir="ltr"><?= date_ar($h['due_date']) ?></bdi>
                            </div>
                            <div class="text-[11px] text-slate-400"><?= is_rtl() ? 'موعد الاستحقاق' : 'Due Date' ?></div>
                        <?php endif; ?>
                        <?php if ($graceVisible && !empty($h['grace_until']) && $h['status'] !== 'paid' && $h['grace_until'] >= date('Y-m-d')): ?>
                            <small class="d-block text-slate-400 text-[10px]"><?= is_rtl() ? 'مهلة حتى ' . date_ar($h['grace_until']) : 'Grace until ' . date_ar($h['grace_until']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="font-numeric font-bold text-slate-900"><?= money($dueVal) ?></td>
                    <td class="font-numeric font-bold <?= $paidVal > 0 ? 'text-emerald-600' : 'text-slate-400' ?>"><?= money($paidVal) ?></td>
                    <td class="font-numeric font-bold <?= $remVal > 0 ? 'text-rose-600' : 'text-emerald-700' ?>">
                        <?= money($remVal) ?>
                    </td>
                    <td>
                        <span class="badge-status badge-<?= $v ?>"><?= $l ?></span>
                        <?php if ($h['status'] === 'partial' && $isOverdue): ?>
                            <small class="d-block text-rose-600 font-bold text-[11px] mt-0.5" title="<?= is_rtl() ? 'تجاوز موعد الاستحقاق ولديه متبقي' : 'Overdue' ?>">
                                <i class="bi bi-clock-history"></i> <?= is_rtl() ? 'متأخر' : 'Overdue' ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-1.5">
                            <?php if ($remVal > 0): ?>
                                <!-- Quick Pay / Partial Pay Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" data-bs-toggle="modal" data-bs-target="#paySubModal<?= $h['id'] ?>" title="<?= is_rtl() ? 'تسجيل سداد / دفعة' : 'Record Payment' ?>">
                                    <i class="bi bi-credit-card-2-front"></i>
                                    <span><?= is_rtl() ? 'سداد' : 'Pay' ?></span>
                                </button>
                            <?php endif; ?>

                            <?php if ($paidVal > 0): ?>
                                <!-- Edit Paid Amount Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" data-bs-toggle="modal" data-bs-target="#editSubPayModal<?= $h['id'] ?>" title="<?= is_rtl() ? 'تعديل المبلغ المسدد' : 'Edit Paid Amount' ?>">
                                    <i class="bi bi-pencil-square"></i>
                                    <span><?= is_rtl() ? 'تعديل' : 'Edit' ?></span>
                                </button>
                                <!-- Cancel Payment Form -->
                                <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/reset-payment/' . $h['id']) ?>" onsubmit="return confirm('<?= is_rtl() ? 'هل أنت متأكد من إلغاء سداد اشتراك شهر ' . e(month_label($h['month'])) . '؟ سيتم إعادة حالة الشهر إلى غير مسدد وحذف المعاملة المالية المرتبطة بالكامل.' : 'Are you sure you want to cancel payment for month ' . e(month_label($h['month'])) . '?' ?>');" class="d-inline m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" title="<?= is_rtl() ? 'إلغاء السداد وحذف المعاملة' : 'Cancel Payment' ?>">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        <span><?= is_rtl() ? 'إلغاء' : 'Reset' ?></span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <tr><td colspan="8"><div class="empty-state py-6"><i class="bi bi-calendar-x text-2xl text-slate-400 mb-2"></i><?= is_rtl() ? 'لا يوجد سجل اشتراكات بعد' : 'No subscriptions recorded yet' ?></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals for Paying Unpaid / Partial Rows -->
<?php foreach ($history as $h): 
    $dueVal = (float) $h['amount_due'];
    $paidVal = (float) $h['amount_paid'];
    $remVal = max(0.0, round($dueVal - $paidVal, 2));
    if ($remVal > 0):
?>
<div class="modal fade" id="paySubModal<?= $h['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                    <i class="bi bi-cash-coin text-emerald-600"></i>
                    <span><?= is_rtl() ? "تسجيل سداد لاشتراك شهر " . month_label($h['month']) . "" : "Record Payment for " . month_label($h['month']) . "" ?></span>
                </h5>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="single_pay">
                <input type="hidden" name="sub_id" value="<?= $h['id'] ?>">
                <div class="modal-body p-4 space-y-3">
                    <!-- Summary info box -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-600 font-bold"><?= is_rtl() ? 'الشهر المستحق:' : 'Month:' ?></span>
                            <span class="font-numeric font-extrabold text-slate-900"><?= e(month_label($h['month'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 font-bold"><?= is_rtl() ? 'المبلغ المستحق:' : 'Amount Due:' ?></span>
                            <span class="font-numeric font-extrabold text-slate-900"><?= money($dueVal) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 font-bold"><?= is_rtl() ? 'المسدد سابقاً:' : 'Previously Paid:' ?></span>
                            <span class="font-numeric font-extrabold text-emerald-600"><?= money($paidVal) ?></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-slate-200">
                            <span class="text-rose-700 font-bold"><?= is_rtl() ? 'المبلغ المتبقي للسداد:' : 'Remaining Balance:' ?></span>
                            <span class="font-numeric font-extrabold text-rose-700 text-sm"><?= money($remVal) ?></span>
                        </div>
                    </div>

                    <!-- Payment Type Selection -->
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'نوع السداد' : 'Payment Type' ?></label>
                        <div class="space-y-2">
                            <div class="form-check p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/50 flex items-center gap-2">
                                <input class="form-check-input m-0" type="radio" name="pay_type" id="payTypeFull<?= $h['id'] ?>" value="full" checked onchange="document.getElementById('partialInputBox<?= $h['id'] ?>').classList.add('hidden')">
                                <label class="form-check-label text-xs font-bold text-slate-800 cursor-pointer flex-1" for="payTypeFull<?= $h['id'] ?>">
                                    <?= is_rtl() ? 'سداد كامل المتبقي (' . money($remVal) . ')' : 'Full Remaining Payment (' . money($remVal) . ')' ?>
                                </label>
                            </div>
                            <div class="form-check p-2.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center gap-2">
                                <input class="form-check-input m-0" type="radio" name="pay_type" id="payTypePartial<?= $h['id'] ?>" value="partial" onchange="document.getElementById('partialInputBox<?= $h['id'] ?>').classList.remove('hidden')">
                                <label class="form-check-label text-xs font-bold text-slate-800 cursor-pointer flex-1" for="payTypePartial<?= $h['id'] ?>">
                                    <?= is_rtl() ? 'سداد دفعة جزئية (تحديد مبلغ محدد)' : 'Partial Payment (Specify Amount)' ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Box (hidden by default) -->
                    <div id="partialInputBox<?= $h['id'] ?>" class="hidden">
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'مبلغ الدفعة الجزئية' : 'Partial Amount' ?></label>
                        <input type="number" step="0.01" min="0.01" max="<?= $remVal ?>" name="amount" class="form-control text-sm font-numeric font-bold" placeholder="0.00">
                        <p class="text-[11px] text-slate-400 mb-0 mt-1"><?= is_rtl() ? 'الحد الأقصى للدفعة الجزئية: ' . money($remVal) : 'Maximum: ' . money($remVal) ?></p>
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'تاريخ السداد (يوم/شهر/سنة)' : 'Payment Date (Day/Month/Year)' ?></label>
                        <input type="date" name="payment_date" class="form-control text-sm font-numeric font-bold" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-t border-slate-100 p-3 bg-slate-50/50 flex justify-between">
                    <button type="button" class="btn btn-light text-xs font-bold px-3 py-2 rounded-xl" data-bs-dismiss="modal"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                    <button type="submit" class="btn btn-primary text-xs font-bold px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 border-0 shadow-md shadow-emerald-600/20">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <?= is_rtl() ? 'تأكيد السداد' : 'Confirm Payment' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; endforeach; ?>

<!-- Modals for Editing Existing Payments -->
<?php foreach ($history as $h): if ((float) $h['amount_paid'] > 0): ?>
<div class="modal fade" id="editSubPayModal<?= $h['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                    <i class="bi bi-pencil-square text-emerald-600"></i>
                    <span><?= is_rtl() ? "تعديل سداد اشتراك شهر " . month_label($h['month']) . "" : "Edit Payment for Month " . month_label($h['month']) . "" ?></span>
                </h5>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/update-payment/' . $h['id']) ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4 space-y-3">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <span class="text-slate-600 font-bold"><?= is_rtl() ? 'المبلغ المستحق لهذا الشهر:' : 'Amount Due:' ?></span>
                        <span class="font-numeric font-extrabold text-slate-900"><?= money($h['amount_due']) ?></span>
                    </div>
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'تاريخ السداد (يوم/شهر/سنة)' : 'Payment Date (Day/Month/Year)' ?></label>
                        <input type="date" name="payment_date" class="form-control text-sm font-numeric font-bold" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'المبلغ المسدد الفعلي' : 'Actual Paid Amount' ?></label>
                        <input type="number" step="0.01" min="0" max="<?= (float) $h['amount_due'] ?>" name="amount_paid" class="form-control text-sm font-numeric font-bold" value="<?= (float) $h['amount_paid'] ?>" required>
                        <p class="text-[11px] text-muted mb-0 mt-1.5">
                            <?= is_rtl() ? 'إذا جعلت المبلغ 0، سيتم إلغاء السداد بالكامل وإعادة حالة الشهر إلى غير مسدد وحذف الحركة المالية.' : 'Setting the amount to 0 will reset the month to unpaid and delete the financial transaction.' ?>
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-t border-slate-100 p-3 bg-slate-50/50 flex justify-between">
                    <button type="button" class="btn btn-light text-xs font-bold px-3 py-2 rounded-xl" data-bs-dismiss="modal"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                    <button type="submit" class="btn btn-primary text-xs font-bold px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 border-0 shadow-md shadow-emerald-600/20">
                        <i class="bi bi-check-lg me-1"></i>
                        <?= is_rtl() ? 'حفظ التعديل' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; endforeach; ?>

<script>
function filterAdminLots(lotId, btn) {
    document.querySelectorAll('.btn-admin-lot-tab').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const rows = document.querySelectorAll('.admin-sub-row');
    rows.forEach(r => {
        const rLot = r.getAttribute('data-lot-id');
        if (lotId === 'all' || rLot === String(lotId)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>
<style>
.btn-admin-lot-tab {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-weight: 700;
    border-radius: 8px;
    padding: 3px 10px;
    font-size: 11px;
    transition: all 0.15s;
}
.btn-admin-lot-tab:hover {
    background: #f1f5f9;
}
.btn-admin-lot-tab.active {
    background: #6366f1;
    color: #fff;
    border-color: #6366f1;
}
</style>

