<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

ob_start();

set_exception_handler(function($e) {
    ob_end_clean();
    if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(500); }
    error_log("[REPORTS] Uncaught: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    error_log("[REPORTS] PHP error ($severity): $message in $file:$line");
    return true;
});

require_once __DIR__ . '/../config/database.php';

// Flush stray output from includes
ob_end_clean();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
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

// Get date range parameters
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$exportFormat = isset($_GET['export']) ? $_GET['export'] : null;

// Only set JSON headers if not exporting (export sets its own headers)
if (!$exportFormat) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

switch($method) {
    case 'GET':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'overview':
                    getOverviewReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'assets':
                    getAssetsReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'maintenance':
                    getMaintenanceReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'procurement':
                    getProcurementReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'audit':
                    getAuditReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'financial':
                    getFinancialReport($db, $dateFrom, $dateTo, $exportFormat);
                    break;
                case 'item_verification':
                    getItemVerificationReport($db);
                    break;
                case 'critical_alerts':
                    getCriticalAlerts($db);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(array("message" => "Invalid action"));
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Action parameter required"));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}

function getOverviewReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();

        // Build date filter condition
        $dateCondition = buildDateCondition($dateFrom, $dateTo, 'created_at');

        // Assets Overview
        $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
        if($checkAssets->rowCount() > 0) {
            $query = "SELECT
                        COUNT(*) as total_assets,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
                        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_assets,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                        SUM(CASE WHEN status = 'damaged' THEN 1 ELSE 0 END) as damaged_assets,
                        SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_assets
                      FROM assets
                      WHERE 1=1 {$dateCondition}";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['assets'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Maintenance Overview
        $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
        if($checkMaintenance->rowCount() > 0) {
            $query = "SELECT
                        COUNT(*) as total_schedules,
                        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN scheduled_date < CURDATE() AND status IN ('scheduled', 'in_progress') THEN 1 ELSE 0 END) as overdue
                      FROM maintenance_schedules";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['maintenance'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Procurement Overview
        $checkProcurement = $db->query("SHOW TABLES LIKE 'procurement_requests'");
        if($checkProcurement->rowCount() > 0) {
            $query = "SELECT
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as completed
                      FROM procurement_requests";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['procurement'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Audits Overview
        $checkAudits = $db->query("SHOW TABLES LIKE 'property_audits'");
        if($checkAudits->rowCount() > 0) {
            $query = "SELECT
                        COUNT(*) as total_audits,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_review
                      FROM property_audits";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['audits'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Overview_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating overview report", "error" => $e->getMessage()));
    }
}

// Helper function to build date condition
function buildDateCondition($dateFrom, $dateTo, $dateColumn = 'created_at') {
    $condition = '';
    if ($dateFrom && $dateTo) {
        $condition = " AND {$dateColumn} BETWEEN '{$dateFrom}' AND '{$dateTo}'";
    } elseif ($dateFrom) {
        $condition = " AND {$dateColumn} >= '{$dateFrom}'";
    } elseif ($dateTo) {
        $condition = " AND {$dateColumn} <= '{$dateTo}'";
    }
    return $condition;
}

// Excel export function
function exportToExcel($data, $filename, $dateFrom = null, $dateTo = null) {
    // Clean any previous output before setting download headers
    if (ob_get_level()) ob_end_clean();
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d_His') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Start HTML output for Excel
    echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; border: 1px solid #000; }";
    echo "td { padding: 8px; border: 1px solid #000; }";
    echo ".header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }";
    echo ".date-range { font-size: 12px; color: #666; margin-bottom: 20px; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";

    // Report header
    echo "<div class='header'>" . htmlspecialchars($filename) . "</div>";
    if ($dateFrom && $dateTo) {
        echo "<div class='date-range'>Date Range: " . htmlspecialchars($dateFrom) . " to " . htmlspecialchars($dateTo) . "</div>";
    }
    echo "<div class='date-range'>Generated: " . date('Y-m-d H:i:s') . "</div>";
    echo "<br>";

    // Convert data to table format
    renderExcelSection($data);

    echo "</body></html>";
    exit;
}

