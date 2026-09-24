<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\MonthlySubscription;
use App\Models\Setting;
use App\Models\ShareLot;
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

        // Paid / overdue months per member in one query. Lots of the same month count as ONE month:
        // paid when everything due that month is collected, late when any part of it is past due.
        $counts = [];
        foreach (MonthlySubscription::raw(
            "SELECT member_id,
                    SUM(due > 0 AND paid >= due) AS paid_months,
                    SUM(late) AS late_months
             FROM (SELECT member_id, month, SUM(amount_due) AS due, SUM(amount_paid) AS paid,
                          MAX(amount_due > amount_paid AND COALESCE(grace_until, due_date) < ?) AS late
                   FROM monthly_subscriptions GROUP BY member_id, month) per_month
             GROUP BY member_id",
            [$today]
        ) as $c) {
            $counts[(int) $c['member_id']] = $c;
        }

        $rows = [];
        foreach ($members as $m) {
            // A member can have several unmerged share lots at once, each with its own row this month: the month's
            // status is derived from the SUM of what is due and paid (paid / partial / unpaid).
            $currentRows = MonthlySubscription::where(['member_id' => $m['id'], 'month' => $currentMonth]);
            $currentStatus = $currentRows ? MonthlySubscription::summarize($currentRows)['status'] : null;
            $late = (int) ($counts[(int) $m['id']]['late_months'] ?? 0);
            // A lot with no row yet this month (nobody has opened it) still counts as overdue once its
            // own due date (its own override, the member's, or the site default) has passed.
            if (empty($currentRows) && (int) $m['shares_count'] > 0 && month_due_date($currentMonth, MonthlySubscription::dueDayFor($m)) < $today) {
                $late++;
                $currentStatus = 'unpaid';
            }
            $rows[] = [
                'member' => $m,
                'share_value' => $shareValue,
                'amount' => $m['shares_count'] * $shareValue,
                'current_status' => $currentStatus ?? 'unpaid',
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

        MonthlySubscription::ensureMonthExistsForMember((int) $memberId, date('Y-m'));
        $history = MonthlySubscription::forMember((int) $memberId);
        $shareValue = (float) Setting::get('share_value', 0);
        $lots = ShareLot::activeFor((int) $memberId);

        // What each lot still owes across its rows. The payment forms only offer lots with something outstanding
        // (a fully paid lot has nothing to record); when every lot is settled they all stay, so a month can still be paid ahead.
        $owed = [];
        foreach ($history as $h) {
            if ($h['lot_id']) {
                $owed[(int) $h['lot_id']] = ($owed[(int) $h['lot_id']] ?? 0) + max(0, round((float) $h['amount_due'] - (float) $h['amount_paid'], 2));
            }
        }
        foreach ($lots as &$lot) {
            $lot['outstanding'] = round($owed[(int) $lot['id']] ?? 0, 2);
        }
        unset($lot);
        $payableLots = array_values(array_filter($lots, fn($l) => $l['outstanding'] > 0));

        $this->view('admin/subscriptions/show', [
            'pageTitle' => __('subscriptions_for', ['name' => $member['name']]),
            'member' => $member,
            'history' => $history,
            'shareValue' => $shareValue,
            'lots' => $lots,
            'payableLots' => $payableLots ?: $lots,
            'allSettled' => !$payableLots,
        ], 'admin/layout');
    }

    /**
     * The subscription row a payment should apply to: the specific lot the admin picked, or -- when
     * the member has at most one row for that month -- that one row with no picking needed. Null when
     * the member has several lots this month and none was specified (the caller must ask the admin to pick).
     */
    private function resolveLotSubscription(int $memberId, string $month, string $lotIdInput): ?array
    {
        $rows = MonthlySubscription::ensureMonthExistsForMember($memberId, $month, false);

        if ($lotIdInput !== '') {
            $lotId = (int) $lotIdInput;
            foreach ($rows as $r) {
                if ((int) ($r['lot_id'] ?? 0) === $lotId) {
                    return $r;
                }
            }
            return null;
        }

        return count($rows) === 1 ? $rows[0] : null;
    }

    /**
     * The earliest month a payment can be recorded for: the start month of the lot the admin picked, or --
     * when none was picked -- of the member's earliest active lot. Null when there is no lot to bound it.
     */
    private function earliestPayableMonth(int $memberId, string $lotIdInput): ?string
    {
        $starts = [];
        foreach (ShareLot::activeFor($memberId) as $lot) {
            if ($lotIdInput === '' || (int) $lot['id'] === (int) $lotIdInput) {
                $starts[] = ShareLot::startMonth($lot);
            }
        }
        return $starts ? min($starts) : null;
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
        $lotIdInput = trim((string) ($data['lot_id'] ?? ''));

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
            $earliest = $this->earliestPayableMonth((int) $memberId, $lotIdInput);
            if ($earliest !== null && $startMonth < $earliest) {
                Session::flash('error', 'لا يمكن تسجيل سداد لشهر قبل بداية الاشتراك (' . $earliest . ').');
                $this->redirect($back);
            }
            $count = (int) $data['months_count'];
            $ts = strtotime($startMonth . '-01');
            $paidAny = false;

            for ($i = 0; $i < $count; $i++) {
                $month = date('Y-m', strtotime("+{$i} month", $ts));
                $sub = $this->resolveLotSubscription((int) $memberId, $month, $lotIdInput);
                if ($sub === null) {
                    Session::flash('error', 'المشترك عنده أكتر من دفعة أسهم منفصلة — حدّد أنهي دفعة تسدّدها.');
                    $this->redirect($back);
                }
                $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);
                if ($remaining > 0) {
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
            $earliest = $this->earliestPayableMonth((int) $memberId, $lotIdInput);
            if ($earliest !== null && $month < $earliest) {
                Session::flash('error', 'لا يمكن تسجيل دفعة لشهر قبل بداية الاشتراك (' . $earliest . ').');
                $this->redirect($back);
            }
            $amount = round((float) $data['partial_amount'], 2);
            $sub = $this->resolveLotSubscription((int) $memberId, $month, $lotIdInput);
            if ($sub === null) {
                Session::flash('error', 'المشترك عنده أكتر من دفعة أسهم منفصلة — حدّد أنهي دفعة تسدّدها.');
                $this->redirect($back);
            }
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
