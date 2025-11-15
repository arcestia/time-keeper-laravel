<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trade #{{ $tradeId }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('trades.page') }}" class="text-sm text-indigo-600 hover:underline">← Back to trades</a>
                    </div>

                    <div id="token-modal" class="hidden fixed inset-0 z-50 bg-black/40">
                        <div class="bg-white w-full max-w-md mx-auto mt-28 rounded shadow">
                            <div class="p-4 border-b flex items-center justify-between"><div class="font-medium">Select Time Tokens</div><button id="tm-close" class="text-sm">✕</button></div>
                            <div class="p-4 space-y-3">
                                <div id="tm-bal" class="text-sm text-gray-600">Loading...</div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm w-20">Color</label>
                                    <select id="tm-color" class="border rounded px-2 py-1 flex-1">
                                        <option value="red">Red</option>
                                        <option value="blue">Blue</option>
                                        <option value="green">Green</option>
                                        <option value="yellow">Yellow</option>
                                        <option value="black">Black</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm w-20">Qty</label>
                                    <input id="tm-qty" type="number" class="border rounded px-2 py-1 w-32" placeholder="0" />
                                </div>
                                <div class="text-right"><button id="tm-add" class="px-3 py-2 bg-indigo-600 text-white rounded">Add</button></div>
                            </div>
                        </div>
                    </div>

                    <div id="trade-ui">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-sm text-gray-600">Trade ID <span id="trade-id">{{ $tradeId }}</span> • Status: <span id="trade-status" class="font-medium">-</span><span id="trade-success-at" class="ml-2 text-xs text-gray-500"></span></div>
                            <div class="flex items-center gap-2">
                                <button id="acceptBtn" class="px-3 py-2 rounded border text-sm">Accept</button>
                                <button id="cancelBtn" class="px-3 py-2 rounded bg-rose-600 text-white text-sm">Cancel</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between mb-2"><div class="font-medium">Your Offer</div><span id="my-accept-badge" class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">-</span></div>
                                <div id="my-lines" class="space-y-2 mb-4"></div>
                                <div id="add-panel" class="border rounded p-3 space-y-3">
                                    <div class="text-sm text-gray-600">Add item from Inventory</div>
                                    <button type="button" id="pick-inv" class="px-3 py-2 rounded border text-sm">Pick from Inventory</button>
                                    <div class="text-sm text-gray-600">Add item from Storage</div>
                                    <button type="button" id="pick-st" class="px-3 py-2 rounded border text-sm">Pick from Storage</button>
                                    <div class="text-sm text-gray-600">Add Time Tokens</div>
                                    <button type="button" id="pick-token" class="px-3 py-2 rounded border text-sm">Pick Time Tokens</button>
                                    <div class="text-sm text-gray-600">Add Time Balance</div>
                                    <div class="flex gap-2">
                                        <select id="bal-src" class="border rounded px-2 py-1">
                                            <option value="bank">Bank</option>
                                            <option value="wallet">Wallet</option>
                                        </select>
                                        <input id="bal-amount" class="border rounded px-2 py-1 w-40" placeholder="e.g. 1d 2h or 3600" />
                                        <button data-type="time_balance" class="add-line px-3 py-1 border rounded text-sm">Add</button>
                                    </div>
                                    <div class="text-xs text-gray-500">Note: 3% fee is paid by sender for time balance lines (recipient receives 100%).</div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2"><div class="font-medium">Partner's Offer</div><span id="their-accept-badge" class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">-</span></div>
                                <div id="their-lines" class="space-y-2 mb-2"></div>
                                <div class="text-xs text-gray-500">Changes by either side reset both accepts.</div>
                            </div>
                        </div>
                    </div>

                    <div id="item-modal" class="hidden fixed inset-0 z-50 bg-black/40">
                        <div class="bg-white w-full max-w-2xl mx-auto mt-20 rounded shadow">
                            <div class="p-4 border-b flex items-center justify-between"><div class="font-medium" id="im-title">Select Items</div><button id="im-close" class="text-sm">✕</button></div>
                            <div class="p-4">
                                <div class="mb-3">
                                    <input id="im-search" class="border rounded px-3 py-2 w-full" placeholder="Search by name or #id (min 2 chars)" />
                                </div>
                                <div id="im-list" class="divide-y max-h-[420px] overflow-y-auto"></div>
                                <div class="mt-3 text-right"><button id="im-add" class="px-3 py-2 bg-indigo-600 text-white rounded">Add Selected</button></div>
                            </div>
                        </div>
                    </div>

                    <script>
                        (function(){
                            let tradeId = {{ $tradeId }}; let pollTimer = null; let myUserId = {{ auth()->id() }};
                            function $(id){ return document.getElementById(id); }
                            function lineHtml(line, names){
                                const id = line.id; const t = line.type; const p = line.payload || {};
                                const nm = names && p.item_id ? (names[String(p.item_id)] || names[p.item_id] || null) : null;
                                const itemLabel = nm ? `${nm} (#${p.item_id})` : `Item #${p.item_id}`;
                                if (t==='item_inventory') return `<div class="flex items-center justify-between border rounded px-2 py-1 text-sm">${itemLabel} × ${p.qty}<button data-id="${id}" class="rm px-2 py-1 text-xs border rounded">Remove</button></div>`;
                                if (t==='item_storage') return `<div class="flex items-center justify-between border rounded px-2 py-1 text-sm">${itemLabel} × ${p.qty}<button data-id="${id}" class="rm px-2 py-1 text-xs border rounded">Remove</button></div>`;
                                if (t==='time_token') return `<div class="flex items-center justify-between border rounded px-2 py-1 text-sm">Token ${p.color} × ${p.qty}<button data-id="${id}" class="rm px-2 py-1 text-xs border rounded">Remove</button></div>`;
                                if (t==='time_balance') return `<div class="flex items-center justify-between border rounded px-2 py-1 text-sm">${p.source} → ${p.amount}<button data-id="${id}" class="rm px-2 py-1 text-xs border rounded">Remove</button></div>`;
                                return `<div class="border rounded px-2 py-1 text-sm">Unknown</div>`;
                            }
                            function setBadge(el, accepted){
                                if (!el) return;
                                if (accepted){ el.textContent='Accepted'; el.className='text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700'; }
                                else { el.textContent='Not accepted'; el.className='text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700'; }
                            }
                            function statusBadge(s){
                                if (s==='open') return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-emerald-100 text-emerald-700">Open</span>';
                                if (s==='finalized') return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-700">Success</span>';
                                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-rose-100 text-rose-700">Canceled</span>';
                            }
                            function fmtDate(iso){
                                try{
                                    const d = new Date(iso);
                                    return d.toLocaleString(undefined,{ year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' });
                                }catch(_){ return iso; }
                            }
                            function openPicker(source){
                                const modal=document.getElementById('item-modal');
                                const title=document.getElementById('im-title');
                                const list=document.getElementById('im-list');
                                const search=document.getElementById('im-search');
                                if (!modal || !title || !list) return;
                                title.textContent = source==='inventory' ? 'Select from Inventory' : 'Select from Storage';
                                modal.classList.remove('hidden');
                                list.innerHTML='<div class="text-xs text-gray-500">Type at least 2 characters to search</div>';
                                // Fetch once
                                fetch('/api/trades/my-items',{headers:{'Accept':'application/json'}})
                                    .then(r=>r.json()).then(d=>{
                                        const dataAll = (source==='inventory'? (d.inventory||[]) : (d.storage||[]));
                                        function render(query){
                                            const q=(query||'').trim().toLowerCase();
                                            list.innerHTML='';
                                            if (q.length<2){ list.innerHTML='<div class="text-xs text-gray-500">Type at least 2 characters to search</div>'; return; }
                                            const results = dataAll.filter(x=>{
                                                const name=(x.name||'').toLowerCase();
                                                return name.includes(q) || (`#${x.item_id}`).includes(q) || String(x.item_id)===q;
                                            }).slice(0,200);
                                            if (results.length===0){ list.innerHTML='<div class="text-xs text-gray-500">No results</div>'; return; }
                                            const frag=document.createDocumentFragment();
                                            results.forEach(row=>{
                                                const line = document.createElement('div');
                                                line.className='flex items-center justify-between py-2';
                                                const left = document.createElement('div'); left.className='text-sm'; left.textContent = `${row.name} (#${row.item_id})`;
                                                const right = document.createElement('div'); right.className='flex items-center gap-2';
                                                const hint = document.createElement('div'); hint.className='text-xs text-gray-500'; hint.textContent = `Have: ${row.qty}`;
                                                const qty = document.createElement('input'); qty.type='number'; qty.className='border rounded px-2 py-1 w-20'; qty.placeholder='Qty'; qty.min='1'; qty.max=String(row.qty);
                                                const chk = document.createElement('input'); chk.type='checkbox'; chk.value=String(row.item_id);
                                                right.appendChild(hint); right.appendChild(qty); right.appendChild(chk);
                                                line.appendChild(left); line.appendChild(right);
                                                frag.appendChild(line);
                                            });
                                            list.appendChild(frag);
                                        }
                                        // Debounced search
                                        let t=null; if (search){ search.value=''; search.oninput=(e)=>{ if(t) clearTimeout(t); t=setTimeout(()=>render(e.target.value), 200); }; }
                                        // First focus to search
                                        if (search) search.focus();
                                        const addBtn = document.getElementById('im-add');
                                        if (addBtn) addBtn.onclick = async ()=>{
                                            const rows = Array.from(list.querySelectorAll('input[type=checkbox]:checked'));
                                            for (const r of rows){
                                                const container = r.closest('div');
                                                const qtyInput = container ? container.querySelector('input[type=number]') : null;
                                                const q = parseInt((qtyInput?.value)||'0',10)||0;
                                                const pid = parseInt(r.value,10)||0;
                                                if (pid>0 && q>0){
                                                    await fetch(`/api/trades/${tradeId}/lines/add`, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content}, body: JSON.stringify({type: source==='inventory'?'item_inventory':'item_storage', payload:{item_id: pid, qty: q}})});
                                                }
                                            }
                                            modal.classList.add('hidden');
                                            load();
                                        };
                                    });
                                const closeBtn = document.getElementById('im-close');
                                if (closeBtn) closeBtn.onclick = (e)=>{ e.preventDefault(); modal.classList.add('hidden'); };
                                modal.addEventListener('click', (e)=>{ if (e.target===modal) { e.preventDefault(); modal.classList.add('hidden'); } });
                            }
                            async function load(){
                                const res = await fetch(`/api/trades/${tradeId}`, { headers:{'Accept':'application/json'} });
                                if (!res.ok) return;
                                const data = await res.json();
                                const tr = data.trade; const partner = data.partner; const names = data.item_names || {};
                                const stEl = $('trade-status'); if (stEl) stEl.innerHTML = statusBadge(tr.status) + (partner? ` • With @${partner.username}`:'');
                                const sucEl = $('trade-success-at');
                                if (sucEl) {
                                    if (tr.status==='finalized' && tr.finalized_at) { sucEl.textContent = `• Succeeded at: ${fmtDate(tr.finalized_at)}`; }
                                    else { sucEl.textContent = ''; }
                                }
                                const a = !!tr.a_accepted, b = !!tr.b_accepted;
                                const mySide = (tr.user_a_id===myUserId?'a': (tr.user_b_id===myUserId?'b':null));
                                const myAccepted = mySide==='a'?a:b; const otherAccepted = mySide==='a'?b:a;
                                // Header accept state text removed per request
                                const myLinesWrap = $('my-lines'); const theirLinesWrap = $('their-lines');
                                myLinesWrap.innerHTML = ''; theirLinesWrap.innerHTML = '';
                                (tr.lines||[]).forEach(l=>{
                                    const isMine = l.side===mySide; const html = lineHtml(l, names);
                                    const div = document.createElement('div'); div.innerHTML = html; const el = div.firstChild;
                                    if (isMine && tr.status==='open'){ const rm = el.querySelector('.rm'); if (rm) rm.addEventListener('click', ()=> removeLine(l.id)); }
                                    else { const rm = el.querySelector('.rm'); if (rm) rm.remove(); }
                                    (isMine?myLinesWrap:theirLinesWrap).appendChild(el);
                                });
                                const open = tr.status==='open';
                                // Toggle accept button label
                                const accBtn = $('acceptBtn');
                                if (accBtn) {
                                    accBtn.textContent = myAccepted ? 'Unaccept' : 'Accept';
                                    accBtn.disabled = !open;
                                    if (!open) accBtn.classList.add('hidden'); else accBtn.classList.remove('hidden');
                                }
                                const cancelBtn = $('cancelBtn');
                                if (cancelBtn) {
                                    cancelBtn.disabled = !open;
                                    if (!open) cancelBtn.classList.add('hidden'); else cancelBtn.classList.remove('hidden');
                                }
                                // Add controls panel visibility
                                const addPanel = $('add-panel'); if (addPanel) { if (!open) addPanel.classList.add('opacity-50','pointer-events-none'); else addPanel.classList.remove('opacity-50','pointer-events-none'); }
                                // badges under columns
                                setBadge($('my-accept-badge'), myAccepted);
                                setBadge($('their-accept-badge'), otherAccepted);
                                if (!open && pollTimer) clearInterval(pollTimer);
                            }
                            async function addLine(type){
                                const payload = {};
                                if (type==='item_inventory'){ payload.item_id = parseInt($('inv-item-id').value||'0',10)||0; payload.qty = parseInt($('inv-qty').value||'0',10)||0; }
                                if (type==='item_storage'){ payload.item_id = parseInt($('st-item-id').value||'0',10)||0; payload.qty = parseInt($('st-qty').value||'0',10)||0; }
                                if (type==='time_token'){ payload.color = $('tok-color').value; payload.qty = parseInt($('tok-qty').value||'0',10)||0; }
                                if (type==='time_balance'){ payload.source = $('bal-src').value; payload.amount = $('bal-amount').value.trim(); }
                                const res = await fetch(`/api/trades/${tradeId}/lines/add`, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({type, payload})});
                                if (res.ok) load();
                            }
                            async function removeLine(lineId){ const res = await fetch(`/api/trades/${tradeId}/lines/remove`, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({line_id: lineId})}); if (res.ok) load(); }
                            async function accept(){ await fetch(`/api/trades/${tradeId}/accept`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}}); load(); }
                            async function unaccept(){ await fetch(`/api/trades/${tradeId}/unaccept`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}}); load(); }
                            async function cancelTrade(){ await fetch(`/api/trades/${tradeId}/cancel`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}}); load(); }
                            async function finalize(){ const res = await fetch(`/api/trades/${tradeId}/finalize`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}}); const d = await res.json().catch(()=>({})); if (!res.ok || !d.ok){ alert(d?.message||'Failed'); } load(); }

                            document.querySelectorAll('.add-line').forEach(btn=> btn.addEventListener('click', (e)=>{ e.preventDefault(); addLine(btn.getAttribute('data-type')); }));
                            const pickInv = $('pick-inv'); if (pickInv) pickInv.addEventListener('click', (e)=>{ e.preventDefault(); openPicker('inventory'); });
                            const pickSt = $('pick-st'); if (pickSt) pickSt.addEventListener('click', (e)=>{ e.preventDefault(); openPicker('storage'); });
                            const pickTok = $('pick-token'); if (pickTok) pickTok.addEventListener('click', async (e)=>{
                                e.preventDefault();
                                const modal = $('token-modal'); const balEl = $('tm-bal'); const colorEl=$('tm-color'); const qtyEl=$('tm-qty');
                                if (!modal) return; modal.classList.remove('hidden'); balEl.textContent='Loading...'; qtyEl.value='';
                                try { const res = await fetch('/api/trades/my-tokens',{headers:{'Accept':'application/json'}}); const d = await res.json(); const b=d.balances||{}; balEl.textContent = `Balances: Red ${b.red||0}, Blue ${b.blue||0}, Green ${b.green||0}, Yellow ${b.yellow||0}, Black ${b.black||0}`; }
                                catch(_) { balEl.textContent='Unable to load balances'; }
                                const addBtn = $('tm-add'); if (addBtn) addBtn.onclick = async ()=>{
                                    const c = colorEl.value; const q = parseInt(qtyEl.value||'0',10)||0; if (q>0){ await fetch(`/api/trades/${tradeId}/lines/add`, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content}, body: JSON.stringify({type:'time_token', payload:{color:c, qty:q}})}); modal.classList.add('hidden'); load(); }
                                };
                                const closeBtn = $('tm-close'); if (closeBtn) closeBtn.onclick = (e)=>{ e.preventDefault(); modal.classList.add('hidden'); };
                                modal.addEventListener('click', (ev)=>{ if (ev.target===modal) { ev.preventDefault(); modal.classList.add('hidden'); } });
                            });
                            const aBtn=$('acceptBtn'); if (aBtn) aBtn.addEventListener('click', async (e)=>{ e.preventDefault(); const txt=aBtn.textContent||''; if (txt.trim().toLowerCase()==='unaccept'){ await fetch(`/api/trades/${tradeId}/unaccept`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content}}); } else { await fetch(`/api/trades/${tradeId}/accept`, {method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content}}); } load(); });
                            const cBtn=$('cancelBtn'); if (cBtn) cBtn.addEventListener('click', (e)=>{ e.preventDefault(); cancelTrade(); });
                            load();
                            pollTimer = setInterval(load, 3000);
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
