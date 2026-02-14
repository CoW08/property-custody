<?php
// Enable error display for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch ANY stray output
ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header("Content-Type: application/json"); http_response_code(500); }
    error_log("[ASSETS] Uncaught: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(["message" => "Server error: " . $e->getMessage(), "error" => $e->getMessage()]);
    exit;
});

// Suppress PHP notices/warnings from outputting (log them instead)
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("[ASSETS] PHP error ($severity): $message in $file:$line");
    return true; // suppress
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
        error_log("[ASSETS] Fatal: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        echo json_encode(["message" => "Fatal server error", "error" => $error['message']]);
    }
});

// Add debug logging
function debug_log($message, $data = null) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data);
    }
    error_log($log_message);
}

function sanitizeIds($ids) {
    if (!is_array($ids) || empty($ids)) {
        return [];
    }

    return array_values(array_filter(array_map(function($value) {
        return is_numeric($value) ? intval($value) : null;
    }, $ids), function($value) {
        return $value !== null && $value > 0;
    }));
}

function normalizeDateInput($value) {
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $value = trim($value);
    }
    if ($value === '' || $value === '0000-00-00') {
        return null;
    }
    $date = DateTime::createFromFormat('Y-m-d', (string) $value);
    if ($date === false) {
        return null;
    }
    return $date->format('Y-m-d');
}

function bulkUpdateStatus($db, $input) {
    $payload = json_decode($input, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid JSON payload"]);
        return;
    }

    $ids = sanitizeIds($payload['ids'] ?? []);
    $status = $payload['status'] ?? '';
    $allowedStatuses = ['available', 'assigned', 'maintenance', 'damaged', 'lost', 'disposed'];

    if (empty($ids) || !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid bulk status request"]);
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "UPDATE assets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";
        $stmt = $db->prepare($query);
        $params = array_merge([$status], $ids);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            "message" => "Status updated",
            "updated" => $stmt->rowCount()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Failed to update status", "error" => $e->getMessage()]);
    }
}

