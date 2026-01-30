<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

// Get current user information
$currentUser = getCurrentUser();
$userName = $currentUser['full_name'] ?: ($currentUser['username'] ?? '') ?: 'User';
$userRole = ucfirst($currentUser['role'] ?: 'User');

$userInitials = '';
if (!empty($userName)) {
    $nameParts = preg_split('/\s+/', trim($userName));
    if ($nameParts && count($nameParts) >= 2) {
        $first = strtoupper(substr($nameParts[0], 0, 1));
        $last = strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
        $userInitials = $first . $last;
    } else {
        $userInitials = strtoupper(substr($userName, 0, 2));
    }
}

if ($userInitials === '' && !empty($currentUser['username'])) {
    $userInitials = strtoupper(substr($currentUser['username'], 0, 2));
}

$userInitials = substr($userInitials ?: 'U', 0, 2);

$pageTitle = "Dashboard - Property Custodian Management";

ob_start();
?>

<!-- Dashboard Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 px-4 py-3 flex justify-between items-center">
        <button onclick="toggleMobileMenu()" class="p-2 text-gray-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
        <div class="flex items-center gap-3">
            <button type="button" class="relative p-2 text-gray-600 hover:text-gray-900 transition rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" data-menu-target="notifications" aria-haspopup="true">
                <span class="sr-only">Open notifications</span>
                <i class="fas fa-bell text-lg"></i>
                <span id="notificationBadgeMobile" class="hidden absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center">0</span>
            </button>
            <button type="button" class="p-2 text-gray-600 hover:text-gray-900 transition rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" data-menu-target="profile" aria-haspopup="true">
                <span class="sr-only">Open profile menu</span>
                <i class="fas fa-user-circle text-2xl"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 overflow-x-hidden">
        <div class="p-4 lg:p-8 pt-16 lg:pt-8">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6 mb-6">
                <div class="flex-1">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Welcome back, <span data-profile-welcome-name><?php echo htmlspecialchars($userName); ?></span>. Here's what's happening today.</p>
                </div>
                <div class="flex items-center justify-end gap-3 sm:gap-4 w-full sm:w-auto">
                    <div class="relative">
                        <button id="notificationMenuButton" type="button" class="relative p-2 text-gray-600 hover:text-gray-900 transition rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-menu-target="notifications" aria-haspopup="true">
                            <span class="sr-only">Open notifications</span>
                            <i class="fas fa-bell text-lg"></i>
                            <span id="notificationBadge" class="hidden absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs font-semibold flex items-center justify-center">0</span>
                        </button>
                        <div id="notificationMenu" class="hidden absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-40">
                            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-800">Notifications</span>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Live</span>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div id="notificationEmptyState" class="px-4 py-6 text-center text-sm text-gray-500">
                                    You're all caught up! We'll drop updates about assignments, audits, and alerts here.
                                </div>
                                <div id="notificationMenuList" class="divide-y divide-gray-100"></div>
                            </div>
                            <div class="px-4 py-2 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-400">Only the latest updates are shown.</span>
                                <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700" data-menu-close data-open-notifications>View all</button>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <button id="profileMenuButton" type="button" class="flex items-center gap-3 px-3 py-2 bg-white border border-gray-200 rounded-full shadow-sm hover:shadow transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-menu-target="profile" aria-haspopup="true">
                            <span class="hidden sm:flex flex-col text-left">
                                <span class="text-sm font-semibold text-gray-900" data-profile-name><?php echo htmlspecialchars($userName); ?></span>
                                <span class="text-xs text-gray-500" data-profile-role><?php echo htmlspecialchars($userRole); ?></span>
                            </span>
                            <span class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-600 text-white font-semibold uppercase" data-profile-initials>
                                <?php echo htmlspecialchars($userInitials); ?>
                            </span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>
                        <div id="profileMenu" class="hidden absolute right-0 mt-3 w-64 bg-white border border-gray-200 rounded-xl shadow-lg z-40">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-900" data-profile-menu-name><?php echo htmlspecialchars($userName); ?></p>
                                <p class="text-xs text-gray-500 mt-0.5" data-profile-menu-role><?php echo htmlspecialchars($userRole); ?></p>
                            </div>
                            <div class="py-2">
                                <button type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2" data-menu-close data-open-profile>
                                    <i class="fas fa-id-badge text-gray-400"></i>
                                    Manage profile
                                </button>

                                <button type="button" id="profileLogoutBtn" class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Sign out
                                </button>
                            </div>
                        </div>
                    </div>

                    <button id="refreshDashboardBtn" type="button" onclick="Dashboard.refreshData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 w-full sm:w-auto flex items-center justify-center gap-2">
                        <i class="fas fa-sync-alt" data-icon></i>
                        <span data-label>Refresh</span>
                    </button>
                </div>
            </div>

            <?php include 'components/dashboard.php'; ?>
        </div>
    </main>
    <div id="profileModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-profile-close></div>
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Profile Overview</h2>
                    <p class="text-xs text-gray-500">Personalize how your account appears across the platform.</p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" data-profile-close>
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>
            <div class="p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <span class="flex items-center justify-center h-14 w-14 rounded-full bg-blue-600 text-white font-semibold text-xl uppercase" data-profile-overview-initials>
                        <?php echo htmlspecialchars($userInitials); ?>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" data-profile-overview-name><?php echo htmlspecialchars($userName); ?></h3>
                        <p class="text-sm text-gray-500">Role: <span data-profile-overview-role><?php echo htmlspecialchars($userRole); ?></span></p>
                        <p class="text-sm text-gray-500 <?php echo empty($currentUser['email']) ? 'hidden' : ''; ?>" data-profile-email-row>
                            Email: <span data-profile-email><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Department</p>
                        <p class="mt-1 text-sm text-gray-800" data-profile-department><?php echo htmlspecialchars($currentUser['department'] ?? 'Not specified'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Last login</p>
                        <p class="mt-1 text-sm text-gray-800">Automatically captured for audit logs.</p>
                    </div>
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50 md:col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Quick actions</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" data-open-profile-form>
                                <i class="fas fa-pen"></i>Edit profile
                            </button>
                            <button type="button" class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition" data-open-password-form>
                                <i class="fas fa-key"></i>Update password
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Need deeper edits? Contact the system administrator to apply changes to your account.</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition" data-profile-close>Close</button>
            </div>
        </div>
    </div>

    <div id="profileEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" data-profile-edit-close></div>
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden" tabindex="-1">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit Profile</h2>
                    <p class="text-xs text-gray-500">Update your name, email, and department information.</p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" data-profile-edit-close>
                    <i class="fas fa-times"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <form id="profileEditForm" class="p-6 space-y-4" novalidate>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="profileFullName">Full name</label>
                    <input id="profileFullName" name="full_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="profileEmail">Email address</label>
                    <input id="profileEmail" name="email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="profileDepartment">Department</label>
                    <input id="profileDepartment" name="department" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <p id="profileEditError" class="text-sm text-red-600 hidden"></p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition" data-profile-edit-close>Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" data-profile-edit-submit>
                        <span>Save changes</span>
                        <span class="hidden animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full" aria-hidden="true" data-spinner></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="notificationsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-notifications-close></div>
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden" tabindex="-1">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Notification Center</h2>
                    <p class="text-xs text-gray-500">
                        Stay on top of system activity, supply alerts, and maintenance reminders.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span id="notificationsModalCount" class="text-xs text-gray-400 hidden sm:inline"></span>
                    <button type="button" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-blue-600 hover:text-blue-700" data-refresh-notifications>
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition" data-notifications-close>
                        <i class="fas fa-times"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>
            </div>

            <div class="max-h-[70vh] overflow-y-auto">
                <div id="notificationsModalEmptyState" class="px-6 py-12 text-center text-sm text-gray-500">
                    You're all caught up! We'll drop updates about assignments, audits, and alerts here.
                </div>
                <ul id="notificationsModalList" class="divide-y divide-gray-100"></ul>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition" data-notifications-close>Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.currentUser = <?php echo json_encode($currentUser ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    (function persistCurrentUser(user) {
        if (!user || typeof sessionStorage === 'undefined') {
            return;
        }

        try {
            sessionStorage.setItem('currentUser', JSON.stringify(user));
        } catch (error) {
            console.warn('Unable to persist current user in sessionStorage', error);
        }
    })(window.currentUser);
