<?php
/**
 * Delete Product API
 * DELETE: id
 */

require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$productId = intval($input['id'] ?? 0);

// Validation
if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid product ID is required']);
    exit;
}

try {
    $db = getDB();

    // Verify product ownership
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$productId, $userId]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Delete product (cascade will handle price_history)
    $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$productId, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);

} catch (PDOException $e) {
    error_log("Delete Product Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}
