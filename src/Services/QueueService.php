<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;

final class QueueService
{
    public static function isEnabled(): bool
    {
        return (bool) self::getConfig('enabled', false);
    }

    public static function maxConcurrent(): int
    {
        return (int) self::getConfig('max_concurrent', 2);
    }

    public static function getPosition(): ?int
    {
        $data = self::getQueueData();
        return $data ? $data['position'] : null;
    }

    public static function getQueueData(): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        $pdo = Database::connect();

        $window = 30;
        $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM api_usage WHERE created_at > datetime('now', ? || ' seconds')"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM api_usage WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
        }
        $stmt->execute([-$window]);
        $activeCount = (int) $stmt->fetchColumn();

        $max = self::maxConcurrent();

        if ($activeCount < $max) {
            return null;
        }

        return [
            'position' => $activeCount - $max + 1,
            'total' => $activeCount,
        ];
    }

    private static function getConfig(string $key, mixed $default = null): mixed
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT config_data FROM payment_config WHERE method = 'queue'");
        $stmt->execute();
        $row = $stmt->fetchColumn();
        if (!$row) {
            return $default;
        }
        $data = json_decode($row, true) ?? [];
        return $data[$key] ?? $default;
    }
}