<?php
/**
 * Evolution Web Portal V18 Client
 *
 * Handles XML-based communication with Evolution Web Portal
 * Features:
 * - Automatic retry with exponential backoff
 * - Request/response logging
 * - XML parsing and validation
 * - All supported events (account_login, cases_caselist, etc.)
 */

namespace App\Services;

use Exception;
use SimpleXMLElement;

class EvolutionClient
{
    private array $config;
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private int $retryCount;
    private int $retryDelay;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->config = $config['evolution'];

        $this->baseUrl = $this->config['base_url'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->timeout = $this->config['timeout'];
        $this->retryCount = $this->config['retry_count'];
        $this->retryDelay = $this->config['retry_delay'];
    }

    /**
     * Account Login
     * Verify credentials and get user permissions
     */
    public function accountLogin(?string $username = null, ?string $password = null): array
    {
        $requestData = [
            'username' => $username ?? $this->username,
            'password' => $password ?? $this->password,
        ];

        $response = $this->sendRequest('account_login', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Get Cases List
     * Retrieve cases within date range
     */
    public function getCasesList(string $startDate, string $endDate, array $additionalParams = []): array
    {
        $requestData = array_merge([
            'username' => $this->username,
            'password' => $this->password,
            'startdate' => $startDate,
            'enddate' => $endDate,
        ], $additionalParams);

        $response = $this->sendRequest('cases_caselist', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Get Case Information
     * Get detailed information for a specific case
     */
    public function getCaseInformation(string $caseNo, array $additionalParams = []): array
    {
        $requestData = array_merge([
            'username' => $this->username,
            'password' => $this->password,
            'caseno' => $caseNo,
        ], $additionalParams);

        $response = $this->sendRequest('case_caseinformation', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Get Case Notes
     */
    public function getCaseNotes(string $caseNo): array
    {
        $requestData = [
            'username' => $this->username,
            'password' => $this->password,
            'caseno' => $caseNo,
        ];

        $response = $this->sendRequest('case_noteget', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Add Case Note
     */
    public function addCaseNote(string $caseNo, string $note, string $noteType = 'general'): array
    {
        $requestData = [
            'username' => $this->username,
            'password' => $this->password,
            'caseno' => $caseNo,
            'note' => $note,
            'notetype' => $noteType,
        ];

        $response = $this->sendRequest('case_noteadd', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Get Case Images List
     */
    public function getCaseImages(string $caseNo): array
    {
        $requestData = [
            'username' => $this->username,
            'password' => $this->password,
            'caseno' => $caseNo,
        ];

        $response = $this->sendRequest('case_imagelist', $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Generic event caller
     * Allows calling any Evolution event
     */
    public function callEvent(string $eventName, array $params = []): array
    {
        $requestData = array_merge([
            'username' => $this->username,
            'password' => $this->password,
        ], $params);

        $response = $this->sendRequest($eventName, $requestData);
        return $this->parseResponse($response);
    }

    /**
     * Send request to Evolution Web Portal with retry logic
     */
    private function sendRequest(string $event, array $data): string
    {
        $url = $this->baseUrl . '/?event=' . urlencode($event);
        $xmlRequest = $this->buildXmlRequest($data);

        $attempt = 0;
        $lastError = null;

        while ($attempt <= $this->retryCount) {
            try {
                $response = $this->makeHttpRequest($url, $xmlRequest);

                // Log successful request
                $this->logRequest($event, $xmlRequest, $response, 'success');

                return $response;
            } catch (Exception $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt <= $this->retryCount) {
                    // Exponential backoff: 2s, 4s, 8s, 16s
                    $delay = $this->retryDelay * pow(2, $attempt - 1);
                    sleep($delay);
                }
            }
        }

        // All retries failed
        $errorMessage = $lastError ? $lastError->getMessage() : 'Unknown error';
        $this->logRequest($event, $xmlRequest, $errorMessage, 'error');

        throw new Exception("Evolution API request failed after {$this->retryCount} retries: {$errorMessage}");
    }

    /**
     * Make HTTP POST request with XML
     */
    private function makeHttpRequest(string $url, string $xmlData): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Content-Length: ' . strlen($xmlData),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP error: {$httpCode}");
        }

        if ($response === false) {
            throw new Exception("Empty response from Evolution Web Portal");
        }

        return $response;
    }

    /**
     * Build XML request from array
     */
    private function buildXmlRequest(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->arrayToXml($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Convert array to XML recursively
     */
    private function arrayToXml(array $data, SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->arrayToXml($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * Parse XML response to array
     */
    private function parseResponse(string $xmlString): array
    {
        // Remove BOM if present
        $xmlString = preg_replace('/^\\xEF\\xBB\\xBF/', '', $xmlString);

        try {
            $xml = new SimpleXMLElement($xmlString);
            return $this->xmlToArray($xml);
        } catch (Exception $e) {
            throw new Exception("Failed to parse Evolution XML response: " . $e->getMessage());
        }
    }

    /**
     * Convert XML to array recursively
     */
    private function xmlToArray(SimpleXMLElement $xml): array
    {
        $result = [];

        // Get attributes
        foreach ($xml->attributes() as $key => $value) {
            $result['@' . $key] = (string)$value;
        }

        // Get children
        if ($xml->count() > 0) {
            foreach ($xml->children() as $key => $value) {
                $key = (string)$key;

                if ($value->count() > 0 || $value->attributes()->count() > 0) {
                    $converted = $this->xmlToArray($value);
                } else {
                    $converted = (string)$value;
                }

                // Handle multiple elements with same name
                if (isset($result[$key])) {
                    if (!is_array($result[$key]) || !isset($result[$key][0])) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key][] = $converted;
                } else {
                    $result[$key] = $converted;
                }
            }
        } else {
            // Leaf node
            $result = (string)$xml;
        }

        return $result;
    }

    /**
     * Log request/response to database
     */
    private function logRequest(string $event, string $request, string $response, string $status): void
    {
        try {
            $db = \App\Core\Database::getInstance();

            // Sanitize password from logs
            $sanitizedRequest = preg_replace(
                '/<password>.*?<\/password>/i',
                '<password>***REDACTED***</password>',
                $request
            );

            $db->insert('import_jobs', [
                'job_type' => 'evo_' . $event,
                'status' => $status,
                'started_at' => date('Y-m-d H:i:s'),
                'ended_at' => date('Y-m-d H:i:s'),
                'raw_request' => $sanitizedRequest,
                'raw_response' => $status === 'success' ? $response : null,
                'error_details' => $status === 'error' ? $response : null,
            ]);
        } catch (Exception $e) {
            // Don't fail the main request if logging fails
            error_log("Failed to log Evolution request: " . $e->getMessage());
        }
    }

    /**
     * Test connection to Evolution Web Portal
     */
    public function testConnection(): array
    {
        try {
            $result = $this->accountLogin();
            return [
                'success' => true,
                'message' => 'Successfully connected to Evolution Web Portal',
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Evolution Web Portal',
                'error' => $e->getMessage()
            ];
        }
    }
}
