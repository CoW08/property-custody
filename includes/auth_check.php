<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enforce session timeout (2 minutes idle)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 2 * 60);
}

if (isset($_SESSION['LAST_ACTIVITY'])) {
    $elapsed = time() - (int) $_SESSION['LAST_ACTIVITY'];
    if ($elapsed > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?session=expired');
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

// Include permissions configuration
$permissions_file = __DIR__ . '/../config/permissions.php';
if (file_exists($permissions_file)) {
    require_once $permissions_file;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $role = $_SESSION['role'] ?? '';
    $permissions = [];
    $menus = [];

    if (defined('ROLE_PERMISSIONS') && isset(ROLE_PERMISSIONS[$role])) {
        $permissions = ROLE_PERMISSIONS[$role];
    }

    if (function_exists('getAccessibleMenus')) {
        $menus = getAccessibleMenus($role);
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $role,
        'email' => $_SESSION['email'] ?? '',
        'department' => $_SESSION['department'] ?? '',
        'permissions' => $permissions,
        'accessible_menus' => $menus
    ];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    requireAuth();
    if ($_SESSION['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. Required role: ' . $role;
        exit();
    }
}

function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Check if current user has a specific permission
 */
function checkPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? '';
    return hasPermission($userRole, $permission);
}

/**
 * Require a specific permission or redirect
 */
function requirePermission($permission, $redirectTo = 'dashboard.php') {
    requireAuth();
    
    if (!checkPermission($permission)) {
        header('HTTP/1.1 403 Forbidden');
        header('Location: ' . $redirectTo . '?error=access_denied');
        exit();
    }
}

/**
 * Check if current user has access to a menu item
 */
function checkMenuAccess($menuItem) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? '';
    return hasMenuAccess($userRole, $menuItem);
}

/**
 * Get all accessible menu items for current user
 */
function getAccessibleMenuItems() {
    if (!isLoggedIn()) {
        return [];
    }
    
    $userRole = $_SESSION['role'] ?? '';
    return getAccessibleMenus($userRole);
}
?>
