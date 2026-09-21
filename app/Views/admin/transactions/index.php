<div class="card-panel mb-4">
    <form method="get" action="<?= url('admin/transactions') ?>" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-xs font-bold text-slate-700"><?= __('search_by_member_name') ?></label>
            <input type="text" name="search" class="form-control" placeholder="<?= __('member_name_placeholder') ?>" value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label text-xs font-bold text-slate-700"><?= __('transaction_category') ?></label>
            <select name="category" class="form-select">
                <option value=""><?= __('all') ?></option>
                <?php foreach ($categoryLabels as $key => $lbl): ?>
                <option value="<?= $key ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-xs font-bold text-slate-700"><?= __('from_date') ?></label>
            <input type="date" name="from" class="form-control font-numeric" value="<?= e($filters['from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label text-xs font-bold text-slate-700"><?= __('to_date') ?></label>
            <input type="date" name="to" class="form-control font-numeric" value="<?= e($filters['to'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill font-bold py-2.5">
                <i class="bi bi-funnel-fill"></i>
                <span><?= __('filter') ?></span>
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="<?= url('admin/transactions/export') ?>?<?= http_build_query($filters) ?>" class="btn-soft-primary text-xs">
        <i class="bi bi-file-earmark-spreadsheet"></i>
        <span><?= __('export_csv') ?></span>
    </a>
    <a href="<?= url('admin/transactions/print') ?>?<?= http_build_query($filters) ?>" target="_blank" rel="noopener" class="btn-soft-primary text-xs">
        <i class="bi bi-printer"></i>
        <span><?= __('export_pdf') ?></span>
    </a>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('date') ?></th>
                <th><?= __('transaction_category') ?></th>
                <th><?= __('member') ?></th>
                <th><?= __('amount') ?></th>
                <th><?= __('admin_user') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td class="text-xs text-slate-500 font-numeric"><?= is_rtl() ? date_ar($t['transaction_date']) : date('M d, Y', strtotime($t['transaction_date'])) ?></td>
                <td><span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold"><?= $categoryLabels[$t['category']] ?? $t['category'] ?></span></td>
                <td class="fw-bold text-slate-900"><?= e($t['member_name']) ?></td>
                <td class="font-numeric font-bold text-slate-900"><?= money($t['amount']) ?></td>
                <td class="text-xs text-slate-600"><?= e($t['admin_name'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($transactions)): ?>
            <tr>
                <td colspan="5" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-journal-x text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_transactions_recorded') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
