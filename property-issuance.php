<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Property Issuance - Property Custodian Management";

ob_start();
?>

<!-- Property Issuance Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden lg:ml-64">
        <!-- Mobile Header -->
        <div class="lg:hidden bg-white shadow-sm border-b">
            <div class="flex items-center justify-between p-4">
                <h1 class="text-lg font-semibold text-gray-900">Property Issuance</h1>
                <button id="mobileSidebarToggle" class="p-2 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6 lg:p-8">
            <div class="hidden lg:flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 lg:mb-8 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Property Issuance</h1>
                    <p class="text-sm text-gray-500 mt-1">Issue, track, and recover assets assigned to personnel.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button id="newIssuanceBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200 text-sm sm:text-base shadow-sm">
                        <i class="fas fa-plus mr-2"></i>New Issuance
                    </button>
                </div>
            </div>

            <!-- Property Issuance Interface -->
            <div id="issuanceFormContainer" class="bg-white rounded-lg shadow p-4 sm:p-6 border border-gray-200 hidden">
                <div class="border-b border-gray-200 pb-4 mb-6 card-head rounded-t-lg">
                    <h2 class="text-xl font-semibold text-gray-900">Create New Property Issuance</h2>
                    <p class="text-sm text-gray-600 mt-1">Fill out the form below to issue property to a staff member</p>
                </div>
                <form id="issuanceForm">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 mb-6">
                        <!-- Item Selection -->
                        <div class="space-y-4">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Select Item</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Code</label>
                                    <input type="text" id="assetCode" name="asset_code" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter item code or scan QR">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                                    <select id="assetSelect" name="asset_id" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                                <div id="assetDetails" class="hidden bg-blue-50 border border-blue-200 p-3 rounded-md">
                                    <p class="text-xs sm:text-sm text-blue-900 mb-1"><strong>Description:</strong> <span id="assetDescription"></span></p>
                                    <p class="text-xs sm:text-sm text-blue-900 mb-1"><strong>Location:</strong> <span id="assetLocation"></span></p>
                                    <p class="text-xs sm:text-sm text-blue-900"><strong>Condition:</strong> <span id="assetCondition"></span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Recipient Information -->
                        <div class="space-y-4">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recipient Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                                    <input type="text" id="employeeId" name="employee_id" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter employee ID" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" id="recipientName" name="recipient_name" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter full name" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                    <select id="department" name="department" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Select Department</option>
                                        <option value="administration">Administration</option>
                                        <option value="academic">Academic Affairs</option>
                                        <option value="finance">Finance</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="it">Information Technology</option>
                                        <option value="library">Library</option>
                                        <option value="security">Security</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Issuance Details -->
                    <div class="space-y-4 mb-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">Issuance Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label>
                                <input type="date" id="issueDate" name="issue_date" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date</label>
                                <input type="date" id="expectedReturnDate" name="expected_return_date" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose/Remarks</label>
                            <textarea id="purpose" name="purpose" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Enter purpose or additional remarks"></textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4">
                        <button type="button" id="cancelBtn" class="w-full sm:w-auto px-4 sm:px-6 py-2 text-sm sm:text-base border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="w-full sm:w-auto px-4 sm:px-6 py-2 text-sm sm:text-base bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 shadow">
                            Issue Property
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Issuances -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6 mt-6 border border-gray-200">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-3">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Property Issuances</h3>
                    <button id="refreshIssuances" class="w-full sm:w-auto px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition duration-200 shadow-sm">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>
                <div class="overflow-x-auto -mx-4 sm:mx-0">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                            <thead class="bg-gray-50 text-xs">
                                <tr>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Item Name</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Department</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Issue Date</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Expected Return</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="issuancesTableBody" class="bg-white divide-y divide-gray-200">
                                <tr id="loadingRow">
                                    <td colspan="8" class="px-3 sm:px-6 py-4 text-center text-gray-500 text-sm">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading issuances...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'components/detail_modal.php'; ?>

