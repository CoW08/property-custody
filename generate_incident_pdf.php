<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Incident Report PDF Generator
class IncidentReportPDF {
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
            }
        </style>
        </head>', $html);
        
        // Add action buttons
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
        $severityColors = [
            'minor' => '#10b981',
            'moderate' => '#f59e0b',
            'major' => '#ef4444',
            'critical' => '#7f1d1d'
        ];
        
        $severityColor = $severityColors[$this->data['severity_level']] ?? '#6b7280';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Incident Report - ' . htmlspecialchars($this->data['asset_code']) . '</title>
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
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 10px 0;
            color: #991b1b;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .severity-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            background-color: ' . $severityColor . ';
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #dc2626;
        }
        .section h2 {
            color: #991b1b;
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
        .photos-section {
            margin: 20px 0;
            padding: 15px;
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
        }
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #d1d5db;
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
            color: rgba(220, 38, 38, 0.05);
            font-weight: bold;
            z-index: -1;
        }
        .alert-box {
            background: #fef2f2;
            border: 2px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .alert-box h3 {
            color: #991b1b;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="watermark">INCIDENT REPORT</div>
    
    <div class="header">
        <h1><i class="fas fa-exclamation-triangle"></i> INCIDENT REPORT</h1>
        <p>Property Custodian Management System</p>
        <p><strong>Report No:</strong> INC-' . str_pad($this->data['id'], 6, '0', STR_PAD_LEFT) . '</p>
        <p><strong>Date Generated:</strong> ' . date('F d, Y h:i A') . '</p>
        <p><span class="severity-badge">' . strtoupper($this->data['severity_level']) . ' SEVERITY</span></p>
    </div>
    
    <div class="alert-box">
        <h3><i class="fas fa-exclamation-circle"></i> Incident Summary</h3>
        <p><strong>Damage Type:</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->data['damage_type']))) . '</p>
        <p><strong>Incident Date:</strong> ' . date('F d, Y', strtotime($this->data['damage_date'])) . '</p>
    </div>
    
    <div class="section">
        <h2>Asset Information</h2>
        <div class="info-row">
            <div class="info-label">Asset Code:</div>
            <div class="info-value">' . htmlspecialchars($this->data['asset_code']) . '</div>
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
            <div class="info-label">Current Location:</div>
            <div class="info-value">' . htmlspecialchars($this->data['current_location'] ?? 'N/A') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Damage Details</h2>
        <div class="info-row">
            <div class="info-label">Damage Type:</div>
            <div class="info-value">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->data['damage_type']))) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Severity Level:</div>
            <div class="info-value"><span class="severity-badge">' . strtoupper($this->data['severity_level']) . '</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Incident Date:</div>
            <div class="info-value">' . date('F d, Y', strtotime($this->data['damage_date'])) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Reported By:</div>
            <div class="info-value">' . htmlspecialchars($this->data['reported_by']) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Report Date:</div>
            <div class="info-value">' . date('F d, Y h:i A', strtotime($this->data['created_at'] ?? 'now')) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->data['status'] ?? 'reported'))) . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Financial Impact</h2>
        <div class="info-row">
            <div class="info-label">Estimated Repair Cost:</div>
            <div class="info-value">₱ ' . number_format($this->data['estimated_repair_cost'] ?? 0, 2) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Asset Purchase Cost:</div>
            <div class="info-value">₱ ' . number_format($this->data['purchase_cost'] ?? 0, 2) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Current Asset Value:</div>
            <div class="info-value">₱ ' . number_format($this->data['current_value'] ?? 0, 2) . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Damage Description</h2>
        <p>' . nl2br(htmlspecialchars($this->data['damage_description'] ?? 'No description provided')) . '</p>
    </div>';
    
    // Add photos if available
    if (!empty($this->data['damage_photos'])) {
        $photos = json_decode($this->data['damage_photos'], true);
        if (is_array($photos) && count($photos) > 0) {
            $html .= '
    <div class="photos-section">
        <h3 style="color: #991b1b; margin-top: 0;"><i class="fas fa-camera"></i> Evidence Photos</h3>
        <div class="photos-grid">';
            
            foreach ($photos as $photo) {
                $html .= '
            <div class="photo-item">
                <img src="' . htmlspecialchars($photo) . '" alt="Damage Photo">
            </div>';
            }
            
            $html .= '
        </div>
    </div>';
        }
    }
    
    $html .= '
    <div class="footer">
        <p><strong>IMPORTANT:</strong> This is an official incident report of the Property Custodian Management System.</p>
        <p>Report any additional findings or updates immediately to the Property Custodian.</p>
        <p>Property Custodian Management System | Generated on ' . date('F d, Y h:i A') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// Handle PDF generation request
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized access');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch incident details
    $query = "SELECT di.*, a.name as asset_name, a.category, a.location as asset_location,
              a.purchase_date, a.purchase_cost, a.current_value
              FROM damaged_items di
              LEFT JOIN assets a ON di.asset_id = a.id
              WHERE di.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $pdf = new IncidentReportPDF($data);
        $pdf->generate();
    } else {
        echo "<h3>Incident report not found</h3>";
        echo "<p>Report ID: " . htmlspecialchars($id) . "</p>";
        echo "<p><a href='damaged-items.php'>Go back</a></p>";
    }
} else {
    echo "No incident ID provided";
}
?>
