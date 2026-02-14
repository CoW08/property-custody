<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
error_reporting(E_ALL);

ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header("Content-Type: application/json"); http_response_code(500); }
    error_log("[SUPPLIES] Uncaught: " . $e->getMessage());
    echo json_encode(["message" => "Server error: " . $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[SUPPLIES] PHP error ($severity): $message in $file:$line");
    return true;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        echo json_encode(['message' => 'Fatal server error']);
    }
});

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/archive_helpers.php';

ob_end_clean();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

if (!defined('STORAGE_LOCATION_OPTIONS')) {
    define('STORAGE_LOCATION_OPTIONS', [
        'Clinic Storage',
        'Library Storage',
        'Event Storage',
        'OSAS Storage'
    ]);
}

function sanitizeStorageLocation($rawLocation)
{
    $location = is_string($rawLocation) ? trim($rawLocation) : '';
    if ($location === '' || !in_array($location, STORAGE_LOCATION_OPTIONS, true)) {
        return null;
    }
    return $location;
}

$database = new Database();
$db = $database->getConnection();

ensureArchiveInfrastructure($db, 'supplies');

// Fix: ensure id columns have AUTO_INCREMENT (existing tables from SQL dump may lack it)
try {
    foreach (['supplies', 'supply_transactions'] as $tbl) {
        $colCheck = $db->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = '$tbl' 
                                AND COLUMN_NAME = 'id'")->fetch(PDO::FETCH_ASSOC);
        if ($colCheck && stripos($colCheck['EXTRA'], 'auto_increment') === false) {
            try { $db->exec("ALTER TABLE $tbl ADD PRIMARY KEY (id)"); } catch (Throwable $e) {}
            $db->exec("ALTER TABLE $tbl MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
            error_log("[SUPPLIES] Fixed $tbl.id AUTO_INCREMENT");
        }
    }
} catch (Throwable $e) {
    error_log("[SUPPLIES] ALTER id fix: " . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            getSupply($db, $_GET['id']);
        } elseif(isset($_GET['action']) && $_GET['action'] === 'transactions') {
            getTransactions($db);
        } elseif(isset($_GET['action']) && $_GET['action'] === 'low_stock') {
            getLowStockAlerts($db);
        } elseif(isset($_GET['action']) && $_GET['action'] === 'stats') {
            getSuppliesStats($db);
        } else {
            getSupplies($db);
        }
        break;
    case 'POST':
        if(isset($_GET['action']) && $_GET['action'] === 'transaction') {
            createTransaction($db);
        } else {
            createSupply($db);
        }
        break;
    case 'PUT':
        if(isset($_GET['id'])) {
            updateSupply($db, $_GET['id']);
        }
        break;
    case 'DELETE':
        if(isset($_GET['id'])) {
            archiveSupply($db, intval($_GET['id']));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getSupplies($db) {
    try {
        $query = "SELECT *,
                  CASE 
                    WHEN current_stock <= 0 THEN 'out_of_stock'
                    WHEN current_stock <= minimum_stock THEN 'low_stock'
                    ELSE 'normal'
                  END as stock_status
                  FROM supplies 
                  WHERE archived_at IS NULL
                  ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $supplies = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $supplies[] = $row;
        }

        http_response_code(200);
        echo json_encode($supplies);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Error loading supplies", "message" => $e->getMessage()));
    }
}

function getSupply($db, $id) {
    $query = "SELECT * FROM supplies WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $supply = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($supply);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Supply not found"));
    }
}

function getTransactions($db) {
    $query = "SELECT st.*, s.name as supply_name, u.full_name as created_by_name
              FROM supply_transactions st
              JOIN supplies s ON st.supply_id = s.id
              LEFT JOIN users u ON st.created_by = u.id
              ORDER BY st.created_at DESC
              LIMIT 50";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $transactions = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $transactions[] = $row;
    }

    http_response_code(200);
    echo json_encode($transactions);
}