function bulkModifyTags($db, $input, $mode = 'add') {
    $payload = json_decode($input, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid JSON payload"]);
        return;
    }

    $ids = sanitizeIds($payload['ids'] ?? []);
    $tagId = isset($payload['tag_id']) && is_numeric($payload['tag_id']) ? intval($payload['tag_id']) : 0;

    if (empty($ids) || $tagId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid bulk tag request"]);
        return;
    }

    try {
        // Ensure tag exists
        $tagCheck = $db->prepare("SELECT id FROM asset_tags WHERE id = ? LIMIT 1");
        $tagCheck->execute([$tagId]);
        if ($tagCheck->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Tag not found"]);
            return;
        }

        if ($mode === 'add') {
            $values = [];
            $placeholders = [];
            foreach ($ids as $assetId) {
                $values[] = $assetId;
                $values[] = $tagId;
                $placeholders[] = '(?, ?)';
            }

            $insert = $db->prepare(
                "INSERT IGNORE INTO asset_tag_relationships (asset_id, tag_id) VALUES " . implode(',', $placeholders)
            );
            $insert->execute($values);
            $affected = $insert->rowCount();
        } else {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $delete = $db->prepare(
                "DELETE FROM asset_tag_relationships WHERE tag_id = ? AND asset_id IN ($placeholders)"
            );
            $delete->execute(array_merge([$tagId], $ids));
            $affected = $delete->rowCount();
        }

        http_response_code(200);
        echo json_encode([
            "message" => "Bulk tag operation completed",
            "affected" => $affected,
            "mode" => $mode
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Failed to process tag operation", "error" => $e->getMessage()]);
    }
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/archive_helpers.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers inline
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
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
    ob_end_clean();
    http_response_code(500);
    error_log("[ASSETS] DB connection failed: " . $e->getMessage());
    echo json_encode(["message" => "Database connection failed", "error" => $e->getMessage()]);
    exit;
}

$input_data = file_get_contents("php://input");

$method = $_SERVER['REQUEST_METHOD'];

// Only ensure archive infrastructure on write operations
if ($method !== 'GET') {
    ensureArchiveInfrastructure($db, 'assets');
}

// Flush any stray output before our JSON
ob_end_clean();

debug_log("API Request", ["method" => $method, "get" => $_GET, "input" => $input_data]);

switch($method) {
    case 'GET':
        if(isset($_GET['action']) && $_GET['action'] === 'export_excel') {
            exportAssetsToExcel($db);
        } elseif(isset($_GET['id'])) {
            getAsset($db, $_GET['id']);
        } else {
            getAssets($db);
        }
        break;
    case 'POST':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'bulk_update_status':
                    bulkUpdateStatus($db, $input_data);
                    break;
                case 'bulk_add_tag':
                    bulkModifyTags($db, $input_data, 'add');
                    break;
                case 'bulk_remove_tag':
                    bulkModifyTags($db, $input_data, 'remove');
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(array("message" => "Unknown bulk action"));
            }
        } else {
            createAsset($db, $input_data);
        }
        break;
    case 'PUT':
        if(isset($_GET['id'])) {
            updateAsset($db, $_GET['id'], $input_data);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Asset ID required for update"));
        }
        break;
    case 'DELETE':
        if(isset($_GET['id'])) {
            $payload = json_decode($input_data, true);
            if (!is_array($payload)) {
                $payload = [];
            }
            archiveAsset($db, intval($_GET['id']), $payload);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Asset ID required for archive"));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getAssets($db) {
    try {
        // Build query with filters
        $whereClause = " WHERE a.archived_at IS NULL";
        $params = array();

        // Add search filter
        if(isset($_GET['search']) && !empty($_GET['search'])) {
            $whereClause .= " AND (a.name LIKE ? OR a.asset_code LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add status filter
        if(isset($_GET['status']) && !empty($_GET['status'])) {
            $whereClause .= " AND a.status = ?";
            $params[] = $_GET['status'];
        }

        // Check if assets table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'assets'");
        if($checkTable->rowCount() == 0) {
            http_response_code(500);
            echo json_encode(array("message" => "Assets table not found. Please run database setup."));
            return;
        }

        // Check which optional tables exist
        $hasCategoriesTable = $db->query("SHOW TABLES LIKE 'asset_categories'")->rowCount() > 0;
        $hasTagsTable = $db->query("SHOW TABLES LIKE 'asset_tags'")->rowCount() > 0;
        $hasTagRelTable = $db->query("SHOW TABLES LIKE 'asset_tag_relationships'")->rowCount() > 0;
        $hasTagSupport = $hasTagsTable && $hasTagRelTable;

        // Check if archived_at column exists
        $hasArchivedAt = false;
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM assets LIKE 'archived_at'");
            $hasArchivedAt = $colCheck->rowCount() > 0;
        } catch (Throwable $e) {}

        // Adjust WHERE clause if archived_at doesn't exist
        if (!$hasArchivedAt) {
            $whereClause = str_replace(" WHERE a.archived_at IS NULL", " WHERE 1=1", $whereClause);
        }

        // Add category filter
        if(isset($_GET['category']) && !empty($_GET['category'])) {
            if ($hasCategoriesTable) {
                $whereClause .= " AND (ac.name = ? OR a.category = ?)";
                $params[] = $_GET['category'];
                $params[] = $_GET['category'];
            } else {
                $whereClause .= " AND a.category = ?";
                $params[] = $_GET['category'];
            }
        }

        // Add tag filter
        if(isset($_GET['tag']) && !empty($_GET['tag']) && $hasTagSupport) {
            $whereClause .= " AND a.id IN (SELECT asset_id FROM asset_tag_relationships WHERE tag_id = ?)";
            $params[] = $_GET['tag'];
        }

        // Get pagination parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        // Build JOINs based on available tables
        $joins = "";
        $categorySelect = "COALESCE(NULLIF(a.category, ''), 'Uncategorized') as category_name";
        $tagSelect = "NULL as tags";
        $groupBy = "";

        if ($hasCategoriesTable) {
            $joins .= " LEFT JOIN asset_categories ac ON (a.category = ac.id OR a.category = ac.name)";
            $categorySelect = "COALESCE(ac.name, NULLIF(a.category, ''), 'Uncategorized') as category_name";
        }

        if ($hasTagSupport) {
            $joins .= " LEFT JOIN asset_tag_relationships atr ON a.id = atr.asset_id";
            $joins .= " LEFT JOIN asset_tags at2 ON atr.tag_id = at2.id";
            $tagSelect = "GROUP_CONCAT(DISTINCT CASE WHEN at2.id IS NULL THEN NULL ELSE CONCAT(at2.id, ':', at2.name, ':', COALESCE(at2.color, '#3B82F6')) END SEPARATOR '|') as tags";
            $groupBy = " GROUP BY a.id";
        }

        // Count query
        $countQuery = "SELECT COUNT(DISTINCT a.id) as total FROM assets a{$joins}{$whereClause}";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Main query
        $query = "SELECT a.*, {$categorySelect}, {$tagSelect}
                  FROM assets a{$joins}{$whereClause}{$groupBy}
                  ORDER BY a.created_at DESC
                  LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare query");
        }

        $stmt->execute($params);

        $assets = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Parse tags
            $tags = array();
            if(!empty($row['tags'])) {
                $tagPairs = explode('|', $row['tags']);
                foreach($tagPairs as $tagPair) {
                    $tagData = explode(':', $tagPair);
                    if(count($tagData) >= 3) {
                        $tags[] = array(
                            'id' => $tagData[0],
                            'name' => $tagData[1],
                            'color' => $tagData[2]
                        );
                    }
                }
            }
            unset($row['tags']); // Remove the raw tags string
            $row['tags'] = $tags;

            $assets[] = $row;
        }

        // Calculate pagination info
        $totalPages = ceil($totalCount / $limit);

        http_response_code(200);
        echo json_encode(array(
            'assets' => $assets,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => intval($totalCount),
                'per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            )
        ));
    } catch (Exception $e) {
        error_log("[ASSETS] getAssets error: " . $e->getMessage());
        // Return 200 with empty data so the page loads gracefully
        http_response_code(200);
        echo json_encode(array(
            'assets' => [],
            'pagination' => array(
                'current_page' => 1,
                'total_pages' => 0,
                'total_items' => 0,
                'per_page' => 10,
                'has_next' => false,
                'has_previous' => false
            ),
            'warning' => 'Error fetching assets: ' . $e->getMessage()
        ));
    }
}

