<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Token Shop</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-lg font-semibold">Spend Time Tokens</div>
                        <div id="ts-balances" class="text-sm text-gray-600"></div>
                    </div>

                    <div class="space-y-8">
                        <div>
                            <h3 class="text-md font-semibold mb-2">Expedition Slots</h3>
                            <p class="text-xs text-gray-500 mb-2">Black = +1 permanent slot. Yellow = +1 slot &amp; extend duration by 1 year. Max 100 extra slots.</p>
                            <div class="flex flex-wrap gap-3">
                                <button id="btn-slot-black" class="px-3 py-2 rounded bg-gray-900 text-white text-sm">Buy 1 Permanent Slot (1 Black)</button>
                                <button id="btn-slot-yellow" class="px-3 py-2 rounded bg-amber-500 text-white text-sm">Buy 1 Temp Slot (1 Yellow)</button>
                            </div>
                            <div id="ts-slots-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>

                        <div>
                            <h3 class="text-md font-semibold mb-2">XP Boosts</h3>
                            <p class="text-xs text-gray-500 mb-2">Each token adds +2% XP multiplier for a duration based on its time (Red 1w, Blue 1m, Green 1y, Yellow 10y, Black 100y). Multipliers stack and durations extend when you buy again.</p>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <select id="xp-color" class="border rounded px-2 py-1">
                                    <option value="red">Red</option>
                                    <option value="blue">Blue</option>
                                    <option value="green">Green</option>
                                    <option value="yellow">Yellow</option>
                                    <option value="black">Black</option>
                                </select>
                                <input id="xp-qty" type="number" min="1" value="1" class="w-20 border rounded px-2 py-1" />
                                <button id="btn-buy-xp" class="px-3 py-2 rounded bg-indigo-600 text-white">Buy XP</button>
                                <span id="xp-preview" class="text-xs text-gray-500"></span>
                            </div>
                            <div id="ts-xp-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>

                        <div>
                            <h3 class="text-md font-semibold mb-2">Item Chests</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-red-600 mb-1">Red Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Red • Reward: 200–500 of 1 random item.</div>
                                    <button data-color="red" class="btn-chest px-3 py-1 rounded bg-red-600 text-white">Open Red Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-blue-600 mb-1">Blue Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Blue • Reward: 1000–5000 of 1 random item.</div>
                                    <button data-color="blue" class="btn-chest px-3 py-1 rounded bg-blue-600 text-white">Open Blue Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-green-600 mb-1">Green Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Green • Reward: 50,000–150,000 of 1 random item.</div>
                                    <button data-color="green" class="btn-chest px-3 py-1 rounded bg-green-600 text-white">Open Green Chest</button>
                                </div>
                                <div class="border rounded p-3">
                                    <div class="font-semibold text-amber-500 mb-1">Yellow Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Yellow • Reward: 100k of every store item.</div>
                                    <button data-color="yellow" class="btn-chest px-3 py-1 rounded bg-amber-500 text-white">Open Yellow Chest</button>
                                </div>
                                <div class="border rounded p-3 md:col-span-2">
                                    <div class="font-semibold text-gray-900 mb-1">Black Chest</div>
                                    <div class="text-xs text-gray-500 mb-2">Cost: 1 Black • Reward: 2.5m of every store item.</div>
                                    <button data-color="black" class="btn-chest px-3 py-1 rounded bg-gray-900 text-white">Open Black Chest</button>
                                </div>
                            </div>
                            <div id="ts-chest-status" class="mt-2 text-xs text-gray-500"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';

            async function refreshBalances() {
                try {
                    const res = await fetch('/api/token-shop/balances', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    const js = await res.json();
                    const b = js && js.balances ? js.balances : {};
                    const el = document.getElementById('ts-balances');
                    el.textContent = `Red ${b.red ?? 0} • Blue ${b.blue ?? 0} • Green ${b.green ?? 0} • Yellow ${b.yellow ?? 0} • Black ${b.black ?? 0}`;
                } catch {
                    const el = document.getElementById('ts-balances');
                    el.textContent = 'Unable to load balances';
                }
            }

            async function buySlot(color) {
                const statusEl = document.getElementById('ts-slots-status');
                statusEl.textContent = 'Processing...';
                try {
                    const res = await fetch('/api/token-shop/buy-slot', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ color })
                    });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) {
                        throw new Error(js.message || 'Purchase failed');
                    }
                    statusEl.textContent = `Extra slots: permanent ${js.permanent_slots ?? 0}, temp ${js.temp_slots ?? 0}`;
                    refreshBalances();
                } catch (err) {
                    statusEl.textContent = err && err.message ? err.message : 'Purchase failed';
                }
            }

            async function buyXp() {
                const statusEl = document.getElementById('ts-xp-status');
                statusEl.textContent = 'Processing...';
                const color = document.getElementById('xp-color').value;
                const qty = Math.max(1, parseInt(document.getElementById('xp-qty').value, 10) || 1);
                try {
                    const res = await fetch('/api/token-shop/buy-xp', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ color, qty })
                    });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) {
                        throw new Error(js.message || 'Purchase failed');
                    }
                    const added = (js.added_percent ?? 0) * 100;
                    const total = (js.bonus_percent ?? 0) * 100;
                    const until = js.expires_at ? new Date(js.expires_at).toLocaleString() : 'unknown';
                    statusEl.textContent = `XP boost +${added.toFixed(1)}% added (total +${total.toFixed(1)}% XP) until ${until}`;
                    refreshBalances();
                } catch (err) {
                    statusEl.textContent = err && err.message ? err.message : 'Purchase failed';
                }
            }

            async function openChest(color) {
                const statusEl = document.getElementById('ts-chest-status');
                statusEl.textContent = 'Opening chest...';
                try {
                    const res = await fetch('/api/token-shop/open-chest', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ color })
                    });
                    const js = await res.json().catch(() => ({}));
                    if (!res.ok || !js.ok) {
                        throw new Error(js.message || 'Open chest failed');
                    }
                    const loot = Array.isArray(js.loot) ? js.loot : [];
                    if (loot.length === 0) {
                        statusEl.textContent = 'No loot.';
                    } else {
                        const parts = loot.map(x => `${x.name || x.key || 'Item'} x${x.qty || 0}`);
                        statusEl.textContent = `Loot: ${parts.join(', ')}`;
                    }
                    refreshBalances();
                } catch (err) {
                    statusEl.textContent = err && err.message ? err.message : 'Open chest failed';
                }
            }

            document.getElementById('btn-slot-black').addEventListener('click', () => buySlot('black'));
            document.getElementById('btn-slot-yellow').addEventListener('click', () => buySlot('yellow'));
            document.getElementById('btn-buy-xp').addEventListener('click', () => buyXp());
            document.querySelectorAll('.btn-chest').forEach(btn => {
                btn.addEventListener('click', () => openChest(btn.getAttribute('data-color')));
            });

            refreshBalances();
        })();
    </script>
</x-app-layout>
