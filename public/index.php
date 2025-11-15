<?php

/**
 * Web Entry Point
 * This file handles web requests and serves the frontend
 */

// Get the request path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API requests should go to api.php
if (strpos($path, '/api') === 0) {
    require_once __DIR__ . '/api.php';
    exit;
}

// Serve static files
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let PHP's built-in server handle static files
}

// All other requests serve the main dashboard
$viewsPath = __DIR__ . '/../resources/views';

// Simple routing
if ($path === '/' || $path === '/dashboard') {
    require_once $viewsPath . '/dashboard.php';
    exit;
}

if ($path === '/login') {
    require_once $viewsPath . '/login.php';
    exit;
}

if (preg_match('#^/cases/(\d+)$#', $path, $matches)) {
    require_once $viewsPath . '/case-detail.php';
    exit;
}

// 404 Not Found
http_response_code(404);
echo "404 - Page Not Found";
