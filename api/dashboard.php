<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

// Temporarily disable session check for debugging
// if(!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(array("message" => "Unauthorized"));
//     exit();
// }

try {
    error_log("Dashboard API: Starting database connection");
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        error_log("Dashboard API: Database connection is NULL");
        throw new Exception("Database connection failed");
    }
    error_log("Dashboard API: Database connected successfully");
} catch (Exception $e) {
    error_log("Dashboard API: Database connection error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array("message" => "Database connection error", "error" => $e->getMessage()));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'none';
error_log("Dashboard API: Method=$method, Action=$action");

switch($method) {
    case 'GET':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'stats':
                    error_log("Dashboard API: Calling getStats");
                    getStats($db);
                    break;
                case 'recent_activities':
                    error_log("Dashboard API: Calling getRecentActivities");
                    getRecentActivities($db);
                    break;
                case 'alerts':
                    error_log("Dashboard API: Calling getAlerts");
                    getAlerts($db);
                    break;
                case 'notifications':
                    error_log("Dashboard API: Calling getNotifications");
                    getNotifications($db);
                    break;
                default:
                    error_log("Dashboard API: Unknown action - " . $_GET['action']);
                    http_response_code(400);
                    echo json_encode(array("message" => "Unknown action"));
                    break;
            }
        } else {
            error_log("Dashboard API: No action specified");
            http_response_code(400);
            echo json_encode(array("message" => "No action specified"));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getStats($db) {
    try {
        // Check if assets table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'assets'");
        if($checkTable->rowCount() == 0) {
            // Return default stats if table doesn't exist
            $stats = array(
                'totalAssets' => 0,
                'availableItems' => 0,
                'maintenanceItems' => 0,
                'maintenanceDueToday' => 0,
                'maintenanceOverdue' => 0,
                'damagedItems' => 0,
                'statusBreakdown' => array(
                    'available' => 0,
                    'assigned' => 0,
                    'maintenance' => 0,
                    'damaged_lost' => 0,
                    'disposed' => 0
                )
            );
            http_response_code(200);
            echo json_encode($stats);
            return;
        }

        $stats = array(
            'totalAssets' => 0,
            'availableItems' => 0,
            'maintenanceItems' => 0,
            'maintenanceDueToday' => 0,
            'maintenanceOverdue' => 0,
            'damagedItems' => 0,
            'statusBreakdown' => array(
                'available' => 0,
                'assigned' => 0,
                'maintenance' => 0,
                'damaged_lost' => 0,
                'disposed' => 0
            )
        );

        // Total assets
        $query = "SELECT COUNT(*) as total FROM assets WHERE status != 'disposed'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['totalAssets'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Available assets
        $query = "SELECT COUNT(*) as total FROM assets WHERE status = 'available'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['availableItems'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Maintenance metrics derived only from assets
        $query = "SELECT COUNT(*) as total FROM assets WHERE status = 'maintenance'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['maintenanceItems'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $stats['maintenanceDueToday'] = 0;
        $stats['maintenanceOverdue'] = 0;

        // Damaged/Lost items derived only from assets
        $query = "SELECT COUNT(*) as total FROM assets WHERE status IN ('damaged', 'lost') OR condition_status = 'damaged'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['damagedItems'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Status breakdown for chart
        $statusQuery = "SELECT 
                            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
                            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS assigned,
                            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance,
                            SUM(CASE WHEN status IN ('damaged', 'lost') OR condition_status = 'damaged' THEN 1 ELSE 0 END) AS damaged_lost,
                            SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) AS disposed
                        FROM assets";
        $statusStmt = $db->prepare($statusQuery);
        $statusStmt->execute();
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC) ?: array();

        $stats['statusBreakdown'] = array(
            'available' => (int)($statusRow['available'] ?? 0),
            'assigned' => (int)($statusRow['assigned'] ?? 0),
            'maintenance' => (int)($statusRow['maintenance'] ?? 0),
            'damaged_lost' => (int)($statusRow['damaged_lost'] ?? 0),
            'disposed' => (int)($statusRow['disposed'] ?? 0)
        );

        http_response_code(200);
        echo json_encode($stats);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error fetching stats", "error" => $e->getMessage()));
    }
}

function getRecentActivities($db) {
    try {
        // Check if required tables exist
        $checkSystemLogs = $db->query("SHOW TABLES LIKE 'system_logs'");
        $checkUsers = $db->query("SHOW TABLES LIKE 'users'");

        if($checkSystemLogs->rowCount() == 0 || $checkUsers->rowCount() == 0) {
            // Return empty activities if tables don't exist
            http_response_code(200);
            echo json_encode(array());
            return;
        }

        $query = "SELECT sl.action, sl.table_name, sl.created_at, u.full_name
                  FROM system_logs sl
                  JOIN users u ON sl.user_id = u.id
                  ORDER BY sl.created_at DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $activities = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = array(
                "action" => $row['action'],
                "table_name" => $row['table_name'],
                "user" => $row['full_name'],
                "created_at" => $row['created_at']
            );
        }

        http_response_code(200);
        echo json_encode($activities);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error fetching activities", "error" => $e->getMessage()));
    }
}

function getAlerts($db) {
    try {
        error_log("getAlerts function called");
        // Returning empty alerts to let modules surface their own actionable items
        http_response_code(200);
        echo json_encode(array());

    } catch (Exception $e) {
        error_log("Error in getAlerts: " . $e->getMessage());
        http_response_code(200); // Still return 200 with empty array
        echo json_encode(array()); // Return empty array instead of error
    }
}

function getNotifications($db) {
    try {
        $notifications = array();

        // Returning an empty notification list; individual modules can surface their own notifications in context
        http_response_code(200);
        echo json_encode($notifications);

    } catch (Exception $e) {
        error_log('getNotifications error: ' . $e->getMessage());
        http_response_code(200);
        echo json_encode(array());
    }
}
?>
