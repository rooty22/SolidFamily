<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Setting;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\LoanInstallment;
use App\Models\ContentPage;

class LoanController extends Controller
{
    private array $reasonLabels = [];

    public function __construct()
    {
        $this->reasonLabels = loan_reasons();
    }

    public function index(): void
    {
        $member = Auth::member();
        $requests = LoanRequest::where(['member_id' => $member['id']], 'created_at DESC');
        foreach ($requests as &$r) {
            $r['queue_position'] = $r['status'] === 'pending' ? LoanRequest::queuePosition($r['id']) : null;
        }
        unset($r);
        $loans = Loan::forMember($member['id']);

        $running = array_filter($loans, fn($l) => in_array($l['status'], ['active', 'partial'], true));
        $nextInstallment = LoanInstallment::rawOne(
            "SELECT li.*, l.id AS loan_number FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE l.member_id = ? AND l.status IN ('active', 'partial') AND li.status <> 'paid'
             ORDER BY li.due_date ASC, li.installment_number ASC LIMIT 1",
            [$member['id']]
        );

        $this->view('site/loans/index', [
            'pageTitle' => __('loans_and_requests'),
            'requests' => $requests,
            'loans' => $loans,
            'reasonLabels' => $this->reasonLabels,
            'runningCount' => count($running),
            'runningRemaining' => array_sum(array_column($running, 'amount_remaining')),
            'pendingRequests' => count(array_filter($requests, fn($r) => $r['status'] === 'pending')),
            'nextInstallment' => $nextInstallment,
        ], 'site/layout');
    }

    public function createRequest(): void
    {
        $member = Auth::member();
        $commitment = ContentPage::bySlug('loan_commitment');

        $shares = (int) $member['shares_count'];
        $shareValue = (float) Setting::get('share_value', 0);
        $ratio = (float) Setting::get('max_loan_ratio', 10);
        $running = Loan::rawOne(
            "SELECT COUNT(*) AS c, COALESCE(SUM(amount_remaining), 0) AS s FROM loans WHERE member_id = ? AND status IN ('active', 'partial')",
            [$member['id']]
        );

        $this->view('site/loans/create-request', [
            'pageTitle' => __('new_loan_request'),
            'reasonLabels' => $this->reasonLabels,
            'commitmentText' => $commitment['content'] ?? '',
            'shares' => $shares,
            'shareValue' => $shareValue,
            'ratio' => $ratio,
            'capital' => $shares * $shareValue,
            'maxLoan' => $shares * $shareValue * $ratio,
            'runningCount' => (int) $running['c'],
            'runningRemaining' => (float) $running['s'],
            'pendingRequests' => LoanRequest::count(['member_id' => $member['id'], 'status' => 'pending']),
        ], 'site/layout');
    }

    public function storeRequest(): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $data = $this->all();

        $validator = Validator::make($data)
            ->required('amount_requested', 'مبلغ القرض')
            ->decimal('amount_requested', 'مبلغ القرض', 1, 10000000)
            ->required('reason', 'سبب القرض')
            ->in('reason', array_keys($this->reasonLabels), 'سبب القرض')
            ->required('installments_months', 'مدة السداد')
            ->integer('installments_months', 'مدة السداد (بالأشهر)', 1, 60)
            ->max('reason_other_text', 500, 'وصف السبب');

        if (($data['reason'] ?? '') === 'other') {
            $validator->required('reason_other_text', 'وصف السبب الآخر');
        }
        if (empty($data['agreed_terms'])) {
            $validator->required('agreed_terms', 'الموافقة على شروط السداد');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('loans/request');
        }

        $amount = round((float) $data['amount_requested'], 2);
        $months = (int) $data['installments_months'];

        // Eligibility: the requested amount cannot exceed (member capital x max loan ratio).
        $shares = (int) $member['shares_count'];
        $maxLoan = $shares * (float) Setting::get('share_value', 0) * (float) Setting::get('max_loan_ratio', 10);
        if ($shares < 1 || $amount > $maxLoan) {
            Session::flash('error', $shares < 1
                ? 'لا يمكن تقديم طلب قرض قبل امتلاك سهم واحد على الأقل.'
                : 'المبلغ المطلوب يتجاوز الحد الأقصى المسموح لك (' . money($maxLoan) . ').');
            $this->redirect('loans/request');
        }

        LoanRequest::create([
            'member_id' => $member['id'],
            'amount_requested' => $amount,
            'reason' => $data['reason'],
            'reason_other_text' => ($data['reason'] === 'other') ? $data['reason_other_text'] : null,
            'installments_months' => $months,
            'agreed_terms' => 1,
            'status' => 'pending',
        ]);

        Session::flash('success', 'تم إرسال طلب القرض للإدارة للمراجعة.');
        $this->redirect('loans');
    }

    public function show(string $id): void
    {
        $member = Auth::member();
        $loan = Loan::find((int) $id);
        if (!$loan || (int) $loan['member_id'] !== (int) $member['id']) {
            $this->redirect('loans');
        }
        $installments = LoanInstallment::forLoan((int) $id);

        $this->view('site/loans/show', [
            'pageTitle' => __('loan_details'),
            'loan' => $loan,
            'installments' => $installments,
            'reasonLabels' => $this->reasonLabels,
        ], 'site/layout');
    }
}
