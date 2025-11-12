<?php

namespace App\Services;

use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public function nextXpForLevel(int $level): int
    {
        $level = max(1, $level);
        return 1000 + ($level - 1) * 50; // linear progression
    }

    public function getOrCreate(int $userId): UserProgress
    {
        return UserProgress::firstOrCreate(
            ['user_id' => $userId],
            ['level' => 1, 'xp' => 0, 'total_xp' => 0, 'next_xp' => $this->nextXpForLevel(1)]
        );
    }

    public function addXp(int $userId, int $amount): UserProgress
    {
        $amount = max(0, $amount);
        return DB::transaction(function () use ($userId, $amount) {
            // Lock the user's progress row; create if missing
            $p = UserProgress::query()->where('user_id', $userId)->lockForUpdate()->first();
            if (!$p) {
                $p = new UserProgress([
                    'user_id' => $userId,
                    'level' => 1,
                    'xp' => 0,
                    'next_xp' => $this->nextXpForLevel(1),
                ]);
                $p->save();
            }
            $p->xp = (int) $p->xp + $amount;
            $p->total_xp = (int) ($p->total_xp ?? 0) + $amount;
            while ($p->xp >= $p->next_xp) {
                $p->xp -= $p->next_xp;
                $p->level = (int) $p->level + 1;
                $p->next_xp = $this->nextXpForLevel((int) $p->level);
            }
            $p->save();
            return $p;
        });
    }
}
