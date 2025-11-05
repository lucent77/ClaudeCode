-- Task Management System Database Schema
-- MySQL 5.7+ / MariaDB 10.2+

-- Drop tables if they exist (for clean re-installation)
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS statuses;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statuses table
CREATE TABLE statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(20) DEFAULT 'gray',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    department_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_department (department_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(50) NULL UNIQUE,
    department_id INT NOT NULL,
    created_by INT NOT NULL,
    assigned_to INT NULL,

    -- Common fields
    date DATE NULL,
    due_date DATE NULL,
    type VARCHAR(50) NULL,
    status_id INT NOT NULL DEFAULT 1,
    priority VARCHAR(20) DEFAULT 'normal',

    -- COCR / Dental specific fields
    tooth VARCHAR(50) NULL,
    implant_type VARCHAR(100) NULL,
    implant_system VARCHAR(100) NULL,
    design VARCHAR(100) NULL,
    lab VARCHAR(255) NULL,
    lab_number VARCHAR(50) NULL,
    patient_number VARCHAR(50) NULL,
    case_number VARCHAR(50) NULL,

    -- Additional fields
    teeth VARCHAR(255) NULL,
    notes TEXT NULL,

    -- Attachments (JSON array of file paths)
    attachments JSON NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,

    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES statuses(id) ON DELETE RESTRICT,

    INDEX idx_external_id (external_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_date (date),
    INDEX idx_due_date (due_date),
    INDEX idx_dept_status (department_id, status_id),
    INDEX idx_dept_date (department_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task history/audit log table
CREATE TABLE task_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_name VARCHAR(50) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('admin', 'Administrator with full system access'),
('dept_user', 'Department user with limited access to their department'),
('front_desk', 'Front desk user with access to all tasks and intake functions');

-- Insert departments
INSERT INTO departments (name, code, description) VALUES
('Front Desk', 'FRONT_DESK', 'Intake and case management department'),
('Solidex', 'SOLIDEX', 'Solidex production department'),
('COCR', 'COCR', 'COCR production department'),
('3D Print', '3D_PRINT', '3D printing department');

-- Insert statuses
INSERT INTO statuses (name, color, sort_order) VALUES
('Pending', 'yellow', 1),
('In Progress', 'blue', 2),
('On Hold', 'orange', 3),
('Completed', 'green', 4),
('Cancelled', 'red', 5);

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123' using bcrypt cost 10
INSERT INTO users (name, email, password_hash, role_id, department_id) VALUES
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL);

-- Create view for task details (includes related data)
CREATE OR REPLACE VIEW task_details AS
SELECT
    t.id,
    t.external_id,
    t.date,
    t.due_date,
    t.type,
    t.tooth,
    t.implant_type,
    t.implant_system,
    t.design,
    t.lab,
    t.lab_number,
    t.patient_number,
    t.case_number,
    t.teeth,
    t.notes,
    t.priority,
    t.attachments,
    t.created_at,
    t.updated_at,
    t.completed_at,
    d.name AS department_name,
    d.code AS department_code,
    s.name AS status_name,
    s.color AS status_color,
    creator.name AS created_by_name,
    creator.email AS created_by_email,
    assignee.name AS assigned_to_name,
    assignee.email AS assigned_to_email
FROM tasks t
INNER JOIN departments d ON t.department_id = d.id
INNER JOIN statuses s ON t.status_id = s.id
INNER JOIN users creator ON t.created_by = creator.id
LEFT JOIN users assignee ON t.assigned_to = assignee.id;
