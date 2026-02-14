<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Global error handler - converts PHP errors/exceptions to JSON responses
set_exception_handler(function($e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    error_log("[FORECASTING] Uncaught exception: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

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

if ($method === 'POST') {
    if ($action === 'seed_history') {
        sendJson(seedHistoricalTransactions($db));
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid POST action']);
    }
    exit();
}

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
    if (!tablesAvailable($db, ['supplies'])) {
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
    if (shouldUseFallbackForecast() || !tablesAvailable($db, ['supplies'])) {
        return getFallbackDemandForecast($db);
    }

    $hasTransactions = tablesAvailable($db, ['supply_transactions']);

    if ($hasTransactions) {
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
    } else {
        $query = "SELECT 
                s.id,
                s.item_code,
                s.name,
                s.current_stock,
                s.minimum_stock,
                0 AS total_out,
                NOW() AS first_txn,
                NOW() AS last_txn
            FROM supplies s
            WHERE s.status = 'active'
            ORDER BY s.name";
    }

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
        if ($item['current_stock'] <= 0) {
            $runoutDays = 0;
            $stockCoverage = 0;
        } else {
            $runoutDays = $avg > 0 ? round($item['current_stock'] / max($avg, 0.0001), 1) : null;
            $stockCoverage = $avg > 0 ? round($item['current_stock'] / $avg, 1) : null;
        }

        $priority = classifyPriority($reorderQty, $runoutDays, $stockCoverage, $item['current_stock'], $item['forecast_30_day'], $item['minimum_stock']);

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

function classifyPriority($reorderQty, $runoutDays, $stockCoverage, $currentStock, $forecast30, $minimumStock = 0)
{
    // Out of stock is always critical
    if ($currentStock <= 0) {
        return 'critical';
    }

    // Below minimum stock is never overstock - check this FIRST
    if ($minimumStock > 0 && $currentStock <= $minimumStock) {
        if ($currentStock <= ceil($minimumStock * 0.25)) {
            return 'critical';
        }
        if ($currentStock <= ceil($minimumStock * 0.5)) {
            return 'high';
        }
        return 'medium';
    }

    // Overstock: only if well above BOTH forecast AND minimum stock
    if ($forecast30 > 0 && $currentStock >= ($forecast30 * 3) && ($minimumStock <= 0 || $currentStock >= ($minimumStock * 3))) {
        return 'overstock';
    }

    // No reorder needed and above minimum = low priority
    if ($reorderQty <= 0) {
        return 'low';
    }

    // No consumption data - use minimum stock to judge
    if ($runoutDays === null) {
        if ($minimumStock > 0 && $currentStock <= $minimumStock) {
            return 'high';
        }
        return 'medium';
    }

    // Running out soon
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
        ['code' => 'LIB-LOUNGE', 'name' => 'Library Lounge Chairs', 'usage' => [8, 10, 12, 9, 14, 15, 11, 12, 16, 18, 14, 9]],
        ['code' => 'LIB-PROJ', 'name' => 'Library Mini Projectors', 'usage' => [3, 4, 5, 3, 6, 6, 4, 5, 7, 8, 6, 4]],
        ['code' => 'CLN-COT', 'name' => 'Clinic Recovery Cots', 'usage' => [2, 2, 3, 2, 3, 4, 2, 3, 4, 5, 3, 2]],
        ['code' => 'CLN-MON', 'name' => 'Clinic Vital Monitors', 'usage' => [1, 1, 2, 1, 2, 2, 1, 2, 3, 3, 2, 1]],
        ['code' => 'OSAS-LANY', 'name' => 'OSAS ID Card Lanyards', 'usage' => [20, 22, 25, 21, 26, 28, 23, 24, 30, 32, 27, 20]],
        ['code' => 'OSAS-FORM', 'name' => 'OSAS Permit Forms', 'usage' => [12, 14, 16, 13, 17, 18, 15, 16, 20, 22, 18, 12]],
        ['code' => 'EVT-KIT', 'name' => 'Event Kits', 'usage' => [2, 3, 4, 2, 5, 6, 3, 4, 6, 7, 5, 3]],
        ['code' => 'EVT-SPEAK', 'name' => 'Portable Speakers', 'usage' => [1, 1, 2, 1, 2, 2, 1, 2, 3, 3, 2, 1]],
    ];

    $cache = [
        'months' => $months,
        'items' => $items
    ];

    return $cache;
}

/**
 * Seed historical transactions for supplies that have no transaction history.
 * This enables the AI forecasting engine to calculate average_daily_usage and projected_runout_days.
 * Generates realistic "out" transactions spread over the past 90 days based on current stock levels.
 */
function seedHistoricalTransactions(PDO $db)
{
    // Ensure supply_transactions table exists
    $db->exec("CREATE TABLE IF NOT EXISTS supply_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supply_id INT NOT NULL,
        transaction_type VARCHAR(20) NOT NULL,
        quantity INT NOT NULL,
        unit_cost DECIMAL(12,2) NULL,
        total_cost DECIMAL(12,2) NULL,
        reference_number VARCHAR(100) NULL,
        notes TEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_supply (supply_id),
        INDEX idx_type (transaction_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Fix AUTO_INCREMENT if missing
    try {
        $colCheck = $db->query("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = 'supply_transactions' 
                                AND COLUMN_NAME = 'id'")->fetch(PDO::FETCH_ASSOC);
        if ($colCheck && stripos($colCheck['EXTRA'], 'auto_increment') === false) {
            try { $db->exec("ALTER TABLE supply_transactions ADD PRIMARY KEY (id)"); } catch (Throwable $e) {}
            $db->exec("ALTER TABLE supply_transactions MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        }
    } catch (Throwable $e) {}

    // Get all active supplies
    $supplies = $db->query("SELECT id, item_code, name, current_stock, minimum_stock, unit_cost 
                            FROM supplies WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($supplies)) {
        return ['success' => false, 'message' => 'No active supplies found. Add items to Live Inventory first.'];
    }

    // Check which supplies already have transactions
    $existingTxn = [];
    try {
        $stmt = $db->query("SELECT DISTINCT supply_id FROM supply_transactions");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingTxn[(int)$row['supply_id']] = true;
        }
    } catch (Throwable $e) {}

    $seeded = 0;
    $skipped = 0;
    $totalTxns = 0;

    $insertSql = "INSERT INTO supply_transactions (supply_id, transaction_type, quantity, unit_cost, total_cost, reference_number, notes, created_by, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertSql);

    // Also prepare an initial stock-in transaction
    foreach ($supplies as $supply) {
        $supplyId = (int)$supply['id'];

        if (isset($existingTxn[$supplyId])) {
            $skipped++;
            continue;
        }

        $currentStock = max(1, (int)$supply['current_stock']);
        $minStock = max(1, (int)$supply['minimum_stock']);
        $unitCost = (float)($supply['unit_cost'] ?? 0);

        // Calculate a realistic daily usage rate: aim for stock to run out in 30-60 days
        $targetRunoutDays = rand(25, 55);
        $dailyUsage = max(1, (int)round($currentStock / $targetRunoutDays));

        // Generate an initial "stock in" transaction 90 days ago (represents when stock was received)
        $initialStock = $currentStock + ($dailyUsage * 90);
        $startDate = date('Y-m-d H:i:s', strtotime('-90 days'));
        $insertStmt->execute([
            $supplyId, 'in', $initialStock, $unitCost, $initialStock * $unitCost,
            'HIST-SEED-IN-' . $supply['item_code'], 'Historical seed: initial stock receipt',
            $_SESSION['user_id'] ?? null, $startDate
        ]);
        $totalTxns++;

        // Generate "out" transactions spread over the past 90 days
        $totalConsumed = $initialStock - $currentStock;
        $daysToSpread = 90;
        $consumed = 0;

        for ($day = $daysToSpread; $day > 0 && $consumed < $totalConsumed; $day--) {
            // Vary daily usage: 50%-150% of average, with some zero days
            if (rand(1, 100) <= 15) continue; // 15% chance of no activity
            
            $variation = rand(50, 150) / 100;
            $qty = max(1, (int)round($dailyUsage * $variation));
            if ($consumed + $qty > $totalConsumed) {
                $qty = $totalConsumed - $consumed;
            }
            if ($qty <= 0) continue;

            $txnDate = date('Y-m-d H:i:s', strtotime("-{$day} days") + rand(28800, 64800)); // 8am-6pm
            $insertStmt->execute([
                $supplyId, 'out', $qty, $unitCost, $qty * $unitCost,
                'HIST-SEED-OUT-' . $supply['item_code'], 'Historical seed: simulated consumption',
                $_SESSION['user_id'] ?? null, $txnDate
            ]);
            $consumed += $qty;
            $totalTxns++;
        }

        $seeded++;
    }

    return [
        'success' => true,
        'message' => "Seeded $seeded item(s) with $totalTxns transaction(s). Skipped $skipped item(s) that already had history.",
        'seeded' => $seeded,
        'skipped' => $skipped,
        'total_transactions' => $totalTxns
    ];
}
