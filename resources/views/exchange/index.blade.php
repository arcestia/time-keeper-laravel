<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Token Exchange</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="text-sm text-gray-600">Market:</div>
                            <div class="inline-flex rounded overflow-hidden border">
                                <button class="px-3 py-1 text-sm ex-color bg-indigo-50 text-indigo-700" data-color="red">Red</button>
                                <button class="px-3 py-1 text-sm ex-color" data-color="blue">Blue</button>
                                <button class="px-3 py-1 text-sm ex-color" data-color="green">Green</button>
                                <button class="px-3 py-1 text-sm ex-color" data-color="yellow">Yellow</button>
                                <button class="px-3 py-1 text-sm ex-color" data-color="black">Black</button>
                                <button class="px-3 py-1 text-sm ex-color" data-color="diamond">Diamond</button>
                            </div>
                        </div>
                        <div id="ex-status" class="text-sm text-gray-500"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1 border rounded p-4">
                            <div class="font-semibold mb-2">Place Order</div>
                            <div class="space-y-3 text-sm">
                                <div class="inline-flex rounded overflow-hidden border">
                                    <button id="btn-side-buy" class="px-3 py-1 bg-emerald-50 text-emerald-700 border-r">Buy</button>
                                    <button id="btn-side-sell" class="px-3 py-1">Sell</button>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Price (seconds per token)</label>
                                    <input id="ex-price" type="number" min="1" class="mt-1 w-full border rounded px-2 py-1" placeholder="e.g. 604800">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Quantity (tokens)</label>
                                    <input id="ex-qty" type="number" min="1" class="mt-1 w-full border rounded px-2 py-1" placeholder="e.g. 10">
                                </div>
                                <div id="ex-preview" class="text-xs text-gray-500">Total: -</div>
                                <div class="flex items-center gap-2">
                                    <button id="ex-submit" class="px-3 py-2 rounded bg-indigo-600 text-white">Submit Order</button>
                                    <button id="ex-buy-best" class="px-3 py-2 rounded border text-sm">Buy Best Ask</button>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold">Bids</div>
                                    <div class="text-xs text-gray-500">Price • Qty</div>
                                </div>
                                <ul id="ex-bids" class="text-sm divide-y"></ul>
                            </div>
                            <div class="border rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold">Asks</div>
                                    <div class="text-xs text-gray-500">Price • Qty</div>
                                </div>
                                <ul id="ex-asks" class="text-sm divide-y"></ul>
                            </div>
                            <div class="md:col-span-2 border rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold">Recent Trades</div>
                                </div>
                                <ul id="ex-trades" class="text-sm divide-y"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border rounded p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-semibold">My Orders</div>
                            <button id="ex-refresh-my" class="text-sm px-2 py-1 border rounded">Refresh</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-600 border-b"><th class="py-1 pr-2">Side</th><th class="py-1 pr-2">Color</th><th class="py-1 pr-2">Price</th><th class="py-1 pr-2">Qty</th><th class="py-1 pr-2">Filled</th><th class="py-1 pr-2">Status</th><th class="py-1">Action</th></tr></thead>
                                <tbody id="ex-my-orders"></tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-1">
                                <div class="font-semibold">My Trades</div>
                                <button id="ex-clear-notifs" class="text-xs px-2 py-1 border rounded">Clear notifications</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead><tr class="text-left text-gray-600 border-b"><th class="py-1 pr-2">Side</th><th class="py-1 pr-2">Color</th><th class="py-1 pr-2">Price</th><th class="py-1 pr-2">Qty</th><th class="py-1 pr-2">Fee</th><th class="py-1 pr-2">Time</th></tr></thead>
                                    <tbody id="ex-my-trades"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
            let color = 'red'; let side = 'buy';
            const statusEl = document.getElementById('ex-status');
            function setStatus(t){ if(statusEl) statusEl.textContent = t||''; }
            // Toast helper
            function showToast(message, type = 'info'){
                let root = document.getElementById('ex-toast-root');
                if (!root) { root = document.createElement('div'); root.id = 'ex-toast-root'; root.className = 'fixed inset-x-0 top-4 flex justify-center z-[60] pointer-events-none'; document.body.appendChild(root); }
                const wrapper = document.createElement('div'); wrapper.className='pointer-events-auto px-4';
                const base='max-w-md w-full rounded-md shadow-lg px-4 py-3 text-sm flex items-start gap-3';
                let color='bg-gray-900 text-white'; if(type==='success') color='bg-emerald-600 text-white'; if(type==='error') color='bg-rose-600 text-white';
                const el=document.createElement('div'); el.className=base+' '+color; el.textContent=message; wrapper.appendChild(el); root.appendChild(wrapper);
                setTimeout(()=>wrapper.remove(), 3000);
            }
            function setActiveColor(btn){ document.querySelectorAll('.ex-color').forEach(x=>x.classList.remove('bg-indigo-50','text-indigo-700')); if(btn){ btn.classList.add('bg-indigo-50','text-indigo-700'); } }
            document.querySelectorAll('.ex-color').forEach(btn=>{
                btn.addEventListener('click', ()=>{ color = btn.getAttribute('data-color')||'red'; setActiveColor(btn); loadBook(); });
            });
            function setSide(s){ side = s; const a=document.getElementById('btn-side-buy'); const b=document.getElementById('btn-side-sell'); if(a&&b){ if(s==='buy'){ a.classList.add('bg-emerald-50','text-emerald-700'); b.classList.remove('bg-rose-50','text-rose-700'); } else { b.classList.add('bg-rose-50','text-rose-700'); a.classList.remove('bg-emerald-50','text-emerald-700'); } } }
            document.getElementById('btn-side-buy')?.addEventListener('click', ()=> setSide('buy'));
            document.getElementById('btn-side-sell')?.addEventListener('click', ()=> setSide('sell'));

            const priceEl = document.getElementById('ex-price'); const qtyEl = document.getElementById('ex-qty'); const previewEl = document.getElementById('ex-preview');
            function updatePreview(){
                const p=parseInt(priceEl.value||'0',10)||0; const q=parseInt(qtyEl.value||'0',10)||0; const total=p*q; const fee=Math.floor(total*0.002);
                if(!previewEl) return;
                if(side==='buy'){
                    previewEl.textContent = `Lock: ${total.toLocaleString()}s • Taker fee (if taker): ${fee.toLocaleString()}s`;
                } else {
                    previewEl.textContent = `Proceeds (if taker): ${(total-fee).toLocaleString()}s • Fee: ${fee.toLocaleString()}s`;
                }
            }
            priceEl?.addEventListener('input', updatePreview); qtyEl?.addEventListener('input', updatePreview);

            document.getElementById('ex-submit')?.addEventListener('click', async ()=>{
                const p = parseInt(priceEl.value||'0',10)||0; const q=parseInt(qtyEl.value||'0',10)||0; if(p<=0||q<=0){ setStatus('Enter valid price and qty'); return; }
                setStatus('Placing order...');
                try{
                    const res = await fetch('/api/exchange/orders', { method:'POST', headers:{ 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify({ side, color, price:p, qty:q }) });
                    const js = await res.json().catch(()=>({}));
                    if (!res.ok || !js.ok){ throw new Error(js.message||'Failed to place order'); }
                    setStatus('Order placed'); priceEl.value=''; qtyEl.value=''; updatePreview();
                    loadBook(); loadMine();
                }catch(err){ setStatus(err && err.message ? err.message : 'Failed to place order'); }
            });

            let lastOrderbook = { bids:[], asks:[] };
            async function loadBook(){
                try{
                    const res = await fetch(`/api/exchange/orderbook?color=${encodeURIComponent(color)}`, { headers:{ 'Accept':'application/json' } });
                    if (!res.ok) throw new Error('failed');
                    const d = await res.json();
                    lastOrderbook = { bids: d.bids||[], asks: d.asks||[] };
                    const bidsUl = document.getElementById('ex-bids'); const asksUl = document.getElementById('ex-asks'); const trUl = document.getElementById('ex-trades');
                    if (bidsUl) { bidsUl.innerHTML=''; (d.bids||[]).forEach(x=>{ const li=document.createElement('li'); li.className='py-1 flex justify-between'; li.innerHTML = `<span class="text-emerald-700">${x.price_per_unit_seconds.toLocaleString()}</span><span>${Number(x.qty||0).toLocaleString()}</span>`; bidsUl.appendChild(li); }); }
                    if (asksUl) {
                        asksUl.innerHTML='';
                        (d.asks||[]).forEach(x=>{
                            const li=document.createElement('li'); li.className='py-1 flex justify-between cursor-pointer hover:bg-rose-50';
                            li.dataset.price = String(x.price_per_unit_seconds||0);
                            li.dataset.qty = String(x.qty||0);
                            li.innerHTML = `<span class="text-rose-700">${x.price_per_unit_seconds.toLocaleString()}</span><span>${Number(x.qty||0).toLocaleString()}</span>`;
                            li.addEventListener('click', ()=>{
                                setSide('buy');
                                if (priceEl) priceEl.value = String(x.price_per_unit_seconds||0);
                                if (qtyEl && Number(qtyEl.value||0)<=0) qtyEl.value = String(Math.max(1, parseInt(x.qty||1,10)));
                                updatePreview();
                            });
                            asksUl.appendChild(li);
                        });
                    }
                    if (trUl) { trUl.innerHTML=''; (d.trades||[]).forEach(t=>{ const li=document.createElement('li'); li.className='py-1 flex justify-between'; li.innerHTML = `<span class="text-gray-600">${t.price_per_unit_seconds.toLocaleString()}</span><span>${Number(t.qty||0).toLocaleString()}</span>`; trUl.appendChild(li); }); }
                    setStatus('');
                }catch(e){ setStatus('Unable to load orderbook'); }
            }

            // Track seen trades to avoid duplicate toasts across polls
            const seenKey = `ex_seen_trades_{{ auth()->id() }}`;
            let seenTrades = new Set();
            try { const raw = sessionStorage.getItem(seenKey); if (raw) { JSON.parse(raw).forEach(id=> seenTrades.add(Number(id)||0)); } } catch {}
            function persistSeen(){ try { const arr=[...seenTrades].slice(-500); sessionStorage.setItem(seenKey, JSON.stringify(arr)); } catch {} }
            function clearSeen(){ try { seenTrades = new Set(); sessionStorage.removeItem(seenKey); } catch {} }

            async function loadMine(showToasts=false){
                try{
                    const res = await fetch('/api/exchange/my', { headers:{ 'Accept':'application/json' } });
                    if(!res.ok) throw new Error('failed');
                    const d = await res.json();
                    const tbody = document.getElementById('ex-my-orders'); tbody.innerHTML='';
                    (d.orders||[]).forEach(o=>{
                        const tr = document.createElement('tr');
                        const canCancel = (o.status==='open' || o.status==='partial');
                        const actionHtml = canCancel ? `<button data-id="${o.id}" class="ex-cancel px-2 py-1 border rounded text-xs">Cancel</button>` : '';
                        tr.innerHTML = `<td class="py-1 pr-2 ${o.side==='buy'?'text-emerald-700':'text-rose-700'}">${o.side}</td><td class="py-1 pr-2">${o.color}</td><td class="py-1 pr-2">${Number(o.price_per_unit_seconds||0).toLocaleString()}</td><td class="py-1 pr-2">${Number(o.qty_total||0).toLocaleString()}</td><td class="py-1 pr-2">${Number(o.qty_filled||0).toLocaleString()}</td><td class="py-1 pr-2">${o.status}</td><td class="py-1">${actionHtml}</td>`;
                        tbody.appendChild(tr);
                    });
                    const tb2 = document.getElementById('ex-my-trades'); tb2.innerHTML='';
                    const trades = Array.isArray(d.trades)?d.trades:[];
                    trades.forEach(t=>{
                        const tr = document.createElement('tr');
                        const sideTxt = (t.taker_user_id==={{ auth()->id() }})? 'taker' : 'maker';
                        tr.innerHTML = `<td class="py-1 pr-2">${sideTxt}</td><td class="py-1 pr-2">${t.color}</td><td class="py-1 pr-2">${Number(t.price_per_unit_seconds||0).toLocaleString()}</td><td class="py-1 pr-2">${Number(t.qty||0).toLocaleString()}</td><td class="py-1 pr-2">${Number(t.fee_seconds||0).toLocaleString()}</td><td class="py-1 pr-2">${(t.created_at||'').toString()}</td>`;
                        tb2.appendChild(tr);
                        const tid = parseInt(t.id,10)||0;
                        if (showToasts && tid>0 && !seenTrades.has(tid)) {
                            const feeTxt = Number(t.fee_seconds||0).toLocaleString();
                            showToast(`Filled ${t.qty} ${t.color} @ ${t.price_per_unit_seconds}s • Fee ${feeTxt}s`, 'success');
                            seenTrades.add(tid); persistSeen();
                        }
                    });
                    document.querySelectorAll('.ex-cancel').forEach(btn=>{
                        btn.addEventListener('click', async ()=>{
                            const id = btn.getAttribute('data-id');
                            try{
                                const res = await fetch(`/api/exchange/orders/${id}/cancel`, { method:'POST', headers:{ 'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } });
                                if(!res.ok) throw new Error('cancel failed');
                                loadBook(); loadMine();
                            }catch{ setStatus('Cancel failed'); }
                        });
                    });
                }catch(e){ /* ignore */ }
            }

            // init
            setSide('buy'); setActiveColor(document.querySelector('.ex-color[data-color="red"]'));
            loadBook(); loadMine();
            document.getElementById('ex-refresh-my')?.addEventListener('click', ()=>{ loadMine(); });
            document.getElementById('ex-clear-notifs')?.addEventListener('click', ()=>{ clearSeen(); showToast('Notifications cleared','success'); });
            const buyBestBtn = document.getElementById('ex-buy-best');
            if (buyBestBtn) buyBestBtn.addEventListener('click', ()=>{
                if (!lastOrderbook.asks || lastOrderbook.asks.length===0){ setStatus('No asks available'); return; }
                const best = lastOrderbook.asks[0];
                setSide('buy'); if (priceEl) priceEl.value = String(best.price_per_unit_seconds||0); if (!qtyEl.value || Number(qtyEl.value)<=0) qtyEl.value='1'; updatePreview();
            });
            setInterval(()=>{ loadBook(); loadMine(true); }, 3000);
        })();
    </script>
</x-app-layout>
