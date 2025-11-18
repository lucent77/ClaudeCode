-- Scanbody File Manager Database Schema
-- Create database (run this first)
CREATE DATABASE IF NOT EXISTS scanbody_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scanbody_manager;

-- Systems table (Implant systems)
CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(255) NOT NULL,
    size VARCHAR(100) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_system_name (system_name),
    INDEX idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manufacturers table (CAD software types)
CREATE TABLE IF NOT EXISTS manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT '3Shape, exocad, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_manufacturer (name, type),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scanbodies table (Links systems to manufacturers with ID codes and STL files)
CREATE TABLE IF NOT EXISTS scanbodies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    manufacturer_id INT NOT NULL,
    id_code VARCHAR(100) DEFAULT NULL,
    stl_file_path VARCHAR(255) DEFAULT NULL,
    original_filename VARCHAR(255) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scanbody (system_id, manufacturer_id),
    INDEX idx_system_manufacturer (system_id, manufacturer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default manufacturers from CSV
INSERT INTO manufacturers (name, type) VALUES
('ARGEN', '3Shape'),
('ARGEN', 'exocad'),
('ASTRA', '3Shape'),
('ASTRA', 'exocad'),
('ASTRA IO', '3Shape'),
('ASTRA IO', 'exocad')
ON DUPLICATE KEY UPDATE name=name;
