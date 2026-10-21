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

    /** Permission sections: key => [label ar, label en, the path segments under /admin that belong to it, icon]. */
    public static function sections(): array
    {
        return [
            'dashboard' => ['نظرة عامة', 'Overview', ['dashboard'], 'grid-1x2-fill'],
            'members' => ['إدارة المشتركين', 'Members', ['members'], 'people-fill'],
            'shares' => ['إدارة الأسهم', 'Shares', ['shares'], 'pie-chart-fill'],
            'share_requests' => ['طلبات الأسهم', 'Share requests', ['share-requests'], 'arrow-left-right'],
            'subscriptions' => ['الاشتراكات الشهرية', 'Monthly subscriptions', ['subscriptions'], 'calendar2-check-fill'],
            'founding' => ['مبالغ التأسيس', 'Founding amounts', ['founding'], 'bank2'],
            'loan_requests' => ['طلبات القروض', 'Loan requests', ['loan-requests'], 'file-earmark-text-fill'],
            'loans' => ['القروض والأقساط', 'Loans & installments', ['loans'], 'cash-coin'],
            'payments' => ['المدفوعات', 'Payments', ['payments'], 'credit-card-2-front-fill'],
            'transactions' => ['المعاملات المالية', 'Transactions', ['transactions'], 'journal-text'],
            'notifications' => ['إرسال الإشعارات', 'Notifications', ['notifications'], 'bell-fill'],
            'messages' => ['رسائل التواصل', 'Messages', ['messages'], 'chat-dots-fill'],
            'content' => ['محتوى صفحات النظام', 'Content pages', ['content'], 'file-earmark-richtext-fill'],
            'live_translate' => ['الترجمة الفورية', 'Live translate', ['live-translate'], 'translate'],
            'settings' => ['إعدادات النظام', 'System settings', ['settings'], 'gear-fill'],
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

    /**
     * The level a request needs: reading a page is 'view', anything that changes data (POST) is 'manage'. The pages
     * that exist only to edit something (add / edit forms, the home-page builder) need 'manage' even to open.
     */
    public static function requiredLevel(string $path, string $method): string
    {
        if (strtoupper($method) !== 'GET') {
            return self::LEVEL_MANAGE;
        }
        if (preg_match('#/(create|edit)$#', $path) || $path === '/admin/content/home') {
            return self::LEVEL_MANAGE;
        }
        return self::LEVEL_VIEW;
    }

    /** True for the pages only a main admin may open (managing the sub-admins). */
    public static function isSuperOnlyPath(string $path): bool
    {
        return (bool) preg_match('#^/admin/admins(/|$)#', $path);
    }

    /**
     * Endpoints that live under one section but are used by buttons on the pages of others: path regex => the other
     * sections whose full control also opens them. (Editing a lot's start date sits on the Shares and Members pages
     * but its route is under Subscriptions.)
     */
    public static function sharedEndpoints(): array
    {
        return [
            '^/admin/subscriptions/[0-9]+/lot/[0-9]+/date$' => ['shares', 'members'],
        ];
    }

    /**
     * Single decision used by the middleware: may this admin open this path with this HTTP method?
     * Main admins always; sub-admins need the path's section at the required level, or full control of a section
     * a shared endpoint is lent to. The sub-admin pages are for main admins only.
     */
    public static function mayUse(array $admin, string $path, string $method): bool
    {
        if (!empty($admin['is_super'])) {
            return true;
        }
        if (self::isSuperOnlyPath($path)) {
            return false;
        }
        $section = self::sectionForPath($path);
        if ($section === null) {
            // Default deny: only the few pages that belong to no section are open to every sub-admin, so a page
            // added later under /admin without a section stays closed until it is given one.
            return $path === '/admin' || (bool) preg_match('#^/admin/(logout|lang)(/|$)#', $path);
        }
        if (self::can($admin, $section, self::requiredLevel($path, $method))) {
            return true;
        }
        if (strtoupper($method) !== 'GET') {
            foreach (self::sharedEndpoints() as $pattern => $sections) {
                if (preg_match('#' . $pattern . '#', $path)) {
                    foreach ($sections as $other) {
                        if (self::can($admin, $other, self::LEVEL_MANAGE)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
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
