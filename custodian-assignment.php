<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Custodian Assignment - Property Custodian Management";

$currentUser = getCurrentUser();
$isCustodian = in_array($currentUser['role'], ['admin', 'custodian']);

ob_start();
?>

<!-- Custodian Assignment Content -->
<div class="min-h-screen flex" data-user-role="<?php echo htmlspecialchars($currentUser['role']); ?>">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 flex-1 overflow-x-hidden">
        <!-- Mobile Header -->
        <div class="lg:hidden bg-white shadow-sm border-b border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <button onclick="toggleMobileMenu()" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-lg font-semibold text-gray-900">Custodian Assignment</h1>
                <div></div> <!-- Spacer for centering -->
            </div>
        </div>

        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6 lg:mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        <?php echo $isCustodian ? 'Custodian Assignment & Transfer' : 'Request Item Assignment'; ?>
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">
                        <?php echo $isCustodian
                            ? 'Coordinate custodians, review item requests, and keep your assignments up to date with quick actions and clear insights.'
                            : 'Submit requests, track their status, and stay in the loop as items are approved or returned by the custodian team.'; ?>
                    </p>
                </div>
                <div class="flex items-center gap-3 sm:gap-4">
                    <?php if ($isCustodian): ?>
                        <button id="refreshBtn" type="button" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg shadow-sm transition duration-200" data-label="Refresh">
                            <i class="fas fa-rotate"></i><span>Refresh</span>
                        </button>
                    <?php else: ?>
                        <button onclick="openRequestModal()" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition duration-200 w-full sm:w-auto">
                            <i class="fas fa-plus"></i><span>New Request</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isCustodian): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6 mb-6">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pending Requests</p>
                            <p id="pendingCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="text-xs text-slate-500">Awaiting custodian review</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <i class="fas fa-clock text-lg"></i>
                        </span>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/20">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-white to-slate-50"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Active Assignments</p>
                            <p id="activeCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="text-xs text-slate-500">Currently issued items</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-check-circle text-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isCustodian): ?>
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Assignment Requests</h3>
                        <p class="text-sm text-gray-500">Review new item requests and take action in a single click.</p>
                    </div>
                    <div id="bulkActions" class="hidden items-center gap-2">
                        <span id="selectedCount" class="text-sm text-gray-600"></span>
                        <button onclick="bulkDelete()" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md transition duration-200">
                            <i class="fas fa-trash"></i><span>Delete Selected</span>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded border-gray-300">
                                </th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Department</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Purpose</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Date</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                            <tr><td colspan="8" class="px-4 sm:px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">My Assignment Requests</h3>
                    <p class="text-sm text-gray-500">Track progress and download approved assignment documents.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Request Date</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Reviewed By</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="myRequestsTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                            <tr><td colspan="6" class="px-4 sm:px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Assignment detail drawer -->
