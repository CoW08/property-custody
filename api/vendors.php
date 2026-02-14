<?php
ini_set("display_errors", 0); ini_set("log_errors", 1);
require_once '../config/cors.php';
require_once '../config/database.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ensure vendors table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255) NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(50) NULL,
        address TEXT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Throwable $e) {
    error_log("[VENDORS] Table creation error: " . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getVendor($db, intval($_GET['id']));
        } else {
            getVendors($db);
        }
        break;
    case 'POST':
        createVendor($db);
        break;
    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing vendor ID']);
            break;
        }
        updateVendor($db, intval($_GET['id']));
        break;
    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing vendor ID']);
            break;
        }
        deleteVendor($db, intval($_GET['id']));
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getVendors(PDO $db): void
{
    try {
        $statusFilter = $_GET['status'] ?? 'active';
        $params = [];
        $whereClause = '';

        if ($statusFilter === 'all') {
            $whereClause = '';
        } else {
            $whereClause = 'WHERE status = :status';
            $params[':status'] = $statusFilter;
        }

        $query = "SELECT id, name, contact_person, email, phone, address, status, created_at, updated_at
                  FROM vendors $whereClause ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $vendors]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load vendors', 'message' => $e->getMessage()]);
    }
}

function getVendor(PDO $db, int $id): void
{
    $query = "SELECT id, name, contact_person, email, phone, address, status, created_at, updated_at
              FROM vendors WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);

    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendor not found']);
        return;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $vendor]);
}

function createVendor(PDO $db): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Vendor name is required']);
        return;
    }

    try {
        $query = "INSERT INTO vendors (name, contact_person, email, phone, address, status)
                  VALUES (:name, :contact_person, :email, :phone, :address, :status)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name' => trim($data['name']),
            ':contact_person' => $data['contact_person'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ]);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Vendor created successfully', 'id' => $db->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create vendor', 'message' => $e->getMessage()]);
    }
}

function updateVendor(PDO $db, int $id): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        return;
    }

    $allowedFields = ['name', 'contact_person', 'email', 'phone', 'address', 'status'];
    $setClauses = [];
    $params = [':id' => $id];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $setClauses[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }

    if (empty($setClauses)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    try {
        $query = 'UPDATE vendors SET ' . implode(', ', $setClauses) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Vendor not found']);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Vendor updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update vendor', 'message' => $e->getMessage()]);
    }
}

function deleteVendor(PDO $db, int $id): void
{
    try {
        $query = "UPDATE vendors SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Vendor not found']);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Vendor archived successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to archive vendor', 'message' => $e->getMessage()]);
    }
}
