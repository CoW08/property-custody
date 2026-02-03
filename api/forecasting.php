<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireAuth();
requirePermission('ai_demand_forecasting');

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection error', 'error' => $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'overview';

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit();
}

switch ($action) {
    case 'overview':
        sendJson(getForecastOverview($db));
        break;
    case 'demand_forecast':
        sendJson(getDemandForecast($db));
        break;
    case 'reorder_recommendations':
        sendJson(getReorderRecommendations($db));
        break;
    case 'alerts':
        sendJson(getForecastAlerts($db));
        break;
    case 'seasonality':
        sendJson(getSeasonalUsage($db));
        break;
    default:
        http_response_code(400);
        echo json_encode(['message' => 'Unknown action']);
        break;
}

function sendJson($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload);
}

function tablesAvailable(PDO $db, array $tables)
{
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
    }
    return true;
}

function getForecastOverview(PDO $db)
{
    if (!tablesAvailable($db, ['supplies', 'supply_transactions'])) {
        return [
            'generated_at' => date('c'),
            'summary' => [],
            'key_metrics' => [],
            'highlights' => []
        ];
    }

    $demand = getDemandForecast($db);
    $reorder = getReorderRecommendations($db);
    $alerts = getForecastAlerts($db);

    $totalForecastedUsage = array_sum(array_column($demand, 'forecast_30_day'));
    $totalSupplies = count($demand);
    $lowStock = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical'));
    $seasonal = getSeasonalUsage($db);

    return [
        'generated_at' => date('c'),
        'summary' => [
            'total_supplies' => $totalSupplies,
            'forecasted_usage' => $totalForecastedUsage,
            'critical_low_stock' => $lowStock,
            'seasonal_trends' => count($seasonal)
        ],
        'key_metrics' => array_slice($reorder, 0, 3),
        'highlights' => array_slice($alerts, 0, 4)
    ];
}

function shouldUseFallbackForecast(): bool
{
    static $useFallback = null;
    if ($useFallback !== null) {
        return $useFallback;
    }

    $envValue = getenv('FORECAST_USE_SIMULATED_DATA');
    if ($envValue === false || $envValue === null || $envValue === '') {
        $useFallback = false;
    } else {
        $parsed = filter_var($envValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $useFallback = $parsed ?? false;
    }

    return (bool) $useFallback;
}

function getDemandForecast(PDO $db)
{
    if (shouldUseFallbackForecast() || !tablesAvailable($db, ['supplies', 'supply_transactions'])) {
        return getFallbackDemandForecast($db);
    }

    $query = "SELECT 
            s.id,
            s.item_code,
            s.name,
            s.current_stock,
            s.minimum_stock,
            COALESCE(SUM(CASE WHEN st.transaction_type = 'out' THEN st.quantity ELSE 0 END), 0) AS total_out,
            COALESCE(MIN(st.created_at), NOW()) AS first_txn,
            COALESCE(MAX(st.created_at), NOW()) AS last_txn
        FROM supplies s
        LEFT JOIN supply_transactions st ON st.supply_id = s.id
            AND st.created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY)
        WHERE s.status = 'active'
        GROUP BY s.id, s.item_code, s.name, s.current_stock, s.minimum_stock
        ORDER BY s.name";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $daysWindow = max(30, ceil((time() - strtotime($row['first_txn'] ?? 'now')) / 86400));
        $avgDailyUsage = $daysWindow > 0 ? round($row['total_out'] / $daysWindow, 2) : 0;
        $forecast30 = round($avgDailyUsage * 30, 2);
        $forecast60 = round($avgDailyUsage * 60, 2);

        $results[] = [
            'supply_id' => (int) $row['id'],
            'item_code' => $row['item_code'],
            'name' => $row['name'],
            'current_stock' => (int) $row['current_stock'],
            'minimum_stock' => (int) $row['minimum_stock'],
            'average_daily_usage' => $avgDailyUsage,
            'forecast_30_day' => $forecast30,
            'forecast_60_day' => $forecast60,
            'last_activity' => $row['last_txn']
        ];
    }

    if (empty($results)) {
        return getFallbackDemandForecast();
    }

    return $results;
}

