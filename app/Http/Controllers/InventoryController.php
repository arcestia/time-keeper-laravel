<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserInventoryItem;
use App\Models\UserStorageItem;
use App\Models\StoreItem;
use App\Models\UserStats;
use App\Services\PremiumService;
use App\Models\UserTimeWallet;
use Carbon\CarbonImmutable;

class InventoryController extends Controller
{
    public function page()
    {
        return view('inventory.index');
    }

    public function list(): JsonResponse
    {
        $userId = Auth::id();
        $inv = UserInventoryItem::with('item')->where('user_id',$userId)->get();
        $sto = UserStorageItem::with('item')->where('user_id',$userId)->get();
        return response()->json([
            'inventory' => $inv,
            'storage' => $sto,
            'cap' => (int) config('inventory.cap', 20000),
        ]);
    }

    public function moveToStorage(): JsonResponse
    {
        $userId = Auth::id();
        $req = request();
        $key = (string) $req->input('key');
        $qty = max(1, (int) ($req->input('qty', 1)));
        return DB::transaction(function() use($userId,$key,$qty){
            $item = StoreItem::where(['key'=>$key])->firstOrFail();
            $inv = UserInventoryItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$inv || $inv->quantity < $qty) { abort(422,'Not enough quantity in inventory'); }
            $inv->quantity -= $qty; if ($inv->quantity <= 0) { $inv->delete(); } else { $inv->save(); }
            $sto = UserStorageItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$userId,'store_item_id'=>$item->id,'quantity'=>0]); }
            $sto->quantity = (int)$sto->quantity + $qty; $sto->save();
            return response()->json(['ok'=>true,'moved'=>$qty]);
        });
    }

    public function moveAllToStorage(): JsonResponse
    {
        $userId = Auth::id();
        return DB::transaction(function() use($userId){
            $items = UserInventoryItem::where('user_id',$userId)->lockForUpdate()->get();
            $movedTotal = 0;
            foreach ($items as $inv) {
                $qty = (int) $inv->quantity;
                if ($qty <= 0) { $inv->delete(); continue; }
                $sto = UserStorageItem::where(['user_id'=>$userId,'store_item_id'=>$inv->store_item_id])->lockForUpdate()->first();
                if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$userId,'store_item_id'=>$inv->store_item_id,'quantity'=>0]); }
                $sto->quantity = (int)$sto->quantity + $qty; $sto->save();
                $inv->delete();
                $movedTotal += $qty;
            }
            return response()->json(['ok'=>true,'moved_total'=>(int)$movedTotal]);
        });
    }

    public function moveToInventory(): JsonResponse
    {
        $userId = Auth::id();
        $req = request();
        $key = (string) $req->input('key');
        $qty = max(1, (int) ($req->input('qty', 1)));
        $INV_MAX = (int) config('inventory.cap', 20000);
        return DB::transaction(function() use($userId,$key,$qty,$INV_MAX){
            $item = StoreItem::where(['key'=>$key])->firstOrFail();
            $sto = UserStorageItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$sto || $sto->quantity < $qty) { abort(422,'Not enough quantity in storage'); }
            // Global cap across all inventory items
            $invTotal = (int) UserInventoryItem::where('user_id',$userId)->lockForUpdate()->sum('quantity');
            if ($invTotal + $qty > $INV_MAX) { abort(422, 'Inventory capacity reached (global max ' . $INV_MAX . ')'); }
            $inv = UserInventoryItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$inv) { $inv = UserInventoryItem::create(['user_id'=>$userId,'store_item_id'=>$item->id,'quantity'=>0]); }
            $inv->quantity = (int)$inv->quantity + $qty;
            $inv->save();
            $sto->quantity = (int)$sto->quantity - $qty; if ($sto->quantity<=0){ $sto->delete(); } else { $sto->save(); }
            return response()->json(['ok'=>true,'moved'=>$qty]);
        });
    }

    public function consume(): JsonResponse
    {
        $userId = Auth::id();
        $key = (string) request('key');
        $qty = max(1, (int) request('qty', 1));
        return DB::transaction(function() use($userId,$key,$qty){
            $item = StoreItem::where(['key'=>$key])->firstOrFail();
            $inv = UserInventoryItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$inv || $inv->quantity < 1) { abort(422,'Not enough quantity in inventory'); }

            $stats = UserStats::where('user_id',$userId)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id'=>$userId, 'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
            $prem = PremiumService::getOrCreate($userId);
            $capMult = 1.0; if (PremiumService::isActive($prem)) { $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated); $benefits = PremiumService::benefitsForTier($tier); $capMult = (float)($benefits['cap_multiplier']??1.0); }
            $cap = (int) floor(100 * $capMult);

            // Compute the maximum items we can consume without overflowing any stat
            $needFood = max(0, $cap - (int)$stats->food);
            $needWater = max(0, $cap - (int)$stats->water);
            $needEnergy = max(0, $cap - (int)$stats->energy);
            $rf = max(0, (int)$item->restore_food);
            $rw = max(0, (int)$item->restore_water);
            $re = max(0, (int)$item->restore_energy);
            $maxFood = $rf > 0 ? intdiv($needFood, $rf) : PHP_INT_MAX;
            $maxWater = $rw > 0 ? intdiv($needWater, $rw) : PHP_INT_MAX;
            $maxEnergy = $re > 0 ? intdiv($needEnergy, $re) : PHP_INT_MAX;
            $maxUsableByStats = min($maxFood, $maxWater, $maxEnergy);
            $useQty = (int) max(0, min($qty, (int)$inv->quantity, $maxUsableByStats));

            if ($useQty <= 0) {
                // Nothing usable without overflow; do not consume
                return response()->json([
                    'ok' => false,
                    'message' => 'Using this item would overflow your stats. Try later or use a smaller value.',
                    'stats' => ['energy'=>(int)$stats->energy,'food'=>(int)$stats->food,'water'=>(int)$stats->water],
                ], 422);
            }

            // Apply restores and deduct only what we can use
            $stats->food = min($cap, (int)$stats->food + $rf * $useQty);
            $stats->water = min($cap, (int)$stats->water + $rw * $useQty);
            $stats->energy = min($cap, (int)$stats->energy + $re * $useQty);
            $stats->save();

            $inv->quantity = (int)$inv->quantity - $useQty; if ($inv->quantity <= 0) { $inv->delete(); } else { $inv->save(); }

            return response()->json([
                'ok'=>true,
                'used' => $useQty,
                'requested' => (int)$qty,
                'unused' => max(0, (int)$qty - $useQty),
                'stats'=>['energy'=>(int)$stats->energy,'food'=>(int)$stats->food,'water'=>(int)$stats->water]
            ]);
        });
    }

    public function sell(): JsonResponse
    {
        $userId = Auth::id();
        $key = (string) request('key');
        $qty = max(1, (int) request('qty', 1));
        $now = CarbonImmutable::now();
        return DB::transaction(function() use($userId,$key,$qty,$now){
            $item = StoreItem::where(['key'=>$key])->lockForUpdate()->firstOrFail();
            $inv = UserInventoryItem::where(['user_id'=>$userId,'store_item_id'=>$item->id])->lockForUpdate()->first();
            if (!$inv || $inv->quantity < $qty) { abort(422,'Not enough quantity in inventory'); }
            $pricePer = (int) $item->price_seconds;
            $proceedsPer = (int) floor($pricePer * 0.5);
            $proceeds = $proceedsPer * $qty;
            $wallet = UserTimeWallet::where('user_id',$userId)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = UserTimeWallet::create([
                    'user_id' => $userId,
                    'available_seconds' => 0,
                    'last_applied_at' => $now,
                    'drain_rate' => 1.000,
                    'is_active' => true,
                ]);
            }
            $wallet->available_seconds = (int)$wallet->available_seconds + $proceeds;
            $wallet->save();
            $inv->quantity = (int)$inv->quantity - $qty; if ($inv->quantity <= 0) { $inv->delete(); } else { $inv->save(); }
            $item->quantity = (int) $item->quantity + $qty; $item->save();
            return response()->json([
                'ok' => true,
                'qty_sold' => (int)$qty,
                'credited_seconds' => (int)$proceeds,
                'wallet_seconds' => (int)$wallet->available_seconds,
                'remaining_inventory' => (int) ($inv->quantity ?? 0),
            ]);
        });
    }
}