function getAsset($db, $id) {
    try {
        $hasCategoriesTable = $db->query("SHOW TABLES LIKE 'asset_categories'")->rowCount() > 0;
        $hasTagsTable = $db->query("SHOW TABLES LIKE 'asset_tags'")->rowCount() > 0;
        $hasTagRelTable = $db->query("SHOW TABLES LIKE 'asset_tag_relationships'")->rowCount() > 0;
        $hasTagSupport = $hasTagsTable && $hasTagRelTable;

        $joins = "";
        $categorySelect = "COALESCE(NULLIF(a.category, ''), 'Uncategorized') as category_name";
        $tagSelect = "NULL as tags";
        $groupBy = "";

        if ($hasCategoriesTable) {
            $joins .= " LEFT JOIN asset_categories ac ON (a.category = ac.id OR a.category = ac.name)";
            $categorySelect = "COALESCE(ac.name, NULLIF(a.category, ''), 'Uncategorized') as category_name";
        }

        if ($hasTagSupport) {
            $joins .= " LEFT JOIN asset_tag_relationships atr ON a.id = atr.asset_id";
            $joins .= " LEFT JOIN asset_tags at2 ON atr.tag_id = at2.id";
            $tagSelect = "GROUP_CONCAT(DISTINCT CASE WHEN at2.id IS NULL THEN NULL ELSE CONCAT(at2.id, ':', at2.name, ':', COALESCE(at2.color, '#3B82F6')) END SEPARATOR '|') as tags";
            $groupBy = " GROUP BY a.id";
        }

        $query = "SELECT a.*, {$categorySelect}, {$tagSelect}
                  FROM assets a{$joins}
                  WHERE a.id = ?{$groupBy}";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

    if($stmt->rowCount() > 0) {
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        // Parse tags
        $tags = array();
        if(!empty($asset['tags'])) {
            $tagPairs = explode('|', $asset['tags']);
            foreach($tagPairs as $tagPair) {
                $tagData = explode(':', $tagPair);
                if(count($tagData) >= 3) {
                    $tags[] = array(
                        'id' => $tagData[0],
                        'name' => $tagData[1],
                        'color' => $tagData[2]
                    );
                }
            }
        }
        unset($asset['tags']); // Remove raw tags string
        $asset['tags'] = $tags;

        http_response_code(200);
        echo json_encode($asset);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Asset not found"));
    }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error fetching asset", "error" => $e->getMessage()));
    }
}

