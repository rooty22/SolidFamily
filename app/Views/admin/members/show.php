<?php 
[$statusLabel, $statusVariant] = status_badge($member['status']); 
[$foundLabel, $foundVariant] = status_badge($founding['status']); 
$catLabels = transaction_categories();
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3.5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-600 to-emerald-500 text-white font-black text-2xl flex items-center justify-center shadow-md shadow-sky-600/20">
            <?= e(mb_substr($member['name'] ?? 'M', 0, 1)) ?>
        </div>
        <div>
            <div class="flex items-center gap-2.5 mb-1 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 m-0"><?= e($member['name']) ?></h1>
                <span class="badge-status badge-<?= $statusVariant ?>"><?= $statusLabel ?></span>
                <?php if (!empty($isAdminMember)): ?>
                    <span class="badge-status badge-warning"><i class="bi bi-shield-check"></i> <?= is_rtl() ? 'حساب إداري' : 'Admin Account' ?></span>
                <?php endif; ?>
                <?php if (!empty($overdueDetails['late_sub']) && !empty($overdueDetails['late_loan'])): ?>
                    <span class="badge-status badge-danger"><?= is_rtl() ? 'متأخر (اشتراك وقرض)' : 'Overdue (Sub & Loan)' ?></span>
                <?php elseif (!empty($overdueDetails['late_sub'])): ?>
                    <span class="badge-status badge-danger"><?= is_rtl() ? 'متأخر (اشتراك شهري)' : 'Overdue (Subscription)' ?></span>
                <?php elseif (!empty($overdueDetails['late_loan'])): ?>
                    <span class="badge-status badge-warning"><?= is_rtl() ? 'متأخر (أقساط قروض)' : 'Overdue (Loan)' ?></span>
                <?php else: ?>
                    <span class="badge-status badge-success"><?= is_rtl() ? 'سداد منتظم' : 'Up to date' ?></span>
                <?php endif; ?>
            </div>
            <div class="text-xs text-slate-500 font-numeric font-medium flex items-center gap-2">
                <span dir="ltr"><?= e($member['mobile']) ?></span>
                <span>•</span>
                <span dir="ltr"><?= e($member['national_id'] ?? '-') ?></span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('admin/members/' . $member['id'] . '/edit') ?>" class="btn-soft-primary font-bold">
            <i class="bi bi-pencil-square"></i>
            <span><?= __('edit_data') ?></span>
        </a>
    </div>
</div>

<?php if (!empty($overdueDetails['is_late'])): ?>
<!-- Overdue Delay Notice Banner -->
<div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
    <div class="flex items-start sm:items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 text-lg">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <div class="font-bold text-sm">
                <?= is_rtl() ? 'تنبيه: يوجد مبالغ متأخرة على هذا المشترك' : 'Notice: This member has overdue payments' ?>
            </div>
            <div class="text-xs text-rose-700 mt-0.5 flex items-center gap-3 flex-wrap">
                <?php if ($overdueDetails['late_sub']): ?>
                    <span>
                        <i class="bi bi-calendar2-x"></i>
                        <?= is_rtl() ? "اشتراك شهري: {$overdueDetails['late_sub_months']} أشهر (" . money($overdueDetails['overdue_sub_amount']) . ")" : "Monthly Sub: {$overdueDetails['late_sub_months']} mos (" . money($overdueDetails['overdue_sub_amount']) . ")" ?>
                    </span>
                <?php endif; ?>
                <?php if ($overdueDetails['late_loan']): ?>
                    <span>
                        <i class="bi bi-cash-coin"></i>
                        <?= is_rtl() ? "أقساط قروض: {$overdueDetails['late_loan_installments']} أقساط (" . money($overdueDetails['overdue_loan_amount']) . ")" : "Loans: {$overdueDetails['late_loan_installments']} inst (" . money($overdueDetails['overdue_loan_amount']) . ")" ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <?php if ($overdueDetails['late_sub']): ?>
            <a href="<?= url('admin/subscriptions/' . $member['id']) ?>" class="btn btn-sm btn-danger font-bold text-xs inline-flex items-center gap-1">
                <i class="bi bi-wallet2"></i>
                <span><?= is_rtl() ? 'سداد الاشتراك' : 'Pay Subscription' ?></span>
            </a>
        <?php endif; ?>
        <?php if ($overdueDetails['late_loan']): ?>
            <a href="<?= url('admin/loans') ?>?q=<?= urlencode($member['name']) ?>" class="btn btn-sm btn-outline-danger font-bold text-xs inline-flex items-center gap-1">
                <i class="bi bi-cash"></i>
                <span><?= is_rtl() ? 'سداد القرض' : 'Pay Loan' ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- 4 Executive KPI Metric Tiles -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <!-- Shares -->
    <div class="metric-tile metric-blue">
        <div class="metric-label"><?= __('shares_number') ?></div>
        <div class="metric-value font-numeric text-sky-950"><?= number_format($member['shares_count']) ?></div>
    </div>

    <!-- Founding Amount -->
    <div class="metric-tile metric-slate">
        <div class="metric-label"><?= __('founding_amount_label') ?></div>
        <div class="metric-value font-numeric text-slate-900"><?= money($founding['total_required']) ?></div>
        <div class="text-[11px] font-bold text-slate-400 mt-0.5"><?= __('paid_stat_sub', ['amount' => money($founding['amount_paid'])]) ?></div>
    </div>

    <!-- Loans Count / Admin Role -->
    <div class="metric-tile metric-amber">
        <?php if (!empty($isAdminMember)): ?>
            <div class="metric-label"><?= is_rtl() ? 'صفة الحساب' : 'Account Role' ?></div>
            <div class="metric-value text-sm font-bold text-amber-800 mt-1"><?= is_rtl() ? 'معفى من القروض' : 'Exempt from Loans' ?></div>
            <div class="text-[11px] font-bold text-slate-400 mt-0.5"><?= is_rtl() ? 'حساب إداري' : 'Admin Role' ?></div>
        <?php else: ?>
            <div class="metric-label"><?= __('loans_count_stat') ?></div>
            <div class="metric-value font-numeric text-amber-700"><?= count($loans) ?></div>
        <?php endif; ?>
    </div>

    <!-- Founding Status -->
    <div class="metric-tile metric-green">
        <div class="metric-label"><?= __('founding_status_stat') ?></div>
        <div class="mt-1.5">
            <span class="badge-status badge-<?= $foundVariant ?>"><?= $foundLabel ?></span>
        </div>
    </div>
