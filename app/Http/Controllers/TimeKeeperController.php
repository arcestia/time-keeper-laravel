<?php

namespace App\Http\Controllers;

use App\Models\TimeAccount;
use App\Models\User;
use App\Models\UserTimeWallet;
use App\Services\TimeBankService;
use App\Support\TimeUnits;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\TimeKeeperReserve;
use Illuminate\Support\Facades\Auth;
use Flasher\Laravel\Facade\Flasher;
use App\Models\TimeSnapshot;

class TimeKeeperController extends Controller
{
    public function __construct(private TimeBankService $bank)
    {
    }

    public function page()
    {
        return view('keeper.index');
    }

    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $bankAccounts = TimeAccount::count();
        $activeWalletUsers = UserTimeWallet::where('is_active', true)->count();

        // Sum bank balances
        $totalBankSeconds = (int) TimeAccount::query()->sum('base_balance_seconds');

        // Sum wallet display balances (decayed)
        $totalWalletSeconds = 0;
        UserTimeWallet::query()->chunkById(500, function ($wallets) use (&$totalWalletSeconds) {
            foreach ($wallets as $wallet) {
                $totalWalletSeconds += $this->bank->getWalletDisplayBalance($wallet);
            }
        });

        // Additional stats
        $zeroWallets = UserTimeWallet::where('available_seconds', 0)->count();
        $avgBank = $bankAccounts > 0 ? (int) floor($totalBankSeconds / $bankAccounts) : 0;
        $avgWallet = $totalUsers > 0 ? (int) floor($totalWalletSeconds / max(1, UserTimeWallet::count())) : 0;
        $reserve = TimeKeeperReserve::query()->first();
        $reserveSeconds = (int) optional($reserve)->balance_seconds;

