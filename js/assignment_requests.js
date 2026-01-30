async function refreshCustodianData(button) {
    if (!button) return;

    if (button.dataset.refreshing === 'true') {
        return;
    }

    button.dataset.refreshing = 'true';
    const icon = button.querySelector('i');
    const label = button.querySelector('span');
    const originalText = label ? label.textContent : '';

    button.classList.add('opacity-70', 'cursor-not-allowed');
    button.disabled = true;
    if (icon) icon.classList.add('animate-spin');
    if (label) label.textContent = 'Refreshing...';

    try {
        await Promise.all([loadPendingRequests(), loadStats()]);
        showNotification('Assignments updated', 'success');
    } catch (error) {
        console.error('Error refreshing custodian data:', error);
        showNotification('Failed to refresh assignments', 'error');
    } finally {
        button.dataset.refreshing = 'false';
        button.classList.remove('opacity-70', 'cursor-not-allowed');
        button.disabled = false;
        if (icon) icon.classList.remove('animate-spin');
        if (label) label.textContent = originalText || 'Refresh';
    }
}

// Assignment Request Management
let currentRequestId = null;
let currentUserRole = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeAssignmentRequests();
});

function initializeAssignmentRequests() {
    // Get current user role
    const container = document.querySelector('[data-user-role]');
    if (container) {
        currentUserRole = container.getAttribute('data-user-role');
    } else {
        const savedUser = sessionStorage.getItem('currentUser');
        if (savedUser) {
            const user = JSON.parse(savedUser);
            currentUserRole = user.role;
        }
    }

    // Load appropriate data based on role
    if (currentUserRole === 'admin' || currentUserRole === 'custodian') {
        loadPendingRequests();
        loadStats();
        
        // Setup refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                await refreshCustodianData(refreshBtn);
            });
        }
    } else {
        loadMyRequests();
        loadAvailableAssets();
    }

    // Setup request form
    const requestForm = document.getElementById('requestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', handleRequestSubmit);
    }
}