</div>

<!-- Share Lots Subscription Start Dates & Loan Eligibility (Image 1 Requirement) -->
<div class="card-panel mb-5">
    <div class="panel-head flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
        <div>
            <h3 class="flex items-center gap-2 m-0 text-base font-bold text-slate-900">
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm shadow-xs">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <span><?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك للأسهم وشروط استحقاق القرض' : 'Share Subscription Start Dates & Loan Eligibility' ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1 mb-0">
                <?= is_rtl() ? 'توثيق تاريخ بداية الاشتراك لكل سهم على حدة باليوم والشهر والسنة (لربط شرط استحقاق القرض بمرور 6 أشهر وسداد 500 ريال تأسيس لكل سهم)' : 'Documenting subscription start dates (Day, Month, Year) per share lot linked to loan eligibility (6 months & 500 SAR founding paid per share).' ?>
            </p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold font-numeric"><?= count($lots ?? []) ?> <?= is_rtl() ? 'حصص مسجلة' : 'lots' ?></span>
    </div>
    <?php if (empty($lots)): ?>
        <div class="empty-state py-6">
            <i class="bi bi-pie-chart text-3xl text-slate-400 mb-2"></i>
            <span><?= is_rtl() ? 'لا توجد حصص أسهم نشطة مسجلة' : 'No active share lots recorded' ?></span>
        </div>
    <?php else: ?>
        <div class="table-lots-box">
            <table class="table-lots-compact">
                <thead>
                    <tr>
                        <th class="col-id">#</th>
                        <th class="col-shares"><?= is_rtl() ? 'الحصة / السهم' : 'Share / Lot' ?></th>
                        <th class="col-date"><?= is_rtl() ? 'تاريخ بداية الاشتراك (يوم/شهر/سنة)' : 'Start Date (Full Date)' ?></th>
                        <th class="col-due"><?= is_rtl() ? 'يوم الاستحقاق الشهري' : 'Due Day' ?></th>
                        <th class="col-sixmo"><?= is_rtl() ? 'شرط مرور 6 أشهر' : '6-Month Rule' ?></th>
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
                        <input type="hidden" name="redirect_to" value="admin/members/<?= $member['id'] ?>">
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
                                <input class="form-check-input" type="checkbox" name="sync_member" value="1" id="syncMemberCheckMem<?= $lot['id'] ?>" checked>
                                <label class="form-check-label text-xs font-bold text-slate-700" for="syncMemberCheckMem<?= $lot['id'] ?>">
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

