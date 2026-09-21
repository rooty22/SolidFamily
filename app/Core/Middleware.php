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
}
