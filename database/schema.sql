-- ============================================
-- CAD/CAM Workflow System Database Schema
-- Version: 1.0.0
-- Platform: MySQL 5.7+ / MariaDB 10.3+
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================
-- CORE TABLES
-- ============================================

-- Users table with role-based access
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `initials` VARCHAR(10) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'department_manager', 'operator') NOT NULL DEFAULT 'operator',
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT', 'ALL') DEFAULT NULL,
    `assigned_steps` JSON DEFAULT NULL COMMENT 'Array of step IDs operator can work on',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_department` (`department`),
    INDEX `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow steps configuration
CREATE TABLE IF NOT EXISTS `workflow_steps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT') NOT NULL,
    `step_code` VARCHAR(20) NOT NULL,
    `step_name` VARCHAR(100) NOT NULL,
    `step_order` INT NOT NULL,
    `is_final_step` TINYINT(1) NOT NULL DEFAULT 0,
    `requires_machine_input` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'CNC/OVENS require machine name',
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_dept_step` (`department`, `step_code`),
    INDEX `idx_steps_department` (`department`),
    INDEX `idx_steps_order` (`department`, `step_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note tags configuration
CREATE TABLE IF NOT EXISTS `note_tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tag_code` VARCHAR(20) NOT NULL UNIQUE,
    `tag_name` VARCHAR(50) NOT NULL,
    `tag_color` VARCHAR(7) DEFAULT '#6B7280' COMMENT 'Hex color code',
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT', 'ALL') DEFAULT 'ALL',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tags_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job types configuration
CREATE TABLE IF NOT EXISTS `job_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT') NOT NULL,
    `job_code` VARCHAR(50) NOT NULL,
    `job_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_dept_job` (`department`, `job_code`),
    INDEX `idx_jobs_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CASE MANAGEMENT TABLES
-- ============================================

-- Main cases table (shared across departments)
CREATE TABLE IF NOT EXISTS `cases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_number` VARCHAR(50) NOT NULL UNIQUE,
    `site` ENUM('NYC', 'HV') NOT NULL,
    `created_timestamp` DATETIME NOT NULL,
    `combo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cross-department collaboration flag',
    `due_date` DATE NOT NULL,
    `ld_type` ENUM('L', 'D') NOT NULL COMMENT 'Lab or Doctor case',
    `pan` VARCHAR(50) DEFAULT NULL,
    `lab_name` VARCHAR(255) NOT NULL COMMENT 'Customer name',
    `patient_name` VARCHAR(255) DEFAULT NULL,
    `tooth_numbers` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated teeth numbers',
    `tooth_count` INT DEFAULT 0,
    `instructions` TEXT DEFAULT NULL,
    `preferences` TEXT DEFAULT NULL,
    `design_confirm_required` TINYINT(1) NOT NULL DEFAULT 0,
    `design_confirm_status` ENUM('NOT_REQUIRED', 'PENDING', 'SENT', 'APPROVED', 'CHANGES_REQUESTED') DEFAULT 'NOT_REQUIRED',
    `design_confirm_sent_at` DATETIME DEFAULT NULL,
    `design_confirm_response_at` DATETIME DEFAULT NULL,

    -- Department assignment flags
    `has_cocr` TINYINT(1) NOT NULL DEFAULT 0,
    `has_solidex` TINYINT(1) NOT NULL DEFAULT 0,
    `has_3d_print` TINYINT(1) NOT NULL DEFAULT 0,

    -- Overall status
    `status` ENUM('ACTIVE', 'ON_HOLD', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',

    -- Google Drive integration
    `drive_folder_id` VARCHAR(255) DEFAULT NULL,
    `drive_folder_url` VARCHAR(512) DEFAULT NULL,

    -- Concurrency control
    `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Optimistic locking version',

    -- Timestamps
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,

    INDEX `idx_cases_case_number` (`case_number`),
    INDEX `idx_cases_site` (`site`),
    INDEX `idx_cases_due_date` (`due_date`),
    INDEX `idx_cases_status` (`status`),
    INDEX `idx_cases_lab` (`lab_name`),
    INDEX `idx_cases_created` (`created_timestamp`),
    INDEX `idx_cases_cocr` (`has_cocr`, `status`),
    INDEX `idx_cases_solidex` (`has_solidex`, `status`),
    INDEX `idx_cases_3dprint` (`has_3d_print`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COCR department data
CREATE TABLE IF NOT EXISTS `case_cocr` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `job_type` VARCHAR(50) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `cocr_type` VARCHAR(100) DEFAULT NULL,
    `disk_material` VARCHAR(100) DEFAULT NULL,
    `model_io_file` ENUM('MODEL', 'IO', 'FILE') DEFAULT NULL,
    `shade` VARCHAR(50) DEFAULT NULL,
    `milling_shade` VARCHAR(50) DEFAULT NULL,
    `facial_cutback` VARCHAR(50) DEFAULT NULL,
    `access_hole` VARCHAR(50) DEFAULT NULL,
    `contact_strength` VARCHAR(50) DEFAULT NULL,
    `occlusion_info` TEXT DEFAULT NULL,
    `implant_type` VARCHAR(100) DEFAULT NULL,

    -- Workflow tracking
    `current_step_id` INT UNSIGNED DEFAULT NULL,
    `current_step_code` VARCHAR(20) DEFAULT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `completed_by` INT UNSIGNED DEFAULT NULL,

    -- HOLD management
    `is_on_hold` TINYINT(1) NOT NULL DEFAULT 0,
    `hold_reason` TEXT DEFAULT NULL,
    `hold_at` DATETIME DEFAULT NULL,
    `hold_by` INT UNSIGNED DEFAULT NULL,

    -- Note tags (stored as JSON array of tag IDs)
    `note_tags` JSON DEFAULT NULL,

    -- Concurrency control
    `version` INT UNSIGNED NOT NULL DEFAULT 1,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_step_id`) REFERENCES `workflow_steps`(`id`),
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`hold_by`) REFERENCES `users`(`id`),
    INDEX `idx_cocr_case` (`case_id`),
    INDEX `idx_cocr_step` (`current_step_code`),
    INDEX `idx_cocr_hold` (`is_on_hold`),
    INDEX `idx_cocr_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOLIDEX department data (case level)
CREATE TABLE IF NOT EXISTS `case_solidex` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `job_type` VARCHAR(50) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `teeth_count` INT DEFAULT 0,
    `teeth_list` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated teeth numbers',
    `implant_system` VARCHAR(100) DEFAULT NULL,
    `lot_number` VARCHAR(100) DEFAULT NULL,

    -- Overall completion (calculated from teeth tasks)
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `teeth_completed_count` INT NOT NULL DEFAULT 0,

    -- HOLD management (case level)
    `is_on_hold` TINYINT(1) NOT NULL DEFAULT 0,
    `hold_reason` TEXT DEFAULT NULL,
    `hold_at` DATETIME DEFAULT NULL,
    `hold_by` INT UNSIGNED DEFAULT NULL,

    -- Note tags
    `note_tags` JSON DEFAULT NULL,

    -- Concurrency control
    `version` INT UNSIGNED NOT NULL DEFAULT 1,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`hold_by`) REFERENCES `users`(`id`),
    INDEX `idx_solidex_case` (`case_id`),
    INDEX `idx_solidex_hold` (`is_on_hold`),
    INDEX `idx_solidex_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOLIDEX per-tooth tracking (critical for workflow)
