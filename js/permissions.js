/**
 * Client-side Permission Management
 * Based on use case diagram role restrictions
 */

// Role-based permissions (matching PHP configuration)
const PERMISSIONS = {
    admin: [
        'login', 'manage_users_access', 'manage_user_profile', 'monitor_system',
        'review_audit_logs', 'view_dashboard', 'manage_assets', 'manage_supplies',
        'manage_inventory', 'process_supply_requests', 'issue_supplies',
        'submit_supply_request', 'track_request_status', 'report_lost_damaged',
        'custodian_assignment', 'preventive_maintenance', 'damaged_items',
        'property_audit', 'procurement', 'generate_reports', 'ai_demand_forecasting',
        'approve_budget', 'review_procurement', 'view_financial_reports', 'user_roles_access'
    ],
    custodian: [
        'login', 'manage_user_profile', 'view_dashboard', 'manage_assets',
        'manage_supplies', 'manage_inventory', 'process_supply_requests',
        'issue_supplies', 'custodian_assignment', 'preventive_maintenance',
        'damaged_items', 'property_audit', 'generate_reports',
        'ai_demand_forecasting', 'track_request_status', 'report_lost_damaged'
    ],
    staff: [
        'login', 'manage_user_profile', 'view_dashboard', 'submit_supply_request',
        'track_request_status', 'report_lost_damaged', 'view_assigned_items'
    ],
    finance: [
        'login', 'manage_user_profile', 'view_dashboard', 'approve_budget',
        'review_procurement', 'view_financial_reports', 'procurement'
    ],
    maintenance: [
        'login', 'manage_user_profile', 'view_dashboard', 'preventive_maintenance',
        'view_maintenance_tasks', 'update_maintenance_status'
    ]
};

class PermissionManager {
    static currentUser = null;
    
    /**
     * Initialize with current user data
     */
    static init(user) {
        this.currentUser = user;
    }
    
    /**
     * Check if current user has a specific permission
     */
    static hasPermission(permission) {
        if (!this.currentUser || !this.currentUser.role) {
            return false;
        }
        
        const userPermissions = PERMISSIONS[this.currentUser.role] || [];
        return userPermissions.includes(permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    static hasAnyPermission(permissions) {
        return permissions.some(permission => this.hasPermission(permission));
    }
    
    /**
     * Check if user has all given permissions
     */
    static hasAllPermissions(permissions) {
        return permissions.every(permission => this.hasPermission(permission));
    }
    
    /**
     * Hide elements that user doesn't have permission for
     */
    static applyRestrictions() {
        // Hide buttons with data-permission attribute
        document.querySelectorAll('[data-permission]').forEach(element => {
            const requiredPermission = element.getAttribute('data-permission');
            if (!this.hasPermission(requiredPermission)) {
                element.style.display = 'none';
            }
        });
        
        // Hide elements with data-permissions attribute (multiple permissions)
        document.querySelectorAll('[data-permissions]').forEach(element => {
            const requiredPermissions = element.getAttribute('data-permissions').split(',');
            if (!this.hasAnyPermission(requiredPermissions)) {
                element.style.display = 'none';
            }
        });
        
        // Hide elements based on role
        document.querySelectorAll('[data-role]').forEach(element => {
            const requiredRole = element.getAttribute('data-role');
            if (this.currentUser.role !== requiredRole) {
                element.style.display = 'none';
            }
        });
        
        // Hide elements that require specific roles
        document.querySelectorAll('[data-roles]').forEach(element => {
            const requiredRoles = element.getAttribute('data-roles').split(',');
            if (!requiredRoles.includes(this.currentUser.role)) {
                element.style.display = 'none';
            }
        });
    }
    
    /**
     * Disable buttons/inputs that user doesn't have permission for
     */
    static disableUnauthorizedActions() {
        document.querySelectorAll('[data-action-permission]').forEach(element => {
            const requiredPermission = element.getAttribute('data-action-permission');
            if (!this.hasPermission(requiredPermission)) {
                element.disabled = true;
                element.classList.add('opacity-50', 'cursor-not-allowed');
                element.title = 'You do not have permission for this action';
            }
        });
    }
    
    /**
     * Show/hide action buttons based on permissions
     */
    static applyActionRestrictions() {
        // Delete buttons
        document.querySelectorAll('.delete-btn, [data-action="delete"]').forEach(btn => {
            if (!this.hasPermission('manage_assets') && !this.hasPermission('manage_supplies')) {
                btn.style.display = 'none';
            }
        });
        
        // Edit buttons
        document.querySelectorAll('.edit-btn, [data-action="edit"]').forEach(btn => {
            if (!this.hasPermission('manage_assets') && !this.hasPermission('manage_supplies')) {
                btn.style.display = 'none';
            }
        });
        
        // Approve buttons (Finance only)
        document.querySelectorAll('.approve-btn, [data-action="approve"]').forEach(btn => {
            if (!this.hasPermission('approve_budget')) {
                btn.style.display = 'none';
            }
        });
        
        // User management buttons (Admin only)
        document.querySelectorAll('[data-action="manage-users"]').forEach(btn => {
            if (!this.hasPermission('manage_users_access')) {
                btn.style.display = 'none';
            }
        });
    }
    
    /**
     * Get user's role
     */
    static getRole() {
        return this.currentUser ? this.currentUser.role : null;
    }
    
    /**
     * Check if user is admin
     */
    static isAdmin() {
        return this.currentUser && this.currentUser.role === 'admin';
    }
    
    /**
     * Check if user is custodian
     */
    static isCustodian() {
        return this.currentUser && this.currentUser.role === 'custodian';
    }
    
    /**
     * Check if user is staff
     */
    static isStaff() {
        return this.currentUser && this.currentUser.role === 'staff';
    }
    
    /**
     * Check if user is finance officer
     */
    static isFinance() {
        return this.currentUser && this.currentUser.role === 'finance';
    }
}

// Auto-apply restrictions when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Get current user from session storage or PHP session
    const currentUser = getCurrentUser();
    if (currentUser) {
        PermissionManager.init(currentUser);
        PermissionManager.applyRestrictions();
        PermissionManager.disableUnauthorizedActions();
        PermissionManager.applyActionRestrictions();
    }
});

// Helper function to get current user (should be defined globally or imported)
function getCurrentUser() {
    // Try to get from sessionStorage first
    const savedUser = sessionStorage.getItem('currentUser');
    if (savedUser) {
        return JSON.parse(savedUser);
    }
    
    // Otherwise check if it's available globally from PHP
    if (typeof window.currentUser !== 'undefined') {
        return window.currentUser;
    }
    
    return null;
}
