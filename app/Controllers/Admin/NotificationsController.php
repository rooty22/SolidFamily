<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Notification;

class NotificationsController extends Controller
{
    public function index(): void
    {
        $filters = [
            'from' => $this->input('from', ''),
            'to' => $this->input('to', ''),
            'target_type' => $this->input('target_type', ''),
            'status' => $this->input('status', ''),
        ];

        $notifications = Notification::withTargetName($filters);

        $this->view('admin/notifications/index', [
            'pageTitle' => __('notifications'),
            'notifications' => $notifications,
            'filters' => $filters,
        ], 'admin/layout');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->all();
        $title = $data['title'] ?? '';
        $body = $data['body'] ?? '';
        $targetType = $data['target_type'] ?? 'all';
        $mobile = normalize_mobile($data['mobile'] ?? '');

        $validator = Validator::make(['title' => $title, 'body' => $body, 'target_type' => $targetType, 'mobile' => $mobile])
            ->required('title', 'عنوان الإشعار')->max('title', 200, 'عنوان الإشعار')
            ->required('body', 'نص الإشعار')->max('body', 2000, 'نص الإشعار')
            ->required('target_type', 'نوع المستهدف')->in('target_type', ['all', 'specific'], 'نوع المستهدف');
        if ($targetType === 'specific') {
            $validator->required('mobile', 'رقم الجوال')->mobile('mobile', 'رقم الجوال');
        }
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/notifications');
        }

        $targetMemberId = null;
        $status = 'sent';

        if ($targetType === 'specific') {
            $member = Member::findBy('mobile', $mobile);
            if (!$member) {
                $status = 'failed';
            } else {
                $targetMemberId = $member['id'];
            }
        }

        Notification::create([
            'title' => $title,
            'body' => $body,
            'target_type' => $targetType,
            'target_member_id' => $targetMemberId,
            'category' => 'manual',
            'status' => $status,
            'created_by' => current_admin_id(),
        ]);

        Session::flash($status === 'sent' ? 'success' : 'error', $status === 'sent' ? 'تم إرسال الإشعار بنجاح.' : 'فشل الإرسال: رقم الجوال غير مسجل.');
        $this->redirect('admin/notifications');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        Notification::delete((int) $id);
        Session::flash('success', 'تم حذف الإشعار.');
        $this->redirect('admin/notifications');
    }
}
