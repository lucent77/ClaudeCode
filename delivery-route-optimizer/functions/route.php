<?php
/**
 * Route Optimization Functions
 * Smart Delivery Route Optimizer
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/functions/google.php';

/**
 * Create optimized delivery route
 *
 * @param array $customerIds Array of customer IDs to include
 * @param array $startLocation ['lat' => float, 'lng' => float] Start point
 * @param array $endLocation ['lat' => float, 'lng' => float] End point (or null for round trip)
 * @param string|null $departureTime ISO datetime string
 * @param array $options Additional options
 * @return array
 */
function createOptimizedRoute(
    array $customerIds,
    ?array $startLocation = null,
    ?array $endLocation = null,
    ?string $departureTime = null,
    array $options = []
): array {
    try {
        $pdo = getDBConnection();

        // Validate customer count
        if (count($customerIds) > MAX_WAYPOINTS) {
            return [
                'success' => false,
                'error' => 'Too many waypoints. Maximum is ' . MAX_WAYPOINTS
            ];
        }

        if (empty($customerIds)) {
            return [
                'success' => false,
                'error' => 'No customers selected'
            ];
        }

        // Fetch customer data
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        $stmt = $pdo->prepare("
            SELECT id, account_number, practice_name, first_name, last_name,
                   addr1, addr2, city, state, zip, phone, email,
                   latitude, longitude
            FROM customers
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($customerIds);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($customers) !== count($customerIds)) {
            return [
                'success' => false,
                'error' => 'Some customer IDs are invalid'
            ];
        }

        // Check for missing coordinates and geocode if needed
        $customersById = [];
        $waypoints = [];
        $missingCoords = [];

        foreach ($customers as $customer) {
            $customersById[$customer['id']] = $customer;

            if (empty($customer['latitude']) || empty($customer['longitude'])) {
                // Try to geocode
                $geocodeResult = geocodeCustomer($customer['id']);

                if (!$geocodeResult['success']) {
                    $missingCoords[] = $customer['account_number'];
                    continue;
                }

                $customer['latitude'] = $geocodeResult['latitude'];
                $customer['longitude'] = $geocodeResult['longitude'];
                $customersById[$customer['id']] = $customer;
            }

            $waypoints[] = [
                'customer_id' => $customer['id'],
                'lat' => (float)$customer['latitude'],
                'lng' => (float)$customer['longitude']
            ];
        }

        if (!empty($missingCoords)) {
            return [
                'success' => false,
                'error' => 'Could not geocode addresses for: ' . implode(', ', $missingCoords)
            ];
        }

        // Set start location
        if (!$startLocation) {
            $startLocation = [
                'lat' => DEFAULT_START_LAT,
                'lng' => DEFAULT_START_LNG
            ];
        }

        // Set end location (return to start by default)
        if (!$endLocation) {
            $endLocation = $startLocation;
        }

        // Parse departure time
        $departureTimestamp = null;
        if ($departureTime) {
            $departureTimestamp = strtotime($departureTime);
        }

        // Get optimized route from Google
        $directionsResult = getDirections(
            $startLocation,
            $endLocation,
            $waypoints,
            $options['optimize'] ?? true,
            $departureTimestamp,
            $options['avoid'] ?? []
        );

        if (!$directionsResult['success']) {
            return [
                'success' => false,
                'error' => 'Directions API error: ' . ($directionsResult['error'] ?? 'Unknown')
            ];
        }

        // Build optimized waypoint list
        $waypointOrder = $directionsResult['waypoint_order'];
        $optimizedCustomerIds = [];
        $optimizedWaypoints = [];

        // Calculate ETAs
        $currentTime = $departureTimestamp ?: time();
        $cumulativeDuration = 0;

        foreach ($waypointOrder as $index => $originalIndex) {
            $wp = $waypoints[$originalIndex];
            $customer = $customersById[$wp['customer_id']];
            $leg = $directionsResult['legs'][$index];

            $cumulativeDuration += $leg['duration_in_traffic']['value'] ?? $leg['duration']['value'];
            $eta = date('Y-m-d\TH:i:s', $currentTime + $cumulativeDuration);

            $optimizedCustomerIds[] = $wp['customer_id'];
            $optimizedWaypoints[] = [
                'customer_id' => $wp['customer_id'],
                'account_number' => $customer['account_number'],
                'practice_name' => $customer['practice_name'] ?: ($customer['first_name'] . ' ' . $customer['last_name']),
                'address' => buildFullAddress($customer),
                'lat' => $wp['lat'],
                'lng' => $wp['lng'],
                'stop_number' => $index + 1,
                'eta' => $eta,
                'duration_from_previous' => $leg['duration']['value'],
                'duration_in_traffic' => $leg['duration_in_traffic']['value'] ?? $leg['duration']['value'],
                'distance_from_previous' => $leg['distance']['value']
            ];
        }

        // Save route to database
        $stmt = $pdo->prepare("
            INSERT INTO delivery_routes
            (route_name, customer_ids, optimized_order, waypoints, start_location, end_location,
             start_time, total_distance, total_duration, duration_in_traffic, polyline, legs_data, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $routeName = 'Route ' . date('Y-m-d H:i');

        $stmt->execute([
            $routeName,
            json_encode($customerIds),
            json_encode($optimizedCustomerIds),
            json_encode($optimizedWaypoints),
            json_encode($startLocation),
            json_encode($endLocation),
            $departureTime ? date('Y-m-d H:i:s', $departureTimestamp) : date('Y-m-d H:i:s'),
            $directionsResult['summary']['total_distance'],
            $directionsResult['summary']['total_duration'],
            $directionsResult['summary']['duration_in_traffic'],
            $directionsResult['polyline'],
            json_encode($directionsResult['legs'])
        ]);

        $routeId = $pdo->lastInsertId();

        // Log route creation
        logRouteHistory($routeId, 'created', null, null, [
            'customer_count' => count($customerIds),
            'optimization' => $options['optimize'] ?? true
        ]);

        return [
            'success' => true,
            'route_id' => (int)$routeId,
            'optimized_order' => $optimizedCustomerIds,
            'waypoints' => $optimizedWaypoints,
            'summary' => $directionsResult['summary'],
            'polyline' => $directionsResult['polyline'],
            'bounds' => $directionsResult['bounds']
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Recalculate route from current position
 *
 * @param int $routeId Existing route ID
 * @param array $currentLocation ['lat' => float, 'lng' => float]
 * @param array $remainingCustomerIds Customer IDs not yet visited
 * @return array
 */
function recalculateRoute(
    int $routeId,
    array $currentLocation,
    array $remainingCustomerIds
): array {
    try {
        $pdo = getDBConnection();

        // Get original route
        $stmt = $pdo->prepare("SELECT * FROM delivery_routes WHERE id = ?");
        $stmt->execute([$routeId]);
        $route = $stmt->fetch();

        if (!$route) {
            return ['success' => false, 'error' => 'Route not found'];
        }

        // Get remaining customers
        $placeholders = implode(',', array_fill(0, count($remainingCustomerIds), '?'));
        $stmt = $pdo->prepare("
            SELECT id, account_number, practice_name, first_name, last_name,
                   addr1, addr2, city, state, zip, latitude, longitude
            FROM customers
            WHERE id IN ($placeholders) AND latitude IS NOT NULL
        ");
        $stmt->execute($remainingCustomerIds);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($customers)) {
            return ['success' => false, 'error' => 'No valid remaining customers'];
        }

        $customersById = [];
        $waypoints = [];

        foreach ($customers as $customer) {
            $customersById[$customer['id']] = $customer;
            $waypoints[] = [
                'customer_id' => $customer['id'],
                'lat' => (float)$customer['latitude'],
                'lng' => (float)$customer['longitude']
            ];
        }

        // Use current location as start
        $endLocation = json_decode($route['end_location'], true);

        // Get new optimized route
        $directionsResult = getDirections(
            $currentLocation,
            $endLocation,
            $waypoints,
            true, // Always optimize on recalculation
            time()
        );

        if (!$directionsResult['success']) {
            return [
                'success' => false,
                'error' => 'Directions API error: ' . ($directionsResult['error'] ?? 'Unknown')
            ];
        }

        // Build new waypoint list
        $waypointOrder = $directionsResult['waypoint_order'];
        $optimizedWaypoints = [];
        $currentTime = time();
        $cumulativeDuration = 0;

        foreach ($waypointOrder as $index => $originalIndex) {
            $wp = $waypoints[$originalIndex];
            $customer = $customersById[$wp['customer_id']];
            $leg = $directionsResult['legs'][$index];

            $cumulativeDuration += $leg['duration_in_traffic']['value'] ?? $leg['duration']['value'];
            $eta = date('Y-m-d\TH:i:s', $currentTime + $cumulativeDuration);

            $optimizedWaypoints[] = [
                'customer_id' => $wp['customer_id'],
                'account_number' => $customer['account_number'],
                'practice_name' => $customer['practice_name'] ?: ($customer['first_name'] . ' ' . $customer['last_name']),
                'address' => buildFullAddress($customer),
                'lat' => $wp['lat'],
                'lng' => $wp['lng'],
                'stop_number' => $index + 1,
                'eta' => $eta,
                'duration_from_previous' => $leg['duration']['value'],
                'distance_from_previous' => $leg['distance']['value']
            ];
        }

        // Update route in database
        $stmt = $pdo->prepare("
            UPDATE delivery_routes
            SET optimized_order = ?,
                waypoints = ?,
                total_distance = ?,
                total_duration = ?,
                duration_in_traffic = ?,
                polyline = ?,
                legs_data = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $newOptimizedOrder = array_map(function($wp) {
            return $wp['customer_id'];
        }, $optimizedWaypoints);

        $stmt->execute([
            json_encode($newOptimizedOrder),
            json_encode($optimizedWaypoints),
            $directionsResult['summary']['total_distance'],
            $directionsResult['summary']['total_duration'],
            $directionsResult['summary']['duration_in_traffic'],
            $directionsResult['polyline'],
            json_encode($directionsResult['legs']),
            $routeId
        ]);

        // Log recalculation
        logRouteHistory($routeId, 'recalculated', $currentLocation['lat'], $currentLocation['lng'], [
            'remaining_stops' => count($remainingCustomerIds)
        ]);

        return [
            'success' => true,
            'route_id' => $routeId,
            'recalculated_at' => date('Y-m-d\TH:i:s'),
            'optimized_order' => $newOptimizedOrder,
            'next_stop' => $optimizedWaypoints[0] ?? null,
            'remaining_waypoints' => $optimizedWaypoints,
            'summary' => [
                'remaining_distance' => $directionsResult['summary']['total_distance'],
                'remaining_duration' => $directionsResult['summary']['total_duration'],
                'estimated_completion' => end($optimizedWaypoints)['eta'] ?? null
            ],
            'polyline' => $directionsResult['polyline']
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Log route history
 */
function logRouteHistory(int $routeId, string $actionType, ?float $lat, ?float $lng, array $snapshotData = []): void {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO route_history (route_id, action_type, driver_lat, driver_lng, snapshot_data)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $routeId,
            $actionType,
            $lat,
            $lng,
            json_encode($snapshotData)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log route history: " . $e->getMessage());
    }
}

/**
 * Get route by ID
 */
function getRouteById(int $routeId): ?array {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM delivery_routes WHERE id = ?");
        $stmt->execute([$routeId]);
        $route = $stmt->fetch();

        if (!$route) {
            return null;
        }

        // Decode JSON fields
        $route['customer_ids'] = json_decode($route['customer_ids'], true);
        $route['optimized_order'] = json_decode($route['optimized_order'], true);
        $route['waypoints'] = json_decode($route['waypoints'], true);
        $route['start_location'] = json_decode($route['start_location'], true);
        $route['end_location'] = json_decode($route['end_location'], true);
        $route['legs_data'] = json_decode($route['legs_data'], true);

        return $route;

    } catch (Exception $e) {
        return null;
    }
}

/**
 * Update route status
 */
function updateRouteStatus(int $routeId, string $status): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE delivery_routes SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $routeId]);
    } catch (Exception $e) {
        return false;
    }
}
