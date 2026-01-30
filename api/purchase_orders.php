<?php
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

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/auth_check.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
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
            'tax_amount' => 0.0,
            'shipping_cost' => 0.0,
            'total_amount' => 0.0
        ];

        foreach ($rows as $row) {
            $pageTotals['subtotal'] += (float)($row['subtotal'] ?? 0);
            $pageTotals['tax_amount'] += (float)($row['tax_amount'] ?? 0);
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
        $taxAmount = isset($input['tax_amount']) ? (float)$input['tax_amount'] : 0.0;
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

        $totalAmount = $subtotal + $taxAmount + $shippingCost;

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
            echo json_encode(['error' => 'Missing purchase order ID']);
            return;
        }

        $sql = "SELECT po.*, pr.request_code, pr.department, pr.request_date,
                       creator.full_name AS created_by_name,
                       approver.full_name AS approved_by_name
                FROM purchase_orders po
                LEFT JOIN procurement_requests pr ON po.request_id = pr.id
                LEFT JOIN users creator ON po.created_by = creator.id
                LEFT JOIN users approver ON po.approved_by = approver.id
                WHERE po.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$po) {
            http_response_code(404);
            echo json_encode(['error' => 'Purchase order not found']);
            return;
        }

        $itemsSql = "SELECT poi.*, pri.item_name AS request_item_name
                     FROM purchase_order_items poi
                     LEFT JOIN procurement_request_items pri ON poi.request_item_id = pri.id
                     WHERE poi.purchase_order_id = :purchase_order_id
                     ORDER BY poi.id";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([':purchase_order_id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $po['items'] = $items;

        echo json_encode([
            'success' => true,
            'data' => $po
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch purchase order details: ' . $e->getMessage()]);
    }
}

function updatePurchaseOrder(PDO $pdo): void
{
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input or missing ID']);
            return;
        }

        $id = (int)$input['id'];
        $po = fetchPurchaseOrder($pdo, $id);
        if (!$po) {
            http_response_code(404);
            echo json_encode(['error' => 'Purchase order not found']);
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
            'tax_amount',
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

            $taxAmount = isset($input['tax_amount']) ? (float)$input['tax_amount'] : (float)$po['tax_amount'];
            $shippingCost = isset($input['shipping_cost']) ? (float)$input['shipping_cost'] : (float)$po['shipping_cost'];
            $totalAmount = $subtotal + $taxAmount + $shippingCost;

            $totalsSql = "UPDATE purchase_orders
                          SET subtotal = :subtotal,
                              tax_amount = :tax_amount,
                              shipping_cost = :shipping_cost,
                              total_amount = :total_amount,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = :id";
            $totalsStmt = $pdo->prepare($totalsSql);
            $totalsStmt->execute([
                ':subtotal' => $subtotal,
                ':tax_amount' => $taxAmount,
                ':shipping_cost' => $shippingCost,
                ':total_amount' => $totalAmount,
                ':id' => $id
            ]);
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
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update purchase order: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Purchase order not found']);
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
