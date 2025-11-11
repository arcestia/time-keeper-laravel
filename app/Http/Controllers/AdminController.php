<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserStats;
use App\Models\JobCatalog;
use App\Models\StoreItem;
use Flasher\Laravel\Facade\Flasher;
use App\Models\StoreBalance;
use App\Models\TimeKeeperReserve;
use App\Support\TimeUnits;

class AdminController extends Controller
{
    public function page()
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        return view('admin.index');
    }

    public function transferReserveToStore(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $amountStr = (string) $request->input('amount', '');
        $seconds = TimeUnits::parseToSeconds($amountStr);
        if ($seconds <= 0) {
            Flasher::addError('Amount must be > 0');
            session()->flash('error', 'Amount must be > 0');
            return response()->json(['ok' => false, 'message' => 'Amount must be > 0'], 422);
        }

        $moved = DB::transaction(function () use ($seconds) {
            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $amount = min((int)$seconds, max(0, (int)$reserve->balance_seconds));
            if ($amount <= 0) {
                return 0;
            }
            $reserve->balance_seconds = (int)$reserve->balance_seconds - $amount;
            $reserve->save();

            $store = StoreBalance::query()->lockForUpdate()->first();
            if (!$store) { $store = StoreBalance::create(['balance_seconds' => 0]); }
            $store->balance_seconds = (int)$store->balance_seconds + $amount;
            $store->save();

            return $amount;
        });

        if ($moved > 0) {
            Flasher::addSuccess('Transferred ' . $moved . 's from Reserve to Store');
            session()->flash('success', 'Transferred ' . $moved . 's from Reserve to Store');
            return response()->json(['ok' => true, 'moved_seconds' => (int)$moved]);
        }
        Flasher::addError('Reserve has insufficient balance');
        session()->flash('error', 'Reserve has insufficient balance');
        return response()->json(['ok' => false, 'message' => 'Insufficient reserve'], 422);
    }

    public function storeBalance(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $bal = StoreBalance::first();
        return response()->json(['seconds' => (int)($bal->balance_seconds ?? 0)]);
    }

    public function transferStoreBalanceToReserve(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $mode = $request->input('mode', 'all');

        $moved = DB::transaction(function () use ($mode) {
            $sb = StoreBalance::query()->lockForUpdate()->first();
            if (!$sb) { $sb = StoreBalance::create(['balance_seconds' => 0]); }
            $amount = (int) $sb->balance_seconds;
            if ($mode !== 'all') {
                $to = TimeUnits::parseToSeconds((string)$mode);
                if ($to > 0) { $amount = min($amount, $to); }
            }
            if ($amount <= 0) {
                return 0;
            }
            $sb->balance_seconds = (int)$sb->balance_seconds - $amount;
            $sb->save();

            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $reserve->balance_seconds = (int)$reserve->balance_seconds + $amount;
            $reserve->save();

            return $amount;
        });

        if ($moved > 0) {
            Flasher::addSuccess('Transferred ' . $moved . 's from Store to Reserve');
            session()->flash('success', 'Transferred ' . $moved . 's from Store to Reserve');
            return response()->json(['ok' => true, 'moved_seconds' => (int)$moved]);
        }
        Flasher::addError('No store balance available to transfer');
        session()->flash('error', 'No store balance available to transfer');
        return response()->json(['ok' => false, 'message' => 'No store balance'], 422);
    }

    public function usersSearch(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $q = trim((string)$request->query('q', ''));
        $query = User::query()->select('id','username','email')->orderBy('id', 'asc')->limit(20);
        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('username', 'like', "%$q%")->orWhere('email', 'like', "%$q%");
            });
        }
        return response()->json($query->get());
    }

    public function getUserStats(int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $target = User::findOrFail($id);
        $stats = UserStats::firstOrCreate(['user_id' => $target->id], [
            'energy' => 100,
            'food' => 100,
            'water' => 100,
            'leisure' => 100,
            'health' => 100,
        ]);
        return response()->json([
            'user' => ['id' => $target->id, 'username' => $target->username, 'email' => $target->email],
            'stats' => [
                'energy' => (int)$stats->energy,
                'food' => (int)$stats->food,
                'water' => (int)$stats->water,
                'leisure' => (int)$stats->leisure,
                'health' => (int)$stats->health,
            ],
        ]);
    }

    public function updateUserStats(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $target = User::findOrFail($id);
        $data = $request->only(['energy','food','water','leisure','health']);
        $rules = [
            'energy' => 'sometimes|integer|min:0|max:100',
            'food' => 'sometimes|integer|min:0|max:100',
            'water' => 'sometimes|integer|min:0|max:100',
            'leisure' => 'sometimes|integer|min:0|max:100',
            'health' => 'sometimes|integer|min:0|max:100',
        ];
        $validated = validator($data, $rules)->validate();

        $stats = DB::transaction(function () use ($target, $validated) {
            $stats = UserStats::firstOrCreate(['user_id' => $target->id]);
            foreach ($validated as $k => $v) {
                $stats->{$k} = (int)$v;
            }
            $stats->clamp();
            $stats->save();
            return $stats;
        });

        Flasher::addSuccess('Updated stats for user ID ' . $target->id);
        session()->flash('success', 'Updated stats for user ID ' . $target->id);
        return response()->json([
            'ok' => true,
            'stats' => [
                'energy' => (int)$stats->energy,
                'food' => (int)$stats->food,
                'water' => (int)$stats->water,
                'leisure' => (int)$stats->leisure,
                'health' => (int)$stats->health,
            ],
        ]);
    }

    public function createJob(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $data = $request->all();
        $validated = validator($data, [
            'key' => 'required|string|alpha_dash:ascii|unique:jobs_catalog,key',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'duration_seconds' => 'required|integer|min:1',
            'reward_seconds' => 'required|integer|min:1',
            'cooldown_seconds' => 'required|integer|min:0',
            'energy_cost' => 'required|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ])->validate();

        $job = JobCatalog::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_seconds' => (int)$validated['duration_seconds'],
            'reward_seconds' => (int)$validated['reward_seconds'],
            'cooldown_seconds' => (int)$validated['cooldown_seconds'],
            'energy_cost' => (int)$validated['energy_cost'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);

        Flasher::addSuccess('Job created: ' . $job->name);
        session()->flash('success', 'Job created: ' . $job->name);
        return response()->json([
            'ok' => true,
            'job' => $job,
        ]);
    }

    public function storeItems(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $items = StoreItem::query()->orderBy('type')->orderBy('name')->get([
            'id','key','name','type','price_seconds','quantity','restore_food','restore_water','restore_energy','is_active'
        ]);
        return response()->json($items);
    }

    public function createStoreItem(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $validated = validator($request->all(), [
            'key' => 'required|string|alpha_dash:ascii|unique:store_items,key',
            'name' => 'required|string|max:120',
            'type' => 'required|in:food,water',
            'description' => 'nullable|string|max:1000',
            'price_seconds' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'restore_food' => 'required|integer|min:0|max:100',
            'restore_water' => 'required|integer|min:0|max:100',
            'restore_energy' => 'required|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ])->validate();

        $item = DB::transaction(function () use ($validated) {
            $price = (int)$validated['price_seconds'];
            $qty = (int)$validated['quantity'];
            $cost = intdiv($price * $qty, 2); // 50%

            $sb = StoreBalance::query()->lockForUpdate()->first();
            if (!$sb) { $sb = StoreBalance::create(['balance_seconds' => 0]); }
            if ((int)$sb->balance_seconds < $cost) {
                abort(422, 'Store balance insufficient for creation cost');
            }
            $sb->balance_seconds = (int)$sb->balance_seconds - $cost;
            $sb->save();

            return StoreItem::create([
                'key' => $validated['key'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'price_seconds' => $price,
                'quantity' => $qty,
                'restore_food' => (int)$validated['restore_food'],
                'restore_water' => (int)$validated['restore_water'],
                'restore_energy' => (int)$validated['restore_energy'],
                'is_active' => (bool)($validated['is_active'] ?? true),
            ]);
        });

        Flasher::addSuccess('Store item created: ' . $item->name);
        session()->flash('success', 'Store item created: ' . $item->name);
        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function restockStoreItem(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $validated = validator($request->all(), [
            'quantity' => 'required|integer|min:1'
        ])->validate();

        [$item, $newQty] = DB::transaction(function () use ($id, $validated) {
            $item = StoreItem::query()->lockForUpdate()->findOrFail($id);
            $qty = (int)$validated['quantity'];
            $price = (int)$item->price_seconds;
            $cost = intdiv($price * $qty, 2); // 50%

            $sb = StoreBalance::query()->lockForUpdate()->first();
            if (!$sb) { $sb = StoreBalance::create(['balance_seconds' => 0]); }
            if ((int)$sb->balance_seconds < $cost) {
                abort(422, 'Store balance insufficient for restock cost');
            }
            $sb->balance_seconds = (int)$sb->balance_seconds - $cost;
            $sb->save();

            $item->quantity = (int)$item->quantity + $qty;
            $item->save();

            return [$item, (int)$item->quantity];
        });

        Flasher::addSuccess('Restocked ' . $validated['quantity'] . ' units of ' . $item->name);
        session()->flash('success', 'Restocked ' . $validated['quantity'] . ' units of ' . $item->name);
        return response()->json(['ok' => true, 'quantity' => (int)$newQty]);
    }
}
