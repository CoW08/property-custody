// Authentication handler
class Auth {
    static currentUser = null;

    static init() {
        // Only initialize if login screen exists (we're on login page)
        const loginScreen = document.getElementById('loginScreen');
        if (!loginScreen) {
            // We're on a different page, just load user from session if available
            const savedUser = sessionStorage.getItem('currentUser');
            if (savedUser) {
                this.currentUser = JSON.parse(savedUser);
            }
            return;
        }

        // Check if user is already logged in from session storage
        const savedUser = sessionStorage.getItem('currentUser');
        if (savedUser) {
            this.currentUser = JSON.parse(savedUser);
            this.showMainApp();
        } else {
            this.showLoginScreen();
        }

        // Setup event listeners
        this.setupEventListeners();
    }

    static setupEventListeners() {
        // Login form submission
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', this.handleLogout.bind(this));
        }
    }

    static async handleLogin(event) {
        event.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showNotification('Please enter username and password', 'error');
            return;
        }

        try {
            const response = await API.login(username, password);
            this.currentUser = response.user;

            // Save user to session storage
            sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));

            showNotification('Login successful!', 'success');
            this.showMainApp();

        } catch (error) {
            showNotification(error.message || 'Login failed', 'error');
        }
    }

    static async handleLogout() {
        try {
            await API.logout();
            this.currentUser = null;
            sessionStorage.removeItem('currentUser');

            showNotification('Logged out successfully', 'success');
            this.showLoginScreen();

        } catch (error) {
            showNotification('Logout failed', 'error');
        }
    }

    static showLoginScreen() {
        const loginScreen = document.getElementById('loginScreen');
        const mainApp = document.getElementById('mainApp');
        
        if (loginScreen) {
            loginScreen.style.display = 'flex';
        }
        if (mainApp) {
            mainApp.classList.add('hidden');
        }

        // Clear form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.reset();
        }
    }

    static showMainApp() {
        const loginScreen = document.getElementById('loginScreen');
        const mainApp = document.getElementById('mainApp');

        if (loginScreen) {
            loginScreen.style.display = 'none';
        }
        if (mainApp) {
            mainApp.classList.remove('hidden');
        }

        // Update user info in the sidebar
        if (this.currentUser) {
            const currentUsername = document.getElementById('currentUsername');
            const currentRole = document.getElementById('currentRole');
            const welcomeUser = document.getElementById('welcomeUser');

            if (currentUsername) {
                currentUsername.textContent = this.currentUser.full_name;
            }
            if (currentRole) {
                currentRole.textContent = this.currentUser.role.charAt(0).toUpperCase() + this.currentUser.role.slice(1);
            }
            if (welcomeUser) {
                welcomeUser.textContent = this.currentUser.full_name;
            }
        }

        // Load dashboard
        App.showModule('dashboard');
    }

    static isLoggedIn() {
        return this.currentUser !== null;
    }

    static hasRole(role) {
        return this.currentUser && this.currentUser.role === role;
    }

    static hasPermission(permission) {
        if (!this.currentUser) return false;

        const permissions = {
            'admin': ['view_all', 'create', 'update', 'delete', 'approve', 'audit'],
            'custodian': ['view_all', 'create', 'update', 'assign'],
            'staff': ['view_own', 'request'],
            'maintenance': ['view_maintenance', 'update_maintenance']
        };

        const userPermissions = permissions[this.currentUser.role] || [];
        return userPermissions.includes(permission);
    }
}

// Initialize authentication when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    Auth.init();
});