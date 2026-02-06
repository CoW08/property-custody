// Procurement Management JavaScript

let currentPage = 1;
let currentFilters = {};
let currentUserRole = null;
let itemIndexCounter = 0;
let supplyCatalog = [];
let supplySelectMarkup = '<option value="">Loading inventory...</option>';

const VENDOR_CONTACTS = [
    {
        id: 'clinic-medical-supplies',
        category: 'Clinic / Medical Supplies',
        name: 'Philippine Medical Supplies',
        phone: '+63 2 8742-9228',
        email: 'support@philmedicalsupplies.com',
        address: 'Quezon City & other branches, Metro Manila, Philippines'
    },
    {
        id: 'library-school-supplies',
        category: 'Library / School Supplies',
        name: 'Supplies.com.ph',
        phone: '+63 917-519-7291',
        email: 'info@supplies.com.ph',
        address: 'Unit 206, PM Apartments, 24 Matalino St, Diliman, Quezon City, Metro Manila, Philippines'
    },
    {
        id: 'school-event-office',
        category: 'School Event / Office Supplies',
        name: 'OfficeWorks Quezon City',
        phone: '+63 2 8535-7333',
        email: 'sales@officeworks.ph',
        address: 'No. 47 Kamias Rd, Diliman, Quezon City, 1102 Metro Manila, Philippines'
    },
    {
        id: 'disaster-preparedness',
        category: 'Disaster & Preparedness Supplies',
        name: 'iCare Philippines Medical Supply',
        phone: '+63 917-520-7999',
        email: 'cs@icareph.com',
        address: '1230 Bambang St., Brgy. 254, Manila, Metro Manila, Philippines'
    }
];

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Get current user role from session
    getCurrentUserRole();
    initVendorDirectory();
    loadSupplyCatalog();
    loadProcurementStats();
    loadProcurementRequests();
    setupEventListeners();
    setDefaultValues();
});

function initVendorDirectory() {
    const selector = document.getElementById('vendorSelector');
    if (!selector) return;

    const options = ['<option value="">Select vendor</option>']
        .concat(VENDOR_CONTACTS.map(vendor => `<option value="${vendor.id}">${vendor.category} - ${vendor.name}</option>`))
        .join('');

    selector.innerHTML = options;
    selector.addEventListener('change', (event) => {
        const vendor = VENDOR_CONTACTS.find(v => v.id === event.target.value);
        if (!vendor) {
            return;
        }
        setVendorFields({
            category: vendor.category,
            name: vendor.name,
            email: vendor.email,
            phone: vendor.phone,
            address: vendor.address
        });
    });
}

function setVendorFields({ category, name, email, phone, address } = {}) {
    const categoryInput = document.getElementById('vendorCategory');
    const nameInput = document.getElementById('vendorName');
    const emailInput = document.getElementById('vendorEmail');
    const phoneInput = document.getElementById('vendorPhone');
    const addressInput = document.getElementById('vendorAddress');

    if (categoryInput && category !== undefined) categoryInput.value = category || '';
    if (nameInput && name !== undefined) nameInput.value = name || '';
    if (emailInput && email !== undefined) emailInput.value = email || '';
    if (phoneInput && phone !== undefined) phoneInput.value = phone || '';
    if (addressInput && address !== undefined) addressInput.value = address || '';
}

function clearVendorFields() {
    setVendorFields({
        category: '',
        name: '',
        email: '',
        phone: '',
        address: ''
    });
    const selector = document.getElementById('vendorSelector');
    if (selector) {
        selector.value = '';
    }
}

// Get current user role
function getCurrentUserRole() {

    const savedUser = sessionStorage.getItem('currentUser');
    if (savedUser) {
        const user = JSON.parse(savedUser);
        currentUserRole = user.role;
    }
    return currentUserRole;
}

async function loadSupplyCatalog() {
    try {
        const supplies = await API.getSupplies();
        supplyCatalog = Array.isArray(supplies)
            ? supplies
            : (Array.isArray(supplies?.data) ? supplies.data : []);
        supplySelectMarkup = buildSupplySelectMarkup();
    } catch (error) {
        console.error('Error loading supply catalog:', error);
        supplyCatalog = [];
        supplySelectMarkup = '<option value="">Unable to load inventory</option>';
    } finally {
        refreshSupplySelectors();
    }
}

function buildSupplySelectMarkup() {
    if (!supplyCatalog.length) {
        return '<option value="">No inventory items available</option>';
    }

    const options = supplyCatalog.map(item => {
        const code = item.item_code ? `${item.item_code} - ` : '';
        const label = `${code}${item.name || 'Unnamed Item'}`;
        return `<option value="${item.id}">${label}</option>`;
    }).join('');

    return `<option value="">Select inventory item</option>${options}`;
}

