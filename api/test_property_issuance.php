<?php
// Simple test version to identify the issue
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    echo json_encode(array(
        "status" => "success",
        "message" => "Test API is working",
        "method" => $_SERVER['REQUEST_METHOD'],
        "timestamp" => date('Y-m-d H:i:s')
    ));
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
}
?>