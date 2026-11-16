<?php
$isEn = !is_rtl();
$net = (float) $summary['net'];
$signMoney = fn(float $v): string => money_signed($v);
// a link that keeps the search and changes the type filter
$typeUrl = fn(string $t): string => url('admin/expenses') . (($q !== '' || $t !== '') ? '?' . http_build_query(array_filter(['q' => $q, 'type' => $t], fn($v) => $v !== '')) : '');
?>
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= $isEn ? 'Administrative & Bank Expenses Log' : 'سجل المصاريف الإدارية والبنكية' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= $isEn ? 'Record the fund\'s administrative and bank transactions: a withdrawal is deducted from the fund balance, a deposit is added to it. Only the main admin sees this page.' : 'تسجيل المعاملات الإدارية والبنكية للصندوق: الصرف يُخصم من رصيد الصندوق والإيداع يُضاف إليه. هذه الصفحة للأدمن الرئيسي فقط.' ?></p>
    </div>
    <div class="flex flex-wrap items-stretch gap-2.5 shrink-0">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-end">
            <div class="text-[11px] font-bold text-rose-700"><i class="bi bi-box-arrow-up"></i> <?= $isEn ? 'Withdrawals' : 'إجمالي الصرف' ?> <span class="font-numeric">(<?= (int) $summary['withdraw_count'] ?>)</span></div>
            <div class="font-num font-extrabold text-xl text-rose-700" data-testid="expenses-total"><?= money($summary['withdrawals']) ?></div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-end">
            <div class="text-[11px] font-bold text-emerald-700"><i class="bi bi-box-arrow-in-down"></i> <?= $isEn ? 'Deposits' : 'إجمالي الإيداع' ?> <span class="font-numeric">(<?= (int) $summary['deposit_count'] ?>)</span></div>
            <div class="font-num font-extrabold text-xl text-emerald-700" data-testid="expenses-deposits"><?= money($summary['deposits']) ?></div>
        </div>
        <div class="rounded-2xl border px-4 py-2.5 text-end <?= $net < 0 ? 'border-rose-300 bg-white' : ($net > 0 ? 'border-emerald-300 bg-white' : 'border-slate-200 bg-slate-50') ?>">
            <div class="text-[11px] font-bold text-slate-600"><i class="bi bi-calculator"></i> <?= $isEn ? 'Net effect on the balance' : 'الصافي على الرصيد' ?> <span class="font-numeric">(<?= (int) $summary['count'] ?>)</span></div>
            <div class="font-num font-extrabold text-xl <?= $net < 0 ? 'text-rose-700' : ($net > 0 ? 'text-emerald-700' : 'text-slate-700') ?>" data-testid="expenses-net"><?= $signMoney($net) ?></div>
        </div>
    </div>
</div>

<div class="card-panel mb-4">
    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
        <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl text-white shadow-sm shrink-0" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="bi bi-plus-lg text-lg"></i></span>
        <div>
            <h3 class="text-base font-extrabold text-slate-900 m-0"><?= $isEn ? 'Add a new transaction' : 'إضافة معاملة جديدة' ?></h3>
            <p class="text-xs text-slate-500 m-0 mt-0.5"><?= $isEn ? 'Choose the type (withdrawal or deposit), then write the name, the amount and the date.' : 'اختر نوع المعاملة (صرف أو إيداع) ثم اكتب اسمها ومبلغها وتاريخها.' ?></p>
        </div>
    </div>
    <?php \App\Core\View::partial('admin/expenses/_form', ['formAction' => url('admin/expenses'), 'expense' => null, 'members' => $pickerMembers]); ?>
    <div class="mt-3 text-[11.5px] text-slate-400 font-medium inline-flex items-center gap-1.5">
        <i class="bi bi-info-circle"></i>
        <?= $isEn ? 'A withdrawal is deducted from the actual bank balance and the fund\'s total balance; a deposit is added to both.' : 'الصرف يُخصم من الرصيد الفعلي بالبنك ومن إجمالي رصيد الصندوق، والإيداع يُضاف إليهما.' ?>
    </div>
</div>

