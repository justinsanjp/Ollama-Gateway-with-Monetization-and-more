<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\App;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], string $layout = 'main'): never
    {
        extract(self::$shared);
        extract($data);

        $viewPath = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: {$view}";
            exit;
        }

        $content = function () use ($viewPath, $data) {
            extract($GLOBALS['_view_data'] ?? []);
            extract($data);
            require $viewPath;
        };

        $layoutPath = dirname(__DIR__, 2) . '/views/layouts/' . $layout . '.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            $content();
        }

        exit;
    }

    public static function renderPartial(string $view, array $data = []): void
    {
        extract(self::$shared);
        extract($data);
        require dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $view) . '.php';
    }

    public static function redirect(string $path, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $path);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function csrfField(): string
    {
        $token = $_SESSION['_csrf_token'] ?? '';
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function old(string $key, string $default = ''): string
    {
        return htmlspecialchars($_SESSION['_old'][$key] ?? $default, ENT_QUOTES, 'UTF-8');
    }

    public static function error(string $key): string
    {
        $errors = $_SESSION['_errors'][$key] ?? [];
        if (empty($errors)) {
            return '';
        }
        return '<div class="text-red-600 text-sm mt-1">' . htmlspecialchars(implode(', ', $errors), ENT_QUOTES, 'UTF-8') . '</div>';
    }

    public static function hasError(string $key): bool
    {
        return isset($_SESSION['_errors'][$key]) && !empty($_SESSION['_errors'][$key]);
    }

    public static function flash(string $key = null): string|array|null
    {
        if ($key === null) {
            $messages = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            return $messages;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public static function money(float $amount): string
    {
        if ($amount < 0) {
            return '-$' . number_format(abs($amount), 2, '.', ',');
        }

        if ($amount === 0.0) {
            return '$0.00';
        }

        if ($amount < 0.01) {
            return '$0.00 (credits remaining)';
        }

        $decimals = $amount < 1 ? 4 : 2;

        $formatted = number_format($amount, $decimals, '.', ',');
        if ($decimals > 2) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        return '$' . $formatted;
    }
}
