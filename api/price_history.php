<?php
/**
 * Price History API
 * GET: Get price history for a product
 * Query params: ?product_id=123&limit=30
 */

require_once __DIR__ . '/auth/middleware.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$productId = intval($_GET['product_id'] ?? 0);
$limit = intval($_GET['limit'] ?? 30);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid product ID is required']);
    exit;
}

try {
    $db = getDB();

    // Verify product ownership
    $stmt = $db->prepare("SELECT id, name FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Get price history
    $stmt = $db->prepare("
        SELECT price, checked_at
        FROM price_history
        WHERE product_id = ?
        ORDER BY checked_at DESC
        LIMIT ?
    ");
    $stmt->execute([$productId, $limit]);
    $history = $stmt->fetchAll();

    // Reverse to show oldest first (for charts)
    $history = array_reverse($history);

    // Calculate statistics
    $prices = array_column($history, 'price');
    $stats = [
        'count' => count($prices),
        'current' => !empty($prices) ? end($prices) : null,
        'average' => !empty($prices) ? round(array_sum($prices) / count($prices), 2) : null,
        'lowest' => !empty($prices) ? min($prices) : null,
        'highest' => !empty($prices) ? max($prices) : null
    ];

    echo json_encode([
        'success' => true,
        'product' => $product,
        'history' => $history,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Price History Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch price history']);
}
