<?php
/**
 * API: Add Customer
 * POST /api/add-customer.php
 *
 * Adds a new customer with optional geocoding
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/functions/google.php';

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
    $pdo = getDBConnection();

    // Get request body
    $input = getRequestBody();

    // Validate required fields
    $required = ['account_number', 'addr1', 'city', 'state', 'zip'];
    $missing = validateRequired($input, $required);

    if (!empty($missing)) {
        errorResponse('VALIDATION_ERROR', 'Missing required fields: ' . implode(', ', $missing), 400, [
            'missing_fields' => $missing
        ]);
    }

    // Check for duplicate account number
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE account_number = ?");
    $stmt->execute([sanitizeString($input['account_number'])]);

    if ($stmt->fetch()) {
        errorResponse('DUPLICATE_ERROR', 'Account number already exists', 400, [
            'account_number' => $input['account_number']
        ]);
    }

    // Prepare customer data
    $customerData = [
        'account_number' => sanitizeString($input['account_number']),
        'title' => sanitizeString($input['title'] ?? ''),
        'first_name' => sanitizeString($input['first_name'] ?? ''),
        'last_name' => sanitizeString($input['last_name'] ?? ''),
        'practice_name' => sanitizeString($input['practice_name'] ?? ''),
        'phone' => sanitizeString($input['phone'] ?? ''),
        'fax' => sanitizeString($input['fax'] ?? ''),
        'cell_phone' => sanitizeString($input['cell_phone'] ?? ''),
        'email' => sanitizeString($input['email'] ?? ''),
        'stmt_email' => sanitizeString($input['stmt_email'] ?? ''),
        'addr1' => sanitizeString($input['addr1']),
        'addr2' => sanitizeString($input['addr2'] ?? ''),
        'addr3' => sanitizeString($input['addr3'] ?? ''),
        'city' => sanitizeString($input['city']),
        'state' => sanitizeString($input['state']),
        'zip' => sanitizeString($input['zip']),
        'route_name' => sanitizeString($input['route_name'] ?? DEFAULT_ROUTE_NAME),
        'ship_to_flag' => isset($input['ship_to_flag']) ? ($input['ship_to_flag'] ? 1 : 0) : 1,
        'salesperson' => sanitizeString($input['salesperson'] ?? ''),
        'account_class' => sanitizeString($input['account_class'] ?? ''),
        'account_type' => (int)($input['account_type'] ?? 0),
        'account_manager' => sanitizeString($input['account_manager'] ?? ''),
        'territory' => sanitizeString($input['territory'] ?? '')
    ];

    // Geocode if requested (default true)
    $geocodeEnabled = $input['geocode'] ?? true;
    $geocodeResult = null;

    if ($geocodeEnabled) {
        $address = buildFullAddress($customerData);

        if (!empty($address)) {
            $geocodeResult = geocodeAddress($address);

            if ($geocodeResult['success']) {
                $customerData['latitude'] = $geocodeResult['lat'];
                $customerData['longitude'] = $geocodeResult['lng'];
                $customerData['geocoded_at'] = date('Y-m-d H:i:s');
            }
        }
    }

    // Insert customer
    $fields = array_keys($customerData);
    $placeholders = array_fill(0, count($fields), '?');

    $sql = "INSERT INTO customers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($customerData));

    $customerId = $pdo->lastInsertId();

    // Build response
    $responseData = [
        'customer_id' => (int)$customerId,
        'account_number' => $customerData['account_number']
    ];

    if ($geocodeEnabled) {
        $responseData['geocoding'] = [
            'status' => $geocodeResult && $geocodeResult['success'] ? 'success' : 'failed',
            'latitude' => $customerData['latitude'] ?? null,
            'longitude' => $customerData['longitude'] ?? null,
            'formatted_address' => $geocodeResult['formatted_address'] ?? null,
            'error' => (!$geocodeResult || !$geocodeResult['success']) ? ($geocodeResult['error'] ?? 'Geocoding failed') : null
        ];
    }

    successResponse($responseData, 'Customer added successfully');

} catch (PDOException $e) {
    error_log("Database error in add-customer.php: " . $e->getMessage());

    if ($e->getCode() == 23000) {
        errorResponse('DUPLICATE_ERROR', 'Account number already exists', 400);
    }

    errorResponse('DB_ERROR', 'Database error occurred', 500);
} catch (Exception $e) {
    error_log("Error in add-customer.php: " . $e->getMessage());
    errorResponse('SERVER_ERROR', 'An error occurred', 500);
}
