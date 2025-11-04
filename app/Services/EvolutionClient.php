<?php

namespace App\Services;

/**
 * Evolution Client
 *
 * Handles communication with Evolution Web Portal V18 via XML API
 * Supports SQL Server direct connection as fallback
 */
class EvolutionClient
{
    private array $config;
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private int $retryCount;
    private int $retryDelay;

    public function __construct(array $config)
    {
        $this->config = $config['evolution'] ?? [];
        $this->baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $this->username = $this->config['username'] ?? '';
        $this->password = $this->config['password'] ?? '';
        $this->timeout = $this->config['timeout'] ?? 30;
        $this->retryCount = $this->config['retry_count'] ?? 3;
        $this->retryDelay = $this->config['retry_delay'] ?? 2;
    }

    /**
     * Get case list from Evolution Portal
     */
    public function getCaseList(string $fromDate, string $toDate): array
    {
        $xmlRequest = $this->buildCaseListRequest($fromDate, $toDate);
        $response = $this->sendRequest('cases_caselist', $xmlRequest);

        return $this->parseCaseListResponse($response);
    }

    /**
     * Get detailed case information
     */
    public function getCaseInformation(string $caseNumber): ?array
    {
        $xmlRequest = $this->buildCaseInfoRequest($caseNumber);
        $response = $this->sendRequest('case_caseinformation', $xmlRequest);

        return $this->parseCaseInfoResponse($response);
    }

    /**
     * Test connection to Evolution Portal
     */
    public function testConnection(): array
    {
        try {
            $xmlRequest = $this->buildLoginRequest();
            $response = $this->sendRequest('account_login', $xmlRequest);

            $xml = simplexml_load_string($response);
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Invalid XML response from server'
                ];
            }

            $status = (string) ($xml->status ?? 'error');

            return [
                'success' => $status === 'success',
                'message' => $status === 'success' ? 'Connected successfully' : 'Authentication failed',
                'response' => $response
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get case from SQL Server directly (NYC or HV)
     */
    public function getCaseFromSQLServer(string $caseNumber, string $location = 'HV'): ?array
    {
        // Check if sqlsrv extension is available
        if (!extension_loaded('sqlsrv')) {
            throw new \Exception('SQL Server extension (sqlsrv) is not installed');
        }

        $serverConfig = $this->config['sql_servers'][$location] ?? null;
        if (!$serverConfig || !($serverConfig['enabled'] ?? false)) {
            throw new \Exception("SQL Server connection not configured for location: {$location}");
        }

        $debugInfo = [
            'location' => $location,
            'server_config' => [
                'host' => $serverConfig['host'],
                'database' => $serverConfig['database'],
                'enabled' => $serverConfig['enabled']
            ],
            'steps' => []
        ];

        try {
            // Connection info
            $connectionInfo = [
                "Database" => $serverConfig['database'],
                "UID" => $serverConfig['username'],
                "PWD" => $serverConfig['password'],
                "CharacterSet" => "UTF-8",
                "ReturnDatesAsStrings" => true
            ];

            $debugInfo['steps'][] = "Attempting connection to {$serverConfig['host']}...";

            // Connect
            $startTime = microtime(true);
            $conn = sqlsrv_connect($serverConfig['host'], $connectionInfo);
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($conn === false) {
                $errors = sqlsrv_errors();
                $debugInfo['steps'][] = "Connection failed after {$connectionTime}ms";
                $debugInfo['connection_errors'] = $errors;
                throw new \Exception('SQL Server connection failed: ' . json_encode($errors));
            }

            $debugInfo['steps'][] = "Connected successfully in {$connectionTime}ms";

            // Call stored procedure
            $sql = "{CALL tl_sp_Order_CaseInfo_Creo_Report(?)}";
            $debugInfo['steps'][] = "Executing stored procedure with case number: {$caseNumber}";

            $params = [$caseNumber];
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $debugInfo['steps'][] = "Query failed";
                $debugInfo['query_errors'] = $errors;
                sqlsrv_close($conn);
                throw new \Exception('SQL Server query failed: ' . json_encode($errors));
            }

            $debugInfo['steps'][] = "Query executed successfully";

            // Fetch result
            $result = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }

            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);

            $debugInfo['steps'][] = "Found " . count($result) . " rows";

            if (empty($result)) {
                $debugInfo['steps'][] = "No data found for case number: {$caseNumber}";
                return null;
            }

            // Parse result
            $caseData = $this->parseSQLServerResult($result[0]);
            $caseData['_debug_info'] = $debugInfo;

