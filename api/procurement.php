<?php
// Start session for user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listProcurementRequests($pdo);
        break;
    case 'create':
        createProcurementRequest($pdo);
        break;
    case 'update':
        updateProcurementRequest($pdo);
        break;
    case 'delete':
        deleteProcurementRequest($pdo);
        break;
    case 'details':
        getProcurementDetails($pdo);
        break;
    case 'approve':
        approveProcurementRequest($pdo);
        break;
    case 'reject':
        rejectProcurementRequest($pdo);
        break;
    case 'stats':
        getProcurementStats($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function listProcurementRequests($pdo) {
    try {
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $department = $_GET['department'] ?? '';
        $request_type = $_GET['request_type'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = intval($_GET['limit'] ?? ITEMS_PER_PAGE);
        $offset = ($page - 1) * $limit;

        $whereConditions = [];
        $params = [];

        if ($status) {
            $whereConditions[] = "pr.status = :status";
            $params[':status'] = $status;
        }

        if ($priority) {
            $whereConditions[] = "pr.priority = :priority";
            $params[':priority'] = $priority;
        }

        if ($department) {
            $whereConditions[] = "pr.department = :department";
            $params[':department'] = $department;
        }

        if ($request_type) {
            $whereConditions[] = "pr.request_type = :request_type";
            $params[':request_type'] = $request_type;
        }

        if ($search) {
            $whereConditions[] = "(pr.request_code LIKE :search OR pr.justification LIKE :search OR u.full_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $countSql = "SELECT COUNT(*) FROM procurement_requests pr
                     LEFT JOIN users u ON pr.requestor_id = u.id
                     $whereClause";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        $sql = "SELECT pr.*,
                       u.full_name as requestor_name,
                       u.department as requestor_department,
                       approver.full_name as approver_name,
                       COUNT(pri.id) as items_count,
                       SUM(pri.total_cost) as total_estimated_cost
                FROM procurement_requests pr
                LEFT JOIN users u ON pr.requestor_id = u.id
                LEFT JOIN users approver ON pr.approved_by = approver.id
                LEFT JOIN procurement_request_items pri ON pr.id = pri.request_id
                $whereClause
                GROUP BY pr.id
                ORDER BY pr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $requests,
            'pagination' => [
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalRecords / $limit)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch procurement requests: ' . $e->getMessage()]);
    }
}

function createProcurementRequest($pdo) {
    try {
        // Get current user from session (Auto-fetch requester information)
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

        // Validate user data integrity - ensure requestor_id matches session
        if (isset($input['requestor_id']) && $input['requestor_id'] != $currentUser['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Requestor ID mismatch. Security violation detected.']);
            return;
        }

        // Auto-populate requester information from session (Override any client input)
        $requestorId = $currentUser['id'];
        $department = $currentUser['department'];
        $requestorName = $currentUser['full_name'];
        $requestorEmail = $currentUser['email'];
        $requestorRole = $currentUser['role'];

        $requiredFields = ['request_type', 'request_date', 'justification', 'items'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        $pdo->beginTransaction();

        // Generate request code
        $requestCode = generateRequestCode($pdo, $input['request_type']);

        // Insert procurement request with auto-fetched user data
        $sql = "INSERT INTO procurement_requests (request_code, request_type, requestor_id, department,
                request_date, required_date, justification, estimated_cost, priority, notes, created_by)
                VALUES (:request_code, :request_type, :requestor_id, :department, :request_date,
                :required_date, :justification, :estimated_cost, :priority, :notes, :created_by)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':request_code' => $requestCode,
            ':request_type' => $input['request_type'],
            ':requestor_id' => $requestorId,  // Auto-fetched from session
            ':department' => $department,      // Auto-fetched from session
            ':request_date' => $input['request_date'],
            ':required_date' => $input['required_date'] ?? null,
            ':justification' => $input['justification'],
            ':estimated_cost' => $input['estimated_cost'] ?? 0,
            ':priority' => $input['priority'] ?? 'medium',
            ':notes' => $input['notes'] ?? null,
            ':created_by' => $requestorId      // Track who created the request
        ]);

        $requestId = $pdo->lastInsertId();

        // Log the request creation with full user details
        error_log("Procurement Request Created - ID: $requestId, Code: $requestCode, Requester: $requestorName ($requestorEmail), Department: $department, Role: $requestorRole");

        // Insert request items
        $itemSql = "INSERT INTO procurement_request_items (request_id, item_name, description,
                    quantity, unit, estimated_unit_cost, total_cost, specifications)
                    VALUES (:request_id, :item_name, :description, :quantity, :unit,
                    :estimated_unit_cost, :total_cost, :specifications)";

        $itemStmt = $pdo->prepare($itemSql);
        $totalEstimatedCost = 0;

        foreach ($input['items'] as $item) {
            $itemTotal = ($item['quantity'] ?? 1) * ($item['estimated_unit_cost'] ?? 0);
            $totalEstimatedCost += $itemTotal;

            $itemStmt->execute([
                ':request_id' => $requestId,
                ':item_name' => $item['item_name'],
                ':description' => $item['description'] ?? null,
                ':quantity' => $item['quantity'] ?? 1,
                ':unit' => $item['unit'] ?? 'piece',
                ':estimated_unit_cost' => $item['estimated_unit_cost'] ?? 0,
                ':total_cost' => $itemTotal,
                ':specifications' => $item['specifications'] ?? null
            ]);
        }

        // Update total estimated cost
        $updateSql = "UPDATE procurement_requests SET estimated_cost = :estimated_cost WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':estimated_cost' => $totalEstimatedCost, ':id' => $requestId]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Procurement request created successfully',
            'request_id' => $requestId,
            'request_code' => $requestCode
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create procurement request: ' . $e->getMessage()]);
    }
}

function updateProcurementRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input or missing ID']);
            return;
        }

        $id = $input['id'];

        // Check if request exists and is editable
        $checkSql = "SELECT status FROM procurement_requests WHERE id = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $id]);
        $request = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Procurement request not found']);
            return;
        }

        if (in_array($request['status'], ['approved', 'ordered', 'received'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot edit approved or processed requests']);
            return;
        }

        $pdo->beginTransaction();

        // Update procurement request
        $updateFields = [];
        $params = [':id' => $id];

        $allowedFields = ['request_type', 'department', 'required_date', 'justification', 'priority', 'notes'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }

        if (!empty($updateFields)) {
            $sql = "UPDATE procurement_requests SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // Update items if provided
        if (isset($input['items']) && is_array($input['items'])) {
            // Delete existing items
            $deleteItemsSql = "DELETE FROM procurement_request_items WHERE request_id = :request_id";
            $deleteStmt = $pdo->prepare($deleteItemsSql);
            $deleteStmt->execute([':request_id' => $id]);

            // Insert new items
            $itemSql = "INSERT INTO procurement_request_items (request_id, item_name, description,
                        quantity, unit, estimated_unit_cost, total_cost, specifications)
                        VALUES (:request_id, :item_name, :description, :quantity, :unit,
                        :estimated_unit_cost, :total_cost, :specifications)";

            $itemStmt = $pdo->prepare($itemSql);
            $totalEstimatedCost = 0;

            foreach ($input['items'] as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['estimated_unit_cost'] ?? 0);
                $totalEstimatedCost += $itemTotal;

                $itemStmt->execute([
                    ':request_id' => $id,
                    ':item_name' => $item['item_name'],
                    ':description' => $item['description'] ?? null,
                    ':quantity' => $item['quantity'] ?? 1,
                    ':unit' => $item['unit'] ?? 'piece',
                    ':estimated_unit_cost' => $item['estimated_unit_cost'] ?? 0,
                    ':total_cost' => $itemTotal,
                    ':specifications' => $item['specifications'] ?? null
                ]);
            }

            // Update total estimated cost
            $updateCostSql = "UPDATE procurement_requests SET estimated_cost = :estimated_cost WHERE id = :id";
            $updateCostStmt = $pdo->prepare($updateCostSql);
            $updateCostStmt->execute([':estimated_cost' => $totalEstimatedCost, ':id' => $id]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Procurement request updated successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update procurement request: ' . $e->getMessage()]);
    }
}

function deleteProcurementRequest($pdo) {
    try {
        $id = $_GET['id'] ?? '';

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing request ID']);
            return;
        }

        // Check if request exists and is deletable
        $checkSql = "SELECT status FROM procurement_requests WHERE id = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => $id]);
        $request = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Procurement request not found']);
            return;
        }

        if (in_array($request['status'], ['approved', 'ordered', 'received'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete approved or processed requests']);
            return;
        }

        $pdo->beginTransaction();

        // Delete request items first (foreign key constraint)
        $deleteItemsSql = "DELETE FROM procurement_request_items WHERE request_id = :id";
        $deleteItemsStmt = $pdo->prepare($deleteItemsSql);
        $deleteItemsStmt->execute([':id' => $id]);

        // Delete procurement request
        $deleteSql = "DELETE FROM procurement_requests WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([':id' => $id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Procurement request deleted successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete procurement request: ' . $e->getMessage()]);
    }
}

function getProcurementDetails($pdo) {
    try {
        $id = $_GET['id'] ?? '';

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing request ID']);
            return;
        }

        // Get request details with complete requester information
        $sql = "SELECT pr.*,
                       u.id as requestor_user_id,
                       u.full_name as requestor_name,
                       u.email as requestor_email,
                       u.role as requestor_role,
                       u.department as requestor_department,
                       u.status as requestor_status,
                       approver.full_name as approver_name,
                       approver.email as approver_email,
                       approver.role as approver_role
                FROM procurement_requests pr
                LEFT JOIN users u ON pr.requestor_id = u.id
                LEFT JOIN users approver ON pr.approved_by = approver.id
                WHERE pr.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Procurement request not found']);
            return;
        }

        // Get request items
        $itemsSql = "SELECT * FROM procurement_request_items WHERE request_id = :id ORDER BY id";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([':id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $request['items'] = $items;

        echo json_encode([
            'success' => true,
            'data' => $request
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch procurement details: ' . $e->getMessage()]);
    }
}

function approveProcurementRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['id']) || !isset($input['approved_by'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $sql = "UPDATE procurement_requests
                SET status = 'approved',
                    approved_by = :approved_by,
                    approval_date = CURDATE(),
                    approved_cost = :approved_cost,
                    notes = :notes
                WHERE id = :id AND status = 'submitted'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $input['id'],
            ':approved_by' => $input['approved_by'],
            ':approved_cost' => $input['approved_cost'] ?? null,
            ':notes' => $input['notes'] ?? null
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Procurement request approved successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Request not found or cannot be approved']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to approve procurement request: ' . $e->getMessage()]);
    }
}

