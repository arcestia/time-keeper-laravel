<?php

namespace App\Services;

use App\Models\TimeAccount;
use App\Models\TimeLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TimeBankService
{
    public function settle(TimeAccount $account, ?CarbonImmutable $now = null): TimeAccount
    {
        $now = $now ?: CarbonImmutable::now();

        if (!$account->is_active) {
            return $account;
        }

        $elapsed = max(0, $now->diffInSeconds($account->last_applied_at));
        if ($elapsed <= 0) {
            return $account;
        }

        $decay = (int) floor($elapsed * (float) $account->drain_rate);
        if ($decay <= 0) {
            $account->last_applied_at = $now;
            $account->save();
            return $account;
        }

        DB::transaction(function () use ($account, $decay, $now) {
            $new = max(0, (int) $account->base_balance_seconds - $decay);

            if ($new < (int) $account->base_balance_seconds) {
                TimeLedger::create([
                    'time_account_id' => $account->id,
                    'type' => 'decay',
                    'amount_seconds' => -($account->base_balance_seconds - $new),
                    'reason' => 'auto-decay',
                    'meta' => [
                        'from' => (int) $account->base_balance_seconds,
                        'to' => $new,
                    ],
                ]);
            }

            $account->base_balance_seconds = $new;
            $account->last_applied_at = $now;
            $account->save();
        });

        return $account->refresh();
    }

    public function getDisplayBalance(TimeAccount $account, ?CarbonImmutable $now = null): int
    {
        $now = $now ?: CarbonImmutable::now();
        if (!$account->is_active) {
            return (int) $account->base_balance_seconds;
        }
        $elapsed = max(0, $now->diffInSeconds($account->last_applied_at));
        $decay = (int) floor($elapsed * (float) $account->drain_rate);
        return max(0, (int) $account->base_balance_seconds - $decay);
    }

    public function deposit(TimeAccount $account, int $seconds, string $reason = 'deposit'): TimeAccount
    {
        $seconds = max(0, $seconds);
        return DB::transaction(function () use ($account, $seconds, $reason) {
            $this->settle($account);
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
            $this->settle($account);
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

    public function settleAllActive(): int
    {
        $count = 0;
        TimeAccount::query()
            ->where('is_active', true)
            ->chunkById(200, function ($accounts) use (&$count) {
                foreach ($accounts as $account) {
                    $this->settle($account);
                    $count++;
                }
            });
        return $count;
    }
}
