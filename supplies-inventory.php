<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Supplies Inventory - Property Custodian Management";

ob_start();
?>

<!-- Supplies Inventory Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 px-4 py-3 flex justify-between items-center">
        <button onclick="toggleMobileMenu()" class="p-2 text-gray-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-lg font-semibold text-gray-800">Supplies Inventory</h1>
        <div class="w-8"></div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 overflow-x-hidden">
        <div class="p-4 lg:p-8 pt-16 lg:pt-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6 lg:mb-8">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Supplies Inventory</h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">Track consumables, monitor low stock alerts, and process transactions from a single consolidated view.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                    <button onclick="openAddSupplyModal()" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition duration-200 w-full sm:w-auto">
                        <i class="fas fa-plus"></i><span>Add Historical Data Item</span>
                    </button>
                    <button onclick="openTransactionModal()" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg shadow-sm transition duration-200 w-full sm:w-auto">
                        <i class="fas fa-exchange-alt"></i><span>Stock Transaction</span>
                    </button>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white border border-gray-200 rounded-xl shadow p-4 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Inventory Filters</h2>
                        <p class="text-sm text-gray-500">Narrow down supplies by category, stock status, or keyword to focus on what matters.</p>
                    </div>
                    <button onclick="filterSupplies()" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition duration-200 self-start sm:self-auto">
                        <i class="fas fa-sync"></i><span>Refresh List</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" placeholder="Search supplies..." onkeyup="filterSupplies()" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="categoryFilter" onchange="filterSupplies()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            <option value="office">Office Supplies</option>
                            <option value="cleaning">Cleaning Supplies</option>
                            <option value="medical">Medical Supplies</option>
                            <option value="educational">Educational Materials</option>
                            <option value="maintenance">Maintenance Supplies</option>
                            <option value="cafeteria">Cafeteria Supplies</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                        <select id="stockFilter" onchange="filterSupplies()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Stock Levels</option>
                            <option value="low">Low Stock</option>
                            <option value="normal">Normal Stock</option>
                            <option value="out">Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="statusFilter" onchange="filterSupplies()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Supplies Table -->
            <div class="bg-white border border-gray-200 rounded-xl shadow overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Historical Data Catalog</h2>
                        <p class="text-sm text-gray-500">Simulated usage trends for key supply groups based on BPA-approved scenarios.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">View mode:</span>
                        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                            <button id="historicalTab" class="view-toggle px-3 py-1 text-sm font-medium rounded-md transition focus:outline-none" type="button">Historical Data</button>
                            <button id="inventoryTab" class="view-toggle px-3 py-1 text-sm font-medium rounded-md transition focus:outline-none" type="button">Live Inventory</button>
                        </div>
                    </div>
                </div>

                <div id="historicalDataPanel" class="px-4 sm:px-6 py-6 space-y-8 bg-gradient-to-b from-slate-50 to-white border-b border-gray-200">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Simulated Historical Data for Supplies Inventory Management</h3>
                        <p class="mt-1 text-sm text-gray-600 max-w-3xl">
                            These datasets power the Gemini AI forecasting module for School Management System 2. The values are system-generated using
                            Business Process Analysis (BPA) assumptions and realistic usage patterns for each supply cluster.
                        </p>
                    </div>

                    <section class="space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-blue-700 uppercase tracking-wide">1. Library Supplies – Historical Usage</p>
                            <p class="text-xs text-gray-500">Monthly consumption for print and stationery needs.</p>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-blue-100 bg-white shadow-sm">
                            <table class="min-w-full text-sm text-left">
                                <thead class="bg-blue-50 text-blue-900">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Month</th>
                                        <th class="px-4 py-3 font-semibold">Bond Paper (reams)</th>
                                        <th class="px-4 py-3 font-semibold">Printer Ink (pcs)</th>
                                        <th class="px-4 py-3 font-semibold">Staples (boxes)</th>
                                        <th class="px-4 py-3 font-semibold">Folders (pcs)</th>
                                        <th class="px-4 py-3 font-semibold">Markers (pcs)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-blue-50 text-gray-700">
                                    <?php
                                    $libraryData = [
                                        ['Jan', 120, 10, 15, 200, 30],
                                        ['Feb', 140, 12, 18, 220, 35],
                                        ['Mar', 160, 14, 20, 250, 40],
                                        ['Apr', 130, 11, 16, 210, 32],
                                        ['May', 170, 15, 22, 260, 45],
                                        ['Jun', 180, 16, 25, 280, 48],
                                        ['Jul', 150, 13, 19, 240, 38],
                                        ['Aug', 160, 14, 20, 250, 40],
                                        ['Sep', 190, 17, 26, 300, 50],
                                        ['Oct', 200, 18, 28, 320, 55],
                                        ['Nov', 170, 15, 22, 260, 45],
                                        ['Dec', 110, 9, 14, 180, 28],
                                    ];
                                    foreach ($libraryData as $row) {
                                        echo '<tr class="hover:bg-blue-50/60">';
                                        foreach ($row as $value) {
                                            echo '<td class="px-4 py-2">' . htmlspecialchars($value) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-teal-700 uppercase tracking-wide">2. Clinic Supplies – Historical Usage</p>
                            <p class="text-xs text-gray-500">Health bay consumption for medical response readiness.</p>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-teal-100 bg-white shadow-sm">
                            <table class="min-w-full text-sm text-left">
                                <thead class="bg-teal-50 text-teal-900">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Month</th>
                                        <th class="px-4 py-3 font-semibold">Alcohol (bottles)</th>
                                        <th class="px-4 py-3 font-semibold">Face Masks (boxes)</th>
                                        <th class="px-4 py-3 font-semibold">Paracetamol (tabs)</th>
                                        <th class="px-4 py-3 font-semibold">Syringes (pcs)</th>
                                        <th class="px-4 py-3 font-semibold">Gloves (boxes)</th>
                                        <th class="px-4 py-3 font-semibold">Vitamins (tabs)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-teal-50 text-gray-700">
                                    <?php
                                    $clinicData = [
                                        ['Jan', 60, 20, 300, 120, 15, 200],
                                        ['Feb', 70, 25, 320, 140, 18, 220],
                                        ['Mar', 80, 30, 350, 160, 20, 250],
                                        ['Apr', 65, 22, 310, 130, 16, 210],
                                        ['May', 75, 28, 340, 150, 19, 240],
                                        ['Jun', 85, 35, 380, 180, 22, 280],
                                        ['Jul', 70, 25, 330, 145, 18, 230],
                                        ['Aug', 75, 28, 350, 150, 19, 240],
                                        ['Sep', 90, 40, 400, 190, 25, 300],
                                        ['Oct', 95, 45, 420, 200, 28, 320],
                                        ['Nov', 80, 30, 360, 160, 20, 260],
                                        ['Dec', 60, 20, 300, 120, 15, 200],
                                    ];
                                    foreach ($clinicData as $row) {
                                        echo '<tr class="hover:bg-teal-50/60">';
                                        foreach ($row as $value) {
                                            echo '<td class="px-4 py-2">' . htmlspecialchars($value) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-rose-700 uppercase tracking-wide">3. First Aid Supplies – Historical Usage</p>
                            <p class="text-xs text-gray-500">Immediate response kits for campus incidents.</p>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-rose-100 bg-white shadow-sm">
                            <table class="min-w-full text-sm text-left">
                                <thead class="bg-rose-50 text-rose-900">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Month</th>
                                        <th class="px-4 py-3 font-semibold">Bandages (pcs)</th>
                                        <th class="px-4 py-3 font-semibold">Antiseptic (bottles)</th>
                                        <th class="px-4 py-3 font-semibold">Gauze Pads (pcs)</th>
                                        <th class="px-4 py-3 font-semibold">Medical Tape (rolls)</th>
                                        <th class="px-4 py-3 font-semibold">Cold Packs (pcs)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-50 text-gray-700">
                                    <?php
                                    $firstAidData = [
                                        ['Jan', 40, 10, 80, 20, 15],
                                        ['Feb', 45, 11, 90, 22, 18],
                                        ['Mar', 50, 12, 100, 25, 20],
                                        ['Apr', 42, 10, 85, 21, 16],
                                        ['May', 55, 13, 110, 28, 22],
                                        ['Jun', 60, 15, 120, 30, 25],
                                        ['Jul', 48, 12, 95, 24, 19],
                                        ['Aug', 52, 13, 100, 26, 21],
                                        ['Sep', 65, 16, 130, 32, 28],
                                        ['Oct', 70, 18, 140, 35, 30],
                                        ['Nov', 55, 14, 115, 29, 23],
                                        ['Dec', 38, 9, 75, 18, 14],
                                    ];
                                    foreach ($firstAidData as $row) {
                                        echo '<tr class="hover:bg-rose-50/60">';
                                        foreach ($row as $value) {
                                            echo '<td class="px-4 py-2">' . htmlspecialchars($value) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-indigo-700 uppercase tracking-wide">4. Event-Related Supplies – Historical Usage</p>
                            <p class="text-xs text-gray-500">Consumables and rentals for campus-wide gatherings.</p>
                        </div>
                        <div class="overflow-x-auto rounded-2xl border border-indigo-100 bg-white shadow-sm">
                            <table class="min-w-full text-sm text-left">
                                <thead class="bg-indigo-50 text-indigo-900">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Month</th>
                                        <th class="px-4 py-3 font-semibold">Event Kits</th>
                                        <th class="px-4 py-3 font-semibold">Chairs &amp; Tables</th>
                                        <th class="px-4 py-3 font-semibold">Sound System Units</th>
                                        <th class="px-4 py-3 font-semibold">Extension Cords</th>
                                        <th class="px-4 py-3 font-semibold">Banners</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-indigo-50 text-gray-700">
                                    <?php
                                    $eventData = [
                                        ['Jan', 2, 50, 2, 10, 5],
                                        ['Feb', 3, 60, 2, 12, 6],
                                        ['Mar', 4, 80, 3, 15, 8],
                                        ['Apr', 2, 55, 2, 11, 5],
                                        ['May', 5, 100, 3, 18, 10],
                                        ['Jun', 6, 120, 4, 20, 12],
                                        ['Jul', 3, 70, 2, 13, 7],
                                        ['Aug', 4, 85, 3, 15, 9],
                                        ['Sep', 6, 130, 4, 22, 13],
                                        ['Oct', 7, 150, 5, 25, 15],
                                        ['Nov', 5, 100, 3, 18, 10],
                                        ['Dec', 3, 60, 2, 12, 6],
                                    ];
                                    foreach ($eventData as $row) {
                                        echo '<tr class="hover:bg-indigo-50/60">';
                                        foreach ($row as $value) {
                                            echo '<td class="px-4 py-2">' . htmlspecialchars($value) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div id="inventoryPanel" class="table-responsive hidden">
                    <table class="min-w-full divide-y divide-gray-200 panel-inventory table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded">
                                </th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Category</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Min. Stock</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Unit Cost</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Location</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 lg:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="suppliesTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="10" class="px-6 py-4 text-center text-gray-500">Loading supplies...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gemini AI Forecasting Insights -->
            <section id="forecastIntegrationSection" class="mt-10 hidden">
                <div class="bg-white border border-indigo-100 rounded-3xl shadow-xl shadow-indigo-500/10 p-6 lg:p-8 space-y-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-indigo-600">Gemini AI Forecasting</p>
                            <h2 class="text-2xl lg:text-3xl font-black text-slate-900">Predictive Supply Signals</h2>
                            <p class="text-sm text-slate-500 max-w-2xl">
                                Live demand projections, smart reorder recommendations, and risk alerts generated by the Gemini AI forecasting engine.
                            </p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i class="fas fa-clock text-indigo-400"></i>
                                <span>Updated <span id="forecastIntegrationGeneratedAt">—</span></span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button id="forecastIntegrationRefresh" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-white font-semibold shadow-lg shadow-indigo-500/25 hover:bg-indigo-700 transition">
                                <i class="fas fa-rotate"></i>
                                Refresh Insights
                            </button>
                        </div>
                    </div>

                    <div id="forecastIntegrationHighlights" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"></div>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 bg-white border border-slate-200 rounded-3xl shadow-lg shadow-slate-900/5 overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-slate-900">AI Reorder Guidance</h3>
                                <span class="text-xs font-medium tracking-wide text-slate-400 uppercase">Top Recommendations</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" id="forecastIntegrationReorderTable">
                                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Supply</th>
                                            <th class="px-4 py-3 text-left font-semibold">Current Stock</th>
                                            <th class="px-4 py-3 text-left font-semibold">Avg Daily Usage</th>
                                            <th class="px-4 py-3 text-left font-semibold">Runout (days)</th>
                                            <th class="px-4 py-3 text-left font-semibold">Suggested Order</th>
                                            <th class="px-4 py-3 text-left font-semibold">Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody id="forecastIntegrationReorderBody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-3xl shadow-lg shadow-slate-900/5 p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-slate-900">Risk &amp; Opportunity Alerts</h3>
                                <span class="text-xs font-medium tracking-wide text-slate-400 uppercase">AI Monitored</span>
                            </div>
                            <div id="forecastIntegrationAlerts" class="space-y-3 text-sm"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mt-6">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Total Items</p>
                            <p id="totalItems" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">All active supply SKUs</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                            <i class="fas fa-boxes text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Low Stock Risks</p>
                            <p id="lowStockItems" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Items below safety threshold</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <i class="fas fa-exclamation-triangle text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Out of Stock</p>
                            <p id="outOfStockItems" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Requires immediate replenishment</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                            <i class="fas fa-times-circle text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-purple-600">Inventory Value</p>
                            <p id="expiringSoonItems" class="mt-2 text-3xl font-bold text-slate-900">₱0.00</p>
                            <p class="mt-1 text-xs text-slate-500">Current stock valuation</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-purple-500 text-white shadow-lg shadow-purple-500/30">
                            <i class="fas fa-peso-sign text-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Supply Modal -->
<div id="addSupplyModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto mt-16 mb-10 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-white border border-gray-200 rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add Historical Data Item</h3>
                    <p class="text-sm text-gray-500">Capture supply details, unit costs, and storage locations.</p>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="supplyForm" onsubmit="handleSupplySubmit(event)" class="px-4 sm:px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Code *</label>
                        <input type="text" id="itemCode" name="item_code" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                        <input type="text" id="itemName" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <option value="office">Office Supplies</option>
                            <option value="cleaning">Cleaning Supplies</option>
                            <option value="medical">Medical Supplies</option>
                            <option value="educational">Educational Materials</option>
                            <option value="maintenance">Maintenance Supplies</option>
                            <option value="cafeteria">Cafeteria Supplies</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                        <input type="text" id="unit" name="unit" placeholder="pcs, box, bottle, etc." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Stock</label>
                        <input type="number" id="currentStock" name="current_stock" min="0" onchange="calculateTotalValue()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock</label>
                        <input type="number" id="minimumStock" name="minimum_stock" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                        <input type="number" id="unitCost" name="unit_cost" step="0.01" min="0" onchange="calculateTotalValue()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Value</label>
                        <input type="number" id="totalValue" name="total_value" step="0.01" min="0" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                        <input type="text" id="storageLocation" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-times"></i><span>Cancel</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save"></i><span>Save Supply</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div id="transactionModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto mt-16 mb-10 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-white border border-gray-200 rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Stock Transaction</h3>
                    <p class="text-sm text-gray-500">Record stock in/out adjustments with optional references.</p>
                </div>
                <button onclick="closeTransactionModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="transactionForm" onsubmit="handleTransactionSubmit(event)" class="px-4 sm:px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Historical Data Item *</label>
                        <select id="transactionSupplyId" name="supply_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Historical Data Item</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type *</label>
                        <select id="transactionType" name="transaction_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="in">Stock In</option>
                            <option value="out">Stock Out</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                        <input type="number" id="transactionQuantity" name="quantity" required min="1" onchange="calculateTransactionCost()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                        <input type="number" id="transactionUnitCost" name="unit_cost" step="0.01" min="0" onchange="calculateTransactionCost()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost</label>
                        <input type="number" id="transactionTotalCost" name="total_cost" step="0.01" min="0" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                        <input type="text" id="referenceNumber" name="reference_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea id="transactionNotes" name="notes" rows="3" placeholder="Enter transaction details, purpose, and any additional notes..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 mt-6">
                    <button type="button" onclick="closeTransactionModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-times"></i><span>Cancel</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition duration-200">
                        <i class="fas fa-check-circle"></i><span>Process Transaction</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'components/detail_modal.php'; ?>

<script src="js/api.js"></script>
<script src="js/detail_handlers.js"></script>
<script>
let liveSupplies = [];
let historicalSupplies = [];
let filteredSupplies = [];
let currentEditId = null;
let currentView = 'inventory';

const forecastIntegrationElements = {
    section: document.getElementById('forecastIntegrationSection'),
    highlights: document.getElementById('forecastIntegrationHighlights'),
    reorderBody: document.getElementById('forecastIntegrationReorderBody'),
    alerts: document.getElementById('forecastIntegrationAlerts'),
    generatedAt: document.getElementById('forecastIntegrationGeneratedAt'),
    refreshBtn: document.getElementById('forecastIntegrationRefresh')
};

const forecastIntegrationState = {
    overview: null,
    reorders: [],
    alerts: []
};

const HISTORICAL_INVENTORY_ITEMS = [
    { item_code: 'LIB-BOND', name: 'Bond Paper (reams)', category: 'library', unit: 'reams', default_stock: 110, minimum_stock: 80, location: 'Library Supply Room', unit_cost: 280, description: 'Multi-purpose bond paper for print production.' },
    { item_code: 'LIB-INK', name: 'Printer Ink (pcs)', category: 'library', unit: 'pcs', default_stock: 9, minimum_stock: 5, location: 'Library Supply Room', unit_cost: 950, description: 'Ink cartridges for library printers.' },
    { item_code: 'LIB-STAP', name: 'Staples (boxes)', category: 'library', unit: 'boxes', default_stock: 14, minimum_stock: 8, location: 'Library Supply Room', unit_cost: 120, description: 'Staple wires for binding.' },
    { item_code: 'LIB-FOLD', name: 'Folders (pcs)', category: 'library', unit: 'pcs', default_stock: 180, minimum_stock: 120, location: 'Records Archive', unit_cost: 25, description: 'Document folders for filing.' },
    { item_code: 'LIB-MARK', name: 'Markers (pcs)', category: 'library', unit: 'pcs', default_stock: 28, minimum_stock: 15, location: 'Records Archive', unit_cost: 45, description: 'Whiteboard and labeling markers.' },
    { item_code: 'CLN-ALC', name: 'Alcohol (bottles)', category: 'clinic', unit: 'bottles', default_stock: 60, minimum_stock: 30, location: 'Clinic Storage', unit_cost: 65, description: 'Disinfecting ethyl alcohol.' },
    { item_code: 'CLN-MASK', name: 'Face Masks (boxes)', category: 'clinic', unit: 'boxes', default_stock: 20, minimum_stock: 10, location: 'Clinic Storage', unit_cost: 120, description: 'Disposable surgical masks.' },
    { item_code: 'CLN-PARA', name: 'Paracetamol (tabs)', category: 'clinic', unit: 'tabs', default_stock: 300, minimum_stock: 200, location: 'Clinic Storage', unit_cost: 6, description: 'Tablets for fever management.' },
    { item_code: 'CLN-SYR', name: 'Syringes (pcs)', category: 'clinic', unit: 'pcs', default_stock: 120, minimum_stock: 80, location: 'Clinic Storage', unit_cost: 18, description: 'Sterile syringes for medical procedures.' },
    { item_code: 'CLN-GLOV', name: 'Gloves (boxes)', category: 'clinic', unit: 'boxes', default_stock: 15, minimum_stock: 8, location: 'Clinic Storage', unit_cost: 150, description: 'Disposable gloves for health bay.' },
    { item_code: 'CLN-VITA', name: 'Vitamins (tabs)', category: 'clinic', unit: 'tabs', default_stock: 200, minimum_stock: 120, location: 'Clinic Storage', unit_cost: 8, description: 'Multivitamins for student welfare.' },
    { item_code: 'FA-BAND', name: 'Bandages (pcs)', category: 'first_aid', unit: 'pcs', default_stock: 38, minimum_stock: 20, location: 'First Aid Cabinets', unit_cost: 12, description: 'Elastic bandages for first aid.' },
    { item_code: 'FA-ANT', name: 'Antiseptic (bottles)', category: 'first_aid', unit: 'bottles', default_stock: 9, minimum_stock: 5, location: 'First Aid Cabinets', unit_cost: 55, description: 'Topical antiseptic solution.' },
    { item_code: 'FA-GAUZ', name: 'Gauze Pads (pcs)', category: 'first_aid', unit: 'pcs', default_stock: 75, minimum_stock: 40, location: 'First Aid Cabinets', unit_cost: 10, description: 'Sterile gauze pads for wound care.' },
    { item_code: 'FA-TAPE', name: 'Medical Tape (rolls)', category: 'first_aid', unit: 'rolls', default_stock: 18, minimum_stock: 10, location: 'First Aid Cabinets', unit_cost: 35, description: 'Hypoallergenic medical tape.' },
    { item_code: 'FA-COLD', name: 'Cold Packs (pcs)', category: 'first_aid', unit: 'pcs', default_stock: 14, minimum_stock: 8, location: 'First Aid Cabinets', unit_cost: 45, description: 'Instant cold packs for injuries.' },
    { item_code: 'EVT-KIT', name: 'Event Kits', category: 'events', unit: 'kits', default_stock: 3, minimum_stock: 2, location: 'Events Storage', unit_cost: 1500, description: 'Standard campus event kits.' },
    { item_code: 'EVT-CHAIR', name: 'Chairs & Tables', category: 'events', unit: 'sets', default_stock: 60, minimum_stock: 40, location: 'Events Storage', unit_cost: 950, description: 'Folding chairs and tables.' },
    { item_code: 'EVT-SOUND', name: 'Sound System Units', category: 'events', unit: 'units', default_stock: 2, minimum_stock: 1, location: 'Events Storage', unit_cost: 8500, description: 'Portable sound systems.' },
    { item_code: 'EVT-CORD', name: 'Extension Cords', category: 'events', unit: 'pcs', default_stock: 12, minimum_stock: 8, location: 'Events Storage', unit_cost: 250, description: 'Heavy-duty extension cords.' },
    { item_code: 'EVT-BAN', name: 'Banners', category: 'events', unit: 'pcs', default_stock: 6, minimum_stock: 4, location: 'Events Storage', unit_cost: 300, description: 'Reusable tarpaulin banners.' }
];

function normalizeSupply(raw, fallback = {}) {
    if (!raw && !fallback) return {};

    const base = raw ? { ...raw } : { ...fallback };
    const unitCost = base.unit_cost !== null && base.unit_cost !== undefined ? parseFloat(base.unit_cost) : (fallback.unit_cost ?? null);
    const currentStock = parseInt(base.current_stock ?? fallback.default_stock ?? 0, 10);
    const minimumStock = parseInt(base.minimum_stock ?? fallback.minimum_stock ?? 0, 10);

    return {
        id: base.id ?? fallback.id ?? null,
        item_code: base.item_code ?? fallback.item_code ?? '',
        name: base.name ?? fallback.name ?? '',
        description: base.description ?? fallback.description ?? '',
        category: base.category ?? fallback.category ?? '',
        unit: base.unit ?? fallback.unit ?? 'pcs',
        current_stock: currentStock,
        minimum_stock: minimumStock,
        unit_cost: unitCost,
        total_value: unitCost ? currentStock * unitCost : 0,
        location: base.location ?? base.storage_location ?? fallback.location ?? '-',
        status: base.status ?? fallback.status ?? 'active'
    };
}

function getHydratedHistoricalItems(liveData = []) {
    const liveMap = new Map((liveData || []).map(item => [String(item.item_code || '').toUpperCase(), normalizeSupply(item)]));

    return HISTORICAL_INVENTORY_ITEMS.map((preset, index) => {
        const live = liveMap.get(preset.item_code.toUpperCase());
        const enriched = normalizeSupply(live, {
            id: index + 1,
            item_code: preset.item_code,
            name: preset.name,
            description: preset.description,
            category: preset.category,
            unit: preset.unit,
            default_stock: preset.default_stock,
            minimum_stock: preset.minimum_stock,
            unit_cost: preset.unit_cost,
            location: preset.location,
            status: 'active'
        });

        return enriched;
    });
}

function getActiveDataset() {
    return currentView === 'historical' ? historicalSupplies : liveSupplies;
}

function setActiveView(mode = 'inventory') {
    currentView = mode === 'historical' ? 'historical' : 'inventory';
    updateToggleState();
    filterSupplies({ skipSummary: true });
    updateSummaryCards();
}

function updateToggleState() {
    const historicalTab = document.getElementById('historicalTab');
    const inventoryTab = document.getElementById('inventoryTab');
    const historicalPanel = document.getElementById('historicalDataPanel');
    const inventoryPanel = document.getElementById('inventoryPanel');

    if (!historicalTab || !inventoryTab || !historicalPanel || !inventoryPanel) {
        return;
    }

    const isHistorical = currentView === 'historical';

    historicalPanel.classList.toggle('hidden', !isHistorical);
    inventoryPanel.classList.toggle('hidden', isHistorical);

    historicalTab.classList.toggle('bg-white', isHistorical);
    historicalTab.classList.toggle('text-gray-900', isHistorical);
    historicalTab.classList.toggle('shadow-sm', isHistorical);
    historicalTab.classList.toggle('text-gray-500', !isHistorical);

    inventoryTab.classList.toggle('bg-white', !isHistorical);
    inventoryTab.classList.toggle('text-gray-900', !isHistorical);
    inventoryTab.classList.toggle('shadow-sm', !isHistorical);
    inventoryTab.classList.toggle('text-gray-500', isHistorical);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadSupplies();
    setupViewToggle();
    initializeForecastIntegration();
});

// Load all supplies
async function loadSupplies() {
    try {
        const response = await API.getSupplies();
        liveSupplies = Array.isArray(response) ? response.map(item => normalizeSupply(item)) : [];
        historicalSupplies = getHydratedHistoricalItems(liveSupplies);

        populateSupplySelect();
        setActiveView(currentView);
    } catch (error) {
        console.error('Error loading supplies:', error);
        showNotification('Error loading supplies', 'error');
    }
}

// Render supplies table
function renderSuppliesTable() {
    const tbody = document.getElementById('suppliesTableBody');

    if (filteredSupplies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="px-6 py-4 text-center text-gray-500">No supplies found</td></tr>';
        return;
    }

    tbody.innerHTML = filteredSupplies.map(supply => {
        const stockStatus = getStockStatus(supply);
        const statusBadge = getStatusBadge(supply.status);
        const liveRecord = liveSupplies.find(item => item.id === supply.id);
        const canTransact = Boolean(liveRecord);

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="rounded">
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${supply.item_code}
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${supply.name}</div>
                    <div class="text-sm text-gray-500">${supply.description || ''}</div>
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                    ${supply.category || '-'}
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${supply.current_stock} ${supply.unit || 'pcs'}</div>
                    <div class="text-xs ${stockStatus.color}">${stockStatus.text}</div>
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                    ${supply.minimum_stock} ${supply.unit || 'pcs'}
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">
                    ${supply.unit_cost ? '₱' + parseFloat(supply.unit_cost).toFixed(2) : '-'}
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden xl:table-cell">
                    <div>${supply.location || '-'}</div>
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center space-x-2">
                        <button onclick="viewSupplyDetails(${supply.id})" class="text-purple-600 hover:text-purple-900" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editSupply(${supply.id})" class="text-blue-600 hover:text-blue-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="archiveSupply(${supply.id})" class="text-orange-600 hover:text-orange-900" title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                        ${canTransact ? `
                            <button onclick="quickTransaction(${supply.id})" class="text-green-600 hover:text-green-900" title="Stock Transaction">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        ` : `
                            <span class="text-gray-300" title="Available on live inventory only">
                                <i class="fas fa-exchange-alt"></i>
                            </span>
                        `}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Get stock status
function getStockStatus(supply) {
    const current = parseInt(supply.current_stock) || 0;
    const minimum = parseInt(supply.minimum_stock) || 0;

    if (current === 0) {
        return { text: 'Out of Stock', color: 'text-red-600' };
    } else if (current <= minimum) {
        return { text: 'Low Stock', color: 'text-yellow-600' };
    } else {
        return { text: 'Normal', color: 'text-green-600' };
    }
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        'discontinued': '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Discontinued</span>',
        'expired': '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>'
    };
    return badges[status] || badges['active'];
}

// Update summary cards
function updateSummaryCards() {
    const dataset = getActiveDataset();
    const total = dataset.length;
    const lowStock = dataset.filter(s => (parseInt(s.current_stock) || 0) <= (parseInt(s.minimum_stock) || 0) && (parseInt(s.current_stock) || 0) > 0).length;
    const outOfStock = dataset.filter(s => (parseInt(s.current_stock) || 0) === 0).length;
    const totalValue = dataset.reduce((sum, s) => {
        const current = parseInt(s.current_stock) || 0;
        const unitCost = s.unit_cost !== null && s.unit_cost !== undefined ? parseFloat(s.unit_cost) : 0;
        return sum + (current * unitCost);
    }, 0);

    document.getElementById('totalItems').textContent = total;
    document.getElementById('lowStockItems').textContent = lowStock;
    document.getElementById('outOfStockItems').textContent = outOfStock;
    document.getElementById('expiringSoonItems').textContent = '₱' + totalValue.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// Populate supply select for transactions
function populateSupplySelect() {
    const select = document.getElementById('transactionSupplyId');
    select.innerHTML = '<option value="">Select Live Inventory Item</option>' +
        liveSupplies.map(supply => `<option value="${supply.id}">${supply.item_code} - ${supply.name}</option>`).join('');
}

function setupViewToggle() {
    const historicalTab = document.getElementById('historicalTab');
    const inventoryTab = document.getElementById('inventoryTab');
    const historicalPanel = document.getElementById('historicalDataPanel');
    const inventoryPanel = document.getElementById('inventoryPanel');

    if (!historicalTab || !inventoryTab || !historicalPanel || !inventoryPanel) {
        return;
    }

    historicalTab.addEventListener('click', () => setActiveView('historical'));
    inventoryTab.addEventListener('click', () => setActiveView('inventory'));

    setActiveView('inventory');
}

// Filter supplies
function filterSupplies(options = {}) {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const stockFilter = document.getElementById('stockFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    const dataset = getActiveDataset();

    filteredSupplies = dataset.filter(supply => {
        const matchesSearch = !search ||
            supply.name.toLowerCase().includes(search) ||
            supply.item_code.toLowerCase().includes(search) ||
            (supply.description && supply.description.toLowerCase().includes(search));

        const matchesCategory = !category || supply.category === category;
        const matchesStatus = !statusFilter || supply.status === statusFilter;

        let matchesStock = true;
        if (stockFilter) {
            const current = parseInt(supply.current_stock) || 0;
            const minimum = parseInt(supply.minimum_stock) || 0;

            switch(stockFilter) {
                case 'low':
                    matchesStock = current <= minimum && current > 0;
                    break;
                case 'normal':
                    matchesStock = current > minimum;
                    break;
                case 'out':
                    matchesStock = current === 0;
                    break;
            }
        }

        return matchesSearch && matchesCategory && matchesStatus && matchesStock;
    });

    if (!options.skipSummary) {
        updateSummaryCards();
    }

    renderSuppliesTable();
}

// Modal functions
function openAddSupplyModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add Historical Data Item';
    document.getElementById('supplyForm').reset();
    document.getElementById('addSupplyModal').classList.remove('hidden');
}

function editSupply(id) {
    const dataset = getActiveDataset();
    const supply = dataset.find(s => s.id == id);
    if (!supply) return;

    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Edit Historical Data Item';

    // Populate form
    document.getElementById('itemCode').value = supply.item_code || '';
    document.getElementById('itemName').value = supply.name || '';
    document.getElementById('description').value = supply.description || '';
    document.getElementById('category').value = supply.category || '';
    document.getElementById('unit').value = supply.unit || '';
    document.getElementById('currentStock').value = supply.current_stock || '';
    document.getElementById('minimumStock').value = supply.minimum_stock || '';
    document.getElementById('totalValue').value = supply.total_value || '';
    document.getElementById('unitCost').value = supply.unit_cost || '';
    document.getElementById('storageLocation').value = supply.location || '';
    document.getElementById('status').value = supply.status || 'active';

    document.getElementById('addSupplyModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('addSupplyModal').classList.add('hidden');
    currentEditId = null;
}

function openTransactionModal() {
    document.getElementById('transactionForm').reset();
    document.getElementById('transactionModal').classList.remove('hidden');
}

function quickTransaction(supplyId) {
    const live = liveSupplies.find(s => s.id == supplyId);
    if (!live) {
        showNotification('Transactions are available for live inventory items only.', 'warning');
        return;
    }

    openTransactionModal();
    document.getElementById('transactionSupplyId').value = supplyId;
}

function closeTransactionModal() {
    document.getElementById('transactionModal').classList.add('hidden');
}

// Handle form submissions
async function handleSupplySubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    try {
        if (currentEditId) {
            await API.updateSupply(currentEditId, data);
            showNotification('Supply updated successfully', 'success');
        } else {
            await API.createSupply(data);
            showNotification('Supply created successfully', 'success');
        }

        closeModal();
        loadSupplies();
    } catch (error) {
        console.error('Error saving supply:', error);
        showNotification('Error saving supply', 'error');
    }
}

async function handleTransactionSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    try {
        await API.createTransaction(data);
        showNotification('Transaction processed successfully', 'success');
        closeTransactionModal();
        loadSupplies();
    } catch (error) {
        console.error('Error processing transaction:', error);
        showNotification('Error processing transaction', 'error');
    }
}

// Archive supply
async function archiveSupply(id) {
    const reason = prompt('Archive reason (optional):', '');
    if (reason === null) return;

    try {
        await API.archiveSupply(id, {
            archive_reason: reason || null
        });
        showNotification('Supply archived successfully', 'success');
        loadSupplies();
    } catch (error) {
        console.error('Error archiving supply:', error);
        showNotification(error.message || 'Error archiving supply', 'error');
    }
}

// Calculate total value for supply
function calculateTotalValue() {
    const currentStock = parseFloat(document.getElementById('currentStock').value) || 0;
    const unitCost = parseFloat(document.getElementById('unitCost').value) || 0;
    const totalValue = currentStock * unitCost;
    document.getElementById('totalValue').value = totalValue.toFixed(2);
}

// Calculate total cost for transaction
function calculateTransactionCost() {
    const quantity = parseFloat(document.getElementById('transactionQuantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('transactionUnitCost').value) || 0;
    const totalCost = quantity * unitCost;
    document.getElementById('transactionTotalCost').value = totalCost.toFixed(2);
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>