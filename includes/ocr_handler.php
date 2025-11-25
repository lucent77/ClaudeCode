<?php
/**
 * Magic Rx Scanner - OCR Handler Class
 *
 * Google Cloud Document AI and Vision API integration
 * Handles OAuth 2.0 authentication and API calls
 */

require_once __DIR__ . '/config.php';

class OCRHandler {
    private $accessToken;
    private $tokenExpiry;
    private $projectId;
    private $location;
    private $processorId;

    public function __construct() {
        $this->projectId = GOOGLE_PROJECT_ID;
        $this->location = GOOGLE_LOCATION;
        $this->processorId = GOOGLE_PROCESSOR_ID;
        $this->accessToken = null;
        $this->tokenExpiry = 0;
    }

    /**
     * Process document using Google Document AI
     *
     * @param string $imagePath Path to image file
     * @return array ['success' => bool, 'text' => string, 'fields' => array, 'confidence' => float, 'error' => string]
     */
    public function processDocumentAI($imagePath) {
        try {
            // Get access token
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Failed to obtain access token'];
            }

            // Read image file
            $imageContent = file_get_contents($imagePath);
            if ($imageContent === false) {
                return ['success' => false, 'error' => 'Failed to read image file'];
            }

            // Prepare API request
            $url = DOCUMENT_AI_ENDPOINT . "/projects/{$this->projectId}/locations/{$this->location}/processors/{$this->processorId}:process";

            $requestBody = [
                'rawDocument' => [
                    'content' => base64_encode($imageContent),
                    'mimeType' => mime_content_type($imagePath)
                ]
            ];

            // Make API call
            $response = $this->makeApiCall($url, $token, $requestBody);

            if (!$response['success']) {
                return $response;
            }

            // Parse response
            $result = $this->parseDocumentAIResponse($response['data']);
            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process image using Google Vision API (Text Detection)
     *
     * @param string $imagePath Path to image file
     * @return array ['success' => bool, 'text' => string, 'confidence' => float, 'error' => string]
     */
    public function processVisionAPI($imagePath) {
        try {
            // Get access token
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Failed to obtain access token'];
            }

            // Read image file
            $imageContent = file_get_contents($imagePath);
            if ($imageContent === false) {
                return ['success' => false, 'error' => 'Failed to read image file'];
            }

            // Prepare API request
            $url = VISION_API_ENDPOINT . "/images:annotate";

            $requestBody = [
                'requests' => [
                    [
                        'image' => [
                            'content' => base64_encode($imageContent)
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 50
                            ]
                        ]
                    ]
                ]
            ];

            // Make API call
            $response = $this->makeApiCall($url, $token, $requestBody);

            if (!$response['success']) {
                return $response;
            }

            // Parse response
            $result = $this->parseVisionAPIResponse($response['data']);
            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get OAuth 2.0 access token using Service Account
     *
     * @return string|false Access token or false on failure
     */
    private function getAccessToken() {
        // Check if token is still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        try {
            // Load service account credentials
            if (!file_exists(GOOGLE_APPLICATION_CREDENTIALS)) {
                throw new Exception('Service account credentials file not found');
            }

            $credentials = json_decode(file_get_contents(GOOGLE_APPLICATION_CREDENTIALS), true);
            if (!$credentials) {
                throw new Exception('Failed to parse service account credentials');
            }

            // Create JWT
            $now = time();
            $jwt = $this->createJWT([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ], $credentials['private_key']);

            // Request access token
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Failed to obtain access token: ' . $response);
            }

            $responseData = json_decode($response, true);
            if (!isset($responseData['access_token'])) {
                throw new Exception('Access token not found in response');
            }

            // Cache token
            $this->accessToken = $responseData['access_token'];
            $this->tokenExpiry = $now + ($responseData['expires_in'] ?? 3600) - 60; // 60s buffer

            return $this->accessToken;

        } catch (Exception $e) {
            $this->logError("Access token error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create JWT for Google OAuth
     *
     * @param array $payload JWT payload
     * @param string $privateKey Private key from service account
     * @return string JWT token
     */
    private function createJWT($payload, $privateKey) {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);

        $signature = '';
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Base64 URL encode
     *
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Make API call to Google Cloud
     *
     * @param string $url API endpoint URL
     * @param string $token Access token
     * @param array $body Request body
     * @param int $retries Number of retries for rate limiting
     * @return array ['success' => bool, 'data' => array, 'error' => string]
     */
    private function makeApiCall($url, $token, $body, $retries = 3) {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $attempt = 0;
        while ($attempt < $retries) {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200) {
                curl_close($ch);
                $data = json_decode($response, true);
                return ['success' => true, 'data' => $data, 'error' => ''];
            } elseif ($httpCode === 429) {
                // Rate limited, retry with exponential backoff
                $attempt++;
                sleep(pow(2, $attempt));
            } else {
                curl_close($ch);
                return ['success' => false, 'error' => "API call failed with status {$httpCode}: {$response}"];
            }
        }

        curl_close($ch);
        return ['success' => false, 'error' => 'API call failed after retries'];
    }

    /**
     * Parse Document AI response
     *
     * @param array $data Response data
     * @return array Parsed data
     */
    private function parseDocumentAIResponse($data) {
        if (!isset($data['document'])) {
            return ['success' => false, 'error' => 'Invalid Document AI response'];
        }

        $document = $data['document'];
        $text = $document['text'] ?? '';
        $fields = [];

        // Extract form fields
        if (isset($document['pages'])) {
            foreach ($document['pages'] as $page) {
                if (isset($page['formFields'])) {
                    foreach ($page['formFields'] as $field) {
                        $fieldName = $this->extractFieldText($field['fieldName'] ?? [], $text);
                        $fieldValue = $this->extractFieldText($field['fieldValue'] ?? [], $text);
                        $confidence = $field['fieldValue']['confidence'] ?? 0;

                        $fields[] = [
                            'name' => $fieldName,
                            'value' => $fieldValue,
                            'confidence' => round($confidence * 100, 2)
                        ];
                    }
                }
            }
        }

        return [
            'success' => true,
            'text' => $text,
            'fields' => $fields,
            'confidence' => isset($document['pages'][0]['confidence']) ? round($document['pages'][0]['confidence'] * 100, 2) : 0,
            'error' => ''
        ];
    }

    /**
     * Parse Vision API response
     *
     * @param array $data Response data
     * @return array Parsed data
     */
    private function parseVisionAPIResponse($data) {
        if (!isset($data['responses'][0])) {
            return ['success' => false, 'error' => 'Invalid Vision API response'];
        }

        $response = $data['responses'][0];

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']['message'] ?? 'Unknown error'];
        }

        $text = '';
        $confidence = 0;

        if (isset($response['textAnnotations']) && count($response['textAnnotations']) > 0) {
            // First annotation contains full text
            $text = $response['textAnnotations'][0]['description'] ?? '';
            $confidence = isset($response['textAnnotations'][0]['confidence']) ? round($response['textAnnotations'][0]['confidence'] * 100, 2) : 0;
        }

        return [
            'success' => true,
            'text' => $text,
            'confidence' => $confidence,
            'error' => ''
        ];
    }

    /**
     * Extract field text from Document AI text anchors
     *
     * @param array $field Field data with textAnchor
     * @param string $fullText Full document text
     * @return string Extracted text
     */
    private function extractFieldText($field, $fullText) {
        if (!isset($field['textAnchor']['textSegments'])) {
            return '';
        }

        $text = '';
        foreach ($field['textAnchor']['textSegments'] as $segment) {
            $startIndex = $segment['startIndex'] ?? 0;
            $endIndex = $segment['endIndex'] ?? strlen($fullText);
            $text .= substr($fullText, $startIndex, $endIndex - $startIndex);
        }

        return trim($text);
    }

    /**
     * Log errors
     *
     * @param string $message Error message
     */
    private function logError($message) {
        if (ENABLE_LOGGING) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] OCR ERROR: {$message}" . PHP_EOL;
            error_log($logMessage, 3, LOG_FILE);
        }
    }
}
