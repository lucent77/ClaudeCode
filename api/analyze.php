<?php
/**
 * Magic Rx Scanner - Analyze API
 *
 * Processes uploaded prescription using OCR (Document AI or Vision API)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/image_processor.php';
require_once __DIR__ . '/../includes/ocr_handler.php';
require_once __DIR__ . '/../includes/csrf_protection.php';

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

/**
 * Log to processing_logs table
 */
function logProcessing($db, $prescriptionId, $level, $message) {
    try {
        $query = "INSERT INTO processing_logs (prescription_id, log_level, message) VALUES (?, ?, ?)";
        $db->insert($query, [$prescriptionId, $level, $message]);
    } catch (Exception $e) {
        // Silently fail logging
    }
}

/**
 * Update prescription status
 */
function updateStatus($db, $prescriptionId, $status, $errorMessage = null) {
    $query = "UPDATE prescriptions SET status = ?, error_message = ? WHERE id = ?";
    $db->update($query, [$status, $errorMessage, $prescriptionId]);
}

/**
 * Save extracted data
 */
function saveExtractedData($db, $prescriptionId, $dataType, $fieldName, $fieldValue, $imageBase64, $confidence) {
    $query = "INSERT INTO extracted_data
              (prescription_id, data_type, field_name, field_value, image_base64, confidence_score)
              VALUES (?, ?, ?, ?, ?, ?)";

    $db->insert($query, [$prescriptionId, $dataType, $fieldName, $fieldValue, $imageBase64, $confidence]);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, [], 'Only POST requests are allowed', 405);
}

// Validate CSRF token if enabled
if (defined('ENABLE_CSRF_PROTECTION') && ENABLE_CSRF_PROTECTION) {
    CSRFProtection::validateOrDie(true);
}

try {
    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['prescription_id'])) {
        sendResponse(false, [], 'prescription_id is required', 400);
    }

    $prescriptionId = (int)$input['prescription_id'];

    // Initialize handlers
    $db = Database::getInstance();
    $imageProcessor = new ImageProcessor();
    $ocrHandler = new OCRHandler();

    // Get prescription record
    $query = "SELECT * FROM prescriptions WHERE id = ?";
    $prescription = $db->selectOne($query, [$prescriptionId]);

    if (!$prescription) {
        sendResponse(false, [], 'Prescription not found', 404);
    }

    // Check if already processed
    if ($prescription['status'] === 'completed') {
        sendResponse(false, [], 'Prescription already processed', 400);
    }

    // Update status to processing
    updateStatus($db, $prescriptionId, 'processing');
    logProcessing($db, $prescriptionId, 'info', 'Starting analysis');

    $responseData = [
        'prescription_id' => $prescriptionId,
        'mode' => $prescription['analysis_mode']
    ];

    // Process based on analysis mode
    if ($prescription['analysis_mode'] === 'handwriting_extraction') {
        // Template-based handwriting extraction
        logProcessing($db, $prescriptionId, 'info', 'Processing handwriting extraction');

        // Subtract template from filled document
        $result = $imageProcessor->subtractTemplate(
            $prescription['file_path'],
            $prescription['template_path']
        );

        if (!$result['success']) {
            updateStatus($db, $prescriptionId, 'failed', $result['error']);
            logProcessing($db, $prescriptionId, 'error', 'Template subtraction failed: ' . $result['error']);
            sendResponse(false, [], 'Image processing failed: ' . $result['error'], 500);
        }

        // Save extracted handwriting image
        saveExtractedData($db, $prescriptionId, 'handwriting_image', null, null, $result['base64'], null);
        logProcessing($db, $prescriptionId, 'info', 'Handwriting image extracted');

        // Perform OCR on extracted handwriting using Vision API
        $ocrResult = $ocrHandler->processVisionAPI($result['output_path']);

        if (!$ocrResult['success']) {
            updateStatus($db, $prescriptionId, 'failed', $ocrResult['error']);
            logProcessing($db, $prescriptionId, 'error', 'Vision API failed: ' . $ocrResult['error']);
            sendResponse(false, [], 'OCR processing failed: ' . $ocrResult['error'], 500);
        }

        // Save extracted text
        saveExtractedData($db, $prescriptionId, 'handwriting_text', null, $ocrResult['text'], null, $ocrResult['confidence']);
        logProcessing($db, $prescriptionId, 'info', 'OCR completed successfully');

        $responseData['handwriting'] = [
            'text' => $ocrResult['text'],
            'image' => $result['base64'],
            'confidence' => $ocrResult['confidence']
        ];

    } else {
        // Document AI processing
        logProcessing($db, $prescriptionId, 'info', 'Processing with Document AI');

        $ocrResult = $ocrHandler->processDocumentAI($prescription['file_path']);

        if (!$ocrResult['success']) {
            updateStatus($db, $prescriptionId, 'failed', $ocrResult['error']);
            logProcessing($db, $prescriptionId, 'error', 'Document AI failed: ' . $ocrResult['error']);
            sendResponse(false, [], 'OCR processing failed: ' . $ocrResult['error'], 500);
        }

        // Save full text
        saveExtractedData($db, $prescriptionId, 'full_text', null, $ocrResult['text'], null, $ocrResult['confidence']);

        // Save individual fields
        foreach ($ocrResult['fields'] as $field) {
            saveExtractedData(
                $db,
                $prescriptionId,
                'field',
                $field['name'],
                $field['value'],
                null,
                $field['confidence']
            );
        }

        logProcessing($db, $prescriptionId, 'info', 'Document AI completed successfully');

        $responseData['document_ai'] = [
            'text' => $ocrResult['text'],
            'fields' => $ocrResult['fields'],
            'confidence' => $ocrResult['confidence']
        ];
    }

    // Update status to completed
    updateStatus($db, $prescriptionId, 'completed');
    logProcessing($db, $prescriptionId, 'info', 'Analysis completed');

    sendResponse(true, $responseData, 'Analysis completed successfully', 200);

} catch (Exception $e) {
    // Log error
    if (ENABLE_LOGGING) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ANALYZE ERROR: {$e->getMessage()}" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    // Update prescription status if available
    if (isset($prescriptionId) && isset($db)) {
        updateStatus($db, $prescriptionId, 'failed', $e->getMessage());
        logProcessing($db, $prescriptionId, 'error', 'Fatal error: ' . $e->getMessage());
    }

    sendResponse(false, [], 'An error occurred during analysis: ' . $e->getMessage(), 500);
}
