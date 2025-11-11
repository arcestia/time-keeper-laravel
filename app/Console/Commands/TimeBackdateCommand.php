<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserTimeWallet;
use Carbon\CarbonImmutable;

class TimeBackdateCommand extends Command
{
    protected $signature = 'time:backdate {--hours=1} {--minutes=0} {--only-active}';

    protected $description = 'Backdate last_applied_at for wallets to simulate elapsed time for testing decay';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $minutes = (int) $this->option('minutes');
        $onlyActive = (bool) $this->option('only-active');

        $now = Carbon\CarbonImmutable::now();
        $delta = $hours * 3600 + $minutes * 60;
        if ($delta <= 0) {
            $this->error('Specify a positive backdate via --hours / --minutes');
            return self::INVALID;
        }

        $target = $now->subSeconds($delta);

        $q = UserTimeWallet::query();
        if ($onlyActive) {
            $q->where('is_active', true);
        }
        $updated = 0;
        $q->chunkById(500, function ($wallets) use (&$updated, $target) {
            foreach ($wallets as $w) {
                $w->last_applied_at = $target;
                $w->save();
                $updated++;
            }
        });

        $this->info("Backdated last_applied_at to {$target->toDateTimeString()} for {$updated} wallets.");
        return self::SUCCESS;
    }
}
