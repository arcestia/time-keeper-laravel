<div id="tab-tokens" class="hidden">
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">Search users (username)</label>
            <input id="tok-q" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. alice">
        </div>

        <button id="tok-search" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-700 disabled:opacity-25 transition">Search</button>
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="text-sm text-gray-600 mb-2">Results</div>
            <ul id="tok-results" class="divide-y border rounded"></ul>
        </div>
        <div>
            <div class="text-sm text-gray-600 mb-2">Grant Time Tokens</div>
            <div id="tok-user" class="text-sm text-gray-500">Select a user in the Results list, then grant tokens.</div>
            <form id="tok-form" class="hidden space-y-3 mt-2">
                <div class="text-sm" id="tok-user-head"></div>
                <div class="grid grid-cols-3 gap-2 items-end">
                    <div>
                        <label for="tok-token-color" class="block text-xs text-gray-600">Color</label>
                        <select id="tok-token-color" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="red">Red</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="yellow">Yellow</option>
                            <option value="black">Black</option>
                        </select>
                    </div>
                    <div>
                        <label for="tok-token-qty" class="block text-xs text-gray-600">Quantity</label>
                        <input id="tok-token-qty" type="number" min="1" value="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button id="tok-token-grant" type="button" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Grant</button>
                    </div>
                </div>
                <div id="tok-token-status" class="text-xs text-gray-500"></div>
            </form>
        </div>
    </div>
</div>
