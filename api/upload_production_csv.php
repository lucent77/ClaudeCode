<?php
/**
 * Upload Production CSV API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['production_csv']) || $_FILES['production_csv']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'No file uploaded or upload error');
    }

    $clearExisting = isset($_POST['clear_existing']) && $_POST['clear_existing'] === 'on';

    $file = $_FILES['production_csv']['tmp_name'];

    // Create upload log
    $logDir = dirname(__DIR__) . '/logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . 'csv_upload_' . date('Y-m-d_H-i-s') . '.log';

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

    // Normalize headers
    $header = array_map(function($h) {
        return strtolower(trim(str_replace([' ', '.'], ['_', ''], $h)));
    }, $header);

    // Map common header variations
    $headerMap = [
        'lot_no' => 'lot_no',
        'lot__no' => 'lot_no',
        'model_name' => 'model_name',
        'cnc_run_time' => 'cnc_run_time',
        'cnc_runtime' => 'cnc_run_time',
        'working_time' => 'working_time',
        'plan' => 'plan_count',
        'plan_count' => 'plan_count',
        'unit_total' => 'unit_total',
        '%' => 'total_achievement',
        'total_achievement' => 'total_achievement'
    ];

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Clear existing data if requested
    if ($clearExisting) {
        $pdo->exec("DELETE FROM production_records");
        file_put_contents($logFile, "Cleared all existing production records\n", FILE_APPEND);
    }

    $processed = 0;
    $errors = [];
    $lineNum = 1;

    // Get equipment mapping
    $equipmentMap = [];
    $stmt = $pdo->query("SELECT id, equipment_code FROM equipment");
    while ($row = $stmt->fetch()) {
        $equipmentMap[$row['equipment_code']] = $row['id'];
    }

    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;

        if (count($row) !== count($header)) {
            $errors[] = "Line $lineNum: Column count mismatch";
            continue;
        }

        $data = array_combine($header, $row);

        // Parse date
        $dateStr = trim($data['date'] ?? '');
        if (empty($dateStr)) {
            $errors[] = "Line $lineNum: Missing date";
            continue;
        }

        // Try to parse date (supports MM-DD or YYYY-MM-DD)
        if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $dateStr, $matches)) {
            $recordDate = date('Y') . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateStr)) {
            $recordDate = $dateStr;
        } else {
            $errors[] = "Line $lineNum: Invalid date format '$dateStr'";
            continue;
        }

        // Get equipment
        $cncCode = strtoupper(trim($data['cnc'] ?? ''));
        if (empty($cncCode) || !isset($equipmentMap[$cncCode])) {
            $errors[] = "Line $lineNum: Invalid or missing CNC code '$cncCode'";
            continue;
        }

        $equipmentId = $equipmentMap[$cncCode];

        try {
            // Extract data
            $dateObj = new DateTime($recordDate);
            $month = $dateObj->format('n');
            $week = $dateObj->format('W');

            // Insert or update record
            $checkStmt = $pdo->prepare("
                SELECT id FROM production_records
                WHERE equipment_id = ? AND record_date = ?
            ");
            $checkStmt->execute([$equipmentId, $recordDate]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Update
                $updateStmt = $pdo->prepare("
                    UPDATE production_records SET
                        month = ?, week = ?, model_name = ?, sku = ?, lot_no = ?,
                        worker_name = ?, cnc_run_time = ?, plan_count = ?,
                        unit_total = ?, total_achievement = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $month,
                    $week,
                    $data['model_name'] ?? null,
                    $data['sku'] ?? null,
                    $data['lot_no'] ?? $data['lot__no'] ?? null,
                    $data['worker'] ?? null,
                    $data['cnc_run_time'] ?? $data['cnc_runtime'] ?? null,
                    $data['plan'] ?? $data['plan_count'] ?? null,
                    $data['unit_total'] ?? null,
                    $data['%'] ?? $data['total_achievement'] ?? null,
                    $existing['id']
                ]);
            } else {
                // Insert
                $insertStmt = $pdo->prepare("
                    INSERT INTO production_records (
                        equipment_id, record_date, month, week,
                        model_name, sku, lot_no, worker_name,
                        cnc_run_time, plan_count, unit_total, total_achievement,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insertStmt->execute([
                    $equipmentId,
                    $recordDate,
                    $month,
                    $week,
                    $data['model_name'] ?? null,
                    $data['sku'] ?? null,
                    $data['lot_no'] ?? $data['lot__no'] ?? null,
                    $data['worker'] ?? null,
                    $data['cnc_run_time'] ?? $data['cnc_runtime'] ?? null,
                    $data['plan'] ?? $data['plan_count'] ?? null,
                    $data['unit_total'] ?? null,
                    $data['%'] ?? $data['total_achievement'] ?? null,
                    $_SESSION['user_id']
                ]);
            }

            $processed++;
        } catch (Exception $e) {
            $errors[] = "Line $lineNum: " . $e->getMessage();
        }
    }

    fclose($handle);
    $pdo->commit();

    // Write log
    file_put_contents($logFile, "Processed: $processed\nErrors: " . count($errors) . "\n", FILE_APPEND);
    if (count($errors) > 0) {
        file_put_contents($logFile, "\nErrors:\n" . implode("\n", $errors) . "\n", FILE_APPEND);
    }

    $message = "Production data uploaded successfully! Processed: $processed records";
    if (count($errors) > 0) {
        $message .= " (" . count($errors) . " errors)";
    }

    jsonResponse(true, $message, [
        'processed' => $processed,
        'errors' => array_slice($errors, 0, 50) // Return first 50 errors
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
