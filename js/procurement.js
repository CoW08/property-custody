// Procurement Management JavaScript

let currentPage = 1;
let currentFilters = {};
let requestItems = [];
let currentUserRole = null;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Get current user role from session
    getCurrentUserRole();
    loadProcurementStats();
    loadProcurementRequests();
    setupEventListeners();
    setDefaultValues();
});

// Get current user role
function getCurrentUserRole() {
    const savedUser = sessionStorage.getItem('currentUser');
    if (savedUser) {
        const user = JSON.parse(savedUser);
        currentUserRole = user.role;
    }
    return currentUserRole;
}

function setupEventListeners() {
    // Search and filter inputs
    document.getElementById('searchInput').addEventListener('input', debounce(filterRequests, 300));
    document.getElementById('statusFilter').addEventListener('change', filterRequests);
    document.getElementById('priorityFilter').addEventListener('change', filterRequests);
    document.getElementById('typeFilter').addEventListener('change', filterRequests);
    document.getElementById('refreshBtn').addEventListener('click', refreshData);

    // Form submission
    document.getElementById('newRequestForm').addEventListener('submit', submitProcurementRequest);

    // Add item button
    document.getElementById('addItemBtn').addEventListener('click', addRequestItem);

    // Set today's date as default
    document.getElementById('requestDate').value = getCurrentDate();
}

function setDefaultValues() {
    // Set current user ID (you should get this from session/auth)
    document.getElementById('requestorId').value = 1; // Default to admin for now

    // Add first item row
    addRequestItem();
}

