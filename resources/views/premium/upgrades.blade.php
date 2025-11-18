<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lifetime Premium Upgrades</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">Use Diamond tokens to permanently upgrade your account.</div>
                        <div id="lt-diamond" class="text-sm"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded p-4">
                            <div class="font-semibold mb-1">Stats Cap</div>
                            <div class="text-sm text-gray-600 mb-2">+50% cap per upgrade, up to 20x overall cap.</div>
                            <div class="text-sm mb-3">Current steps: <span id="lt-cap-steps">0</span></div>
                            <button id="btn-cap" class="px-3 py-2 rounded bg-indigo-600 text-white">Buy (+50%) — 1 Diamond</button>
                        </div>

                        <div class="border rounded p-4">
                            <div class="font-semibold mb-1">Expedition Slots</div>
                            <div class="text-sm text-gray-600 mb-2">Permanent +1 expedition slot per upgrade (max total 250).</div>
                            <div class="text-sm mb-3">Lifetime extra slots: <span id="lt-slots">0</span></div>
                            <button id="btn-slot" class="px-3 py-2 rounded bg-indigo-600 text-white">Buy (+1 slot) — 1 Diamond</button>
                        </div>

                        <div class="md:col-span-2 border rounded p-4">
                            <div class="font-semibold mb-1">Unlimited Energy</div>
                            <div class="text-sm text-gray-600 mb-2">Energy will not be depleted when starting expeditions.</div>
                            <div class="text-sm mb-3">Status: <span id="lt-ue">--</span></div>
                            <button id="btn-ue" class="px-3 py-2 rounded bg-emerald-600 text-white">Buy (Permanent) — 100 Diamonds</button>
                        </div>
                    </div>

                    <div id="lt-status" class="mt-4 text-sm text-gray-500"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const statusEl = document.getElementById('lt-status');
            function setStatus(t){ if(statusEl) statusEl.textContent=t||''; }
            function showToast(message, type='info'){
                let root=document.getElementById('lt-toast-root'); if(!root){ root=document.createElement('div'); root.id='lt-toast-root'; root.className='fixed inset-x-0 top-4 flex justify-center z-[60] pointer-events-none'; document.body.appendChild(root);} const wrap=document.createElement('div'); wrap.className='pointer-events-auto px-4'; const base='max-w-md w-full rounded-md shadow-lg px-4 py-3 text-sm flex items-start gap-3'; let color='bg-gray-900 text-white'; if(type==='success') color='bg-emerald-600 text-white'; if(type==='error') color='bg-rose-600 text-white'; const el=document.createElement('div'); el.className=base+' '+color; el.textContent=message; wrap.appendChild(el); root.appendChild(wrap); setTimeout(()=>wrap.remove(),3000);
            }
            async function fetchJson(url){ const r=await fetch(url,{headers:{'Accept':'application/json'}}); if(!r.ok) throw new Error('Request failed'); return r.json(); }
            async function post(url){ const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')||{}).content || ''}}); if(!r.ok){ let msg='Request failed'; try{ const e=await r.json(); if(e && e.message) msg=e.message; }catch{} throw new Error(msg);} return r.json(); }

            async function refresh(){
                setStatus('Loading...');
                try {
                    const up = await fetchJson('/api/lifetime-upgrades/me');
                    const bal = await fetchJson('/api/token-shop/balances');
                    const b = (bal && bal.balances) ? bal.balances : {};
                    const d = (up && up.upgrades) ? up.upgrades : {};
                    const badge = `\
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200">\
                            <i class="fa-solid fa-gem text-cyan-500 text-[0.6rem]"></i>\
                            <span>Diamond ${(b.diamond||0).toLocaleString()}</span>\
                        </span>`;
                    const dEl = document.getElementById('lt-diamond'); if (dEl) dEl.innerHTML = badge;
                    const s1 = document.getElementById('lt-cap-steps'); if (s1) s1.textContent = (d.stats_cap_steps||0).toString();
                    const s2 = document.getElementById('lt-slots'); if (s2) s2.textContent = (d.extra_expedition_slots||0).toString();
                    const s3 = document.getElementById('lt-ue'); if (s3) s3.textContent = d.unlimited_energy ? 'Enabled' : 'Not purchased';
                    // Disable UE button if already owned
                    const btnUe = document.getElementById('btn-ue'); if (btnUe) btnUe.disabled = !!d.unlimited_energy;
                    setStatus('');
                } catch(e) {
                    setStatus('Unable to load upgrades');
                }
            }

            document.getElementById('btn-cap')?.addEventListener('click', async ()=>{
                setStatus('Purchasing...');
                try{ await post('/api/lifetime-upgrades/stats-cap'); showToast('Stats cap +50% purchased','success'); await refresh(); }catch(e){ setStatus(e.message||'Failed'); showToast(e.message||'Failed','error'); }
            });
            document.getElementById('btn-slot')?.addEventListener('click', async ()=>{
                setStatus('Purchasing...');
                try{ await post('/api/lifetime-upgrades/extra-slot'); showToast('Extra expedition slot purchased','success'); await refresh(); }catch(e){ setStatus(e.message||'Failed'); showToast(e.message||'Failed','error'); }
            });
            document.getElementById('btn-ue')?.addEventListener('click', async ()=>{
                setStatus('Purchasing...');
                try{ await post('/api/lifetime-upgrades/unlimited-energy'); showToast('Unlimited Energy enabled','success'); await refresh(); }catch(e){ setStatus(e.message||'Failed'); showToast(e.message||'Failed','error'); }
            });

            refresh();
        })();
    </script>
</x-app-layout>
