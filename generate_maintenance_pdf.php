<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Maintenance Report PDF Generator
class MaintenanceReportPDF {
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
        $priorityColors = [
            'low' => '#10b981',
            'medium' => '#f59e0b',
            'high' => '#ef4444',
            'urgent' => '#7f1d1d'
        ];
        
        $statusColors = [
            'scheduled' => '#3b82f6',
            'in_progress' => '#f59e0b',
            'completed' => '#10b981',
            'cancelled' => '#6b7280'
        ];
        
        $priorityColor = $priorityColors[$this->data['priority']] ?? '#6b7280';
        $statusColor = $statusColors[$this->data['status']] ?? '#6b7280';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Maintenance Report - ' . htmlspecialchars($this->data['asset_code']) . '</title>
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
            border-bottom: 3px solid #3b82f6;
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
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 16px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
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
            color: rgba(59, 130, 246, 0.05);
            font-weight: bold;
            z-index: -1;
        }
        .alert-box {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .alert-box h3 {
            color: #1e40af;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="watermark">MAINTENANCE</div>
    
    <div class="header">
        <h1><i class="fas fa-tools"></i> MAINTENANCE REPORT</h1>
        <p>Property Custodian Management System</p>
        <p><strong>Report No:</strong> MNT-' . str_pad($this->data['id'], 6, '0', STR_PAD_LEFT) . '</p>
        <p><strong>Date Generated:</strong> ' . date('F d, Y h:i A') . '</p>
        <p>
            <span class="badge" style="background-color: ' . $statusColor . ';">' . strtoupper($this->data['status']) . '</span>
            <span class="badge" style="background-color: ' . $priorityColor . '; margin-left: 10px;">' . strtoupper($this->data['priority']) . ' PRIORITY</span>
        </p>
    </div>
    
    <div class="alert-box">
        <h3><i class="fas fa-wrench"></i> Maintenance Summary</h3>
        <p><strong>Type:</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->data['maintenance_type']))) . '</p>
        <p><strong>Scheduled Date:</strong> ' . date('F d, Y', strtotime($this->data['scheduled_date'])) . '</p>
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
            <div class="info-label">Location:</div>
            <div class="info-value">' . htmlspecialchars($this->data['asset_location'] ?? 'N/A') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Maintenance Details</h2>
        <div class="info-row">
            <div class="info-label">Maintenance Type:</div>
            <div class="info-value">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->data['maintenance_type']))) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Priority Level:</div>
            <div class="info-value"><span class="badge" style="background-color: ' . $priorityColor . ';">' . strtoupper($this->data['priority']) . '</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value"><span class="badge" style="background-color: ' . $statusColor . ';">' . strtoupper($this->data['status']) . '</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Scheduled Date:</div>
            <div class="info-value">' . date('F d, Y', strtotime($this->data['scheduled_date'])) . '</div>
        </div>';
        
        if (!empty($this->data['completed_date'])) {
            $html .= '
        <div class="info-row">
            <div class="info-label">Completed Date:</div>
            <div class="info-value">' . date('F d, Y', strtotime($this->data['completed_date'])) . '</div>
        </div>';
        }
        
        $html .= '
        <div class="info-row">
            <div class="info-label">Assigned Technician:</div>
            <div class="info-value">' . htmlspecialchars($this->data['assigned_technician'] ?? 'Not Assigned') . '</div>
        </div>
    </div>
    
    <div class="section">
        <h2>Cost Information</h2>
        <div class="info-row">
            <div class="info-label">Estimated Cost:</div>
            <div class="info-value">₱ ' . number_format($this->data['estimated_cost'] ?? 0, 2) . '</div>
        </div>';
        
        if (!empty($this->data['actual_cost'])) {
            $html .= '
        <div class="info-row">
            <div class="info-label">Actual Cost:</div>
            <div class="info-value">₱ ' . number_format($this->data['actual_cost'], 2) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Variance:</div>
            <div class="info-value">';
            $variance = $this->data['actual_cost'] - ($this->data['estimated_cost'] ?? 0);
            $html .= '₱ ' . number_format(abs($variance), 2);
            if ($variance > 0) {
                $html .= ' <span style="color: #dc2626;">(Over Budget)</span>';
            } elseif ($variance < 0) {
                $html .= ' <span style="color: #16a34a;">(Under Budget)</span>';
            }
            $html .= '</div>
        </div>';
        }
        
        $html .= '
    </div>
    
    <div class="section">
        <h2>Description & Notes</h2>
        <p>' . nl2br(htmlspecialchars($this->data['description'] ?? 'No description provided')) . '</p>';
        
        if (!empty($this->data['notes'])) {
            $html .= '
        <h3 style="color: #1e40af; margin-top: 20px;">Additional Notes:</h3>
        <p>' . nl2br(htmlspecialchars($this->data['notes'])) . '</p>';
        }
        
        $html .= '
    </div>
    
    <div class="footer">
        <p><strong>IMPORTANT:</strong> This is an official maintenance report of the Property Custodian Management System.</p>
        <p>Report any issues or updates immediately to the maintenance coordinator.</p>
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
    
    // Fetch maintenance details
    $query = "SELECT ms.*, a.name as asset_name, a.asset_code, a.category, a.location as asset_location,
              u.full_name as assigned_technician
              FROM maintenance_schedules ms
              LEFT JOIN assets a ON ms.asset_id = a.id
              LEFT JOIN users u ON ms.assigned_to = u.id
              WHERE ms.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $pdf = new MaintenanceReportPDF($data);
        $pdf->generate();
    } else {
        echo "<h3>Maintenance report not found</h3>";
        echo "<p>Report ID: " . htmlspecialchars($id) . "</p>";
        echo "<p><a href='maintenance.php'>Go back</a></p>";
    }
} else {
    echo "No maintenance ID provided";
}
?>
