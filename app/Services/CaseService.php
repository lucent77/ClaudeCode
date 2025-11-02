<?php
/**
 * Case Service
 *
 * Business logic for case management
 * Handles CRUD operations with concurrency control
 */

namespace App\Services;

use App\Core\Database;
use App\Repositories\CaseRepository;
use Exception;

class CaseService
{
    private Database $db;
    private CaseRepository $caseRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->caseRepo = new CaseRepository();
    }

    /**
     * Create new case with optimistic locking
     */
    public function createCase(array $data, ?int $userId = null): array
    {
        try {
            $this->db->beginTransaction();

            // Check if case already exists
            $existing = $this->caseRepo->findByExternalCaseNo($data['external_case_no']);
            if ($existing) {
                throw new Exception("Case {$data['external_case_no']} already exists");
            }

            // Prepare case data
            $caseData = [
                'external_case_no' => $data['external_case_no'],
                'source' => $data['source'] ?? 'manual',
                'patient_name' => $data['patient_name'] ?? null,
                'lab_name' => $data['lab_name'] ?? null,
                'lab_number' => $data['lab_number'] ?? null,
                'patient_number' => $data['patient_number'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'location' => $data['location'] ?? null,
                'status' => $data['status'] ?? 'new',
                'priority' => $data['priority'] ?? 'normal',
                'raw_payload' => $data['raw_payload'] ?? null,
                'notes' => $data['notes'] ?? null,
                'version' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            $caseId = $this->db->insert('cases', $caseData);

            // Log creation
            $this->logAudit($caseId, $userId, 'create', 'Case created', null, $caseData);

            $this->db->commit();

            return $this->caseRepo->findById($caseId);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update case with optimistic locking
     */
    public function updateCase(int $caseId, array $data, int $version, ?int $userId = null): array
    {
        try {
            $this->db->beginTransaction();

            // Get current case data
            $currentCase = $this->caseRepo->findById($caseId);
            if (!$currentCase) {
                throw new Exception("Case not found");
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = ['patient_name', 'lab_name', 'lab_number', 'patient_number',
                              'due_date', 'location', 'status', 'priority', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $updateData['updated_by'] = $userId;
            $updateData['version'] = $version;

            // Update with version check
            $affected = $this->db->update(
                'cases',
                $updateData,
                ['id' => $caseId],
                true // Use optimistic locking
            );

            if ($affected === 0) {
                throw new Exception('Concurrent modification detected. Please refresh and try again.');
            }

            // Log update
            $this->logAudit($caseId, $userId, 'update', 'Case updated', $currentCase, $updateData);

            $this->db->commit();

            return $this->caseRepo->findById($caseId);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Assign case item to user
     */
    public function assignCaseItem(int $itemId, int $assignedToUserId, ?int $assignedByUserId = null): array
    {
        try {
            $this->db->beginTransaction();

            // Get current item
            $item = $this->db->queryOne("SELECT * FROM case_items WHERE id = ?", [$itemId]);
            if (!$item) {
                throw new Exception("Case item not found");
            }

            // Update assignment
            $updateData = [
                'assigned_to' => $assignedToUserId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'status' => 'assigned',
                'updated_by' => $assignedByUserId,
                'version' => $item['version'],
            ];

            $affected = $this->db->update('case_items', $updateData, ['id' => $itemId], true);

            if ($affected === 0) {
                throw new Exception('Concurrent modification detected. Please refresh and try again.');
            }

            // Log assignment
            $this->logAudit(
                $item['case_id'],
                $assignedByUserId,
                'assign',
                "Assigned to user ID: {$assignedToUserId}",
                $item,
                $updateData,
                $itemId
            );

            $this->db->commit();

            return $this->db->queryOne("SELECT * FROM case_items WHERE id = ?", [$itemId]);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update case item status
     */
    public function updateItemStatus(int $itemId, string $status, int $version, ?int $userId = null): array
    {
        try {
            $this->db->beginTransaction();

            $item = $this->db->queryOne("SELECT * FROM case_items WHERE id = ?", [$itemId]);
            if (!$item) {
                throw new Exception("Case item not found");
            }

            $updateData = [
                'status' => $status,
                'updated_by' => $userId,
                'version' => $version,
            ];

            // Set completion timestamp if status is done
            if ($status === 'done' && $item['status'] !== 'done') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }

            // Set started timestamp if status is working
            if ($status === 'working' && $item['status'] !== 'working' && !$item['started_at']) {
                $updateData['started_at'] = date('Y-m-d H:i:s');
            }

            $affected = $this->db->update('case_items', $updateData, ['id' => $itemId], true);

            if ($affected === 0) {
                throw new Exception('Concurrent modification detected. Please refresh and try again.');
            }

            // Log status change
            $this->logAudit(
                $item['case_id'],
                $userId,
                'status_change',
                "Status changed from {$item['status']} to {$status}",
                $item,
                $updateData,
                $itemId
            );

            $this->db->commit();

            return $this->db->queryOne("SELECT * FROM case_items WHERE id = ?", [$itemId]);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get cases with filters and pagination
     */
    public function getCases(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        return $this->caseRepo->getWithFilters($filters, $page, $perPage);
    }

    /**
     * Get case details with items
     */
    public function getCaseDetails(int $caseId): array
    {
        $case = $this->caseRepo->findById($caseId);
        if (!$case) {
            throw new Exception("Case not found");
        }

        // Get case items
        $items = $this->db->query(
            "SELECT ci.*, u.name as assigned_to_name, d.name as department_name
             FROM case_items ci
             LEFT JOIN users u ON ci.assigned_to = u.id
             LEFT JOIN departments d ON ci.department_id = d.id
             WHERE ci.case_id = ?
             ORDER BY ci.created_at DESC",
            [$caseId]
        );

        // Get audit logs
        $auditLogs = $this->db->query(
            "SELECT cal.*, u.name as user_name
             FROM case_audit_logs cal
             LEFT JOIN users u ON cal.user_id = u.id
             WHERE cal.case_id = ?
             ORDER BY cal.created_at DESC
             LIMIT 50",
            [$caseId]
        );

        $case['items'] = $items;
        $case['audit_logs'] = $auditLogs;

        return $case;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(?int $departmentId = null): array
    {
        $departmentFilter = $departmentId ? "AND ci.department_id = {$departmentId}" : "";

        $stats = [
            'total_cases' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM cases WHERE status NOT IN ('archived', 'canceled')"
            )['count'] ?? 0,

            'new_cases_today' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM cases
                 WHERE DATE(created_at) = CURDATE() AND status = 'new'"
            )['count'] ?? 0,

            'in_progress' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM cases WHERE status = 'in_progress'"
            )['count'] ?? 0,

            'overdue' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM cases
                 WHERE due_date < CURDATE() AND status NOT IN ('done', 'archived', 'canceled')"
            )['count'] ?? 0,

            'department_workload' => $this->db->query(
                "SELECT * FROM v_department_workload"
            ),

            'recent_cases' => $this->db->query(
                "SELECT c.*, u.name as created_by_name
                 FROM cases c
                 LEFT JOIN users u ON c.created_by = u.id
                 ORDER BY c.created_at DESC
                 LIMIT 10"
            ),
        ];

        return $stats;
    }

    /**
     * Log audit trail
     */
    private function logAudit(int $caseId, ?int $userId, string $action, string $description,
                             ?array $before = null, ?array $after = null, ?int $itemId = null): void
    {
        $this->db->insert('case_audit_logs', [
            'case_id' => $caseId,
            'case_item_id' => $itemId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
