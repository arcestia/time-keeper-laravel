<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Expedition;
use App\Models\UserExpedition;
use App\Models\UserExpeditionUpgrade;
use App\Models\UserStats;
use App\Models\UserTimeWallet;
use App\Models\TimeAccount;
use App\Models\StoreItem;
use App\Models\UserStorageItem;
use App\Services\PremiumService;
use App\Services\ProgressService;
use App\Services\ExpeditionMasteryService;
use Flasher\Laravel\Facade\Flasher;

class ExpeditionController extends Controller
{
    public function page()
    {
        return view('expeditions.index');
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
            // compute allowed slots (same as start())
            $prem = \App\Services\PremiumService::getOrCreate($user->id);
            $allowed = 1;
            if (\App\Services\PremiumService::isActive($prem)) {
                $tier = \App\Services\PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = \App\Services\PremiumService::benefitsForTier($tier);
                $allowed = max(1, (int)($benefits['expedition_total_slots'] ?? 1));
            }
            $mastery = app(\App\Services\ExpeditionMasteryService::class)->getOrCreate($user->id);
            $mBonuses = app(\App\Services\ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
            $allowed = (int)$allowed + (int)($mBonuses['expedition_extra_slots'] ?? 0);
            $upgrade = \App\Models\UserExpeditionUpgrade::query()->where('user_id',$user->id)->first();
            if ($upgrade) {
                $extraPerm = (int)$upgrade->permanent_slots;
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
            $started = 0;
            foreach ($toStart as $row) {
                $ue = \App\Models\UserExpedition::where(['id'=>$row->id,'user_id'=>$user->id])->lockForUpdate()->first();
                if (!$ue || $ue->status !== 'pending') { continue; }
                $ue->status = 'active';
                $ue->started_at = $now;
                $ue->ends_at = $now->copy()->addSeconds((int)$ue->duration_seconds);
                $ue->save();
                $started++;
            }
            return response()->json(['ok'=>true,'started'=>$started,'slots_remaining'=>max(0,$remaining-$started)]);
        });
    }

