<?php
/**
 * Case Manager
 * Handles all case-related operations including CRUD and queries
 */

class CaseManager
{
    private Database $db;
    private Auth $auth;
    private WorkflowEngine $workflow;
    private static ?CaseManager $instance = null;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->workflow = WorkflowEngine::getInstance();
    }

    public static function getInstance(): CaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a new case with department assignments
     */
    public function createCase(array $caseData, array $departments = []): int
    {
        return $this->db->transaction(function ($db) use ($caseData, $departments) {
            // Set default values
            $caseData['created_timestamp'] = $caseData['created_timestamp'] ?? date('Y-m-d H:i:s');

            // Set department flags
            $caseData['has_cocr'] = in_array('COCR', $departments) ? 1 : 0;
            $caseData['has_solidex'] = in_array('SOLIDEX', $departments) ? 1 : 0;
            $caseData['has_3d_print'] = in_array('3D_PRINT', $departments) ? 1 : 0;

            // Set design confirm status
            if (!empty($caseData['design_confirm_required'])) {
                $caseData['design_confirm_status'] = DESIGN_CONFIRM_PENDING;
            }

            // Insert main case
            $caseId = $db->insert('cases', $caseData);

            return $caseId;
        });
    }

    /**
     * Initialize department workflows for a case
     */
    public function initializeDepartmentWorkflows(int $caseId, array $departmentData): void
    {
        // Initialize COCR if present
        if (!empty($departmentData['COCR'])) {
            $this->workflow->initializeCase($caseId, 'COCR', $departmentData['COCR']);
            $this->db->update('cases', ['has_cocr' => 1], ['id' => $caseId]);
        }

        // Initialize SOLIDEX if present
        if (!empty($departmentData['SOLIDEX'])) {
            $this->workflow->initializeCase($caseId, 'SOLIDEX', $departmentData['SOLIDEX']);
            $this->db->update('cases', ['has_solidex' => 1], ['id' => $caseId]);
        }

        // Initialize 3D PRINT if present
        if (!empty($departmentData['3D_PRINT'])) {
            $this->workflow->initializeCase($caseId, '3D_PRINT', $departmentData['3D_PRINT']);
            $this->db->update('cases', ['has_3d_print' => 1], ['id' => $caseId]);
        }
    }

    /**
     * Get a case by ID with all department data
     */
    public function getCase(int $caseId): ?array
    {
        $case = $this->db->fetchOne("SELECT * FROM cases WHERE id = ?", [$caseId]);

        if (!$case) {
            return null;
        }

        // Load department data
        if ($case['has_cocr']) {
            $case['cocr'] = $this->db->fetchOne("SELECT * FROM case_cocr WHERE case_id = ?", [$caseId]);
        }

        if ($case['has_solidex']) {
            $case['solidex'] = $this->db->fetchOne("SELECT * FROM case_solidex WHERE case_id = ?", [$caseId]);
            if ($case['solidex']) {
                $case['solidex']['teeth'] = $this->db->fetchAll(
                    "SELECT t.*, ws.step_name as current_step_name
                     FROM solidex_teeth_tasks t
                     LEFT JOIN workflow_steps ws ON t.current_step_id = ws.id
                     WHERE t.case_solidex_id = ?
                     ORDER BY t.tooth_number",
                    [$case['solidex']['id']]
                );
            }
        }

        if ($case['has_3d_print']) {
            $case['print'] = $this->db->fetchOne("SELECT * FROM case_3d_print WHERE case_id = ?", [$caseId]);
        }

        return $case;
    }

    /**
     * Get case by case number
     */
    public function getCaseByCaseNumber(string $caseNumber): ?array
    {
        $case = $this->db->fetchOne("SELECT id FROM cases WHERE case_number = ?", [$caseNumber]);
        return $case ? $this->getCase($case['id']) : null;
    }

    /**
     * Update case common data
     */
    public function updateCase(int $caseId, array $data, ?int $version = null): bool
    {
        return $this->db->update('cases', $data, ['id' => $caseId], $version) > 0;
    }

    /**
     * Get cases list with filters
     */
    public function getCases(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $where = ['1=1'];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
        } else {
            $where[] = 'c.status != ?';
            $params[] = STATUS_CANCELLED;
        }

        // Department filter
        if (!empty($filters['department'])) {
            $deptColumn = 'has_' . strtolower(str_replace('_', '_', $filters['department']));
            if ($filters['department'] === '3D_PRINT') {
                $deptColumn = 'has_3d_print';
            }
            $where[] = "c.$deptColumn = 1";
        }

        // Site filter
        if (!empty($filters['site'])) {
            $where[] = 'c.site = ?';
            $params[] = $filters['site'];
        }

        // Date range filter
        if (!empty($filters['due_date_from'])) {
            $where[] = 'c.due_date >= ?';
            $params[] = $filters['due_date_from'];
        }

        if (!empty($filters['due_date_to'])) {
            $where[] = 'c.due_date <= ?';
            $params[] = $filters['due_date_to'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(c.case_number LIKE ? OR c.lab_name LIKE ? OR c.patient_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases c WHERE $whereClause",
            $params
        );

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get cases
        $sql = "SELECT c.*
                FROM cases c
                WHERE $whereClause
                ORDER BY c.due_date ASC, c.created_timestamp DESC
                LIMIT $perPage OFFSET $offset";

        $cases = $this->db->fetchAll($sql, $params);

        return [
            'data' => $cases,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get department-specific case list
     */
    public function getDepartmentCases(string $department, array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $tableMap = [
            'COCR' => 'case_cocr',
            'SOLIDEX' => 'case_solidex',
            '3D_PRINT' => 'case_3d_print'
        ];

        $table = $tableMap[$department] ?? throw new Exception('Invalid department');
        $deptColumn = $department === '3D_PRINT' ? 'has_3d_print' : 'has_' . strtolower($department);

        $where = ["c.$deptColumn = 1"];
        $params = [];

        // Step filter
        if (!empty($filters['step'])) {
            $where[] = 'd.current_step_code = ?';
            $params[] = $filters['step'];
        }

        // Hold filter
        if (isset($filters['on_hold'])) {
            $where[] = 'd.is_on_hold = ?';
            $params[] = $filters['on_hold'] ? 1 : 0;
        }

        // Completed filter
        if (isset($filters['completed'])) {
            $where[] = 'd.is_completed = ?';
            $params[] = $filters['completed'] ? 1 : 0;
        } else {
            $where[] = 'd.is_completed = 0';
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
        }

        // Search
        if (!empty($filters['search'])) {
            $where[] = '(c.case_number LIKE ? OR c.lab_name LIKE ? OR c.patient_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Due date
        if (!empty($filters['due_date'])) {
            $where[] = 'c.due_date = ?';
            $params[] = $filters['due_date'];
        }

        // Note tags filter
        if (!empty($filters['note_tags']) && is_array($filters['note_tags'])) {
            foreach ($filters['note_tags'] as $tag) {
                $where[] = "JSON_CONTAINS(d.note_tags, ?)";
                $params[] = json_encode($tag);
            }
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM `$table` d
             INNER JOIN cases c ON d.case_id = c.id
             WHERE $whereClause",
            $params
        );

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get cases
        $sql = "SELECT d.*, c.case_number, c.site, c.due_date, c.lab_name, c.patient_name,
                       c.tooth_numbers, c.instructions, c.preferences, c.status as case_status,
                       c.design_confirm_required, c.design_confirm_status,
                       ws.step_name as current_step_name, ws.step_order
                FROM `$table` d
                INNER JOIN cases c ON d.case_id = c.id
                LEFT JOIN workflow_steps ws ON d.current_step_id = ws.id
                WHERE $whereClause
                ORDER BY c.due_date ASC, ws.step_order ASC, c.created_timestamp ASC
                LIMIT $perPage OFFSET $offset";

        $cases = $this->db->fetchAll($sql, $params);

        return [
            'data' => $cases,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get case timeline/history
     */
    public function getCaseTimeline(int $caseId): array
    {
        $transitions = $this->db->fetchAll(
            "SELECT st.*, u.full_name, u.initials
             FROM step_transitions st
             LEFT JOIN users u ON st.operator_id = u.id
             WHERE st.case_id = ?
             ORDER BY st.transition_at DESC",
            [$caseId]
        );

        $holds = $this->db->fetchAll(
            "SELECT hh.*, u.full_name, u.initials
             FROM hold_history hh
             LEFT JOIN users u ON hh.user_id = u.id
             WHERE hh.case_id = ?
             ORDER BY hh.action_at DESC",
            [$caseId]
        );

        // Merge and sort by date
        $timeline = [];

        foreach ($transitions as $t) {
            $timeline[] = [
                'type' => 'transition',
                'date' => $t['transition_at'],
                'data' => $t
            ];
        }

        foreach ($holds as $h) {
            $timeline[] = [
                'type' => 'hold',
                'date' => $h['action_at'],
                'data' => $h
            ];
        }

        usort($timeline, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $timeline;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(?string $department = null): array
    {
        $stats = [
            'total_active' => 0,
            'due_today' => 0,
            'overdue' => 0,
            'on_hold' => 0,
            'completed_today' => 0,
            'by_department' => []
        ];

        $today = date('Y-m-d');

        // Total active cases
        $sql = "SELECT COUNT(*) FROM cases WHERE status = 'ACTIVE'";
        if ($department) {
            $deptCol = $department === '3D_PRINT' ? 'has_3d_print' : 'has_' . strtolower($department);
            $sql .= " AND $deptCol = 1";
        }
        $stats['total_active'] = (int) $this->db->fetchColumn($sql);

        // Due today
        $sql = "SELECT COUNT(*) FROM cases WHERE status = 'ACTIVE' AND due_date = ?";
        $params = [$today];
        if ($department) {
            $deptCol = $department === '3D_PRINT' ? 'has_3d_print' : 'has_' . strtolower($department);
            $sql .= " AND $deptCol = 1";
        }
        $stats['due_today'] = (int) $this->db->fetchColumn($sql, $params);

        // Overdue
        $sql = "SELECT COUNT(*) FROM cases WHERE status = 'ACTIVE' AND due_date < ?";
        if ($department) {
            $deptCol = $department === '3D_PRINT' ? 'has_3d_print' : 'has_' . strtolower($department);
            $sql .= " AND $deptCol = 1";
        }
        $stats['overdue'] = (int) $this->db->fetchColumn($sql, $params);

        // On hold by department
        if ($department) {
            $tableMap = [
                'COCR' => 'case_cocr',
                'SOLIDEX' => 'case_solidex',
                '3D_PRINT' => 'case_3d_print'
            ];
            $table = $tableMap[$department];
            $stats['on_hold'] = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM `$table` WHERE is_on_hold = 1 AND is_completed = 0"
            );
        } else {
            $stats['on_hold'] = (int) $this->db->fetchColumn(
                "SELECT
                    (SELECT COUNT(*) FROM case_cocr WHERE is_on_hold = 1 AND is_completed = 0) +
                    (SELECT COUNT(*) FROM case_solidex WHERE is_on_hold = 1 AND is_completed = 0) +
                    (SELECT COUNT(*) FROM case_3d_print WHERE is_on_hold = 1 AND is_completed = 0)"
            );
        }

        // Completed today
        $sql = "SELECT COUNT(*) FROM cases WHERE completed_at >= ? AND completed_at < DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$today, $today];
        if ($department) {
            $deptCol = $department === '3D_PRINT' ? 'has_3d_print' : 'has_' . strtolower($department);
            $sql .= " AND $deptCol = 1";
        }
        $stats['completed_today'] = (int) $this->db->fetchColumn($sql, $params);

        // Stats by department (for super admin)
        if (!$department) {
            foreach (['COCR', 'SOLIDEX', '3D_PRINT'] as $dept) {
                $stats['by_department'][$dept] = $this->workflow->getStepStats($dept);
            }
        } else {
            $stats['by_step'] = $this->workflow->getStepStats($department);
        }

        return $stats;
    }

    /**
     * Update note tags for a department case
     */
    public function updateNoteTags(string $department, int $entityId, array $tagIds): bool
    {
        $tableMap = [
            'COCR' => 'case_cocr',
            'SOLIDEX' => 'case_solidex',
            '3D_PRINT' => 'case_3d_print'
        ];

        $table = $tableMap[$department] ?? throw new Exception('Invalid department');

        return $this->db->update($table, [
            'note_tags' => json_encode($tagIds)
        ], ['id' => $entityId]) > 0;
    }

    /**
     * Get all note tags
     */
    public function getNoteTags(?string $department = null): array
    {
        $where = 'is_active = 1';
        $params = [];

        if ($department) {
            $where .= ' AND (department = ? OR department = ?)';
            $params = [$department, 'ALL'];
        }

        return $this->db->fetchAll(
            "SELECT * FROM note_tags WHERE $where ORDER BY tag_name",
            $params
        );
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
