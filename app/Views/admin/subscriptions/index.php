<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('subscriptions_index_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('subscriptions_index_desc', ['month' => e($currentMonth)]) ?></p>
    </div>
    <form method="get" action="<?= url('admin/subscriptions') ?>" class="search-box" style="min-width:280px;">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control" placeholder="<?= __('member_name_placeholder') ?>" value="<?= e($q) ?>">
    </form>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('member') ?></th>
                <th><?= __('shares') ?></th>
                <th><?= __('share_value_col') ?></th>
                <th><?= __('monthly_due_col') ?></th>
                <th><?= __('month_status_col', ['month' => e($currentMonth)]) ?></th>
                <th><?= __('paid_months_col') ?></th>
                <th><?= __('late_months_col') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): [$l, $v] = status_badge($row['current_status']); ?>
            <tr>
                <td class="fw-bold text-slate-900"><?= e($row['member']['name']) ?></td>
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
