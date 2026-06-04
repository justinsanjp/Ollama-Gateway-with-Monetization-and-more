<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Database;
use App\Helpers\View;

final class AdminMiddleware
{
    public static function required(): ?string
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['_flash']['warning'] = 'Bitte melde dich zuerst an.';
            View::redirect('/login');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            View::redirect('/');
        }

        return null;
    }
}
