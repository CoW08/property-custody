<?php
ini_set("display_errors", 0); ini_set("log_errors", 1);
/**
 * API Permission Checker
 * Include this file in API endpoints to check permissions
 */

session_start();
require_once __DIR__ . '/../config/permissions.php';

/**
 * Check if user has permission for API access
 */
function checkApiPermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
        exit();
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    if (!hasPermission($userRole, $permission)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'message' => 'You do not have permission to perform this action']);
        exit();
    }
    
    return true;
}

/**
 * Require API permission or return error
 */
function requireApiPermission($permission) {
    return checkApiPermission($permission);
}

/**
 * Check multiple permissions (user needs at least one)
 */
function checkAnyApiPermission($permissions) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
        exit();
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    foreach ($permissions as $permission) {
        if (hasPermission($userRole, $permission)) {
            return true;
        }
    }
    
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'message' => 'You do not have permission to perform this action']);
    exit();
}
?>
