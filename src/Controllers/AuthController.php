<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TotpService;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth.login');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['_errors']['email'][] = 'Please enter email and password.';
            $_SESSION['_old'] = ['email' => $email];
            View::redirect('/login');
        }

        $result = AuthService::login($email, $password);

        if (!$result['success']) {
            $_SESSION['_errors']['email'][] = $result['error'];
            $_SESSION['_old'] = ['email' => $email];
            View::redirect('/login');
        }

        if ($result['requires_2fa']) {
            View::redirect('/verify-2fa');
        }

        $_SESSION['_flash']['success'] = 'Welcome back, ' . htmlspecialchars($result['user']['name']) . '!';
        View::redirect('/dashboard');
    }

    public function showRegister(): void
    {
        View::render('auth.register');
    }

    public function register(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $name = trim($_POST['name'] ?? '');

        if ($password !== $passwordConfirm) {
            $_SESSION['_errors']['password'][] = 'Passwords do not match.';
            $_SESSION['_old'] = ['email' => $email, 'name' => $name];
            View::redirect('/register');
        }

        $result = AuthService::register($email, $password, $name);

        if (!$result['success']) {
            $_SESSION['_errors'] = $result['errors'];
            $_SESSION['_old'] = ['email' => $email, 'name' => $name];
            View::redirect('/register');
        }

        $user = User::findById($result['user_id']);
        AuthService::createSession($user);

        $_SESSION['_flash']['success'] = 'Registration successful! Your API key has been generated.';
        View::redirect('/profile');
    }

    public function logout(): void
    {
        AuthService::logout();
        $_SESSION['_flash']['info'] = 'You have been logged out successfully.';
        View::redirect('/login');
    }

    public function showVerify2fa(): void
    {
        if (!isset($_SESSION['requires_2fa']) || !isset($_SESSION['2fa_user_id'])) {
            View::redirect('/login');
        }
        View::render('auth.verify-2fa');
    }

    public function verify2fa(): void
    {
        $code = trim($_POST['code'] ?? '');

        if (empty($code)) {
            $_SESSION['_errors']['code'][] = 'Please enter the 2FA code.';
            View::redirect('/verify-2fa');
        }

        $result = AuthService::verify2fa($code);

        if (!$result['success']) {
            $_SESSION['_errors']['code'][] = $result['error'];
            View::redirect('/verify-2fa');
        }

        $_SESSION['_flash']['success'] = '2FA successfully verified!';
        View::redirect('/dashboard');
    }

    public function showSetup2fa(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        if ($user['totp_enabled']) {
            View::redirect('/profile');
        }

        $secret = TotpService::generateSecret();
        $qrCode = TotpService::getQrCodeUrl($user['email'], $secret);

        $_SESSION['_2fa_secret'] = $secret;

        View::render('auth.setup-2fa', [
            'secret' => $secret,
            'qrCode' => $qrCode,
        ]);
    }

    public function setup2fa(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $code = trim($_POST['code'] ?? '');
        $secret = $_SESSION['_2fa_secret'] ?? '';

        if (empty($secret)) {
            $_SESSION['_errors']['code'][] = 'Session expired. Please restart.';
            View::redirect('/setup-2fa');
        }

        if (!TotpService::verifyCode($secret, $code)) {
            $_SESSION['_errors']['code'][] = 'Invalid code. Please try again.';
            View::redirect('/setup-2fa');
        }

        $recoveryCodes = TotpService::generateRecoveryCodes();

        User::update($user['id'], [
            'totp_secret' => $secret,
            'totp_enabled' => 1,
            'totp_recovery_codes' => json_encode($recoveryCodes),
        ]);

        unset($_SESSION['_2fa_secret']);

        $_SESSION['_flash']['success'] = '2FA has been successfully activated!';
        $_SESSION['_flash']['recovery_codes'] = $recoveryCodes;

        View::redirect('/profile');
    }

    public function disable2fa(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $password = $_POST['password'] ?? '';
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['_errors']['password'][] = 'Wrong password.';
            View::redirect('/profile');
        }

        User::update($user['id'], [
            'totp_secret' => null,
            'totp_enabled' => 0,
            'totp_recovery_codes' => null,
        ]);

        $_SESSION['_flash']['success'] = '2FA has been disabled.';
        View::redirect('/profile');
    }
}
