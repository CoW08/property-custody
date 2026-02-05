<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Procurement - Property Custodian Management";

ob_start();
?>

<!-- Procurement Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 sm:mb-8 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Procurement Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Track, approve, and receive procurement requests.</p>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <button onclick="openNewRequestModal()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg transition duration-200 text-sm sm:text-base shadow">
                        <i class="fas fa-plus mr-1 sm:mr-2"></i>New Request
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-blue-600">Total Requests</p>
                            <p id="totalRequests" class="mt-2 text-3xl font-bold text-slate-900 truncate">0</p>
                            <p class="mt-1 text-xs text-slate-500">Submitted procurement files</p>
                        </div>
                        <span class="inline-flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                            <i class="fas fa-file-alt text-lg sm:text-xl"></i>
                        </span>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-amber-600">Pending</p>
                            <p id="pendingRequests" class="mt-2 text-3xl font-bold text-slate-900 truncate">0</p>
                            <p class="mt-1 text-xs text-slate-500">Awaiting approval workflow</p>
                        </div>
                        <span class="inline-flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <i class="fas fa-clock text-lg sm:text-xl"></i>
                        </span>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-emerald-600">Approved</p>
                            <p id="approvedRequests" class="mt-2 text-3xl font-bold text-slate-900 truncate">0</p>
                            <p class="mt-1 text-xs text-slate-500">Cleared for ordering</p>
                        </div>
                        <span class="inline-flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-check text-lg sm:text-xl"></i>
                        </span>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-purple-600">Total Cost</p>
                            <p id="totalCost" class="mt-2 text-3xl font-bold text-slate-900 truncate">₱0</p>
                            <p class="mt-1 text-xs text-slate-500">Approved spending to date</p>
                        </div>
                        <span class="inline-flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-purple-500 text-white shadow-lg shadow-purple-500/30">
                            <i class="fas fa-peso-sign text-lg sm:text-xl"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-6 border border-gray-200">
                <div class="space-y-4 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-4 sm:items-center sm:justify-between">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 flex-1">
                        <input type="text" id="searchInput" placeholder="Search requests..."
                               class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">

                        <select id="statusFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="ordered">Ordered</option>
                            <option value="received">Received</option>
                        </select>

                        <select id="priorityFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">All Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>

                        <select id="typeFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">All Types</option>
                            <option value="asset">Item</option>
                            <option value="supply">Supply</option>
                            <option value="service">Service</option>
                        </select>
                    </div>

                    <button id="refreshBtn" class="w-full sm:w-auto bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200 text-sm sm:text-base">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Procurement Requests Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 card-head">
                    <h3 class="text-lg font-semibold text-gray-900">Procurement Requests</h3>
                </div>

                <!-- Mobile Card View (hidden on larger screens) -->
                <div class="block lg:hidden" id="mobileRequestsList">
                    <!-- Mobile cards will be populated here -->
                </div>

                <!-- Desktop Table View (hidden on mobile) -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requestor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="procurementTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-sm text-gray-500 text-center sm:text-left" id="paginationInfo">
                            Showing 0 to 0 of 0 results
                        </div>
                        <div class="flex items-center justify-center space-x-2" id="paginationControls">
                            <!-- Pagination controls will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- New Request Modal -->
<div id="newRequestModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto z-50 flex items-start justify-center p-4 sm:p-8">
    <div class="modal-panel relative w-full sm:w-11/12 max-w-4xl bg-white p-4 sm:p-6 min-h-screen sm:min-h-0">
        <div class="mt-1">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">New Procurement Request</h3>
                <button class="modal-close text-gray-400 hover:text-gray-600" onclick="closeRequestModal()">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <form id="newRequestForm" class="mt-4 sm:mt-6">
                <!-- Auto-Populated Requester Information (Read-Only) -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-semibold text-blue-900 mb-3">
                        <i class="fas fa-user-circle mr-2"></i>Requester Information (Auto-Populated)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                            <input type="text" id="requesterName" readonly
                                   class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 cursor-not-allowed text-sm"
                                   value="<?php echo htmlspecialchars(getCurrentUser()['full_name'] ?? 'N/A'); ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
                            <input type="text" id="requesterDepartment" readonly
                                   class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 cursor-not-allowed text-sm"
                                   value="<?php echo htmlspecialchars(getCurrentUser()['department'] ?? 'N/A'); ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
                            <input type="text" id="requesterRole" readonly
                                   class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 cursor-not-allowed text-sm"
                                   value="<?php echo htmlspecialchars(ucfirst(getCurrentUser()['role'] ?? 'N/A')); ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="text" id="requesterEmail" readonly
                                   class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 cursor-not-allowed text-sm"
                                   value="<?php echo htmlspecialchars(getCurrentUser()['email'] ?? 'N/A'); ?>">
                        </div>
                    </div>
                    <p class="text-xs text-blue-700 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        This information is automatically retrieved from your user account and cannot be edited.
                    </p>
                </div>

                <!-- Hidden fields for submission -->
                <input type="hidden" id="requestorId" name="requestor_id" value="<?php echo getCurrentUser()['id'] ?? ''; ?>">
                <input type="hidden" id="requestDepartment" name="department" value="<?php echo htmlspecialchars(getCurrentUser()['department'] ?? ''); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Request Type *</label>
                        <select id="requestType" name="request_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="asset">Item</option>
                            <option value="supply">Supply</option>
                            <option value="service">Service</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Request Date *</label>
                        <input type="date" id="requestDate" name="request_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Required Date</label>
                        <input type="date" id="requiredDate" name="required_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select id="requestPriority" name="priority"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Justification *</label>
                    <textarea id="requestJustification" name="justification" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Explain why this procurement is needed..."></textarea>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="requestNotes" name="notes" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Additional notes or comments..."></textarea>
                </div>

                <!-- Vendor Contact Section -->
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-6">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">Vendor Contact</h4>
                            <p class="text-xs text-gray-500">Choose from the preset directory to auto-fill contact details or switch to custom entry.</p>
                        </div>
                        <div class="w-full sm:w-64">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Contact List</label>
                            <select id="vendorSelector"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">Select vendor</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" id="vendorIdField" disabled>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Category</label>
                            <input type="text" id="vendorCategory"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                   placeholder="Clinic / Medical Supplies">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Name *</label>
                            <input type="text" name="vendor_name" id="vendorName" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                   placeholder="Vendor company name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Email</label>
                            <input type="email" name="vendor_email" id="vendorEmail"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                   placeholder="contact@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Phone</label>
                            <input type="text" name="vendor_phone" id="vendorPhone"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                   placeholder="+63 xxx-xxx-xxxx">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Address</label>
                            <textarea name="vendor_address" id="vendorAddress" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                      placeholder="Building / Street, City, Province"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium text-gray-900">Request Items</h4>
                        <button type="button" id="addItemBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Item
                        </button>
                    </div>

                    <div id="itemsContainer">
                        <!-- Items will be added here dynamically -->
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeRequestModal()"
                            class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition duration-200 text-sm sm:text-base">
                        Cancel
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 text-sm sm:text-base">
                        <i class="fas fa-save mr-2"></i>Save Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div id="viewRequestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 border w-full sm:w-11/12 max-w-4xl shadow-lg rounded-md bg-white min-h-screen sm:min-h-0">
        <div class="mt-3">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Procurement Request Details</h3>
                <button class="modal-close text-gray-400 hover:text-gray-600" onclick="closeModal('viewRequestModal')">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <div id="requestDetailsContent" class="overflow-auto">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="js/api.js"></script>
<script src="js/procurement.js"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>