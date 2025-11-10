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
                                <div class="text-lg font-semibold">Time Bank</div>
                                <div id="tb-balance" class="text-2xl font-bold text-indigo-600">--:--:--</div>
                            </div>
                            <div class="mt-4 flex items-center gap-2">
                                <input id="tb-seconds" type="number" min="1" value="60" class="border rounded px-3 py-2 w-32" />
                                <button id="tb-deposit" class="bg-green-600 text-white px-4 py-2 rounded">Deposit</button>
                                <button id="tb-withdraw" class="bg-rose-600 text-white px-4 py-2 rounded">Withdraw</button>
                                <button data-quick="60" class="bg-gray-200 px-3 py-2 rounded">+60s</button>
                                <button data-quick="-60" class="bg-gray-200 px-3 py-2 rounded">-60s</button>
                            </div>
                            <div id="tb-status" class="mt-2 text-sm text-gray-500"></div>
                        </div>
                    </div>
                    <script>
                        (() => {
                            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const balEl = document.getElementById('tb-balance');
                            const inputEl = document.getElementById('tb-seconds');
                            const statusEl = document.getElementById('tb-status');
                            let current = 0;
                            let last = Date.now();

                            function fmt(sec) {
                                sec = Math.max(0, parseInt(sec || 0, 10));
                                const d = Math.floor(sec / 86400);
                                sec = sec % 86400;
                                const h = Math.floor(sec / 3600);
                                sec = sec % 3600;
                                const m = Math.floor(sec / 60);
                                const s = sec % 60;
                                const hms = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                return d > 0 ? d + 'd ' + hms : hms;
                            }

                            function render() {
                                balEl.textContent = fmt(current);
                            }

                            async function refresh() {
                                try {
                                    const res = await fetch('/bank', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error('failed');
                                    const data = await res.json();
                                    current = parseInt(data.balance_seconds, 10) || 0;
                                    last = Date.now();
                                    render();
                                    statusEl.textContent = '';
                                } catch (e) {
                                    statusEl.textContent = 'Unable to load balance';
                                }
                            }

                            async function post(path, seconds) {
                                statusEl.textContent = 'Processing...';
                                try {
                                    const res = await fetch(path, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                        body: JSON.stringify({ seconds: seconds })
                                    });
                                    if (!res.ok) throw new Error('failed');
                                    const data = await res.json();
                                    current = parseInt(data.balance_seconds, 10) || 0;
                                    last = Date.now();
                                    render();
                                    statusEl.textContent = '';
                                } catch (e) {
                                    statusEl.textContent = 'Request failed';
                                }
                            }

                            document.getElementById('tb-deposit').addEventListener('click', () => {
                                const v = Math.max(1, parseInt(inputEl.value || '0', 10));
                                post('/bank/deposit', v);
                            });
                            document.getElementById('tb-withdraw').addEventListener('click', () => {
                                const v = Math.max(1, parseInt(inputEl.value || '0', 10));
                                post('/bank/withdraw', v);
                            });
                            document.querySelectorAll('button[data-quick]').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const delta = parseInt(btn.getAttribute('data-quick'), 10);
                                    if (delta > 0) post('/bank/deposit', delta); else post('/bank/withdraw', Math.abs(delta));
                                });
                            });

                            setInterval(() => {
                                const now = Date.now();
                                const elapsed = Math.floor((now - last) / 1000);
                                if (elapsed > 0) {
                                    current = Math.max(0, current - elapsed);
                                    last = now;
                                    render();
                                }
                            }, 1000);

                            refresh();
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
