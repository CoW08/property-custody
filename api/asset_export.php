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

if($method === 'POST') {
    exportAssets($db);
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
}

function exportAssets($db) {
    $data = json_decode(file_get_contents("php://input"));

    $format = $data->format ?? 'csv'; // csv, excel, pdf
    $includeFields = $data->fields ?? ['all'];
    $filters = $data->filters ?? [];
    $assetIds = $data->asset_ids ?? [];

    // Build query
    $whereClause = "";
    $params = array();

    if (!empty($assetIds)) {
        $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';
        $whereClause = "WHERE a.id IN ($placeholders)";
        $params = $assetIds;
    } else {
        // Apply filters
        if (!empty($filters['search'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "(a.name LIKE ? OR a.asset_code LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['category'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "a.category_id = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['tag'])) {
            $whereClause .= ($whereClause ? " AND " : " WHERE ") . "a.id IN (SELECT asset_id FROM asset_tag_relationships WHERE tag_id = ?)";
            $params[] = $filters['tag'];
        }
    }

    $query = "SELECT a.*, ac.name as category_name,
              GROUP_CONCAT(DISTINCT CONCAT(at.id, ':', at.name, ':', at.color) SEPARATOR '|') as tags,
              pa.custodian_id,
              CONCAT(u.full_name) as assigned_to
              FROM assets a
              LEFT JOIN asset_categories ac ON a.category_id = ac.id
              LEFT JOIN asset_tag_relationships atr ON a.id = atr.asset_id
              LEFT JOIN asset_tags at ON atr.tag_id = at.id
              LEFT JOIN property_assignments pa ON a.id = pa.asset_id AND pa.status = 'active'
              LEFT JOIN custodians c ON pa.custodian_id = c.id
              LEFT JOIN users u ON c.user_id = u.id" .
              " " . $whereClause . "
              GROUP BY a.id
              ORDER BY a.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $assets = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse tags
        $tags = array();
        if(!empty($row['tags'])) {
            $tagPairs = explode('|', $row['tags']);
            foreach($tagPairs as $tagPair) {
                $tagData = explode(':', $tagPair);
                if(count($tagData) >= 3) {
                    $tags[] = $tagData[1]; // Just the name for export
                }
            }
        }
        $row['tag_names'] = implode(', ', $tags);
        unset($row['tags']);

        $assets[] = $row;
    }

    if (empty($assets)) {
        http_response_code(404);
        echo json_encode(array("message" => "No assets found for export"));
        return;
    }

    // Log the export activity
    logActivity($db, $_SESSION['user_id'], 'export_assets', 'assets', count($assets));

    switch($format) {
        case 'csv':
            exportAsCSV($assets, $includeFields);
            break;
        case 'excel':
            exportAsExcel($assets, $includeFields);
            break;
        case 'pdf':
            exportAsPDF($assets, $includeFields);
            break;
        default:
            exportAsCSV($assets, $includeFields);
            break;
    }
}

function exportAsCSV($assets, $includeFields) {
    $filename = 'assets_export_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Define field mappings
    $fieldMap = [
        'asset_code' => 'Asset Code',
        'name' => 'Name',
        'description' => 'Description',
        'category_name' => 'Category',
        'brand' => 'Brand',
        'model' => 'Model',
        'serial_number' => 'Serial Number',
        'status' => 'Status',
        'condition_status' => 'Condition',
        'location' => 'Location',
        'purchase_date' => 'Purchase Date',
        'purchase_cost' => 'Purchase Cost',
        'current_value' => 'Current Value',
        'tag_names' => 'Tags',
        'assigned_to' => 'Assigned To',
        'created_at' => 'Created Date'
    ];

    // Select fields to include
    if (in_array('all', $includeFields) || empty($includeFields)) {
        $fieldsToInclude = array_keys($fieldMap);
    } else {
        $fieldsToInclude = array_intersect($includeFields, array_keys($fieldMap));
    }

    // Write headers
    $headers = [];
    foreach ($fieldsToInclude as $field) {
        $headers[] = $fieldMap[$field];
    }
    fputcsv($output, $headers);

    // Write data rows
    foreach ($assets as $asset) {
        $row = [];
        foreach ($fieldsToInclude as $field) {
            $value = $asset[$field] ?? '';

            // Format specific fields
            if ($field === 'purchase_cost' || $field === 'current_value') {
                $value = $value ? '₱' . number_format($value, 2) : '';
            } elseif ($field === 'created_at') {
                $value = $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
            }

            $row[] = $value;
        }
        fputcsv($output, $row);
    }

    fclose($output);
}

