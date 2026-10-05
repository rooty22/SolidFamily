<?php

namespace App\Permissions;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use PDO;

class PermissionManager
{
    /** @var array<int, array<string, bool>> Cache of admin_id => [permission_name => bool] */
    private static array $permissionCache = [];

    /** @var array<int, array<string>> Cache of admin_id => [role_names] */
    private static array $roleCache = [];

    /** @var array<int, bool> Cache of admin_id => is_super */
    private static array $superAdminCache = [];

    /**
     * Clear the in-memory runtime cache.
     */
    public static function clearCache(): void
    {
        self::$permissionCache = [];
        self::$roleCache = [];
        self::$superAdminCache = [];
    }

    /**
     * Determine if an admin has a specific permission.
     */
    public static function can(string $permission, ?int $adminId = null): bool
    {
        if ($adminId === null) {
            $adminId = current_admin_id();
        }

        if (!$adminId) {
            return false;
        }

        // 1. Check super admin bypass
        if (self::isSuperAdmin($adminId)) {
            return true;
        }

        // 2. Check runtime cache
        if (isset(self::$permissionCache[$adminId])) {
            return !empty(self::$permissionCache[$adminId][$permission]);
        }

        // 3. Load all permissions for this admin into cache
        $permissions = self::getAdminPermissions($adminId);
        self::$permissionCache[$adminId] = array_fill_keys($permissions, true);

        return !empty(self::$permissionCache[$adminId][$permission]);
    }

    /**
     * Inverse of can().
     */
    public static function cannot(string $permission, ?int $adminId = null): bool
    {
        return !self::can($permission, $adminId);
    }

    /**
     * Check if an admin has a specific role (or one of multiple roles).
     */
    public static function hasRole(string|array $role, ?int $adminId = null): bool
    {
        if ($adminId === null) {
            $adminId = current_admin_id();
        }

        if (!$adminId) {
            return false;
        }

        $roles = self::getAdminRoles($adminId);
        $roleNames = array_column($roles, 'name');

        if (is_array($role)) {
            return count(array_intersect($role, $roleNames)) > 0;
        }

        return in_array($role, $roleNames, true);
    }

    /**
     * Check if an admin is a Super Admin.
     */
    public static function isSuperAdmin(?int $adminId = null): bool
    {
        if ($adminId === null) {
            $adminId = current_admin_id();
        }

        if (!$adminId) {
            return false;
        }

        if (isset(self::$superAdminCache[$adminId])) {
            return self::$superAdminCache[$adminId];
        }

        // ID 1 is always treated as super admin for safety
        if ($adminId === 1) {
            self::$superAdminCache[$adminId] = true;
            return true;
        }

        // Check if assigned role is 'super_admin'
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM admin_roles ar
            INNER JOIN roles r ON r.id = ar.role_id
            WHERE ar.admin_id = ? AND r.name = 'super_admin'
        ");
        $stmt->execute([$adminId]);
        $isSuper = ((int) $stmt->fetchColumn()) > 0;

        self::$superAdminCache[$adminId] = $isSuper;
        return $isSuper;
    }

