<?php
/**
 * API: Get Customers
 * GET /api/customers.php
 *
 * Returns customer list from database with optional filters
 */

require_once dirname(__DIR__) . '/config/config.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('METHOD_NOT_ALLOWED', 'Only GET method is allowed', 405);
}

try {
    $pdo = getDBConnection();

    // Get query parameters
    $routeName = $_GET['route_name'] ?? DEFAULT_ROUTE_NAME;
    $city = $_GET['city'] ?? null;
    $state = $_GET['state'] ?? null;
    $salesperson = $_GET['salesperson'] ?? null;
    $hasCoords = isset($_GET['has_coords']) ? (bool)$_GET['has_coords'] : null;
    $search = $_GET['search'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 100), 500); // Max 500
    $offset = (int)($_GET['offset'] ?? 0);

    // Build query
    $where = [];
    $params = [];

    // Route name filter (default: Local Courier)
    if ($routeName && $routeName !== 'all') {
        $where[] = "route_name = ?";
        $params[] = $routeName;
    }

    // City filter
    if ($city) {
        $where[] = "city LIKE ?";
        $params[] = "%$city%";
    }

    // State filter
    if ($state) {
        $where[] = "state = ?";
        $params[] = $state;
    }

    // Salesperson filter
    if ($salesperson) {
        $where[] = "salesperson = ?";
        $params[] = $salesperson;
    }

    // Has coordinates filter
    if ($hasCoords !== null) {
        if ($hasCoords) {
            $where[] = "latitude IS NOT NULL AND longitude IS NOT NULL";
        } else {
            $where[] = "latitude IS NULL OR longitude IS NULL";
        }
    }

    // Search filter (account number, name, practice name)
    if ($search) {
        $where[] = "(account_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR practice_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    // Only ship-to customers
    $where[] = "ship_to_flag = 1";

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total count
    $countSql = "SELECT COUNT(*) FROM customers $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // Get customers
    $sql = "
        SELECT
            id,
            account_number,
            title,
            first_name,
            last_name,
            practice_name,
            phone,
            fax,
            cell_phone,
            email,
            addr1,
            addr2,
            addr3,
            city,
            state,
            zip,
            route_name,
            salesperson,
            account_class,
            latitude,
            longitude,
            CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 ELSE 0 END as has_coordinates
        FROM customers
        $whereClause
        ORDER BY practice_name ASC, last_name ASC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $formattedCustomers = array_map(function($c) {
        // Build full address
        $addressParts = array_filter([$c['addr1'], $c['addr2'], $c['city'], $c['state'], $c['zip']]);
        $fullAddress = implode(', ', $addressParts);

        // Display name
        $displayName = $c['practice_name'] ?: trim($c['first_name'] . ' ' . $c['last_name']);

        return [
            'id' => (int)$c['id'],
            'account_number' => $c['account_number'],
            'title' => $c['title'],
            'first_name' => $c['first_name'],
            'last_name' => $c['last_name'],
            'practice_name' => $c['practice_name'],
            'display_name' => $displayName,
            'phone' => $c['phone'],
            'email' => $c['email'],
            'full_address' => $fullAddress,
            'addr1' => $c['addr1'],
            'addr2' => $c['addr2'],
            'city' => $c['city'],
            'state' => $c['state'],
            'zip' => $c['zip'],
            'route_name' => $c['route_name'],
            'salesperson' => $c['salesperson'],
            'latitude' => $c['latitude'] ? (float)$c['latitude'] : null,
            'longitude' => $c['longitude'] ? (float)$c['longitude'] : null,
            'has_coordinates' => (bool)$c['has_coordinates']
        ];
    }, $customers);

    // Get filter options for UI
    $routeNames = $pdo->query("SELECT DISTINCT route_name FROM customers WHERE route_name != '' ORDER BY route_name")->fetchAll(PDO::FETCH_COLUMN);
    $cities = $pdo->query("SELECT DISTINCT city FROM customers WHERE city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
    $states = $pdo->query("SELECT DISTINCT state FROM customers WHERE state != '' ORDER BY state")->fetchAll(PDO::FETCH_COLUMN);

    successResponse([
        'customers' => $formattedCustomers,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'route_names' => $routeNames,
            'cities' => $cities,
            'states' => $states
        ]
    ], 'Customers retrieved successfully');

} catch (PDOException $e) {
    error_log("Database error in customers.php: " . $e->getMessage());
    errorResponse('DB_ERROR', 'Database error occurred', 500);
} catch (Exception $e) {
    error_log("Error in customers.php: " . $e->getMessage());
    errorResponse('SERVER_ERROR', 'An error occurred', 500);
}
