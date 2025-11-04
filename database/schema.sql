-- ============================================================================
-- CREODENT Integrated Work Management System
-- Database Schema - Complete
-- MySQL 8.0+
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- 1. USERS AND AUTHENTICATION
-- ============================================================================

-- Users table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `role` ENUM('super_admin', 'admin', 'manager', 'worker') NOT NULL DEFAULT 'worker',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `login_attempts` INT NOT NULL DEFAULT 0,
  `locked_until` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_department` (`department_id`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code` (`code`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for users.department_id
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department`
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- API tokens (for Windows App integration)
DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `scopes` JSON DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. CASES (Main Case Management)
-- ============================================================================

DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_case_no` VARCHAR(100) NOT NULL,
  `source` ENUM('evolution_web_portal', 'google_sheets', 'manual', 'api', 'windows_app') NOT NULL DEFAULT 'manual',

  -- Patient and Lab Information
  `patient_name` VARCHAR(255) DEFAULT NULL,
  `patient_number` VARCHAR(100) DEFAULT NULL,
  `lab_name` VARCHAR(255) DEFAULT NULL,
  `lab_number` VARCHAR(100) DEFAULT NULL,

  -- Windows App specific fields
  `pan` VARCHAR(100) DEFAULT NULL COMMENT 'PAN # from Windows App',
  `timestamp_str` VARCHAR(50) DEFAULT NULL COMMENT 'TIME STAMP from Windows App',
  `location` VARCHAR(100) DEFAULT NULL COMMENT 'HV, NYC, HVNYC',
  `combo` VARCHAR(50) DEFAULT NULL COMMENT 'COMBO or NO',
  `ld` VARCHAR(10) DEFAULT NULL COMMENT 'L/D: DF, NF, L, DT, LD, LF',
  `trans` VARCHAR(50) DEFAULT NULL COMMENT 'TRANS value',

  -- Dates
  `due_date` DATE DEFAULT NULL,
  `completed_date` DATE DEFAULT NULL,

  -- Status and Priority
  `status` ENUM('new', 'in_progress', 'done', 'on_hold', 'canceled', 'archived') NOT NULL DEFAULT 'new',
  `priority` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',

  -- Additional Information
  `notes` TEXT DEFAULT NULL,
  `instructions` TEXT DEFAULT NULL COMMENT 'INSTRUCTIONS field',
  `preferences` TEXT DEFAULT NULL COMMENT 'PREFERENCES field',
  `raw_payload` LONGTEXT DEFAULT NULL COMMENT 'Original XML/JSON data',

  -- Optimistic Locking
  `version` INT NOT NULL DEFAULT 1,

  -- Audit fields
  `created_by` INT UNSIGNED DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_external_case_no_source` (`external_case_no`, `source`),
  INDEX `idx_source` (`source`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_due_date` (`due_date`),
  INDEX `idx_location` (`location`),
  INDEX `idx_lab_name` (`lab_name`),
  INDEX `idx_patient_name` (`patient_name`),
  INDEX `idx_created_at` (`created_at`),
  FULLTEXT INDEX `ft_search` (`external_case_no`, `patient_name`, `lab_name`, `notes`),

  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CASE ITEMS (Work Items per Case)
-- ============================================================================

DROP TABLE IF EXISTS `case_items`;
CREATE TABLE `case_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `work_type` ENUM('SOLIDEX', '3DPRINT', 'PRINT3D', 'COCR', 'ZEST') NOT NULL,
  `status` ENUM('pending', 'assigned', 'working', 'done', 'remake', 'rejected') NOT NULL DEFAULT 'pending',

  -- Assignment
  `assigned_to_user_id` INT UNSIGNED DEFAULT NULL,
  `assigned_at` TIMESTAMP NULL DEFAULT NULL,

  -- Common fields
  `count` INT DEFAULT 1,
  `tooth_no` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated tooth numbers',
  `material` VARCHAR(100) DEFAULT NULL,
  `instruction` TEXT DEFAULT NULL,
  `preferences` TEXT DEFAULT NULL,

  -- 3D Print specific fields
  `print_type` VARCHAR(100) DEFAULT NULL,
  `implant_type` VARCHAR(255) DEFAULT NULL,
  `note_3dprint` TEXT DEFAULT NULL,
  `design_3dprint` VARCHAR(100) DEFAULT NULL,
  `nesting` VARCHAR(50) DEFAULT NULL,

  -- CoCr specific fields
  `type_cocr` VARCHAR(50) DEFAULT NULL COMMENT 'CR, CO, EMAX, etc',
  `disk_material` VARCHAR(100) DEFAULT NULL,
  `mc_io` VARCHAR(10) DEFAULT NULL COMMENT 'MC/IO/FL',
  `cad_emax` VARCHAR(50) DEFAULT NULL,
  `fc` VARCHAR(10) DEFAULT NULL COMMENT 'Facial Cutback',
  `ah` VARCHAR(10) DEFAULT NULL COMMENT 'Access Hole',
  `contact_cocr` VARCHAR(10) DEFAULT NULL,
  `occ` VARCHAR(10) DEFAULT NULL COMMENT 'Occlusion',
  `shade` VARCHAR(50) DEFAULT NULL,
  `milling_shade` VARCHAR(50) DEFAULT NULL,
  `design_cocr` VARCHAR(100) DEFAULT NULL,

  -- Design tracking
  `design_started_at` TIMESTAMP NULL DEFAULT NULL,
  `design_completed_at` TIMESTAMP NULL DEFAULT NULL,
  `design_notes` TEXT DEFAULT NULL,

  -- Optimistic Locking
  `version` INT NOT NULL DEFAULT 1,

  -- Audit
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_case_id` (`case_id`),
  INDEX `idx_work_type` (`work_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_assigned_to` (`assigned_to_user_id`),

  FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. DEPARTMENT-SPECIFIC TABLES (JSON Payload Storage)
-- ============================================================================

-- Solidex Orders
DROP TABLE IF EXISTS `solidex_orders`;
CREATE TABLE `solidex_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `case_item_id` INT UNSIGNED DEFAULT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Original Google Sheets JSON',
  `imported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`case_item_id`) REFERENCES `case_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3D Print Orders
DROP TABLE IF EXISTS `print3d_orders`;
CREATE TABLE `print3d_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `case_item_id` INT UNSIGNED DEFAULT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Original Windows App JSON',
  `imported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`case_item_id`) REFERENCES `case_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CoCr/ZEST Orders
DROP TABLE IF EXISTS `cocr_orders`;
CREATE TABLE `cocr_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `case_item_id` INT UNSIGNED DEFAULT NULL,
  `payload_json` JSON NOT NULL COMMENT 'Original Windows App JSON',
  `imported_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_case_id` (`case_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`case_item_id`) REFERENCES `case_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. AUDIT LOGGING
-- ============================================================================

DROP TABLE IF EXISTS `case_audit_logs`;
CREATE TABLE `case_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'created, updated, deleted, assigned, status_changed, etc',
  `field_name` VARCHAR(100) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `before_snapshot` JSON DEFAULT NULL COMMENT 'Complete record before change',
  `after_snapshot` JSON DEFAULT NULL COMMENT 'Complete record after change',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_case_id` (`case_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_audit_logs`;
CREATE TABLE `user_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, password_change, profile_update, etc',
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. IMPORT JOBS (Evolution Portal & External Imports)
-- ============================================================================

