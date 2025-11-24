<?php
/**
 * Customer Import Functions
 * JSON to MySQL Import Logic
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/functions/google.php';

/**
 * Import customers from JSON file
 *
 * @param string $jsonFilePath Path to JSON file
 * @param bool $geocodeEnabled Whether to geocode addresses
 * @return array Import results
 */
function importCustomersFromJson(string $jsonFilePath, bool $geocodeEnabled = true): array {
    $results = [
        'success' => false,
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'geocoded' => 0
    ];

    // Read JSON file
    if (!file_exists($jsonFilePath)) {
        $results['errors'][] = "File not found: $jsonFilePath";
        return $results;
    }

    $jsonContent = file_get_contents($jsonFilePath);
    $customers = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $results['errors'][] = "JSON parse error: " . json_last_error_msg();
        return $results;
    }

    if (!is_array($customers)) {
        $results['errors'][] = "Invalid JSON structure: expected array";
        return $results;
    }

    $results['total'] = count($customers);

    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        foreach ($customers as $index => $customerData) {
            $importResult = importSingleCustomer($pdo, $customerData, $geocodeEnabled);

            if ($importResult['success']) {
                if ($importResult['action'] === 'inserted') {
                    $results['imported']++;
                } else {
                    $results['updated']++;
                }

                if ($importResult['geocoded'] ?? false) {
                    $results['geocoded']++;
                }
            } else {
                $results['skipped']++;
                $results['errors'][] = "Row $index: " . ($importResult['error'] ?? 'Unknown error');
            }

            // Add delay for geocoding rate limiting
            if ($geocodeEnabled && ($results['geocoded'] % 10 === 0)) {
                usleep(GEOCODING_DELAY_MS * 1000);
            }
        }

        $pdo->commit();
        $results['success'] = true;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $results['errors'][] = "Database error: " . $e->getMessage();
    }

    return $results;
}

/**
 * Import single customer record
 */
function importSingleCustomer(PDO $pdo, array $data, bool $geocodeEnabled): array {
    // Validate required field
    $accountNumber = $data['Account #'] ?? null;

    if (empty($accountNumber)) {
        return ['success' => false, 'error' => 'Missing Account #'];
    }

    // Map JSON fields to database columns
    $mappedData = mapJsonToDbFields($data);

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE account_number = ?");
    $stmt->execute([$accountNumber]);
    $existingId = $stmt->fetchColumn();

    $geocoded = false;

    if ($existingId) {
        // Update existing customer
        $updateFields = [];
        $updateValues = [];

        foreach ($mappedData as $field => $value) {
            if ($field !== 'account_number') {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
        }

        $updateValues[] = $accountNumber;

        $sql = "UPDATE customers SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE account_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);

        $customerId = $existingId;
        $action = 'updated';

    } else {
        // Insert new customer
        $fields = array_keys($mappedData);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO customers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($mappedData));

        $customerId = $pdo->lastInsertId();
        $action = 'inserted';
    }

    // Geocode if enabled and no coordinates
    if ($geocodeEnabled && empty($mappedData['latitude'])) {
        $address = buildFullAddressFromMapped($mappedData);

        if (!empty($address)) {
            $geocodeResult = geocodeAddress($address);

            if ($geocodeResult['success']) {
                $stmt = $pdo->prepare("UPDATE customers SET latitude = ?, longitude = ?, geocoded_at = NOW() WHERE id = ?");
                $stmt->execute([$geocodeResult['lat'], $geocodeResult['lng'], $customerId]);
                $geocoded = true;
            }
        }
    }

    return [
        'success' => true,
        'action' => $action,
        'customer_id' => $customerId,
        'geocoded' => $geocoded
    ];
}

/**
 * Map JSON fields to database column names
 */
