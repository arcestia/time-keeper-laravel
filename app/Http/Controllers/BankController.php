<?php

namespace App\Http\Controllers;

use App\Models\TimeAccount;
use App\Services\TimeBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class BankController extends Controller
{
    public function __construct(private TimeBankService $bank)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $account = TimeAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'base_balance_seconds' => 86400,
                'last_applied_at' => now(),
                'drain_rate' => 1.000,
                'is_active' => true,
            ]
        );

        $seconds = $this->bank->getDisplayBalance($account);

        return response()->json([
            'balance_seconds' => $seconds,
            'balance_formatted' => $this->formatSeconds($seconds),
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'base_balance_seconds' => 86400,
                'last_applied_at' => now(),
                'drain_rate' => 1.000,
                'is_active' => true,
            ]
        );

        $this->bank->deposit($account, (int) $data['seconds'], $data['reason'] ?? 'deposit');
        $account->refresh();
        $seconds = $this->bank->getDisplayBalance($account);

        return response()->json([
            'balance_seconds' => $seconds,
            'balance_formatted' => $this->formatSeconds($seconds),
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'base_balance_seconds' => 86400,
                'last_applied_at' => now(),
                'drain_rate' => 1.000,
                'is_active' => true,
            ]
        );

        $this->bank->withdraw($account, (int) $data['seconds'], $data['reason'] ?? 'withdraw');
        $account->refresh();
        $seconds = $this->bank->getDisplayBalance($account);

        return response()->json([
            'balance_seconds' => $seconds,
            'balance_formatted' => $this->formatSeconds($seconds),
        ]);
    }

    private function formatSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $days = intdiv($seconds, 86400);
        $rem = $seconds % 86400;
        $hours = intdiv($rem, 3600);
        $rem %= 3600;
        $minutes = intdiv($rem, 60);
        $secs = $rem % 60;
        $hms = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        return $days > 0 ? $days.'d '.$hms : $hms;
    }
}
