-- LifeMandalart: Self-Management Web Service
-- Database Schema for MySQL
-- Version: 1.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `points` INT UNSIGNED DEFAULT 0,
    `level` INT UNSIGNED DEFAULT 1,
    `streak_days` INT UNSIGNED DEFAULT 0,
    `last_active_date` DATE DEFAULT NULL,
    `language` ENUM('ko', 'en') DEFAULT 'ko',
    `theme` ENUM('light', 'dark', 'system') DEFAULT 'system',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Seoul',
    `email_notifications` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_points` (`points` DESC),
    INDEX `idx_level` (`level` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Level thresholds table
CREATE TABLE IF NOT EXISTS `levels` (
    `level` INT UNSIGNED PRIMARY KEY,
    `name_ko` VARCHAR(50) NOT NULL,
    `name_en` VARCHAR(50) NOT NULL,
    `min_points` INT UNSIGNED NOT NULL,
    `badge_icon` VARCHAR(100) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#3B82F6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default levels
INSERT INTO `levels` (`level`, `name_ko`, `name_en`, `min_points`, `color`) VALUES
(1, '씨앗', 'Seed', 0, '#9CA3AF'),
(2, '새싹', 'Sprout', 100, '#84CC16'),
(3, '줄기', 'Stem', 300, '#22C55E'),
(4, '꽃봉오리', 'Bud', 600, '#14B8A6'),
(5, '꽃', 'Flower', 1000, '#06B6D4'),
(6, '열매', 'Fruit', 1500, '#3B82F6'),
(7, '나무', 'Tree', 2500, '#8B5CF6'),
(8, '숲', 'Forest', 4000, '#A855F7'),
(9, '산', 'Mountain', 6000, '#EC4899'),
(10, '별', 'Star', 10000, '#F59E0B');

-- Core Goals (Mandalart center)
CREATE TABLE IF NOT EXISTS `goals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `target_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'completed', 'archived') DEFAULT 'active',
    `progress` DECIMAL(5,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_target_date` (`target_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sub Goals (8 surrounding cells in Mandalart)
CREATE TABLE IF NOT EXISTS `subgoals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `goal_id` INT UNSIGNED NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL COMMENT 'Position 1-8 around center',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#3B82F6',
    `icon` VARCHAR(50) DEFAULT NULL,
    `progress` DECIMAL(5,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`goal_id`) REFERENCES `goals`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_goal_position` (`goal_id`, `position`),
    INDEX `idx_goal_id` (`goal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks (Action items for each subgoal)
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subgoal_id` INT UNSIGNED NOT NULL,
    `position` TINYINT UNSIGNED DEFAULT NULL COMMENT 'Position 1-8 in subgoal grid',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `task_type` ENUM('daily', 'weekly', 'one_time', 'milestone') DEFAULT 'one_time',
    `frequency` TINYINT UNSIGNED DEFAULT NULL COMMENT 'Times per week for recurring tasks',
    `frequency_days` VARCHAR(20) DEFAULT NULL COMMENT 'Specific days: 1,2,3,4,5 for Mon-Fri',
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    `due_date` DATE DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `completion_count` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`subgoal_id`) REFERENCES `subgoals`(`id`) ON DELETE CASCADE,
    INDEX `idx_subgoal_id` (`subgoal_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_task_type` (`task_type`),
    INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily task logs
CREATE TABLE IF NOT EXISTS `task_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `log_date` DATE NOT NULL,
    `status` ENUM('completed', 'partial', 'skipped', 'missed') DEFAULT 'completed',
    `points_earned` INT UNSIGNED DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_task_date` (`task_id`, `log_date`),
    INDEX `idx_user_date` (`user_id`, `log_date`),
    INDEX `idx_log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily summary logs
CREATE TABLE IF NOT EXISTS `daily_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `log_date` DATE NOT NULL,
    `planned_tasks` INT UNSIGNED DEFAULT 0,
    `completed_tasks` INT UNSIGNED DEFAULT 0,
    `execution_score` DECIMAL(5,2) DEFAULT 0.00,
    `points_earned` INT UNSIGNED DEFAULT 0,
    `mood` TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 scale',
    `energy` TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 scale',
    `notes` TEXT DEFAULT NULL,
    `reflection` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_date` (`user_id`, `log_date`),
    INDEX `idx_log_date` (`log_date`),
    INDEX `idx_execution_score` (`execution_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Weekly summaries
CREATE TABLE IF NOT EXISTS `weekly_summaries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `week_start` DATE NOT NULL,
    `week_end` DATE NOT NULL,
    `total_points` INT UNSIGNED DEFAULT 0,
    `avg_execution_score` DECIMAL(5,2) DEFAULT 0.00,
    `active_days` TINYINT UNSIGNED DEFAULT 0,
    `top_subgoal_id` INT UNSIGNED DEFAULT NULL,
    `reflection` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`top_subgoal_id`) REFERENCES `subgoals`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_user_week` (`user_id`, `week_start`),
    INDEX `idx_week_start` (`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Achievements/Badges definition
CREATE TABLE IF NOT EXISTS `achievements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name_ko` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `description_ko` TEXT NOT NULL,
    `description_en` TEXT NOT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#F59E0B',
    `category` ENUM('streak', 'completion', 'milestone', 'social', 'special') DEFAULT 'milestone',
    `condition_type` VARCHAR(50) NOT NULL,
    `condition_value` INT UNSIGNED NOT NULL,
    `points_reward` INT UNSIGNED DEFAULT 0,
    `rarity` ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default achievements
INSERT INTO `achievements` (`code`, `name_ko`, `name_en`, `description_ko`, `description_en`, `icon`, `category`, `condition_type`, `condition_value`, `points_reward`, `rarity`) VALUES
('first_goal', '첫 발걸음', 'First Step', '첫 번째 핵심 목표를 설정했습니다', 'Set your first core goal', 'trophy', 'milestone', 'goals_created', 1, 10, 'common'),
('mandalart_complete', '청사진 완성', 'Blueprint Complete', '만다라트 81칸을 모두 채웠습니다', 'Filled all 81 cells of Mandalart', 'grid', 'completion', 'mandalart_cells', 81, 100, 'epic'),
('streak_7', '일주일 전사', 'Week Warrior', '7일 연속 실행 점수 80점 이상 달성', '7 consecutive days with 80+ execution score', 'fire', 'streak', 'streak_days_80', 7, 50, 'uncommon'),
('streak_30', '한 달의 기적', 'Monthly Miracle', '30일 연속 실행 점수 70점 이상 달성', '30 consecutive days with 70+ execution score', 'calendar', 'streak', 'streak_days_70', 30, 200, 'rare'),
('streak_100', '백일의 약속', '100 Day Promise', '100일 연속 활동', '100 consecutive days of activity', 'crown', 'streak', 'streak_days', 100, 500, 'legendary'),
('early_bird', '아침형 인간', 'Early Bird', '오전 6시 이전에 할 일 완료 30회', 'Complete tasks before 6 AM 30 times', 'sun', 'special', 'early_completions', 30, 75, 'uncommon'),
('night_owl', '야행성 올빼미', 'Night Owl', '자정 이후에 할 일 완료 30회', 'Complete tasks after midnight 30 times', 'moon', 'special', 'night_completions', 30, 75, 'uncommon'),
('perfect_week', '완벽한 한 주', 'Perfect Week', '일주일 동안 모든 계획 100% 달성', '100% completion for an entire week', 'star', 'completion', 'perfect_weeks', 1, 100, 'rare'),
('task_master_100', '과제 마스터', 'Task Master', '100개의 과제 완료', 'Complete 100 tasks', 'check-circle', 'milestone', 'tasks_completed', 100, 50, 'common'),
('task_master_500', '과제 전문가', 'Task Expert', '500개의 과제 완료', 'Complete 500 tasks', 'check-double', 'milestone', 'tasks_completed', 500, 150, 'uncommon'),
('task_master_1000', '과제 달인', 'Task Grandmaster', '1000개의 과제 완료', 'Complete 1000 tasks', 'award', 'milestone', 'tasks_completed', 1000, 300, 'rare'),
('seven_habits', '7가지 습관 마스터', '7 Habits Master', '7가지 습관 영역 모두 진행', 'Progress in all 7 habits areas', 'book', 'special', 'habits_covered', 7, 150, 'epic'),
('first_friend', '첫 친구', 'First Friend', '첫 번째 친구 추가', 'Add your first friend', 'users', 'social', 'friends_added', 1, 20, 'common'),
('motivator', '응원단장', 'Cheerleader', '다른 사용자 격려 50회', 'Encourage other users 50 times', 'heart', 'social', 'encouragements_given', 50, 100, 'uncommon'),
('level_5', '성장의 발판', 'Growth Foundation', '레벨 5 달성', 'Reach level 5', 'trending-up', 'milestone', 'level_reached', 5, 50, 'common'),
('level_10', '정상을 향해', 'Towards the Peak', '레벨 10 달성', 'Reach level 10', 'mountain', 'milestone', 'level_reached', 10, 200, 'epic');

-- User achievements (earned badges)
CREATE TABLE IF NOT EXISTS `user_achievements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `achievement_id` INT UNSIGNED NOT NULL,
    `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notified` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_achievement` (`user_id`, `achievement_id`),
    INDEX `idx_earned_at` (`earned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily plans (tasks selected for today)
CREATE TABLE IF NOT EXISTS `daily_plans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NOT NULL,
    `plan_date` DATE NOT NULL,
    `is_priority` TINYINT(1) DEFAULT 0,
    `sort_order` TINYINT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_plan_task` (`user_id`, `task_id`, `plan_date`),
    INDEX `idx_user_date` (`user_id`, `plan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('achievement', 'reminder', 'streak', 'level_up', 'suggestion', 'social') DEFAULT 'reminder',
    `title_ko` VARCHAR(200) NOT NULL,
    `title_en` VARCHAR(200) NOT NULL,
    `message_ko` TEXT DEFAULT NULL,
    `message_en` TEXT DEFAULT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings for 7 Habits tracking
CREATE TABLE IF NOT EXISTS `habits_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `habit_1_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Be Proactive',
    `habit_2_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Begin with End in Mind',
    `habit_3_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Put First Things First',
    `habit_4_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Think Win-Win',
    `habit_5_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Seek First to Understand',
    `habit_6_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Synergize',
    `habit_7_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Sharpen the Saw',
    `weekly_review_day` TINYINT UNSIGNED DEFAULT 0 COMMENT '0=Sunday, 1=Monday...',
    `daily_reminder_time` TIME DEFAULT '08:00:00',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friends/Social connections
CREATE TABLE IF NOT EXISTS `friendships` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `friend_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `accepted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_friendship` (`user_id`, `friend_id`),
    INDEX `idx_friend_status` (`friend_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encouragements/Cheers between users
CREATE TABLE IF NOT EXISTS `encouragements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_user_id` INT UNSIGNED NOT NULL,
    `to_user_id` INT UNSIGNED NOT NULL,
    `message` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_to_user` (`to_user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session management
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
    `email` VARCHAR(100) PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
