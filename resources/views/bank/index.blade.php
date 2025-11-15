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
                                <div class="flex gap-2 items-center flex-wrap">
                                    <button id="dep-open-btn" class="bg-green-600 text-white px-4 py-2 rounded">Open Deposit</button>
                                    <button data-quick="1d" class="bg-gray-200 px-3 py-2 rounded">Quick +1d</button>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Minimum deposit: 1 day</div>
                            </div>

                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Withdraw</div>
                                <div class="flex gap-2 items-center flex-wrap">
                                    <button id="wd-open-btn" class="bg-rose-600 text-white px-4 py-2 rounded">Open Withdraw</button>
                                </div>
                            </div>

                            <div class="border rounded p-4">
                                <div class="font-semibold mb-2">Transfer</div>
                                <div class="flex gap-2 flex-wrap items-center">
                                    <button id="tr-open-btn" class="bg-blue-600 text-white px-4 py-2 rounded">Open Transfer</button>
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

                    <!-- Deposit Modal -->
                    <div id="dep-modal" class="fixed inset-0 bg-gray-900/40 flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Deposit Time</h3>
                                <button type="button" id="dep-close" class="text-gray-400 hover:text-gray-600">&times;</button>
                            </div>
                            <div class="space-y-3">
                                <p class="text-sm text-gray-600">Enter how much time you want to move from your wallet into the bank.</p>
                                <input id="dep-amount" type="text" placeholder="e.g., 1d 2h" class="border rounded px-3 py-2 w-full" />
                                <p class="text-xs text-gray-500">Minimum deposit: 1 day (86400 seconds).</p>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" id="dep-cancel" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                                <button type="button" id="dep-btn" class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700">Confirm Deposit</button>
                            </div>
                        </div>
                    </div>

                    <!-- Withdraw Modal -->
                    <div id="wd-modal" class="fixed inset-0 bg-gray-900/40 flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Withdraw Time</h3>
                                <button type="button" id="wd-close" class="text-gray-400 hover:text-gray-600">&times;</button>
                            </div>
                            <div class="space-y-3">
                                <p class="text-sm text-gray-600">Enter how much time you want to move from your bank back into your wallet.</p>
                                <input id="wd-amount" type="text" placeholder="e.g., 1h 30m" class="border rounded px-3 py-2 w-full" />
                                <p class="text-xs text-gray-500">You cannot withdraw more than your current bank balance.</p>
                            </div>
                            <div class="mt-6 flex justify-between items-center gap-2">
                                <button type="button" id="wd-all-btn" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Withdraw all</button>
                                <div class="flex gap-2">
                                    <button type="button" id="wd-cancel" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                                    <button type="button" id="wd-btn" class="px-4 py-2 text-sm bg-rose-600 text-white rounded hover:bg-rose-700">Confirm Withdraw</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer Modal -->
                    <div id="tr-modal" class="fixed inset-0 bg-gray-900/40 flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Transfer Time</h3>
                                <button type="button" id="tr-close" class="text-gray-400 hover:text-gray-600">&times;</button>
                            </div>
                            <div class="space-y-3">
                                <p class="text-sm text-gray-600">Send time from your bank balance to another user's bank account.</p>
                                <div class="space-y-2">
                                    <div>
                                        <label for="tr-to" class="block text-xs font-medium text-gray-600 mb-1">Recipient username</label>
                                        <input id="tr-to" type="text" placeholder="e.g., alice" class="border rounded px-3 py-2 w-full" />
                                    </div>
                                    <div>
                                        <label for="tr-amount" class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                                        <input id="tr-amount" type="text" placeholder="e.g., 2h or 1d 3h" class="border rounded px-3 py-2 w-full" />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" id="tr-cancel" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                                <button type="button" id="tr-btn" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Confirm Transfer</button>
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
                            const depOpenBtn = document.getElementById('dep-open-btn');
                            const depModal = document.getElementById('dep-modal');
                            const depClose = document.getElementById('dep-close');
                            const depCancel = document.getElementById('dep-cancel');

                            const wdAmount = document.getElementById('wd-amount');
                            const wdBtn = document.getElementById('wd-btn');
                            const wdAllBtn = document.getElementById('wd-all-btn');
                            const wdOpenBtn = document.getElementById('wd-open-btn');
                            const wdModal = document.getElementById('wd-modal');
                            const wdClose = document.getElementById('wd-close');
                            const wdCancel = document.getElementById('wd-cancel');

                            const trOpenBtn = document.getElementById('tr-open-btn');
                            const trModal = document.getElementById('tr-modal');
                            const trClose = document.getElementById('tr-close');
                            const trCancel = document.getElementById('tr-cancel');
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
                            let transferModalBound = false;

                            function fmt(sec) {
                                sec = Math.max(0, parseInt(sec || 0, 10));
                                const Y = 31536000, W = 604800, D = 86400;
                                const y   = Math.floor(sec / Y);   sec %= Y;
                                const w   = Math.floor(sec / W);   sec %= W;
                                const dd  = Math.floor(sec / D);   sec %= D;
                                const hh  = Math.floor(sec / 3600); sec %= 3600;
                                const mm  = Math.floor(sec / 60);
                                const ss  = sec % 60;
                                return [
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

                            function openModal(modal) {
                                if (!modal) return;
                                modal.classList.remove('hidden');
                            }

                            function closeModal(modal) {
                                if (!modal) return;
                                modal.classList.add('hidden');
                            }

                            function showToast(message, type = 'info') {
                                let root = document.getElementById('bank-toast-root');
                                if (!root) {
                                    root = document.createElement('div');
                                    root.id = 'bank-toast-root';
                                    root.className = 'fixed inset-x-0 top-4 flex justify-center z-[60] pointer-events-none';
                                    document.body.appendChild(root);
                                }
                                const wrapper = document.createElement('div');
                                wrapper.className = 'pointer-events-auto px-4';
                                const base = 'max-w-md w-full rounded-md shadow-lg px-4 py-3 text-sm flex items-start gap-3';
                                let color = 'bg-gray-900 text-white';
                                if (type === 'success') color = 'bg-emerald-600 text-white';
                                if (type === 'error') color = 'bg-rose-600 text-white';
                                const el = document.createElement('div');
                                el.className = base + ' ' + color;
                                el.textContent = message;
                                wrapper.appendChild(el);
                                root.appendChild(wrapper);
                                setTimeout(() => {
                                    wrapper.remove();
                                }, 3000);
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

                            if (setupBtn) setupBtn.addEventListener('click', async () => {
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

                            if (setupInput) setupInput.addEventListener('keydown', (e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    setupBtn.click();
                                }
                            });

                            if (loginBtn) loginBtn.addEventListener('click', async () => {
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

                            if (loginInput) loginInput.addEventListener('keydown', (e) => {
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

                            if (depOpenBtn) {
                                depOpenBtn.addEventListener('click', () => {
                                    if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                    depAmount.value = '';
                                    openModal(depModal);
                                    statusEl.textContent = '';
                                });
                            }

                            if (depClose) depClose.addEventListener('click', () => closeModal(depModal));
                            if (depCancel) depCancel.addEventListener('click', () => closeModal(depModal));

                            if (depBtn) depBtn.addEventListener('click', async () => {
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
                                    statusEl.textContent = '';
                                    closeModal(depModal);
                                    showToast('Deposit successful', 'success');
                                } catch (e) {
                                    statusEl.textContent = e.message;
                                    showToast(e.message, 'error');
                                }
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
                                        statusEl.textContent = '';
                                        showToast('Deposit successful', 'success');
                                    } catch (e) { statusEl.textContent = e.message; showToast(e.message, 'error'); }
                                });
                            });

                            if (wdOpenBtn) {
                                wdOpenBtn.addEventListener('click', () => {
                                    if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                    wdAmount.value = '';
                                    openModal(wdModal);
                                    statusEl.textContent = '';
                                });
                            }

                            if (wdClose) wdClose.addEventListener('click', () => closeModal(wdModal));
                            if (wdCancel) wdCancel.addEventListener('click', () => closeModal(wdModal));

                            if (wdBtn) wdBtn.addEventListener('click', async () => {
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
                                    statusEl.textContent = '';
                                    closeModal(wdModal);
                                    showToast('Withdraw successful', 'success');
                                } catch (e) {
                                    statusEl.textContent = e.message;
                                    showToast(e.message, 'error');
                                }
                            });

                            if (wdAllBtn) {
                                wdAllBtn.addEventListener('click', async () => {
                                    if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                    statusEl.textContent = 'Withdrawing all...';
                                    try {
                                        const data = await postJSON('/bank/withdraw', { amount: 'all' });
                                        bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                        walletCurrent = parseInt(data.wallet_seconds || 0, 10);
                                        bankEl.textContent = fmt(bankCurrent);
                                        walletEl.textContent = fmt(walletCurrent);
                                        last = Date.now();
                                        statusEl.textContent = '';
                                        closeModal(wdModal);
                                        showToast('All funds withdrawn to wallet', 'success');
                                    } catch (e) {
                                        statusEl.textContent = e.message;
                                        showToast(e.message, 'error');
                                    }
                                });
                            }

                            if (trOpenBtn) {
                                trOpenBtn.addEventListener('click', () => {
                                    if (!loggedIn) { statusEl.textContent = 'Login first'; return; }

                                    const modal = document.getElementById('tr-modal');
                                    if (!modal) {
                                        statusEl.textContent = 'Transfer modal not found';
                                        showToast('Transfer modal not found in DOM', 'error');
                                        return;
                                    }
                                    const toInput = document.getElementById('tr-to');
                                    const amtInput = document.getElementById('tr-amount');
                                    const closeBtn = document.getElementById('tr-close');
                                    const cancelBtn = document.getElementById('tr-cancel');
                                    const submitBtn = document.getElementById('tr-btn');

                                    if (toInput) toInput.value = '';
                                    if (amtInput) amtInput.value = '';

                                    if (!transferModalBound) {
                                        if (closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
                                        if (cancelBtn) cancelBtn.addEventListener('click', () => closeModal(modal));
                                        if (submitBtn) submitBtn.addEventListener('click', async () => {
                                            if (!loggedIn) { statusEl.textContent = 'Login first'; return; }
                                            const to = (toInput && toInput.value ? toInput.value : '').trim();
                                            const amt = (amtInput && amtInput.value ? amtInput.value : '').trim();
                                            if (!to) {
                                                statusEl.textContent = 'Enter recipient username';
                                                showToast('Enter recipient username', 'error');
                                                return;
                                            }
                                            statusEl.textContent = 'Transferring...';
                                            try {
                                                const data = await postJSON('/bank/transfer', { to_username: to, amount: amt });
                                                bankCurrent = parseInt(data.balance_seconds || 0, 10);
                                                bankEl.textContent = fmt(bankCurrent);
                                                statusEl.textContent = '';
                                                closeModal(modal);
                                                const msg = data.transferred_formatted ? `Transferred ${data.transferred_formatted}` : 'Transfer successful';
                                                showToast(msg, 'success');
                                            } catch (e) {
                                                statusEl.textContent = e.message;
                                                showToast(e.message, 'error');
                                            }
                                        });
                                        transferModalBound = true;
                                    }

                                    openModal(modal);
                                    statusEl.textContent = '';
                                    showToast('Opening transfer modal…', 'success');
                                });
                            }

                            if (tokBtn) tokBtn.addEventListener('click', async () => {
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

                            if (logoutBtn) logoutBtn.addEventListener('click', async () => {
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