function mapJsonToDbFields(array $data): array {
    // Main fields mapping
    $mapping = [
        'Account #'      => 'account_number',
        'Title'          => 'title',
        'FName'          => 'first_name',
        'LName'          => 'last_name',
        'PracticeName'   => 'practice_name',
        'Phone'          => 'phone',
        'fax'            => 'fax',
        'CellPh'         => 'cell_phone',
        'PrimaryEmail'   => 'email',
        'Stmt Email'     => 'stmt_email',
        'addr1'          => 'addr1',
        'addr2'          => 'addr2',
        'addr3'          => 'addr3',
        'city'           => 'city',
        'statecd'        => 'state',
        'zipcd'          => 'zip',
        'RouteName'      => 'route_name',
        'ShipToFlag'     => 'ship_to_flag',
        'SalesPerson'    => 'salesperson',
        'AccountClass'   => 'account_class',
        'AccountType'    => 'account_type',
        'AcctMgr'        => 'account_manager',
        'Territory'      => 'territory',
        'DateCreated'    => 'date_created'
    ];

    $dbData = [];
    $extendedFields = [];

    foreach ($data as $key => $value) {
        if (isset($mapping[$key])) {
            $dbColumn = $mapping[$key];

            // Handle special types
            if ($dbColumn === 'ship_to_flag') {
                $dbData[$dbColumn] = $value ? 1 : 0;
            } elseif ($dbColumn === 'date_created') {
                $dbData[$dbColumn] = parseDate($value);
            } elseif ($dbColumn === 'zip') {
                $dbData[$dbColumn] = (string)$value;
            } else {
                $dbData[$dbColumn] = is_string($value) ? trim($value) : $value;
            }
        } else {
            // Store unmapped fields in extended_json
            if (!in_array($key, ['Latitude', 'Longitude'])) {
                $extendedFields[$key] = $value;
            }
        }
    }

    // Add extended JSON if any unmapped fields
    if (!empty($extendedFields)) {
        $dbData['extended_json'] = json_encode($extendedFields, JSON_UNESCAPED_UNICODE);
    }

    return $dbData;
}

/**
 * Parse date from various formats
 */
function parseDate($value): ?string {
    if (empty($value) || $value === '######') {
        return null;
    }

    // ISO 8601 format
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        $date = new DateTime($value);
        return $date->format('Y-m-d H:i:s');
    }

    // Try strtotime
    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    return null;
}

/**
 * Build full address from mapped data
 */
function buildFullAddressFromMapped(array $data): string {
    $parts = [];

    if (!empty($data['addr1'])) $parts[] = $data['addr1'];
    if (!empty($data['city'])) $parts[] = $data['city'];
    if (!empty($data['state'])) $parts[] = $data['state'];
    if (!empty($data['zip'])) $parts[] = $data['zip'];

    return implode(', ', $parts);
}

/**
 * Import from JSON string (for API use)
 */
function importCustomersFromJsonString(string $jsonString, bool $geocodeEnabled = true): array {
    $customers = json_decode($jsonString, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'errors' => ["JSON parse error: " . json_last_error_msg()]
        ];
    }

    // Write to temp file and use file import
    $tempFile = tempnam(sys_get_temp_dir(), 'customer_import_');
    file_put_contents($tempFile, $jsonString);

    $result = importCustomersFromJson($tempFile, $geocodeEnabled);

    unlink($tempFile);

    return $result;
}

/**
 * Get import statistics
 */
function getImportStats(): array {
    try {
        $pdo = getDBConnection();

        $stats = [];

        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
        $stats['total_customers'] = (int)$stmt->fetchColumn();

        // Customers with coordinates
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE latitude IS NOT NULL");
        $stats['geocoded_customers'] = (int)$stmt->fetchColumn();

        // By route name
        $stmt = $pdo->query("SELECT route_name, COUNT(*) as count FROM customers GROUP BY route_name ORDER BY count DESC");
        $stats['by_route'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Local Courier count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE route_name = ?");
        $stmt->execute([DEFAULT_ROUTE_NAME]);
        $stats['local_courier_count'] = (int)$stmt->fetchColumn();

        return $stats;

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
