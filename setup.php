<?php

declare(strict_types=1);

echo "╔══════════════════════════════════════════╗\n";
echo "║       Ollama Gateway – Setup              ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// ─── Check PHP ───────────────────────────────────────────────────────
$requiredVersion = '8.3.0';
if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
    echo "✗ PHP {$requiredVersion}+ required (running " . PHP_VERSION . ")\n";
    exit(1);
}
echo "✓ PHP " . PHP_VERSION . "\n";

// ─── Check extensions ────────────────────────────────────────────────
$extensions = ['pdo', 'pdo_sqlite', 'mbstring', 'json', 'session', 'random', 'curl'];
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "✗ Missing PHP extension: {$ext}\n";
        exit(1);
    }
}
echo "✓ All required PHP extensions present\n";

// ─── Check writable directories ──────────────────────────────────────
$dirs = ['data', 'vendor'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!is_writable($dir)) {
        echo "✗ Directory not writable: {$dir}\n";
        exit(1);
    }
}
echo "✓ Directories writable\n";

// ─── Check composer ──────────────────────────────────────────────────
if (!file_exists('vendor/autoload.php')) {
    echo "\n! vendor/ not found. Running composer install...\n";
    passthru('composer install --no-interaction --prefer-dist', $exitCode);
    if ($exitCode !== 0) {
        echo "✗ Composer install failed. Run 'composer install' manually.\n";
        exit(1);
    }
    echo "✓ Composer dependencies installed\n";
} else {
    echo "✓ Composer dependencies present\n";
}

// ─── Create .env if not exists ───────────────────────────────────────
if (file_exists('.env')) {
    echo "✓ .env already exists\n";
} else {
    echo "\n─── Configuration ───\n";
    if (file_exists('.env.example')) {
        $example = file_get_contents('.env.example');
        file_put_contents('.env', $example);
        echo "✓ .env created from .env.example\n";
        echo "! Please edit .env to configure your installation.\n";
    }
}

// ─── Check .env values ───────────────────────────────────────────────
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';
$adminPassword = $_ENV['ADMIN_PASSWORD'] ?? '';

$requiredEnv = ['ADMIN_EMAIL', 'ADMIN_PASSWORD'];
$missing = [];
foreach ($requiredEnv as $key) {
    if (empty($_ENV[$key])) {
        $missing[] = $key;
    }
}

    if (!empty($missing)) {
        echo "\n! Required environment variables not set: " . implode(', ', $missing) . "\n";
        echo "! Please set these values in .env and re-run setup.\n";
        echo "  (Or run: php setup.php)\n";
        exit(1);
    }

// ─── Run migration ───────────────────────────────────────────────────
echo "\n─── Database Migration ───\n";
require_once __DIR__ . '/migrations/migrate.php';
echo "✓ Migration complete\n";

// ─── Done ────────────────────────────────────────────────────────────
echo "\n╔══════════════════════════════════════════╗\n";
echo "║       Setup complete!                   ║\n";
echo "╚══════════════════════════════════════════╝\n\n";
echo "Next steps:\n";
echo "  1. Edit .env with your settings (database, Ollama URL, etc.)\n";
echo "  2. Point your web server to the public/ directory\n";
echo "  3. Or run: php -S localhost:8000 -t public\n";
echo "\n";
