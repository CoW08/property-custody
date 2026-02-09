// Reports functionality
class ReportsManager {
    constructor() {
        this.currentReport = 'overview';
        this.charts = {};
        this.dateFilter = null;
        this.dateFrom = null;
        this.dateTo = null;
        this.init();
    }

    init() {
        this.setupDateFilter();
        this.setupEventListeners();
        this.loadOverviewReport();
    }

    setupDateFilter() {
        // Initialize date range filter
        this.dateFilter = new DateRangeFilter('dateFilterContainer', {
            onApply: (dateFrom, dateTo, filter) => {
                this.dateFrom = dateFrom;
                this.dateTo = dateTo;
                this.refreshCurrentReport();
            }
        });
    }

    setupEventListeners() {
        // Report type buttons
        document.querySelectorAll('.report-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const reportType = e.currentTarget.getAttribute('data-report');
                if (reportType && reportType !== 'null') {
                    this.switchReport(reportType);
                } else {
                    console.error('Invalid report type:', reportType);
                }
            });
        });

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportCurrentReport();
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshCurrentReport();
            });
        }
    }

    switchReport(reportType) {
        // Update active button
        document.querySelectorAll('.report-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-700');
        });

        const activeBtn = document.querySelector(`[data-report="${reportType}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-200', 'text-gray-700');
            activeBtn.classList.add('bg-blue-600', 'text-white');
        }

        this.currentReport = reportType;
        this.loadReport(reportType);
    }

    async loadReport(reportType) {
        try {
            showLoading();
            
            // Build URL with date range parameters
            let url = `reports.php?action=${reportType}`;
            if (this.dateFrom && this.dateTo) {
                url += `&date_from=${this.dateFrom}&date_to=${this.dateTo}`;
            }
            
            const response = await API.request(url);
            this.renderReport(reportType, response);
        } catch (error) {
            console.error('Error loading report:', error);
            showError(`Failed to load ${reportType} report: ${error.message}`);
        } finally {
            hideLoading();
        }
    }

    async exportCurrentReport() {
        try {
            showLoading();
            
            // Build export URL with proper path (no leading slash for relative path)
            const baseUrl = window.location.origin + window.location.pathname.replace('reports.php', '');
            let url = `${baseUrl}api/reports.php?action=${this.currentReport}&export=excel`;
            if (this.dateFrom && this.dateTo) {
                url += `&date_from=${this.dateFrom}&date_to=${this.dateTo}`;
            }
            
            console.log('Export URL:', url); // Debug log
            
            // Open in new window to trigger download
            window.open(url, '_blank');
            
            showNotification('Report export started. File will download shortly.', 'success');
        } catch (error) {
            console.error('Error exporting report:', error);
            showError('Failed to export report');
        } finally {
            hideLoading();
        }
    }

    refreshCurrentReport() {
        this.loadReport(this.currentReport);
    }

    async loadOverviewReport() {
        await this.loadReport('overview');
    }

    renderReport(reportType, data) {
        const container = document.getElementById('reportContent');
        if (!container) return;

        switch (reportType) {
            case 'overview':
                this.renderOverviewReport(container, data);
                break;
            case 'assets':
                this.renderAssetsReport(container, data);
                break;
            case 'maintenance':
                this.renderMaintenanceReport(container, data);
                break;
            case 'procurement':
                this.renderProcurementReport(container, data);
                break;
            case 'audit':
                this.renderAuditReport(container, data);
                break;
            case 'financial':
                this.renderFinancialReport(container, data);
                break;
            default:
                container.innerHTML = '<p class="text-gray-500">Report type not found.</p>';
        }
    }

    renderOverviewReport(container, data) {
        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                ${data.assets ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Items</p>
                            <p class="text-3xl font-bold text-blue-600">${data.assets.total_assets || 0}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-boxes text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-green-600">${data.assets.available_assets || 0}</span> Available •
                        <span class="text-yellow-600">${data.assets.assigned_assets || 0}</span> Assigned •
                        <span class="text-red-600">${data.assets.damaged_assets || 0}</span> Damaged
                    </div>
                </div>
                ` : ''}

                ${data.maintenance ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Maintenance</p>
                            <p class="text-3xl font-bold text-orange-600">${data.maintenance.total_schedules || 0}</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-tools text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-blue-600">${data.maintenance.scheduled || 0}</span> Scheduled •
                        <span class="text-red-600">${data.maintenance.overdue || 0}</span> Overdue
                    </div>
                </div>
                ` : ''}

                ${data.procurement ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Procurement</p>
                            <p class="text-3xl font-bold text-purple-600">${data.procurement.total_requests || 0}</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-yellow-600">${data.procurement.pending || 0}</span> Pending •
                        <span class="text-green-600">${data.procurement.approved || 0}</span> Approved
                    </div>
                </div>
                ` : ''}

                ${data.audits ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Audits</p>
                            <p class="text-3xl font-bold text-green-600">${data.audits.total_audits || 0}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-search text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-blue-600">${data.audits.active || 0}</span> Active •
                        <span class="text-green-600">${data.audits.completed || 0}</span> Completed
                    </div>
                </div>
                ` : ''}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="reportsManager.switchReport('assets')" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <span class="flex items-center">
                                <i class="fas fa-boxes text-blue-600 mr-3"></i>
                                Items Report
                            </span>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </button>
                        <button onclick="reportsManager.switchReport('maintenance')" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <span class="flex items-center">
                                <i class="fas fa-tools text-orange-600 mr-3"></i>
                                Maintenance Report
                            </span>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </button>
                        <button onclick="reportsManager.switchReport('financial')" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <span class="flex items-center">
                                <i class="fas fa-chart-line text-green-600 mr-3"></i>
                                Financial Report
                            </span>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Health</h3>
                    <div class="space-y-4">
                        ${data.assets ? `
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Item Utilization</span>
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${data.assets.total_assets > 0 ? (data.assets.assigned_assets / data.assets.total_assets * 100) : 0}%"></div>
                                </div>
                                <span class="text-sm text-gray-500">${data.assets.total_assets > 0 ? Math.round(data.assets.assigned_assets / data.assets.total_assets * 100) : 0}%</span>
                            </div>
                        </div>
                        ` : ''}

                        ${data.maintenance ? `
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Maintenance Health</span>
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: ${data.maintenance.total_schedules > 0 ? ((data.maintenance.total_schedules - data.maintenance.overdue) / data.maintenance.total_schedules * 100) : 100}%"></div>
                                </div>
                                <span class="text-sm text-gray-500">${data.maintenance.total_schedules > 0 ? Math.round((data.maintenance.total_schedules - data.maintenance.overdue) / data.maintenance.total_schedules * 100) : 100}%</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    renderAssetsReport(container, data) {
        if (data.message) {
            container.innerHTML = `<div class="text-center py-8"><p class="text-gray-500">${data.message}</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items by Category</h3>
                    ${data.by_category && data.by_category.length > 0 ? `
                        <div class="chart-container mb-4">
                            <canvas id="categoryChart" class="w-full h-64"></canvas>
                        </div>
                    ` : ''}
                    <div class="space-y-3 max-h-40 overflow-y-auto">
                        ${data.by_category ? data.by_category.map((item, index) => {
                            const palette = [
                                'bg-blue-100 text-blue-800',
                                'bg-green-100 text-green-800',
                                'bg-amber-100 text-amber-800',
                                'bg-red-100 text-red-800',
                                'bg-purple-100 text-purple-800',
                                'bg-cyan-100 text-cyan-800'
                            ];
                            const colorClass = palette[index % palette.length];
                            return `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-700">${item.category || 'Uncategorized'}</span>
                                    <span class="${colorClass} px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                                </div>
                            `;
                        }).join('') : '<p class="text-gray-500">No category data available</p>'}
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items by Status</h3>
                    ${data.by_status && data.by_status.length > 0 ? `
                        <div class="chart-container mb-4">
                            <canvas id="statusChart" class="w-full h-64"></canvas>
                        </div>
                    ` : ''}
                    <div class="space-y-3 max-h-40 overflow-y-auto">
                        ${data.by_status ? data.by_status.map(item => {
                            const statusColors = {
                                'available': 'bg-green-100 text-green-800',
                                'assigned': 'bg-blue-100 text-blue-800',
                                'maintenance': 'bg-yellow-100 text-yellow-800',
                                'damaged': 'bg-red-100 text-red-800',
                                'lost': 'bg-gray-100 text-gray-800'
                            };
                            return `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-700 capitalize">${item.status}</span>
                                    <span class="${statusColors[item.status] || 'bg-gray-100 text-gray-800'} px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                                </div>
                            `;
                        }).join('') : '<p class="text-gray-500">No status data available</p>'}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items by Location</h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        ${data.by_location ? data.by_location.map(item => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">${item.location}</span>
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No location data available</p>'}
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Item Additions</h3>
                    ${data.monthly_additions && data.monthly_additions.length > 0 ? `
                        <div class="chart-container mb-4">
                            <canvas id="monthlyChart" class="w-full h-64"></canvas>
                        </div>
                    ` : ''}
                    <div class="space-y-3 max-h-40 overflow-y-auto">
                        ${data.monthly_additions ? data.monthly_additions.map(item => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">${item.month}</span>
                                <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No monthly data available</p>'}
                    </div>
                </div>
            </div>
        `;

        // Clear previous charts
        chartManager.clear();

        // Create charts after DOM is updated
        setTimeout(() => {
            if (data.by_category && data.by_category.length > 0) {
                const categoryData = formatChartData(data.by_category, 'category', 'count');
                const categoryChart = createChart('categoryChart', categoryData, { type: 'pie' });
                if (categoryChart) chartManager.addChart(categoryChart);
            }

            if (data.by_status && data.by_status.length > 0) {
                const statusData = formatChartData(data.by_status, 'status', 'count');
                const statusChart = createChart('statusChart', statusData, { type: 'pie' });
                if (statusChart) chartManager.addChart(statusChart);
            }

            if (data.monthly_additions && data.monthly_additions.length > 0) {
                const monthlyData = formatChartData(data.monthly_additions, 'month', 'count');
                const monthlyChart = createChart('monthlyChart', monthlyData, { type: 'line' });
                if (monthlyChart) chartManager.addChart(monthlyChart);
            }
        }, 100);
    }

    renderMaintenanceReport(container, data) {
        if (data.message) {
            container.innerHTML = `<div class="text-center py-8"><p class="text-gray-500">${data.message}</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">By Priority</h3>
                    <div class="space-y-3">
                        ${data.by_priority ? data.by_priority.map(item => {
                            const priorityColors = {
                                'low': 'bg-green-100 text-green-800',
                                'medium': 'bg-yellow-100 text-yellow-800',
                                'high': 'bg-orange-100 text-orange-800',
                                'critical': 'bg-red-100 text-red-800'
                            };
                            return `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-700 capitalize">${item.priority}</span>
                                    <span class="${priorityColors[item.priority] || 'bg-gray-100 text-gray-800'} px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                                </div>
                            `;
                        }).join('') : '<p class="text-gray-500">No priority data available</p>'}
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Averages</h3>
                    ${data.averages ? `
                        <div class="space-y-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Average Cost</p>
                                <p class="text-2xl font-bold text-green-600">${formatCurrency(data.averages.avg_cost)}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Average Duration</p>
                                <p class="text-2xl font-bold text-blue-600">${data.averages.avg_duration ? Math.round(data.averages.avg_duration * 10) / 10 : 0} hrs</p>
                            </div>
                        </div>
                    ` : '<p class="text-gray-500">No average data available</p>'}
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Completed</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        ${data.monthly_completed ? data.monthly_completed.map(item => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">${item.month}</span>
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No monthly data available</p>'}
                    </div>
                </div>
            </div>
        `;
    }

    renderProcurementReport(container, data) {
        if (data.message) {
            container.innerHTML = `<div class="text-center py-8"><p class="text-gray-500">${data.message}</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Requests by Status</h3>
                    <div class="space-y-3">
                        ${data.by_status ? data.by_status.map(item => {
                            const statusColors = {
                                'pending': 'bg-yellow-100 text-yellow-800',
                                'approved': 'bg-green-100 text-green-800',
                                'rejected': 'bg-red-100 text-red-800',
                                'completed': 'bg-blue-100 text-blue-800'
                            };
                            return `
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium text-gray-700 capitalize">${item.status}</span>
                                    <span class="${statusColors[item.status] || 'bg-gray-100 text-gray-800'} px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                                </div>
                            `;
                        }).join('') : '<p class="text-gray-500">No status data available</p>'}
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Requesting Departments</h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        ${data.top_items ? data.top_items.map(item => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700 truncate mr-2">${item.department}</span>
                                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">${item.request_count}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No department data available</p>'}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Procurement Requests</h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    ${data.monthly_requests ? data.monthly_requests.map(item => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">${item.month}</span>
                            <div class="text-right">
                                <div class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium mb-1">${item.count} requests</div>
                                <div class="text-sm text-gray-600">${formatCurrency(item.total_cost)}</div>
                            </div>
                        </div>
                    `).join('') : '<p class="text-gray-500">No monthly data available</p>'}
                </div>
            </div>
        `;
    }

    renderAuditReport(container, data) {
        if (data.message) {
            container.innerHTML = `<div class="text-center py-8"><p class="text-gray-500">${data.message}</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Audit Statistics</h3>
                    ${data.statistics ? `
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">Total Audits</span>
                                <span class="text-2xl font-bold text-blue-600">${data.statistics.total_audits}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">Completed</span>
                                <span class="text-2xl font-bold text-green-600">${data.statistics.completed_audits}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700">Average Duration</span>
                                <span class="text-2xl font-bold text-purple-600">${data.statistics.avg_duration_days ? Math.round(data.statistics.avg_duration_days) : 0} days</span>
                            </div>
                        </div>
                    ` : '<p class="text-gray-500">No statistics available</p>'}
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Audits by Type</h3>
                    <div class="space-y-3">
                        ${data.by_type ? data.by_type.map(item => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-700 capitalize">${item.audit_type}</span>
                                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">${item.count}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No audit type data available</p>'}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Audits</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Summary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${data.recent_audits ? data.recent_audits.map(audit => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">${audit.audit_type}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${audit.department}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(audit.start_date)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${audit.end_date ? formatDate(audit.end_date) : 'Ongoing'}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">${audit.summary || 'No summary'}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent audits available</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    renderFinancialReport(container, data) {
        if (data.message) {
            container.innerHTML = `<div class="text-center py-8"><p class="text-gray-500">${data.message}</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                ${data.asset_values ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Item Values</h3>
                    <div class="space-y-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Value</p>
                            <p class="text-2xl font-bold text-green-600">${formatCurrency(data.asset_values.total_asset_value)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Average Value</p>
                            <p class="text-xl font-semibold text-blue-600">${formatCurrency(data.asset_values.avg_asset_value)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Assets</p>
                            <p class="text-xl font-semibold text-gray-600">${data.asset_values.total_assets}</p>
                        </div>
                    </div>
                </div>
                ` : ''}

                ${data.maintenance_costs ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Maintenance Costs</h3>
                    <div class="space-y-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Cost</p>
                            <p class="text-2xl font-bold text-orange-600">${formatCurrency(data.maintenance_costs.total_maintenance_cost)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Average Cost</p>
                            <p class="text-xl font-semibold text-yellow-600">${formatCurrency(data.maintenance_costs.avg_maintenance_cost)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Records</p>
                            <p class="text-xl font-semibold text-gray-600">${data.maintenance_costs.total_maintenance_records}</p>
                        </div>
                    </div>
                </div>
                ` : ''}

                ${data.procurement_costs ? `
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Procurement Costs</h3>
                    <div class="space-y-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Cost</p>
                            <p class="text-2xl font-bold text-purple-600">${formatCurrency(data.procurement_costs.total_procurement_cost)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Average Cost</p>
                            <p class="text-xl font-semibold text-indigo-600">${formatCurrency(data.procurement_costs.avg_procurement_cost)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total Requests</p>
                            <p class="text-xl font-semibold text-gray-600">${data.procurement_costs.total_procurement_requests}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Timeline (Last 12 Months)</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Procurement Costs</h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            ${data.timeline && data.timeline.procurement ? data.timeline.procurement.map(item => `
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-600">${item.month}</span>
                                    <span class="text-sm font-medium text-purple-600">${formatCurrency(item.procurement_cost)}</span>
                                </div>
                            `).join('') : '<p class="text-gray-500 text-sm">No procurement cost data available</p>'}
                        </div>
                    </div>
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Maintenance Costs</h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            ${data.timeline && data.timeline.maintenance ? data.timeline.maintenance.map(item => `
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-600">${item.month}</span>
                                    <span class="text-sm font-medium text-orange-600">${formatCurrency(item.maintenance_cost)}</span>
                                </div>
                            `).join('') : '<p class="text-gray-500 text-sm">No maintenance cost data available</p>'}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async exportReport() {
        try {
            showLoading();

            // Generate report title
            const reportTitle = `${this.currentReport.charAt(0).toUpperCase() + this.currentReport.slice(1)} Report`;
            const timestamp = new Date().toLocaleString();

            // Get current report data
            const data = await API.request(`reports.php?action=${this.currentReport}`);

            // Create exportable content
            let exportContent = `${reportTitle}\nGenerated on: ${timestamp}\n\n`;
            exportContent += this.generateExportContent(this.currentReport, data);

            // Create and download file
            const blob = new Blob([exportContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `${this.currentReport}_report_${new Date().toISOString().split('T')[0]}.txt`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showSuccess('Report exported successfully');
        } catch (error) {
            console.error('Export error:', error);
            showError('Failed to export report');
        } finally {
            hideLoading();
        }
    }

    generateExportContent(reportType, data) {
        let content = '';

        switch (reportType) {
            case 'overview':
                if (data.assets) {
                    content += 'ITEMS OVERVIEW\n';
                    content += `Total Items: ${data.assets.total_assets}\n`;
                    content += `Available: ${data.assets.available_assets}\n`;
                    content += `Assigned: ${data.assets.assigned_assets}\n`;
                    content += `Maintenance: ${data.assets.maintenance_assets}\n`;
                    content += `Damaged: ${data.assets.damaged_assets}\n\n`;
                }
                break;
            case 'assets':
                if (data.by_category) {
                    content += 'ITEMS BY CATEGORY\n';
                    data.by_category.forEach(item => {
                        content += `${item.category}: ${item.count}\n`;
                    });
                    content += '\n';
                }
                break;
            // Add more export formats for other report types...
        }

        return content;
    }

    refreshCurrentReport() {
        this.loadReport(this.currentReport);
    }
}

// Initialize reports manager when DOM is loaded
let reportsManager;
document.addEventListener('DOMContentLoaded', function() {
    reportsManager = new ReportsManager();
});