function createSupply($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->item_code) && !empty($data->name)) {
        // Check for duplicate item_code
        $checkCode = $db->prepare("SELECT id FROM supplies WHERE item_code = ? AND archived_at IS NULL");
        $checkCode->execute([$data->item_code]);
        if ($checkCode->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(array("message" => "An item with this item code already exists. Please use a unique item code."));
            return;
        }

        // Check for duplicate name (warn if same name exists with different code)
        $checkName = $db->prepare("SELECT id, item_code FROM supplies WHERE LOWER(name) = LOWER(?) AND archived_at IS NULL");
        $checkName->execute([$data->name]);
        if ($checkName->rowCount() > 0) {
            $existing = $checkName->fetch(PDO::FETCH_ASSOC);
            http_response_code(409);
            echo json_encode(array("message" => "An item with the name '{$data->name}' already exists (Code: {$existing['item_code']}). Use a different name or edit the existing item."));
            return;
        }

        $currentStock = isset($data->current_stock) ? (int)$data->current_stock : 0;
        $unitCost = isset($data->unit_cost) ? (float)$data->unit_cost : null;
        $totalValue = isset($data->total_value)
            ? (float)$data->total_value
            : ($unitCost !== null ? $currentStock * $unitCost : null);

        $location = sanitizeStorageLocation($data->location ?? null);
        if ($location === null) {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid storage location"));
            return;
        }

        $query = "INSERT INTO supplies (item_code, name, description, category, unit, current_stock, minimum_stock, unit_cost, total_value, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        if($stmt->execute([
            $data->item_code,
            $data->name,
            $data->description ?? null,
            $data->category ?? null,
            $data->unit ?? 'pcs',
            $currentStock,
            isset($data->minimum_stock) ? (int)$data->minimum_stock : 0,
            $unitCost,
            $totalValue,
            $location,
            $data->status ?? 'active'
        ])) {
            $supply_id = $db->lastInsertId();

            // Log the activity
            logActivity($db, $_SESSION['user_id'], 'create', 'supplies', $supply_id);

            http_response_code(201);
            echo json_encode(array("message" => "Supply created successfully", "id" => $supply_id));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create supply"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Item code and name are required"));
    }
}

function createTransaction($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->supply_id) && !empty($data->transaction_type) && isset($data->quantity)) {
        // Ensure supply_transactions table exists
        $db->exec("CREATE TABLE IF NOT EXISTS supply_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supply_id INT NOT NULL,
            transaction_type VARCHAR(20) NOT NULL,
            quantity INT NOT NULL,
            unit_cost DECIMAL(12,2) NULL,
            total_cost DECIMAL(12,2) NULL,
            reference_number VARCHAR(100) NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_supply (supply_id),
            INDEX idx_type (transaction_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->beginTransaction();

        try {
            $quantity = (int)$data->quantity;
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero');
            }

            // For stock out, verify sufficient stock
            if ($data->transaction_type === 'out') {
                $checkStock = $db->prepare("SELECT current_stock FROM supplies WHERE id = ? AND archived_at IS NULL");
                $checkStock->execute([$data->supply_id]);
                $supply = $checkStock->fetch(PDO::FETCH_ASSOC);
                if (!$supply) {
                    throw new Exception('Supply item not found');
                }
                if ((int)$supply['current_stock'] < $quantity) {
                    throw new Exception('Insufficient stock. Current: ' . $supply['current_stock'] . ', Requested: ' . $quantity);
                }
            }

            $unitCost = isset($data->unit_cost) ? (float)$data->unit_cost : null;
            $totalCost = $unitCost !== null ? $unitCost * $quantity : null;

            $query = "INSERT INTO supply_transactions (supply_id, transaction_type, quantity, unit_cost, total_cost, reference_number, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data->supply_id,
                $data->transaction_type,
                $quantity,
                $unitCost,
                $totalCost,
                $data->reference_number ?? null,
                $data->notes ?? null,
                $_SESSION['user_id']
            ]);

            $transaction_id = $db->lastInsertId();

            // Update supply stock
            if($data->transaction_type === 'in') {
                $query = "UPDATE supplies SET current_stock = current_stock + ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            } else {
                $query = "UPDATE supplies SET current_stock = GREATEST(0, current_stock - ?), updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            }

            $stmt = $db->prepare($query);
            $stmt->execute([$quantity, $data->supply_id]);

            // Also update total_value
            $db->prepare("UPDATE supplies SET total_value = current_stock * COALESCE(unit_cost, 0) WHERE id = ?")->execute([$data->supply_id]);

            $db->commit();

            logActivity($db, $_SESSION['user_id'], 'create', 'supply_transactions', $transaction_id);

            http_response_code(201);
            echo json_encode(array("message" => "Transaction created successfully", "id" => $transaction_id));

        } catch(Exception $e) {
            $db->rollback();
            http_response_code(400);
            echo json_encode(array(
                "message" => $e->getMessage(),
                "error" => $e->getMessage()
            ));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Supply ID, transaction type and quantity are required"));
    }
}

