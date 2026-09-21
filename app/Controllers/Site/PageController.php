<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ContentPage;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Loan;

class PageController extends Controller
{
    public function landing(): void
    {
        $shareValue = 0.0;
        $foundingFee = 0.0;
        $maxLoanRatio = 10.0;
        $totalMembers = 0;
        $totalShares = 0;
        $totalLoansCount = 0;

        try {
            $shareValue = (float) Setting::get('share_value', 0);
            $foundingFee = (float) Setting::get('founding_fee_per_share', 0);
            $maxLoanRatio = (float) Setting::get('max_loan_ratio', 10);

            $totalMembers = (int) Member::count(['status' => 'active']);
            $totalShares = (int) Member::sum('shares_count', ['status' => 'active']);
            $totalLoansCount = (int) Loan::count();
        } catch (\Throwable $e) {
            // Database not migrated yet: show zeros rather than invented numbers.
        }

        $fundCapital = $totalShares * $shareValue;

        $this->view('site/pages/landing', [
            'pageTitle' => 'صندوق العائلة التكافلي - البوابة الرسمية',
            'shareValue' => $shareValue,
            'foundingShareRatio' => $foundingFee, // founding fee per share (used by the calculator)
            'maxLoanRatio' => $maxLoanRatio,
            'totalMembers' => $totalMembers,
            'totalShares' => $totalShares,
            'totalLoansCount' => $totalLoansCount,
            'fundCapital' => $fundCapital,
            'isLoggedIn' => Auth::memberCheck(),
            'currentMember' => Auth::member(),
        ], 'site/landing-layout');
    }

    public function show(string $slug): void
    {
        $page = ContentPage::bySlug($slug);
        if (!$page) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        $title = ContentPage::getTitle($page);
        $formConfig = ContentPage::getFormConfig($page);

        $this->view('site/pages/show', [
            'pageTitle' => $title,
            'page' => $page,
            'formConfig' => $formConfig,
            'isLoggedIn' => Auth::memberCheck(),
            'currentMember' => Auth::member(),
        ], 'site/landing-layout');
    }

    public function submitForm(string $slug): void
    {
        $this->verifyCsrf();
        $page = ContentPage::bySlug($slug);
        if (!$page || !ContentPage::isFormEnabled($page)) {
            $this->redirect('page/' . $slug);
            return;
        }

        $formConfig = ContentPage::getFormConfig($page);
        $member = Auth::member();

        $name = (string) $this->input('name', $member['name'] ?? '');
        $phone = normalize_mobile((string) $this->input('phone', $member['mobile'] ?? ''));
        $email = mb_strtolower((string) $this->input('email', $member['email'] ?? ''));
        $subject = (string) $this->input('subject', '');
        $messageText = (string) $this->input('message', '');

        $validator = Validator::make([
            'name' => $name, 'phone' => $phone, 'email' => $email, 'subject' => $subject, 'message' => $messageText,
        ])
            ->required('name', is_en() ? 'Name' : 'الاسم')->personName('name', is_en() ? 'Name' : 'الاسم')->max('name', 150, is_en() ? 'Name' : 'الاسم')
            ->required('phone', is_en() ? 'Phone' : 'رقم الجوال')->mobile('phone', is_en() ? 'Phone' : 'رقم الجوال')
            ->email('email', is_en() ? 'Email' : 'البريد الإلكتروني')
            ->max('subject', 200, is_en() ? 'Subject' : 'الموضوع')
            ->required('message', is_en() ? 'Message' : 'الرسالة')->min('message', 5, is_en() ? 'Message' : 'الرسالة')->max('message', 3000, is_en() ? 'Message' : 'الرسالة');
        if (!empty($formConfig['require_email'])) {
            $validator->required('email', is_en() ? 'Email' : 'البريد الإلكتروني');
        }
        if (!empty($formConfig['require_subject'])) {
            $validator->required('subject', is_en() ? 'Subject' : 'الموضوع');
        }

        $throttleKey = 'page_form:' . client_ip();
        if (RateLimiter::tooMany($throttleKey, 5, 3600)) {
            Session::flash('error', is_en() ? 'Too many messages, please try again later.' : 'تم إرسال عدد كبير من الرسائل، الرجاء المحاولة لاحقاً.');
            $this->redirect('page/' . $slug . '#form');
            return;
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('page/' . $slug . '#form');
            return;
        }
        RateLimiter::hit($throttleKey);

        // Build full message payload
        $fullMessage = "مصدر الرسالة: صفحة (" . ContentPage::getTitle($page) . ")\n";
        if ($subject !== '') {
            $fullMessage .= "الموضوع: " . $subject . "\n";
        }
        if ($email !== '') {
            $fullMessage .= "البريد الإلكتروني: " . $email . "\n";
        }
        $fullMessage .= "------------------------\n" . $messageText;

        \App\Models\ContactMessage::create([
            'member_id' => $member['id'] ?? null,
            'name' => $name,
            'phone' => $phone ?: ($member['mobile'] ?? null),
            'message' => $fullMessage,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $successMsg = is_en() 
            ? (!empty($formConfig['success_msg_en']) ? $formConfig['success_msg_en'] : 'Your message has been sent successfully.')
            : (!empty($formConfig['success_msg_ar']) ? $formConfig['success_msg_ar'] : 'تم إرسال رسالتكم بنجاح وسيتواصل معكم فريق الصندوق.');

        Session::flash('success', $successMsg);
        $this->redirect('page/' . $slug . '#form');
    }
}
