<?php
/**
 * Magic Rx Scanner - Upload API
 *
 * Handles file uploads for prescriptions and templates
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
require_once __DIR__ . '/../includes/file_handler.php';
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, [], 'Only POST requests are allowed', 405);
}

// Validate CSRF token if enabled
if (defined('ENABLE_CSRF_PROTECTION') && ENABLE_CSRF_PROTECTION) {
    CSRFProtection::validateOrDie(true);
}

try {
    // Initialize handlers
    $db = Database::getInstance();
    $fileHandler = new FileHandler();

    // Check if document file is uploaded
    if (!isset($_FILES['document'])) {
        sendResponse(false, [], 'No document file uploaded', 400);
    }

    $documentFile = $_FILES['document'];
    $templateFile = isset($_FILES['template']) ? $_FILES['template'] : null;

    // Upload document
    $documentUpload = $fileHandler->uploadFile($documentFile, UPLOAD_DIR, 'document');
    if (!$documentUpload['success']) {
        sendResponse(false, [], $documentUpload['error'], 400);
    }

    // Upload template if provided
    $templateUpload = null;
    if ($templateFile !== null) {
        $templateUpload = $fileHandler->uploadFile($templateFile, TEMPLATE_DIR, 'template');
        if (!$templateUpload['success']) {
            // Clean up document file
            $fileHandler->deleteFile($documentUpload['path']);
            sendResponse(false, [], $templateUpload['error'], 400);
        }
    }

    // Determine analysis mode
    $analysisMode = $templateUpload !== null ? 'handwriting_extraction' : 'document_ai';

    // Insert prescription record
    $query = "INSERT INTO prescriptions
              (original_filename, stored_filename, template_filename, file_path, template_path, analysis_mode, status)
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $documentUpload['original_filename'],
        $documentUpload['filename'],
        $templateUpload ? $templateUpload['filename'] : null,
        $documentUpload['path'],
        $templateUpload ? $templateUpload['path'] : null,
        $analysisMode,
        'pending'
    ];

    $prescriptionId = $db->insert($query, $params);

    if (!$prescriptionId) {
        // Clean up uploaded files
        $fileHandler->deleteFile($documentUpload['path']);
        if ($templateUpload) {
            $fileHandler->deleteFile($templateUpload['path']);
        }
        sendResponse(false, [], 'Failed to create prescription record', 500);
    }

    // Log upload
    logProcessing($db, $prescriptionId, 'info', 'Files uploaded successfully');

    // Send success response
    sendResponse(true, [
        'prescription_id' => $prescriptionId,
        'analysis_mode' => $analysisMode,
        'document_filename' => $documentUpload['filename'],
        'template_filename' => $templateUpload ? $templateUpload['filename'] : null
    ], 'Files uploaded successfully', 201);

} catch (Exception $e) {
    // Log error
    if (ENABLE_LOGGING) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] UPLOAD ERROR: {$e->getMessage()}" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    sendResponse(false, [], 'An error occurred during file upload', 500);
}
