<?php
[$subLabel, $subVariant] = status_badge($currentSub['status'] ?? 'unpaid');
[$foundLabel, $foundVariant] = status_badge($founding['status']);

$foundingPaid = (float)($founding['amount_paid'] ?? 0);
$foundingTotal = (float)($founding['total_required'] ?? 0);
$foundingRemaining = max(0, $foundingTotal - $foundingPaid);
$foundingPercent = $foundingTotal > 0 ? min(100, round(($foundingPaid / $foundingTotal) * 100)) : 100;
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-brand-900 via-brand-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white mb-6 shadow-xl relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-brand-500/20 rounded-full blur-2xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-950/60 border border-brand-500/30 text-brand-300 text-xs font-bold mb-2">
                <span class="w-2 h-2 rounded-full bg-brand-400 animate-ping"></span>
                <span><?= __('active_account') ?></span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black mb-1"><?= __('welcome_back', ['name' => e($member['name'])]) ?></h2>
            <p class="text-brand-200 text-xs sm:text-sm max-w-xl">
                <?= __('member_summary_desc', ['month' => date('Y-m')]) ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('loans/request') ?>" class="px-5 py-2.5 rounded-xl bg-gold-500 hover:bg-gold-600 text-slate-950 font-extrabold text-sm shadow-md transition-all flex items-center gap-2">
                <i class="bi bi-cash-coin"></i>
                <span><?= __('request_loan') ?></span>
            </a>
            <a href="<?= url('share-requests') ?>" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 transition-all flex items-center gap-2">
                <i class="bi bi-pie-chart"></i>
                <span><?= __('adjust_shares') ?></span>
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-green">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('shares_count') ?></div>
                <div class="stat-value font-num"><?= number_format($member['shares_count']) ?></div>
                <div class="stat-sub"><?= __('share_value_colon') ?> <?= money($shareValue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-blue">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('monthly_subscription') ?></div>
                <div class="stat-value font-num"><?= money($monthlyAmount) ?></div>
                <div class="stat-sub">
                    <span class="badge-status badge-<?= $subVariant ?>"><?= $subLabel ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-amber">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('estimated_balance') ?></div>
                <div class="stat-value font-num"><?= money($balance) ?></div>
                <div class="stat-sub text-emerald-600 font-bold"><?= __('investment_wallet') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-purple">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('active_loans') ?></div>
                <div class="stat-value font-num"><?= $activeLoansCount ?></div>
                <div class="stat-sub"><?= __('remaining_to_pay', ['amount' => money($activeLoansRemaining)]) ?></div>
                <div class="stat-sub"><?= __('remaining_installments') ?>: <b class="font-num"><?= (int) $remainingInstallments ?></b></div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Detailed Cards -->
<div class="row g-3 mb-4">
    <!-- Subscriptions Progress -->
    <div class="col-lg-4">
        <div class="card-panel h-100 flex flex-col justify-between">
            <div>
                <div class="panel-head">
                    <h3><i class="bi bi-calendar2-check-fill text-brand-600"></i> <?= __('monthly_subscription') ?></h3>
                    <span class="badge-status badge-<?= $subVariant ?>"><?= $subLabel ?></span>
                </div>
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between items-center text-sm py-1 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('paid_months') ?></span>
                        <b class="text-emerald-600 font-num"><?= $paidMonths ?> <?= __('months') ?></b>
                    </div>
                    <div class="flex justify-between items-center text-sm py-1 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('late_months') ?></span>
                        <b class="<?= $lateMonths > 0 ? 'text-rose-600' : 'text-slate-700' ?> font-num"><?= $lateMonths ?> <?= __('months') ?></b>
                    </div>
                    <div class="flex justify-between items-center text-sm py-1">
                        <span class="text-slate-500 font-medium"><?= __('monthly_due') ?></span>
                        <b class="text-slate-900 font-num"><?= money($monthlyAmount) ?></b>
                    </div>
                </div>
            </div>
            <a href="<?= url('shares') ?>" class="btn btn-soft w-100">
                <span><?= __('details') ?></span>
                <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
            </a>
        </div>
    </div>

    <!-- Founding Capital -->
    <div class="col-lg-4">
        <div class="card-panel h-100 flex flex-col justify-between">
            <div>
                <div class="panel-head">
                    <h3><i class="bi bi-bank2 text-sky-600"></i> <?= __('founding_amount') ?></h3>
                    <span class="badge-status badge-<?= $foundVariant ?>"><?= $foundLabel ?></span>
                </div>
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500"><?= __('required_amount') ?></span>
                        <b class="font-num"><?= money($foundingTotal) ?></b>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500"><?= __('paid_amount') ?></span>
                        <b class="text-emerald-600 font-num"><?= money($foundingPaid) ?></b>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500"><?= __('remaining_amount') ?></span>
                        <b class="<?= $foundingRemaining > 0 ? 'text-rose-600' : 'text-slate-700' ?> font-num"><?= money($foundingRemaining) ?></b>
                    </div>
                    <!-- Progress Bar -->
                    <div class="mt-2">
                        <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
                            <span><?= __('progress_ratio') ?></span>
                            <span class="font-num"><?= $foundingPercent ?>%</span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-brand-500 to-brand-600 rounded-full transition-all" style="width: <?= $foundingPercent ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <a href="<?= url('founding') ?>" class="btn btn-soft w-100">
                <span><?= __('details') ?></span>
                <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card-panel h-100 flex flex-col justify-between">
            <div>
                <div class="panel-head">
                    <h3><i class="bi bi-lightning-charge-fill text-gold-500"></i> <?= __('quick_actions') ?></h3>
                </div>
                <div class="space-y-2 mb-4">
                    <a href="<?= url('share-requests') ?>" class="btn btn-soft w-100 !justify-between">
                        <span class="flex items-center gap-2">
                            <i class="bi bi-arrow-left-right text-brand-600"></i>
                            <span><?= __('adjust_shares') ?></span>
                        </span>
                        <i class="bi bi-chevron-<?= is_rtl() ? 'left' : 'right' ?> text-xs text-slate-400"></i>
                    </a>
                    <a href="<?= url('loans/request') ?>" class="btn btn-soft w-100 !justify-between">
                        <span class="flex items-center gap-2">
                            <i class="bi bi-cash-coin text-gold-600"></i>
                            <span><?= __('request_loan') ?></span>
                        </span>
                        <i class="bi bi-chevron-<?= is_rtl() ? 'left' : 'right' ?> text-xs text-slate-400"></i>
                    </a>
                    <a href="<?= url('settings') ?>" class="btn btn-soft w-100 !justify-between">
                        <span class="flex items-center gap-2">
                            <i class="bi bi-headset text-sky-600"></i>
                            <span><?= __('support') ?></span>
                        </span>
                        <i class="bi bi-chevron-<?= is_rtl() ? 'left' : 'right' ?> text-xs text-slate-400"></i>
                    </a>
                </div>
            </div>
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-xs text-slate-500 flex items-center gap-2">
                <i class="bi bi-shield-check text-brand-600 text-base"></i>
                <span><?= __('transactions_bylaws_notice') ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Transactions History Panel -->
<div class="card-panel">
    <div class="panel-head">
        <h3><i class="bi bi-clock-history text-brand-600"></i> <?= __('recent_transactions') ?></h3>
        <span class="text-xs text-slate-400"><?= __('realtime_transactions_ledger') ?></span>
    </div>
    <?php
    $catLabels = [
        'subscription'      => __('cat_subscription'),
        'founding'          => __('cat_founding'),
        'loan_disbursement' => __('cat_loan_disbursement'),
        'loan_installment'  => __('cat_loan_installment'),
        'loan_admin_fee'    => __('cat_loan_admin_fee'),
    ];
    $catIcons = [
        'subscription'      => 'bi-calendar2-check-fill text-brand-600',
        'founding'          => 'bi-bank2 text-sky-600',
        'loan_disbursement' => 'bi-arrow-down-left-circle-fill text-emerald-600',
        'loan_installment'  => 'bi-arrow-up-right-circle-fill text-blue-600',
        'loan_admin_fee'    => 'bi-receipt text-amber-600',
    ];
    ?>
    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <p class="font-bold text-slate-600 mb-1"><?= __('no_transactions') ?></p>
        </div>
    <?php else: ?>
    <div class="table-panel">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><?= __('type_and_desc') ?></th>
                    <th><?= __('amount') ?></th>
                    <th><?= __('date') ?></th>
                    <th><?= __('status') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-base">
                                <i class="bi <?= $catIcons[$t['category']] ?? 'bi-cash' ?>"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-900"><?= $catLabels[$t['category']] ?? $t['category'] ?></div>
                                <?php if (!empty($t['notes'])): ?>
                                    <div class="text-xs text-slate-400"><?= e($t['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="font-bold text-slate-900 font-num"><?= money($t['amount']) ?></td>
                    <td class="text-slate-500 font-num"><?= date_ar($t['transaction_date']) ?></td>
                    <td>
                        <span class="badge-status badge-success"><?= __('status_completed') ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
