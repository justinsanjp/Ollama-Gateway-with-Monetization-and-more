<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\View;
use App\Models\User;

final class AuthMiddleware
{
    public static function required(): ?string
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['_flash']['warning'] = 'Please log in first.';
            View::redirect('/login');
        }

        $fresh = User::findById($_SESSION['user_id']);
        if ($fresh) {
            $_SESSION['user_balance'] = (float) $fresh['balance'];
        }

        if (isset($_SESSION['requires_2fa']) && $_SESSION['requires_2fa'] === true) {
            View::redirect('/verify-2fa');
        }

        return null;
    }

    public static function guest(): ?string
    {
        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['requires_2fa']) && $_SESSION['requires_2fa'] === true) {
                return null;
            }
            View::redirect('/dashboard');
        }
        return null;
    }
}
