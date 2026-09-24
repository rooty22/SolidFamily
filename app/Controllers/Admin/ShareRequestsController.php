<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Member;
use App\Models\MonthlySubscription;
use App\Models\Notification;
use App\Models\ShareLot;
use App\Models\ShareRequest;

class ShareRequestsController extends Controller
{
    private array $typeLabels = [];

    public function __construct()
    {
        $this->typeLabels = share_request_types();
    }

    public function index(): void
    {
        $status = $this->input('status', '');
        $conditions = $status ? ['share_requests.status' => $status] : [];
        $requests = ShareRequest::withMember($conditions);

        $this->view('admin/share_requests/index', [
            'pageTitle' => __('share_requests'),
            'requests' => $requests,
            'status' => $status,
            'typeLabels' => $this->typeLabels,
        ], 'admin/layout');
    }

    public function show(string $id): void
    {
        $request = ShareRequest::find((int) $id);
        if (!$request) {
            $this->redirect('admin/share-requests');
        }
        $member = Member::find($request['member_id']);

        $this->view('admin/share_requests/show', [
            'pageTitle' => __('share_request_details'),
            'request' => $request,
            'member' => $member,
            'typeLabels' => $this->typeLabels,
        ], 'admin/layout');
    }

    public function approve(string $id): void
    {
        $this->verifyCsrf();
        $request = ShareRequest::find((int) $id);
        if (!$request || $request['status'] !== 'pending') {
            $this->redirect('admin/share-requests');
        }

        $note = (string) $this->input('admin_note', '');
        if (mb_strlen($note) > 500) {
            Session::flash('error', 'حقل الملاحظة يجب ألا يزيد عن 500 حرف.');
            $this->redirect('admin/share-requests');
        }

        $dueDayInput = trim((string) $this->input('subscription_due_day', ''));
        $dueDay = null;
        if ($dueDayInput !== '') {
            if (!preg_match('/^\d{1,2}$/', $dueDayInput) || (int) $dueDayInput < 1 || (int) $dueDayInput > 28) {
                Session::flash('error', 'يوم الاستحقاق يجب أن يكون رقماً بين 1 و28.');
                $this->redirect('admin/share-requests');
            }
            $dueDay = (int) $dueDayInput;
        }

        $member = Member::find($request['member_id']);
        $newCount = (int) $member['shares_count'];

        if ($request['type'] === 'merge' && count(ShareLot::activeFor((int) $member['id'])) < 2) {
            Session::flash('error', 'لا يمكن الموافقة على الدمج: المشترك ليس لديه دفعتا أسهم منفصلتان على الأقل. يمكنك رفض الطلب.');
            $this->redirect('admin/share-requests');
        }

        if ($request['type'] === 'cancel' && (int) $request['shares_count'] > $newCount) {
            Session::flash('error', 'لا يمكن الموافقة: عدد الأسهم المطلوب إلغاؤها أكبر من أسهم المشترك الحالية (' . $newCount . ').');
            $this->redirect('admin/share-requests');
        }

        // members.shares_count stays the single total used everywhere else (loans, founding amount, ...).
        // share_lots is the separate, finer-grained record that actually drives monthly subscription billing:
        // each "add" is its own lot with its own due date until a "merge" request folds every lot into one.
        $carriedPaid = 0.0;
        if ($request['type'] === 'add') {
            $newCount += (int) $request['shares_count'];
            ShareLot::create([
                'member_id' => $member['id'],
                'shares_count' => (int) $request['shares_count'],
                'subscription_due_day' => $dueDay,
                'source_request_id' => (int) $id,
            ]);
        } elseif ($request['type'] === 'cancel') {
            $newCount = max(0, $newCount - (int) $request['shares_count']);
            $before = array_column(ShareLot::activeFor($member['id']), 'id');
            ShareLot::cancelShares($member['id'], (int) $request['shares_count']);
            $after = array_column(ShareLot::activeFor($member['id']), 'id');
            // A lot cancelled down to zero disappears from the active list: its own current-month row would
            // otherwise be left behind still showing as owed for shares the member no longer holds. A row
            // with a real payment already on it is left alone -- cancelling doesn't erase money paid.
            $this->voidStaleLotRows((int) $member['id'], array_diff($before, $after), false);
        } elseif ($request['type'] === 'merge') {
            $before = array_column(ShareLot::activeFor($member['id']), 'id');

            // Capture what was already paid on each lot's current-month row before merging, so it can be
            // carried onto the new combined row instead of lost or, worse, charged for again.
            foreach ($before as $oldLotId) {
                $oldRow = MonthlySubscription::first(['member_id' => $member['id'], 'month' => date('Y-m'), 'lot_id' => $oldLotId]);
                $carriedPaid += (float) ($oldRow['amount_paid'] ?? 0);
            }

            $mergedLot = ShareLot::mergeAllFor($member['id'], $dueDay, (int) $id);

            // mergeAllFor() is a no-op (returns the same lot untouched) when there was nothing -- or only
            // one lot -- to merge; only a genuinely new lot means old rows were actually superseded. When it
            // is real, every old row is voided regardless of what was paid on it -- that amount was already
            // captured into $carriedPaid above and gets applied to the new merged row further down.
            if ($mergedLot && !in_array((int) $mergedLot['id'], $before, true)) {
                $this->voidStaleLotRows((int) $member['id'], $before, true);
            } else {
                $carriedPaid = 0.0;
            }
        }

        $memberUpdate = ['shares_count' => $newCount];
        if ($dueDay !== null) {
            $memberUpdate['subscription_due_day'] = $dueDay;
        }
        Member::update($member['id'], $memberUpdate);
        $newRows = MonthlySubscription::ensureMonthExistsForMember((int) $member['id'], date('Y-m'));

        if ($request['type'] === 'merge' && $carriedPaid > 0) {
            $activeLot = ShareLot::activeFor($member['id'])[0] ?? null;
            foreach ($newRows as $row) {
                if ($activeLot && (int) $row['lot_id'] === (int) $activeLot['id']) {
                    $paid = min((float) $row['amount_due'], $carriedPaid);
                    MonthlySubscription::update($row['id'], [
                        'amount_paid' => $paid,
                        'status' => $paid >= (float) $row['amount_due'] ? 'paid' : ($paid > 0 ? 'partial' : $row['status']),
                    ]);
                    break;
                }
            }
        }

        ShareRequest::update((int) $id, [
            'status' => 'approved',
            'reviewed_by' => current_admin_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'admin_note' => $note,
        ]);

        Notification::systemNotify($member['id'], 'تحديث طلب الأسهم', 'تمت الموافقة على طلب ' . ($this->typeLabels[$request['type']] ?? '') . ' الخاص بك.');

        Session::flash('success', 'تمت الموافقة على الطلب وتحديث أسهم المشترك.');
        $this->redirect('admin/share-requests');
    }

