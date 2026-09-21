<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ContactMessage;
use App\Models\Notification;

class MessagesController extends Controller
{
    public function index(): void
    {
        $messages = ContactMessage::all('created_at DESC');
        $this->view('admin/messages/index', [
            'pageTitle' => __('messages_title'),
            'messages' => $messages,
        ], 'admin/layout');
    }

    public function show(string $id): void
    {
        $message = ContactMessage::find((int) $id);
        if (!$message) {
            $this->redirect('admin/messages');
        }
        if ($message['status'] === 'new') {
            ContactMessage::update((int) $id, ['status' => 'read']);
            $message['status'] = 'read';
        }

        $this->view('admin/messages/show', [
            'pageTitle' => __('message_details'),
            'message' => $message,
        ], 'admin/layout');
    }

    /** Store the admin's reply; when the sender is a registered member it also reaches them as a notification. */
    public function reply(string $id): void
    {
        $this->verifyCsrf();
        $message = ContactMessage::find((int) $id);
        if (!$message) {
            $this->redirect('admin/messages');
        }

        $reply = (string) $this->input('reply', '');
        $validator = Validator::make(['reply' => $reply])
            ->required('reply', 'الرد')->min('reply', 2, 'الرد')->max('reply', 2000, 'الرد');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/messages/' . $id);
        }

        ContactMessage::update((int) $id, [
            'reply_text' => $reply,
            'replied_at' => date('Y-m-d H:i:s'),
            'replied_by' => current_admin_id(),
            'status' => 'read',
        ]);

        if (!empty($message['member_id'])) {
            Notification::systemNotify((int) $message['member_id'], 'رد الإدارة على رسالتك', $reply);
            Session::flash('success', 'تم حفظ الرد وإرساله للمشترك كإشعار داخل حسابه.');
        } else {
            Session::flash('success', 'تم حفظ الرد. المرسل ليس مشتركاً مسجلاً، تواصل معه عبر الهاتف.');
        }

        $this->redirect('admin/messages/' . $id);
    }
}