// Generate unique asset code
function generateAssetCode($db, $category = null) {
    $prefix = 'AST';
    
    // Add category prefix if provided
    if ($category) {
        try {
            $categoryStmt = $db->prepare("SELECT name FROM asset_categories WHERE id = ? OR name = ? LIMIT 1");
            $categoryStmt->execute([$category, $category]);
            if ($categoryStmt->rowCount() > 0) {
                $categoryName = $categoryStmt->fetch(PDO::FETCH_ASSOC)['name'];
                $prefix = strtoupper(substr(preg_replace('/[^A-Z]/', '', $categoryName), 0, 3));
                if (strlen($prefix) < 2) $prefix = 'AST';
            }
        } catch (Throwable $e) {
            // asset_categories table may not exist
        }
    }
    
    // Get current year
    $year = date('Y');
    
    // Find the next available number for this prefix and year
    $query = "SELECT asset_code FROM assets WHERE asset_code LIKE ? ORDER BY asset_code DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$prefix . '-' . $year . '-%']);
    
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['asset_code'];
        // Extract number from last code (format: PREFIX-YEAR-NUMBER)
        preg_match('/(\d+)$/', $lastCode, $matches);
        $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $nextNumber = 1;
    }
    
    // Generate new code with zero padding
    return $prefix . '-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
}

function ensureUniqueAssetCode($db, $assetCode) {
    $assetCode = trim((string) $assetCode);
    if ($assetCode === '') {
        return $assetCode;
    }

    $existsStmt = $db->prepare("SELECT id FROM assets WHERE asset_code = ? LIMIT 1");
    $candidate = $assetCode;
    $counter = 2;

    while (true) {
        $existsStmt->execute([$candidate]);
        if ($existsStmt->rowCount() === 0) {
            return $candidate;
        }
        $candidate = $assetCode . '-' . $counter;
        $counter++;
    }
}

