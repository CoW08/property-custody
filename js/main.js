// Main application controller
class App {
    static currentModule = 'dashboard';

    static init() {
        this.setupNavigation();
        this.setupModal();
    }

    static setupNavigation() {
        // Navigation is now handled by direct PHP page links
        // No need to prevent default behavior anymore
        console.log('Navigation setup: Using direct page navigation');
    }

    static setupModal() {
        const modalOverlay = document.getElementById('modalOverlay');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            });
        }
    }

    static setActiveMenuItem(activeItem) {
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.classList.remove('border-r-4', 'border-blue-600', 'bg-blue-50', 'text-blue-600');
            item.classList.add('text-gray-700');
        });

        activeItem.classList.remove('text-gray-700');
        activeItem.classList.add('border-r-4', 'border-blue-600', 'bg-blue-50', 'text-blue-600');
    }

    static async showModule(moduleName) {
        this.currentModule = moduleName;

        // Hide current content
        const dashboardModule = document.getElementById('dashboard-module');
        const dynamicContent = document.getElementById('dynamic-content');

        if (moduleName === 'dashboard') {
            dashboardModule.style.display = 'block';
            dynamicContent.classList.add('hidden');
            await Dashboard.loadData();
        } else {
            dashboardModule.style.display = 'none';
            dynamicContent.classList.remove('hidden');
            await this.loadModuleContent(moduleName);
        }
    }

    static async loadModuleContent(moduleName) {
        const dynamicContent = document.getElementById('dynamic-content');

        try {
            let content = '';

            switch (moduleName) {
                case 'asset-registry':
                    content = await this.loadComponentFile('components/asset-registry.php');
                    break;
                case 'property-issuance':
                    content = await this.getPropertyIssuanceContent();
                    break;
                case 'supplies-inventory':
                    content = await this.loadComponentFile('components/supplies-inventory.php');
                    break;
                case 'custodian-assignment':
                    content = await this.getCustodianAssignmentContent();
                    break;
                case 'maintenance':
                    content = await this.getMaintenanceContent();
                    break;
                case 'damaged-items':
                    content = await this.getDamagedItemsContent();
                    break;
                case 'property-audit':
                    content = await this.getPropertyAuditContent();
                    break;
                case 'procurement':
                    content = await this.getProcurementContent();
                    break;
                case 'reports':
                    content = await this.getReportsContent();
                    break;
                case 'user-roles':
                    content = await this.getUserRolesContent();
                    break;
                default:
                    content = '<div class="p-8"><h1 class="text-2xl font-bold">Module Under Development</h1><p>This module is currently being developed.</p></div>';
            }

            dynamicContent.innerHTML = content;

            // Initialize module-specific functionality
            this.initializeModuleFeatures(moduleName);

        } catch (error) {
            console.error('Error loading module content:', error);
            dynamicContent.innerHTML = '<div class="p-8"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error loading module content</div></div>';
        }
    }

    static async loadComponentFile(filePath) {
        try {
            const response = await fetch(filePath);
            if (!response.ok) {
                throw new Error('Component file not found');
            }
            return await response.text();
        } catch (error) {
            console.error('Error loading component:', error);
            return '<div class="p-8"><div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">Component under development</div></div>';
        }
    }

    static async getAssetRegistryContent() {
        const assets = await API.getAssets();

        return `
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Asset Registry & Tagging</h1>
                    <button onclick="App.openAssetModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Asset
                    </button>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${assets.map(asset => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${asset.asset_code}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${asset.name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${asset.category_name || 'N/A'}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                            asset.status === 'available' ? 'bg-green-100 text-green-800' :
                                            asset.status === 'assigned' ? 'bg-blue-100 text-blue-800' :
                                            asset.status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-red-100 text-red-800'
                                        }">
                                            ${asset.status}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${asset.location || 'N/A'}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="App.editAsset(${asset.id})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button onclick="App.deleteAsset(${asset.id})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    static async getSuppliesInventoryContent() {
        const supplies = await API.getSupplies();

        return `
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Supplies Inventory Management</h1>
                    <div class="space-x-2">
                        <button onclick="App.openSupplyModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Add Supply
                        </button>
                        <button onclick="App.openTransactionModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-exchange-alt mr-2"></i>New Transaction
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${supplies.map(supply => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${supply.item_code}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${supply.name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${supply.current_stock <= supply.minimum_stock ? 'text-red-600 font-bold' : ''}">${supply.current_stock}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${supply.minimum_stock}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${supply.unit}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                            supply.current_stock <= supply.minimum_stock ? 'bg-red-100 text-red-800' :
                                            supply.status === 'active' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        }">
                                            ${supply.current_stock <= supply.minimum_stock ? 'Low Stock' : supply.status}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="App.editSupply(${supply.id})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button onclick="App.deleteSupply(${supply.id})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    static getPropertyIssuanceContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Property Issuance & Acknowledgment</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle property assignments and acknowledgments.</p>
                </div>
            </div>
        `;
    }

    static getCustodianAssignmentContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Custodian Assignment & Transfer</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle custodian assignments and transfers.</p>
                </div>
            </div>
        `;
    }

    static getMaintenanceContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Preventive Maintenance Scheduling</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle maintenance scheduling and tracking.</p>
                </div>
            </div>
        `;
    }

    static getDamagedItemsContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Lost, Damaged, or Unserviceable Items</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle damaged and lost items reporting.</p>
                </div>
            </div>
        `;
    }

    static getPropertyAuditContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Property Audit & Physical Inventory</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle property audits and physical inventory.</p>
                </div>
            </div>
        `;
    }

    static getProcurementContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Procurement Coordination</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle procurement requests and coordination.</p>
                </div>
            </div>
        `;
    }

    static getReportsContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Reports & Analytics</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will provide various reports and analytics.</p>
                </div>
            </div>
        `;
    }

    static getUserRolesContent() {
        return `
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">User Roles & Access Control</h1>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">This module is under development. It will handle user roles and access control.</p>
                </div>
            </div>
        `;
    }

    static initializeModuleFeatures(moduleName) {
        // Initialize module-specific features after content is loaded
        switch (moduleName) {
            case 'asset-registry':
                // Asset registry specific initialization
                break;
            case 'supplies-inventory':
                // Supplies inventory specific initialization
                break;
            // Add other modules as needed
        }
    }

    static openModal(content) {
        const modalContent = document.getElementById('modalContent');
        const modalOverlay = document.getElementById('modalOverlay');

        modalContent.innerHTML = content;
        modalOverlay.classList.remove('hidden');
        modalOverlay.classList.add('flex');
    }

    static closeModal() {
        const modalOverlay = document.getElementById('modalOverlay');
        modalOverlay.classList.remove('flex');
        modalOverlay.classList.add('hidden');
    }

    // Asset modal functions
    static openAssetModal(assetId = null) {
        const isEdit = assetId !== null;
        const title = isEdit ? 'Edit Asset' : 'Add New Asset';

        const content = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">${title}</h2>
                    <button onclick="App.closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="assetForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset Code</label>
                            <input type="text" id="asset_code" name="asset_code" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" id="name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <input type="text" id="brand" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                            <input type="text" id="model" name="model" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                            <input type="number" id="purchase_cost" name="purchase_cost" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input type="text" id="location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="App.closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">${isEdit ? 'Update' : 'Create'} Asset</button>
                    </div>
                </form>
            </div>
        `;

        this.openModal(content);

        // Setup form submission
        document.getElementById('assetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleAssetFormSubmission(isEdit, assetId);
        });
    }

    static async handleAssetFormSubmission(isEdit, assetId) {
        const form = document.getElementById('assetForm');
        const formData = new FormData(form);
        const assetData = Object.fromEntries(formData);

        try {
            if (isEdit) {
                await API.updateAsset(assetId, assetData);
                showNotification('Asset updated successfully', 'success');
            } else {
                await API.createAsset(assetData);
                showNotification('Asset created successfully', 'success');
            }

            this.closeModal();
            this.showModule('asset-registry'); // Refresh the module
        } catch (error) {
            showNotification(error.message || 'Operation failed', 'error');
        }
    }

    static async editAsset(id) {
        // Implementation for editing asset
        this.openAssetModal(id);
    }

    static async deleteAsset(id) {
        if (confirm('Are you sure you want to delete this asset?')) {
            try {
                await API.deleteAsset(id);
                showNotification('Asset deleted successfully', 'success');
                this.showModule('asset-registry');
            } catch (error) {
                showNotification(error.message || 'Delete failed', 'error');
            }
        }
    }

    // Similar functions for supplies
    static openSupplyModal() {
        // Implementation for supply modal
        showNotification('Supply modal functionality will be implemented', 'info');
    }

    static openTransactionModal() {
        // Implementation for transaction modal
        showNotification('Transaction modal functionality will be implemented', 'info');
    }

    static editSupply(id) {
        // Implementation for editing supply
        showNotification('Edit supply functionality will be implemented', 'info');
    }

    static deleteSupply(id) {
        // Implementation for deleting supply
        showNotification('Delete supply functionality will be implemented', 'info');
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});