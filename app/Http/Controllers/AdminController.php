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

class AdminController extends Controller
{
    public function page()
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        return view('admin.index');
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

        flasher()->addSuccess('Updated stats for user ID ' . $target->id);
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

        flasher()->addSuccess('Job created: ' . $job->name);
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

        $item = StoreItem::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'price_seconds' => (int)$validated['price_seconds'],
            'quantity' => (int)$validated['quantity'],
            'restore_food' => (int)$validated['restore_food'],
            'restore_water' => (int)$validated['restore_water'],
            'restore_energy' => (int)$validated['restore_energy'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);

        flasher()->addSuccess('Store item created: ' . $item->name);
        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function restockStoreItem(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->is_admin, 403);
        $validated = validator($request->all(), [
            'quantity' => 'required|integer|min:1'
        ])->validate();

        $item = StoreItem::query()->lockForUpdate()->findOrFail($id);
        $item->quantity = (int)$item->quantity + (int)$validated['quantity'];
        $item->save();

        flasher()->addSuccess('Restocked ' . $validated['quantity'] . ' units of ' . $item->name);
        return response()->json(['ok' => true, 'quantity' => (int)$item->quantity]);
    }
}
