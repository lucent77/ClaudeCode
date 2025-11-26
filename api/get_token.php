<?php
/**
 * Magic Rx Scanner - Get CSRF Token
 *
 * Provides CSRF token for frontend requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/csrf_protection.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed'
    ]);
    exit;
}

try {
    $token = CSRFProtection::getToken();

    echo json_encode([
        'success' => true,
        'token' => $token,
        'token_name' => CSRFProtection::getTokenName(),
        'expires_in' => 3600 // 1 hour
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate token'
    ]);
}
