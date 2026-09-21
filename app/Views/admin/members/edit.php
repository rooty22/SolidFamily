<?php
$isEn = is_en();
?>
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                <i class="bi bi-pencil-square"></i>
                <span><?= $isEn ? 'Member Profile Management' : 'إدارة ملف المشترك' ?></span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                <?= $isEn ? 'Edit Member' : 'تعديل بيانات المشترك' ?>: <?= e($member['name']) ?>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                <?= $isEn ? 'Update member personal profile, national address, banking accounts, or reset credentials.' : 'تحديث البيانات الشخصية، العنوان الوطني، الحساب البنكي، أو إعادة تعيين كلمة المرور.' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-soft inline-flex items-center gap-2">
                <i class="bi bi-eye"></i>
                <span><?= $isEn ? 'View Profile' : 'الملف الكامل' ?></span>
            </a>
            <a href="<?= url('admin/members') ?>" class="btn btn-soft inline-flex items-center gap-2">
                <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
                <span><?= $isEn ? 'Back to Members' : 'العودة للمشتركين' ?></span>
            </a>
        </div>
    </div>

    <!-- Main Card Panel (Full Width) -->
    <div class="card-panel bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="post" action="<?= url('admin/members/' . $member['id']) ?>">
            <?= csrf_field() ?>
            <?php include __DIR__ . '/_form.php'; ?>
            
            <div class="mt-8 pt-5 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="btn btn-primary px-6 py-2.5 font-bold shadow-sm inline-flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span><?= $isEn ? 'Save Changes' : 'حفظ التعديلات' ?></span>
                </button>
                <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-soft px-4 py-2.5">
                    <?= $isEn ? 'Cancel' : 'إلغاء' ?>
                </a>
            </div>
        </form>
    </div>
</div>
