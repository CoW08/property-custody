<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set content type early
header('Content-Type: application/json');

function debug_log($message, $data = null) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data);
    }
    error_log($log_message);
}

function ensureIssuanceSignatureColumn(PDO $db): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    try {
        $stmt = $db->query("SHOW COLUMNS FROM property_issuances LIKE 'recipient_signature'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE property_issuances ADD COLUMN recipient_signature LONGTEXT NULL");
        }
    } catch (Throwable $throwable) {
        debug_log('Failed to ensure recipient_signature column', ['error' => $throwable->getMessage()]);
    }

    $checked = true;
}

// Test if we get this far
debug_log("Property Issuance API Start");

try {
    require_once __DIR__ . '/../config/cors.php';
    debug_log("CORS loaded");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("error" => "CORS load failed: " . $e->getMessage()));
    exit();
}

try {
    require_once __DIR__ . '/../config/database.php';
    debug_log("Database config loaded");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("error" => "Database config load failed: " . $e->getMessage()));
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
debug_log("Session started");

try {
    debug_log("Creating database instance");
    $database = new Database();
    debug_log("Getting database connection");
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Database connection failed - null returned");
    }
    debug_log("Database connection successful");
} catch (Exception $e) {
    debug_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "Database connection error", "error" => $e->getMessage()));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input_data = file_get_contents("php://input");
debug_log("Property Issuance API Request", ["method" => $method, "get" => $_GET, "input" => $input_data]);

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getIssuance($db, $_GET['id']);
        } else {
            getIssuances($db);
        }
        break;
    case 'POST':
        createIssuance($db, $input_data);
        break;
    case 'PUT':
        if(isset($_GET['id'])) {
            updateIssuance($db, $_GET['id'], $input_data);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Issuance ID required for update"));
        }
        break;
    case 'DELETE':
        if(isset($_GET['id'])) {
            deleteIssuance($db, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Issuance ID required for delete"));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getIssuances($db) {
    debug_log("getIssuances called");
    try {
        // First check if the table exists
        debug_log("Checking if property_issuances table exists");
        $checkTable = $db->query("SHOW TABLES LIKE 'property_issuances'");
        if($checkTable->rowCount() == 0) {
            debug_log("property_issuances table not found");
            http_response_code(500);
            echo json_encode(array("message" => "Property issuances table not found. Please run database setup."));
            return;
        }
        debug_log("property_issuances table exists");

        $whereClause = "";
        $params = array();

        if(isset($_GET['search']) && !empty($_GET['search'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "(a.asset_code LIKE ? OR a.name LIKE ? OR pi.recipient_name LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if(isset($_GET['status']) && !empty($_GET['status'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "pi.status = ?";
            $params[] = $_GET['status'];
        }

        if(isset($_GET['department']) && !empty($_GET['department'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "pi.department = ?";
            $params[] = $_GET['department'];
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        $countQuery = "SELECT COUNT(*) as total
                       FROM property_issuances pi
                       LEFT JOIN assets a ON pi.asset_id = a.id" . $whereClause;

        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT pi.*,
                  a.asset_code, a.name as asset_name, a.description as asset_description,
                  u.full_name as issued_by_name
                  FROM property_issuances pi
                  LEFT JOIN assets a ON pi.asset_id = a.id
                  LEFT JOIN users u ON pi.issued_by = u.id" .
                  $whereClause . "
                  ORDER BY pi.created_at DESC
                  LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $issuances = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $issuances[] = $row;
        }

        $totalPages = ceil($totalCount / $limit);

        http_response_code(200);
        echo json_encode(array(
            'issuances' => $issuances,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => intval($totalCount),
                'per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            )
        ));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error fetching issuances", "error" => $e->getMessage()));
    }
}

function getIssuance($db, $id) {
    try {
        $query = "SELECT pi.*,
                  a.asset_code, a.name as asset_name, a.description as asset_description,
                  u.full_name as issued_by_name
                  FROM property_issuances pi
                  LEFT JOIN assets a ON pi.asset_id = a.id
                  LEFT JOIN users u ON pi.issued_by = u.id
                  WHERE pi.id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $issuance = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode($issuance);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Issuance not found"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error fetching issuance", "error" => $e->getMessage()));
    }
}

function createIssuance($db, $input = null) {
    debug_log("createIssuance called");
    if ($input === null) {
        $input = file_get_contents("php://input");
    }
    debug_log("Raw input", $input);

    $data = json_decode($input);
    debug_log("Parsed data", $data);

    if(!isset($data->asset_id) || !isset($data->recipient_name) || !isset($data->employee_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "Asset ID, recipient name, and employee ID are required"));
        return;
    }

    try {
        ensureIssuanceSignatureColumn($db);

        // Check if asset exists and is available
        $assetQuery = "SELECT id, status FROM assets WHERE id = ?";
        $assetStmt = $db->prepare($assetQuery);
        $assetStmt->execute([$data->asset_id]);

        if($assetStmt->rowCount() == 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Asset not found"));
            return;
        }

        $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
        if($asset['status'] !== 'available') {
            http_response_code(400);
            echo json_encode(array("message" => "Asset is not available for issuance"));
            return;
        }

        $db->beginTransaction();

        // Create issuance record
        $insertQuery = "INSERT INTO property_issuances
                       (asset_id, employee_id, recipient_name, department, issue_date, expected_return_date, purpose, recipient_signature, status, issued_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'issued', ?, CURRENT_TIMESTAMP)";

        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            $data->asset_id,
            $data->employee_id,
            $data->recipient_name,
            $data->department ?? null,
            $data->issue_date ?? date('Y-m-d'),
            $data->expected_return_date ?? null,
            $data->purpose ?? null,
            isset($data->recipient_signature) ? $data->recipient_signature : null,
            $_SESSION['user_id'] ?? 1
        ]);

        $issuance_id = $db->lastInsertId();

        // Update asset status to assigned
        $updateAssetQuery = "UPDATE assets SET status = 'assigned', assigned_to = NULL WHERE id = ?";
        $updateAssetStmt = $db->prepare($updateAssetQuery);
        $updateAssetStmt->execute([$data->asset_id]);

        $db->commit();

        // Generate PDF URL
        $pdf_url = 'generate_issuance_pdf.php?issuance_id=' . $issuance_id;

        http_response_code(201);
        echo json_encode(array(
            "message" => "Property issued successfully",
            "id" => $issuance_id,
            "issuance_id" => $issuance_id,
            "pdf_url" => $pdf_url,
            "success" => true
        ));

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Failed to create issuance", "error" => $e->getMessage()));
    }
}