function renderExcelSection($data, $depth = 0) {
    foreach ($data as $section => $sectionData) {
        if (!is_array($sectionData)) {
            // Single key-value pair at top level
            continue;
        }

        $heading = $depth === 0 ? 'h3' : 'h4';
        echo "<$heading>" . htmlspecialchars(ucwords(str_replace('_', ' ', $section))) . "</$heading>";

        // Check if this is an indexed array of rows
        if (isset($sectionData[0]) && is_array($sectionData[0])) {
            // Table with rows
            echo "<table>";
            echo "<thead><tr>";
            foreach (array_keys($sectionData[0]) as $header) {
                echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . "</th>";
            }
            echo "</tr></thead>";
            echo "<tbody>";
            foreach ($sectionData as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    $display = is_array($value) ? json_encode($value) : ($value ?? 'N/A');
                    echo "<td>" . htmlspecialchars($display) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table><br>";
        } elseif (is_array($sectionData)) {
            // Check if values contain nested arrays (e.g., timeline -> procurement/maintenance)
            $hasNestedArrays = false;
            foreach ($sectionData as $v) {
                if (is_array($v)) { $hasNestedArrays = true; break; }
            }

            if ($hasNestedArrays) {
                // Recursively render nested sections
                renderExcelSection($sectionData, $depth + 1);
            } else {
                // Simple key-value pairs
                echo "<table>";
                echo "<thead><tr><th>Metric</th><th>Value</th></tr></thead>";
                echo "<tbody>";
                foreach ($sectionData as $key => $value) {
                    $display = is_array($value) ? json_encode($value) : ($value ?? 'N/A');
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . "</td>";
                    echo "<td>" . htmlspecialchars($display) . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table><br>";
            }
        }
    }
}

function getAssetsReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();
        $notes = array();
        $dateCondition = buildDateCondition($dateFrom, $dateTo, 'created_at');
        $dateFilterApplied = !empty(trim($dateCondition));

        $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
        if($checkAssets->rowCount() == 0) {
            http_response_code(200);
            echo json_encode(array("message" => "No assets data available"));
            return;
        }

        // Asset distribution by category
        $query = "SELECT category, COUNT(*) as count FROM assets WHERE 1=1 {$dateCondition} GROUP BY category ORDER BY count DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($dateFilterApplied && empty($report['by_category'])) {
            $query = "SELECT category, COUNT(*) as count FROM assets GROUP BY category ORDER BY count DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $fallbackData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($fallbackData)) {
                $report['by_category'] = $fallbackData;
                $notes[] = 'No item category data matched the selected date range. Showing overall totals instead.';
            }
        }

        // Asset distribution by status
        $query = "SELECT status, COUNT(*) as count FROM assets WHERE 1=1 {$dateCondition} GROUP BY status";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($dateFilterApplied && empty($report['by_status'])) {
            $query = "SELECT status, COUNT(*) as count FROM assets GROUP BY status";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $fallbackData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($fallbackData)) {
                $report['by_status'] = $fallbackData;
                $notes[] = 'No item status data matched the selected date range. Showing overall totals instead.';
            }
        }

        // Asset distribution by location
        $query = "SELECT location, COUNT(*) as count FROM assets WHERE location IS NOT NULL {$dateCondition} GROUP BY location ORDER BY count DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_location'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($dateFilterApplied && empty($report['by_location'])) {
            $query = "SELECT location, COUNT(*) as count FROM assets WHERE location IS NOT NULL GROUP BY location ORDER BY count DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $fallbackData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($fallbackData)) {
                $report['by_location'] = $fallbackData;
                $notes[] = 'No item location data matched the selected date range. Showing overall totals instead.';
            }
        }

        // Monthly asset additions (last 12 months)
        $query = "SELECT
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                  FROM assets
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['monthly_additions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($notes)) {
            $report['notes'] = $notes;
        }

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Assets_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating assets report", "error" => $e->getMessage()));
    }
}

function getMaintenanceReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();

        $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
        if($checkMaintenance->rowCount() == 0) {
            http_response_code(200);
            echo json_encode(array("message" => "No maintenance data available"));
            return;
        }

        // Maintenance by priority
        $query = "SELECT priority, COUNT(*) as count FROM maintenance_schedules GROUP BY priority";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_priority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monthly maintenance completed (last 12 months)
        $query = "SELECT
                    DATE_FORMAT(completed_date, '%Y-%m') as month,
                    COUNT(*) as count
                  FROM maintenance_schedules
                  WHERE status = 'completed' AND completed_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(completed_date, '%Y-%m')
                  ORDER BY month DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['monthly_completed'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average maintenance cost and duration
        $query = "SELECT
                    AVG(estimated_cost) as avg_cost,
                    AVG(estimated_duration) as avg_duration
                  FROM maintenance_schedules
                  WHERE status = 'completed'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['averages'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Maintenance_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating maintenance report", "error" => $e->getMessage()));
    }
}

function getProcurementReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();

        $checkProcurement = $db->query("SHOW TABLES LIKE 'procurement_requests'");
        if($checkProcurement->rowCount() == 0) {
            http_response_code(200);
            echo json_encode(array("message" => "No procurement data available"));
            return;
        }

        // Procurement by status
        $query = "SELECT status, COUNT(*) as count FROM procurement_requests GROUP BY status";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monthly procurement requests (last 12 months)
        $query = "SELECT
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(estimated_cost) as total_cost
                  FROM procurement_requests
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['monthly_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top requested items by department (since item_description doesn't exist)
        $query = "SELECT department, COUNT(*) as request_count
                  FROM procurement_requests
                  GROUP BY department
                  ORDER BY request_count DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['top_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Procurement_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating procurement report", "error" => $e->getMessage()));
    }
}

function getAuditReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();

        $checkAudits = $db->query("SHOW TABLES LIKE 'property_audits'");
        if($checkAudits->rowCount() == 0) {
            http_response_code(200);
            echo json_encode(array("message" => "No audit data available"));
            return;
        }

        // Audit statistics
        $query = "SELECT
                    COUNT(*) as total_audits,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_audits,
                    AVG(DATEDIFF(end_date, start_date)) as avg_duration_days
                  FROM property_audits";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['statistics'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Audits by type
        $query = "SELECT audit_type, COUNT(*) as count FROM property_audits GROUP BY audit_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent audit findings
        $query = "SELECT audit_type, department, start_date, end_date, summary
                  FROM property_audits
                  ORDER BY start_date DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $report['recent_audits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Audit_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating audit report", "error" => $e->getMessage()));
    }
}

