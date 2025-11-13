<?php
/**
 * Push Notification Subscription API
 * POST: subscription object with endpoint, keys
 */

require_once __DIR__ . '/auth/middleware.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['subscription']) || !is_array($input['subscription'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid subscription data']);
    exit;
}

$subscription = $input['subscription'];
$endpoint = $subscription['endpoint'] ?? '';
$p256dh = $subscription['keys']['p256dh'] ?? '';
$auth = $subscription['keys']['auth'] ?? '';

// Validation
if (empty($endpoint) || empty($p256dh) || empty($auth)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing subscription data']);
    exit;
}

try {
    $db = getDB();

    // Check if this subscription already exists for this user
    $stmt = $db->prepare("SELECT id FROM subscribers WHERE user_id = ? AND endpoint = ?");
    $stmt->execute([$userId, $endpoint]);

    if ($stmt->fetch()) {
        // Already subscribed
        echo json_encode([
            'success' => true,
            'message' => 'Already subscribed'
        ]);
        exit;
    }

    // Insert new subscription
    $stmt = $db->prepare("
        INSERT INTO subscribers (user_id, endpoint, p256dh, auth)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $endpoint, $p256dh, $auth]);

    echo json_encode([
        'success' => true,
        'message' => 'Push notification subscription saved'
    ]);

} catch (PDOException $e) {
    error_log("Push Subscribe Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save subscription']);
}
