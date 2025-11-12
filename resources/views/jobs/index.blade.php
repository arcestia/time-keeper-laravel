<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jobs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold">Available Jobs</div>
                        <div class="flex items-center gap-2">
                            <button id="jobs-all" class="px-3 py-1.5 rounded text-sm border border-indigo-200 bg-indigo-50 text-indigo-700">All</button>
                            <button id="jobs-premium" class="px-3 py-1.5 rounded text-sm border text-gray-700">Premium</button>
                            <button id="jobs-standard" class="px-3 py-1.5 rounded text-sm border text-gray-700">Standard</button>
                            <button id="jobs-refresh" class="text-sm text-indigo-600 hover:underline ml-2">Refresh</button>
                            <span id="jobs-bonus" class="ml-3 text-xs text-emerald-700 hidden"></span>
                        </div>
                    </div>
                    <div id="jobs-status" class="mt-2 text-sm text-gray-500"></div>
                    <ul id="jobs-list" class="mt-3 divide-y border rounded"></ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
            const jobsList = document.getElementById('jobs-list');
            const jobsStatus = document.getElementById('jobs-status');
            document.getElementById('jobs-refresh').addEventListener('click', () => refreshJobs());
            const btnAll = document.getElementById('jobs-all');
            const btnPrem = document.getElementById('jobs-premium');
            const btnStd = document.getElementById('jobs-standard');
            let jobsCache = [];
            let jobsFilter = 'all';
            let rewardMult = 1.0; let xpMult = 1.0; let premActive = false;
            function setJobsFilter(f){
                jobsFilter = f;
                const set = (btn, active) => {
                    if (active){ btn.classList.add('border-indigo-200','bg-indigo-50','text-indigo-700'); }
                    else { btn.classList.remove('border-indigo-200','bg-indigo-50','text-indigo-700'); }
                };
                set(btnAll, f==='all'); set(btnPrem, f==='premium'); set(btnStd, f==='standard');
                renderJobs();
            }
            btnAll.addEventListener('click', () => setJobsFilter('all'));
            btnPrem.addEventListener('click', () => setJobsFilter('premium'));
            btnStd.addEventListener('click', () => setJobsFilter('standard'));

            function fmtHMS(sec) {
                sec = Math.max(0, parseInt(sec || 0, 10));
                const hh = Math.floor(sec / 3600); sec %= 3600;
                const mm = Math.floor(sec / 60);
                const ss = sec % 60;
                return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
            }

            function renderJobs(){
                jobsList.innerHTML = '';
                const list = Array.isArray(jobsCache) ? jobsCache.filter(j => {
                    if (jobsFilter==='premium') return !!j.premium_only;
                    if (jobsFilter==='standard') return !j.premium_only;
                    return true;
                }) : [];
                if (list.length === 0) {
                    jobsList.innerHTML = '<li class="p-3 text-sm text-gray-500">No jobs available</li>';
                    jobsStatus.textContent = '';
                    return;
                }
                for (const j of list) {
                        const li = document.createElement('li');
                        li.className = 'p-3';
                        const title = document.createElement('div');
                        title.className = 'font-medium';
                        const energy = (j.energy_cost ?? 0);
                        const base = (parseInt(j.reward_seconds,10)||0);
                        const eff = premActive && rewardMult>1 ? Math.floor(base * rewardMult) : base;
                        let xp = Math.max(1, Math.floor(eff / 30));
                        const multText = premActive && rewardMult>1 ? (' • x' + rewardMult.toFixed(2)) : '';
                        const xpMultText = premActive && xpMult>1 ? (' • XP x' + xpMult.toFixed(2)) : '';
                        if (premActive && xpMult>1) { xp = Math.max(1, Math.floor(xp * xpMult)); }
                        title.textContent = j.name + ' (+' + eff + 's' + multText + xpMultText + ', +' + xp + ' XP, -' + energy + '% energy)';
                        const meta = document.createElement('div');
                        meta.className = 'text-xs text-gray-500 mt-0.5';
                        meta.textContent = 'Duration: ' + fmtHMS(j.duration_seconds) + ' • Cooldown: ' + fmtHMS(j.cooldown_seconds);
                        const desc = document.createElement('div');
                        desc.className = 'text-sm text-gray-600';
                        desc.textContent = j.description || '';
                        const act = document.createElement('div');
                        act.className = 'mt-2 text-sm';

                        if (j.active_run) {
                            const can = j.active_run.can_claim;
                            const ends = new Date(j.active_run.ends_at);
                            const info = document.createElement('span');
                            info.textContent = can ? 'Completed' : 'In progress, completes at ' + ends.toLocaleTimeString();
                            act.appendChild(info);
                            const btn = document.createElement('button');
                            btn.className = 'ml-3 px-3 py-1 rounded bg-green-600 text-white disabled:opacity-50';
                            btn.textContent = 'Claim';
                            btn.disabled = !can;
                            btn.addEventListener('click', async () => {
                                btn.disabled = true;
                                try {
                                    const r = await fetch('/api/jobs/' + encodeURIComponent(j.key) + '/claim', {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': csrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                    });
                                    if (!r.ok) throw new Error();
                                    await refreshJobs();
                                } catch (e) { btn.disabled = false; }
                            });
                            act.appendChild(btn);
                        } else if (j.next_available_at) {
                            const next = new Date(j.next_available_at);
                            act.textContent = 'On cooldown until ' + next.toLocaleTimeString();
                        } else {
                            const startBtn = document.createElement('button');
                            startBtn.className = 'px-3 py-1 rounded bg-indigo-600 text-white';
                            startBtn.textContent = 'Start';
                            startBtn.addEventListener('click', async () => {
                                startBtn.disabled = true;
                                try {
                                    const r = await fetch('/api/jobs/' + encodeURIComponent(j.key) + '/start', {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': csrf,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                    });
                                    if (!r.ok) throw new Error();
                                    await refreshJobs();
                                } catch (e) { startBtn.disabled = false; }
                            });
                            act.appendChild(startBtn);
                        }

                        li.appendChild(title);
                        li.appendChild(meta);
                        li.appendChild(desc);
                        li.appendChild(act);
                        jobsList.appendChild(li);
                    }
                    jobsStatus.textContent = '';
            }

            async function refreshJobs() {
                jobsList.innerHTML = '';
                try {
                    // pull premium multiplier
                    try {
                        const ps = await fetch('/api/premium/status', { headers: { 'Accept':'application/json' } });
                        if (ps.ok) {
                            const d = await ps.json();
                            premActive = !!d.active;
                            const b = d && d.benefits; 
                            rewardMult = (b && b.reward_multiplier) ? parseFloat(b.reward_multiplier) : 1.0;
                            xpMult = (b && b.xp_multiplier) ? parseFloat(b.xp_multiplier) : 1.0;
                            const bonus = document.getElementById('jobs-bonus');
                            if (premActive && (rewardMult>1 || xpMult>1)) { 
                                const parts = [];
                                if (rewardMult>1) parts.push('Rewards x' + rewardMult.toFixed(2));
                                if (xpMult>1) parts.push('XP x' + xpMult.toFixed(2));
                                bonus.textContent = parts.join(' • ');
                                bonus.classList.remove('hidden'); 
                            } else { 
                                bonus.classList.add('hidden'); bonus.textContent=''; 
                            }
                        }
                    } catch (_) {}
                    const res = await fetch('/api/jobs', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    jobsCache = await res.json();
                    renderJobs();
                } catch (e) {
                    jobsStatus.textContent = 'Unable to load jobs';
                }
            }

            refreshJobs();
        })();
    </script>
</x-app-layout>
