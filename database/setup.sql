-- ============================================
-- Creodent AoX Elevate Dashboard Database Setup
-- ============================================
-- This SQL file initializes the database from scratch.
-- The database must be created before running this script.
--
-- Usage:
-- 1. Run in phpMyAdmin or MySQL client
-- 2. Or command line: mysql -u username -p database_name < setup.sql
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- Drop existing tables (WARNING: All data will be deleted)
-- ============================================
DROP TABLE IF EXISTS `sync_logs`;
DROP TABLE IF EXISTS `case_activity_logs`;
DROP TABLE IF EXISTS `case_steps`;
DROP TABLE IF EXISTS `case_attachments`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

-- ============================================
-- Create Tables
-- ============================================

-- Users Table
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
  `slack_user_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cases Table (21 Slack List fields included)
CREATE TABLE `cases` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slack_case_id` varchar(100) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `assignee_name` varchar(255) DEFAULT NULL,
  `assignee_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `preop_scan_date` date DEFAULT NULL,
  `surgery_date` date DEFAULT NULL,
  `surgery_time` varchar(255) DEFAULT NULL,
  `status` enum('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `slack_canvas_url` varchar(255) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `arch` varchar(50) DEFAULT NULL,
  `existing_implants` varchar(50) DEFAULT NULL,
  `last_edited_by` varchar(100) DEFAULT NULL,
  `last_edited_time` timestamp NULL DEFAULT NULL,
  `ready_for_surgery` tinyint(1) NOT NULL DEFAULT 0,
  `preop_scans` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `postop_scans` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `photos` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `stls` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `preop_cbct` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `postop_cbct` text DEFAULT NULL COMMENT 'Comma-separated Slack file IDs',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cases_slack_case_id_unique` (`slack_case_id`),
  KEY `cases_status_index` (`status`),
  KEY `cases_surgery_date_index` (`surgery_date`),
  KEY `cases_due_date_index` (`due_date`),
  KEY `cases_assignee_user_id_index` (`assignee_user_id`),
  KEY `cases_completed_index` (`completed`),
  KEY `cases_ready_for_surgery_index` (`ready_for_surgery`),
  KEY `cases_arch_index` (`arch`),
  KEY `cases_last_edited_time_index` (`last_edited_time`),
  CONSTRAINT `cases_assignee_user_id_foreign` FOREIGN KEY (`assignee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Attachments Table
CREATE TABLE `case_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('photo','stl','cbct','scan','other') NOT NULL DEFAULT 'other',
  `label` varchar(100) DEFAULT NULL,
  `slack_file_id` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL COMMENT 'Slack URL or external URL',
  `local_path` text DEFAULT NULL COMMENT 'Hostinger local storage path',
  `preview_url` text DEFAULT NULL COMMENT 'Image thumbnail URL',
  `filename` varchar(255) DEFAULT NULL,
  `mimetype` varchar(100) DEFAULT NULL,
  `filesize` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_attachments_case_id_index` (`case_id`),
  KEY `case_attachments_type_index` (`type`),
  KEY `case_attachments_slack_file_id_index` (`slack_file_id`),
  CONSTRAINT `case_attachments_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Steps Table
CREATE TABLE `case_steps` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','completed','skipped') NOT NULL DEFAULT 'pending',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_steps_case_id_index` (`case_id`),
  KEY `case_steps_case_id_step_order_index` (`case_id`,`step_order`),
  CONSTRAINT `case_steps_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `case_steps_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Activity Logs Table
CREATE TABLE `case_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `changes` json DEFAULT NULL COMMENT 'Before/after data for updates',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `case_activity_logs_case_id_index` (`case_id`),
  KEY `case_activity_logs_user_id_index` (`user_id`),
  KEY `case_activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `case_activity_logs_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync Logs Table
CREATE TABLE `sync_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sync_type` enum('manual','auto','webhook') NOT NULL DEFAULT 'manual',
  `status` enum('started','success','failed','partial') NOT NULL DEFAULT 'started',
  `cases_synced` int(11) NOT NULL DEFAULT 0,
  `attachments_synced` int(11) NOT NULL DEFAULT 0,
  `errors_count` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sync_details` json DEFAULT NULL,
  `triggered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_logs_status_index` (`status`),
  KEY `sync_logs_started_at_index` (`started_at`),
  CONSTRAINT `sync_logs_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrations Table
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Initial Data
-- ============================================

-- Create admin account
-- Default password: admin123
-- IMPORTANT: Change password after first login!
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
('Admin', 'admin@creodent.com', '$2y$12$abzlPrD6NC9xLW/vA3w24.mnijhg7fxkpT3So23yvOG2d1uyUhaOW', 'admin', 1, NOW(), NOW());

-- Migration records
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2024_01_01_000001_create_users_table', 1),
('2024_01_01_000002_create_cases_table', 1),
('2024_01_01_000003_create_case_attachments_table', 1),
('2024_01_01_000004_create_case_steps_table', 1),
('2024_01_01_000005_create_case_activity_logs_table', 1),
('2024_01_01_000006_create_sync_logs_table', 1);

-- ============================================
-- Completion Message
-- ============================================
SELECT 'Database setup completed successfully!' AS message;
SELECT 'Default admin account:' AS info;
SELECT 'Email: admin@creodent.com' AS email;
SELECT 'Password: admin123' AS password;
SELECT 'IMPORTANT: Please change the password after first login!' AS warning;
