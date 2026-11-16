<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Member;
use App\Models\Setting;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $stats = $this->buildStats();
        $this->view('admin/dashboard/index', [
            'pageTitle' => __('dashboard_title'),
            'stats' => $stats,
        ], 'admin/layout');
    }

    /**
     * The dashboard report. A real Excel workbook (several sheets: the figures in coloured groups, the subscriptions trend, the loan
     * commissions, the registrations waiting for approval); a semicolon CSV when the server has no zip extension or with ?format=csv
     * (Excel with an Arabic regional setting puts a comma-separated file in ONE column, a semicolon-separated one in columns).
     */
    public function export(): void
    {
        $stats = $this->buildStats();
        $format = strtolower(trim((string) $this->input('format', '')));
        if ($format !== 'csv' && \App\Core\XlsxReport::available()) {
            $this->exportExcel($stats);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=dashboard-stats-' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        csv_put($out, ['المؤشر', 'القيمة', 'الوحدة', 'القسم', 'ملاحظة']);
        foreach ($stats['sections'] as [$title, $rows]) {
            foreach ($rows as $row) {
                [$label, $value, $type, $unit] = $row;
                $note = $row[4] ?? '';
                csv_put($out, [$label, $type === 'pct' ? $value . '%' : $value, $unit, $title, $note]);
            }
        }
        fclose($out);
        exit;
    }

    private function exportExcel(array $stats): void
    {
        $db = Database::connection();
        $ar = is_rtl();
        $X = \App\Core\XlsxReport::class;
        $report = new \App\Core\XlsxReport($ar);
        $money = fn($v) => number_format((float) $v, 2) . ($ar ? ' ريال' : ' SAR');

        // 1) the figures, one coloured band per group
        $report->sheet($ar ? 'لوحة المعلومات' : 'Dashboard', [
            [$ar ? 'المؤشر' : 'Indicator', 64, $X::TEXT],
            [$ar ? 'القيمة' : 'Value', 22, $X::TEXT],
            [$ar ? 'الوحدة' : 'Unit', 12, $X::ID],
            [$ar ? 'ملاحظة' : 'Note', 58, $X::TEXT],
        ], [
            ($ar ? 'لوحة معلومات الصندوق - ' : 'Fund Dashboard - ') . site_name(),
            ($ar ? 'تاريخ التقرير: ' : 'Report date: ') . date('d/m/Y H:i') . ($ar ? ' · الأرقام حتى هذه اللحظة' : ' · Live figures'),
        ]);
        $cellType = ['money' => $X::MONEY, 'int' => $X::INT, 'pct' => $X::PCT, 'text' => $X::TEXT];
        foreach ($stats['sections'] as $i => [$title, $rows]) {
            $report->section(($i + 1) . ') ' . $title);
            foreach ($rows as [$label, $value, $type, $unit, $note]) {
                // Keep numeric ratios like "8 / 17" centred with LTR reading order so they never flip in RTL Excel
                $resolvedType = ($type === 'text' && preg_match('/^\d+\s*\/\s*\d+$/', (string) $value))
                    ? $X::ID
                    : ($cellType[$type] ?? $X::TEXT);
                $report->row([$label, [$value, $resolvedType], $unit, $note]);
            }
        }

        // 2) the subscriptions of the last 6 months
        $report->sheet($ar ? 'اتجاه الاشتراكات' : 'Subscriptions Trend', [
            [$ar ? 'الشهر' : 'Month', 14, $X::ID],
            [$ar ? 'المستحق' : 'Due', 18, $X::MONEY],
            [$ar ? 'المسدد الفعلي' : 'Paid', 18, $X::MONEY],
            [$ar ? 'المتبقي' : 'Remaining', 18, $X::MONEY],
            [$ar ? 'نسبة التحصيل' : 'Collection %', 16, $X::PCT],
        ], [
            $ar ? 'اتجاه الاشتراكات الشهرية (آخر 6 أشهر)' : 'Monthly Subscriptions Trend (Last 6 Months)',
            $ar ? 'مقارنة المستحق والمسدد الفعلي لكل شهر' : 'Comparison of due vs actual collected amounts per month',
        ]);
        $trendDue = 0.0;
        $trendPaid = 0.0;
        $trendRem = 0.0;
        $trendStart = 4; // titleLines = 2 -> header = 4 -> first data row = 5
        foreach ($stats['monthlyTrend'] as $t) {
            $due = (float) $t['due'];
            $paid = (float) $t['paid'];
            $rem = max(0, round($due - $paid, 2));
            $trendDue += $due;
            $trendPaid += $paid;
            $trendRem += $rem;
            $report->row([$t['month_label'], $due, $paid, $rem, $due > 0 ? round($paid / $due * 100, 2) : 0]);
        }
        $trendCount = count($stats['monthlyTrend']);
        if ($trendCount > 0) {
            $r1 = $trendStart + 1;
            $r2 = $trendStart + $trendCount;
            $rt = $r2 + 1;
            $report->totals([
                [$ar ? 'الإجمالي' : 'Total', $X::TEXT],
                ['formula' => "SUM(B{$r1}:B{$r2})", 'value' => round($trendDue, 2), 'type' => $X::MONEY],
                ['formula' => "SUM(C{$r1}:C{$r2})", 'value' => round($trendPaid, 2), 'type' => $X::MONEY],
                ['formula' => "SUM(D{$r1}:D{$r2})", 'value' => round($trendRem, 2), 'type' => $X::MONEY],
                ['formula' => "IF(B{$rt}>0,ROUND(C{$rt}/B{$rt}*100,2),0)", 'value' => $trendDue > 0 ? round($trendPaid / $trendDue * 100, 2) : 0, 'type' => $X::PCT],
            ]);
        }

        // 3) the commissions of every loan that has one
        $fees = $db->query("SELECT l.id, l.amount, l.admin_fee_percent, l.admin_fee_amount, l.fee_status, l.fee_paid_at, l.loan_date, l.status, m.name AS member_name
            FROM loans l JOIN members m ON m.id = l.member_id
            WHERE l.admin_fee_amount > 0 ORDER BY l.loan_date DESC, l.id DESC")->fetchAll() ?: [];
        $loanStatus = $ar
            ? ['active' => 'قائم', 'partial' => 'مسدد جزئياً', 'paid' => 'مسدد', 'closed' => 'مغلق']
            : ['active' => 'Active', 'partial' => 'Partial', 'paid' => 'Paid', 'closed' => 'Closed'];
        $report->sheet($ar ? 'عمولات القروض' : 'Loan Commissions', [
            [$ar ? 'رقم القرض' : 'Loan #', 13, $X::INT],
            [$ar ? 'المشترك' : 'Member', 30, $X::TEXT],
            [$ar ? 'قيمة القرض' : 'Loan amount', 18, $X::MONEY],
            [$ar ? 'نسبة العمولة' : 'Fee %', 15, $X::PCT],
            [$ar ? 'العمولة' : 'Commission', 16, $X::MONEY],
            [$ar ? 'حالة العمولة' : 'Fee status', 15, $X::ID],
            [$ar ? 'تاريخ سداد العمولة' : 'Fee paid on', 18, $X::DATE],
            [$ar ? 'تاريخ الصرف' : 'Disbursed on', 15, $X::DATE],
            [$ar ? 'حالة القرض' : 'Loan status', 15, $X::ID],
        ], [
            $ar ? 'عمولات القروض الإدارية (إيرادات الإدارة)' : 'Loan Administrative Commissions',
            ($ar ? 'الإجمالي ' : 'Total ') . $money($stats['loanAdminFeesTotal'])
                . ($ar ? ' · المسددة ' : ' · Paid ') . $money($stats['loanAdminFees'])
                . ($ar ? ' · المستحقة ' : ' · Pending ') . $money($stats['loanAdminFeesPending']),
        ], true);
        foreach ($fees as $f) {
            $paid = ($f['fee_status'] ?? 'unpaid') === 'paid';
            $report->row([
                (int) $f['id'],
                $f['member_name'],
                (float) $f['amount'],
                (float) $f['admin_fee_percent'],
                (float) $f['admin_fee_amount'],
                $paid ? ($ar ? 'مدفوعة' : 'Paid') : ($ar ? 'غير مدفوعة' : 'Unpaid'),
                $paid ? $f['fee_paid_at'] : null,
                $f['loan_date'],
                $loanStatus[$f['status']] ?? $f['status'],
            ]);
        }
        if ($fees) {
            $report->sumTotals($ar ? 'الإجمالي' : 'Total', [2, 4], 1, 0, count($fees) . ' ' . ($ar ? 'قرض' : 'loans'));
        } else {
            $report->note($ar ? 'لا توجد عمولات قروض مسجلة حتى الآن' : 'No loan commissions recorded yet.');
        }

        // 4) the registrations waiting for approval
        $pending = $db->query("SELECT name, national_id, mobile, email, shares_count, created_at FROM members WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll() ?: [];
        $report->sheet($ar ? 'طلبات التسجيل' : 'Registrations', [
            [$ar ? 'الاسم' : 'Name', 30, $X::TEXT],
            [$ar ? 'رقم الهوية' : 'National ID', 17, $X::ID],
            [$ar ? 'رقم الجوال' : 'Mobile', 17, $X::ID],
            [$ar ? 'البريد الإلكتروني' : 'E-mail', 32, $X::ID],
            [$ar ? 'الأسهم المطلوبة' : 'Requested shares', 17, $X::INT],
            [$ar ? 'تاريخ التقديم' : 'Applied on', 16, $X::DATE],
        ], [
            $ar ? 'طلبات التسجيل بانتظار الموافقة' : 'Registrations Pending Approval',
            ($ar ? 'عدد الطلبات: ' : 'Requests: ') . count($pending),
        ], true);
        foreach ($pending as $m) {
            $report->row([$m['name'], $m['national_id'], $m['mobile'], $m['email'], (int) $m['shares_count'], $m['created_at']]);
        }
        if ($pending) {
            $report->sumTotals($ar ? 'الإجمالي' : 'Total', [4], 0, 1, count($pending) . ' ' . ($ar ? 'طلب' : 'requests'));
        } else {
            $report->note($ar ? 'لا توجد طلبات تسجيل بانتظار الموافقة حاليًا' : 'No registrations pending approval.');
        }

        $report->download('dashboard-' . date('Y-m-d') . '.xlsx');
    }

    private function buildStats(): array
    {
        $db = Database::connection();
        $currentMonth = date('Y-m');

        // Members = approved accounts; self-registrations still waiting for (or refused) approval are counted apart.
        $approvedMembers = Member::withoutRegistrations(Member::all());
        $totalMembers = count($approvedMembers);
        $activeMembers = count(array_filter($approvedMembers, fn($m) => $m['status'] === 'active'));
        $suspendedMembers = $totalMembers - $activeMembers;
        $pendingRegistrations = (int) Member::count(['status' => 'pending']);
        $pendingMembersList = $db->query("SELECT id, name, mobile, email, national_id, shares_count, created_at 
            FROM members 
            WHERE status = 'pending' 
            ORDER BY created_at DESC 
            LIMIT 10")->fetchAll() ?: [];
        $pendingSharesTotal = (int) ($db->query("SELECT COALESCE(SUM(shares_count), 0) FROM members WHERE status = 'pending'")->fetchColumn() ?: 0);
        $totalShares = (int) Member::sum('shares_count');

        // Subscription rows are created lazily, so members nobody has opened yet have no row for this month.
        // Compute the month from the members themselves (LEFT JOIN) so the figures cover everybody who owes something.
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = (int) Setting::get('subscription_due_day', 10);
        $today = date('Y-m-d');

        // One line per member (several share lots of the same month are summed): paid when everything due is
        // collected, partial when part of it is, unpaid when nothing is.
        $subStmt = $db->prepare("SELECT
            COALESCE(SUM(due), 0) AS total_due,
            COALESCE(SUM(LEAST(paid, due)), 0) AS total_paid
            FROM (
                SELECT m.id,
                       GREATEST(COALESCE(SUM(s.amount_due), 0), m.shares_count * :sv) AS due,
                       COALESCE(SUM(s.amount_paid), 0) AS paid
                FROM members m
                LEFT JOIN monthly_subscriptions s ON s.member_id = m.id AND s.month = :cm
                WHERE m.status NOT IN ('pending', 'rejected')
                GROUP BY m.id, m.shares_count
                HAVING due > 0
            ) per_member");
        $subStmt->execute(['sv' => $shareValue, 'cm' => $currentMonth]);
        $subRow = $subStmt->fetch();
        // Paid / partial / unpaid members this month: the very same helper (and members) as the subscriptions page.
        $monthCounts = \App\Models\MonthlySubscription::monthStatusCounts($approvedMembers, $currentMonth);
        $subRow['paid_count'] = $monthCounts['paid'];
        $subRow['partial_count'] = $monthCounts['partial'];
        $subRow['unpaid_count'] = $monthCounts['unpaid'];
        $subRow['late_count'] = $monthCounts['late'];

        // A founding row is only re-computed when someone opens it, so one can still reflect shares that changed
        // since; bring every member's row up to date first so these numbers match the founding page exactly.
        // The founding amount is also counted share by share (each share is paid on its own).
        $foundingShares = ['total' => 0, 'met' => 0];
        foreach ($approvedMembers as $m) {
            $fRow = \App\Models\FoundingAmount::ensureForMember((int) $m['id']);
            foreach (\App\Models\FoundingAmount::lotBreakdown((int) $m['id'], $fRow) as $b) {
                if ($b['required'] > 0) {
                    $foundingShares['total']++;
                    $foundingShares['met'] += $b['met'] ? 1 : 0;
                }
            }
        }
        // Founding amounts of approved members who actually owe one (a member with no shares owes nothing and
        // is neither "paid" nor "unpaid"); the same members the founding page lists.
        $foundRow = $db->query("SELECT
            COALESCE(SUM(f.total_required),0) as total_required,
            COALESCE(SUM(f.amount_paid),0) as total_paid,
            COALESCE(SUM(GREATEST(f.total_required - f.amount_paid, 0)),0) as total_remaining,
            COALESCE(SUM(f.status='paid'),0) as paid_count,
            COALESCE(SUM(f.status='partial'),0) as partial_count,
            COALESCE(SUM(f.status='unpaid'),0) as unpaid_count
            FROM founding_amounts f
            JOIN members m ON m.id = f.member_id
            WHERE m.status NOT IN ('pending', 'rejected') AND f.total_required > 0")->fetch();

        $loanRow = $db->query("SELECT
            COUNT(*) as total_loans,
            SUM(status IN ('paid','closed')) as paid_count,
            SUM(status='partial') as partial_count,
            SUM(status='active') as active_count,
            COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN admin_fee_amount ELSE 0 END),0) as total_fees,
            COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN 0 ELSE admin_fee_amount END),0) as pending_fees,
            COALESCE(SUM(status IN ('active','partial')),0) as running_count
            FROM loans")->fetch();

        $loanRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM loan_requests')->fetch()['c'];
        $shareRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM share_requests')->fetch()['c'];
        $pendingLoanRequestsCount = (int) $db->query("SELECT COUNT(*) as c FROM loan_requests WHERE status = 'pending'")->fetch()['c'];
        $pendingShareRequestsCount = (int) $db->query("SELECT COUNT(*) as c FROM share_requests WHERE status = 'pending'")->fetch()['c'];

        $collected = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category IN ('subscription','founding','loan_installment','loan_admin_fee')")->fetch()['s'];
        $disbursed = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category = 'loan_disbursement'")->fetch()['s'];
        
        // 1. Bank Balance (actual liquidity in bank): money received - loans paid out, plus the net of the administrative /
        // bank expenses log (its deposits are added, its withdrawals deducted). The total fund balance below follows.
        $expenseNet = \App\Models\Expense::net();
        $bankBalance = $collected - $disbursed + $expenseNet;
        $fundBalance = $bankBalance; // preserved for backwards compatibility

        // 2. Remaining loans portfolio (receivables owed back to the fund)
        $loansRemaining = (float) $db->query("SELECT COALESCE(SUM(amount_remaining), 0) FROM loans WHERE status IN ('active', 'partial')")->fetchColumn();

        // 3. Loan commissions (administrative fee). They enter the bank balance only once the administration
        // confirmed the payment (a transaction is booked then); until then they are money the members still owe.
        $loanAdminFees = (float) ($loanRow['total_fees'] ?? 0);               // confirmed as received
        $loanAdminFeesPending = (float) ($loanRow['pending_fees'] ?? 0);      // not received yet
        $loanAdminFeesTotal = round($loanAdminFees + $loanAdminFeesPending, 2); // all commissions of all loans

        // 4. Total fund balance = the actual bank balance + the loans outstanding + the loan commissions received + the net of the
        // administrative / bank expenses log: the four figures of the boxes next to it, added up as the administration asked.
        // NOTE: the bank balance already contains the commissions received and the expenses net (both are booked in the ledger /
        // the log that feed it), so this definition counts those two amounts twice by design. The commissions still due are not part
        // of it. database/audit_totals.sql shows the total without that overlap too.
        $totalFundBalance = $bankBalance + $loansRemaining + $loanAdminFees + $expenseNet;
        $totalLoansAmount = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM loans")->fetchColumn();
        $avgFeePercent = (float) ($db->query("SELECT AVG(admin_fee_percent) FROM loans WHERE admin_fee_percent > 0")->fetchColumn() ?: 0);

        // Detailed loan commissions list (for the dedicated breakdown card)
        $detailedFeeLoans = $db->query("SELECT l.id, l.member_id, l.amount, l.admin_fee_percent, l.admin_fee_amount, l.fee_status, l.fee_paid_at, l.loan_date, l.status, m.name as member_name
            FROM loans l
            JOIN members m ON m.id = l.member_id
            WHERE l.admin_fee_amount > 0
            ORDER BY l.created_at DESC
            LIMIT 10")->fetchAll();

        // Overdue members across both monthly subscriptions and loan installments
        $lateInfo = Member::getLateStatusInfo(true);
        $lateMembers = $lateInfo['totalLateCount'];
        $lateSubsCount = $lateInfo['lateSubCount'];
        $lateLoansCount = $lateInfo['lateLoanCount'];

        // The 6 real calendar months ending with the current one -- NOT just whichever 6 month values happen to
        // have rows, which could be future months already billed ahead (e.g. a member paying several months in
        // advance) and would then push the chart into the future instead of showing the recent trend.
        $sums = [];
        foreach ($db->query("SELECT month, SUM(amount_paid) as paid, SUM(amount_due) as due
            FROM monthly_subscriptions WHERE month <= '{$currentMonth}' GROUP BY month") as $row) {
            $sums[$row['month']] = $row;
        }
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months", strtotime($currentMonth . '-01')));
            $monthlyTrend[] = ($sums[$month] ?? ['month' => $month, 'paid' => 0, 'due' => 0]) + ['month_label' => month_label($month)];
        }

        // Shares deducted by merges / given back by splits, with the monthly amount they were worth.
        $mergeSummary = \App\Models\ShareMergeLog::summary();

        // The expenses log belongs to the main admin alone: nobody else gets its total (screen, CSV or print). A database
        // that has not had the expenses migration yet must not break the dashboard.
        $expenses = null;
        if (\App\Core\Auth::adminIsSuper()) {
            try {
                $expenses = \App\Models\Expense::summary();
            } catch (\Throwable $e) {
                $expenses = null;
            }
        }

        // What the total fund is made of, named in its label. The expenses log belongs to the main admin alone, so its term is only
        // spelled out for him (the number itself, like the bank balance, follows the same definition for everybody).
        $isSuper = \App\Core\Auth::adminIsSuper();
        $totalFundLabel = 'الرصيد الإجمالي للصندوق (الرصيد الفعلي بالبنك + القروض القائمة + العمولات المسددة'
            . ($isSuper ? ' + صافي المصاريف' : '') . ')';

        // The figures of the report, grouped like the dashboard: [title, rows]; a row is [label, value, type, unit, note].
        // The Excel export shows the groups as coloured bands, the CSV / PDF lists the same rows one after the other.
        $money = fn(string $label, $v, string $note = '') => [$label, round((float) $v, 2), 'money', 'ريال', $note];
        $count = fn(string $label, $v, string $unit = 'عدد', string $note = '') => [$label, (int) $v, 'int', $unit, $note];
        $sections = [
            ['الأرصدة والسيولة', [
                $money('الرصيد الفعلي في البنك (السيولة المتاحة)', $bankBalance, 'الوارد (اشتراكات وتأسيس وأقساط وعمولات) - القروض المصروفة' . ($isSuper ? ' + صافي المصاريف' : '')),
                $money($totalFundLabel, $totalFundBalance, 'مجموع البوكسات الأربعة في لوحة المعلومات'),
                $money('مستحقات القروض القائمة والمتبقية (أصل القرض)', $loansRemaining, 'ما سيعود للصندوق من القروض'),
            ]],
            ['المشتركون والأسهم', [
                $count('إجمالي عدد المشتركين المعتمدين', $totalMembers, 'مشترك'),
                $count('المشتركون النشطون', $activeMembers, 'مشترك'),
                $count('المشتركون الموقوفون', $suspendedMembers, 'مشترك'),
                $count('طلبات التسجيل بانتظار الموافقة', $pendingRegistrations, 'طلب'),
                $count('إجمالي عدد الأسهم المسجلة', $totalShares, 'سهم'),
                $count('عدد المشتركين المتأخرين عن السداد', $lateMembers, 'مشترك'),
                $count('المتأخرون في الاشتراكات الشهرية', $lateSubsCount, 'مشترك'),
                $count('المتأخرون في أقساط القروض', $lateLoansCount, 'مشترك'),
            ]],
            ['الاشتراكات الشهرية (الشهر الحالي ' . month_label($currentMonth) . ')', [
                $money('إجمالي قيمة الاشتراكات الشهرية (الشهر الحالي)', $subRow['total_due']),
                $money('المحصّل من الاشتراكات (الشهر الحالي)', $subRow['total_paid']),
                $money('المتبقي من الاشتراكات (الشهر الحالي)', max(0, (float) $subRow['total_due'] - (float) $subRow['total_paid'])),
                $count('الاشتراكات المسددة (الشهر الحالي)', $subRow['paid_count'], 'مشترك'),
                $count('الاشتراكات المسددة جزئياً (الشهر الحالي)', $subRow['partial_count'], 'مشترك'),
                $count('الاشتراكات غير المسددة (الشهر الحالي)', $subRow['unpaid_count'], 'مشترك'),
            ]],
            ['مبالغ التأسيس', [
                $money('إجمالي مبالغ التأسيس المطلوبة', $foundRow['total_required']),
                $money('مبالغ التأسيس المسددة (المبلغ)', $foundRow['total_paid']),
                $money('مبالغ التأسيس المتبقية (المبلغ)', $foundRow['total_remaining']),
                ['الأسهم المكتمل مبلغ تأسيسها (من إجمالي الأسهم المطلوب تأسيسها)', $foundingShares['met'] . ' / ' . $foundingShares['total'], 'text', 'سهم', 'كل سهم يُحسب بمفرده'],
                $count('المشتركون المسددون تأسيسهم (عدد)', $foundRow['paid_count'], 'مشترك'),
                $count('المشتركون المسددون جزئياً (عدد)', $foundRow['partial_count'], 'مشترك'),
                $count('المشتركون غير المسددين (عدد)', $foundRow['unpaid_count'], 'مشترك'),
            ]],
            ['القروض', [
                $count('عدد القروض القائمة', $loanRow['running_count'], 'قرض'),
                $count('إجمالي عدد القروض', $loanRow['total_loans'], 'قرض'),
                $money('إجمالي مبالغ القروض الصادرة', $totalLoansAmount),
                $count('القروض المسددة', $loanRow['paid_count'], 'قرض'),
                $count('القروض المسددة جزئياً', $loanRow['partial_count'], 'قرض'),
                $count('القروض غير المسددة', $loanRow['active_count'], 'قرض'),
                $count('إجمالي طلبات القروض', $loanRequestsCount, 'طلب'),
            ]],
            ['عمولات القروض الإدارية', [
                $money('إجمالي عمولات القروض الإدارية (كل القروض)', $loanAdminFeesTotal, 'المسددة + المستحقة'),
                $money('عمولات القروض الإدارية المؤكد سدادها', $loanAdminFees, 'داخلة في رصيد البنك'),
                $money('عمولات القروض الإدارية غير المسددة بعد', $loanAdminFeesPending, 'تدخل في الرصيد عند تأكيد السداد'),
                ['متوسط نسبة العمولة الإدارية للقروض', round($avgFeePercent, 2), 'pct', '%', ''],
            ]],
            ['الأسهم: الطلبات والدمج والفصل', [
                $count('إجمالي طلبات الأسهم', $shareRequestsCount, 'طلب'),
                $count('الأسهم المخصومة بسبب الدمج (عدد)', $mergeSummary['merged']['shares'], 'سهم'),
                $money('الأسهم المخصومة بسبب الدمج (المبلغ الشهري)', $mergeSummary['merged']['amount']),
                $count('الأسهم المفصولة (عدد)', $mergeSummary['split']['shares'], 'سهم'),
                $money('الأسهم المفصولة (المبلغ الشهري)', $mergeSummary['split']['amount']),
                $money('الأسهم المخصومة حاليًا بعد الفصل (المبلغ الشهري)', $mergeSummary['net_amount']),
            ]],
        ];
        if ($expenses !== null) {
            $sections[] = ['المصاريف الإدارية والبنكية', [
                $money('إجمالي المصاريف الإدارية والبنكية (صرف)', $expenses['withdrawals'], 'تُخصم من الرصيد'),
                $money('إجمالي الإيداعات الإدارية والبنكية', $expenses['deposits'], 'تُضاف إلى الرصيد'),
                $money('صافي أثر المصاريف والإيداعات على الرصيد', $expenses['net'], 'إيداع - صرف'),
                $count('عدد معاملات سجل المصاريف', $expenses['count'] ?? 0, 'معاملة'),
            ]];
        }

        // The same rows as one list (label => value): what the CSV and the printable report show.
        $export = [];
        foreach ($sections as [, $rows]) {
            foreach ($rows as [$label, $value, $type]) {
                $export[$label] = $type === 'pct' ? $value . '%' : $value;
            }
        }

        return compact(
            'totalMembers', 'activeMembers', 'suspendedMembers', 'pendingRegistrations', 'pendingMembersList', 'pendingSharesTotal', 'totalShares', 'subRow', 'foundRow', 'loanRow',
            'loanRequestsCount', 'shareRequestsCount', 'pendingLoanRequestsCount', 'pendingShareRequestsCount', 'fundBalance', 'bankBalance',
            'totalFundBalance', 'loansRemaining', 'loanAdminFees', 'loanAdminFeesPending', 'loanAdminFeesTotal', 'foundingShares', 'totalLoansAmount',
            'avgFeePercent', 'detailedFeeLoans', 'lateMembers', 'lateSubsCount',
            'lateLoansCount', 'monthlyTrend', 'mergeSummary', 'expenses', 'expenseNet', 'export', 'sections'
        );
    }

    /** Printable statistics report (browser "Save as PDF"). */
    public function printReport(): void
    {
        $rows = [];
        foreach ($this->buildStats()['export'] as $label => $value) {
            $rows[] = [$label, $value];
        }
        $this->view('admin/reports/print', [
            'title' => 'إحصائيات النظام',
            'headers' => ['المؤشر', 'القيمة'],
            'rows' => $rows,
        ]);
    }
}
