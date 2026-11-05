<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\LoanInstallment;
use App\Models\Setting;
use App\Models\ShareLot;
use App\Models\Transaction;
use App\Models\Notification;

class LoansController extends Controller
{
    public array $reasonLabels = [];

    public function __construct()
    {
        $this->reasonLabels = loan_reasons();
    }

    /** Reason + free-text description rules shared by create and edit. */
    private function reasonValidator(array $data): Validator
    {
        $v = Validator::make($data)
            ->required('reason', 'سبب القرض')
            ->in('reason', array_keys($this->reasonLabels), 'سبب القرض')
            ->max('reason_other_text', 500, 'وصف السبب');
        if (($data['reason'] ?? '') === 'other') {
            $v->required('reason_other_text', 'وصف السبب الآخر');
        }
        return $v;
    }

    /** Y-m-d / d/m/Y / d-m-Y -> Y-m-d, or null when it is not a real date. */
    private function parseDate(string $raw): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            $parsed = \DateTime::createFromFormat('!' . $fmt, $raw);
            if ($parsed && $parsed->format($fmt) === $raw) {
                return $parsed->format('Y-m-d');
            }
        }
        return null;
    }

    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $status = $this->input('status', '');
        $conditions = [];
        if ($status) {
            $conditions['loans.status'] = $status;
        }
        $loans = Loan::withMember($conditions);
        if ($q !== '') {
            $code = ltrim($q, '#');
            $loans = array_values(array_filter($loans, fn($l) => str_contains($l['member_name'], $q) || str_contains($l['member_mobile'], $q)
                || (ctype_digit($code) && (string) ($l['member_serial'] ?? '') === $code)
                || strcasecmp((string) ($l['member_uid'] ?? ''), $q) === 0));
        }
        // Commission (administrative fee) filter: paid / unpaid (loans without a commission never match).
        $fee = (string) $this->input('fee', '');
        if (in_array($fee, ['paid', 'unpaid'], true)) {
            $loans = array_values(array_filter($loans, fn($l) => Loan::feeState($l) === $fee));
        }

        // Totals of everything that matches the filters (all pages, not only the one on screen).
        $totals = ['count' => count($loans), 'amount' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'fee_total' => 0.0, 'fee_paid' => 0.0, 'fee_due' => 0.0];
        foreach ($loans as $l) {
            $totals['amount'] += (float) $l['amount'];
            $totals['paid'] += (float) $l['amount_paid'];
            $totals['remaining'] += (float) $l['amount_remaining'];
            if (Loan::hasFee($l)) {
                $totals['fee_total'] += (float) $l['admin_fee_amount'];
                $totals[Loan::feeIsPaid($l) ? 'fee_paid' : 'fee_due'] += (float) $l['admin_fee_amount'];
            }
        }
        $totals = array_map(fn($v) => is_float($v) ? round($v, 2) : $v, $totals);

        $pager = paginate($loans);

        $this->view('admin/loans/index', [
            'totals' => $totals,
            'pageTitle' => __('loans'),
            'loans' => $pager['items'],
            'pager' => $pager,
            'q' => $q,
            'status' => $status,
            'fee' => $fee,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function create(): void
    {
        $requestId = $this->input('request_id');
        $loanRequest = $requestId ? LoanRequest::find((int) $requestId) : null;
        $members = Member::withoutRegistrations(Member::all('name ASC'));
        $feePercent = (float) Setting::get('loan_admin_fee_percent', 0);

        // An approved request whose share still has a loan being repaid cannot be issued yet: it waits in the list.
        $blockingLoan = ($loanRequest && $loanRequest['status'] === 'approved' && !Loan::first(['loan_request_id' => (int) $loanRequest['id']]))
            ? LoanRequest::blockingLoan($loanRequest) : null;

        $this->view('admin/loans/create', [
            'pageTitle' => __('add_loan'),
            'members' => $members,
            'loanRequest' => $loanRequest,
            'blockingLoan' => $blockingLoan,
            'queuePosition' => $loanRequest ? LoanRequest::queuePosition((int) $loanRequest['id']) : 0,
            'feePercent' => $feePercent,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->all();
        $data += ['loan_date' => '', 'admin_fee_percent' => '', 'reason_other_text' => '', 'loan_request_id' => '', 'fee_paid_now' => '', 'fee_paid_at' => ''];
        if ($data['loan_date'] === '') {
            $data['loan_date'] = date('Y-m-d');
        }
        if ($data['admin_fee_percent'] === '') {
            $data['admin_fee_percent'] = '0';
        }
        $retry = 'admin/loans/create' . ($data['loan_request_id'] !== '' ? '?request_id=' . urlencode($data['loan_request_id']) : '');

        $validator = $this->reasonValidator($data)
            ->required('member_id', 'المشترك')->integer('member_id', 'المشترك', 1)
            ->required('amount', 'قيمة القرض')->decimal('amount', 'قيمة القرض', 1, 10000000)
            ->required('installments_count', 'عدد الأقساط')->integer('installments_count', 'عدد الأقساط', 1, 60)
            ->decimal('admin_fee_percent', 'نسبة المصاريف الإدارية', 0, 100)
            ->date('loan_date', 'بدء القرض', 366)
            ->integer('loan_request_id', 'رقم الطلب', 1)
            ->integer('lot_id', 'السهم المستفيد', 1);

        $targetMember = Member::find((int) ($data['member_id'] ?? 0));
        if (!$validator->fails() && !$targetMember) {
            Session::flash('error', 'المشترك المحدد غير موجود.');
            $this->redirect($retry);
            return;
        }

        if (!$validator->fails() && $data['loan_request_id'] !== '') {
            $loanRequest = LoanRequest::find((int) $data['loan_request_id']);
            if (!$loanRequest || $loanRequest['status'] !== 'approved') {
                Session::flash('error', 'طلب القرض غير موجود أو لم تتم الموافقة عليه.');
                $this->redirect('admin/loans/create');
            }
            if ((int) $loanRequest['member_id'] !== (int) $data['member_id']) {
                Session::flash('error', 'طلب القرض لا يخص المشترك المحدد.');
                $this->redirect($retry);
            }
            if (Loan::first(['loan_request_id' => (int) $data['loan_request_id']])) {
                Session::flash('error', 'تم إنشاء قرض لهذا الطلب مسبقاً.');
                $this->redirect('admin/loans');
            }
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect($retry);
        }

        $memberId = (int) $data['member_id'];
        $amount = round((float) $data['amount'], 2);
        $installmentsCount = (int) $data['installments_count'];
        $feePercent = round((float) $data['admin_fee_percent'], 2);
        $loanDate = $data['loan_date'];

        $feeAmount = round($amount * $feePercent / 100, 2);
        $installmentValue = round($amount / $installmentsCount, 2);

        // The commission is normally confirmed later, from the loan page, with the day it was really received. The admin
        // may also say right here that it was already received (and when); otherwise it starts as unpaid.
        $feePaidAt = null;
        if (!empty($data['fee_paid_now']) && $feeAmount > 0) {
            $feePaidAt = $data['fee_paid_at'] !== '' ? $this->parseDate($data['fee_paid_at']) : $loanDate;
            if ($feePaidAt === null || $feePaidAt > date('Y-m-d', strtotime('+5 years'))) {
                Session::flash('error', $feePaidAt === null ? 'تاريخ سداد العمولة غير صحيح.' : 'تاريخ سداد العمولة بعيد جداً في المستقبل.');
                $this->redirect($retry);
                return;
            }
        }

        // The loan is issued against ONE share (lot): the admin picks it (or it comes from the member's request) and only
        // that share's conditions are checked -- never the member's other shares, never the member's totals.
        $request = $data['loan_request_id'] !== '' ? LoanRequest::find((int) $data['loan_request_id']) : null;
        $activeLots = ShareLot::activeFor($memberId);
        if (!$activeLots) {
            Session::flash('error', 'لا يمكن إنشاء قرض لمشترك لا يملك سهماً نشطاً.');
            $this->redirect($retry);
            return;
        }
        if ($request && !empty($request['lot_id'])) {
            $lotId = (int) $request['lot_id'];
        } elseif (!empty($data['lot_id'])) {
            $lotId = (int) $data['lot_id'];
        } elseif (count($activeLots) === 1) {
            $lotId = (int) $activeLots[0]['id'];
        } else {
            Session::flash('error', 'اختر السهم المستفيد من القرض.');
            $this->redirect($retry);
            return;
        }

        $lot = null;
        foreach ($activeLots as $l) {
            if ((int) $l['id'] === $lotId) {
                $lot = $l;
            }
        }
        if (!$lot) {
            Session::flash('error', 'السهم المحدد ليس سهماً نشطاً لهذا المشترك.');
            $this->redirect($retry);
            return;
        }

        // The 6 months are counted up to the loan date (a back-dated entry is judged on that day, a future one on today).
        $elig = ShareLot::eligibilityDetails($lot, $targetMember, null, min($loanDate, date('Y-m-d')));
        if (!$elig['is_eligible'] && $request && $elig['blockers'] === ['open_loan']) {
            // Only the share's running loan stands in the way: the approved request is not refused, it waits (with its
            // number in the waiting list) until that loan is settled.
            $pos = LoanRequest::queuePosition((int) $request['id']);
            Session::flash('error', 'الطلب رقم ' . $request['id'] . ' موافَق عليه ومسجّل في قائمة الانتظار' . ($pos ? ' برقم #' . $pos : '') . ': على ' . ShareLot::label($memberId, $lotId) . ' قرض قائم رقم ' . $elig['open_loan']['id'] . ' لم يُسدد بعد، ولا يمكن إصدار قرض ثانٍ على نفس السهم قبل سداده. يُصدر هذا القرض من صفحة الطلب بعد السداد.');
            $this->redirect('admin/loan-requests/' . $request['id']);
            return;
        }
        if (!$elig['is_eligible']) {
            $messages = ShareLot::blockerMessages($elig, ShareLot::label($memberId, $lotId));
            Session::flash('error', 'لا يمكن إنشاء القرض على هذا السهم لعدم استيفاء الشروط: ' . implode(' ', $messages));
            $this->redirect($retry);
            return;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // A loan the admin opens himself needs no review: it is recorded in the requests list as already approved
            // (a member's own request stays "pending" until the admin approves it).
            $requestId = $data['loan_request_id'] !== '' ? (int) $data['loan_request_id'] : null;
            if ($requestId === null) {
                $requestId = (int) LoanRequest::create([
                    'member_id' => $memberId,
                    'lot_id' => $lotId,
                    'amount_requested' => $amount,
                    'reason' => $data['reason'],
                    'reason_other_text' => $data['reason'] === 'other' ? $data['reason_other_text'] : null,
                    'installments_months' => $installmentsCount,
                    'agreed_terms' => 1,
                    'status' => 'approved',
                    'admin_note' => 'قرض أضافه المشرف مباشرة',
                    'reviewed_by' => current_admin_id(),
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $loanId = Loan::create([
                'member_id' => $memberId,
                'loan_request_id' => $requestId,
                'lot_id' => $lotId,
                'amount' => $amount,
                'reason' => $data['reason'],
                'reason_other_text' => $data['reason'] === 'other' ? $data['reason_other_text'] : null,
                'loan_date' => $loanDate,
                'installments_count' => $installmentsCount,
                'installment_value' => $installmentValue,
                'admin_fee_percent' => $feePercent,
                'admin_fee_amount' => $feeAmount,
                'fee_status' => $feePaidAt ? 'paid' : 'unpaid',
                'fee_paid_at' => $feePaidAt,
                'fee_confirmed_by' => $feePaidAt ? current_admin_id() : null,
                'amount_paid' => 0,
                'amount_remaining' => $amount,
                'status' => 'active',
            ]);

            $remainingTotal = $amount;
            for ($i = 1; $i <= $installmentsCount; $i++) {
                $value = $i === $installmentsCount ? round($remainingTotal, 2) : $installmentValue;
                $remainingTotal -= $value;
                LoanInstallment::create([
                    'loan_id' => $loanId,
                    'installment_number' => $i,
                    'due_date' => add_months($loanDate, $i),
                    'amount' => $value,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }

            Transaction::record($memberId, 'loan_disbursement', $loanId, $amount, current_admin_id(), 'صرف قرض جديد', $loanDate);
            // The commission is NOT booked automatically: it counts as received only once the admin confirms the
            // payment, with the day it was really received (here, or later from the loan page: updateFee), because
            // members often pay it a week or more after the loan.
            if ($feePaidAt) {
                Transaction::record($memberId, 'loan_admin_fee', $loanId, $feeAmount, current_admin_id(), 'تحصيل عمولة القرض رقم ' . $loanId, $feePaidAt);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Notification::systemNotify($memberId, 'تم منح القرض', 'تم منحك قرضاً بقيمة ' . money($amount) . ' على ' . $installmentsCount . ' قسط.');

        Session::flash('success', 'تم إنشاء القرض وجدولة الأقساط بنجاح.');
        $this->redirect('admin/loans/' . $loanId);
    }

    /**
     * JSON for the "create loan" form: the member's active shares with the eligibility of EACH share on its own
     * (so the admin sees which share can take a loan). ?as_of=YYYY-MM-DD judges the 6 months on the loan date.
     */
    public function memberLots(string $memberId): void
    {
        $member = Member::find((int) $memberId);
        if (!$member) {
            $this->json(['lots' => []], 404);
        }
        $asOf = (string) $this->input('as_of', '');
        $asOf = preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOf) ? min($asOf, date('Y-m-d')) : date('Y-m-d');

        $founding = \App\Models\FoundingAmount::ensureForMember((int) $member['id']);
        $lots = [];
        foreach (ShareLot::activeFor((int) $member['id']) as $lot) {
            $elig = ShareLot::eligibilityDetails($lot, $member, $founding, $asOf);
            $lots[] = [
                'id' => (int) $lot['id'],
                'label' => ShareLot::label((int) $member['id'], (int) $lot['id'], (int) $lot['shares_count']),
                'eligible' => (bool) $elig['is_eligible'],
                'founding' => money($elig['founding_paid']) . ' / ' . money($elig['founding_required']),
                'messages' => ShareLot::blockerMessages($elig, ShareLot::label((int) $member['id'], (int) $lot['id'])),
            ];
        }
        $this->json(['lots' => $lots]);
    }

    /**
     * Manual commission management: the admin confirms (paid + payment date) or reverts (unpaid) the receipt of the
     * loan's administrative fee. Only a confirmed payment appears in the ledger, dated with the payment date.
     */
    public function updateFee(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }
        // Called from the loan page, or from the quick form of the loans list (which says where to come back to).
        $back = 'admin/loans/' . $id;
        $returnTo = ltrim(trim((string) $this->input('redirect_to', '')), '/');
        if ($returnTo === 'admin/loans' || str_starts_with($returnTo, 'admin/loans?')) {
            $back = $returnTo;
        }

        if (!Loan::hasFee($loan)) {
            Session::flash('error', 'لا توجد عمولة على هذا القرض.');
            $this->redirect($back);
        }

        $status = (string) $this->input('fee_status', '');
        if (!in_array($status, ['paid', 'unpaid'], true)) {
            Session::flash('error', 'حالة العمولة غير صحيحة.');
            $this->redirect($back);
        }

        $paidAt = null;
        if ($status === 'paid') {
            $raw = trim((string) $this->input('fee_paid_at', ''));
            $paidAt = date('Y-m-d');
            if ($raw !== '') {
                $paidAt = $this->parseDate($raw);
                if ($paidAt === null) {
                    Session::flash('error', 'تاريخ سداد العمولة غير صحيح.');
                    $this->redirect($back);
                }
            }
            if ($paidAt > date('Y-m-d', strtotime('+5 years'))) {
                Session::flash('error', 'تاريخ سداد العمولة بعيد جداً في المستقبل.');
                $this->redirect($back);
            }
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $feeTx = Transaction::first(['related_id' => (int) $id, 'category' => 'loan_admin_fee']);
            if ($status === 'paid') {
                $note = 'تحصيل عمولة القرض رقم ' . $id;
                if ($feeTx) {
                    Transaction::update($feeTx['id'], ['amount' => $loan['admin_fee_amount'], 'transaction_date' => $paidAt, 'recorded_by' => current_admin_id(), 'notes' => $note]);
                } else {
                    Transaction::record((int) $loan['member_id'], 'loan_admin_fee', (int) $id, (float) $loan['admin_fee_amount'], current_admin_id(), $note, $paidAt);
                }
            } else {
                Transaction::deleteWhere(['related_id' => (int) $id, 'category' => 'loan_admin_fee']);
            }
            Loan::update((int) $id, [
                'fee_status' => $status,
                'fee_paid_at' => $paidAt,
                'fee_confirmed_by' => current_admin_id(),
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if ($status === 'paid') {
            Notification::systemNotify((int) $loan['member_id'], 'تسجيل سداد عمولة القرض', 'تم تسجيل سداد عمولة القرض رقم ' . $id . ' بقيمة ' . money($loan['admin_fee_amount']) . ' بتاريخ ' . date_ar($paidAt) . '.');
            Session::flash('success', 'تم تسجيل سداد العمولة بتاريخ ' . date_ar($paidAt) . '.');
        } else {
            Session::flash('success', 'تم إرجاع حالة العمولة إلى غير مدفوعة.');
        }
        $this->redirect($back);
    }

    public function show(string $id): void
    {
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }
        $member = Member::find($loan['member_id']);
        $installments = LoanInstallment::forLoan((int) $id);

        $this->view('admin/loans/show', [
            'pageTitle' => __('loan_number', ['id' => $id]),
            'loan' => $loan,
            'member' => $member,
            'installments' => $installments,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function edit(string $id): void
    {
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }
        $member = Member::find($loan['member_id']);

        $this->view('admin/loans/edit', [
            'pageTitle' => __('edit_loan', ['id' => $id]),
            'loan' => $loan,
            'member' => $member,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }

        $data = $this->all();
        $data += ['reason_other_text' => ''];
        $editable = (float) $loan['amount_paid'] <= 0;

        $validator = $this->reasonValidator($data);
        if ($editable) {
            $validator
                ->required('amount', 'قيمة القرض')->decimal('amount', 'قيمة القرض', 1, 10000000)
                ->required('installments_count', 'عدد الأقساط')->integer('installments_count', 'عدد الأقساط', 1, 60);
        }
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/loans/' . $id . '/edit');
        }

        $update = [
            'reason' => $data['reason'],
            'reason_other_text' => $data['reason'] === 'other' ? $data['reason_other_text'] : null,
        ];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            if ($editable) {
                $amount = round((float) $data['amount'], 2);
                $installmentsCount = (int) $data['installments_count'];
                $installmentValue = round($amount / $installmentsCount, 2);

                $update['amount'] = $amount;
                $update['installments_count'] = $installmentsCount;
                $update['installment_value'] = $installmentValue;
                $update['amount_remaining'] = $amount;

                // Keep the admin fee and the ledger in step with the new principal (no payments exist yet).
                $feeAmount = round($amount * (float) $loan['admin_fee_percent'] / 100, 2);
                $update['admin_fee_amount'] = $feeAmount;
                Transaction::updateWhere(['related_id' => (int) $id, 'category' => 'loan_disbursement'], ['amount' => $amount]);
                // The ledger holds the commission only after the admin confirmed it was received: keep that entry in
                // step with the new amount, and never create one here.
                $feeTx = Transaction::first(['related_id' => (int) $id, 'category' => 'loan_admin_fee']);
                if ($feeTx && $feeAmount > 0) {
                    Transaction::update($feeTx['id'], ['amount' => $feeAmount]);
                } elseif ($feeTx) {
                    Transaction::delete($feeTx['id']);
                }
                if ($feeAmount <= 0) {
                    $update['fee_status'] = 'unpaid';
                    $update['fee_paid_at'] = null;
                }

                LoanInstallment::deleteWhere(['loan_id' => $id]);
                $remainingTotal = $amount;
                for ($i = 1; $i <= $installmentsCount; $i++) {
                    $value = $i === $installmentsCount ? round($remainingTotal, 2) : $installmentValue;
                    $remainingTotal -= $value;
                    LoanInstallment::create([
                        'loan_id' => $id,
                        'installment_number' => $i,
                        'due_date' => add_months($loan['loan_date'], $i),
                        'amount' => $value,
                        'amount_paid' => 0,
                        'status' => 'unpaid',
                    ]);
                }
            }

            Loan::update((int) $id, $update);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم تحديث بيانات القرض.');
        $this->redirect('admin/loans/' . $id);
    }

    /**
     * Records a repayment. The amount is applied to the chosen installment first and any excess flows on to the
     * following installments in order, so a member may pay more than one installment or settle the whole balance at once.
     */
    public function recordPayment(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }
        $back = 'admin/loans/' . $id;

        $data = $this->all();
        $data += ['payment_date' => ''];
        $validator = Validator::make($data)
            ->required('installment_id', 'القسط')->integer('installment_id', 'القسط', 1)
            ->required('amount', 'المبلغ')->decimal('amount', 'المبلغ', 0.01, 10000000)
            ->date('payment_date', 'السداد', 1825);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect($back);
        }

        if ($loan['status'] === 'closed') {
            Session::flash('error', 'القرض مغلق ولا يقبل دفعات جديدة.');
            $this->redirect($back);
        }

        $paymentDate = date('Y-m-d');
        if ($data['payment_date'] !== '') {
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
                $parsed = \DateTime::createFromFormat('!' . $fmt, $data['payment_date']);
                if ($parsed && $parsed->format($fmt) === $data['payment_date']) {
                    $paymentDate = $parsed->format('Y-m-d');
                    break;
                }
            }
        }

        $installmentId = (int) $data['installment_id'];
        $amount = round((float) $data['amount'], 2);
        $installment = LoanInstallment::find($installmentId);

        if (!$installment || (int) $installment['loan_id'] !== (int) $id) {
            Session::flash('error', 'القسط المحدد لا يتبع هذا القرض.');
            $this->redirect($back);
        }

        // Installments that can absorb this payment: the chosen one and every later one that still has a balance.
        $targets = array_values(array_filter(
            LoanInstallment::forLoan((int) $id),
            fn($i) => (int) $i['installment_number'] >= (int) $installment['installment_number']
                && round((float) $i['amount'] - (float) $i['amount_paid'], 2) > 0
        ));
        if (!$targets) {
            Session::flash('error', 'هذا القسط مسدد بالكامل.');
            $this->redirect($back);
        }
        $available = round(array_sum(array_map(fn($i) => (float) $i['amount'] - (float) $i['amount_paid'], $targets)), 2);
        if ($amount > $available) {
            Session::flash('error', 'المبلغ يتجاوز المتبقي على القرض من هذا القسط فما بعده (' . money($available) . ').');
            $this->redirect($back);
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $left = $amount;
            $touched = [];
            foreach ($targets as $t) {
                if ($left <= 0) {
                    break;
                }
                $apply = min($left, round((float) $t['amount'] - (float) $t['amount_paid'], 2));
                $left = round($left - $apply, 2);
                $newPaid = round((float) $t['amount_paid'] + $apply, 2);
                LoanInstallment::update((int) $t['id'], [
                    'amount_paid' => $newPaid,
                    'status' => $newPaid >= (float) $t['amount'] ? 'paid' : 'partial',
                    'paid_at' => $paymentDate . ' 00:00:00',
                ]);
                Transaction::record((int) $loan['member_id'], 'loan_installment', (int) $t['id'], $apply, current_admin_id(), 'سداد قسط رقم ' . $t['installment_number'], $paymentDate);
                $touched[] = $t['installment_number'];
            }

            $installments = LoanInstallment::forLoan((int) $id);
            $totalPaid = array_sum(array_column($installments, 'amount_paid'));
            $remaining = round((float) $loan['amount'] - $totalPaid, 2);
            $loanStatus = $remaining <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'active');

            Loan::update((int) $id, [
                'amount_paid' => $totalPaid,
                'amount_remaining' => max(0, $remaining),
                'status' => $loanStatus,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $which = count($touched) > 1 ? 'الأقساط ' . implode('، ', $touched) : 'القسط رقم ' . $touched[0];
        Notification::systemNotify($loan['member_id'], 'تسجيل سداد قسط', 'تم تسجيل سداد بقيمة ' . money($amount) . ' بتاريخ ' . date_ar($paymentDate) . ' على ' . $which . '.');
        Session::flash('success', $remaining <= 0 ? 'تم تسجيل الدفعة وسداد القرض بالكامل.' : 'تم تسجيل الدفعة بنجاح على ' . $which . '.');
        $this->redirect($back);
    }

    public function close(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if ($loan && (float) $loan['amount_remaining'] <= 0) {
            Loan::update((int) $id, ['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
            Session::flash('success', 'تم إغلاق القرض بنجاح.');
        } else {
            Session::flash('error', 'لا يمكن إغلاق القرض قبل اكتمال السداد.');
        }
        $this->redirect('admin/loans/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }

        // A loan that already received money is part of the accounting history and must not disappear.
        if ((float) $loan['amount_paid'] > 0 || LoanInstallment::sum('amount_paid', ['loan_id' => (int) $id]) > 0) {
            Session::flash('error', 'لا يمكن حذف قرض عليه دفعات مسددة، حفاظاً على سجلات الحسابات.');
            $this->redirect('admin/loans/' . $id);
        }

        // Never repaid: remove the loan together with the ledger rows that its creation produced.
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Transaction::deleteWhere(['related_id' => (int) $id, 'category' => 'loan_disbursement']);
            Transaction::deleteWhere(['related_id' => (int) $id, 'category' => 'loan_admin_fee']);
            Loan::delete((int) $id);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم حذف القرض.');
        $this->redirect('admin/loans');
    }

    public function resetPayment(string $id, string $installmentId): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        $installment = LoanInstallment::find((int) $installmentId);
        if (!$loan || !$installment || (int) $installment['loan_id'] !== (int) $id) {
            Session::flash('error', 'بيانات القرض أو القسط غير صحيحة.');
            $this->redirect('admin/loans/' . $id);
        }

        if ((float) $installment['amount_paid'] <= 0) {
            Session::flash('error', 'لا يوجد سداد مسجل على هذا القسط لإلغائه.');
            $this->redirect('admin/loans/' . $id);
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            LoanInstallment::update((int) $installmentId, [
                'amount_paid' => 0.00,
                'status' => 'unpaid',
                'paid_at' => null,
            ]);

            Transaction::deleteWhere([
                'member_id' => (int) $loan['member_id'],
                'category' => 'loan_installment',
                'related_id' => (int) $installmentId,
            ]);

            $installments = LoanInstallment::forLoan((int) $id);
            $totalPaid = array_sum(array_column($installments, 'amount_paid'));
            $remaining = round((float) $loan['amount'] - $totalPaid, 2);
            $loanStatus = $remaining <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'active');

            $loanUpdate = [
                'amount_paid' => $totalPaid,
                'amount_remaining' => max(0, $remaining),
                'status' => $loanStatus,
            ];
            if ($loan['status'] === 'closed' && $remaining > 0) {
                $loanUpdate['closed_at'] = null;
            }
            Loan::update((int) $id, $loanUpdate);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم إلغاء سداد القسط رقم ' . $installment['installment_number'] . ' وتحديث رصيد القرض بنجاح.');
        $this->redirect('admin/loans/' . $id);
    }

    public function updatePayment(string $id, string $installmentId): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        $installment = LoanInstallment::find((int) $installmentId);
        if (!$loan || !$installment || (int) $installment['loan_id'] !== (int) $id) {
            Session::flash('error', 'بيانات القرض أو القسط غير صحيحة.');
            $this->redirect('admin/loans/' . $id);
        }

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('amount_paid', 'المبلغ المسدد')->decimal('amount_paid', 'المبلغ المسدد', 0, 10000000);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/loans/' . $id);
        }

        $newPaid = round((float) $data['amount_paid'], 2);
        $instAmount = (float) $installment['amount'];
        if ($newPaid > $instAmount) {
            Session::flash('error', 'المبلغ المسدد لا يمكن أن يتجاوز قيمة القسط (' . money($instAmount) . ').');
            $this->redirect('admin/loans/' . $id);
        }

        // The day the money was really paid: chosen here (any day: before the loan date, today or a coming date). Left empty, the day already
        // recorded is kept (editing an amount never moves a payment to today).
        $existingTx = Transaction::first([
            'member_id' => (int) $loan['member_id'],
            'category' => 'loan_installment',
            'related_id' => (int) $installmentId,
        ]);
        $raw = trim((string) ($data['payment_date'] ?? ''));
        if ($raw !== '') {
            $payDate = $this->parseDate($raw);
            if ($payDate === null) {
                Session::flash('error', 'تاريخ السداد غير صحيح.');
                $this->redirect('admin/loans/' . $id);
            }
            if ($payDate > date('Y-m-d', strtotime('+5 years'))) {
                Session::flash('error', 'تاريخ السداد بعيد جداً في المستقبل.');
                $this->redirect('admin/loans/' . $id);
            }
        } else {
            $payDate = substr((string) ($existingTx['transaction_date'] ?? $installment['paid_at'] ?? ''), 0, 10) ?: date('Y-m-d');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            if ($newPaid <= 0) {
                LoanInstallment::update((int) $installmentId, [
                    'amount_paid' => 0.00,
                    'status' => 'unpaid',
                    'paid_at' => null,
                ]);
                Transaction::deleteWhere([
                    'member_id' => (int) $loan['member_id'],
                    'category' => 'loan_installment',
                    'related_id' => (int) $installmentId,
                ]);
            } else {
                $status = $newPaid >= $instAmount ? 'paid' : 'partial';
                LoanInstallment::update((int) $installmentId, [
                    'amount_paid' => $newPaid,
                    'status' => $status,
                    'paid_at' => $payDate . ' 00:00:00',
                ]);
                if ($existingTx) {
                    Transaction::update($existingTx['id'], ['amount' => $newPaid, 'transaction_date' => $payDate]);
                } else {
                    Transaction::record((int) $loan['member_id'], 'loan_installment', (int) $installmentId, $newPaid, current_admin_id(), 'سداد قسط رقم ' . $installment['installment_number'], $payDate);
                }
            }

            $installments = LoanInstallment::forLoan((int) $id);
            $totalPaid = array_sum(array_column($installments, 'amount_paid'));
            $remaining = round((float) $loan['amount'] - $totalPaid, 2);
            $loanStatus = $remaining <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'active');

            $loanUpdate = [
                'amount_paid' => $totalPaid,
                'amount_remaining' => max(0, $remaining),
                'status' => $loanStatus,
            ];
            if ($loan['status'] === 'closed' && $remaining > 0) {
                $loanUpdate['closed_at'] = null;
            }
            Loan::update((int) $id, $loanUpdate);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Session::flash('success', 'تم تعديل مبلغ سداد القسط رقم ' . $installment['installment_number'] . ' وتحديث رصيد القرض بنجاح.');
        $this->redirect('admin/loans/' . $id);
    }
}