    /**
     * Fetch all effective permission names for an admin (from both roles and direct permissions).
     */
    public static function getAdminPermissions(int $adminId): array
    {
        $db = Database::connection();

        // If super admin, return all permission names
        if (self::isSuperAdmin($adminId)) {
            return $db->query("SELECT name FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        }

        // Union of role permissions and direct permissions
        $sql = "
            SELECT DISTINCT p.name
            FROM permissions p
            LEFT JOIN role_permissions rp ON rp.permission_id = p.id
            LEFT JOIN admin_roles ar ON ar.role_id = rp.role_id AND ar.admin_id = :admin_id1
            LEFT JOIN admin_permissions ap ON ap.permission_id = p.id AND ap.admin_id = :admin_id2
            WHERE ar.admin_id IS NOT NULL OR ap.admin_id IS NOT NULL
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':admin_id1' => $adminId,
            ':admin_id2' => $adminId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Fetch all roles assigned to an admin.
     */
    public static function getAdminRoles(int $adminId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT r.*
            FROM roles r
            INNER JOIN admin_roles ar ON ar.role_id = r.id
            WHERE ar.admin_id = ?
            ORDER BY r.is_system DESC, r.id ASC
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a formatted display name for the admin's primary role.
     */
    public static function getAdminRoleName(?int $adminId = null, ?string $locale = null): string
    {
        if ($adminId === null) {
            $adminId = current_admin_id();
        }

        if (!$adminId) {
            return '';
        }

        $locale = $locale ?? (function_exists('current_locale') ? current_locale() : 'ar');
        $isEn = ($locale === 'en');

        if (self::isSuperAdmin($adminId)) {
            return $isEn ? 'Super Administrator' : 'المدير العام';
        }

        $roles = self::getAdminRoles($adminId);
        if (!empty($roles)) {
            return $isEn ? ($roles[0]['label_en'] ?? $roles[0]['label_ar']) : $roles[0]['label_ar'];
        }

        return $isEn ? 'Administrative Member' : 'مشرف إداري';
    }

    /**
     * Assign a role to an admin.
     */
    public static function assignRoleToAdmin(int $adminId, int $roleId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT IGNORE INTO admin_roles (admin_id, role_id) VALUES (?, ?)");
        $stmt->execute([$adminId, $roleId]);
        self::clearCache();
    }

    /**
     * Sync roles for an admin (replaces existing assigned roles).
     */
    public static function syncAdminRoles(int $adminId, array $roleIds): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $del = $db->prepare("DELETE FROM admin_roles WHERE admin_id = ?");
            $del->execute([$adminId]);

            if (!empty($roleIds)) {
                $ins = $db->prepare("INSERT IGNORE INTO admin_roles (admin_id, role_id) VALUES (?, ?)");
                foreach ($roleIds as $rId) {
                    $rId = (int) $rId;
                    if ($rId > 0) {
                        $ins->execute([$adminId, $rId]);
                    }
                }
            }
            $db->commit();
            self::clearCache();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Sync direct permissions for an admin (replaces existing direct permissions).
     */
    public static function syncAdminDirectPermissions(int $adminId, array $permissionIds): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $del = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
            $del->execute([$adminId]);

            if (!empty($permissionIds)) {
                $ins = $db->prepare("INSERT IGNORE INTO admin_permissions (admin_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $pId) {
                    $pId = (int) $pId;
                    if ($pId > 0) {
                        $ins->execute([$adminId, $pId]);
                    }
                }
            }
            $db->commit();
            self::clearCache();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Grant dashboard access to a member with a designated role and optional direct permissions.
     * Links/creates an admin row, sets members.is_admin = 1, and assigns role.
     *
     * @return int The admin_id associated with this member.
     */
    public static function grantMemberDashboardAccess(int $memberId, int $roleId, array $directPermissionIds = []): int
    {
        $db = Database::connection();
        $member = Member::find($memberId);
        if (!$member) {
            throw new \InvalidArgumentException('المشترك غير موجود في النظام.');
        }

        $email = mb_strtolower(trim($member['email']));
        $name = trim($member['name']);
        $passwordHash = $member['password'];

        $db->beginTransaction();
        try {
            // 1. Look for existing admin with member_id or matching email
            $stmt = $db->prepare("SELECT id FROM admins WHERE member_id = ? OR email = ? LIMIT 1");
            $stmt->execute([$memberId, $email]);
            $existingAdminId = $stmt->fetchColumn();

            if ($existingAdminId) {
                $adminId = (int) $existingAdminId;
                // Update member_id and credentials sync
                $update = $db->prepare("UPDATE admins SET member_id = ?, name = ?, password = ? WHERE id = ?");
                $update->execute([$memberId, $name, $passwordHash, $adminId]);
            } else {
                // Insert new admin record
                $insert = $db->prepare("INSERT INTO admins (member_id, name, email, password) VALUES (?, ?, ?, ?)");
                $insert->execute([$memberId, $name, $email, $passwordHash]);
                $adminId = (int) $db->lastInsertId();
            }

            // 2. Set members.is_admin = 1
            $updateMember = $db->prepare("UPDATE members SET is_admin = 1 WHERE id = ?");
            $updateMember->execute([$memberId]);

            // 3. Assign the designated role
            $delRoles = $db->prepare("DELETE FROM admin_roles WHERE admin_id = ?");
            $delRoles->execute([$adminId]);
            if ($roleId > 0) {
                $insRole = $db->prepare("INSERT INTO admin_roles (admin_id, role_id) VALUES (?, ?)");
                $insRole->execute([$adminId, $roleId]);
            }

            // 4. Assign direct permissions if any
            $delPerms = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
            $delPerms->execute([$adminId]);
            if (!empty($directPermissionIds)) {
                $insPerm = $db->prepare("INSERT INTO admin_permissions (admin_id, permission_id) VALUES (?, ?)");
                foreach ($directPermissionIds as $pId) {
                    $pId = (int) $pId;
                    if ($pId > 0) {
                        $insPerm->execute([$adminId, $pId]);
                    }
                }
            }

            $db->commit();
            self::clearCache();

            return $adminId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Revoke dashboard access from a member.
     * Clears assigned roles and permissions, unlinks admin row, and sets is_admin = 0.
     */
    public static function revokeMemberDashboardAccess(int $memberId): void
    {
        $db = Database::connection();
        $member = Member::find($memberId);
        if (!$member) {
            return;
        }

        $email = mb_strtolower(trim($member['email']));

        $db->beginTransaction();
        try {
            // Find linked admin
            $stmt = $db->prepare("SELECT id FROM admins WHERE member_id = ? OR email = ?");
            $stmt->execute([$memberId, $email]);
            $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($adminIds as $adminId) {
                $adminId = (int) $adminId;
                // Never delete or strip primary super admin ID 1
                if ($adminId === 1) {
                    continue;
                }

                // Delete roles & permissions
                $db->prepare("DELETE FROM admin_roles WHERE admin_id = ?")->execute([$adminId]);
                $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?")->execute([$adminId]);

                // Delete linked admin row if created for this member
                $db->prepare("DELETE FROM admins WHERE id = ? AND member_id = ?")->execute([$adminId, $memberId]);
            }

            // Set members.is_admin = 0
            $db->prepare("UPDATE members SET is_admin = 0 WHERE id = ?")->execute([$memberId]);

            $db->commit();
            self::clearCache();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Fetch all members who have administrative dashboard access, along with their roles and status.
     */
    public static function getAdministrativeMembers(): array
    {
        $db = Database::connection();
        $sql = "
            SELECT 
                m.id AS member_id,
                m.name AS member_name,
                m.email AS member_email,
                m.mobile AS member_mobile,
                m.national_id AS member_national_id,
                m.status AS member_status,
                m.is_admin AS member_is_admin,
                a.id AS admin_id,
                r.id AS role_id,
                r.name AS role_name,
                r.label_ar AS role_label_ar,
                r.label_en AS role_label_en,
                (
                    SELECT COUNT(DISTINCT p.id)
                    FROM permissions p
                    LEFT JOIN role_permissions rp ON rp.permission_id = p.id AND rp.role_id = r.id
                    LEFT JOIN admin_permissions ap ON ap.permission_id = p.id AND ap.admin_id = a.id
                    WHERE rp.role_id IS NOT NULL OR ap.admin_id IS NOT NULL
                ) AS permissions_count
            FROM members m
            INNER JOIN admins a ON (a.member_id = m.id OR LOWER(a.email) = LOWER(m.email))
            LEFT JOIN admin_roles ar ON ar.admin_id = a.id
            LEFT JOIN roles r ON r.id = ar.role_id
            WHERE m.is_admin = 1 OR a.id IS NOT NULL
            ORDER BY m.name ASC
        ";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
