<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

if (($_ENV['SESSION_SECURE'] ?? 'true') === 'true') {
    ini_set('session.cookie_secure', '1');
}

use App\Router\Router;
use App\Middleware\SessionMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\ApiKeyMiddleware;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\ProfileController;
use App\Controllers\PaymentController;
use App\Controllers\SellerController;

SessionMiddleware::start();

$router = new Router();

$router->addRouteMiddleware('auth', [AuthMiddleware::class, 'required']);
$router->addRouteMiddleware('guest', [AuthMiddleware::class, 'guest']);
$router->addRouteMiddleware('admin', [AdminMiddleware::class, 'required']);

// Web routes - Auth
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/verify-2fa', [AuthController::class, 'showVerify2fa']);
$router->post('/verify-2fa', [AuthController::class, 'verify2fa']);

// Web routes - Profile
$router->get('/dashboard', function () { \App\Helpers\View::redirect('/profile'); });
$router->get('/docs', function () { require __DIR__ . '/../views/public/docs.php'; });
$router->get('/playground', function () {
    $user = \App\Models\User::findById($_SESSION['user_id']);
    $apiKey = $user['api_key'] ?? '';

    // Only show models that actually exist in Ollama
    $ollamaResp = \App\Services\OllamaService::listModels();
    $ollamaNames = [];
    foreach ($ollamaResp['models'] ?? [] as $m) {
        $ollamaNames[] = $m['id'];
    }

    $allVisible = \App\Models\AiModel::getAllVisible();
    $models = [];
    foreach ($allVisible as $m) {
        $resolvedName = !empty($m['internal_model']) ? $m['internal_model'] : $m['name'];
        if (in_array($resolvedName, $ollamaNames, true)) {
            $models[] = $m;
        }
    }

    require __DIR__ . '/../views/public/playground.php';
}, ['auth']);
$router->get('/', function () { \App\Helpers\View::redirect('/login'); });
$router->get('/profile', [ProfileController::class, 'index'], ['auth']);
$router->post('/profile', [ProfileController::class, 'updateProfile'], ['auth']);
$router->get('/profile/usage', [ProfileController::class, 'usage'], ['auth']);
$router->post('/profile/regenerate-api-key', [ProfileController::class, 'regenerateApiKey'], ['auth']);
$router->get('/setup-2fa', [AuthController::class, 'showSetup2fa'], ['auth']);
$router->post('/setup-2fa', [AuthController::class, 'setup2fa'], ['auth']);
$router->post('/disable-2fa', [AuthController::class, 'disable2fa'], ['auth']);

// Web routes - Payments
$router->get('/topup', [ProfileController::class, 'topup'], ['auth']);
$router->post('/topup', [ProfileController::class, 'processTopup'], ['auth']);
$router->post('/profile/referral/create', [ProfileController::class, 'createReferralCode'], ['auth']);

// Admin routes
$router->get('/admin', [AdminController::class, 'dashboard'], ['admin']);
$router->get('/admin/users', [AdminController::class, 'users'], ['admin']);
$router->get('/admin/users/edit/{id}', [AdminController::class, 'userEdit'], ['admin']);
$router->post('/admin/users/edit/{id}', [AdminController::class, 'userUpdate'], ['admin']);
$router->post('/admin/users/delete/{id}', [AdminController::class, 'userDelete'], ['admin']);
$router->get('/admin/users/delete/{id}', [AdminController::class, 'userDelete'], ['admin']);
$router->get('/admin/models', [AdminController::class, 'models'], ['admin']);
$router->get('/admin/models/edit/{id}', [AdminController::class, 'modelEdit'], ['admin']);
$router->post('/admin/models/edit/{id}', [AdminController::class, 'modelUpdate'], ['admin']);
$router->post('/admin/models/create', [AdminController::class, 'modelCreate'], ['admin']);
$router->get('/admin/models/clone/{id}', [AdminController::class, 'modelClone'], ['admin']);
$router->get('/admin/transactions', [AdminController::class, 'transactions'], ['admin']);
$router->get('/admin/transactions/mark-completed/{id}', [PaymentController::class, 'adminMarkTopup'], ['admin']);
$router->get('/admin/settings', [AdminController::class, 'settings'], ['admin']);
$router->post('/admin/settings', [AdminController::class, 'updateSettings'], ['admin']);
$router->get('/admin/referrals', [AdminController::class, 'referralSettings'], ['admin']);
$router->post('/admin/referrals', [AdminController::class, 'updateReferralSettings'], ['admin']);
$router->post('/admin/referrals/create', [AdminController::class, 'referralCodeCreate'], ['admin']);
$router->post('/admin/referrals/update-limit', [AdminController::class, 'referralCodeUpdateLimit'], ['admin']);
$router->get('/admin/discounts', [AdminController::class, 'discounts'], ['admin']);
$router->post('/admin/discounts/create', [AdminController::class, 'discountCreate'], ['admin']);
$router->get('/admin/discounts/delete/{id}', [AdminController::class, 'discountDelete'], ['admin']);
$router->get('/admin/gift-cards', [AdminController::class, 'giftCards'], ['admin']);
$router->post('/admin/gift-cards/create', [AdminController::class, 'giftCardCreate'], ['admin']);
$router->post('/admin/gift-cards/generate', [AdminController::class, 'giftCardGenerate'], ['admin']);
$router->get('/admin/gift-cards/delete/{id}', [AdminController::class, 'giftCardDelete'], ['admin']);

$router->get('/seller/docs', [SellerController::class, 'docs']);
$router->post('/seller/v1/gift-cards', [SellerController::class, 'generateGiftCards']);

// API routes (OpenAI-compatible)
$router->get('/v1/models', [ApiController::class, 'listModels']);
$router->get('/v1/models/{model}', [ApiController::class, 'retrieveModel']);
$router->post('/v1/chat/completions', [ApiController::class, 'chatCompletions']);
$router->post('/v1/chat/completions/{requestId}/cancel', [ApiController::class, 'cancelCompletion']);

// API key authentication for /v1/ routes
$router->addGlobalMiddleware(function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (str_starts_with($uri, '/v1/')) {
        $result = ApiKeyMiddleware::authenticate();
        if ($result !== null) {
            return $result;
        }
    }
    return null;
});

// CSRF protection for POST/PUT/DELETE web routes (not API)
$router->addGlobalMiddleware(function () {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !str_starts_with($uri, '/v1/') && !str_starts_with($uri, '/seller/')) {
        $token = $_POST['_csrf_token'] ?? '';
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        $validToken = $token ?: $header;
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if (empty($validToken) || !hash_equals($sessionToken, $validToken)) {
            http_response_code(419);
            echo json_encode(['error' => 'CSRF token mismatch']);
            exit;
        }
    }
    return null;
});

// Rate limiting for auth endpoints
$router->addGlobalMiddleware(function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($uri, ['/login', '/register', '/verify-2fa']) && $method === 'POST') {
        return \App\Middleware\RateLimitMiddleware::check(10, 60);
    }
    return null;
});

// Security headers
$router->addGlobalMiddleware(function () {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    return null;
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
