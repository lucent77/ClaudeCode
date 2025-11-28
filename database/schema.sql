-- =====================================================
-- 자기 관리 웹서비스 - Database Schema
-- Mandal-Art & 7 Habits Based Goal Management System
-- =====================================================

CREATE DATABASE IF NOT EXISTS self_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE self_management;

-- -----------------------------------------------------
-- Users Table (사용자)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar_url VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0,
    level INT DEFAULT 1,
    streak_days INT DEFAULT 0,
    last_active_date DATE DEFAULT NULL,
    timezone VARCHAR(50) DEFAULT 'Asia/Seoul',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_points (points DESC)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Level Thresholds (레벨 기준)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS level_thresholds (
    level INT PRIMARY KEY,
    min_points INT NOT NULL,
    title VARCHAR(50) NOT NULL,
    badge_icon VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

INSERT INTO level_thresholds (level, min_points, title, badge_icon) VALUES
(1, 0, '새싹', '🌱'),
(2, 500, '성장', '🌿'),
(3, 1500, '발전', '🌳'),
(4, 3500, '성취', '⭐'),
(5, 7000, '마스터', '🏆'),
(6, 12000, '챔피언', '👑'),
(7, 20000, '전설', '💎'),
(8, 35000, '영웅', '🦸'),
(9, 55000, '현자', '🧙'),
(10, 80000, '완성', '🌟');

-- -----------------------------------------------------
-- Core Goals (핵심 목표 - 만다라트 중앙)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS core_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    target_date DATE,
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    progress_percent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Sub Goals (세부 목표 - 만다라트 주변 8칸)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS sub_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    core_goal_id INT NOT NULL,
    position INT NOT NULL CHECK (position BETWEEN 1 AND 8),
    title VARCHAR(200) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#6366f1',
    progress_percent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (core_goal_id) REFERENCES core_goals(id) ON DELETE CASCADE,
    UNIQUE KEY uk_goal_position (core_goal_id, position),
    INDEX idx_core_goal (core_goal_id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tasks (실행 과제 - 각 세부 목표당 최대 8개)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sub_goal_id INT NOT NULL,
    position INT NOT NULL CHECK (position BETWEEN 1 AND 8),
    title VARCHAR(200) NOT NULL,
    description TEXT,
    task_type ENUM('habit', 'one_time', 'milestone') DEFAULT 'habit',
    frequency ENUM('daily', 'weekly', 'monthly', 'custom') DEFAULT 'daily',
    frequency_count INT DEFAULT 1,
    frequency_per VARCHAR(20) DEFAULT 'day',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    points_value INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    completed_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sub_goal_id) REFERENCES sub_goals(id) ON DELETE CASCADE,
    INDEX idx_sub_goal (sub_goal_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Daily Plans (일일 계획)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_date DATE NOT NULL,
    notes TEXT,
    morning_intention TEXT,
    evening_reflection TEXT,
    mood_morning TINYINT CHECK (mood_morning BETWEEN 1 AND 5),
    mood_evening TINYINT CHECK (mood_evening BETWEEN 1 AND 5),
    energy_level TINYINT CHECK (energy_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_date (user_id, plan_date),
    INDEX idx_plan_date (plan_date)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Task Logs (과제 실행 기록)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS task_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    log_date DATE NOT NULL,
    daily_plan_id INT,
    status ENUM('planned', 'completed', 'skipped', 'partial') DEFAULT 'planned',
    is_priority BOOLEAN DEFAULT FALSE,
    completion_time TIMESTAMP NULL,
    notes TEXT,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (daily_plan_id) REFERENCES daily_plans(id) ON DELETE SET NULL,
    UNIQUE KEY uk_task_date (task_id, log_date),
    INDEX idx_user_date (user_id, log_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Daily Scores (일일 점수 요약)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS daily_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score_date DATE NOT NULL,
    total_planned INT DEFAULT 0,
    total_completed INT DEFAULT 0,
    priority_planned INT DEFAULT 0,
    priority_completed INT DEFAULT 0,
    execution_score INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    streak_maintained BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_date (user_id, score_date),
    INDEX idx_score_date (score_date),
    INDEX idx_execution_score (execution_score)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Achievements (업적/배지 정의)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NOT NULL,
    category ENUM('streak', 'completion', 'milestone', 'special', 'social') NOT NULL,
    condition_type VARCHAR(50) NOT NULL,
    condition_value INT NOT NULL,
    points_reward INT DEFAULT 0,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert Default Achievements
INSERT INTO achievements (code, name, description, icon, category, condition_type, condition_value, points_reward, rarity) VALUES
('first_goal', '첫 목표 설정', '첫 번째 핵심 목표를 설정했습니다', '🎯', 'milestone', 'goals_created', 1, 50, 'common'),
('mandal_complete', '만다라트 완성', '만다라트 81칸을 모두 채웠습니다', '📋', 'completion', 'mandal_complete', 1, 200, 'rare'),
('streak_7', '7일 연속', '7일 연속으로 목표를 달성했습니다', '🔥', 'streak', 'streak_days', 7, 100, 'common'),
('streak_30', '30일 챌린지', '30일 연속으로 목표를 달성했습니다', '💪', 'streak', 'streak_days', 30, 500, 'rare'),
('streak_100', '100일 마스터', '100일 연속으로 목표를 달성했습니다', '🏅', 'streak', 'streak_days', 100, 2000, 'epic'),
('early_bird', '아침형 인간', '오전 6시 전에 30번 과제를 완료했습니다', '🌅', 'special', 'early_completions', 30, 150, 'uncommon'),
('perfect_week', '완벽한 한 주', '일주일간 100% 실행률을 달성했습니다', '✨', 'completion', 'perfect_weeks', 1, 200, 'uncommon'),
('level_5', '성장의 증거', '레벨 5에 도달했습니다', '⭐', 'milestone', 'level_reached', 5, 300, 'uncommon'),
('level_10', '완성의 경지', '최고 레벨에 도달했습니다', '🌟', 'milestone', 'level_reached', 10, 1000, 'legendary'),
('first_review', '자기 성찰', '첫 주간 회고를 작성했습니다', '📝', 'milestone', 'reviews_written', 1, 50, 'common'),
('habit_master', '습관의 달인', '하나의 습관을 100번 실행했습니다', '🎖️', 'completion', 'habit_completions', 100, 300, 'rare'),
('social_butterfly', '함께 성장', '5명의 친구를 초대했습니다', '🦋', 'social', 'friends_invited', 5, 200, 'uncommon');

-- -----------------------------------------------------
-- User Achievements (사용자 업적)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_achievement (user_id, achievement_id),
    INDEX idx_earned_at (earned_at)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Weekly Reviews (주간 회고)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS weekly_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    accomplishments TEXT,
    challenges TEXT,
    lessons_learned TEXT,
    next_week_focus TEXT,
    overall_rating TINYINT CHECK (overall_rating BETWEEN 1 AND 5),
    avg_execution_score DECIMAL(5,2),
    total_points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_week (user_id, week_start),
    INDEX idx_week_start (week_start)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Monthly Reviews (월간 회고)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS monthly_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    review_month DATE NOT NULL,
    goal_progress TEXT,
    key_achievements TEXT,
    areas_for_improvement TEXT,
    next_month_priorities TEXT,
    overall_satisfaction TINYINT CHECK (overall_satisfaction BETWEEN 1 AND 5),
    habits_analysis JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_month (user_id, review_month)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Coaching Messages (코칭 메시지/제안)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS coaching_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_type ENUM('suggestion', 'encouragement', 'warning', 'celebration') NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    action_type VARCHAR(50),
    action_data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- User Settings (사용자 설정)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme ENUM('light', 'dark', 'auto') DEFAULT 'auto',
    language VARCHAR(10) DEFAULT 'ko',
    notification_email BOOLEAN DEFAULT TRUE,
    notification_push BOOLEAN DEFAULT TRUE,
    reminder_time TIME DEFAULT '08:00:00',
    weekly_review_day TINYINT DEFAULT 0,
    show_on_leaderboard BOOLEAN DEFAULT TRUE,
    profile_visibility ENUM('public', 'friends', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Friendships (친구 관계)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_friendship (user_id, friend_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Activity Feed (활동 피드)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('achievement', 'level_up', 'streak', 'goal_complete', 'milestone') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_public (is_public, created_at)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Views for Analytics
-- -----------------------------------------------------

-- Weekly Leaderboard View
CREATE OR REPLACE VIEW weekly_leaderboard AS
SELECT
    u.id,
    u.username,
    u.full_name,
    u.avatar_url,
    u.level,
    COALESCE(SUM(ds.points_earned), 0) as weekly_points,
    COALESCE(AVG(ds.execution_score), 0) as avg_score,
    u.streak_days
FROM users u
LEFT JOIN daily_scores ds ON u.id = ds.user_id
    AND ds.score_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY u.id
ORDER BY weekly_points DESC;

-- User Statistics View
CREATE OR REPLACE VIEW user_statistics AS
SELECT
    u.id as user_id,
    u.points as total_points,
    u.level,
    u.streak_days,
    (SELECT COUNT(*) FROM core_goals WHERE user_id = u.id AND status = 'active') as active_goals,
    (SELECT COUNT(*) FROM user_achievements WHERE user_id = u.id) as total_badges,
    (SELECT AVG(execution_score) FROM daily_scores WHERE user_id = u.id AND score_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_avg_score,
    (SELECT COUNT(*) FROM task_logs WHERE user_id = u.id AND status = 'completed') as total_completions
FROM users u;

-- =====================================================
-- Stored Procedures
-- =====================================================

DELIMITER //

-- Calculate Daily Score
CREATE PROCEDURE CalculateDailyScore(IN p_user_id INT, IN p_date DATE)
BEGIN
    DECLARE v_total_planned INT DEFAULT 0;
    DECLARE v_total_completed INT DEFAULT 0;
    DECLARE v_priority_planned INT DEFAULT 0;
    DECLARE v_priority_completed INT DEFAULT 0;
    DECLARE v_execution_score INT DEFAULT 0;
    DECLARE v_points_earned INT DEFAULT 0;

    -- Count tasks
    SELECT
        COUNT(*),
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END),
        SUM(CASE WHEN is_priority = 1 THEN 1 ELSE 0 END),
        SUM(CASE WHEN is_priority = 1 AND status = 'completed' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'completed' THEN points_earned ELSE 0 END)
    INTO v_total_planned, v_total_completed, v_priority_planned, v_priority_completed, v_points_earned
    FROM task_logs
    WHERE user_id = p_user_id AND log_date = p_date;

    -- Calculate execution score (with priority weight)
    IF v_total_planned > 0 THEN
        SET v_execution_score = ROUND(
            ((v_total_completed * 1.0 / v_total_planned) * 70) +
            (CASE WHEN v_priority_planned > 0
                THEN (v_priority_completed * 1.0 / v_priority_planned) * 30
                ELSE 30 END)
        );
    END IF;

    -- Insert or update daily score
    INSERT INTO daily_scores (user_id, score_date, total_planned, total_completed,
        priority_planned, priority_completed, execution_score, points_earned)
    VALUES (p_user_id, p_date, v_total_planned, v_total_completed,
        v_priority_planned, v_priority_completed, v_execution_score, v_points_earned)
    ON DUPLICATE KEY UPDATE
        total_planned = v_total_planned,
        total_completed = v_total_completed,
        priority_planned = v_priority_planned,
        priority_completed = v_priority_completed,
        execution_score = v_execution_score,
        points_earned = v_points_earned,
        updated_at = CURRENT_TIMESTAMP;

    -- Update user points
    UPDATE users SET points = points + v_points_earned WHERE id = p_user_id;
END //

-- Update User Streak
CREATE PROCEDURE UpdateUserStreak(IN p_user_id INT)
BEGIN
    DECLARE v_last_date DATE;
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_yesterday DATE;

    SET v_yesterday = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

    SELECT last_active_date, streak_days INTO v_last_date, v_current_streak
    FROM users WHERE id = p_user_id;

    -- Check if user was active yesterday
    IF v_last_date = v_yesterday THEN
        -- Continue streak
        UPDATE users SET
            streak_days = streak_days + 1,
            last_active_date = CURDATE()
        WHERE id = p_user_id;
    ELSEIF v_last_date < v_yesterday THEN
        -- Reset streak
        UPDATE users SET
            streak_days = 1,
            last_active_date = CURDATE()
        WHERE id = p_user_id;
    END IF;
END //

-- Check and Award Achievements
CREATE PROCEDURE CheckAchievements(IN p_user_id INT)
BEGIN
    DECLARE v_streak INT;
    DECLARE v_level INT;
    DECLARE v_goals_count INT;

    SELECT streak_days, level INTO v_streak, v_level FROM users WHERE id = p_user_id;
    SELECT COUNT(*) INTO v_goals_count FROM core_goals WHERE user_id = p_user_id;

    -- Check streak achievements
    INSERT IGNORE INTO user_achievements (user_id, achievement_id)
    SELECT p_user_id, id FROM achievements
    WHERE condition_type = 'streak_days' AND condition_value <= v_streak;

    -- Check level achievements
    INSERT IGNORE INTO user_achievements (user_id, achievement_id)
    SELECT p_user_id, id FROM achievements
    WHERE condition_type = 'level_reached' AND condition_value <= v_level;

    -- Check goals created
    INSERT IGNORE INTO user_achievements (user_id, achievement_id)
    SELECT p_user_id, id FROM achievements
    WHERE condition_type = 'goals_created' AND condition_value <= v_goals_count;
END //

-- Update User Level
CREATE PROCEDURE UpdateUserLevel(IN p_user_id INT)
BEGIN
    DECLARE v_points INT;
    DECLARE v_new_level INT;
    DECLARE v_current_level INT;

    SELECT points, level INTO v_points, v_current_level FROM users WHERE id = p_user_id;

    SELECT level INTO v_new_level FROM level_thresholds
    WHERE min_points <= v_points ORDER BY level DESC LIMIT 1;

    IF v_new_level > v_current_level THEN
        UPDATE users SET level = v_new_level WHERE id = p_user_id;

        -- Add to activity feed
        INSERT INTO activity_feed (user_id, activity_type, title, description, icon)
        SELECT p_user_id, 'level_up',
            CONCAT('레벨 ', v_new_level, ' 달성!'),
            CONCAT(title, ' 레벨에 도달했습니다!'),
            badge_icon
        FROM level_thresholds WHERE level = v_new_level;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- Sample Data for Testing
-- =====================================================

-- Insert a test user (password: test123)
INSERT INTO users (username, email, password_hash, full_name, points, level, streak_days) VALUES
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '테스트 사용자', 750, 2, 5);

-- Insert user settings
INSERT INTO user_settings (user_id) VALUES (1);

-- Insert a sample core goal
INSERT INTO core_goals (user_id, title, description, target_date) VALUES
(1, '영어 책 한 권 출간하기', '올해 안에 영어로 책을 집필하고 출간하는 것이 목표입니다.', '2025-12-31');

-- Insert sample sub goals
INSERT INTO sub_goals (core_goal_id, position, title, color) VALUES
(1, 1, '출판 기획', '#ef4444'),
(1, 2, '글쓰기 실력', '#f97316'),
(1, 3, '영어 번역', '#eab308'),
(1, 4, '인맥 구축', '#22c55e'),
(1, 5, '출판 지식', '#06b6d4'),
(1, 6, '시간 관리', '#3b82f6'),
(1, 7, '건강 관리', '#8b5cf6'),
(1, 8, '자료 조사', '#ec4899');

-- Insert sample tasks
INSERT INTO tasks (sub_goal_id, position, title, task_type, frequency, priority, points_value) VALUES
(2, 1, '블로그 주 3회 글쓰기', 'habit', 'weekly', 'high', 15),
(2, 2, '매달 글쓰기 모임 참석', 'habit', 'monthly', 'medium', 20),
(2, 3, '작문 교본 1권 완독', 'one_time', 'daily', 'medium', 50),
(7, 1, '주 3회 운동하기', 'habit', 'weekly', 'high', 15),
(7, 2, '하루 7시간 수면', 'habit', 'daily', 'high', 10),
(7, 3, '명상 10분', 'habit', 'daily', 'medium', 10);
