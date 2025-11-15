<?php

namespace App\Services;

class SlackService
{
    private $botToken;
    private $workspaceId;
    private $canvasId;
    private $apiBaseUrl;

    public function __construct()
    {
        $config = include(__DIR__ . '/../../config/services.php');
        $this->botToken = $config['slack']['bot_token'];
        $this->workspaceId = $config['slack']['workspace_id'];
        $this->canvasId = $config['slack']['canvas_id'];
        $this->apiBaseUrl = $config['slack']['api_base_url'];
    }

    /**
     * Make a request to Slack API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->apiBaseUrl . '/' . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->botToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Slack API Error: HTTP {$httpCode} - {$response}");
            return null;
        }

        $result = json_decode($response, true);

        if (!$result || !isset($result['ok']) || !$result['ok']) {
            error_log("Slack API Error: " . ($result['error'] ?? 'Unknown error'));
            return null;
        }

        return $result;
    }

    /**
     * Get Canvas content
     */
    public function getCanvas($canvasId = null)
    {
        $canvasId = $canvasId ?? $this->canvasId;

        $result = $this->makeRequest('conversations.canvases.sections.list', 'GET', [
            'canvas_id' => $canvasId,
        ]);

        return $result;
    }

    /**
     * Sync cases from Slack List
     * This is a simplified version - you'll need to implement the actual List API integration
     */
    public function syncCasesFromList()
    {
        // Note: Slack's List API is relatively new
        // This is a placeholder for the actual implementation
        // You may need to use the Canvas API or other methods to access List data

        $cases = [];

        // Example: Fetch canvas content
        $canvasData = $this->getCanvas();

        if (!$canvasData) {
            return [
                'success' => false,
                'error' => 'Failed to fetch canvas data',
                'cases' => [],
            ];
        }

        // Parse the canvas data to extract List items
        // This will depend on how the List is embedded in the Canvas
        // For now, return a mock response

        return [
            'success' => true,
            'cases' => $cases,
            'count' => count($cases),
        ];
    }

    /**
     * Get file information from Slack
     */
    public function getFileInfo($fileId)
    {
        $result = $this->makeRequest('files.info', 'GET', [
            'file' => $fileId,
        ]);

        if (!$result || !isset($result['file'])) {
            return null;
        }

        return $result['file'];
    }

    /**
     * Download file from Slack
     */
    public function downloadFile($fileId, $savePath)
    {
        $fileInfo = $this->getFileInfo($fileId);

        if (!$fileInfo || !isset($fileInfo['url_private'])) {
            return false;
        }

        $fileUrl = $fileInfo['url_private'];

        $ch = curl_init($fileUrl);
        $fp = fopen($savePath, 'w');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->botToken,
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200 || !$result) {
            @unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     * Get workspace users
     */
    public function getUsers()
    {
        $result = $this->makeRequest('users.list', 'GET');

        if (!$result || !isset($result['members'])) {
            return [];
        }

        return $result['members'];
    }

    /**
     * Test connection to Slack API
     */
    public function testConnection()
    {
        $result = $this->makeRequest('auth.test', 'GET');

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Slack API',
            ];
        }

        return [
            'success' => true,
            'team' => $result['team'] ?? null,
            'user' => $result['user'] ?? null,
            'team_id' => $result['team_id'] ?? null,
            'user_id' => $result['user_id'] ?? null,
        ];
    }

    /**
     * Parse Slack List data (simplified)
     * You'll need to implement the actual parsing based on Slack's List API response
     */
    public function parseListItems($listData)
    {
        $items = [];

        // This is a placeholder
        // Implement actual parsing based on Slack's List API structure

        return $items;
    }

    /**
     * Get file download URL
     */
    public function getFileDownloadUrl($fileId)
    {
        $fileInfo = $this->getFileInfo($fileId);

        if (!$fileInfo) {
            return null;
        }

        return $fileInfo['url_private'] ?? null;
    }
}
