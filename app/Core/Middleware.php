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

    public static function adminAuth(): callable
    {
        return function () {
            if (!Auth::adminCheck()) {
                header('Location: ' . url('admin/login'));
                exit;
            }
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

    /**
     * Guard route by specific permission.
     */
    public static function permission(string $permission): callable
    {
        return function () use ($permission) {
            if (!Auth::adminCheck()) {
                header('Location: ' . url('admin/login'));
                exit;
            }

            if (!admin_can($permission)) {
                if (is_htmx_or_ajax()) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'error' => __('permission_denied', ['action' => $permission]) ?? 'عذراً، لا تملك الصلاحية لتنفيذ هذا الإجراء.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                Session::flash('error', 'عذراً، ليس لديك الصلاحية الكافية للوصول إلى هذا القسم أو الإجراء.');
                header('Location: ' . url('admin/dashboard'));
                exit;
            }
        };
    }

    /**
     * Guard route by specific role.
     */
    public static function role(string|array $role): callable
    {
        return function () use ($role) {
            if (!Auth::adminCheck()) {
                header('Location: ' . url('admin/login'));
                exit;
            }

            if (!admin_has_role($role) && !admin_is_super()) {
                if (is_htmx_or_ajax()) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'error' => 'عذراً، يتطلب هذا الإجراء دوراً وظيفياً محدداً.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                Session::flash('error', 'عذراً، ليس لديك الدور الوظيفي المطلوب للوصول إلى هذا القسم.');
                header('Location: ' . url('admin/dashboard'));
                exit;
            }
        };
    }
}
