-- ============================================================
-- PCMS Comprehensive Fix - Run in phpMyAdmin if auto-fixes fail
-- ============================================================

-- Fix ALL tables with missing AUTO_INCREMENT
ALTER TABLE purchase_orders MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE purchase_order_items MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE supplies MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE supply_transactions MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE damaged_items MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- Seed maintenance technicians (password: password123)
INSERT IGNORE INTO users (username, password, full_name, email, role, department, status) VALUES
('mark.anthony.solis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mark Anthony Solis', 'msolis@school.edu', 'maintenance', 'Electrical', 'active'),
('renato.castillo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Renato Castillo', 'rcastillo@school.edu', 'maintenance', 'Electrical', 'active'),
('john.mendoza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Mendoza', 'jmendoza@school.edu', 'maintenance', 'HVAC', 'active'),
('jayson.rivera', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jayson Rivera', 'jrivera@school.edu', 'maintenance', 'HVAC', 'active'),
('grace.tolentino', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Grace Tolentino', 'gtolentino@school.edu', 'maintenance', 'IT/Networking', 'active'),
('marlon.ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marlon Ramos', 'mramos@school.edu', 'maintenance', 'IT/Networking', 'active');
