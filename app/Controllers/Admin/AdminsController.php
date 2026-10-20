<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Admin;
use App\Models\Member;

/**
 * Sub-admin management (main admins only, enforced by Middleware::adminAuth for every /admin/admins page).
 * A sub-admin has a photo, name, email, password, an active / suspended status and per-section permissions.
 */
class AdminsController extends Controller
{
    private const AVATAR_DIR = 'uploads/admins';

    public function index(): void
    {
        $admins = Admin::all('is_super DESC, created_at ASC');
        $this->view('admin/admins/index', [
            'pageTitle' => is_rtl() ? 'إدارة المشرفين الفرعيين' : 'Sub-admins',
            'admins' => $admins,
            'currentId' => (int) Auth::admin()['id'],
        ], 'admin/layout');
    }

    public function create(): void
    {
        $this->view('admin/admins/form', [
            'pageTitle' => is_rtl() ? 'إضافة مشرف فرعي' : 'Add sub-admin',
            'admin' => null,
        ], 'admin/layout');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->all();
        $name = trim((string) ($data['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        $error = $this->validateFields($name, $email, $password, true, null)
            ?? $this->validateAvatarUpload();
        if ($error !== null) {
            Session::flash('error', $error);
            Session::setOld(['name' => $name, 'email' => $email, 'status' => $status]);
            $this->redirect('admin/admins/create');
        }

        $id = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $status,
            'is_super' => 0,
            'permissions' => Admin::encodePermissions($this->postedPermissions()),
        ]);
        $avatar = $this->saveAvatar();
        if ($avatar !== null) {
            Admin::update($id, ['avatar' => $avatar]);
        }

        Session::flash('success', 'تمت إضافة المشرف الفرعي بنجاح.');
        $this->redirect('admin/admins');
    }

    public function edit(string $id): void
    {
        $admin = $this->findSubAdmin((int) $id);
        $this->view('admin/admins/form', [
            'pageTitle' => is_rtl() ? 'تعديل مشرف فرعي' : 'Edit sub-admin',
            'admin' => $admin,
        ], 'admin/layout');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $admin = $this->findSubAdmin((int) $id);

        $data = $this->all();
        $name = trim((string) ($data['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        $error = $this->validateFields($name, $email, $password, false, (int) $admin['id'])
            ?? $this->validateAvatarUpload();
        if ($error !== null) {
            Session::flash('error', $error);
            $this->redirect('admin/admins/' . (int) $admin['id'] . '/edit');
        }

        $update = [
            'name' => $name,
            'email' => $email,
            'status' => $status,
            'permissions' => Admin::encodePermissions($this->postedPermissions()),
        ];
        if ($password !== '') {
            $update['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if (($data['remove_avatar'] ?? '') === '1') {
            $this->deleteAvatarFile($admin['avatar'] ?? null);
            $update['avatar'] = null;
        }
        $avatar = $this->saveAvatar();
        if ($avatar !== null) {
            $this->deleteAvatarFile($admin['avatar'] ?? null);
            $update['avatar'] = $avatar;
        }
        Admin::update((int) $admin['id'], $update);

        Session::flash('success', 'تم حفظ بيانات المشرف وصلاحياته.');
        $this->redirect('admin/admins');
    }

    /** Suspend / re-activate a sub-admin without deleting it. */
    public function toggleStatus(string $id): void
    {
        $this->verifyCsrf();
        $admin = $this->findSubAdmin((int) $id);
        $newStatus = $admin['status'] === 'active' ? 'inactive' : 'active';
        Admin::update((int) $admin['id'], ['status' => $newStatus]);
        Session::flash('success', $newStatus === 'active' ? 'تم تفعيل حساب المشرف.' : 'تم تعطيل حساب المشرف مؤقتًا، ولا يمكنه الدخول حتى يُفعَّل من جديد.');
        $this->redirect('admin/admins');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $admin = $this->findSubAdmin((int) $id);
        $this->deleteAvatarFile($admin['avatar'] ?? null);
        Admin::delete((int) $admin['id']);
        Session::flash('success', 'تم حذف حساب المشرف من النظام.');
        $this->redirect('admin/admins');
    }

    /** A sub-admin by id; main admins (and yourself) are never edited, suspended or deleted from here. */
    private function findSubAdmin(int $id): array
    {
        $admin = Admin::find($id);
        if (!$admin || !empty($admin['is_super'])) {
            Session::flash('error', 'المشرف غير موجود أو لا يمكن تعديل حساب مدير رئيسي من هنا.');
            $this->redirect('admin/admins');
        }
        return $admin;
    }

    private function validateFields(string $name, string $email, string $password, bool $passwordRequired, ?int $ignoreId): ?string
    {
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password])
            ->required('name', 'اسم المشرف')->max('name', 150, 'اسم المشرف')
            ->required('email', 'البريد الإلكتروني')->email('email', 'البريد الإلكتروني')->max('email', 150, 'البريد الإلكتروني');
        if ($passwordRequired || $password !== '') {
            $validator->required('password', 'كلمة المرور')->min('password', 8, 'كلمة المرور')->max('password', 72, 'كلمة المرور');
        }
        if ($validator->fails()) {
            return $validator->firstError();
        }

        $taken = Admin::findBy('email', $email);
        if ($taken && (int) $taken['id'] !== (int) $ignoreId) {
            return 'هذا البريد مستخدم لحساب إداري آخر.';
        }
        // The admin portal refuses to log in an email that belongs to an ordinary member, so it can't be used here.
        if (Member::findBy('email', $email)) {
            return 'هذا البريد مسجل لمشترك، اختر بريداً مختلفاً للمشرف.';
        }
        return null;
    }

    /** The permission matrix as posted: perm[section] = none|view|manage (input() drops arrays, so read $_POST). */
    private function postedPermissions(): array
    {
        $posted = is_array($_POST['perm'] ?? null) ? $_POST['perm'] : [];
        return array_filter($posted, 'is_string');
    }

    private function validateAvatarUpload(): ?string
    {
        $file = $_FILES['avatar'] ?? null;
        if (!$file || empty($file['name'])) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return 'فشل رفع الصورة، الرجاء المحاولة مرة أخرى.';
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return 'حجم الصورة يجب ألا يزيد عن 2MB.';
        }
        $mimeByExt = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($mimeByExt[$ext])) {
            return 'صيغة الصورة غير مقبولة (المسموح: png, jpg, webp).';
        }
        if ((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== $mimeByExt[$ext] || @getimagesize($file['tmp_name']) === false) {
            return 'محتوى الملف لا يطابق صيغته، الصورة مرفوضة.';
        }
        return null;
    }

    /** Stores the uploaded photo (already validated) and returns its path under public/, or null when none was sent. */
    private function saveAvatar(): ?string
    {
        $file = $_FILES['avatar'] ?? null;
        if (!$file || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $dir = base_dir() . '/public/' . self::AVATAR_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'admin_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . '/' . $filename) ? self::AVATAR_DIR . '/' . $filename : null;
    }

    private function deleteAvatarFile(?string $path): void
    {
        if ($path && str_starts_with($path, self::AVATAR_DIR . '/')) {
            $file = base_dir() . '/public/' . $path;
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
