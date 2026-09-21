<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Member;
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

        $member = Member::find($request['member_id']);
        $newCount = (int) $member['shares_count'];

        if ($request['type'] === 'cancel' && (int) $request['shares_count'] > $newCount) {
            Session::flash('error', 'لا يمكن الموافقة: عدد الأسهم المطلوب إلغاؤها أكبر من أسهم المشترك الحالية (' . $newCount . ').');
            $this->redirect('admin/share-requests');
        }

        if ($request['type'] === 'add') {
            $newCount += (int) $request['shares_count'];
        } elseif ($request['type'] === 'cancel') {
            $newCount = max(0, $newCount - (int) $request['shares_count']);
        }

        Member::update($member['id'], ['shares_count' => $newCount]);
        \App\Models\MonthlySubscription::ensureMonthExists((int) $member['id'], date('Y-m'));

        ShareRequest::update((int) $id, [
            'status' => 'approved',
            'reviewed_by' => current_admin_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'admin_note' => $note,
        ]);

        \App\Models\Notification::systemNotify($member['id'], 'تحديث طلب الأسهم', 'تمت الموافقة على طلب ' . ($this->typeLabels[$request['type']] ?? '') . ' الخاص بك.');

        Session::flash('success', 'تمت الموافقة على الطلب وتحديث أسهم المشترك.');
        $this->redirect('admin/share-requests');
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
