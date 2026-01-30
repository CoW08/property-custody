<?php
// Set error reporting to only log errors, not display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once dirname(__DIR__) . '/config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For testing, set a dummy session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        case 'PUT':
            handlePut($db, $action);
            break;
        case 'DELETE':
            handleDelete($db, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'list':
            getMaintenanceList($db);
            break;
        case 'stats':
            getMaintenanceStats($db);
            break;
        case 'assets':
            getAssetsForMaintenance($db);
            break;
        case 'technicians':
            getTechnicians($db);
            break;
        case 'details':
            getMaintenanceDetails($db);
            break;
        default:
            getMaintenanceList($db);
    }
}

function handlePost($db, $action) {
    switch ($action) {
        case 'schedule':
            scheduleMaintenanceTask($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($db, $action) {
    switch ($action) {
        case 'update_status':
            updateMaintenanceStatus($db);
            break;
        case 'update':
            updateMaintenanceTask($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($db, $action) {
    switch ($action) {
        case 'cancel':
            cancelMaintenanceTask($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getMaintenanceList($db) {
    $query = "SELECT
                ms.id,
                ms.maintenance_type,
                ms.scheduled_date,
                ms.completed_date,
                ms.status,
                ms.priority,
                ms.description,
                ms.estimated_cost,
                ms.actual_cost,
                a.name as asset_name,
                a.asset_code,
                a.location as asset_location,
                u.full_name as assigned_technician
              FROM maintenance_schedules ms
              LEFT JOIN assets a ON ms.asset_id = a.id
              LEFT JOIN users u ON ms.assigned_to = u.id
              ORDER BY
                CASE ms.status
                    WHEN 'scheduled' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed' THEN 3
                    WHEN 'cancelled' THEN 4
                END,
                ms.scheduled_date ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['maintenances' => $maintenances]);
}

function getMaintenanceStats($db) {
    $stats = [];

    // Scheduled count
    $query = "SELECT COUNT(*) as count FROM maintenance_schedules WHERE status = 'scheduled'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['scheduled'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Due today count
    $query = "SELECT COUNT(*) as count FROM maintenance_schedules
              WHERE status = 'scheduled' AND scheduled_date = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['due_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Overdue count
    $query = "SELECT COUNT(*) as count FROM maintenance_schedules
              WHERE status = 'scheduled' AND scheduled_date < CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['overdue'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Completed count (this month)
    $query = "SELECT COUNT(*) as count FROM maintenance_schedules
              WHERE status = 'completed' AND MONTH(completed_date) = MONTH(CURDATE())
              AND YEAR(completed_date) = YEAR(CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode(['stats' => $stats]);
}

function getAssetsForMaintenance($db) {
    $query = "SELECT id, asset_code, name, location, status
              FROM assets
              WHERE status IN ('available', 'assigned', 'maintenance')
              ORDER BY name ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['assets' => $assets]);
}

function getTechnicians($db) {
    $query = "SELECT id, full_name, department
              FROM users
              WHERE role IN ('maintenance', 'admin') AND status = 'active'
              ORDER BY full_name ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['technicians' => $technicians]);
}

function scheduleMaintenanceTask($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    $required_fields = ['asset_id', 'maintenance_type', 'scheduled_date', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }

    $query = "INSERT INTO maintenance_schedules
              (asset_id, maintenance_type, scheduled_date, assigned_to, description,
               estimated_cost, priority, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')";

    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $input['asset_id'],
        $input['maintenance_type'],
        $input['scheduled_date'],
        $input['assigned_to'] ?? null,
        $input['description'],
        $input['estimated_cost'] ?? null,
        $input['priority'] ?? 'medium'
    ]);

    if ($result) {
        $maintenance_id = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Maintenance task scheduled successfully',
            'maintenance_id' => $maintenance_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to schedule maintenance task']);
    }
}

function updateMaintenanceStatus($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Maintenance ID and status are required']);
        return;
    }

    $query = "UPDATE maintenance_schedules SET status = ?";
    $params = [$input['status']];

    // If marking as completed, set completed_date
    if ($input['status'] === 'completed') {
        $query .= ", completed_date = CURDATE()";

        // If actual_cost is provided, update it
        if (isset($input['actual_cost'])) {
            $query .= ", actual_cost = ?";
            $params[] = $input['actual_cost'];
        }
    }

    $query .= " WHERE id = ?";
    $params[] = $input['id'];

    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Maintenance status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update maintenance status']);
    }
}

function cancelMaintenanceTask($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Maintenance ID is required']);
        return;
    }

    $query = "UPDATE maintenance_schedules SET status = 'cancelled' WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$input['id']]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Maintenance task cancelled successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to cancel maintenance task']);
    }
}
?>