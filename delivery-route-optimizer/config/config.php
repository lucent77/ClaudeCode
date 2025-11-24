<?php
/**
 * Application Configuration
 * Smart Delivery Route Optimizer
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ==============================================
// Google Maps API Configuration
// ==============================================
define('GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY_HERE');

// API Endpoints
define('GOOGLE_GEOCODING_URL', 'https://maps.googleapis.com/maps/api/geocode/json');
define('GOOGLE_DIRECTIONS_URL', 'https://maps.googleapis.com/maps/api/directions/json');
define('GOOGLE_DISTANCE_MATRIX_URL', 'https://maps.googleapis.com/maps/api/distancematrix/json');

// ==============================================
// Application Settings
// ==============================================
define('APP_NAME', 'Smart Delivery Route Optimizer');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', false); // Set to false in production

// Default route filter
define('DEFAULT_ROUTE_NAME', 'Local Courier');

// ==============================================
// Route Optimization Settings
// ==============================================
define('MAX_WAYPOINTS', 25);        // Google API limit: 25 waypoints
define('DEFAULT_TRAFFIC_MODEL', 'best_guess'); // best_guess, pessimistic, optimistic

// Default start location (Office/Warehouse)
define('DEFAULT_START_LAT', 41.5008);
define('DEFAULT_START_LNG', -74.0105);
define('DEFAULT_START_ADDRESS', 'Newburgh, NY');

// ==============================================
// Geocoding Settings
// ==============================================
define('GEOCODING_CACHE_ENABLED', true);
define('GEOCODING_RETRY_COUNT', 3);
define('GEOCODING_DELAY_MS', 200); // Delay between requests to avoid rate limiting

// ==============================================
// Timezone
// ==============================================
date_default_timezone_set('America/New_York');

// ==============================================
// Error Handling
// ==============================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==============================================
// Include Database Configuration
// ==============================================
require_once APP_ROOT . '/config/db.php';

// ==============================================
// Helper Functions
// ==============================================

/**
 * JSON Response Helper
 */
function jsonResponse($data, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Error Response Helper
 */
function errorResponse(string $code, string $message, int $httpCode = 400, array $details = []): void {
    $response = [
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ];

    if (!empty($details)) {
        $response['error']['details'] = $details;
    }

    jsonResponse($response, $httpCode);
}

/**
 * Success Response Helper
 */
function successResponse($data, string $message = 'Success'): void {
    jsonResponse([
        'success' => true,
        'data' => $data,
        'message' => $message
    ]);
}

/**
 * Get Request Body as JSON
 */
function getRequestBody(): array {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

/**
 * Validate Required Fields
 */
function validateRequired(array $data, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Sanitize String Input
 */
function sanitizeString(?string $value): string {
    if ($value === null) return '';
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Log API Call
 */
function logApiCall(string $apiType, $request, string $status, $response, float $executionTime): void {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO api_logs (api_type, request_data, response_status, response_data, execution_time)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $apiType,
            is_array($request) ? json_encode($request) : $request,
            $status,
            is_array($response) ? json_encode($response) : substr($response, 0, 5000),
            $executionTime
        ]);
    } catch (Exception $e) {
        error_log("Failed to log API call: " . $e->getMessage());
    }
}
