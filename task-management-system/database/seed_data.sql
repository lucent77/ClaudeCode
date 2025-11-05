-- Sample seed data for testing the task management system

-- Create additional test users
INSERT INTO users (name, email, password_hash, role_id, department_id) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 2), -- Solidex user
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 3), -- COCR user
('Mike Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 4), -- 3D Print user
('Sarah Williams', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1); -- Front Desk user

-- Sample COCR tasks
INSERT INTO tasks (external_id, department_id, created_by, assigned_to, date, type, tooth, implant_type, design, lab, status_id, notes) VALUES
('2025-71014', 3, 5, 3, '2025-11-04', 'CR', '12', 'ACT35 IO 2B-A SA', 'ST', 'E & R Dental Lab (H)', 1, 'Priority case'),
('2025-71015', 3, 5, 3, '2025-11-04', 'CR', '18,19', 'SB 4.3 ZB 2A SA', 'BL', 'Sunshine Dental Lab', 2, NULL),
('2025-71016', 3, 5, 3, '2025-11-05', 'BRIDGE', '14-16', 'N/A', 'PFM', 'Premier Lab', 1, NULL),
('2025-71017', 3, 5, 3, '2025-11-05', 'CROWN', '30', 'N/A', 'ZR', 'E & R Dental Lab (H)', 3, 'On hold - waiting for approval');

-- Sample Solidex tasks
INSERT INTO tasks (external_id, department_id, created_by, assigned_to, date, due_date, teeth, implant_system, design, lab, status_id, notes) VALUES
('2025-S1001', 2, 5, 2, '2025-11-03', '2025-11-10', '12,13', 'Straumann BL', 'TiBase', 'Perfect Smile Lab', 2, 'Rush order'),
('2025-S1002', 2, 5, 2, '2025-11-04', '2025-11-12', '18', 'Nobel Active', 'Screw-retained', 'Advanced Dental', 1, NULL),
('2025-S1003', 2, 5, 2, '2025-11-04', '2025-11-15', '30,31', 'Zimmer TSV', 'Cement-retained', 'Elite Lab Services', 1, NULL),
('2025-S1004', 2, 5, 2, '2025-11-05', '2025-11-11', '8', 'Straumann BL', 'Custom abutment', 'Perfect Smile Lab', 4, 'Completed and shipped');

-- Sample 3D Print tasks
INSERT INTO tasks (external_id, department_id, created_by, assigned_to, date, type, case_number, patient_number, tooth, design, lab_number, status_id, notes) VALUES
('2025-3D001', 4, 5, 4, '2025-11-03', 'MODEL', 'C-12345', 'P-98765', '12-22', 'Full arch', 'LAB-001', 2, 'Printing in progress'),
('2025-3D002', 4, 5, 4, '2025-11-04', 'SURGICAL GUIDE', 'C-12346', 'P-98766', '18,19', 'Implant guide', 'LAB-002', 1, NULL),
('2025-3D003', 4, 5, 4, '2025-11-04', 'MODEL', 'C-12347', 'P-98767', '30', 'Single crown', 'LAB-003', 1, NULL),
('2025-3D004', 4, 5, 4, '2025-11-05', 'TEMP', 'C-12348', 'P-98768', '12,13,14', 'Temporary bridge', 'LAB-004', 4, 'Completed');

-- Sample task history entries
INSERT INTO task_history (task_id, user_id, action, field_name, old_value, new_value) VALUES
(1, 3, 'status_change', 'status_id', '1', '1', NOW() - INTERVAL 1 HOUR),
(2, 3, 'status_change', 'status_id', '1', '2', NOW() - INTERVAL 2 HOUR),
(2, 3, 'update', 'notes', NULL, 'Started working on this case', NOW() - INTERVAL 2 HOUR),
(5, 2, 'status_change', 'status_id', '1', '2', NOW() - INTERVAL 3 HOUR),
(8, 2, 'status_change', 'status_id', '2', '4', NOW() - INTERVAL 1 DAY),
(9, 4, 'status_change', 'status_id', '1', '2', NOW() - INTERVAL 4 HOUR),
(12, 4, 'status_change', 'status_id', '2', '4', NOW() - INTERVAL 2 HOUR);
