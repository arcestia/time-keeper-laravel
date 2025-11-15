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
use App\Services\TimeTokenService;
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

        // Base ranges for potential rewards
        // XP is based on user level × random between 1.0 and 2.2
        $minXp = (int) floor($level * 1.0);
        $maxXp = (int) ceil($level * 2.2);
        $baseXp = random_int(max(1, $minXp), max($minXp + 1, $maxXp));

        // Time seconds reward scales with user level × random between 4 and 7 seconds
        $minTime = (int) floor($level * 4);
        $maxTime = (int) ceil($level * 7);
        $baseTimeSec = random_int(max(1, $minTime), max($minTime + 1, $maxTime));

        if ($isPrem) {
            $benefits = PremiumService::benefitsForTier($tier);
            $xpMult = (float)($benefits['xp_multiplier'] ?? 1.0);
            $timeMult = (float)($benefits['time_multiplier'] ?? 1.0);
            if ($xpMult > 1.0) { $baseXp = max(1, (int) floor($baseXp * $xpMult)); }
            if ($timeMult > 1.0) { $baseTimeSec = max(1, (int) floor($baseTimeSec * $timeMult)); }
        }

        // Decide reward type: XP 58%, time 25%, item 15%, token 2%
        $roll = random_int(1, 100);
        if ($roll <= 58) {
            $rewardType = 'xp';
        } elseif ($roll <= 83) { // 59-83
            $rewardType = 'time';
        } elseif ($roll <= 98) { // 84-98
            $rewardType = 'item';
        } else { // 99-100
            $rewardType = 'token';
        }

        $item = null;
        $itemQty = 0;
        if ($rewardType === 'item') {
            $item = StoreItem::query()->where('is_active', true)->inRandomOrder()->first();
            $itemQty = $item ? random_int(1, 20) : 0;
        }

        // Token reward: pick color by weights and always give qty=1
        $token = null;
        if ($rewardType === 'token') {
            $tokenRoll = random_int(1, 100);
            if ($tokenRoll <= 60) {
                $tokenColor = 'red';
            } elseif ($tokenRoll <= 85) { // 61-85
                $tokenColor = 'blue';
            } elseif ($tokenRoll <= 95) { // 86-95
                $tokenColor = 'green';
            } elseif ($tokenRoll <= 99) { // 96-99
                $tokenColor = 'yellow';
            } else { // 100
                $tokenColor = 'black';
            }
            $token = [
                'color' => $tokenColor,
                'qty' => 1,
            ];
        }

        $xp = 0;
        $timeSec = 0;
        if ($rewardType === 'xp') {
            $xp = $baseXp;
        } elseif ($rewardType === 'time') {
            $timeSec = $baseTimeSec;
        }

        $result = DB::transaction(function () use ($user, $now, $rewardType, $xp, $timeSec, $item, $itemQty, $token) {
            // Always ensure wallet exists, but only apply time when chosen
            $p = app(ProgressService::class)->getOrCreate($user->id);
            if ($rewardType === 'xp' && $xp > 0) {
                $p = app(ProgressService::class)->addXp($user->id, $xp);
            }

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
            if ($rewardType === 'time' && $timeSec > 0) {
                $wallet->available_seconds = (int) $wallet->available_seconds + $timeSec;
                $wallet->save();
            }

            // Time token credit
            if ($rewardType === 'token' && $token && ($token['qty'] ?? 0) > 0) {
                /** @var TimeTokenService $tts */
                $tts = app(TimeTokenService::class);
                $tts->credit($user->id, (string) $token['color'], (int) $token['qty']);
            }

            $grantedItem = null;
            if ($rewardType === 'item' && $item && $itemQty > 0) {
                $cap = (int) config('inventory.cap', 20000);
                $invTotal = (int) UserInventoryItem::query()->where('user_id', $user->id)->lockForUpdate()->sum('quantity');
                $spaceLeft = max(0, $cap - $invTotal);
                $toInv = max(0, min($itemQty, $spaceLeft));
                $toSto = max(0, $itemQty - $toInv);
                if ($toInv > 0) {
                    $inv = UserInventoryItem::query()->where(['user_id' => $user->id, 'store_item_id' => $item->id])->lockForUpdate()->first();
                    if (!$inv) { $inv = UserInventoryItem::create(['user_id' => $user->id, 'store_item_id' => $item->id, 'quantity' => 0]); }
                    $inv->quantity = (int) $inv->quantity + $toInv;
                    $inv->save();
                }
                if ($toSto > 0) {
                    $sto = \App\Models\UserStorageItem::query()->where(['user_id' => $user->id, 'store_item_id' => $item->id])->lockForUpdate()->first();
                    if (!$sto) { $sto = \App\Models\UserStorageItem::create(['user_id' => $user->id, 'store_item_id' => $item->id, 'quantity' => 0]); }
                    $sto->quantity = (int) $sto->quantity + $toSto;
                    $sto->save();
                }
                $grantedItem = [
                    'key' => $item->key,
                    'name' => $item->name,
                    'qty' => (int) $itemQty,
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
                'type' => $rewardType,
                'xp' => (int) $xp,
                'time_seconds' => (int) $timeSec,
                'item' => $grantedItem,
                'token' => $token,
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
