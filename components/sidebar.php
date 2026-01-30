<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>

<!-- Sidebar -->
<nav class="bg-white shadow-lg w-64 fixed h-full overflow-y-auto sidebar-mobile lg:translate-x-0 z-50" id="sidebar">
    <div class="p-6 border-b border-gray-200">
        <img src="logos/logo.jpg" alt="School Logo" class="h-12 w-12 rounded-full mx-auto mb-2 shadow-sm ring-4 ring-indigo-50">
        <h2 class="text-lg font-bold text-gray-800 text-center">Property Custodian</h2>
        <p class="text-sm text-gray-600 text-center">Management System</p>
    </div>

    <div class="px-6 text-xs font-semibold text-gray-400 uppercase tracking-wide mt-4">Overview</div>
    <ul class="mt-2">
        <?php if (checkMenuAccess('dashboard')): ?>
        <li>
            <a href="dashboard.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-chart-line mr-3"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="mt-6">
        <?php $inventoryActive = in_array(basename($_SERVER['PHP_SELF']), ['asset-registry.php','supplies-inventory.php','waste-management.php','damaged-items.php']); ?>
        <button type="button" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 text-xs font-semibold uppercase tracking-wide <?php echo $inventoryActive ? 'text-blue-600' : 'text-gray-500'; ?> hover:text-blue-600 transition" data-target="inventoryMenu" data-default-open="true" data-opened="<?php echo $inventoryActive ? 'true' : 'false'; ?>">
            <span>Inventory Lifecycle</span>
            <i class="fas fa-chevron-right text-xs chevron transition-transform duration-200"></i>
        </button>
        <ul id="inventoryMenu" class="submenu <?php echo $inventoryActive ? '' : 'hidden'; ?> mt-2 space-y-1 pb-1">
            <?php if (checkMenuAccess('asset_registry')): ?>
            <li><a href="asset-registry.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'asset-registry.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-tags mr-3"></i> Item Registry & Tagging
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('supplies_inventory')): ?>
            <li><a href="supplies-inventory.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'supplies-inventory.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-boxes mr-3"></i> Supplies Inventory
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('waste_management')): ?>
            <li><a href="waste-management.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-orange-50 hover:text-orange-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'waste-management.php') ? 'border-r-4 border-orange-500 bg-orange-50 text-orange-600' : ''; ?>">
                <i class="fas fa-recycle mr-3"></i> Waste Management
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('damaged_items')): ?>
            <li><a href="damaged-items.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'damaged-items.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-exclamation-triangle mr-3"></i> Damaged Items
            </a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="mt-4">
        <?php $operationsActive = in_array(basename($_SERVER['PHP_SELF']), ['property-issuance.php','custodian-assignment.php','procurement.php','maintenance.php']); ?>
        <button type="button" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 text-xs font-semibold uppercase tracking-wide <?php echo $operationsActive ? 'text-blue-600' : 'text-gray-500'; ?> hover:text-blue-600 transition" data-target="operationsMenu" data-opened="<?php echo $operationsActive ? 'true' : 'false'; ?>">
            <span>Operations & Requests</span>
            <i class="fas fa-chevron-right text-xs chevron transition-transform duration-200"></i>
        </button>
        <ul id="operationsMenu" class="submenu <?php echo $operationsActive ? '' : 'hidden'; ?> mt-2 space-y-1 pb-1">
            <?php if (checkMenuAccess('property_issuance')): ?>
            <li><a href="property-issuance.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'property-issuance.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-handshake mr-3"></i> Property Issuance
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('custodian_assignment')): ?>
            <li><a href="custodian-assignment.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'custodian-assignment.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-user-tie mr-3"></i> Custodian Assignment
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('procurement')): ?>
            <li><a href="procurement.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'procurement.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-shopping-cart mr-3"></i> Procurement
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('preventive_maintenance')): ?>
            <li><a href="maintenance.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'maintenance.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-wrench mr-3"></i> Preventive Maintenance
            </a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="mt-4">
        <?php $insightsActive = in_array(basename($_SERVER['PHP_SELF']), ['property-audit.php','forecasting.php','reports.php']); ?>
        <button type="button" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 text-xs font-semibold uppercase tracking-wide <?php echo $insightsActive ? 'text-blue-600' : 'text-gray-500'; ?> hover:text-blue-600 transition" data-target="insightsMenu" data-opened="<?php echo $insightsActive ? 'true' : 'false'; ?>">
            <span>Insights & Compliance</span>
            <i class="fas fa-chevron-right text-xs chevron transition-transform duration-200"></i>
        </button>
        <ul id="insightsMenu" class="submenu <?php echo $insightsActive ? '' : 'hidden'; ?> mt-2 space-y-1 pb-1">
            <?php if (checkMenuAccess('property_audit')): ?>
            <li><a href="property-audit.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'property-audit.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-clipboard-check mr-3"></i> Property Audit
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('ai_demand_forecasting')): ?>
            <li><a href="forecasting.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'forecasting.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-robot mr-3"></i> AI Forecasting
            </a></li>
            <?php endif; ?>

            <?php if (checkMenuAccess('reports_analytics')): ?>
            <li><a href="reports.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-chart-bar mr-3"></i> Reports & Analytics
            </a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="mt-4">
        <?php $adminActive = basename($_SERVER['PHP_SELF']) === 'user-roles.php'; ?>
        <button type="button" class="dropdown-toggle w-full flex items-center justify-between px-6 py-2 text-xs font-semibold uppercase tracking-wide <?php echo $adminActive ? 'text-blue-600' : 'text-gray-500'; ?> hover:text-blue-600 transition" data-target="adminMenu" data-opened="<?php echo $adminActive ? 'true' : 'false'; ?>">
            <span>Administration</span>
            <i class="fas fa-chevron-right text-xs chevron transition-transform duration-200"></i>
        </button>
        <ul id="adminMenu" class="submenu <?php echo $adminActive ? '' : 'hidden'; ?> mt-2 space-y-1 pb-8 mb-16">
            <?php if (checkMenuAccess('user_roles_access')): ?>
            <li><a href="user-roles.php" class="menu-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'user-roles.php') ? 'border-r-4 border-blue-600 bg-blue-50 text-blue-600' : ''; ?>">
                <i class="fas fa-users-cog mr-3"></i> User Roles & Access
            </a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sticky bottom-0 w-full px-6 pb-6 pt-5 border-t border-slate-200 bg-white/90 backdrop-blur">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-indigo-500 to-purple-500 text-white text-lg shadow-md shadow-blue-500/25">
                <i class="fas fa-user"></i>
            </div>
            <div class="min-w-0">
                <p id="currentUsername" class="truncate text-sm font-semibold text-slate-800"><?php echo htmlspecialchars(getCurrentUser()['full_name'] ?? 'User'); ?></p>
                <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-blue-100/80 px-2 py-0.5 text-[11px] font-medium text-blue-600">
                    <i class="fas fa-shield-alt"></i>
                    <span id="currentRole"><?php echo htmlspecialchars(ucfirst(getCurrentUser()['role'] ?? 'user')); ?></span>
                </div>
            </div>
        </div>
        <button id="logoutBtn" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/30 transition hover:shadow-blue-500/45 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 focus:ring-offset-white">
            <i class="fas fa-sign-out-alt text-base"></i>
            Logout
        </button>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Dropdown toggles
