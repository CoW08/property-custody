class UserRolesManager {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.currentFilters = {};
        this.init();
    }

    applySearch(rawValue = '') {
        const trimmed = rawValue.trim();

        if (trimmed) {
            this.currentFilters.search = trimmed;
        } else {
            delete this.currentFilters.search;
        }

        this.currentPage = 1;
        this.loadUsers();
    }

    init() {
        this.loadUsers();
        this.loadUserStats();
        this.setupEventListeners();
        this.setupModals();
    }

    setupEventListeners() {
        // Add user button
        document.getElementById('addUserBtn')?.addEventListener('click', () => {
            this.showAddUserModal();
        });

        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            // Trigger search as the user types with debounce
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                const value = e.target.value;
                this.searchTimeout = setTimeout(() => {
                    this.applySearch(value);
                }, 300);
            });

            // Support pressing Enter to search immediately
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(this.searchTimeout);
                    this.applySearch(searchInput.value);
                }
            });

            // Handle clicking the input's clear (x) button
            searchInput.addEventListener('search', () => {
                clearTimeout(this.searchTimeout);
                this.applySearch(searchInput.value);
            });
        }

        // Role filter
        document.getElementById('roleFilter')?.addEventListener('change', (e) => {
            const value = e.target.value;
            if (value) {
                this.currentFilters.role = value;
            } else {
                delete this.currentFilters.role;
            }
            this.currentPage = 1;
            this.loadUsers();
        });

        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            const value = e.target.value;
            if (value) {
                this.currentFilters.status = value;
            } else {
                delete this.currentFilters.status;
            }
            this.currentPage = 1;
            this.loadUsers();
        });

        // Refresh button
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.loadUsers();
            this.loadUserStats();
        });
    }

    setupModals() {
        // Setup modal close functionality
        document.querySelectorAll('[data-modal-hide]').forEach(button => {
            button.addEventListener('click', (e) => {
                const modalId = e.target.getAttribute('data-modal-hide');
                this.hideModal(modalId);
            });
        });

        // Setup form submissions
        document.getElementById('addUserForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleAddUser();
        });

        document.getElementById('editUserForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleEditUser();
        });
    }

    async loadUsers() {
        try {
            this.showLoading('usersTableBody');

            const params = new URLSearchParams({
                action: 'list',
                page: this.currentPage,
                limit: this.itemsPerPage
            });

            Object.entries(this.currentFilters).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    params.set(key, value);
                }
            });

            const response = await fetch(`api/users.php?${params}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.renderUsers(data.data.users);
                this.renderPagination(data.data.pagination);
            } else {
                throw new Error(data.error || 'Failed to load users');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showError('Failed to load users. Please try again.');
        } finally {
            this.hideLoading('usersTableBody');
        }
    }

    async loadUserStats() {
        try {
            const response = await fetch('api/users.php?action=stats');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.renderStats(data.data);
            } else {
                throw new Error(data.error || 'Failed to load stats');
            }
        } catch (error) {
            console.error('Error loading user stats:', error);
        }
    }

    renderUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-2 block"></i>
                        No users found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                ${user.full_name.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${escapeHtml(user.full_name)}</div>
                            <div class="text-sm text-gray-500">@${escapeHtml(user.username)}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${escapeHtml(user.email || 'N/A')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${this.getRoleBadgeClass(user.role)}">
                        ${escapeHtml(user.role_display)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${escapeHtml(user.department || 'N/A')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        <i class="fas fa-circle text-xs mr-1 ${user.status === 'active' ? 'text-green-400' : 'text-red-400'}"></i>
                        ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${new Date(user.created_at).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center space-x-2">
                        <button onclick="userRolesManager.viewUser(${user.id})"
                                class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="userRolesManager.editUser(${user.id})"
                                class="text-yellow-600 hover:text-yellow-900 transition-colors duration-200"
                                title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="userRolesManager.toggleUserStatus(${user.id})"
                                class="text-${user.status === 'active' ? 'orange' : 'green'}-600 hover:text-${user.status === 'active' ? 'orange' : 'green'}-900 transition-colors duration-200"
                                title="${user.status === 'active' ? 'Deactivate' : 'Activate'} User">
                            <i class="fas fa-${user.status === 'active' ? 'pause' : 'play'}"></i>
                        </button>
                        <button onclick="userRolesManager.deleteUser(${user.id})"
                                class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                title="Delete User">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderStats(stats) {
        // Update total users
        const totalUsersEl = document.getElementById('totalUsers');
        if (totalUsersEl) {
            totalUsersEl.textContent = stats.total_users;
        }

        // Update role stats
        const roleStatsEl = document.getElementById('roleStats');
        if (roleStatsEl && stats.role_stats) {
            roleStatsEl.innerHTML = stats.role_stats.map(stat => `
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">${stat.role.charAt(0).toUpperCase() + stat.role.slice(1)}:</span>
                    <span class="text-sm font-semibold text-gray-900">${stat.count}</span>
                </div>
            `).join('');
        }

        // Update recent users
        const recentUsersEl = document.getElementById('recentUsers');
        if (recentUsersEl) {
            recentUsersEl.textContent = stats.recent_users;
        }
    }

    renderPagination(pagination) {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl) return;

        const { page, pages, total } = pagination;

        if (pages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        let paginationHTML = `
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing ${((page - 1) * this.itemsPerPage) + 1} to ${Math.min(page * this.itemsPerPage, total)} of ${total} users
                </div>
                <div class="flex space-x-1">
        `;

        // Previous button
        paginationHTML += `
            <button onclick="userRolesManager.changePage(${page - 1})"
                    ${page === 1 ? 'disabled' : ''}
                    class="px-3 py-1 rounded border ${page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                Previous
            </button>
        `;

        // Page numbers
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        if (startPage > 1) {
            paginationHTML += `<button onclick="userRolesManager.changePage(1)" class="px-3 py-1 rounded border bg-white text-gray-700 hover:bg-gray-50">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="px-2 py-1 text-gray-500">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button onclick="userRolesManager.changePage(${i})"
                        class="px-3 py-1 rounded border ${i === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                    ${i}
                </button>
            `;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                paginationHTML += `<span class="px-2 py-1 text-gray-500">...</span>`;
            }
            paginationHTML += `<button onclick="userRolesManager.changePage(${pages})" class="px-3 py-1 rounded border bg-white text-gray-700 hover:bg-gray-50">${pages}</button>`;
        }

        // Next button
        paginationHTML += `
            <button onclick="userRolesManager.changePage(${page + 1})"
                    ${page === pages ? 'disabled' : ''}
                    class="px-3 py-1 rounded border ${page === pages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                Next
            </button>
        `;

        paginationHTML += '</div></div>';
        paginationEl.innerHTML = paginationHTML;
    }

    changePage(page) {
        this.currentPage = page;
        this.loadUsers();
    }

    getRoleBadgeClass(role) {
        const classes = {
            'admin': 'bg-red-100 text-red-800',
            'custodian': 'bg-blue-100 text-blue-800',
            'staff': 'bg-gray-100 text-gray-800',
            'maintenance': 'bg-yellow-100 text-yellow-800'
        };
        return classes[role] || 'bg-gray-100 text-gray-800';
    }

    async showAddUserModal() {
        // Load roles for the dropdown
        await this.loadRolesForModal();
        this.showModal('addUserModal');
    }

    async loadRolesForModal() {
        try {
            const response = await fetch('api/users.php?action=roles');
            const data = await response.json();

            if (data.success) {
                const roleSelects = document.querySelectorAll('.role-select');
                roleSelects.forEach(select => {
                    select.innerHTML = '<option value="">Select Role</option>' +
                        data.data.roles.map(role =>
                            `<option value="${role.key}">${role.name}</option>`
                        ).join('');
                });
            }
        } catch (error) {
            console.error('Error loading roles:', error);
        }
    }

    async handleAddUser() {
        const form = document.getElementById('addUserForm');
        const formData = new FormData(form);
        const userData = Object.fromEntries(formData.entries());

        try {
            this.showButtonLoading('addUserSubmit');

            const response = await fetch('api/users.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User created successfully!');
                this.hideModal('addUserModal');
                form.reset();
                this.loadUsers();
                this.loadUserStats();
            } else {
                throw new Error(data.error || 'Failed to create user');
            }
        } catch (error) {
            console.error('Error creating user:', error);
            this.showError(error.message);
        } finally {
            this.hideButtonLoading('addUserSubmit');
        }
    }

    async viewUser(userId) {
        try {
            const response = await fetch(`api/users.php?action=details&id=${userId}`);
            const data = await response.json();

            if (data.success) {
                this.showUserDetailsModal(data.data);
            } else {
                throw new Error(data.error || 'Failed to load user details');
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            this.showError('Failed to load user details');
        }
    }

    async editUser(userId) {
        try {
            const response = await fetch(`api/users.php?action=details&id=${userId}`);
            const data = await response.json();

            if (data.success) {
                await this.loadRolesForModal();
                this.populateEditForm(data.data);
                this.showModal('editUserModal');
            } else {
                throw new Error(data.error || 'Failed to load user details');
            }
        } catch (error) {
            console.error('Error loading user for edit:', error);
            this.showError('Failed to load user details');
        }
    }

    populateEditForm(user) {
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editFullName').value = user.full_name;
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editRole').value = user.role;
        document.getElementById('editDepartment').value = user.department || '';
        document.getElementById('editStatus').value = user.status;
    }

    async handleEditUser() {
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);
        const userData = Object.fromEntries(formData.entries());

        try {
            this.showButtonLoading('editUserSubmit');

            const response = await fetch('api/users.php?action=update', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User updated successfully!');
                this.hideModal('editUserModal');
                this.loadUsers();
                this.loadUserStats();
            } else {
                throw new Error(data.error || 'Failed to update user');
            }
        } catch (error) {
            console.error('Error updating user:', error);
            this.showError(error.message);
        } finally {
            this.hideButtonLoading('editUserSubmit');
        }
    }

    async toggleUserStatus(userId) {
        if (!confirm('Are you sure you want to change this user\'s status?')) {
            return;
        }

        try {
            const response = await fetch('api/users.php?action=toggle_status', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: userId })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User status updated successfully!');
                this.loadUsers();
                this.loadUserStats();
            } else {
                throw new Error(data.error || 'Failed to update user status');
            }
        } catch (error) {
            console.error('Error toggling user status:', error);
            this.showError('Failed to update user status');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('api/users.php?action=delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: userId })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('User deleted successfully!');
                this.loadUsers();
                this.loadUserStats();
            } else {
                throw new Error(data.error || 'Failed to delete user');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            this.showError(error.message);
        }
    }

    showUserDetailsModal(user) {
        const modal = document.getElementById('userDetailsModal');
        if (!modal) return;

        const content = modal.querySelector('.modal-content');
        if (content) {
            content.innerHTML = `
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">User Details</h3>
                    <button data-modal-hide="userDetailsModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <div class="h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-semibold mr-4">
                            ${user.full_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-xl font-semibold text-gray-900">${escapeHtml(user.full_name)}</h4>
                            <p class="text-gray-600">@${escapeHtml(user.username)}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <p class="text-gray-900">${escapeHtml(user.email || 'N/A')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${this.getRoleBadgeClass(user.role)}">
                                ${escapeHtml(user.role_display)}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <p class="text-gray-900">${escapeHtml(user.department || 'N/A')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                <i class="fas fa-circle text-xs mr-1 ${user.status === 'active' ? 'text-green-400' : 'text-red-400'}"></i>
                                ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Assets</label>
                            <p class="text-gray-900">${user.assigned_assets || 0}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                            <p class="text-gray-900">${new Date(user.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                        <div class="flex flex-wrap gap-2">
                            ${user.permissions.map(permission => `
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ${permission.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                </span>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;

            // Re-setup close button
            content.querySelector('[data-modal-hide]').addEventListener('click', () => {
                this.hideModal('userDetailsModal');
            });
        }

        this.showModal('userDetailsModal');
    }

    // Utility methods
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="ml-2 text-gray-600">Loading users...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    hideLoading(elementId) {
        // Loading will be replaced by actual content
    }

    showButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            button.innerHTML = `
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Loading...
                </div>
            `;
        }
    }

    hideButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 max-w-sm w-full bg-white border-l-4 ${
            type === 'success' ? 'border-green-500' :
            type === 'error' ? 'border-red-500' : 'border-blue-500'
        } rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full`;

        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas ${
                            type === 'success' ? 'fa-check-circle text-green-500' :
                            type === 'error' ? 'fa-exclamation-circle text-red-500' : 'fa-info-circle text-blue-500'
                        }"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-700">${escapeHtml(message)}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button class="text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.userRolesManager = new UserRolesManager();
});