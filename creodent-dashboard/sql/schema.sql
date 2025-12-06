-- Creodent Dashboard - Database Schema
-- Run this on both creodent_nyc and creodent_hv databases
-- Central tables (users, customer_groups, customer_group_members) only on NYC

-- =============================================================================
-- INVOICES TABLE (Both branches)
-- =============================================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATETIME NOT NULL,
    customer_name VARCHAR(255),
    product_description VARCHAR(500),
    product_number VARCHAR(50),
    net_price DECIMAL(15,2) DEFAULT 0.00,
    units INT DEFAULT 0,
    remake_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,

    -- Additional fields from JSON data
    case_number VARCHAR(50),
    order_date DATETIME,
    sales_rep VARCHAR(100),
    service_center VARCHAR(100),
    case_status VARCHAR(10) DEFAULT 'C',
    invoice_type VARCHAR(10) DEFAULT 'I',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_date (invoice_date),
    INDEX idx_customer (customer_name),
    INDEX idx_product (product_description(100)),
    INDEX idx_product_number (product_number),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- USERS TABLE (NYC only - Central)
-- =============================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'viewer') DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CUSTOMER GROUPS TABLE (NYC only - Central)
-- =============================================================================
CREATE TABLE IF NOT EXISTS customer_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CUSTOMER GROUP MEMBERS TABLE (NYC only - Central)
-- =============================================================================
CREATE TABLE IF NOT EXISTS customer_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    branch ENUM('NYC', 'HV', 'ALL') DEFAULT 'ALL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (group_id) REFERENCES customer_groups(id) ON DELETE CASCADE,
    UNIQUE KEY uk_customer_branch (customer_name, branch),
    INDEX idx_group_id (group_id),
    INDEX idx_customer_name (customer_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DEFAULT ADMIN USER
-- Password: creodent2025 (change this after first login!)
-- =============================================================================
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE username = username;
