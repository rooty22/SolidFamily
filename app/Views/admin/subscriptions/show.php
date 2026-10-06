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
                    <div class="col-7">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'تاريخ السداد / الشهر الأول (يوم/شهر/سنة)' : 'Start Date (Day/Month/Year)' ?></label>
                        <input type="date" name="start_date" class="form-control font-numeric font-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'عدد الأشهر' : 'Months Count' ?></label>
                        <input type="number" name="months_count" class="form-control font-numeric" value="1" min="1" max="24">
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
                    <div class="col-7">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'تاريخ الدفعة / الشهر (يوم/شهر/سنة)' : 'Payment Date / Month (Day/Month/Year)' ?></label>
                        <input type="date" name="partial_date" class="form-control font-numeric font-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label font-bold text-xs"><?= is_rtl() ? 'قيمة الدفعة' : 'Amount' ?></label>
                        <input type="number" step="0.01" min="0.01" name="partial_amount" class="form-control font-numeric" placeholder="0.00" required>
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
                        <th class="col-actions"><?= is_rtl() ? 'الإجراءات' : 'Actions' ?></th>
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

        <?php foreach ($lots as $lot): ?>
        <div class="modal fade" id="editLotDateModal<?= $lot['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-2xl border-0 shadow-2xl">
                    <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                        <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                            <i class="bi bi-calendar2-range text-purple-600"></i>
                            <span><?= is_rtl() ? "تعديل تاريخ بداية اشتراك الحصة #{$lot['id']} ({$lot['shares_count']} سهم)" : "Edit Start Date for Lot #{$lot['id']}" ?></span>
                        </h5>
                        <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/lot/' . $lot['id'] . '/date') ?>">
                        <?= csrf_field() ?>
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
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="sync_member" value="1" id="syncMemberCheck<?= $lot['id'] ?>" checked>
                                <label class="form-check-label text-xs font-bold text-slate-700" for="syncMemberCheck<?= $lot['id'] ?>">
                                    <?= is_rtl() ? 'تحديث تاريخ تسجيل المشترك في الصندوق ليطابق هذا التاريخ أيضاً' : 'Also sync member registration date to match this date' ?>
                                </label>
                            </div>
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

<div class="card-panel">
    <div class="panel-head"><h3><i class="bi bi-clock-history"></i> سجل الاشتراكات</h3></div>
    <table class="table-modern">
        <thead>
            <tr>
                <th>الشهر</th>
                <th>الدفعة</th>
                <th>موعد الاستحقاق</th>
                <th>المستحق</th>
                <th>المسدد</th>
                <th>الحالة</th>
                <th class="text-center"><?= is_rtl() ? 'الإجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $h): [$l, $v] = status_badge($h['status']); ?>
            <tr>
                <td class="fw-bold font-numeric"><?= e($h['month']) ?></td>
                <td class="text-muted" style="font-size:12.5px;"><?= $h['lot_id'] ? '#' . $h['lot_id'] . ' (' . number_format($h['shares_count_snapshot']) . ' سهم)' : '-' ?>
                    <?php if (!empty($h['lot_status']) && $h['lot_status'] !== 'active'): ?><small class="d-block text-danger">دفعة ملغاة</small><?php endif; ?></td>
                <td class="font-numeric"><?= date_ar($h['due_date']) ?>
                    <?php if ($graceVisible && !empty($h['grace_until']) && $h['status'] !== 'paid' && $h['grace_until'] >= date('Y-m-d')): ?><small class="d-block text-muted">مهلة حتى <?= date_ar($h['grace_until']) ?></small><?php endif; ?></td>
                <td class="font-numeric"><?= money($h['amount_due']) ?></td>
                <td class="font-numeric font-bold <?= (float)$h['amount_paid'] > 0 ? 'text-emerald-600' : '' ?>"><?= money($h['amount_paid']) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-center">
                    <?php if ((float) $h['amount_paid'] > 0): ?>
                        <div class="d-inline-flex align-items-center gap-1.5">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" data-bs-toggle="modal" data-bs-target="#editSubPayModal<?= $h['id'] ?>" title="<?= is_rtl() ? 'تعديل المبلغ المسدد' : 'Edit Paid Amount' ?>">
                                <i class="bi bi-pencil-square"></i>
                                <span><?= is_rtl() ? 'تعديل' : 'Edit' ?></span>
                            </button>
                            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/reset-payment/' . $h['id']) ?>" onsubmit="return confirm('<?= is_rtl() ? 'هل أنت متأكد من إلغاء سداد اشتراك شهر ' . e($h['month']) . '؟ سيتم إعادة حالة الشهر إلى غير مسدد وحذف المعاملة المالية المرتبطة بالكامل.' : 'Are you sure you want to cancel payment for month ' . e($h['month']) . '?' ?>');" class="d-inline m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" title="<?= is_rtl() ? 'إلغاء السداد وحذف المعاملة' : 'Cancel Payment' ?>">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span><?= is_rtl() ? 'إلغاء السداد' : 'Reset' ?></span>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span class="text-slate-400 text-xs">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($history)): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-calendar-x"></i>لا يوجد سجل بعد</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php foreach ($history as $h): if ((float) $h['amount_paid'] > 0): ?>
<div class="modal fade" id="editSubPayModal<?= $h['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                    <i class="bi bi-pencil-square text-emerald-600"></i>
                    <span><?= is_rtl() ? "تعديل سداد اشتراك شهر {$h['month']}" : "Edit Payment for Month {$h['month']}" ?></span>
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
