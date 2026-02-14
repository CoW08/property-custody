<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header("Content-Type: application/json"); http_response_code(500); }
    error_log("[DASHBOARD] Uncaught: " . $e->getMessage());
    echo json_encode(["message" => "Server error: " . $e->getMessage(), "error" => $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[DASHBOARD] PHP error ($severity): $message in $file:$line");
    return true;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        echo json_encode(['error' => 'Fatal server error', 'message' => $error['message']]);
    }
});

// Load config for DB constants
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flush stray output from includes
ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    error_log("[DASHBOARD] DB connection failed: " . $e->getMessage());
    echo json_encode(["message" => "Database connection failed", "error" => $e->getMessage()]);
    exit;
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
                case 'supply_stock_chart':
                    getSupplyStockChart($db);
                    break;
                case 'supply_category_chart':
                    getSupplyCategoryChart($db);
                    break;
                case 'monthly_transactions_chart':
                    getMonthlyTransactionsChart($db);
                    break;
                case 'asset_category_chart':
                    getAssetCategoryChart($db);
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
                            0 AS disposed
                        FROM assets";
        $statusStmt = $db->prepare($statusQuery);
        $statusStmt->execute();
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC) ?: array();

        $disposedQuery = "SELECT COUNT(*) as total FROM waste_management_records WHERE status = 'disposed'";
        $disposedStmt = $db->prepare($disposedQuery);
        $disposedStmt->execute();
        $disposedRow = $disposedStmt->fetch(PDO::FETCH_ASSOC) ?: array();
        $disposedCount = (int)($disposedRow['total'] ?? 0);

        $stats['statusBreakdown'] = array(
            'available' => (int)($statusRow['available'] ?? 0),
            'assigned' => (int)($statusRow['assigned'] ?? 0),
            'maintenance' => (int)($statusRow['maintenance'] ?? 0),
            'damaged_lost' => (int)($statusRow['damaged_lost'] ?? 0),
            'disposed' => $disposedCount
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

        // Low stock alerts
        $lowStock = $db->query("SELECT id, name, item_code, current_stock, minimum_stock FROM supplies WHERE archived_at IS NULL AND current_stock <= minimum_stock ORDER BY current_stock ASC LIMIT 5");
        while ($row = $lowStock->fetch(PDO::FETCH_ASSOC)) {
            $label = $row['current_stock'] <= 0 ? 'Out of stock' : 'Low stock';
            $notifications[] = [
                'type' => 'alert',
                'title' => "{$label}: {$row['name']}",
                'message' => "Stock: {$row['current_stock']} / Min: {$row['minimum_stock']} (Code: {$row['item_code']})",
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Overdue maintenance
        $overdue = $db->query("SELECT ms.id, ms.scheduled_date, ms.priority, a.name as asset_name FROM maintenance_schedules ms LEFT JOIN assets a ON ms.asset_id = a.id WHERE ms.status IN ('scheduled','in_progress') AND ms.scheduled_date < CURDATE() ORDER BY ms.scheduled_date ASC LIMIT 5");
        while ($row = $overdue->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'type' => 'maintenance',
                'title' => "Overdue maintenance: " . ($row['asset_name'] ?? 'Unknown asset'),
                'message' => "Scheduled for {$row['scheduled_date']}, priority: {$row['priority']}",
                'created_at' => $row['scheduled_date'] . ' 08:00:00'
            ];
        }

        // Upcoming maintenance due today
        $dueToday = $db->query("SELECT ms.id, a.name as asset_name, u.full_name as technician FROM maintenance_schedules ms LEFT JOIN assets a ON ms.asset_id = a.id LEFT JOIN users u ON ms.assigned_to = u.id WHERE ms.status = 'scheduled' AND ms.scheduled_date = CURDATE() LIMIT 5");
        while ($row = $dueToday->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'type' => 'reminder',
                'title' => "Maintenance due today: " . ($row['asset_name'] ?? 'Unknown asset'),
                'message' => "Assigned to: " . ($row['technician'] ?? 'Unassigned'),
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Pending procurement requests
        $pending = $db->query("SELECT id, item_name, request_date FROM procurement_requests WHERE status = 'pending' ORDER BY request_date DESC LIMIT 3");
        while ($row = $pending->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'type' => 'assignment',
                'title' => "Pending procurement: {$row['item_name']}",
                'message' => "Requested on {$row['request_date']}",
                'created_at' => $row['request_date'] . ' 09:00:00'
            ];
        }

        // Recent waste / archived items
        $waste = $db->query("SELECT name, entity_type, archived_at FROM waste_management_records WHERE status = 'archived' ORDER BY archived_at DESC LIMIT 3");
        while ($row = $waste->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'type' => 'alert',
                'title' => "Archived {$row['entity_type']}: {$row['name']}",
                'message' => "Awaiting disposal decision",
                'created_at' => $row['archived_at']
            ];
        }

        // Sort by date descending
        usort($notifications, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        http_response_code(200);
        echo json_encode($notifications);

    } catch (Exception $e) {
        error_log('getNotifications error: ' . $e->getMessage());
        http_response_code(200);
        echo json_encode(array());
    }
}
function getSupplyStockChart($db) {
    try {
        $stmt = $db->query("SELECT name, current_stock, minimum_stock FROM supplies WHERE archived_at IS NULL ORDER BY current_stock DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['items' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['items' => []]);
    }
}

function getSupplyCategoryChart($db) {
    try {
        $stmt = $db->query("SELECT COALESCE(category, 'Uncategorized') as category, COUNT(*) as count, SUM(current_stock) as total_stock FROM supplies WHERE archived_at IS NULL GROUP BY category ORDER BY count DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['categories' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['categories' => []]);
    }
}

function getMonthlyTransactionsChart($db) {
    try {
        $stmt = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, transaction_type, SUM(quantity) as total FROM supply_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month, transaction_type ORDER BY month ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $months = [];
        foreach ($rows as $row) {
            $m = $row['month'];
            if (!isset($months[$m])) $months[$m] = ['in' => 0, 'out' => 0];
            $months[$m][$row['transaction_type']] = (int)$row['total'];
        }
        echo json_encode(['months' => $months]);
    } catch (Exception $e) {
        echo json_encode(['months' => []]);
    }
}

function getAssetCategoryChart($db) {
    try {
        // Simple query: group assets by their raw category column value
        // NO JOIN - the asset_categories table has duplicates that corrupt counts
        $stmt = $db->query("SELECT 
            COALESCE(NULLIF(category, ''), 'Uncategorized') as cat_raw,
            COUNT(*) as count
            FROM assets
            WHERE status != 'disposed'
            GROUP BY cat_raw
            ORDER BY count DESC");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map numeric category IDs to readable names
        $categoryMap = [
            '1' => 'Computer Equipment',
            '2' => 'Office Furniture',
            '3' => 'Laboratory Equipment',
            '4' => 'Audio Visual Equipment',
            '5' => 'Medical Supply',
            '6' => 'Sports Equipment',
            '7' => 'Books and References'
        ];

        // Also try to load names from asset_categories table
        try {
            $catStmt = $db->query("SELECT id, name FROM asset_categories");
            while ($catRow = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                $categoryMap[strval($catRow['id'])] = $catRow['name'];
                // Also map by name in case category column stores names
                $categoryMap[$catRow['name']] = $catRow['name'];
            }
        } catch (Throwable $e) {
            // asset_categories table may not exist, use hardcoded map
        }

        // Build final result with human-readable names
        $result = [];
        foreach ($rows as $row) {
            $rawCat = $row['cat_raw'];
            $displayName = isset($categoryMap[$rawCat]) ? $categoryMap[$rawCat] : $rawCat;
            $result[] = [
                'category' => $displayName,
                'count' => (int)$row['count']
            ];
        }

        echo json_encode(['categories' => $result]);
    } catch (Exception $e) {
        error_log("[DASHBOARD] getAssetCategoryChart error: " . $e->getMessage());
        echo json_encode(['categories' => []]);
    }
}
