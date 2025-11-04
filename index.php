<?php

/**
 * CREODENT Integrated Work Management System
 * Main Entry Point
 *
 * All requests are routed through this file
 */

// Error reporting (will be overridden by config)
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Define base path
define('BASE_PATH', __DIR__);

// Load configuration
$config = require BASE_PATH . '/config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'America/New_York');

// Set error reporting based on environment
if ($config['app']['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/storage/logs/php_errors.log');
}

// Load Composer autoloader
if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    die('Composer autoloader not found. Please run: composer install');
}
require BASE_PATH . '/vendor/autoload.php';

// Import core classes
use App\Core\Router;
use App\Core\Session;
use App\Core\Database;

// Start session
Session::start();

// Initialize database connection (will be created as singleton)
try {
    Database::getInstance($config['database']);
} catch (\PDOException $e) {
    // Database connection failed
    if ($config['app']['debug']) {
        die('Database Connection Failed: ' . $e->getMessage());
    } else {
        die('Database connection error. Please contact support.');
    }
}

// Initialize router
$router = new Router();

// Define routes
// =============================================================================

// Authentication Routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// Dashboard (requires authentication)
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// Case Management Routes
$router->get('/cases', 'CaseController@index');
$router->get('/cases/create', 'CaseController@create');
$router->post('/cases', 'CaseController@store');
$router->get('/cases/{id}', 'CaseController@show');
$router->post('/cases/{id}', 'CaseController@update');
$router->post('/cases/{id}/delete', 'CaseController@delete');
$router->post('/cases/{id}/archive', 'CaseController@archive');
$router->post('/cases/{id}/assign', 'CaseController@assign');

// Case Items
$router->post('/cases/{id}/items', 'CaseController@addItem');
$router->post('/cases/{caseId}/items/{itemId}', 'CaseController@updateItem');
$router->post('/cases/{caseId}/items/{itemId}/delete', 'CaseController@deleteItem');

// API Routes (JSON responses)
$router->get('/api/cases', 'CaseController@apiList');
$router->post('/api/cases/fetch-evolution', 'CaseController@fetchEvolutionCase');
$router->get('/api/dashboard/stats', 'DashboardController@apiStats');

// Import Routes
$router->get('/imports', 'ImportController@index');
$router->post('/imports/execute', 'ImportController@execute');
$router->get('/imports/results', 'ImportController@results');
$router->post('/imports/test-connection', 'ImportController@testConnection');
$router->get('/api/imports/history', 'ImportController@apiHistory');

// Windows App Integration API
$router->post('/api/import/3dprint', 'WindowsAppImportController@import3DPrint');
$router->post('/api/import/cocr', 'WindowsAppImportController@importCoCr');
$router->post('/api/import/solidex', 'WindowsAppImportController@importSolidex');

// Admin Routes (super_admin, admin only)
$router->get('/admin/users', 'AdminController@users');
$router->get('/admin/users/create', 'AdminController@createUser');
$router->post('/admin/users', 'AdminController@storeUser');
$router->get('/admin/users/{id}/edit', 'AdminController@editUser');
$router->post('/admin/users/{id}', 'AdminController@updateUser');
$router->post('/admin/users/{id}/delete', 'AdminController@deleteUser');
$router->post('/admin/users/{id}/toggle-status', 'AdminController@toggleUserStatus');

// Settings
$router->get('/settings', 'SettingsController@index');
$router->post('/settings/password', 'SettingsController@changePassword');
$router->post('/settings/profile', 'SettingsController@updateProfile');

// Audit Logs (admin only)
$router->get('/audit', 'AuditController@index');
$router->get('/audit/case/{id}', 'AuditController@caseHistory');

// =============================================================================

// Get the requested URL
$url = $_GET['url'] ?? '';

// Dispatch the request
try {
    $router->dispatch($url);
} catch (\Exception $e) {
    // Handle routing errors
    if ($config['app']['debug']) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
        echo '<p>An error occurred. Please try again later.</p>';
    }
}
