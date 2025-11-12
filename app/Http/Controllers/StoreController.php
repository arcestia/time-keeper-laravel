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
use Flasher\Laravel\Facade\Flasher;
use App\Models\StoreBalance;
use App\Services\PremiumService;
use App\Models\Premium;

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
                Flasher::addError('Item out of stock');
                session()->flash('error', 'Item out of stock');
                abort(422, 'Item out of stock');
            }
            $pricePer = (int)$lockedItem->price_seconds;
            // Premium discount
            $prem = PremiumService::getOrCreate($user->id);
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $disc = (int)($benefits['store_discount_pct'] ?? 0);
                if ($disc > 0) {
                    $pricePer = max(0, (int)floor($pricePer * (100 - $disc) / 100));
                }
            }
            $price = $pricePer * $qty;

            // Enforce GLOBAL inventory capacity pre-check (no overflow into storage)
            $INV_MAX = 20000;
            // lock all user's inventory rows by summing within transaction
            $invTotal = (int) \App\Models\UserInventoryItem::query()->where('user_id',$user->id)->lockForUpdate()->sum('quantity');
            if ($invTotal + $qty > $INV_MAX) {
                Flasher::addError('Inventory capacity reached (global max 20,000). Reduce quantity.');
                session()->flash('error', 'Inventory capacity reached (global max 20,000).');
                abort(422, 'Inventory capacity reached');
            }
            $inv = \App\Models\UserInventoryItem::query()->where(['user_id'=>$user->id,'store_item_id'=>$lockedItem->id])->lockForUpdate()->first();
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
                    Flasher::addError('Not enough time balance in wallet');
                    session()->flash('error', 'Not enough time balance in wallet');
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
                    Flasher::addError('Not enough time balance in bank');
                    session()->flash('error', 'Not enough time balance in bank');
                    abort(422, 'Not enough time balance in bank');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price;
                $bank->save();
            }

            // Credit store balance with total price
            $storeBalance = StoreBalance::query()->lockForUpdate()->first();
            if (!$storeBalance) { $storeBalance = StoreBalance::create(['balance_seconds' => 0]); }
            $storeBalance->balance_seconds = (int)$storeBalance->balance_seconds + $price;
            $storeBalance->save();

            // Add items to inventory (no immediate effects)
            if (!$inv) { $inv = \App\Models\UserInventoryItem::create(['user_id'=>$user->id,'store_item_id'=>$lockedItem->id,'quantity'=>0]); }
            $inv->quantity = (int)$inv->quantity + $qty; // safe due to global pre-check
            $added = $qty;
            $inv->save();

            // Decrement stock
            $lockedItem->quantity = max(0, (int)$lockedItem->quantity - $qty);
            $lockedItem->save();

            return [$wallet, $bank, $lockedItem, $paidFrom, $price, $qty, $added];
        });

        [$wallet, $bank, $lockedItem, $paidFrom, $price, $qty, $added] = $result;
        Flasher::addSuccess('Purchased ' . $qty . ' x ' . $item->name . ' • Added to inventory: ' . $added);
        session()->flash('success', 'Purchased ' . $qty . ' x ' . $item->name . ' • Added to inventory: ' . $added);
        return response()->json([
            'ok' => true,
            'paid_from' => $paidFrom,
            'price_seconds' => (int)$price,
            'qty' => (int)$qty,
            'added_to_inventory' => (int)$added,
            'wallet_seconds' => (int)$wallet->available_seconds,
            'bank_seconds' => $bank ? (int)$bank->base_balance_seconds : null,
            'remaining_quantity' => (int)($lockedItem->quantity ?? 0),
        ]);
    }
}
