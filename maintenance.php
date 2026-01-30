<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Preventive Maintenance - Property Custodian Management";

ob_start();
?>

<!-- Preventive Maintenance Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6 lg:mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Preventive Maintenance</h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">Stay ahead of equipment downtime with scheduled inspections, technician assignments, and proactive upkeep across your facilities.</p>
                </div>
                <div class="flex items-center gap-3 sm:gap-4">
                    <button id="schedule-btn" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 sm:px-5 py-2 rounded-lg transition duration-200 shadow-sm text-sm sm:text-base">
                        <i class="fas fa-plus"></i><span>Schedule Maintenance</span>
                    </button>
                </div>
            </div>

            <!-- Maintenance Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Scheduled Tasks</p>
                            <p id="scheduledTasks" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Upcoming maintenance events</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                            <i class="fas fa-calendar-check text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pending Tasks</p>
                            <p id="pendingTasks" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Awaiting technician updates</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <i class="fas fa-clock text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Critical Issues</p>
                            <p id="criticalIssues" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Requires immediate attention</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                            <i class="fas fa-exclamation-triangle text-lg"></i>
                        </span>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Completed</p>
                            <p id="completedTasks" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="mt-1 text-xs text-slate-500">Closed service tickets</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-check-circle text-lg"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Maintenance Schedule Form -->
            <div id="maintenance-form-section" class="hidden bg-white rounded-xl shadow p-4 sm:p-6 mb-6 border border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Schedule New Maintenance</h3>
                        <p class="text-sm text-gray-500">Capture task details, assign a technician, and set reminders to keep assets healthy.</p>
                    </div>
                    <button type="button" id="cancel-btn" class="inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200 self-start sm:self-auto">
                        <i class="fas fa-times"></i><span>Close Form</span>
                    </button>
                </div>

                <form id="maintenance-form">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Asset</label>
                                <select id="asset_id" name="asset_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Choose asset for maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Type</label>
                                <select id="maintenance_type" name="maintenance_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select maintenance type</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date</label>
                                <input type="date" id="scheduled_date" name="scheduled_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select id="priority" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Technician</label>
                                <select id="assigned_to" name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select technician</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Duration (hours)</label>
                                <input type="number" id="estimated_duration" name="estimated_duration" min="0.5" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="2.0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Cost</label>
                                <input type="number" id="estimated_cost" name="estimated_cost" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Recurring Schedule</label>
                                <select id="recurring_schedule" name="recurring_schedule" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">One-time only</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Description</label>
                        <textarea id="description" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe the maintenance tasks to be performed" required></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 shadow">
                            <i class="fas fa-paper-plane"></i><span>Schedule Maintenance</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Maintenance Schedule Table -->
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Maintenance Schedule</h3>
                        <p class="text-sm text-gray-500">Monitor upkeep progress, assignments, and priority levels in one view.</p>
                    </div>
                    <button id="refreshMaintenance" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition duration-200">
                        <i class="fas fa-rotate"></i><span>Refresh</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Maintenance Type</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Assigned To</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Priority</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="maintenance-table-body" class="bg-white divide-y divide-gray-200 text-sm">
                            <tr id="no-maintenance-row">
                                <td colspan="7" class="px-3 sm:px-6 py-6 text-center text-gray-500">No scheduled maintenance found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit Maintenance Modal -->
<div id="edit-maintenance-modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto mt-16 mb-10 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-white rounded-xl shadow-xl border border-gray-200">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Edit Maintenance Task</h3>
                    <p class="text-sm text-gray-500">Update assignments, schedule details, and notes before saving.</p>
                </div>
                <button id="close-edit-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="edit-maintenance-form" class="px-4 sm:px-6 py-4">
                <input type="hidden" id="edit-maintenance-id" name="id">

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Asset</label>
                            <select id="edit-asset-id" name="asset_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Choose asset for maintenance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Type</label>
                            <select id="edit-maintenance-type" name="maintenance_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select maintenance type</option>
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date</label>
                            <input type="date" id="edit-scheduled-date" name="scheduled_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select id="edit-priority" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Technician</label>
                            <select id="edit-assigned-to" name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select technician</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Duration (hours)</label>
                            <input type="number" id="edit-estimated-duration" name="estimated_duration" min="0.5" step="0.5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="2.0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Cost</label>
                            <input type="number" id="edit-estimated-cost" name="estimated_cost" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Actual Cost</label>
                            <input type="number" id="edit-actual-cost" name="actual_cost" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Description</label>
                    <textarea id="edit-description" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe the maintenance tasks to be performed" required></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="edit-notes" name="notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Additional notes (optional)"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6">
                    <button type="button" id="cancel-edit-btn" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-times"></i><span>Cancel</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 shadow">
                        <i class="fas fa-save"></i><span>Update Maintenance</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'components/detail_modal.php'; ?>

<script src="js/api.js"></script>
<script src="js/detail_handlers.js"></script>
<script src="js/maintenance.js"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>