<?php

/**
 * API Routes
 * All routes here are prefixed with /api
 */

require_once __DIR__ . '/../app/Http/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Http/Controllers/CaseController.php';
require_once __DIR__ . '/../app/Http/Controllers/SyncController.php';

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\SyncController;

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api.php prefix
$path = preg_replace('#^/api\.php#', '', $path);

// Authentication routes (public)
if ($path === '/auth/login' && $method === 'POST') {
    $controller = new AuthController();
    $controller->login();
    exit;
}

if ($path === '/auth/logout' && $method === 'POST') {
    $controller = new AuthController();
    $controller->logout();
    exit;
}

if ($path === '/auth/me' && $method === 'GET') {
    $controller = new AuthController();
    $controller->me();
    exit;
}

// Sync routes
if ($path === '/sync/test' && $method === 'GET') {
    $controller = new SyncController();
    $controller->test();
    exit;
}

if ($path === '/sync' && $method === 'POST') {
    $controller = new SyncController();
    $controller->sync();
    exit;
}

if ($path === '/sync/history' && $method === 'GET') {
    $controller = new SyncController();
    $controller->history();
    exit;
}

// Case routes
if ($path === '/cases' && $method === 'GET') {
    $controller = new CaseController();
    $controller->index();
    exit;
}

if ($path === '/cases' && $method === 'POST') {
    $controller = new CaseController();
    $controller->store();
    exit;
}

if (preg_match('#^/cases/(\d+)$#', $path, $matches) && $method === 'GET') {
    $controller = new CaseController();
    $controller->show($matches[1]);
    exit;
}

if (preg_match('#^/cases/(\d+)$#', $path, $matches) && $method === 'PUT') {
    $controller = new CaseController();
    $controller->update($matches[1]);
    exit;
}

if (preg_match('#^/cases/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $controller = new CaseController();
    $controller->destroy($matches[1]);
    exit;
}

if ($path === '/cases/stats' && $method === 'GET') {
    $controller = new CaseController();
    $controller->stats();
    exit;
}

// 404 Not Found
header('Content-Type: application/json');
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint not found',
    'path' => $path,
    'method' => $method,
]);
