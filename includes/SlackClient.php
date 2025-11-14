<?php
/**
 * Slack API Client
 * Handles Slack Unified Files API integration
 * Creodent AoX Elevate Dashboard
 */

class SlackClient {
    private $botToken;
    private $apiBaseUrl = 'https://slack.com/api/';
    private $lastRequestTime = 0;
    private $rateLimitDelay = 1000000; // 1 second in microseconds

    /**
     * Constructor
     */
    public function __construct($botToken = null) {
        $this->botToken = $botToken ?? SLACK_BOT_TOKEN;
    }

    /**
     * Make API request to Slack
     *
     * @param string $endpoint
     * @param array $params
     * @param string $method
     * @return array|null
     */
    private function apiRequest($endpoint, $params = [], $method = 'GET') {
        // Rate limiting
        $this->enforceRateLimit();

        $url = $this->apiBaseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->botToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Slack API cURL Error: " . $error);
            return null;
        }

        if ($httpCode !== 200) {
            error_log("Slack API HTTP Error: " . $httpCode);
            return null;
        }

        $data = json_decode($response, true);

        if (!$data['ok']) {
            error_log("Slack API Error: " . ($data['error'] ?? 'Unknown error'));
            return null;
        }

        return $data;
    }

    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit() {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;

        if ($timeSinceLastRequest < ($this->rateLimitDelay / 1000000)) {
            usleep($this->rateLimitDelay - ($timeSinceLastRequest * 1000000));
        }

        $this->lastRequestTime = microtime(true);
    }

    /**
     * Get all files from Slack workspace (paginated)
     *
     * @param array $options
     * @return array List of files
     */
    public function getFiles($options = []) {
        $allFiles = [];
        $cursor = null;
        $page = 1;
        $limit = $options['limit'] ?? 1000;

        do {
            $params = [
                'limit' => min(100, $limit - count($allFiles)), // Max 100 per request
                'types' => $options['types'] ?? 'all',
            ];

            if (!empty($options['channel'])) {
                $params['channel'] = $options['channel'];
            }

            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $response = $this->apiRequest('files.list', $params, 'GET');

            if (!$response || !isset($response['files'])) {
                error_log("Failed to fetch Slack files (page $page)");
                break;
            }

            $files = $response['files'];
            $allFiles = array_merge($allFiles, $files);

            // Check if there are more pages
            $cursor = $response['response_metadata']['next_cursor'] ?? null;
            $page++;

            if (APP_DEBUG) {
                error_log("Fetched " . count($files) . " files from Slack (page $page)");
            }

        } while ($cursor && count($allFiles) < $limit);

        return $allFiles;
    }

    /**
     * Get file info by ID
     *
     * @param string $fileId
     * @return array|null
     */
    public function getFileInfo($fileId) {
        $response = $this->apiRequest('files.info', ['file' => $fileId], 'GET');
        return $response['file'] ?? null;
    }

    /**
     * Download file from Slack
     *
     * @param string $url Slack private URL
     * @param string $destination Local file path
     * @return bool Success status
     */
    public function downloadFile($url, $destination) {
        $headers = [
            'Authorization: Bearer ' . $this->botToken
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes for large files

        $fileContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Failed to download file from Slack: " . $error);
            return false;
        }

        // Ensure directory exists
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($destination, $fileContent) !== false;
    }

    /**
     * Get file URL with authentication
     *
     * @param string $fileId
     * @return string|null
     */
    public function getAuthenticatedFileUrl($fileId) {
        $fileInfo = $this->getFileInfo($fileId);
        return $fileInfo['url_private'] ?? null;
    }

    /**
     * Test API connection
     *
     * @return bool
     */
    public function testConnection() {
        $response = $this->apiRequest('auth.test', [], 'GET');
        return $response !== null && isset($response['ok']) && $response['ok'] === true;
    }

    /**
     * Get workspace info
     *
     * @return array|null
     */
    public function getWorkspaceInfo() {
        $response = $this->apiRequest('auth.test', [], 'GET');
        return $response;
    }

    /**
     * Parse file metadata from Slack response
     *
     * @param array $slackFile
     * @return array Normalized file metadata
     */
    public static function parseFileMetadata($slackFile) {
        return [
            'slack_file_id' => $slackFile['id'] ?? null,
            'file_name' => $slackFile['name'] ?? 'untitled',
            'file_url' => $slackFile['url_private'] ?? null,
            'preview_url' => $slackFile['thumb_360'] ?? $slackFile['thumb_160'] ?? null,
            'permalink' => $slackFile['permalink'] ?? null,
            'mimetype' => $slackFile['mimetype'] ?? null,
            'size' => $slackFile['size'] ?? 0,
            'uploaded_by' => $slackFile['user'] ?? null,
            'uploaded_at' => isset($slackFile['timestamp'])
                ? date('Y-m-d H:i:s', $slackFile['timestamp'])
                : date('Y-m-d H:i:s'),
            'title' => $slackFile['title'] ?? null,
        ];
    }
}

?>
