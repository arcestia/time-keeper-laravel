<?php
namespace App\Http\Controllers;

use App\Models\UserLifetimeUpgrade;
use App\Services\TimeTokenService;
use App\Models\UserTimeToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PremiumService;

class LifetimeUpgradeController extends Controller
{
    public function me(): JsonResponse
    {
        $uid = Auth::id();
        if (!PremiumService::isLifetimeOrTier20($uid)) { abort(403, 'Lifetime Premium required'); }
        $up = UserLifetimeUpgrade::firstOrCreate(['user_id'=>$uid], [
            'stats_cap_steps'=>0,
            'extra_expedition_slots'=>0,
            'unlimited_energy'=>false,
        ]);
        return response()->json(['ok'=>true,'upgrades'=>[
            'stats_cap_steps'=>(int)$up->stats_cap_steps,
            'extra_expedition_slots'=>(int)$up->extra_expedition_slots,
            'unlimited_energy'=>(bool)$up->unlimited_energy,
        ]]);
    }

    public function buyStatsCapStep(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $uid = Auth::id();
        if (!PremiumService::isLifetimeOrTier20($uid)) { abort(403, 'Lifetime Premium required'); }
        return DB::transaction(function() use($uid) {
            $up = UserLifetimeUpgrade::lockForUpdate()->firstOrCreate(['user_id'=>$uid], [
                'stats_cap_steps'=>0,'extra_expedition_slots'=>0,'unlimited_energy'=>false
            ]);
            // cost: 1 diamond token (exact)
            $row = UserTimeToken::query()->where(['user_id'=>$uid,'color'=>'diamond'])->lockForUpdate()->first();
            $have = (int)($row->quantity ?? 0);
            if ($have < 1) { abort(422, 'Not enough diamond tokens'); }
            // deduct exactly 1
            if ($row) {
                $row->quantity = (int)$row->quantity - 1;
                if ($row->quantity <= 0) { $row->delete(); } else { $row->save(); }
            }
            // Add step, clamp by overall 20x cap enforced downstream; here allow up to 38 steps (1.0 base + 19 tiers + 0.5*steps <= 20)
            $up->stats_cap_steps = (int)$up->stats_cap_steps + 1;
            $up->save();
            return response()->json(['ok'=>true,'stats_cap_steps'=>(int)$up->stats_cap_steps]);
        });
    }

    public function buyExtraSlot(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $uid = Auth::id();
        if (!PremiumService::isLifetimeOrTier20($uid)) { abort(403, 'Lifetime Premium required'); }
        return DB::transaction(function() use($uid) {
            $up = UserLifetimeUpgrade::lockForUpdate()->firstOrCreate(['user_id'=>$uid], [
                'stats_cap_steps'=>0,'extra_expedition_slots'=>0,'unlimited_energy'=>false
            ]);
            // cost: 1 diamond token (exact)
            $row = UserTimeToken::query()->where(['user_id'=>$uid,'color'=>'diamond'])->lockForUpdate()->first();
            $have = (int)($row->quantity ?? 0);
            if ($have < 1) { abort(422, 'Not enough diamond tokens'); }
            if ($row) {
                $row->quantity = (int)$row->quantity - 1;
                if ($row->quantity <= 0) { $row->delete(); } else { $row->save(); }
            }
            $up->extra_expedition_slots = (int)$up->extra_expedition_slots + 1;
            $up->save();
            return response()->json(['ok'=>true,'extra_expedition_slots'=>(int)$up->extra_expedition_slots]);
        });
    }

    public function buyUnlimitedEnergy(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $uid = Auth::id();
        if (!PremiumService::isLifetimeOrTier20($uid)) { abort(403, 'Lifetime Premium required'); }
        return DB::transaction(function() use($uid) {
            $up = UserLifetimeUpgrade::lockForUpdate()->firstOrCreate(['user_id'=>$uid], [
                'stats_cap_steps'=>0,'extra_expedition_slots'=>0,'unlimited_energy'=>false
            ]);
            if ($up->unlimited_energy) {
                return response()->json(['ok'=>true,'unlimited_energy'=>true]);
            }
            // cost: 100 diamond tokens (exact)
            $row = UserTimeToken::query()->where(['user_id'=>$uid,'color'=>'diamond'])->lockForUpdate()->first();
            $have = (int)($row->quantity ?? 0);
            if ($have < 100) { abort(422, 'Not enough diamond tokens'); }
            if ($row) {
                $row->quantity = (int)$row->quantity - 100;
                if ($row->quantity <= 0) { $row->delete(); } else { $row->save(); }
            }
            $up->unlimited_energy = true; $up->save();
            return response()->json(['ok'=>true,'unlimited_energy'=>true]);
        });
    }
}
