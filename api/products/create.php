<?php
/**
 * Create Product API
 * POST: name, store, url, target_price
 */

require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

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
$name = trim($input['name'] ?? '');
$store = trim($input['store'] ?? '');
$url = trim($input['url'] ?? '');
$targetPrice = floatval($input['target_price'] ?? 0);

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Product name is required';
}

if (empty($store)) {
    $errors[] = 'Store is required';
}

if (empty($url)) {
    $errors[] = 'Product URL is required';
} elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
    $errors[] = 'Invalid URL format';
}

if ($targetPrice <= 0) {
    $errors[] = 'Target price must be greater than 0';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $db = getDB();

    // Insert product
    $stmt = $db->prepare("
        INSERT INTO products (user_id, name, store, url, target_price)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $name, $store, $url, $targetPrice]);

    $productId = $db->lastInsertId();

    // Get the created product
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'product' => $product
    ]);

} catch (PDOException $e) {
    error_log("Create Product Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create product']);
}
