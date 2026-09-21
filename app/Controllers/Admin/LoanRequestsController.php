<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Member;
use App\Models\LoanRequest;
use App\Models\Notification;

use App\Models\Setting;
use App\Models\Loan;

class LoanRequestsController extends Controller
{
    public array $reasonLabels = [];

    public function __construct()
    {
        $this->reasonLabels = loan_reasons();
    }

    public function index(): void
    {
        $status = $this->input('status', '');
        $conditions = $status ? ['loan_requests.status' => $status] : [];
        $requests = LoanRequest::withMember($conditions);

        $pendingOrder = array_values(array_filter($requests, fn($r) => $r['status'] === 'pending'));

        $this->view('admin/loan_requests/index', [
            'pageTitle' => __('loan_requests'),
            'requests' => array_reverse($requests),
            'pendingOrder' => $pendingOrder,
            'status' => $status,
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    public function show(string $id): void
    {
        $request = LoanRequest::find((int) $id);
        if (!$request) {
            $this->redirect('admin/loan-requests');
        }
        $member = Member::find($request['member_id']);
        $queuePosition = $request['status'] === 'pending' ? LoanRequest::queuePosition((int) $id) : null;

        $shareValue = (float) (Setting::get('share_value', 1000) ?? 1000);
        $maxLoanRatio = (float) (Setting::get('max_loan_ratio', 10) ?? 10);
        $memberShares = (int) ($member['shares_count'] ?? 1);
        $memberCapital = $memberShares * $shareValue;
        $maxEligibleLoan = $memberCapital * $maxLoanRatio;

        $memberLoans = Loan::forMember((int) $member['id']);
        $activeLoansCount = count(array_filter($memberLoans, fn($l) => in_array($l['status'], ['active', 'partial'])));
        $totalLoansPaid = count(array_filter($memberLoans, fn($l) => in_array($l['status'], ['paid', 'closed'])));

        $this->view('admin/loan_requests/show', [
            'pageTitle' => __('loan_request_details', ['id' => $request['id']]),
            'request' => $request,
            'member' => $member,
            'queuePosition' => $queuePosition,
            'reasonLabels' => $this->reasonLabels,
            'memberShares' => $memberShares,
            'memberCapital' => $memberCapital,
            'maxEligibleLoan' => $maxEligibleLoan,
            'activeLoansCount' => $activeLoansCount,
            'totalLoansPaid' => $totalLoansPaid,
        ], 'admin/layout');
    }

    public function approve(string $id): void
    {
        $this->verifyCsrf();
        $request = LoanRequest::find((int) $id);
        if (!$request || $request['status'] !== 'pending') {
            $this->redirect('admin/loan-requests');
        }

        LoanRequest::update((int) $id, [
            'status' => 'approved',
            'reviewed_by' => current_admin_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        Notification::systemNotify($request['member_id'], 'تحديث طلب القرض', 'تمت الموافقة على طلب القرض الخاص بك، جاري تجهيز بيانات القرض.');

        $this->redirect('admin/loans/create?request_id=' . $id);
    }

    public function reject(string $id): void
    {
        $this->verifyCsrf();
        $request = LoanRequest::find((int) $id);
        if ($request && $request['status'] === 'pending') {
            $note = (string) $this->input('admin_note', '');
            if (mb_strlen($note) > 500) {
                Session::flash('error', 'حقل الملاحظة يجب ألا يزيد عن 500 حرف.');
                $this->redirect('admin/loan-requests/' . $id);
            }
            LoanRequest::update((int) $id, [
                'status' => 'rejected',
                'reviewed_by' => current_admin_id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
                'admin_note' => $note,
            ]);
            Notification::systemNotify($request['member_id'], 'تحديث طلب القرض', 'نعتذر، تم رفض طلب القرض الخاص بك.');
            Session::flash('success', 'تم رفض طلب القرض.');
        }
        $this->redirect('admin/loan-requests');
    }
}
