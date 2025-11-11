<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\TimeAccount;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;

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
            TimeAccount::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'base_balance_seconds' => 0,
                    'last_applied_at' => now(),
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]
            );

            UserTimeWallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'available_seconds' => 864000,
                    'last_applied_at' => now(),
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]
            );

            $reserve = TimeKeeperReserve::firstOrCreate([], ['balance_seconds' => 0]);
            $reserve->balance_seconds = (int) $reserve->balance_seconds - 864000;
            $reserve->save();
        });
    }
}
