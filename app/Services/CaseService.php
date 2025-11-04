<?php

namespace App\Services;

use App\Repositories\CaseRepository;
use App\Core\Database;

/**
 * Case Service
 *
 * Business logic for case management
 * Handles optimistic locking, audit logging, and validation
 */
class CaseService
{
    private CaseRepository $caseRepo;
    private Database $db;

    public function __construct()
    {
        $this->caseRepo = new CaseRepository();
        $this->db = Database::getInstance();
    }

    /**
     * Create new case with audit logging
     */
    public function createCase(array $data, int $userId): array
    {
        // Validate required fields
        $this->validateCaseData($data);

        // Check for duplicates
        if (isset($data['external_case_no']) && isset($data['source'])) {
            if ($this->caseRepo->caseNumberExists($data['external_case_no'], $data['source'])) {
                throw new \Exception('Case number already exists for this source');
            }
        }

        // Set creator
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        // Create case
        $caseId = $this->caseRepo->create($data);

        // Get created case
        $case = $this->caseRepo->findById($caseId);

        // Log audit
        $this->logAudit($caseId, $userId, 'created', null, null, null, $case);

        return $case;
    }

    /**
     * Update case with optimistic locking and audit logging
     */
    public function updateCase(int $id, array $data, int $expectedVersion, int $userId): array
    {
        // Get current case for audit
        $before = $this->caseRepo->findById($id);
        if (!$before) {
            throw new \Exception('Case not found');
        }

        // Validate data
        $this->validateCaseData($data, true);

        // Set updater
        $data['updated_by'] = $userId;

        // Update with optimistic locking
        $this->caseRepo->update($id, $data, $expectedVersion);

        // Get updated case
        $after = $this->caseRepo->findById($id);

        // Log changes
        $this->logChanges($id, $userId, $before, $after);

        return $after;
    }

    /**
     * Delete case
     */
    public function deleteCase(int $id, int $userId): bool
    {
        $case = $this->caseRepo->findById($id);
        if (!$case) {
            throw new \Exception('Case not found');
        }

        // Log audit before deletion
        $this->logAudit($id, $userId, 'deleted', null, null, $case, null);

        return $this->caseRepo->delete($id);
    }

    /**
     * Archive case
     */
    public function archiveCase(int $id, int $expectedVersion, int $userId): array
    {
        $before = $this->caseRepo->findById($id);
        if (!$before) {
            throw new \Exception('Case not found');
        }

        $this->caseRepo->archive($id, $expectedVersion);

        $after = $this->caseRepo->findById($id);

        $this->logAudit($id, $userId, 'archived', 'status', $before['status'], $before, $after);

        return $after;
    }

    /**
     * Get case with items
     */
    public function getCaseWithItems(int $id): ?array
    {
        return $this->caseRepo->findWithItems($id);
    }

    /**
     * Assign case item to user
     */
    public function assignCaseItem(int $caseId, int $itemId, int $userId, int $assignedBy): array
    {
        // Get case item
        $sql = 'SELECT * FROM case_items WHERE id = :id AND case_id = :case_id';
        $item = $this->db->queryOne($sql, [':id' => $itemId, ':case_id' => $caseId]);

        if (!$item) {
            throw new \Exception('Case item not found');
        }

        $before = $item;

        // Update assignment
        $sql = 'UPDATE case_items SET assigned_to_user_id = :user_id, assigned_at = NOW(), status = :status, updated_at = NOW() WHERE id = :id';
        $this->db->query($sql, [
            ':user_id' => $userId,
            ':status' => 'assigned',
            ':id' => $itemId
        ]);

        // Get updated item
        $after = $this->db->queryOne('SELECT * FROM case_items WHERE id = :id', [':id' => $itemId]);

        // Log audit
        $this->logAudit($caseId, $assignedBy, 'item_assigned', 'assigned_to_user_id', $before['assigned_to_user_id'], $before, $after);

        return $after;
    }

    /**
     * Update case item status
     */
    public function updateItemStatus(int $caseId, int $itemId, string $status, int $expectedVersion, int $userId): array
    {
        // Get item
        $sql = 'SELECT * FROM case_items WHERE id = :id AND case_id = :case_id';
        $item = $this->db->queryOne($sql, [':id' => $itemId, ':case_id' => $caseId]);

        if (!$item) {
            throw new \Exception('Case item not found');
        }

        // Check version
        if ((int) $item['version'] !== $expectedVersion) {
            throw new \Exception('Concurrent modification detected. Please refresh and try again.');
        }

        $before = $item;

        // Update status
        $sql = 'UPDATE case_items SET status = :status, version = version + 1, updated_at = NOW() WHERE id = :id AND version = :version';
        $this->db->query($sql, [
            ':status' => $status,
            ':id' => $itemId,
            ':version' => $expectedVersion
        ]);

        // Get updated item
        $after = $this->db->queryOne('SELECT * FROM case_items WHERE id = :id', [':id' => $itemId]);

        // Log audit
        $this->logAudit($caseId, $userId, 'item_status_changed', 'status', $before['status'], $before, $after);

        // Check if all items are done, update case status
        $this->checkAndUpdateCaseStatus($caseId, $userId);

        return $after;
    }