function getReorderRecommendations(PDO $db)
{
    $forecasts = getDemandForecast($db);
    if (empty($forecasts)) {
        return [];
    }

    $leadTimeDays = 7;
    $safetyDays = 5;

    $recommendations = array_map(function ($item) use ($leadTimeDays, $safetyDays) {
        $avg = $item['average_daily_usage'];
        $safetyStock = max($item['minimum_stock'], (int) ceil($avg * $safetyDays));
        $targetStock = (int) ceil($avg * ($leadTimeDays + $safetyDays) + $safetyStock);
        $reorderQty = max($targetStock - $item['current_stock'], 0);
        $runoutDays = $avg > 0 ? round($item['current_stock'] / max($avg, 0.0001), 1) : null;
        $stockCoverage = $avg > 0 ? round($item['current_stock'] / $avg, 1) : null;

        $priority = classifyPriority($reorderQty, $runoutDays, $stockCoverage, $item['current_stock'], $item['forecast_30_day']);

        return [
            'supply_id' => $item['supply_id'],
            'item_code' => $item['item_code'],
            'name' => $item['name'],
            'current_stock' => $item['current_stock'],
            'recommended_reorder_qty' => $reorderQty,
            'safety_stock' => $safetyStock,
            'projected_runout_days' => $runoutDays,
            'average_daily_usage' => $avg,
            'stock_coverage_days' => $stockCoverage,
            'priority' => $priority
        ];
    }, $forecasts);

    usort($recommendations, function ($a, $b) {
        return priorityWeight($b['priority']) <=> priorityWeight($a['priority'])
            ?: $b['recommended_reorder_qty'] <=> $a['recommended_reorder_qty'];
    });

    return $recommendations;
}

function classifyPriority($reorderQty, $runoutDays, $stockCoverage, $currentStock, $forecast30)
{
    if ($forecast30 > 0 && $currentStock >= ($forecast30 * 1.8)) {
        return 'overstock';
    }

    if ($reorderQty <= 0) {
        return 'low';
    }

    if ($runoutDays === null) {
        return 'medium';
    }

    if ($runoutDays <= 7) {
        return 'critical';
    }

    if ($runoutDays <= 14) {
        return 'high';
    }

    return 'medium';
}

function priorityWeight($priority)
{
    return match ($priority) {
        'critical' => 4,
        'high' => 3,
        'medium' => 2,
        'overstock' => 1,
        default => 0,
    };
}

function getForecastAlerts(PDO $db)
{
    $recommendations = getReorderRecommendations($db);
    $alerts = [];

    foreach ($recommendations as $item) {
        if (in_array($item['priority'], ['low', 'medium'], true) && $item['recommended_reorder_qty'] === 0) {
            continue;
        }

        if ($item['priority'] === 'overstock') {
            $alerts[] = [
                'supply_id' => $item['supply_id'],
                'title' => 'Overstock warning',
                'message' => sprintf(
                    '%s stock exceeds projected demand. Consider using or pausing reorders.',
                    $item['name']
                ),
                'severity' => 'overstock',
                'current_stock' => $item['current_stock'],
                'recommended_reorder_qty' => 0
            ];
            continue;
        }

        $alerts[] = [
            'supply_id' => $item['supply_id'],
            'title' => match ($item['priority']) {
                'critical' => 'Out-of-stock risk',
                'high' => 'Low stock warning',
                default => 'Stock attention'
            },
            'message' => sprintf(
                '%s: reorder %d units. Estimated runout in %s days.',
                $item['name'],
                $item['recommended_reorder_qty'],
                $item['projected_runout_days'] !== null ? $item['projected_runout_days'] : 'unknown'
            ),
            'severity' => $item['priority'],
            'current_stock' => $item['current_stock'],
            'recommended_reorder_qty' => $item['recommended_reorder_qty']
        ];
    }

    return $alerts;
}

