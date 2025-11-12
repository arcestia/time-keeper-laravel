<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\UserStats;
use App\Services\PremiumService;

class StatsController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = UserStats::firstOrCreate(['user_id' => $user->id], [
            'energy' => 100,
            'food' => 100,
            'water' => 100,
            'leisure' => 100,
            'health' => 100,
        ]);
        $cap = PremiumService::statsCapPercentForUser($user->id);
        return response()->json([
            'energy' => (int)$stats->energy,
            'food' => (int)$stats->food,
            'water' => (int)$stats->water,
            'leisure' => (int)$stats->leisure,
            'health' => (int)$stats->health,
            'cap_percent' => (int)$cap,
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->only(['energy','food','water','leisure','health']);
        $rules = [
            'energy' => 'sometimes|integer|min:0',
            'food' => 'sometimes|integer|min:0',
            'water' => 'sometimes|integer|min:0',
            'leisure' => 'sometimes|integer|min:0',
            'health' => 'sometimes|integer|min:0',
        ];
        $validated = validator($data, $rules)->validate();

        $stats = DB::transaction(function () use ($user, $validated) {
            $stats = UserStats::firstOrCreate(['user_id' => $user->id]);
            foreach ($validated as $k => $v) {
                $stats->{$k} = (int)$v;
            }
            $cap = PremiumService::statsCapPercentForUser($user->id);
            $stats->clamp($cap);
            $stats->save();
            return $stats;
        });

        return response()->json([
            'energy' => (int)$stats->energy,
            'food' => (int)$stats->food,
            'water' => (int)$stats->water,
            'leisure' => (int)$stats->leisure,
            'health' => (int)$stats->health,
            'cap_percent' => (int)PremiumService::statsCapPercentForUser($user->id),
        ]);
    }
}
