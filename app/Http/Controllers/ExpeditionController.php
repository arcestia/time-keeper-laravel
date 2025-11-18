<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use App\Models\Expedition;
use App\Models\UserExpedition;
use App\Models\UserExpeditionUpgrade;
use App\Models\UserStats;
use App\Models\UserTimeWallet;
use App\Models\TimeAccount;
use App\Models\StoreItem;
use App\Models\UserStorageItem;
use App\Models\GuildMember;
use App\Services\PremiumService;
use App\Services\ProgressService;
use App\Services\ExpeditionMasteryService;
use App\Services\GuildLevelService;
use Flasher\Laravel\Facade\Flasher;
use App\Jobs\ClaimAllExpeditionsJob;

class ExpeditionController extends Controller
{
    public function page()
    {
        return view('expeditions.index');
    }

    public function claimAllStart(): JsonResponse
    {
        $user = Auth::user();
        $limit = max(50, min(1000, (int) request('limit', 300)));
        // reset progress cache
        Cache::forget('claim_all_progress:'.$user->id);
        dispatch(new ClaimAllExpeditionsJob($user->id, $limit));
        return response()->json(['ok'=>true,'started'=>true,'limit'=>$limit]);
    }

    public function claimAllStatus(): JsonResponse
    {
        $user = Auth::user();
        $state = Cache::get('claim_all_progress:'.$user->id);
        if (!$state) {
            // if no state and no remaining, return done
            $remaining = UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])
                ->whereNotNull('ends_at')
                ->where('ends_at','<=',now())
                ->limit(1)->count();
            return response()->json(['ok'=>true,'status'=> $remaining>0 ? 'idle' : 'done','claimed'=>0,'total_xp'=>0,'total_guild_xp'=>0,'loot'=>[],'remaining'=>$remaining]);
        }
        return response()->json(['ok'=>true] + $state);
    }

    public function startAllByLevel(Request $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validate([
            'level' => ['required','integer','min:0','max:50'],
        ]);
        $level = (int)$data['level'];
        $now = now();

        return DB::transaction(function() use($user,$level,$now){
            // compute allowed slots: base from premium tier (or 1), plus mastery extras, lifetime extras, and token shop upgrades
            $prem = \App\Services\PremiumService::getOrCreate($user->id);
            $baseSlots = 1;
            if (\App\Services\PremiumService::isActive($prem)) {
                $tier = \App\Services\PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = \App\Services\PremiumService::benefitsForTier($tier);
                $baseSlots = max(1, (int)($benefits['expedition_total_slots'] ?? 1));
            }
            $allowed = (int)$baseSlots;
            $mastery = app(\App\Services\ExpeditionMasteryService::class)->getOrCreate($user->id);
            $mBonuses = app(\App\Services\ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
            $allowed += (int)($mBonuses['expedition_extra_slots'] ?? 0);
            $allowed += (int) \App\Services\PremiumService::lifetimeExtraSlotsForUser($user->id);
            $upgrade = \App\Models\UserExpeditionUpgrade::query()->where('user_id',$user->id)->first();
            if ($upgrade) {
                $extraPerm = (int)$upgrade->permanent_slots + (int)($upgrade->admin_permanent_slots ?? 0);
                $extraTemp = 0;
                if ($upgrade->temp_expires_at && $upgrade->temp_expires_at->gt($now)) {
                    $extraTemp = (int)$upgrade->temp_slots;
                }
                $allowed += max(0, $extraPerm + $extraTemp);
            }
            $activeCount = (int) \App\Models\UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])->lockForUpdate()->count();
            $remaining = max(0, (int)$allowed - $activeCount);
            if ($remaining <= 0) {
                return response()->json(['ok'=>true,'started'=>0,'message'=>'No free slots']);
            }

            // select pending by requested level (0=any) up to remaining
            $query = \App\Models\UserExpedition::query()
                ->where(['user_id'=>$user->id,'status'=>'pending']);
            if ($level > 0) {
                $query->whereIn('expedition_id', function($q) use ($level){ $q->select('id')->from('expeditions')->where('level',$level); });
            }
            $toStart = $query->orderBy('id')->limit($remaining)->get();
            // Energy gating: stop immediately if energy is 0 (unless Unlimited Energy)
            $unlimited = \App\Services\PremiumService::unlimitedEnergyForUser($user->id);
            $statsPreview = \App\Models\UserStats::where('user_id',$user->id)->first();
            $energyRemaining = (int)($statsPreview->energy ?? 0);
            if (!$unlimited && $energyRemaining <= 0) {
                return response()->json(['ok'=>true,'started'=>0,'message'=>'Energy is 0']);
            }
            $started = 0; $sumEnergyCost = 0;
            foreach ($toStart as $row) {
                $ue = \App\Models\UserExpedition::where(['id'=>$row->id,'user_id'=>$user->id])->lockForUpdate()->first();
                if (!$ue || $ue->status !== 'pending') { continue; }
                // check per-expedition energy cost and stop if it would drop below 0 (unless Unlimited Energy)
                $exp = \App\Models\Expedition::find($ue->expedition_id);
                $costPct = (int)($exp->energy_cost_pct ?? 0);
                if (!$unlimited) {
                    if ($energyRemaining - $costPct < 0) { break; }
                    $energyRemaining -= $costPct;
                }
                $ue->status = 'active';
                $ue->started_at = $now;
                $ue->ends_at = $now->copy()->addSeconds((int)$ue->duration_seconds);
                $ue->save();
                $started++;
                // accumulate energy cost
                if ($exp) { $sumEnergyCost += (int)$exp->energy_cost_pct; }
            }
            // Deduct total energy like start()
            if ($started > 0) {
                $stats = \App\Models\UserStats::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$stats) { $stats = \App\Models\UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
                $prem = \App\Services\PremiumService::getOrCreate($user->id);
                $capMult = 1.0; if (\App\Services\PremiumService::isActive($prem)) { $tier = \App\Services\PremiumService::tierFor((int)$prem->premium_seconds_accumulated); $capMult = (float) (\App\Services\PremiumService::benefitsForTier($tier)['cap_multiplier'] ?? 1.0); }
                $cap = (int) floor(100 * $capMult);
                if (!\App\Services\PremiumService::unlimitedEnergyForUser($user->id)) {
                    $stats->energy = max(0, (int)$stats->energy - (int)$sumEnergyCost);
                }
                $stats->energy = min($cap, (int)$stats->energy);
                $stats->save();
            }
            return response()->json(['ok'=>true,'started'=>$started,'slots_remaining'=>max(0,$remaining-$started)]);
        });
    }

    public function claimAll(): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        $claimed = 0; $totalXp = 0; $totalGuildXp = 0; $lootAgg = [];
        // Iterative mode: process up to 'limit' rows per request to avoid timeouts
        $limit = (int) request('limit', 200);
        $limit = max(1, min(1000, $limit));
        $toProcess = UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])
            ->whereNotNull('ends_at')
            ->where('ends_at','<=',$now)
            ->orderBy('id')
            ->limit($limit)
            ->get();
        if ($toProcess->isEmpty()) {
            return response()->json(['ok'=>true,'claimed'=>0,'total_xp'=>0,'total_guild_xp'=>0,'loot'=>[],'has_more'=>false,'remaining'=>0]);
        }
        DB::transaction(function() use($user, $toProcess, $now, &$claimed, &$totalXp, &$totalGuildXp, &$lootAgg) {
                // lock all rows again inside the transaction and eager load expedition
                $ids = $toProcess->pluck('id')->all();
                $rows = UserExpedition::with('expedition')->whereIn('id', $ids)->lockForUpdate()->get();

                $cfg = config('expeditions');
                // Aggregate values
                $sumXp = 0; $sumGuildXp = 0; $sumTime = 0; $sumFood = 0; $sumWater = 0; $updates = [];
                $lootMap = []; // store_item_id => ['name'=>..., 'qty'=>...]
                $progress = app(ProgressService::class)->getOrCreate($user->id);
                $uLevel = max(1, (int) $progress->level);
                $prem = PremiumService::getOrCreate($user->id);
                $premActive = PremiumService::isActive($prem);
                $premTier = $premActive ? PremiumService::tierFor((int)$prem->premium_seconds_accumulated) : 0;
                $premBenefits = $premActive ? PremiumService::benefitsForTier($premTier) : [];
                $xpMult = (float)($premBenefits['xp_multiplier'] ?? 1.0);
                $timeMult = (float)($premBenefits['time_multiplier'] ?? 1.0);
                $mastery = app(ExpeditionMasteryService::class)->getOrCreate($user->id);
                $mBonuses = app(ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
                $mXpMult = (float)($mBonuses['xp_multiplier'] ?? 1.0);
                // Preload active items once per batch to avoid per-row queries
                $activeItems = StoreItem::where('is_active', true)->get()->values();

                foreach ($rows as $ue) {
                    if ($ue->status !== 'active' || !$ue->ends_at || $now->lt($ue->ends_at)) { continue; }
                    // mark completed -> claimed at end
                    $ue->status = 'completed';
                    $ue->save();

                    $hours = max(1, (int) ceil(((int)$ue->duration_seconds)/3600));
                    $level = (int) optional($ue->expedition)->level ?? 1;
                    $xpRaw = (int) (
                        ($level * (float)($cfg['xp_per_level'] ?? 12))
                        + ($uLevel * (float)($cfg['xp_per_user_level'] ?? 10))
                        + $hours * (
                            (float)($cfg['xp_per_hour_base'] ?? 10)
                            + $level * (float)($cfg['xp_per_hour_per_level'] ?? 1.2)
                            + $uLevel * (float)($cfg['xp_per_hour_per_user_level'] ?? 1.5)
                        )
                    );
                    $exp = $ue->expedition; $costSec = (int) ($exp->cost_seconds ?? 0); $energyPct = (int) ($exp->energy_cost_pct ?? 0);
                    $levMult = (float) ($cfg['level_multipliers'][$level] ?? 1.0);
                    $costW = (float) ($cfg['cost_weight'] ?? 0.0);
                    $energyW = (float) ($cfg['energy_weight'] ?? 0.0);
                    $consW = (float) ($cfg['consumable_weight'] ?? 0.0);
                    $mult = max(1.0, $levMult * (1.0 + $costSec * $costW + $energyPct * $energyW + $hours * $consW));
                    $xpRaw = (int) floor($xpRaw * $mult);
                    $xpVar = max((float)$cfg['variance_min'], 0.0);
                    $xpVarMax = max((float)$cfg['variance_max'], $xpVar);
                    $xp = (int) random_int((int) floor($xpRaw * $xpVar), (int) ceil($xpRaw * $xpVarMax));
                    $xpBaseForGuild = $xp; // base before bonuses
                    if ($xpMult > 1.0) { $xp = max(1, (int) floor($xp * $xpMult)); }
                    if ($mXpMult > 1.0) { $xp = max(1, (int) floor($xp * $mXpMult)); }
                    $sumXp += $xp; $claimed++;
                    $gxp = (int) floor($xpBaseForGuild * 0.001); $sumGuildXp += $gxp;
                    // stats depletion
                    $deplete = min(100, $hours); $sumFood += $deplete; $sumWater += $deplete;
                    // time roll
                    $timeRaw = (int) ($level * (int)$cfg['time_per_level'] + $hours * (int)$cfg['time_per_hour']);
                    $timeRaw = (int) floor($timeRaw * $mult);
                    $baseMargin = (float) ($cfg['time_profit_margin_base'] ?? 0.10);
                    $perLvlMargin = (float) ($cfg['time_profit_margin_per_level'] ?? 0.03);
                    $capMargin = (float) ($cfg['time_profit_margin_cap'] ?? 0.50);
                    $effMargin = min($capMargin, $baseMargin + max(0, $level - 1) * $perLvlMargin);
                    $minTime = (int) ceil($costSec * (1.0 + $effMargin));
                    if ($timeRaw < $minTime) { $timeRaw = $minTime; }
                    $timeRoll = (int) random_int((int) floor($timeRaw * $xpVar), (int) ceil($timeRaw * $xpVarMax));
                    if ($timeMult > 1.0) { $timeRoll = max(0, (int) floor($timeRoll * $timeMult)); }
                    $sumTime += $timeRoll;

                    // loot aggregation
                    $band = $cfg['level_qty_bands'][$level] ?? [1,2];
                    $qtyPerHour = (int) $cfg['qty_per_hour'];
                    $baseMin = (int) $band[0]; $baseMax = (int) $band[1];
                    // sample up to 5 items from preloaded list, then take count up to 3
                    $count = max(1, min(3, $level, $activeItems->count()));
                    $loot = [];
                    for ($i=0; $i<$count; $i++) {
                        // pick a random index each time to diversify loot without DB
                        $idx = random_int(0, max(0, $activeItems->count()-1));
                        $si = $activeItems[$idx] ?? null; if (!$si) break;
                        $roll = random_int($baseMin, $baseMax);
                        $qty = (int) min((int)$cfg['qty_max'], $roll + (int) floor($hours * $qtyPerHour));
                        if ($qty <= 0) continue;
                        $loot[] = ['key'=>$si->key,'name'=>$si->name,'qty'=>$qty];
                        $lootAgg[$si->key] = $lootAgg[$si->key] ?? ['key'=>$si->key,'name'=>$si->name,'qty'=>0];
                        $lootAgg[$si->key]['qty'] += $qty;
                        // map by store_item_id for DB updates later
                        if (!isset($lootMap[$si->id])) { $lootMap[$si->id] = ['name'=>$si->name,'qty'=>0]; }
                        $lootMap[$si->id]['qty'] += $qty;
                    }

                    // persist row
                    $ue->base_xp = (int)$xpBaseForGuild;
                    $ue->loot = $loot; $ue->status = 'claimed'; $ue->save();
                    // daily stats (kept per-row for correctness of date boundaries)
                    app(\App\Services\StatsService::class)->incExpCompleted($user->id);
                }

                // Apply aggregate effects
                app(ExpeditionMasteryService::class)->addXp($user->id, 0); // ensure service caches are safe
                app(ProgressService::class)->addXp($user->id, (int)$sumXp);
                $totalXp += (int)$sumXp; $totalGuildXp += (int)$sumGuildXp;
                // Guild XP apply once
                $gm = GuildMember::where('user_id', $user->id)->lockForUpdate()->first();
                if ($gm && $gm->guild && $sumGuildXp > 0) {
                    app(GuildLevelService::class)->addXp($gm->guild, (int)$sumGuildXp);
                    $gm->increment('contribution_xp', (int)$sumGuildXp);
                }
                // Wallet
                if ($sumTime > 0) {
                    $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                    if (!$wallet) { $wallet = UserTimeWallet::create(['user_id'=>$user->id,'available_seconds'=>0,'last_applied_at'=>$now,'drain_rate'=>1.000,'is_active'=>true]); }
                    $wallet->available_seconds = (int)$wallet->available_seconds + (int)$sumTime; $wallet->save();
                }
                // Stats
                if ($sumFood>0 || $sumWater>0) {
                    $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
                    if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
                    $stats->food = max(0, (int)$stats->food - (int)$sumFood);
                    $stats->water = max(0, (int)$stats->water - (int)$sumWater);
                    $stats->save();
                }
                // Storage items (update per item id)
                foreach ($lootMap as $itemId => $info) {
                    $sto = UserStorageItem::where(['user_id'=>$user->id,'store_item_id'=>$itemId])->lockForUpdate()->first();
                    if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$user->id,'store_item_id'=>$itemId,'quantity'=>0]); }
                    $sto->quantity = (int)$sto->quantity + (int)$info['qty'];
                    $sto->save();
                }
            });
        // Check if more remain for subsequent calls
        $remaining = UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])
            ->whereNotNull('ends_at')
            ->where('ends_at','<=',$now)
            ->limit(1)
            ->count();
        $hasMore = $remaining > 0;
        return response()->json(['ok'=>true,'claimed'=>$claimed,'total_xp'=>$totalXp,'total_guild_xp'=>$totalGuildXp,'loot'=>array_values($lootAgg),'has_more'=>$hasMore,'remaining'=>$remaining]);
    }

    public function catalog(): JsonResponse
    {
        $level = (int) request('level', 0);
        $q = Expedition::query();
        if ($level >= 1 && $level <= 5) { $q->where('level', $level); }
        $list = $q->orderBy('level')->orderBy('id')->limit(200)->get();
        return response()->json($list);
    }

    public function my(): JsonResponse
    {
        $userId = Auth::id();
        $status = request('status');
        $level = (int) request('level', 0);
        $page = (int) request('page', 0);
        $per = (int) request('per_page', 0);

        // Backward-compatible: if no filter/pagination params are provided, return up to 200 as before
        if (!$status && $level === 0 && $page === 0 && $per === 0) {
            $rows = UserExpedition::with('expedition')
                ->where('user_id',$userId)
                ->orderByDesc('id')
                ->limit(200)
                ->get();
            return response()->json($rows);
        }

        // Paginated + filtered
        $q = UserExpedition::with('expedition')->where('user_id',$userId);
        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }
        if ($level >= 1 && $level <= 5) {
            $q->whereHas('expedition', function($qq) use($level){ $qq->where('level', $level); });
        }
        $per = max(1, min(100, $per ?: 50));
        $res = $q->orderByDesc('id')->paginate($per);
        return response()->json($res);
    }

    public function myCounts(): JsonResponse
    {
        $userId = Auth::id();
        $level = (int) request('level', 0);
        $q = UserExpedition::query()
            ->select('status', \DB::raw('COUNT(*) as c'))
            ->where('user_id', $userId);
        if ($level >= 1 && $level <= 5) {
            $q->whereHas('expedition', function($qq) use($level){ $qq->where('level', $level); });
        }
        $rows = $q->groupBy('status')->get();
        $map = [
            'pending' => 0,
            'active' => 0,
            'completed' => 0,
            'claimed' => 0,
        ];
        foreach ($rows as $r) {
            $s = (string)$r->status;
            if (array_key_exists($s, $map)) { $map[$s] = (int)$r->c; }
        }
        $map['completed_all'] = (int)$map['completed'] + (int)$map['claimed'];
        return response()->json(['ok'=>true,'counts'=>$map,'level'=>$level]);
    }

    public function buy(int $expeditionId): JsonResponse
    {
        $user = Auth::user();
        $exp = Expedition::findOrFail($expeditionId);
        $now = now();
        $source = request()->input('source','wallet');
        if (!in_array($source,['wallet','bank'],true)) $source = 'wallet';

        $result = DB::transaction(function() use($user,$exp,$source,$now){
            // charge cost from time balances
            $price = (int)$exp->cost_seconds;
            $wallet = null; $bank = null;
            if ($source==='wallet'){
                $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$wallet || (int)$wallet->available_seconds < $price){
                    Flasher::addError('Not enough wallet balance'); abort(422,'Not enough wallet balance');
                }
                $wallet->available_seconds = (int)$wallet->available_seconds - $price; $wallet->save();
            } else {
                $bank = TimeAccount::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$bank || (int)$bank->base_balance_seconds < $price){
                    Flasher::addError('Not enough bank balance'); abort(422,'Not enough bank balance');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price; $bank->save();
            }
            // choose a randomized duration
            $dur = random_int((int)$exp->min_duration_seconds, (int)$exp->max_duration_seconds);
            // base xp: 1 per 30s
            $baseXp = max(1, (int) floor($dur / 30));
            $ue = UserExpedition::create([
                'user_id' => $user->id,
                'expedition_id' => $exp->id,
                'status' => 'pending',
                'purchased_at' => $now,
                'duration_seconds' => $dur,
                'base_xp' => $baseXp,
            ]);
            return [$ue,$wallet,$bank];
        });
        [$ue,$wallet,$bank] = $result;
        Flasher::addSuccess('Purchased expedition: '.$exp->name);
        return response()->json(['ok'=>true,'expedition'=>$ue]);
    }

    public function buyLevel(): JsonResponse
    {
        $user = Auth::user();
        $level = (int) request()->input('level', 0);
        if ($level < 1 || $level > 5) { abort(422, 'Invalid level'); }
        $exp = Expedition::where('level',$level)->inRandomOrder()->firstOrFail();
        $now = now();
        $source = request()->input('source','wallet');
        if (!in_array($source,['wallet','bank'],true)) $source = 'wallet';
        $qty = max(1, min(250, (int) request()->input('qty', 1)));

        $result = DB::transaction(function() use($user,$exp,$source,$now,$level,$qty){
            $price = (int)$exp->cost_seconds * $qty;
            $wallet = null; $bank = null;
            if ($source==='wallet'){
                $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$wallet || (int)$wallet->available_seconds < $price){
                    Flasher::addError('Not enough wallet balance'); abort(422,'Not enough wallet balance');
                }
                $wallet->available_seconds = (int)$wallet->available_seconds - $price; $wallet->save();
            } else {
                $bank = TimeAccount::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$bank || (int)$bank->base_balance_seconds < $price){
                    Flasher::addError('Not enough bank balance'); abort(422,'Not enough bank balance');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price; $bank->save();
            }
            $created = [];
            for ($i=0; $i<$qty; $i++) {
                $chosen = Expedition::where('level',$level)->inRandomOrder()->firstOrFail();
                $dur = random_int((int)$chosen->min_duration_seconds, (int)$chosen->max_duration_seconds);
                $baseXp = max(1, (int) floor($dur / 30));
                $ue = UserExpedition::create([
                    'user_id' => $user->id,
                    'expedition_id' => $chosen->id,
                    'status' => 'pending',
                    'purchased_at' => $now,
                    'duration_seconds' => $dur,
                    'base_xp' => $baseXp,
                ]);
                $created[] = $ue;
            }
            return [$created,$wallet,$bank,$qty,$level];
        });
        [$created,$wallet,$bank,$qty,$level] = $result;
        Flasher::addSuccess('Purchased '.$qty.' expedition(s) at level '.$level);
        return response()->json(['ok'=>true,'count'=>$qty,'expeditions'=>$created]);
    }

    public function start(int $userExpeditionId): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        return DB::transaction(function() use($user,$userExpeditionId,$now){
            // enforce premium-based active slots
            $prem = PremiumService::getOrCreate($user->id);
            $allowed = 1;
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $allowed = max(1, (int)($benefits['expedition_total_slots'] ?? 1));
            }
            // add expedition mastery extra slots
            $mastery = app(ExpeditionMasteryService::class)->getOrCreate($user->id);
            $mBonuses = app(ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
            $allowed = (int)$allowed + (int)($mBonuses['expedition_extra_slots'] ?? 0);
            $allowed += (int) PremiumService::lifetimeExtraSlotsForUser($user->id);
            $allowed = min(250, (int)$allowed);

            // add token shop extra slots (permanent + active temporary)
            $upgrade = UserExpeditionUpgrade::query()->where('user_id', $user->id)->first();
            if ($upgrade) {
                $extraPerm = (int)$upgrade->permanent_slots + (int)($upgrade->admin_permanent_slots ?? 0);
                $extraTemp = 0;
                if ($upgrade->temp_expires_at && $upgrade->temp_expires_at->gt($now)) {
                    $extraTemp = (int)$upgrade->temp_slots;
                }
                $allowed += max(0, $extraPerm + $extraTemp);
            }
            $activeCount = (int) UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])->lockForUpdate()->count();
            if ($activeCount >= $allowed) {
                return response()->json(['ok'=>false,'message'=>"Active expeditions limit reached (${activeCount}/${allowed}). Upgrade premium tier or wait until one finishes."], 422);
            }
            $ue = UserExpedition::where(['id'=>$userExpeditionId,'user_id'=>$user->id])->lockForUpdate()->first();
            if (!$ue) { return response()->json(['ok'=>false,'message'=>'Expedition not found'], 404); }
            if ($ue->status !== 'pending') { return response()->json(['ok'=>false,'message'=>'Expedition is not pending'], 422); }
            $exp = Expedition::findOrFail($ue->expedition_id);
            $ue->status = 'active';
            $ue->started_at = $now;
            $ue->ends_at = $now->copy()->addSeconds((int)$ue->duration_seconds);
            $ue->save();
            // energy cost on start
            $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
            $prem = PremiumService::getOrCreate($user->id);
            $capMult = 1.0; if (PremiumService::isActive($prem)) { $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated); $capMult = (float) (PremiumService::benefitsForTier($tier)['cap_multiplier'] ?? 1.0); }
            $cap = (int) floor(100 * $capMult);
            if (!PremiumService::unlimitedEnergyForUser($user->id)) {
                $stats->energy = max(0, (int)$stats->energy - (int)$exp->energy_cost_pct);
            }
            $stats->energy = min($cap, (int)$stats->energy);
            $stats->save();
            return response()->json(['ok'=>true,'expedition'=>$ue]);
        });
    }

    public function claim(int $userExpeditionId): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        return DB::transaction(function() use($user,$userExpeditionId,$now){
            $ue = UserExpedition::where(['id'=>$userExpeditionId,'user_id'=>$user->id])->lockForUpdate()->first();
            if (!$ue) { return response()->json(['ok'=>false,'message'=>'Expedition not found'], 404); }
            if ($ue->status !== 'active') { return response()->json(['ok'=>false,'message'=>'Expedition is not active'], 422); }
            if (!$ue->ends_at || $now->lt($ue->ends_at)) { return response()->json(['ok'=>false,'message'=>'Expedition not finished yet'], 422); }
            // Gate claim when food or water is 0
            $statsGate = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
            if ($statsGate && ( (int)$statsGate->food <= 0 || (int)$statsGate->water <= 0)) {
                return response()->json(['ok'=>false,'message'=>'Cannot claim: Food/Water is 0. Please replenish first.'], 422);
            }
            $ue->status = 'completed';
            $ue->save();
            $cfg = config('expeditions');
            $hours = max(1, (int) ceil(((int)$ue->duration_seconds)/3600));
            $level = (int) optional($ue->expedition)->level ?? 1; // expedition level
            $progress = app(ProgressService::class)->getOrCreate($user->id);
            $uLevel = max(1, (int) $progress->level); // user level
            $xpRaw = (int) (
                ($level * (float)($cfg['xp_per_level'] ?? 12))
                + ($uLevel * (float)($cfg['xp_per_user_level'] ?? 10))
                + $hours * (
                    (float)($cfg['xp_per_hour_base'] ?? 10)
                    + $level * (float)($cfg['xp_per_hour_per_level'] ?? 1.2)
                    + $uLevel * (float)($cfg['xp_per_hour_per_user_level'] ?? 1.5)
                )
            );
            $xpVar = max((float)$cfg['variance_min'], 0.0);
            $xpVarMax = max((float)$cfg['variance_max'], $xpVar);
            $xp = (int) random_int((int) floor($xpRaw * $xpVar), (int) ceil($xpRaw * $xpVarMax));
            $xpBaseForGuild = $xp; // base XP before premium/mastery bonuses
            // Apply global nerf before bonuses
            $glob = (float) (config('expeditions.xp_global_multiplier') ?? 1.0);
            if ($glob > 0.0 && $glob !== 1.0) {
                $xp = max(1, (int) floor($xp * $glob));
                $xpBaseForGuild = max(1, (int) floor($xpBaseForGuild * $glob));
            }
            $prem = PremiumService::getOrCreate($user->id);
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $mult = (float)($benefits['xp_multiplier'] ?? 1.0);
                if ($mult > 1.0) { $xp = max(1, (int) floor($xp * $mult)); }
            }
            // apply expedition mastery XP bonus and award mastery XP progress
            $mastery = app(ExpeditionMasteryService::class)->getOrCreate($user->id);
            $mBonuses = app(ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
            $mXpMult = (float)($mBonuses['xp_multiplier'] ?? 1.0);
            if ($mXpMult > 1.0) { $xp = max(1, (int) floor($xp * $mXpMult)); }
            app(ExpeditionMasteryService::class)->addXp($user->id, (int)$xpRaw);
            app(ProgressService::class)->addXp($user->id, $xp);
            // Guild XP: 0.1% of BASE XP (before premium/mastery bonuses)
            $gm = GuildMember::where('user_id', $user->id)->first();
            if ($gm && $gm->guild) {
                $gxp = (int) floor($xpBaseForGuild * 0.001);
                if ($gxp > 0) {
                    app(GuildLevelService::class)->addXp($gm->guild, $gxp);
                    // Track member contribution
                    $gm->increment('contribution_xp', $gxp);
                }
            }
            // deplete food/water based on duration: 1% per hour rounded up
            $hours = max(1, (int) ceil(((int)$ue->duration_seconds)/3600));
            $deplete = min(100, $hours);
            $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
            $stats->food = max(0, (int)$stats->food - $deplete);
            $stats->water = max(0, (int)$stats->water - $deplete);
            $stats->save();
            // time reward
            // define composite multiplier and costs to avoid undefined vars
            $exp = $ue->expedition; $costSec = (int) ($exp->cost_seconds ?? 0); $energyPct = (int) ($exp->energy_cost_pct ?? 0);
            $levMult = (float) ($cfg['level_multipliers'][$level] ?? 1.0);
            $costW = (float) ($cfg['cost_weight'] ?? 0.0);
            $energyW = (float) ($cfg['energy_weight'] ?? 0.0);
            $consW = (float) ($cfg['consumable_weight'] ?? 0.0);
            $mult = max(1.0, $levMult * (1.0 + $costSec * $costW + $energyPct * $energyW + $hours * $consW));
            $timeRaw = (int) ($level * (int)$cfg['time_per_level'] + $hours * (int)$cfg['time_per_hour']);
            // apply same multiplier to time
            $timeRaw = (int) floor($timeRaw * $mult);
            // ensure profitability vs cost_seconds
            $baseMargin = (float) ($cfg['time_profit_margin_base'] ?? 0.10);
            $perLvlMargin = (float) ($cfg['time_profit_margin_per_level'] ?? 0.03);
            $capMargin = (float) ($cfg['time_profit_margin_cap'] ?? 0.50);
            $effMargin = min($capMargin, $baseMargin + max(0, $level - 1) * $perLvlMargin);
            $minTime = (int) ceil($costSec * (1.0 + $effMargin));
            if ($timeRaw < $minTime) { $timeRaw = $minTime; }
            $time = (int) random_int((int) floor($timeRaw * $xpVar), (int) ceil($timeRaw * $xpVarMax));
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $tm = (float)($benefits['time_multiplier'] ?? 1.0);
                if ($tm > 1.0) { $time = max(0, (int) floor($time * $tm)); }
            }
            $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
            if (!$wallet) { $wallet = UserTimeWallet::create(['user_id'=>$user->id,'available_seconds'=>0,'last_applied_at'=>$now,'drain_rate'=>1.000,'is_active'=>true]); }
            $wallet->available_seconds = (int)$wallet->available_seconds + $time; $wallet->save();
            // generate loot: level-based qty; deliver to storage
            $loot = [];
            $band = $cfg['level_qty_bands'][$level] ?? [1,2];
            $qtyPerHour = (int) $cfg['qty_per_hour'];
            $baseMin = (int) $band[0]; $baseMax = (int) $band[1];
            $items = StoreItem::where('is_active', true)->inRandomOrder()->limit(5)->get();
            $count = max(1, min(3, $level, $items->count()));
            for ($i=0; $i<$count; $i++) {
                $si = $items[$i]; if (!$si) break;
                $roll = random_int($baseMin, $baseMax);
                $q = (int) min((int)$cfg['qty_max'], $roll + (int) floor($hours * $qtyPerHour));
                if ($q <= 0) continue;
                $loot[] = ['key'=>$si->key,'name'=>$si->name,'qty'=>$q];
                $sto = UserStorageItem::where(['user_id'=>$user->id,'store_item_id'=>$si->id])->lockForUpdate()->first();
                if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$user->id,'store_item_id'=>$si->id,'quantity'=>0]); }
                $sto->quantity = (int)$sto->quantity + $q; $sto->save();
            }
            $ue->loot = $loot; $ue->status = 'claimed'; $ue->save();
            // daily stats: increment expeditions completed (UTC boundaries)
            app(\App\Services\StatsService::class)->incExpCompleted($user->id);
            Flasher::addSuccess('Expedition claimed: +'.$xp.' XP and loot');
            return response()->json(['ok'=>true,'xp'=>$xp,'time_seconds'=>$time,'loot'=>$loot]);
        });
    }
}
