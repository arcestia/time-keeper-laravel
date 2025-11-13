<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\StatsService;
use App\Models\UserTimeToken;

class AwardLeaderboardRewards extends Command
{
    protected $signature = 'leaderboard:award {--period=} {--date=} {--month=} {--dry-run}';
    protected $description = 'Award time token rewards to top users on leaderboards for daily/weekly/monthly.';

    public function handle(): int
    {
        $period = $this->option('period') ?: 'daily';
        if (!in_array($period, ['daily','weekly','monthly'], true)) {
            $this->error('Invalid --period');
            return self::FAILURE;
        }
        $cfg = config('leaderboard_rewards');
        $top = (int) ($cfg['top'] ?? 10);
        $periodCfg = $cfg['periods'][$period] ?? null;
        if (!$periodCfg) { $this->error('Missing period config'); return self::FAILURE; }
        $color = (string) ($periodCfg['color'] ?? 'red');
        $ranks = $periodCfg['ranks'] ?? [];
        $metrics = $cfg['metrics'] ?? ['steps','exp_completed'];
        $dry = (bool) $this->option('dry-run');

        [$refDate, $periodKey] = $this->computeRef($period);
        if ($this->option('date')) { $refDate = (string)$this->option('date'); $periodKey = $refDate; if ($period==='weekly') { $periodKey = $refDate.'-7d'; } }
        if ($this->option('month') && $period==='monthly') { $refDate = (string)$this->option('month'); $periodKey = $refDate; }

        $this->info("Awarding $period leaderboards for ref=$refDate (key=$periodKey), color=$color, top=$top".
            ($dry ? ' [DRY RUN]' : ''));

        foreach ($metrics as $metric) {
            if (!in_array($metric, ['steps','exp_completed'], true)) continue;
            $rows = app(StatsService::class)->leaderboard($period, $metric === 'steps' ? 'steps' : 'expeditions_completed', $top, $refDate);
            $i = 0; $awarded = 0;
            foreach ($rows as $r) {
                if ($i >= $top) break;
                $qty = (int)($ranks[$i] ?? 0);
                if ($qty <= 0) { $i++; continue; }
                $userId = (int)$r->user_id;
                $rank = $i + 1;
                if ($dry) {
                    $this->line("Would award user #$userId rank $rank $qty x $color ($metric) for $periodKey");
                    $awarded++; $i++; continue;
                }
                DB::transaction(function () use ($period, $metric, $periodKey, $userId, $rank, $color, $qty, &$awarded) {
                    // idempotency check via unique constraint
                    $exists = DB::table('leaderboard_rewards')
                        ->where(['period'=>$period,'metric'=>$metric,'period_key'=>$periodKey,'user_id'=>$userId])
                        ->lockForUpdate()->first();
                    if ($exists) { return; }
                    $tok = UserTimeToken::query()->where(['user_id'=>$userId,'color'=>$color])->lockForUpdate()->first();
                    if (!$tok) { $tok = UserTimeToken::create(['user_id'=>$userId,'color'=>$color,'quantity'=>0]); }
                    $tok->quantity = (int)$tok->quantity + $qty; $tok->save();
                    DB::table('leaderboard_rewards')->insert([
                        'period' => $period,
                        'metric' => $metric,
                        'period_key' => $periodKey,
                        'user_id' => $userId,
                        'rank' => $rank,
                        'token_color' => $color,
                        'quantity' => $qty,
                        'created_at' => now('UTC'),
                        'updated_at' => now('UTC'),
                    ]);
                    $awarded++;
                });
                $i++;
            }
            $this->info("$metric: awarded $awarded users");
        }

        return self::SUCCESS;
    }

    private function computeRef(string $period): array
    {
        $now = Carbon::now('UTC');
        if ($period === 'daily') {
            $ref = $now->copy()->subDay();
            return [$ref->toDateString(), $ref->toDateString()];
        }
        if ($period === 'weekly') {
            // last week ending Sunday; run on Monday
            $ref = $now->copy()->previous(Carbon::SUNDAY);
            return [$ref->toDateString(), $ref->toDateString().'-7d'];
        }
        // monthly: previous month key YYYY-MM
        $ref = $now->copy()->subMonth()->startOfMonth();
        return [$ref->format('Y-m'), $ref->format('Y-m')];
    }
}
