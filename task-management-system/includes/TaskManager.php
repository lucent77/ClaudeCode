<?php
/**
 * Task Management and CRUD Operations
 */

require_once __DIR__ . '/Database.php';

class TaskManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get tasks with filters
     */
    public function getTasks($filters = []) {
        $sql = "SELECT * FROM task_details WHERE 1=1";
        $params = [];

        if (isset($filters['department_id'])) {
            $sql .= " AND department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (isset($filters['status_id'])) {
            $sql .= " AND status_id = ?";
            $params[] = $filters['status_id'];
        }

        if (isset($filters['assigned_to'])) {
            $sql .= " AND assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (isset($filters['date_from'])) {
            $sql .= " AND date >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND date <= ?";
            $params[] = $filters['date_to'];
        }

        if (isset($filters['search'])) {
            $sql .= " AND (external_id LIKE ? OR notes LIKE ? OR lab LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY date DESC, id DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Get single task by ID
     */
    public function getTaskById($id) {
        $sql = "SELECT * FROM task_details WHERE id = ? LIMIT 1";
        return $this->db->queryOne($sql, [$id]);
    }

    /**
     * Create new task
     */
    public function createTask($data) {
        $sql = "INSERT INTO tasks (
                    external_id, department_id, created_by, assigned_to,
                    date, due_date, type, status_id, priority,
                    tooth, implant_type, implant_system, design, lab, lab_number,
                    patient_number, case_number, teeth, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['external_id'] ?? null,
            $data['department_id'],
            $data['created_by'],
            $data['assigned_to'] ?? null,
            $data['date'] ?? date('Y-m-d'),
            $data['due_date'] ?? null,
            $data['type'] ?? null,
            $data['status_id'] ?? 1,
            $data['priority'] ?? 'normal',
            $data['tooth'] ?? null,
            $data['implant_type'] ?? null,
            $data['implant_system'] ?? null,
            $data['design'] ?? null,
            $data['lab'] ?? null,
            $data['lab_number'] ?? null,
            $data['patient_number'] ?? null,
            $data['case_number'] ?? null,
            $data['teeth'] ?? null,
            $data['notes'] ?? null
        ];

        try {
            $this->db->execute($sql, $params);
            $taskId = $this->db->lastInsertId();

            // Log creation
            $this->logTaskHistory($taskId, $data['created_by'], 'created', null, null, null);

            return ['success' => true, 'task_id' => $taskId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create task'];
        }
    }

    /**
     * Update task
     */
    public function updateTask($id, $data, $userId) {
        // Get current task data for history
        $currentTask = $this->getTaskById($id);
        if (!$currentTask) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $updates = [];
        $params = [];

        $allowedFields = [
            'external_id', 'department_id', 'assigned_to', 'date', 'due_date',
            'type', 'status_id', 'priority', 'tooth', 'implant_type',
            'implant_system', 'design', 'lab', 'lab_number', 'patient_number',
            'case_number', 'teeth', 'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];

                // Log change if value is different
                if ($currentTask[$field] != $data[$field]) {
                    $this->logTaskHistory(
                        $id,
                        $userId,
                        'update',
                        $field,
                        $currentTask[$field],
                        $data[$field]
                    );
                }
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $id;
        $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $this->db->execute($sql, $params);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update task'];
        }
    }

    /**
     * Delete task
     */
    public function deleteTask($id, $userId) {
        try {
            // Log deletion
            $this->logTaskHistory($id, $userId, 'deleted', null, null, null);

            $this->db->execute("DELETE FROM tasks WHERE id = ?", [$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete task'];
        }
    }

    /**
     * Log task history
     */
    private function logTaskHistory($taskId, $userId, $action, $fieldName = null, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO task_history (task_id, user_id, action, field_name, old_value, new_value)
                VALUES (?, ?, ?, ?, ?, ?)";

        $this->db->execute($sql, [$taskId, $userId, $action, $fieldName, $oldValue, $newValue]);
    }

    /**
     * Get task history
     */
    public function getTaskHistory($taskId) {
        $sql = "SELECT th.*, u.name as user_name
                FROM task_history th
                INNER JOIN users u ON th.user_id = u.id
                WHERE th.task_id = ?
                ORDER BY th.changed_at DESC";

        return $this->db->query($sql, [$taskId]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($departmentId = null) {
        $where = $departmentId ? "WHERE department_id = $departmentId" : "";

        $stats = [];

        // Total tasks
        $stats['total'] = $this->db->queryOne("SELECT COUNT(*) as count FROM tasks $where")['count'];

        // Pending tasks
        $stats['pending'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tasks $where " . ($where ? "AND" : "WHERE") . " status_id = 1"
        )['count'];

        // In progress
        $stats['in_progress'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tasks $where " . ($where ? "AND" : "WHERE") . " status_id = 2"
        )['count'];

        // Completed
        $stats['completed'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tasks $where " . ($where ? "AND" : "WHERE") . " status_id = 4"
        )['count'];

        // Due today
        $stats['due_today'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tasks $where " . ($where ? "AND" : "WHERE") . " due_date = CURDATE()"
        )['count'];

        // Overdue
        $stats['overdue'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tasks $where " . ($where ? "AND" : "WHERE") . " due_date < CURDATE() AND status_id NOT IN (4, 5)"
        )['count'];

        return $stats;
    }

    /**
     * Get tasks updated since timestamp (for real-time updates)
     */
    public function getUpdatedTasksSince($timestamp, $departmentId = null) {
        $sql = "SELECT * FROM task_details WHERE updated_at > ?";
        $params = [$timestamp];

        if ($departmentId) {
            $sql .= " AND department_id = ?";
            $params[] = $departmentId;
        }

        $sql .= " ORDER BY updated_at DESC";

        return $this->db->query($sql, $params);
    }
}
