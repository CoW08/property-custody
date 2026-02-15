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
                <div></div>
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
                        <button onclick="loadRequests()" type="button" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg shadow-sm transition duration-200">
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Approved Requests</p>
                            <p id="approvedCount" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                            <p class="text-xs text-slate-500">Approved items</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-check-circle text-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isCustodian): ?>
            <!-- Custodian View - All Requests -->
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Assignment Requests</h3>
                    <p class="text-sm text-gray-500">Review new item requests and take action.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-circle-notch fa-spin text-3xl mb-2"></i>
                                    <br>Loading requests...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <!-- Staff View - My Requests -->
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">My Assignment Requests</h3>
                    <p class="text-sm text-gray-500">Track progress of your requests.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="myRequestsTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-circle-notch fa-spin text-3xl mb-2"></i>
                                    <br>Loading your requests...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Request Modal (For Staff) -->
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

<!-- Review Modal (For Custodian) -->
<div id="reviewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="reviewModalTitle">Review Request</h3>
            </div>
            <div class="p-6">
                <div id="reviewDetails" class="mb-4 p-4 bg-gray-50 rounded-lg"></div>
                <div id="rejectReasonDiv" class="hidden mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                    <textarea id="rejectionReason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" placeholder="Please provide a reason for rejection"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeReviewModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" onclick="showRejectReason()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Reject
                    </button>
                    <button type="button" onclick="approveRequest()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Approve
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// API Base URL
const API_BASE_URL = 'https://dpts.qcprotektado.com/api/requesters.php';
let currentRequestId = null;

// Load requests on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, loading requests...');
    loadRequests();
    
    // Set up form submission
    const requestForm = document.getElementById('requestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', submitRequest);
    }
    
    // Load available assets for request modal
    loadAvailableAssets();
});

