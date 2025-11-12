<?php

namespace App\Services;

use App\Models\Premium;
use App\Models\User;
use Carbon\CarbonImmutable;
use App\Models\Setting;

class PremiumService
{
    // Tier thresholds in seconds (accumulated). T20 implies lifetime.
    public static function thresholds(): array
    {
        $fromDb = Setting::get('premium.tiers.thresholds_seconds', null);
        if (!is_array($fromDb)) { return []; }
        $th = [];
        for ($i = 1; $i <= 20; $i++) {
            $v = $fromDb[$i] ?? ($fromDb[(string)$i] ?? null);
            if ($v === null) { continue; }
            $th[$i] = (int)$v;
        }
        return $th;
    }

    public static function tierFor(int $accumulatedSeconds): int
    {
        $tier = 0;
        foreach (self::thresholds() as $t => $req) {
            if ($accumulatedSeconds >= $req) { $tier = $t; } else { break; }
        }
        return max(0, min(20, $tier));
    }

    public static function benefitsForTier(int $tier): array
    {
        if ($tier <= 0) return [
            'cap_multiplier' => 1.0,
            'reward_multiplier' => 1.0,
            'xp_multiplier' => 1.0,
            'store_discount_pct' => 0,
            'heals_per_week' => 0,
            'expedition_extra_slots' => 0,
            'expedition_total_slots' => 1,
        ];
        // Linear scaling
        $capMin = 1.20; $capMax = 11.00; // +20% -> +1000%
        $rewardMin = 1.05; $rewardMax = 2.50; // +5% -> +150%
        $xpMin = 1.05; $xpMax = 3.00; // +5% -> +200%
        $discMin = 1; $discMax = 30; // percent
        $steps = 19; $pos = ($tier - 1);
        $cap = $capMin + ($capMax - $capMin) * ($pos / $steps);
        $reward = $rewardMin + ($rewardMax - $rewardMin) * ($pos / $steps);
        $xp = $xpMin + ($xpMax - $xpMin) * ($pos / $steps);
        $discount = (int)round($discMin + ($discMax - $discMin) * ($pos / $steps));
        $heals = 0;
        if ($tier >= 5) {
            if ($tier >= 17) $heals = 5; else if ($tier >= 14) $heals = 4; else if ($tier >= 11) $heals = 3; else if ($tier >= 8) $heals = 2; else $heals = 1;
        }
        // Expedition extra slots: from tier 5 -> +1, up to tier 20 -> +10
        $extraSlots = 0;
        if ($tier >= 5) {
            $slotsMin = 1; $slotsMax = 10; // extra slots
            $slotSteps = 15; // tiers 5..20 inclusive => 16 tiers => 15 steps
            $slotPos = $tier - 5;
            $extraSlots = (int) floor($slotsMin + ($slotsMax - $slotsMin) * ($slotPos / $slotSteps));
            $extraSlots = max($slotsMin, min($slotsMax, $extraSlots));
        }
        return [
            'cap_multiplier' => (float)$cap,
            'reward_multiplier' => (float)$reward,
            'xp_multiplier' => (float)$xp,
            'store_discount_pct' => (int)$discount,
            'heals_per_week' => (int)$heals,
            'expedition_extra_slots' => (int)$extraSlots,
            'expedition_total_slots' => (int)(1 + $extraSlots),
        ];
    }

    public static function getOrCreate(int $userId): Premium
    {
        $p = Premium::where('user_id', $userId)->first();
        if (!$p) {
            $p = Premium::create([
                'user_id' => $userId,
                'premium_expires_at' => null,
                'premium_seconds_accumulated' => 0,
                'lifetime' => false,
                'weekly_heal_used' => 0,
                'weekly_heal_reset_at' => null,
            ]);
        }
        return $p;
    }

    public static function isActive(Premium $p): bool
    {
        if ($p->lifetime) return true;
        if (!$p->premium_expires_at) return false;
        return CarbonImmutable::now()->lt(CarbonImmutable::parse($p->premium_expires_at));
    }

    public static function grant(Premium $p, int $seconds): Premium
    {
        $add = max(0, $seconds);
        // Extend expiration from now or existing expiration, whichever is later
        $base = $p->premium_expires_at ? CarbonImmutable::parse($p->premium_expires_at) : CarbonImmutable::now();
        if ($base->lt(CarbonImmutable::now())) { $base = CarbonImmutable::now(); }
        $p->premium_expires_at = $base->addSeconds($add);
        $p->premium_seconds_accumulated = (int)$p->premium_seconds_accumulated + $add;
        $tier = self::tierFor((int)$p->premium_seconds_accumulated);
        if ($tier >= 20) { $p->lifetime = true; }
        $p->save();
        return $p;
    }

    public static function remainingSeconds(Premium $p): int
    {
        if ($p->lifetime) return PHP_INT_MAX;
        if (!$p->premium_expires_at) return 0;
        $now = CarbonImmutable::now();
        $exp = CarbonImmutable::parse($p->premium_expires_at);
        $delta = $exp->getTimestamp() - $now->getTimestamp();
        return $delta > 0 ? ($delta + 1) : 0;
    }

    public static function weeklyResetIfNeeded(Premium $p): void
    {
        $now = CarbonImmutable::now();
        if (!$p->weekly_heal_reset_at || $now->greaterThan($p->weekly_heal_reset_at)) {
            $p->weekly_heal_reset_at = $now->endOfWeek();
            $p->weekly_heal_used = 0;
            $p->save();
        }
    }

    // Returns integer percent cap for stats (e.g., 100 for non-premium, up to 1100 for T20)
    public static function statsCapPercentForUser(int $userId): int
    {
        $p = self::getOrCreate($userId);
        $mult = 1.0;
        if (self::isActive($p)) {
            $tier = self::tierFor((int)$p->premium_seconds_accumulated);
            $benefits = self::benefitsForTier($tier);
            $mult = (float)($benefits['cap_multiplier'] ?? 1.0);
        }
        return max(100, (int) floor(100 * $mult));
    }
}
