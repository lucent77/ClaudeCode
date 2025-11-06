-- ============================================
-- Purchase Management System Database Schema
-- ============================================
-- Created for Hostinger PHP + MySQL
-- ============================================

-- Create database (update database name as needed)
CREATE DATABASE IF NOT EXISTS purchase_management;
USE purchase_management;

-- ============================================
-- Table: users
-- Stores department users and admin accounts
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'user') DEFAULT 'user',
  `email` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: vendors
-- Stores vendor information
-- ============================================
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vendor_name` VARCHAR(255) NOT NULL,
  `vendor_link` VARCHAR(500) DEFAULT NULL,
  `contact` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_vendor_name` (`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: products
-- Stores product catalog with vendor references
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `default_vendor_id` INT(11) DEFAULT NULL,
  `avg_price` DECIMAL(10, 2) DEFAULT 0.00,
  `last_purchased_date` DATE DEFAULT NULL,
  `total_purchases` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_name` (`product_name`),
  FOREIGN KEY (`default_vendor_id`) REFERENCES `vendors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: purchase_requests
-- Main table for all purchase orders
-- ============================================
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_type` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `vendor_id` INT(11) DEFAULT NULL,
  `vendor_name` VARCHAR(255) DEFAULT NULL,
  `vendor_link` VARCHAR(500) DEFAULT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `shipping_speed` VARCHAR(100) DEFAULT NULL,
  `reason` TEXT NOT NULL,
  `photo_path` VARCHAR(500) DEFAULT NULL,
  `price` DECIMAL(10, 2) DEFAULT 0.00,
  `status` ENUM('pending', 'approved', 'ordered', 'delivered', 'completed', 'rejected') DEFAULT 'pending',
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_department` (`department`),
  INDEX `idx_status` (`status`),
  INDEX `idx_requested_at` (`requested_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: notifications
-- Stores system notifications for users
-- ============================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `purchase_request_id` INT(11) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Admin Account
-- Username: admin
-- Password: admin123 (CHANGE THIS AFTER FIRST LOGIN!)
-- ============================================
INSERT INTO `users` (`username`, `password`, `department`, `role`, `email`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administration', 'admin', 'admin@company.com');

-- ============================================
-- Insert Sample Departments (Optional)
-- Password for all: password123
-- ============================================
INSERT INTO `users` (`username`, `password`, `department`, `role`, `email`) VALUES
('it_dept', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IT Department', 'user', 'it@company.com'),
('hr_dept', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Department', 'user', 'hr@company.com'),
('finance_dept', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Department', 'user', 'finance@company.com'),
('marketing_dept', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing Department', 'user', 'marketing@company.com');

-- ============================================
-- Insert Sample Vendors (Optional)
-- ============================================
INSERT INTO `vendors` (`vendor_name`, `vendor_link`, `contact`, `email`) VALUES
('Amazon Business', 'https://www.amazon.com/b2b', '+1-888-281-3847', 'business@amazon.com'),
('Office Depot', 'https://www.officedepot.com', '+1-800-463-3768', 'business@officedepot.com'),
('Staples Business', 'https://www.staples.com', '+1-800-333-3330', 'business@staples.com'),
('Alibaba', 'https://www.alibaba.com', 'N/A', 'support@alibaba.com');

-- ============================================
-- Insert Sample Products (Optional)
-- ============================================
INSERT INTO `products` (`product_name`, `description`, `default_vendor_id`, `avg_price`, `total_purchases`) VALUES
('Laptop - Dell XPS 15', 'High-performance laptop for development', 1, 1499.99, 0),
('Office Chair - Ergonomic', 'Comfortable office chair with lumbar support', 2, 299.99, 0),
('Printer - HP LaserJet Pro', 'Black and white laser printer', 3, 199.99, 0),
('Mouse - Logitech MX Master 3', 'Wireless ergonomic mouse', 1, 99.99, 0),
('Monitor - Dell 27" 4K', '4K UHD monitor', 1, 449.99, 0);

-- ============================================
-- Views for Reporting
-- ============================================

-- View: Purchase summary by department
CREATE OR REPLACE VIEW `v_purchase_summary_by_dept` AS
SELECT
  department,
  COUNT(*) as total_requests,
  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
  SUM(price * quantity) as total_spent
FROM purchase_requests
GROUP BY department;

-- View: Product purchase history with vendor details
CREATE OR REPLACE VIEW `v_product_history` AS
SELECT
  pr.id,
  pr.product_name,
  pr.vendor_name,
  pr.vendor_link,
  pr.quantity,
  pr.price,
  pr.department,
  pr.status,
  pr.requested_at
FROM purchase_requests pr
ORDER BY pr.requested_at DESC;

-- ============================================
-- Stored Procedure: Update Product Statistics
-- ============================================
DELIMITER $$

CREATE PROCEDURE `update_product_stats`(IN p_product_id INT)
BEGIN
  DECLARE avg_cost DECIMAL(10,2);
  DECLARE last_date DATE;
  DECLARE total_count INT;

  SELECT
    AVG(price),
    MAX(requested_at),
    COUNT(*)
  INTO avg_cost, last_date, total_count
  FROM purchase_requests
  WHERE product_id = p_product_id
    AND status IN ('completed', 'delivered');

  UPDATE products
  SET
    avg_price = COALESCE(avg_cost, 0),
    last_purchased_date = last_date,
    total_purchases = COALESCE(total_count, 0)
  WHERE id = p_product_id;
END$$

DELIMITER ;

-- ============================================
-- Trigger: Update product stats on purchase completion
-- ============================================
DELIMITER $$

CREATE TRIGGER `after_purchase_status_update`
AFTER UPDATE ON `purchase_requests`
FOR EACH ROW
BEGIN
  IF NEW.status IN ('completed', 'delivered') AND NEW.product_id IS NOT NULL THEN
    CALL update_product_stats(NEW.product_id);
  END IF;
END$$

DELIMITER ;

-- ============================================
-- End of Schema
-- ============================================
