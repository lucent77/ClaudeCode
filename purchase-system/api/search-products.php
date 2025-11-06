<?php
/**
 * Product Search API
 * Returns product suggestions for autocomplete
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short', 'products' => []]);
    exit();
}

$db = Database::getInstance();

try {
    $searchParam = "%{$query}%";

    $products = $db->fetchAll(
        "SELECT p.id, p.product_name, p.default_vendor_id, p.avg_price, v.vendor_name
         FROM products p
         LEFT JOIN vendors v ON p.default_vendor_id = v.id
         WHERE p.product_name LIKE ?
         ORDER BY p.total_purchases DESC, p.product_name ASC
         LIMIT 10",
        [$searchParam]
    );

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("Product search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Search failed',
        'products' => []
    ]);
}
?>
