<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Setting;
use App\Models\ContentPage;

class SettingsController extends Controller
{
    public function index(): void
    {
        $pages = ContentPage::all('title ASC');

        $this->view('admin/settings/index', [
            'pageTitle' => __('system_settings'),
            'settings' => Setting::getAll(),
            'pages' => $pages,
        ], 'admin/layout');
    }

    private const TEXT_FIELDS = [
        // Branding
        'site_name', 'site_name_en', 'site_slogan', 'site_slogan_en', 'logo_icon',
        // Contact & Social
        'official_phone', 'official_email', 'official_whatsapp', 'official_address', 'official_address_en',
        'social_twitter', 'social_instagram', 'social_telegram',
        // SEO
        'seo_meta_title', 'seo_meta_title_en', 'seo_meta_description', 'seo_meta_description_en',
        'seo_meta_keywords', 'seo_meta_keywords_en', 'seo_og_image', 'seo_canonical_url',
        'seo_google_analytics', 'seo_custom_header_scripts', 'seo_custom_footer_scripts',
        // Language Controls
        'site_language_mode', 'site_default_language',
        // Financial Rules
        'share_value', 'founding_fee_per_share', 'loan_admin_fee_percent', 'max_loan_ratio', 'subscription_due_day',
        // OTP & SMS Gateway Integration
        'otp_mode', 'otp_resend_seconds', 'otp_length', 'otp_expiry_minutes',
        'sms_provider', 'sms_sender_name', 'sms_api_key', 'sms_app_sid',
        'sms_username', 'sms_password', 'sms_custom_url',
        // Favicon (URL-based fallback; file upload handled separately)
        'site_favicon_url',
    ];

    /** Financial settings drive every calculation in the system: they can never be blank once submitted. */
    private const FINANCIAL_FIELDS = ['share_value', 'founding_fee_per_share', 'loan_admin_fee_percent', 'max_loan_ratio', 'subscription_due_day'];

    public function update(): void
    {
        $this->verifyCsrf();
        $data = $this->all();

        foreach (['official_phone', 'official_whatsapp'] as $phoneField) {
            if (isset($data[$phoneField])) {
                $data[$phoneField] = normalize_mobile($data[$phoneField]);
            }
        }

        // ---- 1. Validate EVERYTHING first: nothing is saved unless every submitted field is valid ----
        $validator = Validator::make($data)
            ->max('site_name', 150, 'اسم الموقع')->max('site_name_en', 150, 'اسم الموقع بالإنجليزية')
            ->max('site_slogan', 255, 'الشعار النصي')->max('site_slogan_en', 255, 'الشعار النصي بالإنجليزية')
            ->regex('logo_icon', '/^[a-z0-9\-]{1,50}$/', 'اسم أيقونة الشعار غير صحيح (حروف إنجليزية صغيرة وأرقام وشرطات فقط).')
            ->mobile('official_phone', 'رقم الهاتف الرسمي')->mobile('official_whatsapp', 'رقم الواتساب')
            ->email('official_email', 'البريد الرسمي')
            ->max('official_address', 255, 'العنوان')->max('official_address_en', 255, 'العنوان بالإنجليزية')
            ->url('social_twitter', 'تويتر/X')->url('social_instagram', 'إنستغرام')->url('social_telegram', 'تيليجرام')
            ->max('seo_meta_title', 200, 'عنوان SEO')->max('seo_meta_title_en', 200, 'عنوان SEO بالإنجليزية')
            ->max('seo_meta_description', 500, 'وصف SEO')->max('seo_meta_description_en', 500, 'وصف SEO بالإنجليزية')
            ->max('seo_meta_keywords', 500, 'كلمات SEO')->max('seo_meta_keywords_en', 500, 'كلمات SEO بالإنجليزية')
            ->url('seo_og_image', 'صورة المشاركة', true)->url('seo_canonical_url', 'الرابط الأساسي')
            ->regex('seo_google_analytics', '/^(G|GT|AW|UA)-[A-Za-z0-9\-]{4,20}$/', 'معرّف Google Analytics غير صحيح (مثال: G-XXXXXXXXXX).')
            ->max('seo_custom_header_scripts', 20000, 'سكربتات الرأس')->max('seo_custom_footer_scripts', 20000, 'سكربتات التذييل')
            ->in('site_language_mode', ['single', 'multi'], 'وضع اللغة')
            ->in('site_default_language', ['ar', 'en'], 'اللغة الافتراضية')
            ->decimal('share_value', 'قيمة السهم', 1, 1000000)
            ->decimal('founding_fee_per_share', 'رسوم التأسيس لكل سهم', 0, 1000000)
            ->decimal('loan_admin_fee_percent', 'نسبة المصاريف الإدارية', 0, 100)
            ->decimal('max_loan_ratio', 'أقصى نسبة للقرض', 1, 100)
            ->integer('subscription_due_day', 'يوم استحقاق الاشتراك', 1, 28)
            ->in('otp_mode', ['demo', 'live', 'disabled'], 'وضع التحقق (OTP)')
            ->integer('otp_resend_seconds', 'مهلة إعادة الإرسال', 10, 300)
            ->in('otp_length', ['4', '6'], 'طول رمز التحقق')
            ->integer('otp_expiry_minutes', 'صلاحية رمز التحقق', 1, 60)
            ->in('sms_provider', ['taqnyat', 'unifonic', '4jawaly', 'msegat', 'twilio', 'custom'], 'مزود خدمة الرسائل SMS')
            ->max('sms_sender_name', 50, 'اسم المرسل')
            ->url('site_logo_url', 'الشعار', true);
        foreach (self::FINANCIAL_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $validator->required($field, 'القيمة المالية (' . $field . ')');
            }
        }

