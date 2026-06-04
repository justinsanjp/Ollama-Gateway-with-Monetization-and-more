<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

final class User
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByApiKey(string $apiKey): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE api_key = ? AND is_active = 1');
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, name, api_key, role, balance)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            $data['password_hash'],
            $data['name'],
            $data['api_key'],
            $data['role'] ?? 'user',
            $data['balance'] ?? 0.000000,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::connect();
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $stmt = $pdo->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);
    }

    public static function updateBalance(int $id, float $amount): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'UPDATE users SET balance = balance + ? WHERE id = ?'
        );
        $stmt->execute([$amount, $id]);
    }

    public static function deductBalance(int $id, float $amount): bool
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?'
        );
        $stmt->execute([$amount, $id, $amount]);

        return $stmt->rowCount() > 0;
    }

    public static function getBalance(int $id): float
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public static function generateApiKey(): string
    {
        return 'og_' . bin2hex(random_bytes(32));
    }

    public static function getAll(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search) {
            $where = 'WHERE email LIKE ? OR name LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare(
            "SELECT id, email, name, api_key, balance, role, totp_enabled, is_active, is_verified_seller, created_at, updated_at
             FROM users {$where}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }
}
