const WasteManagement = (() => {
    let records = [];

    const selectors = {
        tableBody: '#waste-records-body',
        emptyState: '#waste-empty-state',
        search: '#waste-search',
        entityFilter: '#waste-entity-filter',
        statusFilter: '#waste-status-filter',
        refresh: '#waste-refresh',
        viewModal: '#waste-view-modal',
        confirmModal: '#waste-confirm-modal',
        restoreModal: '#waste-restore-modal',
        disposeModal: '#waste-dispose-modal',
        viewBody: '#waste-view-body',
        viewTitle: '#waste-view-title',
        viewSubtitle: '#waste-view-subtitle',
        viewRestoreBtn: '#waste-view-restore',
        viewDisposeBtn: '#waste-view-dispose',
        restoreDetails: '#waste-restore-details',
        restoreConfirm: '#waste-restore-confirm',
        disposeForm: '#waste-dispose-form',
        disposeId: '#waste-dispose-id',
        confirmTitle: '#waste-confirm-title',
        confirmMessage: '#waste-confirm-message',
        confirmHint: '#waste-confirm-hint',
        confirmAccept: '#waste-confirm-accept',
        confirmCancel: '#waste-confirm-cancel'
    };

    let currentRecord = null;
    let pendingRestoreRecord = null;

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
        const confirmAccept = document.querySelector(selectors.confirmAccept);
        const confirmCancel = document.querySelector(selectors.confirmCancel);

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

        document.addEventListener('click', handleModalTriggers);
        document.addEventListener('click', handleModalClose);
        document.addEventListener('click', handleConfirmDismiss);

        const disposeForm = document.querySelector(selectors.disposeForm);
        disposeForm?.addEventListener('submit', handleDisposeSubmit);
        confirmAccept?.addEventListener('click', handleConfirmAccept);
        confirmCancel?.addEventListener('click', () => {
            pendingRestoreRecord = null;
            toggleConfirmModal(false);
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
                        <td class="px-6 py-4 text-slate-500">${escapeHtml(formatEntityType(record.entity_type))}</td>
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
        const baseButton = `<button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" data-action="view" data-id="${record.id}"><i class="fas fa-eye mr-1"></i> View</button>`;

        if (record.status === 'archived') {
            return `
                <div class="inline-flex gap-2">
                    ${baseButton}
                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200" data-action="restore" data-id="${record.id}">
                        <i class="fas fa-undo mr-1"></i> Restore
                    </button>
                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300" data-action="dispose" data-id="${record.id}">
                        <i class="fas fa-trash-alt mr-1"></i> Dispose
                    </button>
                </div>
            `;
        }

        if (record.status === 'restored' || record.status === 'disposed') {
            return `
                <div class="inline-flex gap-2">
                    ${baseButton}
                </div>
            `;
        }

        return baseButton;
    }

    function handleActionClick(event) {
        const trigger = event.target.closest('button[data-action]');
        if (!trigger) return;

        const action = trigger.dataset.action;
        const id = Number(trigger.dataset.id);
        if (!id) return;

        const record = records.find((item) => item.id === id);
        if (!record) return;

        if (action === 'view') {
            openViewModal(record);
            return;
        }

        if (action === 'restore') {
            confirmRestore(record);
            return;
        }

        if (action === 'dispose') {
            openDisposeModal(record);
        }
    }

    function openViewModal(record) {
        currentRecord = record;
        populateViewModal(record);
        toggleModal(selectors.viewModal, true);
    }

    function openDisposeModal(record, presetMethod = null) {
        currentRecord = record;
        const idField = document.querySelector(selectors.disposeId);
        if (idField) idField.value = record.id;
        const methodField = document.querySelector('#waste-disposal-method');
        if (methodField) methodField.value = presetMethod || record.disposal_method || '';
        const notesField = document.querySelector('#waste-disposal-notes');
        if (notesField) notesField.value = record.disposal_notes || '';
        toggleModal(selectors.disposeModal, true);
    }

    function populateViewModal(record) {
        const title = document.querySelector(selectors.viewTitle);
        const subtitle = document.querySelector(selectors.viewSubtitle);
        const body = document.querySelector(selectors.viewBody);
        const restoreBtn = document.querySelector(selectors.viewRestoreBtn);
        const disposeBtn = document.querySelector(selectors.viewDisposeBtn);

        if (title) {
            title.textContent = record.name || 'Waste record';
        }

        if (subtitle) {
            subtitle.textContent = `${formatEntityType(record.entity_type)} • Archived ${record.archived_at ? new Date(record.archived_at).toLocaleString() : '—'}`;
        }

        if (body) {
            body.innerHTML = `
                <section class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-5">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Identifier</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">${escapeHtml(record.identifier || `#${record.entity_id}`)}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                        <div class="mt-1">${renderStatusBadge(record.status)}</div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Type</p>
                        <p class="mt-1 text-sm text-slate-700">${escapeHtml(formatEntityType(record.entity_type))}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Archived by</p>
                        <p class="mt-1 text-sm text-slate-700">${escapeHtml(record.status === 'disposed' ? (record.disposed_by_name || '—') : (record.archived_by_name || '—'))}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Current state</p>
                        <p class="mt-1 text-sm text-slate-700">${escapeHtml(record.status_detail || capitalize(record.status))}</p>
                    </div>
                </section>

                <section class="mt-4">
                    <h3 class="text-sm font-semibold text-slate-700">Archive notes</h3>
                    <p class="mt-2 text-sm text-slate-600 whitespace-pre-line bg-slate-50 border border-slate-200 rounded-lg p-3">${escapeHtml(record.archive_notes || 'No additional notes recorded.')}</p>
                </section>

                ${renderMetadataDetails(record.metadata)}

                ${renderDispositionDetails(record)}
            `;
        }

        if (restoreBtn) {
            restoreBtn.dataset.id = record.id;
            restoreBtn.classList.toggle('hidden', record.status !== 'archived');
        }

        if (disposeBtn) {
            disposeBtn.dataset.id = record.id;
            disposeBtn.classList.toggle('hidden', record.status !== 'archived');
        }
    }

    function renderDispositionDetails(record) {
        if (record.status !== 'disposed') {
            return '';
        }

        const method = record.disposal_method ? escapeHtml(record.disposal_method) : '—';
        const notes = record.disposal_notes ? escapeHtml(record.disposal_notes) : '—';

        return `
            <section class="mt-4">
                <h3 class="text-sm font-semibold text-slate-700">Disposal details</h3>
                <div class="mt-2">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Disposed at</p>
                        <p class="text-sm text-slate-600">${record.disposed_at ? new Date(record.disposed_at).toLocaleString() : '—'}</p>
                    </div>
                </div>
                <div class="mt-2 space-y-2">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Method</p>
                        <p class="text-sm text-slate-600">${method}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Notes</p>
                        <p class="text-sm text-slate-600 whitespace-pre-line">${notes}</p>
                    </div>
                </div>
            </section>
        `;
    }

    function renderMetadataDetails(metadata) {
        if (!metadata || typeof metadata !== 'object' || Array.isArray(metadata) && metadata.length === 0 || Object.keys(metadata).length === 0) {
            return '';
        }

        const rows = Object.entries(metadata).map(([key, value]) => {
            const label = capitalize(String(key).replace(/_/g, ' '));
            const formattedValue = typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value ?? '—');
            const isMultiline = /\n|\{|\[/.test(formattedValue);
            return `
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">${escapeHtml(label)}</p>
                    <p class="mt-1 text-sm ${isMultiline ? 'whitespace-pre-wrap font-mono text-xs text-slate-600' : 'text-slate-700'}">${escapeHtml(formattedValue)}</p>
                </div>
            `;
        }).join('');

        return `
            <section class="mt-4 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="h-6 w-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs"><i class="fas fa-layer-group"></i></span>
                    <h3 class="text-sm font-semibold text-slate-700">Metadata</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    ${rows}
                </div>
            </section>
        `;
    }

    async function handleDisposeSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const id = Number(form.querySelector(selectors.disposeId)?.value);
        if (!id) {
            showNotification('Missing record ID', 'error');
            return;
        }

        const formData = new FormData(form);
        const payload = {
            id,
            disposal_method: formData.get('disposal_method')?.trim() || null,
            disposal_notes: formData.get('disposal_notes')?.trim() || null
        };

        try {
            await API.disposeWasteRecord(id, payload);
            showNotification('Record marked as disposed', 'success');
            loadRecords();
        } catch (error) {
            console.error('Failed to dispose record', error);
            showNotification(error.message || 'Failed to dispose record', 'error');
        }
    }

    async function handleModalTriggers(event) {
        const trigger = event.target.closest('[data-action]');
        if (!trigger) return;

        const action = trigger.dataset.action;
        if (action === 'open-dispose') {
            toggleModal(selectors.disposeModal, true);
        }
    }

    function handleModalClose(event) {
        const closeTrigger = event.target.closest('[data-modal-close]');
        if (!closeTrigger) return;

        const modal = closeTrigger.closest('.fixed');
        if (modal) {
            toggleModal(modal, false);
        }
    }

    function toggleModal(selectorOrElement, show) {
        const element = typeof selectorOrElement === 'string'
            ? document.querySelector(selectorOrElement)
            : selectorOrElement;
        if (!element) return;

        element.classList.toggle('hidden', show === false);
    }

    function capitalize(text = '') {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function formatEntityType(entityType) {
        if (!entityType) return '—';
        if (entityType === 'asset') return 'Item';
        if (entityType === 'supply') return 'Supply';
        return capitalize(String(entityType));
    }

    async function confirmRestore(record) {
        pendingRestoreRecord = record;

        const title = document.querySelector(selectors.confirmTitle);
        const message = document.querySelector(selectors.confirmMessage);
        const hint = document.querySelector(selectors.confirmHint);

        if (title) {
            title.textContent = 'Restore item?';
        }

        if (message) {
            message.textContent = `Are you sure you want to restore "${record.name}" back to active inventory?`;
        }

        if (hint) {
            const identifier = record.identifier || `#${record.entity_id}`;
            hint.textContent = `Restoring will reactivate the record (${identifier}) and remove it from the waste management list.`;
        }

        toggleConfirmModal(true);
    }

    async function handleConfirmAccept() {
        if (!pendingRestoreRecord) {
            toggleConfirmModal(false);
            return;
        }

        const record = pendingRestoreRecord;
        try {
            await API.restoreWasteRecord(record.id);
            showNotification('Record restored successfully', 'success');
            pendingRestoreRecord = null;
            toggleConfirmModal(false);
            toggleModal(selectors.viewModal, false);
            loadRecords();
        } catch (error) {
            console.error('Failed to restore record', error);
            showNotification(error.message || 'Failed to restore record', 'error');
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

    function toggleConfirmModal(show) {
        toggleModal(selectors.confirmModal, show);
    }

    function handleConfirmDismiss(event) {
        const dismissTrigger = event.target.closest('[data-confirm-dismiss]');
        if (!dismissTrigger) return;

        pendingRestoreRecord = null;
        toggleConfirmModal(false);
    }

    document.addEventListener('click', handleActionClick);
    document.addEventListener('DOMContentLoaded', init);

    return {
        reload: loadRecords,
    };
})();
