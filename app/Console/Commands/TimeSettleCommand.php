<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TimeBankService;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;
use Carbon\CarbonImmutable;

class TimeSettleCommand extends Command
{
    protected $signature = 'time:settle {--trace} {--limit=10}';

    protected $description = 'Apply time decay to all active wallets once';

    public function handle(TimeBankService $bank): int
    {
        $processed = 0;
        $decayed = 0;
        $deactivated = 0;
        $verbose = (bool) $this->option('trace');
        $limit = (int) $this->option('limit');
        $printed = 0;
        $now = CarbonImmutable::now();

        UserTimeWallet::query()->where('is_active', true)->chunkById(200, function ($wallets) use ($bank, &$processed, &$decayed, &$deactivated, $verbose, $limit, &$printed, $now) {
            foreach ($wallets as $wallet) {
                $before = (int) $wallet->available_seconds;
                $wasActive = (bool) $wallet->is_active;
                $elapsed = max(0, $now->diffInSeconds($wallet->last_applied_at ?: $now));
                $rate = (float) $wallet->drain_rate;
                $expected = (int) floor($elapsed * $rate);
                $bank->settleWallet($wallet);
                $after = (int) $wallet->fresh()->available_seconds;
                $processed++;
                $d = max(0, $before - $after);
                $decayed += $d;
                if ($wasActive && $after === 0) {
                    $deactivated++;
                }

                if ($verbose && $printed < $limit) {
                    $printed++;
                    $this->line(sprintf(
                        'wallet_id=%d before=%d after=%d elapsed=%d rate=%.3f expected=%d actual=%d last_applied_at=%s',
                        $wallet->id,
                        $before,
                        $after,
                        $elapsed,
                        $rate,
                        $expected,
                        $d,
                        (string) $wallet->last_applied_at
                    ));
                }
            }
        });

        $reserve = optional(TimeKeeperReserve::query()->first())->balance_seconds ?? 0;

        $this->info("Settled wallets: {$processed}");
        $this->info("Total decayed seconds: {$decayed}");
        $this->info("Deactivated wallets: {$deactivated}");
        $this->info("Reserve balance (seconds): {$reserve}");

        return self::SUCCESS;
    }
}
