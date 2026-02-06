<?php
require_once 'includes/auth_check.php';
requireAuth();
requirePermission('ai_demand_forecasting');

$pageTitle = 'Gemini AI Forecasting - Property Custodian';

$additionalStyles = <<<HTML
<style>
    .forecast-grid {
        display: grid;
        gap: 1.5rem;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }

    .forecast-card {
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: linear-gradient(135deg, #f8fafc, #ffffff);
        box-shadow: 0 18px 45px -24px rgba(15, 23, 42, 0.18);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .forecast-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.75rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .chip-critical { background: #fee2e2; color: #991b1b; }
    .chip-warning { background: #fef3c7; color: #92400e; }
    .chip-normal { background: #dcfce7; color: #14532d; }
    .chip-info { background: #e0f2fe; color: #0c4a6e; }

    .forecast-table {
        width: 100%;
        border-collapse: collapse;
    }

    .forecast-table th,
    .forecast-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
    }

    .forecast-table thead th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        color: #64748b;
    }

    .forecast-empty {
        border: 2px dashed rgba(148, 163, 184, 0.35);
        border-radius: 20px;
        padding: 3rem 1.5rem;
        text-align: center;
        background: linear-gradient(180deg, rgba(236, 252, 203, 0.35), #fff);
    }

    @media (max-width: 768px) {
        .forecast-card { padding: 1.1rem; }
        .forecast-table th, .forecast-table td { padding: 0.6rem 0.75rem; }
    }
</style>
HTML;

ob_start();
?>
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 overflow-x-hidden lg:ml-64">
        <div class="p-4 sm:p-6 lg:p-10 space-y-8">
            <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-sm font-semibold tracking-wide text-blue-600 uppercase">Gemini AI Forecasting</p>
                    <h1 class="text-3xl sm:text-4xl font-black text-slate-900">Smart Supply Forecasting Insights</h1>
                    <p class="text-slate-600 max-w-3xl">
                        Anticipate supply needs with AI-assisted forecasts. Review demand predictions, reorder recommendations,
                        risk alerts, and seasonal patterns generated from your live inventory data.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button id="forecastRefreshBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-white font-semibold shadow-lg shadow-blue-500/25 hover:bg-blue-700 transition">
                        <i class="fas fa-rotate"></i>
                        Refresh Forecasts
                    </button>
                    <button id="forecastExportBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-blue-600 font-semibold border border-blue-200 hover:border-blue-300 hover:bg-blue-50/60 transition">
                        <i class="fas fa-file-export"></i>
                        Export Report
                    </button>
                </div>
            </header>

            <section id="forecastHighlights" class="forecast-grid"></section>

            <section class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold text-slate-900">Reorder Recommendations</h2>
                    <div class="flex gap-2">
                        <span class="forecast-chip chip-critical"><i class="fas fa-circle"></i>Critical</span>
                        <span class="forecast-chip chip-warning"><i class="fas fa-circle"></i>High</span>
                        <span class="forecast-chip chip-normal"><i class="fas fa-circle"></i>Optimal</span>
                        <span class="forecast-chip chip-info"><i class="fas fa-circle"></i>Overstock</span>
                    </div>
                </div>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-900/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="forecast-table" id="forecastReorderTable">
                            <thead>
                                <tr>
                                    <th>Supply</th>
                                    <th>Current Stock</th>
                                    <th>Avg Daily Usage</th>
                                    <th>Runout (days)</th>
                                    <th>Suggested Order</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-900/5 p-6 space-y-4" id="forecastAlerts">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Alerts & Warnings</h2>
                        <span class="text-sm text-slate-500">Updated <span id="forecastGeneratedAt">—</span></span>
                    </div>
                    <div class="space-y-4" id="forecastAlertsList"></div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-900/5 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Seasonal Usage Trends</h2>
                        <button id="seasonalityToggle" class="text-sm text-blue-600 font-semibold hover:text-blue-700 transition">View Details</button>
                    </div>
                    <div id="seasonalityChart" class="min-h-[280px] flex items-center justify-center text-slate-400 text-sm">
                        Loading seasonal data…
                    </div>
                    <div id="seasonalityBreakdown" class="hidden space-y-2 text-sm text-slate-600"></div>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="js/api.js"></script>
<script src="js/forecasting.js?v=2026020709"></script>
<?php
$content = ob_get_clean();
include 'layouts/layout.php';
