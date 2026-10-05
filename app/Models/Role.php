<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Role extends Model
{
    protected static string $table = 'roles';

    /**
     * Get all permissions assigned to this role.
     */
    public static function getPermissions(int $roleId): array
    {
        $sql = "SELECT p.* FROM permissions p
                INNER JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = ?
                ORDER BY p.module ASC, p.id ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all permission names assigned to this role as an array of strings.
     */
    public static function getPermissionNames(int $roleId): array
    {
        $sql = "SELECT p.name FROM permissions p
                INNER JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Sync permissions for a role.
     */
    public static function syncPermissions(int $roleId, array $permissionIds): void
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            // Delete current
            $del = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $del->execute([$roleId]);

            // Insert new
            if (!empty($permissionIds)) {
                $ins = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $permId) {
                    $pId = (int) $permId;
                    if ($pId > 0) {
                        $ins->execute([$roleId, $pId]);
                    }
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Fetch all roles with counts of permissions and assigned admins/members.
     */
    public static function allWithCounts(): array
    {
        $sql = "SELECT r.*,
                (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permissions_count,
                (SELECT COUNT(*) FROM admin_roles ar WHERE ar.role_id = r.id) AS users_count
                FROM roles r
                ORDER BY r.is_system DESC, r.id ASC";
        return self::db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
