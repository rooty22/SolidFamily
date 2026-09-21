<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('loans_management_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('loans_management_desc') ?></p>
    </div>
    <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap">
        <form method="get" action="<?= url('admin/loans') ?>" class="search-box" style="min-width:260px;">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="<?= __('search_by_member_loan') ?>" value="<?= e($q) ?>">
        </form>
        <a href="<?= url('admin/loans/create') ?>" class="btn btn-primary inline-flex items-center gap-2 whitespace-nowrap shrink-0 font-bold">
            <i class="bi bi-plus-circle"></i>
            <span><?= __('add_loan') ?></span>
        </a>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('member') ?></th>
                <th><?= __('value') ?></th>
                <th><?= __('date') ?></th>
                <th><?= __('reason') ?></th>
                <th><?= __('installments') ?></th>
                <th><?= __('installment_value') ?></th>
                <th><?= __('paid') ?></th>
                <th><?= __('remaining') ?></th>
                <th><?= __('repayment_rate') ?></th>
                <th><?= __('admin_fee') ?></th>
                <th><?= __('status') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($loans as $l): [$lb, $v] = status_badge($l['status']); ?>
            <tr>
                <td class="fw-bold text-slate-900"><?= e($l['member_name']) ?></td>
                <td class="font-numeric font-bold text-slate-900"><?= money($l['amount']) ?></td>
                <td class="text-xs text-slate-500 font-numeric"><?= is_rtl() ? date_ar($l['loan_date']) : date('M d, Y', strtotime($l['loan_date'])) ?></td>
                <td><span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold"><?= e(($reasonLabels[$l['reason']] ?? $l['reason'])) ?></span></td>
                <td class="font-numeric"><?= $l['installments_count'] ?></td>
                <td class="font-numeric"><?= money($l['installment_value']) ?></td>
                <td class="font-numeric text-emerald-600 font-bold"><?= money($l['amount_paid']) ?></td>
                <td class="font-numeric text-rose-500 font-bold"><?= money($l['amount_remaining']) ?></td>
                <td class="font-numeric"><?= (float) $l['amount'] > 0 ? min(100, round($l['amount_paid'] / $l['amount'] * 100)) : 0 ?>%</td>
                <td class="font-numeric"><?= money($l['admin_fee_amount']) ?> <span class="text-xs text-slate-400">(<?= rtrim(rtrim(number_format((float) $l['admin_fee_percent'], 2), '0'), '.') ?>%)</span></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $lb ?></span></td>
                <td class="text-end">
                    <a href="<?= url('admin/loans/' . $l['id']) ?>" class="btn btn-sm btn-soft" title="<?= __('view') ?>"><i class="bi bi-eye"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($loans)): ?>
            <tr>
                <td colspan="12" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-cash-coin text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_loans_recorded') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
