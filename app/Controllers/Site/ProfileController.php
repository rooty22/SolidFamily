<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Member;

class ProfileController extends Controller
{
    public function index(): void
    {
        $member = Member::find(Auth::member()['id']);
        $this->view('site/profile/index', ['pageTitle' => __('profile'), 'member' => $member], 'site/layout');
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $member = Member::find(Auth::member()['id']);
        $data = Member::normalize($this->all());

        $validator = Member::validate($data, [
            'mobile', 'email', 'national_address', 'bank_account_number', 'iban', 'bank_name',
        ], (int) $member['id']);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('profile');
        }

        $mobileChanged = $data['mobile'] !== $member['mobile'];

        Member::update($member['id'], [
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'national_address' => ($data['national_address'] ?? '') ?: null,
            'bank_account_number' => ($data['bank_account_number'] ?? '') ?: null,
            'iban' => ($data['iban'] ?? '') ?: null,
            'bank_name' => ($data['bank_name'] ?? '') ?: null,
        ]);

        if ($mobileChanged) {
            Auth::logoutMember();
            Session::flash('success', 'تم تحديث رقم الجوال، الرجاء تسجيل الدخول مجدداً.');
            $this->redirect('login');
        }

        Session::flash('success', 'تم تحديث بياناتك بنجاح.');
        $this->redirect('profile');
    }

    public function updatePassword(): void
    {
        $this->verifyCsrf();
        $member = Member::find(Auth::member()['id']);
        $current = (string) $this->input('current_password');
        $new = (string) $this->input('new_password');
        $confirm = (string) $this->input('new_password_confirmation');

        if (!password_verify($current, $member['password'])) {
            Session::flash('error', 'كلمة المرور الحالية غير صحيحة.');
            $this->redirect('profile');
        }

        if (mb_strlen($new) < 8 || mb_strlen($new) > 72 || $new !== $confirm) {
            Session::flash('error', 'كلمة المرور الجديدة يجب أن تكون بين 8 و72 حرفاً ومتطابقة مع التأكيد.');
            $this->redirect('profile');
        }

        if ($new === $current) {
            Session::flash('error', 'كلمة المرور الجديدة يجب أن تختلف عن الحالية.');
            $this->redirect('profile');
        }

        Member::update($member['id'], ['password' => password_hash($new, PASSWORD_DEFAULT)]);
        Session::flash('success', 'تم تغيير كلمة المرور بنجاح.');
        $this->redirect('profile');
    }
}
