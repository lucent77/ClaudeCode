<?php
/**
 * Import Service
 *
 * Handles importing data from Evolution Web Portal to database
 * Includes department-specific data transformation
 */

namespace App\Services;

use App\Core\Database;
use Exception;

class ImportService
{
    private Database $db;
    private EvolutionClient $evoClient;
    private CaseService $caseService;

    // Field mappings for department-specific data
    private array $solidexMap = [
        'CASE #' => 'external_case_no',
        'ID' => 'id',
        'Timestamp' => 'timestamp',
        'DUE' => 'due_date',
        'SEND' => 'send_date',
        'LAB' => 'lab_name',
        'COUNT' => 'count',
        'TEETH' => 'tooth_no',
        'IMPLANT SYSTEM' => 'implant_system',
        'INSTRUCTION' => 'instruction',
        'PREFERENCES' => 'preferences',
    ];

    private array $print3dMap = [
        'CASE #' => 'external_case_no',
        'TIME STAMP' => 'timestamp',
        'DATE' => 'due_date',
        'TYPE' => 'work_type',
        'LAB #' => 'lab_name',
        'PATIENT #' => 'patient_name',
        'TOOTH #' => 'tooth_no',
        'COUNT' => 'count',
        'HV/NYC' => 'location',
        'INSTRUCTIONS' => 'instruction',
        'uploaded Korea' => 'uploaded_korea',
    ];

    private array $cocrMap = [
        'CASE #' => 'external_case_no',
        'ID' => 'id',
        'DATE' => 'due_date',
        'TYPE' => 'work_type',
        'DISK Material' => 'material',
        'COMBO' => 'combo',
        'MC IO' => 'mc_io',
        'L/D' => 'l_d',
        'LAB #' => 'lab_name',
        'PATIENT #' => 'patient_name',
        'IMPLANT TYPE' => 'implant_type',
        'INSTRUCTIONS' => 'instruction',
        'PREFERENCES' => 'preferences',
        'DESIGN' => 'design',
        'CAM' => 'cam',
        'CNC' => 'cnc',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->evoClient = new EvolutionClient();
        $this->caseService = new CaseService();
    }

    /**
     * Import cases from Evolution Web Portal
     * Default: import yesterday and today
     */
    public function importFromEvolution(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-1 day'));
        $endDate = $endDate ?? date('Y-m-d');

        $jobId = $this->startImportJob('evo_case_list');

        try {
            // Get cases list
            $response = $this->evoClient->getCasesList($startDate, $endDate);

            if (empty($response['cases']) && empty($response['case'])) {
                return $this->completeImportJob($jobId, 'success', 0, 0, 0,
                    "No cases found for date range {$startDate} to {$endDate}");
            }

            // Normalize response (handle both singular and plural)
            $cases = $response['cases'] ?? $response['case'] ?? [];
            if (!isset($cases[0])) {
                $cases = [$cases]; // Single case
            }

            $processed = 0;
            $success = 0;
            $failed = 0;

            foreach ($cases as $caseData) {
                $processed++;

                try {
                    $this->importSingleCase($caseData);
                    $success++;
                } catch (Exception $e) {
                    $failed++;
                    error_log("Failed to import case: " . $e->getMessage());
                }
            }

            return $this->completeImportJob(
                $jobId,
                $failed > 0 ? 'partial' : 'success',
                $processed,
                $success,
                $failed,
                "Imported {$success} of {$processed} cases"
            );

        } catch (Exception $e) {
            return $this->completeImportJob($jobId, 'error', 0, 0, 0, $e->getMessage());
        }
    }

