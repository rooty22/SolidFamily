<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\OtpCode;
use App\Models\FoundingAmount;

class AuthController extends Controller
{
    private const OTP_THROTTLED = 'تم تجاوز الحد المسموح من طلبات/محاولات رمز التحقق، الرجاء المحاولة بعد قليل.';

    private const PENDING_TTL = 1800; // seconds a started registration may wait for its OTP

    /** The registration waiting for OTP confirmation, or null when there is none / it expired. */
    private function pendingRegistration(): ?array
    {
        $pending = Session::get('pending_registration');
        if ($pending && (time() - (int) Session::get('pending_registration_at', 0)) > self::PENDING_TTL) {
            Session::remove('pending_registration');
            Session::remove('pending_registration_at');
            return null;
        }
        return $pending ?: null;
    }

    public function showRegister(): void
    {
        $this->view('site/auth/register', ['pageTitle' => __('register')], 'site/auth-layout');
    }

    public function register(): void
    {
        $this->verifyCsrf();
        $data = Member::normalize($this->all());

        $validator = Member::validate($data, [
            'name', 'mobile', 'email', 'national_id', 'birth_date', 'national_address',
            'bank_account_number', 'iban', 'bank_name', 'password',
        ], null, true, true)->required('password_confirmation', 'تأكيد كلمة المرور')->matches('password_confirmation', 'password', 'تأكيد كلمة المرور');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::setOld($data);
            $this->redirect('register');
        }