<script src="js/api.js?v=<?php echo time(); ?>"></script>
<script src="js/detail_handlers.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug: Check if API class and methods are available
    console.log('API class:', API);
    console.log('API.getAvailableAssets:', typeof API.getAvailableAssets);
    console.log('API.getPropertyIssuances:', typeof API.getPropertyIssuances);

    // Debug: List all API methods
    console.log('All API methods:', Object.getOwnPropertyNames(API).filter(prop => typeof API[prop] === 'function'));

    // Fallback: Add missing methods if they don't exist
    if (typeof API.getAvailableAssets !== 'function') {
        console.log('Adding fallback getAvailableAssets method');
        API.getAvailableAssets = function() {
            return API.request('assets.php?status=available');
        };
    }

    if (typeof API.getPropertyIssuances !== 'function') {
        console.log('Adding fallback getPropertyIssuances method');
        API.getPropertyIssuances = function() {
            return API.request('property_issuance.php');
        };
    }


    if (typeof API.createPropertyIssuance !== 'function') {
        console.log('Adding fallback createPropertyIssuance method');
        API.createPropertyIssuance = function(issuanceData) {
            return API.request('property_issuance.php', {
                method: 'POST',
                body: JSON.stringify(issuanceData)
            });
        };
    }

    if (typeof API.updatePropertyIssuance !== 'function') {
        console.log('Adding fallback updatePropertyIssuance method');
        API.updatePropertyIssuance = function(id, issuanceData) {
            return API.request(`property_issuance.php?id=${id}`, {
                method: 'PUT',
                body: JSON.stringify(issuanceData)
            });
        };
    }

    if (typeof API.deletePropertyIssuance !== 'function') {
        console.log('Adding fallback deletePropertyIssuance method');
        API.deletePropertyIssuance = function(id) {
            return API.request(`property_issuance.php?id=${id}`, {
                method: 'DELETE'
            });
        };
    }

    // Form elements
    const issuanceForm = document.getElementById('issuanceForm');
    const formContainer = document.getElementById('issuanceFormContainer');
    const assetCodeInput = document.getElementById('assetCode');
    const assetSelect = document.getElementById('assetSelect');
    const assetDetails = document.getElementById('assetDetails');
    const issueDate = document.getElementById('issueDate');
    const cancelBtn = document.getElementById('cancelBtn');
    const submitBtn = document.getElementById('submitBtn');
    const refreshBtn = document.getElementById('refreshIssuances');
    // Initialize form hidden and preset date
    issueDate.value = new Date().toISOString().split('T')[0];
    hideIssuanceForm();

    // Load available assets
    loadAvailableAssets();

    // Load recent issuances
    loadRecentIssuances();

    // Asset code input handler
    assetCodeInput.addEventListener('input', function() {
        const code = this.value.trim();
        if (code) {
            const option = Array.from(assetSelect.options).find(opt =>
                opt.dataset.assetCode && opt.dataset.assetCode.toLowerCase() === code.toLowerCase()
            );
            if (option) {
                assetSelect.value = option.value;
                showAssetDetails(option);
            }
        }
    });

    // Asset select change handler
    assetSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            assetCodeInput.value = selectedOption.dataset.assetCode || '';
            showAssetDetails(selectedOption);
        } else {
            hideAssetDetails();
        }
    });

    // Form submission
    issuanceForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const selectedOption = assetSelect.options[assetSelect.selectedIndex];
        const requestItemDetails = {
            asset_code: selectedOption?.dataset?.assetCode || assetCodeInput.value || null,
            asset_name: selectedOption?.dataset?.assetName || null,
            description: selectedOption?.dataset?.description || null,
            location: selectedOption?.dataset?.location || null,
            condition_status: selectedOption?.dataset?.condition || null
        };
        const issuanceData = {
            asset_id: formData.get('asset_id'),
            employee_id: formData.get('employee_id'),
            recipient_name: formData.get('recipient_name'),
            department: formData.get('department'),
            issue_date: formData.get('issue_date'),
            expected_return_date: formData.get('expected_return_date') || null,
            purpose: formData.get('purpose') || null,
            requester_name: formData.get('recipient_name'),
            requester_department: formData.get('department'),
            request_submitted_at: new Date().toISOString(),
            request_item_details: requestItemDetails
        };

        // Validate required fields
        if (!issuanceData.asset_id || !issuanceData.employee_id || !issuanceData.recipient_name || !issuanceData.department) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            const response = await API.createPropertyIssuance(issuanceData);
            showNotification('Property issued successfully!', 'success');

            // Show PDF notification
            if (response.pdf_url) {
                setTimeout(() => {
                    const viewPDF = confirm('Issuance receipt is ready. View and print now?');
                    if (viewPDF) {
                        window.open(response.pdf_url, '_blank');
                    }
                }, 1000);
            }

            // Reset form
            issuanceForm.reset();
            issueDate.value = new Date().toISOString().split('T')[0];
            hideAssetDetails();
            hideIssuanceForm();

            // Reload data
            loadAvailableAssets();
            loadRecentIssuances();

        } catch (error) {
            console.error('Error creating issuance:', error);
            showNotification(error.message || 'Failed to issue property', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Issue Property';
        }
    });

    // Cancel button
    cancelBtn.addEventListener('click', function() {
        issuanceForm.reset();
        issueDate.value = new Date().toISOString().split('T')[0];
        hideAssetDetails();
        hideIssuanceForm();
    });

    // Refresh button
    refreshBtn.addEventListener('click', function() {
        loadRecentIssuances();
    });


    // New Issuance button
    const newIssuanceBtn = document.getElementById('newIssuanceBtn');
    if (newIssuanceBtn) {
        newIssuanceBtn.addEventListener('click', function() {
            if (!formContainer.classList.contains('hidden')) {
                hideIssuanceForm();
                return;
            }

            issuanceForm.reset();
            issueDate.value = new Date().toISOString().split('T')[0];
            hideAssetDetails();
            showIssuanceForm();

            formContainer.classList.add('ring-2', 'ring-green-500', 'ring-opacity-50');

            formContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            setTimeout(() => {
                document.getElementById('assetCode').focus();
                setTimeout(() => {
                    formContainer.classList.remove('ring-2', 'ring-green-500', 'ring-opacity-50');
                }, 2000);
            }, 500);

            showNotification('Ready to create new property issuance', 'success');
        });
    }

    // Functions
    function showIssuanceForm() {
        formContainer.classList.remove('hidden');
    }

    function hideIssuanceForm() {
        formContainer.classList.add('hidden');
        formContainer.classList.remove('ring-2', 'ring-green-500', 'ring-opacity-50');
    }
    async function loadAvailableAssets() {
        try {
            const response = await API.getAvailableAssets();
            const assets = response.assets || [];

            // Clear existing options (except the first one)
            assetSelect.innerHTML = '<option value="">Select Item</option>';

            assets.forEach(asset => {
                const option = document.createElement('option');
                option.value = asset.id;
                option.textContent = `${asset.asset_code} - ${asset.name}`;
                option.dataset.assetCode = asset.asset_code;
                option.dataset.assetName = asset.name || '';
                option.dataset.description = asset.description || '';
                option.dataset.location = asset.location || '';
                option.dataset.condition = asset.condition_status || '';
                assetSelect.appendChild(option);
            });

        } catch (error) {
            console.error('Error loading assets:', error);
            showNotification('Failed to load available assets', 'error');
        }
    }

    async function loadRecentIssuances() {
        try {
            const tableBody = document.getElementById('issuancesTableBody');
            tableBody.innerHTML = '<tr><td colspan="8" class="px-3 sm:px-6 py-4 text-center text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading issuances...</td></tr>';

            const response = await API.getPropertyIssuances();
            const issuances = response.issuances || [];

            if (issuances.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="px-3 sm:px-6 py-4 text-center text-gray-500 text-sm">No recent issuances</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            issuances.forEach(issuance => {
                const row = createIssuanceRow(issuance);
                tableBody.appendChild(row);
            });

        } catch (error) {
            console.error('Error loading issuances:', error);
            const tableBody = document.getElementById('issuancesTableBody');
            tableBody.innerHTML = '<tr><td colspan="8" class="px-3 sm:px-6 py-4 text-center text-red-500 text-sm">Failed to load issuances</td></tr>';
        }
    }

    function createIssuanceRow(issuance) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';

        const statusClass = getStatusClass(issuance.status);
        const statusText = getStatusText(issuance.status);

        row.innerHTML = `
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">${issuance.asset_code || 'N/A'}</td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden sm:table-cell">${issuance.asset_name || 'N/A'}</td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                <div>
                    <div class="font-medium">${issuance.recipient_name}</div>
                    <div class="text-xs text-gray-500 sm:hidden">${issuance.asset_name || 'N/A'}</div>
                    <div class="text-xs text-gray-500 lg:hidden">${issuance.department || 'N/A'}</div>
                    <div class="text-xs text-gray-500 md:hidden">${formatDate(issuance.issue_date)}</div>
                </div>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden lg:table-cell">${issuance.department || 'N/A'}</td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden md:table-cell">${formatDate(issuance.issue_date)}</td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900 hidden xl:table-cell">${formatDate(issuance.expected_return_date)}</td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-1 sm:space-x-2">
                    <button onclick="viewIssuanceDetails(${issuance.id})" class="text-blue-600 hover:text-blue-900 p-1" title="View Details">
                        <i class="fas fa-eye text-xs sm:text-sm"></i>
                    </button>
                    <button onclick="downloadIssuancePDF(${issuance.id})" class="text-green-600 hover:text-green-700 p-1" title="Download Receipt">
                        <i class="fas fa-file-pdf text-sm sm:text-base"></i>
                    </button>
                    ${issuance.status === 'issued' ?
                        `<button onclick="returnProperty(${issuance.id})" class="text-purple-600 hover:text-purple-900 p-1" title="Mark as Returned">
                            <i class="fas fa-undo text-xs sm:text-sm"></i>
                        </button>` : ''
                    }
                    <button onclick="deleteIssuance(${issuance.id})" class="text-red-600 hover:text-red-900 p-1" title="Delete">
                        <i class="fas fa-trash text-xs sm:text-sm"></i>
                    </button>
                </div>
            </td>
        `;

        return row;
    }

    function showAssetDetails(option) {
        document.getElementById('assetDescription').textContent = option.dataset.description || 'N/A';
        document.getElementById('assetLocation').textContent = option.dataset.location || 'N/A';
        document.getElementById('assetCondition').textContent = option.dataset.condition || 'N/A';
        assetDetails.classList.remove('hidden');
    }

    function hideAssetDetails() {
        assetDetails.classList.add('hidden');
    }

    function getStatusClass(status) {
        switch (status) {
            case 'issued': return 'bg-blue-100 text-blue-800';
            case 'returned': return 'bg-green-100 text-green-800';
            case 'overdue': return 'bg-red-100 text-red-800';
            case 'damaged': return 'bg-yellow-100 text-yellow-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    function getStatusText(status) {
        switch (status) {
            case 'issued': return 'Issued';
            case 'returned': return 'Returned';
            case 'overdue': return 'Overdue';
            case 'damaged': return 'Damaged';
            default: return status;
        }
    }
});

