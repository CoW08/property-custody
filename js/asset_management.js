// Asset Management JavaScript Functions

// Global variables
let currentAssets = [];
let allTags = [];
let allCategories = [];
let selectedAssetTags = [];
let bulkActionModal = null;
let bulkActionForm = null;
let bulkActionSection = null;
let bulkSelectedCount = null;
let currentPagination = {
    current_page: 1,
    total_pages: 1,
    total_items: 0,
    per_page: 10,
    has_next: false,
    has_previous: false
};

// Initialize Asset Management
function initAssetManagement() {
    loadCategories();
    loadTags();
    loadAssets();
    setupEventListeners();
}

function updateBulkSelectionState() {
    const checkboxes = document.querySelectorAll('input[name="assetSelect"]');
    const selected = document.querySelectorAll('input[name="assetSelect"]:checked');
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = selected.length > 0 && selected.length === checkboxes.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
    }

    if (bulkSelectedCount) {
        bulkSelectedCount.textContent = `${selected.length}`;
        bulkActionSection.classList.toggle('hidden', selected.length === 0);
    }

    return Array.from(selected).map(cb => parseInt(cb.value, 10));
}

// Setup Event Listeners
function setupEventListeners() {
    // Search functionality
    document.getElementById('searchAssets').addEventListener('input', debounce(filterAssets, 300));

    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="assetSelect"]');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBulkSelectionState();
    });
}

// Debounce function for search
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

