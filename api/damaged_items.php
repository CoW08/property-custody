<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header("Content-Type: application/json"); http_response_code(500); }
    error_log("[DAMAGED_ITEMS] Uncaught: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[DAMAGED_ITEMS] PHP error ($severity): $message in $file:$line");
    return true;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        echo json_encode(['success' => false, 'message' => 'Fatal server error']);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Debug logging
function debug_log($message, $data = null) {
    error_log("[DAMAGED_ITEMS] " . $message . ($data ? " | Data: " . json_encode($data) : ""));
}

requireAuth();

$database = new Database();
$db = $database->getConnection();

// Ensure damaged_items table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS damaged_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        asset_code VARCHAR(50) NOT NULL,
        damage_type ENUM('physical','electrical','software','wear','accident','vandalism','other') NOT NULL,
        severity_level ENUM('minor','moderate','major','total') NOT NULL,
        damage_date DATE NOT NULL,
        reported_by VARCHAR(100) NOT NULL,
        current_location VARCHAR(200) DEFAULT NULL,
        estimated_repair_cost DECIMAL(12,2) DEFAULT NULL,
        damage_description TEXT DEFAULT NULL,
        damage_photos TEXT DEFAULT NULL,
        status ENUM('reported','under_repair','repaired','write_off') DEFAULT 'reported',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_asset_id (asset_id),
        INDEX idx_asset_code (asset_code),
        INDEX idx_status (status),
        INDEX idx_damage_date (damage_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Exception $e) {
    // Table might already exist with slightly different definition, that's OK
    error_log("[DAMAGED_ITEMS] Table check: " . $e->getMessage());
}

