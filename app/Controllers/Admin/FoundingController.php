<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\FoundingAllocation;
use App\Models\FoundingAmount;
use App\Models\FoundingPayment;
use App\Models\ShareLot;
use App\Models\Transaction;
use App\Models\Notification;

class FoundingController extends Controller
{
    public function index(): void
    {
        // Search by name, mobile or national ID (the same search the members / subscriptions / shares pages use).
        $q = trim((string) $this->input('q', ''));
        $all = $q !== '' ? Member::search($q) : Member::all('name ASC');
        if ($q !== '') {
            usort($all, fn($a, $b) => strcmp((string) $a['name'], (string) $b['name']));
        }
        $registered = Member::withoutRegistrations($all);
        // Without a search only the members with a founding amount to show are listed (shares, or something owed / paid);
        // the search still finds anyone.
        $noFoundingCount = 0;
        if ($q === '') {
            $withData = array_flip(array_map('intval', array_column(
                FoundingAmount::raw('SELECT member_id FROM founding_amounts WHERE total_required > 0 OR amount_paid > 0'),
                'member_id'
            )));
            $visible = array_values(array_filter($registered, fn($m) => (int) $m['shares_count'] > 0 || isset($withData[(int) $m['id']])));
            $noFoundingCount = count($registered) - count($visible);
            $registered = $visible;
        }
        $pager = paginate($registered);
        $members = $pager['items'];
        $rows = [];
        foreach ($members as $m) {
            $f = FoundingAmount::ensureForMember($m['id']);
            $next = FoundingAmount::nextInstallment(FoundingAmount::schedule($f, $m));
            $rows[] = $f + ['member' => $m, 'next_installment' => $next, 'lots' => FoundingAmount::lotBreakdown((int) $m['id'], $f)];
        }

        $this->view('admin/founding/index', [
            'pageTitle' => __('founding_amounts'),
            'rows' => $rows,
            'q' => $q,
            'pager' => $pager,
            'noFoundingCount' => $noFoundingCount,
        ], 'admin/layout');
    }

    public function show(string $memberId): void
    {
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/founding');
        }
        $founding = FoundingAmount::ensureForMember((int) $memberId);
        $payments = FoundingPayment::forMember((int) $memberId);
        $schedule = FoundingAmount::schedule($founding, $member);
        $activeLots = ShareLot::activeFor((int) $memberId);

        // Which share each payment was paid for (a payment can cover more than one).
        $allocations = FoundingAllocation::forMember((int) $memberId);
        $allocByPayment = [];
        foreach ($allocations as $a) {
            $allocByPayment[(int) $a['payment_id']][] = $a;
        }

