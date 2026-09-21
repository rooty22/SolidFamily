<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ShareRequest;

class ShareRequestsController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        $requests = ShareRequest::where(['member_id' => $member['id']], 'created_at DESC');

        $this->view('site/share_requests/index', [
            'pageTitle' => __('share_requests'),
            'requests' => $requests,
            'typeLabels' => share_request_types(),
            'member' => $member,
        ], 'site/layout');
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

        $sharesCount = 0;
        if ($type !== 'merge') {
            $validator = Validator::make(['shares_count' => $rawCount])
                ->required('shares_count', 'عدد الأسهم')
                ->integer('shares_count', 'عدد الأسهم', 1, 1000);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
                $this->redirect('share-requests');
            }
            $sharesCount = (int) $rawCount;

            if ($type === 'cancel' && $sharesCount > (int) $member['shares_count']) {
                Session::flash('error', 'لا يمكن طلب إلغاء عدد أسهم أكبر من أسهمك الحالية (' . (int) $member['shares_count'] . ').');
                $this->redirect('share-requests');
            }
        }

        ShareRequest::create([
            'member_id' => $member['id'],
            'type' => $type,
            'shares_count' => $type === 'merge' ? null : $sharesCount,
            'status' => 'pending',
        ]);

        Session::flash('success', 'تم إرسال طلبك للإدارة للمراجعة.');
        $this->redirect('share-requests');
    }
}
