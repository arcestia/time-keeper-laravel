<?php

namespace App\Services;

use App\Models\TimeAccount;
use App\Models\TimeLedger;
use App\Models\UserTimeWallet;
use App\Models\TimeKeeperReserve;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TimeBankService
{
    private ?int $reserveId = null;

    private function reserve(): TimeKeeperReserve
    {
        return TimeKeeperReserve::query()->firstOrCreate([], ['balance_seconds' => 0]);
    }

    private function ensureReserveId(): int
    {
        if ($this->reserveId !== null) {
            return $this->reserveId;
        }
        $reserve = $this->reserve();
        $this->reserveId = $reserve->getKey();
        return $this->reserveId;
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
                $reserveId = $this->ensureReserveId();
                TimeKeeperReserve::query()->whereKey($reserveId)->increment('balance_seconds', $actualDecay);
            }
        });

        return $wallet;
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
        $now = CarbonImmutable::now();
        UserTimeWallet::query()
            ->select(['id', 'available_seconds', 'is_active', 'last_applied_at', 'drain_rate'])
            ->where('is_active', true)
            ->chunkById(1000, function ($wallets) use (&$count, $now) {
                foreach ($wallets as $wallet) {
                    $this->settleWallet($wallet, $now);
                    $count++;
                }
            });
        return $count;
    }

    public function bulkSettleActiveWallets(): array
    {
        $now = CarbonImmutable::now();
        $walletTable = (new UserTimeWallet())->getTable();
        $reserveId = $this->ensureReserveId();

        return DB::transaction(function () use ($now, $walletTable, $reserveId) {
            $nowSql = $now->toDateTimeString();

            $decayExpr = "LEAST(available_seconds, FLOOR(TIMESTAMPDIFF(SECOND, COALESCE(last_applied_at, '{$nowSql}'), '{$nowSql}') * drain_rate))";

            $sumSql = "SELECT 
                    COALESCE(SUM(GREATEST(0, {$decayExpr})), 0) AS total,
                    COUNT(*) AS affected,
                    COALESCE(SUM(CASE WHEN (available_seconds - {$decayExpr}) <= 0 THEN 1 ELSE 0 END), 0) AS deactivated
                FROM {$walletTable}
                WHERE is_active = 1";
            $row = collect(DB::select($sumSql))->first();
            $total = (int) ($row->total ?? 0);
            $affected = (int) ($row->affected ?? 0);
            $deactivated = (int) ($row->deactivated ?? 0);

            if ($affected > 0) {
                $updateSql = "UPDATE {$walletTable}
                    SET available_seconds = GREATEST(0, available_seconds - {$decayExpr}),
                        is_active = CASE WHEN (available_seconds - {$decayExpr}) <= 0 THEN 0 ELSE is_active END,
                        last_applied_at = '{$nowSql}'
                    WHERE is_active = 1";
                DB::statement($updateSql);

                if ($total > 0) {
                    TimeKeeperReserve::query()->whereKey($reserveId)->increment('balance_seconds', $total);
                }
            }

            return [
                'total_settled' => $total,
                'wallets_affected' => $affected,
                'deactivated' => $deactivated,
            ];
        });
    }
}
