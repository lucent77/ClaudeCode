-- Swissturn 생산 관리 시스템 데이터베이스 스키마
-- MySQL 5.7 이상

-- 데이터베이스 생성 (Hostinger에서는 관리자 패널에서 생성)
-- CREATE DATABASE IF NOT EXISTS swissturn_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE swissturn_db;

-- 사용자 테이블
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 장비 테이블
CREATE TABLE IF NOT EXISTS machines (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    status ENUM('running', 'idle', 'maintenance') NOT NULL DEFAULT 'idle',
    current_job VARCHAR(200),
    runtime DECIMAL(10, 2) DEFAULT 0.00,
    downtime DECIMAL(10, 2) DEFAULT 0.00,
    oee DECIMAL(5, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공구 테이블
CREATE TABLE IF NOT EXISTS tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    size VARCHAR(50),
    supplier VARCHAR(100),
    supplier_model VARCHAR(100),
    current_stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    lifespan_type ENUM('time', 'cycles') DEFAULT 'time',
    lifespan_limit DECIMAL(10, 2) DEFAULT 0.00,
    current_usage DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('available', 'in-use', 'used', 'maintenance') DEFAULT 'available',
    machine_id VARCHAR(20),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_machine (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 사용 완료 공구 이력 테이블
CREATE TABLE IF NOT EXISTS used_tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    final_usage DECIMAL(10, 2) DEFAULT 0.00,
    lifespan_limit DECIMAL(10, 2) DEFAULT 0.00,
    machine_id VARCHAR(20),
    replaced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_replaced_at (replaced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 생산 데이터 테이블
CREATE TABLE IF NOT EXISTS production_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month INT,
    week INT,
    date DATE,
    model_name VARCHAR(100),
    sku VARCHAR(100),
    lot_no VARCHAR(100),
    part_length DECIMAL(10, 2),
    worker VARCHAR(100),
    cnc VARCHAR(20),
    diameter VARCHAR(50),
    length VARCHAR(50),
    lot VARCHAR(50),
    unit INT DEFAULT 0,
    exped_count INT DEFAULT 0,
    exped_count_24h INT DEFAULT 0,
    plan INT DEFAULT 0,
    achievement_1day DECIMAL(5, 2),
    achievement_24h DECIMAL(5, 2),
    achievement_total DECIMAL(5, 2),
    tool_broken_fail_qty INT DEFAULT 0,
    dent_failed_qty INT DEFAULT 0,
    dimension_fail_qty INT DEFAULT 0,
    overnight_fail_qty INT DEFAULT 0,
    note TEXT,
    description TEXT,
    inspected_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cnc) REFERENCES machines(id) ON DELETE SET NULL,
    INDEX idx_date (date),
    INDEX idx_cnc (cnc),
    INDEX idx_model (model_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공구 사용 이력 테이블
CREATE TABLE IF NOT EXISTS tool_usage_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    machine_id VARCHAR(20) NOT NULL,
    usage_hours DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    INDEX idx_tool (tool_id),
    INDEX idx_machine (machine_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공구 교체 이력 테이블
CREATE TABLE IF NOT EXISTS tool_replacement_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_tool_id INT,
    new_tool_id INT,
    machine_id VARCHAR(20),
    reason VARCHAR(255),
    old_tool_usage DECIMAL(10, 2),
    replaced_by VARCHAR(50),
    replaced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE SET NULL,
    INDEX idx_old_tool (old_tool_id),
    INDEX idx_new_tool (new_tool_id),
    INDEX idx_replaced_at (replaced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 데이터 삽입
-- 8대 CNC 장비
INSERT INTO machines (id, name, status) VALUES
('MP1', 'MP1', 'idle'),
('MP2', 'MP2', 'idle'),
('MP3', 'MP3', 'idle'),
('HW1', 'HW1', 'idle'),
('HW2', 'HW2', 'idle'),
('HW3', 'HW3', 'idle'),
('HW4', 'HW4', 'idle'),
('HW5', 'HW5', 'idle')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 기본 관리자 계정 (비밀번호: admin123)
-- 실제 배포 시 반드시 변경하세요!
INSERT INTO users (username, password, name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자', 'admin'),
('operator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '작업자', 'operator')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 샘플 공구 데이터
INSERT INTO tools (code, name, category, size, supplier, supplier_model, current_stock, min_stock, lifespan_type, lifespan_limit, description) VALUES
('D-08', '.80 DRILL', 'TWIST DRILL', '0.8', 'N/A', 'N/A', 5, 5, 'time', 200, 'High-speed steel drill bit for general purpose drilling'),
('D-11', '1.1 DRILL', 'TWIST DRILL', '1.1', 'N/A', 'N/A', 5, 5, 'time', 200, 'Carbide end mill for precision milling operations'),
('D-115', '1.15 DRILL', 'TWIST DRILL', '1.15', 'OSG', '3300115', 3, 3, 'time', 200, 'Torque wrench for accurate bolt tightening'),
('D-117', '1.17 DRILL', 'TWIST DRILL', '1.17', 'SPHINX TOOL', 'ART56033-PD26033', 5, 5, 'time', 200, 'Angle grinder cutting disc'),
('D-118', '1.18 DRILL', 'TWIST DRILL', '1.18', 'N/A', '1.18 (N/A)', 4, 4, 'time', 200, 'Tungsten carbide insert for turning operations'),
('D-119', '1.19 DRILL', 'TWIST DRILL', '1.19', 'GUHRING', '3899-1190', 6, 6, 'time', 200, 'Safety glasses for eye protection'),
('D-12-1', '1.20 DRILL', 'TWIST DRILL', '1.2', 'SPHINX TOOL', 'ART56033-PD42040', 8, 8, 'time', 200, 'Complete drill bit set in various sizes'),
('D-12-2', '1.20 DRILL', 'TWIST DRILL', '1.2', 'GUHRING', '3899-1200', 7, 7, 'time', 200, 'Thread tap for M6x1.0 threads'),
('D-12-3', '1.20 DRILL', 'TWIST DRILL', '1.2MM WX-MS-GDS', 'OSG', '3300120', 2, 2, 'time', 200, 'Cutting fluid for machining operations')
ON DUPLICATE KEY UPDATE name=VALUES(name);
