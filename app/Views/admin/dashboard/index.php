<!-- Top Dashboard Action Bar -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('admin_overview') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('admin_overview_sub') ?></p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('admin/dashboard/export') ?>" class="btn btn-soft">
            <i class="bi bi-file-earmark-excel text-emerald-600"></i>
            <span><?= __('export_report') ?></span>
        </a>
        <a href="<?= url('admin/dashboard/print') ?>" target="_blank" rel="noopener" class="btn btn-soft">
            <i class="bi bi-file-earmark-pdf text-rose-600"></i>
            <span>PDF</span>
        </a>
    </div>
</div>

<!-- Executive KPI Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('total_members') ?></div>
                <div class="stat-value font-num"><?= number_format($stats['totalMembers']) ?></div>
                <div class="stat-sub text-slate-400"><?= is_rtl() ? 'عضوية عائلية نشطة' : 'Active family accounts' ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-purple">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('total_shares') ?></div>
                <div class="stat-value font-num"><?= number_format($stats['totalShares']) ?></div>
                <div class="stat-sub text-slate-400"><?= is_rtl() ? 'سهم استثماري تكافلي' : 'Registered shares' ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-green">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('fund_balance') ?></div>
                <div class="stat-value font-num"><?= money($stats['fundBalance']) ?></div>
                <div class="stat-sub text-emerald-600 font-bold"><?= is_rtl() ? 'السيولة المتاحة' : 'Available liquidity' ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-red">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('late_members') ?></div>
                <div class="stat-value font-num"><?= number_format($stats['lateMembers']) ?></div>
                <div class="stat-sub text-rose-500 font-bold"><?= is_rtl() ? 'يحتاجون إلى متابعة' : 'Requires follow-up' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3><i class="bi bi-bar-chart-fill text-sky-600"></i> <?= __('subs_trend') ?></h3>
                <span class="text-xs text-slate-400 font-medium"><?= is_rtl() ? 'مقارنة المستحق والمسدد الفعلي' : 'Due vs. Collected comparison' ?></span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="subsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3><i class="bi bi-pie-chart text-emerald-600"></i> <?= __('subs_collection_status') ?></h3>
                <span class="text-xs text-slate-400 font-medium"><?= is_rtl() ? 'نسبة السداد الإجمالية' : 'Overall collection ratio' ?></span>
            </div>
            <div style="position: relative; height: 200px;">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 space-y-1 text-xs">
                <div class="d-flex justify-content-between">
                    <span class="text-slate-500"><?= is_rtl() ? 'إجمالي قيمة الاشتراكات الشهرية (الشهر الحالي)' : 'Total monthly subscriptions (this month)' ?></span>
                    <b class="font-num text-slate-900"><?= money($stats['subRow']['total_due']) ?></b>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-slate-500"><?= is_rtl() ? 'المحصّل منها' : 'Collected' ?></span>
                    <b class="font-num text-emerald-600"><?= money($stats['subRow']['total_paid']) ?></b>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-slate-500"><?= is_rtl() ? 'مسدد / جزئي / غير مسدد' : 'Paid / Partial / Unpaid' ?></span>
                    <b class="font-num"><?= (int) $stats['subRow']['paid_count'] ?> / <?= (int) $stats['subRow']['partial_count'] ?> / <?= (int) $stats['subRow']['unpaid_count'] ?></b>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Stats & Quick Action Feeds -->
