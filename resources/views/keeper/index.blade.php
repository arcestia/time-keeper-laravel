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

                    <div class="mt-8">
                        <div class="text-lg font-semibold mb-2">Time Series</div>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="p-4 border rounded">
                                <div class="text-sm text-gray-600 mb-2">Reserve Balance</div>
                                <canvas id="chart-reserve" height="140"></canvas>
                            </div>
                            <div class="p-4 border rounded">
                                <div class="text-sm text-gray-600 mb-2">Total Wallets (Display)</div>
                                <canvas id="chart-wallet" height="140"></canvas>
                            </div>
                            <div class="p-4 border rounded">
                                <div class="text-sm text-gray-600 mb-2">Total Banks</div>
                                <canvas id="chart-bank" height="140"></canvas>
                            </div>
                        </div>
                    </div>

                    <div id="st-status" class="mt-4 text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const el = id => document.getElementById(id);
            const statusEl = el('st-status');
            let charts = { reserve: null, wallet: null, bank: null };

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
                try {
                    const r2 = await fetch('/keeper/snapshots?limit=360', { headers: { 'Accept': 'application/json' } });
                    if (!r2.ok) throw new Error('failed');
                    const s = await r2.json();
                    renderCharts(s);
                } catch (e) {
                    // ignore chart errors in UI
                }
            }

            refresh();
            setInterval(refresh, 10000);

            
            function mkChart(ctx, label, data, color){
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label,
                            data: data.series,
                            borderColor: color,
                            backgroundColor: color,
                            fill: false,
                            tension: 0.2,
                            pointRadius: 0,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { display: false },
                            y: { ticks: { callback: v => v.toLocaleString() } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            function renderCharts(s){
                const labels = s.labels || [];
                const reserve = s.reserve || [];
                const wallet = s.wallet || [];
                const bank = s.bank || [];
                const rCtx = el('chart-reserve').getContext('2d');
                const wCtx = el('chart-wallet').getContext('2d');
                const bCtx = el('chart-bank').getContext('2d');
                const rData = { labels, series: reserve };
                const wData = { labels, series: wallet };
                const bData = { labels, series: bank };
                if (charts.reserve) charts.reserve.destroy();
                if (charts.wallet) charts.wallet.destroy();
                if (charts.bank) charts.bank.destroy();
                charts.reserve = mkChart(rCtx, 'Reserve', rData, '#8b5cf6');
                charts.wallet = mkChart(wCtx, 'Wallets', wData, '#10b981');
                charts.bank = mkChart(bCtx, 'Banks', bData, '#3b82f6');
            }
        })();
    </script>
</x-app-layout>