function getFinancialReport($db, $dateFrom = null, $dateTo = null, $exportFormat = null) {
    try {
        $report = array();

        // Total asset value
        $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
        if($checkAssets->rowCount() > 0) {
            $query = "SELECT
                        COUNT(*) as total_assets,
                        SUM(purchase_cost) as total_asset_value,
                        AVG(purchase_cost) as avg_asset_value
                      FROM assets
                      WHERE purchase_cost IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['asset_values'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Maintenance costs
        $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
        if($checkMaintenance->rowCount() > 0) {
            $query = "SELECT
                        SUM(estimated_cost) as total_maintenance_cost,
                        AVG(estimated_cost) as avg_maintenance_cost,
                        COUNT(*) as total_maintenance_records
                      FROM maintenance_schedules
                      WHERE estimated_cost IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['maintenance_costs'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Procurement costs
        $checkProcurement = $db->query("SHOW TABLES LIKE 'procurement_requests'");
        if($checkProcurement->rowCount() > 0) {
            $query = "SELECT
                        SUM(estimated_cost) as total_procurement_cost,
                        AVG(estimated_cost) as avg_procurement_cost,
                        COUNT(*) as total_procurement_requests
                      FROM procurement_requests
                      WHERE estimated_cost IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $report['procurement_costs'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Monthly financial summary (last 12 months)
        $financial_timeline = array();

        if($checkProcurement->rowCount() > 0) {
            $query = "SELECT
                        DATE_FORMAT(request_date, '%Y-%m') as month,
                        SUM(estimated_cost) as procurement_cost
                      FROM procurement_requests
                      WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        AND estimated_cost IS NOT NULL
                      GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                      ORDER BY month DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $financial_timeline['procurement'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if($checkMaintenance->rowCount() > 0) {
            $query = "SELECT
                        DATE_FORMAT(scheduled_date, '%Y-%m') as month,
                        SUM(estimated_cost) as maintenance_cost
                      FROM maintenance_schedules
                      WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        AND estimated_cost IS NOT NULL
                      GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
                      ORDER BY month DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $financial_timeline['maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $report['timeline'] = $financial_timeline;

        // Handle export format
        if ($exportFormat) {
            handleExport($exportFormat, $report, 'Financial_Report', $dateFrom, $dateTo);
        } else {
            http_response_code(200);
            echo json_encode($report);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error generating financial report", "error" => $e->getMessage()));
    }
}

// Unified export handler for excel, csv, and pdf
function handleExport($format, $data, $filename, $dateFrom = null, $dateTo = null) {
    switch ($format) {
        case 'excel':
            exportToExcel($data, $filename, $dateFrom, $dateTo);
            break;
        case 'csv':
            exportToCsv($data, $filename, $dateFrom, $dateTo);
            break;
        case 'pdf':
            exportToPdf($data, $filename, $dateFrom, $dateTo);
            break;
        default:
            exportToExcel($data, $filename, $dateFrom, $dateTo);
            break;
    }
}

// CSV export function
function exportToCsv($data, $filename, $dateFrom = null, $dateTo = null) {
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d_His') . '.csv"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fwrite($output, "\xEF\xBB\xBF");

    // Report header
    fputcsv($output, [$filename]);
    if ($dateFrom && $dateTo) {
        fputcsv($output, ['Date Range', $dateFrom . ' to ' . $dateTo]);
    }
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    renderCsvSection($output, $data);

    fclose($output);
    exit;
}

function renderCsvSection($output, $data, $prefix = '') {
    foreach ($data as $section => $sectionData) {
        if (!is_array($sectionData)) continue;

        $sectionTitle = ucwords(str_replace('_', ' ', $section));
        if ($prefix) $sectionTitle = $prefix . ' - ' . $sectionTitle;
        fputcsv($output, [$sectionTitle]);

        if (isset($sectionData[0]) && is_array($sectionData[0])) {
            // Table rows
            fputcsv($output, array_map(function($h) {
                return ucwords(str_replace('_', ' ', $h));
            }, array_keys($sectionData[0])));
            foreach ($sectionData as $row) {
                fputcsv($output, array_values(array_map(function($v) {
                    return is_array($v) ? json_encode($v) : ($v ?? 'N/A');
                }, $row)));
            }
        } elseif (is_array($sectionData)) {
            $hasNested = false;
            foreach ($sectionData as $v) {
                if (is_array($v)) { $hasNested = true; break; }
            }
            if ($hasNested) {
                renderCsvSection($output, $sectionData, $sectionTitle);
            } else {
                fputcsv($output, ['Metric', 'Value']);
                foreach ($sectionData as $key => $value) {
                    $display = is_array($value) ? json_encode($value) : ($value ?? 'N/A');
                    fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $display]);
                }
            }
        }
        fputcsv($output, []);
    }
}

// PDF export function (generates print-friendly HTML that triggers browser print dialog)
function exportToPdf($data, $filename, $dateFrom = null, $dateTo = null) {
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($filename) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; padding: 40px; color: #1f2937; font-size: 12px; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .report-header h1 { font-size: 22px; color: #1e40af; margin-bottom: 5px; }
        .report-header p { color: #6b7280; font-size: 11px; }
        h3 { font-size: 14px; color: #1e40af; margin: 20px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        h4 { font-size: 13px; color: #374151; margin: 15px 0 6px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
        th { background: #2563eb; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 600; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .no-print { margin: 20px 0; text-align: center; }
        .no-print button { padding: 10px 30px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; margin: 0 5px; }
        .no-print button:hover { background: #1d4ed8; }
        .no-print button.secondary { background: #6b7280; }
        .no-print button.secondary:hover { background: #4b5563; }
        @media print { .no-print { display: none; } body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>' . htmlspecialchars(str_replace('_', ' ', $filename)) . '</h1>';
    if ($dateFrom && $dateTo) {
        echo '<p>Date Range: ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>';
    }
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>';

    renderExcelSection($data);

    echo '<div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
</body></html>';
    exit;
}

// Item Verification Report
function getItemVerificationReport($db) {
    header('Content-Type: application/json');
    try {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            echo json_encode(['results' => [], 'message' => 'Enter an item code or name to search']);
            return;
        }

        $results = [];
        $searchParam = '%' . $search . '%';

        // Search assets
        $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
        if ($checkAssets->rowCount() > 0) {
            $stmt = $db->prepare("SELECT id, asset_code, name, category, status, location, condition_status,
                                         purchase_cost, current_value, 'asset' as item_type
                                  FROM assets
                                  WHERE asset_code LIKE :s1 OR name LIKE :s2
                                  LIMIT 20");
            $stmt->execute([':s1' => $searchParam, ':s2' => $searchParam]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = array_merge($results, $assets);
        }

        // Search supplies
        $checkSupplies = $db->query("SHOW TABLES LIKE 'supplies'");
        if ($checkSupplies->rowCount() > 0) {
            $stmt = $db->prepare("SELECT id, item_code, name, category, status, location, '' as condition_status,
                                         unit_cost as purchase_cost, total_value as current_value, 'supply' as item_type
                                  FROM supplies
                                  WHERE item_code LIKE :s1 OR name LIKE :s2
                                  LIMIT 20");
            $stmt->execute([':s1' => $searchParam, ':s2' => $searchParam]);
            $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = array_merge($results, $supplies);
        }

        $found = count($results) > 0;
        echo json_encode([
            'results' => $results,
            'total' => count($results),
            'verified' => $found,
            'message' => $found ? count($results) . ' item(s) found in the system' : 'No items found matching "' . $search . '"'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Verification failed: ' . $e->getMessage()]);
    }
}

// Critical Alerts for Report Analytics
function getCriticalAlerts($db) {
    header('Content-Type: application/json');
    try {
        $alerts = [];

        // Low stock supplies
        $checkSupplies = $db->query("SHOW TABLES LIKE 'supplies'");
        if ($checkSupplies->rowCount() > 0) {
            $stmt = $db->prepare("SELECT name, current_stock, minimum_stock, unit_cost
                                  FROM supplies
                                  WHERE current_stock <= minimum_stock AND status = 'active'
                                  ORDER BY (current_stock - minimum_stock) ASC
                                  LIMIT 10");
            $stmt->execute();
            $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lowStock as $item) {
                $severity = $item['current_stock'] == 0 ? 'critical' : 'warning';
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => $severity,
                    'title' => 'Low Stock: ' . $item['name'],
                    'message' => "Current stock: {$item['current_stock']} / Min required: {$item['minimum_stock']}",
                    'value' => $item['unit_cost']
                ];
            }
        }

        // Overdue maintenance
        $checkMaintenance = $db->query("SHOW TABLES LIKE 'maintenance_schedules'");
        if ($checkMaintenance->rowCount() > 0) {
            $stmt = $db->prepare("SELECT ms.id, ms.maintenance_type, ms.scheduled_date, ms.priority,
                                         a.name as asset_name, ms.estimated_cost
                                  FROM maintenance_schedules ms
                                  LEFT JOIN assets a ON ms.asset_id = a.id
                                  WHERE ms.scheduled_date < CURDATE()
                                    AND ms.status IN ('scheduled', 'in_progress')
                                  ORDER BY ms.scheduled_date ASC
                                  LIMIT 10");
            $stmt->execute();
            $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($overdue as $item) {
                $severity = $item['priority'] === 'critical' ? 'critical' : ($item['priority'] === 'high' ? 'warning' : 'info');
                $alerts[] = [
                    'type' => 'overdue_maintenance',
                    'severity' => $severity,
                    'title' => 'Overdue: ' . ($item['asset_name'] ?: 'Asset #' . $item['id']),
                    'message' => ucfirst($item['maintenance_type']) . " maintenance overdue since {$item['scheduled_date']}",
                    'value' => $item['estimated_cost']
                ];
            }
        }

        // Damaged/lost assets
        $checkAssets = $db->query("SHOW TABLES LIKE 'assets'");
        if ($checkAssets->rowCount() > 0) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM assets WHERE status IN ('damaged', 'lost')");
            $stmt->execute();
            $damaged = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($damaged['count'] > 0) {
                $alerts[] = [
                    'type' => 'damaged_assets',
                    'severity' => $damaged['count'] > 5 ? 'critical' : 'warning',
                    'title' => 'Damaged/Lost Assets',
                    'message' => "{$damaged['count']} items are currently marked as damaged or lost",
                    'value' => null
                ];
            }
        }

        // Sort by severity (critical first)
        usort($alerts, function($a, $b) {
            $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return ($order[$a['severity']] ?? 3) - ($order[$b['severity']] ?? 3);
        });

        echo json_encode([
            'alerts' => $alerts,
            'total' => count($alerts),
            'critical_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
            'warning_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning'))
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load critical alerts: ' . $e->getMessage()]);
    }
}