DROP TABLE IF EXISTS `import_jobs`;
CREATE TABLE `import_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` ENUM('evolution_web_portal', 'google_sheets', 'windows_app_3dprint', 'windows_app_cocr', 'windows_app_solidex') NOT NULL,
  `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `records_total` INT DEFAULT 0,
  `records_imported` INT DEFAULT 0,
  `records_failed` INT DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `log_file` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_source` (`source`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. SYSTEM SETTINGS
-- ============================================================================

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  `type` ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
  `description` VARCHAR(500) DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_key` (`key`),
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. INITIAL DATA
-- ============================================================================

-- Insert default departments
INSERT INTO `departments` (`id`, `name`, `code`, `description`, `status`) VALUES
(1, 'Solidex', 'SOLIDEX', 'Solidex implant systems and abutments', 'active'),
(2, '3D Print', '3DPRINT', '3D printing models and guides', 'active'),
(3, 'CoCr/ZEST', 'COCR', 'CoCr metal frameworks and ZEST implants', 'active'),
(4, 'Administration', 'ADMIN', 'Administrative and management staff', 'active');

-- Insert default admin user (password: Admin@123)
INSERT INTO `users` (`id`, `username`, `password_hash`, `name`, `email`, `department_id`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@creodent.com', 4, 'super_admin', 'active');

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('system_name', 'CREODENT Work Manager', 'string', 'Application name'),
('system_version', '2.0.0', 'string', 'Current system version'),
('import_enabled', 'true', 'boolean', 'Enable automatic imports from Evolution Portal'),
('import_interval', '3600', 'integer', 'Import interval in seconds'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode');

-- ============================================================================
-- COMMIT CHANGES
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
