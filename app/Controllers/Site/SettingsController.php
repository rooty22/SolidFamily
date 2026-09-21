<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(): void
    {
        $pages = [
            'about' => ContentPage::bySlug('about'),
            'terms' => ContentPage::bySlug('terms'),
            'privacy' => ContentPage::bySlug('privacy'),
        ];

        $this->view('site/settings/index', [
            'pageTitle' => __('settings_support'),
            'pages' => $pages,
            'officialPhone' => Setting::get('official_phone', ''),
            'officialEmail' => Setting::get('official_email', ''),
            'currentLang' => Session::get('lang', 'ar'),
        ], 'site/layout');
    }

    public function sendSupportMessage(): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $message = (string) $this->input('message', '');

        $validator = Validator::make(['message' => $message])
            ->required('message', 'الرسالة')
            ->min('message', 5, 'الرسالة')
            ->max('message', 2000, 'الرسالة');
        if ($validator->fails() || RateLimiter::tooMany('support:' . $member['id'], 5, 3600)) {
            Session::flash('error', $validator->fails() ? $validator->firstError() : 'تم إرسال عدد كبير من الرسائل، الرجاء المحاولة لاحقاً.');
            $this->redirect('settings');
        }
        RateLimiter::hit('support:' . $member['id']);

        ContactMessage::create([
            'member_id' => $member['id'],
            'name' => $member['name'],
            'phone' => $member['mobile'],
            'message' => $message,
            'status' => 'new',
        ]);

        Session::flash('success', 'تم إرسال رسالتك للدعم الفني بنجاح.');
        $this->redirect('settings');
    }

    public function setLanguage(): void
    {
        $this->verifyCsrf();
        $lang = $this->input('lang', 'ar') === 'en' ? 'en' : 'ar';
        \App\Core\Lang::setLocale($lang);
        Session::flash('success', $lang === 'ar' ? 'تم اختيار اللغة العربية.' : 'Language set to English.');
        $this->redirect('settings');
    }
}
