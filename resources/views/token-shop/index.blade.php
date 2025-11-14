<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Token Shop</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-lg font-semibold">Spend Time Tokens</div>
                        <div id="ts-balances" class="text-sm text-gray-600"></div>
                    </div>

                    <div class="border-b mb-3">
                        <div class="flex items-center gap-3">
                            <button id="tab-shop" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Shop</button>
                            <button id="tab-boosts" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Boosts</button>
                            <button id="tab-grants" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Slot Grants</button>
                        </div>
                    </div>

                    <div id="panel-shop" class="space-y-8">
                        <div>
                            <h3 class="text-md font-semibold mb-2">Expedition Slots</h3>
                            <p class="text-xs text-gray-500 mb-2">Black = +1 permanent slot. Yellow = +1 slot &amp; extend duration by 1 year. Max 250 extra slots.</p>
                            <div class="flex flex-wrap gap-3">
                                <button id="btn-slot-black" class="px-3 py-2 rounded bg-gray-900 text-white text-sm">Buy 1 Permanent Slot (1 Black)</button>
                                <button id="btn-slot-yellow" class="px-3 py-2 rounded bg-amber-500 text-white text-sm">Buy 1 Temp Slot (1 Yellow)</button>
                            </div>
                            <div id="ts-slots-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>

                        <div>
                            <h3 class="text-md font-semibold mb-2">XP Boosts</h3>
                            <p class="text-xs text-gray-500 mb-2">Each token adds +2% XP multiplier for a duration based on its time (Red 1w, Blue 1m, Green 1y, Yellow 10y, Black 100y).</p>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <select id="xp-color" class="border rounded px-2 py-1">
                                    <option value="red">Red</option>
                                    <option value="blue">Blue</option>
                                    <option value="green">Green</option>
                                    <option value="yellow">Yellow</option>
                                    <option value="black">Black</option>
                                </select>
                                <input id="xp-qty" type="number" min="1" value="1" class="w-20 border rounded px-2 py-1" />
                                <button id="btn-buy-xp" class="px-3 py-2 rounded bg-indigo-600 text-white">Buy XP</button>
                                <span id="xp-preview" class="text-xs text-gray-500"></span>
                            </div>
                            <div id="ts-xp-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>

                        <div>
                            <h3 class="text-md font-semibold mb-2">Item Chests</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-red-600 mb-1">Red Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Red • Reward: 200–500 of 1 random item.</div>
                                    <button data-color="red" class="btn-chest px-3 py-1 rounded bg-red-600 text-white">Open Red Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-blue-600 mb-1">Blue Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Blue • Reward: 1000–5000 of 1 random item.</div>
                                    <button data-color="blue" class="btn-chest px-3 py-1 rounded bg-blue-600 text-white">Open Blue Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-green-600 mb-1">Green Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Green • Reward: 50,000–150,000 of 1 random item.</div>
                                    <button data-color="green" class="btn-chest px-3 py-1 rounded bg-green-600 text-white">Open Green Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-amber-500 mb-1">Yellow Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Yellow • Reward: 100k of every store item.</div>
                                    <button data-color="yellow" class="btn-chest px-3 py-1 rounded bg-amber-500 text-white">Open Yellow Chest</button>
                                </div>
                                <div class="border rounded p-3 md:col-span-2">
                                    <div class="font-semibold text-gray-900 mb-1">Black Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Black • Reward: 2.5m of every store item.</div>
                                    <button data-color="black" class="btn-chest px-3 py-1 rounded bg-gray-900 text-white">Open Black Chest</button>
                                </div>
                            </div>
                            <div id="ts-chest-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>
                    </div>

                    <div id="panel-boosts" class="hidden">
                        <h3 class="text-md font-semibold mb-2">XP Boost History</h3>
                        <div id="boosts-status" class="text-sm text-gray-500 mb-2">Loading...</div>
                        <ul id="boosts-list" class="space-y-3"></ul>
                    </div>
                    <div id="panel-grants" class="hidden">
                        <h3 class="text-md font-semibold mb-2">Expedition Slot Upgrades</h3>
                        <div id="grants-status" class="text-sm text-gray-500 mb-2">Loading...</div>
                        <ul id="grants-list" class="space-y-3"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            // DOM references
            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
            const tabShop = document.getElementById('tab-shop');
            const tabBoosts = document.getElementById('tab-boosts');
            const tabGrants = document.getElementById('tab-grants');
            const panelShop = document.getElementById('panel-shop');
            const panelBoosts = document.getElementById('panel-boosts');
            const panelGrants = document.getElementById('panel-grants');
            const boostsList = document.getElementById('boosts-list');
            const boostsStatus = document.getElementById('boosts-status');
            const grantsList = document.getElementById('grants-list');
            const grantsStatus = document.getElementById('grants-status');

            // Helpers
            function fmtDate(s){ try { return new Date(s).toLocaleString(); } catch (e) { return String(s||''); } }
            function fmtColon(sec){
                sec = Math.max(0, parseInt(sec||0,10));
                const Y=31536000, W=604800, D=86400;
                const y = Math.floor(sec / Y); sec %= Y;
                const w = Math.floor(sec / W); sec %= W;
                const d = Math.floor(sec / D); sec %= D;
                const h = Math.floor(sec / 3600); sec %= 3600;
                const m = Math.floor(sec / 60);
                const s = sec % 60;
                const pad = (n, p)=>String(n).padStart(p,'0');
                return `${pad(y,3)}:${pad(w,2)}:${pad(d,2)}:${pad(h,2)}:${pad(m,2)}:${pad(s,2)}`;
            }
            async function fetchJson(url){
                const res = await fetch(url, { headers:{ 'Accept':'application/json' } });
                if (!res.ok) throw new Error('Request failed');
                return res.json();
            }

            // Balances
            async function refreshBalances(){
                try {
                    const js = await fetchJson('/api/token-shop/balances');
                    const b = js && js.balances ? js.balances : {};
                    const el = document.getElementById('ts-balances');
                    el.textContent = 'Red ' + (b.red||0) + ' • Blue ' + (b.blue||0) + ' • Green ' + (b.green||0) + ' • Yellow ' + (b.yellow||0) + ' • Black ' + (b.black||0);
                } catch (e) {
                    const el = document.getElementById('ts-balances');
                    el.textContent = 'Unable to load balances';
                }
            }

            // Renderers
            function renderBoostItem(b){
                const li = document.createElement('li');
                li.className = 'p-3 border rounded';
                const pct = (Number(b.bonus_percent||0) * 100).toFixed(1);
                const active = !!b.active;
                const created = fmtDate(b.created_at);
                const expires = b.expires_at ? fmtDate(b.expires_at) : '-';
                const total = Math.max(1, parseInt(b.total_seconds||0,10));
                const remaining = Math.max(0, parseInt(b.remaining_seconds||0,10));
                const elapsed = Math.max(0, total - remaining);
                const prog = Math.max(0, Math.min(100, Math.round((elapsed/total)*100)));
                li.innerHTML = `
                    <div class="flex items-center justify-between text-sm">
                        <div class="font-medium">+${pct}% XP</div>
                        <div class="${active ? 'text-emerald-700' : 'text-gray-500'}">${active ? 'Active' : 'Expired'}</div>
                    </div>
                    <div class="text-xs text-gray-600 mt-0.5">From ${created} to ${expires}</div>
                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div class="boost-bar h-2 bg-gradient-to-r from-emerald-500 to-lime-500" style="width:${prog}%;"></div>
                    </div>
                    <div class="mt-1 text-[11px] text-gray-600 flex justify-between"><span>Elapsed: <span class="boost-elapsed">${fmtColon(elapsed)}</span></span><span>Remaining: <span class="boost-remaining">${fmtColon(remaining)}</span></span></div>
                `;
                li.dataset.total = String(total);
                li.dataset.expiresAt = b.expires_at ? String(b.expires_at) : '';
                return li;
            }

            function renderGrantItem(g){
                const li = document.createElement('li');
                li.className = 'p-3 border rounded';
                const type = String(g.type||'temp');
                const slots = parseInt(g.slots||0,10);
                const active = !!g.active;
                const created = fmtDate(g.created_at);
                const expires = g.expires_at ? fmtDate(g.expires_at) : (type==='permanent' ? '—' : '-');
                const total = Math.max(1, parseInt(g.total_seconds||0,10)||1);
                const remaining = Math.max(0, parseInt(g.remaining_seconds||0,10)||0);
                const elapsed = Math.max(0, total - remaining);
                const prog = g.progress==null ? 0 : Math.max(0, Math.min(100, Math.round((elapsed/total)*100)));
                li.innerHTML = `
                    <div class="flex items-center justify-between text-sm">
                        <div class="font-medium">${type==='permanent' ? `Permanent +${slots}` : `Temp +${slots}`}</div>
                        <div class="${active ? 'text-emerald-700' : 'text-gray-500'}">${active ? 'Active' : 'Expired'}</div>
                    </div>
                    <div class="text-xs text-gray-600 mt-0.5">Started ${created}${type==='temp' ? ` • Expires ${expires}` : ''}</div>
                    ${type==='temp' ? `
                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div class="grant-bar h-2 bg-gradient-to-r from-indigo-500 to-purple-500" style="width:${prog}%;"></div>
                    </div>
                    <div class="mt-1 text-[11px] text-gray-600 flex justify-between"><span>Elapsed: <span class="grant-elapsed">${fmtColon(elapsed)}</span></span><span>Remaining: <span class="grant-remaining">${fmtColon(remaining)}</span></span></div>
                    ` : ''}
                `;
                li.dataset.type = type;
                li.dataset.total = String(total);
                li.dataset.expiresAt = g.expires_at ? String(g.expires_at) : '';
                return li;
            }

            // Loaders
            async function loadBoosts(){
                boostsStatus.textContent = 'Loading...'; boostsList.innerHTML = '';
                try {
                    const js = await fetchJson('/api/token-shop/boosts');
                    const arr = Array.isArray(js.boosts) ? js.boosts : [];
                    if (arr.length === 0){ boostsStatus.textContent = 'No boosts yet'; return; }
                    boostsStatus.textContent = '';
                    for (const b of arr) boostsList.appendChild(renderBoostItem(b));
                } catch (e) { boostsStatus.textContent = 'Unable to load boosts'; }
            }

            async function loadGrants(){
                grantsStatus.textContent = 'Loading...'; grantsList.innerHTML = '';
                try {
                    const js = await fetchJson('/api/token-shop/slot-grants');
                    const arr = Array.isArray(js.grants) ? js.grants : [];
                    if (arr.length === 0){ grantsStatus.textContent = 'No slot upgrades yet'; return; }
                    grantsStatus.textContent = '';
                    for (const g of arr) grantsList.appendChild(renderGrantItem(g));
                } catch (e) { grantsStatus.textContent = 'Unable to load slot upgrades'; }
            }

            // Tickers
            function tickBoosts(){
                const now = Date.now();
                boostsList.querySelectorAll('li').forEach(li => {
                    const total = Math.max(1, parseInt(li.dataset.total||'0',10));
                    const expMs = li.dataset.expiresAt ? new Date(li.dataset.expiresAt).getTime() : 0;
                    if (!expMs) return;
                    const remaining = Math.max(0, Math.floor((expMs - now)/1000));
                    const elapsed = Math.max(0, total - remaining);
                    const prog = Math.max(0, Math.min(100, Math.round((elapsed/total)*100)));
                    const bar = li.querySelector('.boost-bar');
                    const elEl = li.querySelector('.boost-elapsed');
                    const rmEl = li.querySelector('.boost-remaining');
                    if (bar) bar.style.width = prog + '%';
                    if (elEl) elEl.textContent = fmtColon(elapsed);
                    if (rmEl) rmEl.textContent = fmtColon(remaining);
                });
            }

            function tickGrants(){
                const now = Date.now();
                grantsList.querySelectorAll('li').forEach(li => {
                    if (li.dataset.type !== 'temp') return;
                    const total = Math.max(1, parseInt(li.dataset.total||'0',10));
                    const expMs = li.dataset.expiresAt ? new Date(li.dataset.expiresAt).getTime() : 0;
                    if (!expMs) return;
                    const remaining = Math.max(0, Math.floor((expMs - now)/1000));
                    const elapsed = Math.max(0, total - remaining);
                    const prog = Math.max(0, Math.min(100, Math.round((elapsed/total)*100)));
                    const bar = li.querySelector('.grant-bar');
                    const elEl = li.querySelector('.grant-elapsed');
                    const rmEl = li.querySelector('.grant-remaining');
                    if (bar) bar.style.width = prog + '%';
                    if (elEl) elEl.textContent = fmtColon(elapsed);
                    if (rmEl) rmEl.textContent = fmtColon(remaining);
                });
            }

            // Tab handling
            function setTab(which){
                function setActive(btn, active){
                    if (!btn) return;
                    if (active) { btn.classList.add('border-b-2','border-indigo-600','text-indigo-700'); btn.classList.remove('text-gray-600'); }
                    else { btn.classList.remove('border-b-2','border-indigo-600','text-indigo-700'); btn.classList.add('text-gray-600'); }
                }
                setActive(tabShop, which==='shop');
                setActive(tabBoosts, which==='boosts');
                setActive(tabGrants, which==='grants');
                if (panelShop) panelShop.classList.toggle('hidden', which!=='shop');
                if (panelBoosts) panelBoosts.classList.toggle('hidden', which!=='boosts');
                if (panelGrants) panelGrants.classList.toggle('hidden', which!=='grants');
                if (which==='boosts') loadBoosts();
                if (which==='grants') loadGrants();
            }

            // Actions
            async function buySlot(color){
                const statusEl = document.getElementById('ts-slots-status'); statusEl.textContent = 'Processing...';
                try {
                    const res = await fetch('/api/token-shop/buy-slot', { method:'POST', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ color }) });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) throw new Error(js.message || 'Purchase failed');
                    statusEl.textContent = 'Extra slots: permanent ' + (js.permanent_slots||0) + ', temp ' + (js.temp_slots||0);
                    refreshBalances();
                } catch (err) { statusEl.textContent = (err && err.message) ? err.message : 'Purchase failed'; }
            }

            async function buyXp(){
                const statusEl = document.getElementById('ts-xp-status'); statusEl.textContent = 'Processing...';
                const color = document.getElementById('xp-color').value;
                const qty = Math.max(1, parseInt(document.getElementById('xp-qty').value, 10) || 1);
                try {
                    const res = await fetch('/api/token-shop/buy-xp', { method:'POST', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ color, qty }) });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) throw new Error(js.message || 'Purchase failed');
                    const added = (js.added_percent || 0) * 100;
                    const total = (js.bonus_percent || 0) * 100;
                    const until = js.expires_at ? new Date(js.expires_at).toLocaleString() : 'unknown';
                    statusEl.textContent = 'XP boost +' + added.toFixed(1) + '% added (total +' + total.toFixed(1) + '% XP) until ' + until;
                    refreshBalances();
                } catch (err) { statusEl.textContent = (err && err.message) ? err.message : 'Purchase failed'; }
            }

            async function openChest(color){
                const statusEl = document.getElementById('ts-chest-status'); statusEl.textContent = 'Opening chest...';
                try {
                    const res = await fetch('/api/token-shop/open-chest', { method:'POST', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ color }) });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) throw new Error(js.message || 'Open chest failed');
                    const loot = Array.isArray(js.loot) ? js.loot : [];
                    if (loot.length === 0) statusEl.textContent = 'No loot.';
                    else statusEl.textContent = 'Loot: ' + loot.map(x => (x.name || x.key || 'Item') + ' x' + (x.qty || 0)).join(', ');
                    refreshBalances();
                } catch (err) { statusEl.textContent = (err && err.message) ? err.message : 'Open chest failed'; }
            }

            // Wire up events
            const slotBlackBtn = document.getElementById('btn-slot-black'); if (slotBlackBtn) slotBlackBtn.addEventListener('click', ()=>buySlot('black'));
            const slotYellowBtn = document.getElementById('btn-slot-yellow'); if (slotYellowBtn) slotYellowBtn.addEventListener('click', ()=>buySlot('yellow'));
            const buyXpBtn = document.getElementById('btn-buy-xp'); if (buyXpBtn) buyXpBtn.addEventListener('click', ()=>buyXp());
            document.querySelectorAll('.btn-chest').forEach(btn => { btn.addEventListener('click', ()=>openChest(btn.getAttribute('data-color'))); });
            if (tabShop) tabShop.addEventListener('click', ()=>setTab('shop'));
            if (tabBoosts) tabBoosts.addEventListener('click', ()=>setTab('boosts'));
            if (tabGrants) tabGrants.addEventListener('click', ()=>setTab('grants'));

            // Init
            refreshBalances();
            setTab('shop');
            setInterval(function(){ tickBoosts(); tickGrants(); }, 1000);
        })();
    </script>
</x-app-layout>