    /**
     * Add item to case
     */
    public function addCaseItem(int $caseId, array $itemData, int $userId): array
    {
        $itemData['case_id'] = $caseId;
        $itemData['version'] = 1;

        $itemId = $this->db->insert('case_items', $itemData);

        $item = $this->db->queryOne('SELECT * FROM case_items WHERE id = :id', [':id' => $itemId]);

        // Log audit
        $this->logAudit($caseId, $userId, 'item_added', null, null, null, $item);

        return $item;
    }

    /**
     * Delete case item
     */
    public function deleteCaseItem(int $caseId, int $itemId, int $userId): bool
    {
        $item = $this->db->queryOne('SELECT * FROM case_items WHERE id = :id AND case_id = :case_id', [
            ':id' => $itemId,
            ':case_id' => $caseId
        ]);

        if (!$item) {
            throw new \Exception('Case item not found');
        }

        // Log before deletion
        $this->logAudit($caseId, $userId, 'item_deleted', null, null, $item, null);

        $this->db->delete('case_items', 'id = :id', [':id' => $itemId]);

        return true;
    }

    /**
     * Check and update case status based on items
     */
    private function checkAndUpdateCaseStatus(int $caseId, int $userId): void
    {
        $sql = 'SELECT COUNT(*) as total,
                       SUM(CASE WHEN status = "done" THEN 1 ELSE 0 END) as done
                FROM case_items WHERE case_id = :case_id';

        $result = $this->db->queryOne($sql, [':case_id' => $caseId]);

        if ($result['total'] > 0 && $result['total'] == $result['done']) {
            // All items done, update case
            $case = $this->caseRepo->findById($caseId);
            if ($case && $case['status'] === 'in_progress') {
                try {
                    $this->caseRepo->update($caseId, [
                        'status' => 'done',
                        'completed_date' => date('Y-m-d'),
                        'updated_by' => $userId
                    ], (int) $case['version']);
                } catch (\Exception $e) {
                    // Ignore if concurrent modification
                }
            }
        }
    }

    /**
     * Validate case data
     */
    private function validateCaseData(array $data, bool $isUpdate = false): void
    {
        if (!$isUpdate) {
            // Required fields for creation
            if (empty($data['external_case_no'])) {
                throw new \Exception('Case number is required');
            }

            if (empty($data['source'])) {
                throw new \Exception('Source is required');
            }
        }

        // Validate status
        if (isset($data['status'])) {
            $validStatuses = ['new', 'in_progress', 'done', 'on_hold', 'canceled', 'archived'];
            if (!in_array($data['status'], $validStatuses)) {
                throw new \Exception('Invalid status');
            }
        }

        // Validate priority
        if (isset($data['priority'])) {
            $validPriorities = ['low', 'normal', 'high', 'urgent'];
            if (!in_array($data['priority'], $validPriorities)) {
                throw new \Exception('Invalid priority');
            }
        }

        // Validate due date format
        if (isset($data['due_date']) && $data['due_date'] !== null) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
                throw new \Exception('Invalid due date format. Use YYYY-MM-DD');
            }
        }
    }

    /**
     * Log audit entry
     */
    private function logAudit(
        int $caseId,
        int $userId,
        string $action,
        ?string $fieldName = null,
        $oldValue = null,
        ?array $beforeSnapshot = null,
        ?array $afterSnapshot = null
    ): void {
        $data = [
            'case_id' => $caseId,
            'user_id' => $userId,
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue !== null ? (string) $oldValue : null,
            'new_value' => $afterSnapshot && $fieldName ? (string) ($afterSnapshot[$fieldName] ?? null) : null,
            'before_snapshot' => $beforeSnapshot ? json_encode($beforeSnapshot) : null,
            'after_snapshot' => $afterSnapshot ? json_encode($afterSnapshot) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        $this->db->insert('case_audit_logs', $data);
    }

    /**
     * Log all changes between before and after
     */
    private function logChanges(int $caseId, int $userId, array $before, array $after): void
    {
        $ignoreFields = ['version', 'updated_at', 'updated_by'];

        foreach ($after as $field => $newValue) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            $oldValue = $before[$field] ?? null;

            if ($oldValue != $newValue) {
                $this->logAudit($caseId, $userId, 'updated', $field, $oldValue, $before, $after);
            }
        }
    }

    /**
     * Get audit logs for case
     */
    public function getAuditLogs(int $caseId, int $limit = 50): array
    {
        $sql = 'SELECT cal.*, u.name as user_name
                FROM case_audit_logs cal
                LEFT JOIN users u ON cal.user_id = u.id
                WHERE cal.case_id = :case_id
                ORDER BY cal.created_at DESC
                LIMIT :limit';

        return $this->db->query($sql, [':case_id' => $caseId, ':limit' => $limit]);
    }
}
