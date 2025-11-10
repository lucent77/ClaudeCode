<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, null, '권한이 없습니다.');
}

if (!isset($_FILES['tool_csv']) || $_FILES['tool_csv']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, null, '파일 업로드에 실패했습니다.');
}

$file = $_FILES['tool_csv']['tmp_name'];

try {
    $inserted = 0;
    $updated = 0;
    $errors = [];

    // Read CSV file
    if (($handle = fopen($file, 'r')) !== false) {
        // Get header row
        $headers = fgetcsv($handle);

        if ($headers === false) {
            jsonResponse(false, null, 'CSV 파일이 비어있습니다.');
        }

        // Map headers to column indices
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[trim($header)] = $index;
        }

        // Required columns
        $requiredColumns = ['Tool Code', 'Tool Name', 'Lifespan Limit'];
        foreach ($requiredColumns as $col) {
            if (!isset($headerMap[$col])) {
                jsonResponse(false, null, "필수 컬럼이 누락되었습니다: $col");
            }
        }

        dbBeginTransaction();

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($headers)) {
                continue; // Skip incomplete rows
            }

            try {
                $toolCode = trim($row[$headerMap['Tool Code']] ?? '');
                $toolName = trim($row[$headerMap['Tool Name']] ?? '');
                $categoryName = trim($row[$headerMap['Category Name']] ?? '');
                $toolSize = trim($row[$headerMap['Tool Size']] ?? '');
                $supplierName = trim($row[$headerMap['Supplier Name']] ?? '');
                $supplierModelNumber = trim($row[$headerMap['Supplier Model Number']] ?? '');
                $currentStock = intval($row[$headerMap['Current Stock']] ?? 0);
                $minimumStock = intval($row[$headerMap['Minimum Stock']] ?? 0);
                $lifespanType = strtolower(trim($row[$headerMap['Lifespan Type']] ?? 'time'));
                $lifespanLimit = floatval(str_replace(',', '', $row[$headerMap['Lifespan Limit']] ?? 0));
                $description = trim($row[$headerMap['Description']] ?? '');

                if (empty($toolCode) || empty($toolName)) {
                    continue; // Skip rows without code or name
                }

                // Validate lifespan type
                if (!in_array($lifespanType, ['time', 'cycles', 'distance'])) {
                    $lifespanType = 'time';
                }

                // Check if tool already exists
                $existing = dbQueryOne("SELECT id FROM tools WHERE tool_code = ?", [$toolCode]);

                if ($existing) {
                    // Update existing tool
                    dbExecute("
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
                            description = ?,
                            updated_at = NOW()
                        WHERE tool_code = ?
                    ", [
                        $toolName, $categoryName, $toolSize, $supplierName,
                        $supplierModelNumber, $currentStock, $minimumStock,
                        $lifespanType, $lifespanLimit, $description, $toolCode
                    ]);
                    $updated++;
                } else {
                    // Insert new tool
                    dbExecute("
                        INSERT INTO tools (
                            tool_code, tool_name, category_name, tool_size,
                            supplier_name, supplier_model_number, current_stock, minimum_stock,
                            lifespan_type, lifespan_limit, description, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
                    ", [
                        $toolCode, $toolName, $categoryName, $toolSize,
                        $supplierName, $supplierModelNumber, $currentStock, $minimumStock,
                        $lifespanType, $lifespanLimit, $description
                    ]);
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
            }
        }

        fclose($handle);
        dbCommit();

        if (!empty($errors)) {
            jsonResponse(true, [
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors
            ], "완료 (일부 오류 발생): 추가 {$inserted}개, 업데이트 {$updated}개");
        } else {
            jsonResponse(true, [
                'inserted' => $inserted,
                'updated' => $updated
            ], "공구 데이터 업로드 완료: 추가 {$inserted}개, 업데이트 {$updated}개");
        }
    } else {
        jsonResponse(false, null, 'CSV 파일을 열 수 없습니다.');
    }
} catch (Exception $e) {
    if (isset($handle)) {
        fclose($handle);
    }
    dbRollback();
    error_log($e->getMessage());
    jsonResponse(false, null, 'CSV 처리 중 오류가 발생했습니다: ' . $e->getMessage());
}
?>
