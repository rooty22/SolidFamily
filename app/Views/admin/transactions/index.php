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

<?php if (!empty($summary)): ?>
<div class="rounded-2xl p-4 p-md-5 mb-4 text-white shadow-lg" style="background: linear-gradient(135deg, #0f172a 0%, #0c4a6e 55%, #0284c7 100%);">
    <div class="row g-4 align-items-center">
        <div class="col-lg-5">
            <div class="flex items-center gap-2 text-sky-200 text-xs font-bold mb-1">
                <i class="bi bi-calculator-fill"></i>
                <span><?= is_rtl() ? 'إجمالي نتيجة التصفية' : 'Filtered Results Total' ?></span>
            </div>
            <div class="font-numeric font-black" style="font-size: 2.1rem; line-height: 1.15;"><?= money($summary['total']) ?></div>
            <div class="text-sky-100/80 text-xs font-bold mt-1">
                <?= is_rtl() ? 'من ' . (int) $summary['count'] . ' معاملة' : 'from ' . (int) $summary['count'] . ' transactions' ?>
                <?php if (!empty($filters['from']) || !empty($filters['to'])): ?>
                    · <bdi dir="ltr"><?= !empty($filters['from']) ? date_ar($filters['from']) : '...' ?> - <?= !empty($filters['to']) ? date_ar($filters['to']) : '...' ?></bdi>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-2">
                <div class="col-sm-6">
                    <div class="rounded-xl p-3" style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="text-emerald-200 text-[11px] font-bold"><i class="bi bi-arrow-down-circle-fill"></i> <?= is_rtl() ? 'الوارد (اشتراكات وأقساط ورسوم)' : 'Incoming (subscriptions, installments, fees)' ?></div>
                        <div class="font-numeric font-extrabold text-lg mt-1"><?= money($summary['incoming']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="rounded-xl p-3" style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="text-amber-200 text-[11px] font-bold"><i class="bi bi-arrow-up-circle-fill"></i> <?= is_rtl() ? 'المصروف (قروض)' : 'Disbursed (loans)' ?></div>
                        <div class="font-numeric font-extrabold text-lg mt-1"><?= money($summary['disbursed']) ?></div>
                    </div>
                </div>
            </div>
            <?php if (count($summary['byCategory']) > 1): ?>
            <div class="flex flex-wrap gap-1.5 mt-2.5">
                <?php foreach ($summary['byCategory'] as $cat => $catTotal): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold" style="background: rgba(255,255,255,0.12);">
                        <span><?= e($categoryLabels[$cat] ?? $cat) ?></span>
                        <span class="font-numeric text-sky-200"><?= money($catTotal) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
                <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($t['transaction_date']) ?></bdi></td>
                <td>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold"><?= $categoryLabels[$t['category']] ?? $t['category'] ?></span>
                    <?php if (!empty($t['display_details'])): ?>
                        <div class="text-[11px] text-slate-600 font-semibold mt-1 flex items-center gap-1">
                            <i class="bi bi-info-circle text-slate-400"></i>
                            <span><?= e(localize_dates($t['display_details'])) ?></span>
                        </div>
                    <?php endif; ?>
                </td>
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