function updateIssuance($db, $id, $input = null) {
    debug_log("updateIssuance called", ["id" => $id]);
    if ($input === null) {
        $input = file_get_contents("php://input");
    }

    $data = json_decode($input);
    if ($data === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid JSON data"));
        return;
    }

    try {
        // Check if issuance exists
        $checkQuery = "SELECT * FROM property_issuances WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$id]);

        if($checkStmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Issuance not found"));
            return;
        }

        $currentIssuance = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $updateFields = array();
        $updateValues = array();

        if (isset($data->status)) {
            $updateFields[] = 'status = ?';
            $updateValues[] = $data->status;

            // If returning the asset, update asset status
            if($data->status === 'returned') {
                $updateFields[] = 'actual_return_date = ?';
                $updateValues[] = date('Y-m-d H:i:s');

                // Update asset status to available
                $updateAssetQuery = "UPDATE assets SET status = 'available', assigned_to = NULL WHERE id = ?";
                $updateAssetStmt = $db->prepare($updateAssetQuery);
                $updateAssetStmt->execute([$currentIssuance['asset_id']]);
            }
        }

        if (isset($data->expected_return_date)) {
            $updateFields[] = 'expected_return_date = ?';
            $updateValues[] = $data->expected_return_date;
        }

        if (isset($data->purpose)) {
            $updateFields[] = 'purpose = ?';
            $updateValues[] = $data->purpose;
        }

        if (isset($data->remarks)) {
            $updateFields[] = 'remarks = ?';
            $updateValues[] = $data->remarks;
        }

        $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        $updateValues[] = $id;

        $query = "UPDATE property_issuances SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($query);

        if($stmt->execute($updateValues)) {
            http_response_code(200);
            echo json_encode(array("message" => "Issuance updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update issuance"));
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Database error", "error" => $e->getMessage()));
    }
}

function deleteIssuance($db, $id) {
    try {
        // First get the issuance details to update asset status
        $checkQuery = "SELECT asset_id, status FROM property_issuances WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$id]);

        if($checkStmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Issuance not found"));
            return;
        }

        $issuance = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $db->beginTransaction();

        // Delete issuance
        $deleteQuery = "DELETE FROM property_issuances WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$id]);

        // If issuance was active, update asset status to available
        if($issuance['status'] === 'issued') {
            $updateAssetQuery = "UPDATE assets SET status = 'available', assigned_to = NULL WHERE id = ?";
            $updateAssetStmt = $db->prepare($updateAssetQuery);
            $updateAssetStmt->execute([$issuance['asset_id']]);
        }

        $db->commit();

        http_response_code(200);
        echo json_encode(array("message" => "Issuance deleted successfully"));

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(array("message" => "Failed to delete issuance", "error" => $e->getMessage()));
    }
}
?>