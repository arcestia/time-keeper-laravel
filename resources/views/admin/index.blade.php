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
                    <div class="border-b mb-4">
                        <div class="flex gap-2">
                            <button id="tabbtn-stats" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Stats</button>
                            <button id="tabbtn-transfers" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Transfers</button>
                            <button id="tabbtn-jobs" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Jobs</button>
                            <button id="tabbtn-store" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Store</button>
                        </div>

                    <div id="tab-store" class="mt-10 hidden">
                        <h3 class="text-lg font-semibold">Store Management</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 border rounded">
                                <div class="text-sm text-gray-600 mb-2">Items</div>
                                <div id="ast-status" class="text-sm text-gray-500 mb-2"></div>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left border-b">
                                            <th class="py-1">Name</th><th class="py-1">Type</th><th class="py-1">Price</th><th class="py-1">Qty</th><th class="py-1">Effects</th><th class="py-1">Restock</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ast-items"></tbody>
                                </table>
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
                    </div>

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
                    </div> <!-- /tab-stats -->

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
                    </div>

                    <div id="tab-jobs" class="mt-10 hidden">
                        <h3 class="text-lg font-semibold">Create Job</h3>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm">Key</label>
                                    <input id="job-key" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g. city_cleaner">
                                </div>
                                <div>
                                    <label class="block text-sm">Name</label>
                                    <input id="job-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="City Cleaner">
                                </div>
                                <div>
                                    <label class="block text-sm">Description</label>
                                    <textarea id="job-desc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" placeholder="Short description..."></textarea>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm">Duration (s)</label>
                                        <input id="job-duration" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="600">
                                    </div>
                                    <div>
                                        <label class="block text-sm">Reward (s)</label>
                                        <input id="job-reward" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="900">
                                    </div>
                                    <div>
                                        <label class="block text-sm">Cooldown (s)</label>
                                        <input id="job-cooldown" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="1800">
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3 items-end">
                                    <div>
                                        <label class="block text-sm">Energy Cost (%)</label>
                                        <input id="job-energy" type="number" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="10">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input id="job-active" type="checkbox" class="rounded border-gray-300" checked>
                                        <label for="job-active" class="text-sm">Active</label>
                                    </div>
                                    <div class="text-right">
                                        <button id="job-create" type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Create</button>
                                    </div>
                                </div>
                                <div id="job-status" class="text-sm text-gray-500"></div>
                            </div>
                        </div>
                    </div>

                    <script>
                        (() => {
                            // Tabs
                            const tabs = {
                                stats: document.getElementById('tab-stats'),
                                transfers: document.getElementById('tab-transfers'),
                                jobs: document.getElementById('tab-jobs'),
                            };
                            const btns = {
                                stats: document.getElementById('tabbtn-stats'),
                                transfers: document.getElementById('tabbtn-transfers'),
                                jobs: document.getElementById('tabbtn-jobs'),
                                store: document.getElementById('tabbtn-store'),
                            };
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
                            }
                            btns.stats.addEventListener('click', () => activate('stats'));
                            btns.transfers.addEventListener('click', () => activate('transfers'));
                            btns.jobs.addEventListener('click', () => activate('jobs'));
                            btns.store.addEventListener('click', () => activate('store'));
                            activate('stats');
                            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
                            function readCookie(name){
                                const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g,'\\$1') + '=([^;]*)'));
                                return m ? decodeURIComponent(m[1]) : '';
                            }
                            const xsrf = readCookie('XSRF-TOKEN');
                            const q = document.getElementById('adm-q');
                            const searchBtn = document.getElementById('adm-search');
                            const results = document.getElementById('adm-results');
                            const form = document.getElementById('adm-form');
                            const userInfo = document.getElementById('adm-user');
                            const userHead = document.getElementById('adm-user-head');
                            const status = document.getElementById('adm-status');
                            const fields = ['energy','food','water','leisure','health'];
                            const sliders = Object.fromEntries(fields.map(k => [k, document.getElementById(k)]));
                            const vals = Object.fromEntries(fields.map(k => [k, document.getElementById('v-'+k)]));
                            let currentUserId = null;

                            function setVals(data) {
                                for (const k of fields) {
                                    const v = Math.max(0, Math.min(100, parseInt(data[k] ?? 0, 10)));
                                    sliders[k].value = v;
                                    vals[k].textContent = v;
                                }
                            }

                            fields.forEach(k => sliders[k].addEventListener('input', () => {
                                vals[k].textContent = sliders[k].value;
                            }));

                            async function doSearch() {
                                results.innerHTML = '';
                                try {
                                    const res = await fetch('/admin/users?q=' + encodeURIComponent(q.value || ''), { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const list = await res.json();
                                    if (list.length === 0) {
                                        results.innerHTML = '<li class="p-3 text-sm text-gray-500">No results</li>';
                                        return;
                                    }
                                    for (const u of list) {
                                        const li = document.createElement('li');
                                        li.className = 'p-3 hover:bg-gray-50 cursor-pointer';
                                        li.textContent = u.username + ' (' + u.email + ')';
                                        li.addEventListener('click', () => loadUser(u.id));
                                        results.appendChild(li);
                                    }
                                } catch (e) {
                                    results.innerHTML = '<li class="p-3 text-sm text-rose-600">Failed to load results</li>';
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
                                    userHead.textContent = data.user.username + ' (' + data.user.email + ')';
                                    setVals(data.stats);
                                    status.textContent = '';
                                } catch (e) {
                                    status.textContent = 'Failed to load user stats';
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

                            // Admin Store
                            const astItems = document.getElementById('ast-items');
                            const astStatus = document.getElementById('ast-status');
                            async function loadAdminStore() {
                                astItems.innerHTML = '';
                                try {
                                    const res = await fetch('/admin/store/items', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error();
                                    const items = await res.json();
                                    if (!Array.isArray(items) || items.length === 0) { astStatus.textContent = 'No items'; return; }
                                    astStatus.textContent = '';
                                    for (const it of items) {
                                        const tr = document.createElement('tr');
                                        tr.innerHTML = `<td class="py-1 pr-2">${it.name}</td>
                                                        <td class="py-1 pr-2">${it.type}</td>
                                                        <td class="py-1 pr-2">${it.price_seconds}</td>
                                                        <td class="py-1 pr-2">${it.quantity}</td>
                                                        <td class="py-1 pr-2 text-xs">+${it.restore_food}% food, +${it.restore_water}% water, +${it.restore_energy}% energy</td>
                                                        <td class="py-1"><input data-id="${it.id}" class="rstk w-20 border rounded px-2 py-1" type="number" min="1" placeholder="qty"> <button data-id="${it.id}" class="rstkbtn px-2 py-1 bg-emerald-600 text-white rounded">Add</button></td>`;
                                        astItems.appendChild(tr);
                                    }
                                    document.querySelectorAll('.rstkbtn').forEach(btn => btn.addEventListener('click', async (e) => {
                                        const id = e.target.getAttribute('data-id');
                                        const qtyEl = document.querySelector(`input.rstk[data-id="${id}"]`);
                                        const qty = parseInt(qtyEl.value, 10) || 0;
                                        if (qty <= 0) { astStatus.textContent = 'Enter quantity'; return; }
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
                                        } catch (err) { astStatus.textContent = 'Failed to restock'; }
                                    }));
                                } catch (e) { astStatus.textContent = 'Failed to load items'; }
                            }

                            document.getElementById('tabbtn-store').addEventListener('click', loadAdminStore);

                            const ciStatus = document.getElementById('ci-status');
                            document.getElementById('ci-create').addEventListener('click', async () => {
                                ciStatus.textContent = 'Creating...';
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
                                } catch (e) { ciStatus.textContent = 'Failed to create item'; }
                            });
                                    if (!res.ok) throw new Error();
                                    const data = await res.json();
                                    setVals(data.stats);
                                    status.textContent = 'Saved';
                                } catch (e) {
                                    status.textContent = 'Failed to save';
                                }
                            }

                            searchBtn.addEventListener('click', doSearch);
                            document.getElementById('adm-save').addEventListener('click', save);

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
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
