<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseAttachment;
use App\Models\CaseActivityLog;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlackSyncService
{
    protected $slackApi;
    protected $fileStorage;

    public function __construct(SlackApiService $slackApi, FileStorageService $fileStorage)
    {
        $this->slackApi = $slackApi;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Sync all cases from Slack Canvas
     */
    public function syncFromCanvas($syncType = 'manual', $userId = null)
    {
        $syncLog = SyncLog::startSync($syncType, $userId);

        try {
            $canvasId = config('services.slack.canvas_id');

            if (!$canvasId) {
                throw new \Exception('Slack Canvas ID not configured');
            }

            // Get Canvas data from Slack
            $canvasData = $this->slackApi->getCanvas($canvasId);

            if (!$canvasData || !$canvasData['ok']) {
                throw new \Exception('Failed to fetch Canvas data from Slack');
            }

            // Parse table data
            $casesData = $this->slackApi->parseCanvasTable($canvasData);

            $casesSynced = 0;
            $attachmentsSynced = 0;
            $errors = 0;

            // Sync each case
            foreach ($casesData as $caseData) {
                try {
                    $result = $this->syncCase($caseData);
                    $casesSynced++;
                    $attachmentsSynced += $result['attachments_count'];
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error syncing case: ' . $e->getMessage(), [
                        'case_data' => $caseData,
                    ]);
                }
            }

            // Mark sync as completed
            $syncLog->markAsCompleted($casesSynced, $attachmentsSynced, $errors);

            return [
                'success' => true,
                'cases_synced' => $casesSynced,
                'attachments_synced' => $attachmentsSynced,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            $syncLog->markAsFailed($e->getMessage());
            Log::error('Slack Sync Failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync a single case
     */
    protected function syncCase($caseData)
    {
        return DB::transaction(function () use ($caseData) {
            // Extract attachments
            $attachmentsData = $caseData['attachments'] ?? [];
            unset($caseData['attachments']);

            // Find or create case
            $case = CaseModel::updateOrCreate(
                ['slack_case_id' => $caseData['slack_case_id']],
                array_filter($caseData, function ($value) {
                    return $value !== null;
                })
            );

            // Sync attachments
            $attachmentCount = $this->syncAttachments($case, $attachmentsData);

            // Log activity
            CaseActivityLog::logActivity(
                $case->id,
                'sync',
                'Case synced from Slack Canvas',
                null,
                null
            );

            return [
                'case' => $case,
                'attachments_count' => $attachmentCount,
            ];
        });
    }

    /**
     * Sync attachments for a case
     */
    protected function syncAttachments(CaseModel $case, array $attachmentsData)
    {
        $count = 0;
        $storageStrategy = config('services.slack.file_storage_strategy', 'hybrid');

        foreach ($attachmentsData as $attachmentData) {
            try {
                // Check if attachment already exists
                $existing = CaseAttachment::where('case_id', $case->id)
                    ->where('slack_file_id', $attachmentData['slack_file_id'])
                    ->first();

                if ($existing) {
                    continue; // Skip if already synced
                }

                // Handle file storage based on strategy
                $localPath = null;
                if ($this->shouldDownloadFile($attachmentData['type'], $storageStrategy)) {
                    $localPath = $this->fileStorage->downloadFromSlack(
                        $attachmentData['file_url'],
                        $case->id,
                        $attachmentData['type'],
                        $attachmentData['filename']
                    );
                }

                // Create attachment record
                CaseAttachment::create([
                    'case_id' => $case->id,
                    'type' => $attachmentData['type'],
                    'slack_file_id' => $attachmentData['slack_file_id'],
                    'file_url' => $attachmentData['file_url'],
                    'local_path' => $localPath,
                    'preview_url' => $attachmentData['preview_url'] ?? null,
                    'filename' => $attachmentData['filename'],
                    'mimetype' => $attachmentData['mimetype'],
                    'filesize' => $attachmentData['filesize'],
                ]);

                $count++;
            } catch (\Exception $e) {
                Log::error('Error syncing attachment: ' . $e->getMessage(), [
                    'attachment_data' => $attachmentData,
                ]);
            }
        }

        return $count;
    }

    /**
     * Determine if file should be downloaded based on strategy
     */
    protected function shouldDownloadFile($fileType, $strategy)
    {
        if ($strategy === 'local_download') {
            return true;
        }

        if ($strategy === 'slack_url') {
            return false;
        }

        // Hybrid strategy
        if ($strategy === 'hybrid') {
            // Download STL and CBCT files, use Slack URL for photos
            return in_array($fileType, ['stl', 'cbct', 'scan']);
        }

        return false;
    }

    /**
     * Test Slack connection
     */
    public function testConnection()
    {
        return $this->slackApi->testConnection();
    }
}
