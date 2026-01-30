/**
 * Responsive utilities for mobile-friendly behavior
 */

// Add responsive table behaviors
document.addEventListener('DOMContentLoaded', function() {
    initializeResponsiveFeatures();
});

function initializeResponsiveFeatures() {
    // Handle form inputs on mobile
    adjustFormInputsForMobile();

    // Handle modals on mobile
    adjustModalsForMobile();

    // Add touch-friendly behaviors
    addTouchFriendlyFeatures();

    // Handle orientation changes
    handleOrientationChange();
}

// Adjust form inputs for better mobile experience
function adjustFormInputsForMobile() {
    if (window.innerWidth <= 768) {
        // Make select dropdowns more touch-friendly
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.classList.add('text-base'); // Prevent zoom on iOS
        });

        // Adjust input field heights for better touch targets
        const inputs = document.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            if (!input.classList.contains('h-')) {
                input.style.minHeight = '44px'; // Apple's recommended minimum touch target
            }
        });
    }
}

// Adjust modals for mobile devices
function adjustModalsForMobile() {
    const modal = document.getElementById('modalContent');
    if (!modal) return;

    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && modal.children.length > 0) {
                if (window.innerWidth <= 768) {
                    // Make modal full width on mobile
                    modal.style.margin = '0';
                    modal.style.borderRadius = '0';
                    modal.style.maxHeight = '100vh';
                    modal.style.height = '100vh';

                    // Add close button for mobile if not present
                    addMobileCloseButton(modal);
                } else {
                    // Reset modal styles for desktop
                    modal.style.margin = '';
                    modal.style.borderRadius = '';
                    modal.style.maxHeight = '90vh';
                    modal.style.height = '';
                }
            }
        });
    });

    observer.observe(modal, { childList: true });
}

// Add mobile close button to modal
function addMobileCloseButton(modal) {
    const existingCloseBtn = modal.querySelector('.mobile-close-btn');
    if (existingCloseBtn) return;

    const modalHeader = modal.querySelector('.modal-header, .p-6, .px-6');
    if (modalHeader && window.innerWidth <= 768) {
        const closeBtn = document.createElement('button');
        closeBtn.className = 'mobile-close-btn lg:hidden absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-xl z-10';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.onclick = function() {
            document.getElementById('modalOverlay').classList.add('hidden');
        };

        modal.style.position = 'relative';
        modal.insertBefore(closeBtn, modal.firstChild);
    }
}

// Add touch-friendly features
function addTouchFriendlyFeatures() {
    // Add haptic feedback for buttons (where supported)
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('touchstart', function() {
            // Add visual feedback
            this.style.transform = 'scale(0.95)';
        });

        button.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';

            // Haptic feedback (iOS Safari)
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
        });
    });

    // Improve table scrolling on touch devices
    const tableResponsive = document.querySelectorAll('.table-responsive');
    tableResponsive.forEach(container => {
        // Add momentum scrolling for iOS
        container.style.webkitOverflowScrolling = 'touch';

        // Add scroll indicators
        addScrollIndicators(container);
    });
}

// Add visual scroll indicators for tables
function addScrollIndicators(container) {
    const table = container.querySelector('table');
    if (!table) return;

    // Create scroll indicator
    const indicator = document.createElement('div');
    indicator.className = 'scroll-indicator absolute top-0 right-0 bg-blue-500 text-white text-xs px-2 py-1 rounded-bl opacity-75';
    indicator.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Scroll';
    indicator.style.display = 'none';

    container.style.position = 'relative';
    container.appendChild(indicator);

    // Show indicator when table is scrollable
    function checkScrollable() {
        if (table.scrollWidth > container.clientWidth) {
            indicator.style.display = 'block';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    }

    // Check on load and resize
    checkScrollable();
    window.addEventListener('resize', checkScrollable);
}

// Handle orientation changes
function handleOrientationChange() {
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            // Refresh responsive features after orientation change
            adjustFormInputsForMobile();
            adjustModalsForMobile();

            // Trigger window resize event to update other components
            window.dispatchEvent(new Event('resize'));
        }, 100);
    });
}

// Utility function to detect mobile devices
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Export for use in other scripts
window.ResponsiveUtils = {
    initializeResponsiveFeatures,
    adjustFormInputsForMobile,
    adjustModalsForMobile,
    isMobileDevice
};