<?php
/**
 * API: Recalculate Route
 * POST /api/recalculate.php
 *
 * Recalculates route from driver's current position
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/functions/route.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('METHOD_NOT_ALLOWED', 'Only POST method is allowed', 405);
}

try {
    // Get request body
    $input = getRequestBody();

    // Validate required fields
    $required = ['route_id', 'current_location', 'remaining_customer_ids'];
    $missing = validateRequired($input, $required);

    if (!empty($missing)) {
        errorResponse('VALIDATION_ERROR', 'Missing required fields: ' . implode(', ', $missing), 400, [
            'missing_fields' => $missing
        ]);
    }

    // Validate route_id
    $routeId = (int)$input['route_id'];
    if ($routeId <= 0) {
        errorResponse('VALIDATION_ERROR', 'Invalid route_id', 400);
    }

    // Validate current location
    if (!isset($input['current_location']['lat']) || !isset($input['current_location']['lng'])) {
        errorResponse('VALIDATION_ERROR', 'current_location must have lat and lng', 400);
    }

    $currentLocation = [
        'lat' => (float)$input['current_location']['lat'],
        'lng' => (float)$input['current_location']['lng']
    ];

    // Validate coordinates range
    if ($currentLocation['lat'] < -90 || $currentLocation['lat'] > 90) {
        errorResponse('VALIDATION_ERROR', 'Invalid latitude value', 400);
    }
    if ($currentLocation['lng'] < -180 || $currentLocation['lng'] > 180) {
        errorResponse('VALIDATION_ERROR', 'Invalid longitude value', 400);
    }

    // Validate remaining customer IDs
    if (!is_array($input['remaining_customer_ids']) || empty($input['remaining_customer_ids'])) {
        errorResponse('VALIDATION_ERROR', 'remaining_customer_ids must be a non-empty array', 400);
    }

    $remainingCustomerIds = array_map('intval', $input['remaining_customer_ids']);

    // Check route exists
    $route = getRouteById($routeId);
    if (!$route) {
        errorResponse('NOT_FOUND', 'Route not found', 404);
    }

    // Recalculate route
    $result = recalculateRoute($routeId, $currentLocation, $remainingCustomerIds);

    if (!$result['success']) {
        errorResponse('RECALCULATE_ERROR', $result['error'], 400);
    }

    successResponse([
        'route_id' => $result['route_id'],
        'recalculated_at' => $result['recalculated_at'],
        'optimized_order' => $result['optimized_order'],
        'next_stop' => $result['next_stop'],
        'remaining_waypoints' => $result['remaining_waypoints'],
        'summary' => $result['summary'],
        'polyline' => $result['polyline']
    ], 'Route recalculated successfully');

} catch (Exception $e) {
    error_log("Error in recalculate.php: " . $e->getMessage());
    errorResponse('SERVER_ERROR', 'An error occurred while recalculating route', 500);
}
