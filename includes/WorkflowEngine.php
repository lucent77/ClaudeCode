<?php
/**
 * Workflow Engine
 * Handles step transitions, bulk operations, and workflow logic
 */

class WorkflowEngine
{
    private Database $db;
    private Auth $auth;
    private static ?WorkflowEngine $instance = null;
    private array $stepsCache = [];

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->loadStepsCache();
    }

    public static function getInstance(): WorkflowEngine
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadStepsCache(): void
    {
        $steps = $this->db->fetchAll("SELECT * FROM workflow_steps WHERE is_active = 1 ORDER BY department, step_order");
        foreach ($steps as $step) {
            $this->stepsCache[$step['department']][$step['step_code']] = $step;
        }
    }

    /**
     * Get all steps for a department
     */
    public function getSteps(string $department): array
    {
        return $this->stepsCache[$department] ?? [];
    }

    /**
     * Get a specific step
     */
    public function getStep(string $department, string $stepCode): ?array
    {
        return $this->stepsCache[$department][$stepCode] ?? null;
    }

    /**
     * Get the first step for a department
     */
    public function getFirstStep(string $department): ?array
    {
        $steps = $this->getSteps($department);
        return reset($steps) ?: null;
    }

    /**
     * Get the next step after a given step
     */
    public function getNextStep(string $department, string $currentStepCode): ?array
    {
        $steps = array_values($this->getSteps($department));
        foreach ($steps as $index => $step) {
            if ($step['step_code'] === $currentStepCode && isset($steps[$index + 1])) {
                return $steps[$index + 1];
            }
        }
        return null;
    }

    /**
     * Check if a step is the final step
     */
    public function isFinalStep(string $department, string $stepCode): bool
    {
        $step = $this->getStep($department, $stepCode);
        return $step ? (bool) $step['is_final_step'] : false;
    }

    /**
     * Complete a step and advance to next step (single item)
     */
    public function completeStep(
        string $department,
        int $entityId,
        string $currentStepCode,
        ?string $machineName = null,
        ?string $notes = null,
        string $entityType = 'CASE'
    ): array {
        return $this->db->transaction(function ($db) use ($department, $entityId, $currentStepCode, $machineName, $notes, $entityType) {
            // Get the table and entity based on department and type
            $tableInfo = $this->getTableInfo($department, $entityType);
            $table = $tableInfo['table'];
            $idColumn = $tableInfo['id_column'];

            // Fetch current entity with lock
            $entity = $db->fetchOne(
                "SELECT * FROM `$table` WHERE `$idColumn` = ? FOR UPDATE",
                [$entityId]
            );

            if (!$entity) {
                throw new Exception('Record not found');
            }

            if ($entity['is_on_hold']) {
                throw new Exception('Cannot complete step for items on hold');
            }

            if ($entity['current_step_code'] !== $currentStepCode) {
                throw new ConcurrencyException('Step has already been changed. Please refresh.');
            }

            // Get current and next step
            $currentStep = $this->getStep($department, $currentStepCode);
            $nextStep = $this->getNextStep($department, $currentStepCode);

            // Validate machine input if required
            if ($currentStep['requires_machine_input'] && empty($machineName)) {
                throw new Exception('Machine name is required for this step');
            }

            // Prepare update data
            $updateData = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($nextStep) {
                $updateData['current_step_id'] = $nextStep['id'];
                $updateData['current_step_code'] = $nextStep['step_code'];
            } else {
                // Final step completed
                $updateData['is_completed'] = 1;
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                $updateData['completed_by'] = $this->auth->userId();
            }

            // Update with optimistic locking
            $db->update($table, $updateData, [$idColumn => $entityId], $entity['version']);

            // Log the transition
            $caseId = $entityType === 'TOOTH' ? $entity['case_id'] : ($entity['case_id'] ?? $entityId);
            $db->insert('step_transitions', [
                'case_id' => $caseId,
                'department' => $department,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'tooth_number' => $entity['tooth_number'] ?? null,
                'from_step_id' => $currentStep['id'],
                'from_step_code' => $currentStepCode,
                'to_step_id' => $nextStep['id'] ?? $currentStep['id'],
                'to_step_code' => $nextStep['step_code'] ?? 'COMPLETED',
                'operator_id' => $this->auth->userId(),
                'operator_initials' => $this->auth->userInitials(),
                'machine_name' => $machineName,
                'notes' => $notes
            ]);

            // If this is a SOLIDEX tooth task, check if all teeth are complete
            if ($entityType === 'TOOTH' && !$nextStep) {
                $this->checkSolidexCompletion($entity['case_solidex_id']);
            }

            return [
                'success' => true,
                'completed' => !$nextStep,
                'next_step' => $nextStep
            ];
        });
    }

    /**
     * Bulk complete steps for multiple items
     */
    public function bulkCompleteStep(
        string $department,
        array $entityIds,
        string $currentStepCode,
        ?string $machineName = null,
        ?string $notes = null,
        string $entityType = 'CASE'
    ): array {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($entityIds)
        ];

        foreach ($entityIds as $entityId) {
            try {
                $result = $this->completeStep($department, $entityId, $currentStepCode, $machineName, $notes, $entityType);
                $results['success'][] = [
                    'id' => $entityId,
                    'completed' => $result['completed'],
                    'next_step' => $result['next_step']['step_code'] ?? 'COMPLETED'
                ];
            } catch (Exception $e) {
                $results['failed'][] = [
                    'id' => $entityId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Put items on hold
     */
    public function setHold(
        string $department,
        int $entityId,
        string $reason,
        string $entityType = 'CASE'
    ): bool {
        return $this->db->transaction(function ($db) use ($department, $entityId, $reason, $entityType) {
            $tableInfo = $this->getTableInfo($department, $entityType);
            $table = $tableInfo['table'];
            $idColumn = $tableInfo['id_column'];

            // Fetch entity with lock
            $entity = $db->fetchOne("SELECT * FROM `$table` WHERE `$idColumn` = ? FOR UPDATE", [$entityId]);

            if (!$entity) {
                throw new Exception('Record not found');
            }

            if ($entity['is_on_hold']) {
                throw new Exception('Item is already on hold');
            }

            // Update entity
            $db->update($table, [
                'is_on_hold' => 1,
                'hold_reason' => $reason,
                'hold_at' => date('Y-m-d H:i:s'),
                'hold_by' => $this->auth->userId()
            ], [$idColumn => $entityId], $entity['version']);

            // Log hold history
            $caseId = $entityType === 'TOOTH' ? $entity['case_id'] : ($entity['case_id'] ?? $entityId);
            $db->insert('hold_history', [
                'case_id' => $caseId,
                'department' => $department,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'tooth_number' => $entity['tooth_number'] ?? null,
                'action' => 'HOLD',
                'reason' => $reason,
                'user_id' => $this->auth->userId()
            ]);

            return true;
        });
    }

    /**
     * Release items from hold
     */
    public function releaseHold(
        string $department,
        int $entityId,
        ?string $releaseNotes = null,
        string $entityType = 'CASE'
    ): bool {
        return $this->db->transaction(function ($db) use ($department, $entityId, $releaseNotes, $entityType) {
            $tableInfo = $this->getTableInfo($department, $entityType);
            $table = $tableInfo['table'];
            $idColumn = $tableInfo['id_column'];

            // Fetch entity with lock
            $entity = $db->fetchOne("SELECT * FROM `$table` WHERE `$idColumn` = ? FOR UPDATE", [$entityId]);

            if (!$entity) {
                throw new Exception('Record not found');
            }

            if (!$entity['is_on_hold']) {
                throw new Exception('Item is not on hold');
            }

            // Update entity
            $db->update($table, [
                'is_on_hold' => 0,
                'hold_reason' => null,
                'hold_at' => null,
                'hold_by' => null
            ], [$idColumn => $entityId], $entity['version']);

            // Log hold history
            $caseId = $entityType === 'TOOTH' ? $entity['case_id'] : ($entity['case_id'] ?? $entityId);
            $db->insert('hold_history', [
                'case_id' => $caseId,
                'department' => $department,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'tooth_number' => $entity['tooth_number'] ?? null,
                'action' => 'RELEASE',
                'reason' => $releaseNotes,
                'user_id' => $this->auth->userId()
            ]);

            return true;
        });
    }

    /**
     * Bulk set hold for multiple items
     */
    public function bulkSetHold(string $department, array $entityIds, string $reason, string $entityType = 'CASE'): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($entityIds as $entityId) {
            try {
                $this->setHold($department, $entityId, $reason, $entityType);
                $results['success'][] = $entityId;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => $entityId, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get table info for a department and entity type
     */
    private function getTableInfo(string $department, string $entityType): array
    {
        if ($entityType === 'TOOTH') {
            return [
                'table' => 'solidex_teeth_tasks',
                'id_column' => 'id'
            ];
        }

        $tables = [
            'COCR' => 'case_cocr',
            'SOLIDEX' => 'case_solidex',
            '3D_PRINT' => 'case_3d_print'
        ];

        return [
            'table' => $tables[$department] ?? throw new Exception('Invalid department'),
            'id_column' => 'id'
        ];
    }

    /**
     * Check if all SOLIDEX teeth are complete and update case completion
     */
    private function checkSolidexCompletion(int $caseSolidexId): void
    {
        $counts = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
             FROM solidex_teeth_tasks
             WHERE case_solidex_id = ?",
            [$caseSolidexId]
        );

        $isComplete = $counts['total'] > 0 && $counts['total'] == $counts['completed'];

        $this->db->update('case_solidex', [
            'teeth_completed_count' => (int) $counts['completed'],
            'is_completed' => $isComplete ? 1 : 0,
            'completed_at' => $isComplete ? date('Y-m-d H:i:s') : null
        ], ['id' => $caseSolidexId]);
    }

    /**
     * Initialize a new case in a department workflow
     */
    public function initializeCase(int $caseId, string $department, array $departmentData): int
    {
        $firstStep = $this->getFirstStep($department);

        if (!$firstStep) {
            throw new Exception("No workflow steps configured for $department");
        }

        $tableInfo = $this->getTableInfo($department, 'CASE');

        $data = array_merge($departmentData, [
            'case_id' => $caseId,
            'current_step_id' => $firstStep['id'],
            'current_step_code' => $firstStep['step_code']
        ]);

        $insertId = $this->db->insert($tableInfo['table'], $data);

        // For SOLIDEX, create tooth tasks
        if ($department === 'SOLIDEX' && !empty($departmentData['teeth_list'])) {
            $teeth = explode(',', $departmentData['teeth_list']);
            foreach ($teeth as $tooth) {
                $tooth = trim($tooth);
                if (!empty($tooth)) {
                    $this->db->insert('solidex_teeth_tasks', [
                        'case_solidex_id' => $insertId,
                        'case_id' => $caseId,
                        'tooth_number' => $tooth,
                        'current_step_id' => $firstStep['id'],
                        'current_step_code' => $firstStep['step_code']
                    ]);
                }
            }
        }

        return $insertId;
    }

    /**
     * Get cases for operator view (filtered by assigned steps)
     */
    public function getOperatorTasks(string $department, ?array $assignedSteps = null, array $filters = []): array
    {
        $tableInfo = $this->getTableInfo($department, 'CASE');
        $table = $tableInfo['table'];

        $where = ["d.is_completed = 0"];
        $params = [];

        // Filter by assigned steps
        if (!empty($assignedSteps)) {
            $placeholders = implode(',', array_fill(0, count($assignedSteps), '?'));
            $where[] = "d.current_step_code IN ($placeholders)";
            $params = array_merge($params, $assignedSteps);
        }

        // Apply filters
        if (!empty($filters['step'])) {
            $where[] = "d.current_step_code = ?";
            $params[] = $filters['step'];
        }

        if (isset($filters['on_hold'])) {
            $where[] = "d.is_on_hold = ?";
            $params[] = $filters['on_hold'] ? 1 : 0;
        }

        if (!empty($filters['due_date'])) {
            $where[] = "c.due_date = ?";
            $params[] = $filters['due_date'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(c.case_number LIKE ? OR c.lab_name LIKE ? OR c.patient_name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT d.*, c.case_number, c.site, c.due_date, c.lab_name, c.patient_name,
                       c.tooth_numbers, c.instructions, c.status as case_status,
                       ws.step_name as current_step_name
                FROM `$table` d
                INNER JOIN cases c ON d.case_id = c.id
                LEFT JOIN workflow_steps ws ON d.current_step_id = ws.id
                WHERE $whereClause
                ORDER BY c.due_date ASC, c.created_timestamp ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get SOLIDEX tooth tasks for operator
     */
    public function getSolidexToothTasks(?array $assignedSteps = null, array $filters = []): array
    {
        $where = ["t.is_completed = 0"];
        $params = [];

        if (!empty($assignedSteps)) {
            $placeholders = implode(',', array_fill(0, count($assignedSteps), '?'));
            $where[] = "t.current_step_code IN ($placeholders)";
            $params = array_merge($params, $assignedSteps);
        }

        if (!empty($filters['step'])) {
            $where[] = "t.current_step_code = ?";
            $params[] = $filters['step'];
        }

        if (isset($filters['on_hold'])) {
            $where[] = "t.is_on_hold = ?";
            $params[] = $filters['on_hold'] ? 1 : 0;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT t.*, c.case_number, c.site, c.due_date, c.lab_name, c.patient_name,
                       s.job_type, s.implant_system, s.note as solidex_note,
                       ws.step_name as current_step_name
                FROM solidex_teeth_tasks t
                INNER JOIN case_solidex s ON t.case_solidex_id = s.id
                INNER JOIN cases c ON t.case_id = c.id
                LEFT JOIN workflow_steps ws ON t.current_step_id = ws.id
                WHERE $whereClause
                ORDER BY c.due_date ASC, t.tooth_number ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get step statistics for dashboard
     */
    public function getStepStats(string $department): array
    {
        $tableInfo = $this->getTableInfo($department, 'CASE');
        $table = $tableInfo['table'];

        if ($department === 'SOLIDEX') {
            // For SOLIDEX, also include per-tooth stats
            $sql = "SELECT
                        current_step_code,
                        COUNT(*) as count,
                        SUM(CASE WHEN is_on_hold = 1 THEN 1 ELSE 0 END) as on_hold
                    FROM solidex_teeth_tasks
                    WHERE is_completed = 0
                    GROUP BY current_step_code";
        } else {
            $sql = "SELECT
                        current_step_code,
                        COUNT(*) as count,
                        SUM(CASE WHEN is_on_hold = 1 THEN 1 ELSE 0 END) as on_hold
                    FROM `$table`
                    WHERE is_completed = 0
                    GROUP BY current_step_code";
        }

        $results = $this->db->fetchAll($sql);
        $stats = [];

        foreach ($results as $row) {
            $stats[$row['current_step_code']] = [
                'count' => (int) $row['count'],
                'on_hold' => (int) $row['on_hold']
            ];
        }

        return $stats;
    }

    /**
     * Get hold items for management
     */
    public function getHoldItems(string $department, array $filters = []): array
    {
        $tableInfo = $this->getTableInfo($department, 'CASE');
        $table = $tableInfo['table'];

        $sql = "SELECT d.*, c.case_number, c.site, c.due_date, c.lab_name, c.patient_name,
                       u.full_name as hold_by_name, u.initials as hold_by_initials,
                       DATEDIFF(NOW(), d.hold_at) as days_on_hold,
                       ws.step_name as current_step_name
                FROM `$table` d
                INNER JOIN cases c ON d.case_id = c.id
                LEFT JOIN users u ON d.hold_by = u.id
                LEFT JOIN workflow_steps ws ON d.current_step_id = ws.id
                WHERE d.is_on_hold = 1
                ORDER BY d.hold_at ASC";

        return $this->db->fetchAll($sql);
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
