<?php
require_once 'includes/auth_check.php';

requireAuth();

$pageTitle = "Books & eBooks - Property Custodian Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>

    <button id="mobile-menu-toggle" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-2 rounded-md">
        <i class="fas fa-bars"></i>
    </button>

    <main class="w-full lg:ml-64 flex-1 overflow-x-hidden">
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Books & eBooks</h1>
                    <p class="mt-2 text-sm text-gray-500 max-w-2xl">
                        View available printed books and digital eBooks from the connected library service.
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 px-4 sm:px-6 pt-4 sm:pt-5">
                    <nav class="flex space-x-4" aria-label="Tabs">
                        <button id="booksTab" type="button" class="px-3 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                            Books
                        </button>
                        <button id="ebooksTab" type="button" class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                            eBooks
                        </button>
                    </nav>
                </div>

                <div class="px-4 sm:px-6 py-4 sm:py-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="relative max-w-xs w-full">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-search"></i>
                            </span>
                            <input id="librarySearch" type="text" class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Search title, author, or subject">
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 font-medium">
                                <span id="libraryStatusDot" class="w-2 h-2 rounded-full bg-yellow-400 mr-2"></span>
                                <span id="libraryStatusText">Connecting to library service</span>
                            </span>
                        </div>
                    </div>

                    <div id="booksSection">
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Author</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Location</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Availability</th>
                                </tr>
                                </thead>
                                <tbody id="booksTableBody" class="bg-white divide-y divide-gray-100">
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500" id="booksPlaceholder">
                                        Loading books from library service
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="ebooksSection" class="hidden">
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Author</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Format</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Access</th>
                                </tr>
                                </thead>
                                <tbody id="ebooksTableBody" class="bg-white divide-y divide-gray-100">
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500" id="ebooksPlaceholder">
                                        Loading eBooks from library service
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="js/api.js?v=<?php echo time(); ?>"></script>
<script>
const BOOKS_API_ENDPOINT = '';
const EBOOKS_API_ENDPOINT = '';

let booksData = [];
let ebooksData = [];
let activeTab = 'books';

function setLibraryStatus(status, color) {
    const dot = document.getElementById('libraryStatusDot');
    const text = document.getElementById('libraryStatusText');
    if (dot) {
        dot.className = 'w-2 h-2 rounded-full mr-2 ' + color;
    }
    if (text) {
        text.textContent = status;
    }
}

function normalizeItems(payload) {
    if (!payload) {
        return [];
    }
    if (Array.isArray(payload)) {
        return payload;
    }
    if (Array.isArray(payload.items)) {
        return payload.items;
    }
    if (Array.isArray(payload.data)) {
        return payload.data;
    }
    return [];
}

async function fetchFromEndpoint(endpoint) {
    if (!endpoint) {
        return { items: [], message: 'Endpoint not configured' };
    }
    const response = await fetch(endpoint);
    const data = await response.json();
    return data;
}

function renderBooksTable() {
    const tbody = document.getElementById('booksTableBody');
    const search = document.getElementById('librarySearch')?.value.toLowerCase().trim() || '';

    if (!tbody) {
        return;
    }

    let items = booksData;
    if (search) {
        items = items.filter(item => {
            const title = (item.title || '').toLowerCase();
            const author = (item.author || '').toLowerCase();
            const subject = (item.subject || item.category || '').toLowerCase();
            return title.includes(search) || author.includes(search) || subject.includes(search);
        });
    }

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No books found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => {
        const title = item.title || item.name || 'Untitled';
        const author = item.author || item.creator || 'Unknown';
        const category = item.category || item.subject || '';
        const location = item.location || item.shelf || '';
        const availability = item.availability || item.status || '';

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-900 font-medium">${title}</td>
                <td class="px-4 py-2 text-gray-700">${author}</td>
                <td class="px-4 py-2 text-gray-600">${category}</td>
                <td class="px-4 py-2 text-gray-600">${location}</td>
                <td class="px-4 py-2 text-gray-600">${availability}</td>
            </tr>
        `;
    }).join('');
}

function renderEbooksTable() {
    const tbody = document.getElementById('ebooksTableBody');
    const search = document.getElementById('librarySearch')?.value.toLowerCase().trim() || '';

    if (!tbody) {
        return;
    }

    let items = ebooksData;
    if (search) {
        items = items.filter(item => {
            const title = (item.title || '').toLowerCase();
            const author = (item.author || '').toLowerCase();
            const subject = (item.subject || '').toLowerCase();
            return title.includes(search) || author.includes(search) || subject.includes(search);
        });
    }

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No eBooks found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => {
        const title = item.title || item.name || 'Untitled';
        const author = item.author || item.creator || 'Unknown';
        const subject = item.subject || item.category || '';
        const format = item.format || item.file_type || 'Digital';
        const accessUrl = item.url || item.link || item.access_url || '';

        const accessCell = accessUrl
            ? `<a href="${accessUrl}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-medium hover:bg-blue-100">Open<i class="fas fa-external-link-alt ml-1 text-[10px]"></i></a>`
            : (item.availability || item.status || '');

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-900 font-medium">${title}</td>
                <td class="px-4 py-2 text-gray-700">${author}</td>
                <td class="px-4 py-2 text-gray-600">${subject}</td>
                <td class="px-4 py-2 text-gray-600">${format}</td>
                <td class="px-4 py-2 text-gray-600">${accessCell}</td>
            </tr>
        `;
    }).join('');
}