function createAsset($db, $input = null) {
    debug_log("createAsset called");
    if ($input === null) {
        $input = file_get_contents("php://input");
    }
    debug_log("Raw input", $input);

    $data = json_decode($input);
    debug_log("Parsed data", $data);

    $originalAssetCode = $data->asset_code ?? '';

    // Auto-generate asset code if not provided
    if (empty($data->asset_code)) {
        $data->asset_code = generateAssetCode($db, $data->category ?? null);
        debug_log("Auto-generated asset code", $data->asset_code);
    }

    $data->asset_code = ensureUniqueAssetCode($db, $data->asset_code);

    if(!empty($data->asset_code) && !empty($data->name)) {
        debug_log("Validation passed", ["asset_code" => $data->asset_code, "name" => $data->name]);
        try {
            // Check what columns actually exist in the assets table
            $checkStmt = $db->query("DESCRIBE assets");
            $columns = array();
            $columnInfo = array();
            while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
                $columnInfo[$row['Field']] = $row;
            }

            // Build the INSERT query based on available columns
            $insertFields = array();
            $insertValues = array();
            $placeholders = array();

            if (in_array('id', $columns)) {
                $idMeta = $columnInfo['id'] ?? null;
                $hasAutoIncrement = $idMeta && !empty($idMeta['Extra']) && stripos($idMeta['Extra'], 'auto_increment') !== false;
                $idAllowsNull = $idMeta && ($idMeta['Null'] ?? '') === 'YES';
                $idHasDefault = $idMeta && array_key_exists('Default', $idMeta) && $idMeta['Default'] !== null;

                if (!$hasAutoIncrement && !$idAllowsNull && !$idHasDefault) {
                    $nextIdStmt = $db->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM assets");
                    $nextIdRow = $nextIdStmt ? $nextIdStmt->fetch(PDO::FETCH_ASSOC) : null;
                    $nextId = isset($nextIdRow['next_id']) ? (int) $nextIdRow['next_id'] : 1;
                    $insertFields[] = 'id';
                    $insertValues[] = $nextId;
                    $placeholders[] = '?';
                }
            }

            if (in_array('asset_code', $columns) && !empty($data->asset_code)) {
                $insertFields[] = 'asset_code';
                $insertValues[] = $data->asset_code;
                $placeholders[] = '?';
            }

            if (in_array('name', $columns) && !empty($data->name)) {
                $insertFields[] = 'name';
                $insertValues[] = $data->name;
                $placeholders[] = '?';
            }

            if (in_array('description', $columns)) {
                $insertFields[] = 'description';
                $insertValues[] = $data->description ?? null;
                $placeholders[] = '?';
            }

            // Handle category - check if it's category_id or category
            if (in_array('category_id', $columns)) {
                $insertFields[] = 'category_id';
                $insertValues[] = $data->category ?? null;
                $placeholders[] = '?';
            } elseif (in_array('category', $columns)) {
                $insertFields[] = 'category';
                $insertValues[] = $data->category ?? null;
                $placeholders[] = '?';
            }

            if (in_array('condition_status', $columns)) {
                $insertFields[] = 'condition_status';
                $insertValues[] = $data->condition_status ?? 'good';
                $placeholders[] = '?';
            }

            if (in_array('location', $columns)) {
                $insertFields[] = 'location';
                $insertValues[] = $data->location ?? null;
                $placeholders[] = '?';
            }

            if (in_array('purchase_date', $columns)) {
                $insertFields[] = 'purchase_date';
                $insertValues[] = normalizeDateInput($data->purchase_date ?? null);
                $placeholders[] = '?';
            }

            if (in_array('purchase_cost', $columns)) {
                $insertFields[] = 'purchase_cost';
                $insertValues[] = $data->purchase_cost ?? null;
                $placeholders[] = '?';
            }

            if (in_array('current_value', $columns)) {
                $insertFields[] = 'current_value';
                $insertValues[] = $data->current_value ?? null;
                $placeholders[] = '?';
            }

            if (in_array('status', $columns)) {
                $insertFields[] = 'status';
                $insertValues[] = $data->status ?? 'available';
                $placeholders[] = '?';
            }

            // Only include assigned_to if the column exists and we have a valid value
            if (in_array('assigned_to', $columns)) {
                $assigned_to_value = $data->assigned_to ?? null;

                // If assigned_to is provided, validate it exists in users table
                if ($assigned_to_value !== null && !empty($assigned_to_value) && $assigned_to_value !== '') {
                    // Check if it's a valid integer
                    if (is_numeric($assigned_to_value)) {
                        $checkUser = $db->prepare("SELECT id FROM users WHERE id = ?");
                        $checkUser->execute([intval($assigned_to_value)]);
                        if ($checkUser->rowCount() == 0) {
                            // User doesn't exist, set to null instead
                            $assigned_to_value = null;
                            debug_log("Invalid assigned_to user ID in create, setting to null", $data->assigned_to);
                        } else {
                            $assigned_to_value = intval($assigned_to_value);
                        }
                    } else {
                        // Invalid format, set to null
                        $assigned_to_value = null;
                        debug_log("Non-numeric assigned_to value in create, setting to null", $data->assigned_to);
                    }
                } else {
                    $assigned_to_value = null;
                }

                $insertFields[] = 'assigned_to';
                $insertValues[] = $assigned_to_value;
                $placeholders[] = '?';
            }

            $query = "INSERT INTO assets (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";

            $stmt = $db->prepare($query);

            if($stmt->execute($insertValues)) {
                $asset_id = $db->lastInsertId();

                // Auto-generate QR code for the asset
                $qrData = json_encode([
                    'asset_id' => $asset_id,
                    'asset_code' => $data->asset_code,
                    'name' => $data->name,
                    'system' => 'property_custodian',
                    'generated_at' => date('Y-m-d H:i:s')
                ]);
                
                $qrCodeId = 'QR_' . $data->asset_code . '_' . time();
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

                // Update asset with QR code if columns exist
                if (in_array('qr_code', $columns) && in_array('qr_generated', $columns)) {
                    $updateQR = $db->prepare("UPDATE assets SET qr_code = ?, qr_generated = TRUE WHERE id = ?");
                    $updateQR->execute([$qrCodeId, $asset_id]);
                }

                // Log the activity
                // logActivity($db, $_SESSION['user_id'], 'create', 'assets', $asset_id);

                $message = "Asset created successfully";
                if (!empty($originalAssetCode) && $originalAssetCode !== $data->asset_code) {
                    $message = "Asset created successfully. Asset code updated to " . $data->asset_code;
                }

                http_response_code(201);
                echo json_encode(array(
                    "message" => $message,
                    "id" => $asset_id,
                    "asset_code" => $data->asset_code,
                    "qr_code_id" => $qrCodeId,
                    "qr_url" => $qrUrl,
                    "success" => true
                ));
            } else {
                $errorInfo = $stmt->errorInfo();
                http_response_code(500);
                echo json_encode(array("message" => "Failed to create asset", "error" => $errorInfo[2]));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Database error", "error" => $e->getMessage()));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Asset code and name are required"));
    }
}

