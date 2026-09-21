<?php 
[$label, $variant] = status_badge($request['status']); 
$months = max(1, (int) $request['installments_months']);
$monthlyEstimate = (float) $request['amount_requested'] / $months;
$isWithinLimit = (float) $request['amount_requested'] <= $maxEligibleLoan;
?>

<div class="space-y-6">
    <!-- Top Action Breadcrumb Bar -->
    <div class="card-panel flex flex-col sm:flex-row sm:items-center justify-between gap-4 !p-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= url('admin/loan-requests') ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all shadow-xs" title="<?= __('loan_requests') ?>">
                <i class="bi <?= is_rtl() ? 'bi-arrow-right' : 'bi-arrow-left' ?> text-lg"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 m-0"><?= __('loan_request_title', ['id' => $request['id']]) ?></h1>
                    <span class="badge-status badge-<?= $variant ?>"><?= $label ?></span>
                    <?php if ($queuePosition): ?>
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 inline-flex items-center gap-1 font-numeric">
                            <i class="bi bi-hourglass-split text-amber-600"></i>
                            <span><?= __('queue_order', ['pos' => $queuePosition]) ?></span>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1 mb-0 font-numeric">
                    <i class="bi bi-calendar3 me-1 text-slate-400"></i>
                    <span><?= __('submission_date') ?>: <?= is_rtl() ? date_ar($request['created_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($request['created_at'])) ?></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1.5 font-bold">
                <i class="bi bi-person-badge text-sky-600"></i>
                <span><?= __('full_member_profile') ?></span>
            </a>
        </div>
    </div>

    <!-- 2-Column Responsive Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column (Financing Details & Decision) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            
            <!-- Loan Request Details Card -->
            <div class="card-panel space-y-6">
                <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm shadow-xs">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <h2 class="text-base font-bold text-slate-900 m-0"><?= __('requested_financing_data') ?></h2>
                        </div>
                        <p class="text-xs text-slate-500 mb-0"><?= __('loan_details_and_term') ?></p>
                    </div>
                    <div class="text-start sm:text-end">
                        <span class="text-xs text-slate-400 block font-medium"><?= __('requested_amount') ?></span>
                        <span class="text-2xl font-black text-emerald-600 font-numeric tracking-tight"><?= money($request['amount_requested']) ?></span>
                    </div>
                </div>

                <!-- 3 Metric Tiles -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Term -->
                    <div class="metric-tile metric-blue">
                        <div class="metric-label"><?= __('requested_term_months') ?></div>
                        <div class="metric-value font-numeric text-sky-950">
                            <?= __('months_count_label', ['count' => $request['installments_months']]) ?>
                        </div>
                    </div>

                    <!-- Monthly Estimate -->
                    <div class="metric-tile metric-green">
                        <div class="metric-label"><?= __('estimated_monthly_installment') ?></div>
                        <div class="metric-value font-numeric text-emerald-700"><?= money($monthlyEstimate) ?></div>
                    </div>

                    <!-- Reason -->
                    <div class="metric-tile metric-slate">
                        <div class="metric-label"><?= __('loan_reason_purpose') ?></div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-sky-100/70 text-sky-900 text-xs font-bold border border-sky-200/60">
                                <?= $reasonLabels[$request['reason']] ?? $request['reason'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Clarification (if any) -->
                <?php if ($request['reason'] === 'other' && !empty($request['reason_other_text'])): ?>
                    <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80">
                        <span class="text-xs font-bold text-amber-900 block mb-1.5"><?= __('additional_reason_clarification') ?></span>
                        <p class="text-xs text-amber-800 leading-relaxed mb-0"><?= e($request['reason_other_text']) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Table of Legal Specifications -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/70 text-xs font-bold text-slate-600 w-1/3"><?= __('terms_agreement_status') ?></td>
                                <td class="py-3 px-4 font-semibold text-xs">
                                    <?php if ($request['agreed_terms']): ?>
                                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold">
                                            <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                            <span><?= __('terms_agreed_ack') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 text-rose-600 font-bold">
                                            <i class="bi bi-x-circle-fill"></i>
                                            <span><?= __('terms_not_agreed') ?></span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/70 text-xs font-bold text-slate-600"><?= __('submission_date_time') ?></td>
                                <td class="py-3 px-4 text-xs font-bold text-slate-700 font-numeric">
                                    <?= is_rtl() ? date_ar($request['created_at'], 'Y-m-d H:i:s') : date('M d, Y H:i:s', strtotime($request['created_at'])) ?>
                                </td>
                            </tr>
                            <?php if (!empty($request['reviewed_at'])): ?>
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/70 text-xs font-bold text-slate-600"><?= __('review_decision_date') ?></td>
                                <td class="py-3 px-4 text-xs font-bold text-slate-700 font-numeric">
                                    <?= is_rtl() ? date_ar($request['reviewed_at'], 'Y-m-d H:i:s') : date('M d, Y H:i:s', strtotime($request['reviewed_at'])) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($request['admin_note'])): ?>
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/70 text-xs font-bold text-slate-600"><?= __('admin_notes_recorded') ?></td>
                                <td class="py-3 px-4 text-xs text-slate-700 leading-relaxed font-semibold">
                                    <?= e($request['admin_note']) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Administrative Decision Box for Pending Requests -->
            <?php if ($request['status'] === 'pending'): ?>
                <div class="card-panel space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm shadow-xs">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 m-0"><?= __('admin_decision_title') ?></h3>
                        </div>
                        <p class="text-xs text-slate-500 mb-0"><?= __('admin_decision_desc') ?></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <!-- Approval Form Card -->
                        <div class="p-5 rounded-2xl bg-emerald-50/60 border border-emerald-200/90 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-emerald-900 font-bold text-sm mb-2">
                                    <i class="bi bi-check2-circle text-emerald-600 text-lg"></i>
                                    <span><?= __('approve_request') ?></span>
                                </div>
                                <p class="text-xs text-emerald-800 leading-relaxed mb-4">
                                    <?= __('approve_request_desc') ?>
                                </p>
                            </div>
                            <form method="post" action="<?= url('admin/loan-requests/' . $request['id'] . '/approve') ?>" data-confirm="<?= __('approve_loan_confirm') ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary w-full py-2.5 px-4 font-bold text-xs shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
                                    <i class="bi bi-check-lg text-base"></i>
                                    <span><?= __('approve_and_prepare_loan') ?></span>
                                </button>
                            </form>
                        </div>

                        <!-- Rejection Form Card -->
                        <div class="p-5 rounded-2xl bg-rose-50/60 border border-rose-200/90 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-rose-900 font-bold text-sm mb-2">
                                    <i class="bi bi-x-circle text-rose-600 text-lg"></i>
                                    <span><?= __('reject_request') ?></span>
                                </div>
                                <p class="text-xs text-rose-800 leading-relaxed mb-3">
                                    <?= __('reject_request_desc') ?>
                                </p>
                            </div>
                            <form method="post" action="<?= url('admin/loan-requests/' . $request['id'] . '/reject') ?>" data-confirm="<?= __('reject_loan_confirm') ?>" class="m-0 space-y-2">
                                <?= csrf_field() ?>
                                <input type="text" name="admin_note" class="form-control text-xs py-2 px-3 bg-white" placeholder="<?= __('rejection_reason_placeholder') ?>">
                                <button type="submit" class="btn-soft-danger w-full py-2.5 px-4 justify-center text-xs font-bold">
                                    <i class="bi bi-x-lg"></i>
                                    <span><?= __('reject_request_final') ?></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column (Member Profile & Financial Standing) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-6">
            
            <!-- Member Identity & Profile Card -->
            <div class="card-panel space-y-4">
                <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-sky-600 to-emerald-500 text-white font-black text-xl flex items-center justify-center shadow-sm">
                        <?= mb_substr($member['name'], 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 m-0"><?= e($member['name']) ?></h3>
                        <span class="text-xs text-slate-500 font-numeric" dir="ltr"><?= e($member['mobile']) ?></span>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('national_id') ?></span>
                        <span class="font-bold text-slate-900 font-numeric" dir="ltr"><?= e($member['national_id'] ?? '-') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('join_date') ?></span>
                        <span class="font-bold text-slate-900 font-numeric"><?= is_rtl() ? date_ar($member['created_at'], 'Y-m-d') : date('M d, Y', strtotime($member['created_at'])) ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('membership_status') ?></span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <?= $member['status'] === 'active' ? __('active_and_compliant') : e($member['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-soft w-full justify-center py-2.5 text-xs font-bold">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span><?= __('member_full_statement') ?></span>
                    </a>
                </div>
            </div>

            <!-- Financial Standing & Eligibility Card -->
            <div class="card-panel space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 m-0"><?= __('financial_assessment_title') ?></h3>
                        <p class="text-[11px] text-slate-500 mb-0 mt-0.5"><?= __('financial_assessment_desc') ?></p>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between">
                        <span class="text-xs text-slate-600 font-medium"><?= __('subscribed_shares_count') ?></span>
                        <span class="text-sm font-bold text-slate-900 font-numeric"><?= __('shares_count_val', ['count' => $memberShares]) ?></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between">
                        <span class="text-xs text-slate-600 font-medium"><?= __('shares_nominal_value') ?></span>
                        <span class="text-sm font-bold text-slate-900 font-numeric"><?= money($memberCapital) ?></span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-950 block"><?= __('statutory_loan_ceiling') ?></span>
                            <span class="text-[10px] text-sky-700 font-semibold"><?= __('based_on_multiplier') ?></span>
                        </div>
                        <span class="text-base font-black text-sky-800 font-numeric"><?= money($maxEligibleLoan) ?></span>
                    </div>

                    <!-- Limit Indicator -->
                    <div class="p-3 rounded-xl <?= $isWithinLimit ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?> text-xs flex items-center gap-2">
                        <i class="bi <?= $isWithinLimit ? 'bi-check-circle-fill text-emerald-600' : 'bi-exclamation-triangle-fill text-rose-600' ?> text-base shrink-0"></i>
                        <span class="font-medium">
                            <?= $isWithinLimit 
                                ? __('loan_within_limit_msg')
                                : __('loan_exceeds_limit_msg') ?>
                        </span>
                    </div>
                </div>

                <!-- Past Loan History Overview -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-bold text-slate-800 block mb-2.5"><?= __('past_loans_history') ?></span>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-xs text-slate-500 block mb-1 font-medium"><?= __('active_loans_now') ?></span>
                            <span class="text-base font-bold <?= $activeLoansCount > 0 ? 'text-amber-600' : 'text-slate-800' ?> font-numeric"><?= $activeLoansCount ?></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-xs text-slate-500 block mb-1 font-medium"><?= __('fully_paid_loans') ?></span>
                            <span class="text-base font-bold text-emerald-600 font-numeric"><?= $totalLoansPaid ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
