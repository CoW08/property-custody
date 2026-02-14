<?php
if (session_status() == PHP_SESSION_NONE) {
    // Ensure PHP session garbage collector matches our timeout
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    session_start();
}

// Enforce session timeout (30 minutes idle — resets on each authenticated request)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 30 * 60);
}

/**
 * Helper: detect if current request is an API/AJAX call
 * (so we return JSON 401 instead of a Location redirect)
 */
function _isApiRequest() {
    // Check if request is to an API endpoint
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/api/') !== false) {
        return true;
    }
    // Check for AJAX / fetch requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    // Check Accept header for JSON
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false) {
        return true;
    }
    // Check Content-Type for JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        return true;
    }
    return false;
}

/**
 * Send session-expired response appropriate for the request type.
 */
function _sendSessionExpired() {
    session_unset();
    session_destroy();

    if (_isApiRequest()) {
        // API / AJAX request → JSON response (no redirect)
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired', 'session_expired' => true]);
        exit();
    }

    // Normal page request → redirect
    header('Location: login.php?session=expired');
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY'])) {
    $elapsed = time() - (int) $_SESSION['LAST_ACTIVITY'];
    if ($elapsed > SESSION_TIMEOUT) {
        _sendSessionExpired();
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
        if (_isApiRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated', 'session_expired' => true]);
            exit();
        }
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
        if (_isApiRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['message' => 'Access denied: insufficient permissions', 'error' => 'forbidden']);
            exit();
        }
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
