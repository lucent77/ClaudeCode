<?php
/**
 * Google Maps API Functions
 * Smart Delivery Route Optimizer
 */

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Geocode an address to coordinates
 *
 * @param string $address Full address string
 * @return array ['success' => bool, 'lat' => float, 'lng' => float, 'formatted_address' => string]
 */
function geocodeAddress(string $address): array {
    // Check cache first
    if (GEOCODING_CACHE_ENABLED) {
        $cached = getCachedGeocode($address);
        if ($cached) {
            return [
                'success' => true,
                'lat' => (float)$cached['latitude'],
                'lng' => (float)$cached['longitude'],
                'formatted_address' => $cached['formatted_address'],
                'from_cache' => true
            ];
        }
    }

    $startTime = microtime(true);

    $params = [
        'address' => $address,
        'key' => GOOGLE_API_KEY
    ];

    $url = GOOGLE_GEOCODING_URL . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $executionTime = microtime(true) - $startTime;

    if ($response === false) {
        logApiCall('geocoding', $params, 'CURL_ERROR', 'Connection failed', $executionTime);
        return ['success' => false, 'error' => 'Connection failed'];
    }

    $data = json_decode($response, true);

    logApiCall('geocoding', $params, $data['status'] ?? 'UNKNOWN', $response, $executionTime);

    if (!isset($data['status']) || $data['status'] !== 'OK') {
        return [
            'success' => false,
            'error' => $data['status'] ?? 'Unknown error',
            'error_message' => $data['error_message'] ?? ''
        ];
    }

    $result = $data['results'][0];
    $location = $result['geometry']['location'];

    // Save to cache
    if (GEOCODING_CACHE_ENABLED) {
        saveGeocodeCache($address, $location['lat'], $location['lng'], $result['formatted_address']);
    }

    return [
        'success' => true,
        'lat' => $location['lat'],
        'lng' => $location['lng'],
        'formatted_address' => $result['formatted_address'],
        'from_cache' => false
    ];
}

/**
 * Get cached geocode result
 */
