<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Store') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold">Items</div>
                        <button id="st-refresh" class="text-sm text-indigo-600 hover:underline">Refresh</button>
                    </div>
                    <div class="border-b mt-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <button id="tabbtn-food" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Food</button>
                            <button id="tabbtn-water" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Water</button>
                            <div class="ms-auto flex items-center gap-2 text-sm">
                                <span class="text-gray-500">Wallet:</span><span id="bal-wallet" class="font-mono">--:--:--</span>
                                <span class="text-gray-500">Bank:</span><span id="bal-bank" class="font-mono">--:--:--</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-600">Purchases are added to your Inventory. Manage and consume items on the <a href="/inventory" class="text-indigo-600 hover:underline">Inventory</a> page.</div>
                    <div id="st-status" class="mt-2 text-sm text-gray-500"></div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4" id="st-list"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
            function readCookie(name){
                const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g,'\\$1') + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : '';
            }

            function renderFlashesFromResponse(res) {
                try {
                    if (!window.flasher) return;
                    const h1 = res.headers.get('X-Flasher');
                    const h2 = res.headers.get('X-Flash');
                    const payload = h1 || h2;
                    if (payload) {
                        const data = JSON.parse(payload);
                        if (Array.isArray(data) || typeof data === 'object') {
                            window.flasher.render(data);
                        }
                    }
                } catch (_) { /* ignore */ }
            }
            const xsrf = readCookie('XSRF-TOKEN');
            const list = document.getElementById('st-list');
            const status = document.getElementById('st-status');
            const btnFood = document.getElementById('tabbtn-food');
            const btnWater = document.getElementById('tabbtn-water');
            const balWallet = document.getElementById('bal-wallet');
            const balBank = document.getElementById('bal-bank');
            document.getElementById('st-refresh').addEventListener('click', refresh);
            let itemsCache = [];
            let activeType = 'food';

            function fmtHMS(sec) {
                sec = Math.max(0, parseInt(sec || 0, 10));
                const hh = Math.floor(sec / 3600); sec %= 3600;
                const mm = Math.floor(sec / 60);
                const ss = sec % 60;
                return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
            }
            function fmtCompact(sec) {
                sec = Math.max(0, parseInt(sec || 0, 10));
                const Y = 31536000, W = 604800, D = 86400;
                const y   = Math.floor(sec / Y);   sec %= Y;
                const w   = Math.floor(sec / W);   sec %= W;
                const dd  = Math.floor(sec / D);   sec %= D;
                const hh  = Math.floor(sec / 3600); sec %= 3600;
                const mm  = Math.floor(sec / 60);
                const ss  = sec % 60;
                return [
                    String(y).padStart(3,'0'),
                    String(w).padStart(2,'0'),
                    String(dd).padStart(2,'0'),
                    String(hh).padStart(2,'0'),
                    String(mm).padStart(2,'0'),
                    String(ss).padStart(2,'0'),
                ].join(':');
            }

            async function refreshBalances() {
                try {
                    const res = await fetch('/api/store/balances', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    const d = await res.json();
                    balWallet.textContent = fmtCompact(d.wallet_seconds || 0);
                    balBank.textContent = fmtCompact(d.bank_seconds || 0);
                } catch (e) {
                    balWallet.textContent = '--:--:--';
                    balBank.textContent = '--:--:--';
                }
            }

            async function fetchStats() {
                try {
                    const r = await fetch('/api/me/stats', { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) throw new Error();
                    return await r.json();
                } catch (_) {
                    return { energy: 0, food: 0, water: 0, leisure: 0, health: 0 };
                }
            }

            function setActive(type) {
                activeType = type;
                if (type === 'food') {
                    btnFood.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    btnFood.classList.remove('text-gray-600');
                    btnWater.classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                    btnWater.classList.add('text-gray-600');
                } else {
                    btnWater.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    btnWater.classList.remove('text-gray-600');
                    btnFood.classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                    btnFood.classList.add('text-gray-600');
                }
                render();
            }
            btnFood.addEventListener('click', () => setActive('food'));
            btnWater.addEventListener('click', () => setActive('water'));

            function card(item) {
                const div = document.createElement('div');
                div.className = 'p-4 border rounded flex flex-col gap-2';
                const title = document.createElement('div');
                title.className = 'font-medium';
                title.textContent = item.name + ' • ' + item.type.toUpperCase();
                const desc = document.createElement('div');
                desc.className = 'text-sm text-gray-600';
                desc.textContent = item.description || '';
                const eff = document.createElement('div');
                eff.className = 'text-xs text-gray-500';
                const bits = [];
                if (item.restore_food) bits.push('+' + item.restore_food + '% food');
                if (item.restore_water) bits.push('+' + item.restore_water + '% water');
                if (item.restore_energy) bits.push('+' + item.restore_energy + '% energy');
                eff.textContent = (bits.join(', ') || 'No effect');
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between';
                const price = document.createElement('div');
                price.className = 'text-sm font-mono text-indigo-700';
                price.textContent = fmtHMS(item.price_seconds);
                const qty = document.createElement('div');
                qty.className = 'text-xs text-gray-500';
                qty.textContent = 'Qty: ' + (item.quantity ?? 0);
                const btn = document.createElement('button');
                btn.className = 'px-3 py-1 rounded bg-indigo-600 text-white';
                btn.textContent = 'Buy';
                if ((item.quantity ?? 0) <= 0) {
                    btn.disabled = true;
                    btn.classList.add('opacity-50','cursor-not-allowed');
                    btn.textContent = 'Out of stock';
                }
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    try {
                        const opts = await chooseOptions(item);
                        if (!opts) { btn.disabled = false; return; }
                        const res = await fetch('/api/store/buy/' + encodeURIComponent(item.key) + '?source=' + encodeURIComponent(opts.source) + '&qty=' + encodeURIComponent(opts.qty), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-XSRF-TOKEN': xsrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });
                        renderFlashesFromResponse(res);
                        if (!res.ok) throw new Error();
                        status.textContent = 'Purchased ' + item.name + ' • added to Inventory (see Inventory page to use)';
                        await refresh();
                        await refreshBalances();
                    } catch (e) {
                        status.textContent = 'Purchase failed';
                    } finally {
                        btn.disabled = false;
                    }
                });
                const left = document.createElement('div');
                left.className = 'flex items-center gap-3';
                left.appendChild(price);
                left.appendChild(qty);
                row.appendChild(left);
                row.appendChild(btn);
                div.appendChild(title);
                div.appendChild(desc);
                div.appendChild(eff);
                div.appendChild(row);
                return div;
            }

            function render() {
                list.innerHTML = '';
                const filtered = itemsCache.filter(it => (it.type === activeType));
                if (filtered.length === 0) {
                    status.textContent = 'No ' + activeType + ' items available';
                    return;
                }
                filtered.forEach(it => list.appendChild(card(it)));
                status.textContent = '';
            }

            async function refresh() {
                list.innerHTML = '';
                try {
                    const res = await fetch('/api/store/items', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    const items = await res.json();
                    itemsCache = Array.isArray(items) ? items : [];
                    render();
                } catch (e) {
                    status.textContent = 'Unable to load items';
                }
            }

            setActive('food');
            refresh();
            refreshBalances();

            // SweetAlert-based source chooser
            function ensureSwal() {
                return new Promise((resolve) => {
                    if (window.Swal) return resolve();
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                    s.onload = () => resolve();
                    document.head.appendChild(s);
                });
            }

            async function chooseOptions(item) {
                await ensureSwal();
                const stats = await fetchStats();
                const { restore_food = 0, restore_water = 0, restore_energy = 0 } = item || {};
                const { isConfirmed } = await Swal.fire({
                    title: 'Buy ' + (item?.name || 'item'),
                    html: `
                        <div class="text-left">
                          <div class="mb-2">Choose source and quantity</div>
                          <div class="flex items-center gap-4 mb-2">
                            <label class="flex items-center gap-1"><input type="radio" name="pay_src" value="wallet" checked> Wallet</label>
                            <label class="flex items-center gap-1"><input type="radio" name="pay_src" value="bank"> Bank</label>
                          </div>
                          <div class="mb-2">
                            <label>Quantity</label>
                            <input id="sw-qty" type="number" min="1" value="1" class="w-full border rounded px-2 py-1" />
                          </div>
                          <div class="text-xs text-gray-600">Food +${restore_food}% each, Water +${restore_water}% each, Energy +${restore_energy}% each</div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    focusConfirm: false,
                    preConfirm: () => {
                        const src = (document.querySelector('input[name="pay_src"]:checked')?.value) || 'wallet';
                        const qtyRaw = parseInt(document.getElementById('sw-qty')?.value || '1', 10) || 1;
                        return { src, qty: Math.max(1, qtyRaw) };
                    }
                });
                if (!isConfirmed) return null;
                const src = (document.querySelector('input[name="pay_src"]:checked')?.value) || 'wallet';
                const qty = Math.max(1, parseInt(document.getElementById('sw-qty')?.value || '1', 10) || 1);
                // Check caps
                const cap = Math.max(100, parseInt(stats.cap_percent || 100, 10));
                const nf = (stats.food || 0) + (restore_food * qty);
                const nw = (stats.water || 0) + (restore_water * qty);
                const ne = (stats.energy || 0) + (restore_energy * qty);
                const exceeds = (nf > cap) || (nw > cap) || (ne > cap);
                if (exceeds) {
                    const warn = await Swal.fire({
                        title: 'Stats cap warning',
                        icon: 'warning',
                        html: `Buying ${qty} may exceed cap (${cap}%).<br>
                               Food → ${Math.min(nf,cap)}% (from ${stats.food||0}%)<br>
                               Water → ${Math.min(nw,cap)}% (from ${stats.water||0}%)<br>
                               Energy → ${Math.min(ne,cap)}% (from ${stats.energy||0}%)<br>
                               Proceed?`,
                        showCancelButton: true,
                        confirmButtonText: 'Proceed',
                    });
                    if (!warn.isConfirmed) return null;
                }
                return { source: src, qty };
            }
        })();
    </script>
</x-app-layout>
