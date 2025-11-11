<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\UserStats;

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
        return response()->json([
            'energy' => (int)$stats->energy,
            'food' => (int)$stats->food,
            'water' => (int)$stats->water,
            'leisure' => (int)$stats->leisure,
            'health' => (int)$stats->health,
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->only(['energy','food','water','leisure','health']);
        $rules = [
            'energy' => 'sometimes|integer|min:0|max:100',
            'food' => 'sometimes|integer|min:0|max:100',
            'water' => 'sometimes|integer|min:0|max:100',
            'leisure' => 'sometimes|integer|min:0|max:100',
            'health' => 'sometimes|integer|min:0|max:100',
        ];
        $validated = validator($data, $rules)->validate();

        $stats = DB::transaction(function () use ($user, $validated) {
            $stats = UserStats::firstOrCreate(['user_id' => $user->id]);
            foreach ($validated as $k => $v) {
                $stats->{$k} = (int)$v;
            }
            $stats->clamp();
            $stats->save();
            return $stats;
        });

        return response()->json([
            'energy' => (int)$stats->energy,
            'food' => (int)$stats->food,
            'water' => (int)$stats->water,
            'leisure' => (int)$stats->leisure,
            'health' => (int)$stats->health,
        ]);
    }
}