<div id="assignmentDetailDrawer" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60" onclick="closeAssignmentDrawer()"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-4xl bg-white shadow-2xl overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4 sticky top-0 bg-white">
            <div>
                <p class="text-xs uppercase tracking-wide text-blue-600 font-semibold">Assignment Detail</p>
                <h2 id="drawerAssetTitle" class="text-2xl font-bold text-gray-900">Asset</h2>
                <p id="drawerAssignmentMeta" class="text-sm text-gray-500">Loading details...</p>
            </div>
            <button class="text-gray-500 hover:text-gray-700" onclick="closeAssignmentDrawer()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-100 p-4 bg-gradient-to-br from-slate-50 to-white">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Custodian</p>
                    <p id="drawerCustodian" class="mt-1 text-lg font-semibold text-gray-900">-</p>
                    <p id="drawerCustodianMeta" class="text-sm text-gray-500">-</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4 bg-gradient-to-br from-slate-50 to-white">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Asset</p>
                    <p id="drawerAssetCode" class="mt-1 text-lg font-semibold text-gray-900">-</p>
                    <p id="drawerAssetMeta" class="text-sm text-gray-500">-</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs uppercase text-gray-500">Status</p>
                    <p id="drawerStatus" class="mt-1 font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Assigned</p>
                    <p id="drawerAssignedDate" class="mt-1 font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Expected Return</p>
                    <p id="drawerExpectedReturn" class="mt-1 font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Maintenance</p>
                    <p id="drawerMaintenanceStatus" class="mt-1 font-semibold text-gray-900">-</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-4">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <button id="drawerIssueBtn" class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm" onclick="openIssueModal()">
                        <i class="fas fa-pen"></i> Issue Asset
                    </button>
                    <button class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm" onclick="openTransferModal()">
                        <i class="fas fa-random"></i> Initiate Transfer
                    </button>
                    <button class="inline-flex items-center gap-2 px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm" onclick="openMaintenanceModal()">
                        <i class="fas fa-tools"></i> Link Maintenance
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase text-gray-500">Approved by</p>
                        <p id="drawerApprovedMeta" class="mt-1 font-semibold text-gray-900">-</p>
                        <p id="drawerApprovedAt" class="text-xs text-gray-500">-</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-500">Issued by</p>
                        <p id="drawerIssuedMeta" class="mt-1 font-semibold text-gray-900">Awaiting issuance</p>
                        <p id="drawerIssuedAt" class="text-xs text-gray-500">-</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Maintenance Links</h4>
                        <span id="maintenanceCount" class="text-xs uppercase text-gray-400">0 linked</span>
                    </div>
                    <div id="maintenanceList" class="space-y-3 text-sm text-gray-700">
                        <p class="text-gray-500">No maintenance schedules linked.</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Transfers</h4>
                        <span id="transferCount" class="text-xs uppercase text-gray-400">0 records</span>
                    </div>
                    <div id="transferList" class="space-y-3 text-sm text-gray-700">
                        <p class="text-gray-500">No transfer records yet.</p>
                    </div>
                    <div id="transferActionsContainer" class="mt-4"></div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 p-4">
                <h4 class="font-semibold text-gray-900 mb-4">Accountability Timeline</h4>
                <ol id="assignmentHistoryList" class="space-y-3 text-sm text-gray-700">
                    <li class="text-gray-500">Loading timeline...</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Issue assignment modal -->
<div id="issueModal" class="hidden fixed inset-0 z-50 bg-gray-900/60">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase text-gray-500">Issuance</p>
                    <h3 class="text-lg font-semibold text-gray-900">Record Issuance</h3>
                </div>
                <button onclick="hideModal('issueModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="issueAssignmentForm" class="p-6 space-y-4">
                <input type="hidden" id="issueAssignmentId">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2">
                    <i class="fas fa-check-circle"></i><span>Mark as Issued</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Transfer modal -->
<div id="transferModal" class="hidden fixed inset-0 z-50 bg-gray-900/60">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase text-gray-500">Custody Transfer</p>
                    <h3 class="text-lg font-semibold text-gray-900">Initiate Transfer</h3>
                </div>
                <button onclick="hideModal('transferModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="transferForm" class="p-6 space-y-4">
                <input type="hidden" id="transferAssignmentId">
                <div>
                    <label class="text-sm font-medium text-gray-700">New Custodian</label>
                    <select id="transferCustodianSelect" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select custodian...</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Reason</label>
                    <textarea id="transferReasonInput" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" rows="3" placeholder="Why is this transfer needed?" required></textarea>
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2">
                    <i class="fas fa-paper-plane"></i><span>Start Transfer</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance modal -->
<div id="maintenanceModal" class="hidden fixed inset-0 z-50 bg-gray-900/60">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase text-gray-500">Maintenance Link</p>
                    <h3 class="text-lg font-semibold text-gray-900">Attach Schedule</h3>
                </div>
                <button onclick="hideModal('maintenanceModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="maintenanceForm" class="p-6 space-y-4">
                <input type="hidden" id="maintenanceAssignmentId">
                <div>
                    <label class="text-sm font-medium text-gray-700">Maintenance ID</label>
                    <input type="number" id="maintenanceIdInput" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="Schedule record ID" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Link Type</label>
                    <select id="maintenanceLinkType" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="preventive">Preventive</option>
                        <option value="corrective">Corrective</option>
                        <option value="safety">Safety</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="maintenanceNotesInput" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" rows="3"></textarea>
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg px-4 py-2">
                    <i class="fas fa-link"></i><span>Link Maintenance</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="js/api.js"></script>
<script src="js/assignment_requests.js"></script>
<!-- Request Assignment Modal (For Staff/Teachers) -->
<div id="requestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Request Item Assignment</h3>
            </div>
            <form id="requestForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Item</label>
                    <select id="requestAssetId" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose an item...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                    <input type="text" id="requestPurpose" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" placeholder="e.g., For classroom use">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justification</label>
                    <textarea id="requestJustification" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" placeholder="Explain why you need this item"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeRequestModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve/Reject Modal (For Custodian) -->
