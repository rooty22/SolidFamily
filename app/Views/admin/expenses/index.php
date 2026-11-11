<?php
$isEn = !is_rtl();
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= $isEn ? 'Administrative & Bank Expenses Log' : 'سجل المصاريف الإدارية والبنكية' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= $isEn ? 'Record the administrative and bank expenses of the fund (name and cost). Only the main admin sees this page; the total shows on the dashboard.' : 'تسجيل المصاريف الإدارية والبنكية للصندوق (اسم المصروف وتكلفته). هذه الصفحة للأدمن الرئيسي فقط، ويظهر إجمالي المصاريف في الصفحة الرئيسية.' ?></p>
    </div>
    <div class="text-end shrink-0">
        <div class="text-[11px] font-bold text-slate-500"><?= $isEn ? 'Total expenses' : 'إجمالي المصاريف' ?> <span class="font-numeric">(<?= (int) $summary['count'] ?>)</span></div>
        <div class="font-num font-extrabold text-2xl text-rose-700" data-testid="expenses-total"><?= money($summary['total']) ?></div>
    </div>
</div>

<div class="card-panel mb-4">
    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
        <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl text-white shadow-sm shrink-0" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="bi bi-plus-lg text-lg"></i></span>
        <div>
            <h3 class="text-base font-extrabold text-slate-900 m-0"><?= $isEn ? 'Add a new expense' : 'إضافة مصروف جديد' ?></h3>
            <p class="text-xs text-slate-500 m-0 mt-0.5"><?= $isEn ? 'Write the name, the cost and the date: it joins the log and the total.' : 'اكتب اسم المصروف وتكلفته وتاريخه ليُضاف إلى السجل وإلى الإجمالي.' ?></p>
        </div>
    </div>
    <?php \App\Core\View::partial('admin/expenses/_form', ['formAction' => url('admin/expenses'), 'expense' => null, 'members' => $pickerMembers]); ?>
    <div class="mt-3 text-[11.5px] text-slate-400 font-medium inline-flex items-center gap-1.5">
        <i class="bi bi-info-circle"></i>
        <?= $isEn ? 'An expense is recorded for review only: it does not change the actual bank balance.' : 'يُسجَّل المصروف للمراجعة فقط ولا يغيّر الرصيد الفعلي بالبنك.' ?>
    </div>
</div>

<form method="get" action="<?= url('admin/expenses') ?>" class="flex flex-wrap items-center gap-2 mb-3">
    <div class="search-box" style="min-width:300px; flex: 1 1 300px; max-width: 460px;">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control" placeholder="<?= $isEn ? 'Search by expense, member or notes...' : 'ابحث باسم المصروف أو المشترك أو الملاحظات...' ?>" value="<?= e($q) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm font-bold inline-flex items-center gap-1.5"><i class="bi bi-search"></i> <?= $isEn ? 'Search' : 'بحث' ?></button>
    <?php if ($q !== ''): ?>
        <a href="<?= url('admin/expenses') ?>" class="btn btn-soft btn-sm inline-flex items-center gap-1.5"><i class="bi bi-x-circle"></i> <?= $isEn ? 'Clear' : 'إلغاء البحث' ?></a>
        <span class="text-xs text-slate-500 font-bold"><?= number_format($pager['total']) ?> <?= $isEn ? 'result(s)' : 'نتيجة' ?> · <?= money($foundTotal) ?></span>
    <?php endif; ?>
</form>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= $isEn ? 'Date' : 'التاريخ' ?></th>
                <th><?= $isEn ? 'Expense name' : 'اسم المصروف' ?></th>
                <th><?= $isEn ? 'Member' : 'المشترك' ?></th>
                <th><?= $isEn ? 'Cost' : 'التكلفة' ?></th>
                <th class="text-end"><?= $isEn ? 'Actions' : 'إجراءات' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $x): ?>
            <tr>
                <td class="font-numeric text-xs text-slate-500"><bdi dir="ltr"><?= date_ar($x['expense_date']) ?></bdi></td>
                <td class="fw-bold">
                    <?= e($x['name']) ?>
                    <?php if (trim((string) ($x['notes'] ?? '')) !== ''): $noteFlat = trim((string) preg_replace('/\s+/u', ' ', $x['notes'])); ?>
                        <div class="text-[11.5px] text-slate-500 font-medium mt-0.5 flex items-start gap-1.5" style="max-width: 460px; font-weight: 500;" title="<?= e($x['notes']) ?>">
                            <i class="bi bi-chat-left-text text-slate-400 mt-[2px]"></i>
                            <span><?= e(mb_strimwidth($noteFlat, 0, 110, '…')) ?></span>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($x['member_id']) && $x['member_name'] !== null): ?>
                        <?= member_cell((string) $x['member_name'], $x['member_serial'] ?? null, url('admin/members/' . (int) $x['member_id'])) ?>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-[11.5px] font-bold text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-2.5 py-0.5"><i class="bi bi-building"></i> <?= $isEn ? 'General' : 'عام' ?></span>
                    <?php endif; ?>
                </td>
                <td class="font-numeric fw-bold text-rose-700"><?= money((float) $x['amount']) ?></td>
                <td class="text-end">
                    <div class="inline-flex items-center gap-1.5">
                        <a href="<?= url('admin/expenses/' . (int) $x['id'] . '/edit') ?>" class="btn btn-sm btn-soft" title="<?= $isEn ? 'Edit' : 'تعديل' ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/expenses/' . (int) $x['id'] . '/delete') ?>" data-confirm="<?= e($isEn ? 'Delete this expense from the log?' : 'حذف المصروف "' . $x['name'] . '" من السجل؟') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-soft text-danger" title="<?= $isEn ? 'Delete' : 'حذف' ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($expenses)): ?>
            <tr>
                <td colspan="5" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-receipt"></i>
                        <span><?= $q !== '' ? ($isEn ? 'No expense matches your search.' : 'لا يوجد مصروف مطابق للبحث.') : ($isEn ? 'No expenses recorded yet.' : 'لا توجد مصاريف مسجلة بعد.') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= pagination_links($pager ?? []) ?>
