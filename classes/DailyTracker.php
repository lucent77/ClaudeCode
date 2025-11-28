<?php
/**
 * Daily Tracker Class
 * 일일 계획 및 실행 관리
 */

require_once __DIR__ . '/../config/database.php';

class DailyTracker {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get or create daily plan
     */
    public function getDailyPlan($userId, $date = null) {
        $date = $date ?? date('Y-m-d');

        $plan = $this->db->fetch(
            "SELECT * FROM daily_plans WHERE user_id = ? AND plan_date = ?",
            [$userId, $date]
        );

        if (!$plan) {
            $planId = $this->db->insert('daily_plans', [
                'user_id' => $userId,
                'plan_date' => $date
            ]);
            $plan = $this->db->fetch("SELECT * FROM daily_plans WHERE id = ?", [$planId]);
        }

        return $plan;
    }

    /**
     * Update daily plan
     */
    public function updateDailyPlan($planId, $data) {
        $allowedFields = ['notes', 'morning_intention', 'evening_reflection',
                          'mood_morning', 'mood_evening', 'energy_level'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('daily_plans', $updateData, 'id = ?', [$planId]);
        return true;
    }

    /**
     * Get task logs for a date
     */
    public function getTaskLogs($userId, $date = null) {
        $date = $date ?? date('Y-m-d');

        return $this->db->fetchAll(
            "SELECT tl.*, t.title, t.task_type, t.frequency, t.priority, t.points_value,
                    sg.title as sub_goal_title, sg.color, cg.title as core_goal_title
             FROM task_logs tl
             JOIN tasks t ON tl.task_id = t.id
             JOIN sub_goals sg ON t.sub_goal_id = sg.id
             JOIN core_goals cg ON sg.core_goal_id = cg.id
             WHERE tl.user_id = ? AND tl.log_date = ?
             ORDER BY tl.is_priority DESC, t.priority DESC, tl.id",
            [$userId, $date]
        );
    }

    /**
     * Add task to daily plan
     */
    public function addTaskToPlan($userId, $taskId, $date = null, $isPriority = false) {
        $date = $date ?? date('Y-m-d');

        // Get daily plan
        $plan = $this->getDailyPlan($userId, $date);

        // Check if already exists
        $existing = $this->db->fetch(
            "SELECT id FROM task_logs WHERE task_id = ? AND log_date = ?",
            [$taskId, $date]
        );

        if ($existing) {
            return $existing['id'];
        }

        // Get task info
        $task = $this->db->fetch("SELECT points_value FROM tasks WHERE id = ?", [$taskId]);

        return $this->db->insert('task_logs', [
            'task_id' => $taskId,
            'user_id' => $userId,
            'log_date' => $date,
            'daily_plan_id' => $plan['id'],
            'status' => 'planned',
            'is_priority' => $isPriority ? 1 : 0,
            'points_earned' => $task['points_value'] ?? 10
        ]);
    }

    /**
     * Remove task from daily plan
     */
    public function removeTaskFromPlan($logId, $userId) {
        $this->db->delete('task_logs', 'id = ? AND user_id = ?', [$logId, $userId]);
    }

    /**
     * Complete task
     */
    public function completeTask($logId, $userId, $notes = '') {
        $log = $this->db->fetch(
            "SELECT tl.*, t.points_value FROM task_logs tl
             JOIN tasks t ON tl.task_id = t.id
             WHERE tl.id = ? AND tl.user_id = ?",
            [$logId, $userId]
        );

        if (!$log) return false;

        // Calculate bonus points for priority tasks
        $points = $log['points_value'];
        if ($log['is_priority']) {
            $points = round($points * 1.5);
        }

        $this->db->update('task_logs', [
            'status' => 'completed',
            'completion_time' => date('Y-m-d H:i:s'),
            'notes' => $notes,
            'points_earned' => $points
        ], 'id = ?', [$logId]);

        // Update task completion count
        $this->db->query(
            "UPDATE tasks SET completed_count = completed_count + 1 WHERE id = ?",
            [$log['task_id']]
        );

        return $points;
    }

    /**
     * Uncomplete task (revert)
     */
    public function uncompleteTask($logId, $userId) {
        $log = $this->db->fetch(
            "SELECT * FROM task_logs WHERE id = ? AND user_id = ?",
            [$logId, $userId]
        );

        if (!$log || $log['status'] !== 'completed') return false;

        $this->db->update('task_logs', [
            'status' => 'planned',
            'completion_time' => null,
            'points_earned' => 0
        ], 'id = ?', [$logId]);

        // Decrease task completion count
        $this->db->query(
            "UPDATE tasks SET completed_count = GREATEST(0, completed_count - 1) WHERE id = ?",
            [$log['task_id']]
        );

        return true;
    }

    /**
     * Skip task
     */
    public function skipTask($logId, $userId, $reason = '') {
        $this->db->update('task_logs', [
            'status' => 'skipped',
            'notes' => $reason
        ], 'id = ? AND user_id = ?', [$logId, $userId]);
    }

    /**
     * Set task priority
     */
    public function setTaskPriority($logId, $userId, $isPriority) {
        $this->db->update('task_logs', [
            'is_priority' => $isPriority ? 1 : 0
        ], 'id = ? AND user_id = ?', [$logId, $userId]);
    }

    /**
     * Calculate and save daily score
     */
    public function calculateDailyScore($userId, $date = null) {
        $date = $date ?? date('Y-m-d');

        // Use stored procedure
        $this->db->query("CALL CalculateDailyScore(?, ?)", [$userId, $date]);

        return $this->getDailyScore($userId, $date);
    }

    /**
     * Get daily score
     */
    public function getDailyScore($userId, $date = null) {
        $date = $date ?? date('Y-m-d');

        return $this->db->fetch(
            "SELECT * FROM daily_scores WHERE user_id = ? AND score_date = ?",
            [$userId, $date]
        );
    }

    /**
     * Get score history
     */
    public function getScoreHistory($userId, $days = 30) {
        return $this->db->fetchAll(
            "SELECT * FROM daily_scores
             WHERE user_id = ? AND score_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY score_date DESC",
            [$userId, $days]
        );
    }

    /**
     * Get weekly statistics
     */
    public function getWeeklyStats($userId, $weekStart = null) {
        $weekStart = $weekStart ?? date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        return $this->db->fetch(
            "SELECT
                COUNT(*) as days_logged,
                SUM(total_completed) as total_completed,
                SUM(total_planned) as total_planned,
                AVG(execution_score) as avg_score,
                SUM(points_earned) as total_points,
                MAX(execution_score) as best_score,
                MIN(execution_score) as worst_score
             FROM daily_scores
             WHERE user_id = ? AND score_date BETWEEN ? AND ?",
            [$userId, $weekStart, $weekEnd]
        );
    }

    /**
     * Get monthly statistics
     */
    public function getMonthlyStats($userId, $month = null) {
        $month = $month ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->db->fetch(
            "SELECT
                COUNT(*) as days_logged,
                SUM(total_completed) as total_completed,
                SUM(total_planned) as total_planned,
                AVG(execution_score) as avg_score,
                SUM(points_earned) as total_points,
                MAX(execution_score) as best_score,
                SUM(CASE WHEN execution_score >= 80 THEN 1 ELSE 0 END) as great_days,
                SUM(CASE WHEN execution_score >= 100 THEN 1 ELSE 0 END) as perfect_days
             FROM daily_scores
             WHERE user_id = ? AND score_date BETWEEN ? AND ?",
            [$userId, $startDate, $endDate]
        );
    }

    /**
     * Get task completion rate by category
     */
    public function getCompletionByCategory($userId, $days = 30) {
        return $this->db->fetchAll(
            "SELECT
                sg.title as category,
                sg.color,
                COUNT(tl.id) as total_tasks,
                SUM(CASE WHEN tl.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                ROUND(SUM(CASE WHEN tl.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(tl.id), 0), 1) as completion_rate
             FROM task_logs tl
             JOIN tasks t ON tl.task_id = t.id
             JOIN sub_goals sg ON t.sub_goal_id = sg.id
             WHERE tl.user_id = ? AND tl.log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY sg.id, sg.title, sg.color
             ORDER BY completion_rate DESC",
            [$userId, $days]
        );
    }

    /**
     * Get struggling tasks (low completion rate)
     */
    public function getStrugglingTasks($userId, $days = 14, $threshold = 50) {
        return $this->db->fetchAll(
            "SELECT
                t.id,
                t.title,
                t.frequency,
                sg.title as sub_goal_title,
                COUNT(tl.id) as total_planned,
                SUM(CASE WHEN tl.status = 'completed' THEN 1 ELSE 0 END) as completed,
                ROUND(SUM(CASE WHEN tl.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(tl.id), 0), 1) as completion_rate
             FROM task_logs tl
             JOIN tasks t ON tl.task_id = t.id
             JOIN sub_goals sg ON t.sub_goal_id = sg.id
             WHERE tl.user_id = ? AND tl.log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY t.id
             HAVING total_planned >= 3 AND completion_rate < ?
             ORDER BY completion_rate ASC
             LIMIT 5",
            [$userId, $days, $threshold]
        );
    }

    /**
     * Auto-suggest tasks for today based on frequency
     */
    public function suggestTasksForToday($userId) {
        $dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
        $dayOfMonth = date('j');

        return $this->db->fetchAll(
            "SELECT t.*, sg.title as sub_goal_title, sg.color
             FROM tasks t
             JOIN sub_goals sg ON t.sub_goal_id = sg.id
             JOIN core_goals cg ON sg.core_goal_id = cg.id
             WHERE cg.user_id = ? AND cg.status = 'active' AND t.is_active = 1
             AND t.id NOT IN (SELECT task_id FROM task_logs WHERE log_date = CURDATE())
             AND (
                 t.frequency = 'daily'
                 OR (t.frequency = 'weekly' AND DAYOFWEEK(CURDATE()) IN (2, 4, 6))
                 OR (t.frequency = 'monthly' AND DAY(CURDATE()) = 1)
             )
             ORDER BY t.priority DESC, t.id
             LIMIT 10",
            [$userId]
        );
    }
}
