<?php
/**
 * CREODENT Integrated Work Management System
 * Main Entry Point
 *
 * All requests are routed through this file
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Start session
use App\Core\Session;
Session::start();

// Initialize router
use App\Core\Router;
use App\Middleware\AuthMiddleware;

$router = new Router();

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

$router->get('/', function() {
    if (Session::isLoggedIn()) {
        Router::redirect('/dashboard');
    } else {
        Router::redirect('/login');
    }
});

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->any('/logout', 'AuthController@logout');

// ============================================================================
// Authenticated Routes
// ============================================================================

// Dashboard
$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);
$router->get('/api/dashboard/stats', 'DashboardController@getStats', [AuthMiddleware::class]);

// Cases
$router->get('/cases', 'CaseController@index', [AuthMiddleware::class]);
$router->get('/cases/{id}', 'CaseController@show', [AuthMiddleware::class]);

// API - Cases
$router->get('/api/cases', 'CaseController@getCases', [AuthMiddleware::class]);
$router->get('/api/cases/{id}', 'CaseController@getCase', [AuthMiddleware::class]);
$router->post('/api/cases', 'CaseController@create', [AuthMiddleware::class]);
$router->post('/api/cases/{id}', 'CaseController@update', [AuthMiddleware::class]);
$router->post('/api/cases/assign-item', 'CaseController@assignItem', [AuthMiddleware::class]);
$router->post('/api/cases/update-item-status', 'CaseController@updateItemStatus', [AuthMiddleware::class]);
$router->get('/api/cases/statistics', 'CaseController@getStatistics', [AuthMiddleware::class]);
$router->get('/api/cases/search', 'CaseController@search', [AuthMiddleware::class]);
$router->get('/api/cases/overdue', 'CaseController@getOverdue', [AuthMiddleware::class]);

// Import
$router->get('/import', 'ImportController@index', [AuthMiddleware::class]);
$router->post('/api/import/evolution', 'ImportController@importFromEvolution', [AuthMiddleware::class]);
$router->post('/api/import/single-case', 'ImportController@importSingleCase', [AuthMiddleware::class]);
$router->get('/api/import/history', 'ImportController@getHistory', [AuthMiddleware::class]);
$router->get('/api/import/test-connection', 'ImportController@testConnection', [AuthMiddleware::class]);

// Admin
$router->get('/admin/users', 'AdminController@users', [AuthMiddleware::class]);
$router->get('/admin/departments', 'AdminController@departments', [AuthMiddleware::class]);

// API - Admin
$router->get('/api/admin/users', 'AdminController@getUsers', [AuthMiddleware::class]);
$router->get('/api/admin/users/{id}', 'AdminController@getUser', [AuthMiddleware::class]);
$router->post('/api/admin/users', 'AdminController@createUser', [AuthMiddleware::class]);
$router->post('/api/admin/users/{id}', 'AdminController@updateUser', [AuthMiddleware::class]);
$router->delete('/api/admin/users/{id}', 'AdminController@deleteUser', [AuthMiddleware::class]);
$router->get('/api/admin/departments', 'AdminController@getDepartments', [AuthMiddleware::class]);
$router->get('/api/admin/users/{id}/productivity', 'AdminController@getUserProductivity', [AuthMiddleware::class]);

// User Profile
$router->get('/api/auth/me', 'AuthController@me', [AuthMiddleware::class]);
$router->post('/api/auth/change-password', 'AuthController@changePassword', [AuthMiddleware::class]);

// ============================================================================
// External API Endpoints (for VB.NET integration, etc.)
// ============================================================================

$router->post('/api/external/case-import', function() {
    // This endpoint can be used by external systems (VB.NET, etc.)
    // Requires API token authentication

    header('Content-Type: application/json');

    try {
        // Check API token
        $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;

        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'API token required']);
            return;
        }

        // Verify token
        $db = App\Core\Database::getInstance();
        $tokenData = $db->queryOne(
            "SELECT * FROM api_tokens WHERE token = ? AND status = 'active'",
            [$token]
        );

        if (!$tokenData) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid API token']);
            return;
        }

        // Update last used
        $db->execute("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$tokenData['id']]);

        // Get request data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        // Import case
        $importService = new App\Services\ImportService();
        $caseId = $importService->importSingleCase($data);

        echo json_encode([
            'success' => true,
            'data' => ['case_id' => $caseId],
            'message' => 'Case imported successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
});

// ============================================================================
// Dispatch Request
// ============================================================================

try {
    $router->dispatch();
} catch (Exception $e) {
    http_response_code(500);

    if ($config['app']['debug']) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
    }
}
