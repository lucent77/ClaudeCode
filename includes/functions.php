<?php
/**
 * Core Functions
 * LifeMandalart - Self Management Web Service
 */

// Prevent direct access
if (!defined('LIFE_MANDALART')) {
    die('Direct access not permitted');
}

/**
 * User Management Functions
 */
class User {
    /**
     * Get user by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetch(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get user by email
     */
    public static function getByEmail(string $email): ?array {
        return Database::fetch(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    /**
     * Get user by username
     */
    public static function getByUsername(string $username): ?array {
        return Database::fetch(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
    }

    /**
     * Create new user
     */
    public static function create(array $data): int {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        return Database::insert(
            "INSERT INTO users (username, email, password, name, language, theme)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['name'],
                $data['language'] ?? DEFAULT_LANGUAGE,
                $data['theme'] ?? DEFAULT_THEME
            ]
        );
    }

    /**
     * Update user
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        return Database::update(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        ) > 0;
    }

    /**
     * Add points to user
     */
    public static function addPoints(int $userId, int $points): bool {
        return Database::update(
            "UPDATE users SET points = points + ? WHERE id = ?",
            [$points, $userId]
        ) > 0;
    }

    /**
     * Check and update user level
     */
    public static function checkLevelUp(int $userId): ?array {
        $user = self::getById($userId);
        if (!$user) return null;

        $nextLevel = Database::fetch(
            "SELECT * FROM levels WHERE min_points <= ? ORDER BY level DESC LIMIT 1",
            [$user['points']]
        );

        if ($nextLevel && $nextLevel['level'] > $user['level']) {
            self::update($userId, ['level' => $nextLevel['level']]);
            return $nextLevel;
        }

        return null;
    }

    /**
     * Update streak
     */
    public static function updateStreak(int $userId, float $score): bool {
        $user = self::getById($userId);
        if (!$user) return false;

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($score >= STREAK_MIN_SCORE) {
            if ($user['last_active_date'] === $yesterday) {
                // Continue streak
                return self::update($userId, [
                    'streak_days' => $user['streak_days'] + 1,
                    'last_active_date' => $today
                ]);
            } elseif ($user['last_active_date'] !== $today) {
                // Start new streak
                return self::update($userId, [
                    'streak_days' => 1,
                    'last_active_date' => $today
                ]);
            }
        } else {
            // Reset streak if score is too low
            if ($user['last_active_date'] !== $today) {
                return self::update($userId, [
                    'streak_days' => 0,
                    'last_active_date' => $today
                ]);
            }
        }

        return true;
    }

    /**
     * Get user statistics
     */
    public static function getStats(int $userId): array {
        $user = self::getById($userId);

        $totalTasks = Database::fetch(
            "SELECT COUNT(*) as count FROM task_logs WHERE user_id = ? AND status = 'completed'",
            [$userId]
        )['count'] ?? 0;

        $weeklyScore = Database::fetch(
            "SELECT AVG(execution_score) as avg_score FROM daily_logs
             WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            [$userId]
        )['avg_score'] ?? 0;

        $badgeCount = Database::fetch(
            "SELECT COUNT(*) as count FROM user_achievements WHERE user_id = ?",
            [$userId]
        )['count'] ?? 0;

        return [
            'points' => $user['points'] ?? 0,
            'level' => $user['level'] ?? 1,
            'streak_days' => $user['streak_days'] ?? 0,
            'total_tasks_completed' => $totalTasks,
            'weekly_avg_score' => round($weeklyScore, 1),
            'badge_count' => $badgeCount
        ];
    }
}

/**
 * Goal Management Functions
 */
class Goal {
    /**
     * Get all goals for user
     */
    public static function getAllByUser(int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM goals WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Get active goal for user
     */
    public static function getActiveByUser(int $userId): ?array {
        return Database::fetch(
            "SELECT * FROM goals WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
    }

    /**
     * Get goal by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetch(
            "SELECT * FROM goals WHERE id = ?",
            [$id]
        );
    }

    /**
     * Create new goal
     */
    public static function create(int $userId, string $title, ?string $description = null, ?string $targetDate = null): int {
        return Database::insert(
            "INSERT INTO goals (user_id, title, description, target_date) VALUES (?, ?, ?, ?)",
            [$userId, $title, $description, $targetDate]
        );
    }

    /**
     * Update goal
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        return Database::update(
            "UPDATE goals SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        ) > 0;
    }

    /**
     * Delete goal
     */
    public static function delete(int $id): bool {
        return Database::delete("DELETE FROM goals WHERE id = ?", [$id]) > 0;
    }

    /**
     * Update goal progress
     */
    public static function updateProgress(int $goalId): bool {
        // Calculate progress based on subgoals
        $progress = Database::fetch(
            "SELECT AVG(progress) as avg_progress FROM subgoals WHERE goal_id = ?",
            [$goalId]
        )['avg_progress'] ?? 0;

        return self::update($goalId, ['progress' => $progress]);
    }
}

/**
 * SubGoal Management
 */
class SubGoal {
    /**
     * Get all subgoals for a goal
     */
    public static function getAllByGoal(int $goalId): array {
        return Database::fetchAll(
            "SELECT * FROM subgoals WHERE goal_id = ? ORDER BY position",
            [$goalId]
        );
    }

    /**
     * Get subgoal by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetch(
            "SELECT * FROM subgoals WHERE id = ?",
            [$id]
        );
    }

    /**
     * Create or update subgoal
     */
    public static function upsert(int $goalId, int $position, string $title, ?string $description = null, ?string $color = null): int {
        $existing = Database::fetch(
            "SELECT id FROM subgoals WHERE goal_id = ? AND position = ?",
            [$goalId, $position]
        );

        if ($existing) {
            Database::update(
                "UPDATE subgoals SET title = ?, description = ?, color = COALESCE(?, color) WHERE id = ?",
                [$title, $description, $color, $existing['id']]
            );
            return $existing['id'];
        }

        return Database::insert(
            "INSERT INTO subgoals (goal_id, position, title, description, color) VALUES (?, ?, ?, ?, ?)",
            [$goalId, $position, $title, $description, $color ?? '#3B82F6']
        );
    }

    /**
     * Update subgoal progress
     */
    public static function updateProgress(int $subgoalId): bool {
        $progress = Database::fetch(
            "SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as progress
             FROM tasks WHERE subgoal_id = ?",
            [$subgoalId]
        )['progress'] ?? 0;

        return Database::update(
            "UPDATE subgoals SET progress = ? WHERE id = ?",
            [$progress, $subgoalId]
        ) >= 0;
    }
}

/**
 * Task Management
 */
class Task {
    /**
     * Get all tasks for a subgoal
     */
    public static function getAllBySubgoal(int $subgoalId): array {
        return Database::fetchAll(
            "SELECT * FROM tasks WHERE subgoal_id = ? ORDER BY position, created_at",
            [$subgoalId]
        );
    }

    /**
     * Get task by ID
     */
    public static function getById(int $id): ?array {
        return Database::fetch(
            "SELECT t.*, s.goal_id, s.title as subgoal_title
             FROM tasks t
             JOIN subgoals s ON t.subgoal_id = s.id
             WHERE t.id = ?",
            [$id]
        );
    }

    /**
     * Get all tasks for user
     */
    public static function getAllByUser(int $userId): array {
        return Database::fetchAll(
            "SELECT t.*, s.title as subgoal_title, s.color as subgoal_color, g.title as goal_title
             FROM tasks t
             JOIN subgoals s ON t.subgoal_id = s.id
             JOIN goals g ON s.goal_id = g.id
             WHERE g.user_id = ? AND g.status = 'active'
             ORDER BY t.priority DESC, t.created_at",
            [$userId]
        );
    }

    /**
     * Get daily/recurring tasks for user
     */
    public static function getRecurringByUser(int $userId): array {
        return Database::fetchAll(
            "SELECT t.*, s.title as subgoal_title, s.color as subgoal_color
             FROM tasks t
             JOIN subgoals s ON t.subgoal_id = s.id
             JOIN goals g ON s.goal_id = g.id
             WHERE g.user_id = ? AND g.status = 'active' AND t.task_type IN ('daily', 'weekly')
             ORDER BY t.priority DESC",
            [$userId]
        );
    }

    /**
     * Create task
     */
    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO tasks (subgoal_id, position, title, description, task_type, frequency, frequency_days, priority, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['subgoal_id'],
                $data['position'] ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['task_type'] ?? 'one_time',
                $data['frequency'] ?? null,
                $data['frequency_days'] ?? null,
                $data['priority'] ?? 'medium',
                $data['due_date'] ?? null
            ]
        );
    }

    /**
     * Update task
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        return Database::update(
            "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        ) > 0;
    }

    /**
     * Delete task
     */
    public static function delete(int $id): bool {
        return Database::delete("DELETE FROM tasks WHERE id = ?", [$id]) > 0;
    }

    /**
     * Complete task for today
     */
    public static function complete(int $taskId, int $userId): array {
        $today = date('Y-m-d');
        $task = self::getById($taskId);

        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Check if already logged today
        $existingLog = Database::fetch(
            "SELECT * FROM task_logs WHERE task_id = ? AND log_date = ?",
            [$taskId, $today]
        );

        if ($existingLog) {
            return ['success' => false, 'message' => 'Task already completed today'];
        }

        // Calculate points
        $points = POINTS_TASK_COMPLETE;
        if ($task['priority'] === 'high') $points += POINTS_PRIORITY_BONUS;
        if ($task['priority'] === 'critical') $points += POINTS_PRIORITY_BONUS * 2;

        // Log completion
        Database::insert(
            "INSERT INTO task_logs (task_id, user_id, log_date, status, points_earned) VALUES (?, ?, ?, 'completed', ?)",
            [$taskId, $userId, $today, $points]
        );

        // Update task completion count
        Database::update(
            "UPDATE tasks SET completion_count = completion_count + 1, completed_at = NOW() WHERE id = ?",
            [$taskId]
        );

        // For one-time tasks, mark as completed
        if ($task['task_type'] === 'one_time' || $task['task_type'] === 'milestone') {
            self::update($taskId, ['status' => 'completed']);
        }

        // Add points to user
        User::addPoints($userId, $points);

        // Update subgoal progress
        SubGoal::updateProgress($task['subgoal_id']);

        return [
            'success' => true,
            'points' => $points,
            'message' => 'Task completed!'
        ];
    }

    /**
     * Uncomplete task for today
     */
    public static function uncomplete(int $taskId, int $userId): bool {
        $today = date('Y-m-d');

        $log = Database::fetch(
            "SELECT * FROM task_logs WHERE task_id = ? AND log_date = ? AND user_id = ?",
            [$taskId, $today, $userId]
        );

        if ($log) {
            // Remove points
            User::addPoints($userId, -$log['points_earned']);

            // Delete log
            Database::delete(
                "DELETE FROM task_logs WHERE id = ?",
                [$log['id']]
            );

            // Update task
            Database::update(
                "UPDATE tasks SET completion_count = GREATEST(0, completion_count - 1) WHERE id = ?",
                [$taskId]
            );

            return true;
        }

        return false;
    }
}

/**
 * Daily Log Management
 */
class DailyLog {
    /**
     * Get or create daily log
     */
    public static function getOrCreate(int $userId, string $date = null): array {
        $date = $date ?? date('Y-m-d');

        $log = Database::fetch(
            "SELECT * FROM daily_logs WHERE user_id = ? AND log_date = ?",
            [$userId, $date]
        );

        if (!$log) {
            $id = Database::insert(
                "INSERT INTO daily_logs (user_id, log_date) VALUES (?, ?)",
                [$userId, $date]
            );
            $log = Database::fetch("SELECT * FROM daily_logs WHERE id = ?", [$id]);
        }

        return $log;
    }

    /**
     * Update daily log
     */
    public static function update(int $userId, string $date, array $data): bool {
        self::getOrCreate($userId, $date);

        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $userId;
        $values[] = $date;

        return Database::update(
            "UPDATE daily_logs SET " . implode(', ', $fields) . " WHERE user_id = ? AND log_date = ?",
            $values
        ) >= 0;
    }

    /**
     * Calculate and save daily score
     */
    public static function calculateScore(int $userId, string $date = null): float {
        $date = $date ?? date('Y-m-d');

        // Get planned tasks for today
        $planned = Database::fetch(
            "SELECT COUNT(*) as count FROM daily_plans WHERE user_id = ? AND plan_date = ?",
            [$userId, $date]
        )['count'] ?? 0;

        // Get completed tasks for today
        $completed = Database::fetch(
            "SELECT COUNT(*) as count FROM task_logs WHERE user_id = ? AND log_date = ? AND status = 'completed'",
            [$userId, $date]
        )['count'] ?? 0;

        // Calculate score
        $score = $planned > 0 ? ($completed / $planned) * 100 : 0;
        $score = min(100, $score);

        // Calculate points
        $points = Database::fetch(
            "SELECT SUM(points_earned) as total FROM task_logs WHERE user_id = ? AND log_date = ?",
            [$userId, $date]
        )['total'] ?? 0;

        // Bonus for perfect day
        if ($score >= 100 && $planned > 0) {
            $points += POINTS_PERFECT_DAY;
            User::addPoints($userId, POINTS_PERFECT_DAY);
        }

        // Save to daily log
        self::update($userId, $date, [
            'planned_tasks' => $planned,
            'completed_tasks' => $completed,
            'execution_score' => $score,
            'points_earned' => $points
        ]);

        // Update user streak
        User::updateStreak($userId, $score);

        return $score;
    }

    /**
     * Get weekly summary
     */
    public static function getWeeklySummary(int $userId): array {
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));

        $logs = Database::fetchAll(
            "SELECT * FROM daily_logs WHERE user_id = ? AND log_date BETWEEN ? AND ? ORDER BY log_date",
            [$userId, $startDate, $endDate]
        );

        $totalPoints = 0;
        $totalScore = 0;
        $activeDays = 0;
        $dailyData = [];

        foreach ($logs as $log) {
            $totalPoints += $log['points_earned'];
            $totalScore += $log['execution_score'];
            if ($log['execution_score'] > 0) $activeDays++;
            $dailyData[$log['log_date']] = $log;
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_points' => $totalPoints,
            'avg_score' => $activeDays > 0 ? round($totalScore / $activeDays, 1) : 0,
            'active_days' => $activeDays,
            'daily_data' => $dailyData
        ];
    }

