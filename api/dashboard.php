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
                'damagedItems' => 0
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
            'damagedItems' => 0
        );

        // Total assets
        $query = "SELECT COUNT(*) as total FROM assets";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['totalAssets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Available assets
        $query = "SELECT COUNT(*) as total FROM assets WHERE status = 'available'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['availableItems'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Maintenance items
        $query = "SELECT COUNT(*) as total FROM assets WHERE status = 'maintenance'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['maintenanceItems'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Damaged/Lost items
        $query = "SELECT COUNT(*) as total FROM assets WHERE status IN ('damaged', 'lost')";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['damagedItems'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Preventive maintenance tasks (if table exists)
        $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
        if ($checkMaintenance && $checkMaintenance->rowCount() > 0) {
            $query = "
                SELECT
                    SUM(CASE WHEN maintenance_type = 'preventive' AND status IN ('scheduled','in_progress') THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN maintenance_type = 'preventive' AND status = 'scheduled' AND scheduled_date = CURDATE() THEN 1 ELSE 0 END) AS due_today,
                    SUM(CASE WHEN maintenance_type = 'preventive' AND status = 'scheduled' AND scheduled_date < CURDATE() THEN 1 ELSE 0 END) AS overdue
                FROM maintenance_schedules
            ";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $maintenanceStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $activeTasks = (int)($maintenanceStats['active'] ?? 0);
            if ($activeTasks > 0 || $maintenanceStats) {
                $stats['maintenanceItems'] = $activeTasks;
            }
            $stats['maintenanceDueToday'] = (int)($maintenanceStats['due_today'] ?? 0);
            $stats['maintenanceOverdue'] = (int)($maintenanceStats['overdue'] ?? 0);
        }

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
        $alerts = array();

        // Check if supplies table exists for supply alerts
        try {
            $checkSupplies = $db->query("SHOW TABLES LIKE 'supplies'");
            error_log("Supplies table check: " . $checkSupplies->rowCount());
            if($checkSupplies->rowCount() > 0) {
                // Low stock supplies
                $query = "SELECT name, current_stock, minimum_stock FROM supplies WHERE current_stock <= minimum_stock AND status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $alerts[] = array(
                        "type" => "low_stock",
                        "title" => "Low Stock Alert",
                        "message" => "Supply '{$row['name']}' is running low ({$row['current_stock']} remaining)",
                        "priority" => "medium"
                    );
                }

                // Expired supplies
                $query = "SELECT name, expiry_date FROM supplies WHERE expiry_date < CURDATE() AND status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $alerts[] = array(
                        "type" => "expired_supplies",
                        "title" => "Expired Supplies",
                        "message" => "Supply '{$row['name']}' expired on {$row['expiry_date']}",
                        "priority" => "high"
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Error checking supplies: " . $e->getMessage());
        }

        error_log("Returning " . count($alerts) . " alerts");
        http_response_code(200);
        echo json_encode($alerts);
    } catch (Exception $e) {
        error_log("Error in getAlerts: " . $e->getMessage());
        http_response_code(200); // Still return 200 with empty array
        echo json_encode(array()); // Return empty array instead of error
    }
}

function getNotifications($db) {
    try {
        $notifications = array();

        // Recent system actions become notifications
        try {
            $checkLogs = $db->query("SHOW TABLES LIKE 'system_logs'");
            $checkUsers = $db->query("SHOW TABLES LIKE 'users'");

            if ($checkLogs->rowCount() > 0 && $checkUsers->rowCount() > 0) {
                $query = "SELECT sl.action, sl.table_name, sl.created_at, sl.details, u.full_name
                          FROM system_logs sl
                          JOIN users u ON sl.user_id = u.id
                          ORDER BY sl.created_at DESC
                          LIMIT 8";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $action = strtolower($row['action'] ?? 'update');
                    $type = 'reminder';

                    switch ($action) {
                        case 'create':
                        case 'assign':
                            $type = 'assignment';
                            break;
                        case 'login':
                            $type = 'reminder';
                            break;
                        case 'delete':
                            $type = 'alert';
                            break;
                        case 'return':
                            $type = 'maintenance';
                            break;
                        default:
                            $type = 'reminder';
                            break;
                    }

                    $tableName = str_replace('_', ' ', $row['table_name'] ?? 'record');
                    $actor = $row['full_name'] ?? 'System';
                    $message = trim($row['details'] ?? '');
                    if ($message === '') {
                        $message = ucfirst($actor) . " performed a " . $action . " on " . $tableName;
                    }

                    $notifications[] = array(
                        'type' => $type,
                        'title' => ucfirst($actor),
                        'message' => $message,
                        'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s')
                    );
                }
            }
        } catch (Exception $e) {
            error_log('Notifications: system_logs lookup failed - ' . $e->getMessage());
        }

        // Low stock supplies
        try {
            $checkSupplies = $db->query("SHOW TABLES LIKE 'supplies'");
            if ($checkSupplies->rowCount() > 0) {
                $query = "SELECT name, current_stock, minimum_stock FROM supplies WHERE current_stock <= minimum_stock AND status = 'active' LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $notifications[] = array(
                        'type' => 'alert',
                        'title' => 'Low stock: ' . ($row['name'] ?? 'Supply'),
                        'message' => "Only {$row['current_stock']} left (minimum {$row['minimum_stock']}).",
                        'created_at' => date('Y-m-d H:i:s')
                    );
                }
            }
        } catch (Exception $e) {
            error_log('Notifications: supplies lookup failed - ' . $e->getMessage());
        }

        // Imminent maintenance reminders
        try {
            $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
            $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
            if ($checkMaintenance->rowCount() > 0 && $checkAssets->rowCount() > 0) {
                $query = "SELECT a.name, ms.scheduled_date FROM maintenance_schedules ms
                          JOIN assets a ON ms.asset_id = a.id
                          WHERE ms.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                          ORDER BY ms.scheduled_date ASC
                          LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $date = $row['scheduled_date'] ?? date('Y-m-d');
                    $notifications[] = array(
                        'type' => 'maintenance',
                        'title' => 'Upcoming maintenance',
                        'message' => "Asset '{$row['name']}' scheduled on {$date}",
                        'created_at' => $date . ' 00:00:00'
                    );
                }
            }
        } catch (Exception $e) {
            error_log('Notifications: maintenance lookup failed - ' . $e->getMessage());
        }

        // Sort notifications by created_at descending
        usort($notifications, function ($a, $b) {
            return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
        });

        $notifications = array_slice($notifications, 0, 10);

        http_response_code(200);
        echo json_encode($notifications);
    } catch (Exception $e) {
        error_log('getNotifications error: ' . $e->getMessage());
        http_response_code(200);
        echo json_encode(array());
    }
}
?>