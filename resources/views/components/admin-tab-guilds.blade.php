<div id="tab-guilds" class="hidden">
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm text-gray-600">Manage guilds (lock/unlock). Locking a guild prevents joins, leaves, and disband.</div>
        <button id="ag-refresh" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-xs rounded">Refresh</button>
    </div>
    <div id="ag-status" class="text-xs text-gray-500 mb-2"></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left text-xs text-gray-500 uppercase">
                    <th class="py-1 pr-3">Name</th>
                    <th class="py-1 pr-3">Owner</th>
                    <th class="py-1 pr-3">Members</th>
                    <th class="py-1 pr-3">Status</th>
                    <th class="py-1">Actions</th>
                </tr>
            </thead>
            <tbody id="ag-list" class="divide-y"></tbody>
        </table>
    </div>
</div>
