<?php
/**
 * Magic Rx Scanner - Download API
 *
 * Export results as CSV or JSON
 */

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
 * Send error response
 */
function sendError($message) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Only GET requests are allowed');
}

try {
    // Get parameters
    if (!isset($_GET['prescription_id'])) {
        sendError('prescription_id is required');
    }

    $prescriptionId = (int)$_GET['prescription_id'];
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'json';

    if (!in_array($format, ['json', 'csv'])) {
        sendError('Invalid format. Use json or csv');
    }

    // Initialize database
    $db = Database::getInstance();

    // Get prescription record
    $query = "SELECT * FROM prescriptions WHERE id = ?";
    $prescription = $db->selectOne($query, [$prescriptionId]);

    if (!$prescription) {
        sendError('Prescription not found');
    }

    // Get extracted data
    $query = "SELECT * FROM extracted_data WHERE prescription_id = ? ORDER BY id ASC";
    $extractedData = $db->select($query, [$prescriptionId]);

    // Export based on format
    if ($format === 'json') {
        // JSON export
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="prescription_' . $prescriptionId . '.json"');

        $exportData = [
            'prescription' => $prescription,
            'extracted_data' => $extractedData
        ];

        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } else {
        // CSV export
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="prescription_' . $prescriptionId . '.csv"');

        $output = fopen('php://output', 'w');

        // Write BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write prescription info
        fputcsv($output, ['Prescription Information']);
        fputcsv($output, ['ID', $prescription['id']]);
        fputcsv($output, ['Original Filename', $prescription['original_filename']]);
        fputcsv($output, ['Analysis Mode', $prescription['analysis_mode']]);
        fputcsv($output, ['Status', $prescription['status']]);
        fputcsv($output, ['Created At', $prescription['created_at']]);
        fputcsv($output, ['Updated At', $prescription['updated_at']]);
        fputcsv($output, []);

        // Write extracted data
        fputcsv($output, ['Extracted Data']);
        fputcsv($output, ['Data Type', 'Field Name', 'Field Value', 'Confidence Score']);

        foreach ($extractedData as $data) {
            fputcsv($output, [
                $data['data_type'],
                $data['field_name'] ?? '',
                $data['data_type'] === 'handwriting_image' ? '[Image Data]' : ($data['field_value'] ?? ''),
                $data['confidence_score'] ?? ''
            ]);
        }

        fclose($output);
    }

} catch (Exception $e) {
    // Log error
    if (ENABLE_LOGGING) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] DOWNLOAD ERROR: {$e->getMessage()}" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    sendError('An error occurred during export');
}
