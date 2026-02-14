<?php
/**
 * Waste Management API
 * Ultra-robust: every code path returns JSON, never HTML.
 */

// CRITICAL: suppress ALL output before our JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch ANY stray output
ob_start();

// Global safety net — if ANYTHING uncaught happens, return JSON
set_exception_handler(function ($e) {
    ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    error_log("[WASTE_MGMT] Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error'   => $e->getMessage(),
        'data'    => []
    ]);
    exit;
});

// Convert PHP warnings/notices to logged messages (NOT exceptions - too aggressive)
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("[WASTE_MGMT] PHP error ($severity): $message in $file:$line");
    return true; // suppress the error from outputting
});

// Catch fatal errors too
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        error_log("[WASTE_MGMT] Fatal: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error',
            'error'   => $error['message'],
            'data'    => []
        ]);
    }
});

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Headers (inline - don't rely on cors.php) ────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// ── Auth (inline JSON - don't use requireAuth/requirePermission which may redirect with HTML) ──
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode([
        'success'         => false,
        'message'         => 'Not authenticated',
        'session_expired' => true,
        'data'            => []
    ]);
    exit;
}

// ── Permission check (inline) ─────────────────────────────────────────────────
$permFile = __DIR__ . '/../config/permissions.php';
if (file_exists($permFile)) {
    require_once $permFile;
}

$userRole      = $_SESSION['role'] ?? '';
$hasPermission = false;
if (defined('ROLE_PERMISSIONS') && isset(ROLE_PERMISSIONS[$userRole])) {
    $hasPermission = in_array('waste_management', ROLE_PERMISSIONS[$userRole]);
}

if (!$hasPermission) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied: you do not have waste management permissions (role: ' . $userRole . ')',
        'error'   => 'forbidden',
        'data'    => []
    ]);
    exit;
}

// ── Database (inline - don't rely on Database class) ──────────────────────────
require_once __DIR__ . '/../config/config.php';

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
    error_log("[WASTE_MGMT] DB connection failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'error'   => $e->getMessage(),
        'data'    => []
    ]);
    exit;
}

