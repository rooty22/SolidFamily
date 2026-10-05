<?php

namespace App\Core;

use App\Models\Admin;
use App\Models\Member;
use App\Permissions\PermissionManager;

class Auth
{
    public static function loginAdmin(array $admin): void
    {
        Session::regenerate();
        Session::set('admin_id', $admin['id']);
        Session::set('admin_name', $admin['name']);
        Session::set('admin_email', $admin['email'] ?? '');
        Session::set('admin_member_id', $admin['member_id'] ?? null);
        
        PermissionManager::clearCache();
        Session::set('admin_role', PermissionManager::getAdminRoleName($admin['id']));
        Session::set('admin_is_super', PermissionManager::isSuperAdmin($admin['id']));
    }

    public static function logoutAdmin(): void
    {
        Session::remove('admin_id');
        Session::remove('admin_name');
        Session::remove('admin_email');
        Session::remove('admin_member_id');
        Session::remove('admin_role');
        Session::remove('admin_is_super');
        PermissionManager::clearCache();
    }

    public static function adminCheck(): bool
    {
        return Session::has('admin_id');
    }

    public static function admin(): ?array
    {
        $id = Session::get('admin_id');
        if (!$id) {
            return null;
        }

        $admin = Admin::find($id);
        if ($admin) {
            $admin['role_name'] = PermissionManager::getAdminRoleName($id);
            $admin['is_super'] = PermissionManager::isSuperAdmin($id);
        }
        return $admin;
    }

    public static function can(string $permission): bool
    {
        return PermissionManager::can($permission);
    }

    public static function cannot(string $permission): bool
    {
        return PermissionManager::cannot($permission);
    }

    public static function hasRole(string|array $role): bool
    {
        return PermissionManager::hasRole($role);
    }

    public static function isSuperAdmin(): bool
    {
        return PermissionManager::isSuperAdmin();
    }

    public static function roleName(?string $locale = null): string
    {
        return PermissionManager::getAdminRoleName(null, $locale);
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
