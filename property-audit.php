<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Property Audit - Property Custodian Management";

ob_start();
?>

<!-- Property Audit Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Mobile menu toggle -->
    <button id="mobile-menu-toggle" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-2 rounded-md">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="w-full lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 sm:mb-8 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Property Audit</h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">Plan physical checks, track discrepancies, and keep stakeholders informed with up-to-date audit insights.</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button id="toggleAuditFormBtn" type="button" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition duration-200 w-full sm:w-auto">
                        <i class="fas fa-plus"></i><span>Start New Audit</span>
                    </button>
                </div>
            </div>

            <!-- Audit Interface -->
            <div class="mb-6">
                <!-- Audit Creation -->
                <div id="createAuditSection" class="bg-white border border-gray-200 rounded-xl shadow p-4 sm:p-6 hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Create Audit Plan</h3>
                            <p class="text-sm text-gray-500">Define scope, assign departments, and outline objectives before deploying inspectors.</p>
                        </div>
                        <button id="closeAuditFormBtn" type="button" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                            <i class="fas fa-times"></i><span>Close Form</span>
                        </button>
                    </div>

                    <form id="createAuditForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Audit Type</label>
                                <select name="audit_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select audit type</option>
                                    <option value="physical_inventory">Physical Inventory</option>
                                    <option value="financial_audit">Financial Audit</option>
                                    <option value="compliance_check">Compliance Check</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Audit Date</label>
                                <input type="date" name="audit_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" name="department" placeholder="e.g., IT Department, Administration" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Audit Objectives</label>
                            <textarea name="objectives" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe the objectives and focus areas for this audit"></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-2">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 w-full sm:w-auto">
                                Create Audit Plan
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Active Audits Table -->
            <div class="bg-white border border-gray-200 rounded-xl shadow overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Audit History</h3>
                    <p class="text-sm text-gray-500">Monitor timelines, assigned auditors, and outstanding tasks.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audit Code</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Department</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Auditor</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Progress</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="auditTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">No audits found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit Summary & Actions -->
            <div class="mt-6 flex flex-col lg:items-end">
                <div class="w-full lg:w-80 space-y-4">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Audit Status</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Active Audits</span>
                                <span class="font-semibold text-gray-900" id="activeAuditsCount">0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Completed</span>
                                <span class="font-semibold text-gray-900" id="completedAuditsCount">0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Pending Review</span>
                                <span class="font-semibold text-gray-900" id="pendingReviewCount">0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Discrepancies</span>
                                <span class="font-semibold text-red-600" id="discrepanciesCount">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <button id="scanQRBtn" class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md transition duration-200">
                                <i class="fas fa-qrcode mr-2"></i>Scan Item QR Code
                            </button>
                            <button id="markFoundBtn" class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md transition duration-200">
                                <i class="fas fa-check mr-2"></i>Mark Item as Found
                            </button>
                            <button id="reportDiscrepancyBtn" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition duration-200">
                                <i class="fas fa-exclamation mr-2"></i>Report Discrepancy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Audit Details Modal -->
<div id="auditDetailsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative mx-auto mt-16 mb-10 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-white border border-gray-200 rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Audit Details</h3>
                    <p class="text-sm text-gray-500">Drill into findings, team notes, and supporting documentation.</p>
                </div>
                <button onclick="propertyAuditManager.hideAuditDetails()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div id="auditDetailsContent" class="px-4 sm:px-6 py-4">
                <!-- Content populated by JavaScript -->
            </div>
            <div class="flex justify-end px-4 sm:px-6 py-4 border-t border-gray-200">
                <button onclick="propertyAuditManager.hideAuditDetails()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                    <i class="fas fa-times"></i><span>Close</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Audit Modal -->
<div id="editAuditModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative mx-auto mt-10 mb-10 w-11/12 md:w-3/4 lg:w-1/2">
        <div class="bg-white border border-gray-200 rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Edit Audit</h3>
                    <p class="text-sm text-gray-500">Update scheduling details, responsible teams, and progress notes.</p>
                </div>
                <button onclick="propertyAuditManager.hideEditAuditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form id="editAuditForm" class="px-4 sm:px-6 py-4 space-y-4">
                <input type="hidden" id="editAuditId" name="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Audit Code</label>
                        <input type="text" id="editAuditCode" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Audit Type</label>
                        <select id="editAuditType" name="audit_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="physical_inventory">Physical Inventory</option>
                            <option value="financial_audit">Financial Audit</option>
                            <option value="compliance_check">Compliance Check</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="editStartDate" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="editEndDate" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <input type="text" id="editDepartment" name="department" placeholder="e.g., IT Department, Administration" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="editStatus" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Items Audited</label>
                        <input type="number" id="editAssetsAudited" name="total_assets_audited" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discrepancies Found</label>
                        <input type="number" id="editDiscrepancies" name="discrepancies_found" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                    <textarea id="editSummary" name="summary" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Describe the audit objectives and findings"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" onclick="propertyAuditManager.hideEditAuditModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-times"></i><span>Cancel</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save"></i><span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Scan Item QR Code</h3>
                <button onclick="propertyAuditManager.hideQRScannerModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-4">
                <!-- Camera Scanner Section -->
                <div id="cameraSection" class="text-center">
                    <div id="qrScanner" class="relative mx-auto" style="max-width: 400px;">
                        <video id="qrVideo" class="w-full border rounded-lg" style="display: none;"></video>
                        <canvas id="qrCanvas" class="w-full border rounded-lg hidden"></canvas>
                        <div id="scannerOverlay" class="absolute inset-0 border-2 border-blue-500 rounded-lg hidden">
                            <div class="absolute inset-4 border border-white border-dashed rounded"></div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <button id="startScanBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-camera mr-2"></i>Start Camera
                        </button>
                        <button id="stopScanBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 hidden">
                            <i class="fas fa-stop mr-2"></i>Stop Camera
                        </button>
                    </div>
                </div>

                <!-- Manual Input Section -->
                <div class="border-t pt-4">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Or enter item code manually:</h4>
                    <div class="flex gap-2">
                        <input type="text" id="manualAssetCode" placeholder="Enter item code" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button onclick="propertyAuditManager.processAssetCode()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-search mr-2"></i>Find Item
                        </button>
                    </div>
                </div>

                <!-- Item Info Display -->
                <div id="assetInfoDisplay" class="hidden border-t pt-4">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Item Information:</h4>
                    <div id="assetDetails" class="bg-gray-50 p-4 rounded-lg">
                        <!-- Item details will be populated here -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-2">
                <button onclick="propertyAuditManager.hideQRScannerModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mark Item Found Modal -->
