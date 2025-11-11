<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                    <div class="mt-6 grid grid-cols-1 gap-4">
                        <div class="p-4 border rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="text-lg font-semibold">Your Time</div>
                                <div id="dt-balance" class="text-2xl font-bold text-indigo-600">--:--:--</div>
                            </div>
                            <div id="dt-alert" class="mt-2 text-sm text-rose-600 hidden">Warning: Your time is below 1 hour.</div>
                            <div id="dt-status" class="mt-2 text-sm text-gray-500"></div>
                        </div>
                        <div class="p-4 border rounded-lg">
                            <div class="text-lg font-semibold mb-2">Your Stats</div>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm"><span>Energy</span><span id="stat-energy-val">--%</span></div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden"><div id="stat-energy" class="h-2 bg-amber-500" style="width:0%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm"><span>Food</span><span id="stat-food-val">--%</span></div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden"><div id="stat-food" class="h-2 bg-emerald-500" style="width:0%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm"><span>Water</span><span id="stat-water-val">--%</span></div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden"><div id="stat-water" class="h-2 bg-cyan-500" style="width:0%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm"><span>Leisure</span><span id="stat-leisure-val">--%</span></div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden"><div id="stat-leisure" class="h-2 bg-indigo-500" style="width:0%"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm"><span>Health</span><span id="stat-health-val">--%</span></div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden"><div id="stat-health" class="h-2 bg-rose-500" style="width:0%"></div></div>
                                </div>
                                <div id="stats-status" class="text-sm text-gray-500"></div>
                            </div>
                        </div>
                        
                    </div>
                    <script>
                        (() => {
                            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            const balEl = document.getElementById('dt-balance');
                            const statusEl = document.getElementById('dt-status');
                            const alertEl = document.getElementById('dt-alert');
                            const statsStatusEl = document.getElementById('stats-status');
                            const bars = {
                                energy: document.getElementById('stat-energy'),
                                food: document.getElementById('stat-food'),
                                water: document.getElementById('stat-water'),
                                leisure: document.getElementById('stat-leisure'),
                                health: document.getElementById('stat-health'),
                            };
                            const vals = {
                                energy: document.getElementById('stat-energy-val'),
                                food: document.getElementById('stat-food-val'),
                                water: document.getElementById('stat-water-val'),
                                leisure: document.getElementById('stat-leisure-val'),
                                health: document.getElementById('stat-health-val'),
                            };
                            let current = 0;
                            let last = Date.now();

                            function fmt(sec) {
                                sec = Math.max(0, parseInt(sec || 0, 10));
                                const MIL = 31536000000, CEN = 3153600000, DEC = 315360000, Y = 31536000, W = 604800, D = 86400;
                                const mil = Math.floor(sec / MIL); sec %= MIL;
                                const cen = Math.floor(sec / CEN); sec %= CEN;
                                const dec = Math.floor(sec / DEC); sec %= DEC;
                                const y   = Math.floor(sec / Y);   sec %= Y;
                                const w   = Math.floor(sec / W);   sec %= W;
                                const dd  = Math.floor(sec / D);   sec %= D;
                                const hh  = Math.floor(sec / 3600); sec %= 3600;
                                const mm  = Math.floor(sec / 60);
                                const ss  = sec % 60;
                                return [
                                    String(mil).padStart(3, '0'),
                                    String(cen).padStart(3, '0'),
                                    String(dec).padStart(3, '0'),
                                    String(y).padStart(3, '0'),
                                    String(w).padStart(2, '0'),
                                    String(dd).padStart(2, '0'),
                                    String(hh).padStart(2, '0'),
                                    String(mm).padStart(2, '0'),
                                    String(ss).padStart(2, '0'),
                                ].join(':');
                            }

                            async function refresh() {
                                try {
                                    const res = await fetch('/bank/user-time', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error('failed');
                                    const data = await res.json();
                                    current = parseInt(data.seconds, 10) || 0;
                                    last = Date.now();
                                    balEl.textContent = fmt(current);
                                    if (current < 3600) { alertEl.classList.remove('hidden'); } else { alertEl.classList.add('hidden'); }
                                    statusEl.textContent = '';
                                } catch (e) {
                                    statusEl.textContent = 'Unable to load time';
                                }
                                try {
                                    const r2 = await fetch('/api/me/stats', { headers: { 'Accept': 'application/json' } });
                                    if (!r2.ok) throw new Error('failed');
                                    const s = await r2.json();
                                    for (const k of ['energy','food','water','leisure','health']) {
                                        const v = Math.max(0, Math.min(100, parseInt(s[k] ?? 0, 10)));
                                        if (bars[k]) bars[k].style.width = v + '%';
                                        if (vals[k]) vals[k].textContent = v + '%';
                                    }
                                    statsStatusEl.textContent = '';
                                } catch (e) {
                                    statsStatusEl.textContent = 'Unable to load stats';
                                }
                                
                            }

                            setInterval(() => {
                                const now = Date.now();
                                const elapsed = Math.floor((now - last) / 1000);
                                if (elapsed > 0) {
                                    current = Math.max(0, current - elapsed);
                                    last = now;
                                    balEl.textContent = fmt(current);
                                    if (current < 3600) { alertEl.classList.remove('hidden'); } else { alertEl.classList.add('hidden'); }
                                }
                            }, 1000);

                            refresh();
                            setInterval(refresh, 10000);
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
