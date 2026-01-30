<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Simple PDF generation without external libraries
class AccountabilityPDF {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function generate() {
        // Set headers for HTML display (printable)
        header('Content-Type: text/html; charset=UTF-8');
        
        $html = $this->generateHTML();
        
        // Add print CSS and auto-print option
        $html = str_replace('</head>', '
        <style>
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
            }
        </style>
        </head>', $html);
        
        // Add print button at top
        $printButton = '<div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
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
        
        $html = str_replace('<body>', '<body>' . $printButton, $html);
        
        echo $html;
    }
    
    public function generateHTML() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Accountability Transfer Document</title>
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
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 10px 0;
            color: #1e40af;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #2563eb;
        }
        .section h2 {
            color: #1e40af;
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
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
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
            color: rgba(37, 99, 235, 0.05);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL DOCUMENT</div>
    
    <div class="header">
        <h1>ACCOUNTABILITY TRANSFER DOCUMENT</h1>
        <p>Property Custodian Management System</p>
        <p><strong>Document No:</strong> ACT-' . str_pad($this->data['assignment_id'], 6, '0', STR_PAD_LEFT) . '</p>
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
            <div class="info-label">Category:</div>
            <div class="info-value">' . htmlspecialchars($this->data['category'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Description:</div>
            <div class="info-value">' . htmlspecialchars($this->data['description'] ?? 'N/A') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Transfer Details</h2>
        <div class="info-row">
            <div class="info-label">Assigned To:</div>
            <div class="info-value">' . htmlspecialchars($this->data['custodian_name'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Department:</div>
            <div class="info-value">' . htmlspecialchars($this->data['department'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">' . htmlspecialchars($this->data['email'] ?? 'N/A') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Assignment Date:</div>
            <div class="info-value">' . date('F d, Y', strtotime($this->data['assignment_date'] ?? 'now')) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Purpose:</div>
            <div class="info-value">' . htmlspecialchars($this->data['purpose'] ?? 'N/A') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Approval Information</h2>
        <div class="info-row">
            <div class="info-label">Approved By:</div>
            <div class="info-value">' . htmlspecialchars($this->data['approved_by_name'] ?? 'Property Custodian') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Approval Date:</div>
            <div class="info-value">' . date('F d, Y h:i A', strtotime($this->data['approved_at'] ?? 'now')) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value"><strong>APPROVED</strong></div>
        </div>
    </div>
    
    <div class="section">
        <h2>Terms and Conditions</h2>
        <p>By accepting this asset, the custodian acknowledges and agrees to:</p>
        <ol>
            <li>Take full responsibility for the proper care and use of the assigned asset</li>
            <li>Report any damage, loss, or malfunction immediately to the Property Custodian</li>
            <li>Use the asset only for authorized and legitimate purposes</li>
            <li>Return the asset in good condition upon request or end of assignment period</li>
            <li>Be liable for any damage or loss resulting from negligence or misuse</li>
        </ol>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">';
        
        // Check if staff signature image exists
        $staffSignaturePath = __DIR__ . '/signatures/staff_signature.png';
        if (file_exists($staffSignaturePath)) {
            $html .= '<div style="text-align: center; margin-bottom: 10px;">
                <img src="signatures/staff_signature.png" alt="Signature" style="max-width: 150px; height: auto;">
            </div>';
        }
        
        $html .= '<div class="signature-line">
                <strong>' . htmlspecialchars($this->data['custodian_name'] ?? 'Asset Custodian') . '</strong><br>
                Asset Custodian / Receiver<br>
                Date: _________________
            </div>
        </div>
        <div class="signature-box">';
        
        // Check if custodian signature image exists
        $custodianSignaturePath = __DIR__ . '/signatures/custodian_signature.png';
        if (file_exists($custodianSignaturePath)) {
            $html .= '<div style="text-align: center; margin-bottom: 10px;">
                <img src="signatures/custodian_signature.png" alt="Signature" style="max-width: 200px; height: auto;">
            </div>';
        }
        
        $html .= '<div class="signature-line">
                <strong>' . htmlspecialchars($this->data['approved_by_name'] ?? 'Property Custodian') . '</strong><br>
                Property Custodian / Approver<br>
                Date: ' . date('F d, Y', strtotime($this->data['approved_at'] ?? 'now')) . '
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>IMPORTANT:</strong> This document serves as official proof of asset transfer and accountability.</p>
        <p>Keep this document for your records. Any discrepancies should be reported immediately.</p>
        <p>Property Custodian Management System | Generated on ' . date('F d, Y h:i A') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    public function save($filename) {
        $html = $this->generateHTML();
        file_put_contents($filename, $html);
        return $filename;
    }
}

// Handle PDF generation request
if (isset($_GET['assignment_id'])) {
    $assignment_id = intval($_GET['assignment_id']);
    
    // Check authentication (session already started by auth_check.php)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized access');
    }
    
    $current_user_id = $_SESSION['user_id'];
    $current_user_role = $_SESSION['role'] ?? 'staff';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch assignment details
    $query = "SELECT 
                pa.id as assignment_id,
                pa.assignment_date,
                COALESCE(pa.assignment_purpose, '') as purpose,
                a.asset_code,
                a.name as asset_name,
                a.category,
                COALESCE(a.description, 'N/A') as description,
                u.id as custodian_user_id,
                u.full_name as custodian_name,
                u.email,
                u.department,
                approver.full_name as approved_by_name,
                COALESCE(ar.reviewed_at, pa.created_at, NOW()) as approved_at
              FROM property_assignments pa
              JOIN assets a ON pa.asset_id = a.id
              JOIN custodians c ON pa.custodian_id = c.id
              JOIN users u ON c.user_id = u.id
              LEFT JOIN users approver ON pa.assigned_by = approver.id
              LEFT JOIN assignment_requests ar ON ar.asset_id = a.id AND ar.requester_id = u.id AND ar.status = 'approved'
              WHERE pa.id = :assignment_id AND pa.status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':assignment_id', $assignment_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Security check: Staff can only view their own assignments
        if ($current_user_role === 'staff' && $data['custodian_user_id'] != $current_user_id) {
            http_response_code(403);
            die('You can only view your own assignment documents');
        }
        
        // Ensure all required fields have values
        $data['approved_by_name'] = $data['approved_by_name'] ?? 'Property Custodian';
        $data['approved_at'] = $data['approved_at'] ?? date('Y-m-d H:i:s');
        
        $pdf = new AccountabilityPDF($data);
        $pdf->generate();
    } else {
        // Debug info
        echo "<h3>Assignment not found</h3>";
        echo "<p>Assignment ID: " . htmlspecialchars($assignment_id) . "</p>";
        echo "<p><a href='custodian-assignment.php'>Go back</a></p>";
        
        // Check if assignment exists at all
        $checkQuery = "SELECT COUNT(*) as count FROM property_assignments WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':id' => $assignment_id]);
        $count = $checkStmt->fetchColumn();
        echo "<p>Assignment exists in database: " . ($count > 0 ? 'Yes' : 'No') . "</p>";
    }
} else {
    echo "No assignment ID provided";
}
?>
