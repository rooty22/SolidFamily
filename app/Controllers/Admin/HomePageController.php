<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ContentPage;
use App\Models\HomePage;
use App\Models\Setting;

/** Home page builder: sections, their texts (AR/EN), images, order and visibility. */
class HomePageController extends Controller
{
    private const MAX_JSON_BYTES = 1000000;
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public function edit(): void
    {
        $this->view('admin/content/home', [
            'pageTitle' => is_rtl() ? 'تعديل الصفحة الرئيسية' : 'Edit Home Page',
            'sections' => HomePage::sections(),
            'pages' => ContentPage::all('title ASC'),
        ], 'admin/layout');
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $raw = (string) ($_POST['sections_json'] ?? '');
        $decoded = strlen($raw) <= self::MAX_JSON_BYTES ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            Session::flash('error', 'بيانات أقسام الصفحة الرئيسية غير صحيحة، لم يتم حفظ أي تغيير.');
            $this->redirect('admin/content/home');
        }

        Setting::set(HomePage::SETTING, json_encode(HomePage::sanitize($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Session::flash('success', 'تم حفظ الصفحة الرئيسية بنجاح.');
        $this->redirect('admin/content/home');
    }

    /** Puts back the original sections and texts. */
    public function reset(): void
    {
        $this->verifyCsrf();
        Setting::set(HomePage::SETTING, '');
        Session::flash('success', 'تمت استعادة الصفحة الرئيسية الافتراضية.');
        $this->redirect('admin/content/home');
    }

    /** Image upload from the builder (AJAX): answers {success, path, url} or {success: false, message}. */
    public function upload(): void
    {
        $this->verifyCsrf();
        $file = $_FILES['image'] ?? null;
        $error = $this->validateImage($file);
        if ($error !== null) {
            $this->json(['success' => false, 'message' => $error], 422);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $uploadDir = base_dir() . '/public/uploads/home';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = 'home_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            $this->json(['success' => false, 'message' => 'تعذر حفظ الصورة على الخادم.'], 500);
        }

        $path = 'uploads/home/' . $filename;
        $this->json(['success' => true, 'path' => $path, 'url' => url($path)]);
    }

    /** Returns an error message, or null when the uploaded file is an acceptable raster image. */
    private function validateImage(?array $file): ?string
    {
        if (!$file || empty($file['name'])) {
            return 'لم يتم اختيار صورة.';
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'فشل رفع الصورة، الرجاء المحاولة مرة أخرى.';
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'ملف الصورة غير صالح.';
        }
        if ($file['size'] > self::MAX_IMAGE_BYTES) {
            return 'حجم الصورة يجب ألا يزيد عن 5MB.';
        }

        $mimeByExt = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($mimeByExt[$ext])) {
            return 'صيغة الصورة غير مقبولة (المسموح: png, jpg, webp, gif).';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mime !== $mimeByExt[$ext] || @getimagesize($file['tmp_name']) === false) {
            return 'الملف ليس صورة صالحة أو لا يطابق صيغته.';
        }
        return null;
    }
}
