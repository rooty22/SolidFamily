<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Permission extends Model
{
    protected static string $table = 'permissions';

    /**
     * Get all permissions grouped by module.
     *
     * @return array<string, array{module: string, label_ar: string, label_en: string, permissions: array}>
     */
    public static function groupedByModule(): array
    {
        $all = self::all('id ASC');
        $grouped = [];

        foreach ($all as $perm) {
            $mod = $perm['module'];
            if (!isset($grouped[$mod])) {
                $grouped[$mod] = [
                    'module' => $mod,
                    'label_ar' => $perm['module_label_ar'] ?? $mod,
                    'label_en' => $perm['module_label_en'] ?? ucfirst($mod),
                    'permissions' => [],
                ];
            }
            $grouped[$mod]['permissions'][] = $perm;
        }

        return $grouped;
    }
}
