<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Models\User;
use PDO;

final class SellerController
{
    public function docs(): void
    {
        require __DIR__ . '/../../views/public/seller-docs.php';
        exit;
    }

    public function generateGiftCards(): void
    {
        $apiKey = $_SERVER['HTTP_X_SELLER_KEY'] ?? '';

        if (empty($apiKey)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing seller API key. Provide via X-Seller-Key header.']);
            return;
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE seller_api_key = ? AND is_verified_seller = 1 AND is_active = 1");
        $stmt->execute([$apiKey]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seller) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or inactive seller API key.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON body.']);
            return;
        }

        $validAmounts = [5, 10, 15, 25, 50];
        $amount = (int) ($input['amount'] ?? 0);
        $count = max(1, (int) ($input['count'] ?? 1));

        if (!in_array($amount, $validAmounts, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid amount. Allowed: ' . implode(', ', $validAmounts) . ' USD.']);
            return;
        }

        if ($count > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 100 gift cards per request.']);
            return;
        }

        $totalCost = $amount * $count;
        $balance = (float) $seller['balance'];

        if ($balance < $totalCost) {
            http_response_code(402);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Insufficient balance.',
                'required' => $totalCost,
                'available' => $balance,
                'shortfall' => $totalCost - $balance,
            ]);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Deduct balance
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $stmt->execute([(float) $totalCost, (int) $seller['id'], (float) $totalCost]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                http_response_code(402);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Insufficient balance.']);
                return;
            }

            // Generate gift cards
            $stmt = $pdo->prepare("INSERT INTO gift_cards (code, amount) VALUES (?, ?)");
            $cards = [];
            $created = 0;

            for ($i = 0; $i < $count * 2; $i++) {
                if ($created >= $count) break;
                $hex = bin2hex(random_bytes(8));
                $code = implode('-', str_split($hex, 4));
                try {
                    $stmt->execute([$code, (float) $amount]);
                    $cards[] = [
                        'code' => $code,
                        'amount' => $amount,
                        'currency' => 'USD',
                    ];
                    $created++;
                } catch (\Exception $e) {
                    // skip duplicates
                }
            }

            $pdo->commit();

            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$seller['id']]);
            $remainingBalance = (float) $stmt->fetchColumn();

            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'gift_cards' => $cards,
                'total_cost' => $totalCost,
                'balance_remaining' => $remainingBalance,
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error.']);
        }
    }
}