function updateAsset($db, $id, $input = null) {
    debug_log("updateAsset called", ["id" => $id]);
    if ($input === null) {
        $input = file_get_contents("php://input");
    }
    debug_log("Raw input", $input);

    if (empty($input)) {
        http_response_code(400);
        echo json_encode(array("message" => "No input data received"));
        return;
    }

    $data = json_decode($input);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid JSON data"));
        return;
    }

    // Validate required fields
    if (empty($data->name)) {
        http_response_code(400);
        echo json_encode(array("message" => "Asset name is required"));
        return;
    }


    // First, let's check what columns actually exist in the assets table
    try {
        $checkStmt = $db->query("DESCRIBE assets");
        $columns = array();
        while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }

        // Build the UPDATE query based on available columns
        $updateFields = array();
        $updateValues = array();

        if (in_array('asset_code', $columns) && isset($data->asset_code)) {
            $updateFields[] = 'asset_code = ?';
            $updateValues[] = $data->asset_code;
        }

        if (in_array('name', $columns) && !empty($data->name)) {
            $updateFields[] = 'name = ?';
            $updateValues[] = $data->name;
        }

        if (in_array('description', $columns)) {
            $updateFields[] = 'description = ?';
            $updateValues[] = isset($data->description) ? $data->description : null;
        }

        // Handle category - check if it's category_id or category
        if (in_array('category_id', $columns)) {
            $updateFields[] = 'category_id = ?';
            $updateValues[] = isset($data->category) ? $data->category : null;
        } elseif (in_array('category', $columns)) {
            $updateFields[] = 'category = ?';
            $updateValues[] = isset($data->category) ? $data->category : null;
        }

        if (in_array('purchase_date', $columns)) {
            $updateFields[] = 'purchase_date = ?';
            $updateValues[] = normalizeDateInput($data->purchase_date ?? null);
        }

        if (in_array('purchase_cost', $columns)) {
            $updateFields[] = 'purchase_cost = ?';
            $updateValues[] = isset($data->purchase_cost) ? $data->purchase_cost : null;
        }

        if (in_array('current_value', $columns)) {
            $updateFields[] = 'current_value = ?';
            $updateValues[] = isset($data->current_value) ? $data->current_value : null;
        }

        if (in_array('location', $columns)) {
            $updateFields[] = 'location = ?';
            $updateValues[] = isset($data->location) ? $data->location : null;
        }

        if (in_array('status', $columns)) {
            $updateFields[] = 'status = ?';
            $updateValues[] = isset($data->status) ? $data->status : 'available';
        }

        if (in_array('condition_status', $columns)) {
            $updateFields[] = 'condition_status = ?';
            $updateValues[] = isset($data->condition_status) ? $data->condition_status : 'good';
        }

        // Only include assigned_to if the column exists and we have a valid value
        if (in_array('assigned_to', $columns)) {
            $assigned_to_value = isset($data->assigned_to) ? $data->assigned_to : null;

            // If assigned_to is provided, validate it exists in users table
            if ($assigned_to_value !== null && !empty($assigned_to_value) && $assigned_to_value !== '') {
                // Check if it's a valid integer
                if (is_numeric($assigned_to_value)) {
                    $checkUser = $db->prepare("SELECT id FROM users WHERE id = ?");
                    $checkUser->execute([intval($assigned_to_value)]);
                    if ($checkUser->rowCount() == 0) {
                        // User doesn't exist, set to null instead
                        $assigned_to_value = null;
                        debug_log("Invalid assigned_to user ID, setting to null", $data->assigned_to);
                    } else {
                        $assigned_to_value = intval($assigned_to_value);
                    }
                } else {
                    // Invalid format, set to null
                    $assigned_to_value = null;
                    debug_log("Non-numeric assigned_to value, setting to null", $data->assigned_to);
                }
            } else {
                $assigned_to_value = null;
            }

            $updateFields[] = 'assigned_to = ?';
            $updateValues[] = $assigned_to_value;
        }

        if (in_array('updated_at', $columns)) {
            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        // Add the ID for the WHERE clause
        $updateValues[] = $id;

        $query = "UPDATE assets SET " . implode(', ', $updateFields) . " WHERE id = ?";

        $stmt = $db->prepare($query);

        if($stmt->execute($updateValues)) {
            // Log the activity
            // logActivity($db, $_SESSION['user_id'], 'update', 'assets', $id);

            http_response_code(200);
            echo json_encode(array("message" => "Asset updated successfully"));
        } else {
            $errorInfo = $stmt->errorInfo();
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update asset", "error" => $errorInfo[2]));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Database error", "error" => $e->getMessage()));
    }
}