CREATE TABLE IF NOT EXISTS `solidex_teeth_tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_solidex_id` INT UNSIGNED NOT NULL,
    `case_id` INT UNSIGNED NOT NULL,
    `tooth_number` VARCHAR(10) NOT NULL,

    -- Workflow tracking
    `current_step_id` INT UNSIGNED DEFAULT NULL,
    `current_step_code` VARCHAR(20) DEFAULT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `completed_by` INT UNSIGNED DEFAULT NULL,

    -- HOLD management (tooth level)
    `is_on_hold` TINYINT(1) NOT NULL DEFAULT 0,
    `hold_reason` TEXT DEFAULT NULL,
    `hold_at` DATETIME DEFAULT NULL,
    `hold_by` INT UNSIGNED DEFAULT NULL,

    -- Concurrency control
    `version` INT UNSIGNED NOT NULL DEFAULT 1,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_solidex_id`) REFERENCES `case_solidex`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_step_id`) REFERENCES `workflow_steps`(`id`),
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`hold_by`) REFERENCES `users`(`id`),
    UNIQUE KEY `uk_solidex_tooth` (`case_solidex_id`, `tooth_number`),
    INDEX `idx_teeth_case` (`case_id`),
    INDEX `idx_teeth_step` (`current_step_code`),
    INDEX `idx_teeth_hold` (`is_on_hold`),
    INDEX `idx_teeth_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3D PRINT department data
CREATE TABLE IF NOT EXISTS `case_3d_print` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `note` TEXT DEFAULT NULL,
    `print_type` VARCHAR(100) DEFAULT NULL,
    `implant_info` TEXT DEFAULT NULL,

    -- Workflow tracking
    `current_step_id` INT UNSIGNED DEFAULT NULL,
    `current_step_code` VARCHAR(20) DEFAULT NULL,
    `nesting_job_name` VARCHAR(255) DEFAULT NULL COMMENT 'Print job name from NESTING step',
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `completed_by` INT UNSIGNED DEFAULT NULL,

    -- HOLD management
    `is_on_hold` TINYINT(1) NOT NULL DEFAULT 0,
    `hold_reason` TEXT DEFAULT NULL,
    `hold_at` DATETIME DEFAULT NULL,
    `hold_by` INT UNSIGNED DEFAULT NULL,

    -- Note tags
    `note_tags` JSON DEFAULT NULL,

    -- Concurrency control
    `version` INT UNSIGNED NOT NULL DEFAULT 1,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_step_id`) REFERENCES `workflow_steps`(`id`),
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`hold_by`) REFERENCES `users`(`id`),
    INDEX `idx_3dprint_case` (`case_id`),
    INDEX `idx_3dprint_step` (`current_step_code`),
    INDEX `idx_3dprint_hold` (`is_on_hold`),
    INDEX `idx_3dprint_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- WORKFLOW HISTORY & AUDIT TABLES
-- ============================================

-- Step transitions audit log
CREATE TABLE IF NOT EXISTS `step_transitions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT') NOT NULL,
    `entity_type` ENUM('CASE', 'TOOTH') NOT NULL DEFAULT 'CASE',
    `entity_id` INT UNSIGNED NOT NULL COMMENT 'case_cocr.id or solidex_teeth_tasks.id etc',
    `tooth_number` VARCHAR(10) DEFAULT NULL,
    `from_step_id` INT UNSIGNED DEFAULT NULL,
    `from_step_code` VARCHAR(20) DEFAULT NULL,
    `to_step_id` INT UNSIGNED NOT NULL,
    `to_step_code` VARCHAR(20) NOT NULL,
    `operator_id` INT UNSIGNED NOT NULL,
    `operator_initials` VARCHAR(10) NOT NULL,
    `machine_name` VARCHAR(100) DEFAULT NULL COMMENT 'For CNC/OVENS steps',
    `notes` TEXT DEFAULT NULL,
    `transition_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_step_id`) REFERENCES `workflow_steps`(`id`),
    FOREIGN KEY (`to_step_id`) REFERENCES `workflow_steps`(`id`),
    FOREIGN KEY (`operator_id`) REFERENCES `users`(`id`),
    INDEX `idx_transitions_case` (`case_id`),
    INDEX `idx_transitions_dept` (`department`),
    INDEX `idx_transitions_operator` (`operator_id`),
    INDEX `idx_transitions_date` (`transition_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HOLD history
CREATE TABLE IF NOT EXISTS `hold_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT') NOT NULL,
    `entity_type` ENUM('CASE', 'TOOTH') NOT NULL DEFAULT 'CASE',
    `entity_id` INT UNSIGNED NOT NULL,
    `tooth_number` VARCHAR(10) DEFAULT NULL,
    `action` ENUM('HOLD', 'RELEASE') NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `action_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_hold_case` (`case_id`),
    INDEX `idx_hold_dept` (`department`),
    INDEX `idx_hold_date` (`action_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- General audit log for all changes
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(50) NOT NULL,
    `record_id` INT UNSIGNED NOT NULL,
    `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_table` (`table_name`),
    INDEX `idx_audit_record` (`table_name`, `record_id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DESIGN CONFIRM EMAIL TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS `design_confirm_emails` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT') NOT NULL,
    `recipient_email` VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(500) NOT NULL,
    `body_html` TEXT DEFAULT NULL,
    `image_urls` JSON DEFAULT NULL COMMENT 'Array of design image URLs',
    `drive_links` JSON DEFAULT NULL COMMENT 'Array of Google Drive links',
    `gmail_message_id` VARCHAR(255) DEFAULT NULL,
    `gmail_thread_id` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('PENDING', 'SENT', 'FAILED', 'APPROVED', 'CHANGES_REQUESTED') NOT NULL DEFAULT 'PENDING',
    `error_message` TEXT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `sent_by` INT UNSIGNED DEFAULT NULL,
    `response_received_at` DATETIME DEFAULT NULL,
    `response_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`),
    INDEX `idx_email_case` (`case_id`),
    INDEX `idx_email_status` (`status`),
    INDEX `idx_email_sent` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GOOGLE DRIVE FILE TRACKING
-- ============================================

CREATE TABLE IF NOT EXISTS `case_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `department` ENUM('COCR', 'SOLIDEX', '3D_PRINT', 'FRONT_DESK') NOT NULL,
    `step_code` VARCHAR(20) DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) DEFAULT NULL,
    `file_size` BIGINT DEFAULT NULL,
    `drive_file_id` VARCHAR(255) NOT NULL,
    `drive_file_url` VARCHAR(512) NOT NULL,
    `drive_folder_id` VARCHAR(255) DEFAULT NULL,
    `thumbnail_url` VARCHAR(512) DEFAULT NULL,
    `uploaded_by` INT UNSIGNED DEFAULT NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`),
    INDEX `idx_files_case` (`case_id`),
    INDEX `idx_files_dept` (`department`),
    INDEX `idx_files_step` (`step_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSION & TOKEN MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_session_user` (`user_id`),
    INDEX `idx_session_token` (`session_token`),
    INDEX `idx_session_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Google OAuth tokens storage
CREATE TABLE IF NOT EXISTS `google_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL for system-level tokens',
    `token_type` ENUM('DRIVE', 'GMAIL') NOT NULL,
    `access_token` TEXT NOT NULL,
    `refresh_token` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `scope` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_google_user` (`user_id`),
    INDEX `idx_google_type` (`token_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM CONFIGURATION
-- ============================================

CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL UNIQUE,
    `config_value` TEXT DEFAULT NULL,
    `config_type` ENUM('STRING', 'INT', 'BOOL', 'JSON') NOT NULL DEFAULT 'STRING',
    `description` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA
-- ============================================

-- Default workflow steps for COCR
INSERT INTO `workflow_steps` (`department`, `step_code`, `step_name`, `step_order`, `is_final_step`, `requires_machine_input`, `description`) VALUES
('COCR', 'TRANS', 'Transfer', 1, 0, 0, 'Original file transfer from Front Desk'),
('COCR', 'DESIGN', 'Design', 2, 0, 0, 'Designer completes design and sends file'),
('COCR', 'CAM', 'CAM', 3, 0, 0, 'CAM operator completes CAM programming'),
('COCR', 'CNC', 'CNC Milling', 4, 0, 1, 'CNC machine operation - record machine used'),
('COCR', 'OVENS', 'Sintering', 5, 1, 1, 'Sinter oven operation - record oven used');

-- Default workflow steps for SOLIDEX
INSERT INTO `workflow_steps` (`department`, `step_code`, `step_name`, `step_order`, `is_final_step`, `requires_machine_input`, `description`) VALUES
('SOLIDEX', 'TRANSCAN', 'Transfer Scan', 1, 0, 0, 'Scan file upload/receive from Front Desk'),
('SOLIDEX', 'PRECAD', 'Pre-CAD', 2, 0, 0, 'Pre-edit before design'),
('SOLIDEX', 'CAD', 'CAD Design', 3, 0, 0, 'Design and file upload'),
('SOLIDEX', 'PRECAM', 'Pre-CAM', 4, 0, 0, 'CAM preparation - supports bulk operations'),
('SOLIDEX', 'CNC', 'CNC Milling', 5, 0, 1, 'CNC machine operation - record machine used'),
('SOLIDEX', 'QC', 'Quality Control', 6, 1, 0, 'Quality control step');

-- Default workflow steps for 3D PRINT
INSERT INTO `workflow_steps` (`department`, `step_code`, `step_name`, `step_order`, `is_final_step`, `requires_machine_input`, `description`) VALUES
('3D_PRINT', 'TRANSSCAN', 'Transfer Scan', 1, 0, 0, 'Send scan file'),
('3D_PRINT', 'PRECAD', 'Pre-CAD', 2, 0, 0, 'Edit scan file'),
('3D_PRINT', 'DESIGN', 'Model Design', 3, 0, 0, 'Model design creation'),
('3D_PRINT', 'NESTING', 'Nesting', 4, 1, 0, 'Print job nesting - record job name');

-- Default note tags
INSERT INTO `note_tags` (`tag_code`, `tag_name`, `tag_color`, `department`) VALUES
('RUSH', 'Rush', '#EF4444', 'ALL'),
('CR', 'Custom Request', '#8B5CF6', 'ALL'),
('REMAKE', 'Remake', '#F59E0B', 'ALL'),
('IMPLANT', 'Implant', '#3B82F6', 'ALL'),
('COMBO', 'Combo Case', '#10B981', 'ALL'),
('PRIORITY', 'Priority', '#EC4899', 'ALL'),
('SPECIAL', 'Special Instructions', '#6366F1', 'ALL'),
('3D_PRINT', '3D Print', '#14B8A6', '3D_PRINT'),
('ZIRCONIA', 'Zirconia', '#0EA5E9', 'COCR'),
('EMAX', 'E.max', '#84CC16', 'COCR');

-- Default system config
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('app_name', 'CAD/CAM Workflow System', 'STRING', 'Application display name'),
('app_version', '1.0.0', 'STRING', 'Application version'),
('session_timeout_minutes', '480', 'INT', 'Session timeout in minutes (8 hours default)'),
('google_drive_enabled', 'false', 'BOOL', 'Enable Google Drive integration'),
('gmail_enabled', 'false', 'BOOL', 'Enable Gmail integration'),
('default_due_date_days', '5', 'INT', 'Default due date days from case creation'),
('items_per_page', '25', 'INT', 'Default items per page in lists'),
('hold_warning_days', '3', 'INT', 'Days on hold before warning display');

-- Create default admin user (password: admin123 - MUST CHANGE IN PRODUCTION)
INSERT INTO `users` (`username`, `email`, `password_hash`, `initials`, `full_name`, `role`, `department`, `is_active`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM', 'System Administrator', 'super_admin', 'ALL', 1);

SET FOREIGN_KEY_CHECKS = 1;
