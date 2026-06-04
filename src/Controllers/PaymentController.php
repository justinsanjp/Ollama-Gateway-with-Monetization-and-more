<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\View;
use App\Models\User;
use App\Models\Transaction;
use App\Services\BillingService;

final class PaymentController
{
    public function webhookPaypal(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || !isset($payload['event_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        }

        $eventType = $payload['event_type'];

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $payload['resource'] ?? [];
            $invoiceId = $resource['invoice_id'] ?? '';
            $amount = (float) ($resource['amount']['value'] ?? 0);

            if (str_starts_with($invoiceId, 'TOPUP-') && $amount > 0) {
                $tx = Database::connect()->prepare(
                    "SELECT * FROM transactions WHERE reference = ? AND status = 'pending'"
                );
                $tx->execute([$invoiceId]);
                $transaction = $tx->fetch();

                if ($transaction) {
                    BillingService::addFunds(
                        $transaction['user_id'],
                        $amount,
                        'paypal',
                        $payload['id'] ?? ''
                    );
                }
            }
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    public function adminMarkTopup(array $params): void
    {
        $txId = (int) ($params['id'] ?? 0);

        // CSRF protection for GET-based operation
        $expected = hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'mark-topup-' . $txId);
        $provided = $_GET['_confirm'] ?? '';
        if (empty($provided) || !hash_equals($expected, $provided)) {
            $_SESSION['_flash']['error'] = 'Invalid confirmation token.';
            View::redirect('/admin/transactions');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND type = 'topup' AND status = 'pending'");
        $stmt->execute([$txId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            $_SESSION['_flash']['error'] = 'Transaction not found or already processed.';
            View::redirect('/admin/transactions');
        }

        $amount = (float) $transaction['amount'];
        $desc = $transaction['description'] ?? '';

        // Parse promo data from description (JSON appended after " | ")
        $promoData = [];
        if (str_contains($desc, ' | ')) {
            $parts = explode(' | ', $desc, 2);
            $maybeJson = $parts[1] ?? '';
            $decoded = json_decode($maybeJson, true);
            if (is_array($decoded)) {
                $promoData = $decoded;
            }
        }

        // ─── Apply discount ──────────────────────────────────────────
        $discountPercent = (float) ($promoData['discount_percent'] ?? 0);
        $discountCode = $promoData['discount_code'] ?? '';
        if ($discountPercent > 0 && $discountCode !== '') {
            $stmt = $pdo->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (max_uses = 0 OR used_count < max_uses) AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)");
            $stmt->execute([$discountCode]);
            $discount = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($discount) {
                $discountAmount = round($amount * ($discountPercent / 100), 2);
                $amount = round($amount - $discountAmount, 2);
                if ($amount < 0) {
                    $amount = 0;
                }
                $stmt = $pdo->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$discount['id']]);
                // Record discount redemption
                $stmt = $pdo->prepare("INSERT INTO discount_redemptions (discount_code_id, user_id, topup_id) VALUES (?, ?, ?)");
                $stmt->execute([$discount['id'], $transaction['user_id'], $txId]);
            }
        }

        // ─── Credit user ─────────────────────────────────────────────
        if ($amount > 0) {
            BillingService::addFunds(
                $transaction['user_id'],
                $amount,
                $transaction['payment_method'],
                $transaction['reference']
            );
        }

        $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?")
            ->execute([$txId]);

        // ─── Apply referral reward ───────────────────────────────────
        $referralCode = $promoData['referral_code'] ?? '';
        $referrerUserId = (int) ($promoData['referrer_user_id'] ?? 0);
        $refCodeId = (int) ($promoData['referral_code_id'] ?? 0);
        if ($referralCode !== '' && $referrerUserId > 0 && $refCodeId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM referral_codes WHERE id = ? AND user_id = ?");
            $stmt->execute([$refCodeId, $referrerUserId]);
            $refCodeRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($refCodeRow) {
                // Check limit
                $stmt = $pdo->prepare("SELECT s_key, s_value FROM referral_settings");
                $stmt->execute();
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $limitType = $settings['limit_type'] ?? 'unlimited';
                $rewardAmount = (float) ($settings['reward_amount'] ?? 1.00);

                $allow = true;

                // Check global limit_type
                if ($limitType !== 'unlimited') {
                    $intervalMap = [
                        'one-time' => 'ALL',
                        'weekly' => "DATETIME('now', '-7 days')",
                        'monthly' => "DATETIME('now', '-1 month')",
                        'yearly' => "DATETIME('now', '-1 year')",
                    ];
                    $sinceExpr = $intervalMap[$limitType] ?? 'ALL';
                    if ($sinceExpr === 'ALL') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_redemptions WHERE referrer_id = ? AND redeemed_by = ?");
                        $stmt->execute([$referrerUserId, $transaction['user_id']]);
                    } else {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_redemptions WHERE referrer_id = ? AND redeemed_by = ? AND created_at >= {$sinceExpr}");
                        $stmt->execute([$referrerUserId, $transaction['user_id']]);
                    }
                    if ((int) $stmt->fetchColumn() > 0) {
                        $allow = false;
                    }
                }

                // Check per-code max_redemptions
                $maxRedemptions = (int) ($refCodeRow['max_redemptions'] ?? 0);
                if ($maxRedemptions > 0 && (int) $refCodeRow['total_redemptions'] >= $maxRedemptions) {
                    $allow = false;
                }

                if ($allow && $rewardAmount > 0) {
                    // Credit referrer
                    BillingService::addFunds($referrerUserId, $rewardAmount, 'referral', "Referral: {$referralCode}");
                    $stmt = $pdo->prepare("UPDATE referral_codes SET total_redemptions = total_redemptions + 1, total_earned = total_earned + ? WHERE id = ?");
                    $stmt->execute([$rewardAmount, $refCodeRow['id']]);
                    $stmt = $pdo->prepare("INSERT INTO referral_redemptions (referrer_id, redeemed_by, ref_code_id, topup_id, reward_amount) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$referrerUserId, $transaction['user_id'], $refCodeId, $txId, $rewardAmount]);
                }
            }
        }

        $_SESSION['_flash']['success'] = 'Balance credited successfully.';
        if ($amount <= 0) {
            $_SESSION['_flash']['info'] = 'Full amount covered by discount. No balance change needed.';
        }
        View::redirect('/admin/transactions');
    }
}
