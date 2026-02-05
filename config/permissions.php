<?php
/**
 * Role-Based Access Control (RBAC) Configuration
 */

$adminPermissions = [
    'login',
    'manage_users_access',
    'manage_user_profile',
    'monitor_system',
    'review_audit_logs',
    'view_dashboard',
    'manage_assets',
    'manage_supplies',
    'manage_inventory',
    'process_supply_requests',
    'issue_supplies',
    'submit_supply_request',
    'track_request_status',
    'report_lost_damaged',
    'custodian_assignment',
    'preventive_maintenance',
    'damaged_items',
    'property_audit',
    'procurement',
    'purchase_orders',
    'generate_reports',
    'ai_demand_forecasting',
    'waste_management',
    'approve_budget',
    'review_procurement',
    'view_financial_reports',
    'user_roles_access'
];

$propertyCustodianPermissions = [
    'login',
    'manage_user_profile',
    'view_dashboard',
    'manage_assets',
    'manage_supplies',
    'manage_inventory',
    'process_supply_requests',
    'issue_supplies',
    'custodian_assignment',
    'preventive_maintenance',
    'damaged_items',
    'property_audit',
    'ai_demand_forecasting',
    'waste_management',
    'track_request_status',
    'report_lost_damaged',
    'generate_reports',
    'purchase_orders'
];

$staffPermissions = [
    'login',
    'manage_user_profile',
    'view_dashboard',
    'view_items',
    'view_supplies',
    'submit_supply_request',
    'track_request_status',
    'report_lost_damaged'
];

$maintenancePermissions = [
    'login',
    'manage_user_profile',
    'view_dashboard',
    'preventive_maintenance',
    'view_maintenance_tasks',
    'update_maintenance_status',
    'damaged_items',
    'report_lost_damaged'
];

$rolePermissions = [
    'admin' => $adminPermissions,
    'property_custodian' => $propertyCustodianPermissions,
    'staff' => $staffPermissions,
    'maintenance' => $maintenancePermissions,
];

// Backwards compatibility for legacy "custodian" role slug
$rolePermissions['custodian'] = $propertyCustodianPermissions;

define('ROLE_PERMISSIONS', $rolePermissions);

define('ROLE_DISPLAY_NAMES', [
    'admin' => 'Admin',
    'property_custodian' => 'Property Custodian',
    'custodian' => 'Property Custodian',
    'staff' => 'Staff',
    'maintenance' => 'Maintenance Personnel',
]);

// Map permissions to menu items
define('MENU_PERMISSIONS', [
    'dashboard' => ['view_dashboard'],
    'asset_registry' => ['manage_assets', 'view_items'],
    'property_issuance' => ['issue_supplies', 'submit_supply_request'],
    'supplies_inventory' => ['manage_supplies', 'manage_inventory', 'view_supplies'],
    'custodian_assignment' => ['custodian_assignment'],
    'preventive_maintenance' => ['preventive_maintenance'],
    'damaged_items' => ['damaged_items', 'report_lost_damaged'],
    'property_audit' => ['property_audit'],
    'ai_demand_forecasting' => ['ai_demand_forecasting'],
    'procurement' => ['procurement', 'review_procurement'],
    'purchase_orders' => ['purchase_orders', 'procurement', 'review_procurement'],
    'reports_analytics' => ['generate_reports', 'view_financial_reports'],
    'waste_management' => ['waste_management'],
    'user_roles_access' => ['user_roles_access', 'manage_users_access']
]);

/**
 * Check if a user has a specific permission
 */
function hasPermission($userRole, $permission) {
    if (!isset(ROLE_PERMISSIONS[$userRole])) {
        return false;
    }
    return in_array($permission, ROLE_PERMISSIONS[$userRole]);
}

/**
 * Check if user has access to a menu item
 */
function hasMenuAccess($userRole, $menuItem) {
    if (!isset(MENU_PERMISSIONS[$menuItem])) {
        return false;
    }
    
    $requiredPermissions = MENU_PERMISSIONS[$menuItem];
    
    // User needs at least one of the required permissions
    foreach ($requiredPermissions as $permission) {
        if (hasPermission($userRole, $permission)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get accessible menu items for a role
 */
function getAccessibleMenus($userRole) {
    $accessibleMenus = [];
    
    foreach (MENU_PERMISSIONS as $menuItem => $permissions) {
        if (hasMenuAccess($userRole, $menuItem)) {
            $accessibleMenus[] = $menuItem;
        }
    }
    
    return $accessibleMenus;
}

/**
 * Check if user can perform an action
 */
function canPerformAction($userRole, $action) {
    return hasPermission($userRole, $action);
}
?>
