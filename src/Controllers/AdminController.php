<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\View;
use App\Models\User;
use App\Models\AiModel;
use App\Models\Transaction;
use App\Models\Usage;
use App\Services\OllamaService;
use PDO;

final class AdminController
{
    public function dashboard(): void
    {
        $pdo = Database::connect();

        $totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $activeUsers = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
        $totalModels = $pdo->query('SELECT COUNT(*) FROM ai_models WHERE is_active = 1')->fetchColumn();

        $usageStats = Usage::getStats();

        $recentUsage = Usage::getRecent(15);

        $recentUsers = $pdo->query(
            'SELECT id, email, name, balance, created_at FROM users ORDER BY created_at DESC LIMIT 5'
        )->fetchAll();

        $totalRevenue = $pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'topup' AND status = 'completed'"
        )->fetchColumn();

        View::render('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalModels' => $totalModels,
            'usageStats' => $usageStats,
            'recentUsage' => $recentUsage,
            'recentUsers' => $recentUsers,
            'totalRevenue' => (float) $totalRevenue,
        ], 'admin');
    }

    public function users(array $params): void
    {
        $page = (int) ($params['page'] ?? 1);
        $search = $_GET['search'] ?? '';

        $result = User::getAll($page, 20, $search);

        View::render('admin.users', $result, 'admin');
    }

    public function userEdit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $user = User::findById($id);

        if (!$user) {
            $_SESSION['_flash']['error'] = 'User not found.';
            View::redirect('/admin/users');
        }

        $usage = Usage::getTotalByUser($id);
        $transactions = Transaction::getByUser($id, 1, 10);

        View::render('admin.user-edit', [
            'user' => $user,
            'usage' => $usage,
            'transactions' => $transactions['transactions'],
        ], 'admin');
    }

    public function userUpdate(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $user = User::findById($id);

        if (!$user) {
            $_SESSION['_flash']['error'] = 'User not found.';
            View::redirect('/admin/users');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isVerifiedSeller = isset($_POST['is_verified_seller']) ? 1 : 0;
        $balanceAdjustment = (float) ($_POST['balance_adjustment'] ?? 0);
        $balanceNote = trim($_POST['balance_note'] ?? '');

        if (empty($name) || empty($email)) {
            $_SESSION['_flash']['error'] = 'Name and email are required.';
            View::redirect('/admin/users/edit/' . $id);
        }

        $updateData = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'is_active' => $isActive,
            'is_verified_seller' => $isVerifiedSeller,
        ];

        // Auto-generate seller API key when enabling seller
        if ($isVerifiedSeller && empty($user['seller_api_key'])) {
            $updateData['seller_api_key'] = 'slr_' . bin2hex(random_bytes(32));
        }
        if (!$isVerifiedSeller) {
            $updateData['seller_api_key'] = null;
        }

        User::update($id, $updateData);

        if ($balanceAdjustment > 0) {
            $pdo = \App\Config\Database::connect();
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM transactions
                 WHERE user_id = ? AND type = 'admin_adjustment' AND amount > 0
                 AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')"
            );
            $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
            if ($driver !== 'sqlite') {
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM transactions
                     WHERE user_id = ? AND type = 'admin_adjustment' AND amount > 0
                     AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
                );
            }
            $stmt->execute([$id]);
            $monthlyGiven = (float) $stmt->fetchColumn();

            $monthlyLimit = 1000.00;
            $remaining = $monthlyLimit - $monthlyGiven;

            if ($balanceAdjustment > $remaining) {
                $_SESSION['_flash']['error'] = "Monthly admin credit limit reached. Max \${$monthlyLimit}/user/month. Remaining this month: \$" . number_format($remaining, 2, '.', ',');
                View::redirect('/admin/users/edit/' . $id);
            }
        }

        if ($balanceAdjustment != 0) {
            User::updateBalance($id, $balanceAdjustment);
            Transaction::create([
                'user_id' => $id,
                'amount' => $balanceAdjustment,
                'type' => 'admin_adjustment',
                'payment_method' => 'admin',
                'description' => $balanceNote ?: 'Admin balance adjustment',
                'status' => 'completed',
            ]);
        }

        $_SESSION['_flash']['success'] = 'User updated successfully.';
        View::redirect('/admin/users/edit/' . $id);
    }

    public function userDelete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $expected = hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'user-delete-' . $id);
        $provided = $_GET['_confirm'] ?? '';
        if (empty($provided) || !hash_equals($expected, $provided)) {
            $_SESSION['_flash']['error'] = 'Invalid confirmation token.';
            View::redirect('/admin/users');
        }

        $user = User::findById($id);

        if (!$user) {
            $_SESSION['_flash']['error'] = 'User not found.';
            View::redirect('/admin/users');
        }

        if ($user['role'] === 'admin') {
            $_SESSION['_flash']['error'] = 'Admin users cannot be deleted.';
            View::redirect('/admin/users');
        }

        $pdo = \App\Config\Database::connect();
        $pdo->prepare('DELETE FROM api_usage WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM transactions WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        $_SESSION['_flash']['success'] = 'User deleted successfully.';
        View::redirect('/admin/users');
    }

    public function models(array $params): void
    {
        $pdo = Database::connect();

        // Fetch all DB models (including hidden and deactivated)
        $allDb = $pdo->query("SELECT * FROM ai_models ORDER BY display_name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $dbByName = [];
        foreach ($allDb as $m) {
            $dbByName[$m['name']] = $m;
        }

        // Fetch Ollama models and merge
        $ollamaModels = OllamaService::listModels();
        $merged = [];
        $seen = [];
        foreach ($ollamaModels['models'] ?? [] as $m) {
            $name = $m['id'];
            $seen[$name] = true;
            if (isset($dbByName[$name])) {
                $merged[] = $dbByName[$name];
            } else {
                $merged[] = [
                    'id' => 0,
                    'name' => $name,
                    'display_name' => $name,
                    'provider' => 'ollama',
                    'input_price' => 0.00000015,
                    'output_price' => 0.00000090,
                    'context_window' => 8192,
                    'internal_model' => null,
                    'system_prompt' => null,
                    'status' => 'active',
                    'deactivation_reason' => null,
                    'is_active' => true,
                    'created_at' => null,
                    'updated_at' => null,
                ];
            }
        }

        // Append any DB models not matched by Ollama (e.g. ghost models, deconfigured models)
        foreach ($allDb as $m) {
            if (!isset($seen[$m['name']])) {
                $merged[] = $m;
            }
        }

        $result = ['models' => $merged, 'page' => 1, 'total' => count($merged), 'perPage' => 100];

        View::render('admin.models', $result, 'admin');
    }

    public function modelEdit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id === 0) {
            $name = trim($_GET['name'] ?? '');
            if (empty($name)) {
                $_SESSION['_flash']['error'] = 'Model name is required.';
                View::redirect('/admin/models');
            }
            $model = AiModel::findByName($name);
            if (!$model) {
                $model = [
                    'id' => 0,
                    'name' => $name,
                    'display_name' => $name,
                    'input_price' => 0.00000015,
                    'output_price' => 0.00000090,
                    'context_window' => 8192,
                    'internal_model' => null,
                    'system_prompt' => null,
                    'status' => 'active',
                    'deactivation_reason' => null,
                    'is_active' => true,
                    'provider' => 'ollama',
                ];
            }
        } else {
            $model = AiModel::findById($id);
            if (!$model) {
                $_SESSION['_flash']['error'] = 'Model not found.';
                View::redirect('/admin/models');
            }
        }

        $ollamaList = OllamaService::listModels();
        $ollamaModelNames = [];
        foreach ($ollamaList['models'] ?? [] as $m) {
            $ollamaModelNames[] = $m['id'];
        }

        View::render('admin.model-edit', [
            'model' => $model,
            'ollamaModels' => $ollamaModelNames,
        ], 'admin');
    }

    public function modelUpdate(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $name = trim($_POST['name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $inputPrice = (float) str_replace(',', '.', $_POST['input_price'] ?? '0');
        $outputPrice = (float) str_replace(',', '.', $_POST['output_price'] ?? '0');
        $contextWindow = (int) ($_POST['context_window'] ?? 8192);
        $internalModel = !empty($_POST['internal_model']) ? trim($_POST['internal_model']) : null;
        $systemPrompt = !empty($_POST['system_prompt']) ? trim($_POST['system_prompt']) : null;
        $status = $_POST['status'] ?? 'active';
        $deactivationReason = null;

        if ($status === 'deactivated') {
            $reasonType = $_POST['deactivation_reason_type'] ?? 'custom';
            $predefinedReasons = [
                'load' => 'This model is temporarily unavailable due to high demand. Please retry your request later. We appreciate your patience.',
                'exclusive' => 'This model is restricted to authorized members only. If you believe you should have access, please contact our support team to verify your account permissions.',
                'unavailable' => 'This model is currently not available. We apologize for the inconvenience. Please select an alternative model from the available models list.',
                'maintenance' => 'This model is currently undergoing scheduled maintenance to improve performance and reliability. Please try again later. We appreciate your patience.',
            ];
            if ($reasonType === 'custom') {
                $customText = trim($_POST['deactivation_reason_custom'] ?? '');
                if (empty($customText)) {
                    $_SESSION['_flash']['error'] = 'Please provide a deactivation reason or select a predefined option.';
                    View::redirect('/admin/models/edit/' . $id);
                    return;
                }
                $deactivationReason = 'This model has been temporarily deactivated. Reason/Info: ' . $customText;
            } elseif (isset($predefinedReasons[$reasonType])) {
                $deactivationReason = $predefinedReasons[$reasonType];
            }
        }

        $isActive = ($status === 'active') ? 1 : 0;

        if (empty($name)) {
            $_SESSION['_flash']['error'] = 'Model name is required.';
            View::redirect('/admin/models');
        }

        $data = [
            'name' => $name,
            'display_name' => $displayName,
            'input_price' => $inputPrice,
            'output_price' => $outputPrice,
            'context_window' => $contextWindow,
            'internal_model' => $internalModel,
            'system_prompt' => $systemPrompt,
            'status' => $status,
            'deactivation_reason' => $deactivationReason,
            'is_active' => $isActive,
        ];

        if ($id === 0) {
            AiModel::create($data);
        } else {
            $model = AiModel::findById($id);
            if (!$model) {
                $_SESSION['_flash']['error'] = 'Model not found.';
                View::redirect('/admin/models');
            }
            AiModel::update($id, $data);
        }

        $_SESSION['_flash']['success'] = 'Model saved successfully.';
        View::redirect('/admin/models');
    }

    public function modelCreate(): void
    {
        $name = trim($_POST['name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $inputPrice = (float) str_replace(',', '.', $_POST['input_price'] ?? '0.00000015');
        $outputPrice = (float) str_replace(',', '.', $_POST['output_price'] ?? '0.00000090');
        $contextWindow = (int) ($_POST['context_window'] ?? 8192);

        if (empty($name) || empty($displayName)) {
            $_SESSION['_flash']['error'] = 'Name and display name are required.';
            View::redirect('/admin/models');
        }

        AiModel::create([
            'name' => $name,
            'display_name' => $displayName,
            'input_price' => $inputPrice,
            'output_price' => $outputPrice,
            'context_window' => $contextWindow,
        ]);

        $_SESSION['_flash']['success'] = 'Model created successfully.';
        View::redirect('/admin/models');
    }

    public function modelClone(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $expected = hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'model-clone-' . $id);
        $provided = $_GET['_confirm'] ?? '';
        if (empty($provided) || !hash_equals($expected, $provided)) {
            $_SESSION['_flash']['error'] = 'Invalid confirmation token.';
            View::redirect('/admin/models');
        }

        $source = AiModel::findById($id);

        if (!$source) {
            $_SESSION['_flash']['error'] = 'Source model not found.';
            View::redirect('/admin/models');
        }

        $newName = $source['name'] . '-copy';
        $suffix = 1;
        while (AiModel::findByName($newName)) {
            $suffix++;
            $newName = $source['name'] . '-copy' . $suffix;
        }

        $newId = AiModel::create([
            'name' => $newName,
            'display_name' => ($source['display_name'] ?: $source['name']) . ' (Clone)',
            'input_price' => (float) $source['input_price'],
            'output_price' => (float) $source['output_price'],
            'context_window' => (int) $source['context_window'],
            'provider' => $source['provider'] ?? 'ollama',
            'status' => 'active',
            'is_active' => 1,
            'internal_model' => null,
            'system_prompt' => null,
        ]);

        $_SESSION['_flash']['success'] = 'Model cloned. Edit the copy now.';
        View::redirect('/admin/models/edit/' . $newId);
    }

    public function transactions(array $params): void
    {
        $page = (int) ($params['page'] ?? 1);
        $type = $_GET['type'] ?? '';

        $result = Transaction::getAll($page, 30, $type);

        View::render('admin.transactions', $result, 'admin');
    }

    public function settings(): void
    {
        $pdo = Database::connect();
        $paymentConfigs = $pdo->query("SELECT * FROM payment_config WHERE method != 'queue' ORDER BY method")->fetchAll();
        $queueRow = $pdo->query("SELECT * FROM payment_config WHERE method = 'queue'")->fetch(PDO::FETCH_ASSOC);
        $queueData = $queueRow ? json_decode($queueRow['config_data'] ?: '{}', true) : [];

        View::render('admin.settings', [
            'paymentConfigs' => $paymentConfigs,
            'queueConfig' => $queueRow ?: ['is_active' => 0],
            'queueData' => $queueData,
        ], 'admin');
    }

    public function updateSettings(): void
    {
        $pdo = Database::connect();

        $methods = ['paypal', 'crypto'];
        foreach ($methods as $method) {
            $config = $_POST[$method] ?? [];
            $configData = [
                'enabled' => isset($config['enabled']),
            ];

            if ($method === 'paypal') {
                $configData['email'] = $config['email'] ?? '';
            }
            if ($method === 'crypto') {
                $configData['address'] = $config['address'] ?? '';
                $configData['currency'] = $config['currency'] ?? 'USDT';
                $configData['network'] = $config['network'] ?? 'ERC20';
            }

            $stmt = $pdo->prepare(
                'UPDATE payment_config SET config_data = ?, is_active = ? WHERE method = ?'
            );
            $stmt->execute([
                json_encode($configData),
                isset($config['enabled']) ? 1 : 0,
                $method,
            ]);
        }

        $queueConfig = $_POST['queue'] ?? [];
        $stmt = $pdo->prepare(
            'UPDATE payment_config SET config_data = ?, is_active = ? WHERE method = ?'
        );
        $stmt->execute([
            json_encode([
                'enabled' => isset($queueConfig['enabled']),
                'max_concurrent' => (int) ($queueConfig['max_concurrent'] ?? 2),
            ]),
            isset($queueConfig['enabled']) ? 1 : 0,
            'queue',
        ]);

        $_SESSION['_flash']['success'] = 'Settings updated successfully.';
        View::redirect('/admin/settings');
    }

    // ─── Referral Settings ────────────────────────────────────────────

    public function referralSettings(): void
    {
        $pdo = Database::connect();
        $rows = $pdo->query("SELECT s_key, s_value FROM referral_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $codes = $pdo->query("SELECT rc.*, u.name AS user_name FROM referral_codes rc JOIN users u ON rc.user_id = u.id ORDER BY rc.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin.referrals', ['settings' => $rows, 'codes' => $codes], 'admin');
    }

    public function updateReferralSettings(): void
    {
        $pdo = Database::connect();
        $fields = ['limit_type', 'reward_amount'];
        foreach ($fields as $key) {
            $val = trim($_POST[$key] ?? '');
            $stmt = $pdo->prepare("UPDATE referral_settings SET s_value = ?, updated_at = CURRENT_TIMESTAMP WHERE s_key = ?");
            $stmt->execute([$val, $key]);
        }
        $_SESSION['_flash']['success'] = 'Referral settings saved.';
        View::redirect('/admin/referrals');
    }

    public function referralCodeCreate(): void
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $maxRedemptions = max(0, (int) ($_POST['max_redemptions'] ?? 0));

        if ($userId <= 0) {
            $_SESSION['_flash']['error'] = 'Invalid user ID.';
            View::redirect('/admin/referrals');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $_SESSION['_flash']['error'] = 'User not found.';
            View::redirect('/admin/referrals');
        }

        $code = 'REF-' . strtoupper(substr(md5($userId . random_bytes(4)), 0, 8));
        $stmt = $pdo->prepare("INSERT INTO referral_codes (user_id, code, max_redemptions) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $code, $maxRedemptions]);

        $_SESSION['_flash']['success'] = "Referral code {$code} created (limit: " . ($maxRedemptions ?: 'unlimited') . ').';
        View::redirect('/admin/referrals');
    }

    public function referralCodeUpdateLimit(): void
    {
        $id = (int) ($_POST['code_id'] ?? 0);
        $maxRedemptions = max(0, (int) ($_POST['max_redemptions'] ?? 0));

        $pdo = Database::connect();
        $pdo->prepare("UPDATE referral_codes SET max_redemptions = ? WHERE id = ?")->execute([$maxRedemptions, $id]);
        $_SESSION['_flash']['success'] = 'Referral code limit updated.';
        View::redirect('/admin/referrals');
    }

    // ─── Discount Codes ───────────────────────────────────────────────

    public function discounts(): void
    {
        $pdo = Database::connect();
        $codes = $pdo->query("SELECT * FROM discount_codes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin.discounts', ['codes' => $codes], 'admin');
    }

    public function discountCreate(): void
    {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $percent = (float) ($_POST['discount_percent'] ?? 0);
        $maxUses = (int) ($_POST['max_uses'] ?? 0);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($code) || $percent <= 0 || $percent > 100) {
            $_SESSION['_flash']['error'] = 'Invalid code or discount percent (1-100).';
            View::redirect('/admin/discounts');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO discount_codes (code, discount_percent, max_uses, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code, $percent, $maxUses, $expiresAt]);
        $_SESSION['_flash']['success'] = 'Discount code created.';
        View::redirect('/admin/discounts');
    }

    public function discountDelete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $expected = hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'discount-delete-' . $id);
        $provided = $_GET['_confirm'] ?? '';
        if (empty($provided) || !hash_equals($expected, $provided)) {
            $_SESSION['_flash']['error'] = 'Invalid confirmation token.';
            View::redirect('/admin/discounts');
        }

        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM discount_codes WHERE id = ?")->execute([$id]);
        $_SESSION['_flash']['success'] = 'Discount code deleted.';
        View::redirect('/admin/discounts');
    }

    // ─── Gift Cards ───────────────────────────────────────────────────

    public function giftCards(): void
    {
        $pdo = Database::connect();
        $cards = $pdo->query("SELECT g.*, u.name AS redeemed_name FROM gift_cards g LEFT JOIN users u ON g.redeemed_by = u.id ORDER BY g.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin.gift-cards', ['cards' => $cards], 'admin');
    }

    public function giftCardCreate(): void
    {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $amount = (float) ($_POST['amount'] ?? 0);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($code) || $amount <= 0) {
            $_SESSION['_flash']['error'] = 'Invalid code or amount.';
            View::redirect('/admin/gift-cards');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO gift_cards (code, amount, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$code, $amount, $expiresAt]);
        $_SESSION['_flash']['success'] = 'Gift card created.';
        View::redirect('/admin/gift-cards');
    }

    public function giftCardGenerate(): void
    {
        $count = max(1, (int) ($_POST['count'] ?? 1));
        $amount = (float) ($_POST['amount'] ?? 0);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if ($amount <= 0) {
            $_SESSION['_flash']['error'] = 'Invalid amount.';
            View::redirect('/admin/gift-cards');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO gift_cards (code, amount, expires_at) VALUES (?, ?, ?)");
        $created = 0;
        for ($i = 0; $i < $count * 2; $i++) { // allow duplicates, try up to 2x
            if ($created >= $count) break;
            $code = strtoupper(bin2hex(random_bytes(8)));
            $formatted = implode('-', str_split($code, 4));
            try {
                $stmt->execute([$formatted, $amount, $expiresAt]);
                $created++;
            } catch (\Exception $e) {
                // duplicate, skip
            }
        }
        $_SESSION['_flash']['success'] = "{$created} gift card(s) created.";
        View::redirect('/admin/gift-cards');
    }

    public function giftCardDelete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $expected = hash('sha256', ($_SESSION['_csrf_token'] ?? '') . 'giftcard-delete-' . $id);
        $provided = $_GET['_confirm'] ?? '';
        if (empty($provided) || !hash_equals($expected, $provided)) {
            $_SESSION['_flash']['error'] = 'Invalid confirmation token.';
            View::redirect('/admin/gift-cards');
        }

        $pdo = Database::connect();
        $pdo->prepare("DELETE FROM gift_cards WHERE id = ?")->execute([$id]);
        $_SESSION['_flash']['success'] = 'Gift card deleted.';
        View::redirect('/admin/gift-cards');
    }
}
