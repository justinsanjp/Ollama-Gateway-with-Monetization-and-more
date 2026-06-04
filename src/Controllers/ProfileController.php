<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\View;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Usage;
use PDO;

final class ProfileController
{
    public function index(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $usage = Usage::getTotalByUser($user['id']);
        $lastTransactions = Transaction::getByUser($user['id'], 1, 10);

        // Referral info
        $pdo = \App\Config\Database::connect();
        $stmt = $pdo->prepare("SELECT code, total_redemptions AS redeemed_count, max_redemptions FROM referral_codes WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $referralCode = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_redemptions WHERE referrer_id = ?");
        $stmt->execute([$user['id']]);
        $referralCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(reward_amount), 0) FROM referral_redemptions WHERE referrer_id = ?");
        $stmt->execute([$user['id']]);
        $referralEarnings = (float) $stmt->fetchColumn();

        View::render('profile.index', [
            'user' => $user,
            'usage' => $usage,
            'transactions' => $lastTransactions['transactions'],
            'referralCode' => $referralCode,
            'referralCount' => $referralCount,
            'referralEarnings' => $referralEarnings,
        ]);
    }

    public function usage(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $page = (int) ($_GET['page'] ?? 1);
        $result = Usage::getByUser($user['id'], $page, 25);

        View::render('profile.usage', [
            'usage' => $result,
        ]);
    }

    public function updateProfile(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $_SESSION['_errors']['name'][] = 'Name must not be empty.';
            View::redirect('/profile');
        }

        User::update($user['id'], ['name' => $name]);
        $_SESSION['user_name'] = $name;

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        if (!empty($newPassword)) {
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $_SESSION['_errors']['current_password'][] = 'Current password is incorrect.';
                View::redirect('/profile');
            }

            if ($newPassword !== $newPasswordConfirm) {
                $_SESSION['_errors']['new_password'][] = 'New passwords do not match.';
                View::redirect('/profile');
            }

            if (strlen($newPassword) < 8) {
                $_SESSION['_errors']['new_password'][] = 'Password must be at least 8 characters long.';
                View::redirect('/profile');
            }

            $hash = password_hash($newPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
            User::update($user['id'], ['password_hash' => $hash]);
        }

        $_SESSION['_flash']['success'] = 'Profile updated successfully.';
        View::redirect('/profile');
    }

    public function regenerateApiKey(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $password = $_POST['password'] ?? '';
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['_errors']['password'][] = 'Falsches Passwort.';
            View::redirect('/profile');
        }

        $newKey = User::generateApiKey();
        User::update($user['id'], ['api_key' => $newKey]);

        $_SESSION['_flash']['success'] = 'API key has been regenerated. Old key is invalid.';
        View::redirect('/profile');
    }

    public function topup(): void
    {
        View::render('profile.topup');
    }

    public function createReferralCode(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id FROM referral_codes WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        if ($stmt->fetch()) {
            $_SESSION['_flash']['info'] = 'You already have a referral code.';
            View::redirect('/profile');
        }

        $code = 'REF-' . strtoupper(substr(md5($user['id'] . $user['email'] . random_bytes(4)), 0, 8));
        $stmt = $pdo->prepare("INSERT INTO referral_codes (user_id, code) VALUES (?, ?)");
        $stmt->execute([$user['id'], $code]);

        $_SESSION['_flash']['success'] = 'Referral code generated: ' . $code;
        View::redirect('/profile');
    }

    public function processTopup(): void
    {
        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user) {
            View::redirect('/login');
        }

        $method = $_POST['method'] ?? '';
        $amount = (float) ($_POST['amount'] ?? 0);

        $allowedMethods = ['paypal', 'crypto'];
        if (!in_array($method, $allowedMethods)) {
            $_SESSION['_errors']['method'][] = 'Invalid payment method.';
            View::redirect('/topup');
        }

        $amounts = [5, 10, 20, 50, 100, 250, 500, 1000];
        if (!in_array($amount, $amounts) && $amount <= 0) {
            $_SESSION['_errors']['amount'][] = 'Invalid amount.';
            View::redirect('/topup');
        }

        $pdo = Database::connect();

        // ─── Process gift card ────────────────────────────────────────
        $giftCardCode = strtoupper(trim($_POST['gift_card_code'] ?? ''));
        $giftCardAmount = 0.0;
        if ($giftCardCode !== '') {
            $stmt = $pdo->prepare("SELECT * FROM gift_cards WHERE code = ? AND is_redeemed = 0 AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)");
            $stmt->execute([$giftCardCode]);
            $giftCard = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$giftCard) {
                $_SESSION['_errors']['gift_card_code'][] = 'Invalid or already redeemed gift card.';
                View::redirect('/topup');
            }
            // Credit user immediately
            User::updateBalance($user['id'], (float) $giftCard['amount']);
            $stmt = $pdo->prepare("UPDATE gift_cards SET is_redeemed = 1, redeemed_by = ?, redeemed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user['id'], $giftCard['id']]);
            $giftCardAmount = (float) $giftCard['amount'];
            Transaction::create([
                'user_id' => $user['id'],
                'amount' => $giftCardAmount,
                'type' => 'topup',
                'payment_method' => 'gift_card',
                'reference' => $giftCardCode,
                'status' => 'completed',
                'description' => "Gift Card - {$giftCardCode}",
            ]);
            $_SESSION['_flash']['success'] = 'Gift card redeemed! $' . number_format($giftCardAmount, 2) . ' credited to your balance.';
            if ($amount <= 0) {
                View::redirect('/topup');
            }
        }

        $paymentRef = 'TOPUP-' . strtoupper(bin2hex(random_bytes(8)));

        // ─── Gather promo codes for the pending transaction ──────────
        $promoData = [];
        $discountCode = strtoupper(trim($_POST['discount_code'] ?? ''));
        if ($discountCode !== '') {
            $stmt = $pdo->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (max_uses = 0 OR used_count < max_uses) AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)");
            $stmt->execute([$discountCode]);
            $discount = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$discount) {
                $_SESSION['_errors']['discount_code'][] = 'Invalid or expired discount code.';
                View::redirect('/topup');
            }
            $promoData['discount_code'] = $discountCode;
            $promoData['discount_percent'] = (float) $discount['discount_percent'];
        }

        $referralCode = strtoupper(trim($_POST['referral_code'] ?? ''));
        if ($referralCode !== '') {
            $stmt = $pdo->prepare("SELECT * FROM referral_codes WHERE code = ?");
            $stmt->execute([$referralCode]);
            $refCode = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$refCode) {
                $_SESSION['_errors']['referral_code'][] = 'Invalid referral code.';
                View::redirect('/topup');
            }
            if ((int) $refCode['user_id'] === (int) $user['id']) {
                $_SESSION['_errors']['referral_code'][] = 'You cannot use your own referral code.';
                View::redirect('/topup');
            }
            $promoData['referral_code'] = $referralCode;
            $promoData['referrer_user_id'] = (int) $refCode['user_id'];
            $promoData['referral_code_id'] = (int) $refCode['id'];
        }

        if ($method === 'paypal') {
            $stmt = $pdo->prepare("SELECT config_data FROM payment_config WHERE method = 'paypal' AND is_active = 1");
            $stmt->execute();
            $config = json_decode($stmt->fetchColumn() ?: '{}', true);

            if (empty($config['email'])) {
                $_SESSION['_flash']['error'] = 'PayPal is not configured yet.';
                View::redirect('/topup');
            }

            if (!empty($promoData)) {
                $desc = 'PayPal Top-Up | ' . json_encode($promoData);
            } else {
                $desc = 'PayPal Top-Up';
            }

            Transaction::create([
                'user_id' => $user['id'],
                'amount' => $amount,
                'type' => 'topup',
                'payment_method' => 'paypal',
                'reference' => $paymentRef,
                'status' => 'pending',
                'description' => $desc,
            ]);

            $_SESSION['_flash']['info'] = 'Please send $' . number_format($amount, 2, '.', ',') .
                ' via PayPal to ' . htmlspecialchars($config['email']) .
                '. Use reference: ' . $paymentRef .
                ' After receipt of payment, your balance will be credited.';

        } elseif ($method === 'crypto') {
            $stmt = $pdo->prepare("SELECT config_data FROM payment_config WHERE method = 'crypto' AND is_active = 1");
            $stmt->execute();
            $config = json_decode($stmt->fetchColumn() ?: '{}', true);

            if (empty($config['address'])) {
                $_SESSION['_flash']['error'] = 'Crypto payments are not configured yet.';
                View::redirect('/topup');
            }

            $currency = $config['currency'] ?? 'USDT';

            if (!empty($promoData)) {
                $desc = 'Crypto Top-Up - ' . $currency . ' | ' . json_encode($promoData);
            } else {
                $desc = 'Crypto Top-Up - ' . $currency;
            }

            Transaction::create([
                'user_id' => $user['id'],
                'amount' => $amount,
                'type' => 'topup',
                'payment_method' => 'crypto',
                'reference' => $paymentRef,
                'status' => 'pending',
                'description' => $desc,
            ]);

            $_SESSION['_flash']['info'] = 'Please send $' . number_format($amount, 2, '.', ',') .
                ' in ' . $currency . ' to: ' . htmlspecialchars($config['address']) .
                ' (Network: ' . ($config['network'] ?? 'ERC20') . ')' .
                '. Reference: ' . $paymentRef .
                ' After transaction confirmation, your balance will be credited.';
        }

        View::redirect('/topup');
    }
}
