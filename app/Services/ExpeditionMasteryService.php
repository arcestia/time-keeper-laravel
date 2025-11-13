<?php
namespace App\Services;

use App\Models\UserExpeditionMastery;

class ExpeditionMasteryService
{
    public function getOrCreate(int $userId): UserExpeditionMastery
    {
        $m = UserExpeditionMastery::where('user_id', $userId)->first();
        if (!$m) {
            $m = UserExpeditionMastery::create([
                'user_id' => $userId,
                'level' => 1,
                'xp' => 0,
                'total_xp' => 0,
            ]);
        }
        return $m;
    }

    public function addXp(int $userId, int $amount): UserExpeditionMastery
    {
        $cfg = config('expedition_mastery');
        $perLevelFlat = (int)($cfg['xp_per_level'] ?? 100);
        $progressive = (bool)($cfg['xp_progressive'] ?? false);
        $base = (int)($cfg['xp_per_level_base'] ?? $perLevelFlat);
        $inc = (int)($cfg['xp_per_level_increment'] ?? 0);
        $maxLevel = (int)($cfg['max_level'] ?? 20);

        $m = $this->getOrCreate($userId);
        $m->xp = (int)$m->xp + max(0, (int)$amount);
        $m->total_xp = (int)$m->total_xp + max(0, (int)$amount);
        // level up while enough xp and below cap
        while ($m->level < $maxLevel) {
            $req = $progressive
                ? max(1, (int)($base + $inc * max(0, ((int)$m->level - 1))))
                : max(1, $perLevelFlat);
            if ($m->xp < $req) break;
            $m->xp -= $req;
            $m->level += 1;
        }
        $m->save();
        return $m;
    }

    public function bonusesForLevel(int $level): array
    {
        $cfg = config('expedition_mastery');
        $xpPctPerLvl = (float)($cfg['xp_bonus_per_level_pct'] ?? 2.0);
        $maxExtraSlots = max(0, (int)($cfg['max_extra_slots'] ?? 3));
        $maxLevel = max(1, (int)($cfg['max_level'] ?? 20));

        $xpMult = 1.0 + max(0, $level - 1) * ($xpPctPerLvl / 100.0);
        // Progressive extra slots: scale linearly with level towards max_extra_slots at max_level
        $extraSlots = 0;
        if ($maxLevel > 1) {
            $extraSlots = (int) floor((max(0, $level - 1) * $maxExtraSlots) / (max(1, $maxLevel - 1)));
        }
        $extraSlots = max(0, min($maxExtraSlots, $extraSlots));
        return [
            'xp_multiplier' => (float)$xpMult,
            'expedition_extra_slots' => (int)$extraSlots,
        ];
    }
}
