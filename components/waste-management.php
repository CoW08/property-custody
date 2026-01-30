<!-- Waste Management Module -->
<div class="space-y-8">
    <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
            <p class="text-sm font-semibold tracking-wide text-orange-600 uppercase">Waste Management</p>
            <h1 class="text-3xl font-bold text-slate-900">Archived Assets & Supplies</h1>
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
                    <option value="asset">Assets</option>
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
        <div class="overflow-x-auto">
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
                <p class="text-slate-500 text-sm">Archived assets and supplies will appear here. From this panel you can restore records or record their final disposal.</p>
            </div>
        </div>
    </section>
</div>
