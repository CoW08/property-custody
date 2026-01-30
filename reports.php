<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Reports & Analytics - Property Custodian Management";

ob_start();
?>

<!-- Reports Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden lg:ml-64">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 sm:mb-8 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Reports & Analytics</h1>
                    <p class="text-gray-600 mt-1">Comprehensive insights into your property management system.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <button id="refreshBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center shadow-sm">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                    <button id="exportBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center shadow-sm">
                        <i class="fas fa-download mr-2"></i>Export Report
                    </button>
                </div>
            </div>

            <!-- Date Filter -->
            <div id="dateFilterContainer" class="mb-6 sm:mb-8"></div>

            <!-- Report Type Selector -->
            <div class="mb-6 sm:mb-8">
                <div class="flex flex-wrap gap-2 sm:gap-3 p-3 bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
                    <button class="report-btn bg-blue-600 text-white px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0 shadow-sm" data-report="overview">
                        <i class="fas fa-chart-pie mr-2"></i>Overview
                    </button>
                    <button class="report-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0" data-report="assets">
                        <i class="fas fa-boxes mr-2"></i>Items
                    </button>
                    <button class="report-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0" data-report="maintenance">
                        <i class="fas fa-tools mr-2"></i>Maintenance
                    </button>
                    <button class="report-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0" data-report="procurement">
                        <i class="fas fa-shopping-cart mr-2"></i>Procurement
                    </button>
                    <button class="report-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0" data-report="audit">
                        <i class="fas fa-search mr-2"></i>Audits
                    </button>
                    <button class="report-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full font-medium whitespace-nowrap flex-shrink-0" data-report="financial">
                        <i class="fas fa-chart-line mr-2"></i>Financial
                    </button>
                </div>
            </div>

            <!-- Report Content Area -->
            <div id="reportContent" class="space-y-6">
                <!-- Content will be dynamically loaded here -->
                <div class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                        <p class="text-gray-600">Loading report data...</p>
                    </div>
                </div>
            </div>

            <!-- Responsive Helper -->
            <div class="block sm:hidden mt-8 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3 flex-shrink-0"></i>
                    <div>
                        <h3 class="text-sm font-medium text-blue-900">Mobile View</h3>
                        <p class="text-sm text-blue-700 mt-1">Scroll horizontally on charts and tables for better viewing experience.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 modal-overlay z-40 lg:hidden hidden"></div>

<!-- Loading Indicator -->
<div id="loadingIndicator" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
    <div class="modal-panel bg-white rounded-lg p-6 flex items-center space-x-4 mx-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        <span class="text-gray-700">Processing report...</span>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
    <div class="modal-panel bg-white rounded-lg p-6 max-w-md mx-4 w-full">
        <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
            <h3 class="text-lg font-semibold text-gray-900">Error</h3>
        </div>
        <p id="errorMessage" class="text-gray-600 mb-4"></p>
        <div class="flex justify-end">
            <button onclick="closeModal('errorModal')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                Close
            </button>
        </div>
    </div>
</div>

<script src="js/api.js"></script>
<script src="js/date_filter.js"></script>
<script src="js/charts.js"></script>
<script src="js/reports.js"></script>

<style>
    /* Custom responsive styles */
    @media (max-width: 1024px) {
        .ml-64 {
            margin-left: 0;
        }
    }

    @media (max-width: 640px) {
        .report-btn {
            font-size: 14px;
            padding: 8px 12px;
        }

        .report-btn i {
            display: none;
        }
    }

    /* Smooth transitions */
    /* Loading animation */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .5;
        }
    }

    .pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Scroll bars for mobile */
    .overflow-x-auto::-webkit-scrollbar {
        height: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 2px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Chart container responsiveness */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    @media (max-width: 640px) {
        .chart-container {
            height: 200px;
        }
    }
</style>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>