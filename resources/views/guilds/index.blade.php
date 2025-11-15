<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Guilds') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="border-b mb-3">
                        <div class="flex gap-2 text-sm">
                            <button id="gtab-my" class="px-3 py-2 border-b-2 border-indigo-600 text-indigo-700 font-medium">My Guild</button>
                            <button id="gtab-create" class="px-3 py-2 text-gray-600 hover:text-gray-800 font-medium">Create Guild</button>
                            <button id="gtab-browse" class="px-3 py-2 text-gray-600 hover:text-gray-800 font-medium">Browse Guilds</button>
                        </div>
                    </div>

                    <div id="gpanel-my" class="space-y-3">
                        <h3 class="text-lg font-semibold mb-2">My Guild</h3>
                        <div id="guild-status" class="text-sm text-gray-600 mb-2">Loading...</div>
                        <div id="guild-none" class="hidden text-sm text-gray-500">You are not in a guild.</div>
                        <div id="guild-view" class="hidden space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div id="guild-name" class="font-semibold text-gray-800"></div>
                                    <div id="guild-desc" class="text-sm text-gray-600"></div>
                                    <div id="guild-meta" class="text-xs text-gray-500"></div>
                                    <div id="guild-level" class="text-xs text-gray-500 mt-1"></div>
                                    <div id="guild-visibility-row" class="mt-1 text-xs text-gray-600 hidden">
                                        <label class="inline-flex items-center gap-2">
                                            <span>Visibility:</span>
                                            <select id="guild-visibility" class="border-gray-300 rounded-md text-xs">
                                                <option value="0">Open</option>
                                                <option value="1">Private</option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                                <div class="space-x-2">
                                    <button id="guild-leave" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-xs rounded">Leave</button>
                                    <button id="guild-disband" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-xs text-white rounded hidden">Disband</button>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 mb-1">Members</div>
                                <ul id="guild-members" class="border rounded divide-y text-sm"></ul>
                            </div>
                            <div class="mt-3">
                                <div class="text-sm font-medium text-gray-700 mb-1">Member Contribution Leaderboard</div>
                                <ul id="guild-contrib" class="border rounded divide-y text-xs"></ul>
                            </div>
                            <div id="guild-donate" class="mt-3 space-y-2">
                                <div class="text-sm font-medium text-gray-700">Donate Tokens to Guild</div>
                                <div class="flex flex-wrap items-end gap-2 text-xs">
                                    <label class="flex items-center gap-1">
                                        <span>Token:</span>
                                        <select id="guild-donate-color" class="border-gray-300 rounded-md">
                                            <option value="red">Red (10 XP)</option>
                                            <option value="blue">Blue (40 XP)</option>
                                            <option value="green">Green (520 XP)</option>
                                            <option value="yellow">Yellow (5,200 XP)</option>
                                            <option value="black">Black (52,000 XP)</option>
                                        </select>
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <span>Qty:</span>
                                        <input id="guild-donate-qty" type="number" min="1" class="w-24 border-gray-300 rounded-md" value="1">
                                    </label>
                                    <button id="guild-donate-btn" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded">Donate</button>
                                    <div id="guild-donate-status" class="text-xs text-gray-500"></div>
                                </div>
                            </div>
                            <div id="guild-requests-section" class="hidden">
                                <div class="text-sm font-medium text-gray-700 mt-3 mb-1">Pending Join Requests</div>
                                <ul id="guild-requests" class="border rounded divide-y text-sm"></ul>
                            </div>
                        </div>
                    </div>

                    <div id="gpanel-create" class="space-y-3 hidden">
                        <h3 class="text-lg font-semibold mb-2">Create Guild</h3>
                        <div class="text-xs text-gray-500 mb-2">Requires level 1000 and 1 black time token. You can only be in one guild and guilds are capped at 50 members.</div>
                        <div class="space-y-2 max-w-md">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input id="guild-create-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" maxlength="60">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
                                <input id="guild-create-desc" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" maxlength="255">
                            </div>
                            <div class="flex items-center gap-3">
                                <button id="guild-create-btn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded">Create Guild</button>
                                <div id="guild-create-status" class="text-xs text-gray-500"></div>
                            </div>
                        </div>
                    </div>

                    <div id="gpanel-browse" class="space-y-3 hidden">
                        <h3 class="text-lg font-semibold mb-2">Browse Guilds</h3>
                        <div class="flex items-end gap-2 mb-2">
                            <input id="guild-search" type="text" placeholder="Filter by name" class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <button id="guild-refresh" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-xs rounded">Refresh</button>
                            <div id="guild-list-status" class="text-xs text-gray-500"></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-xs text-gray-500 uppercase">
                                        <th class="py-1 pr-3">Name</th>
                                        <th class="py-1 pr-3">Members</th>
                                        <th class="py-1 pr-3">Status</th>
                                        <th class="py-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="guild-list" class="divide-y"></tbody>
                            </table>
                        </div>
                    </div>

                    <script>
                        (function(){
                            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
                            function readCookie(name){
                                const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g,'\\$1') + '=([^;]*)'));
                                return m ? decodeURIComponent(m[1]) : '';
                            }
                            const xsrf = readCookie('XSRF-TOKEN');

                            // Tabs
                            const tabMy = document.getElementById('gtab-my');
                            const tabCreate = document.getElementById('gtab-create');
                            const tabBrowse = document.getElementById('gtab-browse');
                            const panelMy = document.getElementById('gpanel-my');
                            const panelCreate = document.getElementById('gpanel-create');
                            const panelBrowse = document.getElementById('gpanel-browse');

                            function activateTab(name){
                                const tabs = { my: tabMy, create: tabCreate, browse: tabBrowse };
                                const panels = { my: panelMy, create: panelCreate, browse: panelBrowse };
                                Object.keys(panels).forEach(k => {
                                    if (panels[k]) panels[k].classList.toggle('hidden', k !== name);
                                });
                                Object.keys(tabs).forEach(k => {
                                    const btn = tabs[k];
                                    if (!btn) return;
                                    if (k === name){
                                        btn.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                                        btn.classList.remove('text-gray-600');
                                    } else {
                                        btn.classList.remove('border-b-2','border-indigo-600','text-indigo-700');
                                        btn.classList.add('text-gray-600');
                                    }
                                });
                            }

                            const guildStatus = document.getElementById('guild-status');
                            const guildNone = document.getElementById('guild-none');
                            const guildView = document.getElementById('guild-view');
                            const guildName = document.getElementById('guild-name');
                            const guildDesc = document.getElementById('guild-desc');
                            const guildMeta = document.getElementById('guild-meta');
                            const guildLevelText = document.getElementById('guild-level');
                            const guildMembers = document.getElementById('guild-members');
                            const guildContrib = document.getElementById('guild-contrib');
                            const guildLeaveBtn = document.getElementById('guild-leave');
                            const guildDisbandBtn = document.getElementById('guild-disband');
                            const guildVisibilityRow = document.getElementById('guild-visibility-row');
                            const guildVisibility = document.getElementById('guild-visibility');
                            const guildRequestsSection = document.getElementById('guild-requests-section');
                            const guildRequestsList = document.getElementById('guild-requests');
                            const guildDonateColor = document.getElementById('guild-donate-color');
                            const guildDonateQty = document.getElementById('guild-donate-qty');
                            const guildDonateBtn = document.getElementById('guild-donate-btn');
                            const guildDonateStatus = document.getElementById('guild-donate-status');

                            const createName = document.getElementById('guild-create-name');
                            const createDesc = document.getElementById('guild-create-desc');
                            const createBtn = document.getElementById('guild-create-btn');
                            const createStatus = document.getElementById('guild-create-status');

                            const listTbody = document.getElementById('guild-list');
                            const listStatus = document.getElementById('guild-list-status');
                            const listSearch = document.getElementById('guild-search');
                            const listRefresh = document.getElementById('guild-refresh');

                            let myGuildId = null;
                            let myRole = null;

                            function esc(str){
                                return String(str || '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s] || s));
                            }

                            async function getJSON(url){
                                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                const data = await res.json();
                                if (!res.ok) throw new Error(data.message || 'Request failed');
                                return data;
                            }

                            async function postJSON(url, payload){
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-XSRF-TOKEN': xsrf,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify(payload || {}),
                                });
                                const data = await res.json();
                                if (!res.ok || (data && data.ok === false)) {
                                    throw new Error(data.message || 'Request failed');
                                }
                                return data;
                            }

                            async function loadMyGuild(){
                                guildStatus.textContent = 'Loading...';
                                guildNone.classList.add('hidden');
                                guildView.classList.add('hidden');
                                myGuildId = null;
                                myRole = null;
                                try {
                                    const data = await getJSON('/api/guilds/me');
                                    if (!data.guild) {
                                        guildStatus.textContent = '';
                                        guildNone.classList.remove('hidden');
                                        // allow create when not in guild
                                        if (tabCreate) tabCreate.disabled = false;
                                        if (panelCreate) panelCreate.classList.remove('hidden');
                                        return;
                                    }
                                    const g = data.guild;
                                    myGuildId = g.id;
                                    const members = Array.isArray(g.members) ? g.members : [];
                                    const me = members.find(m => m.user_id === data.me_id) || null;
                                    myRole = me ? me.role : null;

                                    guildName.textContent = g.name;
                                    guildDesc.textContent = g.description || '';
                                    guildMeta.textContent = `${members.length} / 50 members` + (g.is_locked ? ' • LOCKED' : '');
                                    if (guildLevelText) {
                                        const lvl = g.level || 1;
                                        const xp = g.xp || 0;
                                        const nx = g.next_xp || 0;
                                        const tot = g.total_xp || 0;
                                        guildLevelText.textContent = `Level ${lvl} • ${xp.toLocaleString()} / ${nx.toLocaleString()} XP (Total: ${tot.toLocaleString()})`;
                                    }
                                    guildMembers.innerHTML = '';
                                    if (guildContrib) guildContrib.innerHTML = '';
                                    for (const m of members) {
                                        const li = document.createElement('li');
                                        li.className = 'px-3 py-1 flex items-center justify-between';
                                        const label = document.createElement('span');
                                        label.textContent = `${m.username || 'User #'+m.user_id}`;
                                        const right = document.createElement('span');
                                        right.className = 'flex items-center gap-2';
                                        const role = document.createElement('span');
                                        let roleCls = 'px-2 py-0.5 rounded-full text-xs';
                                        if (m.role === 'leader') {
                                            roleCls += ' bg-indigo-100 text-indigo-700';
                                        } else if (m.role === 'officer') {
                                            roleCls += ' bg-emerald-100 text-emerald-700';
                                        } else {
                                            roleCls += ' bg-gray-100 text-gray-700';
                                        }
                                        role.className = roleCls;
                                        role.textContent = m.role;
                                        right.appendChild(role);
                                        if (myRole === 'leader' && m.role !== 'leader') {
                                            const btn = document.createElement('button');
                                            btn.className = 'px-2 py-0.5 text-xs rounded border border-gray-300 hover:bg-gray-100';
                                            const isOfficer = m.role === 'officer';
                                            btn.textContent = isOfficer ? 'Demote' : 'Promote';
                                            btn.addEventListener('click', async () => {
                                                guildStatus.textContent = isOfficer ? 'Demoting member...' : 'Promoting member...';
                                                try {
                                                    await postJSON(`/api/guilds/members/${m.id}/role`, { role: isOfficer ? 'member' : 'officer' });
                                                    guildStatus.textContent = 'Role updated';
                                                    await loadMyGuild();
                                                } catch (e) {
                                                    guildStatus.textContent = e.message || 'Failed to update role';
                                                }
                                            });
                                            right.appendChild(btn);
                                        }
                                        li.appendChild(label);
                                        li.appendChild(right);
                                        guildMembers.appendChild(li);
                                    }

                                    // Build member contribution leaderboard (sorted by contribution_xp)
                                    if (guildContrib) {
                                        const contribList = [...members].sort((a, b) => {
                                            const ax = a.contribution_xp || 0;
                                            const bx = b.contribution_xp || 0;
                                            if (bx !== ax) return bx - ax;
                                            return (a.username || '').localeCompare(b.username || '');
                                        });
                                        let rank = 1;
                                        for (const m of contribList) {
                                            const xp = m.contribution_xp || 0;
                                            const li = document.createElement('li');
                                            li.className = 'px-3 py-1 flex items-center justify-between';
                                            const left = document.createElement('span');
                                            left.textContent = `#${rank} ${m.username || 'User #' + m.user_id}`;
                                            const right = document.createElement('span');
                                            right.textContent = `${xp.toLocaleString()} XP`;
                                            right.className = 'text-gray-700';
                                            if (m.user_id === data.me_id) {
                                                li.classList.add('bg-indigo-50');
                                                right.classList.add('font-semibold');
                                            }
                                            li.appendChild(left);
                                            li.appendChild(right);
                                            guildContrib.appendChild(li);
                                            rank++;
                                        }
                                    }

                                    // Visibility toggle only for leader
                                    if (guildVisibilityRow && guildVisibility) {
                                        if (myRole === 'leader') {
                                            guildVisibilityRow.classList.remove('hidden');
                                            guildVisibility.value = g.is_private ? '1' : '0';
                                        } else {
                                            guildVisibilityRow.classList.add('hidden');
                                        }
                                    }

                                    if (g.is_locked) {
                                        guildLeaveBtn.disabled = true;
                                        guildLeaveBtn.classList.add('opacity-50');
                                    } else {
                                        guildLeaveBtn.disabled = false;
                                        guildLeaveBtn.classList.remove('opacity-50');
                                    }

                                    if (myRole === 'leader' && !g.is_locked) {
                                        guildDisbandBtn.classList.remove('hidden');
                                    } else {
                                        guildDisbandBtn.classList.add('hidden');
                                    }

                                    // Pending join requests (leader only)
                                    if (guildRequestsSection && guildRequestsList) {
                                        guildRequestsList.innerHTML = '';
                                        const reqs = Array.isArray(g.join_requests) ? g.join_requests : [];
                                        if (myRole === 'leader' && reqs.length > 0) {
                                            guildRequestsSection.classList.remove('hidden');
                                            for (const r of reqs) {
                                                const li = document.createElement('li');
                                                li.className = 'px-3 py-2 flex items-center justify-between';
                                                const label = document.createElement('span');
                                                label.textContent = r.username || ('User #' + r.user_id);
                                                const btns = document.createElement('span');
                                                btns.innerHTML = `<button data-id="${r.id}" class="gr-approve px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs rounded mr-2">Approve</button>`+
                                                    `<button data-id="${r.id}" class="gr-deny px-2 py-1 bg-rose-600 hover:bg-rose-500 text-white text-xs rounded">Deny</button>`;
                                                li.appendChild(label);
                                                li.appendChild(btns);
                                                guildRequestsList.appendChild(li);
                                            }
                                            guildRequestsList.querySelectorAll('.gr-approve').forEach(btn => {
                                                btn.addEventListener('click', async () => {
                                                    const id = parseInt(btn.getAttribute('data-id'), 10) || 0;
                                                    if (!id) return;
                                                    guildStatus.textContent = 'Approving request...';
                                                    try {
                                                        await postJSON(`/api/guilds/requests/${id}/approve`, {});
                                                        guildStatus.textContent = 'Request approved';
                                                        await loadMyGuild();
                                                        await loadGuilds();
                                                    } catch (e) {
                                                        guildStatus.textContent = e.message || 'Failed to approve request';
                                                    }
                                                });
                                            });
                                            guildRequestsList.querySelectorAll('.gr-deny').forEach(btn => {
                                                btn.addEventListener('click', async () => {
                                                    const id = parseInt(btn.getAttribute('data-id'), 10) || 0;
                                                    if (!id) return;
                                                    guildStatus.textContent = 'Denying request...';
                                                    try {
                                                        await postJSON(`/api/guilds/requests/${id}/deny`, {});
                                                        guildStatus.textContent = 'Request denied';
                                                        await loadMyGuild();
                                                    } catch (e) {
                                                        guildStatus.textContent = e.message || 'Failed to deny request';
                                                    }
                                                });
                                            });
                                        } else {
                                            guildRequestsSection.classList.add('hidden');
                                        }
                                    }

                                    guildStatus.textContent = '';
                                    guildView.classList.remove('hidden');
                                    // hide/disable create tab when already in a guild
                                    if (tabCreate) tabCreate.disabled = true;
                                    if (panelCreate) panelCreate.classList.add('hidden');
                                } catch (e) {
                                    guildStatus.textContent = 'Failed to load guild info';
                                }
                            }

                            async function loadGuilds(){
                                listStatus.textContent = 'Loading...';
                                listTbody.innerHTML = '';
                                try {
                                    const data = await getJSON('/api/guilds');
                                    let list = Array.isArray(data.guilds) ? data.guilds : [];
                                    const q = (listSearch.value || '').toLowerCase();
                                    if (q) {
                                        list = list.filter(g => (g.name || '').toLowerCase().includes(q));
                                    }
                                    if (list.length === 0) {
                                        listTbody.innerHTML = '<tr><td colspan="4" class="py-3 text-sm text-gray-500">No guilds found</td></tr>';
                                        listStatus.textContent = '';
                                        return;
                                    }
                                    for (const g of list) {
                                        const tr = document.createElement('tr');
                                        tr.className = 'hover:bg-gray-50';
                                        const statusLabel = g.is_locked
                                            ? '<span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Locked</span>'
                                            : (g.is_private
                                                ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Private</span>'
                                                : '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Open</span>');
                                        const canJoin = !g.is_locked && !g.is_private;
                                        const canRequest = !g.is_locked && g.is_private;
                                        tr.innerHTML = `
                                            <td class="py-1 pr-3 align-top">
                                                <div class="font-medium text-gray-800">${esc(g.name)}</div>
                                                <div class="text-xs text-gray-500">${esc(g.description || '')}</div>
                                            </td>
                                            <td class="py-1 pr-3 align-top text-xs text-gray-600">${g.members} / 50</td>
                                            <td class="py-1 pr-3 align-top text-xs">${statusLabel}</td>
                                            <td class="py-1 align-top">
                                                ${canJoin || canRequest ? `<button data-id="${g.id}" data-private="${g.is_private?1:0}" class="guild-join px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded">${canJoin ? 'Join' : 'Request'}</button>` : ''}
                                            </td>
                                        `;
                                        listTbody.appendChild(tr);
                                    }
                                    listStatus.textContent = '';
                                    document.querySelectorAll('.guild-join').forEach(btn => {
                                        btn.addEventListener('click', async (e) => {
                                            const id = parseInt(e.target.getAttribute('data-id'), 10) || 0;
                                            const isPrivate = e.target.getAttribute('data-private') === '1';
                                            if (!id) return;
                                            listStatus.textContent = isPrivate ? 'Requesting to join...' : 'Joining...';
                                            try {
                                                await postJSON('/api/guilds/join', { guild_id: id });
                                                listStatus.textContent = isPrivate ? 'Join request sent' : 'Joined guild';
                                                await loadMyGuild();
                                                await loadGuilds();
                                            } catch (err) {
                                                listStatus.textContent = err.message || (isPrivate ? 'Failed to request join' : 'Failed to join guild');
                                            }
                                        });
                                    });
                                } catch (e) {
                                    listStatus.textContent = 'Failed to load guilds';
                                }
                            }

                            createBtn.addEventListener('click', async () => {
                                createStatus.textContent = 'Creating guild...';
                                try {
                                    const payload = {
                                        name: createName.value || '',
                                        description: createDesc.value || '',
                                    };
                                    await postJSON('/api/guilds/create', payload);
                                    createStatus.textContent = 'Guild created';
                                    createName.value = '';
                                    createDesc.value = '';
                                    await loadMyGuild();
                                    await loadGuilds();
                                } catch (e) {
                                    createStatus.textContent = e.message || 'Failed to create guild';
                                }
                            });

                            if (guildDonateBtn && guildDonateColor && guildDonateQty) {
                                guildDonateBtn.addEventListener('click', async () => {
                                    if (!myGuildId) { guildDonateStatus.textContent = 'You are not in a guild'; return; }
                                    const color = (guildDonateColor.value || 'red').toLowerCase();
                                    const qty = parseInt(guildDonateQty.value, 10) || 0;
                                    if (qty <= 0) { guildDonateStatus.textContent = 'Enter quantity'; return; }
                                    guildDonateStatus.textContent = 'Donating tokens...';
                                    try {
                                        const res = await postJSON('/api/guilds/donate-tokens', {
                                            guild_id: myGuildId,
                                            color,
                                            quantity: qty,
                                        });
                                        const xp = res.xp_added || 0;
                                        guildDonateStatus.textContent = xp > 0
                                            ? `Donated ${res.taken} ${color} token(s) for ${xp.toLocaleString()} guild XP`
                                            : 'Donation complete';
                                        await loadMyGuild();
                                    } catch (e) {
                                        guildDonateStatus.textContent = e.message || 'Failed to donate tokens';
                                    }
                                });
                            }

                            guildLeaveBtn.addEventListener('click', async () => {
                                if (!myGuildId) return;
                                guildStatus.textContent = 'Leaving guild...';
                                try {
                                    await postJSON('/api/guilds/leave', {});
                                    guildStatus.textContent = 'Left guild';
                                    await loadMyGuild();
                                    await loadGuilds();
                                } catch (e) {
                                    guildStatus.textContent = e.message || 'Failed to leave guild';
                                }
                            });

                            guildDisbandBtn.addEventListener('click', async () => {
                                if (!myGuildId) return;
                                if (!confirm('Disband this guild? This cannot be undone.')) return;
                                guildStatus.textContent = 'Disbanding guild...';
                                try {
                                    await postJSON('/api/guilds/disband', {});
                                    guildStatus.textContent = 'Guild disbanded';
                                    await loadMyGuild();
                                    await loadGuilds();
                                } catch (e) {
                                    guildStatus.textContent = e.message || 'Failed to disband guild';
                                }
                            });

                            if (guildVisibility) {
                                guildVisibility.addEventListener('change', async () => {
                                    if (!myGuildId) return;
                                    const isPrivate = guildVisibility.value === '1';
                                    guildStatus.textContent = 'Updating visibility...';
                                    try {
                                        await postJSON('/api/guilds/visibility', {
                                            guild_id: myGuildId,
                                            is_private: isPrivate,
                                        });
                                        guildStatus.textContent = 'Visibility updated';
                                        await loadGuilds();
                                    } catch (e) {
                                        guildStatus.textContent = e.message || 'Failed to update visibility';
                                    }
                                });
                            }

                            listRefresh.addEventListener('click', loadGuilds);
                            listSearch.addEventListener('input', () => {
                                loadGuilds();
                            });

                            if (tabMy) tabMy.addEventListener('click', () => activateTab('my'));
                            if (tabCreate) tabCreate.addEventListener('click', () => {
                                if (!tabCreate.disabled) activateTab('create');
                            });
                            if (tabBrowse) tabBrowse.addEventListener('click', () => activateTab('browse'));

                            activateTab('my');
                            loadMyGuild();
                            loadGuilds();
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