<div class="row g-3">
    <!-- Founding Capital Stats -->
    <div class="col-xl-4">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3><i class="bi bi-bank2 text-sky-600"></i> <?= __('manage_founding') ?></h3>
                <a href="<?= url('admin/founding') ?>" class="text-xs font-bold text-sky-600 hover:underline"><?= is_rtl() ? 'عرض الكل' : 'View All' ?></a>
            </div>
            <div class="space-y-3">
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= __('required_amount') ?></span>
                    <b class="font-num text-slate-900"><?= money($stats['foundRow']['total_required']) ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= __('status_paid') ?></span>
                    <b class="text-emerald-600 font-num"><?= (int)$stats['foundRow']['paid_count'] ?> <?= is_rtl() ? 'عضو' : 'members' ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= __('status_partial') ?></span>
                    <b class="text-amber-600 font-num"><?= (int)$stats['foundRow']['partial_count'] ?> <?= is_rtl() ? 'عضو' : 'members' ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5">
                    <span class="text-slate-500"><?= __('status_unpaid') ?></span>
                    <b class="text-rose-600 font-num"><?= (int)$stats['foundRow']['unpaid_count'] ?> <?= is_rtl() ? 'عضو' : 'members' ?></b>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Summary -->
    <div class="col-xl-4">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3><i class="bi bi-cash-coin text-amber-500"></i> <?= __('manage_loans') ?></h3>
                <a href="<?= url('admin/loans') ?>" class="text-xs font-bold text-amber-600 hover:underline"><?= is_rtl() ? 'عرض الكل' : 'View All' ?></a>
            </div>
            <div class="space-y-3">
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= is_rtl() ? 'إجمالي القروض المصروفة' : 'Disbursed Loans' ?></span>
                    <b class="font-num text-slate-900"><?= (int)$stats['loanRow']['total_loans'] ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= is_rtl() ? 'القروض: مسدد / جزئي / غير مسدد' : 'Loans: Paid / Partial / Unpaid' ?></span>
                    <b class="font-num text-slate-900"><?= (int) $stats['loanRow']['paid_count'] ?> / <?= (int) $stats['loanRow']['partial_count'] ?> / <?= (int) $stats['loanRow']['active_count'] ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= is_rtl() ? 'إجمالي طلبات القروض' : 'Total Loan Requests' ?></span>
                    <b class="text-amber-600 font-num"><?= (int)$stats['loanRequestsCount'] ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500"><?= is_rtl() ? 'إيرادات المصاريف الإدارية' : 'Admin Fee Revenues' ?></span>
                    <b class="text-emerald-600 font-num"><?= money($stats['loanRow']['total_fees']) ?></b>
                </div>
                <div class="d-flex justify-content-between py-1.5">
                    <span class="text-slate-500"><?= is_rtl() ? 'إجمالي طلبات الأسهم' : 'Total Share Requests' ?></span>
                    <b class="text-sky-600 font-num"><?= (int)$stats['shareRequestsCount'] ?></b>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts -->
    <div class="col-xl-4">
        <div class="card-panel h-100">
            <div class="panel-head">
                <h3><i class="bi bi-lightning-charge-fill text-gold-500"></i> <?= __('quick_actions') ?></h3>
            </div>
            <div class="d-grid gap-2">
                <a href="<?= url('admin/loan-requests') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-amber-600"></i>
                        <span><?= __('manage_loan_requests') ?></span>
                    </span>
                    <?php if ($stats['loanRequestsCount'] > 0): ?>
                        <span class="badge-status badge-warning font-num"><?= $stats['loanRequestsCount'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('admin/share-requests') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-arrow-left-right text-sky-600"></i>
                        <span><?= __('manage_share_requests') ?></span>
                    </span>
                    <?php if ($stats['shareRequestsCount'] > 0): ?>
                        <span class="badge-status badge-info font-num"><?= $stats['shareRequestsCount'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('admin/members/create') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-person-plus-fill text-emerald-600"></i>
                        <span><?= is_rtl() ? 'إضافة مشترك جديد' : 'Add New Member' ?></span>
                    </span>
                    <i class="bi bi-plus-lg text-slate-400"></i>
                </a>
                <a href="<?= url('admin/notifications') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-broadcast text-purple-600"></i>
                        <span><?= __('manage_notifications') ?></span>
                    </span>
                    <i class="bi bi-send text-slate-400"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trend = <?= json_encode($stats['monthlyTrend'], JSON_UNESCAPED_UNICODE) ?>;
    const isRtl = <?= is_rtl() ? 'true' : 'false' ?>;
    
    // Subscriptions Bar Chart
    const subsCtx = document.getElementById('subsChart');
    if (subsCtx) {
        new Chart(subsCtx, {
            type: 'bar',
            data: {
                labels: trend.map(r => r.month),
                datasets: [
                    {
                        label: isRtl ? 'المستحق الشهري' : 'Monthly Due',
                        data: trend.map(r => r.due),
                        backgroundColor: '#cbd5e1',
                        borderRadius: 8,
                        barPercentage: 0.6
                    },
                    {
                        label: isRtl ? 'المسدد الفعلي' : 'Actual Paid',
                        data: trend.map(r => r.paid),
                        backgroundColor: '#0284c7',
                        borderRadius: 8,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: isRtl ? 'Cairo' : 'Plus Jakarta Sans', size: 12, weight: '700' },
                            usePointStyle: true
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
                }
            }
        });
    }

    // Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    '<?= __('status_paid') ?>',
                    '<?= __('status_partial') ?>',
                    '<?= __('status_unpaid') ?>'
                ],
                datasets: [{
                    data: [
                        <?= (int)$stats['subRow']['paid_count'] ?>,
                        <?= (int)$stats['subRow']['partial_count'] ?>,
                        <?= (int)$stats['subRow']['unpaid_count'] ?>
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: isRtl ? 'Cairo' : 'Plus Jakarta Sans', size: 12, weight: '700' },
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>
