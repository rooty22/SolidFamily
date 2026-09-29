<?php
$buildFilterUrl = function($pStatus = null, $aStatus = null) use ($q, $paymentStatus, $accountStatus) {
    $params = [];
    if ($q !== '') $params['q'] = $q;
    $p = $pStatus !== null ? $pStatus : $paymentStatus;
    $a = $aStatus !== null ? $aStatus : $accountStatus;
    if ($p !== '') $params['payment_status'] = $p;
    if ($a !== '') $params['account_status'] = $a;
    return url('admin/members') . ($params ? '?' . http_build_query($params) : '');
};
$hasFilters = ($paymentStatus !== '' || $accountStatus !== '' || $q !== '');
?>

<!-- Clean, spacious header without cramped dropdowns -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('members_directory_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('members_directory_desc') ?></p>
    </div>
    <div class="flex items-center gap-3">
        <form method="get" action="<?= url('admin/members') ?>" class="search-box" style="min-width:280px;">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="<?= __('search_members_full') ?>" value="<?= e($q) ?>">
            <?php if ($paymentStatus !== ''): ?>
                <input type="hidden" name="payment_status" value="<?= e($paymentStatus) ?>">
            <?php endif; ?>
            <?php if ($accountStatus !== ''): ?>
                <input type="hidden" name="account_status" value="<?= e($accountStatus) ?>">
            <?php endif; ?>
        </form>
        <a href="<?= url('admin/members/create') ?>" class="btn btn-primary inline-flex items-center gap-2 whitespace-nowrap shrink-0 font-bold shadow-xs">
            <i class="bi bi-person-plus-fill"></i>
            <span><?= __('add_member') ?></span>
        </a>
    </div>
</div>

<!-- Modern, aesthetic Filter Toolbar -->
<div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <!-- Quick Filter Pills for Payment Status -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 text-xs font-bold scrollbar-none">
        <span class="text-slate-400 text-xs font-semibold me-1 inline-flex items-center gap-1 shrink-0">
            <i class="bi bi-funnel text-sky-600"></i> <?= is_rtl() ? 'حالة السداد:' : 'Payment:' ?>
        </span>
        <a href="<?= $buildFilterUrl('') ?>" class="px-3 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap <?= $paymentStatus === '' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200/70 hover:bg-slate-100' ?>">
            <span><?= is_rtl() ? 'الكل' : 'All' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['all'] ?>)</span>
        </a>
        <a href="<?= $buildFilterUrl('late') ?>" class="px-3 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $paymentStatus === 'late' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' ?>">
            <i class="bi bi-exclamation-triangle-fill text-[11px]"></i>
            <span><?= is_rtl() ? 'متأخر عن السداد' : 'Overdue' ?></span>
            <span class="px-1.5 py-0.2 rounded-full font-numeric text-[11px] <?= $paymentStatus === 'late' ? 'bg-white/20 text-white' : 'bg-rose-200/70 text-rose-800' ?>"><?= $counts['late'] ?></span>
        </a>
        <a href="<?= $buildFilterUrl('late_subscription') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $paymentStatus === 'late_subscription' ? 'bg-rose-700 text-white border-rose-700 shadow-xs' : 'bg-slate-50 text-rose-700 border-slate-200/70 hover:bg-rose-50' ?>">
            <span><?= is_rtl() ? 'اشتراك شهري' : 'Sub' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['late_subscription'] ?>)</span>
        </a>
        <a href="<?= $buildFilterUrl('late_loan') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $paymentStatus === 'late_loan' ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-slate-50 text-amber-700 border-slate-200/70 hover:bg-amber-50' ?>">
            <span><?= is_rtl() ? 'أقساط قروض' : 'Loan' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['late_loan'] ?>)</span>
        </a>
        <a href="<?= $buildFilterUrl('up_to_date') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $paymentStatus === 'up_to_date' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-emerald-700 border-slate-200/70 hover:bg-emerald-50' ?>">
            <span><?= is_rtl() ? 'منتظم' : 'Up to date' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['up_to_date'] ?>)</span>
        </a>
    </div>

    <!-- Account Status Toggle & Reset -->
    <div class="flex items-center gap-2 shrink-0 justify-between md:justify-end border-t md:border-t-0 pt-2 md:pt-0 border-slate-100">
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold">
            <span class="text-slate-400 px-1 text-[11px] hidden sm:inline"><?= is_rtl() ? 'الحساب:' : 'Account:' ?></span>
            <a href="<?= $buildFilterUrl(null, '') ?>" class="px-2.5 py-1 rounded-lg text-decoration-none transition-colors <?= $accountStatus === '' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' ?>">
                <?= is_rtl() ? 'الكل' : 'All' ?>
            </a>
            <a href="<?= $buildFilterUrl(null, 'active') ?>" class="px-2.5 py-1 rounded-lg text-decoration-none transition-colors <?= $accountStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900' ?>">
                <?= is_rtl() ? 'نشط' : 'Active' ?>
            </a>
            <a href="<?= $buildFilterUrl(null, 'inactive') ?>" class="px-2.5 py-1 rounded-lg text-decoration-none transition-colors <?= $accountStatus === 'inactive' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900' ?>">
                <?= is_rtl() ? 'موقوف' : 'Inactive' ?>
            </a>
        </div>
        <?php if ($hasFilters): ?>
            <a href="<?= url('admin/members') ?>" class="btn btn-sm btn-soft text-slate-500 hover:text-rose-600 text-xs inline-flex items-center gap-1 font-bold py-1 px-2.5 rounded-xl border border-slate-200" title="<?= is_rtl() ? 'إعادة ضبط كل الفلاتر' : 'Reset all filters' ?>">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span><?= is_rtl() ? 'إلغاء الفلترة' : 'Reset' ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('name') ?></th>
                <th><?= __('mobile') ?></th>
                <th><?= __('national_id') ?></th>
                <th><?= __('shares') ?></th>
                <th><?= is_rtl() ? 'حالة الحساب' : 'Account Status' ?></th>
                <th><?= is_rtl() ? 'حالة السداد / نوع التأخير' : 'Payment Status / Delay Type' ?></th>
                <th><?= __('registered_at') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): [$label, $variant] = status_badge($m['status']); ?>
            <tr>
                <td class="fw-bold text-slate-900">
                    <a href="<?= url('admin/members/' . $m['id']) ?>" class="text-slate-900 hover:text-sky-600 text-decoration-none">
                        <?= e($m['name']) ?>
                    </a>
                </td>
                <td class="font-numeric" dir="ltr"><?= e($m['mobile']) ?></td>
                <td class="font-numeric" dir="ltr"><?= e($m['national_id']) ?></td>
                <td class="font-numeric font-bold text-slate-800"><?= number_format($m['shares_count']) ?></td>
                <td><span class="badge-status badge-<?= $variant ?>"><?= $label ?></span></td>
                <td>
                    <?php if (!empty($m['is_late_sub']) && !empty($m['is_late_loan'])): ?>
                        <span class="badge-status badge-danger" title="<?= is_rtl() ? 'متأخر في سداد الاشتراكات الشهرية وأقساط القروض' : 'Overdue in monthly subscription and loans' ?>">
                            <?= is_rtl() ? 'متأخر (اشتراك وقرض)' : 'Overdue (Sub & Loan)' ?>
                        </span>
                    <?php elseif (!empty($m['is_late_sub'])): ?>
                        <span class="badge-status badge-danger" title="<?= is_rtl() ? 'متأخر في سداد الاشتراكات الشهرية' : 'Overdue in monthly subscription' ?>">
                            <?= is_rtl() ? 'متأخر (اشتراك شهري)' : 'Overdue (Subscription)' ?>
                        </span>
                    <?php elseif (!empty($m['is_late_loan'])): ?>
                        <span class="badge-status badge-warning" title="<?= is_rtl() ? 'متأخر في سداد أقساط القروض' : 'Overdue in loan installments' ?>">
                            <?= is_rtl() ? 'متأخر (أقساط قروض)' : 'Overdue (Loans)' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-status badge-success">
                            <?= is_rtl() ? 'منتظم' : 'Up to date' ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td class="text-xs text-slate-500 font-numeric"><?= is_rtl() ? date_ar($m['created_at']) : date('M d, Y', strtotime($m['created_at'])) ?></td>
                <td class="text-end">
                    <a href="<?= url('admin/members/' . $m['id']) ?>" class="btn btn-sm btn-soft" title="<?= __('view') ?>"><i class="bi bi-eye"></i></a>
                    <a href="<?= url('admin/members/' . $m['id'] . '/edit') ?>" class="btn btn-sm btn-soft" title="<?= __('edit') ?>"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="<?= url('admin/members/' . $m['id'] . '/toggle-status') ?>" class="d-inline" data-confirm="<?= __('are_you_sure') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-soft" title="<?= $m['status'] === 'active' ? __('deactivate') : __('activate') ?>">
                            <i class="bi bi-<?= $m['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
            <tr>
                <td colspan="8" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-people text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_members_recorded') ?></span>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
