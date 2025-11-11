<?php
/**
 * Save Production Records API
 * Saves daily production records for all equipment
 */

require_once '../config/database.php';
checkAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['date']) || !isset($input['records'])) {
        jsonResponse(false, 'Invalid input data');
    }

    $date = $input['date'];
    $records = $input['records'];

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $saved = 0;
    $updated = 0;

    foreach ($records as $record) {
        $equipmentId = $record['equipment_id'];

        // Check if any meaningful data exists
        $hasData = false;
        foreach ($record as $key => $value) {
            if ($key !== 'equipment_id' && !empty($value)) {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            continue; // Skip empty records
        }

        // Check if record exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM production_records
            WHERE equipment_id = ? AND record_date = ?
        ");
        $checkStmt->execute([$equipmentId, $date]);
        $existing = $checkStmt->fetch();

        // Extract month and week from date
        $dateObj = new DateTime($date);
        $month = $dateObj->format('n');
        $week = $dateObj->format('W');

        if ($existing) {
            // Update existing record
            $updateStmt = $pdo->prepare("
                UPDATE production_records SET
                    month = ?,
                    week = ?,
                    model_name = ?,
                    sku = ?,
                    lot_no = ?,
                    part_length = ?,
                    worker_name = ?,
                    diameter = ?,
                    length = ?,
                    lot = ?,
                    unit = ?,
                    exped_count = ?,
                    exped_count_24h = ?,
                    plan_count = ?,
                    unit_total = ?,
                    M = ?,
                    S = ?,
                    cnc_run_time = ?,
                    working_time = ?,
                    setting = ?,
                    cnc_total = ?,
                    test_unit = ?,
                    day_achievement = ?,
                    h24_achievement = ?,
                    total_achievement = ?,
                    milling_days_remaining = ?,
                    setting_qty = ?,
                    tool_broken_fail_qty = ?,
                    dent_failed_qty = ?,
                    dimension_fail_qty = ?,
                    overnight_fail_qty = ?,
                    etc_fail_qty = ?,
                    inspected_by = ?,
                    description = ?,
                    note = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $updateStmt->execute([
                $month,
                $week,
                $record['model_name'] ?? null,
                $record['sku'] ?? null,
                $record['lot_no'] ?? null,
                $record['part_length'] ?? null,
                $record['worker_name'] ?? null,
                $record['diameter'] ?? null,
                $record['length'] ?? null,
                $record['lot'] ?? null,
                $record['unit'] ?? null,
                $record['exped_count'] ?? null,
                $record['exped_count_24h'] ?? null,
                $record['plan_count'] ?? null,
                $record['unit_total'] ?? null,
                $record['M'] ?? null,
                $record['S'] ?? null,
                $record['cnc_run_time'] ?? null,
                $record['working_time'] ?? null,
                $record['setting'] ?? null,
                $record['cnc_total'] ?? null,
                $record['test_unit'] ?? null,
                $record['day_achievement'] ?? null,
                $record['h24_achievement'] ?? null,
                $record['total_achievement'] ?? null,
                $record['milling_days_remaining'] ?? null,
                $record['setting_qty'] ?? null,
                $record['tool_broken_fail_qty'] ?? null,
                $record['dent_failed_qty'] ?? null,
                $record['dimension_fail_qty'] ?? null,
                $record['overnight_fail_qty'] ?? null,
                $record['etc_fail_qty'] ?? null,
                $record['inspected_by'] ?? null,
                $record['description'] ?? null,
                $record['note'] ?? null,
                $existing['id']
            ]);

            $updated++;
            $recordId = $existing['id'];
        } else {
            // Insert new record
            $insertStmt = $pdo->prepare("
                INSERT INTO production_records (
                    equipment_id, record_date, month, week,
                    model_name, sku, lot_no, part_length, worker_name,
                    diameter, length, lot, unit, exped_count, exped_count_24h,
                    plan_count, unit_total, M, S, cnc_run_time, working_time,
                    setting, cnc_total, test_unit,
                    day_achievement, h24_achievement, total_achievement,
                    milling_days_remaining, setting_qty,
                    tool_broken_fail_qty, dent_failed_qty, dimension_fail_qty,
                    overnight_fail_qty, etc_fail_qty,
                    inspected_by, description, note, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $insertStmt->execute([
                $equipmentId,
                $date,
                $month,
                $week,
                $record['model_name'] ?? null,
                $record['sku'] ?? null,
                $record['lot_no'] ?? null,
                $record['part_length'] ?? null,
                $record['worker_name'] ?? null,
                $record['diameter'] ?? null,
                $record['length'] ?? null,
                $record['lot'] ?? null,
                $record['unit'] ?? null,
                $record['exped_count'] ?? null,
                $record['exped_count_24h'] ?? null,
                $record['plan_count'] ?? null,
                $record['unit_total'] ?? null,
                $record['M'] ?? null,
                $record['S'] ?? null,
                $record['cnc_run_time'] ?? null,
                $record['working_time'] ?? null,
                $record['setting'] ?? null,
                $record['cnc_total'] ?? null,
                $record['test_unit'] ?? null,
                $record['day_achievement'] ?? null,
                $record['h24_achievement'] ?? null,
                $record['total_achievement'] ?? null,
                $record['milling_days_remaining'] ?? null,
                $record['setting_qty'] ?? null,
                $record['tool_broken_fail_qty'] ?? null,
                $record['dent_failed_qty'] ?? null,
                $record['dimension_fail_qty'] ?? null,
                $record['overnight_fail_qty'] ?? null,
                $record['etc_fail_qty'] ?? null,
                $record['inspected_by'] ?? null,
                $record['description'] ?? null,
                $record['note'] ?? null,
                $_SESSION['user_id']
            ]);

            $saved++;
            $recordId = $pdo->lastInsertId();
        }

        // Update tool usage if cnc_run_time is provided
        if (!empty($record['cnc_run_time']) && $record['cnc_run_time'] > 0) {
            updateToolUsage($pdo, $equipmentId, $record['cnc_run_time'], $date, $recordId);
        }
    }

    $pdo->commit();

    jsonResponse(true, "Data saved successfully! (New: $saved, Updated: $updated)", [
        'saved' => $saved,
        'updated' => $updated
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred while saving data.');
    }
}

/**
 * Update tool usage hours for installed tools
 */
function updateToolUsage($pdo, $equipmentId, $cncRunTime, $date, $productionRecordId) {
    // Get all active tools for this equipment
    $stmt = $pdo->prepare("
        SELECT ets.tool_id, ets.usage_ratio, t.current_usage_hours
        FROM equipment_tool_settings ets
        JOIN tools t ON ets.tool_id = t.id
        WHERE ets.equipment_id = ? AND ets.is_active = 1
    ");
    $stmt->execute([$equipmentId]);
    $tools = $stmt->fetchAll();

    foreach ($tools as $tool) {
        $usageHours = $cncRunTime * ($tool['usage_ratio'] / 100);

        // Update tool's current usage hours
        $updateToolStmt = $pdo->prepare("
            UPDATE tools
            SET current_usage_hours = current_usage_hours + ?,
                status = CASE
                    WHEN status = 'new' THEN 'in_use'
                    ELSE status
                END
            WHERE id = ?
        ");
        $updateToolStmt->execute([$usageHours, $tool['tool_id']]);

        // Record in usage history
        $newCumulativeHours = $tool['current_usage_hours'] + $usageHours;

        $insertHistoryStmt = $pdo->prepare("
            INSERT INTO tool_usage_history (
                tool_id, equipment_id, production_record_id,
                usage_hours, usage_date, cumulative_hours
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertHistoryStmt->execute([
            $tool['tool_id'],
            $equipmentId,
            $productionRecordId,
            $usageHours,
            $date,
            $newCumulativeHours
        ]);
    }
}
?>
