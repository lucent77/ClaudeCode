<?php

namespace App\Http\Controllers;

use PDO;
use App\Services\SlackService;

class SyncController
{
    private $db;
    private $slackService;

    public function __construct()
    {
        $config = include(__DIR__ . '/../../../config/database.php');
        $dbConfig = $config['connections']['mysql'];

        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $this->db = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->slackService = new SlackService();
    }

    /**
     * Test Slack connection
     */
    public function test()
    {
        header('Content-Type: application/json');

        try {
            $result = $this->slackService->testConnection();

            echo json_encode($result);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync cases from Slack
     */
    public function sync()
    {
        header('Content-Type: application/json');

        try {
            // Create sync log
            $stmt = $this->db->prepare("INSERT INTO sync_logs
                (sync_type, status, started_at) VALUES (?, ?, NOW())");
            $stmt->execute(['manual', 'started']);
            $syncLogId = $this->db->lastInsertId();

            // Fetch cases from Slack
            $slackResult = $this->slackService->syncCasesFromList();

            if (!$slackResult['success']) {
                $this->updateSyncLog($syncLogId, 'failed', [
                    'error' => $slackResult['error'] ?? 'Unknown error'
                ]);

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $slackResult['error'] ?? 'Sync failed',
                ]);
                return;
            }

            $cases = $slackResult['cases'];
            $casesSynced = 0;
            $attachmentsSynced = 0;
            $errors = [];

            foreach ($cases as $caseData) {
                try {
                    // Check if case exists
                    $stmt = $this->db->prepare("SELECT id FROM cases WHERE slack_case_id = ?");
                    $stmt->execute([$caseData['slack_case_id']]);
                    $existingCase = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingCase) {
                        // Update existing case
                        $this->updateCase($existingCase['id'], $caseData);
                    } else {
                        // Create new case
                        $this->createCase($caseData);
                    }

                    $casesSynced++;

                    // Sync attachments if present
                    if (!empty($caseData['attachments'])) {
                        $attachmentsSynced += $this->syncAttachments($existingCase['id'] ?? $this->db->lastInsertId(), $caseData['attachments']);
                    }

                } catch (\Exception $e) {
                    $errors[] = "Case {$caseData['patient_name']}: " . $e->getMessage();
                }
            }

            // Update sync log
            $this->updateSyncLog($syncLogId, count($errors) > 0 ? 'partial' : 'success', [
                'cases_synced' => $casesSynced,
                'attachments_synced' => $attachmentsSynced,
                'errors_count' => count($errors),
                'errors' => $errors,
            ]);

            echo json_encode([
                'success' => true,
                'cases_synced' => $casesSynced,
                'attachments_synced' => $attachmentsSynced,
                'errors_count' => count($errors),
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            if (isset($syncLogId)) {
                $this->updateSyncLog($syncLogId, 'failed', [
                    'error' => $e->getMessage()
                ]);
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get sync history
     */
    public function history()
    {
        header('Content-Type: application/json');

        try {
            $limit = $_GET['limit'] ?? 50;

            $stmt = $this->db->prepare("SELECT s.*, u.name as triggered_by_name
                                       FROM sync_logs s
                                       LEFT JOIN users u ON s.triggered_by = u.id
                                       ORDER BY s.started_at DESC
                                       LIMIT ?");
            $stmt->execute([$limit]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'logs' => $logs,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch sync history: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create new case from Slack data
     */
    private function createCase($caseData)
    {
        $stmt = $this->db->prepare("INSERT INTO cases
            (slack_case_id, patient_name, assignee_name, created_time, surgery_date,
             status, notes, slack_canvas_url, metadata, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

        $stmt->execute([
            $caseData['slack_case_id'] ?? null,
            $caseData['patient_name'],
            $caseData['assignee_name'] ?? null,
            $caseData['created_time'] ?? null,
            $caseData['surgery_date'] ?? null,
            $caseData['status'] ?? 'open',
            $caseData['notes'] ?? null,
            $caseData['slack_canvas_url'] ?? null,
            isset($caseData['metadata']) ? json_encode($caseData['metadata']) : null,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update existing case from Slack data
     */
    private function updateCase($caseId, $caseData)
    {
        $stmt = $this->db->prepare("UPDATE cases SET
            patient_name = ?,
            assignee_name = ?,
            surgery_date = ?,
            status = ?,
            notes = ?,
            slack_canvas_url = ?,
            metadata = ?,
            updated_at = NOW()
            WHERE id = ?");

        $stmt->execute([
            $caseData['patient_name'],
            $caseData['assignee_name'] ?? null,
            $caseData['surgery_date'] ?? null,
            $caseData['status'] ?? 'open',
            $caseData['notes'] ?? null,
            $caseData['slack_canvas_url'] ?? null,
            isset($caseData['metadata']) ? json_encode($caseData['metadata']) : null,
            $caseId,
        ]);
    }

    /**
     * Sync attachments for a case
     */
    private function syncAttachments($caseId, $attachments)
    {
        $synced = 0;

        foreach ($attachments as $attachment) {
            try {
                // Check if attachment exists
                $stmt = $this->db->prepare("SELECT id FROM case_attachments WHERE slack_file_id = ?");
                $stmt->execute([$attachment['slack_file_id']]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // Update existing attachment
                    $stmt = $this->db->prepare("UPDATE case_attachments SET
                        file_url = ?, filename = ?, mimetype = ?, filesize = ?, updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([
                        $attachment['file_url'],
                        $attachment['filename'],
                        $attachment['mimetype'] ?? null,
                        $attachment['filesize'] ?? null,
                        $existing['id'],
                    ]);
                } else {
                    // Create new attachment
                    $stmt = $this->db->prepare("INSERT INTO case_attachments
                        (case_id, type, slack_file_id, file_url, filename, mimetype, filesize, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $caseId,
                        $attachment['type'] ?? 'other',
                        $attachment['slack_file_id'],
                        $attachment['file_url'],
                        $attachment['filename'],
                        $attachment['mimetype'] ?? null,
                        $attachment['filesize'] ?? null,
                    ]);
                }

                $synced++;

            } catch (\Exception $e) {
                error_log("Failed to sync attachment: " . $e->getMessage());
            }
        }

        return $synced;
    }

    /**
     * Update sync log
     */
    private function updateSyncLog($syncLogId, $status, $details = [])
    {
        $stmt = $this->db->prepare("UPDATE sync_logs SET
            status = ?,
            cases_synced = ?,
            attachments_synced = ?,
            errors_count = ?,
            error_message = ?,
            sync_details = ?,
            completed_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
            WHERE id = ?");

        $stmt->execute([
            $status,
            $details['cases_synced'] ?? 0,
            $details['attachments_synced'] ?? 0,
            $details['errors_count'] ?? 0,
            $details['error'] ?? null,
            !empty($details) ? json_encode($details) : null,
            $syncLogId,
        ]);
    }
}
