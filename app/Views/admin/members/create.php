<?php
$isEn = is_en();
?>
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold mb-2">
                <i class="bi bi-person-plus-fill"></i>
                <span><?= $isEn ? 'Membership Roster' : 'سجل أعضاء الصندوق العائلي' ?></span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                <?= $isEn ? 'Add New Family Member' : 'تسجيل وإضافة فرد جديد في العائلة' ?>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                <?= $isEn ? 'Register member profile, national identity, banking coordinates, and allocate initial shares.' : 'تسجيل بيانات العضو الجديد، رقم الهوية الوطنية، الحساب البنكي، وتخصيص الأسهم الابتدائية.' ?>
            </p>
        </div>
        <a href="<?= url('admin/members') ?>" class="btn btn-soft self-start sm:self-center inline-flex items-center gap-2">
            <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
            <span><?= $isEn ? 'Back to Members' : 'العودة لقائمة المشتركين' ?></span>
        </a>
    </div>

    <!-- Main Card Panel (Full Width) -->
    <div class="card-panel bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="post" action="<?= url('admin/members') ?>">
            <?= csrf_field() ?>
            <?php include __DIR__ . '/_form.php'; ?>
            
            <div class="mt-8 pt-5 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="btn btn-primary px-6 py-2.5 font-bold shadow-sm inline-flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span><?= $isEn ? 'Save Member Record' : 'حفظ وتسجيل المشترك' ?></span>
                </button>
                <a href="<?= url('admin/members') ?>" class="btn btn-soft px-4 py-2.5">
                    <?= $isEn ? 'Cancel' : 'إلغاء' ?>
                </a>
            </div>
        </form>
    </div>
</div>
