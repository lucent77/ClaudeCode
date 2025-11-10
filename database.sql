-- Swissturn Production and Tool Management System Database Schema

-- Drop tables if exist
DROP TABLE IF EXISTS production_records;
DROP TABLE IF EXISTS tool_usage_history;
DROP TABLE IF EXISTS equipment_tool_settings;
DROP TABLE IF EXISTS tool_changes;
DROP TABLE IF EXISTS tools;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS users;

-- Users table (관리자 및 작업자)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'operator') DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment table (CNC 장비)
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(20) UNIQUE NOT NULL,
    equipment_name VARCHAR(100) NOT NULL,
    status ENUM('running', 'idle', 'maintenance', 'error') DEFAULT 'idle',
    total_runtime_hours DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tools table (공구 정보)
CREATE TABLE tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_code VARCHAR(50) UNIQUE NOT NULL,
    tool_name VARCHAR(100) NOT NULL,
    category_name VARCHAR(50),
    tool_size VARCHAR(50),
    supplier_name VARCHAR(100),
    supplier_model_number VARCHAR(100),
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 0,
    lifespan_type ENUM('time', 'cycles', 'distance') DEFAULT 'time',
    lifespan_limit DECIMAL(10,2) DEFAULT 0,
    current_usage_hours DECIMAL(10,2) DEFAULT 0,
    status ENUM('new', 'in_use', 'used', 'maintenance', 'retired') DEFAULT 'new',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_changed_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment Tool Settings (장비별 공구 세팅)
CREATE TABLE equipment_tool_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    tool_id INT NOT NULL,
    slot_number INT,
    usage_ratio DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Percentage of machine time this tool is used',
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    INDEX idx_equipment_active (equipment_id, is_active),
    INDEX idx_tool_active (tool_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tool Changes (공구 교체 이력)
CREATE TABLE tool_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    old_tool_id INT,
    new_tool_id INT NOT NULL,
    change_reason ENUM('scheduled', 'worn', 'broken', 'upgrade', 'other') DEFAULT 'scheduled',
    old_tool_usage_hours DECIMAL(10,2),
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (old_tool_id) REFERENCES tools(id) ON DELETE SET NULL,
    FOREIGN KEY (new_tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_equipment_changes (equipment_id, changed_at),
    INDEX idx_tool_changes (new_tool_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Production Records (생산 기록)
CREATE TABLE production_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month INT,
    week INT,
    record_date DATE NOT NULL,
    model_name VARCHAR(100),
    sku VARCHAR(100),
    lot_no VARCHAR(100),
    part_length DECIMAL(10,2),
    worker_name VARCHAR(100),
    equipment_id INT,
    diameter VARCHAR(50),
    length VARCHAR(50),
    lot VARCHAR(50),
    unit INT,
    exped_count INT,
    exped_count_24h INT,
    plan_count INT,
    am_count INT,
    pm_count INT,
    completion_percentage DECIMAL(5,2),
    unit_total INT,
    cnc_run_time DECIMAL(10,2) COMMENT 'Hours',
    working_time DECIMAL(10,2) COMMENT 'Hours',
    setting_time DECIMAL(10,2) COMMENT 'Hours',
    cnc_total DECIMAL(10,2) COMMENT 'Hours',
    test_unit INT,
    note TEXT,
    day_achievement DECIMAL(5,2),
    h24_achievement DECIMAL(5,2),
    total_achievement DECIMAL(5,2),
    milling_days_remaining INT,
    setting_qty INT,
    tool_broken_fail_qty INT,
    dent_failed_qty INT,
    dimension_fail_qty INT,
    overnight_fail_qty INT,
    etc_fail_qty INT,
    description TEXT,
    inspected_by VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (record_date),
    INDEX idx_equipment_date (equipment_id, record_date),
    INDEX idx_lot_no (lot_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tool Usage History (공구 사용 이력)
CREATE TABLE tool_usage_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    equipment_id INT NOT NULL,
    production_record_id INT,
    usage_hours DECIMAL(10,2) NOT NULL,
    usage_date DATE NOT NULL,
    cumulative_hours DECIMAL(10,2),
    percentage_used DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (production_record_id) REFERENCES production_records(id) ON DELETE SET NULL,
    INDEX idx_tool_date (tool_id, usage_date),
    INDEX idx_equipment_date (equipment_id, usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default equipment (8 CNC machines)
INSERT INTO equipment (equipment_code, equipment_name, status, description) VALUES
('MP1', 'MP1 - CNC Machine', 'idle', 'Multi-purpose CNC machine 1'),
('MP2', 'MP2 - CNC Machine', 'idle', 'Multi-purpose CNC machine 2'),
('MP3', 'MP3 - CNC Machine', 'idle', 'Multi-purpose CNC machine 3'),
('HW1', 'HW1 - CNC Machine', 'idle', 'Hardware CNC machine 1'),
('HW2', 'HW2 - CNC Machine', 'idle', 'Hardware CNC machine 2'),
('HW3', 'HW3 - CNC Machine', 'idle', 'Hardware CNC machine 3'),
('HW4', 'HW4 - CNC Machine', 'idle', 'Hardware CNC machine 4'),
('HW5', 'HW5 - CNC Machine', 'idle', 'Hardware CNC machine 5');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert default operator user (password: operator123)
INSERT INTO users (username, password, full_name, role) VALUES
('operator', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEfGCJ16LpXc2FxPZfm', 'Production Operator', 'operator');
