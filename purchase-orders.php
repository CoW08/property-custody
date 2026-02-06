<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Purchase Orders - Property Custodian Management";

ob_start();
?>

<!-- Purchase Orders Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 sm:mb-8 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Purchase Orders</h1>
                    <p class="text-sm text-gray-500 mt-1">Generate and track purchase orders generated from approved procurement requests.</p>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <button id="createPurchaseOrderBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg transition duration-200 text-sm sm:text-base shadow">
                        <i class="fas fa-file-invoice-dollar mr-1 sm:mr-2"></i>Create Purchase Order
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8" id="purchaseOrderStats">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm" id="poTotalCard">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-white to-slate-50"></div>
                    <div class="relative">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-blue-600">Total Purchase Orders</p>
                        <p id="totalPurchaseOrders" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                        <p class="mt-1 text-xs text-slate-500">Across all statuses</p>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 via-white to-slate-50"></div>
                    <div class="relative">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-emerald-600">Received</p>
                        <p id="receivedPurchaseOrders" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                        <p class="mt-1 text-xs text-slate-500">Fully received purchase orders</p>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 via-white to-slate-50"></div>
                    <div class="relative">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-amber-600">Pending</p>
                        <p id="pendingPurchaseOrders" class="mt-2 text-3xl font-bold text-slate-900">0</p>
                        <p class="mt-1 text-xs text-slate-500">Awaiting processing</p>
                    </div>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-white to-slate-50"></div>
                    <div class="relative">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-purple-600">Total Order Value</p>
                        <p id="totalPurchaseOrderValue" class="mt-2 text-3xl font-bold text-slate-900">₱0</p>
                        <p class="mt-1 text-xs text-slate-500">Sum of purchase order totals</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-6 border border-gray-200">
                <div class="space-y-4 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-4 sm:items-center sm:justify-between">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 flex-1">
                        <input type="text" id="searchPurchaseOrders" placeholder="Search purchase orders..."
                               class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">

                        <select id="statusFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <select id="requestFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="">All Requests</option>
                        </select>

                        <input type="date" id="orderDateFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" />
                    </div>

                    <button id="refreshPurchaseOrders" class="w-full sm:w-auto bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200 text-sm sm:text-base">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Purchase Orders Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Orders</h3>
                    <div class="flex items-center gap-2">
                        <button id="exportPurchaseOrders" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-file-export mr-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Mobile Card View (hidden on larger screens) -->
                <div class="block lg:hidden" id="mobilePurchaseOrderList">
                    <!-- Mobile cards will be populated here -->
                </div>

                <!-- Desktop Table View (hidden on mobile) -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-striped text-sm">
                        <thead class="bg-gray-50 text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseOrdersTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-sm text-gray-500 text-center sm:text-left" id="purchaseOrdersPaginationInfo">
                            Showing 0 to 0 of 0 results
                        </div>
                        <div class="flex items-center justify-center space-x-2" id="purchaseOrdersPaginationControls">
                            <!-- Pagination controls will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- New Purchase Order Modal -->
