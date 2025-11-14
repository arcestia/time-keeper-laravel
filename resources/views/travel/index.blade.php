<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Travel</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center">
                        <div class="text-lg font-semibold">The start of your adventure...</div>
                        <div class="mt-1 text-sm text-gray-600">You emerge from the hole that you call your home and set off on your adventure.</div>
                        <div id="travel-xp-boost" class="mt-2 text-xs text-emerald-700 hidden"></div>
                        <button id="travel-step" class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-opacity relative overflow-hidden w-full max-w-md mx-auto">
                            <span>Take a step</span>
                            <span id="step-progress" class="absolute left-0 bottom-0 h-1 bg-purple-500" style="width:0%;opacity:0;"></span>
                        </button>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        (() => {
            const btn = document.getElementById('travel-step');
            const xpBoostEl = document.getElementById('travel-xp-boost');
            const bar = document.getElementById('step-progress');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';

            async function refreshXpBoost() {
                try {
                    const res = await fetch('/api/me/xp-boost', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error();
                    const b = await res.json();
                    const bonus = Number(b.bonus_percent || 0);
                    if (bonus > 0) {
                        const pct = (bonus * 100).toFixed(1);
                        xpBoostEl.textContent = `XP Boost: +${pct}%`;
                        xpBoostEl.classList.remove('hidden');
                    } else {
                        xpBoostEl.textContent = '';
                        xpBoostEl.classList.add('hidden');
                    }
                } catch (e) {}
            }

            refreshXpBoost();

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.classList.add('opacity-50','cursor-not-allowed');
                let rafId = 0; let running = true; const start = performance.now();
                let targetEnd = start + 5000; // default until API returns actual delay
                bar.style.opacity = '1'; bar.style.width = '0%';
                const unlock = () => {
                    setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 350);
                    btn.disabled = false;
                    btn.classList.remove('opacity-50','cursor-not-allowed');
                };
                const stepAnim = () => {
                    if (!running) return;
                    const now = performance.now();
                    const p = Math.min(100, Math.max(0, ((now - start) / (targetEnd - start)) * 100));
                    bar.style.width = p.toFixed(2) + '%';
                    if (now >= targetEnd) {
                        running = false; if (rafId) cancelAnimationFrame(rafId);
                        bar.style.width = '100%';
                        unlock();
                        return;
                    }
                    rafId = requestAnimationFrame(stepAnim);
                };
                rafId = requestAnimationFrame(stepAnim);
                
                try {
                    const res = await fetch('/api/travel/step', {
                        method: 'POST',
                        headers: { 'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                        body: JSON.stringify({})
                    });
                    const d = await res.json().catch(() => ({}));
                    const delaySec = Number(d?.delay_seconds || 0) || 0;
                    if (delaySec > 0) {
                        targetEnd = start + (delaySec * 1000);
                    }
                    const success = (d && d.ok === true) || res.ok;
                    if (!success) {
                        const fallback = `Step failed${res && res.status ? ` (HTTP ${res.status})` : ''}`;
                        const msg = (d && (d.message || d.error)) ? String(d.message || d.error) : fallback;
                        if (window.toastr) toastr.error(msg);
                    } else {
                        const awarded = d && d.awarded ? d.awarded : {};
                        const type = awarded.type || null;
                        const dxp = Number(awarded.xp || 0);
                        const dt = Number(awarded.time_seconds || 0);
                        const di = awarded.item;
                        const tk = awarded.token;

                        let toastMsg = '';
                        if (type === 'xp') {
                            toastMsg = `+${dxp.toLocaleString()} XP`;
                        } else if (type === 'time') {
                            toastMsg = `+${dt.toLocaleString()} sec`;
                        } else if (type === 'item') {
                            if (di) {
                                toastMsg = `+${di.qty}x ${di.name}`;
                            } else {
                                toastMsg = '+Item';
                            }
                        } else if (type === 'token') {
                            if (tk && tk.color) {
                                const colorLabel = String(tk.color).charAt(0).toUpperCase() + String(tk.color).slice(1);
                                const qty = Number(tk.qty || 1);
                                toastMsg = `+${qty}x ${colorLabel} Time Token`;
                            } else {
                                toastMsg = '+Time Token';
                            }
                        } else {
                            // Fallback for older responses without type: show combined
                            toastMsg = `+${dxp.toLocaleString()} XP • +${dt.toLocaleString()} sec${di ? ` • +${di.qty}x ${di.name}` : ''}`;
                        }

                        if (window.toastr && toastMsg) toastr.success(toastMsg);
                        refreshXpBoost();
                    }
                } catch(e) {
                    if (window.toastr) toastr.error('Step failed');
                } finally {
                    // Ensure targetEnd is at most a short time in the future if request failed before delay known
                    const now = performance.now();
                    if (!Number.isFinite(targetEnd) || targetEnd <= now) {
                        targetEnd = now + 300; // quick finish
                    }
                }
            });
        })();
    </script>
</x-app-layout>
