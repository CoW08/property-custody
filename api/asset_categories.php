<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
// Temporarily disabled for testing
// if(!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(array("message" => "Unauthorized"));
//     exit();
// }

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Database connection error", "error" => $e->getMessage()));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getCategory($db, $_GET['id']);
        } else {
            getCategories($db);
        }
        break;
    case 'POST':
        createCategory($db);
        break;
    case 'PUT':
        if(isset($_GET['id'])) {
            updateCategory($db, $_GET['id']);
        }
        break;
    case 'DELETE':
        if(isset($_GET['id'])) {
            deleteCategory($db, $_GET['id']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getCategories($db) {
    $query = "SELECT ac.*,
              (SELECT COUNT(*) FROM assets a WHERE a.category = ac.id) as asset_count
              FROM asset_categories ac
              ORDER BY ac.name";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $categories = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row;
    }

    http_response_code(200);
    echo json_encode($categories);
}

function getCategory($db, $id) {
    $query = "SELECT ac.*,
              (SELECT COUNT(*) FROM assets a WHERE a.category = ac.id) as asset_count
              FROM asset_categories ac
              WHERE ac.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($category);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Category not found"));
    }
}

function createCategory($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->name)) {
        $query = "INSERT INTO asset_categories (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($query);

        if($stmt->execute([
            $data->name,
            $data->description ?? null
        ])) {
            $category_id = $db->lastInsertId();

            // Log the activity
            logActivity($db, $_SESSION['user_id'], 'create', 'asset_categories', $category_id);

            http_response_code(201);
            echo json_encode(array("message" => "Category created successfully", "id" => $category_id));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create category"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Category name is required"));
    }
}

function updateCategory($db, $id) {
    $data = json_decode(file_get_contents("php://input"));

    $query = "UPDATE asset_categories SET name = ?, description = ? WHERE id = ?";
    $stmt = $db->prepare($query);

    if($stmt->execute([
        $data->name,
        $data->description ?? null,
        $id
    ])) {
        logActivity($db, $_SESSION['user_id'], 'update', 'asset_categories', $id);

        http_response_code(200);
        echo json_encode(array("message" => "Category updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to update category"));
    }
}

function deleteCategory($db, $id) {
    // Check if category is in use
    $checkQuery = "SELECT COUNT(*) as asset_count FROM assets WHERE category = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$id]);
    $usage = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if($usage['asset_count'] > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Cannot delete category. It is currently assigned to " . $usage['asset_count'] . " asset(s)"));
        return;
    }

    $query = "DELETE FROM asset_categories WHERE id = ?";
    $stmt = $db->prepare($query);

    if($stmt->execute([$id])) {
        logActivity($db, $_SESSION['user_id'], 'delete', 'asset_categories', $id);

        http_response_code(200);
        echo json_encode(array("message" => "Category deleted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to delete category"));
    }
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $table_name, $record_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}
?>