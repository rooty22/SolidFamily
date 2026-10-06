<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\FoundingAmount;
use App\Models\FoundingPayment;
use App\Models\Transaction;
use App\Models\Notification;

class FoundingController extends Controller
{
    public function index(): void
    {
        $members = Member::all('name ASC');
        $rows = [];
        foreach ($members as $m) {
            $f = FoundingAmount::ensureForMember($m['id']);
            $next = FoundingAmount::nextInstallment(FoundingAmount::schedule($f, $m));
            $rows[] = $f + ['member' => $m, 'next_installment' => $next];
        }

        $this->view('admin/founding/index', [
            'pageTitle' => __('founding_amounts'),
            'rows' => $rows,
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
        $activeLots = \App\Models\ShareLot::activeFor((int) $memberId);

        $this->view('admin/founding/show', [
            'pageTitle' => __('founding_for', ['name' => $member['name']]),
            'member' => $member,
            'founding' => $founding,
            'payments' => $payments,
            'schedule' => $schedule,
            'nextInstallment' => FoundingAmount::nextInstallment($schedule),
            'activeLots' => $activeLots,
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

        $founding = FoundingAmount::ensureForMember((int) $memberId);
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

        // Lot association if selected
        $lotId = !empty($data['lot_id']) ? (int) $data['lot_id'] : null;
        $activeLots = \App\Models\ShareLot::activeFor((int) $memberId);
        $lotLabel = '';
        if ($lotId) {
            $lotIdx = array_search($lotId, array_column($activeLots, 'id'));
            $lotLabel = ($lotIdx !== false) ? ('السهم رقم ' . ($lotIdx + 1)) : ('الحصة #' . $lotId);
        }

        $customNotes = trim((string) ($data['notes'] ?? ''));
        $paymentNotes = $lotLabel 
            ? ($lotLabel . ($customNotes !== '' ? ' - ' . $customNotes : ''))
            : $customNotes;

        $paymentId = FoundingPayment::create([
            'founding_amount_id' => $founding['id'],
            'member_id' => $member['id'],
            'amount' => $amount,
            'payment_date' => $date,
            'recorded_by' => current_admin_id(),
            'notes' => $paymentNotes ?: null,
        ]);

        $newPaid = round((float) $founding['amount_paid'] + $amount, 2);
        $status = $newPaid >= (float) $founding['total_required'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
        FoundingAmount::update($founding['id'], ['amount_paid' => $newPaid, 'status' => $status]);

        $txDesc = 'دفعة مبلغ تأسيس' . ($lotLabel ? ' (' . $lotLabel . ')' : '');
        Transaction::record($member['id'], 'founding', $paymentId, $amount, current_admin_id(), $txDesc, $date);
        Notification::systemNotify($member['id'], 'تسجيل سداد مبلغ التأسيس', 'تم تسجيل دفعة بقيمة ' . money($amount) . ' على مبلغ التأسيس' . ($lotLabel ? ' (' . $lotLabel . ')' : '') . '.');

        Session::flash('success', 'تم تسجيل الدفعة بنجاح.');
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
