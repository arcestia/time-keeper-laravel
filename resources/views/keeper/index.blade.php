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

            
        })();
    </script>
</x-app-layout>
