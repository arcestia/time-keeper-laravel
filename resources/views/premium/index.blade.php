<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Premium</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Your Premium</h3>
                    <div id="pm-landing" class="p-6 border rounded-lg hidden">
                        <div class="text-xl font-semibold mb-2">Unlock Premium</div>
                        <p class="text-gray-600 mb-4">Get higher stat caps, more job rewards, store discounts, weekly heals, and access to premium jobs.</p>
                        <div class="flex items-center gap-2">
                            <button id="pm-join-btn" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded">Join Premium</button>
                            <button id="pm-tiers-btn" type="button" class="px-3 py-2 border rounded text-sm">View Tiers</button>
                        </div>
                    </div>

                    <div id="pm-active-ui" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded border bg-gradient-to-r from-indigo-50 to-fuchsia-50">
                            <div class="text-sm text-gray-700 mb-2">Status</div>
                            <div class="flex items-center gap-2">
                                <span id="pm-active" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Loading...</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Tier <span id="pm-tier" class="ml-1">-</span> <i id="pm-tier-star" class="fa-solid fa-star ml-1 text-gray-300"></i></span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600">Active time: <span id="pm-active-seconds">-</span></div>
                            <div class="mt-1 text-sm text-gray-600">Accumulated: <span id="pm-acc-seconds">-</span></div>
                            <div class="mt-1 text-sm text-gray-600">Heals used this week: <span id="pm-heal-used">-</span></div>
                            <div class="mt-1 text-xs text-gray-500">Resets at: <span id="pm-heal-reset">-</span></div>
                            <div id="pm-progress-wrap" class="mt-4 hidden">
                                <div class="flex justify-between text-xs text-gray-700 mb-1"><span>Progress to next tier (<span id="pm-next-tier">-</span>)</span><span id="pm-progress-pct">0%</span></div>
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden"><div id="pm-progress-bar" class="h-2 bg-gradient-to-r from-indigo-500 to-fuchsia-500" style="width:0%"></div></div>
                            </div>
                        </div>
                        <div class="p-4 border rounded bg-white">
                            <div class="text-sm text-gray-600 mb-2">Benefits</div>
                            <div class="text-sm text-gray-700">Stats cap x<span id="pm-cap">-</span></div>
                            <div class="text-sm text-gray-700">Job rewards x<span id="pm-reward">-</span></div>
                            <div class="text-sm text-gray-700">XP gain x<span id="pm-xp">-</span></div>
                            <div class="text-sm text-gray-700">Expedition slots <span id="pm-exp-slots">-</span></div>
                            <div class="text-sm text-gray-700">Store discount <span id="pm-disc">-</span>%</div>
                            <div class="text-sm text-gray-700">Heals/week <span id="pm-heals">-</span></div>
                            <div class="mt-3"><button id="pm-tiers-btn-active" type="button" class="px-3 py-2 border rounded text-sm">View Tiers</button></div>
                        </div>
                    </div>

                    <div id="pm-buy-card" class="mt-6 p-4 border rounded bg-white">
                        <div class="text-sm text-gray-600 mb-2">Buy Premium</div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input id="pm-amount" class="border rounded px-3 py-2 w-64" placeholder="e.g. 1d 2h or 3600" />
                            <button id="pm-buy" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded">Buy</button>
                            <div id="pm-buy-status" class="text-sm text-gray-500"></div>
                        </div>
                        <div class="mt-2 flex items-center gap-2 text-xs">
                            <span class="text-gray-500">Quick:</span>
                            <button data-preset="1h" class="px-2 py-1 rounded border text-gray-700 hover:bg-gray-50">1h</button>
                            <button data-preset="1d" class="px-2 py-1 rounded border text-gray-700 hover:bg-gray-50">1d</button>
                            <button data-preset="7d" class="px-2 py-1 rounded border text-gray-700 hover:bg-gray-50">7d</button>
                        </div>
                        <div class="mt-3 text-sm">
                            <div class="text-gray-600 mb-1">Pay from</div>
                            <div class="flex items-center gap-6">
                                <label class="inline-flex items-center gap-2 text-gray-700">
                                    <input type="radio" name="pm-src" id="pm-src-bank" value="bank" checked>
                                    <span>Bank</span>
                                    <span class="text-xs text-gray-500">(<span id="pm-bank-balance">-</span>)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-gray-700">
                                    <input type="radio" name="pm-src" id="pm-src-wallet" value="wallet">
                                    <span>Wallet</span>
                                    <span class="text-xs text-gray-500">(<span id="pm-wallet-balance">-</span>)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="pm-heal-card" class="mt-4 p-4 border rounded">
                        <div class="text-sm text-gray-600 mb-2">Weekly Heal</div>
                        <button id="pm-heal" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded">Heal to Full Health</button>
                        <div id="pm-heal-status" class="mt-2 text-sm text-gray-500"></div>
                    </div>

                    <script>
                        (function(){
                            function fmtHMS(s){ s = parseInt(s,10)||0; const sign=s<0?-1:1; s=Math.abs(s); const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60; return (sign<0?'-':'')+String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0'); }
                            function tierStarColor(t){ if (t>=20) return 'text-fuchsia-500'; if (t>=15) return 'text-sky-500'; if (t>=10) return 'text-amber-500'; if (t>=5) return 'text-slate-500'; if (t>=1) return 'text-orange-500'; return 'text-gray-300'; }
                            function benefitsForTier(t){
                                const steps=19, pos=(t-1);
                                const capMin=1.20, capMax=11.00;
                                const rewardMin=1.05, rewardMax=2.50;
                                const xpMin=1.05, xpMax=3.00;
                                const discMin=1, discMax=30;
                                const cap=(capMin + (capMax-capMin)*(pos/steps));
                                const reward=(rewardMin + (rewardMax-rewardMin)*(pos/steps));
                                const xp=(xpMin + (xpMax-xpMin)*(pos/steps));
                                const discount=Math.round(discMin + (discMax-discMin)*(pos/steps));
                                let heals=0; if (t>=5){ if (t>=17) heals=5; else if (t>=14) heals=4; else if (t>=11) heals=3; else if (t>=8) heals=2; else heals=1; }
                                // expedition slots: base 1, extra scales from tier 5 (+1) to tier 20 (+10)
                                let extra=0, total=1;
                                if (t>=5){ const slotsMin=1, slotsMax=10, slotSteps=15, slotPos=t-5; extra = Math.max(slotsMin, Math.min(slotsMax, Math.floor(slotsMin + (slotsMax-slotsMin)*(slotPos/slotSteps)))); total = 1 + extra; }
                                return {cap,reward,xp,discount,heals,exp_slots: total, exp_extra: extra};
                            }
                            function renderTiersTable(){
                                let rows='';
                                for (let i=1;i<=20;i++){
                                    const b=benefitsForTier(i);
                                    rows += `<tr><td class=\"px-3 py-1 text-left\">${i}</td><td class=\"px-3 py-1\">x${b.cap.toFixed(2)}</td><td class=\"px-3 py-1\">x${b.reward.toFixed(2)}</td><td class=\"px-3 py-1\">x${b.xp.toFixed(2)}</td><td class=\"px-3 py-1\">${b.exp_slots} ( +${b.exp_extra})</td><td class=\"px-3 py-1\">${b.discount}%</td><td class=\"px-3 py-1\">${b.heals}</td></tr>`;
                                }
                                return `<div class=\"overflow-x-auto\"><table class=\"min-w-full text-sm\"><thead><tr class=\"border-b\"><th class=\"px-3 py-1 text-left\">Tier</th><th class=\"px-3 py-1\">Stats Cap</th><th class=\"px-3 py-1\">Job Reward</th><th class=\"px-3 py-1\">XP Gain</th><th class=\"px-3 py-1\">Exp Slots</th><th class=\"px-3 py-1\">Store Disc</th><th class=\"px-3 py-1\">Heals/Wk</th></tr></thead><tbody>${rows}</tbody></table></div>`;
                            }
                            let pmRemaining = 0; let pmActive = false; let pmLifetime = false;
                            async function load(){
                                const res = await fetch('/api/premium/status', { headers: { 'Accept': 'application/json' } });
                                if (!res.ok) return;
                                const d = await res.json();
                                const activeUI = document.getElementById('pm-active-ui');
                                const landing = document.getElementById('pm-landing');
                                const buyCard = document.getElementById('pm-buy-card');
                                const healCard = document.getElementById('pm-heal-card');
                                if (!d.active) {
                                    activeUI.classList.add('hidden');
                                    landing.classList.remove('hidden');
                                    document.getElementById('pm-join-btn').textContent = (d.accumulated_seconds>0) ? 'Renew Premium' : 'Join Premium';
                                    buyCard.classList.add('hidden');
                                    healCard.classList.add('hidden');
                                } else {
                                    activeUI.classList.remove('hidden');
                                    landing.classList.add('hidden');
                                    buyCard.classList.remove('hidden');
                                    healCard.classList.remove('hidden');
                                }

                                const actEl = document.getElementById('pm-active');
                                actEl.textContent = d.active ? (d.lifetime ? 'Active (Lifetime)' : 'Active') : 'Inactive';
                                actEl.className = `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${d.active? 'bg-emerald-100 text-emerald-700':'bg-gray-100 text-gray-700'}`;
                                document.getElementById('pm-tier').textContent = d.tier;
                                const st = document.getElementById('pm-tier-star'); st.className = `fa-solid fa-star ml-1 ${tierStarColor(d.tier)}`;
                                document.getElementById('pm-active-seconds').textContent = fmtHMS(d.active_seconds);
                                document.getElementById('pm-acc-seconds').textContent = fmtHMS(d.accumulated_seconds);
                                const b = d.benefits || {};
                                document.getElementById('pm-cap').textContent = (b.cap_multiplier||1).toFixed(2);
                                document.getElementById('pm-reward').textContent = (b.reward_multiplier||1).toFixed(2);
                                document.getElementById('pm-disc').textContent = (b.store_discount_pct||0);
                                document.getElementById('pm-xp').textContent = (b.xp_multiplier||1).toFixed(2);
                                const slots = (b.expedition_total_slots||1), extra = Math.max(0, (b.expedition_extra_slots||0));
                                document.getElementById('pm-exp-slots').textContent = `${slots} ( +${extra})`;
                                document.getElementById('pm-heals').textContent = (b.heals_per_week||0);
                                document.getElementById('pm-heal-used').textContent = d.weekly_heal_used;
                                document.getElementById('pm-heal-reset').textContent = d.weekly_heal_reset_at || '-';
                                // Progress to next tier
                                const pw = document.getElementById('pm-progress-wrap');
                                if (d.next_tier && typeof d.progress_to_next === 'number') {
                                    const pct = Math.round(d.progress_to_next * 100);
                                    document.getElementById('pm-next-tier').textContent = d.next_tier;
                                    document.getElementById('pm-progress-pct').textContent = pct + '%';
                                    document.getElementById('pm-progress-bar').style.width = pct + '%';
                                    pw.classList.remove('hidden');
                                } else {
                                    pw.classList.add('hidden');
                                }
                                pmActive = !!d.active; pmLifetime = !!d.lifetime; pmRemaining = parseInt(d.active_seconds||0,10)||0;
                            }
                            load();
                            function getSource(){
                                // Prefer landing selection if visible
                                const landing = document.getElementById('pm-landing');
                                const bL=document.getElementById('pm-src-bank-landing');
                                const wL=document.getElementById('pm-src-wallet-landing');
                                if (landing && !landing.classList.contains('hidden')){
                                    if (wL && wL.checked) return 'wallet';
                                    if (bL && bL.checked) return 'bank';
                                }
                                // Fallback to active buy card
                                const bA=document.getElementById('pm-src-bank');
                                const wA=document.getElementById('pm-src-wallet');
                                if (wA && wA.checked) return 'wallet';
                                if (bA && bA.checked) return 'bank';
                                // Final fallback
                                return 'bank';
                            }
                            // Buy button uses preview+confirm
                            document.getElementById('pm-buy').addEventListener('click', async () => {
                                const status = document.getElementById('pm-buy-status');
                                const amount = document.getElementById('pm-amount').value.trim();
                                const source = getSource();
                                status.textContent = 'Checking...';
                                try {
                                    const prev = await fetch('/api/premium/preview', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ amount, source }) });
                                    const p = await prev.json();
                                    if (!prev.ok || !p.ok) throw new Error(p.message||'Invalid amount');
                                    let proceed = true;
                                    document.getElementById('pm-bank-balance').textContent = (p.bank_seconds||0).toLocaleString()+' sec';
                                    document.getElementById('pm-wallet-balance').textContent = (p.wallet_seconds||0).toLocaleString()+' sec';
                                    const label = (p.source==='wallet'?'Wallet':'Bank');
                                    const html = `Duration: <strong>${amount}</strong><br>Premium seconds: <strong>${p.seconds}</strong><br>Cost (${label}): <strong>${p.cost_seconds} sec</strong><br>Bank balance: <strong>${p.bank_seconds} sec</strong><br>Wallet balance: <strong>${p.wallet_seconds} sec</strong>`;
                                    if (window.Swal){
                                        const { isConfirmed } = await Swal.fire({ title: 'Confirm Purchase', html, icon: (p.can_afford?'question':'warning'), showCancelButton: true, confirmButtonText: p.can_afford ? 'Buy' : 'Buy Anyway' });
                                        proceed = isConfirmed;
                                    } else {
                                        proceed = window.confirm(`Confirm purchase?\n${amount} (${p.seconds}s)\nCost: ${p.cost_seconds}s from ${label}`);
                                    }
                                    if (!proceed) { status.textContent = ''; return; }
                                    status.textContent = 'Processing...';
                                    const res = await fetch('/api/premium/buy', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ amount, source }) });
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

                            // Landing: Join/Renew button → SweetAlert buy modal
                            const joinBtn = document.getElementById('pm-join-btn');
                            joinBtn.addEventListener('click', async () => {
                                let amount = '';
                                let source = getSource();
                                if (window.Swal) {
                                    const html = `
                                        <div class=\"text-left\">
                                            <label class=\"block text-sm text-gray-700 mb-1\">Enter duration</label>
                                            <input id=\"swal-amount\" type=\"text\" class=\"swal2-input\" placeholder=\"e.g. 1d 2h or 3600\" style=\"width: calc(100% - 2em)\" />
                                            <div class=\"mt-2 text-sm\">Pay from</div>
                                            <div class=\"flex items-center gap-6 mt-1\">
                                                <label class=\"inline-flex items-center gap-2\"><input type=\"radio\" name=\"swal-src\" id=\"swal-src-bank\" value=\"bank\" ${source==='bank'?'checked':''}> Bank</label>
                                                <label class=\"inline-flex items-center gap-2\"><input type=\"radio\" name=\"swal-src\" id=\"swal-src-wallet\" value=\"wallet\" ${source==='wallet'?'checked':''}> Wallet</label>
                                            </div>
                                        </div>`;
                                    const res = await Swal.fire({ title: 'Purchase Premium', html, focusConfirm: false, showCancelButton: true, confirmButtonText: 'Buy', didOpen: () => { const inp = document.getElementById('swal-amount'); if (inp) inp.focus(); } });
                                    if (!res.isConfirmed) return;
                                    amount = (document.getElementById('swal-amount')?.value || '').trim();
                                    source = (document.getElementById('swal-src-wallet')?.checked ? 'wallet' : 'bank');
                                } else {
                                    amount = window.prompt('Enter premium duration (e.g. 1d 2h or 3600)') || '';
                                }
                                if (!amount) return;
                                const status = document.getElementById('pm-buy-status');
                                status.textContent = 'Checking...';
                                try {
                                    // Use selection from modal if provided; otherwise fallback
                                    source = source || getSource();
                                    const prev = await fetch('/api/premium/preview', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ amount, source }) });
                                    const p = await prev.json();
                                    if (!prev.ok || !p.ok) throw new Error(p.message||'Invalid amount');
                                    let proceed = true;
                                    document.getElementById('pm-bank-balance').textContent = (p.bank_seconds||0).toLocaleString()+' sec';
                                    document.getElementById('pm-wallet-balance').textContent = (p.wallet_seconds||0).toLocaleString()+' sec';
                                    const label = (p.source==='wallet'?'Wallet':'Bank');
                                    const html = `Duration: <strong>${amount}</strong><br>Premium seconds: <strong>${p.seconds}</strong><br>Cost (${label}): <strong>${p.cost_seconds} sec</strong><br>Bank balance: <strong>${p.bank_seconds} sec</strong><br>Wallet balance: <strong>${p.wallet_seconds} sec</strong>`;
                                    if (window.Swal){
                                        const { isConfirmed } = await Swal.fire({ title: 'Confirm Purchase', html, icon: (p.can_afford?'question':'warning'), showCancelButton: true, confirmButtonText: p.can_afford ? 'Buy' : 'Buy Anyway' });
                                        proceed = isConfirmed;
                                    } else {
                                        proceed = window.confirm(`Confirm purchase?\n${amount} (${p.seconds}s)\nCost: ${p.cost_seconds}s from ${label}`);
                                    }
                                    if (!proceed) { status.textContent = ''; return; }
                                    status.textContent = 'Processing...';
                                    const res = await fetch('/api/premium/buy', { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ amount, source }) });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.message||'Failed');
                                    status.textContent = 'Purchased';
                                    load();
                                } catch(e){ status.textContent = 'Failed'; }
                            });

                            // View tiers modal
                            const tiersBtn = document.getElementById('pm-tiers-btn');
                            if (tiersBtn) tiersBtn.addEventListener('click', async () => {
                                const html = renderTiersTable();
                                if (window.Swal) {
                                    await Swal.fire({ title: 'Premium Tiers & Benefits', html, width: 700 });
                                } else {
                                    const w = window.open('', '_blank'); if (w){ w.document.write(html); w.document.close(); }
                                }
                            });
                            const tiersBtnActive = document.getElementById('pm-tiers-btn-active');
                            if (tiersBtnActive) tiersBtnActive.addEventListener('click', async () => {
                                const html = renderTiersTable();
                                if (window.Swal) {
                                    await Swal.fire({ title: 'Premium Tiers & Benefits', html, width: 700 });
                                } else {
                                    const w = window.open('', '_blank'); if (w){ w.document.write(html); w.document.close(); }
                                }
                            });

                            // Live tick for active time
                            setInterval(() => {
                                if (pmActive && !pmLifetime && pmRemaining > 0) {
                                    pmRemaining = Math.max(0, pmRemaining - 1);
                                    document.getElementById('pm-active-seconds').textContent = fmtHMS(pmRemaining);
                                }
                            }, 1000);
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
