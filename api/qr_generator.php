<?php
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        generateQRCode($db);
        break;
    case 'GET':
        if(isset($_GET['asset_id'])) {
            getQRCode($db, $_GET['asset_id']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function generateQRCode($db) {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->asset_id)) {
        // Get asset details
        $assetQuery = "SELECT asset_code, name FROM assets WHERE id = ?";
        $assetStmt = $db->prepare($assetQuery);
        $assetStmt->execute([$data->asset_id]);

        if($assetStmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Asset not found"));
            return;
        }

        $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);

        // Generate QR code data (URL or JSON with asset info)
        $qrData = json_encode([
            'asset_id' => $data->asset_id,
            'asset_code' => $asset['asset_code'],
            'name' => $asset['name'],
            'system' => 'property_custodian',
            'generated_at' => date('Y-m-d H:i:s')
        ]);

        // Generate unique QR code identifier
        $qrCodeId = 'QR_' . $asset['asset_code'] . '_' . time();

        // Update asset with QR code
        $updateQuery = "UPDATE assets SET qr_code = ?, qr_generated = TRUE WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);

        if($updateStmt->execute([$qrCodeId, $data->asset_id])) {
            // Log the activity
            logActivity($db, $_SESSION['user_id'], 'generate_qr', 'assets', $data->asset_id);

            http_response_code(200);
            echo json_encode([
                "message" => "QR code generated successfully",
                "qr_code_id" => $qrCodeId,
                "qr_data" => $qrData,
                "qr_url" => generateQRCodeURL($qrData)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to generate QR code"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Asset ID is required"));
    }
}

function getQRCode($db, $asset_id) {
    $query = "SELECT a.qr_code, a.asset_code, a.name, a.qr_generated
              FROM assets a WHERE a.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$asset_id]);

    if($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($result['qr_generated'] && !empty($result['qr_code'])) {
            $qrData = json_encode([
                'asset_id' => $asset_id,
                'asset_code' => $result['asset_code'],
                'name' => $result['name'],
                'system' => 'property_custodian'
            ]);

            http_response_code(200);
            echo json_encode([
                "qr_code_id" => $result['qr_code'],
                "qr_data" => $qrData,
                "qr_url" => generateQRCodeURL($qrData),
                "generated" => true
            ]);
        } else {
            http_response_code(200);
            echo json_encode([
                "generated" => false,
                "message" => "QR code not generated for this asset"
            ]);
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Asset not found"));
    }
}

function generateQRCodeURL($data) {
    // Using Google Charts API for QR code generation (free service)
    $encodedData = urlencode($data);
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . $encodedData;
}

function logActivity($db, $user_id, $action, $table_name, $record_id) {
    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $table_name, $record_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}
?>