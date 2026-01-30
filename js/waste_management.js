const WasteManagement = (() => {
    let records = [];

    const selectors = {
        tableBody: '#waste-records-body',
        emptyState: '#waste-empty-state',
        search: '#waste-search',
        entityFilter: '#waste-entity-filter',
        statusFilter: '#waste-status-filter',
        refresh: '#waste-refresh',
    };

    function init() {
        if (!document.querySelector(selectors.tableBody)) {
            return;
        }

        bindEvents();
        loadRecords();
    }

    function bindEvents() {
        const searchInput = document.querySelector(selectors.search);
        const entityFilter = document.querySelector(selectors.entityFilter);
        const statusFilter = document.querySelector(selectors.statusFilter);
        const refreshBtn = document.querySelector(selectors.refresh);

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(loadRecords, 300);
            });
        }

        [entityFilter, statusFilter].forEach((filter) => {
            filter?.addEventListener('change', loadRecords);
        });

        refreshBtn?.addEventListener('click', () => {
            searchInput && (searchInput.value = '');
            loadRecords();
        });
    }

    async function loadRecords() {
        try {
            const params = buildQueryParams();
            const response = await API.getWasteRecords(params);
            records = response?.data || [];
            render();
        } catch (error) {
            console.error('Failed to load waste records', error);
            showNotification('Failed to load waste management records', 'error');
        }
    }

    function buildQueryParams() {
        const params = {};
        const searchValue = document.querySelector(selectors.search)?.value?.trim();
        const entityValue = document.querySelector(selectors.entityFilter)?.value;
        const statusValue = document.querySelector(selectors.statusFilter)?.value;

        if (searchValue) params.search = searchValue;
        if (entityValue) params.entity_type = entityValue;
        if (statusValue) params.status = statusValue;

        return params;
    }

    function render() {
        const tbody = document.querySelector(selectors.tableBody);
        const emptyState = document.querySelector(selectors.emptyState);

        if (!tbody) return;

        if (!records.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-slate-400">No records found</td></tr>';
            if (emptyState) emptyState.hidden = false;
            return;
        }

        if (emptyState) emptyState.hidden = true;

        tbody.innerHTML = records
            .map((record) => {
                const statusBadge = renderStatusBadge(record.status);
                const archivedAt = record.archived_at
                    ? new Date(record.archived_at).toLocaleString()
                    : '—';
                const notes = record.archive_notes || '—';

                const actions = renderActions(record);

                return `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">${escapeHtml(record.identifier || record.entity_id)}</td>
                        <td class="px-6 py-4 text-slate-600">${escapeHtml(record.name)}</td>
                        <td class="px-6 py-4 text-slate-500 capitalize">${escapeHtml(record.entity_type)}</td>
                        <td class="px-6 py-4">${statusBadge}</td>
                        <td class="px-6 py-4 text-slate-500">${archivedAt}</td>
                        <td class="px-6 py-4 text-slate-500">${escapeHtml(notes)}</td>
                        <td class="px-6 py-4 text-right">${actions}</td>
                    </tr>
                `;
            })
            .join('');
    }

    function renderStatusBadge(status) {
        const map = {
            archived: 'bg-orange-100 text-orange-600',
            restored: 'bg-blue-100 text-blue-600',
            disposed: 'bg-slate-200 text-slate-600',
        };

        const label = {
            archived: 'Archived',
            restored: 'Restored',
            disposed: 'Disposed',
        };

        const classes = map[status] || 'bg-slate-100 text-slate-600';
        return `<span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-semibold rounded-full ${classes}">
            <span class="inline-block h-2 w-2 rounded-full bg-current"></span>
            ${label[status] || status}
        </span>`;
    }

    function renderActions(record) {
        if (record.status === 'archived') {
            return `
                <div class="inline-flex gap-2">
                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200" data-action="restore" data-id="${record.id}">
                        <i class="fas fa-undo mr-1"></i> Restore
                    </button>
                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300" data-action="dispose" data-id="${record.id}">
                        <i class="fas fa-trash-alt mr-1"></i> Dispose
                    </button>
                </div>
            `;
        }

        if (record.status === 'restored') {
            return `<span class="text-xs text-slate-400">Restored ${record.restored_at ? new Date(record.restored_at).toLocaleDateString() : ''}</span>`;
        }

        if (record.status === 'disposed') {
            const method = record.disposal_method ? ` • ${escapeHtml(record.disposal_method)}` : '';
            return `<span class="text-xs text-slate-400">Disposed ${record.disposed_at ? new Date(record.disposed_at).toLocaleDateString() : ''}${method}</span>`;
        }

        return '';
    }

    function handleActionClick(event) {
        const trigger = event.target.closest('button[data-action]');
        if (!trigger) return;

        const action = trigger.dataset.action;
        const id = Number(trigger.dataset.id);
        if (!id) return;

        if (action === 'restore') {
            restoreRecord(id);
        } else if (action === 'dispose') {
            disposeRecord(id);
        }
    }

    async function restoreRecord(id) {
        if (!confirm('Restore this record to active inventory?')) return;

        try {
            await API.restoreWasteRecord(id);
            showNotification('Record restored successfully', 'success');
            loadRecords();
        } catch (error) {
            console.error('Failed to restore record', error);
            showNotification(error.message || 'Failed to restore record', 'error');
        }
    }

    async function disposeRecord(id) {
        const method = prompt('Disposal method (e.g., auctioned, recycled, destroyed):', '');
        if (method === null) return;
        const notes = prompt('Additional notes (optional):', '') || null;

        try {
            await API.disposeWasteRecord(id, {
                disposal_method: method || null,
                disposal_notes: notes,
            });
            showNotification('Record marked as disposed', 'success');
            loadRecords();
        } catch (error) {
            console.error('Failed to dispose record', error);
            showNotification(error.message || 'Failed to dispose record', 'error');
        }
    }

    function escapeHtml(value = '') {
        return value
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('click', handleActionClick);
    document.addEventListener('DOMContentLoaded', init);

    return {
        reload: loadRecords,
    };
})();