// Load requests from API
async function loadRequests() {
    const isCustodian = <?php echo json_encode($isCustodian); ?>;
    console.log('Loading requests, isCustodian:', isCustodian);
    
    try {
        let url = `${API_BASE_URL}?action=list`;
        
        if (!isCustodian) {
            const userId = <?php echo json_encode($currentUser['id']); ?>;
            url += `&requester_id=${userId}`;
        }
        
        console.log('Fetching from URL:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.success) {
            if (isCustodian) {
                displayRequests(data.requests || []);
                updateStats(data.statistics || { pending: 0, approved: 0 });
            } else {
                displayMyRequests(data.requests || []);
            }
        } else {
            console.error('API Error:', data.message);
            showAlert('Failed to load requests: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        showAlert('Error loading requests. Please try again.', 'error');
    }
}

// Display requests for custodian view
function displayRequests(requests) {
    const tbody = document.getElementById('requestsTableBody');
    
    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 text-gray-400"></i>
                    <br>No requests found
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    requests.forEach(request => {
        const requestDate = request.request_date ? new Date(request.request_date).toLocaleDateString() : 'N/A';
        const statusClass = getStatusBadgeClass(request.request_status || 'pending');
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 sm:px-6 py-3">
                    <div class="font-medium text-gray-900">${request.first_name || ''} ${request.last_name || ''}</div>
                    <div class="text-xs text-gray-500">${request.email || ''}</div>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="text-sm">${request.department || 'N/A'}</span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <div class="font-medium text-gray-900">${request.item_requested || 'N/A'}</div>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="text-sm text-gray-600">${request.request_purpose || 'N/A'}</span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="text-sm">${requestDate}</span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                        ${request.request_status || 'pending'}
                    </span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <div class="flex items-center gap-2">
                        ${request.request_status === 'pending' ? `
                            <button onclick="openReviewModal(${request.id}, '${request.first_name || ''} ${request.last_name || ''}', '${request.item_requested || ''}')" 
                                class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50" title="Review">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        ` : ''}
                        <button onclick="viewRequestDetails(${request.id})" 
                            class="text-gray-600 hover:text-gray-800 p-1 rounded hover:bg-gray-50" title="View Details">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Display requests for staff view
function displayMyRequests(requests) {
    const tbody = document.getElementById('myRequestsTableBody');
    
    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 text-gray-400"></i>
                    <br>You haven't made any requests yet
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    requests.forEach(request => {
        const requestDate = request.request_date ? new Date(request.request_date).toLocaleDateString() : 'N/A';
        const statusClass = getStatusBadgeClass(request.request_status || 'pending');
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 sm:px-6 py-3">
                    <div class="font-medium text-gray-900">${request.item_requested || 'N/A'}</div>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="text-sm text-gray-600">${request.request_purpose || 'N/A'}</span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="text-sm">${requestDate}</span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                        ${request.request_status || 'pending'}
                    </span>
                </td>
                <td class="px-4 sm:px-6 py-3">
                    ${request.request_status === 'pending' ? `
                        <button onclick="cancelRequest(${request.id})" class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50" title="Cancel Request">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get status badge class
function getStatusBadgeClass(status) {
    switch(status) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'fulfilled': return 'bg-blue-100 text-blue-800';
        case 'pending': 
        default: return 'bg-yellow-100 text-yellow-800';
    }
}

// Update statistics
function updateStats(stats) {
    const pendingEl = document.getElementById('pendingCount');
    const approvedEl = document.getElementById('approvedCount');
    
    if (pendingEl) pendingEl.textContent = stats.pending || 0;
    if (approvedEl) approvedEl.textContent = stats.approved || 0;
}

// Open review modal
function openReviewModal(requestId, requesterName, itemName) {
    console.log('Opening review modal for request:', requestId);
    currentRequestId = requestId;
    
    const reviewDetails = document.getElementById('reviewDetails');
    reviewDetails.innerHTML = `
        <p class="mb-2"><strong>Requester:</strong> ${requesterName}</p>
        <p><strong>Item:</strong> ${itemName}</p>
    `;
    
    document.getElementById('reviewModalTitle').textContent = 'Review Request';
    document.getElementById('rejectReasonDiv').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('hidden');
}

// Show reject reason field
function showRejectReason() {
    document.getElementById('rejectReasonDiv').classList.remove('hidden');
    document.querySelector('#rejectReasonDiv + div button.bg-red-600').textContent = 'Confirm Reject';
}

// Approve request
async function approveRequest() {
    if (!currentRequestId) {
        showAlert('No request selected', 'error');
        return;
    }
    
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'approve',
                requester_id: currentRequestId,
                approved_by: '<?php echo $currentUser['full_name']; ?>',
                notes: 'Approved via custodian panel'
            })
        });
        
        const data = await response.json();
        console.log('Approve response:', data);
        
        if (data.success) {
            showAlert('Request approved successfully!', 'success');
            closeReviewModal();
            loadRequests();
        } else {
            showAlert('Failed to approve request: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error approving request:', error);
        showAlert('Error approving request. Please try again.', 'error');
    }
}

// Reject request
async function rejectRequest() {
    if (!currentRequestId) {
        showAlert('No request selected', 'error');
        return;
    }
    
    const reason = document.getElementById('rejectionReason').value;
    if (!reason) {
        showAlert('Please provide a rejection reason', 'warning');
        return;
    }
    
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reject',
                requester_id: currentRequestId,
                rejected_by: '<?php echo $currentUser['full_name']; ?>',
                reason: reason
            })
        });
        
        const data = await response.json();
        console.log('Reject response:', data);
        
        if (data.success) {
            showAlert('Request rejected successfully!', 'success');
            closeReviewModal();
            loadRequests();
        } else {
            showAlert('Failed to reject request: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
        showAlert('Error rejecting request. Please try again.', 'error');
    }
}

// Cancel request (for staff)
async function cancelRequest(requestId) {
    if (!confirm('Are you sure you want to cancel this request?')) return;
    
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reject',
                requester_id: requestId,
                rejected_by: 'requester',
                reason: 'Cancelled by requester'
            })
        });
        
        const data = await response.json();
        console.log('Cancel response:', data);
        
        if (data.success) {
            showAlert('Request cancelled successfully!', 'success');
            loadRequests();
        } else {
            showAlert('Failed to cancel request: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error cancelling request:', error);
        showAlert('Error cancelling request. Please try again.', 'error');
    }
}

// Submit new request (for staff)
async function submitRequest(event) {
    event.preventDefault();
    
    const assetSelect = document.getElementById('requestAssetId');
    const assetId = assetSelect.value;
    const purpose = document.getElementById('requestPurpose').value;
    const justification = document.getElementById('requestJustification').value;
    
    if (!assetId) {
        showAlert('Please select an item', 'warning');
        return;
    }
    
    // Get asset name from selected option
    const assetName = assetSelect.options[assetSelect.selectedIndex]?.text || 'Unknown Item';
    
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'create',
                requester_id: '<?php echo $currentUser['id']; ?>',
                item_name: assetName,
                purpose: purpose + (justification ? ' - ' + justification : ''),
                source: 'web'
            })
        });
        
        const data = await response.json();
        console.log('Submit response:', data);
        
        if (data.success) {
            showAlert('Request submitted successfully!', 'success');
            closeRequestModal();
            loadRequests();
        } else {
            showAlert('Failed to submit request: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error submitting request:', error);
        showAlert('Error submitting request. Please try again.', 'error');
    }
}

// View request details
async function viewRequestDetails(requestId) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=status&requester_id=${requestId}`);
        const data = await response.json();
        
        console.log('Request details:', data);
        
        if (data.success && data.data) {
            const request = data.data;
            const details = `
Requester: ${request.first_name} ${request.last_name}
Item: ${request.item_requested}
Purpose: ${request.request_purpose}
Status: ${request.request_status}
Date: ${new Date(request.request_date).toLocaleString()}
${request.approval_date ? 'Approved: ' + new Date(request.approval_date).toLocaleString() : ''}
            `;
            showAlert(details, 'info');
        }
    } catch (error) {
        console.error('Error viewing request:', error);
    }
}

// Load available assets for request modal
async function loadAvailableAssets() {
    try {
        const response = await fetch('api/custodian_assignments.php?action=available_assets');
        const data = await response.json();
        console.log('Available assets:', data);
        
        const select = document.getElementById('requestAssetId');
        select.innerHTML = '<option value="">Choose an item...</option>';
        
        if (data.data && data.data.length > 0) {
            data.data.forEach(asset => {
                const option = document.createElement('option');
                option.value = asset.id;
                option.textContent = `${asset.name} (${asset.asset_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading assets:', error);
    }
}

// Modal functions
function openRequestModal() {
    loadAvailableAssets();
    document.getElementById('requestModal').classList.remove('hidden');
}

function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
    document.getElementById('requestForm').reset();
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectReasonDiv').classList.add('hidden');
    currentRequestId = null;
}

// Show alert function
function showAlert(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 animate-fade-in ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'fade-out 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-out {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease;
    }
`;
document.head.appendChild(style);
</script>

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

    main {
        margin-left: 0 !important;
    }
}

/* Form enhancements for mobile */
@media (max-width: 768px) {
    select, input, textarea {
        font-size: 16px;
    }

    .grid {
        gap: 1rem;
    }
}

/* Table hover effects */
tbody tr:hover {
    background-color: #f9fafb;
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>