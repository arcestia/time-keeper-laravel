<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\TimeAccount;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;
use App\Models\UserStats;

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
        // Secure Horizon dashboard to admins only (when Horizon is installed)
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                $user = $request->user();
                return $user && !empty($user->is_admin);
            });
        }

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

            // Initialize player stats at 100%
            UserStats::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'energy' => 100,
                    'food' => 100,
                    'water' => 100,
                    'leisure' => 100,
                    'health' => 100,
                ]
            );
        });
    }
}