// Load stats for custodian
async function loadStats() {
    try {
        // First cleanup any orphaned assignments
        await fetch('api/custodian_assignments.php?action=cleanup_orphaned');
        
        // Then load stats
        const response = await fetch('api/custodian_assignments.php?action=stats');
        const result = await response.json();
        
        if (result.data) {
            document.getElementById('pendingCount').textContent = result.data.pending_requests || 0;
            document.getElementById('activeCount').textContent = result.data.active_assignments || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load pending requests (for custodian)
async function loadPendingRequests() {
    try {
        const response = await fetch('api/custodian_assignments.php?action=requests');
        const result = await response.json();
        
        const tbody = document.getElementById('requestsTableBody');
        if (!tbody) return;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No pending requests</td></tr>';
            return;
        }

        tbody.innerHTML = result.data.map(request => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4">
                    <input type="checkbox" class="request-checkbox rounded border-gray-300" value="${request.id}" onchange="updateBulkActions()">
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${request.requester_name}</div>
                    <div class="text-xs text-gray-500">${request.requester_email}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">${request.requester_department}</td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${request.asset_name}</div>
                    <div class="text-xs text-gray-500">${request.asset_code}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">${request.purpose || 'N/A'}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${formatDate(request.created_at)}</td>
                <td class="px-6 py-4">
                    ${getStatusBadge(request.status)}
                </td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex gap-2">
                        ${request.status === 'pending' ? `
                            <button onclick="reviewRequest(${request.id})" class="text-blue-600 hover:text-blue-900" title="Review Request">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="deleteAssignment(${request.id})" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : request.status === 'approved' && request.assignment_id ? `
                            <button onclick="downloadAssignmentPDF(${request.assignment_id})" class="text-green-600 hover:text-green-900 text-lg" title="Download PDF Document">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        ` : `
                            <button onclick="deleteAssignment(${request.id})" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading requests:', error);
        showNotification('Error loading requests', 'error');
    }
}

// Load user's own requests (for staff)
async function loadMyRequests() {
    try {
        const response = await fetch('api/custodian_assignments.php?action=my_requests');
        const result = await response.json();
        
        const tbody = document.getElementById('myRequestsTableBody');
        if (!tbody) return;

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No requests yet. Click "New Request" to get started.</td></tr>';
            return;
        }

        tbody.innerHTML = result.data.map(request => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${request.asset_name}</div>
                    <div class="text-xs text-gray-500">${request.asset_code}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">${request.purpose || 'N/A'}</td>
                <td class="px-6 py-4 text-sm text-gray-500">${formatDate(request.created_at)}</td>
                <td class="px-6 py-4">
                    ${getStatusBadge(request.status)}
                    ${request.status === 'rejected' && request.rejection_reason ? `
                        <div class="text-xs text-red-600 mt-1">Reason: ${request.rejection_reason}</div>
                    ` : ''}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">${request.reviewed_by_name || '-'}</td>
                <td class="px-6 py-4">
                    ${request.status === 'approved' && request.assignment_id ? `
                        <button onclick="downloadAssignmentPDF(${request.assignment_id})" class="text-green-600 hover:text-green-700 text-xl" title="Download PDF Document">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    ` : `
                        <span class="text-xs text-gray-400">-</span>
                    `}
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading my requests:', error);
        showNotification('Error loading your requests', 'error');
    }
}

// Load available assets for request dropdown
async function loadAvailableAssets() {
    try {
        const response = await fetch('api/custodian_assignments.php?action=available_assets');
        const result = await response.json();
        
        const select = document.getElementById('requestAssetId');
        if (!select) return;

        select.innerHTML = '<option value="">Choose an asset...</option>';
        
        if (result.data) {
            result.data.forEach(asset => {
                const option = document.createElement('option');
                option.value = asset.id;
                option.textContent = `${asset.name} (${asset.asset_code}) - ${asset.category}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading assets:', error);
    }
}

// Open request modal
function openRequestModal() {
    document.getElementById('requestModal').classList.remove('hidden');
    loadAvailableAssets();
}

// Close request modal
function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
    document.getElementById('requestForm').reset();
}

// Handle request submission
async function handleRequestSubmit(e) {
    e.preventDefault();
    
    const data = {
        asset_id: document.getElementById('requestAssetId').value,
        purpose: document.getElementById('requestPurpose').value,
        justification: document.getElementById('requestJustification').value
    };

    try {
        const response = await fetch('api/custodian_assignments.php?action=request_assignment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Request submitted successfully! Awaiting custodian approval.', 'success');
            closeRequestModal();
            loadMyRequests();
        } else {
            showNotification(result.error || 'Failed to submit request', 'error');
        }
    } catch (error) {
        console.error('Error submitting request:', error);
        showNotification('Error submitting request', 'error');
    }
}

// Review request (custodian)
async function reviewRequest(requestId) {
    currentRequestId = requestId;
    
    try {
        const response = await fetch('api/custodian_assignments.php?action=requests');
        const result = await response.json();
        
        const request = result.data.find(r => r.id == requestId);
        if (!request) return;

        document.getElementById('reviewDetails').innerHTML = `
            <div class="space-y-2 text-sm">
                <p><strong>Requester:</strong> ${request.requester_name}</p>
                <p><strong>Department:</strong> ${request.requester_department}</p>
                <p><strong>Email:</strong> ${request.requester_email}</p>
                <p><strong>Asset:</strong> ${request.asset_name} (${request.asset_code})</p>
                <p><strong>Category:</strong> ${request.category}</p>
                <p><strong>Purpose:</strong> ${request.purpose || 'N/A'}</p>
                <p><strong>Justification:</strong> ${request.justification || 'Not provided'}</p>
                <p><strong>Requested:</strong> ${formatDate(request.created_at)}</p>
            </div>
        `;

        document.getElementById('reviewModal').classList.remove('hidden');
        document.getElementById('rejectReasonDiv').classList.add('hidden');
    } catch (error) {
        console.error('Error loading request details:', error);
        showNotification('Error loading request details', 'error');
    }
}

// Close review modal
function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectReasonDiv').classList.add('hidden');
    currentRequestId = null;
}

// Approve request
async function approveRequest() {
    if (!currentRequestId) return;

    if (!confirm('Are you sure you want to approve this request? The asset will be assigned to the requester.')) {
        return;
    }

    try {
        const response = await fetch('api/custodian_assignments.php?action=approve_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: currentRequestId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Request approved! Asset has been assigned.', 'success');
            
            // Show document view notification
            if (result.pdf_url) {
                setTimeout(() => {
                    const viewDoc = confirm('Accountability Transfer Document is ready. View and print now?');
                    if (viewDoc) {
                        window.open(result.pdf_url, '_blank');
                    }
                }, 1000);
            }
            
            closeReviewModal();
            loadPendingRequests();
            loadStats();
        } else {
            showNotification(result.error || 'Failed to approve request', 'error');
        }
    } catch (error) {
        console.error('Error approving request:', error);
        showNotification('Error approving request', 'error');
    }
}

// Reject request
async function rejectRequest() {
    if (!currentRequestId) return;

    // Show rejection reason field
    const rejectDiv = document.getElementById('rejectReasonDiv');
    if (rejectDiv.classList.contains('hidden')) {
        rejectDiv.classList.remove('hidden');
        document.getElementById('rejectBtn').textContent = 'Confirm Reject';
        return;
    }

    const reason = document.getElementById('rejectionReason').value.trim();
    if (!reason) {
        showNotification('Please provide a rejection reason', 'warning');
        return;
    }

    try {
        const response = await fetch('api/custodian_assignments.php?action=reject_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                request_id: currentRequestId,
                reason: reason
            })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Request rejected. Requester has been notified.', 'success');
            closeReviewModal();
            loadPendingRequests();
            loadStats();
        } else {
            showNotification(result.error || 'Failed to reject request', 'error');
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
        showNotification('Error rejecting request', 'error');
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getStatusBadge(status) {
    const badges = {
        pending: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        approved: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>',
        rejected: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>'
    };
    return badges[status] || status;
}

// Download assignment PDF (for both staff and custodian)
function downloadAssignmentPDF(assignmentId) {
    // Open PDF in new tab for viewing/printing/downloading
    window.open(`generate_accountability_pdf.php?assignment_id=${assignmentId}`, '_blank');
}

// Delete assignment
async function deleteAssignment(requestId) {
    if (!confirm('Are you sure you want to delete this assignment? This will:\n\n- Remove the assignment record\n- Set the asset status back to "Available"\n- Delete the request\n\nThis action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('api/custodian_assignments.php?action=delete_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Assignment deleted successfully. Asset is now available.', 'success');
            loadPendingRequests();
            loadStats();
        } else {
            showNotification(result.error || 'Failed to delete assignment', 'error');
        }
    } catch (error) {
        console.error('Error deleting assignment:', error);
        showNotification('Error deleting assignment', 'error');
    }
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.request-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

// Update bulk actions visibility
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.request-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');
    
    if (checkboxes.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = `${checkboxes.length} selected`;
    } else {
        bulkActions.classList.add('hidden');
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.request-checkbox');
    selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
}

// Bulk delete
async function bulkDelete() {
    const checkboxes = document.querySelectorAll('.request-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (selectedIds.length === 0) return;
    
    if (!confirm(`Are you sure you want to delete ${selectedIds.length} assignment(s)? This will:\n\n- Remove the assignment records\n- Set the assets back to "Available"\n- Delete the requests\n\nThis action cannot be undone.`)) {
        return;
    }
    
    let successCount = 0;
    let errorCount = 0;
    
    for (const requestId of selectedIds) {
        try {
            const response = await fetch('api/custodian_assignments.php?action=delete_request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });
            
            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        } catch (error) {
            errorCount++;
        }
    }
    
    if (successCount > 0) {
        showNotification(`${successCount} assignment(s) deleted successfully`, 'success');
    }
    if (errorCount > 0) {
        showNotification(`${errorCount} assignment(s) failed to delete`, 'error');
    }
    
    // Reload data
    loadPendingRequests();
    loadStats();
    
    // Reset selection
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
