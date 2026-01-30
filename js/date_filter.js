// Date Range Filter Component
class DateRangeFilter {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container with id '${containerId}' not found`);
            return;
        }

        this.options = {
            onApply: null,
            showQuickFilters: true,
            showCustomRange: true,
            ...options
        };

        this.dateFrom = null;
        this.dateTo = null;
        this.activeFilter = null;

        this.init();
    }

    init() {
        this.render();
        this.setupEventListeners();
        
        // Set default to "This Month"
        this.applyQuickFilter('this_month');
    }

    render() {
        const html = `
            <div class="date-filter-panel bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Quick Filters -->
                    ${this.options.showQuickFilters ? `
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filters</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-2">
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="today">
                                Today
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="this_week">
                                This Week
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-blue-500 text-white rounded-lg transition-colors" data-filter="this_month">
                                This Month
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="this_quarter">
                                This Quarter
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="this_year">
                                This Year
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="last_30_days">
                                Last 30 Days
                            </button>
                            <button class="quick-filter-btn px-3 py-2 text-sm bg-gray-100 hover:bg-blue-500 hover:text-white rounded-lg transition-colors" data-filter="last_90_days">
                                Last 90 Days
                            </button>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Custom Date Range -->
                    ${this.options.showCustomRange ? `
                    <div class="flex-shrink-0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custom Range</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-600 whitespace-nowrap">From:</label>
                                <input type="date" id="dateFrom" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-600 whitespace-nowrap">To:</label>
                                <input type="date" id="dateTo" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div class="flex gap-2">
                                <button id="applyCustomRange" class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors whitespace-nowrap">
                                    <i class="fas fa-check mr-1"></i>Apply
                                </button>
                                <button id="resetFilter" class="px-4 py-2 text-sm bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-colors">
                                    <i class="fas fa-redo mr-1"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>

                <!-- Active Filter Display -->
                <div id="activeFilterDisplay" class="mt-4 pt-4 border-t border-gray-200 hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-blue-600"></i>
                            <span class="text-sm text-gray-700">
                                Active Filter: <span id="activeFilterText" class="font-medium text-blue-600"></span>
                            </span>
                        </div>
                        <button onclick="dateFilter.clearFilter()" class="text-sm text-red-600 hover:text-red-700">
                            <i class="fas fa-times mr-1"></i>Clear Filter
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    setupEventListeners() {
        // Quick filter buttons
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filter = e.target.closest('.quick-filter-btn').getAttribute('data-filter');
                this.applyQuickFilter(filter);
            });
        });

        // Custom range apply
        const applyBtn = document.getElementById('applyCustomRange');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                this.applyCustomRange();
            });
        }

        // Reset button
        const resetBtn = document.getElementById('resetFilter');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetFilter();
            });
        }

        // Date inputs - apply on change
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        
        if (dateFromInput) {
            dateFromInput.addEventListener('change', () => {
                if (dateToInput.value) {
                    this.applyCustomRange();
                }
            });
        }

        if (dateToInput) {
            dateToInput.addEventListener('change', () => {
                if (dateFromInput.value) {
                    this.applyCustomRange();
                }
            });
        }
    }

    applyQuickFilter(filter) {
        const today = new Date();
        let dateFrom, dateTo;

        switch (filter) {
            case 'today':
                dateFrom = dateTo = this.formatDate(today);
                break;

            case 'this_week':
                dateFrom = this.formatDate(this.getStartOfWeek(today));
                dateTo = this.formatDate(today);
                break;

            case 'this_month':
                dateFrom = this.formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                dateTo = this.formatDate(today);
                break;

            case 'this_quarter':
                const quarter = Math.floor(today.getMonth() / 3);
                dateFrom = this.formatDate(new Date(today.getFullYear(), quarter * 3, 1));
                dateTo = this.formatDate(today);
                break;

            case 'this_year':
                dateFrom = this.formatDate(new Date(today.getFullYear(), 0, 1));
                dateTo = this.formatDate(today);
                break;

            case 'last_30_days':
                const last30 = new Date(today);
                last30.setDate(last30.getDate() - 30);
                dateFrom = this.formatDate(last30);
                dateTo = this.formatDate(today);
                break;

            case 'last_90_days':
                const last90 = new Date(today);
                last90.setDate(last90.getDate() - 90);
                dateFrom = this.formatDate(last90);
                dateTo = this.formatDate(today);
                break;

            default:
                return;
        }

        this.dateFrom = dateFrom;
        this.dateTo = dateTo;
        this.activeFilter = filter;

        // Update UI
        this.updateActiveButton(filter);
        this.updateDateInputs(dateFrom, dateTo);
        this.showActiveFilter(filter);

        // Trigger callback
        if (this.options.onApply) {
            this.options.onApply(dateFrom, dateTo, filter);
        }
    }

    applyCustomRange() {
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');

        if (!dateFromInput || !dateToInput) return;

        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;

        if (!dateFrom || !dateTo) {
            this.showNotification('Please select both start and end dates', 'warning');
            return;
        }

        if (dateFrom > dateTo) {
            this.showNotification('Start date must be before end date', 'error');
            return;
        }

        this.dateFrom = dateFrom;
        this.dateTo = dateTo;
        this.activeFilter = 'custom';

        // Update UI
        this.updateActiveButton(null); // Clear all quick filter buttons
        this.showActiveFilter('custom', `${this.formatDisplayDate(dateFrom)} - ${this.formatDisplayDate(dateTo)}`);

        // Trigger callback
        if (this.options.onApply) {
            this.options.onApply(dateFrom, dateTo, 'custom');
        }
    }

    resetFilter() {
        // Reset to default (This Month)
        this.applyQuickFilter('this_month');
    }

    clearFilter() {
        this.dateFrom = null;
        this.dateTo = null;
        this.activeFilter = null;

        // Clear UI
        this.updateActiveButton(null);
        this.hideActiveFilter();
        
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        if (dateFromInput) dateFromInput.value = '';
        if (dateToInput) dateToInput.value = '';

        // Trigger callback with null values
        if (this.options.onApply) {
            this.options.onApply(null, null, null);
        }
    }

    updateActiveButton(filter) {
        // Remove active class from all buttons
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        });

        // Add active class to selected button
        if (filter) {
            const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
                activeBtn.classList.add('bg-blue-500', 'text-white');
            }
        }
    }

    updateDateInputs(dateFrom, dateTo) {
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');

        if (dateFromInput) dateFromInput.value = dateFrom;
        if (dateToInput) dateToInput.value = dateTo;
    }

    showActiveFilter(filter, customText = null) {
        const display = document.getElementById('activeFilterDisplay');
        const text = document.getElementById('activeFilterText');

        if (!display || !text) return;

        if (customText) {
            text.textContent = customText;
        } else {
            const filterNames = {
                'today': 'Today',
                'this_week': 'This Week',
                'this_month': 'This Month',
                'this_quarter': 'This Quarter',
                'this_year': 'This Year',
                'last_30_days': 'Last 30 Days',
                'last_90_days': 'Last 90 Days'
            };
            text.textContent = `${filterNames[filter]} (${this.formatDisplayDate(this.dateFrom)} - ${this.formatDisplayDate(this.dateTo)})`;
        }

        display.classList.remove('hidden');
    }

    hideActiveFilter() {
        const display = document.getElementById('activeFilterDisplay');
        if (display) {
            display.classList.add('hidden');
        }
    }

    getStartOfWeek(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
        return new Date(d.setDate(diff));
    }

    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    formatDisplayDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

    showNotification(message, type = 'info') {
        // Use global notification function if available
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            // Fallback to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    // Getter methods
    getDateFrom() {
        return this.dateFrom;
    }

    getDateTo() {
        return this.dateTo;
    }

    getActiveFilter() {
        return this.activeFilter;
    }

    getDateRange() {
        return {
            dateFrom: this.dateFrom,
            dateTo: this.dateTo,
            filter: this.activeFilter
        };
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DateRangeFilter;
}
