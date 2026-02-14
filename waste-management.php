<?php
require_once 'includes/auth_check.php';

requireAuth();
requirePermission('waste_management');

$pageTitle = 'Waste Management - Property Custodian Management';

ob_start();
?>

<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-x-hidden">
        <div class="p-4 lg:p-8">
            <?php include 'components/waste-management.php'; ?>
        </div>
    </main>
</div>

<script>
// Inline patch: intercept fetch errors for waste_management API to show real server response
(function() {
    const origFetch = window.fetch;
    window.fetch = async function(...args) {
        const url = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
        const resp = await origFetch.apply(this, args);
        if (url.includes('waste_management') && !resp.ok) {
            const clone = resp.clone();
            try {
                const body = await clone.text();
                console.error('[WASTE_MGMT DEBUG] Status:', resp.status, 'Body:', body.substring(0, 500));
            } catch(e) {}
        }
        return resp;
    };
})();
</script>
<script src="js/api.js?v=<?php echo time(); ?>"></script>
<script src="js/waste_management.js?v=<?php echo time(); ?>"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