    /**
     * Get monthly chart data
     */
    public static function getMonthlyData(int $userId, int $year = null, int $month = null): array {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        return Database::fetchAll(
            "SELECT log_date, execution_score, points_earned, completed_tasks, planned_tasks
             FROM daily_logs
             WHERE user_id = ? AND log_date BETWEEN ? AND ?
             ORDER BY log_date",
            [$userId, $startDate, $endDate]
        );
    }
}

/**
 * Achievement Management
 */
class Achievement {
    /**
     * Get all achievements
     */
    public static function getAll(): array {
        return Database::fetchAll(
            "SELECT * FROM achievements WHERE is_active = 1 ORDER BY category, condition_value"
        );
    }

    /**
     * Get user's earned achievements
     */
    public static function getByUser(int $userId): array {
        return Database::fetchAll(
            "SELECT a.*, ua.earned_at
             FROM achievements a
             JOIN user_achievements ua ON a.id = ua.achievement_id
             WHERE ua.user_id = ?
             ORDER BY ua.earned_at DESC",
            [$userId]
        );
    }

    /**
     * Check and award achievements
     */
    public static function checkAndAward(int $userId): array {
        $awarded = [];
        $user = User::getById($userId);
        $stats = User::getStats($userId);

        $achievements = self::getAll();

        foreach ($achievements as $achievement) {
            // Skip if already earned
            $existing = Database::fetch(
                "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?",
                [$userId, $achievement['id']]
            );

            if ($existing) continue;

            $earned = false;

            switch ($achievement['condition_type']) {
                case 'goals_created':
                    $count = Database::fetch(
                        "SELECT COUNT(*) as count FROM goals WHERE user_id = ?",
                        [$userId]
                    )['count'];
                    $earned = $count >= $achievement['condition_value'];
                    break;

                case 'tasks_completed':
                    $earned = $stats['total_tasks_completed'] >= $achievement['condition_value'];
                    break;

                case 'streak_days':
                    $earned = $user['streak_days'] >= $achievement['condition_value'];
                    break;

                case 'streak_days_80':
                    // Check consecutive days with 80+ score
                    $count = self::getConsecutiveHighScoreDays($userId, 80);
                    $earned = $count >= $achievement['condition_value'];
                    break;

                case 'streak_days_70':
                    $count = self::getConsecutiveHighScoreDays($userId, 70);
                    $earned = $count >= $achievement['condition_value'];
                    break;

                case 'level_reached':
                    $earned = $user['level'] >= $achievement['condition_value'];
                    break;

                case 'mandalart_cells':
                    $count = self::countMandalartCells($userId);
                    $earned = $count >= $achievement['condition_value'];
                    break;

                case 'perfect_weeks':
                    $count = self::countPerfectWeeks($userId);
                    $earned = $count >= $achievement['condition_value'];
                    break;
            }

            if ($earned) {
                Database::insert(
                    "INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)",
                    [$userId, $achievement['id']]
                );

                // Award bonus points
                if ($achievement['points_reward'] > 0) {
                    User::addPoints($userId, $achievement['points_reward']);
                }

                // Create notification
                Notification::create($userId, 'achievement', [
                    'title_ko' => '업적 달성: ' . $achievement['name_ko'],
                    'title_en' => 'Achievement Unlocked: ' . $achievement['name_en'],
                    'message_ko' => $achievement['description_ko'],
                    'message_en' => $achievement['description_en']
                ]);

                $awarded[] = $achievement;
            }
        }

        return $awarded;
    }

