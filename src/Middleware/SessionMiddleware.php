<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\App;

final class SessionMiddleware
{
    public static function start(): ?string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return null;
        }

        // Don't start sessions for API routes
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (str_starts_with($uri, '/v1/')) {
            return null;
        }

        $cookieParams = [
            'lifetime' => App::sessionLifetime() * 60,
            'path' => '/',
            'domain' => '',
            'secure' => App::isSessionSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        session_set_cookie_params($cookieParams);
        session_name('OG_SID');
        session_start();

        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        if (time() - $_SESSION['_created'] > App::sessionLifetime() * 60) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }

        return null;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
