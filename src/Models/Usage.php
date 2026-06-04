<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Usage
{
    public static function record(array $data): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO api_usage (user_id, model_id, tokens_input, tokens_output, cost, endpoint, streamed, duration_ms, request_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['model_id'],
            $data['tokens_input'] ?? 0,
            $data['tokens_output'] ?? 0,
            $data['cost'] ?? 0.000000,
            $data['endpoint'],
            $data['streamed'] ?? 0,
            $data['duration_ms'] ?? null,
            $data['request_id'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM api_usage WHERE user_id = ?');
        $countStmt->execute([$userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT u.*, m.display_name as model_name
             FROM api_usage u
             JOIN ai_models m ON u.model_id = m.id
             WHERE u.user_id = ?
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $perPage, $offset]);

        return [
            'usage' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function getTotalByUser(int $userId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT
                COUNT(*) as total_requests,
                COALESCE(SUM(tokens_input), 0) as total_tokens_input,
                COALESCE(SUM(tokens_output), 0) as total_tokens_output,
                COALESCE(SUM(cost), 0) as total_cost
             FROM api_usage WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function getStats(): array
    {
        $pdo = Database::connect();
        $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        $dateCondition = $driver === 'sqlite'
            ? "created_at >= datetime('now', '-30 days')"
            : 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';

        $stmt = $pdo->query(
            "SELECT
                COUNT(*) as total_requests,
                COALESCE(SUM(tokens_input), 0) as total_tokens_input,
                COALESCE(SUM(tokens_output), 0) as total_tokens_output,
                COALESCE(SUM(cost), 0) as total_cost,
                COUNT(DISTINCT user_id) as active_users
             FROM api_usage
             WHERE {$dateCondition}"
        );
        return $stmt->fetch();
    }

    public static function getRecent(int $limit = 10): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT u.*, m.display_name as model_name, us.email as user_email, us.name as user_name
             FROM api_usage u
             JOIN ai_models m ON u.model_id = m.id
             JOIN users us ON u.user_id = us.id
             ORDER BY u.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
