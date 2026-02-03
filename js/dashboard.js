// Dashboard functionality
class Dashboard {
    static async loadData() {
        try {
            // Load all dashboard data in parallel
            const loaders = [
                this.loadStats(),
                this.loadRecentActivities()
            ];


            if (typeof API.getForecastOverview === 'function') {
                loaders.push(this.loadForecastSummary());
            }


            await Promise.all(loaders);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            showNotification('Failed to load dashboard data', 'error');
            throw error;
        }
    }

    static async loadForecastSummary() {
        if (typeof API.getForecastOverview !== 'function') {
            return;
        }

        try {
            if (!Dashboard.userHasPermission('ai_demand_forecasting')) {
                return;
            }

            const data = await API.getForecastOverview();
            Dashboard.renderForecastSummary(data);
        } catch (error) {
            console.warn('Forecast summary unavailable:', error);
            Dashboard.renderForecastSummary(null, true);
        }
    }

    static renderForecastSummary(summary, isError = false) {
        const container = document.querySelector('[data-forecast-summary]');
        if (!container) return;

        if (isError) {
            container.innerHTML = `
                <div class="rounded-2xl border border-red-100 bg-red-50/60 p-4">
                    <div class="flex items-center gap-3 text-sm text-red-600">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>AI forecast unavailable right now. Try refreshing later.</span>
                    </div>
                </div>
            `;
            return;
        }

        if (!summary || !summary.summary || Object.keys(summary.summary).length === 0) {
            container.innerHTML = `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-robot text-slate-400"></i>
                        <span>AI forecasting activates once supply usage history is recorded.</span>
                    </div>
                </div>
            `;
            return;
        }

        const { forecasted_usage = 0, critical_low_stock = 0, seasonal_trends = 0 } = summary.summary;

        container.innerHTML = `
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">AI Forecast</p>
                        <h4 class="mt-1 text-base font-semibold text-slate-900">${new Intl.NumberFormat().format(forecasted_usage)} units (30-day)</h4>
                        <p class="mt-1 text-xs text-slate-500">${critical_low_stock} critical stock risks • ${seasonal_trends} seasonal shifts</p>
                    </div>
                    <a href="forecasting.php" class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition" data-no-transition>
                        View insights
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        `;
    }

    static userHasPermission(permission) {
        try {
            const current = this.getCurrentUserFromStorage();
            if (!current || !Array.isArray(current.permissions)) {
                return false;
            }
            return current.permissions.includes(permission);
        } catch (error) {
            console.warn('Permission check failed:', error);
            return false;
        }
    }

