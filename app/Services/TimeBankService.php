<?php

namespace App\Services;

use App\Models\TimeAccount;
use App\Models\TimeLedger;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;
use App\Models\WalletLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TimeBankService
{
    private function reserve(): TimeKeeperReserve
    {
        return TimeKeeperReserve::query()->firstOrCreate([], ['balance_seconds' => 0]);
    }

    // Wallet decay now represents the per-second deduction source
    public function settleWallet(UserTimeWallet $wallet, ?CarbonImmutable $now = null): UserTimeWallet
    {
        $now = $now ?: CarbonImmutable::now();

        if (!$wallet->is_active) {
            return $wallet;
        }

        $last = $wallet->last_applied_at ? CarbonImmutable::parse($wallet->last_applied_at) : $now;
        $elapsed = max(0, $last->diffInSeconds($now));
        if ($elapsed <= 0) {
            if (!$wallet->last_applied_at) {
                $wallet->last_applied_at = $now;
                $wallet->save();
            }
            return $wallet;
        }

        $decay = (int) floor($elapsed * (float) $wallet->drain_rate);
        if ($decay <= 0) {
            $wallet->last_applied_at = $now;
            $wallet->save();
            return $wallet;
        }

        DB::transaction(function () use ($wallet, $decay, $now, $elapsed) {
            $before = (int) $wallet->available_seconds;
            $new = max(0, $before - $decay);
            $actualDecay = $before - $new;

            $wallet->available_seconds = $new;
            if ($new === 0) {
                $wallet->is_active = false;
            }
            $wallet->last_applied_at = $now;
            $wallet->save();

            if ($actualDecay > 0) {
                $reserve = $this->reserve();
                $reserve->balance_seconds = (int) $reserve->balance_seconds + $actualDecay;
                $reserve->save();

                WalletLedger::create([
                    'user_time_wallet_id' => $wallet->id,
                    'type' => 'decay',
                    'amount_seconds' => -$actualDecay,
                    'from_seconds' => $before,
                    'to_seconds' => $new,
                    'meta' => [
                        'elapsed_seconds' => $elapsed,
                        'drain_rate' => (float) $wallet->drain_rate,
                    ],
                ]);
            }
        });

        return $wallet->refresh();
    }

    public function getWalletDisplayBalance(UserTimeWallet $wallet, ?CarbonImmutable $now = null): int
    {
        $now = $now ?: CarbonImmutable::now();
        if (!$wallet->is_active) {
            return (int) $wallet->available_seconds;
        }
        $last = $wallet->last_applied_at ?: $now;
        $elapsed = max(0, $now->diffInSeconds($last));
        $decay = (int) floor($elapsed * (float) $wallet->drain_rate);
        return max(0, (int) $wallet->available_seconds - $decay);
    }

    // Bank balance does not decay; expose for controller use
    public function getDisplayBalance(TimeAccount $account, ?CarbonImmutable $now = null): int
    {
        return (int) $account->base_balance_seconds;
    }

    public function deposit(TimeAccount $account, int $seconds, string $reason = 'deposit'): TimeAccount
    {
        $seconds = max(0, $seconds);
        return DB::transaction(function () use ($account, $seconds, $reason) {
            $account->base_balance_seconds = (int) $account->base_balance_seconds + $seconds;
            $account->save();

            TimeLedger::create([
                'time_account_id' => $account->id,
                'type' => 'credit',
                'amount_seconds' => $seconds,
                'reason' => $reason,
            ]);

            return $account->refresh();
        });
    }

    public function withdraw(TimeAccount $account, int $seconds, string $reason = 'withdraw'): TimeAccount
    {
        $seconds = max(0, $seconds);
        return DB::transaction(function () use ($account, $seconds, $reason) {
            $new = max(0, (int) $account->base_balance_seconds - $seconds);
            $actualDebit = (int) $account->base_balance_seconds - $new;
            $account->base_balance_seconds = $new;
            $account->save();

            if ($actualDebit > 0) {
                TimeLedger::create([
                    'time_account_id' => $account->id,
                    'type' => 'debit',
                    'amount_seconds' => -$actualDebit,
                    'reason' => $reason,
                ]);
            }

            return $account->refresh();
        });
    }

    public function settleAllWallets(): int
    {
        $count = 0;
        UserTimeWallet::query()
            ->where('is_active', true)
            ->chunkById(200, function ($wallets) use (&$count) {
                foreach ($wallets as $wallet) {
                    $this->settleWallet($wallet);
                    $count++;
                }
            });
        return $count;
    }
}
