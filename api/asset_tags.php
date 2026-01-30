<?php
// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
// Set default user_id if not in session (for testing)
if(!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user ID for testing
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getTag($db, $_GET['id']);
        } elseif(isset($_GET['asset_id'])) {
            getAssetTags($db, $_GET['asset_id']);
        } else {
            getAllTags($db);
        }
        break;
    case 'POST':
        if(isset($_GET['assign'])) {
            assignTagToAsset($db);
        } else {
            createTag($db);
        }
        break;
    case 'DELETE':
        if(isset($_GET['unassign'])) {
            unassignTagFromAsset($db);
        } elseif(isset($_GET['id'])) {
            deleteTag($db, $_GET['id']);
        }
        break;
    case 'PUT':
        if(isset($_GET['id'])) {
            updateTag($db, $_GET['id']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getAllTags($db) {
    $query = "SELECT t.*, u.full_name as created_by_name,
              (SELECT COUNT(*) FROM asset_tag_relationships atr WHERE atr.tag_id = t.id) as usage_count
              FROM asset_tags t
              LEFT JOIN users u ON t.created_by = u.id
              ORDER BY t.name";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $tags = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tags[] = $row;
    }

    http_response_code(200);
    echo json_encode($tags);
}

function getTag($db, $id) {
    $query = "SELECT t.*, u.full_name as created_by_name
              FROM asset_tags t
              LEFT JOIN users u ON t.created_by = u.id
              WHERE t.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($tag);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Tag not found"));
    }
}

function getAssetTags($db, $asset_id) {
    $query = "SELECT t.*, atr.assigned_at, u.full_name as assigned_by_name
              FROM asset_tags t
              JOIN asset_tag_relationships atr ON t.id = atr.tag_id
              LEFT JOIN users u ON atr.assigned_by = u.id
              WHERE atr.asset_id = ?
              ORDER BY t.name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $asset_id);
    $stmt->execute();

    $tags = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tags[] = $row;
    }

    http_response_code(200);
    echo json_encode($tags);
}

function createTag($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->name)) {
        $query = "INSERT INTO asset_tags (name, description, color, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);

        if($stmt->execute([
            $data->name,
            isset($data->description) ? $data->description : null,
            isset($data->color) ? $data->color : '#3B82F6',
            $_SESSION['user_id']
        ])) {
            $tag_id = $db->lastInsertId();

            // Log the activity
            logActivity($db, $_SESSION['user_id'], 'create', 'asset_tags', $tag_id);

            http_response_code(201);
            echo json_encode(array("message" => "Tag created successfully", "id" => $tag_id));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create tag"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Tag name is required"));
    }
}

function updateTag($db, $id) {
    $data = json_decode(file_get_contents("php://input"));

    $query = "UPDATE asset_tags SET name = ?, description = ?, color = ? WHERE id = ?";
    $stmt = $db->prepare($query);

    if($stmt->execute([
        $data->name,
        $data->description ?? null,
        $data->color ?? '#3B82F6',
        $id
    ])) {
        logActivity($db, $_SESSION['user_id'], 'update', 'asset_tags', $id);

        http_response_code(200);
        echo json_encode(array("message" => "Tag updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to update tag"));
    }
}

function deleteTag($db, $id) {
    // Check if tag is in use
    $checkQuery = "SELECT COUNT(*) as usage_count FROM asset_tag_relationships WHERE tag_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$id]);
    $usage = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if($usage['usage_count'] > 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Cannot delete tag. It is currently assigned to " . $usage['usage_count'] . " asset(s)"));
        return;
    }

    $query = "DELETE FROM asset_tags WHERE id = ?";
    $stmt = $db->prepare($query);

    if($stmt->execute([$id])) {
        logActivity($db, $_SESSION['user_id'], 'delete', 'asset_tags', $id);

        http_response_code(200);
        echo json_encode(array("message" => "Tag deleted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to delete tag"));
    }
}

function assignTagToAsset($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->asset_id) && !empty($data->tag_id)) {
        // Check if relationship already exists
        $checkQuery = "SELECT id FROM asset_tag_relationships WHERE asset_id = ? AND tag_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$data->asset_id, $data->tag_id]);

        if($checkStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Tag already assigned to this asset"));
            return;
        }

        $query = "INSERT INTO asset_tag_relationships (asset_id, tag_id, assigned_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);

        if($stmt->execute([
            $data->asset_id,
            $data->tag_id,
            $_SESSION['user_id']
        ])) {
            $relationship_id = $db->lastInsertId();
            logActivity($db, $_SESSION['user_id'], 'assign_tag', 'asset_tag_relationships', $relationship_id);

            http_response_code(201);
            echo json_encode(array("message" => "Tag assigned successfully", "id" => $relationship_id));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to assign tag"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Asset ID and Tag ID are required"));
    }
}

function unassignTagFromAsset($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->asset_id) && !empty($data->tag_id)) {
        $query = "DELETE FROM asset_tag_relationships WHERE asset_id = ? AND tag_id = ?";
        $stmt = $db->prepare($query);

        if($stmt->execute([$data->asset_id, $data->tag_id])) {
            logActivity($db, $_SESSION['user_id'], 'unassign_tag', 'asset_tag_relationships', $data->asset_id);

            http_response_code(200);
            echo json_encode(array("message" => "Tag unassigned successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to unassign tag"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Asset ID and Tag ID are required"));
    }
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    // Temporarily disabled logging to avoid errors if system_logs table doesn't exist
    // $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    // $stmt = $db->prepare($query);
    // $stmt->execute([$user_id, $action, $table_name, $record_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}
?>