<div id="purchaseOrderModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto z-50 flex items-start justify-center p-4 sm:p-8">
    <div class="modal-panel relative w-full sm:w-11/12 max-w-4xl bg-white p-4 sm:p-6 min-h-screen sm:min-h-0">
        <div class="mt-1">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Create Purchase Order</h3>
                <button class="modal-close text-gray-400 hover:text-gray-600" id="closePurchaseOrderModal">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <form id="purchaseOrderForm" class="mt-4 sm:mt-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Linked Request *</label>
                        <select name="request_id" id="purchaseOrderRequest" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Approved Request</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Name *</label>
                        <input type="text" name="vendor_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Email</label>
                        <input type="email" name="vendor_email"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Phone</label>
                        <input type="text" name="vendor_phone"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Date *</label>
                        <input type="date" name="order_date" id="purchaseOrderDate" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Address</label>
                    <textarea name="vendor_address" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Enter vendor address details"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_terms" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select payment method</option>
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="GCash">GCash</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Method</label>
                        <select name="shipping_method" id="purchaseOrderShippingMethod" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select shipping method</option>
                            <option value="Standard">Standard</option>
                            <option value="Express">Express</option>
                            <option value="Pickup">Pickup</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Add any relevant notes or instructions"></textarea>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Purchase Order Items</h4>
                    <p class="text-sm text-gray-500 mb-3">Purchase order items will auto-populate from the linked procurement request. You can also pull items directly from Supplies Inventory.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Add Inventory Item</label>
                            <select id="purchaseOrderSupplySelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Loading inventory...</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="purchaseOrderAddItemBtn" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-plus mr-2"></i>Add Item
                            </button>
                        </div>
                    </div>
                    <div id="purchaseOrderItemsContainer" class="space-y-3">
                        <!-- Items will be rendered here -->
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal</label>
                        <input type="number" name="subtotal" step="0.01" min="0" id="purchaseOrderSubtotal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" id="purchaseOrderShippingCost" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                        <input type="number" name="total_amount" step="0.01" min="0" id="purchaseOrderTotalAmount" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                    <button type="button" id="cancelPurchaseOrder" class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition duration-200 text-sm sm:text-base">
                        Cancel
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 text-sm sm:text-base">
                        <i class="fas fa-save mr-2"></i>Save Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Purchase Order Modal -->
<div id="viewPurchaseOrderModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto z-40 flex items-start justify-center p-4 sm:p-8">
    <div class="modal-panel relative w-full sm:w-11/12 max-w-4xl bg-white p-4 sm:p-6 min-h-screen sm:min-h-0">
        <div class="mt-1">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Purchase Order Details</h3>
                <button class="modal-close text-gray-400 hover:text-gray-600" id="closeViewPurchaseOrderModal">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <div id="purchaseOrderDetailsContent" class="mt-4 sm:mt-6 space-y-4">
                <!-- Content populated dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Purchase Order Modal -->
<div id="editPurchaseOrderModal" class="hidden fixed inset-0 modal-overlay overflow-y-auto z-40 flex items-start justify-center p-4 sm:p-8">
    <div class="modal-panel relative w-full sm:w-11/12 max-w-4xl bg-white p-4 sm:p-6 min-h-screen sm:min-h-0">
        <div class="mt-1">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Edit Purchase Order</h3>
                <button class="modal-close text-gray-400 hover:text-gray-600" id="closeEditPurchaseOrderModal">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <form id="editPurchaseOrderForm" class="mt-4 sm:mt-6 space-y-4">
                <input type="hidden" name="id" id="editPurchaseOrderId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Name *</label>
                        <input type="text" name="vendor_name" id="editVendorName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Date *</label>
                        <input type="date" name="order_date" id="editOrderDate" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" id="editExpectedDeliveryDate"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_terms" id="editPaymentMethod"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select payment method</option>
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="GCash">GCash</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Method</label>
                        <select name="shipping_method" id="editShippingMethod"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select shipping method</option>
                            <option value="Standard">Standard</option>
                            <option value="Express">Express</option>
                            <option value="Pickup">Pickup</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="editStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" id="editNotes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Update notes or instructions"></textarea>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Purchase Order Items</h4>
                    <div id="editPurchaseOrderItemsContainer" class="space-y-3">
                        <!-- Editable items populated dynamically -->
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal</label>
                        <input type="number" name="subtotal" step="0.01" min="0" id="editSubtotal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" id="editShippingCost" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                        <input type="number" name="total_amount" step="0.01" min="0" id="editTotalAmount" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                    <button type="button" id="cancelEditPurchaseOrder" class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition duration-200 text-sm sm:text-base">
                        Cancel
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 text-sm sm:text-base">
                        <i class="fas fa-save mr-2"></i>Update Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/api.js"></script>
<script src="js/purchase_orders.js?v=2026020710"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
