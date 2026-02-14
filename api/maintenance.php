<?php
// Set error reporting to only log errors, not display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch ANY stray output from includes
ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
    error_log("[MAINTENANCE] Uncaught: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[MAINTENANCE] PHP error ($severity): $message in $file:$line");
    return true; // suppress output
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        error_log("[MAINTENANCE] Fatal: " . $error['message']);
        echo json_encode(['error' => 'Fatal server error', 'message' => $error['message']]);
    }
});

// Load config for DB constants
require_once dirname(__DIR__) . '/config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dummy session for development/testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
}

// Flush any stray output from includes, then set headers
ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inline PDO connection (no Database class dependency)
try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log("[MAINTENANCE] DB connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}

// Seed default maintenance technicians if they don't exist
try {
    $technicianSeeds = [
        ['Mark Anthony Solis', 'Electrical', 'msolis@school.edu'],
        ['Renato Castillo', 'Electrical', 'rcastillo@school.edu'],
        ['John Mendoza', 'HVAC', 'jmendoza@school.edu'],
        ['Jayson Rivera', 'HVAC', 'jrivera@school.edu'],
        ['Grace Tolentino', 'IT/Networking', 'gtolentino@school.edu'],
        ['Marlon Ramos', 'IT/Networking', 'mramos@school.edu'],
    ];
    $checkStmt = $db->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'maintenance' LIMIT 1");
    $insertStmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, department, status) VALUES (?, ?, ?, ?, 'maintenance', ?, 'active')");
    foreach ($technicianSeeds as $tech) {
        $checkStmt->execute([$tech[0]]);
        if (!$checkStmt->fetch()) {
            $username = strtolower(str_replace(' ', '.', $tech[0]));
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $insertStmt->execute([$username, $password, $tech[0], $tech[2], $tech[1]]);
        }
    }
} catch (Throwable $e) {
    error_log("[MAINTENANCE] Technician seed: " . $e->getMessage());
}

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
        case 'alerts':
            getMaintenanceAlerts($db);
            break;
        case 'upcoming':
            getUpcomingMaintenance($db);
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

    // Scheduled tasks (future or overdue but not started)
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules WHERE status = 'scheduled'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['scheduled'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Pending tasks (in progress)
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules WHERE status = 'in_progress'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Critical issues (high priority awaiting action)
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules
              WHERE status IN ('scheduled','in_progress') AND priority IN ('high','critical')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['critical'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Completed tasks (all time)
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules WHERE status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['completed'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Due today
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules
              WHERE status IN ('scheduled','in_progress') AND scheduled_date = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['due_today'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Overdue tasks
    $query = "SELECT COUNT(*) AS count FROM maintenance_schedules
              WHERE status IN ('scheduled','in_progress') AND scheduled_date < CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['overdue'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Preventive maintenance snapshot
    $query = "SELECT
                SUM(CASE WHEN maintenance_type = 'preventive' AND status IN ('scheduled','in_progress') THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN maintenance_type = 'preventive' AND status IN ('scheduled','in_progress') AND scheduled_date = CURDATE() THEN 1 ELSE 0 END) AS due_today,
                SUM(CASE WHEN maintenance_type = 'preventive' AND status IN ('scheduled','in_progress') AND scheduled_date < CURDATE() THEN 1 ELSE 0 END) AS overdue
              FROM maintenance_schedules";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $preventiveStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['preventive_active'] = (int)($preventiveStats['active'] ?? 0);
    $stats['preventive_due_today'] = (int)($preventiveStats['due_today'] ?? 0);
    $stats['preventive_overdue'] = (int)($preventiveStats['overdue'] ?? 0);

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
    try {
        // Only return users with 'maintenance' role — these are the actual technicians
        $query = "SELECT id, full_name, department
                  FROM users
                  WHERE role = 'maintenance' AND status = 'active'
                  ORDER BY full_name ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['technicians' => $technicians]);
    } catch (Exception $e) {
        error_log("[MAINTENANCE] getTechnicians error: " . $e->getMessage());
        echo json_encode(['technicians' => [], 'error' => $e->getMessage()]);
    }
}

function getMaintenanceDetails($db) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Maintenance ID is required']);
        return;
    }

    $query = "SELECT
                ms.*,
                a.name as asset_name,
                a.asset_code,
                a.location as asset_location,
                a.category,
                u.full_name as assigned_technician
              FROM maintenance_schedules ms
              LEFT JOIN assets a ON ms.asset_id = a.id
              LEFT JOIN users u ON ms.assigned_to = u.id
              WHERE ms.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$maintenance) {
        http_response_code(404);
        echo json_encode(['error' => 'Maintenance task not found']);
        return;
    }

    // Add PDF URL
    $maintenance['pdf_url'] = 'generate_maintenance_pdf.php?id=' . $id;

    echo json_encode(['maintenance' => $maintenance]);
}

