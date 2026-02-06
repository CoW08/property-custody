// Purchase Orders Management JavaScript

let purchaseOrdersState = {
    page: 1,
    filters: {
        search: '',
        status: '',
        request_id: '',
        order_date: ''
    }
};

let purchaseOrderItems = [];
let editPurchaseOrderItems = [];
let selectedRequestDetails = null;
let selectedEditPurchaseOrder = null;
let purchaseOrderSupplies = [];
const SHIPPING_RATES = {
    Standard: 100,
    Express: 250,
    Pickup: 0
};

window.addEventListener('DOMContentLoaded', () => {
    bindPurchaseOrderEvents();
    loadPurchaseOrderStats();
    loadApprovedRequests();
    loadPurchaseOrders();
    loadSupplyCatalogForPO();
});

function bindPurchaseOrderEvents() {
    const searchInput = document.getElementById('searchPurchaseOrders');
    const statusFilter = document.getElementById('statusFilter');
    const requestFilter = document.getElementById('requestFilter');
    const orderDateFilter = document.getElementById('orderDateFilter');
    const refreshBtn = document.getElementById('refreshPurchaseOrders');
    const createBtn = document.getElementById('createPurchaseOrderBtn');
    const cancelBtn = document.getElementById('cancelPurchaseOrder');
    const closeModalBtn = document.getElementById('closePurchaseOrderModal');
    const viewCloseBtn = document.getElementById('closeViewPurchaseOrderModal');
    const editCloseBtn = document.getElementById('closeEditPurchaseOrderModal');
    const cancelEditBtn = document.getElementById('cancelEditPurchaseOrder');
    const requestSelect = document.getElementById('purchaseOrderRequest');
    const itemSupplySelect = document.getElementById('purchaseOrderSupplySelect');
    const addItemBtn = document.getElementById('purchaseOrderAddItemBtn');
    const shippingInput = document.getElementById('purchaseOrderShippingCost');
    const shippingMethodSelect = document.getElementById('purchaseOrderShippingMethod');
    const editShippingInput = document.getElementById('editShippingCost');
    const editShippingMethodSelect = document.getElementById('editShippingMethod');
    const form = document.getElementById('purchaseOrderForm');
    const editForm = document.getElementById('editPurchaseOrderForm');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(event => {
            purchaseOrdersState.filters.search = event.target.value.trim();
            purchaseOrdersState.page = 1;
            loadPurchaseOrders();
        }, 300));
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', event => {
            purchaseOrdersState.filters.status = event.target.value;
            purchaseOrdersState.page = 1;
            loadPurchaseOrders();
        });
    }

    if (requestFilter) {
        requestFilter.addEventListener('change', event => {
            purchaseOrdersState.filters.request_id = event.target.value;
            purchaseOrdersState.page = 1;
            loadPurchaseOrders();
        });
    }

    if (orderDateFilter) {
        orderDateFilter.addEventListener('change', event => {
            purchaseOrdersState.filters.order_date = event.target.value;
            purchaseOrdersState.page = 1;
            loadPurchaseOrders();
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            purchaseOrdersState.page = 1;
            loadPurchaseOrderStats();
            loadPurchaseOrders();
        });
    }

    if (createBtn) {
        createBtn.addEventListener('click', openPurchaseOrderModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closePurchaseOrderModal);
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closePurchaseOrderModal);
    }

    if (viewCloseBtn) {
        viewCloseBtn.addEventListener('click', closeViewPurchaseOrderModal);
    }

    if (editCloseBtn) {
        editCloseBtn.addEventListener('click', closeEditPurchaseOrderModal);
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeEditPurchaseOrderModal);
    }

    if (requestSelect) {
        requestSelect.addEventListener('change', async event => {
            const requestId = event.target.value;
            if (!requestId) {
                selectedRequestDetails = null;
                purchaseOrderItems = [];
                renderPurchaseOrderItems();
                recalculatePurchaseOrderTotals();
                return;
            }

            try {
                const res = await fetch(`api/procurement.php?action=details&id=${requestId}`);
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load request details');
                }
                selectedRequestDetails = data.data;
                populatePurchaseOrderFromRequest(selectedRequestDetails);
            } catch (error) {
                console.error('Failed to load request details:', error);
                showNotification('Failed to load procurement request details.', 'error');
            }
        });
    }

    if (itemSupplySelect) {
        itemSupplySelect.addEventListener('change', () => {
            if (addItemBtn) {
                addItemBtn.disabled = !itemSupplySelect.value;
            }
        });
    }

    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            addSupplyItemToPurchaseOrder();
        });
    }

    if (shippingMethodSelect) {
        shippingMethodSelect.addEventListener('change', () => {
            applyShippingCostFromMethod(shippingMethodSelect, shippingInput, recalculatePurchaseOrderTotals);
        });
    }


    if (shippingInput) {
        shippingInput.addEventListener('input', recalculatePurchaseOrderTotals);
    }
    if (editShippingMethodSelect) {
        editShippingMethodSelect.addEventListener('change', () => {
            applyShippingCostFromMethod(editShippingMethodSelect, editShippingInput, recalculateEditPurchaseOrderTotals);
        });
    }


    if (editShippingInput) {
        editShippingInput.addEventListener('input', recalculateEditPurchaseOrderTotals);

    if (form) {
        form.addEventListener('submit', submitPurchaseOrderForm);
    }

    if (editForm) {
        editForm.addEventListener('submit', submitEditPurchaseOrderForm);
    }
}

