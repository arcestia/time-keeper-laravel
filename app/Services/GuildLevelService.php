<?php

namespace App\Services;

use App\Models\Guild;
use Illuminate\Support\Facades\DB;

class GuildLevelService
{
    public function nextXpForLevel(int $level): int
    {
        $level = max(1, $level);
        return 10000 + ($level - 1) * 500;
    }

    public function addXp(Guild $guild, int $xp): Guild
    {
        $xp = max(0, $xp);
        if ($xp === 0) {
            return $guild;
        }

        return DB::transaction(function () use ($guild, $xp) {
            /** @var Guild $g */
            $g = Guild::lockForUpdate()->findOrFail($guild->id);
            $g->total_xp = (int) $g->total_xp + $xp;
            $g->xp = (int) $g->xp + $xp;

            $level = (int) ($g->level ?? 1);
            $next = (int) ($g->next_xp ?: $this->nextXpForLevel($level));
            $maxLevel = 1000;

            while ($g->xp >= $next && $level < $maxLevel) {
                $g->xp -= $next;
                $level++;
                $next = $this->nextXpForLevel($level);
            }

            // At cap: keep level fixed and next_xp at cap value; xp can keep accumulating visually within the bar or be clamped if desired.
            if ($level >= $maxLevel) {
                $level = $maxLevel;
                $next = $this->nextXpForLevel($maxLevel);
            }

            $g->level = $level;
            $g->next_xp = $next;
            $g->save();

            return $g;
        });
    }
}
