-- Magic Rx Scanner - Database Schema
-- MySQL/MariaDB Database Schema for Hostinger

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS magic_rx_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE magic_rx_scanner;

-- Table 1: prescriptions (Main prescription records)
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'Future user authentication',
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    template_filename VARCHAR(255) NULL,
    file_path VARCHAR(500) NOT NULL,
    template_path VARCHAR(500) NULL,
    analysis_mode ENUM('document_ai', 'handwriting_extraction') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: extracted_data (OCR extracted data)
CREATE TABLE IF NOT EXISTS extracted_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    data_type ENUM('full_text', 'field', 'handwriting_text', 'handwriting_image') NOT NULL,
    field_name VARCHAR(255) NULL COMMENT 'Field name (for field type only)',
    field_value TEXT NULL COMMENT 'Field value or text content',
    image_base64 LONGTEXT NULL COMMENT 'Base64 encoded image (for handwriting_image)',
    confidence_score DECIMAL(5,2) NULL COMMENT 'Confidence score (0-100)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_data_type (data_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: processing_logs (Processing logs for debugging)
CREATE TABLE IF NOT EXISTS processing_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    log_level ENUM('info', 'warning', 'error') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_log_level (log_level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: api_keys (Optional: Store encrypted API keys)
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., google_cloud',
    encrypted_key TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