// Load Categories
async function loadCategories() {
    try {
        const response = await fetch('./api/asset_categories.php');
        if (response.ok) {
            allCategories = await response.json();
            populateCategoryFilters();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Load Tags
async function loadTags() {
    try {
        const response = await fetch('./api/asset_tags.php');
        if (response.ok) {
            allTags = await response.json();
            populateTagFilters();
            populateTagsList();
        }
    } catch (error) {
        console.error('Error loading tags:', error);
    }
}

// Load Assets
async function loadAssets(filters = {}, page = 1, limit = 10) {
    try {
        let url = './api/assets.php';
        const params = new URLSearchParams();

        if (filters.search) params.append('search', filters.search);
        if (filters.category) params.append('category', filters.category);
        if (filters.status) params.append('status', filters.status);
        if (filters.tag) params.append('tag', filters.tag);

        // Add pagination parameters
        params.append('page', page);
        params.append('limit', limit);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await fetch(url);
        if (response.ok) {
            const data = await response.json();

            // Handle both old format (array) and new format (object with assets and pagination)
            if (Array.isArray(data)) {
                // Old format - no pagination
                currentAssets = data;
                currentPagination = {
                    current_page: 1,
                    total_pages: 1,
                    total_items: data.length,
                    per_page: data.length,
                    has_next: false,
                    has_previous: false
                };
            } else {
                // New format with pagination
                currentAssets = data.assets || [];
                currentPagination = data.pagination || {
                    current_page: 1,
                    total_pages: 1,
                    total_items: 0,
                    per_page: limit,
                    has_next: false,
                    has_previous: false
                };
            }

            renderAssetsTable();
            renderPagination();
        } else {
            console.error('Failed to load assets:', response.status);
            showNotification('Failed to load assets', 'error');
        }
    } catch (error) {
        console.error('Error loading assets:', error);
        showNotification('Error loading assets: ' + error.message, 'error');
    }
}

// Populate Category Filters
function populateCategoryFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    const assetCategory = document.getElementById('assetCategory');

    // Clear existing options except first
    categoryFilter.innerHTML = '<option value="">All Categories</option>';
    assetCategory.innerHTML = '<option value="">Select Category</option>';

    allCategories.forEach(category => {
        categoryFilter.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        assetCategory.innerHTML += `<option value="${category.id}">${category.name}</option>`;
    });
}

// Populate Tag Filters
function populateTagFilters() {
    const tagFilter = document.getElementById('tagFilter');
    const availableTags = document.getElementById('availableTags');

    // Clear existing options except first
    tagFilter.innerHTML = '<option value="">All Tags</option>';
    availableTags.innerHTML = '<option value="">Select a tag to add</option>';

    allTags.forEach(tag => {
        tagFilter.innerHTML += `<option value="${tag.id}">${tag.name}</option>`;
        availableTags.innerHTML += `<option value="${tag.id}">${tag.name}</option>`;
    });
}

// Populate Tags List for Management
function populateTagsList() {
    const tagsList = document.getElementById('tagsList');

    if (!allTags || allTags.length === 0) {
        tagsList.innerHTML = '<p class="text-gray-500 text-center py-4">No tags found</p>';
        return;
    }

    tagsList.innerHTML = allTags.map(tag => `
        <div class="flex items-center justify-between p-3 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <span class="inline-block w-4 h-4 rounded" style="background-color: ${tag.color}"></span>
                <div>
                    <span class="font-medium">${tag.name}</span>
                    ${tag.description ? `<p class="text-sm text-gray-500">${tag.description}</p>` : ''}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">${tag.usage_count || 0} assets</span>
                <button onclick="deleteTag(${tag.id})" class="text-red-600 hover:text-red-800"
                        ${(tag.usage_count && tag.usage_count > 0) ? 'disabled title="Cannot delete tag in use"' : ''}>
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Render Assets Table
function renderAssetsTable() {
    const tbody = document.getElementById('assetsTableBody');

    if (!currentAssets || currentAssets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="px-6 py-4 text-center text-gray-500">No assets found</td></tr>';
        updateBulkSelectionState();
        return;
    }

    tbody.innerHTML = currentAssets.map(asset => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" name="assetSelect" value="${asset.id}" class="rounded" onchange="updateBulkSelectionState()">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${asset.asset_code}</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">${asset.name}</div>
                ${asset.description ? `<div class="text-sm text-gray-500">${asset.description}</div>` : ''}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">${asset.category_name || 'N/A'}</span>
            </td>
            <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                    ${renderAssetTags(asset.tags)}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeClass(asset.status)}">
                    ${asset.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${asset.location || 'N/A'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                ${asset.qr_generated && asset.qr_code ?
                    `<button onclick="showQRCode(${asset.id})" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-qrcode"></i>
                    </button>` :
                    `<button onclick="generateQRCode(${asset.id})" class="text-gray-400 hover:text-blue-600" title="Generate QR Code">
                        <i class="fas fa-qrcode"></i>
                    </button>`
                }
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ₱${asset.current_value ? parseFloat(asset.current_value).toLocaleString() : 'N/A'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex gap-2">
                    <button onclick="viewAssetDetails(${asset.id})" class="text-blue-600 hover:text-blue-900" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editAsset(${asset.id})" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="archiveAsset(${asset.id})" class="text-orange-600 hover:text-orange-900" title="Archive">
                        <i class="fas fa-archive"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    updateBulkSelectionState();
}

// Render Asset Tags
function renderAssetTags(tags) {
    if (!tags || tags.length === 0) {
        return '<span class="text-gray-400 text-xs">No tags</span>';
    }

    return tags.map(tag => `
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
              style="background-color: ${tag.color}15; color: ${tag.color}; border: 1px solid ${tag.color}40;">
            ${tag.name}
        </span>
    `).join('');
}

// Get Status Badge Class
function getStatusBadgeClass(status) {
    const classes = {
        'available': 'bg-green-100 text-green-800',
        'assigned': 'bg-blue-100 text-blue-800',
        'maintenance': 'bg-yellow-100 text-yellow-800',
        'damaged': 'bg-red-100 text-red-800',
        'lost': 'bg-gray-100 text-gray-800',
        'disposed': 'bg-purple-100 text-purple-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

// Filter Assets
function filterAssets() {
    const filters = {
        search: document.getElementById('searchAssets').value,
        category: document.getElementById('categoryFilter').value,
        status: document.getElementById('statusFilter').value,
        tag: document.getElementById('tagFilter').value
    };

    // Reset to page 1 when filtering
    loadAssets(filters, 1, currentPagination.per_page);
}

// Render Pagination
function renderPagination() {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    const mobilePagination = document.getElementById('mobilePagination');

    if (!paginationInfo || !paginationControls) return;

    // Update pagination info
    const startItem = currentPagination.total_items === 0 ? 0 : ((currentPagination.current_page - 1) * currentPagination.per_page) + 1;
    const endItem = Math.min(currentPagination.current_page * currentPagination.per_page, currentPagination.total_items);

    paginationInfo.innerHTML = `
        Showing <span class="font-medium">${startItem}</span> to
        <span class="font-medium">${endItem}</span> of
        <span class="font-medium">${currentPagination.total_items}</span> results
    `;

    // Generate page numbers to show
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPagination.current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(currentPagination.total_pages, startPage + maxVisiblePages - 1);

    // Adjust start page if we're near the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // Build pagination controls
    let paginationHTML = '';

    // Previous button
    paginationHTML += `
        <button onclick="goToPage(${currentPagination.current_page - 1})"
                ${!currentPagination.has_previous ? 'disabled' : ''}
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${!currentPagination.has_previous ? 'cursor-not-allowed opacity-50' : ''}">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPagination.current_page;
        paginationHTML += `
            <button onclick="goToPage(${i})"
                    class="${isActive
                        ? 'bg-blue-50 border-blue-500 text-blue-600'
                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}
                    relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                ${i}
            </button>
        `;
    }

    // Next button
    paginationHTML += `
        <button onclick="goToPage(${currentPagination.current_page + 1})"
                ${!currentPagination.has_next ? 'disabled' : ''}
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${!currentPagination.has_next ? 'cursor-not-allowed opacity-50' : ''}">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    paginationControls.innerHTML = paginationHTML;

    // Update mobile pagination
    if (mobilePagination) {
        mobilePagination.innerHTML = `
            <button onclick="goToPage(${currentPagination.current_page - 1})"
                    ${!currentPagination.has_previous ? 'disabled' : ''}
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${!currentPagination.has_previous ? 'cursor-not-allowed opacity-50' : ''}">
                Previous
            </button>
            <button onclick="goToPage(${currentPagination.current_page + 1})"
                    ${!currentPagination.has_next ? 'disabled' : ''}
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${!currentPagination.has_next ? 'cursor-not-allowed opacity-50' : ''}">
                Next
            </button>
        `;
    }
}

// Go to specific page
function goToPage(page) {
    if (page < 1 || page > currentPagination.total_pages) return;

    const filters = {
        search: document.getElementById('searchAssets').value,
        category: document.getElementById('categoryFilter').value,
        status: document.getElementById('statusFilter').value,
        tag: document.getElementById('tagFilter').value
    };

    loadAssets(filters, page, currentPagination.per_page);
}

// Modal Functions
window.App = window.App || {};

App.openAssetModal = function(assetId = null) {
    selectedAssetTags = [];

    // Show modal first
    document.getElementById('assetModal').classList.remove('hidden');

    if (assetId) {
        // Edit mode
        document.getElementById('assetModalTitle').textContent = 'Edit Asset';
        document.getElementById('assetSubmitText').textContent = 'Update Asset';

        // Clear form first to show loading state
        document.getElementById('assetForm').reset();
        document.getElementById('assetId').value = assetId;

        // Load asset data
        loadAssetForEdit(assetId);
    } else {
        // Add mode
        document.getElementById('assetModalTitle').textContent = 'Add Asset';
        document.getElementById('assetSubmitText').textContent = 'Save Asset';
        document.getElementById('assetForm').reset();
        document.getElementById('assetId').value = '';
        updateSelectedTagsDisplay();
    }
};

App.closeAssetModal = function() {
    document.getElementById('assetModal').classList.add('hidden');
};

App.openTagModal = function() {
    document.getElementById('tagModal').classList.remove('hidden');
    loadTags(); // Refresh tags list
};

App.closeTagModal = function() {
    document.getElementById('tagModal').classList.add('hidden');
};

App.closeQRModal = function() {
    document.getElementById('qrModal').classList.add('hidden');
};

// Asset CRUD Operations
async function handleAssetSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const assetData = Object.fromEntries(formData);
    const assetId = assetData.asset_id;

    try {
        const method = assetId ? 'PUT' : 'POST';
        const url = assetId ? `./api/assets.php?id=${assetId}` : './api/assets.php';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(assetData)
        });

        const responseText = await response.text();
        console.log('Raw API response:', responseText);
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', responseText);
            throw new Error('Server returned invalid response: ' + responseText.substring(0, 200));
        }

        if (response.ok) {
            showNotification(result.message, 'success');
            App.closeAssetModal();

            // Show loading indicator
            showNotification('Refreshing asset list...', 'info');

            // Handle tag assignments for new assets
            try {
                if (!assetId && selectedAssetTags.length > 0) {
                    await assignTagsToAsset(result.id, selectedAssetTags);
                } else if (assetId) {
                    // Always update tags for existing assets (even if empty to remove existing tags)
                    await updateAssetTags(assetId, selectedAssetTags);
                }
            } catch (tagError) {
                console.error('Tag operation failed:', tagError);
                showNotification('Asset saved, but tag update failed', 'warning');
            }

            const filters = {
                search: document.getElementById('searchAssets').value,
                category: document.getElementById('categoryFilter').value,
                status: document.getElementById('statusFilter').value,
                tag: document.getElementById('tagFilter').value
            };
            await loadAssets(filters, currentPagination.current_page, currentPagination.per_page);

            // Highlight the updated row temporarily
            if (assetId) {
                highlightUpdatedAsset(assetId);
            }

            showNotification('Asset list updated successfully!', 'success');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error saving asset:', error);
        showNotification('Error saving asset: ' + error.message, 'error');
    }
}

// Load Asset for Edit
async function loadAssetForEdit(assetId) {
    try {
        showNotification('Loading asset data...', 'info');
        const response = await fetch(`./api/assets.php?id=${assetId}`);

        if (response.ok) {
            const asset = await response.json();
    
            // Populate form fields with proper validation
            document.getElementById('assetId').value = asset.id || '';
            document.getElementById('assetCode').value = asset.asset_code || '';
            document.getElementById('assetName').value = asset.name || '';
            document.getElementById('assetDescription').value = asset.description || '';
            document.getElementById('assetCategory').value = asset.category || '';
            document.getElementById('assetStatus').value = asset.status || 'available';
            document.getElementById('assetLocation').value = asset.location || '';
            document.getElementById('assetPurchaseDate').value = asset.purchase_date || '';
            document.getElementById('assetPurchaseCost').value = asset.purchase_cost || '';
            document.getElementById('assetCurrentValue').value = asset.current_value || '';

            // Handle condition status if field exists
            const conditionField = document.getElementById('assetConditionStatus');
            if (conditionField) {
                conditionField.value = asset.condition_status || 'good';
            }

            // Handle assigned to if field exists
            const assignedField = document.getElementById('assetAssignedTo');
            if (assignedField) {
                assignedField.value = asset.assigned_to || '';
            }

            // Set selected tags
            selectedAssetTags = asset.tags || [];
            updateSelectedTagsDisplay();

            showNotification('Asset data loaded successfully', 'success');
        } else {
            const error = await response.json();
            showNotification(error.message || 'Error loading asset', 'error');
        }
    } catch (error) {
        console.error('Error loading asset:', error);
        showNotification('Error loading asset: ' + error.message, 'error');
    }
}

// Edit Asset
function editAsset(assetId) {
    App.openAssetModal(assetId);
}

// Delete Asset
async function deleteAsset(assetId) {
    if (!confirm('Are you sure you want to delete this asset?')) {
        return;
    }

    try {
        const response = await fetch(`./api/assets.php?id=${assetId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(result.message, 'success');
            const filters = {
                search: document.getElementById('searchAssets').value,
                category: document.getElementById('categoryFilter').value,
                status: document.getElementById('statusFilter').value,
                tag: document.getElementById('tagFilter').value
            };
            loadAssets(filters, currentPagination.current_page, currentPagination.per_page);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting asset:', error);
        showNotification('Error deleting asset', 'error');
    }
}

// Tag Management
async function handleTagSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const tagData = Object.fromEntries(formData);

    try {
        const response = await fetch('./api/asset_tags.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(tagData)
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(result.message, 'success');
            event.target.reset();
            document.getElementById('tagColor').value = '#3B82F6';
            loadTags();
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error creating tag:', error);
        showNotification('Error creating tag', 'error');
    }
}

// Delete Tag
async function deleteTag(tagId) {
    if (!confirm('Are you sure you want to delete this tag?')) {
        return;
    }

    try {
        const response = await fetch(`./api/asset_tags.php?id=${tagId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(result.message, 'success');
            loadTags();
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting tag:', error);
        showNotification('Error deleting tag', 'error');
    }
}

// Archive Asset
async function archiveAsset(assetId) {
    const reason = prompt('Archive reason (optional):', '');
    if (reason === null) {
        return; // User cancelled
    }

    try {
        await API.archiveAsset(assetId, {
            archive_reason: reason || null
        });

        showNotification('Asset archived successfully', 'success');
        loadAssets();
    } catch (error) {
        console.error('Error archiving asset:', error);
        showNotification(error.message || 'Error archiving asset', 'error');
    }
}

// Add Tag to Asset
function addTagToAsset() {
    const tagSelect = document.getElementById('availableTags');
    const tagId = tagSelect.value;

    if (!tagId) return;

    const tag = allTags.find(t => t.id == tagId);
    if (!tag) return;

    // Check if tag already selected
    if (selectedAssetTags.some(t => t.id == tagId)) {
        showNotification('Tag already added', 'warning');
        return;
    }

    selectedAssetTags.push(tag);
    updateSelectedTagsDisplay();
    tagSelect.value = '';
}

// Remove Tag from Asset
function removeTagFromAsset(tagId) {
    selectedAssetTags = selectedAssetTags.filter(t => t.id != tagId);
    updateSelectedTagsDisplay();
}

// Update Selected Tags Display
function updateSelectedTagsDisplay() {
    const container = document.getElementById('selectedTags');

    if (selectedAssetTags.length === 0) {
        container.innerHTML = '<span class="text-gray-400 text-sm">No tags selected</span>';
        return;
    }

    container.innerHTML = selectedAssetTags.map(tag => `
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium gap-2"
              style="background-color: ${tag.color}15; color: ${tag.color}; border: 1px solid ${tag.color}40;">
            ${tag.name}
            <button type="button" onclick="removeTagFromAsset(${tag.id})" class="hover:bg-red-100 rounded-full p-1">
                <i class="fas fa-times text-xs"></i>
            </button>
        </span>
    `).join('');
}

// Assign Tags to Asset
async function assignTagsToAsset(assetId, tags) {
    for (const tag of tags) {
        try {
            await fetch('./api/asset_tags.php?assign=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_id: assetId,
                    tag_id: tag.id
                })
            });
        } catch (error) {
            console.error('Error assigning tag:', error);
        }
    }
}

// Update Asset Tags (for edit mode)
async function updateAssetTags(assetId, newTags) {
    try {
        console.log('Updating tags for asset:', assetId, 'New tags:', newTags);

        // Ensure newTags is a valid array
        if (!Array.isArray(newTags)) newTags = [];

        let currentTags = [];

        try {
            // First, get current tags for the asset
            const response = await fetch(`./api/asset_tags.php?asset_id=${assetId}`);

            if (response.ok) {
                const responseText = await response.text();
                if (responseText.trim()) {
                    currentTags = JSON.parse(responseText);
                }
            } else if (response.status !== 404) {
                // Only throw error for non-404 errors (404 means no tags, which is fine)
                console.warn(`Failed to fetch current tags: ${response.status}`);
            }
        } catch (fetchError) {
            console.log('Error fetching current tags, assuming no current tags:', fetchError.message);
            currentTags = [];
        }

        // Ensure currentTags is a valid array
        if (!Array.isArray(currentTags)) currentTags = [];

        const currentTagIds = currentTags.map(tag => parseInt(tag.id));
        const newTagIds = newTags.map(tag => parseInt(tag.id));

        // Find tags to remove (in current but not in new)
        const tagsToRemove = currentTagIds.filter(id => !newTagIds.includes(id));

        // Find tags to add (in new but not in current)
        const tagsToAdd = newTagIds.filter(id => !currentTagIds.includes(id));

        console.log('Tags to remove:', tagsToRemove);
        console.log('Tags to add:', tagsToAdd);

        // Remove tags
        for (const tagId of tagsToRemove) {
            try {
                const removeResponse = await fetch('./api/asset_tags.php?unassign=1', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        asset_id: parseInt(assetId),
                        tag_id: parseInt(tagId)
                    })
                });

                if (!removeResponse.ok) {
                    const errorText = await removeResponse.text().catch(() => 'Unknown error');
                    console.warn('Failed to remove tag:', tagId, errorText);
                } else {
                    console.log('Successfully removed tag:', tagId);
                }
            } catch (error) {
                console.error('Error removing tag:', tagId, error.message);
            }
        }

        // Add new tags
        for (const tagId of tagsToAdd) {
            try {
                const addResponse = await fetch('./api/asset_tags.php?assign=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        asset_id: parseInt(assetId),
                        tag_id: parseInt(tagId)
                    })
                });

                if (!addResponse.ok) {
                    const errorText = await addResponse.text().catch(() => 'Unknown error');
                    console.warn('Failed to add tag:', tagId, errorText);
                } else {
                    console.log('Successfully added tag:', tagId);
                }
            } catch (error) {
                console.error('Error adding tag:', tagId, error.message);
            }
        }

        if (tagsToAdd.length > 0 || tagsToRemove.length > 0) {
            showNotification(`Tags updated: ${tagsToAdd.length} added, ${tagsToRemove.length} removed`, 'success');
        } else {
            console.log('No tag changes needed');
        }

    } catch (error) {
        console.error('Error updating asset tags:', error);
        // Don't show error notification for tag updates to avoid disrupting main save flow
        console.log('Tag update failed but continuing with asset save');
    }
}

// QR Code Functions
async function generateQRCode(assetId) {
    try {
        const response = await fetch('./api/qr_generator.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ asset_id: assetId })
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(result.message, 'success');
            const filters = {
                search: document.getElementById('searchAssets').value,
                category: document.getElementById('categoryFilter').value,
                status: document.getElementById('statusFilter').value,
                tag: document.getElementById('tagFilter').value
            };
            loadAssets(filters, currentPagination.current_page, currentPagination.per_page); // Refresh to show QR code icon
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error generating QR code:', error);
        showNotification('Error generating QR code', 'error');
    }
}

async function showQRCode(assetId) {
    try {
        const response = await fetch(`./api/qr_generator.php?asset_id=${assetId}`);
        const result = await response.json();

        if (response.ok && result.generated) {
            const qrContainer = document.getElementById('qrCodeContent');
            qrContainer.innerHTML = `
                <img src="${result.qr_url}" alt="QR Code" class="mx-auto mb-2">
                <p class="text-sm text-gray-600">Asset: ${result.qr_data}</p>
            `;

            document.getElementById('qrModal').classList.remove('hidden');
        } else {
            showNotification('QR code not found', 'error');
        }
    } catch (error) {
        console.error('Error showing QR code:', error);
        showNotification('Error showing QR code', 'error');
    }
}

function printQRCode() {
    const qrContent = document.getElementById('qrCodeContent');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head><title>QR Code</title></head>
            <body style="text-align: center; padding: 20px;">
                ${qrContent.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function downloadQRCode() {
    const img = document.querySelector('#qrCodeContent img');
    if (img) {
        const link = document.createElement('a');
        link.download = 'qr-code.png';
        link.href = img.src;
        link.click();
    }
}

// Export Functions
function exportAssets() {
    if (!confirm('Export all assets to Excel?')) {
        return;
    }

    // Build export URL with current filters
    let exportUrl = 'api/assets.php?action=export_excel';
    
    // Add search filter if active
    const searchInput = document.getElementById('searchAssets');
    if (searchInput && searchInput.value.trim()) {
        exportUrl += '&search=' + encodeURIComponent(searchInput.value.trim());
    }
    
    // Add category filter if active
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter && categoryFilter.value) {
        exportUrl += '&category=' + encodeURIComponent(categoryFilter.value);
    }
    
    // Add status filter if active
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter && statusFilter.value) {
        exportUrl += '&status=' + encodeURIComponent(statusFilter.value);
    }

    // Trigger download by navigating to the export URL
    window.location.href = exportUrl;
}

function bulkActions() {
    const selectedIds = updateBulkSelectionState();

    if (!selectedIds || selectedIds.length === 0) {
        showNotification('Select at least one item first.', 'warning');
        return;
    }

    if (!bulkActionModal) {
        createBulkActionModal();
    }

    bulkActionModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    updateBulkSelectionState();
}

function closeBulkActionModal() {
    if (bulkActionModal) {
        bulkActionModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        bulkActionForm.reset();
        updateBulkSelectionState();
    }
}

function createBulkActionModal() {
    bulkActionModal = document.createElement('div');
    bulkActionModal.id = 'bulkActionModal';
    bulkActionModal.className = 'fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 hidden';
    bulkActionModal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Bulk Actions</h3>
                        <p class="text-sm text-gray-500">Apply changes to multiple items at once.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" id="bulkActionModalClose">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <form id="bulkActionForm" class="px-6 py-5 space-y-4">
                    <div class="p-3 rounded-lg bg-blue-50 text-blue-700 text-sm flex items-center gap-2">
                        <i class="fas fa-layer-group"></i>
                        <span><strong id="bulkSelectedCount">0</strong> items selected</span>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Choose action</label>
                        <select name="bulk_action" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select an action</option>
                            <option value="status">Change Status</option>
                            <option value="add_tag">Add Tag</option>
                            <option value="remove_tag">Remove Tag</option>
                        </select>
                    </div>
                    <div id="bulkActionDynamicFields" class="space-y-3 hidden"></div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-200">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50" id="bulkActionCancel">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(bulkActionModal);
    bulkActionForm = document.getElementById('bulkActionForm');
    bulkActionSection = document.getElementById('bulkActionDynamicFields');
    bulkSelectedCount = document.getElementById('bulkSelectedCount');

    document.getElementById('bulkActionModalClose').addEventListener('click', closeBulkActionModal);
    document.getElementById('bulkActionCancel').addEventListener('click', (e) => {
        e.preventDefault();
        closeBulkActionModal();
    });

    bulkActionForm.addEventListener('change', (event) => {
        if (event.target.name === 'bulk_action') {
            renderBulkActionFields(event.target.value);
        }
    });

    bulkActionForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitBulkAction(new FormData(bulkActionForm));
    });
}

function renderBulkActionFields(action) {
    if (!bulkActionSection) return;

    bulkActionSection.innerHTML = '';
    bulkActionSection.classList.add('hidden');

    if (action === 'status') {
        bulkActionSection.innerHTML = `
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Select new status</label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose status</option>
                    <option value="available">Available</option>
                    <option value="assigned">Assigned</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="damaged">Damaged</option>
                    <option value="lost">Lost</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
        `;
        bulkActionSection.classList.remove('hidden');
    }

    if (action === 'add_tag' || action === 'remove_tag') {
        const options = allTags.map(tag => `<option value="${tag.id}">${tag.name}</option>`).join('');
        bulkActionSection.innerHTML = `
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Select tag</label>
                <select name="tag_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose tag</option>
                    ${options}
                </select>
            </div>
        `;
        bulkActionSection.classList.remove('hidden');
    }
}

async function submitBulkAction(formData) {
    const selectedIds = updateBulkSelectionState();
    if (!selectedIds || selectedIds.length === 0) {
        showNotification('No items selected.', 'warning');
        return;
    }

    const action = formData.get('bulk_action');
    if (!action) {
        showNotification('Select an action first.', 'warning');
        return;
    }

    const payload = { ids: selectedIds };
    let endpoint = '';

    if (action === 'status') {
        payload.status = formData.get('status');
        if (!payload.status) {
            showNotification('Please choose a status.', 'warning');
            return;
        }
        endpoint = './api/assets.php?action=bulk_update_status';
    }

    if (action === 'add_tag' || action === 'remove_tag') {
        payload.tag_id = formData.get('tag_id');
        if (!payload.tag_id) {
            showNotification('Please select a tag.', 'warning');
            return;
        }
        endpoint = action === 'add_tag'
            ? './api/assets.php?action=bulk_add_tag'
            : './api/assets.php?action=bulk_remove_tag';
    }

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Bulk action failed');
        }

        showNotification('Bulk operation completed successfully.', 'success');
        closeBulkActionModal();
        await loadAssets({
            search: document.getElementById('searchAssets').value,
            category: document.getElementById('categoryFilter').value,
            status: document.getElementById('statusFilter').value,
            tag: document.getElementById('tagFilter').value
        }, currentPagination.current_page, currentPagination.per_page);
    } catch (error) {
        console.error('Bulk action error:', error);
        showNotification(error.message || 'Unable to complete bulk action.', 'error');
    }
}

// Notification Function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${getNotificationClass(type)}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${getNotificationIcon(type)} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function getNotificationClass(type) {
    const classes = {
        'success': 'bg-green-100 text-green-800 border border-green-300',
        'error': 'bg-red-100 text-red-800 border border-red-300',
        'warning': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
        'info': 'bg-blue-100 text-blue-800 border border-blue-300'
    };
    return classes[type] || classes.info;
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('assetsTable')) {
        initAssetManagement();
    }
});