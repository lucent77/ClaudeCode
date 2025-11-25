<?php
/**
 * Magic Rx Scanner - Results API
 *
 * Retrieves analysis results for a prescription
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

/**
 * Send JSON response
 */
function sendResponse($success, $data = [], $message = '', $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, [], 'Only GET requests are allowed', 405);
}

try {
    // Get prescription_id from query parameter
    if (!isset($_GET['prescription_id'])) {
        sendResponse(false, [], 'prescription_id is required', 400);
    }

    $prescriptionId = (int)$_GET['prescription_id'];

    // Initialize database
    $db = Database::getInstance();

    // Get prescription record
    $query = "SELECT id, original_filename, analysis_mode, status, error_message, created_at, updated_at
              FROM prescriptions WHERE id = ?";
    $prescription = $db->selectOne($query, [$prescriptionId]);

    if (!$prescription) {
        sendResponse(false, [], 'Prescription not found', 404);
    }

    // Get extracted data
    $query = "SELECT data_type, field_name, field_value, image_base64, confidence_score
              FROM extracted_data
              WHERE prescription_id = ?
              ORDER BY id ASC";
    $extractedData = $db->select($query, [$prescriptionId]);

    // Organize extracted data by type
    $organizedData = [
        'full_text' => '',
        'fields' => [],
        'handwriting' => [
            'text' => '',
            'image' => '',
            'confidence' => 0
        ]
    ];

    foreach ($extractedData as $data) {
        switch ($data['data_type']) {
            case 'full_text':
                $organizedData['full_text'] = $data['field_value'];
                break;

            case 'field':
                $organizedData['fields'][] = [
                    'name' => $data['field_name'],
                    'value' => $data['field_value'],
                    'confidence' => $data['confidence_score']
                ];
                break;

            case 'handwriting_text':
                $organizedData['handwriting']['text'] = $data['field_value'];
                $organizedData['handwriting']['confidence'] = $data['confidence_score'];
                break;

            case 'handwriting_image':
                $organizedData['handwriting']['image'] = $data['image_base64'];
                break;
        }
    }

    // Get processing logs (optional, for debugging)
    $includeLog = isset($_GET['include_logs']) && $_GET['include_logs'] === 'true';
    $logs = [];

    if ($includeLog) {
        $query = "SELECT log_level, message, created_at
                  FROM processing_logs
                  WHERE prescription_id = ?
                  ORDER BY created_at ASC";
        $logs = $db->select($query, [$prescriptionId]);
    }

    // Build response
    $responseData = [
        'prescription' => [
            'id' => $prescription['id'],
            'filename' => $prescription['original_filename'],
            'mode' => $prescription['analysis_mode'],
            'status' => $prescription['status'],
            'error_message' => $prescription['error_message'],
            'created_at' => $prescription['created_at'],
            'updated_at' => $prescription['updated_at']
        ],
        'extracted_data' => $organizedData
    ];

    if ($includeLog) {
        $responseData['logs'] = $logs;
    }

    sendResponse(true, $responseData, 'Results retrieved successfully', 200);

} catch (Exception $e) {
    // Log error
    if (ENABLE_LOGGING) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] RESULTS ERROR: {$e->getMessage()}" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    sendResponse(false, [], 'An error occurred while retrieving results', 500);
}