function refreshSupplySelectors() {
    const options = supplySelectMarkup;
    document.querySelectorAll('.item-supply-select').forEach(select => {
        const previousValue = select.value;
        select.innerHTML = options;
        select.disabled = supplyCatalog.length === 0;
        if (previousValue) {
            select.value = previousValue;
        }
    });
}

function handleSupplySelection(index, supplyId) {
    const supplyIdInput = document.querySelector(`input[name="items[${index}][supply_id]"]`);
    if (supplyIdInput) {
        supplyIdInput.value = supplyId || '';
    }

    if (!supplyId) {
        return;
    }

    const supply = supplyCatalog.find(item => String(item.id) === String(supplyId));
    if (!supply) {
        return;
    }

    const itemName = supply.name || supply.item_code || '';
    const combinedName = supply.item_code && supply.name
        ? `${supply.item_code} - ${supply.name}`
        : itemName;

    setItemFields(index, {
        item_name: combinedName,
        quantity: 1,
        unit: supply.unit || 'piece',
        estimated_unit_cost: supply.unit_cost != null ? Number(supply.unit_cost) : '',
        total_cost: supply.unit_cost != null ? Number(supply.unit_cost) : '',
        description: supply.description || '',
        specifications: supply.category ? `Category: ${supply.category}` : ''
    });

    calculateItemTotal(index);
}

