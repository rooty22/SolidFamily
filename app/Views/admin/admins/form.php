<?php
use App\Models\Admin;

$isEdit = !empty($admin);
$sections = Admin::sections();
$granted = $isEdit ? Admin::permissionsOf($admin) : [];
$name = $isEdit ? $admin['name'] : old('name');
$email = $isEdit ? $admin['email'] : old('email');
$status = $isEdit ? ($admin['status'] ?? 'active') : (old('status') ?: 'active');
$avatar = $isEdit ? Admin::avatarUrl($admin) : null;
$action = $isEdit ? url('admin/admins/' . (int) $admin['id']) : url('admin/admins');
?>
<style>
    .perm-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .perm-preset { border: 1px solid #e2e8f0; background: #fff; color: #475569; border-radius: 999px; padding: 6px 14px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
    .perm-preset:hover { background: #f1f5f9; }
    .perm-preset.view { color: #0369a1; border-color: #bae6fd; background: #f0f9ff; }
    .perm-preset.manage { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
    .perm-summary { display: inline-flex; gap: 6px; flex-wrap: wrap; font-size: 11.5px; font-weight: 800; }
    .perm-summary span { padding: 4px 12px; border-radius: 999px; }
    .perm-summary .s-manage { background: #ecfdf5; color: #047857; }
    .perm-summary .s-view { background: #f0f9ff; color: #0369a1; }
    .perm-summary .s-none { background: #f1f5f9; color: #64748b; }
    .perm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; }
    .perm-card { border: 1.5px solid #e8edf3; border-radius: 16px; padding: 14px; background: #fff; transition: border-color .15s, background .15s, box-shadow .15s; }
    .perm-card:hover { box-shadow: 0 4px 14px rgba(15, 23, 42, .06); }
    .perm-card[data-level="view"] { border-color: #7dd3fc; background: #f7fcff; }
    .perm-card[data-level="manage"] { border-color: #6ee7b7; background: #f5fdf9; }
    .perm-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .perm-icon { width: 34px; height: 34px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; transition: all .15s; }
    .perm-card[data-level="view"] .perm-icon { background: #e0f2fe; color: #0284c7; }
    .perm-card[data-level="manage"] .perm-icon { background: #d1fae5; color: #059669; }
    .perm-title { font-weight: 800; font-size: 13.5px; color: #0f172a; }
    .perm-seg { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; padding: 3px; background: #eef2f6; border-radius: 12px; }
    .perm-seg label { margin: 0; cursor: pointer; position: relative; }
    .perm-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .perm-seg label span { display: flex; align-items: center; justify-content: center; gap: 4px; padding: 7px 4px; border-radius: 9px; font-size: 12px; font-weight: 800; color: #64748b; transition: all .15s; white-space: nowrap; }
    .perm-seg label:hover span { color: #0f172a; }
    .perm-seg .opt-none input:checked + span { background: #fff; color: #334155; box-shadow: 0 1px 3px rgba(15, 23, 42, .15); }
    .perm-seg .opt-view input:checked + span { background: #0ea5e9; color: #fff; box-shadow: 0 2px 6px rgba(14, 165, 233, .35); }
    .perm-seg .opt-manage input:checked + span { background: #10b981; color: #fff; box-shadow: 0 2px 6px rgba(16, 185, 129, .35); }
    .perm-seg input:focus-visible + span { outline: 2px solid #6366f1; outline-offset: 1px; }
</style>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                <?= $isEdit ? (is_rtl() ? 'تعديل مشرف فرعي' : 'Edit sub-admin') : (is_rtl() ? 'إضافة مشرف فرعي' : 'Add sub-admin') ?>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1"><?= is_rtl() ? 'بيانات الحساب وما يستطيع المشرف فتحه أو تعديله في لوحة التحكم.' : 'Account details and what this sub-admin may open or change.' ?></p>
        </div>
        <a href="<?= url('admin/admins') ?>" class="btn btn-soft self-start sm:self-center inline-flex items-center gap-2">
            <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
            <span><?= is_rtl() ? 'العودة للمشرفين' : 'Back to sub-admins' ?></span>
        </a>
    </div>

    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="card-panel bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
        <?= csrf_field() ?>

        <div class="row g-3" style="margin-top:0">
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= is_rtl() ? 'اسم المشرف' : 'Name' ?> <span class="text-rose-500">*</span></label>
                <input type="text" name="name" class="form-control text-sm" value="<?= e($name) ?>" maxlength="150" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= is_rtl() ? 'البريد الإلكتروني' : 'Email' ?> <span class="text-rose-500">*</span></label>
                <input type="email" name="email" class="form-control text-sm" dir="ltr" value="<?= e($email) ?>" maxlength="150" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700">
                    <?= is_rtl() ? 'كلمة المرور' : 'Password' ?> <?php if (!$isEdit): ?><span class="text-rose-500">*</span><?php endif; ?>
                </label>
                <input type="password" name="password" class="form-control text-sm" dir="ltr" minlength="8" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                <div class="text-[11px] text-slate-400 mt-1">
                    <?= $isEdit ? (is_rtl() ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : 'Leave empty to keep the current password.') : (is_rtl() ? '8 أحرف على الأقل.' : 'At least 8 characters.') ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label font-bold text-xs text-slate-700"><?= is_rtl() ? 'حالة المشرف' : 'Status' ?></label>
                <select name="status" class="form-select text-sm">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= is_rtl() ? 'نشط' : 'Active' ?></option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= is_rtl() ? 'موقوف (لا يستطيع الدخول)' : 'Suspended (cannot log in)' ?></option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label font-bold text-xs text-slate-700"><?= is_rtl() ? 'صورة المشرف' : 'Photo' ?></label>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="" class="rounded-circle" style="width:56px;height:56px;object-fit:cover;">
                    <?php endif; ?>
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="form-control text-sm" style="max-width:360px;">
                    <?php if ($avatar): ?>
                        <label class="d-flex align-items-center gap-1 text-xs text-slate-600"><input type="checkbox" name="remove_avatar" value="1" class="form-check-input mt-0"> <?= is_rtl() ? 'حذف الصورة' : 'Remove photo' ?></label>
                    <?php endif; ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">png / jpg / webp - <?= is_rtl() ? 'حتى 2 ميجابايت' : 'up to 2MB' ?></div>
            </div>
        </div>

        <div>
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="text-sm font-black text-slate-800 mb-1"><?= is_rtl() ? 'الصلاحيات' : 'Permissions' ?></h4>
                    <p class="text-xs text-slate-500 mb-0"><?= is_rtl() ? 'اختر لكل قسم: بدون (لا يظهر له)، أو عرض (يفتح الصفحات دون تعديل)، أو تحكم (يعرض ويعدّل وينفّذ الإجراءات).' : 'Per section: none (hidden), view (read only) or manage (full control).' ?></p>
                </div>
                <div class="perm-summary" id="permSummary"></div>
            </div>

            <div class="perm-toolbar">
                <button type="button" class="perm-preset" onclick="setAllPerms('none')"><i class="bi bi-slash-circle"></i> <?= is_rtl() ? 'إلغاء الكل' : 'Clear all' ?></button>
                <button type="button" class="perm-preset view" onclick="setAllPerms('view')"><i class="bi bi-eye"></i> <?= is_rtl() ? 'عرض الكل' : 'View all' ?></button>
                <button type="button" class="perm-preset manage" onclick="setAllPerms('manage')"><i class="bi bi-pencil-square"></i> <?= is_rtl() ? 'تحكم كامل في الكل' : 'Full control for all' ?></button>
            </div>

            <div class="perm-grid">
                <?php foreach ($sections as $key => $def): $cur = $granted[$key] ?? 'none'; ?>
                    <div class="perm-card" data-level="<?= e($cur) ?>">
                        <div class="perm-head">
                            <span class="perm-icon"><i class="bi bi-<?= e($def[3]) ?>"></i></span>
                            <span class="perm-title"><?= e($def[is_rtl() ? 0 : 1]) ?></span>
                        </div>
                        <div class="perm-seg" role="radiogroup">
                            <label class="opt-none"><input type="radio" class="perm-radio" name="perm[<?= e($key) ?>]" value="none" <?= $cur === 'none' ? 'checked' : '' ?>><span><?= is_rtl() ? 'بدون' : 'None' ?></span></label>
                            <label class="opt-view"><input type="radio" class="perm-radio" name="perm[<?= e($key) ?>]" value="view" <?= $cur === 'view' ? 'checked' : '' ?>><span><i class="bi bi-eye"></i> <?= is_rtl() ? 'عرض' : 'View' ?></span></label>
                            <label class="opt-manage"><input type="radio" class="perm-radio" name="perm[<?= e($key) ?>]" value="manage" <?= $cur === 'manage' ? 'checked' : '' ?>><span><i class="bi bi-pencil-square"></i> <?= is_rtl() ? 'تحكم' : 'Manage' ?></span></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-2">
            <a href="<?= url('admin/admins') ?>" class="btn btn-soft"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></a>
            <button type="submit" class="btn btn-primary font-bold"><i class="bi bi-check-lg"></i> <?= is_rtl() ? 'حفظ' : 'Save' ?></button>
        </div>
    </form>
</div>
<script>
(function () {
    var RTL = <?= is_rtl() ? 'true' : 'false' ?>;
    var cards = document.querySelectorAll('.perm-card');

    function refresh() {
        var n = { none: 0, view: 0, manage: 0 };
        cards.forEach(function (card) {
            var checked = card.querySelector('input.perm-radio:checked');
            var level = checked ? checked.value : 'none';
            card.setAttribute('data-level', level);
            n[level]++;
        });
        document.getElementById('permSummary').innerHTML =
            '<span class="s-manage">' + (RTL ? 'تحكم: ' : 'Manage: ') + n.manage + '</span>' +
            '<span class="s-view">' + (RTL ? 'عرض: ' : 'View: ') + n.view + '</span>' +
            '<span class="s-none">' + (RTL ? 'بدون: ' : 'None: ') + n.none + '</span>';
    }

    window.setAllPerms = function (level) {
        document.querySelectorAll('input.perm-radio[value="' + level + '"]').forEach(function (r) { r.checked = true; });
        refresh();
    };

    document.querySelectorAll('input.perm-radio').forEach(function (r) { r.addEventListener('change', refresh); });
    refresh();
})();
</script>
