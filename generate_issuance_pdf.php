<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Property Issuance Receipt PDF Generator
class IssuanceReceiptPDF {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function generate() {
        // Set headers for HTML display (printable)
        header('Content-Type: text/html; charset=UTF-8');
        
        $html = $this->generateHTML();
        
        // Add print CSS
        $html = str_replace('</head>', '
        <style>
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
            }
        </style>
        </head>', $html);
        
        // Add action buttons at top
        $actionButtons = '<div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fas fa-download"></i> Download PDF
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
    <title>Property Issuance Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #16a34a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 10px 0;
            color: #15803d;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #16a34a;
        }
        .section h2 {
            color: #15803d;
            margin-top: 0;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: bold;
            width: 200px;
            color: #475569;
        }
        .info-value {
            flex: 1;
            color: #1e293b;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-issued {
            background: #dcfce7;
            color: #166534;
        }
        .status-returned {
            background: #dbeafe;
            color: #1e40af;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-image {
            width: 360px;
            max-width: 100%;
            height: 160px;
            object-fit: contain;
        }
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 15px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(22, 163, 74, 0.05);
            font-weight: bold;
            z-index: -1;
        }
        .acknowledgment-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .acknowledgment-box h3 {
            color: #92400e;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL RECEIPT</div>
    
    <div class="header">
        <h1>PROPERTY ISSUANCE RECEIPT</h1>
        <p>Property Custodian Management System</p>
        <p><strong>Receipt No:</strong> ISS-' . str_pad($this->data['id'] ?? '0', 6, '0', STR_PAD_LEFT) . '</p>
        <p><strong>Date Generated:</strong> ' . date('F d, Y h:i A') . '</p>
    </div>
    
    <div class="section">
        <h2>Asset Information</h2>
        <div class="info-row">
            <div class="info-label">Asset Code:</div>
            <div class="info-value">' . htmlspecialchars($this->data['asset_code'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Asset Name:</div>
            <div class="info-value">' . htmlspecialchars($this->data['asset_name'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Description:</div>
            <div class="info-value">' . htmlspecialchars($this->data['asset_description'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Quantity:</div>
            <div class="info-value">' . htmlspecialchars($this->data['quantity'] ?? '1') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Recipient Information</h2>
        <div class="info-row">
            <div class="info-label">Employee ID:</div>
            <div class="info-value">' . htmlspecialchars($this->data['employee_id'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Recipient Name:</div>
            <div class="info-value">' . htmlspecialchars($this->data['recipient_name'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Department:</div>
            <div class="info-value">' . htmlspecialchars(ucfirst($this->data['department'] ?? 'N/A')) . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Issuance Details</h2>
        <div class="info-row">
            <div class="info-label">Issue Date:</div>
            <div class="info-value">' . date('F d, Y', strtotime($this->data['issue_date'] ?? 'now')) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Expected Return Date:</div>
            <div class="info-value">' . ($this->data['expected_return_date'] ? date('F d, Y', strtotime($this->data['expected_return_date'])) : 'Not Specified') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Purpose:</div>
            <div class="info-value">' . htmlspecialchars($this->data['purpose'] ?? 'General Use') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Issued By:</div>
            <div class="info-value">' . htmlspecialchars($this->data['issued_by_name'] ?? 'Property Custodian') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                <span class="status-badge status-' . strtolower($this->data['status'] ?? 'issued') . '">' . strtoupper($this->data['status'] ?? 'issued') . '</span>
            </div>
        </div>
    </div>
    
    <div class="acknowledgment-box">
        <h3><i class="fas fa-exclamation-triangle"></i> Recipient Acknowledgment</h3>
        <p>I hereby acknowledge receipt of the above-mentioned property/item in good condition.</p>
        <p>I understand and agree to:</p>
        <ul>
            <li>Take full responsibility for the proper care and use of the issued item</li>
            <li>Use the item only for authorized and legitimate purposes</li>
            <li>Report any damage, loss, or malfunction immediately</li>
            <li>Return the item in good condition on or before the expected return date</li>
            <li>Be liable for any damage or loss resulting from negligence or misuse</li>
        </ul>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">';
        
        // Recipient signature (uploaded or drawn)
        if (!empty($this->data['recipient_signature'])) {
            $html .= '<div style="text-align: center; margin-bottom: 10px;">
                <img src="' . htmlspecialchars($this->data['recipient_signature']) . '" alt="Recipient Signature" class="signature-image">
            </div>';
        } else {
            $staffSignaturePath = __DIR__ . '/signatures/staff_signature.png';
            if (file_exists($staffSignaturePath)) {
                $html .= '<div style="text-align: center; margin-bottom: 10px;">
                    <img src="signatures/staff_signature.png" alt="Signature" class="signature-image">
                </div>';
            }
        }
        
        $html .= '<div class="signature-line">
                <strong>' . htmlspecialchars($this->data['recipient_name'] ?? 'Recipient') . '</strong><br>
                Recipient Signature<br>
                Date: _________________
            </div>
        </div>
        <div class="signature-box">';
        
        // Check if custodian signature image exists
        $custodianSignaturePath = __DIR__ . '/signatures/custodian_signature.png';
        if (file_exists($custodianSignaturePath)) {
            $html .= '<div style="text-align: center; margin-bottom: 10px;">
                <img src="signatures/custodian_signature.png" alt="Signature" class="signature-image">
            </div>';
        }
        
        $html .= '<div class="signature-line">
                <strong>' . htmlspecialchars($this->data['issued_by_name'] ?? 'Property Custodian') . '</strong><br>
                Property Custodian<br>
                Date: ' . date('F d, Y', strtotime($this->data['issue_date'] ?? 'now')) . '
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>IMPORTANT:</strong> This receipt serves as official proof of property issuance.</p>
        <p>Please keep this document for your records and present it when returning the item.</p>
        <p>Property Custodian Management System | Generated on ' . date('F d, Y h:i A') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// Handle PDF generation request
if (isset($_GET['issuance_id'])) {
    $issuance_id = intval($_GET['issuance_id']);
    
    // Check authentication (session already started by auth_check.php)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized access');
    }
    
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'] ?? 'staff';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch issuance details
    $query = "SELECT 
                pi.id,
                pi.asset_id,
                pi.employee_id,
                pi.recipient_name,
                pi.recipient_signature,
                pi.issue_date,
                pi.expected_return_date,
                pi.purpose,
                pi.remarks,
                pi.status,
                pi.department,
                a.asset_code,
                a.name as asset_name,
                a.description as asset_description,
                a.category,
                a.purchase_cost,
                a.condition_status,
                a.location,
                issuer.full_name as issued_by_name
              FROM property_issuances pi
              JOIN assets a ON pi.asset_id = a.id
              LEFT JOIN users issuer ON pi.issued_by = issuer.id
              WHERE pi.id = :issuance_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':issuance_id', $issuance_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Security check: Staff can only view their own issuances
        if ($current_user_role === 'staff' && $data['employee_id'] != $current_user_id) {
            http_response_code(403);
            die('You can only view your own issuance receipts');
        }
        
        // Ensure all required fields have values
        $data['issued_by_name'] = $data['issued_by_name'] ?? 'Property Custodian';
        $data['quantity'] = 1; // Property issuances are always 1 item (one asset per issuance)
        
        $pdf = new IssuanceReceiptPDF($data);
        $pdf->generate();
    } else {
        echo "<h3>Issuance record not found</h3>";
        echo "<p>Issuance ID: " . htmlspecialchars($issuance_id) . "</p>";
        echo "<p><a href='property-issuance.php'>Go back</a></p>";
    }
} else {
    echo "No issuance ID provided";
}
?>