// Global functions for row actions
async function returnProperty(issuanceId) {
    if (!confirm('Mark this property as returned?')) return;

    try {
        await API.updatePropertyIssuance(issuanceId, { status: 'returned' });
        showNotification('Property marked as returned', 'success');
        document.getElementById('refreshIssuances').click();
    } catch (error) {
        console.error('Error returning property:', error);
        showNotification('Failed to return property', 'error');
    }
}

async function editIssuance(issuanceId) {
    // This would open an edit modal or navigate to edit page
    showNotification('Edit functionality not implemented yet', 'warning');
}

async function deleteIssuance(issuanceId) {
    if (!confirm('Are you sure you want to delete this issuance record?')) return;

    try {
        await API.deletePropertyIssuance(issuanceId);
        showNotification('Issuance record deleted', 'success');
        document.getElementById('refreshIssuances').click();
    } catch (error) {
        console.error('Error deleting issuance:', error);
        showNotification('Failed to delete issuance record', 'error');
    }
}

// Download issuance PDF
function downloadIssuancePDF(issuanceId) {
    window.open(`generate_issuance_pdf.php?issuance_id=${issuanceId}`, '_blank');
}

// View issuance details
async function viewIssuanceDetails(issuanceId) {
    try {
        const response = await API.getPropertyIssuance(issuanceId);
        const issuance = response.issuance || response;
        
        if (!issuance) {
            throw new Error('Issuance not found');
        }
        
        const statusColors = {
            'issued': 'warning',
            'returned': 'success',
            'overdue': 'danger',
            'damaged': 'warning'
        };
        
        const content = `
            ${createDetailSection('Item Information', [
                { label: 'Item Code', value: issuance.asset_code },
                { label: 'Item Name', value: issuance.asset_name || 'N/A' },
                { label: 'Category', value: issuance.category || 'N/A' },
                { label: 'Item Condition', value: issuance.condition_status || 'N/A' }
            ])}
            
            ${createDetailSection('Recipient Information', [
                { label: 'Employee ID', value: issuance.employee_id || 'N/A' },
                { label: 'Recipient Name', value: issuance.recipient_name },
                { label: 'Department', value: issuance.department || 'N/A' },
                { label: 'Position', value: issuance.position || 'N/A' }
            ])}
            
            ${createDetailSection('Issuance Details', [
                { label: 'Issuance Date', value: formatDate(issuance.issue_date) },
                { label: 'Expected Return', value: issuance.expected_return_date ? formatDate(issuance.expected_return_date) : 'Not specified' },
                { label: 'Actual Return', value: issuance.actual_return_date ? formatDate(issuance.actual_return_date) : 'Not returned' },
                { label: 'Status', value: createStatusBadge(issuance.status?.toUpperCase() || 'N/A', statusColors[issuance.status] || 'default') }
            ])}
            
            ${issuance.purpose ? `
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Purpose/Remarks</h4>
                <p class="text-gray-700">${issuance.purpose}</p>
            </div>
            ` : ''}
            
            ${createDetailSection('Issued By', [
                { label: 'Issued By', value: issuance.issued_by_name || 'System' },
                { label: 'Created At', value: formatDate(issuance.created_at) }
            ])}
        `;
        
        const footer = `
            <button onclick="window.open('generate_issuance_pdf.php?issuance_id=${issuance.id}', '_blank')" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-file-pdf mr-2"></i>Download Receipt
            </button>
            ${issuance.status === 'issued' ? `
                <button onclick="returnProperty(${issuance.id}); closeDetailModal();" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-undo mr-2"></i>Mark as Returned
                </button>
            ` : ''}
            <button onclick="closeDetailModal()" 
                    class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        `;
        
        openDetailModal('Property Issuance: ' + issuance.asset_code, content, footer);
        
    } catch (error) {
        console.error('Error loading issuance details:', error);
        showNotification('Failed to load issuance details', 'error');
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
