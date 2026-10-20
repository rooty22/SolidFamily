<?php

namespace App\Models;

use App\Core\Model;

/**
 * Main admins (is_super) can do everything and manage the sub-admins; a sub-admin only gets the sections the main
 * admin granted, each as 'view' (read the pages) or 'manage' (also change data). Sub-admins can be suspended
 * (status = inactive) without deleting them.
 */
class Admin extends Model
{
    protected static string $table = 'admins';

    public const LEVEL_NONE = 'none';
    public const LEVEL_VIEW = 'view';
    public const LEVEL_MANAGE = 'manage';

    /** Permission sections: key => [label ar, label en, the path segments under /admin that belong to it]. */
    public static function sections(): array
    {
        return [
            'dashboard' => ['نظرة عامة', 'Overview', ['dashboard']],
            'members' => ['إدارة المشتركين', 'Members', ['members']],
            'shares' => ['إدارة الأسهم', 'Shares', ['shares']],
            'share_requests' => ['طلبات الأسهم', 'Share requests', ['share-requests']],
            'subscriptions' => ['الاشتراكات الشهرية', 'Monthly subscriptions', ['subscriptions']],
            'founding' => ['مبالغ التأسيس', 'Founding amounts', ['founding']],
            'loan_requests' => ['طلبات القروض', 'Loan requests', ['loan-requests']],
            'loans' => ['القروض والأقساط', 'Loans & installments', ['loans']],
            'payments' => ['المدفوعات', 'Payments', ['payments']],
            'transactions' => ['المعاملات المالية', 'Transactions', ['transactions']],
            'notifications' => ['إرسال الإشعارات', 'Notifications', ['notifications']],
            'messages' => ['رسائل التواصل', 'Messages', ['messages']],
            'content' => ['محتوى صفحات النظام', 'Content pages', ['content']],
            'live_translate' => ['الترجمة الفورية', 'Live translate', ['live-translate']],
            'settings' => ['إعدادات النظام', 'System settings', ['settings']],
        ];
    }

    /** The section a request path (e.g. /admin/loans/5/pay) belongs to, or null when it is not a permissioned section. */
    public static function sectionForPath(string $path): ?string
    {
        if (!preg_match('#^/admin/([^/]+)#', $path, $m)) {
            return null;
        }
        foreach (self::sections() as $key => $def) {
            if (in_array($m[1], $def[2], true)) {
                return $key;
            }
        }
        return null;
    }

    /** True for the pages only a main admin may open (managing the sub-admins). */
    public static function isSuperOnlyPath(string $path): bool
    {
        return (bool) preg_match('#^/admin/admins(/|$)#', $path);
    }

    /** The granted permissions as [section => 'view'|'manage'], unknown sections / levels dropped. */
    public static function permissionsOf(array $admin): array
    {
        $raw = json_decode((string) ($admin['permissions'] ?? ''), true);
        $out = [];
        foreach (is_array($raw) ? $raw : [] as $section => $level) {
            if (isset(self::sections()[$section]) && in_array($level, [self::LEVEL_VIEW, self::LEVEL_MANAGE], true)) {
                $out[$section] = $level;
            }
        }
        return $out;
    }

    /** Whether the admin may open (level 'view') or change (level 'manage') a section; manage implies view. */
    public static function can(array $admin, string $section, string $level = self::LEVEL_VIEW): bool
    {
        if (!empty($admin['is_super'])) {
            return true;
        }
        $granted = self::permissionsOf($admin)[$section] ?? self::LEVEL_NONE;
        return $granted === self::LEVEL_MANAGE || ($granted === self::LEVEL_VIEW && $level === self::LEVEL_VIEW);
    }

    /** Normalises a posted permissions array (section => level) into the JSON to store. */
    public static function encodePermissions(array $posted): string
    {
        $clean = [];
        foreach ($posted as $section => $level) {
            if (isset(self::sections()[$section]) && in_array($level, [self::LEVEL_VIEW, self::LEVEL_MANAGE], true)) {
                $clean[$section] = $level;
            }
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /** The admin portal path of the first section this admin may view (where to land when a page is not allowed). */
    public static function homePath(array $admin): ?string
    {
        foreach (self::sections() as $key => $def) {
            if (self::can($admin, $key)) {
                return 'admin/' . $def[2][0];
            }
        }
        return null;
    }

    public static function avatarUrl(array $admin): ?string
    {
        return !empty($admin['avatar']) ? url($admin['avatar']) : null;
    }
}