function switchTab(tab) {
    activeTab = tab;
    const booksTab = document.getElementById('booksTab');
    const ebooksTab = document.getElementById('ebooksTab');
    const booksSection = document.getElementById('booksSection');
    const ebooksSection = document.getElementById('ebooksSection');

    if (!booksTab || !ebooksTab || !booksSection || !ebooksSection) {
        return;
    }

    if (tab === 'books') {
        booksTab.classList.add('text-blue-600', 'border-blue-600');
        booksTab.classList.remove('text-gray-500', 'border-transparent');
        ebooksTab.classList.remove('text-blue-600', 'border-blue-600');
        ebooksTab.classList.add('text-gray-500', 'border-transparent');
        booksSection.classList.remove('hidden');
        ebooksSection.classList.add('hidden');
        renderBooksTable();
    } else {
        ebooksTab.classList.add('text-blue-600', 'border-blue-600');
        ebooksTab.classList.remove('text-gray-500', 'border-transparent');
        booksTab.classList.remove('text-blue-600', 'border-blue-600');
        booksTab.classList.add('text-gray-500', 'border-transparent');
        ebooksSection.classList.remove('hidden');
        booksSection.classList.add('hidden');
        renderEbooksTable();
    }
}

async function loadLibraryData() {
    try {
        setLibraryStatus('Connecting to library service', 'bg-yellow-400');

        const [booksPayload, ebooksPayload] = await Promise.all([
            fetchFromEndpoint(BOOKS_API_ENDPOINT),
            fetchFromEndpoint(EBOOKS_API_ENDPOINT)
        ]);

        booksData = normalizeItems(booksPayload);
        ebooksData = normalizeItems(ebooksPayload);

        if (!BOOKS_API_ENDPOINT && !EBOOKS_API_ENDPOINT) {
            setLibraryStatus('Library endpoints not configured', 'bg-gray-400');
        } else {
            setLibraryStatus('Connected to library service', 'bg-green-500');
        }

        renderBooksTable();
        renderEbooksTable();
    } catch (error) {
        console.error('Error loading library data:', error);
        setLibraryStatus('Unable to reach library service', 'bg-red-500');
        const booksBody = document.getElementById('booksTableBody');
        const ebooksBody = document.getElementById('ebooksTableBody');
        if (booksBody) {
            booksBody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Unable to load books from library service</td></tr>';
        }
        if (ebooksBody) {
            ebooksBody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Unable to load eBooks from library service</td></tr>';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            overlay.classList.remove('active');
        });
    }

    const booksTab = document.getElementById('booksTab');
    const ebooksTab = document.getElementById('ebooksTab');
    const searchInput = document.getElementById('librarySearch');

    if (booksTab) {
        booksTab.addEventListener('click', () => switchTab('books'));
    }
    if (ebooksTab) {
        ebooksTab.addEventListener('click', () => switchTab('ebooks'));
    }
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (activeTab === 'books') {
                renderBooksTable();
            } else {
                renderEbooksTable();
            }
        });
    }

    loadLibraryData();
});
</script>
</body>
</html>