function exportAsExcel($assets, $includeFields) {
    // For Excel export, we'll use HTML table format that Excel can read
    $filename = 'assets_export_' . date('Y-m-d_H-i-s') . '.xls';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';

    // Define field mappings
    $fieldMap = [
        'asset_code' => 'Asset Code',
        'name' => 'Name',
        'description' => 'Description',
        'category_name' => 'Category',
        'brand' => 'Brand',
        'model' => 'Model',
        'serial_number' => 'Serial Number',
        'status' => 'Status',
        'condition_status' => 'Condition',
        'location' => 'Location',
        'purchase_date' => 'Purchase Date',
        'purchase_cost' => 'Purchase Cost',
        'current_value' => 'Current Value',
        'tag_names' => 'Tags',
        'assigned_to' => 'Assigned To',
        'created_at' => 'Created Date'
    ];

    // Select fields to include
    if (in_array('all', $includeFields) || empty($includeFields)) {
        $fieldsToInclude = array_keys($fieldMap);
    } else {
        $fieldsToInclude = array_intersect($includeFields, array_keys($fieldMap));
    }

    // Write headers
    echo '<tr>';
    foreach ($fieldsToInclude as $field) {
        echo '<th>' . htmlspecialchars($fieldMap[$field]) . '</th>';
    }
    echo '</tr>';

    // Write data rows
    foreach ($assets as $asset) {
        echo '<tr>';
        foreach ($fieldsToInclude as $field) {
            $value = $asset[$field] ?? '';

            // Format specific fields
            if ($field === 'purchase_cost' || $field === 'current_value') {
                $value = $value ? '₱' . number_format($value, 2) : '';
            } elseif ($field === 'created_at') {
                $value = $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
            }

            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
}

function exportAsPDF($assets, $includeFields) {
    // For PDF, we'll create a simple HTML version
    // In a real implementation, you'd use a PDF library like TCPDF or DomPDF
    $filename = 'assets_export_' . date('Y-m-d_H-i-s') . '.html';

    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Assets Report</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f8f9fa; font-weight: bold; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.export-info { background-color: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 20px; }';
    echo '</style>';
    echo '</head><body>';

    echo '<h1>Asset Registry Report</h1>';
    echo '<div class="export-info">';
    echo '<strong>Export Date:</strong> ' . date('Y-m-d H:i:s') . '<br>';
    echo '<strong>Total Assets:</strong> ' . count($assets) . '<br>';
    echo '<strong>Generated by:</strong> Property Custodian Management System';
    echo '</div>';

    echo '<table>';

    // Define field mappings (simplified for PDF)
    $fieldMap = [
        'asset_code' => 'Asset Code',
        'name' => 'Name',
        'category_name' => 'Category',
        'status' => 'Status',
        'location' => 'Location',
        'current_value' => 'Current Value',
        'tag_names' => 'Tags'
    ];

    // Use limited fields for PDF to ensure readability
    $fieldsToInclude = ['asset_code', 'name', 'category_name', 'status', 'location', 'current_value'];

    // Write headers
    echo '<tr>';
    foreach ($fieldsToInclude as $field) {
        echo '<th>' . htmlspecialchars($fieldMap[$field]) . '</th>';
    }
    echo '</tr>';

    // Write data rows
    foreach ($assets as $asset) {
        echo '<tr>';
        foreach ($fieldsToInclude as $field) {
            $value = $asset[$field] ?? '';

            // Format specific fields
            if ($field === 'current_value') {
                $value = $value ? '₱' . number_format($value, 2) : 'N/A';
            }

            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
}

function logActivity($db, $user_id, $action, $table_name, $record_count) {
    $query = "INSERT INTO system_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $newValues = json_encode(['exported_count' => $record_count, 'timestamp' => date('Y-m-d H:i:s')]);
    $stmt->execute([$user_id, $action, $table_name, 0, $newValues, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}
?>