function updateSupply($db, $id) {
    $data = json_decode(file_get_contents("php://input"));

    $currentStock = isset($data->current_stock) ? (int)$data->current_stock : null;
    $unitCost = isset($data->unit_cost) ? (float)$data->unit_cost : null;
    $totalValue = isset($data->total_value)
        ? (float)$data->total_value
        : (($currentStock !== null && $unitCost !== null) ? $currentStock * $unitCost : null);

    $location = sanitizeStorageLocation($data->location ?? null);
    if ($location === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid storage location"));
        return;
    }

    $query = "UPDATE supplies SET name = ?, description = ?, category = ?, unit = ?, current_stock = ?, minimum_stock = ?, unit_cost = ?, total_value = ?, location = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";

    $stmt = $db->prepare($query);

    if($stmt->execute([
        $data->name,
        $data->description ?? null,
        $data->category ?? null,
        $data->unit ?? 'pcs',
        $currentStock ?? 0,
        isset($data->minimum_stock) ? (int)$data->minimum_stock : 0,
        $unitCost,
        $totalValue,
        $location,
        $data->status ?? 'active',
        $id
    ])) {
        // Log the activity
        logActivity($db, $_SESSION['user_id'], 'update', 'supplies', $id);

        http_response_code(200);
        echo json_encode(array("message" => "Supply updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to update supply"));
    }
}

function archiveSupply(PDO $db, int $id): void
{
    ensureArchiveInfrastructure($db, 'supplies');

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM supplies WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $supply = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supply) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(array("message" => "Supply not found"));
            return;
        }

        $archiveData = [
            ':archived_at' => date('Y-m-d H:i:s'),
            ':archived_by' => $_SESSION['user_id'] ?? null,
            ':archive_reason' => null,
            ':archive_notes' => null,
            ':id' => $id,
        ];

        $updateSql = "UPDATE supplies SET archived_at = :archived_at, archived_by = :archived_by, archive_reason = :archive_reason, archive_notes = :archive_notes WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($archiveData);

        recordWasteEntry($db, 'supply', $id, [
            'name' => $supply['name'] ?? 'Supply #' . $id,
            'identifier' => $supply['item_code'] ?? null,
            'archived_at' => $archiveData[':archived_at'],
            'archived_by' => $archiveData[':archived_by'],
            'metadata' => $supply,
        ]);

        logActivity($db, $_SESSION['user_id'], 'archive', 'supplies', $id);

        $db->commit();

        http_response_code(200);
        echo json_encode(array("message" => "Supply archived successfully"));
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        http_response_code(500);
        echo json_encode(array("message" => "Failed to archive supply", "error" => $throwable->getMessage()));
    }
}

// Get low stock alerts
function getLowStockAlerts($db) {
    try {
        // Check if status column exists, if not, query without it
        $query = "SELECT *,
                  CASE 
                    WHEN current_stock <= 0 THEN 'out_of_stock'
                    WHEN current_stock <= minimum_stock THEN 'low_stock'
                    ELSE 'normal'
                  END as stock_status,
                  (minimum_stock - current_stock) as shortage_quantity
                  FROM supplies 
                  WHERE archived_at IS NULL AND current_stock <= minimum_stock
                  ORDER BY 
                    CASE 
                      WHEN current_stock <= 0 THEN 1
                      ELSE 2
                    END,
                    current_stock ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();

        $alerts = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[] = $row;
        }

        http_response_code(200);
        echo json_encode(array(
            'alerts' => $alerts,
            'count' => count($alerts)
        ));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Error loading low stock alerts", "message" => $e->getMessage()));
    }
}

// Get supplies statistics
function getSuppliesStats($db) {
    try {
        // Total supplies
        $totalQuery = "SELECT COUNT(*) as total FROM supplies WHERE archived_at IS NULL";
        $stmt = $db->prepare($totalQuery);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Low stock count
        $lowStockQuery = "SELECT COUNT(*) as low_stock FROM supplies WHERE archived_at IS NULL AND current_stock <= minimum_stock AND current_stock > 0";
        $stmt = $db->prepare($lowStockQuery);
        $stmt->execute();
        $lowStock = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];

        // Out of stock count
        $outStockQuery = "SELECT COUNT(*) as out_of_stock FROM supplies WHERE archived_at IS NULL AND current_stock <= 0";
        $stmt = $db->prepare($outStockQuery);
        $stmt->execute();
        $outOfStock = $stmt->fetch(PDO::FETCH_ASSOC)['out_of_stock'];

        // Total value
        $valueQuery = "SELECT SUM(current_stock * unit_cost) as total_value FROM supplies WHERE archived_at IS NULL";
        $stmt = $db->prepare($valueQuery);
        $stmt->execute();
        $totalValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;

        http_response_code(200);
        echo json_encode(array(
            'total_supplies' => intval($total),
            'low_stock_count' => intval($lowStock),
            'out_of_stock_count' => intval($outOfStock),
            'total_inventory_value' => floatval($totalValue)
        ));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("error" => "Error loading statistics", "message" => $e->getMessage()));
    }
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    try {
        $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $user_id,
            $action,
            $table_name,
            $record_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Throwable $th) {
        error_log('Failed to write system log entry: ' . $th->getMessage());
    }
}
?>
