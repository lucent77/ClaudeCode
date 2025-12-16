<?php
/**
 * API: Admin Logout
 * POST /api/logout.php
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

logoutAdmin();

jsonResponse([
    'success' => true,
    'message' => 'Logged out successfully'
]);