// View request details
async function viewRequest(id) {
    try {
        showLoading();

        const response = await fetch(`api/procurement.php?action=details&id=${id}`);
        const result = await response.json();

        if (!result.success) {
            showError(result.error || 'Failed to fetch request details');
            return;
        }

        const request = result.data;
        const container = document.getElementById('requestDetailsContent');
        if (!container) {
            showError('Details container not found');
            return;
        }

        const itemsHtml = (request.items && request.items.length)
            ? `
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Item</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Qty</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Unit</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Unit Cost</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        ${request.items.map(item => `
                            <tr>
                                <td class="px-4 py-2">
                                    <p class="font-medium text-gray-900">${item.item_name}</p>
                                    ${item.description ? `<p class="text-xs text-gray-500">${item.description}</p>` : ''}
                                </td>
                                <td class="px-4 py-2">${item.quantity || 0}</td>
                                <td class="px-4 py-2">${item.unit || 'pcs'}</td>
                                <td class="px-4 py-2">${formatCurrency(item.estimated_unit_cost || 0)}</td>
                                <td class="px-4 py-2 font-semibold text-gray-900">${formatCurrency(item.total_cost || 0)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `
            : '<p class="text-sm text-gray-500">No items recorded for this request.</p>';

        const vendorDetails = request.vendor_name || request.vendor_email || request.vendor_phone || request.vendor_address
            ? `
                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                        <p class="text-xs uppercase font-semibold text-amber-600 mb-2">Vendor</p>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd>${request.vendor_category || matchVendorPreset(request.vendor_name, request.vendor_phone, request.vendor_email)?.category || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd class="font-semibold text-gray-900">${request.vendor_name || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>${request.vendor_email || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>${request.vendor_phone || 'N/A'}</dd></div>
                            <div><dt class="text-gray-500 text-sm">Address</dt><dd class="text-gray-900">${request.vendor_address || 'N/A'}</dd></div>
                        </dl>
                    </div>
                </section>
            `
            : '';

        container.innerHTML = `
            <div class="space-y-6 py-4">
                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <p class="text-xs uppercase font-semibold text-blue-600 mb-2">Request Summary</p>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Request Code</dt><dd class="font-semibold text-gray-900">${request.request_code}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd>${capitalizeFirst(request.request_type)}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>${capitalizeFirst(request.status)}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Priority</dt><dd>${capitalizeFirst(request.priority)}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Request Date</dt><dd>${formatDate(request.request_date)}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Required Date</dt><dd>${request.required_date ? formatDate(request.required_date) : 'N/A'}</dd></div>
                        </dl>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-xs uppercase font-semibold text-gray-600 mb-2">Requester</p>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd class="font-semibold text-gray-900">${request.requestor_name || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Department</dt><dd>${request.requestor_department || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>${request.requestor_email || 'N/A'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Role</dt><dd>${capitalizeFirst(request.requestor_role || 'N/A')}</dd></div>
                        </dl>
                    </div>
                </section>

                ${vendorDetails}

                <section>
                    <p class="text-sm font-semibold text-gray-900 mb-2">Justification</p>
                    <p class="text-sm text-gray-700 bg-white border border-gray-200 rounded-lg p-3">${request.justification || 'No justification provided.'}</p>
                </section>

                <section>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-900">Requested Items</p>
                        <p class="text-sm text-gray-500">Estimated Total: <span class="font-semibold text-gray-900">${formatCurrency(request.estimated_cost || request.total_estimated_cost || 0)}</span></p>
                    </div>
                    <div class="overflow-x-auto border border-gray-200 rounded-xl">${itemsHtml}</div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white border border-gray-200 rounded-xl p-4">
                        <p class="text-xs uppercase font-semibold text-gray-600 mb-2">Approval</p>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Approver</dt><dd>${request.approver_name || 'Pending'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Approval Date</dt><dd>${request.approval_date ? formatDate(request.approval_date) : 'Pending'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Approved Cost</dt><dd>${request.approved_cost ? formatCurrency(request.approved_cost) : 'Pending'}</dd></div>
                        </dl>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-4">
                        <p class="text-xs uppercase font-semibold text-gray-600 mb-2">Notes</p>
                        <p class="text-sm text-gray-700">${request.notes || 'No additional notes.'}</p>
                    </div>
                </section>
            </div>
        `;

        openModal('viewRequestModal');
    } catch (error) {
        console.error('Error loading request details:', error);
        showError('Error loading request details');
    } finally {
        hideLoading();
    }
}

async function submitProcurementRequest(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const editId = form.dataset.editId;
        const isEdit = !!editId;

        const formData = new FormData(form);
        const vendorName = (formData.get('vendor_name') || '').trim();
        if (!vendorName) {
            showError('Vendor name is required');
            return;
        }

        const requestData = {
            request_type: formData.get('request_type'),
            department: formData.get('department'),
            request_date: formData.get('request_date'),
            required_date: formData.get('required_date'),
            justification: formData.get('justification'),
            priority: formData.get('priority'),
            notes: formData.get('notes'),
            vendor_name: vendorName,
            vendor_email: (formData.get('vendor_email') || '').trim() || null,
            vendor_phone: (formData.get('vendor_phone') || '').trim() || null,
            vendor_address: (formData.get('vendor_address') || '').trim() || null,
            items: []
        };

        if (isEdit) {
            requestData.id = parseInt(editId);
        }

        const itemRows = Array.from(document.querySelectorAll('.item-row'));
        if (!itemRows.length) {
            showError('Please add at least one item to the request');
            return;
        }

        for (const row of itemRows) {
            const index = row.dataset.index;
            const name = (formData.get(`items[${index}][item_name]`) || '').trim();
            if (!name) {
                showError('Each item must have a name');
                return;
            }

            requestData.items.push({
                item_name: name,
                quantity: parseInt(formData.get(`items[${index}][quantity]`)) || 1,
                unit: (formData.get(`items[${index}][unit]`) || 'piece').trim(),
                estimated_unit_cost: parseFloat(formData.get(`items[${index}][estimated_unit_cost]`)) || 0,
                description: (formData.get(`items[${index}][description]`) || '').trim(),
                specifications: (formData.get(`items[${index}][specifications]`) || '').trim(),
                supply_id: formData.get(`items[${index}][supply_id]`) || ''
            });
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
}

function setDefaultValues() {
    // Set current user ID (you should get this from session/auth)
    document.getElementById('requestorId').value = 1; // Default to admin for now

    // Reset item rows
    resetItemRows();
}

function resetItemRows() {
    const itemsContainer = document.getElementById('itemsContainer');
    itemsContainer.innerHTML = '';
    itemIndexCounter = 0;
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
            page: page
        });

        Object.entries(currentFilters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                params.set(key, value);
            }
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
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const type = document.getElementById('typeFilter').value;

    currentFilters = {};
    if (search) currentFilters.search = search;
    if (status) currentFilters.status = status;
    if (priority) currentFilters.priority = priority;
    if (type) currentFilters.request_type = type;

    loadProcurementRequests(1);
}

// Refresh data
function refreshData() {
    loadProcurementStats();
    loadProcurementRequests(currentPage);
}

// Add request item to form
function addRequestItem(itemData) {

    const container = document.getElementById('itemsContainer');
    if (!container) return;

    const itemIndex = itemIndexCounter++;

    const itemHtml = `
        <div class="item-row border border-gray-200 rounded-lg p-3 sm:p-4 mb-4" data-index="${itemIndex}">
            <div class="flex justify-between items-start mb-3 sm:mb-4">
                <h5 class="text-sm sm:text-md font-medium text-gray-900">Item ${itemIndex + 1}</h5>
                <button type="button" onclick="removeRequestItem(${itemIndex})" class="text-red-600 hover:text-red-900 p-1">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Inventory Item (optional)</label>
                <select class="item-supply-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                        data-index="${itemIndex}"
                        onchange="handleSupplySelection(${itemIndex}, this.value)">
                    ${supplySelectMarkup}
                </select>
                <input type="hidden" name="items[${itemIndex}][supply_id]" value="">
                <p class="text-xs text-gray-500 mt-1">Selecting an inventory item will auto-fill the fields below.</p>
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

    if (itemData) {
        setItemFields(itemIndex, itemData);
    }

    calculateItemTotal(itemIndex);
}

function setItemFields(index, item = {}) {
    const nameInput = document.querySelector(`input[name="items[${index}][item_name]"]`);
    const quantityInput = document.querySelector(`input[name="items[${index}][quantity]"]`);
    const unitInput = document.querySelector(`input[name="items[${index}][unit]"]`);
    const unitCostInput = document.querySelector(`input[name="items[${index}][estimated_unit_cost]"]`);
    const totalCostInput = document.querySelector(`input[name="items[${index}][total_cost]"]`);
    const descriptionInput = document.querySelector(`textarea[name="items[${index}][description]"]`);
    const specsInput = document.querySelector(`textarea[name="items[${index}][specifications]"]`);
    const supplyIdInput = document.querySelector(`input[name="items[${index}][supply_id]"]`);
    const supplySelect = document.querySelector(`.item-supply-select[data-index="${index}"]`);

    if (nameInput) nameInput.value = item.item_name || '';
    if (quantityInput) quantityInput.value = item.quantity || 1;
    if (unitInput) unitInput.value = item.unit || 'piece';
    if (unitCostInput) unitCostInput.value = item.estimated_unit_cost != null ? item.estimated_unit_cost : '';
    if (totalCostInput) totalCostInput.value = item.total_cost != null ? item.total_cost : '';
    if (descriptionInput) descriptionInput.value = item.description || '';
    if (specsInput) specsInput.value = item.specifications || '';
    if (supplyIdInput && item.supply_id) supplyIdInput.value = item.supply_id;
    if (supplySelect && item.supply_id) supplySelect.value = item.supply_id;
}

function calculateItemTotal(index) {
    const quantityInput = document.querySelector(`input[name="items[${index}][quantity]"]`);
    const unitCostInput = document.querySelector(`input[name="items[${index}][estimated_unit_cost]"]`);
    const totalCostInput = document.querySelector(`input[name="items[${index}][total_cost]"]`);

    if (!quantityInput || !unitCostInput || !totalCostInput) {
        calculateTotalCost();
        return;
    }

    const quantity = parseFloat(quantityInput.value) || 0;
    const unitCost = parseFloat(unitCostInput.value) || 0;
    const total = quantity * unitCost;

    totalCostInput.value = total.toFixed(2);
    calculateTotalCost();
}

function calculateTotalCost() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const index = row.dataset.index;
        const totalInput = document.querySelector(`input[name="items[${index}][total_cost]"]`);
        total += parseFloat(totalInput?.value) || 0;
    });

    const totalField = document.getElementById('estimatedCostDisplay');
    if (totalField) {
        totalField.textContent = formatCurrency(total);
    }
}

// Remove request item
function removeRequestItem(index) {
    const itemRow = document.querySelector(`[data-index="${index}"]`);
    if (itemRow) {
        itemRow.remove();
        calculateTotalCost();
    }

    if (!document.querySelector('.item-row')) {
        addRequestItem();
    }
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
            const container = document.getElementById('itemsContainer');
            container.innerHTML = '';
            itemIndexCounter = 0;

            if (request.items && request.items.length > 0) {
                request.items.forEach(item => {
                    addRequestItem();
                    setItemFields(itemIndexCounter - 1, {
                        item_name: item.item_name,
                        quantity: item.quantity,
                        unit: item.unit,
                        estimated_unit_cost: item.estimated_unit_cost,
                        total_cost: item.total_cost,
                        description: item.description,
                        specifications: item.specifications
                    });
                });
            } else {
                addRequestItem();
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
    const form = document.getElementById('newRequestForm');
    form.reset();
    delete form.dataset.editId;

    document.getElementById('requestDate').value = getCurrentDate();
    document.getElementById('requestPriority').value = 'medium';
    document.getElementById('requestorId').value = 1;
    clearVendorFields();

    resetItemRows();
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
