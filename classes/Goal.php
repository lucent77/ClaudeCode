<?php
/**
 * Goal Management Class
 * 만다라트 목표 관리
 */

require_once __DIR__ . '/../config/database.php';

class Goal {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create core goal
     */
    public function createCoreGoal($userId, $title, $description = '', $targetDate = null) {
        return $this->db->insert('core_goals', [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'target_date' => $targetDate
        ]);
    }

    /**
     * Get core goal by ID
     */
    public function getCoreGoal($goalId, $userId = null) {
        $sql = "SELECT * FROM core_goals WHERE id = ?";
        $params = [$goalId];

        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return $this->db->fetch($sql, $params);
    }

    /**
     * Get all core goals for user
     */
    public function getUserCoreGoals($userId, $status = 'active') {
        return $this->db->fetchAll(
            "SELECT cg.*,
                    (SELECT COUNT(*) FROM sub_goals WHERE core_goal_id = cg.id) as sub_goals_count,
                    (SELECT COUNT(*) FROM tasks t
                     JOIN sub_goals sg ON t.sub_goal_id = sg.id
                     WHERE sg.core_goal_id = cg.id) as tasks_count
             FROM core_goals cg
             WHERE cg.user_id = ? AND cg.status = ?
             ORDER BY cg.created_at DESC",
            [$userId, $status]
        );
    }

