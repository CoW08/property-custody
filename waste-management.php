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

<script src="js/api.js"></script>
<script src="js/waste_management.js"></script>

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