        $this->view('admin/founding/show', [
            'pageTitle' => __('founding_for', ['name' => $member['name']]),
            'member' => $member,
            'founding' => $founding,
            'payments' => $payments,
            'schedule' => $schedule,
            'nextInstallment' => FoundingAmount::nextInstallment($schedule),
            'activeLots' => $activeLots,
            'lotNumbers' => ShareLot::numbersFor((int) $memberId),
            'breakdown' => FoundingAmount::lotBreakdown((int) $memberId, $founding),
            'allocByPayment' => $allocByPayment,
            'unallocated' => FoundingAllocation::unallocatedPayments((int) $memberId),
        ], 'admin/layout');
    }

    public function recordPayment(string $memberId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/founding');
        }

        $data = $this->all();
        $data += ['payment_date' => date('Y-m-d')];
        $validator = Validator::make($data)
            ->required('amount', 'المبلغ')->decimal('amount', 'المبلغ', 0.01, 10000000)
            ->required('payment_date', 'تاريخ السداد')->date('payment_date', 'السداد')
            ->max('notes', 500, 'الملاحظات');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/founding/' . $memberId);
        }

        $founding = FoundingAmount::ensureForMember((int) $memberId, true);
        $amount = round((float) $data['amount'], 2);
        $date = $data['payment_date'];

        $remaining = round((float) $founding['total_required'] - (float) $founding['amount_paid'], 2);
        if ((float) $founding['total_required'] <= 0) {
            Session::flash('error', 'لا يوجد مبلغ تأسيس مطلوب على هذا المشترك (عدد أسهمه أو رسوم التأسيس تساوي صفراً).');
            $this->redirect('admin/founding/' . $memberId);
        }
        if ($remaining <= 0) {
            Session::flash('error', 'مبلغ التأسيس مسدد بالكامل.');
            $this->redirect('admin/founding/' . $memberId);
        }
        if ($amount > $remaining) {
            Session::flash('error', 'المبلغ يتجاوز المتبقي من مبلغ التأسيس (' . money($remaining) . ').');
            $this->redirect('admin/founding/' . $memberId);
        }

        // The payment is paid FOR ONE SHARE: the admin names it, and only that share is credited. A member who owns
        // shares must name one; the money is never spread over the member's other shares.
        $activeLots = ShareLot::activeFor((int) $memberId);
        $breakdown = FoundingAmount::lotBreakdown((int) $memberId, $founding);
        $lotId = !empty($data['lot_id']) ? (int) $data['lot_id'] : null;
        $lotLabel = '';
        if ($activeLots) {
            if (!$lotId || !isset($breakdown[$lotId])) {
                Session::flash('error', 'اختر السهم الذي تُسدَّد عنه هذه الدفعة.');
                $this->redirect('admin/founding/' . $memberId);
            }
            $lotRow = $breakdown[$lotId];
            if ($lotRow['remaining'] <= 0) {
                Session::flash('error', 'مبلغ التأسيس للسهم رقم ' . $lotRow['number'] . ' مسدد بالكامل.');
                $this->redirect('admin/founding/' . $memberId);
            }
            if ($amount > $lotRow['remaining']) {
                Session::flash('error', 'المبلغ يتجاوز المتبقي من مبلغ تأسيس السهم رقم ' . $lotRow['number'] . ' (' . money($lotRow['remaining']) . ').');
                $this->redirect('admin/founding/' . $memberId);
            }
            $lotLabel = 'السهم رقم ' . $lotRow['number'];
        } else {
            $lotId = null;
        }

        $customNotes = trim((string) ($data['notes'] ?? ''));
        $paymentNotes = $lotLabel
            ? ($lotLabel . ($customNotes !== '' ? ' - ' . $customNotes : ''))
            : $customNotes;

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $paymentId = FoundingPayment::create([
                'founding_amount_id' => $founding['id'],
                'member_id' => $member['id'],
                'amount' => $amount,
                'payment_date' => $date,
                'recorded_by' => current_admin_id(),
                'notes' => $paymentNotes ?: null,
            ]);
            if ($lotId) {
                FoundingAllocation::allocate($paymentId, $lotId, $amount);
            }

            $newPaid = round((float) $founding['amount_paid'] + $amount, 2);
            $status = $newPaid >= (float) $founding['total_required'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
            FoundingAmount::update($founding['id'], ['amount_paid' => $newPaid, 'status' => $status]);

            $txDesc = 'دفعة مبلغ تأسيس' . ($lotLabel ? ' (' . $lotLabel . ')' : '');
            Transaction::record($member['id'], 'founding', $paymentId, $amount, current_admin_id(), $txDesc, $date);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Notification::systemNotify($member['id'], 'تسجيل سداد مبلغ التأسيس', 'تم تسجيل دفعة بقيمة ' . money($amount) . ' على مبلغ التأسيس' . ($lotLabel ? ' (' . $lotLabel . ')' : '') . '.');

        Session::flash('success', 'تم تسجيل الدفعة' . ($lotLabel ? ' على ' . $lotLabel : '') . ' بنجاح.');
        $this->redirect('admin/founding/' . $memberId);
    }

    /**
     * Assigns money that was paid before shares were tracked separately (a payment, or part of one, that belongs to
     * no share yet) to ONE share. Nothing is recorded in the ledger: the money was already received.
     */
    public function allocate(string $memberId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/founding');
        }
        $back = 'admin/founding/' . $memberId;

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('payment_id', 'الدفعة')->integer('payment_id', 'الدفعة', 1)
            ->required('lot_id', 'السهم')->integer('lot_id', 'السهم', 1)
            ->required('amount', 'المبلغ')->decimal('amount', 'المبلغ', 0.01, 10000000);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect($back);
        }

        $payment = null;
        foreach (FoundingAllocation::unallocatedPayments((int) $memberId) as $p) {
            if ((int) $p['id'] === (int) $data['payment_id']) {
                $payment = $p;
            }
        }
        $breakdown = FoundingAmount::lotBreakdown((int) $memberId);
        $lotRow = $breakdown[(int) $data['lot_id']] ?? null;
        $amount = round((float) $data['amount'], 2);

        if (!$payment) {
            Session::flash('error', 'هذه الدفعة مخصصة بالكامل لأسهم المشترك أو لا تتبعه.');
        } elseif (!$lotRow) {
            Session::flash('error', 'السهم المحدد ليس سهماً نشطاً لهذا المشترك.');
        } elseif ($amount > $payment['unallocated']) {
            Session::flash('error', 'المبلغ يتجاوز غير المخصص من هذه الدفعة (' . money($payment['unallocated']) . ').');
        } elseif ($amount > $lotRow['remaining']) {
            Session::flash('error', 'المبلغ يتجاوز المتبقي من مبلغ تأسيس السهم رقم ' . $lotRow['number'] . ' (' . money($lotRow['remaining']) . ').');
        } else {
            FoundingAllocation::allocate((int) $payment['id'], (int) $lotRow['lot_id'], $amount);
            Session::flash('success', 'تم تخصيص ' . money($amount) . ' للسهم رقم ' . $lotRow['number'] . '.');
        }
        $this->redirect($back);
    }

    /** Takes a mistaken assignment back: the money returns to "not assigned to a share" so it can be assigned again. */
    public function deallocate(string $memberId, string $allocationId): void
    {
        $this->verifyCsrf();
        $alloc = FoundingAllocation::find((int) $allocationId);
        $payment = $alloc ? FoundingPayment::find((int) $alloc['payment_id']) : null;
        if ($alloc && $payment && (int) $payment['member_id'] === (int) $memberId) {
            FoundingAllocation::delete((int) $alloc['id']);
            Session::flash('success', 'تم إلغاء تخصيص ' . money($alloc['amount']) . ' من السهم، ويمكنك تخصيصه لسهم آخر.');
        } else {
            Session::flash('error', 'بيانات التخصيص غير صحيحة.');
        }
        $this->redirect('admin/founding/' . $memberId);
    }

    public function setPlan(string $memberId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->redirect('admin/founding');
        }

        $months = (int) $this->input('plan_months', 1);
        if ($months < 1 || $months > FoundingAmount::MAX_PLAN_MONTHS) {
            Session::flash('error', 'مدة خطة السداد غير صحيحة.');
            $this->redirect('admin/founding/' . $memberId);
            return;
        }

        FoundingAmount::setPlan((int) $memberId, $months);
        Session::flash('success', $months === 1 ? 'تم تعيين خطة السداد: دفعة واحدة بنجاح.' : "تم تعيين خطة السداد: تقسيط على {$months} أشهر بنجاح.");
        $this->redirect('admin/founding/' . $memberId);
    }
}