    static async loadStats() {
        try {
            const stats = await API.getDashboardStats();

            const totalItemsEl = document.getElementById('totalItems') || document.getElementById('totalAssets');
            if (totalItemsEl) {
                totalItemsEl.textContent = stats.totalAssets || 0;
            }

            const availableEl = document.getElementById('availableItems');
            if (availableEl) {
                availableEl.textContent = stats.availableItems || 0;
            }

            const maintenanceEl = document.getElementById('maintenanceItems');
            if (maintenanceEl) {
                maintenanceEl.textContent = stats.maintenanceItems || 0;
            }

            const maintenanceDueEl = document.getElementById('maintenanceDueToday');
            if (maintenanceDueEl) {
                const dueCount = Number(stats.maintenanceDueToday || stats.maintenance_due_today || 0);
                maintenanceDueEl.textContent = `${dueCount} due today`;
            }

            const maintenanceOverdueEl = document.getElementById('maintenanceOverdue');
            if (maintenanceOverdueEl) {
                const overdueCount = Number(stats.maintenanceOverdue || stats.maintenance_overdue || 0);
                maintenanceOverdueEl.textContent = `${overdueCount} overdue`;
            }

            const damagedEl = document.getElementById('damagedItems');
            if (damagedEl) {
                damagedEl.textContent = stats.damagedItems || 0;
            }

        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    static async loadRecentActivities() {
        try {
            const activities = await API.getRecentActivities();
            const container = document.getElementById('recentActivities');

            if (activities.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No recent activities</p>';
                return;
            }

            container.innerHTML = activities.map(activity => `
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                        <i class="fas fa-${this.getActivityIcon(activity.action)} text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${this.formatActivityMessage(activity)}</p>
                        <p class="text-xs text-gray-500">${this.formatTimeAgo(activity.created_at)}</p>
                    </div>
                </div>
            `).join('');

        } catch (error) {
            console.error('Error loading activities:', error);
            const container = document.getElementById('recentActivities');
            container.innerHTML = '<p class="text-red-500 text-sm">Failed to load activities</p>';
        }
    }

    static getActivityIcon(action) {
        const icons = {
            'create': 'plus',
            'update': 'edit',
            'delete': 'trash',
            'login': 'sign-in-alt',
            'assign': 'handshake',
            'return': 'undo'
        };
        return icons[action] || 'info-circle';
    }

    static formatActivityMessage(activity) {
        const actionMap = {
            'create': 'created',
            'update': 'updated',
            'delete': 'deleted',
            'login': 'logged in',
            'assign': 'assigned',
            'return': 'returned'
        };

        const action = actionMap[activity.action] || activity.action;
        const table = activity.table_name ? activity.table_name.replace('_', ' ') : 'item';

        return `${activity.user} ${action} ${table}`;
    }

    static getAlertIcon(type) {
        const icons = {
            'low_stock': 'exclamation-triangle',
            'overdue_maintenance': 'wrench',
            'expired_supplies': 'clock',
            'audit_required': 'clipboard-check'
        };
        return icons[type] || 'bell';
    }

    static async loadNotifications() {
        try {
            const response = await API.getNotifications();
            const notifications = Array.isArray(response) ? response : [];
            window.dashboardNotifications = notifications;
            const menuList = document.getElementById('notificationMenuList');
            const emptyState = document.getElementById('notificationEmptyState');

            if (!menuList || !emptyState) return;

            if (!notifications || notifications.length === 0) {
                menuList.innerHTML = '';
                emptyState.classList.remove('hidden');
                this.updateNotificationBadge(0);
                this.renderNotificationsModal(notifications);
                return;
            }

            const items = notifications.slice(0, 6).map(notification => `
                <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-100 transition flex gap-3" data-menu-close>
                    <span class="mt-0.5">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full ${this.getNotificationAccent(notification.type)}">
                            <i class="fas fa-${this.getNotificationIcon(notification.type)} text-base"></i>
                        </span>
                    </span>
                    <span class="flex-1">
                        <span class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-gray-900">${notification.title || 'Notification'}</span>
                            <span class="text-xs text-gray-400 whitespace-nowrap">${this.formatTimeAgo(notification.created_at)}</span>
                        </span>
                        <span class="block text-sm text-gray-600 mt-1">${notification.message || ''}</span>
                    </span>
                </button>
            `).join('');
            
            menuList.innerHTML = items;
            emptyState.classList.add('hidden');
            this.updateNotificationBadge(notifications.length);
            this.renderNotificationsModal(notifications);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    static getNotificationIcon(type) {
        const icons = {
            assignment: 'handshake',
            reminder: 'bell',
            alert: 'exclamation',
            audit: 'clipboard-check',
            maintenance: 'wrench'
        };
        return icons[type] || 'info-circle';
    }

    static getNotificationAccent(type) {
        const accents = {
            assignment: 'bg-blue-100 text-blue-600',
            reminder: 'bg-indigo-100 text-indigo-600',
            alert: 'bg-red-100 text-red-600',
            audit: 'bg-emerald-100 text-emerald-600',
            maintenance: 'bg-amber-100 text-amber-600'
        };
        return accents[type] || 'bg-slate-100 text-slate-600';
    }

    static updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        const badgeMobile = document.getElementById('notificationBadgeMobile');
        const clamped = Math.max(0, Number(count) || 0);

        [badge, badgeMobile].forEach(el => {
            if (!el) return;
            if (clamped > 0) {
                el.textContent = clamped > 9 ? '9+' : clamped;
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    }

    static renderNotificationsModal(notifications = []) {
        const list = document.getElementById('notificationsModalList');
        const emptyState = document.getElementById('notificationsModalEmptyState');
        const countLabel = document.getElementById('notificationsModalCount');

        if (!list || !emptyState) return;

        if (!Array.isArray(notifications) || notifications.length === 0) {
            list.innerHTML = '';
            emptyState.classList.remove('hidden');
            if (countLabel) {
                countLabel.textContent = 'No notifications yet';
            }
            return;
        }

        emptyState.classList.add('hidden');
        list.innerHTML = notifications.map(notification => `
            <li class="px-6 py-4 flex gap-4">
                <span class="mt-0.5">
                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full ${this.getNotificationAccent(notification.type)}">
                        <i class="fas fa-${this.getNotificationIcon(notification.type)} text-base"></i>
                    </span>
                </span>
                <div class="flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">${notification.title || 'Notification'}</p>
                        <span class="text-xs text-gray-400 whitespace-nowrap">${this.formatTimeAgo(notification.created_at)}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">${notification.message || ''}</p>
                </div>
            </li>
        `).join('');

        if (countLabel) {
            const total = notifications.length;
            countLabel.textContent = `${total} update${total === 1 ? '' : 's'}`;
        }
    }

    static formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / (1000 * 60));
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 0) {
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else if (hours > 0) {
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (minutes > 0) {
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else {
            return 'Just now';
        }
    }

    static get refreshButton() {
        return document.getElementById('refreshDashboardBtn');
    }

    static setRefreshing(isRefreshing = false) {
        const button = this.refreshButton;
        if (!button) return;

        const icon = button.querySelector('[data-icon]');
        const label = button.querySelector('[data-label]');

        button.disabled = isRefreshing;
        button.setAttribute('aria-busy', isRefreshing ? 'true' : 'false');
        button.classList.toggle('opacity-70', isRefreshing);
        button.classList.toggle('cursor-not-allowed', isRefreshing);

        if (icon) {
            icon.classList.toggle('animate-spin', isRefreshing);
        }

        if (label) {
            label.textContent = isRefreshing ? 'Refreshing...' : 'Refresh';
        }
    }

    static async refreshData() {
        this.setRefreshing(true);
        try {
            await this.loadData();
            showNotification('Dashboard refreshed', 'success');
        } catch (error) {
            // loadData already handles logging and notification
        } finally {
            this.setRefreshing(false);
        }
    }

    static getCurrentUserFromStorage() {
        try {
            const stored = sessionStorage.getItem('currentUser');
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (error) {
            console.warn('Unable to parse currentUser from sessionStorage', error);
        }

        if (window.currentUser) {
            try {
                return { ...window.currentUser };
            } catch (error) {
                return window.currentUser;
            }
        }

        return null;
    }

    static setCurrentUserInStorage(user) {
        if (!user) return;

        try {
            sessionStorage.setItem('currentUser', JSON.stringify(user));
        } catch (error) {
            console.warn('Unable to store currentUser in sessionStorage', error);
        }

        window.currentUser = user;
    }

    static async fetchCurrentUser({ force = false } = {}) {
        if (!force) {
            const cached = this.getCurrentUserFromStorage();
            if (cached) {
                return cached;
            }
        }

        try {
            const response = await API.getCurrentUser();
            const user = response?.data ?? response;
            if (user) {
                this.setCurrentUserInStorage(user);
            }
            return user;
        } catch (error) {
            console.error('Failed to fetch current user:', error);
            throw error;
        }
    }

    static async refreshCurrentUser() {
        return this.fetchCurrentUser({ force: true });
    }

    static populateProfileEditForm(form, user) {
        if (!form || !user) return;

        const fullNameInput = form.querySelector('#profileFullName');
        const emailInput = form.querySelector('#profileEmail');
        const departmentInput = form.querySelector('#profileDepartment');

        if (fullNameInput) {
            fullNameInput.value = user.full_name ?? user.username ?? '';
        }

        if (emailInput) {
            emailInput.value = user.email ?? '';
        }

        if (departmentInput) {
            departmentInput.value = user.department ?? '';
        }
    }

    static updateProfileUI(user) {
        if (!user) return;

        const fullName = (user.full_name && user.full_name.trim()) || user.username || '';
        const roleDisplay = user.role_display || (user.role ? `${user.role.charAt(0).toUpperCase()}${user.role.slice(1)}` : 'User');
        const email = user.email || '';
        const department = user.department && user.department.trim() ? user.department : 'Not specified';
        const initials = this.computeInitials(fullName || user.username || '', 'U');

        const setTextContent = (selector, value) => {
            if (!selector) return;
            document.querySelectorAll(selector).forEach(element => {
                if (element) {
                    element.textContent = value;
                }
            });
        };

        setTextContent('[data-profile-name]', fullName);
        setTextContent('[data-profile-menu-name]', fullName);
        setTextContent('[data-profile-overview-name]', fullName);
        setTextContent('[data-profile-welcome-name]', fullName);

        setTextContent('[data-profile-role]', roleDisplay);
        setTextContent('[data-profile-menu-role]', roleDisplay);
        setTextContent('[data-profile-overview-role]', roleDisplay);

        setTextContent('[data-profile-initials]', initials);

        const overviewInitialsEl = document.querySelector('[data-profile-overview-initials]');
        if (overviewInitialsEl) {
            overviewInitialsEl.textContent = initials;
        }

        const emailRow = document.querySelector('[data-profile-email-row]');
        if (emailRow) {
            emailRow.classList.toggle('hidden', !email);
        }
        setTextContent('[data-profile-email]', email);

        setTextContent('[data-profile-department]', department);
    }

    static computeInitials(name, fallback = 'U') {
        if (!name || typeof name !== 'string') {
            return fallback.toUpperCase();
        }

        const trimmed = name.trim();
        if (!trimmed) {
            return fallback.toUpperCase();
        }

        const parts = trimmed.split(/\s+/);

        if (parts.length === 1) {
            return parts[0].substring(0, 2).toUpperCase();
        }

        const first = parts[0].charAt(0);
        const last = parts[parts.length - 1].charAt(0);

        return (first + last).toUpperCase();
    }

    static toggleButtonLoading(button, isLoading, options = {}) {
        if (!button) return;

        const {
            loadingLabel = 'Saving...'
        } = options;

        const labelEl = Array.from(button.querySelectorAll('span')).find(span => span.getAttribute('aria-hidden') !== 'true');
        const spinnerEl = button.querySelector('[data-spinner]') || button.querySelector('[aria-hidden="true"]');

        if (labelEl && !button.dataset.originalLabel) {
            button.dataset.originalLabel = labelEl.textContent || '';
        }

        button.disabled = !!isLoading;
        button.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        button.classList.toggle('cursor-not-allowed', !!isLoading);
        button.classList.toggle('opacity-70', !!isLoading);

        if (spinnerEl) {
            spinnerEl.classList.toggle('hidden', !isLoading);
        }

        if (labelEl) {
            labelEl.textContent = isLoading ? loadingLabel : (button.dataset.originalLabel || labelEl.textContent);
        }
    }

    static isValidEmail(email) {
        if (!email) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
}