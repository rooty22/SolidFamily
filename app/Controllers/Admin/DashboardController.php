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

        $totalMembers = (int) Member::count();
        $totalShares = (int) Member::sum('shares_count');

        // Subscription rows are created lazily, so members nobody has opened yet have no row for this month.
        // Compute the month from the members themselves (LEFT JOIN) so the figures cover everybody who owes something.
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = (int) Setting::get('subscription_due_day', 10);
        $today = date('Y-m-d');

        $subStmt = $db->prepare("SELECT
            COALESCE(SUM(COALESCE(s.amount_due, m.shares_count * :sv1)), 0) AS total_due,
            COALESCE(SUM(COALESCE(s.amount_paid, 0)), 0) AS total_paid,
            COALESCE(SUM(s.status = 'paid'), 0) AS paid_count,
            COALESCE(SUM(s.status = 'partial'), 0) AS partial_count,
            COALESCE(SUM(s.id IS NULL OR s.status = 'unpaid'), 0) AS unpaid_count
            FROM members m
            LEFT JOIN monthly_subscriptions s ON s.member_id = m.id AND s.month = :cm
            WHERE m.status = 'active' AND COALESCE(s.amount_due, m.shares_count * :sv2) > 0");
        $subStmt->execute(['sv1' => $shareValue, 'sv2' => $shareValue, 'cm' => $currentMonth]);
        $subRow = $subStmt->fetch();

        $foundRow = $db->query("SELECT
            COALESCE(SUM(total_required),0) as total_required,
            COALESCE(SUM(amount_paid),0) as total_paid,
            SUM(status='paid') as paid_count,
            SUM(status='partial') as partial_count,
            SUM(status='unpaid') as unpaid_count
            FROM founding_amounts")->fetch();

        $loanRow = $db->query("SELECT
            COUNT(*) as total_loans,
            SUM(status IN ('paid','closed')) as paid_count,
            SUM(status='partial') as partial_count,
            SUM(status='active') as active_count,
            COALESCE(SUM(admin_fee_amount),0) as total_fees
            FROM loans")->fetch();

        $loanRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM loan_requests')->fetch()['c'];
        $shareRequestsCount = (int) $db->query('SELECT COUNT(*) as c FROM share_requests')->fetch()['c'];

        $collected = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category IN ('subscription','founding','loan_installment','loan_admin_fee')")->fetch()['s'];
        $disbursed = (float) $db->query("SELECT COALESCE(SUM(amount),0) as s FROM transactions WHERE category = 'loan_disbursement'")->fetch()['s'];
        $fundBalance = $collected - $disbursed;

        $currentMonthOverdue = month_due_date($currentMonth, $dueDay) < $today ? 1 : 0;
        $lateStmt = $db->prepare("SELECT COUNT(*) AS c
            FROM members m
            LEFT JOIN monthly_subscriptions cur ON cur.member_id = m.id AND cur.month = :cm
            WHERE m.status = 'active' AND (
                EXISTS (SELECT 1 FROM monthly_subscriptions s
                        WHERE s.member_id = m.id AND s.status <> 'paid' AND s.amount_due > 0 AND s.due_date < :today)
                OR (m.shares_count > 0 AND :overdue = 1 AND (cur.id IS NULL OR cur.status <> 'paid'))
            )");
        $lateStmt->execute(['cm' => $currentMonth, 'today' => $today, 'overdue' => $currentMonthOverdue]);
        $lateMembers = (int) $lateStmt->fetch()['c'];

        $monthlyTrend = $db->query("SELECT month, SUM(amount_paid) as paid, SUM(amount_due) as due
            FROM monthly_subscriptions GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $monthlyTrend = array_reverse($monthlyTrend);

        $export = [
            'إجمالي عدد المشتركين' => $totalMembers,
            'إجمالي عدد الأسهم المسجلة' => $totalShares,
            'إجمالي قيمة الاشتراكات الشهرية (الشهر الحالي)' => $subRow['total_due'],
            'الاشتراكات المسددة (الشهر الحالي)' => $subRow['paid_count'],
            'الاشتراكات المسددة جزئياً (الشهر الحالي)' => $subRow['partial_count'],
            'الاشتراكات غير المسددة (الشهر الحالي)' => $subRow['unpaid_count'],
            'إجمالي مبالغ التأسيس المطلوبة' => $foundRow['total_required'],
            'مبالغ التأسيس المسددة' => $foundRow['paid_count'],
            'مبالغ التأسيس المسددة جزئياً' => $foundRow['partial_count'],
            'مبالغ التأسيس غير المسددة' => $foundRow['unpaid_count'],
            'إجمالي عدد القروض' => $loanRow['total_loans'],
            'القروض المسددة' => $loanRow['paid_count'],
            'القروض المسددة جزئياً' => $loanRow['partial_count'],
            'القروض غير المسددة' => $loanRow['active_count'],
            'إجمالي طلبات القروض' => $loanRequestsCount,
            'إجمالي إيرادات المصاريف الإدارية للقروض' => $loanRow['total_fees'],
            'إجمالي طلبات الأسهم' => $shareRequestsCount,
            'إجمالي الرصيد الحالي للصندوق' => $fundBalance,
            'عدد المشتركين المتأخرين عن السداد' => $lateMembers,
        ];

        return compact('totalMembers', 'totalShares', 'subRow', 'foundRow', 'loanRow', 'loanRequestsCount', 'shareRequestsCount', 'fundBalance', 'lateMembers', 'monthlyTrend', 'export');
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
