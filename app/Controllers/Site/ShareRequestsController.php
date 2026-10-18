<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ShareLot;
use App\Models\ShareRequest;

class ShareRequestsController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        ShareRequest::repairMissingMergeCounts();
        $requests = ShareRequest::where(['member_id' => $member['id']], 'created_at DESC');

        $pager = paginate($requests, 10);

        $this->view('site/share_requests/index', $this->allowances($member) + [
            'pageTitle' => __('share_requests'),
            'requests' => $requests,
            'requestsPage' => $pager['items'],
            'pager' => $pager,
            'typeLabels' => share_request_types(),
            'member' => $member,
        ], 'site/layout');
    }

    /**
     * What this member can meaningfully request right now:
     *  - add:    always (that is how a member with no shares gets them)
     *  - cancel: only shares they own that are not already covered by a pending cancel request
     *  - merge:  only with at least two separate share lots, and none already pending
     */
    private function allowances(array $member): array
    {
        $owned = (int) $member['shares_count'];
        $pendingCancel = (int) ShareRequest::sum('shares_count', ['member_id' => $member['id'], 'type' => 'cancel', 'status' => 'pending']);
        $lotCount = count(ShareLot::activeFor((int) $member['id']));
        $pendingMerge = ShareRequest::count(['member_id' => $member['id'], 'type' => 'merge', 'status' => 'pending']) > 0;

        return [
            'cancelableShares' => max(0, $owned - $pendingCancel),
            'canMerge' => $lotCount >= 2 && !$pendingMerge,
            'activeLots' => ShareLot::activeFor((int) $member['id']),
            'mergeBlockReason' => $owned < 1 ? 'none_owned' : ($lotCount < 2 ? 'single_lot' : ($pendingMerge ? 'pending' : null)),
        ];
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $type = (string) $this->input('type');
        $rawCount = (string) $this->input('shares_count', '');

        if (!in_array($type, ['add', 'merge', 'cancel'], true)) {
            Session::flash('error', 'نوع الطلب غير صحيح.');
            $this->redirect('share-requests');
        }

        $allow = $this->allowances($member);

        // A member with no shares can only ask to ADD some; merging/cancelling needs something to work on.
        if ($type === 'merge' && !$allow['canMerge']) {
            $reasons = [
                'none_owned' => 'ليس لديك أسهم لدمجها. يمكنك تقديم طلب إضافة أسهم أولاً.',
                'single_lot' => 'الدمج يحتاج دفعتين أسهم منفصلتين على الأقل، ولديك دفعة واحدة فقط.',
                'pending' => 'لديك طلب دمج قيد المراجعة بالفعل.',
            ];
            Session::flash('error', $reasons[$allow['mergeBlockReason']] ?? 'لا يمكن تقديم طلب دمج الآن.');
            $this->redirect('share-requests');
        }
        if ($type === 'cancel' && $allow['cancelableShares'] < 1) {
            Session::flash('error', (int) $member['shares_count'] < 1
                ? 'ليس لديك أسهم لإلغائها. يمكنك تقديم طلب إضافة أسهم أولاً.'
                : 'أسهمك كلها مشمولة بطلبات إلغاء قيد المراجعة.');
            $this->redirect('share-requests');
        }

        $sharesCount = 0;
        $lotIds = null;
        if ($type !== 'merge') {
            $validator = Validator::make(['shares_count' => $rawCount])
                ->required('shares_count', 'عدد الأسهم')
                ->integer('shares_count', 'عدد الأسهم', 1, 1000);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect('share-requests');
            }
            $sharesCount = (int) $rawCount;

            if ($type === 'cancel' && $sharesCount > $allow['cancelableShares']) {
                Session::flash('error', 'لا يمكن طلب إلغاء أكثر من ' . $allow['cancelableShares'] . ' سهم (أسهمك الحالية بعد خصم طلبات الإلغاء المعلّقة).');
                $this->redirect('share-requests');
            }
        } else {
            // The member picks which lots to merge; at least two of their own active lots are required.
            // input() drops array values, so the checkbox list is read straight from the POST body.
            $picked = is_array($_POST['lot_ids'] ?? null) ? array_filter($_POST['lot_ids'], 'is_scalar') : [];
            $selected = ShareLot::activeAmong((int) $member['id'], array_map('intval', $picked));
            if (count($selected) < 2) {
                Session::flash('error', 'اختر حصتين على الأقل من حصصك لدمجهما.');
                $this->redirect('share-requests');
            }
            $lotIds = implode(',', array_map(fn($l) => (int) $l['id'], $selected));
            $sharesCount = (int) array_sum(array_column($selected, 'shares_count'));
        }

        ShareRequest::create([
            'member_id' => $member['id'],
            'type' => $type,
            'shares_count' => $sharesCount,
            'status' => 'pending',
        ] + ($lotIds !== null ? ['lot_ids' => $lotIds] : []));

        Session::flash('success', 'تم إرسال طلبك للإدارة للمراجعة.');
        $this->redirect('share-requests');
    }
}
