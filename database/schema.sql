-- ============================================================================
-- CREODENT Integrated Work Management System
-- Database Schema
--
-- Features:
-- - Optimistic locking (version columns)
-- - Audit logging
-- - Multi-department support
-- - Concurrent user access
-- - Evolution Web Portal integration
-- ============================================================================

-- Drop tables if exists (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS case_audit_logs;
DROP TABLE IF EXISTS case_items;
DROP TABLE IF EXISTS cocr_orders;
DROP TABLE IF EXISTS print3d_orders;
DROP TABLE IF EXISTS solidex_orders;
DROP TABLE IF EXISTS cases;
DROP TABLE IF EXISTS import_jobs;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS error_logs;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Core Tables
-- ============================================================================

-- Departments Table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    department_id INT,
    role ENUM('super_admin', 'admin', 'manager', 'worker') DEFAULT 'worker',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_department (department_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cases Table (Main case data from Evolution Portal)
CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_case_no VARCHAR(100) NOT NULL UNIQUE,
    source ENUM('evolution_web_portal', 'google_sheets', 'manual', 'api') DEFAULT 'evolution_web_portal',
    patient_name VARCHAR(255),
    lab_name VARCHAR(255),
    lab_number VARCHAR(100),
    patient_number VARCHAR(100),
    due_date DATE,
    location VARCHAR(100) COMMENT 'HV, NYC, HVNYC, etc',
    status ENUM('new', 'in_progress', 'done', 'on_hold', 'canceled', 'archived') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    raw_payload LONGTEXT COMMENT 'Original XML/JSON from Evolution',
    notes TEXT,
    version INT DEFAULT 1 COMMENT 'For optimistic locking',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_external_case_no (external_case_no),
    INDEX idx_source (source),
    INDEX idx_lab_name (lab_name),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_version (version),
    FULLTEXT idx_fulltext (patient_name, lab_name, lab_number, patient_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Case Items Table (Individual work items per department/tooth)
CREATE TABLE case_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    department_id INT,
    work_type VARCHAR(100) COMMENT 'SOLIDEX, 3DPRINT, COCR, etc',
    tooth_no VARCHAR(50),
    part_no VARCHAR(50),
    count INT DEFAULT 1,
    material VARCHAR(100),
    instruction TEXT,
    preferences TEXT,
    design_notes TEXT,
    status ENUM('pending', 'assigned', 'working', 'done', 'remake', 'rejected') DEFAULT 'pending',
    assigned_to INT COMMENT 'User ID of assigned worker',
    assigned_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    version INT DEFAULT 1 COMMENT 'For optimistic locking',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_case_id (case_id),
    INDEX idx_department_id (department_id),
    INDEX idx_work_type (work_type),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Audit and Logging Tables
-- ============================================================================

-- Case Audit Logs Table
CREATE TABLE case_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    case_id INT,
    case_item_id INT,
    user_id INT,
    action VARCHAR(100) NOT NULL COMMENT 'create, update, assign, status_change, import_from_evo, etc',
    description TEXT,
    before_json TEXT COMMENT 'State before change',
    after_json TEXT COMMENT 'State after change',
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (case_item_id) REFERENCES case_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_case_id (case_id),
    INDEX idx_case_item_id (case_item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import Jobs Table
CREATE TABLE import_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(100) NOT NULL COMMENT 'evo_case_list, evo_case_info, gsheet_solidex, etc',
    status ENUM('running', 'success', 'error', 'partial') DEFAULT 'running',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    duration_seconds INT,
    records_processed INT DEFAULT 0,
    records_success INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    message TEXT,
    raw_request TEXT,
    raw_response LONGTEXT,
    error_details TEXT,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job_type (job_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error Logs Table
CREATE TABLE error_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) DEFAULT 'error' COMMENT 'debug, info, warning, error, critical',
    message TEXT NOT NULL,
    context TEXT COMMENT 'Additional context as JSON',
    file VARCHAR(500),
    line INT,
    trace TEXT,
    user_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Department-Specific Tables (JSON Payload Storage)
-- ============================================================================

-- Solidex Orders (preserves original Google Sheets structure)
CREATE TABLE solidex_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    payload_json LONGTEXT NOT NULL COMMENT 'Original Solidex_data.json row',
    synced_to_sheets BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_synced (synced_to_sheets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3D Print Orders
CREATE TABLE print3d_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    payload_json LONGTEXT NOT NULL COMMENT 'Original 3d_print_converted_data.json row',
    synced_to_sheets BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_synced (synced_to_sheets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CoCr Orders
CREATE TABLE cocr_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    payload_json LONGTEXT NOT NULL COMMENT 'Original cocr_converted_data.json row',
    synced_to_sheets BOOLEAN DEFAULT FALSE,
    last_sync_at TIMESTAMP NULL,
    version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_synced (synced_to_sheets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Session and Security Tables
-- ============================================================================

-- Sessions Table
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    payload TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Tokens Table
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    name VARCHAR(255) COMMENT 'Token name/description',
    scopes TEXT COMMENT 'JSON array of allowed scopes',
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Initial Data Seeding
-- ============================================================================

-- Insert default departments
INSERT INTO departments (code, name, description) VALUES
('SOLIDEX', 'Solidex Department', 'Solidex implant and prosthetic work'),
('3DPRINT', '3D Print Department', '3D printing and model creation'),
('COCR', 'CoCr/ZEST Department', 'CoCr metal framework and ZEST attachments'),
('KOREA', 'Korea Branch', 'Korea office operations'),
('QC', 'Quality Control', 'Quality assurance and inspection'),
('ADMIN', 'Administration', 'Administrative and management');

-- Insert default super admin (password: Admin@123 - CHANGE THIS!)
-- Password hash for 'Admin@123'
INSERT INTO users (username, password_hash, name, email, department_id, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@creodent.com',
    (SELECT id FROM departments WHERE code = 'ADMIN'), 'super_admin', 'active');

-- ============================================================================
-- Views for Reporting
-- ============================================================================

-- Active cases by department
CREATE OR REPLACE VIEW v_department_workload AS
SELECT
    d.code AS department_code,
    d.name AS department_name,
    COUNT(DISTINCT ci.case_id) AS active_cases,
    COUNT(ci.id) AS total_items,
    SUM(CASE WHEN ci.status = 'pending' THEN 1 ELSE 0 END) AS pending_items,
    SUM(CASE WHEN ci.status = 'assigned' THEN 1 ELSE 0 END) AS assigned_items,
    SUM(CASE WHEN ci.status = 'working' THEN 1 ELSE 0 END) AS working_items,
    SUM(CASE WHEN ci.status = 'done' THEN 1 ELSE 0 END) AS done_items
FROM departments d
LEFT JOIN case_items ci ON d.id = ci.department_id
LEFT JOIN cases c ON ci.case_id = c.id
WHERE c.status NOT IN ('archived', 'canceled') OR c.status IS NULL
GROUP BY d.id, d.code, d.name;

-- User productivity view
CREATE OR REPLACE VIEW v_user_productivity AS
SELECT
    u.id AS user_id,
    u.username,
    u.name,
    d.name AS department_name,
    COUNT(ci.id) AS assigned_items,
    SUM(CASE WHEN ci.status = 'done' THEN 1 ELSE 0 END) AS completed_items,
    SUM(CASE WHEN ci.status = 'working' THEN 1 ELSE 0 END) AS in_progress_items,
    SUM(ci.actual_hours) AS total_hours_worked
FROM users u
LEFT JOIN departments d ON u.department_id = d.id
LEFT JOIN case_items ci ON u.id = ci.assigned_to
WHERE u.status = 'active'
GROUP BY u.id, u.username, u.name, d.name;

-- ============================================================================
-- Stored Procedures
-- ============================================================================

DELIMITER //

-- Procedure to assign case item to user
CREATE PROCEDURE sp_assign_case_item(
    IN p_item_id INT,
    IN p_user_id INT,
    IN p_assigned_by INT
)
BEGIN
    DECLARE v_current_version INT;

    -- Get current version
    SELECT version INTO v_current_version FROM case_items WHERE id = p_item_id;

    -- Update with version check (optimistic locking)
    UPDATE case_items
    SET
        assigned_to = p_user_id,
        assigned_at = NOW(),
        status = 'assigned',
        updated_by = p_assigned_by,
        version = version + 1
    WHERE id = p_item_id AND version = v_current_version;

    -- Check if update was successful
    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Concurrent modification detected. Please refresh and try again.';
    END IF;

    -- Log the assignment
    INSERT INTO case_audit_logs (case_id, case_item_id, user_id, action, description)
    SELECT case_id, id, p_assigned_by, 'assign',
           CONCAT('Assigned to user ID: ', p_user_id)
    FROM case_items WHERE id = p_item_id;
END//

DELIMITER ;

-- ============================================================================
-- End of Schema
-- ============================================================================
