-- Migration: Ensure all required tables and columns exist
-- Run this migration to fix schema issues

-- Ensure maintenance_schedules table exists
CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    maintenance_type VARCHAR(50) NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE NULL,
    assigned_to INT NULL,
    description TEXT,
    estimated_cost DECIMAL(12,2) NULL,
    actual_cost DECIMAL(12,2) NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(30) DEFAULT 'scheduled',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset (asset_id),
    INDEX idx_status (status),
    INDEX idx_date (scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure supply_transactions table exists
CREATE TABLE IF NOT EXISTS supply_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    transaction_type VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2) NULL,
    total_cost DECIMAL(12,2) NULL,
    reference_number VARCHAR(100) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supply (supply_id),
    INDEX idx_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure waste_management_records has all required columns
-- Add disposal_method if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'waste_management_records' AND COLUMN_NAME = 'disposal_method');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE waste_management_records ADD COLUMN disposal_method VARCHAR(100) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add disposal_notes if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'waste_management_records' AND COLUMN_NAME = 'disposal_notes');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE waste_management_records ADD COLUMN disposal_notes TEXT NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure vendors table exists
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure OTP table exists
CREATE TABLE IF NOT EXISTS user_otp_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix any NULL archived_by in waste_management_records by setting to 1 (admin)
UPDATE waste_management_records SET archived_by = 1 WHERE archived_by IS NULL;

-- Fix any NULL disposed_by in waste_management_records where status = 'disposed'
UPDATE waste_management_records SET disposed_by = archived_by WHERE status = 'disposed' AND disposed_by IS NULL;

-- Update session timeout config
-- The session timeout is now handled by includes/auth_check.php (30 minutes)
-- and the client-side activity tracker (api/session_keepalive.php)

-- Ensure custodians table exists
CREATE TABLE IF NOT EXISTS custodians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    employee_id VARCHAR(50) NOT NULL,
    department VARCHAR(100) NULL,
    position VARCHAR(100) NULL,
    contact_number VARCHAR(50) NULL,
    office_location VARCHAR(200) NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure property_assignments table exists
CREATE TABLE IF NOT EXISTS property_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    custodian_id INT NOT NULL,
    assigned_by INT NULL,
    assignment_date DATE NOT NULL,
    expected_return_date DATE NULL,
    actual_return_date DATE NULL,
    assignment_purpose TEXT NULL,
    conditions TEXT NULL,
    notes TEXT NULL,
    status VARCHAR(30) DEFAULT 'active',
    approved_by INT NULL,
    approved_signature TEXT NULL,
    approved_at DATETIME NULL,
    issued_by INT NULL,
    issued_at DATETIME NULL,
    current_custodian_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset (asset_id),
    INDEX idx_custodian (custodian_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure assignment_requests table exists
CREATE TABLE IF NOT EXISTS assignment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    asset_id INT NOT NULL,
    purpose TEXT NULL,
    justification TEXT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    rejection_reason TEXT NULL,
    approver_signature TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_requester (requester_id),
    INDEX idx_asset (asset_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure assignment_history table exists
CREATE TABLE IF NOT EXISTS assignment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    asset_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    actor_id INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assignment (assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure custodian_transfers table exists
CREATE TABLE IF NOT EXISTS custodian_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    from_custodian_id INT NULL,
    to_custodian_id INT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assignment (assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure assignment_maintenance_links table exists
CREATE TABLE IF NOT EXISTS assignment_maintenance_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    maintenance_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assignment (assignment_id),
    INDEX idx_maintenance (maintenance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure purchase_orders table exists
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL,
    request_id INT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    vendor_contact_name VARCHAR(255) NULL,
    vendor_email VARCHAR(255) NULL,
    vendor_phone VARCHAR(50) NULL,
    vendor_address TEXT NULL,
    order_date DATE NULL,
    expected_delivery_date DATE NULL,
    payment_terms VARCHAR(100) NULL,
    shipping_method VARCHAR(100) NULL,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    shipping_cost DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    status VARCHAR(30) DEFAULT 'pending',
    notes TEXT NULL,
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_po_number (po_number),
    INDEX idx_request (request_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure purchase_order_items table exists
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    request_item_id INT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    quantity INT DEFAULT 1,
    unit VARCHAR(50) NULL,
    unit_cost DECIMAL(12,2) DEFAULT 0,
    total_cost DECIMAL(12,2) DEFAULT 0,
    expected_delivery_date DATE NULL,
    status VARCHAR(30) DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po (purchase_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure property_issuances table exists
CREATE TABLE IF NOT EXISTS property_issuances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    issued_to INT NULL,
    issued_by INT NULL,
    issuance_date DATE NOT NULL,
    expected_return_date DATE NULL,
    actual_return_date DATE NULL,
    purpose TEXT NULL,
    status VARCHAR(30) DEFAULT 'issued',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset (asset_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure property_audits table exists
CREATE TABLE IF NOT EXISTS property_audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_name VARCHAR(255) NOT NULL,
    audit_date DATE NOT NULL,
    auditor_id INT NULL,
    status VARCHAR(30) DEFAULT 'planned',
    notes TEXT NULL,
    total_items INT DEFAULT 0,
    items_found INT DEFAULT 0,
    items_missing INT DEFAULT 0,
    items_damaged INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure audit_findings table exists
CREATE TABLE IF NOT EXISTS audit_findings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    asset_id INT NULL,
    finding_type VARCHAR(50) NULL,
    description TEXT NULL,
    status VARCHAR(30) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit (audit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure system_logs table exists
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NULL,
    table_name VARCHAR(50) NULL,
    record_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
