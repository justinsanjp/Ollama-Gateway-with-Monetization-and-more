<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Transaction
{
    public static function create(array $data): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, amount, type, payment_method, reference, status, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['amount'],
            $data['type'],
            $data['payment_method'] ?? null,
            $data['reference'] ?? null,
            $data['status'] ?? 'completed',
            $data['description'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ?');
        $countStmt->execute([$userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $perPage, $offset]);

        return [
            'transactions' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function getAll(int $page = 1, int $perPage = 30, string $type = ''): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($type) {
            $where = 'WHERE t.type = ?';
            $params[] = $type;
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.*, u.email as user_email, u.name as user_name
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                {$where}
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'transactions' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }
}
