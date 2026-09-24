<?php 
[$statusLabel, $statusVariant] = status_badge($member['status']); 
[$foundLabel, $foundVariant] = status_badge($founding['status']); 
$catLabels = transaction_categories();
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3.5">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-600 to-emerald-500 text-white font-black text-2xl flex items-center justify-center shadow-md shadow-sky-600/20">
            <?= e(mb_substr($member['name'] ?? 'M', 0, 1)) ?>
        </div>
        <div>
            <div class="flex items-center gap-2.5 mb-1">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 m-0"><?= e($member['name']) ?></h1>
                <span class="badge-status badge-<?= $statusVariant ?>"><?= $statusLabel ?></span>
            </div>
            <div class="text-xs text-slate-500 font-numeric font-medium flex items-center gap-2">
                <span dir="ltr"><?= e($member['mobile']) ?></span>
                <span>•</span>
                <span dir="ltr"><?= e($member['national_id'] ?? '-') ?></span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('admin/members/' . $member['id'] . '/edit') ?>" class="btn-soft-primary font-bold">
            <i class="bi bi-pencil-square"></i>
            <span><?= __('edit_data') ?></span>
        </a>
    </div>
</div>

<!-- 4 Executive KPI Metric Tiles -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <!-- Shares -->
    <div class="metric-tile metric-blue">
        <div class="metric-label"><?= __('shares_number') ?></div>
        <div class="metric-value font-numeric text-sky-950"><?= number_format($member['shares_count']) ?></div>
    </div>

    <!-- Founding Amount -->
    <div class="metric-tile metric-slate">
        <div class="metric-label"><?= __('founding_amount_label') ?></div>
        <div class="metric-value font-numeric text-slate-900"><?= money($founding['total_required']) ?></div>
        <div class="text-[11px] font-bold text-slate-400 mt-0.5"><?= __('paid_stat_sub', ['amount' => money($founding['amount_paid'])]) ?></div>
    </div>

    <!-- Loans Count -->
    <div class="metric-tile metric-amber">
        <div class="metric-label"><?= __('loans_count_stat') ?></div>
        <div class="metric-value font-numeric text-amber-700"><?= count($loans) ?></div>
    </div>

    <!-- Founding Status -->
    <div class="metric-tile metric-green">
        <div class="metric-label"><?= __('founding_status_stat') ?></div>
        <div class="mt-1.5">
            <span class="badge-status badge-<?= $foundVariant ?>"><?= $foundLabel ?></span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Personal Details Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>
                    <span><?= __('personal_info_title') ?></span>
                </h3>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                <table class="table-modern table-kv w-full">
                    <tbody>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60 w-2/5 sm:w-1/3"><?= __('email') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['email']) ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('birth_date') ?></td><td class="font-bold text-slate-900 text-xs font-numeric"><?= is_rtl() ? date_ar($member['birth_date']) : date('M d, Y', strtotime($member['birth_date'])) ?></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('national_address') ?></td><td class="font-bold text-slate-900 text-xs break-words"><?= e($member['national_address'] ?: '-') ?></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('bank_name') ?></td><td class="font-bold text-slate-900 text-xs"><?= e($member['bank_name'] ?: '-') ?></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('bank_account_number') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['bank_account_number'] ?: '-') ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('iban') ?></td><td class="font-bold text-slate-900 text-xs font-numeric break-all"><bdi dir="ltr"><?= e($member['iban'] ?: '-') ?></bdi></td></tr>
                        <tr><td class="text-slate-500 font-medium text-xs bg-slate-50/60"><?= __('registration_date') ?></td><td class="font-bold text-slate-900 text-xs font-numeric"><?= is_rtl() ? date_ar($member['created_at']) : date('M d, Y', strtotime($member['created_at'])) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Subscriptions Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head flex items-center justify-between">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>
                    <span><?= __('latest_monthly_subscriptions') ?></span>
                </h3>
                <a href="<?= url('admin/subscriptions/' . $member['id']) ?>" class="btn btn-sm btn-soft font-bold"><?= __('view_all') ?></a>
            </div>
            <?php if (empty($subscriptions)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-calendar-x text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_subscriptions_recorded') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('month') ?></th>
                                <th><?= __('due_amount') ?></th>
                                <th><?= __('paid_amount') ?></th>
                                <th><?= __('status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subscriptions as $s): [$l,$v] = status_badge($s['status']); ?>
                            <tr>
                                <td class="font-numeric font-bold text-slate-900"><?= e($s['month']) ?></td>
                                <td class="font-numeric font-bold"><?= money($s['amount_due']) ?></td>
                                <td class="font-numeric font-bold text-emerald-600"><?= money($s['amount_paid']) ?></td>
                                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Loans Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head flex items-center justify-between">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <span><?= __('loans') ?></span>
                </h3>
                <a href="<?= url('admin/loans') ?>?q=<?= urlencode($member['name']) ?>" class="btn btn-sm btn-soft font-bold"><?= __('view_all') ?></a>
            </div>
            <?php if (empty($loans)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-cash text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_loans_found_simple') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('value') ?></th>
                                <th><?= __('date') ?></th>
                                <th><?= __('remaining') ?></th>
                                <th><?= __('status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans as $l): [$lb,$v] = status_badge($l['status']); ?>
                            <tr>
                                <td class="font-numeric font-bold text-slate-900"><?= money($l['amount']) ?></td>
                                <td class="font-numeric text-xs text-slate-500"><?= is_rtl() ? date_ar($l['loan_date']) : date('M d, Y', strtotime($l['loan_date'])) ?></td>
                                <td class="font-numeric font-bold text-rose-600"><?= money($l['amount_remaining']) ?></td>
                                <td><a href="<?= url('admin/loans/' . $l['id']) ?>"><span class="badge-status badge-<?= $v ?>"><?= $lb ?></span></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Financial Transactions Card -->
    <div class="col-lg-6">
        <div class="card-panel h-100 space-y-4">
            <div class="panel-head">
                <h3 class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <span><?= __('recent_financial_transactions') ?></span>
                </h3>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="empty-state py-8">
                    <i class="bi bi-journal-x text-3xl text-slate-400 mb-2"></i>
                    <span><?= __('no_transactions_found_simple') ?></span>
                </div>
            <?php else: ?>
                <div class="table-panel border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th><?= __('type') ?></th>
                                <th><?= __('amount') ?></th>
                                <th><?= __('date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold"><?= $catLabels[$t['category']] ?? $t['category'] ?></span></td>
                                <td class="font-numeric font-bold text-slate-900"><?= money($t['amount']) ?></td>
                                <td class="font-numeric text-xs text-slate-500"><?= is_rtl() ? date_ar($t['transaction_date']) : date('M d, Y', strtotime($t['transaction_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
