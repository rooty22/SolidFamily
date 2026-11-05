<?php
$isEn = is_en();
$defaultAmount = !empty($loanRequest['amount_requested']) ? (float)$loanRequest['amount_requested'] : 10000;
$defaultMonths = !empty($loanRequest['installments_months']) ? (int)$loanRequest['installments_months'] : 12;
$defaultFee = (float)($feePercent ?? 0);
$requestLotId = !empty($loanRequest['lot_id']) ? (int) $loanRequest['lot_id'] : 0;
?>
<div class="space-y-6" x-data="{
    amount: <?= $defaultAmount ?>,
    months: <?= $defaultMonths ?>,
    feePercent: <?= $defaultFee ?>,
    memberId: '<?= $loanRequest ? (int) $loanRequest['member_id'] : '' ?>',
    loanDate: '<?= date('Y-m-d') ?>',
    requestLotId: <?= $requestLotId ?>,
    lots: [],
    lotId: '<?= $requestLotId ?: '' ?>',
    loadingLots: false,
    feePaidNow: false,
    today: '<?= date('Y-m-d') ?>',
    feePaidAt: '<?= date('Y-m-d') ?>',
    feeTouched: false,
    // The commission date has no lower limit (it may be paid before or after the loan) and ends today. While the admin has
    // not picked a day himself it follows the loan date; the picker (flatpickr) is updated by hand because it reads
    // its settings only once.
    syncFeeDate() {
        const el = this.$refs.feePaidAt;
        const fp = el && el._flatpickr;
        if (!fp) { setTimeout(() => this.syncFeeDate(), 250); return; }
        if (this.loanDate > this.today) { this.feePaidNow = false; }
        fp.set('maxDate', this.today);
        if (!this.feeTouched || this.feePaidAt > this.today) {
            const d = this.loanDate && this.loanDate <= this.today ? this.loanDate : this.today;
            this.feePaidAt = d;
            fp.setDate(d, false);
        }
    },
    async loadLots() {
        this.lots = [];
        if (!this.memberId) { this.lotId = ''; return; }
        this.loadingLots = true;
        try {
            const r = await fetch('<?= url('admin/loans/member-lots') ?>/' + this.memberId + '?as_of=' + encodeURIComponent(this.loanDate), { headers: { 'Accept': 'application/json' } });
            this.lots = (await r.json()).lots || [];
        } catch (e) { this.lots = []; }
        this.loadingLots = false;
        const keep = this.lots.find(l => String(l.id) === String(this.lotId));
        if (!keep) {
            const first = this.lots.find(l => l.eligible) || this.lots[0];
            this.lotId = first ? String(first.id) : '';
        }
        // The options are re-created every time the shares are reloaded (e.g. after the loan date changes) and the browser
        // then drops the select's value although lotId is unchanged: put the chosen share back once they are rendered.
        this.$nextTick(() => { const s = this.$refs.lotSelect; if (s) s.value = this.lotId; });
    },
    get currentLot() { return this.lots.find(l => String(l.id) === String(this.lotId)) || null; },
    get monthlyInstallment() {
        let m = parseInt(this.months) || 1;
        let a = parseFloat(this.amount) || 0;
        return (a / m).toFixed(2);
    },
    get adminFeeAmount() {
        let a = parseFloat(this.amount) || 0;
        let f = parseFloat(this.feePercent) || 0;
        return ((a * f) / 100).toFixed(2);
    },
    get totalWithFee() {
        let a = parseFloat(this.amount) || 0;
        let fee = parseFloat(this.adminFeeAmount) || 0;
        return (a + fee).toFixed(2);
    }
}" x-init="if (memberId) { loadLots(); } $watch('loanDate', () => syncFeeDate()); setTimeout(() => syncFeeDate(), 300);">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold mb-2">
                <i class="bi bi-cash-coin"></i>
                <span><?= $isEn ? 'Loans & Credit Portfolio' : 'محفظة القروض والتسهيلات التكافلية' ?></span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                <?= $isEn ? 'Create & Schedule New Loan' : 'إنشاء وجدولة قرض حسن جديد' ?>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                <?= $isEn ? 'Issue a new benevolent loan, verify member terms, and automatically generate the monthly installment schedule.' : 'إصدار تمويل أسري ميسر، تعيين الأقساط، وتوليد جدول السداد التلقائي للمشترك.' ?>
            </p>
        </div>
        <a href="<?= url('admin/loans') ?>" class="btn btn-soft self-start sm:self-center inline-flex items-center gap-2">
            <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
            <span><?= $isEn ? 'Back to Loans' : 'العودة للقروض' ?></span>
        </a>
    </div>

    <!-- 2-Column Responsive Layout -->
    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card-panel bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                <form method="post" action="<?= url('admin/loans') ?>">
                    <?= csrf_field() ?>
                    <?php if ($loanRequest): ?>
                        <input type="hidden" name="loan_request_id" value="<?= $loanRequest['id'] ?>">
                        <?php if (!empty($loanRequest['lot_id'])): ?>
                            <input type="hidden" name="lot_id" value="<?= (int) $loanRequest['lot_id'] ?>">
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Section 1: Member & Timeline -->
                    <div class="border-b border-slate-100 pb-5 mb-5">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span><?= $isEn ? 'Member & Purpose' : 'المشترك وتفاصيل الطلب' ?></span>
                        </h4>

                        <div class="row g-3">
                            <?php if ($loanRequest && !empty($loanRequest['lot_id'])): ?>
                                <div class="col-12">
                                    <div class="p-2.5 rounded-xl border border-sky-200 bg-sky-50/80 flex items-center justify-between text-xs">
                                        <span class="font-bold text-sky-950 flex items-center gap-1.5">
                                            <i class="bi bi-pie-chart-fill text-sky-600"></i>
                                            <span><?= $isEn ? 'Bound Share Lot from Request:' : 'حصة الأسهم المحددة من المشترك في الطلب:' ?></span>
                                            <span class="font-numeric"><?= e(\App\Models\ShareLot::label((int) $loanRequest['member_id'], (int) $loanRequest['lot_id'])) ?></span>
                                        </span>
                                        <span class="badge-status badge-info"><?= $isEn ? 'Unmerged Share Lot' : 'حصة غير مدمجة' ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Family Member' : 'المشترك المستفيد' ?> <span class="text-rose-500">*</span></label>
                                <select name="member_id" class="form-select text-sm" x-model="memberId" @change="loadLots()" required <?= $loanRequest ? 'disabled' : '' ?>>
                                    <option value=""><?= $isEn ? '-- Select Member --' : '-- اختر المشترك المستفيد --' ?></option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['id'] ?>" <?= ($loanRequest && $loanRequest['member_id'] == $m['id']) ? 'selected' : '' ?>>
                                            <?= !empty($m['serial_no']) ? '#' . (int) $m['serial_no'] . ' - ' : '' ?><?= e($m['name']) ?> (<?= e($m['mobile']) ?>) - <?= e($m['national_id'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($loanRequest): ?>
                                    <input type="hidden" name="member_id" value="<?= $loanRequest['member_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Loan Issue Date' : 'تاريخ بدء القرض' ?> <span class="text-rose-500">*</span></label>
                                <input type="date" name="loan_date" class="form-control text-sm" x-model="loanDate" @change="loadLots()" required>
                            </div>

                            <!-- The share the loan is issued against: only ITS conditions are checked, never the member's other shares -->
                            <?php if (!$requestLotId): ?>
                            <div class="col-12" x-show="memberId" x-cloak>
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Beneficiary Share' : 'السهم المستفيد من القرض' ?> <span class="text-rose-500">*</span></label>
                                <select name="lot_id" x-ref="lotSelect" class="form-select text-sm" x-model="lotId" required>
                                    <option value=""><?= $isEn ? '-- Select the share --' : '-- اختر السهم --' ?></option>
                                    <template x-for="l in lots" :key="l.id">
                                        <option :value="String(l.id)" x-text="l.label + (l.eligible ? ' — <?= $isEn ? 'eligible' : 'مستوفٍ للشروط' ?>' : ' — <?= $isEn ? 'not eligible' : 'غير مستوفٍ' ?>')"></option>
                                    </template>
                                </select>
                                <div class="text-xs text-slate-500 mt-1" x-show="loadingLots"><?= $isEn ? 'Loading shares...' : 'جارٍ تحميل أسهم المشترك...' ?></div>
                                <div class="text-xs text-rose-600 mt-1 font-bold" x-show="!loadingLots && memberId && lots.length === 0"><?= $isEn ? 'This member has no active share.' : 'هذا المشترك لا يملك سهماً نشطاً.' ?></div>
                                <template x-if="currentLot">
                                    <div class="mt-2 p-2.5 rounded-xl border text-xs" :class="currentLot.eligible ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
                                        <div class="font-bold" x-text="currentLot.eligible ? '<?= $isEn ? 'This share meets every condition.' : 'هذا السهم مستوفٍ لجميع شروط القرض.' ?>' : '<?= $isEn ? 'This share cannot take a loan yet:' : 'لا يمكن صرف قرض على هذا السهم حالياً:' ?>'"></div>
                                        <template x-for="m in currentLot.messages" :key="m"><div x-text="m"></div></template>
                                        <div class="mt-1 text-slate-600"><?= $isEn ? 'Founding amount of this share:' : 'مبلغ تأسيس هذا السهم:' ?> <b x-text="currentLot.founding"></b></div>
                                    </div>
                                </template>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Loan Reason' : 'سبب التمويل' ?> <span class="text-rose-500">*</span></label>
                                <select name="reason" class="form-select text-sm" required>
                                    <?php foreach ($reasonLabels as $key => $lbl): ?>
                                        <option value="<?= $key ?>" <?= ($loanRequest['reason'] ?? '') === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Additional Description (if Other)' : 'وصف أو مبررات أخرى' ?></label>
                                <input type="text" name="reason_other_text" class="form-control text-sm" placeholder="<?= $isEn ? 'Optional details...' : 'تفاصيل إضافية إن وجدت...' ?>" value="<?= e($loanRequest['reason_other_text'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Financial Terms -->
                    <div class="mb-6">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">2</span>
                            <span><?= $isEn ? 'Financial Terms & Installments' : 'المحددات المالية وجدولة الأقساط' ?></span>
                        </h4>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Principal Amount (SAR)' : 'قيمة أصل القرض (ريال)' ?> <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="1" name="amount" x-model.number="amount" class="form-control font-numeric text-base font-bold" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Installments Count (Months)' : 'مدة السداد (عدد الأقساط بالأشهر)' ?> <span class="text-rose-500">*</span></label>
                                <input type="number" name="installments_count" x-model.number="months" class="form-control font-numeric text-base font-bold" min="1" max="60" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Admin Fee (%)' : 'نسبة المصاريف الإدارية %' ?></label>
                                <input type="number" step="0.01" name="admin_fee_percent" x-model.number="feePercent" class="form-control font-numeric text-base font-bold" min="0">
                            </div>

                            <!-- The commission is NOT assumed received: tick this only if it was already paid, with the day it was received -->
                            <div class="col-12" x-show="parseFloat(adminFeeAmount) > 0 && loanDate <= today" x-cloak>
                                <div class="p-3 rounded-xl border border-amber-200 bg-amber-50/60">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="fee_paid_now" value="1" id="feePaidNow" x-model="feePaidNow">
                                        <label class="form-check-label font-bold text-xs text-slate-800" for="feePaidNow">
                                            <?= $isEn ? 'The commission was already received' : 'تم استلام العمولة بالفعل' ?>
                                            (<span class="font-numeric" x-text="adminFeeAmount"></span> <?= __('currency') ?>)
                                        </label>
                                    </div>
                                    <div class="mt-2" x-show="feePaidNow" x-cloak>
                                        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Commission payment date' : 'تاريخ استلام العمولة' ?></label>
                                        <input type="date" name="fee_paid_at" x-ref="feePaidAt" class="form-control text-sm font-numeric" x-model="feePaidAt" @change="feeTouched = true" max="<?= date('Y-m-d') ?>" :required="feePaidNow">
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-1.5">
                                        <?= $isEn ? 'Leave unticked if it has not been paid yet; you can record the payment and its date later from the loan page.' : 'اتركها بدون تأشير إن لم تُسدَّد بعد؛ يمكنك تسجيل السداد وتاريخه لاحقاً من صفحة القرض أو من قائمة القروض.' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                        <button type="submit" class="btn btn-primary px-5 py-2.5 font-bold shadow-sm inline-flex items-center gap-2" <?= $requestLotId ? '' : ':disabled="memberId && !loadingLots && !(currentLot && currentLot.eligible)"' ?>>
                            <i class="bi bi-check2-circle text-lg"></i>
                            <span><?= $isEn ? 'Approve & Generate Installments' : 'اعتماد القرض وجدولة الأقساط' ?></span>
                        </button>
                        <a href="<?= url('admin/loans') ?>" class="btn btn-soft px-4 py-2.5">
                            <?= $isEn ? 'Cancel' : 'إلغاء' ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Financial Preview Sidebar Column -->
        <div class="col-lg-4">
            <div class="bg-gradient-to-b from-slate-900 to-navy-900 rounded-2xl p-6 text-white shadow-xl sticky top-24">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                        <i class="bi bi-shield-check"></i>
                        <?= $isEn ? 'Live Financial Breakdown' : 'المعاينة الحية لبيانات القرض' ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/30">
                        0% فوائد ربوية
                    </span>
                </div>

                <!-- Big Installment Display -->
                <div class="bg-white/5 rounded-xl p-4 border border-white/10 mb-4 text-center">
                    <span class="text-[11px] font-bold text-slate-400 block mb-1">
                        <?= $isEn ? 'Projected Monthly Installment' : 'القسط الشهري المتوقع' ?>
                    </span>
                    <div class="text-3xl font-black font-numeric text-emerald-400">
                        <span x-text="monthlyInstallment"></span>
                        <span class="text-xs text-white/70 font-normal"><?= __('currency') ?>/شهر</span>
                    </div>
                </div>

                <!-- Key Metrics List -->
                <div class="space-y-3 text-xs mb-6">
                    <div class="flex justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400"><?= $isEn ? 'Loan Principal:' : 'أصل مبلغ القرض:' ?></span>
                        <span class="font-bold font-numeric text-white"><span x-text="amount"></span> <?= __('currency') ?></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400"><?= $isEn ? 'Repayment Period:' : 'فترة السداد:' ?></span>
                        <span class="font-bold font-numeric text-white"><span x-text="months"></span> <?= $isEn ? 'Months' : 'أشهر' ?></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400"><?= $isEn ? 'Administrative Fee:' : 'رسوم إدارية رمزية:' ?></span>
                        <span class="font-bold font-numeric text-amber-400"><span x-text="adminFeeAmount"></span> <?= __('currency') ?> (<span x-text="feePercent"></span>%)</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-800">
                        <span class="text-slate-400"><?= $isEn ? 'Total Borrower Repayment:' : 'إجمالي السداد المطلوب:' ?></span>
                        <span class="font-bold font-numeric text-emerald-300"><span x-text="amount"></span> <?= __('currency') ?></span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-400"><?= $isEn ? 'Total Installments + Admin Fee:' : 'إجمالي الأقساط مع المصاريف الإدارية:' ?></span>
                        <span class="font-bold font-numeric text-white"><span x-text="totalWithFee"></span> <?= __('currency') ?></span>
                    </div>
                </div>

                <!-- Guarantee & Rules Notice -->
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5 text-xs text-emerald-200 flex items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-emerald-400 shrink-0 mt-0.5"></i>
                    <p class="leading-relaxed">
                        <?= $isEn ? 'Upon submission, monthly installment records will be immediately generated and bound to the member ledger.' : 'بمجرد اعتماد القرض سيتم آلياً توليد جدول الأقساط الشهرية وربطها بسجلات المشترك المالية.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
