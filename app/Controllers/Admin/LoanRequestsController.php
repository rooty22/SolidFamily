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

        // The queue position is counted over ALL pending requests, so it stays correct while a search narrows the list.
        $pendingOrder = array_values(array_filter($requests, fn($r) => $r['status'] === 'pending'));

        $q = trim((string) $this->input('q', ''));
        if ($q !== '') {
            $code = ltrim($q, '#');
            $requests = array_values(array_filter($requests, fn($r) => mb_stripos((string) $r['member_name'], $q) !== false
                || str_contains((string) $r['member_mobile'], $q)
                || (ctype_digit($code) && (string) ($r['member_serial'] ?? '') === $code)
                || strcasecmp((string) ($r['member_uid'] ?? ''), $q) === 0));
        }

        $pager = paginate(array_reverse($requests));

        // Approved requests whose loan was not issued yet: their number in the waiting list, and whether the share still
        // has a loan being repaid (then the request keeps its number and waits for that loan to be settled).
        $waiting = LoanRequest::waitingPositions();
        foreach ($pager['items'] as &$item) {
            $item['waiting_position'] = $waiting[(int) $item['id']] ?? null;
            $item['blocking_loan'] = $item['waiting_position'] !== null ? LoanRequest::blockingLoan($item) : null;
        }
        unset($item);

        $this->view('admin/loan_requests/index', [
            'pageTitle' => __('loan_requests'),
            'requests' => $pager['items'],
            'pager' => $pager,
            'pendingOrder' => $pendingOrder,
            'status' => $status,
            'q' => $q,
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
        $queuePosition = in_array($request['status'], ['pending', 'approved'], true) ? LoanRequest::queuePosition((int) $id) : null;
        $issuedLoan = $request['status'] === 'approved' ? Loan::first(['loan_request_id' => (int) $id]) : null;
        $blockingLoan = ($request['status'] === 'approved' && !$issuedLoan) ? LoanRequest::blockingLoan($request) : null;

        $shareValue = (float) (Setting::get('share_value', 1000) ?? 1000);
        $memberShares = (int) ($member['shares_count'] ?? 1);
        $memberCapital = $memberShares * $shareValue;

        $memberLoans = Loan::forMember((int) $member['id']);
        $activeLoansCount = count(array_filter($memberLoans, fn($l) => in_array($l['status'], ['active', 'partial'])));
        $totalLoansPaid = count(array_filter($memberLoans, fn($l) => in_array($l['status'], ['paid', 'closed'])));

        $memberLots = \App\Models\ShareLot::activeFor((int) $member['id']);
        $founding = \App\Models\FoundingAmount::ensureForMember((int) $member['id']);

        $selectedLot = !empty($request['lot_id']) ? \App\Models\ShareLot::find((int) $request['lot_id']) : null;

        $this->view('admin/loan_requests/show', [
            'pageTitle' => __('loan_request_details', ['id' => $request['id']]),
            'request' => $request,
            'member' => $member,
            'queuePosition' => $queuePosition ?: null,
            'issuedLoan' => $issuedLoan,
            'blockingLoan' => $blockingLoan,
            'reasonLabels' => $this->reasonLabels,
            'memberShares' => $memberShares,
            'memberCapital' => $memberCapital,
            'selectedLot' => $selectedLot,
            'activeLoansCount' => $activeLoansCount,
            'totalLoansPaid' => $totalLoansPaid,
            'memberLots' => $memberLots,
            'founding' => $founding,
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

        // The request joins the waiting list at the next free number. If the share still has a loan being repaid (for
        // example a first request of the same share was already issued) there is nothing to prepare now: the request
        // keeps its number and the loan can be issued once the current one is settled.
        $position = LoanRequest::queuePosition((int) $id);
        $blocking = LoanRequest::blockingLoan($request);
        if ($blocking) {
            Notification::systemNotify($request['member_id'], 'تحديث طلب القرض', 'تمت الموافقة على طلب القرض الخاص بك وترتيبك في قائمة الانتظار رقم #' . $position . '، وسيُصدر القرض بعد سداد قرضك الحالي رقم ' . $blocking['id'] . ' على نفس السهم.');
            Session::flash('success', 'تمت الموافقة على الطلب وسُجّل في قائمة الانتظار برقم #' . $position . '. لا يمكن إصدار قرض ثانٍ على نفس السهم قبل سداد القرض القائم رقم ' . $blocking['id'] . '؛ يمكنك إصدار هذا القرض من صفحة الطلب بعد سداده.');
            $this->redirect('admin/loan-requests/' . $id);
            return;
        }

        Notification::systemNotify($request['member_id'], 'تحديث طلب القرض', 'تمت الموافقة على طلب القرض الخاص بك وترتيبك في قائمة الانتظار رقم #' . $position . '، جاري تجهيز بيانات القرض.');

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
