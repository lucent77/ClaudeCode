-- ====================================
-- Hostinger CRM Database Schema
-- ====================================

-- Drop existing tables (in correct order due to foreign keys)
DROP TABLE IF EXISTS sales_performance;
DROP TABLE IF EXISTS interactions;
DROP TABLE IF EXISTS meetings;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

-- ====================================
-- Users Table
-- ====================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'sales_rep') NOT NULL DEFAULT 'sales_rep',
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Customers Table (Based on CSV format)
-- ====================================
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_key VARCHAR(50) UNIQUE,
    account_number VARCHAR(50) UNIQUE,
    billto_account_number VARCHAR(50),
    account_type VARCHAR(10),
    account_class VARCHAR(10),
    customer_status VARCHAR(10),
    customer_status_description VARCHAR(100),
    customer_status_reason VARCHAR(255),
    title VARCHAR(20),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    middle_initial VARCHAR(10),
    practice_name VARCHAR(255),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    address_line3 VARCHAR(255),
    city VARCHAR(100),
    state_code VARCHAR(10),
    zip_code VARCHAR(20),
    website VARCHAR(255),
    phone_number VARCHAR(30),
    fax_number VARCHAR(30),
    email_address VARCHAR(150),
    cell_phone VARCHAR(30),
    route_key VARCHAR(50),
    route_order VARCHAR(50),
    route_visit_timeframe VARCHAR(100),
    billto_flag BOOLEAN DEFAULT FALSE,
    shipto_flag BOOLEAN DEFAULT FALSE,
    allow_case_entry BOOLEAN DEFAULT FALSE,
    always_visit BOOLEAN DEFAULT FALSE,
    date_created DATETIME,
    date_of_first_case DATETIME,
    date_of_last_case DATETIME,
    new_customer TINYINT(1) DEFAULT 0,
    credit_card_autopay_group VARCHAR(100),
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Active customer flag (1=Active, 0=Prospect)',
    active_since DATE COMMENT 'Date when customer became active',
    notes TEXT,
    created_in_system TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_key (customer_key),
    INDEX idx_account_number (account_number),
    INDEX idx_customer_status (customer_status),
    INDEX idx_email (email_address),
    INDEX idx_state (state_code),
    INDEX idx_is_active (is_active),
    INDEX idx_account_class (account_class),
    FULLTEXT idx_search (first_name, last_name, practice_name, email_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Assignments Table (Customer-to-Sales Rep mapping)
-- ====================================
CREATE TABLE assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by INT NOT NULL COMMENT 'User ID of admin who made assignment',
    assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_current TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id),
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_current (is_current),
    INDEX idx_assignment_date (assignment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Interactions Table (Contact logs)
-- ====================================
CREATE TABLE interactions (
    interaction_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    interaction_type ENUM('call', 'email', 'meeting', 'visit', 'note', 'other') NOT NULL,
    interaction_date DATETIME NOT NULL,
    subject VARCHAR(255),
    description TEXT,
    outcome VARCHAR(255) COMMENT 'Result of interaction',
    follow_up_required TINYINT(1) DEFAULT 0,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_date (interaction_date),
    INDEX idx_type (interaction_type),
    INDEX idx_follow_up (follow_up_required, follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Meetings Table (Scheduled appointments)
-- ====================================
CREATE TABLE meetings (
    meeting_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    meeting_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    location VARCHAR(255),
    meeting_type ENUM('call', 'visit', 'online', 'other') DEFAULT 'call',
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    reminder_sent TINYINT(1) DEFAULT 0,
    reminder_date DATETIME,
    notes TEXT COMMENT 'Post-meeting notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_meeting_date (meeting_date),
    INDEX idx_status (status),
    INDEX idx_reminder (reminder_sent, reminder_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Sales Performance Table (Monthly revenue tracking)
-- ====================================
CREATE TABLE sales_performance (
    performance_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_id INT,
    period_month INT NOT NULL COMMENT 'Month (1-12)',
    period_year INT NOT NULL COMMENT 'Year (e.g., 2024)',
    revenue DECIMAL(12, 2) DEFAULT 0.00,
    new_active_customers INT DEFAULT 0,
    total_interactions INT DEFAULT 0,
    total_meetings INT DEFAULT 0,
    notes TEXT,
    created_by INT COMMENT 'Admin who entered the data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    UNIQUE KEY unique_performance (user_id, customer_id, period_month, period_year),
    INDEX idx_user (user_id),
    INDEX idx_period (period_year, period_month),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- System Logs Table (Audit trail)
-- ====================================
CREATE TABLE system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Default Admin User (password: admin123)
-- Password hash is bcrypt hash of 'admin123'
-- ====================================
INSERT INTO users (username, password_hash, email, first_name, last_name, role, is_active)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@dentalcrm.com',
    'System',
    'Administrator',
    'admin',
    1
);

-- ====================================
-- Sample Data (Optional - for testing)
-- ====================================

-- Sample Sales Rep
INSERT INTO users (username, password_hash, email, first_name, last_name, role, is_active)
VALUES (
    'sales1',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'sales1@dentalcrm.com',
    'John',
    'Smith',
    'sales_rep',
    1
);

-- Sample Manager
INSERT INTO users (username, password_hash, email, first_name, last_name, role, is_active)
VALUES (
    'manager1',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'manager1@dentalcrm.com',
    'Sarah',
    'Johnson',
    'manager',
    1
);

-- ====================================
-- End of Schema
-- ====================================
