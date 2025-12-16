-- ============================================
-- Design Confirm System - Database Schema
-- Dental Case Management System
-- ============================================

-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS `case_products`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `email_templates`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `system_settings`;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `slack_member_id` VARCHAR(50) NULL,
    `role` ENUM('Admin', 'User') NOT NULL DEFAULT 'User',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Clients Table
-- ============================================
CREATE TABLE `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `type` ENUM('Clinical', 'Lab') NOT NULL DEFAULT 'Clinical',
    `location` VARCHAR(10) NOT NULL DEFAULT 'NYC',
    `notification_pref` ENUM('Email_Only', 'Text_Only', 'Both', 'None') NOT NULL DEFAULT 'Email_Only',
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_clients_email` (`email`),
    INDEX `idx_clients_type` (`type`),
    INDEX `idx_clients_location` (`location`),
    INDEX `idx_clients_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Products Table
-- ============================================
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `google_sheet_id` VARCHAR(100) NULL,
    `case_col` VARCHAR(5) NOT NULL DEFAULT 'A',
    `patient_col` VARCHAR(5) NOT NULL DEFAULT 'H',
    `pan_col` VARCHAR(5) NOT NULL DEFAULT 'I',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_products_name` (`name`),
    INDEX `idx_products_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Cases Table
-- ============================================
CREATE TABLE `cases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `case_number` VARCHAR(50) NOT NULL UNIQUE,
    `pan_number` VARCHAR(50) NULL,
    `patient_name` VARCHAR(100) NOT NULL,
    `status` ENUM('Pending', 'Confirmed', 'Action Needed', 'Confirmed with Action Needed', 'Resolved') NOT NULL DEFAULT 'Pending',
    `client_id` INT NOT NULL,
    `created_by_user_id` INT NULL,
    `assigned_to_user_id` INT NULL,
    `priority` ENUM('Low', 'Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    `notes` TEXT NULL,
    `design_link` VARCHAR(500) NULL,
    `last_email_sent_at` DATETIME NULL,
    `last_email_received_at` DATETIME NULL,
    `due_date` DATE NULL,
    `resolved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_cases_case_number` (`case_number`),
    INDEX `idx_cases_pan_number` (`pan_number`),
    INDEX `idx_cases_patient_name` (`patient_name`),
    INDEX `idx_cases_status` (`status`),
    INDEX `idx_cases_client_id` (`client_id`),
    INDEX `idx_cases_priority` (`priority`),
    INDEX `idx_cases_due_date` (`due_date`),
    INDEX `idx_cases_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Case Products Junction Table
-- ============================================
CREATE TABLE `case_products` (
    `case_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`case_id`, `product_id`),
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_case_products_case_id` (`case_id`),
    INDEX `idx_case_products_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Email Templates Table
-- ============================================
CREATE TABLE `email_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `variables` JSON NULL COMMENT 'Available template variables',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_templates_name` (`name`),
    INDEX `idx_templates_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Email Logs Table
-- ============================================
CREATE TABLE `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT NULL,
    `client_id` INT NULL,
    `direction` ENUM('Inbound', 'Outbound') NOT NULL,
    `from_email` VARCHAR(255) NOT NULL,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(500) NULL,
    `body` TEXT NULL,
    `ai_analysis` JSON NULL COMMENT 'AI analysis result',
    `status` ENUM('Sent', 'Failed', 'Received', 'Processed') NOT NULL DEFAULT 'Sent',
    `error_message` TEXT NULL,
    `message_id` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_email_logs_case_id` (`case_id`),
    INDEX `idx_email_logs_client_id` (`client_id`),
    INDEX `idx_email_logs_direction` (`direction`),
    INDEX `idx_email_logs_status` (`status`),
    INDEX `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- System Settings Table
-- ============================================
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Initial Data
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');

-- Default Email Templates
INSERT INTO `email_templates` (`name`, `subject`, `body`, `variables`) VALUES
('design_confirmation_request', 'Design Confirmation Request - Case #{case_number}',
'Dear {client_name},\n\nWe have completed the design for Case #{case_number}.\n\nPatient: {patient_name}\nPAN: {pan_number}\n\nPlease review the design at the following link:\n{design_link}\n\nPlease reply to confirm the design or let us know if any modifications are needed.\n\nBest regards,\nDesign Team',
'["case_number", "client_name", "patient_name", "pan_number", "design_link"]'),

('status_confirmed', 'Design Confirmed - Case #{case_number}',
'Dear {client_name},\n\nThank you for confirming the design for Case #{case_number}.\n\nPatient: {patient_name}\n\nWe will proceed with production.\n\nBest regards,\nDesign Team',
'["case_number", "client_name", "patient_name"]'),

('status_action_needed', 'Action Required - Case #{case_number}',
'Dear Team,\n\nAction is required for Case #{case_number}.\n\nPatient: {patient_name}\nClient: {client_name}\n\nClient feedback:\n{feedback}\n\nPlease review and make necessary modifications.\n\nBest regards,\nSystem',
'["case_number", "patient_name", "client_name", "feedback"]');

-- Default Products
INSERT INTO `products` (`name`, `description`, `google_sheet_id`, `case_col`, `patient_col`, `pan_col`) VALUES
('Crown', 'Dental Crown', NULL, 'A', 'H', 'I'),
('Bridge', 'Dental Bridge', NULL, 'A', 'H', 'I'),
('Implant', 'Dental Implant', NULL, 'A', 'H', 'I'),
('Veneer', 'Dental Veneer', NULL, 'A', 'H', 'I'),
('Denture', 'Full or Partial Denture', NULL, 'A', 'H', 'I');

-- Default System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('email_monitor_enabled', 'true', 'boolean', 'Enable automatic email monitoring'),
('email_monitor_interval', '60', 'integer', 'Email monitoring interval in seconds'),
('ai_analysis_enabled', 'true', 'boolean', 'Enable AI-powered email analysis'),
('slack_notifications_enabled', 'true', 'boolean', 'Enable Slack notifications'),
('sms_notifications_enabled', 'true', 'boolean', 'Enable SMS notifications'),
('default_location', 'NYC', 'string', 'Default location for new clients');
