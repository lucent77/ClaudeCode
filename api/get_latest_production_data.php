<?php
/**
 * Get Latest Production Data API
 * Retrieves the most recent production data for each equipment
 */

require_once '../config/database.php';
checkAuth();

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();

    // Get latest record for each equipment
    $stmt = $pdo->query("
        SELECT pr1.*
        FROM production_records pr1
        INNER JOIN (
            SELECT equipment_id, MAX(record_date) as max_date
            FROM production_records
            GROUP BY equipment_id
        ) pr2 ON pr1.equipment_id = pr2.equipment_id AND pr1.record_date = pr2.max_date
    ");

    $records = $stmt->fetchAll();

    $dataByEquipment = [];

    foreach ($records as $record) {
        $equipId = $record['equipment_id'];

        // Fields to copy (before "unit" field)
        $fieldsBeforeUnit = [
            'model_name', 'sku', 'lot_no', 'part_length', 'worker_name',
            'diameter', 'length', 'lot'
        ];

        $dataByEquipment[$equipId] = [];

        foreach ($fieldsBeforeUnit as $field) {
            $dataByEquipment[$equipId][$field] = $record[$field];
        }

        // Clear fields after "unit"
        $fieldsAfterUnit = [
            'unit', 'exped_count', 'exped_count_24h', 'unit_total', 'M', 'S',
            'cnc_run_time', 'working_time', 'setting', 'cnc_total', 'test_unit',
            'day_achievement', 'h24_achievement', 'total_achievement',
            'milling_days_remaining', 'setting_qty', 'tool_broken_fail_qty',
            'dent_failed_qty', 'dimension_fail_qty', 'overnight_fail_qty',
            'etc_fail_qty', 'inspected_by', 'description', 'note'
        ];

        foreach ($fieldsAfterUnit as $field) {
            $dataByEquipment[$equipId][$field] = null;
        }
    }

    jsonResponse(true, 'Latest data retrieved', [
        'data' => $dataByEquipment
    ]);

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
