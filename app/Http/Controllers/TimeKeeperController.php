<?php

namespace App\Http\Controllers;

use App\Models\TimeAccount;
use App\Models\User;
use App\Models\UserTimeWallet;
use App\Services\TimeBankService;
use App\Support\TimeUnits;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        ]);
    }
}
