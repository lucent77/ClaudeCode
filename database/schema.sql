-- ============================================
-- Creodent AoX Elevate Dashboard
-- MySQL Database Schema
-- Slack Unified Files Based Case Management
-- ============================================

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS sync_logs;
DROP TABLE IF EXISTS case_activity_logs;
DROP TABLE IF EXISTS case_attachments;
DROP TABLE IF EXISTS cases;

-- ============================================
-- 1. Cases Table
-- ============================================
CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_code VARCHAR(255) UNIQUE NOT NULL COMMENT 'Unique case identifier (e.g., Nicolas_S_2025_11_18)',
    patient_name VARCHAR(255) NOT NULL,
    surgery_date DATE NULL,
    arch ENUM('Upper', 'Lower', 'Both') NULL,
    surgeon VARCHAR(255) NULL,
    clinic VARCHAR(255) NULL,
    status ENUM('open', 'in_progress', 'completed') DEFAULT 'open',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_patient_name (patient_name),
    INDEX idx_surgery_date (surgery_date),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Case Attachments Table (Slack Files)
-- ============================================
CREATE TABLE case_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    slack_file_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Slack file ID',

    file_name VARCHAR(500) NOT NULL,
    file_type ENUM(
        'photo',
        'stl',
        'preop_scan',
        'postop_scan',
        'preop_cbct',
        'postop_cbct',
        'design',
        'radiograph',
        'other'
    ) DEFAULT 'other',

    file_url TEXT NOT NULL COMMENT 'Slack url_private',
    preview_url TEXT NULL COMMENT 'Slack thumbnail URL',
    permalink TEXT NULL COMMENT 'Slack permalink',
    local_path TEXT NULL COMMENT 'Optional local storage path',

    mimetype VARCHAR(100) NULL,
    size BIGINT NULL COMMENT 'File size in bytes',

    uploaded_by VARCHAR(255) NULL COMMENT 'Slack user who uploaded',
    uploaded_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_file_type (file_type),
    INDEX idx_slack_file_id (slack_file_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Case Activity Logs Table
-- ============================================
CREATE TABLE case_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    action VARCHAR(255) NOT NULL COMMENT 'Action type (created, updated, file_added, etc.)',
    message TEXT NOT NULL,
    user VARCHAR(255) NULL,
    metadata JSON NULL COMMENT 'Additional context data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_case_id (case_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Sync Logs Table (Slack Synchronization)
-- ============================================
CREATE TABLE sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_files INT DEFAULT 0,
    matched_files INT DEFAULT 0,
    unmatched_files INT DEFAULT 0,
    new_cases INT DEFAULT 0,
    new_files INT DEFAULT 0,
    status ENUM('success', 'partial', 'error') DEFAULT 'success',
    detail TEXT NULL,
    error_message TEXT NULL,
    execution_time FLOAT NULL COMMENT 'Execution time in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Sample Data (Optional)
-- ============================================

-- Sample Case 1
INSERT INTO cases (case_code, patient_name, surgery_date, arch, surgeon, clinic, status, notes)
VALUES (
    'Nicolas_S_2025_11_18',
    'Nicolas S.',
    '2025-11-18',
    'Upper',
    'Dr. Smith',
    'Advanced Dental Clinic',
    'in_progress',
    'Full arch restoration with implant planning'
);

-- Sample Case 2
INSERT INTO cases (case_code, patient_name, surgery_date, arch, surgeon, status)
VALUES (
    'Tom_Bischof_2025_10_25',
    'Tom Bischof',
    '2025-10-25',
    'Both',
    'Dr. Johnson',
    'completed'
);

-- Sample Case 3
INSERT INTO cases (case_code, patient_name, surgery_date, arch, status)
VALUES (
    'Margaret_Colarusso_2025_12_05',
    'Margaret Colarusso',
    '2025-12-05',
    'Lower',
    'open'
);

-- ============================================
-- Database Views (Optional - for reporting)
-- ============================================

-- View: Case Summary with File Counts
CREATE OR REPLACE VIEW v_case_summary AS
SELECT
    c.id,
    c.case_code,
    c.patient_name,
    c.surgery_date,
    c.arch,
    c.surgeon,
    c.clinic,
    c.status,
    c.created_at,
    c.updated_at,
    COUNT(DISTINCT ca.id) as total_files,
    SUM(CASE WHEN ca.file_type = 'photo' THEN 1 ELSE 0 END) as photo_count,
    SUM(CASE WHEN ca.file_type = 'stl' THEN 1 ELSE 0 END) as stl_count,
    SUM(CASE WHEN ca.file_type LIKE '%cbct%' THEN 1 ELSE 0 END) as cbct_count,
    SUM(CASE WHEN ca.file_type LIKE '%scan%' THEN 1 ELSE 0 END) as scan_count,
    SUM(CASE WHEN ca.file_type = 'design' THEN 1 ELSE 0 END) as design_count
FROM cases c
LEFT JOIN case_attachments ca ON c.id = ca.case_id
GROUP BY c.id, c.case_code, c.patient_name, c.surgery_date, c.arch, c.surgeon, c.clinic, c.status, c.created_at, c.updated_at;

-- ============================================
-- End of Schema
-- ============================================
