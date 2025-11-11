<?php
/**
 * Update Tool API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $toolId = $input['tool_id'] ?? null;

    if (!$toolId) {
        jsonResponse(false, 'Tool ID is required');
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        UPDATE tools SET
            tool_code = ?,
            tool_name = ?,
            category_name = ?,
            tool_size = ?,
            supplier_name = ?,
            supplier_model_number = ?,
            current_stock = ?,
            minimum_stock = ?,
            lifespan_type = ?,
            lifespan_limit = ?,
            status = ?,
            description = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $input['tool_code'],
        $input['tool_name'],
        $input['category_name'] ?? null,
        $input['tool_size'] ?? null,
        $input['supplier_name'] ?? null,
        $input['supplier_model_number'] ?? null,
        $input['current_stock'] ?? 0,
        $input['minimum_stock'] ?? 0,
        $input['lifespan_type'] ?? 'time',
        $input['lifespan_limit'] ?? 0,
        $input['status'] ?? 'new',
        $input['description'] ?? null,
        $toolId
    ]);

    jsonResponse(true, 'Tool updated successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