    private static function getConsecutiveHighScoreDays(int $userId, int $minScore): int {
        $logs = Database::fetchAll(
            "SELECT log_date, execution_score FROM daily_logs
             WHERE user_id = ? ORDER BY log_date DESC LIMIT 100",
            [$userId]
        );

        $count = 0;
        $expectedDate = date('Y-m-d');

        foreach ($logs as $log) {
            if ($log['log_date'] !== $expectedDate) break;
            if ($log['execution_score'] < $minScore) break;
            $count++;
            $expectedDate = date('Y-m-d', strtotime($expectedDate . ' -1 day'));
        }

        return $count;
    }

    private static function countMandalartCells(int $userId): int {
        $goal = Goal::getActiveByUser($userId);
        if (!$goal) return 0;

        $count = 1; // Core goal

        $subgoals = SubGoal::getAllByGoal($goal['id']);
        $count += count($subgoals);

        foreach ($subgoals as $subgoal) {
            $tasks = Task::getAllBySubgoal($subgoal['id']);
            $count += count($tasks);
        }

        return $count;
    }

    private static function countPerfectWeeks(int $userId): int {
        return Database::fetch(
            "SELECT COUNT(*) as count FROM weekly_summaries
             WHERE user_id = ? AND active_days = 7 AND avg_execution_score >= 100",
            [$userId]
        )['count'] ?? 0;
    }
}

