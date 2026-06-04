CREATE DATABASE IF NOT EXISTS ollama_gateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ollama_gateway;

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    provider VARCHAR(100) NOT NULL DEFAULT 'ollama',
    input_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000015,
    output_price DECIMAL(10,8) NOT NULL DEFAULT 0.00000090,
    context_window INT NOT NULL DEFAULT 8192,
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

INSERT INTO payment_config (method, is_active, config_data) VALUES
('paypal', 1, '{"email": "", "enabled": true}'),
('crypto', 1, '{"address": "", "currency": "USDT", "network": "ERC20", "enabled": true}'),
('paysafecard', 0, '{"enabled": false}');

