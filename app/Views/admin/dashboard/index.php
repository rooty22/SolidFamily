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

<!-- Executive KPI Stat Cards (Financial & Operational Breakdown) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Fund Balance (Includes cash in bank + remaining loans portfolio) -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-green">
                <i class="bi bi-bank2"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('total_fund_balance') ?></div>
                <div class="stat-value font-num text-emerald-700"><?= money($stats['totalFundBalance']) ?></div>
                <div class="stat-sub text-slate-500 font-medium">
                    <?= is_rtl() ? 'شامل سيولة البنك ومتبقي القروض المستحقة' : 'Includes bank cash + active loans portfolio' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Actual Bank Balance (Liquidity available after loans disbursed & expenses) -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-blue">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('actual_bank_balance') ?></div>
                <div class="stat-value font-num text-sky-800"><?= money($stats['bankBalance']) ?></div>
                <div class="stat-sub text-emerald-600 font-bold">
                    <?= is_rtl() ? 'السيولة المتاحة بعد خصم القروض والمصروفات' : 'Available cash after loans & expenses' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Earned Loan Commissions (Admin Revenue) -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-purple">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="stat-label"><?= __('loan_admin_fees_stat') ?></div>
                <div class="stat-value font-num text-purple-700"><?= money($stats['loanAdminFees']) ?></div>
                <div class="stat-sub text-purple-600 font-bold">
                    <?= is_rtl() ? 'إيرادات الإدارة المحققة (متوسط ' . round($stats['avgFeePercent'], 1) . '%)' : 'Earned loan admin revenue (avg ' . round($stats['avgFeePercent'], 1) . '%)' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Approved Members -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-grad-blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-label"><?= is_rtl() ? 'المشتركون المعتمدون' : 'Approved members' ?></div>
                <div class="stat-value font-num"><?= number_format($stats['totalMembers']) ?></div>
                <div class="stat-sub text-slate-500 font-medium">
                    <?= is_rtl() ? number_format($stats['activeMembers']) . ' نشط' : number_format($stats['activeMembers']) . ' active' ?>
                    <?php if ($stats['suspendedMembers'] > 0): ?> · <?= is_rtl() ? number_format($stats['suspendedMembers']) . ' موقوف' : number_format($stats['suspendedMembers']) . ' suspended' ?><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Total Shares -->
    <div class="col-xl-4 col-md-6">
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

    <!-- 6. Overdue Members (Clickable with delay type breakdown) -->
    <div class="col-xl-4 col-md-6">
        <a href="<?= url('admin/members?payment_status=late') ?>" class="stat-card text-decoration-none block transition-all hover:scale-[1.02] hover:shadow-lg hover:border-rose-400 group cursor-pointer" title="<?= is_rtl() ? 'انتقال إلى قائمة المشتركين المتأخرين' : 'View overdue members' ?>">
            <div class="d-flex items-center justify-between mb-2">
                <div class="stat-icon bg-grad-red group-hover:scale-110 transition-transform">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                    <span><?= is_rtl() ? 'عرض المتأخرين' : 'View Overdue' ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>-short"></i>
                </span>
            </div>
            <div>
                <div class="stat-label text-slate-500 font-medium"><?= __('late_members') ?></div>
                <div class="stat-value font-num text-rose-600"><?= number_format($stats['lateMembers']) ?></div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-rose-500 font-bold"><?= is_rtl() ? 'يحتاجون إلى متابعة' : 'Requires follow-up' ?></span>
                    <div class="flex items-center gap-1.5 text-[11px] font-numeric">
                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold border border-rose-200" title="<?= is_rtl() ? 'متأخر في الاشتراك الشهري' : 'Overdue Subscriptions' ?>">
                            <?= is_rtl() ? 'اشتراك: ' : 'Sub: ' ?><?= (int) ($stats['lateSubsCount'] ?? 0) ?>
                        </span>
                        <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold border border-amber-200" title="<?= is_rtl() ? 'متأخر في أقساط القروض' : 'Overdue Loans' ?>">
                            <?= is_rtl() ? 'قروض: ' : 'Loan: ' ?><?= (int) ($stats['lateLoansCount'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Shares deducted by merges / given back by splits -->
<?php $ms = $stats['mergeSummary']; ?>
<div class="card-panel mb-4">
    <div class="panel-head">
        <h3><i class="bi bi-arrows-collapse text-purple-600"></i> <?= is_rtl() ? 'الأسهم المخصومة بالدمج والمفصولة' : 'Shares deducted by merge / restored by split' ?></h3>
        <span class="text-xs text-slate-400 font-medium"><?= is_rtl() ? 'المبلغ = عدد الأسهم × قيمة السهم الشهرية وقت العملية' : 'Amount = shares × monthly share value at the time' ?></span>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="rounded-xl p-3 border border-rose-200 bg-rose-50">
                <div class="text-[11px] font-bold text-rose-700"><i class="bi bi-arrows-collapse"></i> <?= is_rtl() ? 'مخصومة بسبب الدمج' : 'Deducted by merges' ?> <span class="font-numeric">(<?= (int) $ms['merged']['count'] ?>)</span></div>
                <div class="font-num font-extrabold text-xl text-rose-700 mt-1"><?= money($ms['merged']['amount']) ?></div>
                <div class="text-xs text-rose-600 font-bold font-numeric"><?= number_format($ms['merged']['shares']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-xl p-3 border border-emerald-200 bg-emerald-50">
                <div class="text-[11px] font-bold text-emerald-700"><i class="bi bi-arrows-expand"></i> <?= is_rtl() ? 'مفصولة (أُعيدت بعد الدمج)' : 'Split (restored)' ?> <span class="font-numeric">(<?= (int) $ms['split']['count'] ?>)</span></div>
                <div class="font-num font-extrabold text-xl text-emerald-700 mt-1"><?= money($ms['split']['amount']) ?></div>
                <div class="text-xs text-emerald-600 font-bold font-numeric"><?= number_format($ms['split']['shares']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-xl p-3 border border-slate-200 bg-slate-50">
                <div class="text-[11px] font-bold text-slate-600"><i class="bi bi-calculator"></i> <?= is_rtl() ? 'المخصوم حاليًا (الدمج − الفصل)' : 'Currently deducted (merge − split)' ?></div>
                <div class="font-num font-extrabold text-xl text-slate-800 mt-1"><?= money($ms['net_amount']) ?></div>
                <div class="text-xs text-slate-500 font-bold font-numeric"><?= number_format($ms['net_shares']) ?> <?= is_rtl() ? 'سهم' : 'shares' ?></div>
            </div>
        </div>
    </div>
    <?php if (!empty($ms['recent'])): ?>
    <div class="table-responsive mt-3">
        <table class="table-modern text-xs">
            <thead>
                <tr>
                    <th><?= __('date') ?></th>
                    <th><?= __('member') ?></th>
                    <th><?= is_rtl() ? 'العملية' : 'Action' ?></th>
                    <th><?= is_rtl() ? 'الأسهم' : 'Shares' ?></th>
                    <th><?= __('amount') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ms['recent'] as $r): ?>
                <tr>
                    <td class="font-numeric"><bdi dir="ltr"><?= date_ar(substr($r['created_at'], 0, 10)) ?></bdi></td>
                    <td class="fw-bold"><?= e($r['member_name']) ?></td>
                    <td>
                        <?php if ($r['action'] === 'merge'): ?>
                            <span class="badge-status badge-danger"><?= is_rtl() ? 'دمج (خصم)' : 'Merge (deduct)' ?></span>
                        <?php else: ?>
                            <span class="badge-status badge-success"><?= is_rtl() ? 'فصل (إعادة)' : 'Split (restore)' ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="font-numeric"><?= number_format($r['shares']) ?></td>
                    <td class="font-numeric fw-bold"><?= money($r['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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
                    <b class="font-num"><?= (int) $stats['subRow']['paid_count'] ?> / <?= (int) $stats['subRow']['partial_count'] ?> / <?= (int) $stats['subRow']['unpaid_count'] ?><?php if (!empty($stats['subRow']['late_count'])): ?> <small class="text-rose-600">(<?= is_rtl() ? 'منها ' . (int) $stats['subRow']['late_count'] . ' متأخر' : (int) $stats['subRow']['late_count'] . ' late' ?>)</small><?php endif; ?></b>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dedicated Section: متابعة وتفصيل عمولات القروض وإيرادات الإدارة (Admin Revenue Breakdown) -->
<div class="card-panel mb-4">
    <div class="panel-head flex items-center justify-between">
        <h3 class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm shadow-xs">
                <i class="bi bi-cash-stack"></i>
            </div>
            <span><?= is_rtl() ? 'متابعة العمولات المكتسبة من القروض التفصيلية (إيرادات الإدارة)' : 'Loan Admin Commissions & Revenue Breakdown' ?></span>
        </h3>
        <a href="<?= url('admin/loans') ?>" class="text-xs font-bold text-purple-600 hover:underline inline-flex items-center gap-1">
            <span><?= is_rtl() ? 'عرض كل القروض' : 'View All Loans' ?></span>
            <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>-short"></i>
        </a>
    </div>

    <!-- 4 Quick Metric Badges for Loan Revenue -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100">
            <div class="text-xs text-purple-700 font-bold"><?= is_rtl() ? 'إجمالي العمولات الإدارية المكتسبة' : 'Total Admin Fees Earned' ?></div>
            <div class="text-xl font-black font-numeric text-purple-900 mt-1"><?= money($stats['loanAdminFees']) ?></div>
            <div class="text-[11px] text-purple-600 mt-0.5"><?= is_rtl() ? 'إيرادات فعلية محققة للصندوق' : 'Net fund revenue earned' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
            <div class="text-xs text-slate-500 font-bold"><?= is_rtl() ? 'إجمالي مبالغ القروض الصادرة' : 'Total Disbursed Loans' ?></div>
            <div class="text-xl font-black font-numeric text-slate-900 mt-1"><?= money($stats['totalLoansAmount']) ?></div>
            <div class="text-[11px] text-slate-400 mt-0.5"><?= (int) $stats['loanRow']['total_loans'] ?> <?= is_rtl() ? 'قروض تم صرفها' : 'disbursed loans' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
            <div class="text-xs text-emerald-800 font-bold"><?= is_rtl() ? 'متوسط نسبة العمولة الإدارية' : 'Avg Commission Rate' ?></div>
            <div class="text-xl font-black font-numeric text-emerald-700 mt-1"><?= round($stats['avgFeePercent'], 2) ?>%</div>
            <div class="text-[11px] text-emerald-600 mt-0.5"><?= is_rtl() ? 'نسبة المصاريف على أصل القرض' : 'Fee on principal' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-sky-50/60 border border-sky-100">
            <div class="text-xs text-sky-800 font-bold"><?= is_rtl() ? 'مستحقات القروض المتبقية' : 'Remaining Loans Portfolio' ?></div>
            <div class="text-xl font-black font-numeric text-sky-800 mt-1"><?= money($stats['loansRemaining']) ?></div>
            <div class="text-[11px] text-sky-600 mt-0.5"><?= is_rtl() ? 'أصول مستحقة التحصيل' : 'Receivable loan assets' ?></div>
        </div>
    </div>

    <!-- Itemized Table of Loan Commissions -->
    <?php if (empty($stats['detailedFeeLoans'])): ?>
        <div class="empty-state py-6">
            <i class="bi bi-cash-coin text-3xl text-slate-400 mb-2"></i>
            <span><?= is_rtl() ? 'لا توجد عمولات قروض مسجلة حتى الآن' : 'No loan commissions recorded yet' ?></span>
        </div>
    <?php else: ?>
        <div class="table-panel border-0 shadow-none">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th><?= is_rtl() ? 'المشترك / رقم القرض' : 'Member / Loan #' ?></th>
                        <th><?= is_rtl() ? 'قيمة القرض' : 'Loan Amount' ?></th>
                        <th><?= is_rtl() ? 'نسبة العمولة' : 'Fee %' ?></th>
                        <th><?= is_rtl() ? 'العمولة المكتسبة' : 'Admin Fee Earned' ?></th>
                        <th><?= is_rtl() ? 'تاريخ الصرف' : 'Disbursement Date' ?></th>
                        <th><?= __('status') ?></th>
                        <th class="text-end"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stats['detailedFeeLoans'] as $fl): [$flLabel, $flVariant] = status_badge($fl['status']); ?>
                    <tr>
                        <td>
                            <div class="font-bold text-slate-900"><?= e($fl['member_name']) ?></div>
                            <div class="text-[11px] text-slate-400 font-numeric">#<?= $fl['id'] ?></div>
                        </td>
                        <td class="font-numeric font-bold text-slate-900"><?= money($fl['amount']) ?></td>
                        <td class="font-numeric font-bold text-slate-700"><?= rtrim(rtrim(number_format((float) $fl['admin_fee_percent'], 2), '0'), '.') ?>%</td>
                        <td class="font-numeric font-bold text-purple-700"><?= money($fl['admin_fee_amount']) ?></td>
                        <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($fl['loan_date']) ?></bdi></td>
                        <td><span class="badge-status badge-<?= $flVariant ?>"><?= $flLabel ?></span></td>
                        <td class="text-end">
                            <a href="<?= url('admin/loans/' . $fl['id']) ?>" class="btn btn-sm btn-soft" title="<?= __('view') ?>">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Dedicated Section: طلبات التسجيل بانتظار الموافقة (Registration Requests Awaiting Approval) -->
<div class="card-panel mb-4 <?= $stats['pendingRegistrations'] > 0 ? 'border-amber-300 shadow-sm' : '' ?>">
    <div class="panel-head flex items-center justify-between">
        <h3 class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl <?= $stats['pendingRegistrations'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' ?> flex items-center justify-center text-sm shadow-xs">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <span><?= is_rtl() ? 'طلبات التسجيل بانتظار الموافقة' : 'Registration Requests Awaiting Approval' ?></span>
            <?php if ($stats['pendingRegistrations'] > 0): ?>
                <span class="badge-status badge-warning font-numeric">
                    <?= number_format($stats['pendingRegistrations']) ?> <?= is_rtl() ? 'طلب بانتظار القرار' : 'pending' ?>
                </span>
            <?php else: ?>
                <span class="badge-status badge-success text-[11px] font-bold">
                    <i class="bi bi-check2-circle"></i> <?= is_rtl() ? 'جميع الطلبات مدققة ومكتملة' : 'All clear' ?>
                </span>
            <?php endif; ?>
        </h3>
        <a href="<?= url('admin/members?account_status=pending') ?>" class="text-xs font-bold text-amber-700 hover:text-amber-800 hover:underline inline-flex items-center gap-1">
            <span><?= is_rtl() ? 'عرض كل الطلبات في إدارة المشتركين' : 'View All in Members' ?></span>
            <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>-short"></i>
        </a>
    </div>

    <!-- Quick Metric Badges for Registrations -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="p-3.5 rounded-xl <?= $stats['pendingRegistrations'] > 0 ? 'bg-amber-50/80 border border-amber-200' : 'bg-slate-50 border border-slate-200/80' ?>">
            <div class="text-xs <?= $stats['pendingRegistrations'] > 0 ? 'text-amber-800' : 'text-slate-500' ?> font-bold"><?= is_rtl() ? 'طلبات بانتظار الموافقة' : 'Pending Approvals' ?></div>
            <div class="text-xl font-black font-numeric <?= $stats['pendingRegistrations'] > 0 ? 'text-amber-800' : 'text-slate-800' ?> mt-1"><?= number_format($stats['pendingRegistrations']) ?></div>
            <div class="text-[11px] <?= $stats['pendingRegistrations'] > 0 ? 'text-amber-700 font-medium' : 'text-slate-400' ?> mt-0.5"><?= is_rtl() ? 'طلبات تسجيل ذاتي جديدة' : 'New self-registrations' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100">
            <div class="text-xs text-sky-800 font-bold"><?= is_rtl() ? 'إجمالي المشتركين المعتمدين' : 'Total Approved Members' ?></div>
            <div class="text-xl font-black font-numeric text-sky-900 mt-1"><?= number_format($stats['totalMembers']) ?></div>
            <div class="text-[11px] text-sky-600 mt-0.5"><?= is_rtl() ? number_format($stats['activeMembers']) . ' نشط حالياً' : number_format($stats['activeMembers']) . ' currently active' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100">
            <div class="text-xs text-purple-800 font-bold"><?= is_rtl() ? 'الأسهم المطلوبة بالطلبات' : 'Shares in Pending Requests' ?></div>
            <div class="text-xl font-black font-numeric text-purple-800 mt-1"><?= number_format($stats['pendingSharesTotal'] ?? 0) ?></div>
            <div class="text-[11px] text-purple-600 mt-0.5"><?= is_rtl() ? 'سهم مطلوب تخصيصه' : 'Requested shares' ?></div>
        </div>
        <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
            <div class="text-xs text-emerald-800 font-bold"><?= is_rtl() ? 'حالة التدقيق والمراجعة' : 'Review Status' ?></div>
            <div class="text-base font-bold text-emerald-700 mt-1 flex items-center gap-1.5">
                <i class="bi bi-shield-check text-emerald-600"></i>
                <span><?= $stats['pendingRegistrations'] > 0 ? (is_rtl() ? 'تتطلب قرار فوري' : 'Action Required') : (is_rtl() ? 'محدثة بالكامل' : 'Up to Date') ?></span>
            </div>
            <div class="text-[11px] text-emerald-600 mt-0.5"><?= is_rtl() ? 'تحديث لحظي لطلبات الانضمام' : 'Real-time account review' ?></div>
        </div>
    </div>

    <!-- Content: Itemized Table or Empty State -->
    <?php if (empty($stats['pendingMembersList'])): ?>
        <div class="text-center py-6 px-4 rounded-xl bg-slate-50/70 border border-dashed border-slate-200">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mx-auto mb-2 shadow-xs border border-emerald-100">
                <i class="bi bi-check2-all"></i>
            </div>
            <div class="font-bold text-slate-800 text-sm"><?= is_rtl() ? 'لا توجد طلبات تسجيل معلقة بانتظار الموافقة حالياً' : 'No pending registration requests awaiting approval' ?></div>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto"><?= is_rtl() ? 'جميع طلبات العضوية والتسجيل الذاتي تم اعتمادها وتدقيقها بالكامل. عند قيام أي مشترك جديد بالتسجيل الذاتي سيظهر طلبه وبياناته هنا فوراً للتدقيق والاعتماد.' : 'All registrations have been reviewed. New self-registrations will appear here immediately for approval.' ?></p>
            <div class="flex items-center justify-center gap-2 mt-3">
                <a href="<?= url('admin/members') ?>" class="btn btn-sm btn-soft text-xs font-bold">
                    <i class="bi bi-people"></i>
                    <span><?= is_rtl() ? 'سجل المشتركين المعتمدين' : 'View Approved Members' ?></span>
                </a>
                <a href="<?= url('admin/members/create') ?>" class="btn btn-sm btn-primary text-xs font-bold">
                    <i class="bi bi-person-plus-fill"></i>
                    <span><?= is_rtl() ? 'إضافة مشترك جديد' : 'Add New Member' ?></span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-panel border-0 shadow-none">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th><?= is_rtl() ? 'المشترك' : 'Member' ?></th>
                        <th><?= is_rtl() ? 'رقم الهوية' : 'National ID' ?></th>
                        <th><?= is_rtl() ? 'رقم الجوال' : 'Mobile' ?></th>
                        <th><?= is_rtl() ? 'البريد الإلكتروني' : 'Email' ?></th>
                        <th><?= is_rtl() ? 'الأسهم المطلوبة' : 'Shares' ?></th>
                        <th><?= is_rtl() ? 'تاريخ التقديم' : 'Submission Date' ?></th>
                        <th class="text-end"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stats['pendingMembersList'] as $pm): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900"><?= e($pm['name']) ?></div>
                                    <div class="text-[11px] text-amber-700 font-bold font-numeric"><?= is_rtl() ? 'طلب تسجيل جديد' : 'New request' ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="font-numeric font-bold text-slate-700"><?= e($pm['national_id']) ?></td>
                        <td class="font-numeric text-xs text-slate-600"><bdi dir="ltr"><?= e($pm['mobile']) ?></bdi></td>
                        <td class="text-xs text-slate-500 font-mono"><?= e($pm['email']) ?></td>
                        <td class="font-numeric font-bold text-slate-900">
                            <?= (int)$pm['shares_count'] > 0 ? number_format($pm['shares_count']) . ' ' . (is_rtl() ? 'سهم' : 'shares') : (is_rtl() ? 'بدون أسهم' : '0') ?>
                        </td>
                        <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($pm['created_at']) ?></bdi></td>
                        <td class="text-end whitespace-nowrap">
                            <form method="post" action="<?= url('admin/members/' . $pm['id'] . '/registration/approve') ?>" class="d-inline" data-confirm="<?= e(is_rtl() ? 'الموافقة على تسجيل "' . $pm['name'] . '" وتفعيل حسابه؟' : 'Approve registration for "' . $pm['name'] . '"?') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-soft text-emerald-600 hover:bg-emerald-50 font-bold py-1 px-2.5" title="<?= is_rtl() ? 'موافقة واعتماد' : 'Approve' ?>">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span><?= is_rtl() ? 'اعتماد' : 'Approve' ?></span>
                                </button>
                            </form>
                            <form method="post" action="<?= url('admin/members/' . $pm['id'] . '/registration/reject') ?>" class="d-inline" data-confirm="<?= e(is_rtl() ? 'رفض طلب تسجيل "' . $pm['name'] . '"؟' : 'Reject registration for "' . $pm['name'] . '"?') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-soft text-rose-600 hover:bg-rose-50 font-bold py-1 px-2.5" title="<?= is_rtl() ? 'رفض الطلب' : 'Reject' ?>">
                                    <i class="bi bi-x-circle-fill"></i>
                                    <span><?= is_rtl() ? 'رفض' : 'Reject' ?></span>
                                </button>
                            </form>
                            <a href="<?= url('admin/members/' . $pm['id']) ?>" class="btn btn-sm btn-soft py-1 px-2 text-slate-600" title="<?= __('view') ?>">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
                    <?php if ($stats['pendingLoanRequestsCount'] > 0): ?>
                        <span class="badge-status badge-warning font-num"><?= $stats['pendingLoanRequestsCount'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('admin/share-requests') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-arrow-left-right text-sky-600"></i>
                        <span><?= __('manage_share_requests') ?></span>
                    </span>
                    <?php if ($stats['pendingShareRequestsCount'] > 0): ?>
                        <span class="badge-status badge-info font-num"><?= $stats['pendingShareRequestsCount'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('admin/members?account_status=pending') ?>" class="btn btn-soft text-start !justify-between">
                    <span class="flex items-center gap-2">
                        <i class="bi bi-person-check-fill text-amber-600"></i>
                        <span><?= is_rtl() ? 'طلبات التسجيل بانتظار الموافقة' : 'Registration Requests' ?></span>
                    </span>
                    <?php if ($stats['pendingRegistrations'] > 0): ?>
                        <span class="badge-status badge-warning font-num"><?= $stats['pendingRegistrations'] ?></span>
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
                labels: trend.map(r => r.month_label || r.month),
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
