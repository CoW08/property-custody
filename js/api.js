// API Configuration
const API_BASE_URL = 'api/';

class API {
    static async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
            },
            ...options
        };

        try {
            console.log('API Request:', url);
            const response = await fetch(url, config);
            console.log('API Response status:', response.status);
            
            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('JSON parse failed for', url, '- status:', response.status);
                throw new Error('Server returned invalid response (status ' + response.status + ')');
            }
            console.log('API Response data:', data);

            // Handle session expiry
            if (data.session_expired || response.status === 401) {
                window.location.href = 'login.php?session=expired';
                throw new Error('Session expired');
            }

            if (!response.ok) {
                throw new Error(data.message || data.error || 'API request failed (status ' + response.status + ')');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            console.error('API URL:', url);
            throw error;
        }
    }

    // Authentication
    static async login(username, password) {
        return this.request('auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
    }

    static async logout() {
        return this.request('auth.php?action=logout', {
            method: 'POST'
        });
    }

    static async getCurrentUser() {
        return this.request('users.php?action=profile');
    }

    static async updateProfile(payload) {
        return this.request('users.php?action=update_profile', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
    }

    static async changePassword(payload) {
        return this.request('users.php?action=change_password', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
    }

    // Dashboard
    static async getDashboardStats() {
        return this.request('dashboard.php?action=stats');
    }

    static async getRecentActivities() {
        return this.request('dashboard.php?action=recent_activities');
    }

    static async getAlerts() {
        return this.request('dashboard.php?action=alerts');
    }

    static async getNotifications() {
        return this.request('dashboard.php?action=notifications');
    }

    // Forecasting
    static async getForecastOverview() {
        return this.request('forecasting.php?action=overview');
    }

    static async getForecastDemand() {
        return this.request('forecasting.php?action=demand_forecast');
    }

    static async getForecastReorders() {
        return this.request('forecasting.php?action=reorder_recommendations');
    }

    static async getForecastAlerts() {
        return this.request('forecasting.php?action=alerts');
    }

    static async getForecastSeasonality() {
        return this.request('forecasting.php?action=seasonality');
    }

    // Assets
    static async getAssets() {
        return this.request('assets.php');
    }

    static async getAsset(id) {
        return this.request(`assets.php?id=${id}`);
    }

    static async createAsset(assetData) {
        return this.request('assets.php', {
            method: 'POST',
            body: JSON.stringify(assetData)
        });
    }

    static async updateAsset(id, assetData) {
        return this.request(`assets.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(assetData)
        });
    }

    static async archiveAsset(id, payload = {}) {
        return this.request(`assets.php?id=${id}`, {
            method: 'DELETE',
            body: JSON.stringify(payload)
        });
    }

    // Supplies
    static async getSupplies() {
        return this.request('supplies.php');
    }

    static async getSupply(id) {
        return this.request(`supplies.php?id=${id}`);
    }

    static async createSupply(supplyData) {
        return this.request('supplies.php', {
            method: 'POST',
            body: JSON.stringify(supplyData)
        });
    }

    static async updateSupply(id, supplyData) {
        return this.request(`supplies.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(supplyData)
        });
    }

    static async archiveSupply(id, payload = {}) {
        return this.request(`supplies.php?id=${id}`, {
            method: 'DELETE',
            body: JSON.stringify(payload)
        });
    }

    static async getSupplyTransactions() {
        return this.request('supplies.php?action=transactions');
    }

    static async createTransaction(transactionData) {
        return this.request('supplies.php?action=transaction', {
            method: 'POST',
            body: JSON.stringify(transactionData)
        });
    }

    // Waste management
    static async getWasteRecords(params = {}) {
        const query = new URLSearchParams(params).toString();
        return this.request(`waste_management.php${query ? `?${query}` : ''}`);
    }

    static async restoreWasteRecord(id) {
        return this.request('waste_management.php?action=restore', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
    }

    static async disposeWasteRecord(id, payload = {}) {
        return this.request('waste_management.php?action=dispose', {
            method: 'POST',
            body: JSON.stringify({ id, ...payload })
        });
    }

    // Property Issuance
    static async getPropertyIssuances() {
        return this.request('property_issuance.php');
    }

    static async getPropertyIssuance(id) {
        return this.request(`property_issuance.php?id=${id}`);
    }

    static async createPropertyIssuance(issuanceData) {
        return this.request('property_issuance.php', {
            method: 'POST',
            body: JSON.stringify(issuanceData)
        });
    }

    static async updatePropertyIssuance(id, issuanceData) {
        return this.request(`property_issuance.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(issuanceData)
        });
    }

    static async deletePropertyIssuance(id) {
        return this.request(`property_issuance.php?id=${id}`, {
            method: 'DELETE'
        });
    }

    static async getAvailableAssets() {
        return this.request('assets.php?status=available');
    }

    // Reports
    static async getOverviewReport() {
        return this.request('reports.php?action=overview');
    }

    static async getAssetsReport() {
        return this.request('reports.php?action=assets');
    }

    static async getMaintenanceReport() {
        return this.request('reports.php?action=maintenance');
    }

    static async getProcurementReport() {
        return this.request('reports.php?action=procurement');
    }

    static async getAuditReport() {
        return this.request('reports.php?action=audit');
    }

    static async getFinancialReport() {
        return this.request('reports.php?action=financial');
    }
}

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatCurrency(amount) {
    if (!amount) return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Legacy API call function for backward compatibility
async function apiCall(url, method = 'GET', data = null) {
    const config = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        config.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, config);
        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            console.error('JSON parse failed for', url, '- status:', response.status);
            throw new Error('Server returned invalid response (status ' + response.status + ')');
        }

        // Handle session expiry
        if (result.session_expired || response.status === 401) {
            window.location.href = 'login.php?session=expired';
            throw new Error('Session expired');
        }

        if (!response.ok) {
            throw new Error(result.error || result.message || 'Request failed (status ' + response.status + ')');
        }

        return result;
    } catch (error) {
        console.error('API Call Error:', error);
        throw error;
    }
}

// Alert function for user notifications
function showAlert(message, type = 'info') {
    showNotification(message, type);
}

// Modal utility functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Enhanced notification functions for procurement.js compatibility
function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showLoading() {
    // Create or show loading indicator
    let loadingIndicator = document.getElementById('loadingIndicator');
    if (!loadingIndicator) {
        loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'loadingIndicator';
        loadingIndicator.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
        loadingIndicator.innerHTML = `
            <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">Loading...</span>
            </div>
        `;
        document.body.appendChild(loadingIndicator);
    }
    loadingIndicator.classList.remove('hidden');
}

function hideLoading() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.classList.add('hidden');
    }
}