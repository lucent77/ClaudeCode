<?php
/**
 * Authentication Middleware
 * Include this file in any protected API endpoint
 */

session_start();
require_once __DIR__ . '/../config.php';

function requireAuth() {
    // Check if session exists and is valid
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired']);
            exit;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    return $_SESSION['user_id'];
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}
