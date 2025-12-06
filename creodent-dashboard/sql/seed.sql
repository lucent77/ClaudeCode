-- Creodent Dashboard - Sample Data
-- This file contains sample data for testing purposes

-- Sample Customer Groups
INSERT INTO customer_groups (name, description) VALUES
('VIP Customers', 'High-value customers with over $50,000 annual sales'),
('Labs', 'Dental laboratory customers'),
('New Accounts', 'Customers acquired in the last 6 months'),
('At Risk', 'Customers showing declining sales trends');

-- Sample Group Members
INSERT INTO customer_group_members (group_id, customer_name, branch) VALUES
(1, 'J&J Dental Lab, LLC (H)', 'ALL'),
(1, 'Studio 32 Dental Lab (H)', 'ALL'),
(2, 'J&J Dental Lab, LLC (H)', 'HV'),
(2, 'Studio 32 Dental Lab (H)', 'HV');

-- Note: Invoice data should be imported using the import utility
-- See /utils/import.php for JSON data import
