<?php
require_once 'includes/auth_check.php';

// Require authentication for this page
requireAuth();

$pageTitle = "Asset Registry & Tagging - Property Custodian Management";

ob_start();
?>

<!-- Asset Registry Content -->
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 px-4 py-3 flex justify-between items-center">
        <button onclick="toggleMobileMenu()" class="p-2 text-gray-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-lg font-semibold text-gray-800">Asset Registry</h1>
        <div class="w-8"></div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 overflow-x-hidden">
        <div class="p-4 lg:p-8 pt-16 lg:pt-8">
            <?php include 'components/asset-registry.php'; ?>
        </div>
    </main>
</div>

<?php include 'components/modal.php'; ?>
<?php include 'components/detail_modal.php'; ?>

<script src="js/api.js?v=20240205"></script>
<script src="js/asset_management.js?v=2026020706"></script>
<script src="js/detail_handlers.js?v=20240205"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('assetsTable')) {
        initAssetManagement();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
