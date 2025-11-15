<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <x-admin-tabbar />
                    
                    <x-admin-tab-stats />
                    <x-admin-tab-transfers />
                    <x-admin-tab-tokens />
                    <x-admin-tab-jobs />
                    <x-admin-tab-store />

                    <script>
                        (() => {
                            // Tabs
                            const tabs = {
                                stats: document.getElementById('tab-stats'),
                                transfers: document.getElementById('tab-transfers'),
                                tokens: document.getElementById('tab-tokens'),
                                jobs: document.getElementById('tab-jobs'),
                                store: document.getElementById('tab-store'),
                            };
                            const btns = {
                                stats: document.getElementById('tabbtn-stats'),
                                transfers: document.getElementById('tabbtn-transfers'),
                                tokens: document.getElementById('tabbtn-tokens'),
                                jobs: document.getElementById('tabbtn-jobs'),
                                store: document.getElementById('tabbtn-store'),
                            };
                            let sbTimer = null;
                            function activate(name) {
                                for (const k of Object.keys(tabs)) {
                                    if (k === name) { tabs[k].classList.remove('hidden'); } else { tabs[k].classList.add('hidden'); }
                                }
                                for (const k of Object.keys(btns)) {
                                    if (k === name) {
                                        btns[k].classList.add('border-b-2','border-indigo-600','text-indigo-700');
                                        btns[k].classList.remove('text-gray-600');
                                    } else {
                                        btns[k].classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                                        btns[k].classList.add('text-gray-600');
                                    }
                                }
                                if (name === 'store') {
                                    if (typeof loadAdminStore === 'function') { loadAdminStore(); }
                                    if (typeof loadStoreBalance === 'function') { loadStoreBalance(); }
                                    if (!sbTimer && typeof loadStoreBalance === 'function') { sbTimer = setInterval(loadStoreBalance, 10000); }
                                } else {
                                    if (sbTimer) { clearInterval(sbTimer); sbTimer = null; }
                                }
                            }
                            btns.stats.addEventListener('click', () => activate('stats'));
                            btns.transfers.addEventListener('click', () => activate('transfers'));
                            btns.tokens.addEventListener('click', () => activate('tokens'));
                            btns.jobs.addEventListener('click', () => activate('jobs'));
                            btns.store.addEventListener('click', () => activate('store'));
                            activate('stats');
                            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
                            function readCookie(name){
                                const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g,'\\$1') + '=([^;]*)'));
                                return m ? decodeURIComponent(m[1]) : '';
                            }
                            const xsrf = readCookie('XSRF-TOKEN');
                            // Stats tab elements
                            const q = document.getElementById('adm-q');
                            const searchBtn = document.getElementById('adm-search');
                            const results = document.getElementById('adm-results');
                            const form = document.getElementById('adm-form');
                            const userInfo = document.getElementById('adm-user');
                            const userHead = document.getElementById('adm-user-head');
                            const status = document.getElementById('adm-status');
                            // Tokens tab elements
                            const tokQ = document.getElementById('tok-q');
                            const tokSearchBtn = document.getElementById('tok-search');
                            const tokResults = document.getElementById('tok-results');
                            const tokForm = document.getElementById('tok-form');
                            const tokUserInfo = document.getElementById('tok-user');
                            const tokUserHead = document.getElementById('tok-user-head');
                            const tokStatus = document.getElementById('tok-token-status');
                            const fields = ['energy','food','water','leisure','health'];
                            const sliders = Object.fromEntries(fields.map(k => [k, document.getElementById(k)]));
                            const vals = Object.fromEntries(fields.map(k => [k, document.getElementById('v-'+k)]));
                            let currentUserId = null; // stats tab user
                            let currentTokenUserId = null; // tokens tab user
                            let currentCap = 100;

                            function tierStarColor(t){ if (t>=20) return 'text-fuchsia-500'; if (t>=15) return 'text-sky-500'; if (t>=10) return 'text-amber-500'; if (t>=5) return 'text-slate-500'; if (t>=1) return 'text-orange-500'; return 'text-gray-300'; }

                            function setVals(data) {
                                for (const k of fields) {
                                    sliders[k].max = String(currentCap);
                                    const v = Math.max(0, Math.min(currentCap, parseInt(data[k] ?? 0, 10)));
                                    sliders[k].value = v;
                                    vals[k].textContent = v;
                                }
                            }

                            fields.forEach(k => sliders[k].addEventListener('input', () => {
                                vals[k].textContent = sliders[k].value;
                            }));

                            async function doSearch() {
                                if (!results) return;
                                results.innerHTML = '';
                                try {
                                    const res = await fetch('/admin/users?q=' + encodeURIComponent((q?.value || '')), { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const list = await res.json();
                                    if (list.length === 0) {
                                        results.innerHTML = '<li class="p-3 text-sm text-gray-500">No results</li>';
                                        return;
                                    }
                                    // Fetch minimal premium tier for each user to show star
                                    const withPremium = await Promise.all(list.map(async (u) => {
                                        try {
                                            const r = await fetch('/admin/users/' + u.id + '/stats', { headers: { 'Accept': 'application/json' } });
                                            if (!r.ok) throw new Error();
                                            const d = await r.json();
                                            const tier = (d && d.premium && d.premium.tier) || 0;
                                            return { ...u, tier };
                                        } catch (_) { return { ...u, tier: 0 }; }
                                    }));
                                    for (const u of withPremium) {
                                        const li = document.createElement('li');
                                        li.className = 'p-3 hover:bg-gray-50 cursor-pointer flex items-center gap-2';
                                        const star = document.createElement('i');
                                        star.className = 'fa-solid fa-star ' + tierStarColor(u.tier);
                                        const label = document.createElement('span');
                                        label.textContent = u.username;
                                        li.appendChild(label);
                                        li.appendChild(star);
                                        li.addEventListener('click', () => loadUser(u.id));
                                        results.appendChild(li);
                                    }
                                } catch (e) {
                                    results.innerHTML = '<li class="p-3 text-sm text-rose-600">Failed to load results</li>';
                                }
                            }

                            async function tokDoSearch() {
                                if (!tokResults) return;
                                tokResults.innerHTML = '';
                                try {
                                    const res = await fetch('/admin/users?q=' + encodeURIComponent((tokQ?.value || '')), { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const list = await res.json();
                                    if (list.length === 0) {
                                        tokResults.innerHTML = '<li class="p-3 text-sm text-gray-500">No results</li>';
                                        return;
                                    }
                                    const withPremium = await Promise.all(list.map(async (u) => {
                                        try {
                                            const r = await fetch('/admin/users/' + u.id + '/stats', { headers: { 'Accept': 'application/json' } });
                                            if (!r.ok) throw new Error();
                                            const d = await r.json();
                                            const tier = (d && d.premium && d.premium.tier) || 0;
                                            return { ...u, tier };
                                        } catch (_) { return { ...u, tier: 0 }; }
                                    }));
                                    for (const u of withPremium) {
                                        const li = document.createElement('li');
                                        li.className = 'p-3 hover:bg-gray-50 cursor-pointer flex items-center gap-2';
                                        const star = document.createElement('i');
                                        star.className = 'fa-solid fa-star ' + tierStarColor(u.tier);
                                        const label = document.createElement('span');
                                        label.textContent = u.username;
                                        li.appendChild(label);
                                        li.appendChild(star);
                                        li.addEventListener('click', () => tokLoadUser(u.id));
                                        tokResults.appendChild(li);
                                    }
                                } catch (e) {
                                    tokResults.innerHTML = '<li class="p-3 text-sm text-rose-600">Failed to load results</li>';
                                }
                            }

                            async function loadUser(id) {
                                try {
                                    const res = await fetch('/admin/users/' + id + '/stats', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const data = await res.json();
                                    currentUserId = data.user.id;
                                    userInfo.classList.add('hidden');
                                    form.classList.remove('hidden');
                                    currentCap = Math.max(100, parseInt((data.premium && data.premium.cap_percent) || 100, 10));
                                    const tier = (data.premium && data.premium.tier) || 0;
                                    const starCls = tierStarColor(tier);
                                    userHead.innerHTML = `${data.user.username} <i class=\"fa-solid fa-star ${starCls}\"></i>`;
                                    setVals(data.stats);
                                    status.textContent = '';
                                } catch (e) {
                                    status.textContent = 'Failed to load user stats';
                                }
                            }

                            async function tokLoadUser(id) {
                                try {
                                    const res = await fetch('/admin/users/' + id + '/stats', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const data = await res.json();
                                    currentTokenUserId = data.user.id;
                                    if (tokUserInfo && tokForm) {
                                        tokUserInfo.classList.add('hidden');
                                        tokForm.classList.remove('hidden');
                                    }
                                    if (tokUserHead) {
                                        const tier = (data.premium && data.premium.tier) || 0;
                                        const starCls = tierStarColor(tier);
                                        tokUserHead.innerHTML = `${data.user.username} <i class=\"fa-solid fa-star ${starCls}\"></i>`;
                                    }
                                    if (tokStatus) tokStatus.textContent = '';
                                } catch (e) {
                                    if (tokStatus) tokStatus.textContent = 'Failed to load user';
                                }
                            }

                            async function save() {
                                if (!currentUserId) return;
                                status.textContent = 'Saving...';
                                const payload = Object.fromEntries(fields.map(k => [k, parseInt(sliders[k].value, 10)]));
                                try {
                                    const res = await fetch('/admin/users/' + currentUserId + '/stats', {
                                        method: 'PATCH',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrf,
                                            'X-XSRF-TOKEN': xsrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                        body: JSON.stringify(payload),
                                    });
                                    if (!res.ok) throw new Error();
                                    const data = await res.json();
                                    setVals(data.stats);
                                    status.textContent = 'Saved';
                                } catch (e) {
                                    status.textContent = 'Failed to save';
                                }
                            }

                            async function grantTokens() {
                                if (!tokStatus) return;
                                if (!currentTokenUserId) {
                                    tokStatus.textContent = 'Select a user first';
                                    return;
                                }
                                const colorEl = document.getElementById('tok-token-color');
                                const qtyEl = document.getElementById('tok-token-qty');
                                if (!colorEl || !qtyEl) {
                                    tokStatus.textContent = 'Token controls not available';
                                    return;
                                }
                                const color = (colorEl.value || '').toLowerCase();
                                const qty = parseInt(qtyEl.value, 10) || 0;
                                if (!color || qty <= 0) {
                                    tokStatus.textContent = 'Choose a color and quantity > 0';
                                    return;
                                }
                                tokStatus.textContent = 'Granting tokens...';
                                try {
                                    const res = await fetch(`/admin/users/${currentTokenUserId}/tokens`, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrf,
                                            'X-XSRF-TOKEN': xsrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                        body: JSON.stringify({ color, qty }),
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) {
                                        tokStatus.textContent = (data && data.message) ? data.message : 'Failed to grant tokens';
                                        return;
                                    }
                                    tokStatus.textContent = `Granted ${data.granted_qty} ${data.color} token(s). New total: ${data.total_qty}`;
                                } catch (e) {
                                    tokStatus.textContent = 'Failed to grant tokens';
                                }
                            }

                            if (searchBtn) searchBtn.addEventListener('click', doSearch);
                            if (tokSearchBtn) tokSearchBtn.addEventListener('click', tokDoSearch);
                            const admSaveBtn = document.getElementById('adm-save');
                            if (admSaveBtn) admSaveBtn.addEventListener('click', save);
                            const grantBtn = document.getElementById('tok-token-grant');
                            if (grantBtn) {
                                grantBtn.addEventListener('click', grantTokens);
                            }

                            // Admin Store - moved after save() so it always registers
                            const astItems = document.getElementById('ast-items');
                            const astStatus = document.getElementById('ast-status');
                            const astf = {
                                all: document.getElementById('astf-all'),
                                food: document.getElementById('astf-food'),
                                water: document.getElementById('astf-water'),
                                sold: document.getElementById('astf-sold'),
                            };
                            const asts = {
                                asc: document.getElementById('asts-asc'),
                                desc: document.getElementById('asts-desc'),
                            };
                            const astPs = document.getElementById('ast-ps');
                            const astPrev = document.getElementById('ast-prev');
                            const astNext = document.getElementById('ast-next');
                            const astPageInfo = document.getElementById('ast-page-info');
                            let astCache = [];
                            let astFilter = 'all';
                            let astSort = 'desc'; // qty sort: 'asc' | 'desc'
                            let astPage = 1;
                            let astPageSize = (parseInt(astPs?.value,10)||20);
                            function setAstFilter(f){
                                astFilter = f;
                                for (const k of Object.keys(astf)){
                                    const btn = astf[k];
                                    if (!btn) continue;
                                    if (k === f){
                                        btn.classList.add('border-indigo-200','bg-indigo-50','text-indigo-700');
                                    } else {
                                        btn.classList.remove('border-indigo-200','bg-indigo-50','text-indigo-700');
                                    }
                                }
                                renderAdminStore();
                            }
                            function setAstSort(s){
                                astSort = s;
                                for (const k of Object.keys(asts)){
                                    const btn = asts[k];
                                    if (!btn) continue;
                                    if (k === s){
                                        btn.classList.add('border-indigo-200','bg-indigo-50','text-indigo-700');
                                    } else {
                                        btn.classList.remove('border-indigo-200','bg-indigo-50','text-indigo-700');
                                    }
                                }
                                renderAdminStore();
                            }
                            function fmtHMS(sec){
                                sec = Math.max(0, parseInt(sec||0,10));
                                const h = Math.floor(sec/3600);
                                const m = Math.floor((sec%3600)/60);
                                const s = sec%60;
                                return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                            }
                            function effectChips(it){
                                const chips = [];
                                const push = (val, label, color) => {
                                    if ((parseInt(val,10)||0) > 0) chips.push(`<span class=\"px-2 py-0.5 rounded-full ${color} text-xs\">+${parseInt(val,10)}% ${label}</span>`);
                                };
                                push(it.restore_food, 'food', 'bg-green-100 text-green-700');
                                push(it.restore_water, 'water', 'bg-sky-100 text-sky-700');
                                push(it.restore_energy, 'energy', 'bg-amber-100 text-amber-700');
                                return chips.length ? chips.join(' ') : '<span class="text-xs text-gray-400">no effect</span>';
                            }
                            function renderAdminStore(){
                                astItems.innerHTML = '';
                                let list = Array.isArray(astCache) ? astCache.slice() : [];
                                if (astFilter === 'food') list = list.filter(i => i.type === 'food');
                                if (astFilter === 'water') list = list.filter(i => i.type === 'water');
                                if (astFilter === 'sold') list = list.filter(i => (parseInt(i.quantity,10)||0) === 0);
                                list.sort((a,b) => {
                                    const qa = parseInt(a.quantity,10)||0;
                                    const qb = parseInt(b.quantity,10)||0;
                                    return astSort === 'asc' ? qa - qb : qb - qa;
                                });
                                if (list.length === 0){
                                    astStatus.textContent = 'No items';
                                    if (astPageInfo){ astPageInfo.textContent = 'Page 0 of 0 • 0 items'; }
                                    if (astPrev) astPrev.disabled = true;
                                    if (astNext) astNext.disabled = true;
                                    return;
                                }
                                astStatus.textContent = '';
                                const total = list.length;
                                const pages = Math.max(1, Math.ceil(total / astPageSize));
                                astPage = Math.max(1, Math.min(astPage, pages));
                                const start = (astPage - 1) * astPageSize;
                                const end = Math.min(total, start + astPageSize);
                                const pageItems = list.slice(start, end);
                                if (astPageInfo){ astPageInfo.textContent = `Page ${astPage} of ${pages} • ${total} items`; }
                                if (astPrev){ astPrev.disabled = astPage <= 1; astPrev.classList.toggle('opacity-50', astPrev.disabled); }
                                if (astNext){ astNext.disabled = astPage >= pages; astNext.classList.toggle('opacity-50', astNext.disabled); }
                                for (const it of pageItems) {
                                        const tr = document.createElement('tr');
                                        tr.className = 'border-b hover:bg-gray-50';
                                        tr.innerHTML = `<td class=\"py-2 pr-3 align-top\"><div class=\"font-medium text-gray-800\">${it.name}</div><div class=\"text-xs text-gray-500\">${it.description||''}</div></td>
                                                        <td class=\"py-2 pr-3 align-top\"><span class=\"px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs\">${it.type}</span></td>
                                                        <td class=\"py-2 pr-3 align-top\"><code class=\"text-indigo-700\">${fmtHMS(it.price_seconds)}</code></td>
                                                        <td class=\"py-2 pr-3 align-top\"><span class=\"px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs\">${it.quantity}</span></td>
                                                        <td class=\"py-2 pr-3 align-top space-x-1\">${effectChips(it)}</td>
                                                        <td class=\"py-2 align-top\">
                                                            <div class=\"flex items-center gap-2\">
                                                                <input data-id=\"${it.id}\" data-price=\"${it.price_seconds}\" data-name=\"${it.name.replace(/"/g,'&quot;')}\" class=\"rstk w-24 border rounded px-2 py-1 text-sm\" type=\"number\" min=\"1\" placeholder=\"qty\">
                                                                <button data-id=\"${it.id}\" data-price=\"${it.price_seconds}\" data-name=\"${it.name.replace(/"/g,'&quot;')}\" class=\"rstkbtn px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-sm\">Add</button>
                                                            </div>
                                                        </td>`;
                                        astItems.appendChild(tr);
                                    }
                                    document.querySelectorAll('.rstkbtn').forEach(btn => btn.addEventListener('click', async (e) => {
                                        const id = e.target.getAttribute('data-id');
                                        const price = parseInt(e.target.getAttribute('data-price'),10)||0;
                                        const name = e.target.getAttribute('data-name')||'Item';
                                        const qtyEl = document.querySelector(`input.rstk[data-id=\"${id}\"]`);
                                        const qty = parseInt(qtyEl.value, 10) || 0;
                                        if (qty <= 0) { astStatus.textContent = 'Enter quantity'; return; }
                                        const cost = Math.floor((price * qty) / 2);
                                        let proceed = true;
                                        if (window.Swal) {
                                            const { isConfirmed } = await Swal.fire({
                                                title: 'Confirm Restock',
                                                html: `<div class=\"text-left space-y-1\">`
                                                    + `<div><strong>Item:</strong> ${name}</div>`
                                                    + `<div><strong>Quantity:</strong> ${qty}</div>`
                                                    + `<div><strong>Price per unit:</strong> ${fmtHMS(price)}</div>`
                                                    + `<div><strong>Cost (50%):</strong> ${fmtHMS(cost)}</div>`
                                                    + `</div>`,
                                                icon: 'question',
                                                showCancelButton: true,
                                                confirmButtonText: 'Proceed',
                                            });
                                            proceed = isConfirmed;
                                        } else {
                                            proceed = window.confirm(`Restock ${name}\nQty: ${qty}\nPrice: ${fmtHMS(price)} each\nCost to Store Balance (50%): ${fmtHMS(cost)}\n\nProceed?`);
                                        }
                                        if (!proceed) return;
                                        astStatus.textContent = 'Restocking...';
                                        try {
                                            const res = await fetch(`/admin/store/items/${id}/restock`, {
                                                method: 'POST',
                                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
                                                credentials: 'same-origin',
                                                body: JSON.stringify({ quantity: qty })
                                            });
                                            const data = await res.json();
                                            if (!res.ok || !data.ok) throw new Error();
                                            astStatus.textContent = 'Restocked';
                                            await loadAdminStore();
                                            if (typeof loadStoreBalance === 'function') { loadStoreBalance(); }
                                        } catch (err) { astStatus.textContent = 'Failed to restock'; }
                                    }));
                            }

                            async function loadAdminStore() {
                                try {
                                    const res = await fetch('/admin/store/items', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    astCache = await res.json();
                                    astPage = 1;
                                    renderAdminStore();
                                } catch (e) { astStatus.textContent = 'Failed to load items'; }
                            }

                            // Store Balance
                            const sbValue = document.getElementById('sb-value');
                            const sbStatus = document.getElementById('sb-status');
                            async function loadStoreBalance(){
                                try {
                                    const res = await fetch('/admin/store/balance', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const data = await res.json();
                                    sbValue.textContent = fmtHMS(parseInt(data.seconds,10)||0);
                                } catch(e){ sbValue.textContent = '--:--:--'; }
                            }
                            document.getElementById('sb-refresh').addEventListener('click', loadStoreBalance);
                            document.getElementById('sb-transfer').addEventListener('click', async () => {
                                const mode = (document.getElementById('sb-amount').value || 'all').trim() || 'all';
                                sbStatus.textContent = 'Transferring...';
                                try {
                                    const res = await fetch('/admin/store/balance/transfer', {
                                        method: 'POST',
                                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
                                        credentials: 'same-origin',
                                        body: JSON.stringify({ mode })
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message || 'Failed');
                                    sbStatus.textContent = `Moved ${data.moved_seconds} seconds to Reserve`;
                                    await loadStoreBalance();
                                } catch (e) { sbStatus.textContent = 'Transfer failed'; }
                            });

                            document.getElementById('tabbtn-store').addEventListener('click', loadAdminStore);
                            astf.all.addEventListener('click', () => setAstFilter('all'));
                            astf.food.addEventListener('click', () => setAstFilter('food'));
                            astf.water.addEventListener('click', () => setAstFilter('water'));
                            astf.sold.addEventListener('click', () => setAstFilter('sold'));
                            asts.asc.addEventListener('click', () => setAstSort('asc'));
                            asts.desc.addEventListener('click', () => setAstSort('desc'));
                            if (astPs) astPs.addEventListener('change', () => { astPageSize = parseInt(astPs.value,10)||20; astPage = 1; renderAdminStore(); });
                            if (astPrev) astPrev.addEventListener('click', () => { if (astPage > 1) { astPage--; renderAdminStore(); } });
                            if (astNext) astNext.addEventListener('click', () => { astPage++; renderAdminStore(); });

                            const ciStatus = document.getElementById('ci-status');
                            document.getElementById('ci-create').addEventListener('click', async () => {
                                const payload = {
                                    key: document.getElementById('ci-key').value.trim(),
                                    name: document.getElementById('ci-name').value.trim(),
                                    type: document.getElementById('ci-type').value,
                                    description: document.getElementById('ci-desc').value.trim(),
                                    price_seconds: parseInt(document.getElementById('ci-price').value, 10) || 0,
                                    quantity: parseInt(document.getElementById('ci-qty').value, 10) || 0,
                                    restore_food: parseInt(document.getElementById('ci-rf').value, 10) || 0,
                                    restore_water: parseInt(document.getElementById('ci-rw').value, 10) || 0,
                                    restore_energy: parseInt(document.getElementById('ci-re').value, 10) || 0,
                                    is_active: !!document.getElementById('ci-active').checked,
                                };
                                const price = payload.price_seconds;
                                const qty = payload.quantity;
                                const cost = Math.floor((price * qty) / 2);
                                let proceed = true;
                                if (window.Swal) {
                                    const { isConfirmed } = await Swal.fire({
                                        title: 'Confirm Create Item',
                                        html: `<div class=\"text-left space-y-1\">`
                                            + `<div><strong>Name:</strong> ${payload.name || '(unnamed)'} </div>`
                                            + `<div><strong>Type:</strong> ${payload.type}</div>`
                                            + `<div><strong>Quantity:</strong> ${qty}</div>`
                                            + `<div><strong>Price per unit:</strong> ${fmtHMS(price)}</div>`
                                            + `<div><strong>Cost (50%):</strong> ${fmtHMS(cost)}</div>`
                                            + `</div>`,
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonText: 'Create',
                                    });
                                    proceed = isConfirmed;
                                } else {
                                    proceed = window.confirm(`Create item ${payload.name || ''}\nQty: ${qty}\nPrice: ${fmtHMS(price)} each\nCost to Store Balance (50%): ${fmtHMS(cost)}\n\nProceed?`);
                                }
                                if (!proceed) return;
                                ciStatus.textContent = 'Creating...';
                                try {
                                    const res = await fetch('/admin/store/items', {
                                        method: 'POST',
                                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
                                        credentials: 'same-origin',
                                        body: JSON.stringify(payload)
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error();
                                    ciStatus.textContent = 'Item created';
                                    await loadAdminStore();
                                    if (typeof loadStoreBalance === 'function') { loadStoreBalance(); }
                                } catch (e) { ciStatus.textContent = 'Failed to create item'; }
                            });

                            // Create Job
                            const jobCreateBtn = document.getElementById('job-create');
                            const jobStatus = document.getElementById('job-status');
                            jobCreateBtn.addEventListener('click', async () => {
                                jobStatus.textContent = 'Creating...';
                                const payload = {
                                    key: document.getElementById('job-key').value.trim(),
                                    name: document.getElementById('job-name').value.trim(),
                                    description: document.getElementById('job-desc').value.trim(),
                                    duration_seconds: parseInt(document.getElementById('job-duration').value, 10) || 0,
                                    reward_seconds: parseInt(document.getElementById('job-reward').value, 10) || 0,
                                    cooldown_seconds: parseInt(document.getElementById('job-cooldown').value, 10) || 0,
                                    energy_cost: parseInt(document.getElementById('job-energy').value, 10) || 0,
                                    is_active: !!document.getElementById('job-active').checked,
                                };
                                try {
                                    const res = await fetch('/admin/jobs', {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrf,
                                            'X-XSRF-TOKEN': xsrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                        body: JSON.stringify(payload),
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message || 'Failed');
                                    jobStatus.textContent = 'Job created: ' + data.job.name;
                                } catch (e) {
                                    jobStatus.textContent = 'Failed to create job';
                                }
                            });

                            // Reserve transfers
                            async function postJSON(url, payload) {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-XSRF-TOKEN': xsrf,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify(payload),
                                });
                                return res.json();
                            }

                            const trStatus = document.getElementById('tr-status');
                            document.getElementById('tr-dep-btn').addEventListener('click', async () => {
                                const username = document.getElementById('tr-dep-username').value.trim();
                                const amount = document.getElementById('tr-dep-amount').value.trim();
                                if (!username || !amount) { trStatus.textContent = 'Enter username and amount'; return; }
                                trStatus.textContent = 'Processing deposit...';
                                try {
                                    const r = await postJSON('/keeper/admin/deposit', { username, amount });
                                    trStatus.textContent = r.status === 'ok' ? 'Deposit complete' : (r.message || 'Deposit failed');
                                } catch (e) { trStatus.textContent = 'Deposit failed'; }
                            });

                            document.getElementById('tr-wd-btn').addEventListener('click', async () => {
                                const username = document.getElementById('tr-wd-username').value.trim();
                                const amount = document.getElementById('tr-wd-amount').value.trim();
                                if (!username || !amount) { trStatus.textContent = 'Enter username and amount'; return; }
                                trStatus.textContent = 'Processing withdrawal...';
                                try {
                                    const r = await postJSON('/keeper/admin/withdraw', { username, amount });
                                    trStatus.textContent = r.status === 'ok' ? 'Withdrawal complete' : (r.message || 'Withdrawal failed');
                                } catch (e) { trStatus.textContent = 'Withdrawal failed'; }
                            });

                            document.getElementById('tr-dist-btn').addEventListener('click', async () => {
                                const amount = document.getElementById('tr-dist-amount').value.trim();
                                if (!amount) { trStatus.textContent = 'Enter an amount'; return; }
                                trStatus.textContent = 'Distributing...';
                                try {
                                    const r = await postJSON('/keeper/admin/distribute', { amount });
                                    if (r && r.status === 'ok') {
                                        trStatus.textContent = `Distributed ${r.per_user} seconds per user. Remaining reserve: ${r.remaining_reserve}`;
                                    } else {
                                        trStatus.textContent = (r && r.message) ? r.message : 'Distribution failed';
                                    }
                                } catch (e) { trStatus.textContent = 'Distribution failed'; }
                            });

                            // Reserve -> Store
                            const trRsStatus = document.getElementById('tr-rs-status');
                            document.getElementById('tr-rs-btn').addEventListener('click', async () => {
                                const amount = document.getElementById('tr-rs-amount').value.trim();
                                if (!amount) { trRsStatus.textContent = 'Enter an amount'; return; }
                                trRsStatus.textContent = 'Transferring...';
                                try {
                                    const res = await fetch('/admin/store/balance/from-reserve', {
                                        method: 'POST',
                                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
                                        credentials: 'same-origin',
                                        body: JSON.stringify({ amount })
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message || 'Failed');
                                    trRsStatus.textContent = `Moved ${data.moved_seconds} seconds to Store`;
                                    // Also refresh the Store Balance card if present
                                    if (typeof loadStoreBalance === 'function') { loadStoreBalance(); }
                                } catch (e) { trRsStatus.textContent = 'Transfer failed'; }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