function archiveAsset(PDO $db, int $id, array $payload = []): void
{
    ensureArchiveInfrastructure($db, 'assets');

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM assets WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$asset) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(["message" => "Asset not found"]);
            return;
        }

        if (!empty($asset['archived_at'])) {
            $db->rollBack();
            http_response_code(200);
            echo json_encode(["message" => "Asset already archived"]);
            return;
        }

        $archiveData = [
            ':archived_at' => date('Y-m-d H:i:s'),
            ':archived_by' => $_SESSION['user_id'] ?? null,
            ':archive_reason' => $payload['archive_reason'] ?? null,
            ':archive_notes' => $payload['archive_notes'] ?? null,
            ':id' => $id,
        ];

        $updateSql = "UPDATE assets SET archived_at = :archived_at, archived_by = :archived_by, archive_reason = :archive_reason, archive_notes = :archive_notes WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($archiveData);

        recordWasteEntry($db, 'asset', $id, [
            'name' => $asset['name'] ?? 'Asset #' . $id,
            'identifier' => $asset['asset_code'] ?? null,
            'archived_at' => $archiveData[':archived_at'],
            'archived_by' => $archiveData[':archived_by'],
            'archive_reason' => $archiveData[':archive_reason'],
            'archive_notes' => $archiveData[':archive_notes'],
            'metadata' => $asset,
        ]);

        $db->commit();

        http_response_code(200);
        echo json_encode(["message" => "Asset archived successfully"]);
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        http_response_code(500);
        echo json_encode(["message" => "Failed to archive asset", "error" => $throwable->getMessage()]);
    }
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $table_name, $record_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

