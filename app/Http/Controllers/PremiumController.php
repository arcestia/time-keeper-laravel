<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Flasher\Laravel\Facade\Flasher;
use App\Services\PremiumService;
use App\Services\TimeBankService;
use App\Models\Premium;
use App\Models\TimeAccount;
use App\Models\UserTimeWallet;
use App\Models\UserStats;
use App\Models\Setting;

class PremiumController extends Controller
{
    public function page()
    {
        return view('premium.index');
    }

    public function preview(): JsonResponse
    {
        $user = Auth::user();
        // Block purchases for lifetime or Tier 20 users
        $p = \App\Services\PremiumService::getOrCreate($user->id);
        $currentTier = \App\Services\PremiumService::tierFor((int)$p->premium_seconds_accumulated);
        if ($p->lifetime || $currentTier >= 20) {
            return response()->json(['ok' => false, 'message' => 'Premium purchases are disabled for Lifetime or Tier 20 users'], 422);
        }
        $amountStr = (string) request()->input('amount', '');
        $source = (string) request()->input('source', 'bank');
        $seconds = \App\Support\TimeUnits::parseToSeconds($amountStr) ?? 0;
        if ($seconds <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid amount'], 422);
        }
        $min = (int) (Setting::get('premium.min_purchase_seconds', 3600) ?? 3600);
        if ($seconds < $min) {
            return response()->json(['ok' => false, 'message' => 'Minimum purchase is ' . $min . ' seconds'], 422);
        }
        [$premUnit, $bankUnit] = $this->ratio();
        $cost = (int) ceil($seconds * $bankUnit / $premUnit);
        $bank = TimeAccount::where('user_id', $user->id)->first();
        $bankSeconds = (int)($bank->base_balance_seconds ?? 0);
        // Wallet display balance accounts for decay
        $wallet = UserTimeWallet::where('user_id', $user->id)->first();
        $walletDisplay = 0;
        if ($wallet) {
            $svc = app(TimeBankService::class);
            $walletDisplay = (int) $svc->getWalletDisplayBalance($wallet);
        }
        $canAfford = $source === 'wallet' ? ($walletDisplay >= $cost) : ($bankSeconds >= $cost);
        return response()->json([
            'ok' => true,
            'seconds' => (int)$seconds,
            'cost_seconds' => (int)$cost,
            'bank_seconds' => $bankSeconds,
            'wallet_seconds' => $walletDisplay,
            'source' => $source,
            'can_afford' => $canAfford,
        ]);
    }

    public function status(): JsonResponse
    {
        $user = Auth::user();
        $p = PremiumService::getOrCreate($user->id);
        PremiumService::weeklyResetIfNeeded($p);
        $tier = PremiumService::tierFor((int)$p->premium_seconds_accumulated);
        $benefits = PremiumService::benefitsForTier($tier);
        $thresholds = PremiumService::thresholds();
        $prevReq = (int)($thresholds[$tier] ?? 0);
        $nextTier = $tier < 20 ? ($tier + 1) : null;
        $nextReq = $nextTier ? (int)($thresholds[$nextTier] ?? 0) : null;
        $progress = null;
        if ($nextTier && $nextReq && $nextReq > $prevReq) {
            $num = max(0, (int)$p->premium_seconds_accumulated - $prevReq);
            $den = max(1, $nextReq - $prevReq);
            $progress = min(1, $num / $den);
        }
        $remaining = (int) PremiumService::remainingSeconds($p);
        return response()->json([
            'active' => $p->lifetime || $remaining > 0,
            'lifetime' => (bool)$p->lifetime,
            'active_seconds' => $remaining,
            'expires_at' => optional($p->premium_expires_at)->toIso8601String(),
            'accumulated_seconds' => (int)$p->premium_seconds_accumulated,
            'tier' => (int)$tier,
            'benefits' => $benefits,
            'next_tier' => $nextTier,
            'next_required_seconds' => $nextReq,
            'progress_to_next' => $progress,
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
        // Block purchases for lifetime or Tier 20 users
        $pExisting = \App\Services\PremiumService::getOrCreate($user->id);
        $tierExisting = \App\Services\PremiumService::tierFor((int)$pExisting->premium_seconds_accumulated);
        if ($pExisting->lifetime || $tierExisting >= 20) {
            Flasher::addError('Premium purchases are disabled for Lifetime or Tier 20 users');
            return response()->json(['ok' => false, 'message' => 'Premium purchases are disabled for Lifetime or Tier 20 users'], 422);
        }
        $amountStr = (string) request()->input('amount', '');
        $source = (string) request()->input('source', 'bank'); // 'bank' or 'wallet'
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
        if ($source !== 'wallet') { $source = 'bank'; }

        $result = DB::transaction(function () use ($user, $seconds, $cost, $source) {
            $bank = TimeAccount::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$bank) { $bank = TimeAccount::create(['user_id' => $user->id, 'base_balance_seconds' => 0]); }

            $wallet = UserTimeWallet::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserTimeWallet::create(['user_id' => $user->id, 'available_seconds' => 0, 'is_active' => false, 'drain_rate' => 1.000]);
            }

            if ($source === 'wallet') {
                $svc = app(TimeBankService::class);
                // Settle wallet to reflect current display balance
                $svc->settleWallet($wallet);
                $wallet->refresh();
                $display = $svc->getWalletDisplayBalance($wallet);
                if ($display < $cost) {
                    Flasher::addError('Not enough wallet balance');
                    abort(422, 'Not enough wallet balance');
                }
                // Deduct from wallet
                $wallet->available_seconds = (int) $wallet->available_seconds - $cost;
                if ($wallet->available_seconds < 0) { $wallet->available_seconds = 0; }
                $wallet->save();
            } else {
                if ((int)$bank->base_balance_seconds < $cost) {
                    Flasher::addError('Not enough bank balance');
                    abort(422, 'Not enough bank balance');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $cost;
                $bank->save();
            }

            $p = PremiumService::getOrCreate($user->id);
            PremiumService::grant($p, $seconds);

            return [$bank, $wallet, $p, $cost, $seconds, $source];
        });

        [$bank, $wallet, $p, $cost, $seconds, $source] = $result;
        // Refresh to ensure we have the committed expiration
        $p->refresh();
        Flasher::addSuccess('Premium purchased');
        return response()->json([
            'ok' => true,
            'cost_seconds' => (int)$cost,
            'granted_seconds' => (int)$seconds,
            'premium_active_seconds' => (int) PremiumService::remainingSeconds($p),
            'premium_tier' => PremiumService::tierFor((int)$p->premium_seconds_accumulated),
            'bank_seconds' => (int)$bank->base_balance_seconds,
            'wallet_seconds' => (int) app(TimeBankService::class)->getWalletDisplayBalance($wallet->fresh()),
            'source' => $source,
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
            // Heal: restore health to current premium cap percent
            $cap = \App\Services\PremiumService::statsCapPercentForUser($user->id);
            $stats->health = (int) $cap;
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
