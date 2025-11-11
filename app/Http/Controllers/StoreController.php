<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StoreItem;
use App\Models\UserStats;
use App\Models\UserTimeWallet;
use App\Models\TimeAccount;
use Carbon\CarbonImmutable;

class StoreController extends Controller
{
    public function page()
    {
        return view('store.index');
    }

    public function items(): JsonResponse
    {
        $items = StoreItem::query()->where('is_active', true)->orderBy('type')->orderBy('price_seconds')->get();
        return response()->json($items);
    }

    public function balances(): JsonResponse
    {
        $user = Auth::user();
        $wallet = UserTimeWallet::where('user_id', $user->id)->first();
        $bank = TimeAccount::where('user_id', $user->id)->first();
        return response()->json([
            'wallet_seconds' => (int)($wallet->available_seconds ?? 0),
            'bank_seconds' => (int)($bank->base_balance_seconds ?? 0),
        ]);
    }

    public function buy(string $key): JsonResponse
    {
        $user = Auth::user();
        $item = StoreItem::where(['key' => $key, 'is_active' => true])->firstOrFail();
        $now = CarbonImmutable::now();

        $source = request()->input('source', 'wallet');
        if (!in_array($source, ['wallet', 'bank'], true)) { $source = 'wallet'; }
        $qty = max(1, (int) request()->input('qty', 1));

        $result = DB::transaction(function () use ($user, $item, $now, $source, $qty) {
            // Lock item for stock check
            $lockedItem = StoreItem::query()->where('id', $item->id)->lockForUpdate()->first();
            if ((int)($lockedItem->quantity ?? 0) < $qty) {
                abort(422, 'Item out of stock');
            }
            $pricePer = (int)$lockedItem->price_seconds;
            $price = $pricePer * $qty;
            $paidFrom = $source;
            $wallet = null;
            $bank = null;
            if ($source === 'wallet') {
                $wallet = UserTimeWallet::query()->where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet) {
                    $wallet = UserTimeWallet::create([
                        'user_id' => $user->id,
                        'available_seconds' => 0,
                        'last_applied_at' => $now,
                        'drain_rate' => 1.000,
                        'is_active' => true,
                    ]);
                }
                if ((int)$wallet->available_seconds < $price) {
                    abort(422, 'Not enough time balance in wallet');
                }
                $wallet->available_seconds = (int)$wallet->available_seconds - $price;
                $wallet->save();
            } else { // bank
                $bank = TimeAccount::query()->where('user_id', $user->id)->lockForUpdate()->first();
                if (!$bank) {
                    $bank = TimeAccount::create(['user_id' => $user->id, 'base_balance_seconds' => 0]);
                }
                if ((int)$bank->base_balance_seconds < $price) {
                    abort(422, 'Not enough time balance in bank');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price;
                $bank->save();
            }

            $stats = UserStats::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$stats) {
                $stats = UserStats::create(['user_id' => $user->id, 'energy' => 100, 'food' => 100, 'water' => 100, 'leisure' => 100, 'health' => 100]);
            }
            $stats->food = min(100, (int)$stats->food + ((int)$lockedItem->restore_food * $qty));
            $stats->water = min(100, (int)$stats->water + ((int)$lockedItem->restore_water * $qty));
            $stats->energy = min(100, (int)$stats->energy + ((int)$lockedItem->restore_energy * $qty));
            $stats->save();

            // Decrement stock
            $lockedItem->quantity = max(0, (int)$lockedItem->quantity - $qty);
            $lockedItem->save();

            return [$wallet, $bank, $stats, $lockedItem, $paidFrom, $price, $qty];
        });

        [$wallet, $bank, $stats, $lockedItem, $paidFrom, $price, $qty] = $result;
        return response()->json([
            'ok' => true,
            'paid_from' => $paidFrom,
            'price_seconds' => (int)$price,
            'qty' => (int)$qty,
            'wallet_seconds' => (int)$wallet->available_seconds,
            'bank_seconds' => $bank ? (int)$bank->base_balance_seconds : null,
            'stats' => [
                'energy' => (int)$stats->energy,
                'food' => (int)$stats->food,
                'water' => (int)$stats->water,
                'leisure' => (int)$stats->leisure,
                'health' => (int)$stats->health,
            ],
            'remaining_quantity' => (int)($lockedItem->quantity ?? 0),
        ]);
    }
}
