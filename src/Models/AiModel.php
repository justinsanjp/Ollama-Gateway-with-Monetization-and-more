<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class AiModel
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM ai_models WHERE id = ?');
        $stmt->execute([$id]);
        $model = $stmt->fetch();
        return $model ?: null;
    }

    public static function findByName(string $name): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM ai_models WHERE name = ?');
        $stmt->execute([$name]);
        $model = $stmt->fetch();
        return $model ?: null;
    }

    public static function getAllActive(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query(
            "SELECT * FROM ai_models WHERE status = 'active' ORDER BY display_name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function getAllVisible(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query(
            "SELECT * FROM ai_models WHERE status IN ('active', 'deactivated') ORDER BY display_name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function getAllIncludingHidden(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query(
            'SELECT * FROM ai_models ORDER BY display_name ASC'
        );
        return $stmt->fetchAll();
    }

    public static function getAll(int $page = 1, int $perPage = 50): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;

        $countStmt = $pdo->query('SELECT COUNT(*) FROM ai_models');
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM ai_models ORDER BY display_name ASC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$perPage, $offset]);
        $models = $stmt->fetchAll();

        return [
            'models' => $models,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function create(array $data): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "INSERT INTO ai_models (name, display_name, provider, input_price, output_price, context_window, internal_model, system_prompt, status, deactivation_reason, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['display_name'],
            $data['provider'] ?? 'ollama',
            $data['input_price'] ?? 0.00000015,
            $data['output_price'] ?? 0.00000090,
            $data['context_window'] ?? 8192,
            $data['internal_model'] ?? null,
            $data['system_prompt'] ?? null,
            $data['status'] ?? 'active',
            $data['deactivation_reason'] ?? null,
            $data['is_active'] ?? 1,
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
            'UPDATE ai_models SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('DELETE FROM ai_models WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function getRealOllamaNames(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query(
            "SELECT name FROM ai_models WHERE internal_model IS NULL AND is_active = 1 ORDER BY name"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
