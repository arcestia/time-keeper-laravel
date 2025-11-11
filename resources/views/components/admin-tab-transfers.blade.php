<div id="tab-transfers" class="mt-10 hidden">
    <h3 class="text-lg font-semibold">Time Reserve Transfers</h3>
    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 border rounded">
            <div class="text-sm text-gray-600 mb-1">Deposit from User Bank → Reserve</div>
            <input id="tr-dep-username" type="text" placeholder="username" class="border rounded px-3 py-2 w-full mb-2" />
            <input id="tr-dep-amount" type="text" placeholder="amount (e.g. 1d 2h)" class="border rounded px-3 py-2 w-full mb-2" />
            <button id="tr-dep-btn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded">Deposit</button>
        </div>
        <div class="p-4 border rounded">
            <div class="text-sm text-gray-600 mb-1">Withdraw from Reserve → User Bank</div>
            <input id="tr-wd-username" type="text" placeholder="username" class="border rounded px-3 py-2 w-full mb-2" />
            <input id="tr-wd-amount" type="text" placeholder="amount (e.g. 1d 2h)" class="border rounded px-3 py-2 w-full mb-2" />
            <button id="tr-wd-btn" type="button" class="bg-rose-600 text-white px-4 py-2 rounded">Withdraw</button>
        </div>
    </div>
    <div class="mt-4 p-4 border rounded">
        <div class="text-sm text-gray-600 mb-2">Distribute Reserve → All Users (per-user amount)</div>
        <div class="flex gap-2 flex-wrap items-center">
            <input id="tr-dist-amount" type="text" placeholder="amount per user (e.g. 1h 30m)" class="border rounded px-3 py-2 w-80" />
            <button id="tr-dist-btn" type="button" class="bg-emerald-600 text-white px-4 py-2 rounded">Distribute</button>
        </div>
        <div id="tr-status" class="mt-2 text-sm text-gray-500"></div>
    </div>

    <div class="mt-4 p-4 border rounded">
        <div class="text-sm text-gray-600 mb-2">Transfer Reserve → Store (amount)</div>
        <div class="flex gap-2 flex-wrap items-center">
            <input id="tr-rs-amount" type="text" placeholder="amount (e.g. 1h 30m)" class="border rounded px-3 py-2 w-80" />
            <button id="tr-rs-btn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded">Reserve → Store</button>
        </div>
        <div id="tr-rs-status" class="mt-2 text-sm text-gray-500"></div>
    </div>
</div>
