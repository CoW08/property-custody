<!-- Universal Detail Modal Component -->
<div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 id="modalTitle" class="text-2xl font-bold text-gray-900"></h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div id="modalBody" class="py-4 max-h-[70vh] overflow-y-auto">
            <!-- Content will be loaded here -->
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                <p class="mt-4 text-gray-600">Loading details...</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div id="modalFooter" class="flex items-center justify-end pt-3 border-t gap-2">
            <button onclick="closeDetailModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Universal Detail Modal Functions
function openDetailModal(title, content, footer = '') {
    const modal = document.getElementById('detailModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalFooter = document.getElementById('modalFooter');
    
    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    
    if (footer) {
        modalFooter.innerHTML = footer;
    } else {
        modalFooter.innerHTML = `
            <button onclick="closeDetailModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        `;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDetailModal();
            }
        });
    }
});

// Helper function to format detail rows
function createDetailSection(title, rows) {
    let html = `
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">${title}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    `;
    
    rows.forEach(row => {
        html += `
            <div class="flex flex-col">
                <span class="text-sm font-medium text-gray-500">${row.label}</span>
                <span class="text-base text-gray-900">${row.value || 'N/A'}</span>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// Helper function to create status badge
function createStatusBadge(status, type = 'default') {
    const colors = {
        'success': 'bg-green-100 text-green-800',
        'warning': 'bg-yellow-100 text-yellow-800',
        'danger': 'bg-red-100 text-red-800',
        'info': 'bg-blue-100 text-blue-800',
        'default': 'bg-gray-100 text-gray-800'
    };
    
    return `<span class="px-3 py-1 rounded-full text-sm font-semibold ${colors[type] || colors.default}">${status}</span>`;
}
</script>

<style>
#modalBody::-webkit-scrollbar {
    width: 8px;
}

#modalBody::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#modalBody::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

#modalBody::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media print {
    #detailModal {
        display: none !important;
    }
}
</style>
