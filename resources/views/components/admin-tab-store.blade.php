<div id="tab-store" class="mt-10 hidden">
    <h3 class="text-lg font-semibold">Store Management</h3>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 border rounded md:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm text-gray-600">Store Balance</div>
                <button id="sb-refresh" class="px-2 py-1 text-xs border rounded">Refresh</button>
            </div>
            <div class="flex items-center gap-4">
                <div id="sb-value" class="text-lg font-semibold text-gray-800">--:--:--</div>
                <div class="flex items-center gap-2">
                    <input id="sb-amount" class="border rounded px-2 py-1 text-sm" placeholder="amount (e.g. 1h) or 'all'">
                    <button id="sb-transfer" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-sm">Transfer to Reserve</button>
                    <div id="sb-status" class="text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
        <div class="p-4 border rounded">
            <div class="text-sm text-gray-600 mb-2">Items</div>
            <div class="mb-3 flex items-center gap-2">
                <button id="astf-all" class="px-3 py-1.5 rounded text-sm border border-indigo-200 bg-indigo-50 text-indigo-700">All</button>
                <button id="astf-food" class="px-3 py-1.5 rounded text-sm border text-gray-700">Food</button>
                <button id="astf-water" class="px-3 py-1.5 rounded text-sm border text-gray-700">Water</button>
                <button id="astf-sold" class="px-3 py-1.5 rounded text-sm border text-gray-700">Sold Out</button>
                <span class="mx-2 h-5 w-px bg-gray-200"></span>
                <span class="text-xs text-gray-500">Sort by Qty</span>
                <button id="asts-asc" class="px-2 py-1 rounded text-xs border text-gray-700">Asc</button>
                <button id="asts-desc" class="px-2 py-1 rounded text-xs border border-indigo-200 bg-indigo-50 text-indigo-700">Desc</button>
                <span class="mx-2 h-5 w-px bg-gray-200"></span>
                <span class="text-xs text-gray-500">Page size</span>
                <select id="ast-ps" class="text-xs border rounded px-1 py-0.5">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div id="ast-status" class="text-sm text-gray-500 mb-2"></div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-1">Name</th><th class="py-1">Type</th><th class="py-1">Price</th><th class="py-1">Qty</th><th class="py-1">Effects</th><th class="py-1">Restock</th>
                    </tr>
                </thead>
                <tbody id="ast-items"></tbody>
            </table>
            <div class="mt-3 flex items-center justify-between">
                <div class="text-xs text-gray-500" id="ast-page-info">Page 1</div>
                <div class="flex items-center gap-2">
                    <button id="ast-prev" class="px-2 py-1 text-xs border rounded">Prev</button>
                    <button id="ast-next" class="px-2 py-1 text-xs border rounded">Next</button>
                </div>
            </div>
        </div>
        <div class="p-4 border rounded">
            <div class="text-sm text-gray-600 mb-2">Create Item</div>
            <div class="grid grid-cols-2 gap-2">
                <div class="col-span-2"><input id="ci-key" class="w-full border rounded px-2 py-1" placeholder="key (alpha_dash)"></div>
                <div class="col-span-2"><input id="ci-name" class="w-full border rounded px-2 py-1" placeholder="name"></div>
                <div><select id="ci-type" class="w-full border rounded px-2 py-1"><option value="food">food</option><option value="water">water</option></select></div>
                <div><input id="ci-price" type="number" min="1" class="w-full border rounded px-2 py-1" placeholder="price (sec)"></div>
                <div><input id="ci-qty" type="number" min="0" class="w-full border rounded px-2 py-1" placeholder="quantity"></div>
                <div><input id="ci-rf" type="number" min="0" max="100" class="w-full border rounded px-2 py-1" placeholder="restore food %"></div>
                <div><input id="ci-rw" type="number" min="0" max="100" class="w-full border rounded px-2 py-1" placeholder="restore water %"></div>
                <div><input id="ci-re" type="number" min="0" max="100" class="w-full border rounded px-2 py-1" placeholder="restore energy %"></div>
                <div class="flex items-center gap-2"><input id="ci-active" type="checkbox" checked><label class="text-sm">Active</label></div>
                <div class="col-span-2"><textarea id="ci-desc" class="w-full border rounded px-2 py-1" rows="2" placeholder="description"></textarea></div>
                <div class="col-span-2 text-right"><button id="ci-create" class="px-3 py-1 bg-indigo-600 text-white rounded">Create</button></div>
                <div id="ci-status" class="col-span-2 text-sm text-gray-500"></div>
            </div>
        </div>
    </div>
</div>
