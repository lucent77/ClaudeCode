<?php
/**
 * Upload Tools CSV API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['tools_csv']) || $_FILES['tools_csv']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'No file uploaded or upload error');
    }

    $file = $_FILES['tools_csv']['tmp_name'];

    // Read CSV file
    $handle = fopen($file, 'r');
    if (!$handle) {
        jsonResponse(false, 'Could not read file');
    }

    // Read header
    $header = fgetcsv($handle);
    if (!$header) {
        jsonResponse(false, 'Invalid CSV format - no header row');
    }

    // Normalize headers (lowercase, trim spaces)
    $header = array_map(function($h) {
        return strtolower(trim($h));
    }, $header);

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $added = 0;
    $updated = 0;
    $errors = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header)) {
            continue; // Skip malformed rows
        }

        $data = array_combine($header, $row);

        // Required fields
        $toolCode = trim($data['tool code'] ?? '');
        $toolName = trim($data['tool name'] ?? '');

        if (empty($toolCode) || empty($toolName)) {
            continue; // Skip rows without required data
        }

        try {
            // Check if tool exists
            $checkStmt = $pdo->prepare("SELECT id FROM tools WHERE tool_code = ?");
            $checkStmt->execute([$toolCode]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Update existing tool
                $updateStmt = $pdo->prepare("
                    UPDATE tools SET
                        tool_name = ?,
                        category_name = ?,
                        tool_size = ?,
                        supplier_name = ?,
                        supplier_model_number = ?,
                        current_stock = ?,
                        minimum_stock = ?,
                        lifespan_type = ?,
                        lifespan_limit = ?,
                        description = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $toolName,
                    $data['category name'] ?? null,
                    $data['tool size'] ?? null,
                    $data['supplier name'] ?? null,
                    $data['supplier model number'] ?? null,
                    $data['current stock'] ?? 0,
                    $data['minimum stock'] ?? 0,
                    $data['lifespan type'] ?? 'time',
                    $data['lifespan limit'] ?? 0,
                    $data['description'] ?? null,
                    $existing['id']
                ]);

                $updated++;
            } else {
                // Insert new tool
                $insertStmt = $pdo->prepare("
                    INSERT INTO tools (
                        tool_code, tool_name, category_name, tool_size,
                        supplier_name, supplier_model_number,
                        current_stock, minimum_stock,
                        lifespan_type, lifespan_limit,
                        description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insertStmt->execute([
                    $toolCode,
                    $toolName,
                    $data['category name'] ?? null,
                    $data['tool size'] ?? null,
                    $data['supplier name'] ?? null,
                    $data['supplier model number'] ?? null,
                    $data['current stock'] ?? 0,
                    $data['minimum stock'] ?? 0,
                    $data['lifespan type'] ?? 'time',
                    $data['lifespan limit'] ?? 0,
                    $data['description'] ?? null
                ]);

                $added++;
            }
        } catch (Exception $e) {
            $errors[] = "Error processing tool '$toolCode': " . $e->getMessage();
        }
    }

    fclose($handle);
    $pdo->commit();

    $message = "Tools uploaded successfully! Added: $added, Updated: $updated";
    if (count($errors) > 0) {
        $message .= " (" . count($errors) . " errors)";
    }

    jsonResponse(true, $message, [
        'added' => $added,
        'updated' => $updated,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred while uploading');
    }
}
?>
