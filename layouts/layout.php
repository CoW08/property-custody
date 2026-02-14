<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'School Management System - Property Custodian Management'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1d4ed8;
            --primary-strong: #153eaf;
            --surface: #ffffff;
            --bg: #f6f8fd;
            --muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 18px 45px -24px rgba(17, 24, 39, 0.35);
            --radius: 16px;
        }

        body {
            background: var(--bg);
            min-height: 100vh;
            color: #0f172a;
            font-family: 'Nunito', 'Segoe UI', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Page scaffolding */
        main {
            background: var(--bg);
        }

        /* Page transition wrapper */
        .page-transition {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.35s ease, transform 0.35s ease;
        }

        .page-transition.page-entered {
            opacity: 1;
            transform: translateY(0);
        }

        .page-transition.page-exiting {
            opacity: 0;
            transform: translateY(-12px);
        }

        /* Card surfaces to match dashboard tiles */
        .bg-white.shadow, .bg-white.shadow-md, .bg-white.shadow-lg, .card-surface {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        /* Sidebar refinements */
        #sidebar {
            border-right: 1px solid var(--border);
            background: var(--surface);
        }

        #sidebar .p-6 {
            background: linear-gradient(180deg, #eef2ff, #ffffff);
        }

        .menu-item {
            border-radius: 12px;
            margin: 4px 12px;
            transition: all 0.2s ease;
        }

        .menu-item:hover {
            background: #eef2ff;
            color: var(--primary);
        }

        .menu-item.bg-blue-50,
        .menu-item.text-blue-600 {
            background: var(--primary);
            color: #ffffff !important;
            border-right: none !important;
            box-shadow: 0 12px 30px -18px rgba(29, 78, 216, 0.75);
        }

        .menu-item.bg-blue-50 i {
            color: #ffffff;
        }

        /* Top search bar look (when present) */
        .top-search, input[type="search"].top-search {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9999px;
            padding: 10px 16px;
            box-shadow: 0 10px 32px -20px rgba(17, 24, 39, 0.55);
        }

        .top-search:focus {
            outline: 2px solid #c7d2fe;
            border-color: #c7d2fe;
        }

        /* Section headers and quick-action cards */
        h1, h2, h3, h4 {
            color: #0b1324;
        }

        .quick-action {
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            background: #f8fbff;
        }

        /* Form controls */
        input:not([type="checkbox"]):not([type="radio"]), select, textarea {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 12px 30px -24px rgba(17, 24, 39, 0.25);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #c7d2fe;
            box-shadow: 0 16px 40px -26px rgba(29, 78, 216, 0.55), 0 0 0 3px rgba(199, 210, 254, 0.75);
            transform: translateY(-1px);
        }

        /* Buttons */
        button {
            transition: transform 0.08s ease, box-shadow 0.12s ease;
        }

        button:active {
            transform: translateY(1px) scale(0.995);
        }

        .btn-primary, .bg-blue-600 {
            box-shadow: 0 12px 32px -18px rgba(37, 99, 235, 0.55);
        }

        /* Tables */
        .table-striped tbody tr:nth-child(even) {
            background: #f9fbff;
        }

        table th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
        }

        table th, table td {
            border-color: var(--border);
        }

        /* Table rows hover */
        table tbody tr:hover {
            background: #f4f7ff;
        }

        /* Status pills */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 12px;
            border: 1px solid transparent;
        }
        .pill.success { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
        .pill.warning { background: #fffbeb; color: #92400e; border-color: #fcd34d; }
        .pill.danger  { background: #fef2f2; color: #b91c1c; border-color: #fecdd3; }
        .pill.info    { background: #eef2ff; color: #312e81; border-color: #c7d2fe; }

        /* Card headers */
        .card-head {
            border-bottom: 1px solid var(--border);
            background: linear-gradient(180deg, #f9fbff, #fff);
        }

        /* Modals */
        .modal-overlay {
            backdrop-filter: blur(2px);
            background: rgba(15, 23, 42, 0.35);
        }

        .modal-panel {
            border-radius: 18px;
            box-shadow: 0 24px 60px -32px rgba(15, 23, 42, 0.45);
            border: 1px solid var(--border);
        }

        /* Subtle table/box outlines */
        .outline-panel {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
        }

        /* Mobile menu toggle */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            display: none;
        }

        .mobile-menu-overlay.active {
            display: block;
        }

        /* Responsive table wrapper */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Mobile responsive utilities */
        @media (max-width: 768px) {
            .table-responsive table {
                min-width: 800px;
            }

            .mobile-hidden {
                display: none !important;
            }

            .mobile-full-width {
                width: 100% !important;
            }
        }

        /* Sidebar responsive styles */
        @media (max-width: 1024px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-mobile.active {
                transform: translateX(0);
            }
        }
    </style>
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body>
    <div id="pageTransitionContainer" class="page-transition">
        <?php echo $content; ?>
    </div>

    <!-- Scripts -->
    <script src="js/responsive.js"></script>
    <script>
    (function() {
        const container = document.getElementById('pageTransitionContainer');
        if (!container) return;

        // Enter animation
        requestAnimationFrame(() => {
            container.classList.add('page-entered');
        });

        function navigateWithTransition(href) {
            if (!href) return;
            if (container.classList.contains('page-exiting')) {
                window.location.href = href;
                return;
            }
            container.classList.add('page-exiting');
            setTimeout(() => {
                window.location.href = href;
            }, 220);
        }

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || link.target === '_blank' || link.dataset.noTransition !== undefined) {
                return;
            }

            // Only intercept same-origin navigations
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return;

            event.preventDefault();
            navigateWithTransition(url.href);
        });

        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                container.classList.remove('page-exiting');
                requestAnimationFrame(() => container.classList.add('page-entered'));
            }
        });
    })();
    </script>
    <?php
    // Only load auth.js on dashboard (not on login page)
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage === 'dashboard.php') {
        echo '<script src="js/auth.js"></script>';
    }
    ?>
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Session Activity Tracker -->
    <script>
    (function() {
        let lastPing = 0;
        const PING_INTERVAL = 120000; // Ping server every 2 minutes of activity (was 60s — too aggressive)
        const SESSION_WARN = 25 * 60 * 1000; // Warn at 25 minutes
        let sessionTimer = null;
        let lastActivity = Date.now();
        let isExpired = false; // Prevent multiple redirects

        function resetSessionTimer() {
            lastActivity = Date.now();
            if (sessionTimer) clearTimeout(sessionTimer);
            sessionTimer = setTimeout(function() {
                if (isExpired) return;
                if (confirm('Your session is about to expire due to inactivity. Click OK to stay logged in.')) {
                    pingServer(true); // force ping
                } else {
                    handleExpired();
                }
            }, SESSION_WARN);
        }

        function handleExpired() {
            if (isExpired) return; // Only redirect once
            isExpired = true;
            window.location.href = 'login.php?session=expired';
        }

        function pingServer(force) {
            if (isExpired) return;
            var now = Date.now();
            if (!force && (now - lastPing < PING_INTERVAL)) return;
            lastPing = now;
            fetch('api/session_keepalive.php', { method: 'POST', credentials: 'same-origin' })
                .then(function(r) {
                    if (r.status === 401) {
                        handleExpired();
                        return;
                    }
                    // Check if we were redirected to login page (session expired mid-flight)
                    if (r.redirected && r.url && r.url.indexOf('login') !== -1) {
                        handleExpired();
                        return;
                    }
                    // Verify we got valid JSON back (not an HTML login page)
                    return r.text().then(function(txt) {
                        try {
                            var data = JSON.parse(txt);
                            if (data.session_expired) {
                                handleExpired();
                            }
                        } catch(e) {
                            // Got HTML back (probably redirected to login) — session is dead
                            handleExpired();
                        }
                    });
                })
                .catch(function() {
                    // Network error — don't redirect, just skip
                });
            resetSessionTimer();
        }

        // Throttled activity listener — only track meaningful events
        var activityThrottle = 0;
        function onActivity() {
            var now = Date.now();
            if (now - activityThrottle < 5000) return; // Max once per 5 seconds
            activityThrottle = now;
            pingServer(false);
        }

        ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(evt) {
            document.addEventListener(evt, onActivity, { passive: true });
        });

        // mousemove is very noisy — use a much longer throttle
        var moveThrottle = 0;
        document.addEventListener('mousemove', function() {
            var now = Date.now();
            if (now - moveThrottle < 30000) return; // Max once per 30 seconds for mousemove
            moveThrottle = now;
            onActivity();
        }, { passive: true });

        resetSessionTimer();
    })();
    </script>
    <?php endif; ?>

    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
