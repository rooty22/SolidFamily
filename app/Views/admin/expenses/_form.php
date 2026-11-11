<?php
/**
 * The name / cost / date / member fields of the expenses log, shared by the "add" card and the edit page.
 *
 * @var string     $formAction where the form posts
 * @var array|null $expense    the expense being edited (null when adding)
 * @var array      $members    the members an expense can be linked to (Expense::memberOptions())
 */
$isEn = !is_rtl();
$isEdit = !empty($expense);
$pickerI18n = ['none' => $isEn ? 'No member (a general expense)' : 'بدون مشترك (مصروف عام)'];
?>
<style>
    .exp-form { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 16px; align-items: end; }
    .exp-form > div { grid-column: span 12; min-width: 0; }
    @media (min-width: 992px) {
        .exp-form > .c-name { grid-column: span 6; }
        .exp-form > .c-cost { grid-column: span 3; }
        .exp-form > .c-date { grid-column: span 3; }
        .exp-form > .c-member { grid-column: span 9; }
        .exp-form > .c-submit { grid-column: span 3; }
    }
    .exp-form .form-label { display: flex; align-items: center; gap: 6px; margin-bottom: 7px; }
    .exp-form .form-label .req { color: #e11d48; }
    .exp-form .form-label .opt { color: #94a3b8; font-weight: 600; font-size: 11px; }
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

    /* member picker: a button that opens a searchable list */
    .exp-picker { display: block; width: 100%; text-align: start; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
    .exp-picker.is-empty { color: #94a3b8 !important; font-weight: 500 !important; }
    .exp-box .exp-tools { position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; gap: 6px; z-index: 4; }
    .exp-tools .exp-clear { border: 0; background: #e2e8f0; color: #475569; width: 24px; height: 24px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; }
    .exp-tools .exp-clear:hover { background: #fecdd3; color: #be123c; }
    .exp-tools .exp-chev { color: #94a3b8; font-size: 13px; pointer-events: none; transition: transform .2s ease; }
    .exp-picker-open .exp-chev { transform: rotate(180deg); }
    html[dir="rtl"] .exp-box.exp-picker-box .form-control { padding-left: 70px !important; }
    html[dir="ltr"] .exp-box.exp-picker-box .form-control { padding-right: 70px !important; }
    .exp-picker-panel { position: absolute; inset-inline: 0; top: calc(100% + 6px); z-index: 60; background: #fff; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 14px 34px rgba(15, 23, 42, .16); padding: 10px; }
    .exp-picker-search { position: relative; margin-bottom: 8px; }
    .exp-picker-search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none; }
    .exp-picker-search input { width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 11px; padding: 0 12px; font-size: 13.5px; background: #f8fafc; outline: none; }
    html[dir="rtl"] .exp-picker-search input { padding-right: 36px; }
    html[dir="ltr"] .exp-picker-search input { padding-left: 36px; }
    .exp-picker-search input:focus { border-color: #10b981; background: #fff; box-shadow: 0 0 0 3px rgba(16, 185, 129, .15); }
    .exp-picker-list { list-style: none; margin: 0; padding: 0; max-height: 260px; overflow-y: auto; }
    .exp-picker-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 12px; border-radius: 10px; cursor: pointer; font-size: 13.5px; color: #0f172a; }
    .exp-picker-list li small { color: #94a3b8; font-size: 11.5px; direction: ltr; flex-shrink: 0; }
    .exp-picker-list li:hover { background: #f1f5f9; }
    .exp-picker-list li.on { background: #ecfdf5; color: #047857; font-weight: 700; }
    .exp-picker-list li.exp-none { color: #475569; font-weight: 700; border-bottom: 1px dashed #e2e8f0; border-radius: 10px 10px 0 0; margin-bottom: 4px; }
    .exp-picker-list li.exp-empty { color: #94a3b8; justify-content: center; cursor: default; }
    .exp-picker-list li.exp-empty:hover { background: transparent; }
</style>
<script>
    // Searchable member list of the expenses form. The Arabic-friendly comparison ignores diacritics and the common
    // alef / ya / ta-marbuta spelling variants, like the member search of the loan form.
    function expenseMemberPicker(members, selected, i18n) {
        return {
            members: members,
            selectedId: selected && String(selected) !== '0' ? String(selected) : '',
            open: false,
            term: '',
            norm(t) {
                return String(t || '').toLowerCase()
                    .replace(/[ً-ْـ]/g, '')
                    .replace(/[أإآ]/g, 'ا')
                    .replace(/ى/g, 'ي')
                    .replace(/ة/g, 'ه')
                    .replace(/\s+/g, ' ').trim();
            },
            get current() { return this.members.find(m => String(m.id) === this.selectedId) || null; },
            get label() { return this.current ? this.current.label : i18n.none; },
            get filtered() {
                const q = this.norm(this.term);
                if (!q) { return this.members; }
                const words = q.split(' ');
                return this.members.filter(m => words.every(w => this.norm(m.q).includes(w)));
            },
            toggle() {
                this.open = !this.open;
                if (this.open) { this.term = ''; this.$nextTick(() => { if (this.$refs.q) { this.$refs.q.focus(); } }); }
            },
            pick(m) { this.selectedId = String(m.id); this.open = false; },
            clear() { this.selectedId = ''; this.open = false; },
            pickFirst() { const f = this.filtered[0]; if (f) { this.pick(f); } }
        };
    }
</script>

<form method="post" action="<?= e($formAction) ?>" class="exp-form" autocomplete="off">
    <?= csrf_field() ?>

    <div class="c-name">
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Expense name' : 'اسم المصروف' ?> <span class="req">*</span></label>
        <div class="exp-box">
            <i class="bi bi-tag exp-ico"></i>
            <input type="text" name="name" class="form-control" maxlength="150" required
                   value="<?= e($expense['name'] ?? '') ?>"
                   placeholder="<?= $isEn ? 'e.g. Bank transfer fees' : 'مثال: رسوم تحويل بنكي' ?>">
        </div>
    </div>

    <div class="c-cost">
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Cost' : 'تكلفة المصروف' ?> <span class="req">*</span></label>
        <div class="exp-box has-suffix">
            <i class="bi bi-cash-coin exp-ico"></i>
            <input type="number" name="amount" class="form-control font-numeric" step="0.01" min="0.01" inputmode="decimal" required
                   value="<?= e(isset($expense['amount']) ? (string) $expense['amount'] : '') ?>" placeholder="0.00">
            <span class="exp-suffix"><?= __('currency') ?></span>
        </div>
    </div>

    <div class="c-date">
        <label class="form-label font-bold text-xs text-slate-700"><?= $isEn ? 'Date' : 'التاريخ' ?></label>
        <div class="exp-box">
            <i class="bi bi-calendar-event exp-ico"></i>
            <input type="date" name="expense_date" class="form-control font-numeric" value="<?= e($expense['expense_date'] ?? date('Y-m-d')) ?>">
        </div>
    </div>

    <div class="c-member"
         x-data="expenseMemberPicker(<?= e(json_encode($members ?? [], JSON_UNESCAPED_UNICODE)) ?>, '<?= (int) ($expense['member_id'] ?? 0) ?>', <?= e(json_encode($pickerI18n, JSON_UNESCAPED_UNICODE)) ?>)"
         @click.outside="open = false" @keydown.escape="open = false">
        <label class="form-label font-bold text-xs text-slate-700">
            <?= $isEn ? 'Member' : 'المشترك' ?>
            <span class="opt"><?= $isEn ? '(optional) leave it empty for a general expense of the fund' : '(اختياري) اتركه فارغاً إذا كان المصروف عاماً للصندوق' ?></span>
        </label>
        <div class="exp-box exp-picker-box" :class="{ 'exp-picker-open': open }" data-testid="expense-member-picker">
            <i class="bi bi-person exp-ico"></i>
            <input type="hidden" name="member_id" :value="selectedId">
            <button type="button" class="form-control exp-picker" :class="{ 'is-empty': !selectedId }" @click="toggle()" :aria-expanded="open" aria-haspopup="listbox">
                <span x-text="label"></span>
            </button>
            <span class="exp-tools">
                <button type="button" class="exp-clear" x-show="selectedId" style="display:none" @click.stop="clear()" title="<?= e($isEn ? 'Remove the member' : 'إزالة المشترك') ?>"><i class="bi bi-x-lg"></i></button>
                <i class="bi bi-chevron-down exp-chev"></i>
            </span>
            <div class="exp-picker-panel" x-show="open" style="display:none">
                <div class="exp-picker-search">
                    <i class="bi bi-search"></i>
                    <input type="text" x-ref="q" x-model="term" @keydown.enter.prevent="pickFirst()"
                           placeholder="<?= e($isEn ? 'Search by name, mobile or number...' : 'ابحث بالاسم أو الجوال أو الرقم...') ?>">
                </div>
                <ul class="exp-picker-list" role="listbox">
                    <li class="exp-none" :class="{ on: !selectedId }" @click="clear()">
                        <span><i class="bi bi-slash-circle"></i> <?= $isEn ? 'No member (a general expense)' : 'بدون مشترك (مصروف عام)' ?></span>
                    </li>
                    <template x-for="m in filtered" :key="m.id">
                        <li :class="{ on: String(m.id) === selectedId }" @click="pick(m)" role="option">
                            <span x-text="m.label"></span>
                            <small x-text="m.sub"></small>
                        </li>
                    </template>
                    <li class="exp-empty" x-show="filtered.length === 0" style="display:none"><?= $isEn ? 'No member matches' : 'لا يوجد مشترك مطابق' ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="c-submit">
        <button type="submit" class="btn btn-primary exp-submit">
            <i class="bi bi-<?= $isEdit ? 'check2-circle' : 'plus-lg' ?>"></i>
            <span><?= $isEdit ? ($isEn ? 'Save changes' : 'حفظ التعديلات') : ($isEn ? 'Add expense' : 'إضافة المصروف') ?></span>
        </button>
    </div>
</form>