    public function claimAll(): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        $claimed = 0; $totalXp = 0; $lootAgg = [];
        // Process each finished active expedition in its own transaction to reduce lock contention
        $toProcess = UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])
            ->whereNotNull('ends_at')
            ->where('ends_at','<=',$now)
            ->orderBy('id')
            ->limit(100)
            ->get();
        foreach ($toProcess as $ueRow) {
            DB::transaction(function() use($user,$ueRow,$now,&$claimed,&$totalXp,&$lootAgg) {
                $ue = UserExpedition::where(['id'=>$ueRow->id,'user_id'=>$user->id])->lockForUpdate()->first();
                if (!$ue || $ue->status !== 'active' || !$ue->ends_at || $now->lt($ue->ends_at)) { return; }
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
                // composite multiplier by level, cost, energy, duration
                $exp = $ue->expedition; $costSec = (int) ($exp->cost_seconds ?? 0); $energyPct = (int) ($exp->energy_cost_pct ?? 0);
                $levMult = (float) ($cfg['level_multipliers'][$level] ?? 1.0);
                $costW = (float) ($cfg['cost_weight'] ?? 0.0);
                $energyW = (float) ($cfg['energy_weight'] ?? 0.0);
                $consW = (float) ($cfg['consumable_weight'] ?? 0.0);
                $mult = max(1.0, $levMult * (1.0 + $costSec * $costW + $energyPct * $energyW + $hours * $consW));
                $xpRaw = (int) floor($xpRaw * $mult);
                $xpVar = max((float)$cfg['variance_min'], 0.0);
                $xpVarMax = max((float)$cfg['variance_max'], $xpVar);
                $xpRoll = (int) random_int((int) floor($xpRaw * $xpVar), (int) ceil($xpRaw * $xpVarMax));
                $prem = PremiumService::getOrCreate($user->id);
                if (PremiumService::isActive($prem)) {
                    $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                    $benefits = PremiumService::benefitsForTier($tier);
                    $mult = (float)($benefits['xp_multiplier'] ?? 1.0);
                    if ($mult > 1.0) { $xpRoll = max(1, (int) floor($xpRoll * $mult)); }
                }
                // apply expedition mastery XP bonus and award mastery XP progress
                $mastery = app(ExpeditionMasteryService::class)->getOrCreate($user->id);
                $mBonuses = app(ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
                $mXpMult = (float)($mBonuses['xp_multiplier'] ?? 1.0);
                if ($mXpMult > 1.0) { $xpRoll = max(1, (int) floor($xpRoll * $mXpMult)); }
                app(ExpeditionMasteryService::class)->addXp($user->id, (int)$xpRaw);
                app(ProgressService::class)->addXp($user->id, $xpRoll);
                $totalXp += $xpRoll; $claimed++;
                // deplete food/water
                $deplete = min(100, $hours);
                $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
                $stats->food = max(0, (int)$stats->food - $deplete);
                $stats->water = max(0, (int)$stats->water - $deplete);
                $stats->save();
                // time reward
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
                $timeRoll = (int) random_int((int) floor($timeRaw * $xpVar), (int) ceil($timeRaw * $xpVarMax));
                if (PremiumService::isActive($prem)) {
                    $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                    $benefits = PremiumService::benefitsForTier($tier);
                    $tm = (float)($benefits['time_multiplier'] ?? 1.0);
                    if ($tm > 1.0) { $timeRoll = max(0, (int) floor($timeRoll * $tm)); }
                }
                $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$wallet) { $wallet = UserTimeWallet::create(['user_id'=>$user->id,'available_seconds'=>0,'last_applied_at'=>$now,'drain_rate'=>1.000,'is_active'=>true]); }
                $wallet->available_seconds = (int)$wallet->available_seconds + $timeRoll; $wallet->save();
                // loot (level-based qty, send to storage)
                $loot = [];
                $band = $cfg['level_qty_bands'][$level] ?? [1,2];
                $qtyPerHour = (int) $cfg['qty_per_hour'];
                $baseMin = (int) $band[0]; $baseMax = (int) $band[1];
                $items = StoreItem::where('is_active', true)->inRandomOrder()->limit(5)->get();
                // decide number of different items by level (simple): 1..min(level,3)
                $count = max(1, min(3, $level, $items->count()));
                for ($i=0; $i<$count; $i++) {
                    $si = $items[$i]; if (!$si) break;
                    $roll = random_int($baseMin, $baseMax);
                    $qty = (int) min((int)$cfg['qty_max'], $roll + (int) floor($hours * $qtyPerHour));
                    if ($qty <= 0) continue;
                    $loot[] = ['key'=>$si->key,'name'=>$si->name,'qty'=>$qty];
                    $sto = UserStorageItem::where(['user_id'=>$user->id,'store_item_id'=>$si->id])->lockForUpdate()->first();
                    if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$user->id,'store_item_id'=>$si->id,'quantity'=>0]); }
                    $sto->quantity = (int)$sto->quantity + $qty; $sto->save();
                    if (!isset($lootAgg[$si->key])) { $lootAgg[$si->key] = ['key'=>$si->key,'name'=>$si->name,'qty'=>0]; }
                    $lootAgg[$si->key]['qty'] += $qty;
                }
                $ue->loot = $loot; $ue->status = 'claimed'; $ue->save();
                // daily stats: increment expeditions completed (UTC boundaries) for bulk claim
                app(\App\Services\StatsService::class)->incExpCompleted($user->id);
            });
        }
        return response()->json(['ok'=>true,'claimed'=>$claimed,'total_xp'=>$totalXp,'loot'=>array_values($lootAgg)]);
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
        $rows = UserExpedition::query()
            ->select('status', \DB::raw('COUNT(*) as c'))
            ->where('user_id', $userId)
            ->groupBy('status')
            ->get();
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
        return response()->json(['ok'=>true,'counts'=>$map]);
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

            // add token shop extra slots (permanent + active temporary)
            $upgrade = UserExpeditionUpgrade::query()->where('user_id', $user->id)->first();
            if ($upgrade) {
                $extraPerm = (int)$upgrade->permanent_slots;
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
            $stats->energy = max(0, (int)$stats->energy - (int)$exp->energy_cost_pct);
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
