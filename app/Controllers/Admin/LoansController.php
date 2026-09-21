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
            $loans = array_values(array_filter($loans, fn($l) => str_contains($l['member_name'], $q) || str_contains($l['member_mobile'], $q)));
        }

        $this->view('admin/loans/index', [
            'pageTitle' => __('loans'),
            'loans' => $loans,
            'q' => $q,
            'status' => $status,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function create(): void
    {
        $requestId = $this->input('request_id');
        $loanRequest = $requestId ? LoanRequest::find((int) $requestId) : null;
        $members = Member::all('name ASC');
        $feePercent = (float) Setting::get('loan_admin_fee_percent', 0);

        $this->view('admin/loans/create', [
            'pageTitle' => __('add_loan'),
            'members' => $members,
            'loanRequest' => $loanRequest,
            'feePercent' => $feePercent,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->all();
        $data += ['loan_date' => '', 'admin_fee_percent' => '', 'reason_other_text' => '', 'loan_request_id' => ''];
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
            ->integer('loan_request_id', 'رقم الطلب', 1);

        if (!$validator->fails() && !Member::find((int) $data['member_id'])) {
            Session::flash('error', 'المشترك المحدد غير موجود.');
            $this->redirect($retry);
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

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $loanId = Loan::create([
                'member_id' => $memberId,
                'loan_request_id' => $data['loan_request_id'] !== '' ? (int) $data['loan_request_id'] : null,
                'amount' => $amount,
                'reason' => $data['reason'],
                'reason_other_text' => $data['reason'] === 'other' ? $data['reason_other_text'] : null,
                'loan_date' => $loanDate,
                'installments_count' => $installmentsCount,
                'installment_value' => $installmentValue,
                'admin_fee_percent' => $feePercent,
                'admin_fee_amount' => $feeAmount,
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
            if ($feeAmount > 0) {
                Transaction::record($memberId, 'loan_admin_fee', $loanId, $feeAmount, current_admin_id(), 'مصاريف إدارية على القرض', $loanDate);
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
                $feeTx = Transaction::first(['related_id' => (int) $id, 'category' => 'loan_admin_fee']);
                if ($feeTx && $feeAmount > 0) {
                    Transaction::update($feeTx['id'], ['amount' => $feeAmount]);
                } elseif ($feeTx) {
                    Transaction::delete($feeTx['id']);
                } elseif ($feeAmount > 0) {
                    Transaction::record((int) $loan['member_id'], 'loan_admin_fee', (int) $id, $feeAmount, current_admin_id(), 'مصاريف إدارية على القرض', $loan['loan_date']);
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

    public function recordPayment(string $id): void
    {
        $this->verifyCsrf();
        $loan = Loan::find((int) $id);
        if (!$loan) {
            $this->redirect('admin/loans');
        }
        $back = 'admin/loans/' . $id;

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('installment_id', 'القسط')->integer('installment_id', 'القسط', 1)
            ->required('amount', 'المبلغ')->decimal('amount', 'المبلغ', 0.01, 10000000);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect($back);
        }

        if ($loan['status'] === 'closed') {
            Session::flash('error', 'القرض مغلق ولا يقبل دفعات جديدة.');
            $this->redirect($back);
        }

        $installmentId = (int) $data['installment_id'];
        $amount = round((float) $data['amount'], 2);
        $installment = LoanInstallment::find($installmentId);

        if (!$installment || (int) $installment['loan_id'] !== (int) $id) {
            Session::flash('error', 'القسط المحدد لا يتبع هذا القرض.');
            $this->redirect($back);
        }

        $remainingOnInstallment = round((float) $installment['amount'] - (float) $installment['amount_paid'], 2);
        if ($remainingOnInstallment <= 0) {
            Session::flash('error', 'هذا القسط مسدد بالكامل.');
            $this->redirect($back);
        }
        if ($amount > $remainingOnInstallment) {
            Session::flash('error', 'المبلغ يتجاوز المتبقي على القسط (' . money($remainingOnInstallment) . ').');
            $this->redirect($back);
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $newPaid = round((float) $installment['amount_paid'] + $amount, 2);
            $status = $newPaid >= (float) $installment['amount'] ? 'paid' : 'partial';
            LoanInstallment::update($installmentId, [
                'amount_paid' => $newPaid,
                'status' => $status,
                'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : $installment['paid_at'],
            ]);

            $installments = LoanInstallment::forLoan((int) $id);
            $totalPaid = array_sum(array_column($installments, 'amount_paid'));
            $remaining = round((float) $loan['amount'] - $totalPaid, 2);
            $loanStatus = $remaining <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'active');

            Loan::update((int) $id, [
                'amount_paid' => $totalPaid,
                'amount_remaining' => max(0, $remaining),
                'status' => $loanStatus,
            ]);

            Transaction::record($loan['member_id'], 'loan_installment', $installmentId, $amount, current_admin_id(), 'سداد قسط رقم ' . $installment['installment_number']);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Notification::systemNotify($loan['member_id'], 'تسجيل سداد قسط', 'تم تسجيل سداد بقيمة ' . money($amount) . ' على القسط رقم ' . $installment['installment_number'] . '.');
        Session::flash('success', 'تم تسجيل دفعة القسط بنجاح.');
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
}
