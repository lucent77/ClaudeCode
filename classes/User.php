<?php
/**
 * User Class
 * 사용자 관리 및 인증
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $fullName;
    private $avatarUrl;
    private $points;
    private $level;
    private $streakDays;
    private $lastActiveDate;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFullName() { return $this->fullName; }
    public function getAvatarUrl() { return $this->avatarUrl; }
    public function getPoints() { return $this->points; }
    public function getLevel() { return $this->level; }
    public function getStreakDays() { return $this->streakDays; }

    /**
     * Register new user
     */
    public function register($username, $email, $password, $fullName = '') {
        // Validate input
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => '사용자명은 3-50자 사이여야 합니다.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => '유효한 이메일 주소를 입력하세요.'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => '비밀번호는 6자 이상이어야 합니다.'];
        }

        // Check if username or email exists
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($existing) {
            return ['success' => false, 'error' => '이미 존재하는 사용자명 또는 이메일입니다.'];
        }

        // Hash password and create user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'full_name' => $fullName ?: $username
            ]);

            // Create default settings
            $this->db->insert('user_settings', ['user_id' => $userId]);

            // Award first achievement
            $this->db->query(
                "INSERT INTO activity_feed (user_id, activity_type, title, description, icon)
                 VALUES (?, 'milestone', '여정의 시작', '자기 관리 웹서비스에 가입했습니다!', '🚀')",
                [$userId]
            );

            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => '회원가입 중 오류가 발생했습니다.'];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => '이메일 또는 비밀번호가 올바르지 않습니다.'];
        }

        $this->loadFromArray($user);
        $this->updateStreak();

        return ['success' => true, 'user' => $this->toArray()];
    }

    /**
     * Load user by ID
     */
    public function loadById($id) {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if ($user) {
            $this->loadFromArray($user);
            return true;
        }
        return false;
    }

    /**
     * Load user data from array
     */
    private function loadFromArray($data) {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->fullName = $data['full_name'];
        $this->avatarUrl = $data['avatar_url'];
        $this->points = $data['points'];
        $this->level = $data['level'];
        $this->streakDays = $data['streak_days'];
        $this->lastActiveDate = $data['last_active_date'];
    }

    /**
     * Get user as array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'avatar_url' => $this->avatarUrl,
            'points' => $this->points,
            'level' => $this->level,
            'streak_days' => $this->streakDays
        ];
    }

    /**
     * Update streak
     */
    public function updateStreak() {
        if (!$this->id) return;
        $this->db->query("CALL UpdateUserStreak(?)", [$this->id]);
        $this->loadById($this->id);
    }

    /**
     * Add points to user
     */
    public function addPoints($points) {
        if (!$this->id) return;

        $this->db->query(
            "UPDATE users SET points = points + ? WHERE id = ?",
            [$points, $this->id]
        );

        // Check level up
        $this->db->query("CALL UpdateUserLevel(?)", [$this->id]);
        $this->loadById($this->id);
    }

    /**
     * Get level info
     */
    public function getLevelInfo() {
        $current = $this->db->fetch(
            "SELECT * FROM level_thresholds WHERE level = ?",
            [$this->level]
        );

        $next = $this->db->fetch(
            "SELECT * FROM level_thresholds WHERE level = ?",
            [$this->level + 1]
        );

        $progress = 0;
        if ($next) {
            $progressRange = $next['min_points'] - $current['min_points'];
            $userProgress = $this->points - $current['min_points'];
            $progress = min(100, round(($userProgress / $progressRange) * 100));
        }

        return [
            'current_level' => $current,
            'next_level' => $next,
            'progress' => $progress,
            'points_to_next' => $next ? $next['min_points'] - $this->points : 0
        ];
    }

    /**
     * Get user statistics
     */
    public function getStatistics() {
        if (!$this->id) return null;

        return $this->db->fetch(
            "SELECT * FROM user_statistics WHERE user_id = ?",
            [$this->id]
        );
    }

    /**
     * Get user achievements
     */
    public function getAchievements() {
        if (!$this->id) return [];

        return $this->db->fetchAll(
            "SELECT a.*, ua.earned_at
             FROM user_achievements ua
             JOIN achievements a ON ua.achievement_id = a.id
             WHERE ua.user_id = ?
             ORDER BY ua.earned_at DESC",
            [$this->id]
        );
    }

    /**
     * Check and award achievements
     */
    public function checkAchievements() {
        if (!$this->id) return;
        $this->db->query("CALL CheckAchievements(?)", [$this->id]);
    }

    /**
     * Get activity feed
     */
    public function getActivityFeed($limit = 20) {
        if (!$this->id) return [];

        return $this->db->fetchAll(
            "SELECT * FROM activity_feed
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$this->id, $limit]
        );
    }

    /**
     * Update profile
     */
    public function updateProfile($data) {
        if (!$this->id) return false;

        $allowedFields = ['full_name', 'avatar_url'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('users', $updateData, 'id = ?', [$this->id]);
        $this->loadById($this->id);
        return true;
    }

    /**
     * Change password
     */
    public function changePassword($currentPassword, $newPassword) {
        if (!$this->id) return ['success' => false, 'error' => '로그인이 필요합니다.'];

        $user = $this->db->fetch("SELECT password_hash FROM users WHERE id = ?", [$this->id]);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => '현재 비밀번호가 올바르지 않습니다.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => '새 비밀번호는 6자 이상이어야 합니다.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', ['password_hash' => $newHash], 'id = ?', [$this->id]);

        return ['success' => true];
    }

    /**
     * Get settings
     */
    public function getSettings() {
        if (!$this->id) return null;

        return $this->db->fetch(
            "SELECT * FROM user_settings WHERE user_id = ?",
            [$this->id]
        );
    }

    /**
     * Update settings
     */
    public function updateSettings($data) {
        if (!$this->id) return false;

        $allowedFields = ['theme', 'language', 'notification_email', 'notification_push',
                          'reminder_time', 'weekly_review_day', 'show_on_leaderboard', 'profile_visibility'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) return false;

        $this->db->update('user_settings', $updateData, 'user_id = ?', [$this->id]);
        return true;
    }
}
