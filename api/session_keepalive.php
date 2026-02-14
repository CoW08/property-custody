<?php
ini_set("display_errors", 0); ini_set("log_errors", 1);
/**
 * Session Keep-Alive Endpoint
 * Called by the client-side activity tracker whenever user is active.
 * Resets the LAST_ACTIVITY timestamp to prevent session expiry.
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800); // Match our 30-min timeout
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthenticated', 'session_expired' => true]);
    exit();
}

// Reset activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();

http_response_code(200);
echo json_encode(['status' => 'ok', 'expires_in' => 1800]);
