<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Bank') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div id="bank-status" class="text-sm text-gray-500 mb-3"></div>

                    <div id="passcode-setup" class="hidden">
                        <div class="mb-2 text-gray-700">Set a passcode to secure your bank.</div>
                        <div class="flex gap-2 flex-wrap items-center">
                            <input id="setup-passcode" type="password" placeholder="New passcode" class="border rounded px-3 py-2 w-56" />
                            <button id="setup-btn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded">Set Passcode</button>
                        </div>
                    </div>

                    <div id="passcode-login" class="hidden">
                        <div class="mb-2 text-gray-700">Enter your bank passcode to continue.</div>
                        <div class="flex gap-2 flex-wrap items-center">
                            <input id="login-passcode" type="password" placeholder="Passcode" class="border rounded px-3 py-2 w-56" />
                            <button id="login-btn" type="button" class="bg-indigo-600 text-white px-4 py-2 rounded">Login</button>
                        </div>
                    </div>

                    <div id="bank-content" class="hidden mt-6">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-sm text-gray-600">Wallet (decays)</div>
                            <div id="wallet-balance" class="text-xl font-semibold text-gray-800">--:--:--</div>
                        </div>
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-sm text-gray-600">Bank Balance</div>
                            <div id="bank-balance" class="text-xl font-semibold text-indigo-600">--:--:--</div>
                        </div>

                        <div class="mt-6 grid gap-6">
                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Deposit</div>
                                <div class="flex gap-2">
                                    <input id="dep-amount" type="text" placeholder="e.g., 1d 2h" class="border rounded px-3 py-2 w-56" />
                                    <button id="dep-btn" class="bg-green-600 text-white px-4 py-2 rounded">Deposit</button>
                                    <button data-quick="1d" class="bg-gray-200 px-3 py-2 rounded">+1d</button>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Minimum deposit: 1 day</div>
                            </div>

                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Withdraw</div>
                                <div class="flex gap-2">
                                    <input id="wd-amount" type="text" placeholder="e.g., 1h 30m" class="border rounded px-3 py-2 w-56" />
                                    <button id="wd-btn" class="bg-rose-600 text-white px-4 py-2 rounded">Withdraw</button>
                                </div>
                            </div>

                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Transfer</div>
                                <div class="flex gap-2 flex-wrap">
                                    <input id="tr-to" type="text" placeholder="Recipient username" class="border rounded px-3 py-2 w-56" />
                                    <input id="tr-amount" type="text" placeholder="Amount e.g., 2h" class="border rounded px-3 py-2 w-56" />
                                    <button id="tr-btn" class="bg-blue-600 text-white px-4 py-2 rounded">Transfer</button>
                                </div>
                            </div>

                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Exchange Time Tokens</div>
                                <div id="tok-balances" class="text-xs text-gray-600 mb-2">Balances: --</div>
                                <div class="flex gap-2 flex-wrap items-center">
                                    <label for="tok-color" class="text-sm text-gray-600">Color</label>
                                    <select id="tok-color" class="border rounded px-3 py-2">
                                        <option value="red">Red (1 Week)</option>
                                        <option value="blue">Blue (1 Month)</option>
                                        <option value="green">Green (1 Year)</option>
                                        <option value="yellow">Yellow (1 Decade)</option>
                                        <option value="black">Black (1 Century)</option>
                                    </select>
                                    <label for="tok-qty" class="text-sm text-gray-600">Qty</label>
                                    <input id="tok-qty" type="number" min="1" value="1" class="border rounded px-3 py-2 w-24" />
                                    <button id="tok-exchange-btn" class="bg-purple-600 text-white px-4 py-2 rounded">Exchange</button>
                                </div>
                                <div id="tok-result" class="text-xs text-gray-500 mt-1"></div>
                            </div>

                            <div class="flex justify-end">
                                <button id="logout-btn" class="text-sm text-gray-600 hover:text-gray-800">Lock Bank</button>
                            </div>
                        </div>
                    </div>

                    <script>
                        (() => {
                            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const statusEl = document.getElementById('bank-status');
                            const setupBox = document.getElementById('passcode-setup');
                            const loginBox = document.getElementById('passcode-login');
                            const contentBox = document.getElementById('bank-content');
                            const setupInput = document.getElementById('setup-passcode');
                            const setupBtn = document.getElementById('setup-btn');
                            const loginInput = document.getElementById('login-passcode');
                            const loginBtn = document.getElementById('login-btn');

                            const walletEl = document.getElementById('wallet-balance');
                            const bankEl = document.getElementById('bank-balance');

                            const depAmount = document.getElementById('dep-amount');
                            const depBtn = document.getElementById('dep-btn');
                            const wdAmount = document.getElementById('wd-amount');
                            const wdBtn = document.getElementById('wd-btn');
                            const trTo = document.getElementById('tr-to');
                            const trAmount = document.getElementById('tr-amount');
                            const trBtn = document.getElementById('tr-btn');
                            const tokColor = document.getElementById('tok-color');
                            const tokQty = document.getElementById('tok-qty');
                            const tokBtn = document.getElementById('tok-exchange-btn');
                            const tokResult = document.getElementById('tok-result');
                            const tokBalancesEl = document.getElementById('tok-balances');
                            const logoutBtn = document.getElementById('logout-btn');

                            let loggedIn = false;
                            let walletCurrent = 0;
                            let bankCurrent = 0;
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

                            function showSetup() {
                                setupBox.classList.remove('hidden');
                                loginBox.classList.add('hidden');
                                contentBox.classList.add('hidden');
                            }
                            function showLogin() {
                                setupBox.classList.add('hidden');
                                loginBox.classList.remove('hidden');
                                contentBox.classList.add('hidden');
                            }
                            function showContent() {
                                setupBox.classList.add('hidden');
                                loginBox.classList.add('hidden');
                                contentBox.classList.remove('hidden');
                            }

                            async function loadData() {
                                statusEl.textContent = 'Loading...';
                                try {
                                    const res = await fetch('/bank/data', { headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) throw new Error('failed');
                                    const data = await res.json();
                                    if (data.requires_passcode) {
                                        showSetup();
                                        statusEl.textContent = 'Passcode not set';
                                        return;
                                    }
                                    loggedIn = !!data.bank_logged_in;
                                    if (loggedIn) {
                                        showContent();
                                    } else {
                                        showLogin();
                                    }
                                    bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                    walletCurrent = parseInt(data.wallet_seconds || 0, 10);
                                    bankEl.textContent = bankCurrent ? fmt(bankCurrent) : '--:--:--';
                                    walletEl.textContent = fmt(walletCurrent);
                                    if (loggedIn) { await loadTokenBalances(); }
                                    last = Date.now();
                                    statusEl.textContent = '';
                                } catch (e) {
                                    statusEl.textContent = 'Unable to load bank info';
                                }
                            }

                            async function loadTokenBalances(){
                                try{
                                    const r = await fetch('/bank/token-balances', { headers: { 'Accept':'application/json' } });
                                    if (!r.ok) throw new Error('');
                                    const j = await r.json();
                                    const b = j && j.balances ? j.balances : {};
                                    tokBalancesEl.textContent = `Balances: Red ${b.red||0}, Blue ${b.blue||0}, Green ${b.green||0}, Yellow ${b.yellow||0}, Black ${b.black||0}`;
                                }catch{ tokBalancesEl.textContent = 'Balances: --'; }
                            }

                            setupBtn.addEventListener('click', async () => {
                                const p = setupInput.value.trim();
                                if (!p) { statusEl.textContent = 'Enter passcode'; return; }
                                statusEl.textContent = 'Setting passcode...';
                                try {
                                    const res = await fetch('/bank/passcode', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                        body: JSON.stringify({ passcode: p })
                                    });
                                    if (!res.ok) throw new Error('failed');
                                    statusEl.textContent = 'Passcode set. Please login.';
                                    showLogin();
                                } catch (e) {
                                    statusEl.textContent = 'Failed to set passcode';
                                }
                            });

                            setupInput.addEventListener('keydown', (e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    setupBtn.click();
                                }
                            });

                            loginBtn.addEventListener('click', async () => {
                                const p = loginInput.value.trim();
                                if (!p) { statusEl.textContent = 'Enter passcode'; return; }
                                statusEl.textContent = 'Logging in...';
                                try {
                                    const res = await fetch('/bank/login', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                        body: JSON.stringify({ passcode: p })
                                    });
                                    if (!res.ok) throw new Error('failed');
                                    loggedIn = true;
                                    statusEl.textContent = '';
                                    showContent();
                                } catch (e) {
                                    statusEl.textContent = 'Login failed';
                                }
                            });

                            loginInput.addEventListener('keydown', (e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    loginBtn.click();
                                }
                            });

                            async function postJSON(url, payload) {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                    body: JSON.stringify(payload)
                                });
                                if (!res.ok) {
                                    let msg = 'Request failed';
                                    try { const err = await res.json(); if (err && err.message) msg = err.message; } catch {}
                                    throw new Error(msg);
                                }
                                return res.json();
                            }

                            depBtn.addEventListener('click', async () => {
                                if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                const amt = (depAmount.value || '').trim();
                                statusEl.textContent = 'Depositing...';
                                try {
                                    const data = await postJSON('/bank/deposit', { amount: amt });
                                    bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                    walletCurrent = parseInt(data.wallet_seconds || 0, 10);
                                    bankEl.textContent = fmt(bankCurrent);
                                    walletEl.textContent = fmt(walletCurrent);
                                    last = Date.now();
                                    statusEl.textContent = 'Deposited';
                                } catch (e) { statusEl.textContent = e.message; }
                            });

                            document.querySelectorAll('button[data-quick]').forEach(btn => {
                                btn.addEventListener('click', async () => {
                                    if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                    const token = btn.getAttribute('data-quick');
                                    statusEl.textContent = 'Depositing...';
                                    try {
                                        const data = await postJSON('/bank/deposit', { amount: token });
                                        bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                        walletCurrent = parseInt(data.wallet_seconds || 0, 10);
                                        bankEl.textContent = fmt(bankCurrent);
                                        walletEl.textContent = fmt(walletCurrent);
                                        last = Date.now();
                                        statusEl.textContent = 'Deposited';
                                    } catch (e) { statusEl.textContent = e.message; }
                                });
                            });

                            wdBtn.addEventListener('click', async () => {
                                if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                const amt = (wdAmount.value || '').trim();
                                statusEl.textContent = 'Withdrawing...';
                                try {
                                    const data = await postJSON('/bank/withdraw', { amount: amt });
                                    bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                    walletCurrent = parseInt(data.wallet_seconds || 0, 10);
                                    bankEl.textContent = fmt(bankCurrent);
                                    walletEl.textContent = fmt(walletCurrent);
                                    last = Date.now();
                                    statusEl.textContent = 'Withdrawn';
                                } catch (e) { statusEl.textContent = e.message; }
                            });

                            trBtn.addEventListener('click', async () => {
                                if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                const to = (trTo.value || '').trim();
                                const amt = (trAmount.value || '').trim();
                                if (!to) { statusEl.textContent = 'Enter recipient username'; return; }
                                statusEl.textContent = 'Transferring...';
                                try {
                                    const data = await postJSON('/bank/transfer', { to_username: to, amount: amt });
                                    bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                    bankEl.textContent = fmt(bankCurrent);
                                    statusEl.textContent = `Transferred ${data.transferred_formatted}`;
                                } catch (e) { statusEl.textContent = e.message; }
                            });

                            tokBtn.addEventListener('click', async () => {
                                if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                const color = (tokColor.value || 'red');
                                const qty = Math.max(1, parseInt(tokQty.value||'1',10));
                                statusEl.textContent = 'Exchanging...'; tokResult.textContent = '';
                                try {
                                    const data = await postJSON('/bank/exchange-tokens', { color, qty });
                                    bankCurrent = parseInt((await (await fetch('/bank/data', { headers: { 'Accept':'application/json' } })).json()).balance_seconds || 0, 10);
                                    bankEl.textContent = fmt(bankCurrent);
                                    statusEl.textContent = 'Exchanged';
                                    tokResult.textContent = `Exchanged ${data.exchanged_qty} ${color} token(s); credited ${data.credited_seconds}s`;
                                    await loadTokenBalances();
                                } catch (e) { statusEl.textContent = e.message; }
                            });

                            logoutBtn.addEventListener('click', async () => {
                                try {
                                    await postJSON('/bank/lock', {});
                                } catch {}
                                loggedIn = false;
                                showLogin();
                                statusEl.textContent = 'Bank locked';
                            });

                            setInterval(() => {
                                const now = Date.now();
                                const elapsed = Math.floor((now - last) / 1000);
                                if (elapsed > 0) {
                                    walletCurrent = Math.max(0, walletCurrent - elapsed);
                                    walletEl.textContent = fmt(walletCurrent);
                                    last = now;
                                }
                            }, 1000);

                            loadData();
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
