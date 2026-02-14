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

<script>
// Debug: intercept fetch for assets API to log actual server response on errors
(function() {
    const origFetch = window.fetch;
    window.fetch = async function(...args) {
        const url = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
        const resp = await origFetch.apply(this, args);
        if (url.includes('assets.php') && !resp.ok) {
            const clone = resp.clone();
            try {
                const body = await clone.text();
                console.error('[ASSETS DEBUG] Status:', resp.status, 'URL:', url, 'Body:', body.substring(0, 1000));
                // Show in notification if available
                if (typeof showNotification === 'function') {
                    const msg = body.startsWith('{') ? JSON.parse(body).message || body.substring(0,200) : body.substring(0,200);
                    showNotification('Assets API Error: ' + msg, 'error');
                }
            } catch(e) { console.error('[ASSETS DEBUG] Could not read body:', e); }
        }
        return resp;
    };
})();
</script>
<script src="js/api.js?v=<?php echo time(); ?>"></script>
<script src="js/asset_management.js?v=<?php echo time(); ?>"></script>
<script src="js/detail_handlers.js?v=<?php echo time(); ?>"></script>

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
