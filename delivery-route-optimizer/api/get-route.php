<?php
/**
 * API: Get Optimized Route
 * POST /api/get-route.php
 *
 * Creates an optimized delivery route for selected customers
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
    if (empty($input['customer_ids']) || !is_array($input['customer_ids'])) {
        errorResponse('VALIDATION_ERROR', 'customer_ids is required and must be an array', 400, [
            'field' => 'customer_ids'
        ]);
    }

    $customerIds = array_map('intval', $input['customer_ids']);

    // Validate customer count
    if (count($customerIds) < 1) {
        errorResponse('VALIDATION_ERROR', 'At least one customer must be selected', 400);
    }

    if (count($customerIds) > MAX_WAYPOINTS) {
        errorResponse('VALIDATION_ERROR', 'Maximum ' . MAX_WAYPOINTS . ' customers allowed per route', 400, [
            'max_waypoints' => MAX_WAYPOINTS,
            'requested' => count($customerIds)
        ]);
    }

    // Parse start location
    $startLocation = null;
    if (!empty($input['start_location'])) {
        if (isset($input['start_location']['lat']) && isset($input['start_location']['lng'])) {
            $startLocation = [
                'lat' => (float)$input['start_location']['lat'],
                'lng' => (float)$input['start_location']['lng']
            ];
        } elseif (!empty($input['start_location']['address'])) {
            // Geocode the start address
            require_once dirname(__DIR__) . '/functions/google.php';
            $geocodeResult = geocodeAddress($input['start_location']['address']);
            if ($geocodeResult['success']) {
                $startLocation = [
                    'lat' => $geocodeResult['lat'],
                    'lng' => $geocodeResult['lng']
                ];
            }
        }
    }

    // Parse end location
    $endLocation = null;
    if (!empty($input['end_location'])) {
        if (isset($input['end_location']['return_to_start']) && $input['end_location']['return_to_start']) {
            $endLocation = $startLocation; // Will be set in createOptimizedRoute if null
        } elseif (isset($input['end_location']['lat']) && isset($input['end_location']['lng'])) {
            $endLocation = [
                'lat' => (float)$input['end_location']['lat'],
                'lng' => (float)$input['end_location']['lng']
            ];
        }
    }

    // Parse departure time
    $departureTime = $input['departure_time'] ?? null;

    // Build options
    $options = [
        'optimize' => $input['optimize'] ?? true,
        'avoid' => $input['avoid'] ?? []
    ];

    // Create optimized route
    $result = createOptimizedRoute(
        $customerIds,
        $startLocation,
        $endLocation,
        $departureTime,
        $options
    );

    if (!$result['success']) {
        errorResponse('ROUTE_ERROR', $result['error'], 400);
    }

    successResponse([
        'route_id' => $result['route_id'],
        'optimized_order' => $result['optimized_order'],
        'waypoints' => $result['waypoints'],
        'summary' => $result['summary'],
        'polyline' => $result['polyline'],
        'bounds' => $result['bounds']
    ], 'Route optimized successfully');

} catch (Exception $e) {
    error_log("Error in get-route.php: " . $e->getMessage());
    errorResponse('SERVER_ERROR', 'An error occurred while creating route', 500);
}
