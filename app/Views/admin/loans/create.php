<?php
$isEn = is_en();
$defaultAmount = !empty($loanRequest['amount_requested']) ? (float)$loanRequest['amount_requested'] : 10000;
$defaultMonths = !empty($loanRequest['installments_months']) ? (int)$loanRequest['installments_months'] : 12;
$defaultFee = (float)($feePercent ?? 0);
?>
<div class="space-y-6" x-data="{
    amount: <?= $defaultAmount ?>,
    months: <?= $defaultMonths ?>,
    feePercent: <?= $defaultFee ?>,
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
}">
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
                    <?php endif; ?>

                    <!-- Section 1: Member & Timeline -->
                    <div class="border-b border-slate-100 pb-5 mb-5">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span><?= $isEn ? 'Member & Purpose' : 'المشترك وتفاصيل الطلب' ?></span>
                        </h4>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Family Member' : 'المشترك المستفيد' ?> <span class="text-rose-500">*</span></label>
                                <select name="member_id" class="form-select text-sm" required <?= $loanRequest ? 'disabled' : '' ?>>
                                    <option value=""><?= $isEn ? '-- Select Member --' : '-- اختر المشترك المستفيد --' ?></option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['id'] ?>" <?= ($loanRequest && $loanRequest['member_id'] == $m['id']) ? 'selected' : '' ?>>
                                            <?= e($m['name']) ?> (<?= e($m['mobile']) ?>) - <?= e($m['national_id'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($loanRequest): ?>
                                    <input type="hidden" name="member_id" value="<?= $loanRequest['member_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Loan Issue Date' : 'تاريخ بدء القرض' ?> <span class="text-rose-500">*</span></label>
                                <input type="date" name="loan_date" class="form-control text-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>

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
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                        <button type="submit" class="btn btn-primary px-5 py-2.5 font-bold shadow-sm inline-flex items-center gap-2">
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
