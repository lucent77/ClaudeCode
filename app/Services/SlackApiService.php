<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SlackApiService
{
    protected $client;
    protected $botToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.slack.bot_token');
        $this->baseUrl = config('services.slack.api_base_url', 'https://slack.com/api');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->botToken,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Get Canvas data
     */
    public function getCanvas($canvasId)
    {
        try {
            $response = $this->client->get('/canvases.sections.lookup', [
                'query' => ['canvas_id' => $canvasId],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            Log::error('Slack API Error - Get Canvas: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse Canvas table data
     */
    public function parseCanvasTable($canvasData)
    {
        $cases = [];

        if (!isset($canvasData['sections'])) {
            return $cases;
        }

        foreach ($canvasData['sections'] as $section) {
            if ($section['type'] === 'table') {
                $rows = $section['rows'] ?? [];
                $headers = $section['headers'] ?? [];

                foreach ($rows as $row) {
                    $caseData = $this->parseTableRow($row, $headers);
                    if ($caseData) {
                        $cases[] = $caseData;
                    }
                }
            }
        }

        return $cases;
    }

    /**
     * Parse a single table row
     */
    protected function parseTableRow($row, $headers)
    {
        $caseData = [
            'slack_case_id' => $row['id'] ?? null,
            'attachments' => [],
        ];

        foreach ($row['cells'] as $index => $cell) {
            $headerName = $headers[$index]['name'] ?? '';

            switch (strtolower($headerName)) {
                case 'patient name':
                case 'patient':
                    $caseData['patient_name'] = $this->extractTextFromCell($cell);
                    break;

                case 'assignee':
                case 'assigned to':
                    $caseData['assignee_name'] = $this->extractTextFromCell($cell);
                    break;

                case 'surgery date':
                    $caseData['surgery_date'] = $this->extractDateFromCell($cell);
                    break;

                case 'due date':
                    $caseData['due_date'] = $this->extractDateFromCell($cell);
                    break;

                case 'preop scan date':
                    $caseData['preop_scan_date'] = $this->extractDateFromCell($cell);
                    break;

                case 'status':
                    $caseData['status'] = $this->normalizeStatus($this->extractTextFromCell($cell));
                    break;

                case 'priority':
                    $caseData['priority'] = $this->normalizePriority($this->extractTextFromCell($cell));
                    break;

                case 'notes':
                    $caseData['notes'] = $this->extractTextFromCell($cell);
                    break;

                case 'photos':
                    $caseData['attachments'] = array_merge(
                        $caseData['attachments'],
                        $this->extractFilesFromCell($cell, 'photo')
                    );
                    break;

                case 'stls':
                case 'stl files':
                    $caseData['attachments'] = array_merge(
                        $caseData['attachments'],
                        $this->extractFilesFromCell($cell, 'stl')
                    );
                    break;

                case 'post-op cbct':
                case 'cbct':
                    $caseData['attachments'] = array_merge(
                        $caseData['attachments'],
                        $this->extractFilesFromCell($cell, 'cbct')
                    );
                    break;
            }
        }

        return $caseData;
    }

    /**
     * Extract text from cell
     */
    protected function extractTextFromCell($cell)
    {
        if (isset($cell['text'])) {
            return $cell['text'];
        }

        if (isset($cell['elements'])) {
            $texts = [];
            foreach ($cell['elements'] as $element) {
                if (isset($element['text'])) {
                    $texts[] = $element['text'];
                }
            }
            return implode(' ', $texts);
        }

        return null;
    }

    /**
     * Extract date from cell
     */
    protected function extractDateFromCell($cell)
    {
        $text = $this->extractTextFromCell($cell);

        if (!$text) {
            return null;
        }

        try {
            return date('Y-m-d', strtotime($text));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract files from cell
     */
    protected function extractFilesFromCell($cell, $type)
    {
        $files = [];

        if (!isset($cell['files'])) {
            return $files;
        }

        foreach ($cell['files'] as $file) {
            $files[] = [
                'type' => $type,
                'slack_file_id' => $file['id'] ?? null,
                'file_url' => $file['url_private'] ?? null,
                'preview_url' => $file['thumb_360'] ?? $file['preview'] ?? null,
                'filename' => $file['name'] ?? null,
                'mimetype' => $file['mimetype'] ?? null,
                'filesize' => $file['size'] ?? null,
            ];
        }

        return $files;
    }

    /**
     * Get file info
     */
    public function getFileInfo($fileId)
    {
        try {
            $response = $this->client->get('/files.info', [
                'query' => ['file' => $fileId],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['ok']) {
                return $data['file'];
            }

            return null;
        } catch (GuzzleException $e) {
            Log::error('Slack API Error - Get File Info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download file from Slack
     */
    public function downloadFile($fileUrl, $destination)
    {
        try {
            $client = new Client([
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->botToken,
                ],
            ]);

            $response = $client->get($fileUrl);

            $directory = dirname($destination);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($destination, $response->getBody());

            return true;
        } catch (GuzzleException $e) {
            Log::error('Slack File Download Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize status values
     */
    protected function normalizeStatus($status)
    {
        $status = strtolower(trim($status));

        $mapping = [
            'open' => 'open',
            'in progress' => 'in_progress',
            'in-progress' => 'in_progress',
            'completed' => 'completed',
            'done' => 'completed',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
        ];

        return $mapping[$status] ?? 'open';
    }

    /**
     * Normalize priority values
     */
    protected function normalizePriority($priority)
    {
        $priority = strtolower(trim($priority));

        $mapping = [
            'low' => 'low',
            'normal' => 'normal',
            'medium' => 'normal',
            'high' => 'high',
            'critical' => 'critical',
            'urgent' => 'critical',
        ];

        return $mapping[$priority] ?? 'normal';
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            $response = $this->client->post('/auth.test');
            $data = json_decode($response->getBody(), true);

            return $data['ok'] ?? false;
        } catch (GuzzleException $e) {
            Log::error('Slack API Test Connection Error: ' . $e->getMessage());
            return false;
        }
    }
}
