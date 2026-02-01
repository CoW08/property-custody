<!-- Waste Management Module -->
<div class="space-y-8">
    <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
            <p class="text-sm font-semibold tracking-wide text-orange-600 uppercase">Waste Management</p>
            <h1 class="text-3xl font-bold text-slate-900">Archived Items & Supplies</h1>
            <p class="text-slate-600 max-w-2xl">Review archived records, restore them to active inventory, or finalize their disposal with proper documentation.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label for="waste-search" class="sr-only">Search records</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><i class="fas fa-search"></i></span>
                    <input id="waste-search" type="search" placeholder="Search by name or identifier" class="pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500" />
                </div>
            </div>
            <div>
                <label for="waste-entity-filter" class="sr-only">Entity filter</label>
                <select id="waste-entity-filter" class="pl-3 pr-10 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Types</option>
                    <option value="asset">Items</option>
                    <option value="supply">Supplies</option>
                </select>
            </div>
            <div>
                <label for="waste-status-filter" class="sr-only">Status filter</label>
                <select id="waste-status-filter" class="pl-3 pr-10 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="archived">Archived</option>
                    <option value="restored">Restored</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
            <button id="waste-refresh" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-600 text-white font-semibold shadow-sm hover:bg-orange-700 transition">
                <i class="fas fa-sync"></i>
                Refresh
            </button>
        </div>
    </header>

    <section class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-900/5 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="min-w-full" id="waste-records-table">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">Identifier</th>
                        <th class="px-6 py-3 text-left">Name</th>
                        <th class="px-6 py-3 text-left">Type</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Archived</th>
                        <th class="px-6 py-3 text-left">Notes</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" id="waste-records-body">
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400">Loading waste management records…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-slate-50 border border-dashed border-slate-200 rounded-3xl p-6" id="waste-empty-state" hidden>
        <div class="flex flex-col items-center gap-4">
            <div class="h-16 w-16 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center text-2xl">
                <i class="fas fa-recycle"></i>
            </div>
            <div class="text-center max-w-lg">
                <h2 class="text-lg font-semibold text-slate-800">Nothing in waste management yet</h2>
                <p class="text-slate-500 text-sm">Archived items and supplies will appear here. From this panel you can restore records or record their final disposal.</p>
            </div>
        </div>
    </section>

    <!-- View Record Modal -->
    <div id="waste-view-modal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay" data-modal-close></div>
        <div class="modal-panel max-w-4xl w-full overflow-hidden mx-auto mt-10 sm:mt-16">
            <header class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-6 py-5 shadow-sm">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-300">Waste Management</p>
                    <h2 class="text-2xl font-semibold" id="waste-view-title">Waste Record Details</h2>
                    <p class="text-sm text-slate-300" id="waste-view-subtitle"></p>
                </div>
                <button class="absolute top-5 right-6 text-slate-300 hover:text-white transition" data-modal-close>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </header>

            <div class="bg-white px-6 py-6 space-y-6" id="waste-view-body">
                <!-- Populated by JS -->
            </div>

            <footer class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                <button class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition" data-modal-close>Close</button>
                <button id="waste-view-restore" data-action="restore" class="px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow hidden">
                    <i class="fas fa-undo mr-2"></i>Restore
                </button>
                <button id="waste-view-dispose" data-action="open-dispose" class="px-4 py-2 text-sm font-semibold rounded-lg bg-slate-700 text-white hover:bg-slate-800 shadow hidden">
                    <i class="fas fa-trash-alt mr-2"></i>Dispose
                </button>
            </footer>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="waste-confirm-modal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay" data-confirm-dismiss></div>
        <div class="modal-panel max-w-md w-full mx-auto mt-24 sm:mt-32 overflow-hidden">
            <header class="bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500 px-6 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-blue-100">Confirm Action</p>
                        <h2 id="waste-confirm-title" class="mt-1 text-xl font-semibold">Restore item?</h2>
                    </div>
                    <button class="text-blue-100 hover:text-white transition" data-confirm-dismiss>
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </header>

            <div class="bg-white px-6 py-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 text-lg">
                        <i class="fas fa-question"></i>
                    </span>
                    <p id="waste-confirm-message" class="text-sm text-slate-600">
                        Are you sure you want to restore this item back to active inventory?
                    </p>
                </div>
                <div class="bg-blue-50 border border-blue-100 text-xs text-blue-700 rounded-lg p-3" id="waste-confirm-hint">
                    Restoring will reactivate the record and remove it from the waste management list.
                </div>
            </div>

            <footer class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                <button id="waste-confirm-cancel" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition">No</button>
                <button id="waste-confirm-accept" class="px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow">Yes, restore</button>
            </footer>
        </div>
    </div>

    <!-- Dispose Modal -->
    <div id="waste-dispose-modal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay" data-modal-close></div>
        <div class="modal-panel max-w-lg w-full mx-auto mt-12 sm:mt-20 overflow-hidden">
            <header class="relative bg-gradient-to-r from-orange-500 via-orange-600 to-amber-600 text-white px-6 py-5">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-orange-100">Waste Management</p>
                    <h2 class="text-xl font-semibold">Finalize disposal</h2>
                    <p class="text-sm text-orange-100">Record how this item was disposed.</p>
                </div>
                <button class="absolute top-5 right-6 text-orange-100 hover:text-white transition" data-modal-close>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </header>

            <form id="waste-dispose-form" class="bg-white px-6 py-6 space-y-5">
                <div class="space-y-2">
                    <label for="waste-disposal-method" class="text-sm font-semibold text-slate-700">Disposal method *</label>
                    <select id="waste-disposal-method" name="disposal_method" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white">
                        <option value="">Select disposal method</option>
                        <option value="Reuse / Repurpose">Reuse / Repurpose</option>
                        <option value="Donation/Transfer">Donation/Transfer</option>
                        <option value="Recycling">Recycling</option>
                        <option value="Secure / Hazardous Disposal">Secure / Hazardous Disposal</option>
                        <option value="Incineration">Incineration</option>
                        <option value="Sale / Auction">Sale / Auction</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="waste-disposal-notes" class="text-sm font-semibold text-slate-700">Notes</label>
                    <textarea id="waste-disposal-notes" name="disposal_notes" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Optional details about the disposal"></textarea>
                </div>

                <input type="hidden" id="waste-dispose-id" name="id">

                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition" data-modal-close>Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 shadow">
                        Record disposal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