<div class="row g-4 mb-4">
    <!-- Personal Details Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>
                    <span><?= __('personal_info_title') ?></span>
                </h3>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                <table class="table-modern table-kv w-full">
                    <tbody>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60 w-2/5 sm:w-1/3"><?= __('email') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['email']) ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('birth_date') ?></td><td class="font-bold text-slate-900 text-xs font-numeric"><bdi dir="ltr"><?= !empty($member['birth_date']) ? date('d/m/Y', strtotime($member['birth_date'])) : '-' ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('national_address') ?></td><td class="font-bold text-slate-900 text-xs break-words"><?= e($member['national_address'] ?: '-') ?></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('bank_name') ?></td><td class="font-bold text-slate-900 text-xs"><?= e($member['bank_name'] ?: '-') ?></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('bank_account_number') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['bank_account_number'] ?: '-') ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('iban') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['iban'] ?: '-') ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('registration_date') ?></td><td class="font-bold text-slate-900 text-xs font-numeric"><bdi dir="ltr"><?= !empty($member['created_at']) ? date('d/m/Y', strtotime($member['created_at'])) : '-' ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= is_rtl() ? 'يوم استحقاق الاشتراك' : 'Subscription Due Day' ?></td><td class="font-bold text-slate-900 text-xs font-numeric">
                            <?php if ($member['subscription_due_day'] !== null): ?>
                                <?= (int) $member['subscription_due_day'] ?> <span class="text-slate-400 font-normal">(<?= is_rtl() ? 'خاص بهذا المشترك' : 'custom for this member' ?>)</span>
                            <?php else: ?>
                                <?= (int) \App\Models\Setting::get('subscription_due_day', 10) ?> <span class="text-slate-400 font-normal">(<?= is_rtl() ? 'افتراضي النظام' : 'system default' ?>)</span>
                            <?php endif; ?>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Subscriptions Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head flex items-center justify-between">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>
                    <span><?= __('latest_monthly_subscriptions') ?></span>
                </h3>
                <a href="<?= url('admin/subscriptions/' . $member['id']) ?>" class="btn btn-sm btn-soft font-bold"><?= __('view_all') ?></a>
            </div>
            <?php if (empty($subscriptions)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-calendar-x text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_subscriptions_recorded') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('month') ?></th>
                                <th><?= is_rtl() ? 'الاستحقاق' : 'Due Date' ?></th>
                                <th><?= __('due_amount') ?></th>
                                <th><?= __('paid_amount') ?></th>
                                <th><?= __('status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subscriptions as $s): [$l,$v] = status_badge($s['status']); ?>
                            <tr>
                                <td class="font-numeric font-bold text-slate-900"><?= e($s['month']) ?></td>
                                <td class="font-numeric text-xs text-slate-500"><?= date_ar($s['due_date']) ?></td>
                                <td class="font-numeric font-bold"><?= money($s['amount_due']) ?></td>
                                <td class="font-numeric font-bold text-emerald-600"><?= money($s['amount_paid']) ?></td>
                                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Loans Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head flex items-center justify-between">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <span><?= __('loans') ?></span>
                </h3>
                <?php if (empty($isAdminMember)): ?>
                    <a href="<?= url('admin/loans') ?>?q=<?= urlencode($member['name']) ?>" class="btn btn-sm btn-soft font-bold"><?= __('view_all') ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($isAdminMember)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-shield-check text-3xl text-amber-500 mb-2"></i>
                    <span class="font-bold text-slate-800 text-sm"><?= is_rtl() ? 'حساب إداري معفى من القروض' : 'Admin Account - Exempt from Loans' ?></span>
                    <small class="text-slate-500 mt-1 max-w-sm mx-auto block leading-relaxed"><?= is_rtl() ? 'هذا المشترك يحمل صفة إدارية وهو معفى تماماً من القروض والأقساط التمويلية ولا يمكن صرف قروض له.' : 'This member holds administrative status and is completely exempt from loans and financing installments.' ?></small>
                </div>
            <?php elseif (empty($loans)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-cash text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_loans_found_simple') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('value') ?></th>
                                <th><?= __('date') ?></th>
                                <th><?= __('remaining') ?></th>
                                <th><?= __('status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans as $l): [$lb,$v] = status_badge($l['status']); ?>
                            <tr>
                                <td class="font-numeric font-bold text-slate-900"><?= money($l['amount']) ?></td>
                                <td class="font-numeric text-xs text-slate-500"><bdi dir="ltr"><?= date_ar($l['loan_date']) ?></bdi></td>
                                <td class="font-numeric font-bold text-rose-600"><?= money($l['amount_remaining']) ?></td>
                                <td><a href="<?= url('admin/loans/' . $l['id']) ?>"><span class="badge-status badge-<?= $v ?>"><?= $lb ?></span></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Financial Transactions Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <span><?= __('recent_financial_transactions') ?></span>
                </h3>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-journal-x text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_transactions_found_simple') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('type') ?></th>
                                <th><?= __('amount') ?></th>
                                <th><?= __('date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold"><?= $catLabels[$t['category']] ?? $t['category'] ?></span></td>
                                <td class="font-numeric font-bold text-slate-900"><?= money($t['amount']) ?></td>
                                <td class="font-numeric text-xs text-slate-500"><bdi dir="ltr"><?= date_ar($t['transaction_date']) ?></bdi></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