function initializeDropdowns() {
    const toggles = document.querySelectorAll('.dropdown-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const targetId = toggle.getAttribute('data-target');
            const submenu = document.getElementById(targetId);
            const chevron = toggle.querySelector('.chevron');

            if (submenu) {
                const isOpen = submenu.classList.contains('hidden');
                if (isOpen) {
                    submenu.classList.remove('hidden');
                    chevron?.classList.add('rotate-90');
                    toggle.dataset.opened = 'true';
                } else {
                    submenu.classList.add('hidden');
                    chevron?.classList.remove('rotate-90');
                    toggle.dataset.opened = 'false';
                }
            }
        });
    });

    // Respect default-open or pre-opened states
    toggles.forEach(toggle => {
        const targetId = toggle.getAttribute('data-target');
        const submenu = document.getElementById(targetId);
        const chevron = toggle.querySelector('.chevron');
        if (!submenu) return;

        if (toggle.dataset.defaultOpen === 'true' || toggle.dataset.opened === 'true') {
            submenu.classList.remove('hidden');
            chevron?.classList.add('rotate-90');
            toggle.dataset.opened = 'true';
        }

        // Auto-open the submenu containing the active page
        const activeLink = submenu.querySelector('.bg-blue-50, .text-blue-600');
        if (activeLink) {
            submenu.classList.remove('hidden');
            chevron?.classList.add('rotate-90');
            toggle.dataset.opened = 'true';
        }
    });
}

// Close mobile menu when clicking on a menu item
document.addEventListener('DOMContentLoaded', function() {
    initializeDropdowns();

    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                toggleMobileMenu();
            }
        });
    });

    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function() {
            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    window.location.href = 'login.php';
                } else {
                    console.error('Logout failed');
                }
            } catch (error) {
                console.error('Error during logout:', error);
            }
        });
    }
});
</script>