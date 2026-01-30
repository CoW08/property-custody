class MaintenanceManager {
    constructor() {
        this.apiBase = 'api/maintenance.php';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadMaintenanceStats();
        this.loadMaintenanceList();
        this.loadAssets();
        this.loadTechnicians();
        this.setMinDate();
    }

    bindEvents() {
        document.getElementById('schedule-btn').addEventListener('click', () => {
            this.showMaintenanceForm();
        });

        document.getElementById('cancel-btn').addEventListener('click', () => {
            this.hideMaintenanceForm();
        });

        document.getElementById('maintenance-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.scheduleMaintenanceTask();
        });

        // Edit modal events
        document.getElementById('close-edit-modal').addEventListener('click', () => {
            this.hideEditModal();
        });

        document.getElementById('cancel-edit-btn').addEventListener('click', () => {
            this.hideEditModal();
        });

        document.getElementById('edit-maintenance-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateMaintenanceTask();
        });

        // Close modal when clicking outside
        document.getElementById('edit-maintenance-modal').addEventListener('click', (e) => {
            if (e.target.id === 'edit-maintenance-modal') {
                this.hideEditModal();
            }
        });
    }

    setMinDate() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('scheduled_date').min = today;
        document.getElementById('scheduled_date').value = today;
    }

    showMaintenanceForm() {
        document.getElementById('maintenance-form-section').style.display = 'block';
        document.getElementById('schedule-btn').style.display = 'none';
        document.getElementById('maintenance-form').reset();
        this.setMinDate();
    }

    hideMaintenanceForm() {
        document.getElementById('maintenance-form-section').style.display = 'none';
        document.getElementById('schedule-btn').style.display = 'inline-flex';
    }

    async loadMaintenanceStats() {
        try {
            const response = await fetch(`${this.apiBase}?action=stats`);
            const data = await response.json();

            if (data.stats) {
                const scheduled = document.getElementById('scheduledTasks');
                const dueToday = document.getElementById('pendingTasks');
                const overdue = document.getElementById('criticalIssues');
                const completed = document.getElementById('completedTasks');

                if (scheduled) scheduled.textContent = data.stats.scheduled ?? 0;
                if (dueToday) dueToday.textContent = data.stats.due_today ?? 0;
                if (overdue) overdue.textContent = data.stats.overdue ?? 0;
                if (completed) completed.textContent = data.stats.completed ?? 0;
            }
        } catch (error) {
            console.error('Error loading maintenance stats:', error);
            this.showNotification('Error loading maintenance statistics', 'error');
        }
    }

    async loadMaintenanceList() {
        try {
            const response = await fetch(`${this.apiBase}?action=list`);
            const data = await response.json();

            this.renderMaintenanceTable(data.maintenances || []);
        } catch (error) {
            console.error('Error loading maintenance list:', error);
            this.showNotification('Error loading maintenance list', 'error');
        }
    }

    async loadAssets() {
        try {
            const response = await fetch(`${this.apiBase}?action=assets`);
            const data = await response.json();

            const assetSelect = document.getElementById('asset_id');
            assetSelect.innerHTML = '<option value="">Choose asset for maintenance</option>';

            if (data.assets && data.assets.length > 0) {
                data.assets.forEach(asset => {
                    const option = document.createElement('option');
                    option.value = asset.id;
                    option.textContent = `${asset.asset_code} - ${asset.name} (${asset.location})`;
                    assetSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading assets:', error);
            this.showNotification('Error loading assets', 'error');
        }
    }

    async loadTechnicians() {
        try {
            const response = await fetch(`${this.apiBase}?action=technicians`);
            const data = await response.json();

            const technicianSelect = document.getElementById('assigned_to');
            technicianSelect.innerHTML = '<option value="">Select technician</option>';

            if (data.technicians && data.technicians.length > 0) {
                data.technicians.forEach(technician => {
                    const option = document.createElement('option');
                    option.value = technician.id;
                    option.textContent = `${technician.full_name} - ${technician.department}`;
                    technicianSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading technicians:', error);
            this.showNotification('Error loading technicians', 'error');
        }
    }

    renderMaintenanceTable(maintenances) {
        const tbody = document.getElementById('maintenance-table-body');
        const noDataRow = document.getElementById('no-maintenance-row');

        if (maintenances.length === 0) {
            noDataRow.style.display = 'table-row';
            // Clear any existing maintenance rows
            const existingRows = tbody.querySelectorAll('tr:not(#no-maintenance-row)');
            existingRows.forEach(row => row.remove());
            return;
        }

        noDataRow.style.display = 'none';

        // Clear existing rows except no-data row
        const existingRows = tbody.querySelectorAll('tr:not(#no-maintenance-row)');
        existingRows.forEach(row => row.remove());

        maintenances.forEach(maintenance => {
            const row = this.createMaintenanceRow(maintenance);
            tbody.appendChild(row);
        });
    }

    createMaintenanceRow(maintenance) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';

        const statusColors = {
            scheduled: 'bg-blue-100 text-blue-800',
            in_progress: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800'
        };

        const priorityColors = {
            low: 'bg-gray-100 text-gray-800',
            medium: 'bg-blue-100 text-blue-800',
            high: 'bg-orange-100 text-orange-800',
            critical: 'bg-red-100 text-red-800'
        };

        const statusClass = statusColors[maintenance.status] || 'bg-gray-100 text-gray-800';
        const priorityClass = priorityColors[maintenance.priority] || 'bg-gray-100 text-gray-800';

        row.innerHTML = `
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${maintenance.asset_name}</div>
                <div class="text-sm text-gray-500">${maintenance.asset_code}</div>
                <div class="sm:hidden text-xs text-gray-500 mt-1">${this.formatMaintenanceType(maintenance.maintenance_type)}</div>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                <span class="text-sm text-gray-900">${this.formatMaintenanceType(maintenance.maintenance_type)}</span>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${this.formatDate(maintenance.scheduled_date)}
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden lg:table-cell">
                ${maintenance.assigned_technician || 'Unassigned'}
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${priorityClass}">
                    ${maintenance.priority.charAt(0).toUpperCase() + maintenance.priority.slice(1)}
                </span>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                    ${maintenance.status.replace('_', ' ').charAt(0).toUpperCase() + maintenance.status.replace('_', ' ').slice(1)}
                </span>
                <div class="md:hidden text-xs text-gray-500 mt-1">
                    ${maintenance.priority.charAt(0).toUpperCase() + maintenance.priority.slice(1)} Priority
                </div>
            </td>
            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-1 sm:space-x-2">
                    ${this.getActionButtons(maintenance)}
                </div>
            </td>
        `;

        return row;
    }

    getActionButtons(maintenance) {
        let buttons = '';

        // Always show View Details button first
        buttons += `
            <button onclick="maintenanceManager.viewMaintenance(${maintenance.id})"
                    class="text-blue-600 hover:text-blue-900" title="View Details">
                <i class="fas fa-eye"></i>
            </button>
        `;

        if (maintenance.status === 'scheduled') {
            buttons += `
                <button onclick="maintenanceManager.updateMaintenanceStatus(${maintenance.id}, 'in_progress')"
                        class="text-green-600 hover:text-green-900" title="Start Maintenance">
                    <i class="fas fa-play"></i>
                </button>
                <button onclick="maintenanceManager.editMaintenance(${maintenance.id})"
                        class="text-indigo-600 hover:text-indigo-900" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="maintenanceManager.cancelMaintenance(${maintenance.id})"
                        class="text-red-600 hover:text-red-900" title="Cancel">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else if (maintenance.status === 'in_progress') {
            buttons += `
                <button onclick="maintenanceManager.completeMaintenance(${maintenance.id})"
                        class="text-green-600 hover:text-green-900" title="Mark Complete">
                    <i class="fas fa-check"></i>
                </button>
                <button onclick="maintenanceManager.editMaintenance(${maintenance.id})"
                        class="text-indigo-600 hover:text-indigo-900" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        }

        return buttons;
    }

    formatMaintenanceType(type) {
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    async scheduleMaintenanceTask() {
        const formData = new FormData(document.getElementById('maintenance-form'));
        const data = Object.fromEntries(formData.entries());

        // Remove empty fields
        Object.keys(data).forEach(key => {
            if (data[key] === '') {
                delete data[key];
            }
        });

        try {
            const response = await fetch(`${this.apiBase}?action=schedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this.showNotification('Maintenance task scheduled successfully!', 'success');
                this.hideMaintenanceForm();
                this.loadMaintenanceStats();
                this.loadMaintenanceList();
            } else {
                this.showNotification(result.error || 'Failed to schedule maintenance task', 'error');
            }
        } catch (error) {
            console.error('Error scheduling maintenance:', error);
            this.showNotification('Error scheduling maintenance task', 'error');
        }
    }

    async updateMaintenanceStatus(id, status) {
        try {
            const response = await fetch(`${this.apiBase}?action=update_status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, status })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this.showNotification(`Maintenance status updated to ${status.replace('_', ' ')}`, 'success');
                this.loadMaintenanceStats();
                this.loadMaintenanceList();
            } else {
                this.showNotification(result.error || 'Failed to update maintenance status', 'error');
            }
        } catch (error) {
            console.error('Error updating maintenance status:', error);
            this.showNotification('Error updating maintenance status', 'error');
        }
    }

    async completeMaintenance(id) {
        const actualCost = prompt('Enter actual cost (optional):');
        const data = { id, status: 'completed' };

        if (actualCost && !isNaN(actualCost)) {
            data.actual_cost = parseFloat(actualCost);
        }

        try {
            const response = await fetch(`${this.apiBase}?action=update_status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this.showNotification('Maintenance marked as completed!', 'success');
                this.loadMaintenanceStats();
                this.loadMaintenanceList();
            } else {
                this.showNotification(result.error || 'Failed to complete maintenance', 'error');
            }
        } catch (error) {
            console.error('Error completing maintenance:', error);
            this.showNotification('Error completing maintenance', 'error');
        }
    }

    async cancelMaintenance(id) {
        if (!confirm('Are you sure you want to cancel this maintenance task?')) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBase}?action=cancel`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this.showNotification('Maintenance task cancelled', 'success');
                this.loadMaintenanceStats();
                this.loadMaintenanceList();
            } else {
                this.showNotification(result.error || 'Failed to cancel maintenance', 'error');
            }
        } catch (error) {
            console.error('Error cancelling maintenance:', error);
            this.showNotification('Error cancelling maintenance', 'error');
        }
    }

    async editMaintenance(id) {
        try {
            // Load maintenance details
            const response = await fetch(`${this.apiBase}?action=details&id=${id}`);
            const data = await response.json();

            if (response.ok && data.maintenance) {
                this.populateEditForm(data.maintenance);
                this.showEditModal();
            } else {
                this.showNotification(data.error || 'Failed to load maintenance details', 'error');
            }
        } catch (error) {
            console.error('Error loading maintenance details:', error);
            this.showNotification('Error loading maintenance details', 'error');
        }
    }

    viewMaintenance(id) {
        // Call the detail handler function
        viewMaintenanceDetails(id);
    }

    showEditModal() {
        document.getElementById('edit-maintenance-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    hideEditModal() {
        document.getElementById('edit-maintenance-modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        this.clearEditForm();
    }

    populateEditForm(maintenance) {
        // Populate all form fields with maintenance data
        document.getElementById('edit-maintenance-id').value = maintenance.id;
        document.getElementById('edit-asset-id').value = maintenance.asset_id;
        document.getElementById('edit-maintenance-type').value = maintenance.maintenance_type;
        document.getElementById('edit-scheduled-date').value = maintenance.scheduled_date;
        document.getElementById('edit-priority').value = maintenance.priority;
        document.getElementById('edit-assigned-to').value = maintenance.assigned_to || '';
        document.getElementById('edit-estimated-duration').value = maintenance.estimated_duration || '';
        document.getElementById('edit-estimated-cost').value = maintenance.estimated_cost || '';
        document.getElementById('edit-actual-cost').value = maintenance.actual_cost || '';
        document.getElementById('edit-description').value = maintenance.description;
        document.getElementById('edit-notes').value = maintenance.notes || '';

        // Load assets and technicians for dropdowns if not already loaded
        this.loadEditFormOptions();
    }

    clearEditForm() {
        document.getElementById('edit-maintenance-form').reset();
    }

    async loadEditFormOptions() {
        // Load assets for edit form
        try {
            const response = await fetch(`${this.apiBase}?action=assets`);
            const data = await response.json();

            const assetSelect = document.getElementById('edit-asset-id');
            const currentValue = assetSelect.value;

            assetSelect.innerHTML = '<option value="">Choose asset for maintenance</option>';

            if (data.assets && data.assets.length > 0) {
                data.assets.forEach(asset => {
                    const option = document.createElement('option');
                    option.value = asset.id;
                    option.textContent = `${asset.asset_code} - ${asset.name} (${asset.location})`;
                    assetSelect.appendChild(option);
                });
            }

            // Restore selected value
            assetSelect.value = currentValue;
        } catch (error) {
            console.error('Error loading assets for edit form:', error);
        }

        // Load technicians for edit form
        try {
            const response = await fetch(`${this.apiBase}?action=technicians`);
            const data = await response.json();

            const technicianSelect = document.getElementById('edit-assigned-to');
            const currentValue = technicianSelect.value;

            technicianSelect.innerHTML = '<option value="">Select technician</option>';

            if (data.technicians && data.technicians.length > 0) {
                data.technicians.forEach(technician => {
                    const option = document.createElement('option');
                    option.value = technician.id;
                    option.textContent = `${technician.full_name} - ${technician.department}`;
                    technicianSelect.appendChild(option);
                });
            }

            // Restore selected value
            technicianSelect.value = currentValue;
        } catch (error) {
            console.error('Error loading technicians for edit form:', error);
        }
    }

    async updateMaintenanceTask() {
        const formData = new FormData(document.getElementById('edit-maintenance-form'));
        const data = Object.fromEntries(formData.entries());

        // Remove empty fields
        Object.keys(data).forEach(key => {
            if (data[key] === '') {
                delete data[key];
            }
        });

        try {
            const response = await fetch(`${this.apiBase}?action=update`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                this.showNotification('Maintenance task updated successfully!', 'success');
                this.hideEditModal();
                this.loadMaintenanceStats();
                this.loadMaintenanceList();
            } else {
                this.showNotification(result.error || 'Failed to update maintenance task', 'error');
            }
        } catch (error) {
            console.error('Error updating maintenance:', error);
            this.showNotification('Error updating maintenance task', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${this.getNotificationClasses(type)}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationClasses(type) {
        const classes = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };
        return classes[type] || classes.info;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.maintenanceManager = new MaintenanceManager();
});