function applyShippingCostFromMethod(methodSelect, costInput, recalc) {
    if (!methodSelect || !costInput) return;
    const rate = SHIPPING_RATES[methodSelect.value] ?? 0;
    costInput.value = rate.toFixed(2);
    if (typeof recalc === 'function') {
        recalc();
    }
}

function getSupplyMatchKey(value) {
    return (value || '').toString().trim().toLowerCase();
}

function findSupplyForName(name) {
    const key = getSupplyMatchKey(name);
    if (!key) return null;
    return purchaseOrderSupplies.find(supply => getSupplyMatchKey(supply.name) === key) || null;
}

function applyInventoryPricing(items) {
    return items.map(item => {
        const matchedSupply = findSupplyForName(item.item_name);
        const unitCost = matchedSupply && matchedSupply.unit_cost != null
            ? Number(matchedSupply.unit_cost)
            : (parseFloat(item.unit_cost) || 0);
        const quantity = parseFloat(item.quantity) || 0;
        return {
            ...item,
            unit_cost: unitCost,
            total_cost: quantity * unitCost
        };
    });
}

async function loadSupplyCatalogForPO() {
    const itemSupplySelect = document.getElementById('purchaseOrderSupplySelect');
    const addItemBtn = document.getElementById('purchaseOrderAddItemBtn');

    try {
        const supplies = await API.getSupplies();
        purchaseOrderSupplies = Array.isArray(supplies)
            ? supplies
            : (Array.isArray(supplies?.data) ? supplies.data : []);

        if (!itemSupplySelect) return;

        if (!purchaseOrderSupplies.length) {
            itemSupplySelect.innerHTML = '<option value="">No inventory items available</option>';
            if (addItemBtn) addItemBtn.disabled = true;
            return;
        }

        const options = purchaseOrderSupplies.map(item => {
            const code = item.item_code ? `${item.item_code} - ` : '';
            const label = `${code}${item.name || 'Unnamed Item'}`;
            return `<option value="${item.id}">${label}</option>`;
        }).join('');

        itemSupplySelect.innerHTML = `<option value="">Select inventory item</option>${options}`;
        if (addItemBtn) addItemBtn.disabled = true;

        if (purchaseOrderItems.length) {
            purchaseOrderItems = applyInventoryPricing(purchaseOrderItems);
            renderPurchaseOrderItems();
            recalculatePurchaseOrderTotals();
        }

        if (editPurchaseOrderItems.length) {
            editPurchaseOrderItems = applyInventoryPricing(editPurchaseOrderItems);
            renderEditPurchaseOrderItems();
            recalculateEditPurchaseOrderTotals();
        }
    } catch (error) {
        console.error('Failed to load inventory for purchase orders:', error);
        if (itemSupplySelect) {
            itemSupplySelect.innerHTML = '<option value="">Unable to load inventory</option>';
        }
        if (addItemBtn) addItemBtn.disabled = true;
    }
}

function addSupplyItemToPurchaseOrder() {
    const itemSupplySelect = document.getElementById('purchaseOrderSupplySelect');
    if (!itemSupplySelect) return;
    const supplyId = itemSupplySelect.value;
    if (!supplyId) return;

    const supply = purchaseOrderSupplies.find(item => String(item.id) === String(supplyId));
    if (!supply) return;

    const unitCost = supply.unit_cost != null ? Number(supply.unit_cost) : 0;
    const quantity = 1;

    purchaseOrderItems.push({
        request_item_id: null,
        item_name: supply.name || supply.item_code || '',
        description: supply.description || '',
        quantity,
        unit: supply.unit || 'piece',
        unit_cost: unitCost,
        total_cost: quantity * unitCost,
        expected_delivery_date: null,
        status: 'pending',
        notes: ''
    });

    renderPurchaseOrderItems();
    recalculatePurchaseOrderTotals();

    itemSupplySelect.value = '';
    const addItemBtn = document.getElementById('purchaseOrderAddItemBtn');
    if (addItemBtn) addItemBtn.disabled = true;
}