// Fix: ensure id has AUTO_INCREMENT (existing table may lack it)
try {
    $colCheck = $db->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'damaged_items' 
                            AND COLUMN_NAME = 'id'")->fetch(PDO::FETCH_ASSOC);
    if ($colCheck && stripos($colCheck['EXTRA'], 'auto_increment') === false) {
        try { $db->exec("ALTER TABLE damaged_items ADD PRIMARY KEY (id)"); } catch (Throwable $e) {}
        $db->exec("ALTER TABLE damaged_items MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        error_log("[DAMAGED_ITEMS] Fixed id AUTO_INCREMENT");
    }
} catch (Throwable $e) {
    error_log("[DAMAGED_ITEMS] ALTER id: " . $e->getMessage());
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':
            if ($method === 'GET') {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $offset = ($page - 1) * $limit;

                $sql = "SELECT di.*, a.name as asset_name, a.category, a.location as asset_location
                        FROM damaged_items di
                        LEFT JOIN assets a ON di.asset_id = a.id
                        ORDER BY di.created_at DESC
                        LIMIT :limit OFFSET :offset";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get total count
                $countSql = "SELECT COUNT(*) as total FROM damaged_items";
                $countStmt = $db->prepare($countSql);
                $countStmt->execute();
                $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

                echo json_encode([
                    'success' => true,
                    'data' => $items,
                    'pagination' => [
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'total' => (int)$total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'create':
            if ($method === 'POST') {
                debug_log("CREATE request received");
                $input = json_decode(file_get_contents('php://input'), true);
                debug_log("Input data", $input);

                if (!$input) {
                    throw new Exception("Invalid JSON data received");
                }

                // Validate required fields
                $required = ['asset_code', 'damage_type', 'severity_level', 'damage_date', 'reported_by'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        debug_log("Missing required field: $field");
                        throw new Exception("$field is required");
                    }
                }

                // Get asset_id from asset_code
                $assetSql = "SELECT id FROM assets WHERE asset_code = :asset_code";
                $assetStmt = $db->prepare($assetSql);
                $assetStmt->bindParam(':asset_code', $input['asset_code']);
                $assetStmt->execute();
                $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);

                if (!$asset) {
                    throw new Exception("Asset with code {$input['asset_code']} not found");
                }

                $sql = "INSERT INTO damaged_items (
                    asset_id, asset_code, damage_type, severity_level, damage_date,
                    reported_by, current_location, estimated_repair_cost, damage_description, damage_photos
                ) VALUES (
                    :asset_id, :asset_code, :damage_type, :severity_level, :damage_date,
                    :reported_by, :current_location, :estimated_repair_cost, :damage_description, :damage_photos
                )";

                $stmt = $db->prepare($sql);

                // Prepare variables for bindParam
                $asset_id = $asset['id'];
                $asset_code = $input['asset_code'];
                $damage_type = $input['damage_type'];
                $severity_level = $input['severity_level'];
                $damage_date = $input['damage_date'];
                $reported_by = $input['reported_by'];
                $current_location = $input['current_location'] ?? null;
                $estimated_repair_cost = $input['estimated_repair_cost'] ?? null;
                $damage_description = $input['damage_description'] ?? null;
                $damage_photos = $input['damage_photos'] ?? null;

                $stmt->bindParam(':asset_id', $asset_id);
                $stmt->bindParam(':asset_code', $asset_code);
                $stmt->bindParam(':damage_type', $damage_type);
                $stmt->bindParam(':severity_level', $severity_level);
                $stmt->bindParam(':damage_date', $damage_date);
                $stmt->bindParam(':reported_by', $reported_by);
                $stmt->bindParam(':current_location', $current_location);
                $stmt->bindParam(':estimated_repair_cost', $estimated_repair_cost);
                $stmt->bindParam(':damage_description', $damage_description);
                $stmt->bindParam(':damage_photos', $damage_photos);

                if ($stmt->execute()) {
                    $incident_id = $db->lastInsertId();
                    
                    // Update asset condition to damaged
                    $updateAssetSql = "UPDATE assets SET condition_status = 'damaged', status = 'maintenance' WHERE id = :asset_id";
                    $updateStmt = $db->prepare($updateAssetSql);
                    $updateStmt->bindParam(':asset_id', $asset['id']);
                    $updateStmt->execute();

                    // Generate PDF URL
                    $pdf_url = 'generate_incident_pdf.php?id=' . $incident_id;

                    echo json_encode([
                        'success' => true,
                        'message' => 'Damage report created successfully',
                        'id' => $incident_id,
                        'pdf_url' => $pdf_url
                    ]);
                } else {
                    throw new Exception("Failed to create damage report");
                }
            }
            break;

        case 'update':
            if ($method === 'PUT') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';

                if (empty($id)) {
                    throw new Exception("Damage report ID is required");
                }

                $updateFields = [];
                $params = [':id' => $id];

                $allowedFields = [
                    'damage_type', 'severity_level', 'damage_date', 'reported_by',
                    'current_location', 'estimated_repair_cost', 'damage_description',
                    'damage_photos', 'status'
                ];

                foreach ($allowedFields as $field) {
                    if (isset($input[$field])) {
                        $updateFields[] = "$field = :$field";
                        $params[":$field"] = $input[$field];
                    }
                }

                if (empty($updateFields)) {
                    throw new Exception("No fields to update");
                }

                $sql = "UPDATE damaged_items SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $stmt = $db->prepare($sql);

                if ($stmt->execute($params)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Damage report updated successfully'
                    ]);
                } else {
                    throw new Exception("Failed to update damage report");
                }
            }
            break;

        case 'details':
            if ($method === 'GET') {
                $id = $_GET['id'] ?? '';
                if (empty($id)) {
                    throw new Exception("Damage report ID is required");
                }

                $sql = "SELECT di.*, a.name as asset_name, a.category, a.location as asset_location,
                        a.purchase_date, a.purchase_cost, a.current_value
                        FROM damaged_items di
                        LEFT JOIN assets a ON di.asset_id = a.id
                        WHERE di.id = :id";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    // Add PDF URL
                    $item['pdf_url'] = 'generate_incident_pdf.php?id=' . $id;
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $item
                    ]);
                } else {
                    throw new Exception("Damage report not found");
                }
            }
            break;

        case 'stats':
            if ($method === 'GET') {
                $stats = [];

                // Total damaged items
                $totalSql = "SELECT COUNT(*) as total FROM damaged_items";
                $totalStmt = $db->prepare($totalSql);
                $totalStmt->execute();
                $stats['total_damaged'] = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Under repair
                $repairSql = "SELECT COUNT(*) as total FROM damaged_items WHERE status = 'under_repair'";
                $repairStmt = $db->prepare($repairSql);
                $repairStmt->execute();
                $stats['under_repair'] = $repairStmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Repaired items
                $repairedSql = "SELECT COUNT(*) as total FROM damaged_items WHERE status = 'repaired'";
                $repairedStmt = $db->prepare($repairedSql);
                $repairedStmt->execute();
                $stats['repaired'] = $repairedStmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Write-offs
                $writeoffSql = "SELECT COUNT(*) as total FROM damaged_items WHERE status = 'write_off'";
                $writeoffStmt = $db->prepare($writeoffSql);
                $writeoffStmt->execute();
                $stats['write_offs'] = $writeoffStmt->fetch(PDO::FETCH_ASSOC)['total'];

                // Total repair cost
                $costSql = "SELECT SUM(estimated_repair_cost) as total_cost FROM damaged_items WHERE status IN ('reported', 'under_repair')";
                $costStmt = $db->prepare($costSql);
                $costStmt->execute();
                $stats['total_repair_cost'] = $costStmt->fetch(PDO::FETCH_ASSOC)['total_cost'] ?? 0;

                echo json_encode([
                    'success' => true,
                    'data' => $stats
                ]);
            }
            break;

        case 'search_asset':
            if ($method === 'GET') {
                $asset_code = $_GET['asset_code'] ?? '';
                if (empty($asset_code)) {
                    throw new Exception("Asset code is required");
                }

                $sql = "SELECT id, asset_code, name, category, location, condition_status
                        FROM assets
                        WHERE asset_code = :asset_code AND status != 'disposed'";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':asset_code', $asset_code);
                $stmt->execute();
                $asset = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($asset) {
                    echo json_encode([
                        'success' => true,
                        'data' => $asset
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Asset not found'
                    ]);
                }
            }
            break;

        default:
            throw new Exception("Invalid action: '" . htmlspecialchars($action) . "'. Valid actions: list, create, update, details, stats, search_asset");
    }

} catch (PDOException $e) {
    error_log("[DAMAGED_ITEMS] Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $code = $e->getCode();
    http_response_code(($code >= 400 && $code < 600) ? $code : 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>