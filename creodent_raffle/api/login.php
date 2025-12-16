<?php
/**
 * API: Admin Login
 * POST /api/login.php
 *
 * Body: { "pin": "2025" }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$input = getJsonInput();

if (empty($input['pin'])) {
    jsonError('PIN is required');
}

if (authenticateAdmin($input['pin'])) {
    jsonResponse([
        'success' => true,
        'message' => 'Authentication successful'
    ]);
} else {
    jsonError('Invalid PIN', 401);
}
