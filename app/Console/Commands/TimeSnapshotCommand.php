<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeSnapshot;
use App\Models\TimeAccount;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;
use App\Services\TimeBankService;
use Carbon\CarbonImmutable;

class TimeSnapshotCommand extends Command
{
    protected $signature = 'time:snapshot {--window=1440}';

    protected $description = 'Record a time snapshot of reserve, total wallet (display), and total bank balances';

    public function handle(TimeBankService $bank): int
    {
        $now = CarbonImmutable::now();

        // Totals
        $reserve = (int) optional(TimeKeeperReserve::query()->first())->balance_seconds;
        $totalBank = (int) TimeAccount::query()->sum('base_balance_seconds');

        // Sum wallet display balances (decayed)
        $totalWalletDisplay = 0;
        UserTimeWallet::query()
            ->select(['id','available_seconds','is_active','last_applied_at','drain_rate'])
            ->chunkById(1000, function ($wallets) use (&$totalWalletDisplay, $bank, $now) {
                foreach ($wallets as $w) {
                    $totalWalletDisplay += $bank->getWalletDisplayBalance($w, $now);
                }
            });

        TimeSnapshot::create([
            'captured_at' => $now,
            'reserve_seconds' => $reserve,
            'total_wallet_seconds' => $totalWalletDisplay,
            'total_bank_seconds' => $totalBank,
        ]);

        // Trim window (keep last N by captured_at)
        $window = max(10, (int) $this->option('window'));
        $idsToKeep = TimeSnapshot::query()
            ->orderByDesc('captured_at')
            ->limit($window)
            ->pluck('id')
            ->all();
        if (!empty($idsToKeep)) {
            TimeSnapshot::query()->whereNotIn('id', $idsToKeep)->delete();
        }

        $this->info('Snapshot recorded at ' . $now->toDateTimeString());
        return self::SUCCESS;
    }
}
