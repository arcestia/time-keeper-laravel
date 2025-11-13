<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventory</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-lg font-semibold">Your Items</div>
                            <div class="mt-1 text-xs text-gray-600">Inventory items can be consumed or moved to storage. Storage has unlimited capacity.</div>
                            <div id="inv-meta" class="mt-1 text-xs text-gray-700">Inventory total: 0 • Global cap: 20,000</div>
                        </div>
                        <button id="inv-refresh" class="text-sm text-indigo-600 hover:underline">Refresh</button>
                    </div>

                    <div class="border-b mt-4">
                        <div class="flex items-center gap-3">
                            <button id="tab-inv" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Inventory (0)</button>
                            <button id="tab-sto" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Storage (0)</button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <ul id="inv-list" class="divide-y"></ul>
                        <ul id="sto-list" class="divide-y hidden"></ul>
                    </div>
                    <div id="inv-status" class="mt-3 text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
            const invList = document.getElementById('inv-list');
            const stoList = document.getElementById('sto-list');
            const status = document.getElementById('inv-status');
            const invMeta = document.getElementById('inv-meta');
            const tabInv = document.getElementById('tab-inv');
            const tabSto = document.getElementById('tab-sto');
            document.getElementById('inv-refresh').addEventListener('click', load);

            let activeTab = 'inv';
            function setTab(t) {
                activeTab = t;
                if (t === 'inv') {
                    tabInv.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    tabInv.classList.remove('text-gray-600');
                    tabSto.classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                    tabSto.classList.add('text-gray-600');
                    invList.classList.remove('hidden');
                    stoList.classList.add('hidden');
                } else {
                    tabSto.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    tabSto.classList.remove('text-gray-600');
                    tabInv.classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                    tabInv.classList.add('text-gray-600');
                    stoList.classList.remove('hidden');
                    invList.classList.add('hidden');
                }
            }
            tabInv.addEventListener('click', () => setTab('inv'));
            tabSto.addEventListener('click', () => setTab('sto'));

            function row(entry, side) {
                const li = document.createElement('li');
                li.className = 'py-3';
                const name = entry?.item?.name || '(unknown)';
                const key = entry?.item?.key;
                const qty = entry?.quantity || 0;
                const title = document.createElement('div');
                title.className = 'font-medium';
                title.textContent = name + ' • x' + qty;
                const actions = document.createElement('div');
                actions.className = 'mt-1 text-sm flex items-center gap-2 flex-wrap';
                if (side === 'inv') {
                    const consumeManyBtn = document.createElement('button');
                    consumeManyBtn.className = 'px-2 py-1 rounded border text-gray-700 hover:bg-gray-50';
                    consumeManyBtn.textContent = 'Consume';
                    consumeManyBtn.addEventListener('click', async () => {
                        const q = await promptQty('Consume how many?', 1, qty);
                        if (!q) return;
                        await act('/api/inventory/consume', { key, qty: q });
                    });
                    const toStorageBtn = document.createElement('button');
                    toStorageBtn.className = 'px-2 py-1 rounded border text-gray-700 hover:bg-gray-50';
                    toStorageBtn.textContent = 'Move to storage';
                    toStorageBtn.addEventListener('click', async () => {
                        const q = await promptQty('Move how many to storage?', 1, qty);
                        if (!q) return;
                        await act('/api/inventory/move-to-storage', { key, qty: q });
                    });
                    actions.appendChild(consumeManyBtn);
                    actions.appendChild(toStorageBtn);
                } else {
                    const toInventoryBtn = document.createElement('button');
                    toInventoryBtn.className = 'px-2 py-1 rounded border text-gray-700 hover:bg-gray-50';
                    toInventoryBtn.textContent = 'Move to inventory';
                    toInventoryBtn.addEventListener('click', async () => {
                        const q = await promptQty('Move how many to inventory?', 1, qty);
                        if (!q) return;
                        await act('/api/inventory/move-to-inventory', { key, qty: q });
                    });
                    actions.appendChild(toInventoryBtn);
                }
                li.appendChild(title);
                li.appendChild(actions);
                return li;
            }

            function ensureSwal() {
                return new Promise((resolve) => {
                    if (window.Swal) return resolve();
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                    s.onload = () => resolve();
                    document.head.appendChild(s);
                });
            }

            async function promptQty(message, min, max) {
                await ensureSwal();
                const { isConfirmed, isDenied, value } = await Swal.fire({
                    title: message,
                    input: 'number',
                    inputValue: min,
                    inputAttributes: { min: String(min), max: String(max), step: '1' },
                    showCancelButton: true,
                    showDenyButton: true,
                    denyButtonText: 'All',
                    confirmButtonText: 'Confirm',
                    preConfirm: () => {
                        const v = parseInt(Swal.getInput().value, 10) || 0;
                        if (v < min || v > max) {
                            Swal.showValidationMessage(`Enter a value between ${min} and ${max}`);
                            return false;
                        }
                        return v;
                    }
                });
                if (!isConfirmed && !isDenied) return 0;
                const n = isDenied ? max : (parseInt(value, 10) || 0);
                return Math.max(min, Math.min(max, n));
            }

            async function act(url, body) {
                try {
                    status.textContent = 'Working...';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ key: String(body.key||''), qty: Math.max(1, parseInt(body.qty,10)||1) })
                    });
                    const d = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const msg = (d && (d.message || d.error)) ? String(d.message || d.error) : 'Action failed';
                        status.textContent = msg;
                        return;
                    }
                    if (d && d.moved) {
                        status.textContent = `Moved ${d.moved}`;
                    } else {
                        status.textContent = 'Done';
                    }
                    await load();
                } catch (e) {
                    status.textContent = 'Action failed';
                }
            }

            async function load() {
                invList.innerHTML = ''; stoList.innerHTML='';
                try {
                    const res = await fetch('/api/inventory', { headers: { 'Accept':'application/json' } });
                    if (!res.ok) throw new Error();
                    const d = await res.json();
                    const inv = Array.isArray(d.inventory) ? d.inventory : [];
                    const sto = Array.isArray(d.storage) ? d.storage : [];
                    // counts
                    const invTotal = inv.reduce((s,e) => s + (parseInt(e.quantity,10)||0), 0);
                    const stoTotal = sto.reduce((s,e) => s + (parseInt(e.quantity,10)||0), 0);
                    tabInv.textContent = `Inventory (${invTotal})`;
                    tabSto.textContent = `Storage (${stoTotal})`;
                    const cap = Number(d.cap || 20000);
                    invMeta.textContent = `Inventory total: ${invTotal.toLocaleString()} • Global cap: ${cap.toLocaleString()}`;

                    if (inv.length === 0) invList.innerHTML = '<li class="py-2 text-sm text-gray-500">No items in inventory</li>';
                    else inv.forEach(e => invList.appendChild(row(e, 'inv')));
                    if (sto.length === 0) stoList.innerHTML = '<li class="py-2 text-sm text-gray-500">No items in storage</li>';
                    else sto.forEach(e => stoList.appendChild(row(e, 'sto')));
                    status.textContent = '';
                } catch (e) {
                    status.textContent = 'Unable to load inventory';
                }
            }

            setTab('inv');
            load();
        })();
    </script>
</x-app-layout>
