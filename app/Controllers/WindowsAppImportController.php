<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CaseService;

/**
 * Windows App Import Controller
 *
 * Handles REST API endpoints for Windows VB.NET application
 * Imports work items for 3D Print, CoCr/ZEST, and Solidex departments
 */
class WindowsAppImportController extends Controller
{
    private CaseService $caseService;
    private array $config;

    public function __construct()
    {
        parent::__construct();
        $this->caseService = new CaseService();
        $this->config = require BASE_PATH . '/config/config.php';
    }

    /**
     * Authenticate Windows App request
     */
    private function authenticateRequest(): bool
    {
        // Check if Windows App integration is enabled
        if (!($this->config['windows_app']['enabled'] ?? false)) {
            return false;
        }

        // Get API key from header
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        // Validate API key
        if (empty($apiKey) || $apiKey !== ($this->config['windows_app']['api_key'] ?? '')) {
            return false;
        }

        // Check IP whitelist if configured
        $allowedIps = $this->config['windows_app']['allowed_ips'] ?? [];
        if (!empty($allowedIps)) {
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!in_array($clientIp, $allowedIps)) {
                $this->log("Windows App API access denied from IP: {$clientIp}", 'warning');
                return false;
            }
        }

        return true;
    }

    /**
     * Import 3D Print work items
     *
     * Expected JSON format:
     * {
     *   "items": [
     *     {
     *       "case_no": "2025-48801",
     *       "patient_name": "John Doe",
     *       "lab_name": "ABC Dental Lab",
     *       "work_type": "3DPRINT",
     *       "quantity": 1,
     *       "due_date": "2025-11-10",
     *       "notes": "Special instructions..."
     *     }
     *   ]
     * }
     */
    public function import3DPrint(): void
    {
        // Authenticate request
        if (!$this->authenticateRequest()) {
            $this->error('Unauthorized', 401);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['items']) || !is_array($input['items'])) {
                $this->error('Invalid request format. Expected "items" array', 400);
                return;
            }

            $results = $this->processImport($input['items'], '3DPRINT', 'windows_app');

            $this->success([
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
                'total' => count($input['items'])
            ], 'Import completed');

        } catch (\Exception $e) {
            $this->log('Error in 3D Print import: ' . $e->getMessage(), 'error');
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Import CoCr/ZEST work items
     */
    public function importCoCr(): void
    {
        // Authenticate request
        if (!$this->authenticateRequest()) {
            $this->error('Unauthorized', 401);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['items']) || !is_array($input['items'])) {
                $this->error('Invalid request format. Expected "items" array', 400);
                return;
            }

            $results = $this->processImport($input['items'], 'COCR', 'windows_app');

            $this->success([
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
                'total' => count($input['items'])
            ], 'Import completed');

        } catch (\Exception $e) {
            $this->log('Error in CoCr import: ' . $e->getMessage(), 'error');
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Import Solidex work items
     */
    public function importSolidex(): void
    {
        // Authenticate request
        if (!$this->authenticateRequest()) {
            $this->error('Unauthorized', 401);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['items']) || !is_array($input['items'])) {
                $this->error('Invalid request format. Expected "items" array', 400);
                return;
            }

            $results = $this->processImport($input['items'], 'SOLIDEX', 'windows_app');

            $this->success([
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
                'total' => count($input['items'])
            ], 'Import completed');

        } catch (\Exception $e) {
            $this->log('Error in Solidex import: ' . $e->getMessage(), 'error');
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Process import for work items
     */
    private function processImport(array $items, string $workType, string $source): array
    {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'errors' => []
        ];

        // Use system user for Windows App imports (user_id = 1)
        $systemUserId = 1;

        foreach ($items as $item) {
            try {
                // Validate required fields
                if (empty($item['case_no'])) {
                    $results['errors'][] = [
                        'case_no' => $item['case_no'] ?? 'unknown',
                        'error' => 'Case number is required'
                    ];
                    continue;
                }

                $caseNo = $item['case_no'];

                // Check if case already exists
                $existingCase = $this->db->queryOne(
                    'SELECT id, version FROM cases WHERE external_case_no = :case_no',
                    [':case_no' => $caseNo]
                );

                if ($existingCase) {
                    // Update existing case
                    $updateData = [
                        'patient_name' => $item['patient_name'] ?? null,
                        'lab_name' => $item['lab_name'] ?? null,
                        'due_date' => $item['due_date'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'last_imported_at' => date('Y-m-d H:i:s')
                    ];

                    // Remove null values
                    $updateData = array_filter($updateData, function($value) {
                        return $value !== null;
                    });

                    if (!empty($updateData)) {
                        $this->caseService->updateCase(
                            $existingCase['id'],
                            $updateData,
                            (int) $existingCase['version'],
                            $systemUserId
                        );
                    }

                    // Add work item if not exists
                    $this->addWorkItemToCase($existingCase['id'], $item, $workType, $systemUserId);

                    $results['updated']++;
                } else {
                    // Create new case
                    $caseData = [
                        'external_case_no' => $caseNo,
                        'patient_name' => $item['patient_name'] ?? null,
                        'lab_name' => $item['lab_name'] ?? null,
                        'due_date' => $item['due_date'] ?? null,
                        'status' => 'new',
                        'priority' => 'normal',
                        'source' => $source,
                        'notes' => $item['notes'] ?? null,
                        'last_imported_at' => date('Y-m-d H:i:s')
                    ];

                    $case = $this->caseService->createCase($caseData, $systemUserId);

                    // Add work item
                    $this->addWorkItemToCase($case['id'], $item, $workType, $systemUserId);

                    $results['imported']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'case_no' => $item['case_no'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                $this->log("Error importing case {$caseNo}: " . $e->getMessage(), 'error');
            }
        }

        return $results;
    }

    /**
     * Add work item to case
     */
    private function addWorkItemToCase(int $caseId, array $itemData, string $workType, int $userId): void
    {
        // Check if work item already exists for this case and work type
        $existing = $this->db->queryOne(
            'SELECT id FROM case_items WHERE case_id = :case_id AND work_type = :work_type',
            [
                ':case_id' => $caseId,
                ':work_type' => $workType
            ]
        );

        if ($existing) {
            // Update existing item
            $this->db->update('case_items', $existing['id'], [
                'quantity' => $itemData['quantity'] ?? 1,
                'notes' => $itemData['notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Create new item
            $itemCreateData = [
                'work_type' => $workType,
                'quantity' => $itemData['quantity'] ?? 1,
                'notes' => $itemData['notes'] ?? null
            ];

            $this->caseService->addCaseItem($caseId, $itemCreateData, $userId);
        }
    }

    /**
     * Get case status (for Windows App to check)
     */
    public function getCaseStatus(): void
    {
        // Authenticate request
        if (!$this->authenticateRequest()) {
            $this->error('Unauthorized', 401);
            return;
        }

        try {
            $caseNo = $_GET['case_no'] ?? '';

            if (empty($caseNo)) {
                $this->error('Case number is required', 400);
                return;
            }

            $case = $this->db->queryOne(
                'SELECT
                    c.id,
                    c.external_case_no,
                    c.patient_name,
                    c.lab_name,
                    c.status,
                    c.priority,
                    c.due_date,
                    c.created_at,
                    c.last_imported_at
                FROM cases c
                WHERE c.external_case_no = :case_no',
                [':case_no' => $caseNo]
            );

            if (!$case) {
                $this->error('Case not found', 404);
                return;
            }

            // Get case items
            $items = $this->db->query(
                'SELECT
                    ci.id,
                    ci.work_type,
                    ci.quantity,
                    ci.status,
                    ci.assigned_to_user_id,
                    u.name as assigned_to_name
                FROM case_items ci
                LEFT JOIN users u ON ci.assigned_to_user_id = u.id
                WHERE ci.case_id = :case_id',
                [':case_id' => $case['id']]
            );

            $case['items'] = $items;

            $this->success($case);

        } catch (\Exception $e) {
            $this->log('Error getting case status: ' . $e->getMessage(), 'error');
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function healthCheck(): void
    {
        // Authenticate request
        if (!$this->authenticateRequest()) {
            $this->error('Unauthorized', 401);
            return;
        }

        $this->success([
            'status' => 'online',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->config['app']['version'] ?? '2.0.0'
        ], 'Service is healthy');
    }
}
