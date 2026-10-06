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
        $statusCounts = [
            'all' => count($members),
            'late' => 0,
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
        ];

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
                $currentStatus = 'late';
            }

            $currentStatus = $currentStatus ?? 'unpaid';

            if ($late > 0 || $currentStatus === 'late') {
                $statusCounts['late']++;
            }
            if ($currentStatus !== 'late' && isset($statusCounts[$currentStatus])) {
                $statusCounts[$currentStatus]++;
            }

            $rows[] = [
                'member' => $m,
                'share_value' => $shareValue,
                'amount' => $m['shares_count'] * $shareValue,
                'current_status' => $currentStatus,
                'paid_months' => (int) ($counts[(int) $m['id']]['paid_months'] ?? 0),
                'late_months' => $late,
            ];
        }

        $status = trim((string) $this->input('status', ''));
        if ($status === '') {
            $status = trim((string) $this->input('payment_status', ''));
        }

        if ($status !== '' && $status !== 'all') {
            $rows = array_values(array_filter($rows, function($r) use ($status) {
                if ($status === 'late') {
                    return $r['late_months'] > 0 || $r['current_status'] === 'late';
                }
                return $r['current_status'] === $status;
            }));
        }

        $this->view('admin/subscriptions/index', [
            'pageTitle' => __('subscriptions'),
            'rows' => $rows,
            'q' => $q,
            'status' => $status,
            'counts' => $statusCounts,
            'currentMonth' => $currentMonth,
        ], 'admin/layout');
    }

    public function show(string $memberId): void
    {
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/subscriptions');
        }

        $earliestMonth = $this->earliestPayableMonth((int) $memberId, '');
        if ($earliestMonth && $earliestMonth <= date('Y-m')) {
            $cur = strtotime($earliestMonth . '-01');
            $end = strtotime(date('Y-m-01'));
            $safety = 0;
            while ($cur <= $end && $safety < 60) {
                MonthlySubscription::ensureMonthExistsForMember((int) $memberId, date('Y-m', $cur));
                $cur = strtotime('+1 month', $cur);
                $safety++;
            }
        } else {
            MonthlySubscription::ensureMonthExistsForMember((int) $memberId, date('Y-m'));
        }
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
            'founding' => \App\Models\FoundingAmount::ensureForMember((int) $memberId),
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
            $rawDate = trim((string) ($data['start_date'] ?? $data['start_month'] ?? date('Y-m-d')));
            $paymentDate = date('Y-m-d');
            $startMonth = date('Y-m');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $startMonth = substr($rawDate, 0, 7);
                $paymentDate = $rawDate;
            } elseif (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
                $startMonth = $rawDate;
            } else {
                Session::flash('error', 'صيغة تاريخ السداد غير صحيحة.');
                $this->redirect($back);
                return;
            }

            $validator = Validator::make($data)
                ->required('months_count', 'عدد الأشهر')->integer('months_count', 'عدد الأشهر', 1, 24);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect($back);
                return;
            }

            $earliest = $this->earliestPayableMonth((int) $memberId, $lotIdInput);
            if ($earliest !== null && $startMonth < $earliest) {
                Session::flash('error', 'لا يمكن تسجيل سداد لشهر قبل بداية الاشتراك (' . $earliest . ').');
                $this->redirect($back);
                return;
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
                    return;
                }
                $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);
                if ($remaining > 0) {
                    MonthlySubscription::update($sub['id'], ['amount_paid' => $sub['amount_due'], 'status' => 'paid']);
                    Transaction::record((int) $memberId, 'subscription', $sub['id'], $remaining, current_admin_id(), 'سداد اشتراك شهر ' . $month, $paymentDate);
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
            $rawDate = trim((string) ($data['partial_date'] ?? $data['partial_month'] ?? date('Y-m-d')));
            $paymentDate = date('Y-m-d');
            $month = date('Y-m');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $month = substr($rawDate, 0, 7);
                $paymentDate = $rawDate;
            } elseif (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
                $month = $rawDate;
            } else {
                Session::flash('error', 'صيغة تاريخ الدفعة غير صحيحة.');
                $this->redirect($back);
                return;
            }

            $validator = Validator::make($data)
                ->required('partial_amount', 'مبلغ الدفعة')->decimal('partial_amount', 'مبلغ الدفعة', 0.01, 10000000);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect($back);
                return;
            }

            $earliest = $this->earliestPayableMonth((int) $memberId, $lotIdInput);
            if ($earliest !== null && $month < $earliest) {
                Session::flash('error', 'لا يمكن تسجيل دفعة لشهر قبل بداية الاشتراك (' . $earliest . ').');
                $this->redirect($back);
                return;
            }
            $amount = round((float) $data['partial_amount'], 2);
            $sub = $this->resolveLotSubscription((int) $memberId, $month, $lotIdInput);
            if ($sub === null) {
                Session::flash('error', 'المشترك عنده أكتر من دفعة أسهم منفصلة — حدّد أنهي دفعة تسدّدها.');
                $this->redirect($back);
                return;
            }
            $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);

            if ($remaining <= 0) {
                Session::flash('error', 'اشتراك شهر ' . $month . ' مسدد بالكامل.');
                $this->redirect($back);
                return;
            }
            if ($amount > $remaining) {
                Session::flash('error', 'المبلغ يتجاوز المتبقي على اشتراك شهر ' . $month . ' (' . money($remaining) . ').');
                $this->redirect($back);
                return;
            }

            $newPaid = round((float) $sub['amount_paid'] + $amount, 2);
            $status = $newPaid >= (float) $sub['amount_due'] ? 'paid' : 'partial';
            MonthlySubscription::update($sub['id'], ['amount_paid' => $newPaid, 'status' => $status]);
            Transaction::record((int) $memberId, 'subscription', $sub['id'], $amount, current_admin_id(), 'دفعة جزئية لشهر ' . $month, $paymentDate);
            Notification::systemNotify((int) $memberId, 'تسجيل سداد اشتراك', 'تم تسجيل دفعة بقيمة ' . money($amount) . ' على اشتراك شهر ' . $month . '.');
            Session::flash('success', 'تم تسجيل الدفعة بنجاح.');
        } else {
            Session::flash('error', 'نوع العملية غير صحيح.');
        }

        $this->redirect($back);
    }

    public function updateLotDate(string $memberId, string $lotId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        $lot = ShareLot::find((int) $lotId);
        $redirect = trim((string) $this->input('redirect_to', ''));
        $back = ($redirect !== '' && !str_starts_with($redirect, 'http'))
            ? ltrim($redirect, '/')
            : 'admin/subscriptions/' . $memberId;

        if (!$member || !$lot || (int) $lot['member_id'] !== (int) $memberId) {
            Session::flash('error', 'بيانات الحصة غير صحيحة.');
            $this->redirect($back);
            return;
        }

        $date = trim((string) $this->input('start_date', ''));
        if ($date === '' || !strtotime($date)) {
            Session::flash('error', 'تاريخ بداية الاشتراك غير صحيح.');
            $this->redirect($back);
            return;
        }

        $time = !empty($lot['created_at']) ? date('H:i:s', strtotime($lot['created_at'])) : '12:00:00';
        $newTimestamp = date('Y-m-d H:i:s', strtotime($date . ' ' . $time));

        ShareLot::update((int) $lotId, ['created_at' => $newTimestamp]);

        // If requested or if single active lot, sync member registration date
        if (!empty($this->input('sync_member')) || count(ShareLot::activeFor((int) $memberId)) === 1) {
            Member::update((int) $memberId, ['created_at' => $newTimestamp]);
        }

        Session::flash('success', 'تم تعديل تاريخ بداية اشتراك الحصة بنجاح.');
        $this->redirect($back);
    }

    public function resetPayment(string $memberId, string $subId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        $sub = MonthlySubscription::find((int) $subId);
        if (!$member || !$sub || (int) $sub['member_id'] !== (int) $memberId) {
            Session::flash('error', 'سجل الاشتراك غير موجود.');
            $this->redirect('admin/subscriptions/' . $memberId);
        }

        if ((float) $sub['amount_paid'] <= 0) {
            Session::flash('error', 'لا توجد دفعات مسجلة على هذا الشهر لإلغائها.');
            $this->redirect('admin/subscriptions/' . $memberId);
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            $isLate = MonthlySubscription::effectiveDue($sub) < date('Y-m-d');
            $newStatus = ((float) $sub['amount_due'] > 0 && $isLate) ? 'late' : 'unpaid';

            MonthlySubscription::update((int) $subId, [
                'amount_paid' => 0.00,
                'status' => $newStatus,
            ]);

            Transaction::deleteWhere([
                'member_id' => (int) $memberId,
                'category' => 'subscription',
                'related_id' => (int) $subId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم إلغاء سداد اشتراك شهر ' . $sub['month'] . ' بنجاح وحذف المعاملة المالية المرتبطة.');
        $this->redirect('admin/subscriptions/' . $memberId);
    }

    public function updatePayment(string $memberId, string $subId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        $sub = MonthlySubscription::find((int) $subId);
        if (!$member || !$sub || (int) $sub['member_id'] !== (int) $memberId) {
            Session::flash('error', 'سجل الاشتراك غير موجود.');
            $this->redirect('admin/subscriptions/' . $memberId);
        }

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('amount_paid', 'المبلغ المسدد')->decimal('amount_paid', 'المبلغ المسدد', 0, 10000000);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/subscriptions/' . $memberId);
        }

        $newPaid = round((float) $data['amount_paid'], 2);
        $due = (float) $sub['amount_due'];
        if ($newPaid > $due) {
            Session::flash('error', 'المبلغ المسدد لا يمكن أن يتجاوز المستحق (' . money($due) . ').');
            $this->redirect('admin/subscriptions/' . $memberId);
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            if ($newPaid <= 0) {
                $isLate = MonthlySubscription::effectiveDue($sub) < date('Y-m-d');
                $status = ($due > 0 && $isLate) ? 'late' : 'unpaid';
                MonthlySubscription::update((int) $subId, [
                    'amount_paid' => 0.00,
                    'status' => $status,
                ]);
                Transaction::deleteWhere([
                    'member_id' => (int) $memberId,
                    'category' => 'subscription',
                    'related_id' => (int) $subId,
                ]);
            } else {
                $status = $newPaid >= $due ? 'paid' : 'partial';
                MonthlySubscription::update((int) $subId, [
                    'amount_paid' => $newPaid,
                    'status' => $status,
                ]);
                $paymentDate = !empty($data['payment_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['payment_date']) ? $data['payment_date'] : null;
                $tx = Transaction::first([
                    'member_id' => (int) $memberId,
                    'category' => 'subscription',
                    'related_id' => (int) $subId,
                ]);
                if ($tx) {
                    $txUpdate = [
                        'amount' => $newPaid,
                        'description' => ($status === 'paid' ? 'سداد اشتراك شهر ' : 'دفعة جزئية لشهر ') . $sub['month'],
                    ];
                    if ($paymentDate) {
                        $txUpdate['transaction_date'] = $paymentDate;
                    }
                    Transaction::update($tx['id'], $txUpdate);
                } else {
                    Transaction::record((int) $memberId, 'subscription', (int) $subId, $newPaid, current_admin_id(), ($status === 'paid' ? 'سداد اشتراك شهر ' : 'دفعة جزئية لشهر ') . $sub['month'], $paymentDate);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم تعديل مبلغ سداد اشتراك شهر ' . $sub['month'] . ' بنجاح.');
        $this->redirect('admin/subscriptions/' . $memberId);
    }
}
