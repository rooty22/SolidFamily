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

        <div class="row g-3">
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
            <h4 class="text-sm font-black text-slate-800 mb-1"><?= is_rtl() ? 'الصلاحيات' : 'Permissions' ?></h4>
            <p class="text-xs text-slate-500 mb-3"><?= is_rtl() ? 'لكل قسم: بدون صلاحية (لا يظهر له)، أو عرض فقط (يفتح الصفحات دون تعديل)، أو تحكم كامل (يعرض ويعدّل وينفّذ الإجراءات).' : 'Per section: no access (hidden), view only, or full control.' ?></p>
            <div class="table-responsive">
                <table class="table-modern text-sm">
                    <thead>
                        <tr>
                            <th><?= is_rtl() ? 'القسم' : 'Section' ?></th>
                            <th class="text-center"><?= is_rtl() ? 'بدون' : 'None' ?></th>
                            <th class="text-center"><?= is_rtl() ? 'عرض فقط' : 'View' ?></th>
                            <th class="text-center"><?= is_rtl() ? 'تحكم كامل' : 'Full' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sections as $key => $def): $cur = $granted[$key] ?? 'none'; ?>
                        <tr>
                            <td class="fw-bold"><?= e($def[is_rtl() ? 0 : 1]) ?></td>
                            <?php foreach (['none', 'view', 'manage'] as $level): ?>
                                <td class="text-center"><input type="radio" class="form-check-input perm-radio" name="perm[<?= e($key) ?>]" value="<?= $level ?>" <?= $cur === $level ? 'checked' : '' ?>></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-soft" onclick="setAllPerms('none')"><?= is_rtl() ? 'إلغاء الكل' : 'Clear all' ?></button>
                <button type="button" class="btn btn-sm btn-soft" onclick="setAllPerms('view')"><?= is_rtl() ? 'عرض الكل' : 'View all' ?></button>
                <button type="button" class="btn btn-sm btn-soft" onclick="setAllPerms('manage')"><?= is_rtl() ? 'تحكم كامل في الكل' : 'Full control for all' ?></button>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-2">
            <a href="<?= url('admin/admins') ?>" class="btn btn-soft"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></a>
            <button type="submit" class="btn btn-primary font-bold"><i class="bi bi-check-lg"></i> <?= is_rtl() ? 'حفظ' : 'Save' ?></button>
        </div>
    </form>
</div>
<script>
function setAllPerms(level) {
    document.querySelectorAll('input.perm-radio[value="' + level + '"]').forEach(function (r) { r.checked = true; });
}
</script>