        return response()->json([
            'total_users' => $totalUsers,
            'active_users' => $activeWalletUsers,
            'users_with_bank_accounts' => $bankAccounts,
            'total_wallet_seconds' => $totalWalletSeconds,
            'total_wallet_formatted' => TimeUnits::compactColon($totalWalletSeconds),
            'total_bank_seconds' => $totalBankSeconds,
            'total_bank_formatted' => TimeUnits::compactColon($totalBankSeconds),
            'zero_wallets' => $zeroWallets,
            'avg_wallet_seconds' => $avgWallet,
            'avg_wallet_formatted' => TimeUnits::compactColon($avgWallet),
            'avg_bank_seconds' => $avgBank,
            'avg_bank_formatted' => TimeUnits::compactColon($avgBank),
            'reserve_seconds' => $reserveSeconds,
            'reserve_formatted' => TimeUnits::compactColon($reserveSeconds),
        ]);
    }

    public function snapshots(): JsonResponse
    {
        $limit = max(30, (int) request('limit', 360));
        $rows = TimeSnapshot::query()
            ->orderByDesc('captured_at')
            ->limit($limit)
            ->get(['captured_at','reserve_seconds','total_wallet_seconds','total_bank_seconds'])
            ->reverse()
            ->values();

        return response()->json([
            'labels' => $rows->pluck('captured_at')->map(fn($d) => $d->toDateTimeString())->all(),
            'reserve' => $rows->pluck('reserve_seconds')->all(),
            'wallet' => $rows->pluck('total_wallet_seconds')->all(),
            'bank' => $rows->pluck('total_bank_seconds')->all(),
        ]);
    }

    public function adminDepositFromUserToReserve(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $data = request()->validate([
            'username' => ['required','string'],
            'amount' => ['required','string'],
        ]);

        $toSeconds = TimeUnits::parseToSeconds($data['amount']);
        if ($toSeconds <= 0) {
            Flasher::addError('Amount must be > 0');
            session()->flash('error', 'Amount must be > 0');
            return response()->json(['message' => 'Amount must be > 0'], 422);
        }

        return DB::transaction(function () use ($data, $toSeconds) {
            $targetUser = \App\Models\User::where('username', $data['username'])->firstOrFail();
            // Lock both rows to avoid races
            $account = TimeAccount::query()->where('user_id', $targetUser->id)->lockForUpdate()->first();
            if (!$account) {
                $account = TimeAccount::create(['user_id' => $targetUser->id, 'base_balance_seconds' => 0]);
            }
            $amount = min($toSeconds, (int) $account->base_balance_seconds);
            if ($amount <= 0) {
                Flasher::addError('User bank has insufficient balance');
                session()->flash('error', 'User bank has insufficient balance');
                return response()->json(['message' => 'User bank has insufficient balance'], 422);
            }
            $account->base_balance_seconds = (int) $account->base_balance_seconds - $amount;
            $account->save();

            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $reserve->balance_seconds = (int) $reserve->balance_seconds + $amount;
            $reserve->save();

            Flasher::addSuccess('Deposited ' . $amount . 's from bank to reserve for ' . $data['username']);
            session()->flash('success', 'Deposited ' . $amount . 's from bank to reserve for ' . $data['username']);
            return response()->json(['status' => 'ok', 'moved_seconds' => $amount, 'reserve' => (int) $reserve->balance_seconds, 'user_bank' => (int) $account->base_balance_seconds]);
        });
    }

    public function adminWithdrawFromReserveToUser(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $data = request()->validate([
            'username' => ['required','string'],
            'amount' => ['required','string'],
        ]);

        $toSeconds = TimeUnits::parseToSeconds($data['amount']);
        if ($toSeconds <= 0) {
            return response()->json(['message' => 'Amount must be > 0'], 422);
        }

        return DB::transaction(function () use ($data, $toSeconds) {
            $targetUser = \App\Models\User::where('username', $data['username'])->firstOrFail();
            $account = TimeAccount::query()->where('user_id', $targetUser->id)->lockForUpdate()->first();
            if (!$account) {
                $account = TimeAccount::create(['user_id' => $targetUser->id, 'base_balance_seconds' => 0]);
            }

            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $amount = min($toSeconds, max(0, (int) $reserve->balance_seconds));
            if ($amount <= 0) {
                Flasher::addError('Reserve has insufficient balance');
                session()->flash('error', 'Reserve has insufficient balance');
                return response()->json(['message' => 'Reserve has insufficient balance'], 422);
            }
            $reserve->balance_seconds = (int) $reserve->balance_seconds - $amount;
            $reserve->save();

            $account->base_balance_seconds = (int) $account->base_balance_seconds + $amount;
            $account->save();

            Flasher::addSuccess('Withdrew ' . $amount . 's from reserve to bank for ' . $data['username']);
            session()->flash('success', 'Withdrew ' . $amount . 's from reserve to bank for ' . $data['username']);
            return response()->json(['status' => 'ok', 'moved_seconds' => $amount, 'reserve' => (int) $reserve->balance_seconds, 'user_bank' => (int) $account->base_balance_seconds]);
        });
    }

    public function adminDistributeReserveToAll(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);

        $data = request()->validate([
            'amount' => ['required','string'], // per-user amount
        ]);

        $perUser = TimeUnits::parseToSeconds($data['amount']);
        if ($perUser <= 0) {
            Flasher::addError('Amount must be > 0');
            session()->flash('error', 'Amount must be > 0');
            return response()->json(['message' => 'Amount must be > 0'], 422);
        }

        $totalUsers = User::count();
        if ($totalUsers <= 0) {
            Flasher::addError('No users to distribute to');
            session()->flash('error', 'No users to distribute to');
            return response()->json(['message' => 'No users to distribute to'], 422);
        }

        $result = DB::transaction(function () use ($perUser, $totalUsers) {
            // Lock reserve row, recalc caps with current value
            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $reserveSeconds = (int) $reserve->balance_seconds;
            $required = $perUser * $totalUsers;
            if ($required > $reserveSeconds) {
                $perUser = intdiv($reserveSeconds, $totalUsers);
            }
            if ($perUser <= 0) {
                Flasher::addError('Reserve too low for distribution');
                session()->flash('error', 'Reserve too low for distribution');
                return ['ok' => false, 'message' => 'Reserve too low for distribution'];
            }
            $moveTotal = $perUser * $totalUsers;

            // Deduct reserve upfront
            $reserve->balance_seconds = (int) $reserve->balance_seconds - $moveTotal;
            $reserve->save();

            // Credit each user's WALLET (users time), not bank
            $now = \Carbon\CarbonImmutable::now();
            User::query()->select('id')->chunkById(1000, function ($users) use ($perUser, $now) {
                foreach ($users as $u) {
                    $wallet = UserTimeWallet::query()->where('user_id', $u->id)->lockForUpdate()->first();
                    if (!$wallet) {
                        $wallet = UserTimeWallet::create([
                            'user_id' => $u->id,
                            'available_seconds' => 0,
                            'last_applied_at' => $now,
                            'drain_rate' => 1.000,
                            'is_active' => true,
                        ]);
                    }
                    $wallet->available_seconds = (int) $wallet->available_seconds + $perUser;
                    if (!$wallet->is_active) {
                        $wallet->is_active = true;
                        $wallet->last_applied_at = $now;
                    }
                    $wallet->save();
                }
            });

            return ['ok' => true, 'per_user' => $perUser, 'move_total' => $moveTotal, 'remaining' => (int) $reserve->balance_seconds];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        Flasher::addSuccess('Distributed ' . $result['per_user'] . 's to each user');
        session()->flash('success', 'Distributed ' . $result['per_user'] . 's to each user');
        return response()->json([
            'status' => 'ok',
            'per_user' => $result['per_user'],
            'distributed_total' => $result['move_total'],
            'remaining_reserve' => $result['remaining'],
        ]);
    }
}
