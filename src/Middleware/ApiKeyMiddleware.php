<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Database;

final class ApiKeyMiddleware
{
    public static function authenticate(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'message' => 'Missing or invalid Authorization header. Use: Bearer <api_key>',
                    'type' => 'authentication_error',
                    'code' => 401,
                ],
            ]);
            exit;
        }

        $apiKey = $matches[1];

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT id, email, name, balance, role FROM users WHERE api_key = ? AND is_active = 1'
        );
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'message' => 'Invalid API key.',
                    'type' => 'authentication_error',
                    'code' => 401,
                ],
            ]);
            exit;
        }

        $_REQUEST['_api_user'] = $user;

        return null;
    }
}