        $pending = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'birth_date' => ($data['birth_date'] ?? '') ?: null,
            'national_address' => ($data['national_address'] ?? '') ?: null,
            'national_id' => $data['national_id'],
            'bank_account_number' => ($data['bank_account_number'] ?? '') ?: null,
            'iban' => ($data['iban'] ?? '') ?: null,
            'bank_name' => ($data['bank_name'] ?? '') ?: null,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ];

        if (otp_is_disabled()) {
            $memberId = $this->createMember($pending);
            Auth::loginMember(Member::find($memberId));
            Session::flash('success', 'تم إنشاء حسابك بنجاح.');
            $this->redirect('home');
        }

        $code = OtpCode::generate($pending['mobile'], 'member_register');
        if ($code === null) {
            Session::flash('error', self::OTP_THROTTLED);
            Session::setOld($data);
            $this->redirect('register');
        }

        Session::set('pending_registration', $pending);
        Session::set('pending_registration_at', time());
        Session::flash('success', otp_notice('تم إرسال رمز التحقق إلى جوالك.', $code));
        $this->redirect('register/verify');
    }

    public function showVerifyRegister(): void
    {
        $pending = $this->pendingRegistration();
        if (!$pending) {
            $this->redirect('register');
        }
        $this->view('site/auth/verify-register', [
            'pageTitle' => __('otp_verification'),
            'mobile' => $pending['mobile'],
        ], 'site/auth-layout');
    }

    public function verifyRegister(): void
    {
        $this->verifyCsrf();
        $pending = $this->pendingRegistration();
        if (!$pending) {
            $this->redirect('register');
        }

        $code = (string) $this->input('code');
        $otp = OtpCode::isWellFormed($code) ? OtpCode::verify($pending['mobile'], 'member_register', $code) : null;

        if (!$otp) {
            Session::flash('error', OtpCode::isLocked($pending['mobile'], 'member_register')
                ? self::OTP_THROTTLED
                : 'رمز التحقق غير صحيح أو منتهي الصلاحية.');
            $this->redirect('register/verify');
        }

        // The uniqueness checks ran when the form was submitted; someone else may have taken the values since.
        $recheck = Member::validate($pending, ['mobile', 'email', 'national_id']);
        if ($recheck->fails()) {
            Session::remove('pending_registration');
            Session::flash('error', $recheck->firstError());
            $this->redirect('register');
        }

        $memberId = $this->createMember($pending);

        Session::remove('pending_registration');
        Auth::loginMember(Member::find($memberId));

        Session::flash('success', 'تم إنشاء حسابك بنجاح.');
        $this->redirect('home');
    }

    private function createMember(array $pending): int
    {
        $memberId = Member::create(array_merge($pending, ['shares_count' => 0, 'status' => 'active']));
        FoundingAmount::ensureForMember($memberId);
        return $memberId;
    }

    public function resendRegisterOtp(): void
    {
        $this->verifyCsrf();
        $pending = $this->pendingRegistration();
        if ($pending) {
            $code = OtpCode::generate($pending['mobile'], 'member_register');
            Session::flash($code === null ? 'error' : 'success', $code === null
                ? self::OTP_THROTTLED
                : otp_notice('تم إرسال رمز جديد.', $code));
        }
        $this->redirect('register/verify');
    }

    public function showLogin(): void
    {
        $this->view('site/auth/login', ['pageTitle' => __('login')], 'site/auth-layout');
    }

    public function login(): void
    {
        $this->verifyCsrf();
        $nationalId = (string) $this->input('national_id');
        $password = (string) $this->input('password');

        if (RateLimiter::loginBlocked('member', $nationalId)) {
            Session::flash('error', 'تم تجاوز عدد محاولات تسجيل الدخول، الرجاء المحاولة بعد 15 دقيقة.');
            $this->redirect('login');
        }

        $member = Member::findBy('national_id', $nationalId);

        if (!$member || !password_verify($password, $member['password'])) {
            RateLimiter::loginFailed('member', $nationalId);
            Session::flash('error', 'رقم الهوية أو كلمة المرور غير صحيحة.');
            Session::setOld(['national_id' => $nationalId]);
            $this->redirect('login');
        }

        RateLimiter::loginSucceeded('member', $nationalId);

        if ($member['status'] !== 'active') {
            Session::flash('error', 'تم إيقاف هذا الحساب، الرجاء التواصل مع الإدارة.');
            $this->redirect('login');
        }

        Auth::loginMember($member);
        $this->redirect('home');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logoutMember();
        Session::regenerate();
        $this->redirect('login');
    }

    public function showForgot(): void
    {
        $this->view('site/auth/forgot', ['pageTitle' => __('forgot_password')], 'site/auth-layout');
    }

    public function sendOtp(): void
    {
        $this->verifyCsrf();
        $mobile = normalize_mobile((string) $this->input('mobile'));

        $validator = Validator::make(['mobile' => $mobile])->required('mobile', 'رقم الجوال')->mobile('mobile', 'رقم الجوال');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('forgot-password');
        }

        $member = Member::findBy('mobile', $mobile);

        if (!$member) {
            Session::flash('error', 'لا يوجد حساب مرتبط بهذا الرقم.');
            $this->redirect('forgot-password');
        }

        Session::set('reset_mobile', $mobile);

        if (otp_is_disabled()) {
            Session::set('reset_verified', true);
            $this->redirect('forgot-password/reset');
        }

        $code = OtpCode::generate($mobile, 'member_reset');
        if ($code === null) {
            Session::flash('error', self::OTP_THROTTLED);
            $this->redirect('forgot-password');
        }

        Session::flash('success', otp_notice('تم إرسال رمز التحقق.', $code));
        $this->redirect('forgot-password/verify');
    }

    public function showVerify(): void
    {
        if (!Session::get('reset_mobile')) {
            $this->redirect('forgot-password');
        }
        $this->view('site/auth/verify', ['pageTitle' => __('otp_verification'), 'mobile' => Session::get('reset_mobile')], 'site/auth-layout');
    }

    public function verifyOtp(): void
    {
        $this->verifyCsrf();
        $mobile = Session::get('reset_mobile');
        if (!$mobile) {
            $this->redirect('forgot-password');
        }

        $code = (string) $this->input('code');
        $otp = OtpCode::isWellFormed($code) ? OtpCode::verify($mobile, 'member_reset', $code) : null;

        if (!$otp) {
            Session::flash('error', OtpCode::isLocked($mobile, 'member_reset')
                ? self::OTP_THROTTLED
                : 'رمز التحقق غير صحيح أو منتهي الصلاحية.');
            $this->redirect('forgot-password/verify');
        }

        Session::set('reset_verified', true);
        $this->redirect('forgot-password/reset');
    }

    public function showReset(): void
    {
        if (!Session::get('reset_verified')) {
            $this->redirect('forgot-password');
        }
        $this->view('site/auth/reset', ['pageTitle' => __('reset_password')], 'site/auth-layout');
    }

    public function reset(): void
    {
        $this->verifyCsrf();
        $mobile = Session::get('reset_mobile');
        if (!$mobile || !Session::get('reset_verified')) {
            $this->redirect('forgot-password');
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
            $this->redirect('forgot-password/reset');
        }

        $member = Member::findBy('mobile', $mobile);
        if (!$member) {
            $this->redirect('forgot-password');
        }
        Member::update($member['id'], ['password' => password_hash($data['password'], PASSWORD_DEFAULT)]);

        Session::remove('reset_mobile');
        Session::remove('reset_verified');
        Session::flash('success', 'تم تحديث كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.');
        $this->redirect('login');
    }
}
