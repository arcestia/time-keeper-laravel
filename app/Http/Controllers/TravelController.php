<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\UserTimeWallet;
use App\Models\UserInventoryItem;
use App\Models\StoreItem;
use App\Services\PremiumService;
use App\Services\ProgressService;
use Carbon\CarbonImmutable;

class TravelController extends Controller
{
    public function page()
    {
        return view('travel.index');
    }

    public function step(): JsonResponse
    {
        $user = Auth::user();
        $now = CarbonImmutable::now();
        // Premium T20 gets fast travel (1s delay)
        $prem = PremiumService::getOrCreate($user->id);
        $tier = 0; $isPrem = PremiumService::isActive($prem);
        if ($isPrem) { $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated); }
        $delay = ($isPrem && $tier >= 20) ? 1 : random_int(2, 5);
        sleep($delay);
        $progress = app(ProgressService::class)->getOrCreate($user->id);
        $level = max(1, (int) $progress->level);
        $minXp = (int) floor($level * 10 * 0.9);
        $maxXp = (int) ceil($level * 10 * 1.2);
        $xp = random_int(max(1,$minXp), max($minXp+1,$maxXp));
        $minTime = (int) floor($level * 30 * 0.9);
        $maxTime = (int) ceil($level * 30 * 1.2);
        $timeSec = random_int(max(1,$minTime), max($minTime+1,$maxTime));
        if ($isPrem) {
            $benefits = PremiumService::benefitsForTier($tier);
            $xpMult = (float)($benefits['xp_multiplier'] ?? 1.0);
            $timeMult = (float)($benefits['time_multiplier'] ?? 1.0);
            if ($xpMult > 1.0) { $xp = max(1, (int) floor($xp * $xpMult)); }
            if ($timeMult > 1.0) { $timeSec = max(1, (int) floor($timeSec * $timeMult)); }
        }
        $item = StoreItem::query()->where('is_active', true)->inRandomOrder()->first();
        $itemQty = $item ? random_int(1, 10) : 0;
        $result = DB::transaction(function () use ($user, $now, $xp, $timeSec, $item, $itemQty) {
            $p = app(ProgressService::class)->addXp($user->id, $xp);
            $wallet = UserTimeWallet::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserTimeWallet::create([
                    'user_id' => $user->id,
                    'available_seconds' => 0,
                    'last_applied_at' => $now,
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]);
            }
            $wallet->available_seconds = (int)$wallet->available_seconds + $timeSec;
            $wallet->save();
            $grantedItem = null;
            if ($item && $itemQty > 0) {
                $cap = (int) config('inventory.cap', 20000);
                $invTotal = (int) UserInventoryItem::query()->where('user_id',$user->id)->lockForUpdate()->sum('quantity');
                $spaceLeft = max(0, $cap - $invTotal);
                $toInv = max(0, min($itemQty, $spaceLeft));
                $toSto = max(0, $itemQty - $toInv);
                if ($toInv > 0) {
                    $inv = UserInventoryItem::query()->where(['user_id'=>$user->id,'store_item_id'=>$item->id])->lockForUpdate()->first();
                    if (!$inv) { $inv = UserInventoryItem::create(['user_id'=>$user->id,'store_item_id'=>$item->id,'quantity'=>0]); }
                    $inv->quantity = (int)$inv->quantity + $toInv;
                    $inv->save();
                }
                if ($toSto > 0) {
                    $sto = \App\Models\UserStorageItem::query()->where(['user_id'=>$user->id,'store_item_id'=>$item->id])->lockForUpdate()->first();
                    if (!$sto) { $sto = \App\Models\UserStorageItem::create(['user_id'=>$user->id,'store_item_id'=>$item->id,'quantity'=>0]); }
                    $sto->quantity = (int)$sto->quantity + $toSto;
                    $sto->save();
                }
                $grantedItem = [
                    'key' => $item->key,
                    'name' => $item->name,
                    'qty' => (int)$itemQty,
                ];
            }
            return [$p, $wallet, $grantedItem];
        });
        [$p, $wallet, $grantedItem] = $result;
        // count 1 step for this travel action (UTC daily)
        app(\App\Services\StatsService::class)->addSteps($user->id, 1);
        return response()->json([
            'ok' => true,
            'delay_seconds' => (int)$delay,
            'awarded' => [
                'xp' => (int)$xp,
                'time_seconds' => (int)$timeSec,
                'item' => $grantedItem,
            ],
            'progress' => [
                'level' => (int)$p->level,
                'xp' => (int)$p->xp,
                'next_xp' => (int)$p->next_xp,
            ],
            'wallet_seconds' => (int)$wallet->available_seconds,
        ]);
    }
}