<div id="reviewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="reviewModalTitle">Review Request</h3>
            </div>
            <div class="p-6">
                <div id="reviewDetails" class="mb-4"></div>
                <div id="rejectReasonDiv" class="hidden mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                    <textarea id="rejectionReason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeReviewModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" id="rejectBtn" onclick="rejectRequest()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Reject
                    </button>
                    <button type="button" id="approveBtn" onclick="approveRequest()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Approve
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// API utility functions
async function apiCall(url, method = 'GET', data = null) {
    const config = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        config.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, config);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || result.message || 'Request failed');
        }

        return result;
    } catch (error) {
        console.error('API Call Error:', error);
        throw error;
    }
}

function showAlert(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Custodian Assignment Management
class CustodianAssignmentManager {
    constructor() {
        this.custodians = [];
        this.assets = [];
        this.assignments = [];
        this.init();
    }

    init() {
        this.loadCustodians();
        this.loadAssets();
        this.loadAssignments();
        this.bindEvents();
        this.setTodayDate();
    }

    setTodayDate() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('assignmentDate').value = today;
    }

    bindEvents() {
        // Form submission
        document.getElementById('custodianAssignmentForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createAssignment();
        });

        // New assignment buttons (desktop and mobile)
        document.getElementById('newAssignmentBtn')?.addEventListener('click', () => {
            this.showAssignmentForm();
        });

        document.getElementById('newAssignmentBtnMobile')?.addEventListener('click', () => {
            this.showAssignmentForm();
        });

        // Close form button (mobile)
        document.getElementById('closeFormBtn')?.addEventListener('click', () => {
            this.hideAssignmentForm();
        });

        // New custodian modal
        document.getElementById('createNewCustodianBtn').addEventListener('click', () => {
            this.showCustodianModal();
        });

        document.getElementById('closeCustodianModal').addEventListener('click', () => {
            this.hideCustodianModal();
        });

        document.getElementById('closeCustodianModalX')?.addEventListener('click', () => {
            this.hideCustodianModal();
        });

        // New custodian form
        document.getElementById('newCustodianForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createCustodian();
        });

        // Refresh button
        document.getElementById('refreshAssignmentsBtn').addEventListener('click', () => {
            this.loadAssignments();
        });

        // Cancel button
        document.getElementById('cancelAssignmentBtn').addEventListener('click', () => {
            this.hideAssignmentForm();
        });
    }

    async loadCustodians() {
        try {
            const response = await apiCall('api/custodian_assignments.php?action=custodians', 'GET');
            this.custodians = response.data || [];
            this.populateCustodianSelect();
        } catch (error) {
            console.error('Error loading custodians:', error);
            showAlert('Error loading custodians', 'error');
        }
    }

    async loadAssets() {
        try {
            const response = await apiCall('api/custodian_assignments.php?action=available_assets', 'GET');
            this.assets = response.data || [];
            this.populateAssetSelect();
        } catch (error) {
            console.error('Error loading assets:', error);
            showAlert('Error loading assets', 'error');
        }
    }

    async loadAssignments() {
        try {
            const response = await apiCall('api/custodian_assignments.php?action=assignments', 'GET');
            this.assignments = response.data || [];
            this.populateAssignmentsTable();
        } catch (error) {
            console.error('Error loading assignments:', error);
            showAlert('Error loading assignments', 'error');
        }
    }

    populateCustodianSelect() {
        const select = document.getElementById('custodianSelect');
        select.innerHTML = '<option value="">Select Custodian</option>';

        this.custodians.forEach(custodian => {
            const option = document.createElement('option');
            option.value = custodian.id;
            option.textContent = `${custodian.employee_id} - ${custodian.full_name || 'N/A'} (${custodian.department})`;
            select.appendChild(option);
        });
    }

    populateAssetSelect() {
        const select = document.getElementById('assetSelect');
        select.innerHTML = '<option value="">Select Asset</option>';

        this.assets.forEach(asset => {
            const option = document.createElement('option');
            option.value = asset.id;
            option.textContent = `${asset.asset_code} - ${asset.name} (${asset.category_name || 'No Category'})`;
            select.appendChild(option);
        });
    }

    populateAssignmentsTable() {
        const tbody = document.getElementById('assignmentsTableBody');
        const cardContainer = document.getElementById('assignmentsCardContainer');

        if (this.assignments.length === 0) {
            // Desktop view
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No assignments found</td></tr>';
            // Mobile view
            cardContainer.innerHTML = '<div class="p-4 text-center text-gray-500">No assignments found</div>';
            return;
        }

        // Populate desktop table
        tbody.innerHTML = this.assignments.map(assignment => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${assignment.custodian_name || 'N/A'}</div>
                    <div class="text-sm text-gray-500">${assignment.employee_id}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${assignment.custodian_department}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${assignment.asset_name}</div>
                    <div class="text-sm text-gray-500">${assignment.asset_code}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(assignment.assignment_date).toLocaleDateString()}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${assignment.expected_return_date ? new Date(assignment.expected_return_date).toLocaleDateString() : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getStatusColor(assignment.status)}">
                        ${assignment.status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="custodianManager.viewAssignment(${assignment.id})" class="text-blue-600 hover:text-blue-900">View</button>
                        <button onclick="custodianManager.returnAsset(${assignment.id})" class="text-green-600 hover:text-green-900">Return</button>
                        <button onclick="custodianManager.deleteAssignment(${assignment.id})" class="text-red-600 hover:text-red-900">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Populate mobile cards
        cardContainer.innerHTML = this.assignments.map(assignment => `
            <div class="border-b border-gray-200 p-4">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">${assignment.custodian_name || 'N/A'}</h4>
                        <p class="text-sm text-gray-500">${assignment.employee_id} • ${assignment.custodian_department}</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                        <i class="fas fa-clock text-lg"></i>
                    </span>
                </div>
                <div class="mb-3">
                    <p class="font-medium text-gray-900">${assignment.asset_name}</p>
                    <p class="text-sm text-gray-500">${assignment.asset_code}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div>
                        <span class="text-gray-500">Assigned:</span>
                        <p class="font-medium">${new Date(assignment.assignment_date).toLocaleDateString()}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Expected Return:</span>
                        <p class="font-medium">${assignment.expected_return_date ? new Date(assignment.expected_return_date).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="custodianManager.viewAssignment(${assignment.id})" class="flex-1 px-3 py-2 text-sm bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                    <button onclick="custodianManager.returnAsset(${assignment.id})" class="flex-1 px-3 py-2 text-sm bg-green-50 text-green-600 rounded-md hover:bg-green-100">
                        <i class="fas fa-undo mr-1"></i>Return
                    </button>
                    <button onclick="custodianManager.deleteAssignment(${assignment.id})" class="flex-1 px-3 py-2 text-sm bg-red-50 text-red-600 rounded-md hover:bg-red-100">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    getStatusColor(status) {
        switch (status) {
            case 'active': return 'bg-green-100 text-green-800';
            case 'returned': return 'bg-gray-100 text-gray-800';
            case 'transferred': return 'bg-yellow-100 text-yellow-800';
            case 'lost': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    showAssignmentForm() {
        document.getElementById('assignmentForm').classList.remove('hidden');
        // On mobile, scroll to form
        if (window.innerWidth < 1024) {
            document.getElementById('assignmentForm').scrollIntoView({ behavior: 'smooth' });
        }
    }

    hideAssignmentForm() {
        document.getElementById('assignmentForm').classList.add('hidden');
        this.resetForm();
    }

    showCustodianModal() {
        document.getElementById('custodianModal').classList.remove('hidden');
    }

    hideCustodianModal() {
        document.getElementById('custodianModal').classList.add('hidden');
        document.getElementById('newCustodianForm').reset();
    }

    async createCustodian() {
        const custodianData = {
            employee_id: document.getElementById('newEmployeeId').value,
            full_name: document.getElementById('newFullName').value,
            email: document.getElementById('newEmail').value,
            department: document.getElementById('newDepartment').value,
            position: document.getElementById('newPosition').value
        };

        try {
            const response = await apiCall('api/custodian_assignments.php?action=create_custodian', 'POST', custodianData);
            showAlert('Custodian created successfully!', 'success');
            this.hideCustodianModal();
            this.loadCustodians();
        } catch (error) {
            console.error('Error creating custodian:', error);
            showAlert('Error creating custodian: ' + error.message, 'error');
        }
    }

    async createAssignment() {
        const custodianId = document.getElementById('custodianSelect').value;
        const assetId = document.getElementById('assetSelect').value;

        if (!custodianId || !assetId) {
            showAlert('Please select both custodian and asset', 'error');
            return;
        }

        const assignmentData = {
            custodian_id: custodianId,
            asset_id: assetId,
            assignment_date: document.getElementById('assignmentDate').value,
            expected_return_date: document.getElementById('expectedReturnDate').value || null,
            assignment_purpose: document.getElementById('assignmentPurpose').value,
            notes: document.getElementById('assignmentNotes').value
        };

        try {
            const response = await apiCall('api/custodian_assignments.php?action=create_assignment', 'POST', assignmentData);
            showAlert('Assignment created successfully!', 'success');
            this.hideAssignmentForm();
            this.loadAssignments();
            this.loadAssets(); // Refresh assets to update availability
        } catch (error) {
            console.error('Error creating assignment:', error);
            showAlert('Error creating assignment: ' + error.message, 'error');
        }
    }

    async returnAsset(assignmentId) {
        if (!confirm('Mark this assignment as returned?')) return;

        try {
            const response = await apiCall(`api/custodian_assignments.php?action=update_assignment&id=${assignmentId}`, 'PUT', {
                status: 'returned'
            });
            showAlert('Asset returned successfully!', 'success');
            this.loadAssignments();
            this.loadAssets();
        } catch (error) {
            console.error('Error returning asset:', error);
            showAlert('Error returning asset: ' + error.message, 'error');
        }
    }

    async deleteAssignment(assignmentId) {
        if (!confirm('Are you sure you want to delete this assignment?')) return;

        try {
            const response = await apiCall(`api/custodian_assignments.php?action=delete_assignment&id=${assignmentId}`, 'DELETE');
            showAlert('Assignment deleted successfully!', 'success');
            this.loadAssignments();
            this.loadAssets();
        } catch (error) {
            console.error('Error deleting assignment:', error);
            showAlert('Error deleting assignment: ' + error.message, 'error');
        }
    }

    async viewAssignment(assignmentId) {
        try {
            const response = await apiCall(`api/custodian_assignments.php?action=assignment_details&id=${assignmentId}`, 'GET');
            const assignment = response.data;

            alert(`Assignment Details:
Custodian: ${assignment.custodian_name} (${assignment.employee_id})
Asset: ${assignment.asset_name} (${assignment.asset_code})
Assignment Date: ${new Date(assignment.assignment_date).toLocaleDateString()}
Expected Return: ${assignment.expected_return_date ? new Date(assignment.expected_return_date).toLocaleDateString() : 'N/A'}
Purpose: ${assignment.assignment_purpose || 'N/A'}
Notes: ${assignment.notes || 'N/A'}
Status: ${assignment.status}`);
        } catch (error) {
            console.error('Error viewing assignment:', error);
            showAlert('Error loading assignment details', 'error');
        }
    }

    resetForm() {
        document.getElementById('custodianAssignmentForm').reset();
        this.setTodayDate();
    }
}

// Old assignment manager disabled - now using assignment_requests.js
// let custodianManager;
// document.addEventListener('DOMContentLoaded', function() {
//     custodianManager = new CustodianAssignmentManager();
// });
</script>

<!-- Responsive CSS -->
<style>
/* Mobile menu styles */
.sidebar-mobile {
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
}

.sidebar-mobile.active {
    transform: translateX(0);
}

.mobile-menu-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 40;
}

.mobile-menu-overlay.active {
    display: block;
}

/* Desktop - show sidebar by default */
@media (min-width: 1024px) {
    .sidebar-mobile {
        transform: translateX(0) !important;
        position: fixed;
        z-index: 50;
    }
}

/* Mobile responsive adjustments */
@media (max-width: 1024px) {
    .sidebar-mobile {
        position: fixed;
        z-index: 50;
        transform: translateX(-100%);
    }

    .sidebar-mobile.active {
        transform: translateX(0);
    }

    /* Ensure main content doesn't overlap on mobile */
    main {
        margin-left: 0 !important;
    }
}

/* Form enhancements for mobile */
@media (max-width: 768px) {
    select, input, textarea {
        font-size: 16px; /* Prevents zoom on iOS */
    }

    .grid {
        gap: 1rem;
    }
}

/* Card hover effects */
.assignment-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.assignment-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
