<?php
// System Configuration
define('APP_NAME', 'Property Custodian Management System');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'School Management System for Property and Supplies Management');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'systems');
define('DB_USER', 'root');
define('DB_PASS', 'root');


// File Upload Configuration
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// System Settings
define('ITEMS_PER_PAGE', 20);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Asset Categories
define('ASSET_CATEGORIES', [
    1 => 'Computer Equipment',
    2 => 'Office Furniture',
    3 => 'Laboratory Equipment',
    4 => 'Audio Visual Equipment',
    5 => 'Medical Supply',
    6 => 'Sports Equipment',
    7 => 'Books and References'
]);

// Supply Categories
define('SUPPLY_CATEGORIES', [
    'Office Supplies',
    'Cleaning Supplies',
    'Medical Supplies',
    'IT Supplies',
    'Laboratory Supplies',
    'Kitchen Supplies',
    'Maintenance Supplies'
]);

// User Roles and Permissions
define('USER_ROLES', [
    'admin' => [
        'name' => 'Administrator',
        'permissions' => ['view_all', 'create', 'update', 'delete', 'approve', 'audit', 'reports', 'user_management']
    ],
    'custodian' => [
        'name' => 'Property Custodian',
        'permissions' => ['view_all', 'create', 'update', 'assign', 'maintenance', 'reports']
    ],
    'staff' => [
        'name' => 'Staff Member',
        'permissions' => ['view_own', 'request', 'view_assigned']
    ],
    'maintenance' => [
        'name' => 'Maintenance Personnel',
        'permissions' => ['view_maintenance', 'update_maintenance', 'maintenance_reports']
    ]
]);

// Asset Status Options
define('ASSET_STATUSES', [
    'available' => 'Available',
    'assigned' => 'Assigned',
    'maintenance' => 'Under Maintenance',
    'damaged' => 'Damaged',
    'lost' => 'Lost',
    'disposed' => 'Disposed'
]);

// Asset Condition Options
define('ASSET_CONDITIONS', [
    'excellent' => 'Excellent',
    'good' => 'Good',
    'fair' => 'Fair',
    'poor' => 'Poor'
]);

// Supply Status Options
define('SUPPLY_STATUSES', [
    'active' => 'Active',
    'discontinued' => 'Discontinued',
    'expired' => 'Expired'
]);

// Transaction Types
define('TRANSACTION_TYPES', [
    'in' => 'Stock In',
    'out' => 'Stock Out',
    'adjustment' => 'Stock Adjustment'
]);

// Maintenance Types
define('MAINTENANCE_TYPES', [
    'preventive' => 'Preventive',
    'corrective' => 'Corrective',
    'emergency' => 'Emergency'
]);

// Priority Levels
define('PRIORITY_LEVELS', [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'critical' => 'Critical',
    'urgent' => 'Urgent'
]);

// Audit Types
define('AUDIT_TYPES', [
    'physical_inventory' => 'Physical Inventory',
    'financial_audit' => 'Financial Audit',
    'compliance_check' => 'Compliance Check'
]);

// Report Types
define('REPORT_TYPES', [
    'asset_listing' => 'Asset Listing Report',
    'supply_inventory' => 'Supply Inventory Report',
    'maintenance_schedule' => 'Maintenance Schedule Report',
    'audit_findings' => 'Audit Findings Report',
    'procurement_summary' => 'Procurement Summary Report',
    'depreciation_report' => 'Asset Depreciation Report',
    'assignment_history' => 'Property Assignment History',
    'financial_summary' => 'Financial Summary Report'
]);

// Email Configuration (if needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'jerickdellosa3131@gmail.com');
define('SMTP_PASSWORD', 'ilhraidghbytwnzj');
define('FROM_EMAIL', 'jerickdellosa3131@gmail.com');
define('FROM_NAME', 'Property Management System');
define('OTP_DEV_MODE', false);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>