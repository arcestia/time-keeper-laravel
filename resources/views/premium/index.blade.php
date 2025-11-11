<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Premium</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Your Premium Status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600 mb-2">Status</div>
                            <div id="pm-active" class="text-emerald-700 font-semibold">Loading...</div>
                            <div class="mt-1 text-sm text-gray-600">Tier: <span id="pm-tier">-</span></div>
                            <div class="mt-1 text-sm text-gray-600">Active time: <span id="pm-active-seconds">-</span></div>
                            <div class="mt-1 text-sm text-gray-600">Accumulated: <span id="pm-acc-seconds">-</span></div>
                            <div class="mt-1 text-sm text-gray-600">Heals used this week: <span id="pm-heal-used">-</span></div>
                            <div class="mt-1 text-xs text-gray-500">Resets at: <span id="pm-heal-reset">-</span></div>
                        </div>
                        <div class="p-4 border rounded">
                            <div class="text-sm text-gray-600 mb-2">Benefits</div>
                            <div class="text-sm text-gray-700">Stats cap x<span id="pm-cap">-</span></div>
                            <div class="text-sm text-gray-700">Job rewards x<span id="pm-reward">-</span></div>
                            <div class="text-sm text-gray-700">Store discount <span id="pm-disc">-</span>%</div>
                            <div class="text-sm text-gray-700">Heals/week <span id="pm-heals">-</span></div>
                        </div>
                    </div>

                    <div class="mt-6 p-4 border rounded">
                        <div class="text-sm text-gray-600 mb-2">Buy Premium</div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input id="pm-amount" class="border rounded px-3 py-2 w-64" placeholder="e.g. 1d 2h or 3600" />
                            <button id="pm-buy" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded">Buy</button>
                            <div id="pm-buy-status" class="text-sm text-gray-500"></div>
                        </div>
                    </div>

                    <div class="mt-4 p-4 border rounded">
                        <div class="text-sm text-gray-600 mb-2">Weekly Heal</div>
                        <button id="pm-heal" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded">Heal to Full Health</button>
                        <div id="pm-heal-status" class="mt-2 text-sm text-gray-500"></div>
                    </div>

                    <script>
                        (function(){
                            function fmtHMS(s){ s = parseInt(s,10)||0; const sign=s<0?-1:1; s=Math.abs(s); const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60; return (sign<0?'-':'')+String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0'); }
                            async function load(){
                                const res = await fetch('/api/premium/status', { headers: { 'Accept': 'application/json' } });
                                if (!res.ok) return;
                                const d = await res.json();
                                document.getElementById('pm-active').textContent = d.active ? (d.lifetime ? 'Active (Lifetime)' : 'Active') : 'Inactive';
                                document.getElementById('pm-tier').textContent = d.tier;
                                document.getElementById('pm-active-seconds').textContent = fmtHMS(d.active_seconds);
                                document.getElementById('pm-acc-seconds').textContent = fmtHMS(d.accumulated_seconds);
                                const b = d.benefits || {};
                                document.getElementById('pm-cap').textContent = (b.cap_multiplier||1).toFixed(2);
                                document.getElementById('pm-reward').textContent = (b.reward_multiplier||1).toFixed(2);
                                document.getElementById('pm-disc').textContent = (b.store_discount_pct||0);
                                document.getElementById('pm-heals').textContent = (b.heals_per_week||0);
                                document.getElementById('pm-heal-used').textContent = d.weekly_heal_used;
                                document.getElementById('pm-heal-reset').textContent = d.weekly_heal_reset_at || '-';
                            }
                            load();
                            document.getElementById('pm-buy').addEventListener('click', async () => {
                                const status = document.getElementById('pm-buy-status');
                                const amount = document.getElementById('pm-amount').value.trim();
                                status.textContent = 'Processing...';
                                try {
                                    const res = await fetch('/api/premium/buy', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ amount }) });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message||'Failed');
                                    status.textContent = 'Purchased';
                                    load();
                                } catch(e){ status.textContent = 'Failed'; }
                            });
                            document.getElementById('pm-heal').addEventListener('click', async () => {
                                const status = document.getElementById('pm-heal-status');
                                status.textContent = 'Healing...';
                                try {
                                    const res = await fetch('/api/premium/heal', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message||'Failed');
                                    status.textContent = 'Healed to full health';
                                    load();
                                } catch(e){ status.textContent = 'Failed'; }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
