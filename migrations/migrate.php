<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
echo "Migrating database using driver: {$driver}\n";

try {
    if ($driver === 'sqlite') {
        $database = $_ENV['DB_DATABASE'] ?? dirname(__DIR__) . '/data/ollama_gateway.sqlite';
        $dir = dirname($database);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new PDO("sqlite:{$database}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
    } else {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? 'ollama_gateway';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $pdo = new PDO(
            "{$driver}:host={$host};port={$port};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE {$database}");
    }

    if ($driver === 'sqlite') {
        $schema = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            api_key VARCHAR(128) UNIQUE NOT NULL,
            balance DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
            role VARCHAR(10) NOT NULL DEFAULT 'user' CHECK(role IN ('user', 'admin')),
            totp_secret VARCHAR(64) DEFAULT NULL,
            totp_enabled INTEGER NOT NULL DEFAULT 0,
            totp_recovery_codes TEXT DEFAULT NULL,
            email_verified_at DATETIME DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            is_verified_seller INTEGER NOT NULL DEFAULT 0,
            seller_api_key VARCHAR(128) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE UNIQUE INDEX IF NOT EXISTS idx_users_seller_key ON users(seller_api_key);
        CREATE INDEX IF NOT EXISTS idx_users_api_key ON users(api_key);
        CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
        CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);

        CREATE TABLE IF NOT EXISTS ai_models (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            provider VARCHAR(100) NOT NULL DEFAULT 'ollama',
            input_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000015,
            output_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000090,
            context_window INTEGER NOT NULL DEFAULT 8192,
            internal_model VARCHAR(255) DEFAULT NULL,
            system_prompt TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            deactivation_reason TEXT DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_models_active ON ai_models(is_active);

        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount DECIMAL(14,6) NOT NULL,
            type VARCHAR(20) NOT NULL CHECK(type IN ('topup', 'usage', 'refund', 'admin_adjustment')),
            payment_method VARCHAR(50) DEFAULT NULL,
            reference VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed' CHECK(status IN ('pending', 'completed', 'failed', 'cancelled')),
            description TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_tx_user ON transactions(user_id);
        CREATE INDEX IF NOT EXISTS idx_tx_type ON transactions(type);
        CREATE INDEX IF NOT EXISTS idx_tx_status ON transactions(status);
        CREATE INDEX IF NOT EXISTS idx_tx_created ON transactions(created_at);

        CREATE TABLE IF NOT EXISTS api_usage (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            model_id INTEGER NOT NULL,
            tokens_input INTEGER NOT NULL DEFAULT 0,
            tokens_output INTEGER NOT NULL DEFAULT 0,
            cost DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
            endpoint VARCHAR(100) NOT NULL,
            streamed INTEGER NOT NULL DEFAULT 0,
            duration_ms INTEGER DEFAULT NULL,
            request_id VARCHAR(64) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (model_id) REFERENCES ai_models(id) ON DELETE RESTRICT
        );

        CREATE INDEX IF NOT EXISTS idx_usage_user ON api_usage(user_id);
        CREATE INDEX IF NOT EXISTS idx_usage_model ON api_usage(model_id);
        CREATE INDEX IF NOT EXISTS idx_usage_created ON api_usage(created_at);
        CREATE INDEX IF NOT EXISTS idx_usage_request ON api_usage(request_id);

        CREATE TABLE IF NOT EXISTS payment_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            method VARCHAR(50) NOT NULL UNIQUE,
            is_active INTEGER NOT NULL DEFAULT 1,
            config_data TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS referral_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            s_key VARCHAR(100) NOT NULL UNIQUE,
            s_value TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        INSERT OR IGNORE INTO referral_settings (s_key, s_value) VALUES ('limit_type', 'unlimited');
        INSERT OR IGNORE INTO referral_settings (s_key, s_value) VALUES ('reward_amount', '1.00');

        CREATE TABLE IF NOT EXISTS referral_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            code VARCHAR(32) NOT NULL UNIQUE,
            total_earned DECIMAL(14,6) NOT NULL DEFAULT 0,
            total_redemptions INTEGER NOT NULL DEFAULT 0,
            max_redemptions INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_ref_code_user ON referral_codes(user_id);

        CREATE TABLE IF NOT EXISTS referral_redemptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            referrer_id INTEGER NOT NULL,
            redeemed_by INTEGER NOT NULL,
            ref_code_id INTEGER NOT NULL,
            reward_amount DECIMAL(14,6) NOT NULL,
            topup_id INTEGER NOT NULL,
            period_start DATE DEFAULT NULL,
            period_end DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (ref_code_id) REFERENCES referral_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (topup_id) REFERENCES transactions(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_ref_red_ref ON referral_redemptions(referrer_id);
        CREATE INDEX IF NOT EXISTS idx_ref_red_redeemed ON referral_redemptions(redeemed_by);

        CREATE TABLE IF NOT EXISTS discount_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(64) NOT NULL UNIQUE,
            discount_percent DECIMAL(5,2) NOT NULL,
            max_uses INTEGER NOT NULL DEFAULT 0,
            used_count INTEGER NOT NULL DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS discount_redemptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            discount_code_id INTEGER NOT NULL,
            topup_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (topup_id) REFERENCES transactions(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_disc_red_user ON discount_redemptions(user_id);

        CREATE TABLE IF NOT EXISTS gift_cards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(64) NOT NULL UNIQUE,
            amount DECIMAL(14,6) NOT NULL,
            is_redeemed INTEGER NOT NULL DEFAULT 0,
            redeemed_by INTEGER DEFAULT NULL,
            redeemed_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE SET NULL
        );

        CREATE INDEX IF NOT EXISTS idx_gift_card_code ON gift_cards(code);
        ";
    } else {
        $schema = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            api_key VARCHAR(128) UNIQUE NOT NULL,
            balance DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
            role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            totp_secret VARCHAR(64) DEFAULT NULL,
            totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
            totp_recovery_codes TEXT DEFAULT NULL,
            email_verified_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_verified_seller TINYINT(1) NOT NULL DEFAULT 0,
            seller_api_key VARCHAR(128) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_api_key (api_key),
            INDEX idx_role (role),
            INDEX idx_active (is_active),
            UNIQUE INDEX idx_seller_key (seller_api_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS ai_models (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            provider VARCHAR(100) NOT NULL DEFAULT 'ollama',
            input_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000015,
            output_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000090,
            context_window INT NOT NULL DEFAULT 8192,
            internal_model VARCHAR(255) DEFAULT NULL,
            system_prompt TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            deactivation_reason TEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(14,6) NOT NULL,
            type ENUM('topup', 'usage', 'refund', 'admin_adjustment') NOT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            reference VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'completed',
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS api_usage (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            model_id INT NOT NULL,
            tokens_input INT NOT NULL DEFAULT 0,
            tokens_output INT NOT NULL DEFAULT 0,
            cost DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
            endpoint VARCHAR(100) NOT NULL,
            streamed TINYINT(1) NOT NULL DEFAULT 0,
            duration_ms INT DEFAULT NULL,
            request_id VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (model_id) REFERENCES ai_models(id) ON DELETE RESTRICT,
            INDEX idx_user (user_id),
            INDEX idx_model (model_id),
            INDEX idx_created (created_at),
            INDEX idx_request (request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS payment_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            method VARCHAR(50) NOT NULL UNIQUE,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            config_data JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS referral_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            s_key VARCHAR(100) NOT NULL UNIQUE,
            s_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        INSERT IGNORE INTO referral_settings (s_key, s_value) VALUES ('limit_type', 'unlimited');
        INSERT IGNORE INTO referral_settings (s_key, s_value) VALUES ('reward_amount', '1.00');

        CREATE TABLE IF NOT EXISTS referral_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(32) NOT NULL UNIQUE,
            total_earned DECIMAL(14,6) NOT NULL DEFAULT 0,
            total_redemptions INT NOT NULL DEFAULT 0,
            max_redemptions INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS referral_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            redeemed_by INT NOT NULL,
            ref_code_id INT NOT NULL,
            reward_amount DECIMAL(14,6) NOT NULL,
            topup_id INT NOT NULL,
            period_start DATE DEFAULT NULL,
            period_end DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (ref_code_id) REFERENCES referral_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (topup_id) REFERENCES transactions(id) ON DELETE CASCADE,
            INDEX idx_referrer (referrer_id),
            INDEX idx_redeemed (redeemed_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS discount_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(64) NOT NULL UNIQUE,
            discount_percent DECIMAL(5,2) NOT NULL,
            max_uses INT NOT NULL DEFAULT 0,
            used_count INT NOT NULL DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS discount_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            discount_code_id INT NOT NULL,
            topup_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (discount_code_id) REFERENCES discount_codes(id) ON DELETE CASCADE,
            FOREIGN KEY (topup_id) REFERENCES transactions(id) ON DELETE CASCADE,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS gift_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(64) NOT NULL UNIQUE,
            amount DECIMAL(14,6) NOT NULL,
            is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
            redeemed_by INT DEFAULT NULL,
            redeemed_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (redeemed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }

    $statements = explode(';', $schema);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "  ✓ Tables created\n";

    // Seed payment config
    $configs = [
        ['paypal', 1, json_encode(['email' => '', 'enabled' => true])],
        ['crypto', 1, json_encode(['address' => '', 'currency' => 'USDT', 'network' => 'ERC20', 'enabled' => true])],
        ['queue', 0, json_encode(['enabled' => false, 'max_concurrent' => 2])],
    ];

    foreach ($configs as $cfg) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payment_config WHERE method = ?");
        $stmt->execute([$cfg[0]]);
        if ((int) $stmt->fetchColumn() === 0) {
            $stmt = $pdo->prepare("INSERT INTO payment_config (method, is_active, config_data) VALUES (?, ?, ?)");
            $stmt->execute($cfg);
        }
    }

    // Create admin user (must be set in .env)
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';
    $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? '';

    if (empty($adminEmail) || empty($adminPassword)) {
        echo "  ! ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env\n";
        exit(1);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ((int) $stmt->fetchColumn() === 0) {
        $hash = password_hash($adminPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
        $apiKey = 'og_' . bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            "INSERT INTO users (email, password_hash, name, api_key, role, balance)
             VALUES (?, ?, 'Admin', ?, 'admin', 0.000000)"
        );
        $stmt->execute([$adminEmail, $hash, $apiKey]);
        echo "  ✓ Admin user created: {$adminEmail}\n";
        echo "  ✓ API Key: {$apiKey}\n";
    } else {
        echo "  - Admin user already exists: {$adminEmail}\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
