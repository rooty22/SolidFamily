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
    }

    public static function logoutAdmin(): void
    {
        Session::remove('admin_id');
        Session::remove('admin_name');
    }

    public static function adminCheck(): bool
    {
        return Session::has('admin_id');
    }

    public static function admin(): ?array
    {
        $id = Session::get('admin_id');
        return $id ? Admin::find($id) : null;
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
            Session::flash('error', 'تم إيقاف هذا الحساب، الرجاء التواصل مع الإدارة.');
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
