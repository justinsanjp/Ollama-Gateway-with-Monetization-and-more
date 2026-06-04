<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Usage;
use PDO;

final class BillingService
{
    public static function calculateCost(int $tokensInput, int $tokensOutput, float $inputPrice, float $outputPrice): float
    {
        return ($tokensInput * $inputPrice) + ($tokensOutput * $outputPrice);
    }

    public static function checkBalance(int $userId, float $estimatedCost): array
    {
        $balance = User::getBalance($userId);

        if ($balance < $estimatedCost) {
            return [
                'sufficient' => false,
                'balance' => $balance,
                'required' => $estimatedCost,
                'shortfall' => $estimatedCost - $balance,
            ];
        }

        return [
            'sufficient' => true,
            'balance' => $balance,
            'required' => $estimatedCost,
            'shortfall' => 0,
        ];
    }

    public static function deductAndRecord(array $params): bool
    {
        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?'
            );
            $stmt->execute([$params['cost'], $params['user_id'], $params['cost']]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }

            Usage::record([
                'user_id' => $params['user_id'],
                'model_id' => $params['model_id'],
                'tokens_input' => $params['tokens_input'],
                'tokens_output' => $params['tokens_output'],
                'cost' => $params['cost'],
                'endpoint' => $params['endpoint'],
                'streamed' => $params['streamed'] ?? 0,
                'duration_ms' => $params['duration_ms'] ?? null,
                'request_id' => $params['request_id'] ?? null,
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function addFunds(int $userId, float $amount, string $paymentMethod, string $reference = null): int
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            User::updateBalance($userId, $amount);

            $txId = Transaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'topup',
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'status' => 'completed',
                'description' => "Guthaben-Aufladung via {$paymentMethod}",
            ]);

            $pdo->commit();
            return $txId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function requestId(): string
    {
        return 'req_' . bin2hex(random_bytes(16));
    }
}
