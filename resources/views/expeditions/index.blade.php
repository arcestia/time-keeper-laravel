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
                            <span class="text-sm text-gray-600">Choose Level:</span>
                            <button data-lvl="1" class="lvl-btn px-2 py-1 rounded border bg-indigo-50 text-indigo-700">1</button>
                            <button data-lvl="2" class="lvl-btn px-2 py-1 rounded border">2</button>
                            <button data-lvl="3" class="lvl-btn px-2 py-1 rounded border">3</button>
                            <button data-lvl="4" class="lvl-btn px-2 py-1 rounded border">4</button>
                            <button data-lvl="5" class="lvl-btn px-2 py-1 rounded border">5</button>
                        </div>
                        <div id="cat-status" class="mt-2 text-sm text-gray-500"></div>
                        <div class="mt-3 p-4 border rounded">
                            <div id="level-meta" class="text-sm text-gray-700">Level 1 • Duration: - • Cost: - • Energy: -</div>
                            <div class="mt-3 flex items-center gap-3">
                                <label class="text-sm text-gray-600">Quantity</label>
                                <input id="buy-qty" type="number" min="1" max="50" value="1" class="w-24 border rounded px-2 py-1 text-sm" />
                                <button id="buy-level" class="px-3 py-2 rounded bg-indigo-600 text-white">Buy Random Expedition</button>
                            </div>
                        </div>
                    </div>

                    <div id="panel-my" class="mt-4 hidden">
                        <div class="flex items-center gap-3 text-sm">
                            <button id="tab-pending" class="px-3 py-2 font-medium border-b-2 border-indigo-600 text-indigo-700">Pending (0)</button>
                            <button id="tab-active" class="px-3 py-2 font-medium text-gray-600">Active (0)</button>
                            <button id="tab-completed" class="px-3 py-2 font-medium text-gray-600">Completed (0)</button>
                        </div>
                        <div id="pending-level-filter" class="mt-2 hidden">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-600">Level:</span>
                                <button data-plvl="0" class="plvl-btn px-2 py-1 rounded border bg-indigo-50 text-indigo-700">All</button>
                                <button data-plvl="1" class="plvl-btn px-2 py-1 rounded border">1</button>
                                <button data-plvl="2" class="plvl-btn px-2 py-1 rounded border">2</button>
                                <button data-plvl="3" class="plvl-btn px-2 py-1 rounded border">3</button>
                                <button data-plvl="4" class="plvl-btn px-2 py-1 rounded border">4</button>
                                <button data-plvl="5" class="plvl-btn px-2 py-1 rounded border">5</button>
                            </div>
                        </div>
                        <div id="active-row" class="mt-2 hidden flex items-center justify-between">
                            <div id="active-status-filter" class="flex items-center gap-2">
                                <span class="text-xs text-gray-600">Show:</span>
                                <button data-aflt="all" class="aflt-btn px-2 py-1 rounded border bg-indigo-50 text-indigo-700">All</button>
                                <button data-aflt="progress" class="aflt-btn px-2 py-1 rounded border">On-Progress</button>
                                <button data-aflt="finished" class="aflt-btn px-2 py-1 rounded border">Finished</button>
                            </div>
                            <button id="btn-claim-all" class="px-3 py-1 rounded border text-gray-700 hover:bg-gray-50">Claim All</button>
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
            const EXP_CFG = @json(config('expeditions'));
            const panelCatalog = document.getElementById('panel-catalog');
            const panelMy = document.getElementById('panel-my');
            const tabCatalog = document.getElementById('tab-catalog');
            const tabMy = document.getElementById('tab-my');
            const catStatus = document.getElementById('cat-status');
            const buyLevelBtn = document.getElementById('buy-level');
            const levelMeta = document.getElementById('level-meta');
            const buyQty = document.getElementById('buy-qty');
            const myStatus = document.getElementById('my-status');
            const listPending = document.getElementById('list-pending');
            const listActive = document.getElementById('list-active');
            const listCompleted = document.getElementById('list-completed');
            const tPending = document.getElementById('tab-pending');
            const tActive = document.getElementById('tab-active');
            const tCompleted = document.getElementById('tab-completed');
            const pendingFilterWrap = document.getElementById('pending-level-filter');
            const activeFilterWrap = document.getElementById('active-status-filter');
            const activeRow = document.getElementById('active-row');
            let pendingLevel = 0;
            let activeFilter = 'all';
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
                // Show level filter only for pending tab
                if (which==='pending') { pendingFilterWrap.classList.remove('hidden'); }
                else { pendingFilterWrap.classList.add('hidden'); }
                // Show combined active row (filter + claim) only on active tab
                if (which==='active') { activeRow.classList.remove('hidden'); }
                else { activeRow.classList.add('hidden'); }
            }
            tPending.addEventListener('click',()=>setMyTab('pending'));
            tActive.addEventListener('click',()=>setMyTab('active'));
            tCompleted.addEventListener('click',()=>setMyTab('completed'));

            let currentLevel = 1;
            document.querySelectorAll('.lvl-btn').forEach(b=>{
                b.addEventListener('click',()=>{
                    document.querySelectorAll('.lvl-btn').forEach(x=>x.classList.remove('bg-indigo-50','text-indigo-700'));
                    b.classList.add('bg-indigo-50','text-indigo-700');
                    currentLevel = parseInt(b.getAttribute('data-lvl'),10) || 0;
                    loadLevelMeta();
                });
            });

            function fmtHMS(s){ s = parseInt(s,10)||0; const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60; return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`; }
            function badge(text, bg, fg){ const b=document.createElement('span'); b.className=`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium ${bg} ${fg}`; b.textContent=text; return b; }
            function clamp01(x){ return Math.max(0, Math.min(1, x)); }
            function estXp(level, seconds){
                const h = Math.max(1, Math.ceil((parseInt(seconds,10)||0)/3600));
                const lvl = Math.max(1, parseInt(level,10)||1);
                const base = EXP_CFG.xp_per_hour_base ?? 10;
                const perLv = EXP_CFG.xp_per_hour_per_level ?? 1.2;
                const raw = (lvl * (EXP_CFG.xp_per_level ?? 12)) + (h * (base + lvl * perLv));
                const vmin = EXP_CFG.variance_min || 0.9, vmax = Math.max(EXP_CFG.variance_max||1.2, vmin);
                return [Math.floor(raw * vmin), Math.ceil(raw * vmax)];
            }
            function estTime(level, seconds){
                const h = Math.max(1, Math.ceil((parseInt(seconds,10)||0)/3600));
                const raw = (level * (EXP_CFG.time_per_level||36)) + (h * (EXP_CFG.time_per_hour||15));
                const vmin = EXP_CFG.variance_min || 0.9, vmax = Math.max(EXP_CFG.variance_max||1.2, vmin);
                return [Math.floor(raw * vmin), Math.ceil(raw * vmax)];
            }
            function estItemQty(level, seconds){
                const band = (EXP_CFG.level_qty_bands||{})[level] || [1,2];
                const h = Math.max(1, Math.ceil((parseInt(seconds,10)||0)/3600));
                const perHour = EXP_CFG.qty_per_hour || 1;
                const min = Math.min(EXP_CFG.qty_max||16, (band[0]||1) + Math.floor(h * perHour));
                const max = Math.min(EXP_CFG.qty_max||16, (band[1]||2) + Math.floor(h * perHour));
                return [min, Math.max(min, max)];
            }
            function updateProgress(el){
                const start = parseInt(el.getAttribute('data-start')||'0',10)||0;
                const end = parseInt(el.getAttribute('data-end')||'0',10)||0;
                if (!start || !end || end<=start) return;
                const now = Date.now();
                const pct = clamp01((now - start) / (end - start));
                const bar = el.querySelector('.exp-progress-bar');
                const label = el.querySelector('.exp-progress-label');
                if (bar) bar.style.width = (pct*100).toFixed(0) + '%';
                if (label) label.textContent = (pct*100).toFixed(0) + '%';
            }
            function updateAllProgress(){ document.querySelectorAll('.exp-progress').forEach(updateProgress); }

            async function loadLevelMeta(){
                try{
                    const url = `/api/expeditions?level=${currentLevel}`;
                    const res = await fetch(url, { headers:{'Accept':'application/json'} });
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    const e = Array.isArray(data) && data[0] ? data[0] : null;
                    if (!e){ catStatus.textContent='No expeditions found'; levelMeta.textContent = `Level ${currentLevel} • Duration: - • Cost: - • Energy: -`; return; }
                    catStatus.textContent='';
                    const xpMinMax = estXp(currentLevel, e.min_duration_seconds);
                    const xpMaxMax = estXp(currentLevel, e.max_duration_seconds);
                    const tMinMax = estTime(currentLevel, e.min_duration_seconds);
                    const tMaxMax = estTime(currentLevel, e.max_duration_seconds);
                    const qMin = estItemQty(currentLevel, e.min_duration_seconds);
                    const qMax = estItemQty(currentLevel, e.max_duration_seconds);
                    levelMeta.textContent = `Level ${currentLevel} • Duration: ${fmtHMS(e.min_duration_seconds)} - ${fmtHMS(e.max_duration_seconds)} • Cost: ${fmtHMS(e.cost_seconds)} • Energy: -${e.energy_cost_pct}% • Est. XP: ${xpMinMax[0]}–${xpMaxMax[1]} • Est. Time: ${tMinMax[0]}–${tMaxMax[1]} sec • Est. item qty per drop: ${qMin[0]}–${qMax[1]}`;
                }catch(e){ catStatus.textContent='Unable to load expeditions'; }
            }

            buyLevelBtn.addEventListener('click', async ()=>{
                await ensureSwal();
                const { isConfirmed, value: src } = await Swal.fire({
                    title:`Buy Random Expedition (Level ${currentLevel})`,
                    input:'select',
                    inputOptions:{ wallet:'Wallet', bank:'Bank' },
                    inputValue:'wallet',
                    showCancelButton:true,
                    confirmButtonText:'Buy'
                });
                if (!isConfirmed) return;
                try{
                    const qty = Math.max(1, Math.min(50, parseInt(buyQty.value,10)||1));
                    const res = await fetch(`/api/expeditions/buy-level`, { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest'}, body: JSON.stringify({ level: currentLevel, source: src, qty }) });
                    if (!res.ok) throw new Error();
                    await loadMy();
                    setTopTab('my');
                    const r = await res.json();
                    Swal.fire({ icon:'success', title:`Purchased ${r?.count||qty}` });
                }catch(err){ const msg = (err && err.message) ? err.message : 'Failed to buy'; Swal.fire({ icon:'error', title: msg }); }
            });

            async function loadMy(){
                listPending.innerHTML=''; listActive.innerHTML=''; listCompleted.innerHTML='';
                try{
                    const res = await fetch('/api/expeditions/my', { headers:{'Accept':'application/json'} });
                    if (!res.ok) throw new Error();
                    const rows = await res.json();
                    let cP=0,cA=0,cC=0;
                    let finishedCount=0;
                    for (const r of rows){
                        const lvl = r.expedition?.level || 0;
                        const li = document.createElement('li'); li.className='py-3';
                        const name = r.expedition?.name || '(unknown)';
                        const meta = document.createElement('div'); meta.className='text-xs text-gray-500';
                        const dur = r.duration_seconds ? fmtHMS(r.duration_seconds) : '-';
                        // badges row
                        const badges = document.createElement('div'); badges.className='mt-0.5 flex items-center gap-3 text-xs text-gray-600';
                        const lblDur = document.createElement('span'); lblDur.textContent = 'Duration';
                        const lblXp = document.createElement('span'); lblXp.textContent = 'Est. XP';
                        const lblTm = document.createElement('span'); lblTm.textContent = 'Est. Time';
                        const dot = document.createElement('span'); dot.textContent = '•'; dot.className='text-gray-400';
                        badges.appendChild(lblDur);
                        badges.appendChild(badge(`${dur}`,'bg-indigo-100','text-indigo-700'));
                        badges.appendChild(dot);
                        badges.appendChild(lblXp);
                        const xpMM = estXp(lvl, r.duration_seconds||0);
                        badges.appendChild(badge(`${xpMM[0]}–${xpMM[1]}`,'bg-emerald-100','text-emerald-700'));
                        badges.appendChild(dot.cloneNode(true));
                        badges.appendChild(lblTm);
                        const tmMM = estTime(lvl, r.duration_seconds||0);
                        badges.appendChild(badge(`${tmMM[0]}–${tmMM[1]}s`,'bg-amber-100','text-amber-700'));
                        const title = document.createElement('div'); title.className='font-medium'; title.textContent=`${name}`;
                        const actions = document.createElement('div'); actions.className='mt-1 text-sm flex items-center gap-2 flex-wrap';
                        if (r.status==='pending'){
                            if (pendingLevel && lvl !== pendingLevel) { continue; }
                            const startBtn = document.createElement('button'); startBtn.className='px-2 py-1 rounded border text-gray-700 hover:bg-gray-50'; startBtn.textContent='Start';
                            startBtn.addEventListener('click', async ()=>{
                                try{ 
                                    const res = await fetch(`/api/expeditions/start/${r.id}`, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } }); 
                                    if (!res.ok) { const e = await res.json().catch(()=>({})); const msg = e && e.message ? e.message : 'Failed to start'; throw new Error(msg); }
                                    await loadMy(); 
                                }catch(err){ 
                                    await ensureSwal(); 
                                    Swal.fire({icon:'error', title: (err && err.message) ? err.message : 'Failed to start'}); 
                                }
                            });
                            actions.appendChild(startBtn); const wrap=document.createElement('div'); wrap.appendChild(title); wrap.appendChild(badges); wrap.appendChild(actions); const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listPending.appendChild(li2); cP++;
                        } else if (r.status==='active'){
                            const ends = r.ends_at ? new Date(r.ends_at) : null;
                            const eta = document.createElement('div'); eta.className='text-xs text-gray-500'; eta.textContent = ends ? (`Ends at: ${ends.toLocaleString()}`) : '';
                            if (ends && Date.now() >= ends.getTime()) finishedCount++;
                            // filter active
                            const isFinished = !!(ends && Date.now() >= ends.getTime());
                            if (activeFilter==='progress' && isFinished) { continue; }
                            if (activeFilter==='finished' && !isFinished) { continue; }
                            // progress bar
                            const started = r.started_at ? new Date(r.started_at) : null;
                            const progWrap = document.createElement('div'); progWrap.className='mt-1';
                            const progMeta = document.createElement('div'); progMeta.className='flex justify-between text-xs text-gray-600';
                            const progLbl = document.createElement('span'); progLbl.textContent = 'Progress';
                            const progPct = document.createElement('span'); progPct.className='exp-progress-label'; progPct.textContent='0%';
                            progMeta.appendChild(progLbl); progMeta.appendChild(progPct);
                            const progOuter = document.createElement('div'); progOuter.className='w-full bg-gray-200 rounded-full h-2 overflow-hidden';
                            const progInner = document.createElement('div'); progInner.className='exp-progress-bar h-2 bg-gradient-to-r from-indigo-500 to-fuchsia-500'; progInner.style.width='0%';
                            const prog = document.createElement('div'); prog.className='exp-progress'; prog.setAttribute('data-start', started ? started.getTime() : '0'); prog.setAttribute('data-end', ends ? ends.getTime() : '0');
                            progOuter.appendChild(progInner); prog.appendChild(progMeta); prog.appendChild(progOuter); progWrap.appendChild(prog);
                            const claimBtn = document.createElement('button'); claimBtn.className='px-2 py-1 rounded border text-gray-700 hover:bg-gray-50'; claimBtn.textContent='Claim';
                            claimBtn.addEventListener('click', async ()=>{
                                try{ 
                                    const res = await fetch(`/api/expeditions/claim/${r.id}`, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } }); 
                                    if (!res.ok) { const e = await res.json().catch(()=>({})); const msg = e && e.message ? e.message : 'Failed to claim'; throw new Error(msg); }
                                    await loadMy(); await ensureSwal(); Swal.fire({icon:'success', title:'Claimed'}); 
                                }catch(err){ await ensureSwal(); Swal.fire({icon:'error', title: (err && err.message) ? err.message : 'Failed to claim'}); }
                            });
                            const wrap=document.createElement('div'); wrap.appendChild(title); wrap.appendChild(badges); wrap.appendChild(eta); wrap.appendChild(progWrap); wrap.appendChild(actions); actions.appendChild(claimBtn); const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listActive.appendChild(li2); cA++;
                        } else if (r.status==='completed' || r.status==='claimed'){
                            const wrap=document.createElement('div');
                            wrap.appendChild(title);
                            wrap.appendChild(badges);
                            // Loot summary for claimed expeditions
                            if (r.status==='claimed'){
                                const lootArr = Array.isArray(r.loot) ? r.loot : [];
                                const lootDiv = document.createElement('div'); lootDiv.className='text-xs text-gray-600 mt-0.5';
                                if (lootArr.length>0){
                                    const parts = lootArr.map(x => `${x.name || x.key || 'Item'} x${x.qty||1}`);
                                    lootDiv.textContent = `Loot: ${parts.join(', ')}`;
                                } else {
                                    lootDiv.textContent = 'Loot: (none)';
                                }
                                wrap.appendChild(lootDiv);
                            }
                            const li2=document.createElement('li'); li2.className='py-3'; li2.appendChild(wrap); listCompleted.appendChild(li2); cC++;
                        }
                    }
                    tPending.textContent = `Pending (${cP})`; tActive.textContent=`Active (${cA})`; tCompleted.textContent=`Completed (${cC})`;
                    const claimAllBtn = document.getElementById('btn-claim-all');
                    if (claimAllBtn) claimAllBtn.disabled = finishedCount<=0;
                    myStatus.textContent='';
                }catch(e){ myStatus.textContent='Unable to load expeditions'; }
            }

            // Claim all finished
            const claimAllBtn = document.getElementById('btn-claim-all');
            if (claimAllBtn){
                claimAllBtn.addEventListener('click', async ()=>{
                    try{
                        const res = await fetch('/api/expeditions/claim-all', { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN': csrf,'X-Requested-With':'XMLHttpRequest' } });
                        if (!res.ok) { const e = await res.json().catch(()=>({})); const msg = e && e.message ? e.message : 'Failed to claim all'; throw new Error(msg); }
                        const r = await res.json();
                        await ensureSwal();
                        const loot = (r.loot||[]).map(x => `${x.name} x${x.qty}`).join(', ');
                        Swal.fire({ icon:'success', title:`Claimed ${r.claimed} expeditions`, text: `+${r.total_xp} XP${loot? ' • Loot: '+loot:''}` });
                        await loadMy();
                    }catch(err){ await ensureSwal(); Swal.fire({icon:'error', title: (err && err.message) ? err.message : 'Failed to claim all'}); }
                });
            }

            // Pending-level filters behavior
            pendingFilterWrap.addEventListener('click', (ev) => {
                const btn = ev.target.closest('.plvl-btn'); if (!btn) return;
                pendingLevel = parseInt(btn.getAttribute('data-plvl'),10)||0;
                document.querySelectorAll('.plvl-btn').forEach(x=>x.classList.remove('bg-indigo-50','text-indigo-700'));
                btn.classList.add('bg-indigo-50','text-indigo-700');
                loadMy();
            });

            // Active status filter behavior
            activeFilterWrap.addEventListener('click', (ev) => {
                const btn = ev.target.closest('.aflt-btn'); if (!btn) return;
                activeFilter = btn.getAttribute('data-aflt') || 'all';
                document.querySelectorAll('.aflt-btn').forEach(x=>x.classList.remove('bg-indigo-50','text-indigo-700'));
                btn.classList.add('bg-indigo-50','text-indigo-700');
                loadMy();
            });

            // init
            setTopTab('catalog'); setMyTab('pending');
            loadLevelMeta(); loadMy();
            setInterval(updateAllProgress, 1000);
        })();
    </script>
</x-app-layout>
