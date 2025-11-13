<?php
/**
 * Read Products API
 * GET: Get all user products or single product by ID
 * Query params: ?id=123 (optional, for single product)
 */

require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    $productId = $_GET['id'] ?? null;

    if ($productId) {
        // Get single product
        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM price_history WHERE product_id = p.id) as price_count,
                   (SELECT MIN(price) FROM price_history WHERE product_id = p.id) as lowest_price,
                   (SELECT MAX(price) FROM price_history WHERE product_id = p.id) as highest_price
            FROM products p
            WHERE p.id = ? AND p.user_id = ?
        ");
        $stmt->execute([$productId, $userId]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'product' => $product
        ]);

    } else {
        // Get all user products
        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM price_history WHERE product_id = p.id) as price_count,
                   (SELECT price FROM price_history WHERE product_id = p.id ORDER BY checked_at DESC LIMIT 1) as latest_price
            FROM products p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        $products = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count' => count($products),
            'products' => $products
        ]);
    }

} catch (PDOException $e) {
    error_log("Read Products Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch products']);
}
