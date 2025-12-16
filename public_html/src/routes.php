<?php
/**
 * API Routes Definition
 *
 * All API endpoints are defined here
 */

// Health check endpoint
$router->get('/api/health', function () {
    Router::success([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'php_version' => PHP_VERSION,
    ], 'System is running');
});

// Root endpoint
$router->get('/', function () {
    Router::success([
        'name' => 'Design Confirm System',
        'description' => 'Dental Case Management API',
        'version' => '1.0.0',
        'endpoints' => [
            'health' => '/api/health',
            'auth' => '/api/auth/*',
            'users' => '/api/users/*',
            'clients' => '/api/clients/*',
            'cases' => '/api/cases/*',
            'products' => '/api/products/*',
            'templates' => '/api/templates/*',
        ],
    ], 'Welcome to Design Confirm System API');
});

// ============================================
// Authentication Routes
// ============================================
$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->get('/api/auth/me', 'AuthController@me');

// ============================================
// User Routes
// ============================================
$router->get('/api/users', 'UserController@index');
$router->post('/api/users', 'UserController@store');
$router->get('/api/users/{id}', 'UserController@show');
$router->put('/api/users/{id}', 'UserController@update');
$router->delete('/api/users/{id}', 'UserController@destroy');

// ============================================
// Client Routes
// ============================================
$router->get('/api/clients', 'ClientController@index');
$router->post('/api/clients', 'ClientController@store');
$router->get('/api/clients/{id}', 'ClientController@show');
$router->put('/api/clients/{id}', 'ClientController@update');
$router->delete('/api/clients/{id}', 'ClientController@destroy');

// ============================================
// Case Routes
// ============================================
$router->get('/api/cases', 'CaseController@index');
$router->post('/api/cases', 'CaseController@store');
$router->get('/api/cases/products', 'CaseController@products');
$router->get('/api/cases/stats', 'CaseController@stats');
$router->get('/api/cases/{id}', 'CaseController@show');
$router->put('/api/cases/{id}', 'CaseController@update');
$router->delete('/api/cases/{id}', 'CaseController@destroy');
$router->post('/api/cases/{id}/send-email', 'CaseController@sendEmail');
$router->post('/api/cases/{id}/status', 'CaseController@updateStatus');

// ============================================
// Product Routes
// ============================================
$router->get('/api/products', 'ProductController@index');
$router->post('/api/products', 'ProductController@store');
$router->get('/api/products/{id}', 'ProductController@show');
$router->put('/api/products/{id}', 'ProductController@update');
$router->delete('/api/products/{id}', 'ProductController@destroy');

// ============================================
// Email Template Routes
// ============================================
$router->get('/api/templates', 'TemplateController@index');
$router->post('/api/templates', 'TemplateController@store');
$router->get('/api/templates/{id}', 'TemplateController@show');
$router->put('/api/templates/{id}', 'TemplateController@update');
$router->delete('/api/templates/{id}', 'TemplateController@destroy');

// ============================================
// Notification Routes
// ============================================
$router->post('/api/notifications/test-email', 'NotificationController@testEmail');
$router->post('/api/notifications/test-sms', 'NotificationController@testSms');
$router->post('/api/notifications/test-slack', 'NotificationController@testSlack');

// ============================================
// 404 Handler
// ============================================
$router->notFound(function () {
    Router::error('Endpoint not found. Please check the URL and try again.', 404);
});
