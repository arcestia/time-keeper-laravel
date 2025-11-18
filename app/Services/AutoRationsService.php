<?php
namespace App\Services;

use App\Models\UserAutoRations;
use App\Models\UserInventoryItem;
use App\Models\UserStats;
use App\Models\StoreItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AutoRationsService
{
    public function getOrCreate(int $userId): UserAutoRations
    {
        return UserAutoRations::firstOrCreate(['user_id'=>$userId], [
            'food_enabled'=>false,
            'water_enabled'=>false,
            'food_threshold'=>50,
            'water_threshold'=>50,
            'access_until'=>null,
        ]);
    }

    public function hasAccess(int $userId): bool
    {
        $row = $this->getOrCreate($userId);
        return $row->access_until && $row->access_until->isFuture();
    }

    /**
     * Purchase access time using time tokens. Red = 6h per token. Others scale by token time value.
     */
    public function purchaseAccess(int $userId, string $color, int $qty, TimeTokenService $tokens): array
    {
        $qty = max(1, (int)$qty);
        $color = strtolower(trim($color));
        // seconds per token baseline
        $redSeconds = 6 * 3600; // 6 hours
        $valueSeconds = (int) $tokens->valueSeconds($color);
        if ($color === 'diamond' || $valueSeconds <= 0) {
            return ['ok'=>false,'message'=>'Unsupported token color'];
        }
        // scale vs red value (604800 sec)
        $scale = max(1e-6, $valueSeconds / max(1, config('time_tokens.values.red', 604800)));
        $grantSecondsPer = (int) round($redSeconds * $scale);
        return DB::transaction(function () use ($userId, $color, $qty, $grantSecondsPer, $tokens) {
            $row = $this->getOrCreate($userId);
            // debit exact qty
            $res = $tokens->debit($userId, $color, $qty);
            if (!($res['ok'] ?? false) || (int)($res['taken'] ?? 0) < $qty) {
                return ['ok'=>false,'message'=>'Not enough tokens'];
            }
            $add = $grantSecondsPer * $qty;
            $now = now();
            $base = ($row->access_until && $row->access_until->gt($now)) ? $row->access_until : $now;
            $row->access_until = $base->copy()->addSeconds($add);
            $row->save();
            return ['ok'=>true,'granted_seconds'=>$add,'access_until'=>$row->access_until->toIso8601String()];
        });
    }

    /**
     * Attempt to top up stats using smallest suitable inventory items without exceeding cap.
     */
    public function maybeTopUp(int $userId, array $which = ['food','water']): array
    {
        $settings = $this->getOrCreate($userId);
        if (!$this->hasAccess($userId)) { return ['ok'=>false,'used'=>[]]; }
        $stats = UserStats::where('user_id',$userId)->lockForUpdate()->first();
        if (!$stats) { return ['ok'=>false,'used'=>[]]; }
        $cap = (int) PremiumService::statsCapPercentForUser($userId);
        $need = [];
        if (in_array('food',$which,true) && $settings->food_enabled) {
            $threshold = max(1, min($cap, (int)$settings->food_threshold));
            if ((int)$stats->food < $threshold) { $need['food'] = $threshold - (int)$stats->food; }
        }
        if (in_array('water',$which,true) && $settings->water_enabled) {
            $threshold = max(1, min($cap, (int)$settings->water_threshold));
            if ((int)$stats->water < $threshold) { $need['water'] = $threshold - (int)$stats->water; }
        }
        if (empty($need)) { return ['ok'=>true,'used'=>[]]; }
        // Load candidate items with restore values
        $inv = UserInventoryItem::with('item')->where('user_id',$userId)->lockForUpdate()->get();
        $foodAllowed = is_array($settings->food_item_keys) && count($settings->food_item_keys) ? array_map('strval', $settings->food_item_keys) : null;
        $waterAllowed = is_array($settings->water_item_keys) && count($settings->water_item_keys) ? array_map('strval', $settings->water_item_keys) : null;
        $foodItems = $inv->filter(function($x) use($foodAllowed){
                $restore = (int)($x->item->restore_food ?? 0);
                if ($restore <= 0) return false;
                if (is_array($foodAllowed)) { return in_array((string)$x->item->key, $foodAllowed, true); }
                return true;
            })->sortBy(fn($x)=> (int)$x->item->restore_food);
        $waterItems = $inv->filter(function($x) use($waterAllowed){
                $restore = (int)($x->item->restore_water ?? 0);
                if ($restore <= 0) return false;
                if (is_array($waterAllowed)) { return in_array((string)$x->item->key, $waterAllowed, true); }
                return true;
            })->sortBy(fn($x)=> (int)$x->item->restore_water);
        $used = ['food'=>[],'water'=>[]];
        // Greedy consume smallest first
        if (isset($need['food'])) {
            $remain = (int)$need['food'];
            foreach ($foodItems as $e) {
                if ($remain <= 0) break;
                $per = max(1, (int)$e->item->restore_food);
                $can = (int) min((int)$e->quantity, intdiv($remain + $per - 1, $per));
                if ($can <= 0) continue;
                $use = $can;
                $stats->food = min($cap, (int)$stats->food + $per * $use);
                $e->quantity = (int)$e->quantity - $use; if ($e->quantity <= 0) { $e->delete(); } else { $e->save(); }
                $used['food'][] = ['key'=>(string)$e->item->key,'used'=>$use];
                $remain = max(0, $cap - (int)$stats->food);
            }
        }
        if (isset($need['water'])) {
            $remain = (int)$need['water'];
            foreach ($waterItems as $e) {
                if ($remain <= 0) break;
                $per = max(1, (int)$e->item->restore_water);
                $can = (int) min((int)$e->quantity, intdiv($remain + $per - 1, $per));
                if ($can <= 0) continue;
                $use = $can;
                $stats->water = min($cap, (int)$stats->water + $per * $use);
                $e->quantity = (int)$e->quantity - $use; if ($e->quantity <= 0) { $e->delete(); } else { $e->save(); }
                $used['water'][] = ['key'=>(string)$e->item->key,'used'=>$use];
                $remain = max(0, $cap - (int)$stats->water);
            }
        }
        $stats->save();
        return ['ok'=>true,'used'=>$used];
    }
}
