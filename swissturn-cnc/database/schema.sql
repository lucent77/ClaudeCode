-- SwissTurn CNC Parser Database Schema
-- MySQL 5.7+ Compatible

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- MACHINES & PROFILES
-- ============================================

DROP TABLE IF EXISTS machine;
CREATE TABLE machine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(50) NOT NULL,
    controller_type VARCHAR(50) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_machine_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS machine_profile;
CREATE TABLE machine_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    axis_config JSON,
    spindle_config JSON,
    turret_config JSON,
    default_modal_state JSON,
    quirks JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE CASCADE,
    UNIQUE KEY uk_machine_profile (machine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- G-CODE & M-CODE KNOWLEDGE BASE
-- ============================================

DROP TABLE IF EXISTS modal_group;
CREATE TABLE modal_group (
    id INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    UNIQUE KEY uk_modal_group_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS g_code;
CREATE TABLE g_code (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT DEFAULT NULL,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    modal_group_id INT DEFAULT NULL,
    is_modal TINYINT(1) DEFAULT 1,
    parameters JSON,
    semantic_type VARCHAR(50),
    explanation_template TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE CASCADE,
    FOREIGN KEY (modal_group_id) REFERENCES modal_group(id),
    UNIQUE KEY uk_g_code (machine_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS m_code;
CREATE TABLE m_code (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT DEFAULT NULL,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parameters JSON,
    semantic_type VARCHAR(50),
    explanation_template TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE CASCADE,
    UNIQUE KEY uk_m_code (machine_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PROGRAMS & PARSED DATA
-- ============================================

DROP TABLE IF EXISTS program;
CREATE TABLE program (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    source_code LONGTEXT NOT NULL,
    source_filename VARCHAR(255),
    status ENUM('pending', 'parsing', 'parsed', 'error') DEFAULT 'pending',
    error_message TEXT,
    line_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS parsed_line;
CREATE TABLE parsed_line (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    line_number INT NOT NULL,
    block_number INT DEFAULT NULL,
    raw_text TEXT NOT NULL,
    normalized_text TEXT,
    tokens_json JSON,
    commands_json JSON,
    modal_state_before JSON,
    modal_state_after JSON,
    explanation TEXT,
    is_comment_only TINYINT(1) DEFAULT 0,
    has_errors TINYINT(1) DEFAULT 0,
    errors_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES program(id) ON DELETE CASCADE,
    UNIQUE KEY uk_program_line (program_id, line_number),
    INDEX idx_program_id (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PROCESS BLOCKS & TEMPLATES
-- ============================================

DROP TABLE IF EXISTS process_block;
CREATE TABLE process_block (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    process_type VARCHAR(50) NOT NULL,
    start_line INT NOT NULL,
    end_line INT NOT NULL,
    tool_context VARCHAR(20),
    sequence_order INT NOT NULL,
    metadata_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES program(id) ON DELETE CASCADE,
    INDEX idx_program_id (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS template;
CREATE TABLE template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_id INT DEFAULT NULL,
    process_type VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    source_code LONGTEXT NOT NULL,
    variables_json JSON,
    metadata_json JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS template_variable;
CREATE TABLE template_variable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100),
    default_value DECIMAL(15,6),
    value_type VARCHAR(30),
    unit VARCHAR(20),
    min_value DECIMAL(15,6),
    max_value DECIMAL(15,6),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES template(id) ON DELETE CASCADE,
    UNIQUE KEY uk_template_var (template_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DWG FILES & GEOMETRY BINDING
-- ============================================

DROP TABLE IF EXISTS dwg_file;
CREATE TABLE dwg_file (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    metadata_json JSON,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS geometry_binding;
CREATE TABLE geometry_binding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dwg_file_id INT NOT NULL,
    template_id INT NOT NULL,
    variable_id INT NOT NULL,
    geometry_ref VARCHAR(255),
    bound_value DECIMAL(15,6),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dwg_file_id) REFERENCES dwg_file(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES template(id) ON DELETE CASCADE,
    FOREIGN KEY (variable_id) REFERENCES template_variable(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BLOCK COMPOSER
-- ============================================

DROP TABLE IF EXISTS saved_block;
CREATE TABLE saved_block (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    process_type VARCHAR(50),
    machine_id INT DEFAULT NULL,
    template_id INT DEFAULT NULL,
    source_code LONGTEXT,
    is_enabled TINYINT(1) DEFAULT 1,
    sequence_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES template(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS composed_program;
CREATE TABLE composed_program (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    machine_id INT NOT NULL,
    blocks_json JSON,
    output_code LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machine(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
