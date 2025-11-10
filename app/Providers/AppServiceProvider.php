<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\TimeAccount;
use App\Models\TimeLedger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::created(function (User $user) {
            $account = TimeAccount::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'base_balance_seconds' => 86400,
                    'last_applied_at' => now(),
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]
            );

            if ($account->wasRecentlyCreated) {
                TimeLedger::create([
                    'time_account_id' => $account->id,
                    'type' => 'credit',
                    'amount_seconds' => 86400,
                    'reason' => 'initial',
                ]);
            }
        });
    }
}
