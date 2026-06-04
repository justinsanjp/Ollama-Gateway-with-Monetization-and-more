<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use PDO;

final class AuthService
{
    public static function register(string $email, string $password, string $name): array
    {
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email address.';
        }

        if (User::findByEmail($email)) {
            $errors['email'][] = 'This email is already in use.';
        }

        if (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors['password'][] = 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors['password'][] = 'Password must contain at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors['password'][] = 'Password must contain at least one number.';
        }

        if (empty(trim($name))) {
            $errors['name'][] = 'Name must not be empty.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);

        $apiKey = User::generateApiKey();

        $userId = User::create([
            'email' => $email,
            'password_hash' => $hash,
            'name' => trim($name),
            'api_key' => $apiKey,
            'role' => 'user',
            'balance' => 0.000000,
        ]);

        return [
            'success' => true,
            'user_id' => $userId,
            'api_key' => $apiKey,
        ];
    }

    public static function login(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
            User::update($user['id'], ['password_hash' => $newHash]);
        }

        if ($user['totp_enabled']) {
            session_regenerate_id(true);
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['requires_2fa'] = true;
            return [
                'success' => true,
                'requires_2fa' => true,
                'user' => $user,
            ];
        }

        self::createSession($user);

        return [
            'success' => true,
            'requires_2fa' => false,
            'user' => $user,
        ];
    }

    public static function createSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_balance'] = (float) $user['balance'];
        $_SESSION['_created'] = time();
        unset($_SESSION['requires_2fa'], $_SESSION['2fa_user_id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
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
        session_destroy();
    }

    public static function verify2fa(string $code): array
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;

        if (!$userId) {
            return ['success' => false, 'error' => 'No pending 2FA verification.'];
        }

        $user = User::findById((int) $userId);
        if (!$user || !$user['totp_enabled']) {
            return ['success' => false, 'error' => '2FA is not enabled.'];
        }

        $totp = \RobThree\Auth\TwoFactorAuth::create(
            $_ENV['APP_NAME'] ?? 'Ollama Gateway',
            $user['totp_secret']
        );

        if (!$totp->verifyCode($code, 2)) {
            return ['success' => false, 'error' => 'Invalid 2FA code.'];
        }

        self::createSession($user);

        return ['success' => true, 'user' => $user];
    }
}