function getSeasonalUsage(PDO $db)
{
    if (shouldUseFallbackForecast() || !tablesAvailable($db, ['supply_transactions'])) {
        return getFallbackSeasonality();
    }

    $query = "SELECT 
            DATE_FORMAT(st.created_at, '%Y-%m') AS period,
            SUM(CASE WHEN st.transaction_type = 'out' THEN st.quantity ELSE 0 END) AS usage_qty
        FROM supply_transactions st
        WHERE st.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(st.created_at, '%Y-%m')
        ORDER BY period";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        return getFallbackSeasonality();
    }

    $averageUsage = array_sum(array_column($rows, 'usage_qty')) / count($rows);
    $results = [];

    foreach ($rows as $row) {
        $variation = $averageUsage > 0 ? round((($row['usage_qty'] - $averageUsage) / $averageUsage) * 100, 1) : 0;
        $results[] = [
            'period' => $row['period'],
            'usage_qty' => (int) $row['usage_qty'],
            'variation_percent' => $variation,
            'classification' => $variation >= 25 ? 'peak' : ($variation <= -25 ? 'low' : 'normal')
        ];
    }

    return $results;
}

function getFallbackDemandForecast(PDO $db = null)
{
    $dataset = getFallbackUsageDataset();
    $months = $dataset['months'];
    $items = $dataset['items'];
    $results = [];

    $stockByCode = [];
    if ($db instanceof PDO && tablesAvailable($db, ['supplies'])) {
        try {
            $stockQuery = "SELECT item_code, name, current_stock, minimum_stock FROM supplies WHERE status = 'active'";
            $stmt = $db->prepare($stockQuery);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $code = $row['item_code'];
                if ($code) {
                    $stockByCode[$code] = [
                        'name' => $row['name'],
                        'current_stock' => (int)($row['current_stock'] ?? 0),
                        'minimum_stock' => (int)($row['minimum_stock'] ?? 0)
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('Fallback demand forecast: unable to read supplies inventory - ' . $e->getMessage());
            $stockByCode = [];
        }
    }

    foreach ($items as $index => $item) {
        $usage = $item['usage'];
        $totalOut = array_sum($usage);
        $monthsCount = max(1, count($usage));
        $daysWindow = max(30, $monthsCount * 30);
        $avgDailyUsage = round($totalOut / $daysWindow, 2);
        $avgMonthlyUsage = $totalOut / $monthsCount;
        $currentStock = max(0, (int) round($avgMonthlyUsage * 0.55));
        $minimumStock = max(1, (int) round($avgMonthlyUsage * 0.3));

        if (!empty($stockByCode)) {
            if (isset($stockByCode[$item['code']])) {
                $currentStock = $stockByCode[$item['code']]['current_stock'];
                $minimumStock = max(1, $stockByCode[$item['code']]['minimum_stock']);
                $itemName = $stockByCode[$item['code']]['name'] ?? $item['name'];
            } else {
                $itemName = $item['name'];
            }
        } else {
            $itemName = $item['name'];
        }

        $forecast30 = round($avgDailyUsage * 30, 2);
        $forecast60 = round($avgDailyUsage * 60, 2);

        $lastPeriod = $months[$monthsCount - 1] ?? date('Y-m');
        $results[] = [
            'supply_id' => $index + 1,
            'item_code' => $item['code'],
            'name' => $itemName,
            'current_stock' => $currentStock,
            'minimum_stock' => $minimumStock,
            'average_daily_usage' => $avgDailyUsage,
            'forecast_30_day' => $forecast30,
            'forecast_60_day' => $forecast60,
            'last_activity' => $lastPeriod . '-01'
        ];
    }

    return $results;
}

function getFallbackSeasonality()
{
    $dataset = getFallbackUsageDataset();
    $months = $dataset['months'];
    $items = $dataset['items'];

    $monthlyTotals = array_fill(0, count($months), 0);
    foreach ($items as $item) {
        foreach ($item['usage'] as $idx => $value) {
            $monthlyTotals[$idx] += $value;
        }
    }

    $averageUsage = count($monthlyTotals) > 0 ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;
    $results = [];

    foreach ($monthlyTotals as $idx => $total) {
        $variation = $averageUsage > 0 ? round((($total - $averageUsage) / $averageUsage) * 100, 1) : 0;
        $results[] = [
            'period' => $months[$idx] ?? date('Y-m'),
            'usage_qty' => (int) round($total),
            'variation_percent' => $variation,
            'classification' => $variation >= 25 ? 'peak' : ($variation <= -25 ? 'low' : 'normal')
        ];
    }

    return $results;
}

function getFallbackUsageDataset()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $months = [
        '2025-01', '2025-02', '2025-03', '2025-04', '2025-05', '2025-06',
        '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12'
    ];

    $items = [
        // Library supplies
        ['code' => 'LIB-BOND', 'name' => 'Bond Paper (reams)', 'usage' => [120, 140, 160, 130, 170, 180, 150, 160, 190, 200, 170, 110]],
        ['code' => 'LIB-INK', 'name' => 'Printer Ink (pcs)', 'usage' => [10, 12, 14, 11, 15, 16, 13, 14, 17, 18, 15, 9]],
        ['code' => 'LIB-STAP', 'name' => 'Staples (boxes)', 'usage' => [15, 18, 20, 16, 22, 25, 19, 20, 26, 28, 22, 14]],
        ['code' => 'LIB-FOLD', 'name' => 'Folders (pcs)', 'usage' => [200, 220, 250, 210, 260, 280, 240, 250, 300, 320, 260, 180]],
        ['code' => 'LIB-MARK', 'name' => 'Markers (pcs)', 'usage' => [30, 35, 40, 32, 45, 48, 38, 40, 50, 55, 45, 28]],

        // Clinic supplies
        ['code' => 'CLN-ALCO', 'name' => 'Alcohol (bottles)', 'usage' => [60, 70, 80, 65, 75, 85, 70, 75, 90, 95, 80, 60]],
        ['code' => 'CLN-MASK', 'name' => 'Face Masks (boxes)', 'usage' => [20, 25, 30, 22, 28, 35, 25, 28, 40, 45, 30, 20]],
        ['code' => 'CLN-PARA', 'name' => 'Paracetamol (tabs)', 'usage' => [300, 320, 350, 310, 340, 380, 330, 350, 400, 420, 360, 300]],
        ['code' => 'CLN-SYR', 'name' => 'Syringes (pcs)', 'usage' => [120, 140, 160, 130, 150, 180, 145, 150, 190, 200, 160, 120]],
        ['code' => 'CLN-GLOV', 'name' => 'Gloves (boxes)', 'usage' => [15, 18, 20, 16, 19, 22, 18, 19, 25, 28, 20, 15]],
        ['code' => 'CLN-VITA', 'name' => 'Vitamins (tabs)', 'usage' => [200, 220, 250, 210, 240, 280, 230, 240, 300, 320, 260, 200]],

        // First aid supplies
        ['code' => 'FA-BAND', 'name' => 'Bandages (pcs)', 'usage' => [40, 45, 50, 42, 55, 60, 48, 52, 65, 70, 55, 38]],
        ['code' => 'FA-ANT', 'name' => 'Antiseptic (bottles)', 'usage' => [10, 11, 12, 10, 13, 15, 12, 13, 16, 18, 14, 9]],
        ['code' => 'FA-GAUZ', 'name' => 'Gauze Pads (pcs)', 'usage' => [80, 90, 100, 85, 110, 120, 95, 100, 130, 140, 115, 75]],
        ['code' => 'FA-TAPE', 'name' => 'Medical Tape (rolls)', 'usage' => [20, 22, 25, 21, 28, 30, 24, 26, 32, 35, 29, 18]],
        ['code' => 'FA-COLD', 'name' => 'Cold Packs (pcs)', 'usage' => [15, 18, 20, 16, 22, 25, 19, 21, 28, 30, 23, 14]],

        // Event-related supplies
        ['code' => 'EVT-KIT', 'name' => 'Event Kits (sets)', 'usage' => [2, 3, 4, 2, 5, 6, 3, 4, 6, 7, 5, 3]],
        ['code' => 'EVT-CHR', 'name' => 'Chairs & Tables (sets)', 'usage' => [50, 60, 80, 55, 100, 120, 70, 85, 130, 150, 100, 60]],
        ['code' => 'EVT-SND', 'name' => 'Sound System Units', 'usage' => [2, 2, 3, 2, 3, 4, 2, 3, 4, 5, 3, 2]],
        ['code' => 'EVT-EXT', 'name' => 'Extension Cords', 'usage' => [10, 12, 15, 11, 18, 20, 13, 15, 22, 25, 18, 12]],
        ['code' => 'EVT-BNR', 'name' => 'Banners', 'usage' => [5, 6, 8, 5, 10, 12, 7, 9, 13, 15, 10, 6]],
    ];

    $cache = [
        'months' => $months,
        'items' => $items
    ];

    return $cache;
}