<div id="markFoundModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Mark Item as Found</h3>
                <button onclick="propertyAuditManager.hideMarkFoundModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="markFoundForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Active Audit</label>
                    <select id="foundAuditSelect" name="audit_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select an audit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Code</label>
                    <input type="text" id="foundAssetCode" name="asset_code" required placeholder="Enter or scan item code" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Location</label>
                    <input type="text" id="foundLocation" name="current_location" placeholder="Where was the item found?" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                    <select id="foundCondition" name="condition" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="foundNotes" name="notes" rows="3" placeholder="Any additional notes about the item" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="propertyAuditManager.hideMarkFoundModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Mark as Found
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Discrepancy Modal -->
<div id="discrepancyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Report Discrepancy</h3>
                <button onclick="propertyAuditManager.hideDiscrepancyModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="discrepancyForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Active Audit</label>
                    <select id="discrepancyAuditSelect" name="audit_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select an audit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Code (Optional)</label>
                    <input type="text" id="discrepancyAssetCode" name="asset_code" placeholder="Item code if applicable" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Finding Type</label>
                    <select id="discrepancyType" name="finding_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select finding type</option>
                        <option value="missing">Missing Item</option>
                        <option value="damaged">Damaged Item</option>
                        <option value="location_mismatch">Location Mismatch</option>
                        <option value="data_error">Data Error</option>
                        <option value="unauthorized_use">Unauthorized Use</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                    <select id="discrepancySeverity" name="severity" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="discrepancyDescription" name="description" required rows="4" placeholder="Describe the discrepancy in detail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proposed Corrective Action</label>
                    <textarea id="discrepancyAction" name="corrective_action" rows="3" placeholder="What action should be taken to resolve this?" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Resolution Date</label>
                    <input type="date" id="discrepancyTargetDate" name="target_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="propertyAuditManager.hideDiscrepancyModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Report Discrepancy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/api.js"></script>
<script src="js/property_audit.js"></script>
<script>
const auditFormSection = document.getElementById('createAuditSection');
const toggleAuditFormBtn = document.getElementById('toggleAuditFormBtn');
const closeAuditFormBtn = document.getElementById('closeAuditFormBtn');
const createAuditForm = document.getElementById('createAuditForm');
const auditFirstInput = document.querySelector('#createAuditForm select[name="audit_type"]');

const startButtonClasses = ['bg-green-600', 'hover:bg-green-700', 'text-white'];
const closeButtonClasses = ['bg-gray-200', 'hover:bg-gray-300', 'text-gray-800'];

function setToggleButton(isOpen) {
    if (!toggleAuditFormBtn) return;
    toggleAuditFormBtn.classList.remove(...(isOpen ? startButtonClasses : closeButtonClasses));
    toggleAuditFormBtn.classList.add(...(isOpen ? closeButtonClasses : startButtonClasses));
    toggleAuditFormBtn.innerHTML = isOpen
        ? '<i class="fas fa-times"></i><span>Close Audit Form</span>'
        : '<i class="fas fa-plus"></i><span>Start New Audit</span>';
}

function showCreateAuditForm() {
    if (!auditFormSection) return;
    auditFormSection.classList.remove('hidden');
    setToggleButton(true);
    requestAnimationFrame(() => {
        auditFormSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            auditFirstInput?.focus();
            auditFormSection.classList.add('ring-2', 'ring-blue-500');
            setTimeout(() => auditFormSection.classList.remove('ring-2', 'ring-blue-500'), 1200);
        }, 350);
    });
}

function hideCreateAuditForm() {
    if (!auditFormSection) return;
    auditFormSection.classList.add('hidden');
    createAuditForm?.reset();
    setToggleButton(false);
}

function toggleCreateAuditForm() {
    if (!auditFormSection) return;
    if (auditFormSection.classList.contains('hidden')) {
        showCreateAuditForm();
    } else {
        hideCreateAuditForm();
    }
}

toggleAuditFormBtn?.addEventListener('click', toggleCreateAuditForm);
closeAuditFormBtn?.addEventListener('click', hideCreateAuditForm);

// Expose for legacy calls if needed
window.toggleCreateAuditForm = toggleCreateAuditForm;
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