// Get maintenance alerts (overdue and due soon)
function getMaintenanceAlerts($db) {
    $query = "SELECT
                ms.id,
                ms.maintenance_type,
                ms.scheduled_date,
                ms.priority,
                ms.description,
                a.name as asset_name,
                a.asset_code,
                a.location as asset_location,
                u.full_name as assigned_technician,
                DATEDIFF(ms.scheduled_date, CURDATE()) as days_until_due,
                CASE
                    WHEN ms.scheduled_date < CURDATE() THEN 'overdue'
                    WHEN ms.scheduled_date = CURDATE() THEN 'due_today'
                    WHEN ms.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'scheduled'
                END as alert_status
              FROM maintenance_schedules ms
              LEFT JOIN assets a ON ms.asset_id = a.id
              LEFT JOIN users u ON ms.assigned_to = u.id
              WHERE ms.status = 'scheduled'
              AND ms.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              ORDER BY ms.scheduled_date ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'alerts' => $alerts,
        'count' => count($alerts)
    ]);
}

// Get upcoming maintenance (next 30 days)
function getUpcomingMaintenance($db) {
    $query = "SELECT
                ms.id,
                ms.maintenance_type,
                ms.scheduled_date,
                ms.priority,
                ms.description,
                a.name as asset_name,
                a.asset_code,
                u.full_name as assigned_technician
              FROM maintenance_schedules ms
              LEFT JOIN assets a ON ms.asset_id = a.id
              LEFT JOIN users u ON ms.assigned_to = u.id
              WHERE ms.status = 'scheduled'
              AND ms.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              ORDER BY ms.scheduled_date ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'upcoming' => $upcoming,
        'count' => count($upcoming)
    ]);
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

    try {
        // Ensure maintenance_schedules table exists
        $db->exec("CREATE TABLE IF NOT EXISTS maintenance_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            maintenance_type VARCHAR(50) NOT NULL,
            scheduled_date DATE NOT NULL,
            completed_date DATE NULL,
            assigned_to INT NULL,
            description TEXT,
            estimated_cost DECIMAL(12,2) NULL,
            actual_cost DECIMAL(12,2) NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(30) DEFAULT 'scheduled',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_asset (asset_id),
            INDEX idx_status (status),
            INDEX idx_date (scheduled_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $query = "INSERT INTO maintenance_schedules
                  (asset_id, maintenance_type, scheduled_date, assigned_to, description,
                   estimated_cost, priority, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')";

        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['asset_id'],
            $input['maintenance_type'],
            $input['scheduled_date'],
            !empty($input['assigned_to']) ? $input['assigned_to'] : null,
            $input['description'],
            !empty($input['estimated_cost']) ? $input['estimated_cost'] : null,
            $input['priority'] ?? 'medium'
        ]);

        if ($result) {
            $maintenance_id = $db->lastInsertId();

            $assetUpdate = $db->prepare("UPDATE assets SET status = 'maintenance' WHERE id = ?");
            $assetUpdate->execute([$input['asset_id']]);

            logAction($db, 'CREATE', 'maintenance_schedules', $maintenance_id);

            $pdf_url = 'generate_maintenance_pdf.php?id=' . $maintenance_id;

            echo json_encode([
                'success' => true,
                'message' => 'Maintenance task scheduled successfully',
                'maintenance_id' => $maintenance_id,
                'pdf_url' => $pdf_url
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to schedule maintenance task']);
        }
    } catch (Exception $e) {
        error_log('Schedule maintenance error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
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
        $assetIdStmt = $db->prepare("SELECT asset_id FROM maintenance_schedules WHERE id = ?");
        $assetIdStmt->execute([$input['id']]);
        $row = $assetIdStmt->fetch(PDO::FETCH_ASSOC);
        $assetId = $row ? (int)$row['asset_id'] : 0;

        if ($assetId > 0) {
            if (in_array($input['status'], ['scheduled', 'in_progress'])) {
                $assetUpdate = $db->prepare("UPDATE assets SET status = 'maintenance' WHERE id = ?");
                $assetUpdate->execute([$assetId]);
            } elseif (in_array($input['status'], ['completed', 'cancelled'])) {
                $assetUpdate = $db->prepare("UPDATE assets SET status = 'available' WHERE id = ?");
                $assetUpdate->execute([$assetId]);
            }
        }

        logAction($db, 'UPDATE', 'maintenance_schedules', $input['id']);

        echo json_encode(['success' => true, 'message' => 'Maintenance status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update maintenance status']);
    }
}

function updateMaintenanceTask($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Maintenance ID is required']);
        return;
    }

    $fields = [];
    $params = [];

    $allowed_fields = [
        'asset_id', 'maintenance_type', 'scheduled_date', 'assigned_to',
        'description', 'estimated_cost', 'actual_cost', 'priority', 'notes',
        'estimated_duration'
    ];

    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            // Handle empty strings for optional fields
            if ($input[$field] === '' && in_array($field, ['assigned_to', 'estimated_cost', 'actual_cost', 'notes', 'estimated_duration'])) {
                $fields[] = "$field = NULL";
            } else {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $query = "UPDATE maintenance_schedules SET " . implode(', ', $fields) . " WHERE id = ?";
    $params[] = $input['id'];

    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);

    if ($result) {
        // Log the action
        logAction($db, 'UPDATE', 'maintenance_schedules', $input['id']);

        echo json_encode(['success' => true, 'message' => 'Maintenance task updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update maintenance task']);
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
        // Log the action
        logAction($db, 'UPDATE', 'maintenance_schedules', $input['id']);

        echo json_encode(['success' => true, 'message' => 'Maintenance task cancelled successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to cancel maintenance task']);
    }
}

function logAction($db, $action, $table, $record_id) {
    if (!isset($_SESSION['user_id'])) return;

    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address)
              VALUES (?, ?, ?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $table,
        $record_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}
?>
