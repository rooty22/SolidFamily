<?php
$isEn = current_locale() === 'en';
?>
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-800 flex items-center gap-2 mb-1">
                <i class="bi bi-shield-plus text-emerald-600"></i>
                <span><?= $isEn ? 'Create New Role' : 'إنشاء دور وصلاحيات جديدة' ?></span>
            </h1>
            <p class="text-xs text-slate-500 mb-0">
                <?= $isEn ? 'Define a new role and choose exactly what permissions it grants across the dashboard.' : 'قم بتحديد اسم الدور واختيار الصلاحيات الدقيقة الممنوحة له في لوحة التحكم.' ?>
            </p>
        </div>
        <a href="<?= url('admin/roles') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
            <span><?= $isEn ? 'Back to Roles' : 'العودة للأدوار' ?></span>
        </a>
    </div>

    <form method="post" action="<?= url('admin/roles') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Role Basic Info Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">
                <?= $isEn ? '1. Role Information' : '١. بيانات الدور' ?>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label text-xs font-bold text-slate-700">
                        <?= $isEn ? 'Role Slug (Identifier)' : 'رمز الدور البرمجي (Slug)' ?> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="<?= e(old('name')) ?>" placeholder="e.g. loan_auditor" required class="form-control text-xs rounded-xl border-slate-200 font-mono">
                    <p class="text-[10px] text-slate-400 mt-1 mb-0"><?= $isEn ? 'English letters, numbers and underscores only' : 'أحرف إنجليزية وأرقام بدون مسافات' ?></p>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700">
                        <?= $isEn ? 'Arabic Name' : 'اسم الدور بالعربية' ?> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="label_ar" value="<?= e(old('label_ar')) ?>" placeholder="مثال: مدقق طلبات القروض" required class="form-control text-xs rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700">
                        <?= $isEn ? 'English Name' : 'اسم الدور بالإنجليزية' ?> <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="label_en" value="<?= e(old('label_en')) ?>" placeholder="e.g. Loan Auditor" required class="form-control text-xs rounded-xl border-slate-200">
                </div>
            </div>

            <div>
                <label class="form-label text-xs font-bold text-slate-700">
                    <?= $isEn ? 'Description' : 'الوصف والمهام' ?>
                </label>
                <input type="text" name="description" value="<?= e(old('description')) ?>" placeholder="<?= $isEn ? 'Brief description of duties' : 'نبذة موجزة عن مهام ومسؤوليات صاحب هذا الدور' ?>" class="form-control text-xs rounded-xl border-slate-200">
            </div>
        </div>

        <!-- Permissions Matrix Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-0.5">
                        <?= $isEn ? '2. Permissions Matrix' : '٢. مصفوفة الصلاحيات الممنوحة' ?>
                    </h2>
                    <p class="text-xs text-slate-500 mb-0">
                        <?= $isEn ? 'Check the permissions to grant to this role.' : 'حدد الصلاحيات التي سيتاح لصاحب هذا الدور استخدامها في لوحة التحكم.' ?>
                    </p>
                </div>
                <button type="button" onclick="toggleAllPermissions(true)" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                    <?= $isEn ? 'Select All' : 'تحديد الكل' ?>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php foreach ($groupedPermissions as $moduleKey => $group): ?>
                    <div class="border border-slate-200/80 rounded-xl p-4 bg-slate-50/50">
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-200">
                            <span class="font-black text-xs text-slate-800 flex items-center gap-1.5">
                                <i class="bi bi-folder-fill text-emerald-600"></i>
                                <span><?= e($isEn ? $group['label_en'] : $group['label_ar']) ?></span>
                            </span>
                            <button type="button" onclick="toggleModuleCheckboxes('mod_<?= e($moduleKey) ?>')" class="text-[11px] font-bold text-slate-500 hover:text-slate-800">
                                <?= $isEn ? 'Toggle' : 'تحديد / إلغاء' ?>
                            </button>
                        </div>
                        <div class="space-y-2.5">
                            <?php foreach ($group['permissions'] as $p): ?>
                                <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-700 hover:text-slate-900 select-none">
                                    <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" class="perm-checkbox mod_<?= e($moduleKey) ?> mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    <div>
                                        <div class="font-bold"><?= e($isEn ? $p['label_en'] : $p['label_ar']) ?></div>
                                        <div class="font-mono text-[10px] text-slate-400"><?= e($p['name']) ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3">
            <a href="<?= url('admin/roles') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
                <?= $isEn ? 'Cancel' : 'إلغاء' ?>
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-2">
                <i class="bi bi-check-lg"></i>
                <span><?= $isEn ? 'Save & Create Role' : 'حفظ وإنشاء الدور' ?></span>
            </button>
        </div>
    </form>
</div>

<script>
function toggleAllPermissions(selectAll) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = selectAll);
}
function toggleModuleCheckboxes(className) {
    const list = document.querySelectorAll('.' + className);
    const anyUnchecked = Array.from(list).some(cb => !cb.checked);
    list.forEach(cb => cb.checked = anyUnchecked);
}
</script>