    /**
     * Marks lots that just became inactive (merged away or cancelled to zero) as no longer separately owed
     * this month. With $force false (cancel), a row that already has a real payment on it is left alone --
     * cancelling doesn't erase money paid and there's no new lot to carry it onto. With $force true (merge),
     * every row is voided regardless, since the caller has already carried its amount onto the new merged row.
     */
    private function voidStaleLotRows(int $memberId, array $staleLotIds, bool $force): void
    {
        MonthlySubscription::voidRowsOfLots($memberId, $staleLotIds, $force);
    }

    public function reject(string $id): void
    {
        $this->verifyCsrf();
        $request = ShareRequest::find((int) $id);
        if ($request && $request['status'] === 'pending') {
            $note = (string) $this->input('admin_note', '');
            if (mb_strlen($note) > 500) {
                Session::flash('error', 'حقل الملاحظة يجب ألا يزيد عن 500 حرف.');
                $this->redirect('admin/share-requests');
            }
            ShareRequest::update((int) $id, [
                'status' => 'rejected',
                'reviewed_by' => current_admin_id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
                'admin_note' => $note,
            ]);
            \App\Models\Notification::systemNotify($request['member_id'], 'تحديث طلب الأسهم', 'تم رفض طلب ' . ($this->typeLabels[$request['type']] ?? '') . ' الخاص بك.');
            Session::flash('success', 'تم رفض الطلب.');
        }
        $this->redirect('admin/share-requests');
    }
}