async function loadPurchaseOrderStats() {
    const totalElement = document.getElementById('totalPurchaseOrders');
    const receivedElement = document.getElementById('receivedPurchaseOrders');
    const pendingElement = document.getElementById('pendingPurchaseOrders');
    const totalValueElement = document.getElementById('totalPurchaseOrderValue');

    try {
        const response = await fetch('api/purchase_orders.php?action=stats');
        const data = await response.json();
        if (!data.success) return;

        const stats = data.data;
        if (totalElement) totalElement.textContent = stats.total_purchase_orders ?? 0;
        if (receivedElement) receivedElement.textContent = stats.received ?? 0;
        if (pendingElement) pendingElement.textContent = stats.pending ?? 0;
        if (totalValueElement) totalValueElement.textContent = formatCurrency(stats.total_value ?? 0);
    } catch (error) {
        console.error('Failed to load purchase order stats:', error);
    }
}

async function loadApprovedRequests() {
    const requestFilter = document.getElementById('requestFilter');
    const requestSelect = document.getElementById('purchaseOrderRequest');
    try {
        const params = new URLSearchParams({
            action: 'list',
            status: 'approved',
            limit: 100
        });
        const res = await fetch(`api/procurement.php?${params}`);
        const data = await res.json();
        if (!data.success) return;

        const options = data.data || [];
        const optionMarkup = ['<option value="">All Requests</option>'].concat(
            options.map(request => `<option value="${request.id}">${request.request_code} - ${request.requestor_name || 'N/A'}</option>`) ?? []
        );
        if (requestFilter) {
            requestFilter.innerHTML = optionMarkup.join('');
        }
        if (requestSelect) {
            const formOptions = ['<option value="">Select Approved Request</option>'].concat(
                options.map(request => `<option value="${request.id}">${request.request_code} - ${request.requestor_name || 'N/A'}</option>`) ?? []
            );
            requestSelect.innerHTML = formOptions.join('');
        }
    } catch (error) {
        console.error('Failed to load approved requests:', error);
    }
}

