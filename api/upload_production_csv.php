<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, null, '권한이 없습니다.');
}

if (!isset($_FILES['production_csv']) || $_FILES['production_csv']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, null, '파일 업로드에 실패했습니다.');
}

$file = $_FILES['production_csv']['tmp_name'];

try {
    $processed = 0;
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

        // Get equipment mapping
        $equipmentMap = [];
        $equipmentList = dbQuery("SELECT id, equipment_code FROM equipment");
        foreach ($equipmentList as $eq) {
            $equipmentMap[$eq['equipment_code']] = $eq['id'];
        }

        dbBeginTransaction();

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($headers)) {
                continue; // Skip incomplete rows
            }

            try {
                // Parse data - handle potential missing columns
                $date = trim($row[$headerMap['DATE']] ?? '');
                $modelName = trim($row[$headerMap['MODEL NAME']] ?? '');
                $sku = trim($row[$headerMap['SKU']] ?? '');
                $lotNo = trim($row[$headerMap['LOT. NO']] ?? $row[$headerMap['LOT NO']] ?? '');
                $worker = trim($row[$headerMap['WORKER']] ?? '');
                $cncCode = trim($row[$headerMap['CNC']] ?? '');
                $cncRunTime = floatval(str_replace(',', '', $row[$headerMap['CNC Run Time']] ?? 0));
                $plan = intval(str_replace(',', '', $row[$headerMap['PLAN']] ?? 0));
                $unitTotal = intval(str_replace(',', '', $row[$headerMap['UNIT TOTAL']] ?? 0));
                $percentage = floatval(str_replace(['%', ','], '', $row[$headerMap['%']] ?? 0));

                if (empty($date) || empty($cncCode)) {
                    continue; // Skip rows without date or equipment
                }

                // Parse date
                $recordDate = date('Y-m-d', strtotime($date));

                // Get equipment ID
                $equipmentId = $equipmentMap[$cncCode] ?? null;

                if (!$equipmentId) {
                    $errors[] = "Row $rowNumber: Unknown equipment code '$cncCode'";
                    continue;
                }

                // Insert production record
                dbExecute("
                    INSERT INTO production_records (
                        record_date, model_name, sku, lot_no, worker_name,
                        equipment_id, cnc_run_time, plan_count, unit_total,
                        total_achievement, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ", [
                    $recordDate, $modelName, $sku, $lotNo, $worker,
                    $equipmentId, $cncRunTime, $plan, $unitTotal,
                    $percentage, $_SESSION['user_id']
                ]);

                // Update equipment runtime
                dbExecute("
                    UPDATE equipment
                    SET total_runtime_hours = total_runtime_hours + ?
                    WHERE id = ?
                ", [$cncRunTime, $equipmentId]);

                // Update tool usage for this equipment
                $tools = dbQuery("
                    SELECT ets.tool_id, ets.usage_ratio, t.current_usage_hours
                    FROM equipment_tool_settings ets
                    JOIN tools t ON ets.tool_id = t.id
                    WHERE ets.equipment_id = ? AND ets.is_active = 1
                ", [$equipmentId]);

                foreach ($tools as $tool) {
                    $toolUsageHours = $cncRunTime * ($tool['usage_ratio'] / 100);

                    // Update tool usage
                    dbExecute("
                        UPDATE tools
                        SET current_usage_hours = current_usage_hours + ?
                        WHERE id = ?
                    ", [$toolUsageHours, $tool['tool_id']]);

                    // Record usage history
                    dbExecute("
                        INSERT INTO tool_usage_history (
                            tool_id, equipment_id, usage_hours, usage_date, cumulative_hours
                        ) VALUES (?, ?, ?, ?, ?)
                    ", [
                        $tool['tool_id'], $equipmentId, $toolUsageHours,
                        $recordDate, $tool['current_usage_hours'] + $toolUsageHours
                    ]);
                }

                $processed++;
            } catch (Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
            }
        }

        fclose($handle);
        dbCommit();

        if (!empty($errors)) {
            jsonResponse(true, [
                'processed' => $processed,
                'errors' => $errors
            ], "완료 (일부 오류 발생): 처리된 행 {$processed}개");
        } else {
            jsonResponse(true, [
                'processed' => $processed
            ], "생산 데이터 업로드 완료: {$processed}개 행 처리");
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
