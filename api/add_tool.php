<?php
/**
 * Add Tool API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $toolCode = $input['tool_code'] ?? null;
    $toolName = $input['tool_name'] ?? null;

    if (!$toolCode || !$toolName) {
        jsonResponse(false, 'Tool code and name are required');
    }

    $pdo = getDbConnection();

    // Check if tool code already exists
    $checkStmt = $pdo->prepare("SELECT id FROM tools WHERE tool_code = ?");
    $checkStmt->execute([$toolCode]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, 'Tool code already exists');
    }

    $stmt = $pdo->prepare("
        INSERT INTO tools (
            tool_code, tool_name, category_name, tool_size,
            supplier_name, supplier_model_number,
            current_stock, minimum_stock,
            lifespan_type, lifespan_limit,
            status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $toolCode,
        $toolName,
        $input['category_name'] ?? null,
        $input['tool_size'] ?? null,
        $input['supplier_name'] ?? null,
        $input['supplier_model_number'] ?? null,
        $input['current_stock'] ?? 0,
        $input['minimum_stock'] ?? 0,
        $input['lifespan_type'] ?? 'time',
        $input['lifespan_limit'] ?? 0,
        $input['status'] ?? 'new',
        $input['description'] ?? null
    ]);

    jsonResponse(true, 'Tool added successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
