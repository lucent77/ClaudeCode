-- Slack List Hostinger Integration Database Schema
-- Create database (update database name as needed)

CREATE DATABASE IF NOT EXISTS slack_list_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE slack_list_manager;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'worker') DEFAULT 'worker',
    full_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks table (synced from Slack List)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(200),
    arch VARCHAR(50),
    pre_op_scan_date DATE,
    pre_op_scans TEXT,
    surgery_date DATE,
    surgery_time VARCHAR(100),
    due_date DATE,
    post_op_scans TEXT,
    existing_implants ENUM('Yes', 'No') DEFAULT 'No',
    notes TEXT,
    assignee_slack_id VARCHAR(50),
    assignee_name VARCHAR(100),
    completed TINYINT(1) DEFAULT 0,
    photos TEXT,
    stls TEXT,
    pre_op_cbct TEXT,
    post_op_cbct TEXT,
    ready_for_surgery TINYINT(1) DEFAULT 0,
    slack_created_time TIMESTAMP NULL,
    slack_created_by VARCHAR(50),
    slack_last_edited_by VARCHAR(50),
    slack_last_edited_time TIMESTAMP NULL,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_name (patient_name),
    INDEX idx_surgery_date (surgery_date),
    INDEX idx_due_date (due_date),
    INDEX idx_completed (completed),
    INDEX idx_assignee (assignee_slack_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files table for managing Slack file references
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT,
    file_slack_id VARCHAR(50) UNIQUE NOT NULL,
    file_type ENUM('photo', 'stl', 'cbct', 'scan', 'other') DEFAULT 'other',
    file_name VARCHAR(255),
    file_url TEXT,
    file_size BIGINT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_file_type (file_type),
    INDEX idx_file_slack_id (file_slack_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync log table to track synchronization history
CREATE TABLE IF NOT EXISTS sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed', 'partial') DEFAULT 'success',
    tasks_synced INT DEFAULT 0,
    files_synced INT DEFAULT 0,
    error_message TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log for audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: admin123 (Please change after first login!)
INSERT INTO users (username, email, password_hash, role, full_name) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator'),
('worker', 'worker@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 'Worker User');

-- Note: Default password for both users is 'password' - CHANGE IMMEDIATELY IN PRODUCTION!
