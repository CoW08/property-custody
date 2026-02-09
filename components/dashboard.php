<!-- Dashboard Module -->
<div id="dashboard-module" class="module">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1">Property Management Dashboard</h1>
                <p class="text-gray-600">Welcome back, <span id="welcomeUser" class="font-medium"><?php echo htmlspecialchars($userName ?? 'User'); ?></span>
                <span class="text-sm text-gray-500">(<?php echo htmlspecialchars($userRole ?? 'User'); ?>)</span></p>
            </div>
            <span class="pill info">Today</span>
        </div>
        <div class="mt-5" data-forecast-summary>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-500">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-blue-50 text-blue-600">
                        <i class="fas fa-robot"></i>
                    </span>
                    <div>
                        <p class="font-semibold text-gray-700">Gemini AI Forecasting</p>
                        <p>Loading intelligent supply insights…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats & Status Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-500/20">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-slate-50 to-white"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Total Items</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900" id="totalItems">0</p>
                    <p class="mt-1 text-xs text-slate-500">Items tracked across the institution</p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                    <i class="fas fa-boxes text-lg"></i>
                </span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-emerald-500/20">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-slate-50 to-white"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Available Items</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900" id="availableItems">0</p>
                    <p class="mt-1 text-xs text-slate-500">Ready for assignment or issuance</p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                    <i class="fas fa-check-circle text-lg"></i>
                </span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-amber-400/20">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-slate-50 to-white"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Needs Maintenance</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900" id="maintenanceItems">0</p>
                    <p class="mt-1 text-xs text-slate-500">Queued for servicing or inspection</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-amber-700">
                            <i class="fas fa-calendar-day text-[10px]"></i>
                            <span id="maintenanceDueToday">0 due today</span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-rose-700">
                            <i class="fas fa-exclamation-circle text-[10px]"></i>
                            <span id="maintenanceOverdue">0 overdue</span>
                        </span>
                    </div>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-rose-500/20">
            <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 via-slate-50 to-white"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Damaged / Lost</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900" id="damagedItems">0</p>
                    <p class="mt-1 text-xs text-slate-500">Items needing incident resolution</p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                    <i class="fas fa-times-circle text-lg"></i>
                </span>
            </div>
        </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-slate-50 to-white"></div>
            <div class="relative flex flex-col h-full">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Item Status Mix</p>
                        <p class="mt-1 text-xs text-slate-500">Available, assigned, maintenance, damaged/lost, disposed</p>
                    </div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="w-full h-52">
                        <canvas id="assetStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Recent Activities</h3>
            <span class="text-xs text-gray-500">Last 20</span>
        </div>
        <div id="recentActivities" class="space-y-4 text-sm text-gray-700">
            <!-- Activities will be loaded here -->
        </div>
    </div>
</div>
