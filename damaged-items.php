<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Damaged Items - Property Custodian Management";

ob_start();
?>

<!-- Damaged Items Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6 lg:mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Damaged Items Management</h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">Capture damage reports quickly, monitor severity, and track repair status across your asset base.</p>
                </div>
                <div class="flex items-center gap-3 sm:gap-4">
                    <button id="reportDamageBtn" type="button" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 sm:px-5 py-2 rounded-lg transition duration-200 shadow-sm text-sm sm:text-base">
                        <i class="fas fa-plus"></i><span>Report Damage</span>
                    </button>
                </div>
            </div>

            <!-- Damage Report Form -->
            <div id="damage-report-form" class="hidden bg-white rounded-xl shadow p-4 sm:p-6 mb-6 border border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Report Damaged Item</h3>
                        <p class="text-sm text-gray-500">Document what happened, attach evidence, and route the report for resolution.</p>
                    </div>
                    <button type="button" id="cancel-btn" class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200 self-start sm:self-auto">
                        <i class="fas fa-times"></i><span>Close Form</span>
                    </button>
                </div>

                <form id="damage-form" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Code *</label>
                                <input type="text" id="asset_code" name="asset_code" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter asset code or scan QR" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Name</label>
                                <input type="text" id="asset_name" name="asset_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Asset name will appear here" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Damage Type *</label>
                                <select id="damage_type" name="damage_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select damage type</option>
                                    <option value="physical">Physical Damage</option>
                                    <option value="electrical">Electrical Failure</option>
                                    <option value="software">Software Issue</option>
                                    <option value="wear">Normal Wear & Tear</option>
                                    <option value="accident">Accident</option>
                                    <option value="vandalism">Vandalism</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Severity Level *</label>
                                <select id="severity_level" name="severity_level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select severity level</option>
                                    <option value="minor">Minor - Still functional</option>
                                    <option value="moderate">Moderate - Limited functionality</option>
                                    <option value="major">Major - Not functional</option>
                                    <option value="total">Total Loss - Cannot be repaired</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Damage *</label>
                                <input type="date" id="damage_date" name="damage_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reported By *</label>
                                <input type="text" id="reported_by" name="reported_by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Name of person reporting" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Location</label>
                                <input type="text" id="current_location" name="current_location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Where is the item currently located">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Repair Cost</label>
                                <input type="number" id="estimated_repair_cost" name="estimated_repair_cost" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Damage Description</label>
                        <textarea id="damage_description" name="damage_description" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Provide detailed description of the damage"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Damage Photos</label>
                        <div class="mt-2 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <div class="space-y-2">
                                <i class="fas fa-camera text-4xl text-gray-400"></i>
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-1 text-sm text-gray-600">
                                    <label class="relative cursor-pointer font-medium text-blue-600 hover:text-blue-500">
                                        <span>Upload photos</span>
                                        <input type="file" id="damage_photos" name="damage_photos" class="sr-only" multiple accept="image/*">
                                    </label>
                                    <span class="hidden sm:inline">or</span>
                                    <p>drag and drop files</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4">
                        <button type="submit" id="submit-btn" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 shadow">
                            <i class="fas fa-paper-plane"></i><span>Report Damage</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Damaged Items Table -->
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Damaged Items List</h3>
                        <p class="text-sm text-gray-500">Stay aware of outstanding issues, response timelines, and financial impact.</p>
                    </div>
                    <button id="refreshDamagedItems" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition duration-200">
                        <i class="fas fa-rotate"></i><span>Refresh</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Code</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Asset Name</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Damage Type</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Date Reported</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Reported By</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Repair Cost</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="damagedItemsTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                            <tr>
                                <td colspan="9" class="px-3 sm:px-6 py-6 text-center text-gray-500">No damaged items reported</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 sm:gap-6 mt-6">

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Total Damaged</p>
                            <p id="totalDamaged" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Awaiting resolution</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                            <i class="fas fa-exclamation-triangle text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Under Repair</p>
                            <p id="underRepair" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Currently being serviced</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <i class="fas fa-wrench text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Repaired</p>
                            <p id="repairedItems" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Successfully restored assets</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-check-circle text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Unusable</p>
                            <p id="writeOffs" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Items retired from service</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-500 text-white shadow-lg shadow-slate-500/30">
                            <i class="fas fa-trash text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-purple-600">Total Repair Cost</p>
                            <p id="totalRepairCost" class="mt-2 text-3xl font-bold text-slate-900">₱0.00</p>
                            <p class="mt-1 text-xs text-slate-500">Approved maintenance spend</p>
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

<?php include 'components/detail_modal.php'; ?>

<script src="js/api.js?v=<?php echo time(); ?>"></script>
<script src="js/detail_handlers.js?v=<?php echo time(); ?>"></script>
<script src="js/damaged_items.js?v=<?php echo time(); ?>"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>