// ── Ensure table exists ───────────────────────────────────────────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `waste_management_records` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `entity_type` VARCHAR(40) NOT NULL,
        `entity_id` INT UNSIGNED NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `identifier` VARCHAR(120) DEFAULT NULL,
        `status` ENUM('archived', 'restored', 'disposed') NOT NULL DEFAULT 'archived',
        `archived_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `archived_by` INT UNSIGNED DEFAULT NULL,
        `archive_reason` VARCHAR(255) DEFAULT NULL,
        `archive_notes` TEXT DEFAULT NULL,
        `metadata` LONGTEXT DEFAULT NULL,
        `disposed_at` DATETIME DEFAULT NULL,
        `disposed_by` INT UNSIGNED DEFAULT NULL,
        `disposal_method` VARCHAR(120) DEFAULT NULL,
        `disposal_notes` TEXT DEFAULT NULL,
        `restored_at` DATETIME DEFAULT NULL,
        `restored_by` INT UNSIGNED DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_entity` (`entity_type`, `entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Throwable $e) {
    error_log("[WASTE_MGMT] Table creation error (non-fatal): " . $e->getMessage());
}

// Ensure disposal columns exist (for tables created by older schema)
try {
    $cols = $db->query("SHOW COLUMNS FROM waste_management_records LIKE 'disposal_method'");
    if ($cols->rowCount() === 0) {
        $db->exec("ALTER TABLE waste_management_records ADD COLUMN disposal_method VARCHAR(120) DEFAULT NULL");
    }
} catch (Throwable $e) { /* ignore */ }

try {
    $cols2 = $db->query("SHOW COLUMNS FROM waste_management_records LIKE 'disposal_notes'");
    if ($cols2->rowCount() === 0) {
        $db->exec("ALTER TABLE waste_management_records ADD COLUMN disposal_notes TEXT DEFAULT NULL");
    }
} catch (Throwable $e) { /* ignore */ }

// ── Load archive helpers (for restore) ────────────────────────────────────────
$archiveFile = __DIR__ . '/../includes/archive_helpers.php';
if (file_exists($archiveFile)) {
    require_once $archiveFile;
}

// ── Route ─────────────────────────────────────────────────────────────────────
// Flush any buffered stray output before sending our JSON
ob_end_clean();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    listWasteRecords($db);
} elseif ($method === 'POST') {
    $inputRaw = file_get_contents('php://input');
    $payload  = [];
    if (!empty($inputRaw)) {
        $decoded = json_decode($inputRaw, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    $action = $_GET['action'] ?? ($payload['action'] ?? '');

    if ($action === 'restore') {
        restoreWasteRecord($db, $payload);
    } elseif ($action === 'dispose') {
        disposeWasteRecord($db, $payload);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action', 'data' => []]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed', 'data' => []]);
}
exit;

// ═══════════════════════════════════════════════════════════════════════════════
// Functions
// ═══════════════════════════════════════════════════════════════════════════════

function listWasteRecords(PDO $db): void
{
    try {
        $entityType = $_GET['entity_type'] ?? null;
        $status     = $_GET['status'] ?? null;
        $search     = $_GET['search'] ?? null;

        $conditions = [];
        $params     = [];

        if (!empty($entityType)) {
            $conditions[]           = 'wmr.entity_type = :entity_type';
            $params[':entity_type'] = $entityType;
        }
        if (!empty($status)) {
            $conditions[]      = 'wmr.status = :status';
            $params[':status'] = $status;
        }
        if (!empty($search)) {
            $conditions[]      = '(wmr.name LIKE :search OR wmr.identifier LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Detect available columns to avoid errors on older schemas
        $hasDisposalCols = false;
        try {
            $db->query("SELECT disposal_method FROM waste_management_records LIMIT 0");
            $hasDisposalCols = true;
        } catch (Throwable $e) { /* columns don't exist */ }

        // Check if users table has full_name column
        $hasUsersTable = false;
        try {
            $db->query("SELECT id, full_name FROM users LIMIT 0");
            $hasUsersTable = true;
        } catch (Throwable $e) { /* users table missing or different schema */ }

        // Build query dynamically
        $selectCols = "wmr.id, wmr.entity_type, wmr.entity_id, wmr.name, wmr.identifier,
            wmr.status, wmr.archived_at, wmr.archived_by, wmr.archive_reason,
            wmr.archive_notes, wmr.metadata, wmr.disposed_at, wmr.disposed_by,
            wmr.restored_at, wmr.restored_by, wmr.created_at, wmr.updated_at";

        if ($hasDisposalCols) {
            $selectCols .= ", wmr.disposal_method, wmr.disposal_notes";
        }

        $joins = '';
        if ($hasUsersTable) {
            $selectCols .= ",
                COALESCE(u1.full_name, 'System') AS archived_by_name,
                COALESCE(u2.full_name, CASE WHEN wmr.disposed_by IS NOT NULL THEN 'Unknown User' ELSE '' END) AS disposed_by_name";
            $joins = "LEFT JOIN users u1 ON wmr.archived_by = u1.id
                      LEFT JOIN users u2 ON wmr.disposed_by = u2.id";
        }

        $query = "SELECT {$selectCols}
                  FROM waste_management_records wmr
                  {$joins}
                  {$whereClause}
                  ORDER BY wmr.archived_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $records = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['metadata']         = !empty($row['metadata']) ? json_decode($row['metadata'], true) : null;
            $row['archived_by_name'] = $row['archived_by_name'] ?? 'System';
            $row['disposed_by_name'] = $row['disposed_by_name'] ?? '';
            $row['disposal_method']  = $row['disposal_method'] ?? '';
            $row['disposal_notes']   = $row['disposal_notes'] ?? '';
            $row['identifier']       = $row['identifier'] ?? 'N/A';
            $row['entity_type']      = $row['entity_type'] ?? 'unknown';
            $records[]               = $row;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data'    => $records,
        ]);

    } catch (Throwable $e) {
        error_log("[WASTE_MGMT] listWasteRecords error: " . $e->getMessage());
        // Return 200 with empty data so the page still loads gracefully
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data'    => [],
            'warning' => 'Could not load records: ' . $e->getMessage(),
        ]);
    }
}

function restoreWasteRecord(PDO $db, array $payload): void
{
    $id = isset($payload['id']) ? intval($payload['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Record ID is required']);
        return;
    }

    $stmt = $db->prepare('SELECT * FROM waste_management_records WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Waste record not found']);
        return;
    }

    $entityType = $record['entity_type'];
    $entityId   = intval($record['entity_id']);

    try {
        $db->beginTransaction();

        if (function_exists('clearArchiveState')) {
            if ($entityType === 'asset') {
                clearArchiveState($db, 'assets', $entityId);
            } elseif ($entityType === 'supply') {
                clearArchiveState($db, 'supplies', $entityId);
            }
        }

        $update = $db->prepare('UPDATE waste_management_records SET status = "restored", restored_at = NOW(), restored_by = :user_id WHERE id = :id');
        $update->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':id'      => $id,
        ]);

        $db->commit();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Record restored successfully']);
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to restore record: ' . $throwable->getMessage(),
            'error'   => $throwable->getMessage(),
        ]);
    }
}

function disposeWasteRecord(PDO $db, array $payload): void
{
    $id = isset($payload['id']) ? intval($payload['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Record ID is required']);
        return;
    }

    $stmt = $db->prepare('SELECT entity_type, entity_id FROM waste_management_records WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Waste record not found']);
        return;
    }

    $method = $payload['disposal_method'] ?? 'standard';
    $notes  = $payload['disposal_notes'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        $checkUser = $db->prepare('SELECT archived_by FROM waste_management_records WHERE id = :id');
        $checkUser->execute([':id' => $id]);
        $rec    = $checkUser->fetch(PDO::FETCH_ASSOC);
        $userId = $rec['archived_by'] ?? null;
    }

    try {
        $db->beginTransaction();

        $update = $db->prepare('UPDATE waste_management_records SET status = "disposed", disposed_at = NOW(), disposed_by = :user_id, disposal_method = :method, disposal_notes = :notes WHERE id = :id');
        $update->execute([
            ':user_id' => $userId,
            ':method'  => $method,
            ':notes'   => $notes,
            ':id'      => $id,
        ]);

        $entityType = $record['entity_type'] ?? null;
        $entityId   = isset($record['entity_id']) ? (int) $record['entity_id'] : 0;

        if ($entityType && $entityId > 0) {
            if ($entityType === 'asset') {
                try {
                    $db->prepare('UPDATE assets SET status = "disposed" WHERE id = :eid')->execute([':eid' => $entityId]);
                } catch (Throwable $e) { /* ignore if assets table differs */ }
            } elseif ($entityType === 'supply') {
                try {
                    $db->prepare('UPDATE supplies SET status = "discontinued" WHERE id = :eid')->execute([':eid' => $entityId]);
                } catch (Throwable $e) { /* ignore */ }
            }
        }

        $db->commit();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Record marked as disposed']);
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to dispose record: ' . $throwable->getMessage(),
            'error'   => $throwable->getMessage(),
        ]);
    }
}
