<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized access');
}

// Get audit ID from URL
$audit_id = isset($_GET['audit_id']) ? intval($_GET['audit_id']) : 0;

if (!$audit_id) {
    die('Audit ID is required');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch audit details
    $query = "SELECT pa.*, 
                     u.full_name as auditor_name,
                     u.email as auditor_email
              FROM property_audits pa
              LEFT JOIN users u ON pa.auditor_id = u.id
              WHERE pa.id = :audit_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':audit_id', $audit_id);
    $stmt->execute();
    $audit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$audit) {
        die('Audit not found');
    }

    // Fetch audit items/findings (if you have an audit_items table)
    $query_items = "SELECT * FROM audit_items WHERE audit_id = :audit_id ORDER BY created_at";
    $stmt_items = $db->prepare($query_items);
    $stmt_items->bindParam(':audit_id', $audit_id);
    $stmt_items->execute();
    $audit_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Fetch discrepancies if table exists
    $discrepancies = [];
    $checkDiscrepancies = $db->query("SHOW TABLES LIKE 'audit_discrepancies'");
    if($checkDiscrepancies->rowCount() > 0) {
        $query_disc = "SELECT ad.*, a.asset_code, a.name as asset_name
                       FROM audit_discrepancies ad
                       LEFT JOIN assets a ON ad.asset_id = a.id
                       WHERE ad.audit_id = :audit_id
                       ORDER BY ad.severity DESC, ad.created_at";
        $stmt_disc = $db->prepare($query_disc);
        $stmt_disc->bindParam(':audit_id', $audit_id);
        $stmt_disc->execute();
        $discrepancies = $stmt_disc->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set headers for PDF display
header('Content-Type: text/html; charset=utf-8');

// Format dates
function formatDate($date) {
    return $date ? date('F d, Y', strtotime($date)) : 'N/A';
}

function formatDateTime($datetime) {
    return $datetime ? date('F d, Y h:i A', strtotime($datetime)) : 'N/A';
}

// Calculate audit statistics
$total_assets_checked = $audit['total_assets_audited'] ?? 0;
$discrepancies_count = count($discrepancies);
$discrepancy_rate = $total_assets_checked > 0 ? round(($discrepancies_count / $total_assets_checked) * 100, 2) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Audit Report - <?php echo htmlspecialchars($audit['audit_code']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #1e40af;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .audit-code {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 10px;
        }

        /* Executive Summary */
        .executive-summary {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin: 30px 0;
        }

        .executive-summary h2 {
            color: #1e40af;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dbeafe;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Sections */
        .section {
            margin: 30px 0;
        }

        .section-title {
            background: #1e40af;
            color: white;
            padding: 10px 15px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 10px;
            background: #f9fafb;
            border-left: 3px solid #2563eb;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #111827;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table thead {
            background: #1e40af;
            color: white;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table tbody tr:hover {
            background: #f3f4f6;
        }

        /* Severity badges */
        .severity {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .severity-low { background: #d1fae5; color: #065f46; }
        .severity-medium { background: #fef3c7; color: #92400e; }
        .severity-high { background: #fee2e2; color: #991b1b; }
        .severity-critical { background: #fecaca; color: #7f1d1d; }

        /* Status badges */
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-planned { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #f3f4f6; color: #4b5563; }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #000;
            margin: 50px 20px 10px;
            padding-top: 10px;
        }

        .signature-label {
            font-size: 12px;
            color: #6b7280;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 11px;
        }

        /* Print styles */
        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .container {
                padding: 20px;
            }

            table {
                page-break-inside: avoid;
            }
        }

        /* Action buttons */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-print {
            background: #2563eb;
            color: white;
        }

        .btn-print:hover {
            background: #1e40af;
        }

        .btn-close {
            background: #6b7280;
            color: white;
        }

        .btn-close:hover {
            background: #4b5563;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-print">
            📄 Print / Save PDF
        </button>
        <button onclick="window.close()" class="btn btn-close">
            ✕ Close
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>PROPERTY AUDIT REPORT</h1>
            <div class="subtitle">Property Custodian Management System</div>
            <div class="subtitle">School Management System</div>
            <div class="audit-code"><?php echo htmlspecialchars($audit['audit_code']); ?></div>
        </div>

        <!-- Executive Summary -->
        <div class="executive-summary">
            <h2>Executive Summary</h2>
            <p>This report summarizes the findings from the property audit conducted from 
               <?php echo formatDate($audit['start_date']); ?> to 
               <?php echo formatDate($audit['end_date']) ?: 'Ongoing'; ?>.</p>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_assets_checked; ?></div>
                    <div class="stat-label">Assets Audited</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $discrepancies_count; ?></div>
                    <div class="stat-label">Discrepancies Found</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $discrepancy_rate; ?>%</div>
                    <div class="stat-label">Discrepancy Rate</div>
                </div>
            </div>
        </div>

        <!-- Audit Information -->
        <div class="section">
            <div class="section-title">Audit Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Audit Type</div>
                    <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $audit['audit_type'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status status-<?php echo $audit['status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $audit['status'])); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Auditor</div>
                    <div class="info-value"><?php echo htmlspecialchars($audit['auditor_name'] ?? 'Not Assigned'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Department/Scope</div>
                    <div class="info-value"><?php echo htmlspecialchars($audit['department'] ?? 'All Departments'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Start Date</div>
                    <div class="info-value"><?php echo formatDate($audit['start_date']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">End Date</div>
                    <div class="info-value"><?php echo formatDate($audit['end_date']) ?: 'Ongoing'; ?></div>
                </div>
            </div>

            <?php if (!empty($audit['summary'])): ?>
            <div class="info-item" style="grid-column: 1 / -1;">
                <div class="info-label">Summary</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($audit['summary'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Discrepancies Found -->
        <?php if (count($discrepancies) > 0): ?>
        <div class="section">
            <div class="section-title">Discrepancies Found</div>
            <table>
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Discrepancy Type</th>
                        <th>Severity</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discrepancies as $disc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($disc['asset_code'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($disc['asset_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $disc['discrepancy_type'])); ?></td>
                        <td>
                            <span class="severity severity-<?php echo $disc['severity']; ?>">
                                <?php echo strtoupper($disc['severity']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($disc['expected_value'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($disc['actual_value'] ?? 'N/A'); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $disc['status'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="section">
            <div class="section-title">Discrepancies Found</div>
            <div class="no-data">
                ✓ No discrepancies found. All assets are in order.
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <div class="section">
            <div class="section-title">Recommendations</div>
            <div style="padding: 15px; background: #f9fafb;">
                <?php if ($discrepancies_count > 0): ?>
                <ol style="padding-left: 20px; line-height: 2;">
                    <li>Review and resolve all high and critical severity discrepancies immediately.</li>
                    <li>Update asset records to reflect actual physical locations and conditions.</li>
                    <li>Implement regular spot-checks to prevent future discrepancies.</li>
                    <li>Provide additional training to staff on asset tracking procedures.</li>
                    <li>Consider implementing barcode/QR code scanning for real-time updates.</li>
                </ol>
                <?php else: ?>
                <p>Excellent audit results! Continue with current asset management practices. Schedule next audit as per policy.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">
                    <?php echo htmlspecialchars($audit['auditor_name'] ?? ''); ?>
                </div>
                <div class="signature-label">Auditor Signature</div>
                <div class="signature-label">Date: <?php echo formatDate($audit['end_date'] ?: date('Y-m-d')); ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    Property Custodian
                </div>
                <div class="signature-label">Custodian Signature</div>
                <div class="signature-label">Date: _______________</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Confidential Document</strong></p>
            <p>Property Custodian Management System - School Management System</p>
            <p>Generated on <?php echo date('F d, Y h:i A'); ?></p>
            <p>Document ID: <?php echo $audit['audit_code']; ?></p>
        </div>
    </div>

    <script>
        // Auto-print option (disabled by default)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
