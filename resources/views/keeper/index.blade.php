<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Keeper') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">Global statistics (auto-refresh every 10s)</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Total Users</div>
                            <div id="st-users" class="text-2xl font-semibold">-</div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Active Users (Wallet Active)</div>
                            <div id="st-active" class="text-2xl font-semibold">-</div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Users With Bank Accounts</div>
                            <div id="st-bank-accounts" class="text-2xl font-semibold">-</div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Zero Wallets</div>
                            <div id="st-zero-wallets" class="text-2xl font-semibold">-</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Total Time (Wallets)</div>
                            <div id="st-wallet" class="text-xl font-mono">-</div>
                            <div id="st-wallet-seconds" class="text-xs text-gray-500">-</div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Total Time (Banks)</div>
                            <div id="st-bank" class="text-xl font-mono">-</div>
                            <div id="st-bank-seconds" class="text-xs text-gray-500">-</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Average Wallet Balance</div>
                            <div id="st-avg-wallet" class="text-xl font-mono">-</div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Average Bank Balance</div>
                            <div id="st-avg-bank" class="text-xl font-mono">-</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600">Time Keeper Reserve</div>
                            <div id="st-reserve" class="text-xl font-mono">-</div>
                            <div id="st-reserve-seconds" class="text-xs text-gray-500">-</div>
                        </div>
                    </div>

                    @if (Auth::user() && Auth::user()->is_admin)
                    <div class="mt-6 p-4 border rounded">
                        <div class="text-lg font-semibold mb-2">Admin Transfers</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Deposit from User Bank → Reserve</div>
                                <input id="adm-dep-username" type="text" placeholder="username" class="border rounded px-3 py-2 w-full mb-2" />
                                <input id="adm-dep-amount" type="text" placeholder="amount (e.g. 1d 2h)" class="border rounded px-3 py-2 w-full mb-2" />
                                <button id="adm-dep-btn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded">Deposit</button>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Withdraw from Reserve → User Bank</div>
                                <input id="adm-wd-username" type="text" placeholder="username" class="border rounded px-3 py-2 w-full mb-2" />
                                <input id="adm-wd-amount" type="text" placeholder="amount (e.g. 1d 2h)" class="border rounded px-3 py-2 w-full mb-2" />
                                <button id="adm-wd-btn" type="button" class="bg-rose-600 text-white px-4 py-2 rounded">Withdraw</button>
                            </div>
                        </div>
                        <div class="mt-4 p-4 border rounded">
                            <div class="text-sm text-gray-600 mb-2">Distribute Reserve → All Users (per-user amount)</div>
                            <div class="flex gap-2 flex-wrap items-center">
                                <input id="adm-dist-amount" type="text" placeholder="amount per user (e.g. 1h 30m)" class="border rounded px-3 py-2 w-80" />
                                <button id="adm-dist-btn" type="button" class="bg-emerald-600 text-white px-4 py-2 rounded">Distribute</button>
                            </div>
                            <div id="adm-dist-status" class="mt-2 text-sm text-gray-500"></div>
                        </div>
                    </div>
                    @endif

                    <div id="st-status" class="mt-4 text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const el = id => document.getElementById(id);
            const statusEl = el('st-status');

            async function refresh() {
                statusEl.textContent = 'Loading...';
                try {
                    const res = await fetch('/keeper/stats', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('failed');
                    const d = await res.json();
                    el('st-users').textContent = d.total_users;
                    el('st-active').textContent = d.active_users;
                    el('st-bank-accounts').textContent = d.users_with_bank_accounts;
                    el('st-zero-wallets').textContent = d.zero_wallets;
                    el('st-wallet').textContent = d.total_wallet_formatted;
                    el('st-wallet-seconds').textContent = d.total_wallet_seconds + ' seconds';
                    el('st-bank').textContent = d.total_bank_formatted;
                    el('st-bank-seconds').textContent = d.total_bank_seconds + ' seconds';
                    el('st-avg-wallet').textContent = d.avg_wallet_formatted;
                    el('st-avg-bank').textContent = d.avg_bank_formatted;
                    el('st-reserve').textContent = d.reserve_formatted;
                    el('st-reserve-seconds').textContent = d.reserve_seconds + ' seconds';
                    statusEl.textContent = '';
                } catch (e) {
                    statusEl.textContent = 'Unable to load stats';
                }
            }

            refresh();
            setInterval(refresh, 10000);

            @if (Auth::user() && Auth::user()->is_admin)
            async function postJSON(url, payload) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(payload)
                });
                return res.json();
            }

            document.getElementById('adm-dep-btn')?.addEventListener('click', async () => {
                const username = document.getElementById('adm-dep-username').value.trim();
                const amount = document.getElementById('adm-dep-amount').value.trim();
                if (!username || !amount) { statusEl.textContent = 'Enter username and amount'; return; }
                statusEl.textContent = 'Processing deposit...';
                try {
                    const r = await postJSON('/keeper/admin/deposit', { username, amount });
                    statusEl.textContent = r.status === 'ok' ? 'Deposit complete' : (r.message || 'Deposit failed');
                    refresh();
                } catch (e) {
                    statusEl.textContent = 'Deposit failed';
                }
            });

            document.getElementById('adm-wd-btn')?.addEventListener('click', async () => {
                const username = document.getElementById('adm-wd-username').value.trim();
                const amount = document.getElementById('adm-wd-amount').value.trim();
                if (!username || !amount) { statusEl.textContent = 'Enter username and amount'; return; }
                statusEl.textContent = 'Processing withdrawal...';
                try {
                    const r = await postJSON('/keeper/admin/withdraw', { username, amount });
                    statusEl.textContent = r.status === 'ok' ? 'Withdrawal complete' : (r.message || 'Withdrawal failed');
                    refresh();
                } catch (e) {
                    statusEl.textContent = 'Withdrawal failed';
                }
            });

            // Distribute per-user amount from reserve to all users
            document.getElementById('adm-dist-btn')?.addEventListener('click', async () => {
                const amount = document.getElementById('adm-dist-amount').value.trim();
                const dstat = document.getElementById('adm-dist-status');
                if (!amount) { dstat.textContent = 'Enter an amount'; return; }
                dstat.textContent = 'Distributing...';
                try {
                    const r = await postJSON('/keeper/admin/distribute', { amount });
                    if (r && r.status === 'ok') {
                        dstat.textContent = `Distributed ${r.per_user} seconds per user. Remaining reserve: ${r.remaining_reserve}`;
                        refresh();
                    } else {
                        dstat.textContent = (r && r.message) ? r.message : 'Distribution failed';
                    }
                } catch (e) {
                    dstat.textContent = 'Distribution failed';
                }
            });
            @endif
        })();
    </script>
</x-app-layout>
