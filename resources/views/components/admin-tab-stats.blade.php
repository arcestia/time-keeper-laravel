<div id="tab-stats">
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">Search users (username/email)</label>
            <input id="adm-q" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. alice or alice@example.com">
        </div>

        <button id="adm-search" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-700 disabled:opacity-25 transition">Search</button>
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-sm text-gray-600 mb-2">Results</div>
            <ul id="adm-results" class="divide-y border rounded"></ul>
        </div>
        <div>
            <div class="text-sm text-gray-600 mb-2">User Stats</div>
            <div id="adm-user" class="text-sm text-gray-500">Select a user to view/edit stats.</div>
            <form id="adm-form" class="hidden space-y-3 mt-2">
                <div class="text-sm" id="adm-user-head"></div>
                <div>
                    <label class="block text-sm">Energy <span id="v-energy" class="ml-1 text-gray-500">--</span>%</label>
                    <input type="range" id="energy" min="0" max="100" value="100" class="w-full">
                </div>
                <div>
                    <label class="block text-sm">Food <span id="v-food" class="ml-1 text-gray-500">--</span>%</label>
                    <input type="range" id="food" min="0" max="100" value="100" class="w-full">
                </div>
                <div>
                    <label class="block text-sm">Water <span id="v-water" class="ml-1 text-gray-500">--</span>%</label>
                    <input type="range" id="water" min="0" max="100" value="100" class="w-full">
                </div>
                <div>
                    <label class="block text-sm">Leisure <span id="v-leisure" class="ml-1 text-gray-500">--</span>%</label>
                    <input type="range" id="leisure" min="0" max="100" value="100" class="w-full">
                </div>
                <div>
                    <label class="block text-sm">Health <span id="v-health" class="ml-1 text-gray-500">--</span>%</label>
                    <input type="range" id="health" min="0" max="100" value="100" class="w-full">
                </div>
                <div class="flex gap-2">
                    <button id="adm-save" type="button" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">Save</button>
                    <div id="adm-status" class="text-sm text-gray-500"></div>
                </div>
            </form>
        </div>
    </div>
</div>
