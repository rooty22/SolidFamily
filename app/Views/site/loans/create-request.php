<?php 
$canRequest = ($shares > 0) && ($foundingMet ?? false) && ($hasEligibleLot ?? false); 
?>
<div class="page-head">
    <div>
        <p><?= __('loan_request_subtitle') ?></p>
    </div>
    <a href="<?= url('loans') ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i> <?= __('back_to_loans') ?></a>
</div>

<div class="split">
    <div class="card-panel"
         x-data="{ 
             lots: <?= htmlspecialchars(json_encode($lotsData ?? []), ENT_QUOTES, 'UTF-8') ?>,
             selectedLotId: <?= !empty($lotsData) ? (int) $lotsData[0]['id'] : 'null' ?>,
             foundingMet: <?= !empty($foundingMet) ? 'true' : 'false' ?>,
             amount: '', 
             months: 6, 
             reason: 'personal', 
             get currentLot() { 
                 return this.lots.find(l => l.id === this.selectedLotId) || (this.lots.length > 0 ? this.lots[0] : null); 
             },
             get max() { 
                 return this.currentLot ? this.currentLot.max_loan : <?= (float) $maxLoan ?>; 
             },
             get isCurrentLotEligible() {
                 return this.foundingMet && this.currentLot && this.currentLot.six_months_met;
             },
             get installment() { 
                 return (this.amount > 0 && this.months > 0) ? this.amount / this.months : 0; 
             },
             get over() { 
                 return this.max > 0 && this.amount > this.max; 
             },
             get canSubmit() {
                 return this.isCurrentLotEligible && !this.over && this.amount > 0;
             }
         }">
        <div class="panel-head"><h3><i class="bi bi-file-earmark-plus-fill"></i> <?= __('request_details') ?></h3></div>

        <?php if ($shares < 1): ?>
            <div class="tip-box mb-3"><i class="bi bi-info-circle-fill"></i> <?= __('loan_need_shares_tip', ['url' => url('share-requests')]) ?></div>
        <?php elseif (empty($foundingMet)): ?>
            <!-- Alert requested: founding amount requirement -->
            <div class="tip-box mb-3" style="border: 1.5px solid #fde68a; background: #fffbeb; border-radius: 14px; padding: 14px 18px;">
                <div class="d-flex align-items-center gap-2 font-bold mb-1" style="font-size: 14px; color: #b45309;">
                    <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg"></i>
                    <span><?= is_rtl() ? 'تنبيه مبلغ التأسيس:' : 'Founding Amount Notice:' ?></span>
                </div>
                <p class="mb-2 text-slate-700" style="font-size: 13px; line-height: 1.6;">
                    <?= is_rtl() ? 'لا يمكن طلب قرض إلا بعد سداد كامل مبلغ التأسيس (' . money($founding['total_required'] ?? ($shares * 500)) . '). المبلغ المسدد حالياً: ' . money($founding['amount_paid'] ?? 0) . '.' : 'A loan cannot be requested except after full payment of the founding amount (' . money($founding['total_required'] ?? ($shares * 500)) . ').' ?>
                </p>
                <a href="<?= url('founding') ?>" class="btn btn-warning btn-sm fw-bold">
                    <i class="bi bi-wallet2"></i> <?= is_rtl() ? 'سداد مبلغ التأسيس الآن' : 'Pay Founding Amount Now' ?>
                </a>
            </div>
        <?php elseif (empty($hasEligibleLot)): ?>
            <!-- Alert for 6-month condition -->
            <div class="tip-box mb-3" style="border: 1.5px solid #e0e7ff; background: #eef2ff; border-radius: 14px; padding: 14px 18px;">
                <div class="d-flex align-items-center gap-2 font-bold mb-1" style="font-size: 14px; color: #4338ca;">
                    <i class="bi bi-hourglass-split text-indigo-600 text-lg"></i>
                    <span><?= is_rtl() ? 'تنبيه شرط استحقاق القرض (مرور 6 أشهر):' : 'Loan Eligibility Notice (6 Months Rule):' ?></span>
                </div>
                <p class="mb-0 text-slate-700" style="font-size: 13px; line-height: 1.6;">
                    <?= is_rtl() ? 'لا يمكن تقديم طلب قرض حالياً؛ حيث تشترط لائحة الصندوق مرور 6 أشهر كاملة على تاريخ بداية الاشتراك في السهم لتأكيد أهلية الاقتراض (أقرب سهم مؤهل يكتمل بتاريخ: ' . (!empty($lotsData) ? $lotsData[0]['target_date_ar'] : '') . ').' : 'A loan cannot be requested at this time as the 6-month share subscription condition has not yet been completed.' ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('loans/request') ?>">
            <?= csrf_field() ?>
            <fieldset <?= $canRequest ? '' : 'disabled' ?>>
                <div class="row g-3">
                    <?php if (count($lots) > 1): ?>
                        <!-- Selection of share lot for unmerged shares -->
                        <div class="col-12">
                            <label class="form-label fw-bold d-flex align-items-center gap-1 text-slate-800">
                                <i class="bi bi-pie-chart-fill text-brand-600"></i>
                                <span><?= is_rtl() ? 'تحديد السهم / الحصة المراد طلب القرض عليها:' : 'Select Share Lot for Loan Application:' ?></span>
                                <span class="text-danger">*</span>
                            </label>
                            <select name="lot_id" x-model.number="selectedLotId" class="form-select font-num" required>
                                <template x-for="l in lots" :key="l.id">
                                    <option :value="l.id" x-text="`حصة أسهم #${l.id} (${l.shares_count} سهم) - [${l.six_months_met ? 'مستوفية شرط 6 أشهر' : ('متبقي ' + l.months_remaining_label)}] - سقف القرض: ${Number(l.max_loan).toLocaleString()} ريال`"></option>
                                </template>
                            </select>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-info-circle"></i>
                                <?= is_rtl() ? 'لديك عدة أسهم غير مدمجة، يرجى اختيار السهم المراد تقديم طلب القرض عليه لضبط السقف المالي والشروط المعتمدة.' : 'You have multiple unmerged shares. Choose which share lot to borrow against.' ?>
                            </div>

                            <!-- Live Selected Lot Highlights -->
                            <template x-if="currentLot">
                                <div class="mt-2.5 p-3 rounded-xl border border-slate-200/90 bg-slate-50 text-xs">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <span class="fw-bold text-slate-900 font-num" x-text="`حصة أسهم #${currentLot.id} (${currentLot.shares_count} سهم)`"></span>
                                        <span class="badge-status" :class="isCurrentLotEligible ? 'badge-success' : 'badge-warning'" x-text="isCurrentLotEligible ? 'مستوفية الشروط' : 'قيد استيفاء الشروط'"></span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 text-slate-600 font-num">
                                        <span><i class="bi bi-calendar-check text-brand-600"></i> تاريخ البداية: <b x-text="currentLot.start_date_ar"></b></span>
                                        <span><i class="bi bi-wallet2 text-emerald-600"></i> سقف القرض للحصة: <b class="text-emerald-700" x-text="Number(currentLot.max_loan).toLocaleString() + ' ريال'"></b></span>
                                        <span :class="currentLot.six_months_met ? 'text-emerald-700' : 'text-amber-700'">
                                            <i class="bi" :class="currentLot.six_months_met ? 'bi-check-circle-fill' : 'bi-hourglass-split'"></i>
                                            <span x-text="currentLot.six_months_met ? 'مكتمل مرور 6 أشهر' : ('متبقي ' + currentLot.months_remaining_label)"></span>
                                        </span>
                                    </div>
                                    <div x-show="!currentLot.six_months_met" class="text-danger fw-bold mt-1.5 pt-1.5 border-t border-slate-200/60">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <span><?= is_rtl() ? 'هذه الحصة لم تستوفِ شرط مرور 6 أشهر بعد، ولا يمكن تقديم طلب قرض عليها حالياً.' : 'This share lot has not yet completed the 6-month condition.' ?></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    <?php elseif (count($lots) === 1): ?>
                        <input type="hidden" name="lot_id" :value="selectedLotId">
                        <div class="col-12">
                            <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 d-flex align-items-center justify-content-between text-xs">
                                <div>
                                    <span class="fw-bold text-slate-800"><i class="bi bi-pie-chart text-brand-600"></i> <?= is_rtl() ? 'السهم المرتبط بطلب القرض:' : 'Linked Share Lot:' ?></span>
                                    <span class="font-num ms-1"><?= is_rtl() ? 'حصة أسهم #' . (int) $lots[0]['id'] . ' (' . number_format($lots[0]['shares_count']) . ' سهم)' : 'Lot #' . (int) $lots[0]['id'] . ' (' . number_format($lots[0]['shares_count']) . ' shares)' ?></span>
                                </div>
                                <span class="badge-status badge-info font-num"><?= is_rtl() ? 'سقف القرض: ' . money($lotsData[0]['max_loan']) : 'Max: ' . money($lotsData[0]['max_loan']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label class="form-label"><?= __('loan_amount_requested_riyal') ?></label>
                        <input type="number" step="0.01" min="1" :max="max" name="amount_requested" x-model.number="amount" class="form-control font-num" placeholder="0.00" required>
                        <div class="small mt-1" :class="over ? 'text-danger fw-bold' : 'text-muted'">
                            <?= __('max_loan_available') ?> <b class="font-num" x-text="Number(max).toLocaleString() + ' ريال'"></b>
                            <span x-show="over" class="d-block text-danger mt-0.5"><i class="bi bi-exclamation-triangle-fill"></i> <?= is_rtl() ? 'المبلغ المطلوب يتجاوز سقف القرض المسموح للحصة المحددة' : 'Amount exceeds limit for selected lot' ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('repayment_period_months') ?></label>
                        <select name="installments_months" x-model.number="months" class="form-select" required>
                            <?php foreach ([3, 6, 9, 12, 18, 24, 36, 48, 60] as $m): ?>
                                <option value="<?= $m ?>" <?= $m === 6 ? 'selected' : '' ?>><?= $m ?> <?= __('months') ?><?= $m === 12 ? ' (' . __('one_year') . ')' : ($m === 24 ? ' (' . __('two_years') . ')' : '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('loan_reason') ?></label>
                        <select name="reason" x-model="reason" class="form-select" required>
                            <?php foreach ($reasonLabels as $key => $lbl): ?>
                                <option value="<?= $key ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('estimated_monthly_installment') ?></label>
                        <div class="form-control d-flex align-items-center justify-content-between" style="background:var(--body-bg);">
                            <b class="font-num" x-text="installment.toLocaleString('en-US', {maximumFractionDigits: 2})">0</b><span class="text-muted small"><?= __('riyal_per_month') ?></span>
                        </div>
                    </div>
                    <div class="col-12" x-show="reason === 'other'" x-cloak style="display:none;">
                        <label class="form-label"><?= __('reason_description') ?></label>
                        <textarea name="reason_other_text" class="form-control" rows="2" maxlength="500" placeholder="<?= __('reason_placeholder') ?>" :required="reason === 'other'"></textarea>
                    </div>
                </div>

                <div class="mt-3 p-3" style="background:var(--body-bg);border:1px dashed var(--border-color);border-radius:12px;font-size:13px;line-height:1.9;">
                    <div class="fw-bold mb-1"><i class="bi bi-file-earmark-text"></i> <?= __('commitment_text_title') ?></div>
                    <?= nl2br(e($commitmentText)) ?>
                </div>
                <div class="form-check mt-3 mb-4">
                    <input class="form-check-input" type="checkbox" name="agreed_terms" id="agreeCheck" value="1" required>
                    <label class="form-check-label" for="agreeCheck"><?= __('agree_loan_terms_label') ?></label>
                </div>
                <button type="submit" class="btn btn-primary w-100" :disabled="!canSubmit"><i class="bi bi-send-fill"></i> <?= __('submit_loan_request') ?></button>
                <template x-if="!isCurrentLotEligible">
                    <div class="mt-2 text-danger fw-bold small text-center">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span x-show="!foundingMet"><?= is_rtl() ? 'لا يمكن طلب قرض إلا بعد سداد كامل مبلغ التأسيس' : 'Cannot request loan until founding amount is paid' ?></span>
                        <span x-show="foundingMet && currentLot && !currentLot.six_months_met"><?= is_rtl() ? 'لا يمكن طلب قرض على هذه الحصة قبل اكتمال مرور 6 أشهر' : 'Cannot request loan on this lot before 6 months have passed' ?></span>
                    </div>
                </template>
            </fieldset>
        </form>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-person-check-fill"></i> <?= __('loan_eligibility_title') ?></h3></div>
            <div class="info-list">
                <div class="info-row"><span><?= __('your_shares_count') ?></span><b class="font-num"><?= number_format($shares) ?></b></div>
                <div class="info-row"><span><?= __('your_capital_calc') ?></span><b class="font-num"><?= money($capital) ?></b></div>
                <div class="info-row"><span><?= __('max_loan_calc', ['ratio' => rtrim(rtrim(number_format($ratio, 2), '0'), '.')]) ?></span><b class="font-num text-success" x-text="Number(max).toLocaleString() + ' ريال'"><?= money($maxLoan) ?></b></div>
                <div class="info-row"><span><?= __('active_loans') ?></span><b class="font-num"><?= $runningCount ?> · <?= money($runningRemaining) ?></b></div>
                <div class="info-row"><span><?= __('pending_requests') ?></span><b class="font-num"><?= $pendingRequests ?></b></div>
            </div>

            <!-- Share Lots Start Dates & Criteria Verification -->
            <div class="mt-3 pt-3 border-t border-slate-100">
                <div class="text-xs font-bold text-slate-800 mb-2">
                    <i class="bi bi-shield-check text-brand-600"></i>
                    <?= is_rtl() ? 'شروط استحقاق القرض المعتمدة للأسهم:' : 'Statutory Loan Eligibility Conditions:' ?>
                </div>
                <?php if (!empty($lots)): ?>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($lots as $lot): 
                            $elig = \App\Models\ShareLot::eligibilityDetails($lot, $member, $founding ?? null);
                        ?>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 transition-all"
                                 :class="currentLot && currentLot.id === <?= (int) $lot['id'] ?> ? 'ring-2 ring-brand-500 bg-sky-50/50' : ''">
                                <div class="flex items-center justify-between font-bold text-slate-800 mb-1">
                                    <span><?= is_rtl() ? 'حصة أسهم #' . (int) $lot['id'] . ' (' . number_format($lot['shares_count']) . ' سهم)' : 'Lot #' . (int) $lot['id'] . ' (' . number_format($lot['shares_count']) . ' shares)' ?></span>
                                    <span class="badge-status badge-<?= $elig['is_eligible'] ? 'success' : 'secondary' ?>"><?= $elig['is_eligible'] ? (is_rtl() ? 'مستوفية' : 'Eligible') : (is_rtl() ? 'قيد الاستيفاء' : 'Pending') ?></span>
                                </div>
                                <div class="text-[11px] text-slate-500 font-num mb-1">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= is_rtl() ? 'تاريخ بداية الاشتراك:' : 'Start Date:' ?> <b><?= date_ar($elig['start_date']) ?></b>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] <?= $elig['six_months_met'] ? 'text-emerald-700' : 'text-amber-700' ?>">
                                    <i class="bi <?= $elig['six_months_met'] ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?>"></i>
                                    <span><?= $elig['six_months_met'] ? (is_rtl() ? "مكتمل مرور 6 أشهر ({$elig['months_passed']} شهر)" : '6 months passed') : (is_rtl() ? "متبقي {$elig['months_remaining_label']} (حتى " . date_ar($elig['target_date']) . ")" : "{$elig['months_remaining']} mos left") ?></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] <?= $elig['founding_met'] ? 'text-emerald-700' : 'text-amber-700' ?> mt-0.5">
                                    <i class="bi <?= $elig['founding_met'] ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i>
                                    <span><?= $elig['founding_met'] ? (is_rtl() ? 'سداد 500 ريال تأسيس للسهم (مكتمل)' : '500 SAR founding paid') : (is_rtl() ? 'سداد 500 ريال تأسيس (مسدد: ' . money($elig['founding_paid_per_share']) . ')' : 'Founding: ' . money($elig['founding_paid_per_share'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-signpost-split-fill"></i> <?= __('how_loan_process_works') ?></h3></div>
            <ol class="steps">
                <li><?= __('loan_step_1') ?></li>
                <li><?= __('loan_step_2') ?></li>
                <li><?= __('loan_step_3') ?></li>
                <li><?= __('loan_step_4') ?></li>
            </ol>
        </div>
    </div>
</div>