    /**
     * Update core goal
     */
    public function updateCoreGoal($goalId, $userId, $data) {
        $allowedFields = ['title', 'description', 'target_date', 'status', 'progress_percent'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('core_goals', $updateData, 'id = ? AND user_id = ?', [$goalId, $userId]);
        return true;
    }

    /**
     * Delete core goal
     */
    public function deleteCoreGoal($goalId, $userId) {
        $this->db->delete('core_goals', 'id = ? AND user_id = ?', [$goalId, $userId]);
    }

    /**
     * Create sub goal
     */
    public function createSubGoal($coreGoalId, $position, $title, $description = '', $color = '#6366f1') {
        // Check if position is available
        $existing = $this->db->fetch(
            "SELECT id FROM sub_goals WHERE core_goal_id = ? AND position = ?",
            [$coreGoalId, $position]
        );

        if ($existing) {
            // Update existing
            $this->db->update('sub_goals', [
                'title' => $title,
                'description' => $description,
                'color' => $color
            ], 'id = ?', [$existing['id']]);
            return $existing['id'];
        }

        return $this->db->insert('sub_goals', [
            'core_goal_id' => $coreGoalId,
            'position' => $position,
            'title' => $title,
            'description' => $description,
            'color' => $color
        ]);
    }

    /**
     * Get sub goals for core goal
     */
    public function getSubGoals($coreGoalId) {
        return $this->db->fetchAll(
            "SELECT sg.*,
                    (SELECT COUNT(*) FROM tasks WHERE sub_goal_id = sg.id) as tasks_count,
                    (SELECT COUNT(*) FROM tasks WHERE sub_goal_id = sg.id AND is_active = 1) as active_tasks_count
             FROM sub_goals sg
             WHERE sg.core_goal_id = ?
             ORDER BY sg.position",
            [$coreGoalId]
        );
    }

    /**
     * Get single sub goal
     */
    public function getSubGoal($subGoalId) {
        return $this->db->fetch("SELECT * FROM sub_goals WHERE id = ?", [$subGoalId]);
    }

    /**
     * Update sub goal
     */
    public function updateSubGoal($subGoalId, $data) {
        $allowedFields = ['title', 'description', 'color', 'progress_percent'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('sub_goals', $updateData, 'id = ?', [$subGoalId]);
        return true;
    }

    /**
     * Delete sub goal
     */
    public function deleteSubGoal($subGoalId) {
        $this->db->delete('sub_goals', 'id = ?', [$subGoalId]);
    }

    /**
     * Create task
     */
    public function createTask($subGoalId, $position, $data) {
        // Check if position is available
        $existing = $this->db->fetch(
            "SELECT id FROM tasks WHERE sub_goal_id = ? AND position = ?",
            [$subGoalId, $position]
        );

        $taskData = [
            'sub_goal_id' => $subGoalId,
            'position' => $position,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'task_type' => $data['task_type'] ?? 'habit',
            'frequency' => $data['frequency'] ?? 'daily',
            'frequency_count' => $data['frequency_count'] ?? 1,
            'priority' => $data['priority'] ?? 'medium',
            'points_value' => $data['points_value'] ?? 10
        ];

        if ($existing) {
            unset($taskData['sub_goal_id'], $taskData['position']);
            $this->db->update('tasks', $taskData, 'id = ?', [$existing['id']]);
            return $existing['id'];
        }

        return $this->db->insert('tasks', $taskData);
    }

    /**
     * Get tasks for sub goal
     */
    public function getTasks($subGoalId) {
        return $this->db->fetchAll(
            "SELECT * FROM tasks WHERE sub_goal_id = ? ORDER BY position",
            [$subGoalId]
        );
    }

    /**
     * Get single task
     */
    public function getTask($taskId) {
        return $this->db->fetch("SELECT * FROM tasks WHERE id = ?", [$taskId]);
    }

    /**
     * Update task
     */
    public function updateTask($taskId, $data) {
        $allowedFields = ['title', 'description', 'task_type', 'frequency', 'frequency_count',
                          'priority', 'points_value', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('tasks', $updateData, 'id = ?', [$taskId]);
        return true;
    }

    /**
     * Delete task
     */
    public function deleteTask($taskId) {
        $this->db->delete('tasks', 'id = ?', [$taskId]);
    }

    /**
     * Get full mandal-art structure
     */
    public function getFullMandalart($coreGoalId) {
        $coreGoal = $this->getCoreGoal($coreGoalId);
        if (!$coreGoal) return null;

        $subGoals = $this->getSubGoals($coreGoalId);
        foreach ($subGoals as &$subGoal) {
            $subGoal['tasks'] = $this->getTasks($subGoal['id']);
        }

        return [
            'core_goal' => $coreGoal,
            'sub_goals' => $subGoals
        ];
    }

    /**
     * Get user's active tasks for daily planning
     */
    public function getActiveTasksForUser($userId) {
        return $this->db->fetchAll(
            "SELECT t.*, sg.title as sub_goal_title, sg.color, cg.title as core_goal_title
             FROM tasks t
             JOIN sub_goals sg ON t.sub_goal_id = sg.id
             JOIN core_goals cg ON sg.core_goal_id = cg.id
             WHERE cg.user_id = ? AND cg.status = 'active' AND t.is_active = 1
             ORDER BY t.priority DESC, t.id",
            [$userId]
        );
    }

    /**
     * Calculate and update progress
     */
    public function updateProgress($coreGoalId) {
        // Update sub goals progress
        $this->db->query(
            "UPDATE sub_goals sg SET progress_percent = (
                SELECT COALESCE(
                    ROUND(SUM(CASE WHEN t.completed_count > 0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)),
                    0
                )
                FROM tasks t WHERE t.sub_goal_id = sg.id
             )
             WHERE sg.core_goal_id = ?",
            [$coreGoalId]
        );

        // Update core goal progress
        $this->db->query(
            "UPDATE core_goals SET progress_percent = (
                SELECT COALESCE(ROUND(AVG(progress_percent)), 0)
                FROM sub_goals WHERE core_goal_id = ?
             )
             WHERE id = ?",
            [$coreGoalId, $coreGoalId]
        );
    }

    /**
     * Get mandal-art completion stats
     */
    public function getCompletionStats($coreGoalId) {
        return $this->db->fetch(
            "SELECT
                (SELECT COUNT(*) FROM sub_goals WHERE core_goal_id = ?) as sub_goals_count,
                (SELECT COUNT(*) FROM tasks t
                 JOIN sub_goals sg ON t.sub_goal_id = sg.id
                 WHERE sg.core_goal_id = ?) as tasks_count,
                (SELECT COUNT(*) FROM sub_goals WHERE core_goal_id = ? AND title != '') as filled_sub_goals,
                (SELECT COUNT(*) FROM tasks t
                 JOIN sub_goals sg ON t.sub_goal_id = sg.id
                 WHERE sg.core_goal_id = ? AND t.title != '') as filled_tasks",
            [$coreGoalId, $coreGoalId, $coreGoalId, $coreGoalId]
        );
    }
}
