<?php

namespace App\Jobs;

use App\Models\UserExpedition;
use App\Models\UserStats;
use App\Models\StoreItem;
use App\Models\UserStorageItem;
use App\Models\GuildMember;
use App\Models\UserTimeWallet;
use App\Services\PremiumService;
use App\Services\ProgressService;
use App\Services\ExpeditionMasteryService;
use App\Services\GuildLevelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ClaimAllExpeditionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $limitPerBatch;

    public function __construct(int $userId, int $limitPerBatch = 300)
    {
        $this->userId = $userId;
        $this->limitPerBatch = max(50, min(1000, (int)$limitPerBatch));
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $now = now();
        $cacheKey = $this->cacheKey();
        $state = [
            'status' => 'running',
            'claimed' => 0,
            'total_xp' => 0,
            'total_guild_xp' => 0,
            'loot' => [],
            'remaining' => 0,
            'updated_at' => now()->toIso8601String(),
        ];
        Cache::put($cacheKey, $state, 3600);

        try {
            // Block if Food/Water at 0
            $stats0 = \App\Models\UserStats::where('user_id',$this->userId)->first();
            if ($stats0 && ( (int)$stats0->food <= 0 || (int)$stats0->water <= 0)) {
                $state['status'] = 'blocked';
                $state['message'] = 'Cannot claim: Food/Water is 0. Please replenish first.';
                $state['updated_at'] = now()->toIso8601String();
                Cache::put($cacheKey, $state, 3600);
                return;
            }
            while (true) {
                $now = now();
                $toProcess = UserExpedition::where(['user_id'=>$this->userId,'status'=>'active'])
                    ->whereNotNull('ends_at')
                    ->where('ends_at','<=',$now)
                    ->orderBy('id')
                    ->limit($this->limitPerBatch)
                    ->get();
                if ($toProcess->isEmpty()) {
                    break;
                }

                DB::transaction(function () use ($toProcess, &$state, $now) {
                    $ids = $toProcess->pluck('id')->all();
                    $rows = UserExpedition::with('expedition')->whereIn('id', $ids)->lockForUpdate()->get();

                    $cfg = config('expeditions');
                    $sumXp = 0; $sumGuildXp = 0; $sumTime = 0; $sumFood = 0; $sumWater = 0;
                    $lootMap = [];

                    $progress = app(ProgressService::class)->getOrCreate($this->userId);
                    $uLevel = max(1, (int)$progress->level);
                    $prem = PremiumService::getOrCreate($this->userId);
                    $premActive = PremiumService::isActive($prem);
                    $premTier = $premActive ? PremiumService::tierFor((int)$prem->premium_seconds_accumulated) : 0;
                    $premBenefits = $premActive ? PremiumService::benefitsForTier($premTier) : [];
                    $xpMult = (float)($premBenefits['xp_multiplier'] ?? 1.0);
                    $timeMult = (float)($premBenefits['time_multiplier'] ?? 1.0);
                    $mastery = app(ExpeditionMasteryService::class)->getOrCreate($this->userId);
                    $mBonuses = app(ExpeditionMasteryService::class)->bonusesForLevel((int)$mastery->level);
                    $mXpMult = (float)($mBonuses['xp_multiplier'] ?? 1.0);

                    $activeItems = StoreItem::where('is_active', true)->get()->values();

                    foreach ($rows as $ue) {
                        if ($ue->status !== 'active' || !$ue->ends_at || $now->lt($ue->ends_at)) { continue; }
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
                        $xpBaseForGuild = $xp;
                        if ($xpMult > 1.0) { $xp = max(1, (int) floor($xp * $xpMult)); }
                        if ($mXpMult > 1.0) { $xp = max(1, (int) floor($xp * $mXpMult)); }
                        $sumXp += $xp; $state['claimed']++;
                        $gxp = (int) floor($xpBaseForGuild * 0.001); $sumGuildXp += $gxp;

                        $deplete = min(100, $hours); $sumFood += $deplete; $sumWater += $deplete;

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
                        $count = max(1, min(3, $level, $activeItems->count()));
                        $loot = [];
                        for ($i=0; $i<$count; $i++) {
                            $idx = random_int(0, max(0, $activeItems->count()-1));
                            $si = $activeItems[$idx] ?? null; if (!$si) break;
                            $roll = random_int($baseMin, $baseMax);
                            $qty = (int) min((int)$cfg['qty_max'], $roll + (int) floor($hours * $qtyPerHour));
                            if ($qty <= 0) continue;
                            $loot[] = ['key'=>$si->key,'name'=>$si->name,'qty'=>$qty];
                            // aggregate for response cache
                            $state['loot'][$si->name] = ($state['loot'][$si->name] ?? 0) + $qty;
                            // map by store_item_id
                            if (!isset($lootMap[$si->id])) { $lootMap[$si->id] = ['name'=>$si->name,'qty'=>0]; }
                            $lootMap[$si->id]['qty'] += $qty;
                        }

                        $ue->base_xp = (int)$xpBaseForGuild;
                        $ue->loot = $loot; $ue->status = 'claimed'; $ue->save();
                        app(\App\Services\StatsService::class)->incExpCompleted($this->userId);
                    }

                    // Apply aggregate effects
                    app(ExpeditionMasteryService::class)->addXp($this->userId, 0);
                    app(ProgressService::class)->addXp($this->userId, (int)$sumXp);
                    $state['total_xp'] += (int)$sumXp; $state['total_guild_xp'] += (int)$sumGuildXp;

                    $gm = GuildMember::where('user_id', $this->userId)->lockForUpdate()->first();
                    if ($gm && $gm->guild && $sumGuildXp > 0) {
                        app(GuildLevelService::class)->addXp($gm->guild, (int)$sumGuildXp);
                        $gm->increment('contribution_xp', (int)$sumGuildXp);
                    }

                    if ($sumTime > 0) {
                        $wallet = UserTimeWallet::where('user_id',$this->userId)->lockForUpdate()->first();
                        if (!$wallet) { $wallet = UserTimeWallet::create(['user_id'=>$this->userId,'available_seconds'=>0,'last_applied_at'=>$now,'drain_rate'=>1.000,'is_active'=>true]); }
                        $wallet->available_seconds = (int)$wallet->available_seconds + (int)$sumTime; $wallet->save();
                    }

                    if ($sumFood>0 || $sumWater>0) {
                        $stats = UserStats::where('user_id',$this->userId)->lockForUpdate()->first();
                        if (!$stats) { $stats = UserStats::create(['user_id'=>$this->userId,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
                        $stats->food = max(0, (int)$stats->food - (int)$sumFood);
                        $stats->water = max(0, (int)$stats->water - (int)$sumWater);
                        $stats->save();
                    }

                    foreach ($lootMap as $itemId => $info) {
                        $sto = UserStorageItem::where(['user_id'=>$this->userId,'store_item_id'=>$itemId])->lockForUpdate()->first();
                        if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$this->userId,'store_item_id'=>$itemId,'quantity'=>0]); }
                        $sto->quantity = (int)$sto->quantity + (int)$info['qty'];
                        $sto->save();
                    }
                });

                // update progress cache after batch
                $remaining = UserExpedition::where(['user_id'=>$this->userId,'status'=>'active'])
                    ->whereNotNull('ends_at')
                    ->where('ends_at','<=',now())
                    ->count();
                $state['remaining'] = $remaining;
                $state['updated_at'] = now()->toIso8601String();
                Cache::put($cacheKey, $state, 3600);

                // Re-check block condition after each batch
                $statsAfter = \App\Models\UserStats::where('user_id',$this->userId)->first();
                if ($statsAfter && ( (int)$statsAfter->food <= 0 || (int)$statsAfter->water <= 0)) {
                    $state['status'] = 'blocked';
                    $state['message'] = 'Claim-all paused: Food/Water reached 0. Refill to continue.';
                    $state['updated_at'] = now()->toIso8601String();
                    Cache::put($cacheKey, $state, 3600);
                    return;
                }

                // small delay to prevent tight loop hammering DB
                usleep(100000); // 100ms
            }

            $state['status'] = 'done';
            $state['updated_at'] = now()->toIso8601String();
            Cache::put($cacheKey, $state, 3600);
        } catch (\Throwable $e) {
            Log::error('ClaimAllExpeditionsJob failed', ['user_id'=>$this->userId, 'error'=>$e->getMessage()]);
            $state['status'] = 'error';
            $state['error'] = $e->getMessage();
            $state['updated_at'] = now()->toIso8601String();
            Cache::put($cacheKey, $state, 3600);
        }
    }

    private function cacheKey(): string
    {
        return 'claim_all_progress:'.$this->userId;
    }
}
