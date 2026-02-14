<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
error_reporting(E_ALL);

ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
    error_log("[PURCHASE_ORDERS] Uncaught: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[PURCHASE_ORDERS] PHP error ($severity): $message in $file:$line");
    return true;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        error_log("[PURCHASE_ORDERS] Fatal: " . $error['message']);
        echo json_encode(['success' => false, 'error' => 'Fatal server error', 'message' => $error['message']]);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getPurchaseOrderStats(PDO $pdo): void
{
    try {
        $sql = "SELECT
                    COUNT(*) AS total_purchase_orders,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_count,
                    COUNT(CASE WHEN status = 'sent' THEN 1 END) AS sent_count,
                    COUNT(CASE WHEN status = 'partially_received' THEN 1 END) AS partially_received_count,
                    COUNT(CASE WHEN status = 'received' THEN 1 END) AS received_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled_count,
                    COALESCE(SUM(total_amount), 0) AS total_order_value
                FROM purchase_orders";

        $stmt = $pdo->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        echo json_encode([
            'success' => true,
            'data' => [
                'total_purchase_orders' => (int)($stats['total_purchase_orders'] ?? 0),
                'pending' => (int)($stats['pending_count'] ?? 0),
                'sent' => (int)($stats['sent_count'] ?? 0),
                'partially_received' => (int)($stats['partially_received_count'] ?? 0),
                'received' => (int)($stats['received_count'] ?? 0),
                'cancelled' => (int)($stats['cancelled_count'] ?? 0),
                'total_value' => (float)($stats['total_order_value'] ?? 0)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch purchase order stats: ' . $e->getMessage()]);
    }
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Flush any stray output from includes/function definitions
ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Ensure purchase_orders table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(50) NOT NULL,
        request_id INT NULL,
        vendor_name VARCHAR(255) NOT NULL,
        vendor_contact_name VARCHAR(255) NULL,
        vendor_email VARCHAR(255) NULL,
        vendor_phone VARCHAR(50) NULL,
        vendor_address TEXT NULL,
        order_date DATE NULL,
        expected_delivery_date DATE NULL,
        payment_terms VARCHAR(100) NULL,
        shipping_method VARCHAR(100) NULL,
        subtotal DECIMAL(12,2) DEFAULT 0,
        tax_amount DECIMAL(12,2) DEFAULT 0,
        shipping_cost DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(30) DEFAULT 'pending',
        notes TEXT NULL,
        created_by INT NULL,
        approved_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_po_number (po_number),
        INDEX idx_request (request_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Fix: ensure id columns have AUTO_INCREMENT (critical for existing tables)
    // This handles tables that were created without AUTO_INCREMENT
    try {
        // Check if purchase_orders.id already has auto_increment
        $colCheck = $pdo->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                                 WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                                 AND TABLE_NAME = 'purchase_orders' 
                                 AND COLUMN_NAME = 'id'")->fetch(PDO::FETCH_ASSOC);
        if ($colCheck && stripos($colCheck['EXTRA'], 'auto_increment') === false) {
            error_log("[PURCHASE_ORDERS] Fixing purchase_orders.id - missing AUTO_INCREMENT");
            // First ensure it's a primary key, then add auto_increment
            try { $pdo->exec("ALTER TABLE purchase_orders ADD PRIMARY KEY (id)"); } catch (Throwable $e) {}
            $pdo->exec("ALTER TABLE purchase_orders MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
            error_log("[PURCHASE_ORDERS] Fixed purchase_orders.id AUTO_INCREMENT");
        }
    } catch (Throwable $e) {
        error_log("[PURCHASE_ORDERS] ALTER purchase_orders.id failed: " . $e->getMessage());
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT NOT NULL,
        request_item_id INT NULL,
        item_name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        quantity INT DEFAULT 1,
        unit VARCHAR(50) NULL,
        unit_cost DECIMAL(12,2) DEFAULT 0,
        total_cost DECIMAL(12,2) DEFAULT 0,
        expected_delivery_date DATE NULL,
        status VARCHAR(30) DEFAULT 'pending',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_po (purchase_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    try {
        $colCheck2 = $pdo->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                                  AND TABLE_NAME = 'purchase_order_items' 
                                  AND COLUMN_NAME = 'id'")->fetch(PDO::FETCH_ASSOC);
        if ($colCheck2 && stripos($colCheck2['EXTRA'], 'auto_increment') === false) {
            try { $pdo->exec("ALTER TABLE purchase_order_items ADD PRIMARY KEY (id)"); } catch (Throwable $e) {}
            $pdo->exec("ALTER TABLE purchase_order_items MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        }
    } catch (Throwable $e) {
        error_log("[PURCHASE_ORDERS] ALTER purchase_order_items.id failed: " . $e->getMessage());
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listPurchaseOrders($pdo);
        break;
    case 'create':
        createPurchaseOrder($pdo);
        break;
    case 'details':
        getPurchaseOrderDetails($pdo);
        break;
    case 'update':
        updatePurchaseOrder($pdo);
        break;
    case 'delete':
        deletePurchaseOrder($pdo);
        break;
    case 'stats':
        getPurchaseOrderStats($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function listPurchaseOrders(PDO $pdo): void
{
    try {
        $status = $_GET['status'] ?? '';
        $requestId = $_GET['request_id'] ?? '';
        $search = $_GET['search'] ?? '';
        $orderDate = $_GET['order_date'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, (int)($_GET['limit'] ?? ITEMS_PER_PAGE));
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];

        if ($status !== '') {
            $conditions[] = 'po.status = :status';
            $params[':status'] = $status;
        }

        if ($requestId !== '') {
            $conditions[] = 'po.request_id = :request_id';
            $params[':request_id'] = $requestId;
        }

        if ($orderDate !== '') {
            $conditions[] = 'po.order_date = :order_date';
            $params[':order_date'] = $orderDate;
        }

        if ($search !== '') {
            $conditions[] = '(po.po_number LIKE :search OR po.vendor_name LIKE :search OR pr.request_code LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $countSql = "SELECT COUNT(*)
                      FROM purchase_orders po
                      LEFT JOIN procurement_requests pr ON po.request_id = pr.id
                      $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT po.*, pr.request_code, pr.department, pr.request_date,
                       creator.full_name AS created_by_name,
                       approver.full_name AS approved_by_name
                FROM purchase_orders po
                LEFT JOIN procurement_requests pr ON po.request_id = pr.id
                LEFT JOIN users creator ON po.created_by = creator.id
                LEFT JOIN users approver ON po.approved_by = approver.id
                $whereClause
                ORDER BY po.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate dataset-level totals (based on current page results)
        $pageTotals = [
            'subtotal' => 0.0,
            'shipping_cost' => 0.0,
            'total_amount' => 0.0
        ];

        foreach ($rows as $row) {
            $pageTotals['subtotal'] += (float)($row['subtotal'] ?? 0);
            $pageTotals['shipping_cost'] += (float)($row['shipping_cost'] ?? 0);
            $pageTotals['total_amount'] += (float)($row['total_amount'] ?? 0);
        }

        echo json_encode([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int)ceil($total / $limit)
            ],
            'totals' => $pageTotals
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to list purchase orders: ' . $e->getMessage()]);
    }
}

function createPurchaseOrder(PDO $pdo): void
{
    try {
        $currentUser = getCurrentUser();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            return;
        }

        $requiredFields = ['request_id', 'vendor_name', 'order_date'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        $requestId = (int)$input['request_id'];
        $request = fetchProcurementRequest($pdo, $requestId);
        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Procurement request not found']);
            return;
        }

        if (!in_array($request['status'], ['approved', 'ordered', 'received'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Purchase orders can only be generated from approved requests']);
            return;
        }

        $requestItems = fetchProcurementRequestItems($pdo, $requestId);
        if (empty($requestItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Procurement request has no items to generate a purchase order']);
            return;
        }

        $pdo->beginTransaction();

        $poNumber = generatePurchaseOrderNumber($pdo);
        $itemsInput = is_array($input['items'] ?? null) ? $input['items'] : [];
        $taxAmount = 0.0;
        $shippingCost = isset($input['shipping_cost']) ? (float)$input['shipping_cost'] : 0.0;

        $itemsToPersist = [];
        $subtotal = 0.0;

        if (!empty($itemsInput)) {
            foreach ($itemsInput as $rawItem) {
                if (!isset($rawItem['item_name'])) {
                    continue;
                }

                $quantity = max(1, (int)($rawItem['quantity'] ?? 1));
                $unitCost = (float)($rawItem['unit_cost'] ?? 0);
                $totalCost = $rawItem['total_cost'] ?? ($quantity * $unitCost);
                $totalCost = (float)$totalCost;

                $subtotal += $totalCost;

                $itemsToPersist[] = [
                    'request_item_id' => isset($rawItem['request_item_id']) ? (int)$rawItem['request_item_id'] : null,
                    'item_name' => $rawItem['item_name'],
                    'description' => $rawItem['description'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $rawItem['unit'] ?? null,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'expected_delivery_date' => $rawItem['expected_delivery_date'] ?? null,
                    'status' => $rawItem['status'] ?? 'pending',
                    'notes' => $rawItem['notes'] ?? null
                ];
            }
        } else {
            // Auto-populate from procurement request items
            foreach ($requestItems as $requestItem) {
                $quantity = (int)$requestItem['quantity'];
                $unitCost = (float)($requestItem['estimated_unit_cost'] ?? 0);
                $totalCost = (float)($requestItem['total_cost'] ?? ($quantity * $unitCost));

                $subtotal += $totalCost;

                $itemsToPersist[] = [
                    'request_item_id' => (int)$requestItem['id'],
                    'item_name' => $requestItem['item_name'],
                    'description' => $requestItem['description'],
                    'quantity' => $quantity,
                    'unit' => $requestItem['unit'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'expected_delivery_date' => $input['expected_delivery_date'] ?? null,
                    'status' => 'pending',
                    'notes' => null
                ];
            }
        }

        if ($subtotal === 0.0 && isset($input['subtotal'])) {
            $subtotal = (float)$input['subtotal'];
        }

        $totalAmount = $subtotal + $shippingCost;

        $insertSql = "INSERT INTO purchase_orders (
                            po_number,
                            request_id,
                            vendor_name,
                            vendor_contact_name,
                            vendor_email,
                            vendor_phone,
                            vendor_address,
                            order_date,
                            expected_delivery_date,
                            payment_terms,
                            shipping_method,
                            subtotal,
                            tax_amount,
                            shipping_cost,
                            total_amount,
                            status,
                            notes,
                            created_by,
                            approved_by
                        ) VALUES (
                            :po_number,
                            :request_id,
                            :vendor_name,
                            :vendor_contact_name,
                            :vendor_email,
                            :vendor_phone,
                            :vendor_address,
                            :order_date,
                            :expected_delivery_date,
                            :payment_terms,
                            :shipping_method,
                            :subtotal,
                            :tax_amount,
                            :shipping_cost,
                            :total_amount,
                            :status,
                            :notes,
                            :created_by,
                            :approved_by
                        )";

        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':po_number' => $poNumber,
            ':request_id' => $requestId,
            ':vendor_name' => $input['vendor_name'],
            ':vendor_contact_name' => $input['vendor_contact_name'] ?? null,
            ':vendor_email' => $input['vendor_email'] ?? null,
            ':vendor_phone' => $input['vendor_phone'] ?? null,
            ':vendor_address' => $input['vendor_address'] ?? null,
            ':order_date' => $input['order_date'],
            ':expected_delivery_date' => $input['expected_delivery_date'] ?? null,
            ':payment_terms' => $input['payment_terms'] ?? null,
            ':shipping_method' => $input['shipping_method'] ?? null,
            ':subtotal' => $subtotal,
            ':tax_amount' => $taxAmount,
            ':shipping_cost' => $shippingCost,
            ':total_amount' => $totalAmount,
            ':status' => $input['status'] ?? 'pending',
            ':notes' => $input['notes'] ?? null,
            ':created_by' => $currentUser['id'] ?? null,
            ':approved_by' => $input['approved_by'] ?? $request['approved_by'] ?? null
        ]);

        $purchaseOrderId = (int)$pdo->lastInsertId();

        $itemSql = "INSERT INTO purchase_order_items (
                        purchase_order_id,
                        request_item_id,
                        item_name,
                        description,
                        quantity,
                        unit,
                        unit_cost,
                        total_cost,
                        expected_delivery_date,
                        status,
                        notes
                    ) VALUES (
                        :purchase_order_id,
                        :request_item_id,
                        :item_name,
                        :description,
                        :quantity,
                        :unit,
                        :unit_cost,
                        :total_cost,
                        :expected_delivery_date,
                        :status,
                        :notes
                    )";
        $itemStmt = $pdo->prepare($itemSql);

        foreach ($itemsToPersist as $item) {
            $itemStmt->execute([
                ':purchase_order_id' => $purchaseOrderId,
                ':request_item_id' => $item['request_item_id'],
                ':item_name' => $item['item_name'],
                ':description' => $item['description'],
                ':quantity' => $item['quantity'],
                ':unit' => $item['unit'],
                ':unit_cost' => $item['unit_cost'],
                ':total_cost' => $item['total_cost'],
                ':expected_delivery_date' => $item['expected_delivery_date'],
                ':status' => $item['status'],
                ':notes' => $item['notes']
            ]);
        }

        if ($request['status'] === 'approved') {
            $updateRequestSql = "UPDATE procurement_requests SET status = 'ordered' WHERE id = :id";
            $updateRequestStmt = $pdo->prepare($updateRequestSql);
            $updateRequestStmt->execute([':id' => $requestId]);
        }

        $newStatus = $input['status'] ?? 'pending';
        if ($newStatus === 'received') {
            applyPurchaseOrderReceipt($pdo, $purchaseOrderId, $poNumber, $requestId, $currentUser['id'] ?? null);
            $requestStatusSql = "UPDATE procurement_requests SET status = 'received' WHERE id = :id";
            $requestStatusStmt = $pdo->prepare($requestStatusSql);
            $requestStatusStmt->execute([':id' => $requestId]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase order created successfully',
            'purchase_order_id' => $purchaseOrderId,
            'po_number' => $poNumber
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create purchase order: ' . $e->getMessage()]);
    }
}

function getPurchaseOrderDetails(PDO $pdo): void
{
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing purchase order ID']);
            return;
        }

        // Check if procurement_requests table exists for the JOIN
        $hasProcRequests = false;
        try {
            $pdo->query("SELECT 1 FROM procurement_requests LIMIT 0");
            $hasProcRequests = true;
        } catch (Throwable $e) {}

        if ($hasProcRequests) {
            $sql = "SELECT po.*, pr.request_code, pr.department, pr.request_date,
                           creator.full_name AS created_by_name,
                           approver.full_name AS approved_by_name
                    FROM purchase_orders po
                    LEFT JOIN procurement_requests pr ON po.request_id = pr.id
                    LEFT JOIN users creator ON po.created_by = creator.id
                    LEFT JOIN users approver ON po.approved_by = approver.id
                    WHERE po.id = :id";
        } else {
            $sql = "SELECT po.*,
                           creator.full_name AS created_by_name,
                           approver.full_name AS approved_by_name
                    FROM purchase_orders po
                    LEFT JOIN users creator ON po.created_by = creator.id
                    LEFT JOIN users approver ON po.approved_by = approver.id
                    WHERE po.id = :id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$po) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
            return;
        }

        // Check if purchase_order_items table exists
        $items = [];
        try {
            $hasProcItems = false;
            try {
                $pdo->query("SELECT 1 FROM procurement_request_items LIMIT 0");
                $hasProcItems = true;
            } catch (Throwable $e) {}

            if ($hasProcItems) {
                $itemsSql = "SELECT poi.*, pri.item_name AS request_item_name
                             FROM purchase_order_items poi
                             LEFT JOIN procurement_request_items pri ON poi.request_item_id = pri.id
                             WHERE poi.purchase_order_id = :purchase_order_id
                             ORDER BY poi.id";
            } else {
                $itemsSql = "SELECT poi.*
                             FROM purchase_order_items poi
                             WHERE poi.purchase_order_id = :purchase_order_id
                             ORDER BY poi.id";
            }
            $itemsStmt = $pdo->prepare($itemsSql);
            $itemsStmt->execute([':purchase_order_id' => $id]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("[PURCHASE_ORDERS] Items query failed: " . $e->getMessage());
        }

        $po['items'] = $items;

        echo json_encode([
            'success' => true,
            'data' => $po
        ]);
    } catch (Exception $e) {
        error_log("[PURCHASE_ORDERS] getPurchaseOrderDetails error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch purchase order details: ' . $e->getMessage()]);
    }
}

function updatePurchaseOrder(PDO $pdo): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input or missing ID']);
            return;
        }

        $id = (int)$input['id'];
        $po = fetchPurchaseOrder($pdo, $id);
        if (!$po) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
            return;
        }

        $pdo->beginTransaction();

        $allowedFields = [
            'vendor_name',
            'vendor_contact_name',
            'vendor_email',
            'vendor_phone',
            'vendor_address',
            'order_date',
            'expected_delivery_date',
            'payment_terms',
            'shipping_method',
            'subtotal',
            'shipping_cost',
            'total_amount',
            'status',
            'notes',
            'approved_by'
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }

        if (!empty($updateFields)) {
            $sql = 'UPDATE purchase_orders SET ' . implode(', ', $updateFields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        $subtotal = null;
        if (isset($input['items']) && is_array($input['items'])) {
            $deleteSql = 'DELETE FROM purchase_order_items WHERE purchase_order_id = :id';
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':id' => $id]);

            $subtotal = 0.0;
            $itemSql = "INSERT INTO purchase_order_items (
                            purchase_order_id,
                            request_item_id,
                            item_name,
                            description,
                            quantity,
                            unit,
                            unit_cost,
                            total_cost,
                            expected_delivery_date,
                            status,
                            notes
                        ) VALUES (
                            :purchase_order_id,
                            :request_item_id,
                            :item_name,
                            :description,
                            :quantity,
                            :unit,
                            :unit_cost,
                            :total_cost,
                            :expected_delivery_date,
                            :status,
                            :notes
                        )";
            $itemStmt = $pdo->prepare($itemSql);

            foreach ($input['items'] as $rawItem) {
                if (!isset($rawItem['item_name'])) {
                    continue;
                }

                $quantity = max(1, (int)($rawItem['quantity'] ?? 1));
                $unitCost = (float)($rawItem['unit_cost'] ?? 0);
                $totalCost = (float)($rawItem['total_cost'] ?? ($quantity * $unitCost));
                $subtotal += $totalCost;

                $itemStmt->execute([
                    ':purchase_order_id' => $id,
                    ':request_item_id' => isset($rawItem['request_item_id']) ? (int)$rawItem['request_item_id'] : null,
                    ':item_name' => $rawItem['item_name'],
                    ':description' => $rawItem['description'] ?? null,
                    ':quantity' => $quantity,
                    ':unit' => $rawItem['unit'] ?? null,
                    ':unit_cost' => $unitCost,
                    ':total_cost' => $totalCost,
                    ':expected_delivery_date' => $rawItem['expected_delivery_date'] ?? null,
                    ':status' => $rawItem['status'] ?? 'pending',
                    ':notes' => $rawItem['notes'] ?? null
                ]);
            }

        }

        $shouldRecalculate = $subtotal !== null
            || array_key_exists('subtotal', $input)
            || array_key_exists('shipping_cost', $input);

        if ($shouldRecalculate) {
            $taxAmount = 0.0;
            $finalSubtotal = $subtotal ?? (array_key_exists('subtotal', $input) ? (float)$input['subtotal'] : (float)$po['subtotal']);
            $shippingCost = array_key_exists('shipping_cost', $input) ? (float)$input['shipping_cost'] : (float)$po['shipping_cost'];
            $totalAmount = $finalSubtotal + $shippingCost;

            $totalsSql = "UPDATE purchase_orders
                          SET subtotal = :subtotal,
                              tax_amount = :tax_amount,
                              shipping_cost = :shipping_cost,
                              total_amount = :total_amount,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = :id";
            $totalsStmt = $pdo->prepare($totalsSql);
            $totalsStmt->execute([
                ':subtotal' => $finalSubtotal,
                ':tax_amount' => $taxAmount,
                ':shipping_cost' => $shippingCost,
                ':total_amount' => $totalAmount,
                ':id' => $id
            ]);
        }

        $newStatus = $input['status'] ?? $po['status'];
        if ($po['status'] !== 'received' && $newStatus === 'received') {
            try {
                applyPurchaseOrderReceipt($pdo, $id, $po['po_number'], (int)($po['request_id'] ?? 0), $_SESSION['user_id'] ?? null);
            } catch (Throwable $e) {
                error_log("[PURCHASE_ORDERS] applyPurchaseOrderReceipt failed: " . $e->getMessage());
            }
            try {
                if (!empty($po['request_id'])) {
                    $requestStatusSql = "UPDATE procurement_requests SET status = 'received' WHERE id = :id";
                    $requestStatusStmt = $pdo->prepare($requestStatusSql);
                    $requestStatusStmt->execute([':id' => $po['request_id']]);
                }
            } catch (Throwable $e) {
                error_log("[PURCHASE_ORDERS] Update procurement_request status failed: " . $e->getMessage());
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase order updated successfully'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[PURCHASE_ORDERS] updatePurchaseOrder error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update purchase order: ' . $e->getMessage()]);
    }
}

function deletePurchaseOrder(PDO $pdo): void
{
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing purchase order ID']);
            return;
        }

        $po = fetchPurchaseOrder($pdo, $id);
        if (!$po) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
            return;
        }

        if (in_array($po['status'], ['received', 'partially_received'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete a purchase order that has received items']);
            return;
        }

        $pdo->beginTransaction();

        $deleteItemsSql = 'DELETE FROM purchase_order_items WHERE purchase_order_id = :id';
        $deleteItemsStmt = $pdo->prepare($deleteItemsSql);
        $deleteItemsStmt->execute([':id' => $id]);

        $deletePoSql = 'DELETE FROM purchase_orders WHERE id = :id';
        $deletePoStmt = $pdo->prepare($deletePoSql);
        $deletePoStmt->execute([':id' => $id]);

        // Optionally reset procurement request status if no remaining purchase orders exist
        $remainingSql = 'SELECT COUNT(*) FROM purchase_orders WHERE request_id = :request_id';
        $remainingStmt = $pdo->prepare($remainingSql);
        $remainingStmt->execute([':request_id' => $po['request_id']]);
        $remaining = (int)$remainingStmt->fetchColumn();

        if ($remaining === 0) {
            $resetSql = "UPDATE procurement_requests SET status = 'approved' WHERE id = :id AND status = 'ordered'";
            $resetStmt = $pdo->prepare($resetSql);
            $resetStmt->execute([':id' => $po['request_id']]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase order deleted successfully'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete purchase order: ' . $e->getMessage()]);
    }
}

function fetchProcurementRequest(PDO $pdo, int $id): ?array
{
    $sql = 'SELECT * FROM procurement_requests WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function fetchProcurementRequestItems(PDO $pdo, int $requestId): array
{
    $sql = 'SELECT * FROM procurement_request_items WHERE request_id = :id ORDER BY id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $requestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchPurchaseOrder(PDO $pdo, int $id): ?array
{
    $sql = 'SELECT * FROM purchase_orders WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function fetchPurchaseOrderItems(PDO $pdo, int $purchaseOrderId): array
{
    $sql = 'SELECT * FROM purchase_order_items WHERE purchase_order_id = :id ORDER BY id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $purchaseOrderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mapStorageLocation(?string $department): string
{
    $value = strtolower(trim((string)$department));
    if ($value !== '') {
        if (stripos($value, 'clinic') !== false) {
            return 'Clinic Storage';
        }
        if (stripos($value, 'library') !== false) {
            return 'Library Storage';
        }
        if (stripos($value, 'osas') !== false) {
            return 'OSAS Storage';
        }
        if (stripos($value, 'event') !== false) {
            return 'Event Storage';
        }
    }

    return 'Event Storage';
}

function generateSupplyCode(string $poNumber, int $index): string
{
    $suffix = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    return 'PO-' . preg_replace('/[^A-Z0-9-]+/', '', strtoupper($poNumber)) . '-' . $suffix;
}

function applyPurchaseOrderReceipt(PDO $pdo, int $purchaseOrderId, string $poNumber, int $requestId, ?int $userId): void
{
    $existingSql = "SELECT COUNT(*) FROM supply_transactions WHERE reference_number = :ref AND transaction_type = 'in'";
    $existingStmt = $pdo->prepare($existingSql);
    $existingStmt->execute([':ref' => $poNumber]);
    if ((int)$existingStmt->fetchColumn() > 0) {
        return;
    }

    $request = fetchProcurementRequest($pdo, $requestId);
    $location = mapStorageLocation($request['department'] ?? null);
    $items = fetchPurchaseOrderItems($pdo, $purchaseOrderId);

    if (empty($items)) {
        return;
    }

    $findSupplySql = "SELECT id, current_stock, unit_cost FROM supplies WHERE archived_at IS NULL AND LOWER(name) = LOWER(:name) LIMIT 1";
    $findSupplyStmt = $pdo->prepare($findSupplySql);
    $updateSupplySql = "UPDATE supplies SET current_stock = :current_stock, unit_cost = :unit_cost, total_value = :total_value, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $updateSupplyStmt = $pdo->prepare($updateSupplySql);

    $insertSupplySql = "INSERT INTO supplies (item_code, name, description, category, unit, current_stock, minimum_stock, unit_cost, total_value, location, status)
                        VALUES (:item_code, :name, :description, :category, :unit, :current_stock, :minimum_stock, :unit_cost, :total_value, :location, :status)";
    $insertSupplyStmt = $pdo->prepare($insertSupplySql);

    $transactionSql = "INSERT INTO supply_transactions (supply_id, transaction_type, quantity, unit_cost, total_cost, reference_number, notes, created_by)
                       VALUES (:supply_id, 'in', :quantity, :unit_cost, :total_cost, :reference_number, :notes, :created_by)";
    $transactionStmt = $pdo->prepare($transactionSql);

    foreach ($items as $index => $item) {
        $name = trim((string)($item['item_name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $quantity = max(1, (int)($item['quantity'] ?? 1));
        $unitCost = (float)($item['unit_cost'] ?? 0);
        $unit = $item['unit'] ?? 'pcs';
        $description = $item['description'] ?? null;

        $findSupplyStmt->execute([':name' => $name]);
        $existing = $findSupplyStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $currentStock = (int)$existing['current_stock'] + $quantity;
            $finalUnitCost = $unitCost > 0 ? $unitCost : (float)$existing['unit_cost'];
            $totalValue = $finalUnitCost > 0 ? $currentStock * $finalUnitCost : 0;
            $updateSupplyStmt->execute([
                ':current_stock' => $currentStock,
                ':unit_cost' => $finalUnitCost,
                ':total_value' => $totalValue,
                ':id' => $existing['id']
            ]);
            $supplyId = (int)$existing['id'];
        } else {
            $itemCode = generateSupplyCode($poNumber, $index);
            $totalValue = $unitCost > 0 ? $quantity * $unitCost : 0;
            $insertSupplyStmt->execute([
                ':item_code' => $itemCode,
                ':name' => $name,
                ':description' => $description,
                ':category' => null,
                ':unit' => $unit,
                ':current_stock' => $quantity,
                ':minimum_stock' => 1,
                ':unit_cost' => $unitCost > 0 ? $unitCost : 0,
                ':total_value' => $totalValue,
                ':location' => $location,
                ':status' => 'active'
            ]);
            $supplyId = (int)$pdo->lastInsertId();
        }

        $transactionStmt->execute([
            ':supply_id' => $supplyId,
            ':quantity' => $quantity,
            ':unit_cost' => $unitCost > 0 ? $unitCost : ($existing ? (float)$existing['unit_cost'] : 0),
            ':total_cost' => ($unitCost > 0 ? $unitCost : ($existing ? (float)$existing['unit_cost'] : 0)) * $quantity,
            ':reference_number' => $poNumber,
            ':notes' => 'Received via PO ' . $poNumber,
            ':created_by' => $userId
        ]);
    }
}

function generatePurchaseOrderNumber(PDO $pdo): string
{
    $prefix = 'PO';
    $yearMonth = date('Ymd');
    $pattern = $prefix . '-' . $yearMonth . '-%';

    $sql = "SELECT COUNT(*) + 1 AS next_seq
            FROM purchase_orders
            WHERE po_number LIKE :pattern";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pattern' => $pattern]);
    $sequence = (int)$stmt->fetchColumn();

    $sequenceFormatted = str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);

    return $prefix . '-' . $yearMonth . '-' . $sequenceFormatted;
}
