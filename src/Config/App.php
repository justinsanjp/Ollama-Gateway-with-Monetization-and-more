<?php

declare(strict_types=1);

namespace App\Config;

final class App
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim(self::get('APP_URL', 'http://localhost'), '/');
        return $base . '/' . ltrim($path, '/');
    }

    public static function sessionLifetime(): int
    {
        return (int) self::get('SESSION_LIFETIME', 120);
    }

    public static function isSessionSecure(): bool
    {
        return self::get('SESSION_SECURE', 'true') === 'true';
    }
}