function getCachedGeocode(string $address): ?array {
    try {
        $pdo = getDBConnection();
        $hash = md5(strtolower(trim($address)));

        $stmt = $pdo->prepare("SELECT * FROM geocoding_cache WHERE address_hash = ?");
        $stmt->execute([$hash]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Save geocode result to cache
 */
function saveGeocodeCache(string $address, float $lat, float $lng, string $formattedAddress): bool {
    try {
        $pdo = getDBConnection();
        $hash = md5(strtolower(trim($address)));

        $stmt = $pdo->prepare("
            INSERT INTO geocoding_cache (address_hash, full_address, latitude, longitude, formatted_address)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE latitude = ?, longitude = ?, formatted_address = ?
        ");

        return $stmt->execute([$hash, $address, $lat, $lng, $formattedAddress, $lat, $lng, $formattedAddress]);
    } catch (Exception $e) {
        error_log("Failed to save geocode cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Get optimized route using Google Directions API
 *
 * @param array $origin ['lat' => float, 'lng' => float]
 * @param array $destination ['lat' => float, 'lng' => float]
 * @param array $waypoints Array of ['lat' => float, 'lng' => float]
 * @param bool $optimize Whether to optimize waypoint order
 * @param int|null $departureTime Unix timestamp
 * @param array $avoid Array of avoid options ['tolls', 'highways', 'ferries']
 * @return array
 */
function getDirections(
    array $origin,
    array $destination,
    array $waypoints = [],
    bool $optimize = true,
    ?int $departureTime = null,
    array $avoid = []
): array {
    $startTime = microtime(true);

    // Build waypoints string
    $waypointsStr = '';
    if (!empty($waypoints)) {
        $waypointCoords = array_map(function($wp) {
            return $wp['lat'] . ',' . $wp['lng'];
        }, $waypoints);

        $prefix = $optimize ? 'optimize:true|' : '';
        $waypointsStr = $prefix . implode('|', $waypointCoords);
    }

    $params = [
        'origin' => $origin['lat'] . ',' . $origin['lng'],
        'destination' => $destination['lat'] . ',' . $destination['lng'],
        'key' => GOOGLE_API_KEY
    ];

    if ($waypointsStr) {
        $params['waypoints'] = $waypointsStr;
    }

    // Add departure time for traffic data
    if ($departureTime) {
        $params['departure_time'] = $departureTime;
        $params['traffic_model'] = DEFAULT_TRAFFIC_MODEL;
    } else {
        $params['departure_time'] = 'now';
        $params['traffic_model'] = DEFAULT_TRAFFIC_MODEL;
    }

    // Add avoid options
    if (!empty($avoid)) {
        $params['avoid'] = implode('|', $avoid);
    }

    $url = GOOGLE_DIRECTIONS_URL . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $executionTime = microtime(true) - $startTime;

    if ($response === false) {
        logApiCall('directions', $params, 'CURL_ERROR', 'Connection failed', $executionTime);
        return ['success' => false, 'error' => 'Connection failed'];
    }

    $data = json_decode($response, true);

    logApiCall('directions', $params, $data['status'] ?? 'UNKNOWN', substr($response, 0, 5000), $executionTime);

    if (!isset($data['status']) || $data['status'] !== 'OK') {
        return [
            'success' => false,
            'error' => $data['status'] ?? 'Unknown error',
            'error_message' => $data['error_message'] ?? ''
        ];
    }

    $route = $data['routes'][0];

    // Calculate totals from legs
    $totalDistance = 0;
    $totalDuration = 0;
    $totalDurationTraffic = 0;
    $legs = [];

    foreach ($route['legs'] as $leg) {
        $totalDistance += $leg['distance']['value'];
        $totalDuration += $leg['duration']['value'];
        $totalDurationTraffic += $leg['duration_in_traffic']['value'] ?? $leg['duration']['value'];

        $legs[] = [
            'start_address' => $leg['start_address'],
            'end_address' => $leg['end_address'],
            'distance' => $leg['distance'],
            'duration' => $leg['duration'],
            'duration_in_traffic' => $leg['duration_in_traffic'] ?? $leg['duration']
        ];
    }

    return [
        'success' => true,
        'waypoint_order' => $route['waypoint_order'] ?? [],
        'polyline' => $route['overview_polyline']['points'],
        'bounds' => $route['bounds'],
        'legs' => $legs,
        'summary' => [
            'total_distance' => $totalDistance,
            'total_distance_text' => formatDistance($totalDistance),
            'total_duration' => $totalDuration,
            'total_duration_text' => formatDuration($totalDuration),
            'duration_in_traffic' => $totalDurationTraffic,
            'duration_in_traffic_text' => formatDuration($totalDurationTraffic)
        ]
    ];
}

/**
 * Format distance in meters to human readable
 */
function formatDistance(int $meters): string {
    if ($meters >= 1000) {
        $km = $meters / 1000;
        return number_format($km, 1) . ' km';
    }
    return $meters . ' m';
}

/**
 * Format duration in seconds to human readable
 */
function formatDuration(int $seconds): string {
    if ($seconds < 60) {
        return $seconds . ' sec';
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($hours > 0) {
        return $hours . ' hr ' . $minutes . ' min';
    }

    return $minutes . ' min';
}

/**
 * Build full address from components
 */
function buildFullAddress(array $customer): string {
    $parts = [];

    if (!empty($customer['addr1'])) $parts[] = $customer['addr1'];
    if (!empty($customer['addr2'])) $parts[] = $customer['addr2'];
    if (!empty($customer['city'])) $parts[] = $customer['city'];
    if (!empty($customer['state'])) $parts[] = $customer['state'];
    if (!empty($customer['zip'])) $parts[] = $customer['zip'];

    return implode(', ', $parts);
}

/**
 * Geocode customer and update database
 */
function geocodeCustomer(int $customerId): array {
    try {
        $pdo = getDBConnection();

        // Get customer
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['success' => false, 'error' => 'Customer not found'];
        }

        $address = buildFullAddress($customer);

        if (empty($address)) {
            return ['success' => false, 'error' => 'No address available'];
        }

        // Geocode
        $result = geocodeAddress($address);

        if (!$result['success']) {
            return $result;
        }

        // Update customer with coordinates
        $stmt = $pdo->prepare("
            UPDATE customers
            SET latitude = ?, longitude = ?, geocoded_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$result['lat'], $result['lng'], $customerId]);

        return [
            'success' => true,
            'customer_id' => $customerId,
            'latitude' => $result['lat'],
            'longitude' => $result['lng'],
            'formatted_address' => $result['formatted_address']
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