        $logoError = $this->validateLogoUpload();
        if ($logoError !== null) {
            Session::flash('error', $logoError);
            $this->redirect('admin/settings');
        }

        $faviconError = $this->validateFaviconUpload();
        if ($faviconError !== null) {
            Session::flash('error', $faviconError);
            $this->redirect('admin/settings');
        }

        [$menuJson, $menuError] = $this->sanitizeMenu($data['navigation_menu_json'] ?? '');
        if ($menuError !== null) {
            Session::flash('error', $menuError);
            $this->redirect('admin/settings');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/settings');
        }

        // ---- 2. Logo file upload / removal / URL ----
        if (($data['remove_logo'] ?? '') === '1') {
            Setting::set('site_logo', '');
        } elseif (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uploadDir = base_dir() . '/public/uploads/branding';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                Setting::set('site_logo', 'uploads/branding/' . $filename);
            }
        } elseif (($data['site_logo_url'] ?? '') !== '') {
            Setting::set('site_logo', $data['site_logo_url']);
        }

        // ---- 2b. Favicon file upload / removal / URL ----
        if (($data['remove_favicon'] ?? '') === '1') {
            Setting::set('site_favicon', '');
        } elseif (!empty($_FILES['favicon_file']['name']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['favicon_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uploadDir = base_dir() . '/public/uploads/branding';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'favicon_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                Setting::set('site_favicon', 'uploads/branding/' . $filename);
            }
        } elseif (($data['site_favicon_url'] ?? '') !== '') {
            Setting::set('site_favicon', $data['site_favicon_url']);
        }

        // ---- 3. Text & configuration fields ----
        foreach (self::TEXT_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                Setting::set($field, (string) $data[$field]);
            }
        }

        // ---- 4. Homepage section visibility toggles ----
        $toggleFields = [
            'section_hero_enabled',
            'section_stats_enabled',
            'section_features_enabled',
            'section_calculator_enabled',
            'section_charter_enabled',
            'section_hadith_enabled',
            'section_cta_enabled',
        ];
        foreach ($toggleFields as $tf) {
            // Checkbox returns '1' or 'on' when checked, absent when unchecked
            $val = $data[$tf] ?? null;
            Setting::set($tf, ($val === '1' || $val === 'on') ? '1' : '0');
        }

        // ---- 5. Navigation menu ----
        if ($menuJson !== null) {
            Setting::set('navigation_menu_json', $menuJson);
        }

        Session::flash('success', 'تم حفظ جميع الإعدادات والهوية والقوائم بنجاح.');
        $this->redirect('admin/settings');
    }

    /** Returns an error message, or null when there is no upload or the uploaded logo is acceptable. */
    private function validateLogoUpload(): ?string
    {
        $file = $_FILES['logo_file'] ?? null;
        if (!$file || empty($file['name'])) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'فشل رفع ملف الشعار، الرجاء المحاولة مرة أخرى.';
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'ملف الشعار غير صالح.';
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return 'حجم ملف الشعار يجب ألا يزيد عن 2MB.';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeByExt = [
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml', 'text/xml', 'text/plain', 'application/xml'],
        ];
        if (!isset($mimeByExt[$ext])) {
            return 'صيغة ملف الشعار غير مقبولة (المسموح: png, jpg, webp, svg).';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $mimeByExt[$ext], true)) {
            return 'محتوى الملف لا يطابق صيغته، ملف الشعار مرفوض.';
        }

        if ($ext === 'svg') {
            $svg = (string) file_get_contents($file['tmp_name']);
            if (stripos($svg, '<svg') === false
                || preg_match('/<\s*(script|iframe|object|embed|foreignObject)|\son[a-z]+\s*=|javascript:|data:text\/html/i', $svg)) {
                return 'ملف SVG يحتوي على عناصر غير آمنة، ملف الشعار مرفوض.';
            }
        } elseif (@getimagesize($file['tmp_name']) === false) {
            return 'ملف الشعار ليس صورة صالحة.';
        }

        return null;
    }

    /** Returns an error message, or null when there is no upload or the uploaded favicon is acceptable. */
    private function validateFaviconUpload(): ?string
    {
        $file = $_FILES['favicon_file'] ?? null;
        if (!$file || empty($file['name'])) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'فشل رفع ملف الـ Favicon، الرجاء المحاولة مرة أخرى.';
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'ملف الـ Favicon غير صالح.';
        }
        if ($file['size'] > 512 * 1024) {
            return 'حجم ملف الـ Favicon يجب ألا يزيد عن 512 كيلوبايت.';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeByExt = [
            'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream'],
            'png'  => ['image/png'],
            'svg'  => ['image/svg+xml', 'text/xml', 'text/plain', 'application/xml'],
            'webp' => ['image/webp'],
        ];
        if (!isset($mimeByExt[$ext])) {
            return 'صيغة ملف الـ Favicon غير مقبولة (المسموح: ico, png, svg, webp).';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $mimeByExt[$ext], true)) {
            return 'محتوى ملف الـ Favicon لا يطابق صيغته، الملف مرفوض.';
        }

        if ($ext === 'svg') {
            $svg = (string) file_get_contents($file['tmp_name']);
            if (stripos($svg, '<svg') === false
                || preg_match('/<\s*(script|iframe|object|embed|foreignObject)|\son[a-z]+\s*=|javascript:|data:text\/html/i', $svg)) {
                return 'ملف SVG الـ Favicon يحتوي على عناصر غير آمنة، الملف مرفوض.';
            }
        } elseif ($ext !== 'ico' && @getimagesize($file['tmp_name']) === false) {
            return 'ملف الـ Favicon ليس صورة صالحة.';
        }

        return null;
    }

    /**
     * Validates and rebuilds the navigation menu keeping only known keys.
     * Returns [json|null, error|null]; json is null when nothing was submitted.
     */
    private function sanitizeMenu(string $raw): array
    {
        if ($raw === '') {
            return [null, null];
        }
        $error = 'صيغة قائمة التنقل (JSON) غير صحيحة، لم يتم حفظ أي تغيير.';
        if (strlen($raw) > 50000) {
            return [null, $error];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded) || count($decoded) > 30) {
            return [null, $error];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                return [null, $error];
            }
            $titleAr = is_string($item['title_ar'] ?? null) ? trim($item['title_ar']) : '';
            $titleEn = is_string($item['title_en'] ?? null) ? trim($item['title_en']) : '';
            $url = is_string($item['url'] ?? null) ? trim($item['url']) : '';
            $target = $item['target'] ?? '_self';

            $urlOk = preg_match('#^(https?://[^\s<>"\']+|/[^\s<>"\':]*|\#[A-Za-z0-9_\-]*|[A-Za-z0-9_\-./?=&\#%]+)$#', $url)
                && !preg_match('/^\s*(javascript|data|vbscript):/i', $url);
            if ($titleAr === '' || mb_strlen($titleAr) > 100 || mb_strlen($titleEn) > 100 || $url === '' || mb_strlen($url) > 255
                || !$urlOk || !in_array($target, ['_self', '_blank'], true)) {
                return [null, 'عنصر في قائمة التنقل غير صحيح (العنوان أو الرابط أو الهدف).'];
            }

            $items[] = [
                'id' => preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($item['id'] ?? '')) ?: ('item' . (count($items) + 1)),
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'url' => $url,
                'target' => $target,
                'enabled' => !empty($item['enabled']),
            ];
        }

        return [json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), null];
    }

    public function testSms(): void
    {
        $this->verifyCsrf();
        $mobile = normalize_mobile((string) $this->input('test_mobile'));
        if (empty($mobile)) {
            $this->json(['success' => false, 'message' => 'الرجاء إدخال رقم جوال صحيح للاختبار (مثال: 05xxxxxxxx).']);
            return;
        }

        $code = generate_otp(otp_length());
        $msg = "رسالة تجريبية لاختبار بوابة الرسائل في " . site_name() . " - رمز الاختبار: " . $code;
        $result = \App\Core\SmsService::send($mobile, $msg);
        $this->json($result);
    }
}
