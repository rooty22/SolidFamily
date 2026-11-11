<?php
/**
 * The name / cost / date fields of the expenses log, shared by the "add" card and the edit page.
 *
 * @var string     $formAction where the form posts
 * @var array|null $expense    the expense being edited (null when adding)
 */
$isEn = !is_rtl();
$isEdit = !empty($expense);
?>
<style>
    .exp-form { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; align-items: end; }
    @media (min-width: 992px) { .exp-form { grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr) minmax(0, 0.95fr) auto; } }
    .exp-form .form-label { display: flex; align-items: center; gap: 6px; margin-bottom: 7px; }
    .exp-form .form-label .req { color: #e11d48; }
    .exp-box { position: relative; }
    .exp-box .exp-ico { position: absolute; inset-inline-start: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none; z-index: 3; transition: color .2s ease; }
    .exp-box:focus-within .exp-ico { color: #0284c7; }
    .exp-box .exp-suffix { position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: #e2e8f0; color: #475569; font-size: 11.5px; font-weight: 800; padding: 5px 11px; border-radius: 9px; pointer-events: none; z-index: 3; }
    /* physical sides on purpose: the date picker's own input is always dir="ltr", so logical padding would flip there */
    html[dir="rtl"] .exp-box .form-control { padding-right: 44px !important; padding-left: 14px !important; }
    html[dir="ltr"] .exp-box .form-control { padding-left: 44px !important; padding-right: 14px !important; }
    html[dir="rtl"] .exp-box.has-suffix .form-control { padding-left: 74px !important; }
    html[dir="ltr"] .exp-box.has-suffix .form-control { padding-right: 74px !important; }
    /* the date picker's own input is dir="ltr": line its text up with the other fields (next to the icon) */
    html[dir="rtl"] .exp-box input.form-control[dir="ltr"] { text-align: right; }
    /* a cost is typed, not stepped: no spinner arrows */
    .exp-box input[type="number"]::-webkit-inner-spin-button, .exp-box input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .exp-box input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
    .exp-submit { height: 48px; padding-inline: 28px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; white-space: nowrap; }
    @media (max-width: 991px) { .exp-submit { width: 100%; } }
</style>

<form method="post" action="<?= e($formAction) ?>" class="exp-form" autocomplete="off">
    <?= csrf_field() ?>

    <div>
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Expense name' : 'اسم المصروف' ?> <span class="req">*</span></label>
        <div class="exp-box">
            <i class="bi bi-tag exp-ico"></i>
            <input type="text" name="name" class="form-control" maxlength="150" required
                   value="<?= e($expense['name'] ?? '') ?>"
                   placeholder="<?= $isEn ? 'e.g. Bank transfer fees' : 'مثال: رسوم تحويل بنكي' ?>">
        </div>
    </div>

    <div>
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Cost' : 'تكلفة المصروف' ?> <span class="req">*</span></label>
        <div class="exp-box has-suffix">
            <i class="bi bi-cash-coin exp-ico"></i>
            <input type="number" name="amount" class="form-control font-numeric" step="0.01" min="0.01" inputmode="decimal" required
                   value="<?= e(isset($expense['amount']) ? (string) $expense['amount'] : '') ?>" placeholder="0.00">
            <span class="exp-suffix"><?= __('currency') ?></span>
        </div>
    </div>

    <div>
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Date' : 'التاريخ' ?></label>
        <div class="exp-box">
            <i class="bi bi-calendar-event exp-ico"></i>
            <input type="date" name="expense_date" class="form-control font-numeric" value="<?= e($expense['expense_date'] ?? date('Y-m-d')) ?>">
        </div>
    </div>

    <div>
        <button type="submit" class="btn btn-primary exp-submit">
            <i class="bi bi-<?= $isEdit ? 'check2-circle' : 'plus-lg' ?>"></i>
            <span><?= $isEdit ? ($isEn ? 'Save changes' : 'حفظ التعديلات') : ($isEn ? 'Add expense' : 'إضافة المصروف') ?></span>
        </button>
    </div>
</form>
