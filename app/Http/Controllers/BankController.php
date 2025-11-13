<?php

namespace App\Http\Controllers;

use App\Models\TimeAccount;
use App\Models\UserTimeWallet;
use App\Services\TimeBankService;
use App\Services\TimeTokenService;
use App\Support\TimeUnits;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class BankController extends Controller
{
    public function __construct(private TimeBankService $bank)
    {
    }

    public function page()
    {
        return view('bank.index');
    }

    private function bankLoggedIn(Request $request): bool
    {
        return (bool) $request->session()->get('bank_logged_in', false);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::where('user_id', $user->id)->firstOrFail();
        if (empty($account->passcode_hash)) {
            return response()->json(['message' => 'Passcode not set'], 422);
        }
        if (!Hash::check($data['passcode'], $account->passcode_hash)) {
            return response()->json(['message' => 'Invalid passcode'], 403);
        }
        $request->session()->put('bank_logged_in', true);
        return response()->json(['status' => 'ok']);
    }

    public function lock(Request $request): JsonResponse
    {
        $request->session()->forget('bank_logged_in');
        return response()->json(['status' => 'ok']);
    }

    public function exchangeTokens(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'color' => ['required','string','in:red,blue,green,yellow,black'],
            'qty' => ['required','integer','min:1','max:1000000'],
        ]);

        $user = Auth::user();
        if (!$this->bankLoggedIn($request)) {
            return response()->json(['message' => 'Bank locked'], 403);
        }

        $result = $tokens->exchange($user->id, $data['color'], (int)$data['qty']);
        if (!($result['ok'] ?? false)) {
            $msg = (string)($result['message'] ?? 'Exchange failed');
            return response()->json(['message' => $msg], 422);
        }
        return response()->json([
            'ok' => true,
            'color' => $data['color'],
            'exchanged_qty' => (int)$result['exchanged_qty'],
            'credited_seconds' => (int)$result['credited_seconds'],
            'remaining_qty' => (int)$result['remaining_qty'],
        ]);
    }

    public function tokenBalances(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $user = Auth::user();
        if (!$this->bankLoggedIn($request)) {
            return response()->json(['message' => 'Bank locked'], 403);
        }
        $balances = $tokens->getBalances($user->id);
        return response()->json(['ok'=>true,'balances'=>$balances]);
    }

    public function userTime(Request $request): JsonResponse
    {
        $user = Auth::user();
        $wallet = UserTimeWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['available_seconds' => 864000]
        );
        $seconds = $this->bank->getWalletDisplayBalance($wallet);
        return response()->json([
            'seconds' => $seconds,
            'formatted' => $this->formatSeconds($seconds),
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to_username' => ['required', 'string'],
            'amount' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $fromAccount = TimeAccount::where('user_id', $user->id)->firstOrFail();
        if (!$this->bankLoggedIn($request)) {
            return response()->json(['message' => 'Bank locked'], 403);
        }

        try {
            $amount = TimeUnits::parseToSeconds($data['amount']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid amount: '.$e->getMessage()], 422);
        }
        if ($amount <= 0) {
            return response()->json(['message' => 'Amount must be greater than zero'], 422);
        }

        $to = User::where('username', $data['to_username'])->first();
        if (!$to) {
            return response()->json(['message' => 'Recipient not found'], 422);
        }
        if ($to->id === $user->id) {
            return response()->json(['message' => 'Cannot transfer to yourself'], 422);
        }

        $result = DB::transaction(function () use ($fromAccount, $to, $amount, $data) {
            $before = (int) $fromAccount->base_balance_seconds;
            $this->bank->withdraw($fromAccount->refresh(), $amount, $data['reason'] ?? 'transfer');
            $after = (int) $fromAccount->fresh()->base_balance_seconds;
            $actual = max(0, $before - $after);

            $toAccount = TimeAccount::firstOrCreate(
                ['user_id' => $to->id],
                [
                    'base_balance_seconds' => 0,
                    'last_applied_at' => now(),
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]
            );
            if ($actual > 0) {
                $this->bank->deposit($toAccount->refresh(), $actual, 'transfer from '.$fromAccount->user_id);
            }

            return [
                'from_after' => $after,
                'transferred' => $actual,
            ];
        });

        return response()->json([
            'balance_seconds' => $result['from_after'],
            'balance_formatted' => $this->formatSeconds($result['from_after']),
            'transferred_seconds' => $result['transferred'],
            'transferred_formatted' => $this->formatSeconds($result['transferred']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $account = TimeAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'base_balance_seconds' => 0,
                'last_applied_at' => now(),
                'drain_rate' => 1.000,
                'is_active' => true,
            ]
        );
        $wallet = UserTimeWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['available_seconds' => 864000]
        );

        $requiresPasscode = empty($account->passcode_hash);
        $seconds = $requiresPasscode ? null : $this->bank->getDisplayBalance($account);
        $walletDisplay = $this->bank->getWalletDisplayBalance($wallet);
        $loggedIn = !$requiresPasscode && $this->bankLoggedIn($request);

        return response()->json([
            'requires_passcode' => $requiresPasscode,
            'bank_logged_in' => $loggedIn,
            'balance_seconds' => $seconds,
            'balance_formatted' => $seconds === null ? null : $this->formatSeconds($seconds),
            'wallet_seconds' => $walletDisplay,
            'wallet_formatted' => $this->formatSeconds($walletDisplay),
        ]);
    }

    public function setPasscode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'passcode' => ['required', 'string', 'min:4', 'max:64'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::where('user_id', $user->id)->firstOrFail();
        if (!empty($account->passcode_hash)) {
            return response()->json(['message' => 'Passcode already set'], 422);
        }

        $account->passcode_hash = Hash::make($data['passcode']);
        $account->passcode_set_at = now();
        $account->save();

        return response()->json(['status' => 'ok']);
    }

    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::where('user_id', $user->id)->firstOrFail();
        if (!$this->bankLoggedIn($request)) {
            return response()->json(['message' => 'Bank locked'], 403);
        }

        $wallet = UserTimeWallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
        try {
            $amount = TimeUnits::parseToSeconds($data['amount']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid amount: '.$e->getMessage()], 422);
        }
        if ($amount < 86400) {
            return response()->json(['message' => 'Minimum deposit is 1 day (86400 seconds)'], 422);
        }
        $reason = $data['reason'] ?? 'deposit';

        $seconds = DB::transaction(function () use ($wallet, $account, $amount, $reason) {
            if ((int) $wallet->available_seconds < $amount) {
                abort(422, 'Insufficient wallet balance');
            }
            $wallet->available_seconds = (int) $wallet->available_seconds - $amount;
            $wallet->save();

            $this->bank->deposit($account->refresh(), $amount, $reason);
            $accDisplay = $this->bank->getDisplayBalance($account->refresh());
            return $accDisplay;
        });

        $walletFresh = $wallet->fresh();
        $walletDisplay = $this->bank->getWalletDisplayBalance($walletFresh);

        return response()->json([
            'balance_seconds' => $seconds,
            'balance_formatted' => $this->formatSeconds($seconds),
            'wallet_seconds' => $walletDisplay,
            'wallet_formatted' => $this->formatSeconds($walletDisplay),
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $account = TimeAccount::where('user_id', $user->id)->firstOrFail();
        if (!$this->bankLoggedIn($request)) {
            return response()->json(['message' => 'Bank locked'], 403);
        }

        $wallet = UserTimeWallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
        try {
            $amount = TimeUnits::parseToSeconds($data['amount']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid amount: '.$e->getMessage()], 422);
        }
        $reason = $data['reason'] ?? 'withdraw';

        $seconds = DB::transaction(function () use ($wallet, $account, $amount, $reason) {
            $this->bank->withdraw($account->refresh(), $amount, $reason);
            $account->refresh();
            $available = $this->bank->getDisplayBalance($account);
            $debited = min($amount, $available + $amount); // actual debited is what left the account
            $wallet->available_seconds = (int) $wallet->available_seconds + $debited;
            $wallet->save();
            return $this->bank->getDisplayBalance($account->refresh());
        });

        $walletFresh = $wallet->fresh();
        $walletDisplay = $this->bank->getWalletDisplayBalance($walletFresh);

        return response()->json([
            'balance_seconds' => $seconds,
            'balance_formatted' => $this->formatSeconds($seconds),
            'wallet_seconds' => $walletDisplay,
            'wallet_formatted' => $this->formatSeconds($walletDisplay),
        ]);
    }

    private function formatSeconds(int $seconds): string
    {
        return TimeUnits::compactColon($seconds);
    }
}
