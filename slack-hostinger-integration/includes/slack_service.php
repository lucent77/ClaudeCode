<?php
/**
 * Slack API Integration Service
 */

require_once __DIR__ . '/../config/config.php';

class SlackService {

    private $botToken;
    private $apiBaseUrl;

    public function __construct() {
        $this->botToken = SLACK_BOT_TOKEN;
        $this->apiBaseUrl = SLACK_API_BASE_URL;
    }

    /**
     * Make API request to Slack
     */
    private function makeRequest($endpoint, $params = [], $method = 'GET') {
        $url = $this->apiBaseUrl . '/' . $endpoint;

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
            error_log("Slack API request error: " . $error);
            return ['ok' => false, 'error' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !$result['ok']) {
            error_log("Slack API error: " . json_encode($result));
        }

        return $result;
    }

    /**
     * Get file information from Slack
     */
    public function getFileInfo($fileId) {
        return $this->makeRequest('files.info', ['file' => $fileId]);
    }

    /**
     * Get multiple files information
     */
    public function getFilesInfo($fileIds) {
        $files = [];
        foreach ($fileIds as $fileId) {
            $result = $this->getFileInfo($fileId);
            if ($result['ok'] && isset($result['file'])) {
                $files[] = $result['file'];
            }
        }
        return $files;
    }

    /**
     * Download file from Slack
     */
    public function downloadFile($fileUrl, $savePath) {
        $ch = curl_init($fileUrl);
        $fp = fopen($savePath, 'wb');

        $headers = [
            'Authorization: Bearer ' . $this->botToken
        ];

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if (!$result || $httpCode !== 200) {
            unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     * Get user information
     */
    public function getUserInfo($userId) {
        return $this->makeRequest('users.info', ['user' => $userId]);
    }

    /**
     * Parse file IDs from comma-separated string
     */
    public function parseFileIds($fileString) {
        if (empty($fileString)) {
            return [];
        }

        $fileIds = array_map('trim', explode(',', $fileString));
        return array_filter($fileIds);
    }

    /**
     * Sync tasks from CSV data to database
     */
    public function syncTasksFromCSV($csvData) {
        try {
            $db = getDB();
            $syncedCount = 0;
            $errors = [];

            // Parse CSV
            $rows = str_getcsv($csvData, "\n");
            $headers = str_getcsv(array_shift($rows));

            foreach ($rows as $index => $row) {
                $data = str_getcsv($row);

                if (count($data) < count($headers)) {
                    continue;
                }

                $task = array_combine($headers, $data);

                try {
                    // Check if task already exists (by patient name and surgery date)
                    $checkStmt = $db->prepare("
                        SELECT id FROM tasks
                        WHERE patient_name = ? AND surgery_date = ?
                        LIMIT 1
                    ");

                    $surgeryDate = !empty($task['Surgery Date']) ?
                        date('Y-m-d', strtotime($task['Surgery Date'])) : null;

                    $checkStmt->execute([
                        $task['Patient Name'] ?? '',
                        $surgeryDate
                    ]);

                    $existingTask = $checkStmt->fetch();

                    $taskData = [
                        'patient_name' => $task['Patient Name'] ?? '',
                        'arch' => $task['Arch'] ?? '',
                        'pre_op_scan_date' => !empty($task['Pre-op Scan Date']) ?
                            date('Y-m-d', strtotime($task['Pre-op Scan Date'])) : null,
                        'pre_op_scans' => $task['Pre-op Scans'] ?? '',
                        'surgery_date' => $surgeryDate,
                        'surgery_time' => $task['Surgery Time'] ?? '',
                        'due_date' => !empty($task['Due Date']) ?
                            date('Y-m-d', strtotime($task['Due Date'])) : null,
                        'post_op_scans' => $task['Post-op Scans'] ?? '',
                        'existing_implants' => ($task['Existing Implants'] ?? '') === 'Yes' ? 'Yes' : 'No',
                        'notes' => $task['Notes'] ?? '',
                        'assignee_slack_id' => $task['Assignee'] ?? '',
                        'completed' => ($task['Completed'] ?? 'false') === 'true' ? 1 : 0,
                        'photos' => $task['Photos'] ?? '',
                        'stls' => $task['STLs'] ?? '',
                        'pre_op_cbct' => $task['Pre-op CBCT'] ?? '',
                        'post_op_cbct' => $task['Post-op CBCT'] ?? '',
                        'ready_for_surgery' => ($task['Ready for Surgery'] ?? 'false') === 'true' ? 1 : 0,
                        'slack_created_by' => $task['Created by'] ?? '',
                        'slack_last_edited_by' => $task['Last edited by'] ?? ''
                    ];

                    if ($existingTask) {
                        // Update existing task
                        $stmt = $db->prepare("
                            UPDATE tasks SET
                                arch = ?, pre_op_scan_date = ?, pre_op_scans = ?,
                                surgery_time = ?, due_date = ?, post_op_scans = ?,
                                existing_implants = ?, notes = ?, assignee_slack_id = ?,
                                completed = ?, photos = ?, stls = ?, pre_op_cbct = ?,
                                post_op_cbct = ?, ready_for_surgery = ?,
                                slack_last_edited_by = ?, updated_at = NOW()
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $taskData['arch'],
                            $taskData['pre_op_scan_date'],
                            $taskData['pre_op_scans'],
                            $taskData['surgery_time'],
                            $taskData['due_date'],
                            $taskData['post_op_scans'],
                            $taskData['existing_implants'],
                            $taskData['notes'],
                            $taskData['assignee_slack_id'],
                            $taskData['completed'],
                            $taskData['photos'],
                            $taskData['stls'],
                            $taskData['pre_op_cbct'],
                            $taskData['post_op_cbct'],
                            $taskData['ready_for_surgery'],
                            $taskData['slack_last_edited_by'],
                            $existingTask['id']
                        ]);
                    } else {
                        // Insert new task
                        $stmt = $db->prepare("
                            INSERT INTO tasks (
                                patient_name, arch, pre_op_scan_date, pre_op_scans,
                                surgery_date, surgery_time, due_date, post_op_scans,
                                existing_implants, notes, assignee_slack_id, completed,
                                photos, stls, pre_op_cbct, post_op_cbct, ready_for_surgery,
                                slack_created_by, slack_last_edited_by
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");

                        $stmt->execute([
                            $taskData['patient_name'],
                            $taskData['arch'],
                            $taskData['pre_op_scan_date'],
                            $taskData['pre_op_scans'],
                            $taskData['surgery_date'],
                            $taskData['surgery_time'],
                            $taskData['due_date'],
                            $taskData['post_op_scans'],
                            $taskData['existing_implants'],
                            $taskData['notes'],
                            $taskData['assignee_slack_id'],
                            $taskData['completed'],
                            $taskData['photos'],
                            $taskData['stls'],
                            $taskData['pre_op_cbct'],
                            $taskData['post_op_cbct'],
                            $taskData['ready_for_surgery'],
                            $taskData['slack_created_by'],
                            $taskData['slack_last_edited_by']
                        ]);
                    }

                    $syncedCount++;

                } catch (PDOException $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                    error_log("Task sync error: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            error_log("CSV sync error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