            return $caseData;

        } catch (\Exception $e) {
            $debugInfo['steps'][] = "Exception: " . $e->getMessage();
            $debugInfo['exception'] = $e->getMessage();

            // Log error
            $this->logError('SQL Server error: ' . $e->getMessage(), [
                'case_number' => $caseNumber,
                'location' => $location,
                'debug_info' => $debugInfo
            ]);

            throw $e;
        }
    }

    /**
     * Parse SQL Server result to case data format
     */
    private function parseSQLServerResult(array $row): array
    {
        return [
            'external_case_no' => $row['CaseNo'] ?? $row['caseno'] ?? null,
            'patient_name' => $row['PatientName'] ?? $row['patient_name'] ?? null,
            'lab_name' => $row['LabName'] ?? $row['lab_name'] ?? null,
            'lab_number' => $row['LabNo'] ?? $row['lab_number'] ?? null,
            'patient_number' => $row['PatientNo'] ?? $row['patient_number'] ?? null,
            'due_date' => $row['DueDate'] ?? $row['due_date'] ?? null,
            'pan' => $row['PAN'] ?? $row['pan'] ?? null,
            'instructions' => $row['Instructions'] ?? $row['instructions'] ?? null,
            'preferences' => $row['Preferences'] ?? $row['preferences'] ?? null,
            'notes' => $row['Notes'] ?? $row['notes'] ?? null,
            'raw_data' => $row
        ];
    }

    /**
     * Build login request XML
     */
    private function buildLoginRequest(): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<request>
    <event>account_login</event>
    <username>{$this->username}</username>
    <password>{$this->password}</password>
</request>";
    }

    /**
     * Build case list request XML
     */
    private function buildCaseListRequest(string $fromDate, string $toDate): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<request>
    <event>cases_caselist</event>
    <username>{$this->username}</username>
    <password>{$this->password}</password>
    <from_date>{$fromDate}</from_date>
    <to_date>{$toDate}</to_date>
</request>";
    }

    /**
     * Build case info request XML
     */
    private function buildCaseInfoRequest(string $caseNumber): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<request>
    <event>case_caseinformation</event>
    <username>{$this->username}</username>
    <password>{$this->password}</password>
    <case_number>{$caseNumber}</case_number>
</request>";
    }

    /**
     * Send HTTP request to Evolution Portal
     */
    private function sendRequest(string $event, string $xmlData, int $attempt = 1): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api.php',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Content-Length: ' . strlen($xmlData)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Handle errors with retry
        if ($response === false || $httpCode !== 200) {
            if ($attempt < $this->retryCount) {
                sleep($this->retryDelay * $attempt);
                return $this->sendRequest($event, $xmlData, $attempt + 1);
            }

            $errorMsg = $error ?: "HTTP {$httpCode}";
            throw new \Exception("Evolution Portal request failed: {$errorMsg}");
        }

        return $response;
    }

    /**
     * Parse case list XML response
     */
    private function parseCaseListResponse(string $xmlResponse): array
    {
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            throw new \Exception('Invalid XML response from Evolution Portal');
        }

        $cases = [];
        foreach ($xml->case ?? [] as $case) {
            $cases[] = [
                'external_case_no' => (string) $case->case_number,
                'patient_name' => (string) $case->patient_name,
                'lab_name' => (string) $case->lab_name,
                'due_date' => (string) $case->due_date,
                'status' => (string) ($case->status ?? 'new'),
                'raw_xml' => $case->asXML()
            ];
        }

        return $cases;
    }

    /**
     * Parse case info XML response
     */
    private function parseCaseInfoResponse(string $xmlResponse): ?array
    {
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            throw new \Exception('Invalid XML response from Evolution Portal');
        }

        if (!isset($xml->case)) {
            return null;
        }

        $case = $xml->case;

        return [
            'external_case_no' => (string) $case->case_number,
            'patient_name' => (string) $case->patient_name,
            'patient_number' => (string) ($case->patient_number ?? ''),
            'lab_name' => (string) $case->lab_name,
            'lab_number' => (string) ($case->lab_number ?? ''),
            'due_date' => (string) $case->due_date,
            'instructions' => (string) ($case->instructions ?? ''),
            'preferences' => (string) ($case->preferences ?? ''),
            'notes' => (string) ($case->notes ?? ''),
            'status' => (string) ($case->status ?? 'new'),
            'priority' => (string) ($case->priority ?? 'normal'),
            'raw_xml' => $case->asXML()
        ];
    }

    /**
     * Log error
     */
    private function logError(string $message, array $context = []): void
    {
        $logFile = BASE_PATH . '/storage/logs/evolution_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        if (!empty($context)) {
            $logMessage .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . PHP_EOL;
        }
        $logMessage .= str_repeat('-', 80) . PHP_EOL;

        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