async function loadPurchaseOrders(page = purchaseOrdersState.page) {
    const tbody = document.getElementById('purchaseOrdersTableBody');
    const mobileList = document.getElementById('mobilePurchaseOrderList');
    const info = document.getElementById('purchaseOrdersPaginationInfo');
    const controls = document.getElementById('purchaseOrdersPaginationControls');

    const params = new URLSearchParams({
        action: 'list',
        page
    });

    Object.entries(purchaseOrdersState.filters).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });

    try {
        showLoading();
        const response = await fetch(`api/purchase_orders.php?${params}`);
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to load purchase orders');
        }

        const purchaseOrders = result.data || [];
        purchaseOrdersState.page = result.pagination.page;

        renderPurchaseOrdersTable(tbody, purchaseOrders);
        renderPurchaseOrdersMobile(mobileList, purchaseOrders);
        renderPurchaseOrdersPagination(controls, result.pagination);
        updatePurchaseOrdersPaginationInfo(info, result.pagination);
    } catch (error) {
        console.error('Error loading purchase orders:', error);
        showNotification(error.message || 'Error loading purchase orders', 'error');
        if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Failed to load purchase orders</td></tr>`;
        if (mobileList) mobileList.innerHTML = `<div class="p-6 text-center text-gray-500">Failed to load purchase orders</div>`;
    } finally {
        hideLoading();
    }
}

function renderPurchaseOrdersTable(tbody, purchaseOrders) {
    if (!tbody) return;

    if (purchaseOrders.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No purchase orders found</td></tr>`;
        return;
    }

    tbody.innerHTML = purchaseOrders.map(po => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${po.po_number}</div>
                <div class="text-xs text-gray-500">Created ${formatDate(po.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${po.request_code || 'N/A'}</div>
                <div class="text-xs text-gray-500">${po.department || '—'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${po.vendor_name || 'N/A'}</div>
                <div class="text-xs text-gray-500">${po.vendor_email || po.vendor_phone || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDate(po.order_date)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getPurchaseOrderStatusColor(po.status)}">
                    ${capitalizeFirst(po.status || 'pending')}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatCurrency(po.total_amount || 0)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button class="text-blue-600 hover:text-blue-900" title="View" onclick="viewPurchaseOrder(${po.id})"><i class="fas fa-eye"></i></button>
                <button class="text-yellow-600 hover:text-yellow-900" title="Edit" onclick="editPurchaseOrder(${po.id})"><i class="fas fa-edit"></i></button>
                <button class="text-red-600 hover:text-red-900" title="Delete" onclick="deletePurchaseOrder(${po.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderPurchaseOrdersMobile(container, purchaseOrders) {
    if (!container) return;

    if (purchaseOrders.length === 0) {
        container.innerHTML = `<div class="p-6 text-center text-gray-500">No purchase orders found</div>`;
        return;
    }

    container.innerHTML = purchaseOrders.map(po => `
        <div class="border-b border-gray-200 p-4">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">${po.po_number}</h4>
                    <p class="text-xs text-gray-500">${po.request_code || 'N/A'} • ${po.department || '—'}</p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getPurchaseOrderStatusColor(po.status)}">
                    ${capitalizeFirst(po.status || 'pending')}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-gray-500">Order Date:</span> ${formatDate(po.order_date)}</div>
                <div><span class="text-gray-500">Vendor:</span> ${po.vendor_name || 'N/A'}</div>
                <div><span class="text-gray-500">Total:</span> ${formatCurrency(po.total_amount || 0)}</div>
                <div><span class="text-gray-500">Created:</span> ${formatDate(po.created_at)}</div>
            </div>
            <div class="flex items-center justify-end mt-3 space-x-3 text-sm">
                <button class="text-blue-600 hover:text-blue-900" onclick="viewPurchaseOrder(${po.id})"><i class="fas fa-eye mr-1"></i>View</button>
                <button class="text-yellow-600 hover:text-yellow-900" onclick="editPurchaseOrder(${po.id})"><i class="fas fa-edit mr-1"></i>Edit</button>
                <button class="text-red-600 hover:text-red-900" onclick="deletePurchaseOrder(${po.id})"><i class="fas fa-trash mr-1"></i>Delete</button>
            </div>
        </div>
    `).join('');
}

function renderPurchaseOrdersPagination(container, pagination) {
    if (!container) return;

    const { page, pages } = pagination;
    if (pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let controls = '';
    if (page > 1) {
        controls += `<button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50" onclick="changePurchaseOrdersPage(${page - 1})">Previous</button>`;
    }

    controls += `<span class="px-3 py-1 text-sm text-gray-600">Page ${page} of ${pages}</span>`;

    if (page < pages) {
        controls += `<button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50" onclick="changePurchaseOrdersPage(${page + 1})">Next</button>`;
    }

    container.innerHTML = controls;
}

function changePurchaseOrdersPage(page) {
    purchaseOrdersState.page = page;
    loadPurchaseOrders(page);
}

function updatePurchaseOrdersPaginationInfo(element, pagination) {
    if (!element) return;
    const { page, limit, total } = pagination;
    const start = (page - 1) * limit + 1;
    const end = Math.min(page * limit, total);

    element.textContent = total === 0
        ? 'Showing 0 to 0 of 0 results'
        : `Showing ${start} to ${end} of ${total} results`;
}

function openPurchaseOrderModal() {
    const modal = document.getElementById('purchaseOrderModal');
    const form = document.getElementById('purchaseOrderForm');

    if (form) {
        form.reset();
    }

    selectedRequestDetails = null;
    purchaseOrderItems = [];
    renderPurchaseOrderItems();
    recalculatePurchaseOrderTotals();

    const today = new Date().toISOString().split('T')[0];
    const orderDateInput = document.getElementById('purchaseOrderDate');
    if (orderDateInput) {
        orderDateInput.value = today;
    }
    const shippingMethodSelect = document.getElementById('purchaseOrderShippingMethod');
    const shippingInput = document.getElementById('purchaseOrderShippingCost');
    if (shippingMethodSelect && shippingInput) {
        applyShippingCostFromMethod(shippingMethodSelect, shippingInput, recalculatePurchaseOrderTotals);
    }

    showModal(modal);
}

function closePurchaseOrderModal() {
    const modal = document.getElementById('purchaseOrderModal');
    hideModal(modal);
}

function openViewPurchaseOrderModal() {
    const modal = document.getElementById('viewPurchaseOrderModal');
    showModal(modal);
}

function closeViewPurchaseOrderModal() {
    const modal = document.getElementById('viewPurchaseOrderModal');
    hideModal(modal);
}

function openEditPurchaseOrderModal() {
    const modal = document.getElementById('editPurchaseOrderModal');
    showModal(modal);
}

function closeEditPurchaseOrderModal() {
    const modal = document.getElementById('editPurchaseOrderModal');
    hideModal(modal);
}

function populatePurchaseOrderFromRequest(request) {
    if (!request) return;

    const requestSelect = document.getElementById('purchaseOrderRequest');
    if (requestSelect) {
        requestSelect.value = request.id;
    }

    purchaseOrderItems = applyInventoryPricing((request.items || []).map(item => ({
        request_item_id: item.id,
        item_name: item.item_name,
        description: item.description || '',
        quantity: item.quantity || 1,
        unit: item.unit || 'piece',
        unit_cost: item.estimated_unit_cost || 0,
        total_cost: item.total_cost || ((item.quantity || 1) * (item.estimated_unit_cost || 0)),
        expected_delivery_date: request.required_date || null,
        status: 'pending',
        notes: ''
    })));

    renderPurchaseOrderItems();
    recalculatePurchaseOrderTotals();
}

function renderPurchaseOrderItems() {
    const container = document.getElementById('purchaseOrderItemsContainer');
    if (!container) return;

    if (purchaseOrderItems.length === 0) {
        container.innerHTML = `<div class="text-sm text-gray-500">Select an approved procurement request to auto-populate items.</div>`;
        return;
    }

    container.innerHTML = purchaseOrderItems.map((item, index) => `
        <div class="border border-gray-200 rounded-lg p-3" data-index="${index}">
            <div class="flex justify-between items-start mb-2">
                <h5 class="text-sm font-medium text-gray-900">${item.item_name}</h5>
                <button type="button" class="text-red-600 hover:text-red-800 text-xs" onclick="removePurchaseOrderItem(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 text-sm">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                    <input type="number" min="1" value="${item.quantity}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updatePurchaseOrderItem(${index}, 'quantity', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Unit</label>
                    <input type="text" value="${item.unit || ''}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updatePurchaseOrderItem(${index}, 'unit', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Unit Cost</label>
                    <input type="number" step="0.01" min="0" value="${item.unit_cost}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        readonly
                        onchange="updatePurchaseOrderItem(${index}, 'unit_cost', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Total</label>
                    <input type="number" step="0.01" min="0" value="${item.total_cost}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        readonly
                        onchange="updatePurchaseOrderItem(${index}, 'total_cost', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Expected Delivery</label>
                    <input type="date" value="${item.expected_delivery_date || ''}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updatePurchaseOrderItem(${index}, 'expected_delivery_date', this.value)" />
                </div>
            </div>
            <div class="mt-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea class="w-full px-2 py-1 border border-gray-300 rounded" rows="2"
                    onchange="updatePurchaseOrderItem(${index}, 'notes', this.value)">${item.notes || ''}</textarea>
            </div>
        </div>
    `).join('');
}

function updatePurchaseOrderItem(index, field, value) {
    if (!purchaseOrderItems[index]) return;

    const parsedValue = (field === 'quantity' || field === 'unit_cost' || field === 'total_cost') ? parseFloat(value) : value;
    purchaseOrderItems[index][field] = Number.isNaN(parsedValue) ? 0 : parsedValue;

    if (field === 'quantity' || field === 'unit_cost') {
        const quantity = parseFloat(purchaseOrderItems[index].quantity) || 0;
        const unitCost = parseFloat(purchaseOrderItems[index].unit_cost) || 0;
        purchaseOrderItems[index].total_cost = quantity * unitCost;
        renderPurchaseOrderItems();
    }

    recalculatePurchaseOrderTotals();
}

function removePurchaseOrderItem(index) {
    purchaseOrderItems.splice(index, 1);
    renderPurchaseOrderItems();
    recalculatePurchaseOrderTotals();
}

function recalculatePurchaseOrderTotals() {
    const subtotalInput = document.getElementById('purchaseOrderSubtotal');
    const shippingInput = document.getElementById('purchaseOrderShippingCost');
    const totalInput = document.getElementById('purchaseOrderTotalAmount');

    const subtotal = purchaseOrderItems.reduce((sum, item) => sum + (parseFloat(item.total_cost) || 0), 0);
    const shippingCost = parseFloat(shippingInput?.value) || 0;
    const total = subtotal + shippingCost;

    if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
    if (totalInput) totalInput.value = total.toFixed(2);
}

async function submitPurchaseOrderForm(event) {
    event.preventDefault();

    if (purchaseOrderItems.length === 0) {
        showNotification('Please select a procurement request to populate items.', 'error');
        return;
    }

    const form = event.target;
    const formData = new FormData(form);

    const payload = {
        request_id: parseInt(formData.get('request_id'), 10),
        vendor_name: formData.get('vendor_name'),
        vendor_email: formData.get('vendor_email') || null,
        vendor_phone: formData.get('vendor_phone') || null,
        vendor_address: formData.get('vendor_address') || null,
        order_date: formData.get('order_date'),
        expected_delivery_date: formData.get('expected_delivery_date') || null,
        payment_terms: formData.get('payment_terms') || null,
        shipping_method: formData.get('shipping_method') || null,
        status: formData.get('status') || 'pending',
        notes: formData.get('notes') || null,
        subtotal: parseFloat(formData.get('subtotal')) || 0,
        shipping_cost: parseFloat(formData.get('shipping_cost')) || 0,
        total_amount: parseFloat(formData.get('total_amount')) || 0,
        items: purchaseOrderItems.map(item => ({
            request_item_id: item.request_item_id,
            item_name: item.item_name,
            description: item.description,
            quantity: parseFloat(item.quantity) || 0,
            unit: item.unit,
            unit_cost: parseFloat(item.unit_cost) || 0,
            total_cost: parseFloat(item.total_cost) || 0,
            expected_delivery_date: item.expected_delivery_date || null,
            status: item.status || 'pending',
            notes: item.notes || null
        }))
    };

    try {
        const response = await fetch('api/purchase_orders.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to create purchase order');
        }

        showNotification('Purchase order created successfully', 'success');
        closePurchaseOrderModal();
        loadPurchaseOrderStats();
        loadPurchaseOrders();
        loadApprovedRequests();
    } catch (error) {
        console.error('Error creating purchase order:', error);
        showNotification(error.message || 'Error creating purchase order', 'error');
    }
}

async function viewPurchaseOrder(id) {
    try {
        const response = await fetch(`api/purchase_orders.php?action=details&id=${id}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Failed to load purchase order details');
        }

        renderPurchaseOrderDetails(data.data);
        openViewPurchaseOrderModal();
    } catch (error) {
        console.error('Failed to view purchase order:', error);
        showNotification('Failed to view purchase order.', 'error');
    }
}

async function editPurchaseOrder(id) {
    try {
        const response = await fetch(`api/purchase_orders.php?action=details&id=${id}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Failed to load purchase order details');
        }

        selectedEditPurchaseOrder = data.data;
        populateEditPurchaseOrderForm(selectedEditPurchaseOrder);
        openEditPurchaseOrderModal();
    } catch (error) {
        console.error('Failed to load purchase order for edit:', error);
        showNotification('Failed to load purchase order for editing.', 'error');
    }
}

async function deletePurchaseOrder(id) {
    if (!confirm('Are you sure you want to delete this purchase order?')) {
        return;
    }

    try {
        const response = await fetch(`api/purchase_orders.php?action=delete&id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to delete purchase order');
        }

        showNotification('Purchase order deleted successfully', 'success');
        loadPurchaseOrderStats();
        loadPurchaseOrders();
    } catch (error) {
        console.error('Failed to delete purchase order:', error);
        showNotification(error.message || 'Failed to delete purchase order', 'error');
    }
}

function getPurchaseOrderStatusColor(status) {
    switch (status) {
        case 'received':
            return 'bg-emerald-100 text-emerald-700';
        case 'partially_received':
            return 'bg-yellow-100 text-yellow-700';
        case 'sent':
            return 'bg-blue-100 text-blue-700';
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function debounce(fn, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), wait);
    };
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(value || 0);
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString();
}

function capitalizeFirst(value) {
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function showModal(modal) {
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function hideModal(modal) {
    if (!modal) return;
    modal.classList.add('hidden');
    if (!document.querySelector('.modal-overlay:not(.hidden)')) {
        document.body.classList.remove('overflow-hidden');
    }
}

function renderPurchaseOrderDetails(po) {
    const container = document.getElementById('purchaseOrderDetailsContent');
    if (!container) return;

    const items = (po.items || []).map(item => `
        <tr>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.item_name}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.quantity}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${item.unit || '—'}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${formatCurrency(item.unit_cost || 0)}</td>
            <td class="px-3 sm:px-4 py-2 border text-sm">${formatCurrency(item.total_cost || 0)}</td>
        </tr>
    `).join('');

    container.innerHTML = `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">PO Number</label>
                <p class="mt-1 text-sm text-gray-900">${po.po_number}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <p class="mt-1 text-sm text-gray-900">${capitalizeFirst(po.status)}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Vendor</label>
                <p class="mt-1 text-sm text-gray-900">${po.vendor_name || 'N/A'}</p>
                <p class="text-xs text-gray-500">${po.vendor_email || ''}${po.vendor_email && po.vendor_phone ? ' • ' : ''}${po.vendor_phone || ''}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Order Date</label>
                <p class="mt-1 text-sm text-gray-900">${formatDate(po.order_date)}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Expected Delivery</label>
                <p class="mt-1 text-sm text-gray-900">${formatDate(po.expected_delivery_date)}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Linked Request</label>
                <p class="mt-1 text-sm text-gray-900">${po.request_code || 'N/A'}</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Vendor Address</label>
            <p class="mt-1 text-sm text-gray-900">${po.vendor_address || '—'}</p>
        </div>

        <div class="mt-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Items</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                            <th class="px-3 sm:px-4 py-2 border text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items || '<tr><td colspan="5" class="px-3 sm:px-4 py-3 text-center text-sm text-gray-500">No items</td></tr>'}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-3">
                <p class="text-xs text-gray-500">Subtotal</p>
                <p class="text-sm font-semibold text-gray-900">${formatCurrency(po.subtotal || 0)}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-3">
                <p class="text-xs text-gray-500">Shipping Cost</p>
                <p class="text-sm font-semibold text-gray-900">${formatCurrency(po.shipping_cost || 0)}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-3">
                <p class="text-xs text-gray-500">Total</p>
                <p class="text-sm font-semibold text-gray-900">${formatCurrency(po.total_amount || 0)}</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <p class="mt-1 text-sm text-gray-900">${po.notes || '—'}</p>
        </div>
    `;
}

function populateEditPurchaseOrderForm(po) {
    const idField = document.getElementById('editPurchaseOrderId');
    const vendorName = document.getElementById('editVendorName');
    const orderDate = document.getElementById('editOrderDate');
    const expectedDate = document.getElementById('editExpectedDeliveryDate');
    const paymentMethod = document.getElementById('editPaymentMethod');
    const shippingMethod = document.getElementById('editShippingMethod');
    const status = document.getElementById('editStatus');
    const notes = document.getElementById('editNotes');
    const shipping = document.getElementById('editShippingCost');
    const subtotal = document.getElementById('editSubtotal');
    const total = document.getElementById('editTotalAmount');

    if (idField) idField.value = po.id;
    if (vendorName) vendorName.value = po.vendor_name || '';
    if (orderDate) orderDate.value = po.order_date ? po.order_date.substring(0, 10) : '';
    if (expectedDate) expectedDate.value = po.expected_delivery_date ? po.expected_delivery_date.substring(0, 10) : '';
    if (paymentMethod) paymentMethod.value = po.payment_terms || '';
    if (shippingMethod) shippingMethod.value = po.shipping_method || '';
    if (status) status.value = po.status || 'pending';
    if (notes) notes.value = po.notes || '';
    if (shipping) shipping.value = po.shipping_cost || 0;
    if (subtotal) subtotal.value = (po.subtotal || 0).toFixed(2);
    if (total) total.value = (po.total_amount || 0).toFixed(2);

    if (shippingMethod && shipping) {
        applyShippingCostFromMethod(shippingMethod, shipping, recalculateEditPurchaseOrderTotals);
    }

    editPurchaseOrderItems = applyInventoryPricing((po.items || []).map(item => ({
        id: item.id,
        request_item_id: item.request_item_id,
        item_name: item.item_name,
        description: item.description || '',
        quantity: item.quantity || 1,
        unit: item.unit || 'piece',
        unit_cost: item.unit_cost || 0,
        total_cost: item.total_cost || ((item.quantity || 1) * (item.unit_cost || 0)),
        expected_delivery_date: item.expected_delivery_date || null,
        status: item.status || 'pending',
        notes: item.notes || ''
    })));

    renderEditPurchaseOrderItems();
    recalculateEditPurchaseOrderTotals();
}

function renderEditPurchaseOrderItems() {
    const container = document.getElementById('editPurchaseOrderItemsContainer');
    if (!container) return;

    if (editPurchaseOrderItems.length === 0) {
        container.innerHTML = `<div class="text-sm text-gray-500">This purchase order has no items.</div>`;
        return;
    }

    container.innerHTML = editPurchaseOrderItems.map((item, index) => `
        <div class="border border-gray-200 rounded-lg p-3" data-index="${index}">
            <div class="flex justify-between items-start mb-2">
                <h5 class="text-sm font-medium text-gray-900">${item.item_name}</h5>
                <button type="button" class="text-red-600 hover:text-red-800 text-xs" onclick="removeEditPurchaseOrderItem(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 text-sm">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                    <input type="number" min="1" value="${item.quantity}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updateEditPurchaseOrderItem(${index}, 'quantity', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Unit</label>
                    <input type="text" value="${item.unit || ''}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updateEditPurchaseOrderItem(${index}, 'unit', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Unit Cost</label>
                    <input type="number" step="0.01" min="0" value="${item.unit_cost}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        readonly
                        onchange="updateEditPurchaseOrderItem(${index}, 'unit_cost', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Total</label>
                    <input type="number" step="0.01" min="0" value="${item.total_cost}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        readonly
                        onchange="updateEditPurchaseOrderItem(${index}, 'total_cost', this.value)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Expected Delivery</label>
                    <input type="date" value="${item.expected_delivery_date ? item.expected_delivery_date.substring(0, 10) : ''}" class="w-full px-2 py-1 border border-gray-300 rounded"
                        onchange="updateEditPurchaseOrderItem(${index}, 'expected_delivery_date', this.value)" />
                </div>
            </div>
            <div class="mt-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea class="w-full px-2 py-1 border border-gray-300 rounded" rows="2"
                    onchange="updateEditPurchaseOrderItem(${index}, 'notes', this.value)">${item.notes || ''}</textarea>
            </div>
        </div>
    `).join('');
}

function updateEditPurchaseOrderItem(index, field, value) {
    if (!editPurchaseOrderItems[index]) return;

    const numericFields = ['quantity', 'unit_cost', 'total_cost'];
    const parsedValue = numericFields.includes(field) ? parseFloat(value) : value;
    editPurchaseOrderItems[index][field] = numericFields.includes(field) && Number.isNaN(parsedValue) ? 0 : parsedValue;

    if (field === 'quantity' || field === 'unit_cost') {
        const quantity = parseFloat(editPurchaseOrderItems[index].quantity) || 0;
        const unitCost = parseFloat(editPurchaseOrderItems[index].unit_cost) || 0;
        editPurchaseOrderItems[index].total_cost = quantity * unitCost;
        renderEditPurchaseOrderItems();
    }

    recalculateEditPurchaseOrderTotals();
}

function removeEditPurchaseOrderItem(index) {
    editPurchaseOrderItems.splice(index, 1);
    renderEditPurchaseOrderItems();
    recalculateEditPurchaseOrderTotals();
}

function recalculateEditPurchaseOrderTotals() {
    const subtotalInput = document.getElementById('editSubtotal');
    const shippingInput = document.getElementById('editShippingCost');
    const totalInput = document.getElementById('editTotalAmount');

    const subtotal = editPurchaseOrderItems.reduce((sum, item) => sum + (parseFloat(item.total_cost) || 0), 0);
    const shippingCost = parseFloat(shippingInput?.value) || 0;
    const total = subtotal + shippingCost;

    if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
    if (totalInput) totalInput.value = total.toFixed(2);
}

async function submitEditPurchaseOrderForm(event) {
    event.preventDefault();

    if (!selectedEditPurchaseOrder) {
        showNotification('No purchase order selected for editing.', 'error');
        return;
    }

    const form = event.target;
    const formData = new FormData(form);

    const payload = {
        id: parseInt(formData.get('id'), 10),
        vendor_name: formData.get('vendor_name'),
        order_date: formData.get('order_date'),
        expected_delivery_date: formData.get('expected_delivery_date') || null,
        payment_terms: formData.get('payment_terms') || null,
        shipping_method: formData.get('shipping_method') || null,
        status: formData.get('status') || 'pending',
        notes: formData.get('notes') || null,
        subtotal: parseFloat(formData.get('subtotal')) || 0,
        shipping_cost: parseFloat(formData.get('shipping_cost')) || 0,
        total_amount: parseFloat(formData.get('total_amount')) || 0,
        items: editPurchaseOrderItems.map(item => ({
            request_item_id: item.request_item_id,
            item_name: item.item_name,
            description: item.description,
            quantity: parseFloat(item.quantity) || 0,
            unit: item.unit,
            unit_cost: parseFloat(item.unit_cost) || 0,
            total_cost: parseFloat(item.total_cost) || 0,
            expected_delivery_date: item.expected_delivery_date || null,
            status: item.status || 'pending',
            notes: item.notes || null
        }))
    };

    try {
        const response = await fetch('api/purchase_orders.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to update purchase order');
        }

        showNotification('Purchase order updated successfully', 'success');
        closeEditPurchaseOrderModal();
        loadPurchaseOrderStats();
        loadPurchaseOrders();
    } catch (error) {
        console.error('Failed to update purchase order:', error);
        showNotification(error.message || 'Failed to update purchase order', 'error');
    }
}
