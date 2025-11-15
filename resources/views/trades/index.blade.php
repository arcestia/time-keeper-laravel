<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trades</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2">
                            <div id="trade-create" class="mb-4">
                                <div class="text-sm text-gray-600 mb-2">Start a new trade</div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input id="partner" class="border rounded px-3 py-2 w-64" placeholder="Partner username" />
                                    <button id="createBtn" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded">Create</button>
                                    <div id="createStatus" class="text-sm text-gray-500"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-2">My trades</div>
                            <div id="trade-list" class="border rounded divide-y"></div>
                        </div>
                    </div>

                    <!-- Detail UI moved to /trades/{id} -->

                    <script>
                        (function(){
                            let listTimer = null;
                            function $(id){ return document.getElementById(id); }
                            function badge(text, cls){ return `<span class=\"inline-block px-2 py-0.5 rounded text-xs ${cls}\">${text}</span>`; }
                            function statusBadge(s){
                                if (s==='open') return badge('Open','bg-emerald-100 text-emerald-700');
                                if (s==='finalized') return badge('Finalized','bg-indigo-100 text-indigo-700');
                                return badge('Canceled','bg-rose-100 text-rose-700');
                            }
                            async function list(){
                                const wrap = $('trade-list');
                                if (!wrap) return;
                                const res = await fetch('/api/trades', { headers:{'Accept':'application/json'} });
                                if (!res.ok) return;
                                const data = await res.json();
                                wrap.innerHTML = '';
                                const arr = (data.trades||[]);
                                if (arr.length===0){ wrap.innerHTML = '<div class="p-3 text-xs text-gray-500">No trades yet</div>'; return; }
                                arr.forEach(t=>{
                                    const row = document.createElement('button');
                                    row.type = 'button';
                                    const isCanceled = t.status === 'canceled';
                                    row.className = 'w-full text-left px-3 py-2 flex items-center justify-between ' + (isCanceled ? 'opacity-60 cursor-not-allowed' : 'hover:bg-gray-50');
                                    const partner = t.partner ? `@${t.partner.username}` : 'Unknown';
                                    row.innerHTML = `<div class=\"text-sm\">#${t.id} • ${partner}</div><div>${statusBadge(t.status)}</div>`;
                                    if (!isCanceled) {
                                        row.addEventListener('click', ()=>{ window.location = `/trades/${t.id}`; });
                                    }
                                    wrap.appendChild(row);
                                });
                            }
                            async function create(){
                                const partner = $('partner').value.trim();
                                $('createStatus').textContent = 'Creating...';
                                try{
                                    const res = await fetch('/api/trades/create',{method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content}, body: JSON.stringify({partner_username: partner})});
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message||'Failed');
                                    $('createStatus').textContent='';
                                    window.location = `/trades/${data.id}`;
                                }catch(e){ $('createStatus').textContent = 'Failed'; }
                            }

                            $('createBtn').addEventListener('click', create);

                            // initial lists and polling
                            list();
                            listTimer = setInterval(list, 5000);
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
