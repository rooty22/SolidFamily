<?php
$buildSubFilterUrl = function($s = null) use ($q, $status) {
    $params = [];
    if ($q !== '') $params['q'] = $q;
    $target = $s !== null ? $s : $status;
    if ($target !== '') $params['status'] = $target;
    return url('admin/subscriptions') . ($params ? '?' . http_build_query($params) : '');
};
$hasSubFilters = ($status !== '' || $q !== '');
?>

<!-- Clean, spacious header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('subscriptions_index_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('subscriptions_index_desc', ['month' => e(month_label($currentMonth))]) ?></p>
    </div>
    <div class="flex items-center gap-3">
        <form method="get" action="<?= url('admin/subscriptions') ?>" class="search-box" style="min-width:280px;">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="<?= __('member_name_placeholder') ?>" value="<?= e($q) ?>">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Modern, aesthetic Filter Toolbar -->
<div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
    <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 text-xs font-bold scrollbar-none">
        <span class="text-slate-400 text-xs font-semibold me-1 inline-flex items-center gap-1 shrink-0">
            <i class="bi bi-funnel text-sky-600"></i> <?= is_rtl() ? 'حالة السداد:' : 'Payment:' ?>
        </span>
        <a href="<?= $buildSubFilterUrl('') ?>" class="px-3 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap <?= $status === '' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200/70 hover:bg-slate-100' ?>">
            <span><?= is_rtl() ? 'جميع الاشتراكات' : 'All Subscriptions' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['all'] ?>)</span>
        </a>
        <a href="<?= $buildSubFilterUrl('late') ?>" class="px-3 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $status === 'late' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' ?>">
            <i class="bi bi-exclamation-triangle-fill text-[11px]"></i>
            <span><?= is_rtl() ? 'متأخر عن السداد' : 'Overdue' ?></span>
            <span class="px-1.5 py-0.2 rounded-full font-numeric text-[11px] <?= $status === 'late' ? 'bg-white/20 text-white' : 'bg-rose-200/70 text-rose-800' ?>"><?= $counts['late'] ?></span>
        </a>
        <a href="<?= $buildSubFilterUrl('paid') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $status === 'paid' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-emerald-700 border-slate-200/70 hover:bg-emerald-50' ?>">
            <span><?= is_rtl() ? 'مسدد' : 'Paid' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['paid'] ?>)</span>
        </a>
        <a href="<?= $buildSubFilterUrl('partial') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $status === 'partial' ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-slate-50 text-amber-700 border-slate-200/70 hover:bg-amber-50' ?>">
            <span><?= is_rtl() ? 'مسدد جزئياً' : 'Partially Paid' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['partial'] ?>)</span>
        </a>
        <a href="<?= $buildSubFilterUrl('unpaid') ?>" class="px-2.5 py-1.5 rounded-xl border transition-all text-decoration-none whitespace-nowrap flex items-center gap-1.5 <?= $status === 'unpaid' ? 'bg-slate-700 text-white border-slate-700 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200/70 hover:bg-slate-50' ?>">
            <span><?= is_rtl() ? 'غير مسدد' : 'Unpaid' ?></span>
            <span class="opacity-75 font-numeric ms-1">(<?= $counts['unpaid'] ?>)</span>
        </a>
    </div>

    <?php if ($hasSubFilters): ?>
        <div class="flex items-center gap-2 shrink-0 justify-end border-t md:border-t-0 pt-2 md:pt-0 border-slate-100">
            <a href="<?= url('admin/subscriptions') ?>" class="btn btn-sm btn-soft text-slate-500 hover:text-rose-600 text-xs inline-flex items-center gap-1 font-bold py-1 px-2.5 rounded-xl border border-slate-200" title="<?= is_rtl() ? 'إعادة ضبط كل الفلاتر' : 'Reset all filters' ?>">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span><?= is_rtl() ? 'إلغاء الفلترة' : 'Reset' ?></span>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('member') ?></th>
                <th><?= __('shares') ?></th>
                <th><?= __('share_value_col') ?></th>
                <th><?= __('monthly_due_col') ?></th>
                <th><?= __('month_status_col', ['month' => e(month_label($currentMonth))]) ?></th>
                <th><?= __('paid_months_col') ?></th>
                <th><?= __('late_months_col') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): [$l, $v] = status_badge($row['current_status']); ?>
            <tr>
                <td class="fw-bold text-slate-900">
                    <a href="<?= url('admin/subscriptions/' . $row['member']['id']) ?>" class="text-slate-900 hover:text-sky-600 text-decoration-none">
                        <?= e($row['member']['name']) ?>
                    </a>
                </td>
                <td class="font-numeric"><?= number_format($row['member']['shares_count']) ?></td>
                <td class="font-numeric"><?= money($row['share_value']) ?></td>
                <td class="font-numeric font-bold text-slate-900"><?= money($row['amount']) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="font-numeric text-emerald-600 font-bold"><?= (int) $row['paid_months'] ?></td>
                <td class="font-numeric <?= $row['late_months'] > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' ?>"><?= (int) $row['late_months'] ?></td>
                <td class="text-end">
                    <a href="<?= url('admin/subscriptions/' . $row['member']['id']) ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1.5 font-bold">
                        <i class="bi bi-eye"></i>
                        <span><?= __('details_and_pay') ?></span>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="8" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-calendar-check text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_subscriptions_recorded') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
