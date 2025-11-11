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
                        <button id="jobs-refresh" class="text-sm text-indigo-600 hover:underline">Refresh</button>
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

            function fmtHMS(sec) {
                sec = Math.max(0, parseInt(sec || 0, 10));
                const hh = Math.floor(sec / 3600); sec %= 3600;
                const mm = Math.floor(sec / 60);
                const ss = sec % 60;
                return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
            }

            async function refreshJobs() {
                jobsList.innerHTML = '';
                try {
                    const res = await fetch('/api/jobs', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    const jobs = await res.json();
                    if (!Array.isArray(jobs) || jobs.length === 0) {
                        jobsList.innerHTML = '<li class="p-3 text-sm text-gray-500">No jobs available</li>';
                        jobsStatus.textContent = '';
                        return;
                    }
                    for (const j of jobs) {
                        const li = document.createElement('li');
                        li.className = 'p-3';
                        const title = document.createElement('div');
                        title.className = 'font-medium';
                        const energy = (j.energy_cost ?? 0);
                        title.textContent = j.name + ' (+' + j.reward_seconds + 's, -' + energy + '% energy)';
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
                } catch (e) {
                    jobsStatus.textContent = 'Unable to load jobs';
                }
            }

            refreshJobs();
        })();
    </script>
</x-app-layout>
