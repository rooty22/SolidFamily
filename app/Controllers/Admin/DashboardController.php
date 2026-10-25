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

    public function export(): void
    {
        $stats = $this->buildStats();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=dashboard-stats-' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        csv_put($out, ['المؤشر', 'القيمة']);
        foreach ($stats['export'] as $label => $value) {
            csv_put($out, [$label, $value]);
        }
        fclose($out);
        exit;
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
        foreach ($approvedMembers as $m) {
            \App\Models\FoundingAmount::ensureForMember((int) $m['id']);
        }
        // Founding amounts of approved members who actually owe one (a member with no shares owes nothing and
        // is neither "paid" nor "unpaid"); the same members the founding page lists.
        $foundRow = $db->query("SELECT
            COALESCE(SUM(f.total_required),0) as total_required,
            COALESCE(SUM(f.amount_paid),0) as total_paid,
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
            COALESCE(SUM(admin_fee_amount),0) as total_fees
            FROM loans")->fetch();

        $loanRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM loan_requests')->fetch()['c'];
        $shareRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM share_requests')->fetch()['c'];
        $pendingLoanRequestsCount = (int) $db->query("SELECT COUNT(*) as c FROM loan_requests WHERE status = 'pending'")->fetch()['c'];
        $pendingShareRequestsCount = (int) $db->query("SELECT COUNT(*) as c FROM share_requests WHERE status = 'pending'")->fetch()['c'];

        $collected = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category IN ('subscription','founding','loan_installment','loan_admin_fee')")->fetch()['s'];
        $disbursed = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category = 'loan_disbursement'")->fetch()['s'];
        
        // 1. Bank Balance (actual liquidity in bank after loans disbursed and expenses)
        $bankBalance = $collected - $disbursed;
        $fundBalance = $bankBalance; // preserved for backwards compatibility

        // 2. Remaining loans portfolio (receivables owed back to the fund)
        $loansRemaining = (float) $db->query("SELECT COALESCE(SUM(amount_remaining), 0) FROM loans WHERE status IN ('active', 'partial')")->fetchColumn();

        // 3. Total Fund Assets / Net Worth (includes cash in bank + remaining loans portfolio)
        $totalFundBalance = $bankBalance + $loansRemaining;

        // 4. Detailed Admin Revenue / Commissions earned from loans
        $loanAdminFees = (float) ($loanRow['total_fees'] ?? 0);
        $totalLoansAmount = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM loans")->fetchColumn();
        $avgFeePercent = (float) ($db->query("SELECT AVG(admin_fee_percent) FROM loans WHERE admin_fee_percent > 0")->fetchColumn() ?: 0);

        // Detailed loan commissions list (for the dedicated breakdown card)
        $detailedFeeLoans = $db->query("SELECT l.id, l.member_id, l.amount, l.admin_fee_percent, l.admin_fee_amount, l.loan_date, l.status, m.name as member_name
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

        $export = [
            'إجمالي عدد المشتركين المعتمدين' => $totalMembers,
            'طلبات التسجيل بانتظار الموافقة' => $pendingRegistrations,
            'إجمالي عدد الأسهم المسجلة' => $totalShares,
            'الرصيد الفعلي في البنك (السيولة المتاحة)' => $bankBalance,
            'الرصيد الإجمالي للصندوق (شامل محفظة القروض)' => $totalFundBalance,
            'مستحقات القروض القائمة والمتبقية' => $loansRemaining,
            'إجمالي قيمة الاشتراكات الشهرية (الشهر الحالي)' => $subRow['total_due'],
            'الاشتراكات المسددة (الشهر الحالي)' => $subRow['paid_count'],
            'الاشتراكات المسددة جزئياً (الشهر الحالي)' => $subRow['partial_count'],
            'الاشتراكات غير المسددة (الشهر الحالي)' => $subRow['unpaid_count'],
            'إجمالي مبالغ التأسيس المطلوبة' => $foundRow['total_required'],
            'مبالغ التأسيس المسددة' => $foundRow['paid_count'],
            'مبالغ التأسيس المسددة جزئياً' => $foundRow['partial_count'],
            'مبالغ التأسيس غير المسددة' => $foundRow['unpaid_count'],
            'إجمالي عدد القروض' => $loanRow['total_loans'],
            'إجمالي مبالغ القروض الصادرة' => $totalLoansAmount,
            'القروض المسددة' => $loanRow['paid_count'],
            'القروض المسددة جزئياً' => $loanRow['partial_count'],
            'القروض غير المسددة' => $loanRow['active_count'],
            'إجمالي طلبات القروض' => $loanRequestsCount,
            'إجمالي عمولات القروض الإدارية المكتسبة' => $loanAdminFees,
            'متوسط نسبة العمولة الإدارية للقروض' => round($avgFeePercent, 2) . '%',
            'إجمالي طلبات الأسهم' => $shareRequestsCount,
            'الأسهم المخصومة بسبب الدمج (عدد)' => $mergeSummary['merged']['shares'],
            'الأسهم المخصومة بسبب الدمج (المبلغ الشهري)' => $mergeSummary['merged']['amount'],
            'الأسهم المفصولة (عدد)' => $mergeSummary['split']['shares'],
            'الأسهم المفصولة (المبلغ الشهري)' => $mergeSummary['split']['amount'],
            'الأسهم المخصومة حاليًا بعد الفصل (المبلغ الشهري)' => $mergeSummary['net_amount'],
            'عدد المشتركين المتأخرين عن السداد' => $lateMembers,
            'المتأخرون في الاشتراكات الشهرية' => $lateSubsCount,
            'المتأخرون في أقساط القروض' => $lateLoansCount,
        ];

        return compact(
            'totalMembers', 'activeMembers', 'suspendedMembers', 'pendingRegistrations', 'totalShares', 'subRow', 'foundRow', 'loanRow',
            'loanRequestsCount', 'shareRequestsCount', 'pendingLoanRequestsCount', 'pendingShareRequestsCount', 'fundBalance', 'bankBalance',
            'totalFundBalance', 'loansRemaining', 'loanAdminFees', 'totalLoansAmount',
            'avgFeePercent', 'detailedFeeLoans', 'lateMembers', 'lateSubsCount',
            'lateLoansCount', 'monthlyTrend', 'mergeSummary', 'export'
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
