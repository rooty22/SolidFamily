<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Admin;
use App\Models\OtpCode;

class AuthController extends Controller
{
    private const OTP_THROTTLED = 'تم تجاوز الحد المسموح من طلبات/محاولات رمز التحقق، الرجاء المحاولة بعد قليل.';

    public function showLogin(): void
    {
        $this->view('admin/auth/login', ['pageTitle' => __('login')], 'admin/auth-layout');
    }

    public function login(): void
    {
        $this->verifyCsrf();
        $email = mb_strtolower((string) $this->input('email'));
        $password = (string) $this->input('password');

        if (RateLimiter::loginBlocked('admin', $email)) {
            Session::flash('error', 'تم تجاوز عدد محاولات تسجيل الدخول، الرجاء المحاولة بعد 15 دقيقة.');
            $this->redirect('admin/login');
        }

        $admin = Admin::findBy('email', $email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            RateLimiter::loginFailed('admin', $email);
            Session::flash('error', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
            Session::setOld(['email' => $email]);
            $this->redirect('admin/login');
        }

        RateLimiter::loginSucceeded('admin', $email);
        Auth::loginAdmin($admin);
        $this->redirect('admin/dashboard');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logoutAdmin();
        Session::regenerate();
        $this->redirect('admin/login');
    }

    public function showForgot(): void
    {
        $this->view('admin/auth/forgot', ['pageTitle' => __('forgot_password')], 'admin/auth-layout');
    }

    public function sendOtp(): void
    {
        $this->verifyCsrf();
        $email = mb_strtolower((string) $this->input('email'));

        $validator = Validator::make(['email' => $email])->required('email', 'البريد الإلكتروني')->email('email', 'البريد الإلكتروني');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/forgot-password');
        }

        $admin = Admin::findBy('email', $email);

        if (!$admin) {
            Session::flash('error', 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني.');
            $this->redirect('admin/forgot-password');
        }

        $code = OtpCode::generate($email, 'admin_reset');
        if ($code === null) {
            Session::flash('error', self::OTP_THROTTLED);
            $this->redirect('admin/forgot-password');
        }

        Session::set('admin_reset_email', $email);
        Session::flash('success', otp_notice('تم إرسال رمز التحقق.', $code));
        $this->redirect('admin/forgot-password/verify');
    }

    public function showVerify(): void
    {
        if (!Session::get('admin_reset_email')) {
            $this->redirect('admin/forgot-password');
        }
        $this->view('admin/auth/verify', [
            'pageTitle' => __('verify_code_title'),
            'email' => Session::get('admin_reset_email'),
        ], 'admin/auth-layout');
    }

    public function verifyOtp(): void
    {
        $this->verifyCsrf();
        $email = Session::get('admin_reset_email');
        $code = (string) $this->input('code');

        if (!$email) {
            $this->redirect('admin/forgot-password');
        }

        $otp = OtpCode::isWellFormed($code) ? OtpCode::verify($email, 'admin_reset', $code) : null;
        if (!$otp) {
            Session::flash('error', OtpCode::isLocked($email, 'admin_reset')
                ? self::OTP_THROTTLED
                : 'رمز التحقق غير صحيح أو منتهي الصلاحية.');
            $this->redirect('admin/forgot-password/verify');
        }

        Session::set('admin_reset_verified', true);
        $this->redirect('admin/forgot-password/reset');
    }

    public function showReset(): void
    {
        if (!Session::get('admin_reset_verified')) {
            $this->redirect('admin/forgot-password');
        }
        $this->view('admin/auth/reset', ['pageTitle' => __('new_password_title')], 'admin/auth-layout');
    }

    public function reset(): void
    {
        $this->verifyCsrf();
        $email = Session::get('admin_reset_email');
        if (!$email || !Session::get('admin_reset_verified')) {
            $this->redirect('admin/forgot-password');
        }

        $data = $this->all();
        $validator = Validator::make($data)
            ->required('password', 'كلمة المرور')
            ->min('password', 8, 'كلمة المرور')
            ->max('password', 72, 'كلمة المرور')
            ->required('password_confirmation', 'تأكيد كلمة المرور')
            ->matches('password_confirmation', 'password', 'تأكيد كلمة المرور');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/forgot-password/reset');
        }

        $admin = Admin::findBy('email', $email);
        if (!$admin) {
            $this->redirect('admin/forgot-password');
        }
        Admin::update($admin['id'], ['password' => password_hash($data['password'], PASSWORD_DEFAULT)]);

        Session::remove('admin_reset_email');
        Session::remove('admin_reset_verified');
        Session::flash('success', 'تم تحديث كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.');
        $this->redirect('admin/login');
    }
}
