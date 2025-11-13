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
                        <div class="flex items-center gap-3 flex-wrap">
                            <button id="tab-inv" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Inventory (0)</button>
                            <button id="tab-sto" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Storage (0)</button>
                            <div class="ml-auto flex items-center gap-2">
                                <input id="inv-search" type="text" placeholder="Search items" class="border rounded px-2 py-1 text-sm" />
                                <select id="inv-sort" class="border rounded px-2 py-1 text-sm">
                                    <option value="name">Name</option>
                                    <option value="qty">Quantity</option>
                                    <option value="price">Price</option>
                                    <option value="type">Type</option>
                                </select>
                            </div>
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
            const searchBox = document.getElementById('inv-search');
            const sortSel = document.getElementById('inv-sort');
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
                const sub = document.createElement('div');
                sub.className = 'mt-0.5 text-xs text-gray-600';
                const type = entry?.item?.type || '';
                const desc = entry?.item?.description || '';
                const price = entry?.item?.price_seconds != null ? Number(entry.item.price_seconds) : null;
                const parts = [];
                if (type) parts.push(type);
                if (price != null) parts.push(`${price.toLocaleString()} sec`);
                if (desc) parts.push(desc);
                sub.textContent = parts.join(' • ');
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
                    const sellBtn = document.createElement('button');
                    sellBtn.className = 'px-2 py-1 rounded border text-gray-700 hover:bg-gray-50';
                    sellBtn.textContent = 'Sell (50%)';
                    sellBtn.addEventListener('click', async () => {
                        const q = await promptQty('Sell how many?', 1, qty);
                        if (!q) return;
                        await act('/api/inventory/sell', { key, qty: q });
                    });
                    const moveAllBtn = document.createElement('button');
                    moveAllBtn.className = 'px-2 py-1 rounded border text-gray-700 hover:bg-gray-50';
                    moveAllBtn.textContent = 'Move all to storage';
                    moveAllBtn.addEventListener('click', async () => {
                        if (!qty) return;
                        await act('/api/inventory/move-to-storage', { key, qty });
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
                    actions.appendChild(sellBtn);
                    actions.appendChild(moveAllBtn);
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
                li.appendChild(sub);
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
                    if (d && d.credited_seconds) {
                        status.textContent = `Sold • Credited ${Number(d.credited_seconds||0).toLocaleString()} seconds`;
                    } else if (d && d.moved) {
                        status.textContent = `Moved ${d.moved}`;
                    } else {
                        status.textContent = 'Done';
                    }
                    await load();
                } catch (e) {
                    status.textContent = 'Action failed';
                }
            }

            let dataInv = [];
            let dataSto = [];
            let dataCap = 20000;
            function applyFilterSort(list) {
                const q = (searchBox.value || '').toLowerCase();
                let out = list;
                if (q) out = out.filter(e => {
                    const n = (e?.item?.name||'').toLowerCase();
                    const t = (e?.item?.type||'').toLowerCase();
                    const d = (e?.item?.description||'').toLowerCase();
                    return n.includes(q) || t.includes(q) || d.includes(q);
                });
                const by = sortSel.value || 'name';
                out = out.slice().sort((a,b) => {
                    if (by === 'qty') return (parseInt(b.quantity,10)||0) - (parseInt(a.quantity,10)||0);
                    if (by === 'price') return (parseInt(b?.item?.price_seconds,10)||0) - (parseInt(a?.item?.price_seconds,10)||0);
                    if (by === 'type') return String(a?.item?.type||'').localeCompare(String(b?.item?.type||''));
                    return String(a?.item?.name||'').localeCompare(String(b?.item?.name||''));
                });
                return out;
            }
            function render() {
                invList.innerHTML = '';
                stoList.innerHTML = '';
                const inv = applyFilterSort(dataInv);
                const sto = applyFilterSort(dataSto);
                const invTotal = dataInv.reduce((s,e) => s + (parseInt(e.quantity,10)||0), 0);
                const stoTotal = dataSto.reduce((s,e) => s + (parseInt(e.quantity,10)||0), 0);
                tabInv.textContent = `Inventory (${invTotal})`;
                tabSto.textContent = `Storage (${stoTotal})`;
                invMeta.textContent = `Inventory total: ${invTotal.toLocaleString()} • Global cap: ${dataCap.toLocaleString()}`;
                invMeta.classList.remove('text-gray-700','text-yellow-600','text-red-600');
                const ratio = dataCap > 0 ? invTotal / dataCap : 0;
                if (ratio >= 0.9) invMeta.classList.add('text-red-600');
                else if (ratio >= 0.75) invMeta.classList.add('text-yellow-600');
                else invMeta.classList.add('text-gray-700');
                if (inv.length === 0) invList.innerHTML = '<li class="py-2 text-sm text-gray-500">No items in inventory</li>';
                else inv.forEach(e => invList.appendChild(row(e, 'inv')));
                if (sto.length === 0) stoList.innerHTML = '<li class="py-2 text-sm text-gray-500">No items in storage</li>';
                else sto.forEach(e => stoList.appendChild(row(e, 'sto')));
            }
            async function load() {
                try {
                    const res = await fetch('/api/inventory', { headers: { 'Accept':'application/json' } });
                    if (!res.ok) throw new Error();
                    const d = await res.json();
                    dataInv = Array.isArray(d.inventory) ? d.inventory : [];
                    dataSto = Array.isArray(d.storage) ? d.storage : [];
                    dataCap = Number(d.cap || 20000);
                    status.textContent = '';
                    render();
                } catch (e) {
                    status.textContent = 'Unable to load inventory';
                }
            }
            searchBox.addEventListener('input', render);
            sortSel.addEventListener('change', render);

            setTab('inv');
            load();
        })();
    </script>
</x-app-layout>
