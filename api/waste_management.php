<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/archive_helpers.php';

requireAuth();
requirePermission('waste_management');

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
}
catch (Exception $exception) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Database connection error',
        'error' => $exception->getMessage(),
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$inputRaw = file_get_contents('php://input');
$payload = [];
if (!empty($inputRaw)) {
    $decoded = json_decode($inputRaw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

switch ($method) {
    case 'GET':
        listWasteRecords($db);
        break;
    case 'POST':
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        if ($action === 'restore') {
            restoreWasteRecord($db, $payload);
        }
        elseif ($action === 'dispose') {
            disposeWasteRecord($db, $payload);
        }
        else {
            http_response_code(400);
            echo json_encode(['message' => 'Unknown action']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function listWasteRecords(PDO $db): void
{
    $entityType = $_GET['entity_type'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    $conditions = [];
    $params = [];

    if (!empty($entityType)) {
        $conditions[] = 'entity_type = :entity_type';
        $params[':entity_type'] = $entityType;
    }

    if (!empty($status)) {
        $conditions[] = 'status = :status';
        $params[':status'] = $status;
    }

    if (!empty($search)) {
        $conditions[] = '(name LIKE :search OR identifier LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $query = "SELECT 
        wmr.*,
        u1.full_name AS archived_by_name,
        u2.full_name AS disposed_by_name
    FROM waste_management_records wmr
    LEFT JOIN users u1 ON wmr.archived_by = u1.id
    LEFT JOIN users u2 ON wmr.disposed_by = u2.id
    {$whereClause} 
    ORDER BY wmr.archived_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $records = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['metadata'] = !empty($row['metadata']) ? json_decode($row['metadata'], true) : null;
        $records[] = $row;
    }

    http_response_code(200);
    echo json_encode([
        'data' => $records,
    ]);
}

function restoreWasteRecord(PDO $db, array $payload): void
{
    $id = isset($payload['id']) ? intval($payload['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Record ID is required']);
        return;
    }

    $stmt = $db->prepare('SELECT * FROM waste_management_records WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        echo json_encode(['message' => 'Waste record not found']);
        return;
    }

    $entityType = $record['entity_type'];
    $entityId = intval($record['entity_id']);

    try {
        $db->beginTransaction();

        if ($entityType === 'asset') {
            clearArchiveState($db, 'assets', $entityId);
        }
        elseif ($entityType === 'supply') {
            clearArchiveState($db, 'supplies', $entityId);
        }

        $update = $db->prepare('UPDATE waste_management_records SET status = "restored", restored_at = NOW(), restored_by = :user_id WHERE id = :id');
        $update->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':id' => $id,
        ]);

        $db->commit();

        http_response_code(200);
        echo json_encode(['message' => 'Record restored successfully']);
    }
    catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'message' => 'Failed to restore record',
            'error' => $throwable->getMessage(),
        ]);
    }
}

function disposeWasteRecord(PDO $db, array $payload): void
{
    $id = isset($payload['id']) ? intval($payload['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Record ID is required']);
        return;
    }

    $method = $payload['disposal_method'] ?? null;
    $notes = $payload['disposal_notes'] ?? null;

    try {
        $stmt = $db->prepare('UPDATE waste_management_records SET status = "disposed", disposed_at = NOW(), disposed_by = :user_id, disposal_method = :method, disposal_notes = :notes WHERE id = :id');
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':method' => $method,
            ':notes' => $notes,
            ':id' => $id,
        ]);

        http_response_code(200);
        echo json_encode(['message' => 'Record marked as disposed']);
    }
    catch (Throwable $throwable) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Failed to update record',
            'error' => $throwable->getMessage(),
        ]);
    }
}
