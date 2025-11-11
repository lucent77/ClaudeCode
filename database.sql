-- ============================================================
-- INTEGRATED PRODUCTION MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================================
-- Version: 1.0
-- Date: 2025-01-11
-- Description: Complete database schema for manufacturing production management
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: production_management
--

-- ============================================================
-- Table: users
-- Description: User authentication and authorization
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','operator') DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: equipment
-- Description: CNC equipment/machines management
-- ============================================================

CREATE TABLE IF NOT EXISTS `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(20) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `status` enum('running','idle','maintenance','error') DEFAULT 'idle',
  `total_runtime_hours` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_code` (`equipment_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: tools
-- Description: Manufacturing tools and their specifications
-- ============================================================

CREATE TABLE IF NOT EXISTS `tools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_code` varchar(50) NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `category_name` varchar(50) DEFAULT NULL,
  `tool_size` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `supplier_model_number` varchar(100) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 0,
  `lifespan_type` enum('time','cycles','distance') DEFAULT 'time',
  `lifespan_limit` decimal(10,2) DEFAULT 0.00,
  `current_usage_hours` decimal(10,2) DEFAULT 0.00,
  `status` enum('new','in_use','used','maintenance','retired') DEFAULT 'new',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_changed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tool_code` (`tool_code`),
  KEY `idx_category` (`category_name`),
  KEY `idx_status` (`status`),
  KEY `idx_stock` (`current_stock`, `minimum_stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: equipment_tool_settings
-- Description: Equipment-to-tool relationship and configuration
-- ============================================================

CREATE TABLE IF NOT EXISTS `equipment_tool_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `slot_number` varchar(10) DEFAULT NULL,
  `usage_ratio` decimal(5,2) DEFAULT 100.00,
  `installed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `removed_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipment` (`equipment_id`),
  KEY `idx_tool` (`tool_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_equipment_active` (`equipment_id`, `is_active`),
  CONSTRAINT `fk_ets_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ets_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: production_records
-- Description: Daily production records and metrics
-- ============================================================

CREATE TABLE IF NOT EXISTS `production_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `month` int(11) DEFAULT NULL,
  `week` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `model_name` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `lot_no` varchar(100) DEFAULT NULL,
  `part_length` decimal(10,2) DEFAULT NULL,
  `worker_name` varchar(100) DEFAULT NULL,
  `diameter` varchar(50) DEFAULT NULL,
  `length` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `exped_count` int(11) DEFAULT NULL,
  `exped_count_24h` int(11) DEFAULT NULL,
  `plan_count` int(11) DEFAULT NULL,
  `unit_total` int(11) DEFAULT NULL,
  `M` int(11) DEFAULT NULL COMMENT 'Minutes for 1 unit production',
  `S` int(11) DEFAULT NULL COMMENT 'Seconds for 1 unit production',
  `cnc_run_time` decimal(10,2) DEFAULT NULL COMMENT 'CNC running time in hours',
  `working_time` decimal(10,2) DEFAULT NULL COMMENT 'Total machine working time in hours',
  `setting` text DEFAULT NULL,
  `cnc_total` text DEFAULT NULL,
  `test_unit` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `completion_percentage` decimal(5,2) DEFAULT NULL,
  `day_achievement` decimal(5,2) DEFAULT NULL,
  `h24_achievement` decimal(5,2) DEFAULT NULL,
  `total_achievement` decimal(5,2) DEFAULT NULL,
  `milling_days_remaining` int(11) DEFAULT NULL,
  `setting_qty` int(11) DEFAULT NULL,
  `tool_broken_fail_qty` int(11) DEFAULT NULL,
  `dent_failed_qty` int(11) DEFAULT NULL,
  `dimension_fail_qty` int(11) DEFAULT NULL,
  `overnight_fail_qty` int(11) DEFAULT NULL,
  `etc_fail_qty` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `inspected_by` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`record_date`),
  KEY `idx_equipment_date` (`equipment_id`, `record_date`),
  KEY `idx_lot_no` (`lot_no`),
  KEY `idx_month_week` (`month`, `week`),
  CONSTRAINT `fk_pr_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: tool_usage_history
-- Description: Historical tracking of tool usage
-- ============================================================

CREATE TABLE IF NOT EXISTS `tool_usage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `production_record_id` int(11) DEFAULT NULL,
  `usage_hours` decimal(10,2) NOT NULL,
  `usage_date` date NOT NULL,
  `cumulative_hours` decimal(10,2) DEFAULT NULL,
  `percentage_used` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tool_date` (`tool_id`, `usage_date`),
  KEY `idx_equipment_date` (`equipment_id`, `usage_date`),
  KEY `idx_production_record` (`production_record_id`),
  CONSTRAINT `fk_tuh_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tuh_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tuh_production` FOREIGN KEY (`production_record_id`) REFERENCES `production_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: tool_changes
-- Description: Tool replacement and maintenance history
-- ============================================================

CREATE TABLE IF NOT EXISTS `tool_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `old_tool_id` int(11) DEFAULT NULL,
  `new_tool_id` int(11) NOT NULL,
  `change_reason` enum('scheduled','worn','broken','upgrade','other') DEFAULT 'scheduled',
  `old_tool_usage_hours` decimal(10,2) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipment` (`equipment_id`),
  KEY `idx_old_tool` (`old_tool_id`),
  KEY `idx_new_tool` (`new_tool_id`),
  KEY `idx_changed_at` (`changed_at`),
  CONSTRAINT `fk_tc_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_old_tool` FOREIGN KEY (`old_tool_id`) REFERENCES `tools` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tc_new_tool` FOREIGN KEY (`new_tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INITIAL DATA
-- ============================================================

-- Insert default admin user
-- Username: admin
-- Password: admin123 (change this immediately in production!)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('operator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Production Operator', 'operator');

-- Insert default equipment (8 CNC machines)
INSERT INTO `equipment` (`equipment_code`, `equipment_name`, `status`, `description`) VALUES
('MP1', 'CNC Machine MP1', 'idle', 'Primary milling machine'),
('MP2', 'CNC Machine MP2', 'idle', 'Primary milling machine'),
('MP3', 'CNC Machine MP3', 'idle', 'Primary milling machine'),
('HW1', 'CNC Machine HW1', 'idle', 'High-precision workstation'),
('HW2', 'CNC Machine HW2', 'idle', 'High-precision workstation'),
('HW3', 'CNC Machine HW3', 'idle', 'High-precision workstation'),
('HW4', 'CNC Machine HW4', 'idle', 'High-precision workstation'),
('HW5', 'CNC Machine HW5', 'idle', 'High-precision workstation');

-- ============================================================
-- END OF SCHEMA
-- ============================================================
