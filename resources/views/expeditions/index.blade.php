<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Expeditions</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold">Explore & Manage</div>
                        <button id="xp-refresh" class="text-sm text-indigo-600 hover:underline">Refresh</button>
                    </div>

                    <div class="border-b mt-4">
                        <div class="flex items-center gap-3">
                            <button id="tab-catalog" class="px-3 py-2 text-sm font-medium border-b-2 border-indigo-600 text-indigo-700">Catalog</button>
                            <button id="tab-my" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">My Expeditions</button>
                        </div>
                    </div>

                    <div id="panel-catalog" class="mt-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Level:</span>
                            <button data-lvl="0" class="lvl-btn px-2 py-1 rounded border bg-indigo-50 text-indigo-700">All</button>
                            <button data-lvl="1" class="lvl-btn px-2 py-1 rounded border">1</button>
                            <button data-lvl="2" class="lvl-btn px-2 py-1 rounded border">2</button>
                            <button data-lvl="3" class="lvl-btn px-2 py-1 rounded border">3</button>
                            <button data-lvl="4" class="lvl-btn px-2 py-1 rounded border">4</button>
                            <button data-lvl="5" class="lvl-btn px-2 py-1 rounded border">5</button>
                        </div>
                        <div id="cat-status" class="mt-2 text-sm text-gray-500"></div>
                        <div id="cat-list" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                    </div>

                    <div id="panel-my" class="mt-4 hidden">
                        <div class="flex items-center gap-3 text-sm">
                            <button id="tab-pending" class="px-3 py-2 font-medium border-b-2 border-indigo-600 text-indigo-700">Pending (0)</button>
                            <button id="tab-active" class="px-3 py-2 font-medium text-gray-600">Active (0)</button>
                            <button id="tab-completed" class="px-3 py-2 font-medium text-gray-600">Completed (0)</button>
                        </div>
                        <div id="my-status" class="mt-2 text-sm text-gray-500"></div>
                        <div class="mt-3">
                            <ul id="list-pending" class="divide-y"></ul>
                            <ul id="list-active" class="divide-y hidden"></ul>
                            <ul id="list-completed" class="divide-y hidden"></ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';
            const panelCatalog = document.getElementById('panel-catalog');
            const panelMy = document.getElementById('panel-my');
            const tabCatalog = document.getElementById('tab-catalog');
            const tabMy = document.getElementById('tab-my');
            const catList = document.getElementById('cat-list');
            const catStatus = document.getElementById('cat-status');
            const myStatus = document.getElementById('my-status');
            const listPending = document.getElementById('list-pending');
            const listActive = document.getElementById('list-active');
            const listCompleted = document.getElementById('list-completed');
            const tPending = document.getElementById('tab-pending');
            const tActive = document.getElementById('tab-active');
            const tCompleted = document.getElementById('tab-completed');
            document.getElementById('xp-refresh').addEventListener('click', () => { loadCatalog(); loadMy(); });

            function ensureSwal(){
                return new Promise((resolve)=>{
                    if (window.Swal) return resolve();
                    const s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; s.onload=()=>resolve(); document.head.appendChild(s);
                });
            }

            function setTopTab(which){
                if (which==='catalog'){
                    tabCatalog.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    tabMy.classList.remove('border-b-2','border-indigo-600','text-indigo-700'); tabMy.classList.add('text-gray-600');
                    panelCatalog.classList.remove('hidden'); panelMy.classList.add('hidden');
                } else {
                    tabMy.classList.add('border-b-2','border-indigo-600','text-indigo-700');
                    tabCatalog.classList.remove('border-b-2','border-indigo-600','text-indigo-700'); tabCatalog.classList.add('text-gray-600');
                    panelMy.classList.remove('hidden'); panelCatalog.classList.add('hidden');
                }
            }
            tabCatalog.addEventListener('click',()=>setTopTab('catalog'));
            tabMy.addEventListener('click',()=>setTopTab('my'));

            function setMyTab(which){
                const on = (btn, active)=>{ if(active){btn.classList.add('border-b-2','border-indigo-600','text-indigo-700'); btn.classList.remove('text-gray-600');} else { btn.classList.remove('border-b-2','border-indigo-600','text-indigo-700'); btn.classList.add('text-gray-600'); } };
                on(tPending, which==='pending'); on(tActive, which==='active'); on(tCompleted, which==='completed');
                listPending.classList.toggle('hidden', which!=='pending');
                listActive.classList.toggle('hidden', which!=='active');
                listCompleted.classList.toggle('hidden', which!=='completed');
            }
            tPending.addEventListener('click',()=>setMyTab('pending'));
            tActive.addEventListener('click',()=>setMyTab('active'));
            tCompleted.addEventListener('click',()=>setMyTab('completed'));

            let currentLevel = 0;
            document.querySelectorAll('.lvl-btn').forEach(b=>{
                b.addEventListener('click',()=>{
                    document.querySelectorAll('.lvl-btn').forEach(x=>x.classList.remove('bg-indigo-50','text-indigo-700'));
                    b.classList.add('bg-indigo-50','text-indigo-700');
                    currentLevel = parseInt(b.getAttribute('data-lvl'),10) || 0;
                    loadCatalog();
                });
            });

            function fmtHMS(s){ s = parseInt(s,10)||0; const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60; return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`; }

            async function loadCatalog(){
                catList.innerHTML='';
                try{
                    const url = currentLevel>0 ? `/api/expeditions?level=${currentLevel}` : '/api/expeditions';
                    const res = await fetch(url, { headers:{'Accept':'application/json'} });
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    if (!Array.isArray(data) || data.length===0){ catStatus.textContent='No expeditions found'; return; }
                    catStatus.textContent='';
                    for (const e of data){ catList.appendChild(catCard(e)); }
                }catch(e){ catStatus.textContent='Unable to load expeditions'; }
            }

            function catCard(e){
                const div = document.createElement('div'); div.className='p-4 border rounded flex flex-col gap-2';
                const title = document.createElement('div'); title.className='font-medium'; title.textContent = `${e.name} • L${e.level}`; div.appendChild(title);
                const desc = document.createElement('div'); desc.className='text-sm text-gray-600'; desc.textContent = e.description || ''; div.appendChild(desc);
                const meta = document.createElement('div'); meta.className='text-xs text-gray-500'; meta.textContent = `Duration: ${fmtHMS(e.min_duration_seconds)} - ${fmtHMS(e.max_duration_seconds)} • Cost: ${fmtHMS(e.cost_seconds)} • Energy: -${e.energy_cost_pct}%`; div.appendChild(meta);
                const btns = document.createElement('div'); btns.className='mt-1';
                const buyBtn = document.createElement('button'); buyBtn.className='px-3 py-1 rounded bg-indigo-600 text-white'; buyBtn.textContent='Buy';
                buyBtn.addEventListener('click', async ()=>{
                    await ensureSwal();
                    const { isConfirmed, value: src } = await Swal.fire({
                        title:'Buy Expedition',
                        input:'select',
                        inputOptions:{ wallet:'Wallet', bank:'Bank' },
                        inputValue:'wallet',
                        showCancelButton:true,
                        confirmButtonText:'Buy'
                    });
                    if (!isConfirmed) return;
                    try{
                        const res = await fetch(`/api/expeditions/buy/${e.id}?source=${encodeURIComponent(src)}`, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                        if (!res.ok) throw new Error();
                        await loadMy();
                        Swal.fire({ icon:'success', title:'Purchased' });
                    }catch(_){ Swal.fire({ icon:'error', title:'Failed to buy' }); }
                });
                btns.appendChild(buyBtn); div.appendChild(btns);
                return div;
            }

            async function loadMy(){
                listPending.innerHTML=''; listActive.innerHTML=''; listCompleted.innerHTML='';
                try{
                    const res = await fetch('/api/expeditions/my', { headers:{'Accept':'application/json'} });
                    if (!res.ok) throw new Error();
                    const rows = await res.json();
                    let cP=0,cA=0,cC=0;
                    for (const r of rows){
                        const li = document.createElement('li'); li.className='py-3';
                        const name = r.expedition?.name || '(unknown)';
                        const meta = document.createElement('div'); meta.className='text-xs text-gray-500';
                        const dur = r.duration_seconds ? fmtHMS(r.duration_seconds) : '-';
                        meta.textContent = `Duration: ${dur} • Base XP: ${r.base_xp}`;
                        const title = document.createElement('div'); title.className='font-medium'; title.textContent=`${name}`;
                        const actions = document.createElement('div'); actions.className='mt-1 text-sm flex items-center gap-2 flex-wrap';
                        if (r.status==='pending'){
                            const startBtn = document.createElement('button'); startBtn.className='px-2 py-1 rounded border text-gray-700 hover:bg-gray-50'; startBtn.textContent='Start';
                            startBtn.addEventListener('click', async ()=>{
                                try{ const res = await fetch(`/api/expeditions/start/${r.id}`, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } }); if (!res.ok) throw new Error(); await loadMy(); }catch(_){ await ensureSwal(); Swal.fire({icon:'error', title:'Failed to start'}); }
                            });
                            actions.appendChild(startBtn); listPending.appendChild(title); listPending.appendChild(meta); listPending.appendChild(actions);
                            const wrap=document.createElement('div'); wrap.appendChild(title); wrap.appendChild(meta); wrap.appendChild(actions); const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listPending.appendChild(li2); cP++;
                        } else if (r.status==='active'){
                            const ends = r.ends_at ? new Date(r.ends_at) : null;
                            const eta = document.createElement('div'); eta.className='text-xs text-gray-500'; eta.textContent = ends ? (`Ends at: ${ends.toLocaleString()}`) : '';
                            const claimBtn = document.createElement('button'); claimBtn.className='px-2 py-1 rounded border text-gray-700 hover:bg-gray-50'; claimBtn.textContent='Claim';
                            claimBtn.addEventListener('click', async ()=>{
                                try{ const res = await fetch(`/api/expeditions/claim/${r.id}`, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } }); if (!res.ok) throw new Error(); await loadMy(); await ensureSwal(); Swal.fire({icon:'success', title:'Claimed'}); }catch(_){ await ensureSwal(); Swal.fire({icon:'error', title:'Not finished yet'}); }
                            });
                            const wrap=document.createElement('div'); wrap.appendChild(title); wrap.appendChild(meta); wrap.appendChild(eta); wrap.appendChild(actions); actions.appendChild(claimBtn); const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listActive.appendChild(li2); cA++;
                        } else if (r.status==='completed' || r.status==='claimed'){
                            const wrap=document.createElement('div'); wrap.appendChild(title); wrap.appendChild(meta); const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listCompleted.appendChild(li2); cC++;
                        }
                    }
                    tPending.textContent = `Pending (${cP})`; tActive.textContent=`Active (${cA})`; tCompleted.textContent=`Completed (${cC})`;
                    myStatus.textContent='';
                }catch(e){ myStatus.textContent='Unable to load expeditions'; }
            }

            // init
            setTopTab('catalog'); setMyTab('pending');
            loadCatalog(); loadMy();
        })();
    </script>
</x-app-layout>