function getCurrentDate() {
    const today = new Date();
    return today.toISOString().split('T')[0];
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load procurement statistics
async function loadProcurementStats() {
    try {
        const response = await fetch('api/procurement.php?action=stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.data.overall;
            document.getElementById('totalRequests').textContent = stats.total_requests || 0;
            document.getElementById('pendingRequests').textContent = stats.submitted_count || 0;
            document.getElementById('approvedRequests').textContent = stats.approved_count || 0;
            document.getElementById('totalCost').textContent = formatCurrency(stats.total_estimated_cost || 0);
        }
    } catch (error) {
        console.error('Error loading procurement stats:', error);
    }
}

// Load procurement requests with filters
async function loadProcurementRequests(page = 1) {
    try {
        showLoading();

        const params = new URLSearchParams({
            action: 'list',
            page: page,
            ...currentFilters
        });

        const response = await fetch(`api/procurement.php?${params}`);
        const data = await response.json();

        if (data.success) {
            displayProcurementRequests(data.data);
            updatePagination(data.pagination);
            currentPage = page;
        } else {
            showError('Failed to load procurement requests');
        }
    } catch (error) {
        console.error('Error loading procurement requests:', error);
        showError('Error loading procurement requests');
    } finally {
        hideLoading();
    }
}

// Display procurement requests in table and mobile cards
function displayProcurementRequests(requests) {
    const tbody = document.getElementById('procurementTableBody');
    const mobileList = document.getElementById('mobileRequestsList');

    if (requests.length === 0) {
        // Desktop table
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                    No procurement requests found
                </td>
            </tr>
        `;

        // Mobile cards
        mobileList.innerHTML = `
            <div class="p-6 text-center text-gray-500">
                No procurement requests found
            </div>
        `;
        return;
    }

    // Desktop table view
    tbody.innerHTML = requests.map(request => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${request.request_code}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getTypeColor(request.request_type)}">
                    ${capitalizeFirst(request.request_type)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${request.requestor_name || 'N/A'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${request.department || 'N/A'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${formatDate(request.request_date)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getPriorityColor(request.priority)}">
                    ${capitalizeFirst(request.priority)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(request.status)}">
                    ${capitalizeFirst(request.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${formatCurrency(request.total_estimated_cost || request.estimated_cost || 0)}</div>
                <div class="text-xs text-gray-500">${request.items_count || 0} items</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="viewRequest(${request.id})" class="text-blue-600 hover:text-blue-900" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${canEditRequest(request.status) ? `
                        <button onclick="editRequest(${request.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteRequest(${request.id})" class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                    ${request.status === 'submitted' && (currentUserRole === 'admin' || currentUserRole === 'custodian' || currentUserRole === 'finance') ? `
                        <button onclick="approveRequest(${request.id})" class="text-green-600 hover:text-green-900" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="rejectRequest(${request.id})" class="text-red-600 hover:text-red-900" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');

    // Mobile card view
    mobileList.innerHTML = requests.map(request => `
        <div class="border-b border-gray-200 p-4">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">${request.request_code}</h4>
                    <p class="text-xs text-gray-500">${request.requestor_name || 'N/A'} • ${request.department || 'N/A'}</p>
                </div>
                <div class="flex space-x-2">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(request.status)}">
                        ${capitalizeFirst(request.status)}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3 text-xs">
                <div>
                    <span class="text-gray-500">Type:</span>
                    <span class="ml-1 px-2 py-0.5 rounded-full ${getTypeColor(request.request_type)}">
                        ${capitalizeFirst(request.request_type)}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Priority:</span>
                    <span class="ml-1 px-2 py-0.5 rounded-full ${getPriorityColor(request.priority)}">
                        ${capitalizeFirst(request.priority)}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Date:</span>
                    <span class="ml-1 text-gray-900">${formatDate(request.request_date)}</span>
                </div>
                <div>
                    <span class="text-gray-500">Items:</span>
                    <span class="ml-1 text-gray-900">${request.items_count || 0}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-900">
                    ${formatCurrency(request.total_estimated_cost || request.estimated_cost || 0)}
                </div>
                <div class="flex space-x-3">
                    <button onclick="viewRequest(${request.id})" class="text-blue-600 hover:text-blue-900 text-sm" title="View Details">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                    ${canEditRequest(request.status) ? `
                        <button onclick="editRequest(${request.id})" class="text-yellow-600 hover:text-yellow-900 text-sm" title="Edit">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button onclick="deleteRequest(${request.id})" class="text-red-600 hover:text-red-900 text-sm" title="Delete">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    ` : ''}
                    ${request.status === 'submitted' ? `
                        <button onclick="approveRequest(${request.id})" class="text-green-600 hover:text-green-900 text-sm" title="Approve">
                            <i class="fas fa-check mr-1"></i>Approve
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

// Check if user can edit request based on status and role
function canEditRequest(status) {
    // Admin and Custodian can edit any status
    if (currentUserRole === 'admin' || currentUserRole === 'custodian') {
        return true;
    }
    // Other roles can only edit draft or submitted
    return status === 'draft' || status === 'submitted';
}

// Filter requests
function filterRequests() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const type = document.getElementById('typeFilter').value;

    currentFilters = {
        ...(search && { search }),
        ...(status && { status }),
        ...(priority && { priority }),
        ...(type && { request_type: type })
    };

    loadProcurementRequests(1);
}

// Refresh data
function refreshData() {
    loadProcurementStats();
    loadProcurementRequests(currentPage);
}

// Add request item to form
function addRequestItem() {
    const container = document.getElementById('itemsContainer');
    const itemIndex = requestItems.length;

    const itemHtml = `
        <div class="item-row border border-gray-200 rounded-lg p-3 sm:p-4 mb-4" data-index="${itemIndex}">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <h5 class="text-sm sm:text-md font-medium text-gray-900">Item ${itemIndex + 1}</h5>
                <button type="button" onclick="removeRequestItem(${itemIndex})" class="text-red-600 hover:text-red-900 p-1">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                    <input type="text" name="items[${itemIndex}][item_name]" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="items[${itemIndex}][quantity]" min="1" value="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           onchange="calculateItemTotal(${itemIndex})">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <input type="text" name="items[${itemIndex}][unit]" value="piece"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                    <input type="number" name="items[${itemIndex}][estimated_unit_cost]" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           onchange="calculateItemTotal(${itemIndex})">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost</label>
                    <input type="number" name="items[${itemIndex}][total_cost]" step="0.01" readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                </div>
            </div>

            <div class="mt-3 sm:mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="items[${itemIndex}][description]" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
            </div>

            <div class="mt-3 sm:mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Specifications</label>
                <textarea name="items[${itemIndex}][specifications]" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"></textarea>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);
    requestItems.push({ index: itemIndex });
}

// Remove request item
function removeRequestItem(index) {
    const itemRow = document.querySelector(`[data-index="${index}"]`);
    if (itemRow) {
        itemRow.remove();
        requestItems = requestItems.filter(item => item.index !== index);
        calculateTotalCost();
    }
}

// Calculate item total cost
function calculateItemTotal(index) {
    const quantityInput = document.querySelector(`input[name="items[${index}][quantity]"]`);
    const unitCostInput = document.querySelector(`input[name="items[${index}][estimated_unit_cost]"]`);
    const totalCostInput = document.querySelector(`input[name="items[${index}][total_cost]"]`);

    const quantity = parseFloat(quantityInput.value) || 0;
    const unitCost = parseFloat(unitCostInput.value) || 0;
    const total = quantity * unitCost;

    totalCostInput.value = total.toFixed(2);
    calculateTotalCost();
}

// Calculate total estimated cost
function calculateTotalCost() {
    let total = 0;
    document.querySelectorAll('input[name$="[total_cost]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    // Update estimated cost display if needed
    console.log('Total estimated cost:', total);
}

// Submit procurement request
async function submitProcurementRequest(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const editId = form.dataset.editId;
        const isEdit = !!editId;
        
        const formData = new FormData(form);
        const requestData = {
            request_type: formData.get('request_type'),
            requestor_id: parseInt(formData.get('requestor_id')),
            department: formData.get('department'),
            request_date: formData.get('request_date'),
            required_date: formData.get('required_date'),
            justification: formData.get('justification'),
            priority: formData.get('priority'),
            notes: formData.get('notes'),
            items: []
        };

        // If editing, include the ID
        if (isEdit) {
            requestData.id = parseInt(editId);
        }

        // Collect items data
        requestItems.forEach(item => {
            const index = item.index;
            const itemData = {
                item_name: formData.get(`items[${index}][item_name]`),
                quantity: parseInt(formData.get(`items[${index}][quantity]`)) || 1,
                unit: formData.get(`items[${index}][unit]`) || 'piece',
                estimated_unit_cost: parseFloat(formData.get(`items[${index}][estimated_unit_cost]`)) || 0,
                description: formData.get(`items[${index}][description]`),
                specifications: formData.get(`items[${index}][specifications]`)
            };
            requestData.items.push(itemData);
        });

        if (requestData.items.length === 0) {
            showError('Please add at least one item to the request');
            return;
        }

        const action = isEdit ? 'update' : 'create';
        const response = await fetch(`api/procurement.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        if (result.success) {
            showSuccess(`Procurement request ${isEdit ? 'updated' : 'created'} successfully`);
            closeModal('newRequestModal');
            resetForm();
            refreshData();
        } else {
            showError(result.error || `Failed to ${isEdit ? 'update' : 'create'} procurement request`);
        }
    } catch (error) {
        console.error('Error submitting procurement request:', error);
        showError('Error submitting procurement request');
    }
}

// View request details
async function viewRequest(id) {
    try {
        const response = await fetch(`api/procurement.php?action=details&id=${id}`);
        const data = await response.json();

        if (data.success) {
            displayRequestDetails(data.data);
            openModal('viewRequestModal');
        } else {
            showError('Failed to load request details');
        }
    } catch (error) {
        console.error('Error loading request details:', error);
        showError('Error loading request details');
    }
}

// Display request details in modal
function displayRequestDetails(request) {
    const content = document.getElementById('requestDetailsContent');

    // Mobile card view for items
    const itemsCardsHtml = request.items.map(item => `
        <div class="border border-gray-200 rounded-lg p-3 mb-3">
            <h5 class="font-medium text-gray-900 mb-2">${item.item_name}</h5>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-gray-500">Quantity:</span> ${item.quantity} ${item.unit}</div>
                <div><span class="text-gray-500">Unit Cost:</span> ${formatCurrency(item.estimated_unit_cost)}</div>
                <div class="col-span-2"><span class="text-gray-500">Total:</span> <span class="font-medium">${formatCurrency(item.total_cost)}</span></div>
            </div>
            ${item.description ? `<p class="text-xs text-gray-600 mt-2">${item.description}</p>` : ''}
        </div>
    `).join('');

    // Desktop table view for items
    const itemsTableHtml = request.items.map(item => `
        <tr>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.item_name}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.quantity}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.unit}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${formatCurrency(item.estimated_unit_cost)}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${formatCurrency(item.total_cost)}</td>
        </tr>
    `).join('');

    content.innerHTML = `
        <div class="mt-4 sm:mt-6 space-y-4 sm:space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Request Code</label>
                    <p class="mt-1 text-sm text-gray-900">${request.request_code}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <p class="mt-1 text-sm text-gray-900">${capitalizeFirst(request.request_type)}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Requestor</label>
                    <p class="mt-1 text-sm text-gray-900">${request.requestor_name}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <p class="mt-1 text-sm text-gray-900">${request.department}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Request Date</label>
                    <p class="mt-1 text-sm text-gray-900">${formatDate(request.request_date)}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Required Date</label>
                    <p class="mt-1 text-sm text-gray-900">${request.required_date ? formatDate(request.required_date) : 'N/A'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Priority</label>
                    <p class="mt-1 text-sm text-gray-900">${capitalizeFirst(request.priority)}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <p class="mt-1 text-sm text-gray-900">${capitalizeFirst(request.status)}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Justification</label>
                <p class="mt-1 text-sm text-gray-900">${request.justification}</p>
            </div>

            ${request.notes ? `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <p class="mt-1 text-sm text-gray-900">${request.notes}</p>
                </div>
            ` : ''}

            <div>
                <h4 class="text-lg font-medium text-gray-900 mb-4">Request Items</h4>

                <!-- Mobile Card View -->
                <div class="block sm:hidden">
                    ${itemsCardsHtml}
                </div>

                <!-- Desktop Table View -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                                <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsTableHtml}
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-center sm:text-right">
                    <p class="text-base sm:text-lg font-semibold">Total Estimated Cost: ${formatCurrency(request.estimated_cost)}</p>
                </div>
            </div>
        </div>
    `;
}

// Edit request
async function editRequest(id) {
    try {
        // Fetch request details
        const response = await fetch(`api/procurement.php?action=details&id=${id}`);
        const result = await response.json();

        if (result.success) {
            const request = result.data;
            
            // Populate form with existing data
            document.getElementById('requestType').value = request.request_type;
            document.getElementById('requestDate').value = request.request_date;
            document.getElementById('requiredDate').value = request.required_date || '';
            document.getElementById('requestPriority').value = request.priority;
            document.getElementById('requestJustification').value = request.justification;
            document.getElementById('requestNotes').value = request.notes || '';
            
            // Clear and populate items
            const itemsContainer = document.getElementById('itemsContainer');
            itemsContainer.innerHTML = '';
            requestItems = [];
            
            if (request.items && request.items.length > 0) {
                request.items.forEach(item => {
                    requestItems.push({
                        item_name: item.item_name,
                        description: item.description || '',
                        quantity: item.quantity,
                        unit: item.unit,
                        estimated_unit_cost: item.estimated_unit_cost,
                        specifications: item.specifications || ''
                    });
                    addRequestItem(item);
                });
            }
            
            // Store request ID for update
            document.getElementById('newRequestForm').dataset.editId = id;
            
            // Change modal title and button text
            document.querySelector('#newRequestModal h3').textContent = 'Edit Procurement Request';
            document.querySelector('#newRequestForm button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Update Request';
            
            // Open modal
            openModal('newRequestModal');
        } else {
            showError(result.error || 'Failed to load request details');
        }
    } catch (error) {
        console.error('Error loading request for edit:', error);
        showError('Error loading request details');
    }
}

// Delete request
async function deleteRequest(id) {
    if (!confirm('Are you sure you want to delete this procurement request?')) {
        return;
    }

    try {
        const response = await fetch(`api/procurement.php?action=delete&id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Procurement request deleted successfully');
            refreshData();
        } else {
            showError(result.error || 'Failed to delete procurement request');
        }
    } catch (error) {
        console.error('Error deleting procurement request:', error);
        showError('Error deleting procurement request');
    }
}

