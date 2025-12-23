-- ==============================================
-- URL Collector Database Schema
-- MySQL/MariaDB Compatible
-- ==============================================

-- Create database (if needed)
-- CREATE DATABASE IF NOT EXISTS url_collector
--     CHARACTER SET utf8mb4
--     COLLATE utf8mb4_unicode_ci;
-- USE url_collector;

-- ==============================================
-- Main Links Table
-- ==============================================
CREATE TABLE IF NOT EXISTS `links` (
    -- Primary Key
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- URL Information
    `url` TEXT NOT NULL,
    `url_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 hash for duplicate detection',
    `site` VARCHAR(255) NULL COMMENT 'Domain name',

    -- Extracted Metadata
    `title` TEXT NULL,
    `description` TEXT NULL,
    `image_url` TEXT NULL,
    `raw_text` MEDIUMTEXT NULL COMMENT 'Extracted page text content',

    -- AI Analysis Results
    `summary` TEXT NULL COMMENT 'AI-generated summary',
    `keywords` JSON NULL COMMENT 'Array of keywords',
    `category` VARCHAR(50) NULL COMMENT 'news|video|shopping|social|tech|business|design|music|dental|education|other',

    -- Scores
    `auto_score` INT NULL COMMENT 'AI-suggested interest score (0-100)',
    `interest_score` INT NULL COMMENT 'User-assigned interest score (0-100)',

    -- Review Status
    `review_status` ENUM('inbox', 'keep', 'archive') NOT NULL DEFAULT 'inbox',

    -- User Notes
    `note` TEXT NULL,

    -- Processing Status
    `status` ENUM('pending', 'done', 'error') NOT NULL DEFAULT 'pending',
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_message` TEXT NULL,

    -- Source Tracking
    `source` VARCHAR(50) NULL COMMENT 'Origin of URL (ios_shortcut, web, etc.)',

    -- Timestamps
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `analyzed_at` DATETIME NULL,
    `reviewed_at` DATETIME NULL COMMENT 'When review_status changed from inbox',

    PRIMARY KEY (`id`),

    -- Unique constraint on URL hash
    UNIQUE KEY `idx_url_hash` (`url_hash`),

    -- Status and processing indexes
    KEY `idx_status_created` (`status`, `created_at`),
    KEY `idx_status_retry` (`status`, `retry_count`),

    -- Review and sorting indexes
    KEY `idx_review_status` (`review_status`, `created_at`),
    KEY `idx_review_score` (`review_status`, `interest_score`, `created_at`),
    KEY `idx_review_auto_score` (`review_status`, `auto_score`, `created_at`),

    -- Category filtering
    KEY `idx_category` (`category`),

    -- Date-based queries
    KEY `idx_analyzed_at` (`analyzed_at`),
    KEY `idx_reviewed_at` (`reviewed_at`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Collected and analyzed URLs';


-- ==============================================
-- Rate Limiting Table (Optional - for DB-based rate limiting)
-- ==============================================
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_hash` CHAR(32) NOT NULL COMMENT 'MD5 hash of IP address',
    `endpoint` VARCHAR(50) NOT NULL DEFAULT 'ingest',
    `requests` INT UNSIGNED NOT NULL DEFAULT 1,
    `window_start` DATETIME NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ip_endpoint_window` (`ip_hash`, `endpoint`, `window_start`),
    KEY `idx_window_start` (`window_start`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate limiting tracking';


-- ==============================================
-- Activity Log Table (Optional)
-- ==============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, update, delete, etc.',
    `entity_type` VARCHAR(50) NULL COMMENT 'link, settings, etc.',
    `entity_id` BIGINT UNSIGNED NULL,
    `details` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created_at` (`created_at`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Activity and audit log';


-- ==============================================
-- Cleanup Events (Optional - for automatic maintenance)
-- ==============================================

-- Clean up old rate limit records (run daily)
-- DELIMITER //
-- CREATE EVENT IF NOT EXISTS `cleanup_rate_limits`
-- ON SCHEDULE EVERY 1 DAY
-- DO
-- BEGIN
--     DELETE FROM `rate_limits`
--     WHERE `window_start` < DATE_SUB(NOW(), INTERVAL 1 HOUR);
-- END //
-- DELIMITER ;

-- Clean up old activity logs (keep 90 days)
-- DELIMITER //
-- CREATE EVENT IF NOT EXISTS `cleanup_activity_log`
-- ON SCHEDULE EVERY 1 DAY
-- DO
-- BEGIN
--     DELETE FROM `activity_log`
--     WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);
-- END //
-- DELIMITER ;


-- ==============================================
-- Sample Data (for testing - remove in production)
-- ==============================================

-- INSERT INTO `links` (`url`, `url_hash`, `site`, `title`, `status`, `review_status`, `source`, `created_at`)
-- VALUES
-- ('https://example.com/article1', SHA2('https://example.com/article1', 256), 'example.com', 'Sample Article 1', 'pending', 'inbox', 'test', NOW()),
-- ('https://github.com/example/repo', SHA2('https://github.com/example/repo', 256), 'github.com', 'Example Repository', 'pending', 'inbox', 'test', NOW());


-- ==============================================
-- Useful Queries (for reference)
-- ==============================================

-- Count by status
-- SELECT status, COUNT(*) as count FROM links GROUP BY status;

-- Count by review_status
-- SELECT review_status, COUNT(*) as count FROM links GROUP BY review_status;

-- Count by category
-- SELECT category, COUNT(*) as count FROM links WHERE status = 'done' GROUP BY category ORDER BY count DESC;

-- Top scoring items
-- SELECT id, title, interest_score, auto_score
-- FROM links
-- WHERE status = 'done'
-- ORDER BY COALESCE(interest_score, auto_score, 0) DESC
-- LIMIT 10;

-- Items needing review
-- SELECT id, title, site, auto_score, created_at
-- FROM links
-- WHERE status = 'done' AND review_status = 'inbox'
-- ORDER BY auto_score DESC, created_at DESC;

-- Failed items for retry
-- SELECT id, url, error_message, retry_count
-- FROM links
-- WHERE status = 'error' AND retry_count < 3;
