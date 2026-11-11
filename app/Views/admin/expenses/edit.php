<?php
$isEn = !is_rtl();
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= $isEn ? 'Edit expense' : 'تعديل مصروف' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= e($expense['name']) ?></p>
    </div>
    <a href="<?= url('admin/expenses') ?>" class="btn btn-soft self-start sm:self-center inline-flex items-center gap-2">
        <i class="bi bi-arrow-<?= $isEn ? 'left' : 'right' ?>"></i> <?= $isEn ? 'Back to the log' : 'العودة إلى السجل' ?>
    </a>
</div>

<div class="card-panel">
    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
        <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl text-white shadow-sm shrink-0" style="background: linear-gradient(135deg, #0284c7, #0369a1);"><i class="bi bi-pencil-fill text-lg"></i></span>
        <div>
            <h3 class="text-base font-extrabold text-slate-900 m-0"><?= $isEn ? 'Expense details' : 'بيانات المصروف' ?></h3>
            <p class="text-xs text-slate-500 m-0 mt-0.5"><?= $isEn ? 'Change what is needed and save.' : 'عدّل ما تريد ثم احفظ.' ?></p>
        </div>
    </div>
    <?php \App\Core\View::partial('admin/expenses/_form', ['formAction' => url('admin/expenses/' . (int) $expense['id']), 'expense' => $expense]); ?>
</div>
