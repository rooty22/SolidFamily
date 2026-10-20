<?php

namespace App\Core;

class Middleware
{
    public static function adminGuest(): callable
    {
        return function () {
            if (Auth::adminCheck()) {
                header('Location: ' . url('admin/dashboard'));
                exit;
            }
        };
    }

    /**
     * Logged-in admin, and allowed to open the section of the requested page: reading a page needs 'view', anything
     * that changes data (POST) needs 'manage'. The sub-admin management pages are for main admins only.
     */
    public static function adminAuth(): callable
    {
        return function () {
            if (!Auth::adminCheck()) {
                header('Location: ' . url('admin/login'));
                exit;
            }

            $path = current_path();
            $admin = Auth::admin();
            $allowed = true;
            if (\App\Models\Admin::isSuperOnlyPath($path)) {
                $allowed = !empty($admin['is_super']);
            } elseif (($section = \App\Models\Admin::sectionForPath($path)) !== null) {
                $level = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? \App\Models\Admin::LEVEL_VIEW : \App\Models\Admin::LEVEL_MANAGE;
                $allowed = \App\Models\Admin::can($admin, $section, $level);
            }
            if ($allowed) {
                return;
            }

            // Land on the first section this admin does have instead of a dead end.
            $home = \App\Models\Admin::homePath($admin);
            if ($home !== null && '/' . $home !== $path) {
                Session::flash('error', 'ليست لديك صلاحية لهذه الصفحة أو لهذا الإجراء.');
                header('Location: ' . url($home));
                exit;
            }
            http_response_code(403);
            echo 'ليست لديك صلاحية للوصول إلى لوحة التحكم، الرجاء التواصل مع مدير النظام.';
            exit;
        };
    }

    public static function memberGuest(): callable
    {
        return function () {
            if (Auth::memberCheck()) {
                header('Location: ' . url('home'));
                exit;
            }
        };
    }

    public static function memberAuth(): callable
    {
        return function () {
            if (!Auth::memberCheck()) {
                header('Location: ' . url('login'));
                exit;
            }
        };
    }
}
