<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Flasher\Laravel\Facade\Flasher;
use App\Services\PremiumService;
use App\Models\Premium;
use App\Models\TimeAccount;
use App\Models\UserStats;
use App\Models\Setting;

class PremiumController extends Controller
{
    public function page()
    {
        return view('premium.index');
    }

    public function status(): JsonResponse
    {
        $user = Auth::user();
        $p = PremiumService::getOrCreate($user->id);
        PremiumService::weeklyResetIfNeeded($p);
        $tier = PremiumService::tierFor((int)$p->premium_seconds_accumulated);
        $benefits = PremiumService::benefitsForTier($tier);
        return response()->json([
            'active' => PremiumService::isActive($p),
            'lifetime' => (bool)$p->lifetime,
            'active_seconds' => (int) PremiumService::remainingSeconds($p),
            'accumulated_seconds' => (int)$p->premium_seconds_accumulated,
            'tier' => (int)$tier,
            'benefits' => $benefits,
            'weekly_heal_used' => (int)$p->weekly_heal_used,
            'weekly_heal_reset_at' => optional($p->weekly_heal_reset_at)->toIso8601String(),
        ]);
    }

    // Default ratio 1:3 (1 premium sec costs 3 bank seconds). Read from settings key 'premium.price_ratio'.
    protected function ratio(): array {
        $cfg = Setting::get('premium.price_ratio', ['premium' => 1, 'bank' => 3]);
        if (is_array($cfg)) {
            $prem = (int)($cfg['premium'] ?? 1);
            $bank = (int)($cfg['bank'] ?? 3);
            if ($prem > 0 && $bank > 0) return [$prem, $bank];
        }
        return [1, 3];
    }

    public function buy(): JsonResponse
    {
        $user = Auth::user();
        $amountStr = (string) request()->input('amount', '');
        // Parse simple formats; reuse TimeUnits if available, else seconds integer
        $seconds = \App\Support\TimeUnits::parseToSeconds($amountStr) ?? 0;
        if ($seconds <= 0) {
            Flasher::addError('Invalid amount');
            return response()->json(['ok' => false, 'message' => 'Invalid amount'], 422);
        }
        $min = (int) (Setting::get('premium.min_purchase_seconds', 3600) ?? 3600);
        if ($seconds < $min) {
            Flasher::addError('Minimum purchase is ' . $min . ' seconds');
            return response()->json(['ok' => false, 'message' => 'Minimum purchase is ' . $min . ' seconds'], 422);
        }
        [$premUnit, $bankUnit] = $this->ratio();
        $cost = (int) ceil($seconds * $bankUnit / $premUnit);

        $result = DB::transaction(function () use ($user, $seconds, $cost) {
            $bank = TimeAccount::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$bank) { $bank = TimeAccount::create(['user_id' => $user->id, 'base_balance_seconds' => 0]); }
            if ((int)$bank->base_balance_seconds < $cost) {
                Flasher::addError('Not enough bank balance');
                abort(422, 'Not enough bank balance');
            }
            $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $cost;
            $bank->save();

            $p = PremiumService::getOrCreate($user->id);
            PremiumService::grant($p, $seconds);

            return [$bank, $p, $cost, $seconds];
        });

        [$bank, $p, $cost, $seconds] = $result;
        Flasher::addSuccess('Premium purchased');
        return response()->json([
            'ok' => true,
            'cost_seconds' => (int)$cost,
            'granted_seconds' => (int)$seconds,
            'premium_active_seconds' => (int) PremiumService::remainingSeconds($p),
            'premium_tier' => PremiumService::tierFor((int)$p->premium_seconds_accumulated),
            'bank_seconds' => (int)$bank->base_balance_seconds,
        ]);
    }

    public function heal(): JsonResponse
    {
        $user = Auth::user();
        $result = DB::transaction(function () use ($user) {
            $p = PremiumService::getOrCreate($user->id);
            PremiumService::weeklyResetIfNeeded($p);
            $tier = PremiumService::tierFor((int)$p->premium_seconds_accumulated);
            $benefits = PremiumService::benefitsForTier($tier);
            $allowed = (int)($benefits['heals_per_week'] ?? 0);
            if ($allowed <= 0 || !PremiumService::isActive($p)) {
                Flasher::addError('Heal not available');
                abort(422, 'Heal not available');
            }
            if ((int)$p->weekly_heal_used >= $allowed) {
                Flasher::addError('Weekly heal limit reached');
                abort(422, 'Weekly heal limit reached');
            }
            $stats = UserStats::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id' => $user->id, 'energy' => 100, 'food' => 100, 'water' => 100, 'leisure' => 100, 'health' => 100]); }
            // Heal: restore health to 100%
            $stats->health = 100;
            $stats->save();
            $p->weekly_heal_used = (int)$p->weekly_heal_used + 1;
            $p->save();
            return [$stats, $p];
        });
        [$stats, $p] = $result;
        Flasher::addSuccess('Healed to full health');
        return response()->json(['ok' => true, 'health' => (int)$stats->health, 'weekly_heal_used' => (int)$p->weekly_heal_used]);
    }
}
