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
                    </div>
                    <script>
                        (() => {
                            const balEl = document.getElementById('dt-balance');
                            const statusEl = document.getElementById('dt-status');
                            const alertEl = document.getElementById('dt-alert');
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
