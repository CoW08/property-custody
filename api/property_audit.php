<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Database connection error", "error" => $e->getMessage()));
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch($method) {
    case 'GET':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'list':
                    getAudits($db);
                    break;
                case 'details':
                    if(isset($_GET['id'])) {
                        getAuditDetails($db, $_GET['id']);
                    }
                    break;
                case 'stats':
                    getAuditStats($db);
                    break;
                case 'findings':
                    if(isset($_GET['audit_id'])) {
                        getAuditFindings($db, $_GET['audit_id']);
                    }
                    break;
                default:
                    getAudits($db);
            }
        } else {
            getAudits($db);
        }
        break;

    case 'POST':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'create':
                    createAudit($db, $input);
                    break;
                case 'add_finding':
                    addAuditFinding($db, $input);
                    break;
                default:
                    createAudit($db, $input);
            }
        } else {
            createAudit($db, $input);
        }
        break;

    case 'PUT':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'update':
                    updateAudit($db, $input);
                    break;
                case 'update_status':
                    updateAuditStatus($db, $input);
                    break;
                case 'update_finding':
                    updateAuditFinding($db, $input);
                    break;
            }
        }
        break;

    case 'DELETE':
        if(isset($_GET['id'])) {
            deleteAudit($db, $_GET['id']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getAudits($db) {
    try {
        $query = "SELECT pa.*,
                         u.username as auditor_name,
                         COUNT(af.id) as total_findings
                  FROM property_audits pa
                  LEFT JOIN users u ON pa.auditor_id = u.id
                  LEFT JOIN audit_findings af ON pa.id = af.audit_id
                  GROUP BY pa.id
                  ORDER BY pa.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $audits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array("status" => "success", "data" => $audits));
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function getAuditDetails($db, $id) {
    try {
        $query = "SELECT pa.*,
                         u.username as auditor_name
                  FROM property_audits pa
                  LEFT JOIN users u ON pa.auditor_id = u.id
                  WHERE pa.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $audit = $stmt->fetch(PDO::FETCH_ASSOC);

        if($audit) {
            echo json_encode(array("status" => "success", "data" => $audit));
        } else {
            http_response_code(404);
            echo json_encode(array("status" => "error", "message" => "Audit not found"));
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function getAuditStats($db) {
    try {
        $stats = array();

        // Active audits
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM property_audits WHERE status IN ('planned', 'in_progress')");
        $stmt->execute();
        $stats['active_audits'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Completed audits
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM property_audits WHERE status = 'completed'");
        $stmt->execute();
        $stats['completed_audits'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Pending review (completed but with open findings)
        $stmt = $db->prepare("SELECT COUNT(DISTINCT pa.id) as count
                             FROM property_audits pa
                             INNER JOIN audit_findings af ON pa.id = af.audit_id
                             WHERE pa.status = 'completed' AND af.status IN ('open', 'in_progress')");
        $stmt->execute();
        $stats['pending_review'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total discrepancies
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM audit_findings WHERE status IN ('open', 'in_progress')");
        $stmt->execute();
        $stats['total_discrepancies'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode(array("status" => "success", "data" => $stats));
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function getAuditFindings($db, $audit_id) {
    try {
        $query = "SELECT af.*,
                         a.asset_code, a.description as asset_description,
                         u.username as responsible_person_name
                  FROM audit_findings af
                  LEFT JOIN assets a ON af.asset_id = a.id
                  LEFT JOIN users u ON af.responsible_person = u.id
                  WHERE af.audit_id = ?
                  ORDER BY af.severity DESC, af.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$audit_id]);
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array("status" => "success", "data" => $findings));
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function createAudit($db, $data) {
    try {
        // Generate audit code
        $audit_code = 'AUD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Get auditor_id from session or default to 1
        $auditor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

        $query = "INSERT INTO property_audits (audit_code, audit_type, start_date, auditor_id, department, status, summary)
                  VALUES (?, ?, ?, ?, ?, 'planned', ?)";

        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            $audit_code,
            $data['audit_type'] ?? 'physical_inventory',
            $data['start_date'] ?? date('Y-m-d'),
            $auditor_id,
            $data['department'] ?? 'General',
            $data['summary'] ?? ''
        ]);

        if($success) {
            $audit_id = $db->lastInsertId();
            echo json_encode(array(
                "status" => "success",
                "message" => "Audit created successfully",
                "audit_id" => $audit_id,
                "audit_code" => $audit_code
            ));
        } else {
            throw new Exception("Failed to create audit");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function updateAudit($db, $data) {
    try {
        $query = "UPDATE property_audits SET ";
        $params = array();
        $updates = array();

        if(isset($data['audit_type'])) {
            $updates[] = "audit_type = ?";
            $params[] = $data['audit_type'];
        }
        if(isset($data['start_date'])) {
            $updates[] = "start_date = ?";
            $params[] = $data['start_date'];
        }
        if(isset($data['end_date'])) {
            $updates[] = "end_date = ?";
            $params[] = $data['end_date'];
        }
        if(isset($data['department'])) {
            $updates[] = "department = ?";
            $params[] = $data['department'];
        }
        if(isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        if(isset($data['summary'])) {
            $updates[] = "summary = ?";
            $params[] = $data['summary'];
        }
        if(isset($data['total_assets_audited'])) {
            $updates[] = "total_assets_audited = ?";
            $params[] = $data['total_assets_audited'];
        }
        if(isset($data['discrepancies_found'])) {
            $updates[] = "discrepancies_found = ?";
            $params[] = $data['discrepancies_found'];
        }

        if(empty($updates)) {
            throw new Exception("No data to update");
        }

        $query .= implode(", ", $updates) . " WHERE id = ?";
        $params[] = $data['id'];

        $stmt = $db->prepare($query);
        $success = $stmt->execute($params);

        if($success) {
            echo json_encode(array("status" => "success", "message" => "Audit updated successfully"));
        } else {
            throw new Exception("Failed to update audit");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function updateAuditStatus($db, $data) {
    try {
        $query = "UPDATE property_audits SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$data['status'], $data['id']]);

        if($success) {
            echo json_encode(array("status" => "success", "message" => "Audit status updated successfully"));
        } else {
            throw new Exception("Failed to update audit status");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function addAuditFinding($db, $data) {
    try {
        $query = "INSERT INTO audit_findings (audit_id, asset_id, finding_type, description, severity, corrective_action, responsible_person, target_date, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')";

        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            $data['audit_id'],
            $data['asset_id'] ?? null,
            $data['finding_type'],
            $data['description'],
            $data['severity'] ?? 'medium',
            $data['corrective_action'] ?? '',
            $data['responsible_person'] ?? null,
            $data['target_date'] ?? null
        ]);

        if($success) {
            echo json_encode(array("status" => "success", "message" => "Audit finding added successfully"));
        } else {
            throw new Exception("Failed to add audit finding");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function updateAuditFinding($db, $data) {
    try {
        $query = "UPDATE audit_findings SET ";
        $params = array();
        $updates = array();

        if(isset($data['finding_type'])) {
            $updates[] = "finding_type = ?";
            $params[] = $data['finding_type'];
        }
        if(isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        if(isset($data['severity'])) {
            $updates[] = "severity = ?";
            $params[] = $data['severity'];
        }
        if(isset($data['corrective_action'])) {
            $updates[] = "corrective_action = ?";
            $params[] = $data['corrective_action'];
        }
        if(isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        if(isset($data['target_date'])) {
            $updates[] = "target_date = ?";
            $params[] = $data['target_date'];
        }

        if(empty($updates)) {
            throw new Exception("No data to update");
        }

        $query .= implode(", ", $updates) . " WHERE id = ?";
        $params[] = $data['id'];

        $stmt = $db->prepare($query);
        $success = $stmt->execute($params);

        if($success) {
            echo json_encode(array("status" => "success", "message" => "Audit finding updated successfully"));
        } else {
            throw new Exception("Failed to update audit finding");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}

function deleteAudit($db, $id) {
    try {
        // First delete related findings
        $stmt = $db->prepare("DELETE FROM audit_findings WHERE audit_id = ?");
        $stmt->execute([$id]);

        // Then delete the audit
        $stmt = $db->prepare("DELETE FROM property_audits WHERE id = ?");
        $success = $stmt->execute([$id]);

        if($success) {
            echo json_encode(array("status" => "success", "message" => "Audit deleted successfully"));
        } else {
            throw new Exception("Failed to delete audit");
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }
}
?>