<?php
/**
 * Creodent Anonymous Voice - Front Controller
 *
 * All requests are routed through this file.
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Load autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Set error display based on environment
if ($_ENV['APP_DEBUG'] ?? false) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('UTC');

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

// Initialize CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load database configuration
$db = require BASE_PATH . '/config/database.php';

// Load and execute router
$router = new App\Middleware\Router($db);
$router->dispatch();
