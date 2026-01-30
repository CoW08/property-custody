<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Asset Label/Tag PDF Generator
class AssetLabelPDF {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function generate() {
        header('Content-Type: text/html; charset=UTF-8');
        
        $html = $this->generateHTML();
        
        // Add print CSS
        $html = str_replace('</head>', '
        <style>
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
                @page { size: A4; margin: 1cm; }
            }
        </style>
        </head>', $html);
        
        // Add action buttons
        $actionButtons = '<div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fas fa-download"></i> Download Label
            </button>
            <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                Close
            </button>
        </div>';
        
        $html = str_replace('<body>', '<body>' . $actionButtons, $html);
        
        echo $html;
    }
    
    public function generateHTML() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Asset Label - ' . htmlspecialchars($this->data['asset_code']) . '</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 20px;
        }
        .label-container {
            border: 3px solid #2563eb;
            border-radius: 10px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 10px 0;
            color: #1e40af;
            font-size: 24px;
        }
        .asset-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .info-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
            display: inline-block;
            width: 140px;
        }
        .info-value {
            color: #1e293b;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #ffffff;
            border: 2px dashed #2563eb;
            border-radius: 8px;
        }
        .qr-section img {
            max-width: 250px;
            height: auto;
        }
        .asset-code-large {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
            text-align: center;
            padding: 15px;
            background: #dbeafe;
            border-radius: 8px;
            margin: 20px 0;
            letter-spacing: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-available { background: #dcfce7; color: #166534; }
        .status-assigned { background: #fef3c7; color: #92400e; }
        .status-maintenance { background: #fce7f3; color: #9f1239; }
        .status-retired { background: #f3f4f6; color: #374151; }
        .footer-note {
            margin-top: 30px;
            padding: 15px;
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="header">
            <h1><i class="fas fa-tag"></i> ASSET LABEL</h1>
            <p>Property Custodian Management System</p>
        </div>
        
        <div class="asset-code-large">
            ' . htmlspecialchars($this->data['asset_code']) . '
        </div>
        
        <div class="asset-info">
            <div class="info-section">
                <h3 style="margin-top: 0; color: #1e40af;"><i class="fas fa-info-circle"></i> Asset Information</h3>
                <div class="info-row">
                    <span class="info-label">Asset Name:</span>
                    <span class="info-value">' . htmlspecialchars($this->data['name']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Asset Code:</span>
                    <span class="info-value">' . htmlspecialchars($this->data['asset_code']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value">' . htmlspecialchars($this->data['category_name'] ?? 'N/A') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-' . strtolower($this->data['status'] ?? 'available') . '">
                            ' . strtoupper($this->data['status'] ?? 'AVAILABLE') . '
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="info-section">
                <h3 style="margin-top: 0; color: #1e40af;"><i class="fas fa-clipboard"></i> Details</h3>
                <div class="info-row">
                    <span class="info-label">Description:</span>
                    <span class="info-value">' . htmlspecialchars($this->data['description'] ?? 'N/A') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value">' . htmlspecialchars($this->data['location'] ?? 'N/A') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Condition:</span>
                    <span class="info-value">' . htmlspecialchars(ucfirst($this->data['condition_status'] ?? 'Good')) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Purchase Date:</span>
                    <span class="info-value">' . ($this->data['purchase_date'] ? date('F d, Y', strtotime($this->data['purchase_date'])) : 'N/A') . '</span>
                </div>
            </div>
        </div>
        
        ' . ($this->data['qr_url'] ? '
        <div class="qr-section">
            <h3 style="color: #1e40af; margin-top: 0;"><i class="fas fa-qrcode"></i> QR Code</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">Scan to view asset details</p>
            <img src="' . htmlspecialchars($this->data['qr_url']) . '" alt="QR Code">
            <p style="color: #64748b; font-size: 12px; margin-top: 10px;">QR ID: ' . htmlspecialchars($this->data['qr_code'] ?? 'N/A') . '</p>
        </div>
        ' : '') . '
        
        ' . ($this->data['assigned_to_name'] ? '
        <div class="info-section" style="margin-top: 20px;">
            <h3 style="margin-top: 0; color: #1e40af;"><i class="fas fa-user"></i> Assignment Information</h3>
            <div class="info-row">
                <span class="info-label">Assigned To:</span>
                <span class="info-value">' . htmlspecialchars($this->data['assigned_to_name']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                <span class="info-value">' . htmlspecialchars($this->data['assigned_department'] ?? 'N/A') . '</span>
            </div>
        </div>
        ' : '') . '
        
        <div class="footer-note">
            <p><strong><i class="fas fa-exclamation-triangle"></i> IMPORTANT:</strong> This is an official asset label of the Property Custodian Management System.</p>
            <p>Report any discrepancies or damage immediately to the Property Custodian.</p>
            <p style="margin-top: 10px; color: #64748b;">Label Generated: ' . date('F d, Y h:i A') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// Handle PDF generation request
if (isset($_GET['asset_id'])) {
    $asset_id = intval($_GET['asset_id']);
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized access');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch asset details with QR code
    $query = "SELECT 
                a.*,
                COALESCE(ac.name, 'Uncategorized') as category_name,
                u.full_name as assigned_to_name,
                u.department as assigned_department
              FROM assets a
              LEFT JOIN asset_categories ac ON a.category = ac.id
              LEFT JOIN users u ON a.assigned_to = u.id
              WHERE a.id = :asset_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':asset_id', $asset_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Generate QR URL if QR code exists
        if (!empty($data['qr_code'])) {
            $qrData = json_encode([
                'asset_id' => $asset_id,
                'asset_code' => $data['asset_code'],
                'name' => $data['name'],
                'system' => 'property_custodian'
            ]);
            $data['qr_url'] = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrData);
        } else {
            $data['qr_url'] = null;
        }
        
        $pdf = new AssetLabelPDF($data);
        $pdf->generate();
    } else {
        echo "<h3>Asset not found</h3>";
        echo "<p>Asset ID: " . htmlspecialchars($asset_id) . "</p>";
        echo "<p><a href='asset-registry.php'>Go back</a></p>";
    }
} else {
    echo "No asset ID provided";
}
?>
