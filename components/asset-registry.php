<!-- Item Registry Module -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Item Registry & Tagging</h1>
            <p class="text-sm text-gray-500 mt-1">Track, tag, and organize items with filters and quick actions.</p>
        </div>
        <button onclick="App.openAssetModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full sm:w-auto shadow">
            <i class="fas fa-plus mr-2"></i>Add Item
        </button>
    </div>

    <!-- Filter and Search -->
    <div class="bg-white rounded-lg shadow p-4 lg:p-6 mb-6 border border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" id="searchAssets" placeholder="Search items..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select id="categoryFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Categories</option>
                    <!-- Categories will be loaded dynamically -->
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="assigned">Assigned</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="damaged">Damaged</option>
                    <option value="lost">Lost</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tag</label>
                <select id="tagFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Tags</option>
                    <!-- Tags will be loaded dynamically -->
                </select>
            </div>
            <div class="flex items-end sm:col-span-2 lg:col-span-1">
                <button onclick="filterAssets()" class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2 pt-4 border-top border-gray-200">
            <button onclick="App.openTagModal()" class="bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 text-sm flex-1 sm:flex-initial shadow-sm">
                <i class="fas fa-tags mr-1"></i><span class="hidden sm:inline">Manage </span>Tags
            </button>
            <button onclick="exportAssets()" class="bg-purple-600 text-white px-3 py-2 rounded-md hover:bg-purple-700 text-sm flex-1 sm:flex-initial shadow-sm">
                <i class="fas fa-download mr-1"></i>Export
            </button>
            <button onclick="bulkActions()" class="bg-orange-600 text-white px-3 py-2 rounded-md hover:bg-orange-700 text-sm flex-1 sm:flex-initial shadow-sm">
                <i class="fas fa-tasks mr-1"></i><span class="hidden sm:inline">Bulk </span>Actions
            </button>
        </div>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-200 card-head">
            <h3 class="text-lg font-medium text-gray-900">Items List</h3>
        </div>
        <div class="table-responsive">
            <table class="min-w-full divide-y divide-gray-200 table-striped text-sm" id="assetsTable">
                <thead class="bg-gray-50 text-xs">
                    <tr>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded">
                        </th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Tags</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Location</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">QR Code</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Value</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="assetsTableBody">
                    <!-- Item rows will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex-1 flex justify-between sm:hidden" id="mobilePagination">
            <!-- Mobile pagination buttons will be rendered here by JavaScript -->
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700" id="paginationInfo">
                    <!-- Pagination info will be rendered here by JavaScript -->
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination" id="paginationControls">
                    <!-- Pagination controls will be rendered here by JavaScript -->
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Item Modal -->
<div id="assetModal" class="fixed inset-0 modal-overlay overflow-y-auto h-full w-full hidden z-50">
    <div class="modal-panel relative top-10 mx-auto p-5 w-11/12 md:w-3/4 lg:w-1/2 bg-white">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-3">
            <h3 class="text-lg font-medium text-gray-900" id="assetModalTitle">Add Item</h3>
            <button onclick="App.closeAssetModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="assetForm" onsubmit="handleAssetSubmit(event)">
            <input type="hidden" id="assetId" name="asset_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Code *</label>
                    <input type="text" id="assetCode" name="asset_code" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                    <input type="text" id="assetName" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Item Description</label>
                <textarea id="assetDescription" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select id="assetCategory" name="category"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Category</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="assetStatus" name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="available">Available</option>
                        <option value="assigned">Assigned</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Damaged</option>
                        <option value="lost">Lost</option>
                        <option value="disposed">Disposed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Location</label>
                    <input type="text" id="assetLocation" name="location"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Condition Status</label>
                    <select id="assetConditionStatus" name="condition_status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                    <input type="date" id="assetPurchaseDate" name="purchase_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Cost</label>
                    <input type="number" step="0.01" id="assetPurchaseCost" name="purchase_cost"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Value</label>
                    <input type="number" step="0.01" id="assetCurrentValue" name="current_value"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Tags Section -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Item Tags</label>
                <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-md bg-gray-50 mb-2" id="selectedTags">
                    <!-- Selected tags will appear here -->
                </div>
                <div class="flex gap-2">
                    <select id="availableTags" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a tag to add</option>
                    </select>
                    <button type="button" onclick="addTagToAsset()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
                <button type="button" onclick="App.closeAssetModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 shadow">
                    <span id="assetSubmitText">Save Item</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tag Management Modal -->
<div id="tagModal" class="fixed inset-0 modal-overlay overflow-y-auto h-full w-full hidden z-50">
    <div class="modal-panel relative top-10 mx-auto p-5 w-11/12 md:w-2/3 lg:w-1/2 bg-white">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-3">
            <h3 class="text-lg font-medium text-gray-900">Manage Tags</h3>
            <button onclick="App.closeTagModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Add New Tag Form -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
            <h4 class="font-medium text-gray-900 mb-3">Create New Tag</h4>
            <form id="tagForm" onsubmit="handleTagSubmit(event)">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <input type="text" id="tagName" name="name" placeholder="Tag name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <input type="color" id="tagColor" name="color" value="#3B82F6"
                               class="w-full h-10 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Create Tag
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <input type="text" id="tagDescription" name="description" placeholder="Description (optional)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                </div>
            </form>
        </div>

        <!-- Existing Tags List -->
        <div class="max-h-64 overflow-y-auto">
            <div id="tagsList">
                <!-- Tags will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="fixed inset-0 modal-overlay overflow-y-auto h-full w-full hidden z-50">
    <div class="modal-panel relative top-10 mx-auto p-5 w-96 bg-white">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-3">
            <h3 class="text-lg font-medium text-gray-900">QR Code</h3>
            <button onclick="App.closeQRModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="text-center">
            <div id="qrCodeContent" class="mb-4">
                <!-- QR code will be displayed here -->
            </div>
            <div class="flex justify-center gap-2">
                <button onclick="printQRCode()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button onclick="downloadQRCode()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>Download
                </button>
            </div>
        </div>
    </div>
</div>