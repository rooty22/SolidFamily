<?php

namespace App\Core;

use App\Models\Admin;
use App\Models\Member;

class Auth
{
    public static function loginAdmin(array $admin): void
    {
        Session::regenerate();
        Session::set('admin_id', $admin['id']);
        Session::set('admin_name', $admin['name']);
        self::$adminRow = null;
    }

    public static function logoutAdmin(): void
    {
        Session::remove('admin_id');
        Session::remove('admin_name');
        self::$adminRow = null;
    }

    /** Per-request cache of the logged-in admin's row (null = not loaded yet, false = no valid admin). */
    private static $adminRow = null;

    public static function adminCheck(): bool
    {
        return self::admin() !== null;
    }

    /**
     * The logged-in admin. Re-read on every request so deleting or suspending a sub-admin takes effect immediately,
     * not when the session expires.
     */
    public static function admin(): ?array
    {
        $id = Session::get('admin_id');
        if (!$id) {
            return null;
        }
        if (self::$adminRow === null) {
            $row = Admin::find($id);
            if (!$row) {
                self::logoutAdmin();
                $row = false;
            } elseif (($row['status'] ?? 'active') !== 'active') {
                self::logoutAdmin();
                Session::flash('error', 'تم تعطيل حساب المشرف هذا، الرجاء التواصل مع مدير النظام.');
                $row = false;
            }
            self::$adminRow = $row;
        }
        return self::$adminRow ?: null;
    }

    /** Whether the logged-in admin may open ('view') or change ('manage') a section. */
    public static function adminCan(string $section, string $level = Admin::LEVEL_VIEW): bool
    {
        $admin = self::admin();
        return $admin !== null && Admin::can($admin, $section, $level);
    }

    public static function adminIsSuper(): bool
    {
        $admin = self::admin();
        return $admin !== null && !empty($admin['is_super']);
    }

    public static function loginMember(array $member): void
    {
        Session::regenerate();
        Session::set('member_id', $member['id']);
        Session::set('member_name', $member['name']);
    }

    public static function logoutMember(): void
    {
        Session::remove('member_id');
        Session::remove('member_name');
    }

    public static function memberCheck(): bool
    {
        $id = Session::get('member_id');
        if (!$id) {
            return false;
        }

        // Re-check on every request so suspending a member takes effect immediately, not when the session expires.
        $member = Member::find($id);
        if (!$member) {
            self::logoutMember();
            return false;
        }
        if ($member['status'] !== 'active') {
            self::logoutMember();
            Session::flash('error', Member::inactiveMessage($member['status']));
            return false;
        }
        if (Member::isAdmin($member)) {
            self::logoutMember();
            Session::flash('error', 'هذا الحساب مخصص لإدارة الصندوق ولا يمكن استخدامه كحساب مشترك. يرجى الدخول من بوابة الإدارة.');
            return false;
        }
        return true;
    }

    public static function member(): ?array
    {
        $id = Session::get('member_id');
        return $id ? Member::find($id) : null;
    }
}