</script>
<script src="js/api.js"></script>
<script src="js/dashboard.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Dashboard.loadData();

    Dashboard.fetchCurrentUser()
        .then(user => {
            if (user) {
                Dashboard.updateProfileUI(user);
            }
        })
        .catch(() => {});

    const menuButtons = document.querySelectorAll('[data-menu-target]');

    const menus = {
        notifications: document.getElementById('notificationMenu'),
        profile: document.getElementById('profileMenu')
    };
    let openMenuKey = null;

    function closeMenu(key) {
        if (!key || !menus[key]) return;
        menus[key].classList.add('hidden');
        openMenuKey = null;
    }

    function openMenu(key) {
        if (!menus[key]) return;

        Object.keys(menus).forEach(k => {
            if (k !== key) {
                menus[k]?.classList.add('hidden');
            }
        });

        menus[key].classList.toggle('hidden');
        openMenuKey = menus[key].classList.contains('hidden') ? null : key;
    }

    menuButtons.forEach(button => {
        const targetKey = button.getAttribute('data-menu-target');
        if (!targetKey) return;

        button.addEventListener('click', (event) => {
            event.stopPropagation();

            if (openMenuKey === targetKey) {
                closeMenu(targetKey);
            } else {
                openMenu(targetKey);
            }
        });
    });

    document.addEventListener('click', (event) => {
        const closeTrigger = event.target.closest('[data-menu-close]');
        if (closeTrigger && openMenuKey) {
            closeMenu(openMenuKey);
            return;
        }

        if (!openMenuKey) return;
        const menuEl = menus[openMenuKey];
        if (!menuEl) return;

        if (!menuEl.contains(event.target) && !event.target.closest('[data-menu-target]')) {
            closeMenu(openMenuKey);
        }
    });

    const logoutBtn = document.getElementById('profileLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            logoutBtn.disabled = true;
            logoutBtn.classList.add('opacity-70', 'cursor-not-allowed');

            try {
                await API.logout();
                sessionStorage.removeItem('currentUser');
                showNotification('Signed out successfully', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 600);
            } catch (error) {
                console.error('Logout failed:', error);
                showNotification('Failed to sign out', 'error');
                logoutBtn.disabled = false;
                logoutBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        });
    }

    const profileModal = document.getElementById('profileModal');
    if (profileModal) {
        const openTriggers = document.querySelectorAll('[data-open-profile]');
        const closeTriggers = profileModal.querySelectorAll('[data-profile-close]');

        const openProfileModal = () => {
            profileModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeProfileModal = () => {
            profileModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        openTriggers.forEach(trigger => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                closeMenu('profile');
                openProfileModal();
            });
        });

        closeTriggers.forEach(trigger => {
            trigger.addEventListener('click', closeProfileModal);
        });

        profileModal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeProfileModal();
            }
        });
    }

    const profileEditModal = document.getElementById('profileEditModal');
    if (profileEditModal) {
        const openEditButtons = document.querySelectorAll('[data-open-profile-form]');
        const closeEditButtons = profileEditModal.querySelectorAll('[data-profile-edit-close]');
        const profileEditForm = profileEditModal.querySelector('#profileEditForm');
        const profileEditError = profileEditModal.querySelector('#profileEditError');
        const submitButton = profileEditModal.querySelector('[data-profile-edit-submit]');

        const showProfileEditError = (message) => {
            if (!profileEditError) return;
            if (message) {
                profileEditError.textContent = message;
                profileEditError.classList.remove('hidden');
            } else {
                profileEditError.textContent = '';
                profileEditError.classList.add('hidden');
            }
        };

        const setFormDisabled = (isDisabled) => {
            if (!profileEditForm) return;
            profileEditForm.querySelectorAll('input, button').forEach(element => {
                if (element.getAttribute('data-profile-edit-close') !== null) return;
                element.disabled = !!isDisabled;
            });
        };

        const openProfileEditModal = async () => {
            profileEditModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            showProfileEditError('');

            if (!profileEditForm) return;

            const cachedUser = Dashboard.getCurrentUserFromStorage();
            if (cachedUser) {
                Dashboard.populateProfileEditForm(profileEditForm, cachedUser);
            }

            try {
                const freshUser = await Dashboard.fetchCurrentUser({ force: true });
                if (freshUser) {
                    Dashboard.populateProfileEditForm(profileEditForm, freshUser);
                }
            } catch (error) {
                console.error('Unable to refresh user profile before editing:', error);
                showProfileEditError('Unable to refresh your latest profile details. You can still edit using the current values.');
            }
        };

        const closeProfileEditModal = () => {
            profileEditModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            showProfileEditError('');
        };

        openEditButtons.forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                closeMenu('profile');
                await openProfileEditModal();
            });
        });

        closeEditButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                closeProfileEditModal();
            });
        });

        profileEditModal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeProfileEditModal();
            }
        });

        if (profileEditForm && submitButton) {
            profileEditForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData(profileEditForm);
                const fullName = (formData.get('full_name') || '').toString().trim();
                const email = (formData.get('email') || '').toString().trim();
                const department = (formData.get('department') || '').toString().trim();

                if (!fullName) {
                    showProfileEditError('Full name is required.');
                    profileEditForm.querySelector('#profileFullName')?.focus();
                    return;
                }

                if (!email) {
                    showProfileEditError('Email address is required.');
                    profileEditForm.querySelector('#profileEmail')?.focus();
                    return;
                }

                if (!Dashboard.isValidEmail(email)) {
                    showProfileEditError('Please enter a valid email address.');
                    profileEditForm.querySelector('#profileEmail')?.focus();
                    return;
                }

                showProfileEditError('');
                Dashboard.toggleButtonLoading(submitButton, true, { loadingLabel: 'Saving...' });
                setFormDisabled(true);

                try {
                    const payload = {
                        full_name: fullName,
                        email,
                        department
                    };

                    const response = await API.updateProfile(payload);
                    const updatedProfile = response?.data?.profile ?? response?.profile ?? null;
                    const message = response?.data?.message ?? response?.message ?? 'Profile updated successfully';

                    if (!updatedProfile) {
                        throw new Error('Profile response is missing updated data.');
                    }

                    Dashboard.setCurrentUserInStorage(updatedProfile);
                    Dashboard.updateProfileUI(updatedProfile);
                    showNotification(message, 'success');

                    closeProfileEditModal();

                    if (profileModal && !profileModal.classList.contains('hidden')) {
                        Dashboard.populateProfileEditForm(profileEditForm, updatedProfile);
                    }
                } catch (error) {
                    console.error('Failed to update profile:', error);
                    const errorMessage = error?.message || 'Failed to update profile. Please try again.';
                    showProfileEditError(errorMessage);
                    showNotification(errorMessage, 'error');
                } finally {
                    Dashboard.toggleButtonLoading(submitButton, false);
                    setFormDisabled(false);
                }
            });
        }
    }

    const notificationsModal = document.getElementById('notificationsModal');
    if (notificationsModal) {
        const openNotificationTriggers = document.querySelectorAll('[data-open-notifications]');
        const closeNotificationTriggers = notificationsModal.querySelectorAll('[data-notifications-close]');

        const refreshNotificationsBtn = notificationsModal.querySelector('[data-refresh-notifications]');

        const openNotificationsModal = async () => {
            closeMenu('notifications');
            notificationsModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            Dashboard.renderNotificationsModal(window.dashboardNotifications || []);

            try {
                await Dashboard.loadNotifications();
                Dashboard.renderNotificationsModal(window.dashboardNotifications || []);
            } catch (error) {
                console.error('Failed to refresh notifications:', error);
                showNotification('Failed to refresh notifications', 'error');
            }
        };

        const closeNotificationsModal = () => {
            notificationsModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        openNotificationTriggers.forEach(trigger => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openNotificationsModal();
            });
        });

        closeNotificationTriggers.forEach(trigger => {
            trigger.addEventListener('click', closeNotificationsModal);
        });

        if (refreshNotificationsBtn) {
            refreshNotificationsBtn.addEventListener('click', async (event) => {
                event.preventDefault();

                refreshNotificationsBtn.disabled = true;
                refreshNotificationsBtn.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    await Dashboard.loadNotifications();
                    Dashboard.renderNotificationsModal(window.dashboardNotifications || []);
                    showNotification('Notifications refreshed', 'success');
                } catch (error) {
                    console.error('Failed to refresh notifications:', error);
                    showNotification('Failed to refresh notifications', 'error');
                } finally {
                    refreshNotificationsBtn.disabled = false;
                    refreshNotificationsBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !notificationsModal.classList.contains('hidden')) {
                closeNotificationsModal();
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>