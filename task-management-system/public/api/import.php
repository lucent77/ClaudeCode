<?php
/**
 * JSON Import API Endpoint
 * Handles importing tasks from JSON files
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Security.php';
require_once __DIR__ . '/../../includes/TaskManager.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

// Only admin and front desk can import
if (!$auth->isAdmin() && !$auth->isFrontDesk()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $departmentId = $_POST['department_id'] ?? null;

    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department is required']);
        exit;
    }

    if (!isset($_FILES['json_file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['json_file'];

    // Validate file
    $validation = Security::validateFileUpload($file, ['json']);
    if (!$validation['success']) {
        echo json_encode($validation);
        exit;
    }

    // Read and parse JSON
    $jsonContent = file_get_contents($file['tmp_name']);
    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON file']);
        exit;
    }

    if (!is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'JSON must be an array of tasks']);
        exit;
    }

    $taskManager = new TaskManager();
    $db = Database::getInstance();
    $userId = $auth->getUserId();

    $imported = 0;
    $errors = [];

    $db->beginTransaction();

    try {
        foreach ($data as $taskData) {
            // Map JSON fields to database fields
            $task = [
                'external_id' => $taskData['ID'] ?? $taskData['CASE #'] ?? $taskData['CASE#'] ?? null,
                'department_id' => $departmentId,
                'created_by' => $userId,
                'date' => parseDate($taskData['DATE'] ?? null),
                'due_date' => parseDate($taskData['DUE'] ?? $taskData['DUE DATE'] ?? null),
                'type' => $taskData['TYPE'] ?? null,
                'status_id' => 1, // Default to Pending
                'tooth' => $taskData['TOOTH #'] ?? $taskData['TOOTH#'] ?? null,
                'teeth' => $taskData['TEETH'] ?? null,
                'implant_type' => $taskData['IMPLANT TYPE'] ?? null,
                'implant_system' => $taskData['IMPLANT SYSTEM'] ?? null,
                'design' => $taskData['DESIGN'] ?? null,
                'lab' => $taskData['LAB #'] ?? $taskData['LAB'] ?? null,
                'lab_number' => $taskData['LAB #'] ?? null,
                'patient_number' => $taskData['PATIENT #'] ?? null,
                'case_number' => $taskData['CASE #'] ?? $taskData['CASE#'] ?? null,
                'notes' => $taskData['NOTE'] ?? $taskData['NOTES'] ?? null
            ];

            $result = $taskManager->createTask($task);

            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = "Failed to import task: " . ($task['external_id'] ?? 'Unknown');
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'count' => $imported,
            'errors' => $errors,
            'message' => "Successfully imported $imported tasks"
        ]);
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
    error_log($e->getMessage());
}

/**
 * Parse date from various formats
 */
function parseDate($dateStr) {
    if (empty($dateStr)) {
        return null;
    }

    // Try to handle formats like "11/04" (month/day without year)
    if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $dateStr, $matches)) {
        $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = date('Y'); // Use current year
        return "$year-$month-$day";
    }

    // Try standard date parsing
    $timestamp = strtotime($dateStr);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}
