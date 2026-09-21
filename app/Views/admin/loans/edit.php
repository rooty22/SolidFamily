<?php
$isEn = is_en();
$isLocked = ((float) $loan['amount_paid'] > 0);
?>
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                <i class="bi bi-pencil-square"></i>
                <span><?= $isEn ? 'Loan Management' : 'إدارة وتعديل القروض' ?></span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                <?= $isEn ? 'Edit Loan' : 'تعديل بيانات القرض' ?> #<?= $loan['id'] ?> - <?= e($member['name']) ?>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                <?= $isEn ? 'Update loan details, purpose, and terms where permissible.' : 'تحديث بيانات القرض وأسباب التمويل وفق الضوابط المالية المعتمدة.' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/loans/' . $loan['id']) ?>" class="btn btn-soft inline-flex items-center gap-2">
                <i class="bi bi-eye"></i>
                <span><?= $isEn ? 'View Schedule' : 'عرض جدول الأقساط' ?></span>
            </a>
            <a href="<?= url('admin/loans') ?>" class="btn btn-soft inline-flex items-center gap-2">
                <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
                <span><?= $isEn ? 'Back' : 'العودة' ?></span>
            </a>
        </div>
    </div>

    <?php if ($isLocked): ?>
        <div class="alert-modern alert-warning-modern flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold block"><?= $isEn ? 'Installment Payments In Progress' : 'توجد دفعات سداد مسجلة' ?></span>
                <span class="text-xs"><?= $isEn ? 'Payments have already been made towards this loan. The loan principal and number of installments are locked to preserve ledger integrity. You can still modify the purpose description.' : 'تم تسجيل دفعات على هذا القرض، لا يمكن تعديل القيمة أو عدد الأقساط لحماية سجلات الحسابات، ويمكنك تعديل سبب القرض والوصف.' ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-panel bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="post" action="<?= url('admin/loans/' . $loan['id']) ?>">
            <?= csrf_field() ?>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Loan Amount (SAR)' : 'قيمة القرض (ريال)' ?> <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="1" name="amount" class="form-control font-numeric text-base font-bold <?= $isLocked ? 'bg-slate-100' : '' ?>" value="<?= $loan['amount'] ?>" <?= $isLocked ? 'readonly' : '' ?> required>
                    <?php if ($isLocked): ?>
                        <span class="text-[11px] text-slate-400 mt-1 block"><?= $isEn ? 'Locked due to active payment records' : 'مقفل لوجود دفعات مسددة' ?></span>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Installments Count (Months)' : 'عدد الأقساط' ?> <span class="text-rose-500">*</span></label>
                    <input type="number" name="installments_count" class="form-control font-numeric text-base font-bold <?= $isLocked ? 'bg-slate-100' : '' ?>" value="<?= $loan['installments_count'] ?>" <?= $isLocked ? 'readonly' : '' ?> min="1" max="60" required>
                    <?php if ($isLocked): ?>
                        <span class="text-[11px] text-slate-400 mt-1 block"><?= $isEn ? 'Locked due to active payment records' : 'مقفل لوجود دفعات مسددة' ?></span>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Loan Reason' : 'سبب القرض' ?> <span class="text-rose-500">*</span></label>
                    <select name="reason" class="form-select text-sm" required>
                        <?php foreach ($reasonLabels as $key => $lbl): ?>
                            <option value="<?= $key ?>" <?= $loan['reason'] === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Additional Description' : 'وصف آخر' ?></label>
                    <input type="text" name="reason_other_text" class="form-control text-sm" value="<?= e($loan['reason_other_text']) ?>">
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="btn btn-primary px-6 py-2.5 font-bold shadow-sm inline-flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span><?= $isEn ? 'Save Changes' : 'حفظ التعديلات' ?></span>
                </button>
                <a href="<?= url('admin/loans/' . $loan['id']) ?>" class="btn btn-soft px-4 py-2.5">
                    <?= $isEn ? 'Cancel' : 'إلغاء' ?>
                </a>
            </div>
        </form>
    </div>
</div>