function rejectProcurementRequest($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['id']) || !isset($input['approved_by'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $sql = "UPDATE procurement_requests
                SET status = 'rejected',
                    approved_by = :approved_by,
                    approval_date = CURDATE(),
                    notes = :notes
                WHERE id = :id AND status = 'submitted'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $input['id'],
            ':approved_by' => $input['approved_by'],
            ':notes' => $input['notes'] ?? null
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Procurement request rejected successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Request not found or cannot be rejected']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reject procurement request: ' . $e->getMessage()]);
    }
}

function getProcurementStats($pdo) {
    try {
        $sql = "SELECT
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_count,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                    COUNT(CASE WHEN status = 'ordered' THEN 1 END) as ordered_count,
                    COUNT(CASE WHEN status = 'received' THEN 1 END) as received_count,
                    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_count,
                    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_count,
                    SUM(estimated_cost) as total_estimated_cost,
                    SUM(approved_cost) as total_approved_cost,
                    AVG(estimated_cost) as avg_request_cost
                FROM procurement_requests";

        $stmt = $pdo->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get department breakdown
        $deptSql = "SELECT department, COUNT(*) as count, SUM(estimated_cost) as total_cost
                    FROM procurement_requests
                    GROUP BY department
                    ORDER BY count DESC";

        $deptStmt = $pdo->query($deptSql);
        $departmentStats = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get monthly trends
        $trendSql = "SELECT
                        DATE_FORMAT(request_date, '%Y-%m') as month,
                        COUNT(*) as count,
                        SUM(estimated_cost) as total_cost
                     FROM procurement_requests
                     WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                     ORDER BY month DESC";

        $trendStmt = $pdo->query($trendSql);
        $monthlyTrends = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'overall' => $stats,
                'by_department' => $departmentStats,
                'monthly_trends' => $monthlyTrends
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch procurement statistics: ' . $e->getMessage()]);
    }
}

function generateRequestCode($pdo, $requestType) {
    $prefix = strtoupper(substr($requestType, 0, 2));
    $year = date('Y');
    $month = date('m');

    // Get the next sequence number for this month
    $sql = "SELECT COUNT(*) + 1 as next_seq
            FROM procurement_requests
            WHERE request_code LIKE :pattern";

    $pattern = "$prefix-$year$month-%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pattern' => $pattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $sequence = str_pad($result['next_seq'], 3, '0', STR_PAD_LEFT);

    return "$prefix-$year$month-$sequence";
}
?>