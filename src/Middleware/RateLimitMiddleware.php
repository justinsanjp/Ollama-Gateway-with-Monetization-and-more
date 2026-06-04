<?php

declare(strict_types=1);

namespace App\Middleware;

final class RateLimitMiddleware
{
    private static string $logDir = '/tmp/opencode-rate-limit';

    public static function check(int $maxAttempts = 5, int $windowSeconds = 60): ?array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rl_' . md5($ip . ($_SERVER['REQUEST_URI'] ?? '/'));

        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }

        $file = self::$logDir . '/' . $key;
        $now = time();
        $attempts = [];

        if (file_exists($file)) {
            $data = @file_get_contents($file);
            $attempts = $data ? array_filter(json_decode($data, true) ?: [], fn($t) => $t > ($now - $windowSeconds)) : [];
        }

        $attempts[] = $now;

        if (count($attempts) > $maxAttempts) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            exit;
        }

        @file_put_contents($file, json_encode($attempts), LOCK_EX);

        return null;
    }
}