// Approve request
async function approveRequest(id) {
    const approvedCost = prompt('Enter approved cost (optional):');
    const notes = prompt('Enter approval notes (optional):');

    try {
        const response = await fetch('api/procurement.php?action=approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                approved_by: 1, // Should get from current user session
                approved_cost: approvedCost ? parseFloat(approvedCost) : null,
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Procurement request approved successfully');
            refreshData();
        } else {
            showError(result.error || 'Failed to approve procurement request');
        }
    } catch (error) {
        console.error('Error approving procurement request:', error);
        showError('Error approving procurement request');
    }
}

// Reject request
async function rejectRequest(id) {
    const notes = prompt('Enter rejection reason:');
    if (!notes) return;

    try {
        const response = await fetch('api/procurement.php?action=reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                approved_by: 1, // Should get from current user session
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Procurement request rejected successfully');
            refreshData();
        } else {
            showError(result.error || 'Failed to reject procurement request');
        }
    } catch (error) {
        console.error('Error rejecting procurement request:', error);
        showError('Error rejecting procurement request');
    }
}

// Open new request modal (reset form for new request)
function openNewRequestModal() {
    // Reset form
    resetForm();
    
    // Clear edit mode
    delete document.getElementById('newRequestForm').dataset.editId;
    
    // Reset modal title and button text
    document.querySelector('#newRequestModal h3').textContent = 'New Procurement Request';
    document.querySelector('#newRequestForm button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Save Request';
    
    // Set default values
    document.getElementById('requestDate').value = getCurrentDate();
    document.getElementById('requestPriority').value = 'medium';
    
    // Add first item row
    addRequestItem();
    
    // Open modal
    openModal('newRequestModal');
}

// Close request modal and reset form
function closeRequestModal() {
    closeModal('newRequestModal');
    resetForm();
    // Clear edit mode
    delete document.getElementById('newRequestForm').dataset.editId;
    // Reset modal title
    document.querySelector('#newRequestModal h3').textContent = 'New Procurement Request';
    document.querySelector('#newRequestForm button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Save Request';
}

// Reset form
function resetForm() {
    document.getElementById('newRequestForm').reset();
    document.getElementById('itemsContainer').innerHTML = '';
    requestItems = [];
    document.getElementById('requestDate').value = getCurrentDate();
    document.getElementById('requestorId').value = 1;
    addRequestItem();
}

// Update pagination
function updatePagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    const start = (pagination.page - 1) * pagination.limit + 1;
    const end = Math.min(pagination.page * pagination.limit, pagination.total);

    info.textContent = `Showing ${start} to ${end} of ${pagination.total} results`;

    let paginationHtml = '';

    // Previous button
    if (pagination.page > 1) {
        paginationHtml += `
            <button onclick="loadProcurementRequests(${pagination.page - 1})"
                    class="px-3 py-1 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Previous
            </button>
        `;
    }

    // Page numbers
    const startPage = Math.max(1, pagination.page - 2);
    const endPage = Math.min(pagination.pages, pagination.page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === pagination.page;
        paginationHtml += `
            <button onclick="loadProcurementRequests(${i})"
                    class="px-3 py-1 ${isActive ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50'} border border-gray-300 rounded-md">
                ${i}
            </button>
        `;
    }

    // Next button
    if (pagination.page < pagination.pages) {
        paginationHtml += `
            <button onclick="loadProcurementRequests(${pagination.page + 1})"
                    class="px-3 py-1 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Next
            </button>
        `;
    }

    controls.innerHTML = paginationHtml;
}

// Utility functions
function getStatusColor(status) {
    const colors = {
        'draft': 'bg-gray-100 text-gray-800',
        'submitted': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'ordered': 'bg-blue-100 text-blue-800',
        'received': 'bg-purple-100 text-purple-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function getPriorityColor(priority) {
    const colors = {
        'low': 'bg-green-100 text-green-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
}

function getTypeColor(type) {
    const colors = {
        'asset': 'bg-blue-100 text-blue-800',
        'supply': 'bg-green-100 text-green-800',
        'service': 'bg-purple-100 text-purple-800'
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
}

function formatCurrency(amount) {
    return '₱' + parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Modal functions (assuming they exist in api.js or main.js)
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Notification functions (assuming they exist in api.js or main.js)
function showSuccess(message) {
    // Implementation depends on your notification system
    alert('Success: ' + message);
}

function showError(message) {
    // Implementation depends on your notification system
    alert('Error: ' + message);
}

function showLoading() {
    // Implementation depends on your loading system
    console.log('Loading...');
}

function hideLoading() {
    // Implementation depends on your loading system
    console.log('Loading complete');
}