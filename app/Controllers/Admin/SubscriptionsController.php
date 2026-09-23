<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\MonthlySubscription;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Notification;

class SubscriptionsController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $members = $q !== '' ? Member::search($q) : Member::all('name ASC');
        $currentMonth = date('Y-m');
        $today = date('Y-m-d');
        $shareValue = (float) Setting::get('share_value', 0);

        // Paid / overdue months per member in one query instead of one per row.
        $counts = [];
        foreach (MonthlySubscription::raw(
            "SELECT member_id,
                    SUM(status = 'paid') AS paid_months,
                    SUM(status <> 'paid' AND amount_due > 0 AND due_date < ?) AS late_months
             FROM monthly_subscriptions GROUP BY member_id",
            [$today]
        ) as $c) {
            $counts[(int) $c['member_id']] = $c;
        }

        $rows = [];
        foreach ($members as $m) {
            $current = MonthlySubscription::first(['member_id' => $m['id'], 'month' => $currentMonth]);
            $late = (int) ($counts[(int) $m['id']]['late_months'] ?? 0);
            // The current month has no row until somebody opens it: it still counts as overdue once its own due
            // date (the member's own override, or the site default) has passed.
            if (!$current && (int) $m['shares_count'] > 0 && month_due_date($currentMonth, MonthlySubscription::dueDayFor($m)) < $today) {
                $late++;
            }
            $rows[] = [
                'member' => $m,
                'share_value' => $shareValue,
                'amount' => $m['shares_count'] * $shareValue,
                'current_status' => $current['status'] ?? 'unpaid',
                'paid_months' => (int) ($counts[(int) $m['id']]['paid_months'] ?? 0),
                'late_months' => $late,
            ];
        }

        $this->view('admin/subscriptions/index', [
            'pageTitle' => __('subscriptions'),
            'rows' => $rows,
            'q' => $q,
            'currentMonth' => $currentMonth,
        ], 'admin/layout');
    }

    public function show(string $memberId): void
    {
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/subscriptions');
        }

        MonthlySubscription::ensureMonthExists((int) $memberId, date('Y-m'));
        $history = MonthlySubscription::forMember((int) $memberId);
        $shareValue = (float) Setting::get('share_value', 0);

        $this->view('admin/subscriptions/show', [
            'pageTitle' => __('subscriptions_for', ['name' => $member['name']]),
            'member' => $member,
            'history' => $history,
            'shareValue' => $shareValue,
        ], 'admin/layout');
    }

    public function recordPayment(string $memberId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/subscriptions');
        }

        $back = 'admin/subscriptions/' . $memberId;
        $data = $this->all();
        $action = $data['action'] ?? 'bulk_pay';

        if ($action === 'bulk_pay') {
            $data += ['start_month' => date('Y-m'), 'months_count' => '1'];
            $validator = Validator::make($data)
                ->required('start_month', 'شهر البداية')->month('start_month', 'البداية')
                ->required('months_count', 'عدد الأشهر')->integer('months_count', 'عدد الأشهر', 1, 24);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect($back);
            }

            $startMonth = $data['start_month'];
            $count = (int) $data['months_count'];
            $ts = strtotime($startMonth . '-01');
            $paidAny = false;

            for ($i = 0; $i < $count; $i++) {
                $month = date('Y-m', strtotime("+{$i} month", $ts));
                $sub = MonthlySubscription::ensureMonthExists((int) $memberId, $month, false);
                if ($sub['status'] !== 'paid') {
                    $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);
                    MonthlySubscription::update($sub['id'], ['amount_paid' => $sub['amount_due'], 'status' => 'paid']);
                    if ($remaining > 0) {
                        Transaction::record((int) $memberId, 'subscription', $sub['id'], $remaining, current_admin_id(), 'سداد اشتراك شهر ' . $month);
                    }
                    $paidAny = true;
                }
            }

            if ($paidAny) {
                Notification::systemNotify((int) $memberId, 'تسجيل سداد اشتراك', 'تم تسجيل سداد الاشتراك الشهري بنجاح.');
                Session::flash('success', "تم تسجيل سداد {$count} شهر/أشهر بنجاح.");
            } else {
                Session::flash('error', 'الأشهر المحددة مسددة بالكامل مسبقاً.');
            }
        } elseif ($action === 'partial_pay' || $action === 'partial') {
            $data += ['partial_month' => date('Y-m')];
            $validator = Validator::make($data)
                ->required('partial_month', 'الشهر')->month('partial_month', 'الدفعة')
                ->required('partial_amount', 'مبلغ الدفعة')->decimal('partial_amount', 'مبلغ الدفعة', 0.01, 10000000);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect($back);
            }

            $month = $data['partial_month'];
            $amount = round((float) $data['partial_amount'], 2);
            $sub = MonthlySubscription::ensureMonthExists((int) $memberId, $month, false);
            $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);

            if ($remaining <= 0) {
                Session::flash('error', 'اشتراك شهر ' . $month . ' مسدد بالكامل.');
                $this->redirect($back);
            }
            if ($amount > $remaining) {
                Session::flash('error', 'المبلغ يتجاوز المتبقي على اشتراك شهر ' . $month . ' (' . money($remaining) . ').');
                $this->redirect($back);
            }

            $newPaid = round((float) $sub['amount_paid'] + $amount, 2);
            $status = $newPaid >= (float) $sub['amount_due'] ? 'paid' : 'partial';
            MonthlySubscription::update($sub['id'], ['amount_paid' => $newPaid, 'status' => $status]);
            Transaction::record((int) $memberId, 'subscription', $sub['id'], $amount, current_admin_id(), 'دفعة جزئية لشهر ' . $month);
            Notification::systemNotify((int) $memberId, 'تسجيل سداد اشتراك', 'تم تسجيل دفعة بقيمة ' . money($amount) . ' على اشتراك شهر ' . $month . '.');
            Session::flash('success', 'تم تسجيل الدفعة بنجاح.');
        } else {
            Session::flash('error', 'نوع العملية غير صحيح.');
        }

        $this->redirect($back);
    }
}