/**
 * Notification Management
 */
class Notification {
    /**
     * Create notification
     */
    public static function create(int $userId, string $type, array $data): int {
        return Database::insert(
            "INSERT INTO notifications (user_id, type, title_ko, title_en, message_ko, message_en, link)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $type,
                $data['title_ko'] ?? '',
                $data['title_en'] ?? '',
                $data['message_ko'] ?? null,
                $data['message_en'] ?? null,
                $data['link'] ?? null
            ]
        );
    }

    /**
     * Get user notifications
     */
    public static function getByUser(int $userId, int $limit = 20): array {
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Get unread count
     */
    public static function getUnreadCount(int $userId): int {
        return Database::fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        )['count'] ?? 0;
    }

    /**
     * Mark as read
     */
    public static function markAsRead(int $id): bool {
        return Database::update(
            "UPDATE notifications SET is_read = 1 WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Mark all as read
     */
    public static function markAllAsRead(int $userId): bool {
        return Database::update(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        ) >= 0;
    }
}

/**
 * Leaderboard Management
 */
class Leaderboard {
    /**
     * Get weekly leaderboard
     */
    public static function getWeekly(int $limit = 10): array {
        $startDate = date('Y-m-d', strtotime('monday this week'));

        return Database::fetchAll(
            "SELECT u.id, u.username, u.name, u.avatar, u.level,
                    SUM(dl.points_earned) as weekly_points,
                    AVG(dl.execution_score) as avg_score
             FROM users u
             JOIN daily_logs dl ON u.id = dl.user_id
             WHERE dl.log_date >= ?
             GROUP BY u.id
             ORDER BY weekly_points DESC
             LIMIT ?",
            [$startDate, $limit]
        );
    }

    /**
     * Get monthly leaderboard
     */
    public static function getMonthly(int $limit = 10): array {
        $startDate = date('Y-m-01');

        return Database::fetchAll(
            "SELECT u.id, u.username, u.name, u.avatar, u.level,
                    SUM(dl.points_earned) as monthly_points,
                    AVG(dl.execution_score) as avg_score
             FROM users u
             JOIN daily_logs dl ON u.id = dl.user_id
             WHERE dl.log_date >= ?
             GROUP BY u.id
             ORDER BY monthly_points DESC
             LIMIT ?",
            [$startDate, $limit]
        );
    }

    /**
     * Get all-time leaderboard
     */
    public static function getAllTime(int $limit = 10): array {
        return Database::fetchAll(
            "SELECT id, username, name, avatar, level, points
             FROM users
             ORDER BY points DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get user rank
     */
    public static function getUserRank(int $userId, string $type = 'all_time'): int {
        if ($type === 'weekly') {
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $result = Database::fetch(
                "SELECT COUNT(*) + 1 as rank FROM (
                    SELECT user_id, SUM(points_earned) as total
                    FROM daily_logs
                    WHERE log_date >= ?
                    GROUP BY user_id
                ) as rankings
                WHERE total > (
                    SELECT COALESCE(SUM(points_earned), 0)
                    FROM daily_logs
                    WHERE user_id = ? AND log_date >= ?
                )",
                [$startDate, $userId, $startDate]
            );
        } else {
            $result = Database::fetch(
                "SELECT COUNT(*) + 1 as rank FROM users WHERE points > (SELECT points FROM users WHERE id = ?)",
                [$userId]
            );
        }

        return $result['rank'] ?? 0;
    }
}

/**
 * Feedback Suggestions
 */
class Suggestion {
    /**
     * Generate suggestions based on user data
     */
    public static function generate(int $userId): array {
        $suggestions = [];
        $user = User::getById($userId);

        // Check for low completion rate tasks
        $lowCompletionTasks = Database::fetchAll(
            "SELECT t.id, t.title, t.frequency,
                    COUNT(tl.id) as completions,
                    DATEDIFF(NOW(), t.created_at) as days_since_created
             FROM tasks t
             JOIN subgoals s ON t.subgoal_id = s.id
             JOIN goals g ON s.goal_id = g.id
             LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.status = 'completed'
             WHERE g.user_id = ? AND t.task_type IN ('daily', 'weekly') AND g.status = 'active'
             GROUP BY t.id
             HAVING days_since_created > 14 AND (completions / days_since_created) < 0.3",
            [$userId]
        );

        foreach ($lowCompletionTasks as $task) {
            $suggestions[] = [
                'type' => 'reduce_frequency',
                'task_id' => $task['id'],
                'task_title' => $task['title'],
                'current_frequency' => $task['frequency'] ?? 7,
                'suggested_frequency' => max(1, ($task['frequency'] ?? 7) - 2)
            ];
        }

        // Check for low recent scores
        $recentAvg = Database::fetch(
            "SELECT AVG(execution_score) as avg FROM daily_logs
             WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            [$userId]
        )['avg'] ?? 0;

        if ($recentAvg < 40 && $recentAvg > 0) {
            $suggestions[] = [
                'type' => 'rest',
                'message' => 'suggestion_rest'
            ];
        }

        // Check for streak celebration
        if ($user['streak_days'] > 0 && $user['streak_days'] % 7 === 0) {
            $suggestions[] = [
                'type' => 'celebrate',
                'streak_days' => $user['streak_days']
            ];
        }

        return $suggestions;
    }
}