// Export assets to true Excel format (XML Spreadsheet)
function exportAssetsToExcel($db) {
    try {
        // Build query with filters
        $whereClause = "";
        $params = array();

        if(isset($_GET['search']) && !empty($_GET['search'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "(a.name LIKE ? OR a.asset_code LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if(isset($_GET['category']) && !empty($_GET['category'])) {
            if ($hasCategoriesTable) {
                $whereClause .= ($whereClause ? " AND " : " WHERE ") . "(ac.name = ? OR a.category = ?)";
                $params[] = $_GET['category'];
                $params[] = $_GET['category'];
            } else {
                $whereClause .= ($whereClause ? " AND " : " WHERE ") . "a.category = ?";
                $params[] = $_GET['category'];
            }
        }

        if(isset($_GET['status']) && !empty($_GET['status'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "a.status = ?";
            $params[] = $_GET['status'];
        }

        $hasCategoriesTable = $db->query("SHOW TABLES LIKE 'asset_categories'")->rowCount() > 0;

        $catJoin = "";
        $catSelect = "COALESCE(NULLIF(a.category, ''), 'Uncategorized') as category";
        if ($hasCategoriesTable) {
            $catJoin = "LEFT JOIN asset_categories ac ON (a.category = ac.id OR a.category = ac.name)";
            $catSelect = "COALESCE(ac.name, NULLIF(a.category, ''), 'Uncategorized') as category";
        }

        // Get all assets
        $query = "SELECT 
                    a.asset_code,
                    a.name,
                    {$catSelect},
                    a.description,
                    a.status,
                    a.condition_status,
                    a.location,
                    a.purchase_date,
                    a.purchase_cost,
                    a.current_value,
                    u.full_name as assigned_to,
                    u.department as assigned_department,
                    a.qr_code,
                    a.created_at
                  FROM assets a
                  {$catJoin}
                  LEFT JOIN users u ON a.assigned_to = u.id" .
                  $whereClause . "
                  ORDER BY a.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Assets_Export_' . date('Y-m-d_His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Generate Excel XML format
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
        echo "<Worksheet ss:Name=\"Assets\">\n";
        echo "<Table>\n";
        
        // Header row with styling
        echo "<Row>\n";
        $headers = ['Asset Code', 'Asset Name', 'Category', 'Description', 'Status', 'Condition', 
                   'Location', 'Purchase Date', 'Purchase Cost', 'Current Value', 
                   'Assigned To', 'Department', 'QR Code', 'Created At'];
        foreach ($headers as $header) {
            echo "<Cell><Data ss:Type=\"String\"><b>" . htmlspecialchars($header) . "</b></Data></Cell>\n";
        }
        echo "</Row>\n";
        
        // Data rows
        foreach ($assets as $asset) {
            echo "<Row>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['asset_code'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['name'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['category'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['description'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['status'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['condition_status'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['location'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['purchase_date'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"Number\">" . htmlspecialchars($asset['purchase_cost'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"Number\">" . htmlspecialchars($asset['current_value'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['assigned_to'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['assigned_department'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['qr_code'] ?? '') . "</Data></Cell>\n";
            echo "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($asset['created_at'] ?? '') . "</Data></Cell>\n";
            echo "</Row>\n";
        }
        
        echo "</Table>\n";
        echo "</Worksheet>\n";
        echo "</Workbook>\n";
        
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Error exporting assets", "error" => $e->getMessage()));
    }
}
?>
