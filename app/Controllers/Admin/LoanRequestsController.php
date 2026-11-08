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
        $status = (string) $this->input('status', '');
        // "waiting" = approved but the loan was not issued yet (the waiting list for disbursement).
        $conditions = ($status && $status !== 'waiting') ? ['loan_requests.status' => $status] : [];
        $requests = LoanRequest::withMember($status === 'waiting' ? ['loan_requests.status' => 'approved'] : $conditions);
        $allRequests = $status === 'waiting' ? LoanRequest::withMember([]) : $requests;
        if ($status === 'waiting') {
            $requests = array_values(array_filter($requests, fn($r) => empty($r['loan_id'])));
        }

        // The queue position is counted over ALL pending requests, so it stays correct while a search narrows the list.
        $pendingOrder = array_values(array_filter($allRequests, fn($r) => $r['status'] === 'pending'));

        // Several still-open requests (pending, or approved without a loan) of the same member on the same share are
        // probably duplicates: they get a hint so the admin can delete the extra ones.
        $openByShare = [];
        foreach (LoanRequest::withMember([]) as $r) {
            if ($r['status'] === 'pending' || ($r['status'] === 'approved' && empty($r['loan_id']))) {
                $k = (int) $r['member_id'] . ':' . (int) ($r['lot_id'] ?? 0);
                $openByShare[$k] = ($openByShare[$k] ?? 0) + 1;
            }
        }

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
            $isOpen = $item['status'] === 'pending' || ($item['status'] === 'approved' && empty($item['loan_id']));
            $item['duplicate_hint'] = $isOpen && ($openByShare[(int) $item['member_id'] . ':' . (int) ($item['lot_id'] ?? 0)] ?? 0) > 1;
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
            'waitingCount' => count($waiting),
            'reasonLabels' => $this->reasonLabels,
        ], 'admin/layout');
    }

    /** Deletes one request. A loan that was issued from it stays as it is (only its link to the request is removed). */
    private function deleteRequest(int $id): ?array
    {
        $request = LoanRequest::find($id);
        if (!$request) {
            return null;
        }
        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE loans SET loan_request_id = NULL WHERE loan_request_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM loan_requests WHERE id = ?')->execute([$id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $request;
    }

    private function backToList(): string
    {
        $to = ltrim(trim((string) $this->input('redirect_to', '')), '/');
        return ($to === 'admin/loan-requests' || str_starts_with($to, 'admin/loan-requests?')) ? $to : 'admin/loan-requests';
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $request = $this->deleteRequest((int) $id);
        Session::flash($request ? 'success' : 'error', $request ? 'تم حذف طلب القرض رقم ' . $id . '.' : 'طلب القرض غير موجود.');
        $this->redirect($this->backToList());
    }

    public function bulkDelete(): void
    {
        $this->verifyCsrf();
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($_POST['ids'] ?? null) ? $_POST['ids'] : []))));
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->deleteRequest($id)) {
                $deleted++;
            }
        }
        Session::flash($deleted ? 'success' : 'error', $deleted ? 'تم حذف ' . $deleted . ' طلب قرض.' : 'لم يتم تحديد أي طلب.');
        $this->redirect($this->backToList());
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
