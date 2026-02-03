<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../includes/archive_helpers.php';

session_start();
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

ensureArchiveInfrastructure($db, 'supplies');

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
    $query = "SELECT st.*, s.name as supply_name, u1.full_name as requested_by_name, u2.full_name as approved_by_name
              FROM supply_transactions st
              JOIN supplies s ON st.supply_id = s.id
              LEFT JOIN users u1 ON st.requested_by = u1.id
              LEFT JOIN users u2 ON st.approved_by = u2.id
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
        $currentStock = isset($data->current_stock) ? (int)$data->current_stock : 0;
        $unitCost = isset($data->unit_cost) ? (float)$data->unit_cost : null;
        $totalValue = isset($data->total_value)
            ? (float)$data->total_value
            : ($unitCost !== null ? $currentStock * $unitCost : null);

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
            $data->location ?? null,
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
        $db->beginTransaction();

        try {
            // Insert transaction
            $query = "INSERT INTO supply_transactions (supply_id, transaction_type, quantity, reference_number, transaction_date, requested_by, approved_by, purpose, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $data->supply_id,
                $data->transaction_type,
                $data->quantity,
                $data->reference_number ?? null,
                $data->transaction_date ?? date('Y-m-d'),
                $_SESSION['user_id'],
                $data->approved_by ?? $_SESSION['user_id'],
                $data->purpose ?? null,
                $data->notes ?? null
            ]);

            $transaction_id = $db->lastInsertId();

            // Update supply stock
            if($data->transaction_type === 'in') {
                $query = "UPDATE supplies SET current_stock = current_stock + ? WHERE id = ?";
            } else {
                $query = "UPDATE supplies SET current_stock = current_stock - ? WHERE id = ?";
            }

            $stmt = $db->prepare($query);
            $stmt->execute([$data->quantity, $data->supply_id]);

            $db->commit();

            // Log the activity
            logActivity($db, $_SESSION['user_id'], 'create', 'supply_transactions', $transaction_id);

            http_response_code(201);
            echo json_encode(array("message" => "Transaction created successfully", "id" => $transaction_id));

        } catch(Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create transaction"));
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
        $data->location ?? null,
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

        $stmt = $db->prepare("SELECT id, item_code, name FROM supplies WHERE id = :id LIMIT 1");
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