<form method="get" action="<?= url('admin/expenses') ?>" class="flex flex-wrap items-center gap-2 mb-3">
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <div class="search-box" style="min-width:300px; flex: 1 1 300px; max-width: 460px;">
        <i class="bi bi-search"></i>
        <input type="text" name="q" list="member-names" autocomplete="off" class="form-control" placeholder="<?= $isEn ? 'Search by name, member or notes...' : 'ابحث باسم المعاملة أو المشترك أو الملاحظات...' ?>" value="<?= e($q) ?>"><?= member_names_datalist() ?>
    </div>
    <button type="submit" class="btn btn-primary btn-sm font-bold inline-flex items-center gap-1.5"><i class="bi bi-search"></i> <?= $isEn ? 'Search' : 'بحث' ?></button>
    <div class="inline-flex items-center gap-1.5 text-xs" data-testid="expense-type-filter">
        <a href="<?= $typeUrl('') ?>" class="px-3 py-1.5 rounded-xl border text-decoration-none font-bold <?= $type === '' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' ?>"><?= $isEn ? 'All' : 'الكل' ?></a>
        <a href="<?= $typeUrl('withdraw') ?>" class="px-3 py-1.5 rounded-xl border text-decoration-none font-bold <?= $type === 'withdraw' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' ?>"><?= $isEn ? 'Withdrawals' : 'صرف' ?></a>
        <a href="<?= $typeUrl('deposit') ?>" class="px-3 py-1.5 rounded-xl border text-decoration-none font-bold <?= $type === 'deposit' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' ?>"><?= $isEn ? 'Deposits' : 'إيداع' ?></a>
    </div>
    <?php if ($q !== '' || $type !== ''): ?>
        <a href="<?= url('admin/expenses') ?>" class="btn btn-soft btn-sm inline-flex items-center gap-1.5"><i class="bi bi-x-circle"></i> <?= $isEn ? 'Clear' : 'إلغاء البحث' ?></a>
        <span class="text-xs text-slate-500 font-bold"><?= number_format($pager['total']) ?> <?= $isEn ? 'result(s)' : 'نتيجة' ?> · <?= $signMoney((float) $foundNet) ?></span>
    <?php endif; ?>
    <?php
    $expExportParams = array_filter(['q' => $q, 'type' => $type], fn($v) => $v !== '');
    $expExportUrl = url('admin/expenses/export') . ($expExportParams ? '?' . http_build_query($expExportParams) : '');
    ?>
    <a href="<?= e($expExportUrl) ?>" class="btn btn-soft btn-sm ms-auto inline-flex items-center gap-1.5 font-bold whitespace-nowrap">
        <i class="bi bi-file-earmark-excel text-emerald-600"></i>
        <span><?= __('export_excel') ?></span>
    </a>
</form>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= $isEn ? 'Date' : 'التاريخ' ?></th>
                <th><?= $isEn ? 'Transaction name' : 'اسم المعاملة' ?></th>
                <th><?= $isEn ? 'Type' : 'النوع' ?></th>
                <th><?= $isEn ? 'Member' : 'المشترك' ?></th>
                <th><?= $isEn ? 'Amount' : 'المبلغ' ?></th>
                <th class="text-end"><?= $isEn ? 'Actions' : 'إجراءات' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $x): $isDeposit = ($x['type'] ?? 'withdraw') === 'deposit'; ?>
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
                    <?php if ($isDeposit): ?>
                        <span class="inline-flex items-center gap-1 text-[11.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5" data-type="deposit"><i class="bi bi-box-arrow-in-down"></i> <?= $isEn ? 'Deposit' : 'إيداع' ?></span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-[11.5px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-full px-2.5 py-0.5" data-type="withdraw"><i class="bi bi-box-arrow-up"></i> <?= $isEn ? 'Withdrawal' : 'صرف' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($x['member_id']) && $x['member_name'] !== null): ?>
                        <?= member_cell((string) $x['member_name'], $x['member_serial'] ?? null, url('admin/members/' . (int) $x['member_id'])) ?>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-[11.5px] font-bold text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-2.5 py-0.5"><i class="bi bi-building"></i> <?= $isEn ? 'General' : 'عام' ?></span>
                    <?php endif; ?>
                </td>
                <td class="font-numeric fw-bold" data-amount="<?= e(number_format(\App\Models\Expense::signed($x), 2, '.', '')) ?>"><span style="color: <?= $isDeposit ? '#047857' : '#be123c' ?>;"><?= money_signed(\App\Models\Expense::signed($x)) ?></span></td>
                <td class="text-end">
                    <div class="inline-flex items-center gap-1.5">
                        <a href="<?= url('admin/expenses/' . (int) $x['id'] . '/edit') ?>" class="btn btn-sm btn-soft" title="<?= $isEn ? 'Edit' : 'تعديل' ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/expenses/' . (int) $x['id'] . '/delete') ?>" data-confirm="<?= e($isEn ? 'Delete this transaction from the log? The balance changes back.' : 'حذف ' . ($isDeposit ? 'الإيداع' : 'المصروف') . ' "' . $x['name'] . '" من السجل؟ سيتغيّر الرصيد تبعاً لذلك.') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-soft text-danger" title="<?= $isEn ? 'Delete' : 'حذف' ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($expenses)): ?>
            <tr>
                <td colspan="6" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-receipt"></i>
                        <span><?= ($q !== '' || $type !== '') ? ($isEn ? 'No transaction matches your search.' : 'لا يوجد مصروف مطابق للبحث.') : ($isEn ? 'No transactions recorded yet.' : 'لا توجد مصاريف مسجلة بعد.') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= pagination_links($pager ?? []) ?>