    /**
     * Import single case with department-specific processing
     */
    public function importSingleCase(array $caseData): int
    {
        $this->db->beginTransaction();

        try {
            // Prepare basic case data
            $externalCaseNo = $caseData['caseno'] ?? $caseData['case_number'] ?? null;

            if (!$externalCaseNo) {
                throw new Exception("Case number not found in data");
            }

            // Check if case exists
            $existingCase = $this->db->queryOne(
                "SELECT id, version FROM cases WHERE external_case_no = ?",
                [$externalCaseNo]
            );

            $caseRecord = [
                'external_case_no' => $externalCaseNo,
                'source' => 'evolution_web_portal',
                'patient_name' => $caseData['patient'] ?? $caseData['patient_name'] ?? null,
                'lab_name' => $caseData['lab'] ?? $caseData['lab_name'] ?? null,
                'lab_number' => $caseData['lab_number'] ?? null,
                'patient_number' => $caseData['patient_number'] ?? null,
                'due_date' => $this->parseDate($caseData['duedate'] ?? $caseData['due_date'] ?? null),
                'location' => $caseData['location'] ?? null,
                'status' => 'new',
                'raw_payload' => json_encode($caseData),
            ];

            if ($existingCase) {
                // Update existing case
                $caseRecord['version'] = $existingCase['version'];
                $this->db->update('cases', $caseRecord, ['id' => $existingCase['id']], true);
                $caseId = $existingCase['id'];
            } else {
                // Insert new case
                $caseRecord['version'] = 1;
                $caseId = $this->db->insert('cases', $caseRecord);

                // Log import
                $this->db->insert('case_audit_logs', [
                    'case_id' => $caseId,
                    'action' => 'import_from_evo',
                    'description' => 'Case imported from Evolution Web Portal',
                    'after_json' => json_encode($caseRecord),
                ]);
            }

            // Process department-specific data if present
            $this->processDepartmentData($caseId, $caseData);

            $this->db->commit();
            return $caseId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Process department-specific data and create case items
     */
    private function processDepartmentData(int $caseId, array $caseData): void
    {
        // Determine work type/department based on case data
        $workType = $this->determineWorkType($caseData);

        if (!$workType) {
            return; // No specific department data
        }

        // Get department ID
        $department = $this->db->queryOne(
            "SELECT id FROM departments WHERE code = ?",
            [$workType]
        );

        if (!$department) {
            return;
        }

        // Create case item
        $itemData = [
            'case_id' => $caseId,
            'department_id' => $department['id'],
            'work_type' => $workType,
            'tooth_no' => $caseData['tooth'] ?? $caseData['tooth_no'] ?? null,
            'count' => $caseData['count'] ?? 1,
            'material' => $caseData['material'] ?? null,
            'instruction' => $caseData['instructions'] ?? $caseData['instruction'] ?? null,
            'preferences' => $caseData['preferences'] ?? null,
            'status' => 'pending',
            'version' => 1,
        ];

        $this->db->insert('case_items', $itemData);

        // Store in department-specific table
        $this->storeDepartmentSpecificData($caseId, $workType, $caseData);
    }

    /**
     * Store data in department-specific tables (preserves Google Sheets format)
     */
    private function storeDepartmentSpecificData(int $caseId, string $workType, array $data): void
    {
        $table = match($workType) {
            'SOLIDEX' => 'solidex_orders',
            '3DPRINT' => 'print3d_orders',
            'COCR' => 'cocr_orders',
            default => null,
        };

        if (!$table) {
            return;
        }

        // Check if already exists
        $existing = $this->db->queryOne(
            "SELECT id FROM {$table} WHERE case_id = ?",
            [$caseId]
        );

        $payload = [
            'case_id' => $caseId,
            'payload_json' => json_encode($data),
            'version' => 1,
        ];

        if ($existing) {
            $this->db->update($table, $payload, ['id' => $existing['id']], false);
        } else {
            $this->db->insert($table, $payload);
        }
    }

    /**
     * Determine work type from case data
     */
    private function determineWorkType(array $data): ?string
    {
        $type = strtoupper($data['type'] ?? $data['work_type'] ?? '');

        if (strpos($type, 'SOLIDEX') !== false) {
            return 'SOLIDEX';
        } elseif (strpos($type, '3D') !== false || strpos($type, 'PRINT') !== false) {
            return '3DPRINT';
        } elseif (strpos($type, 'COCR') !== false || strpos($type, 'ZEST') !== false) {
            return 'COCR';
        }

        return null;
    }

    /**
     * Parse date in various formats
     */
    private function parseDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Start import job and get job ID
     */
    private function startImportJob(string $jobType): int
    {
        return $this->db->insert('import_jobs', [
            'job_type' => $jobType,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Complete import job with results
     */
    private function completeImportJob(int $jobId, string $status, int $processed, int $success,
                                      int $failed, string $message): array
    {
        $endTime = date('Y-m-d H:i:s');
        $startTime = $this->db->queryOne(
            "SELECT started_at FROM import_jobs WHERE id = ?",
            [$jobId]
        )['started_at'];

        $duration = strtotime($endTime) - strtotime($startTime);

        $this->db->update('import_jobs', [
            'status' => $status,
            'ended_at' => $endTime,
            'duration_seconds' => $duration,
            'records_processed' => $processed,
            'records_success' => $success,
            'records_failed' => $failed,
            'message' => $message,
        ], ['id' => $jobId], false);

        return [
            'job_id' => $jobId,
            'status' => $status,
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'message' => $message,
            'duration' => $duration,
        ];
    }

    /**
     * Get import job history
     */
    public function getImportHistory(int $limit = 20): array
    {
        return $this->db->query(
            "SELECT *, CONCAT(records_success, '/', records_processed) as result
             FROM import_jobs
             ORDER BY started_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
