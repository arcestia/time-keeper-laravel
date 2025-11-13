<?php

namespace App\Services;

use App\Models\UserDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsService
{
    private function todayUtcDate(): string
    {
        return Carbon::now('UTC')->toDateString();
    }

    private function ensureRow(int $userId, string $date): UserDailyStat
    {
        $row = UserDailyStat::where(['user_id'=>$userId,'date'=>$date])->lockForUpdate()->first();
        if (!$row) {
            $row = UserDailyStat::create(['user_id'=>$userId,'date'=>$date,'steps_count'=>0,'expeditions_completed'=>0]);
        }
        return $row;
    }

    public function addSteps(int $userId, int $delta): UserDailyStat
    {
        $date = $this->todayUtcDate();
        return DB::transaction(function() use($userId,$date,$delta){
            $row = $this->ensureRow($userId, $date);
            $row->steps_count = max(0, (int)$row->steps_count + max(0,$delta));
            $row->save();
            return $row;
        });
    }

    public function incExpCompleted(int $userId): UserDailyStat
    {
        $date = $this->todayUtcDate();
        return DB::transaction(function() use($userId,$date){
            $row = $this->ensureRow($userId, $date);
            $row->expeditions_completed = (int)$row->expeditions_completed + 1;
            $row->save();
            return $row;
        });
    }

    private function rangeFor(string $period, ?string $dateStr=null): array
    {
        $ref = $dateStr ? Carbon::parse($dateStr, 'UTC') : Carbon::now('UTC');
        if ($period === 'daily') {
            $start = $ref->copy()->startOfDay(); $end = $ref->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            // last 7 days ending on ref date (UTC)
            $start = $ref->copy()->startOfDay()->subDays(6); $end = $ref->copy()->endOfDay();
        } else { // monthly
            $start = $ref->copy()->startOfMonth(); $end = $ref->copy()->endOfMonth();
        }
        return [$start->toDateString(), $end->toDateString()];
    }

    public function leaderboard(string $period, string $metric, int $limit=25, ?string $refDate=null)
    {
        [$startDate, $endDate] = $this->rangeFor($period, $refDate);
        $col = $metric === 'steps' ? 'steps_count' : 'expeditions_completed';
        $rows = DB::table('user_daily_stats as uds')
            ->join('users as u', 'u.id', '=', 'uds.user_id')
            ->select('u.username', 'uds.user_id', DB::raw('SUM(uds.' . $col . ') as total'))
            ->whereBetween('uds.date', [$startDate, $endDate])
            ->groupBy('uds.user_id','u.username')
            ->orderByDesc('total')
            ->orderBy('u.username')
            ->limit($limit)
            ->get();
        return $rows->map(function($r, $idx){
            $r->rank = $idx + 1; return $r;
        });
    }
}
