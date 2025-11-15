<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\UserStats;
use App\Services\PremiumService;
use Illuminate\Support\Facades\Auth;
use App\Services\StatsService;

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

    public function addSteps(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'steps_delta' => ['required','integer','min:0']
        ]);
        $row = app(StatsService::class)->addSteps($user->id, (int)$data['steps_delta']);
        return response()->json([
            'ok' => true,
            'date' => $row->date->toDateString(),
            'steps_count' => (int)$row->steps_count,
            'expeditions_completed' => (int)$row->expeditions_completed,
        ]);
    }

    public function leaderboard(Request $request, string $period): JsonResponse
    {
        $period = in_array($period, ['daily','weekly','monthly'], true) ? $period : 'daily';
        $metric = $request->query('metric','steps');
        if (!in_array($metric, ['steps','exp_completed','level','wallet','bank','total_xp'], true)) { $metric = 'steps'; }
        // map metric to service column / key
        $metricKey = $metric;
        if ($metricKey === 'exp_completed') { $metricKey = 'expeditions_completed'; }
        $limit = max(1, min(100, (int)$request->query('limit', 25)));
        // reference date params (UTC)
        $ref = $request->query('date');
        if ($period === 'monthly') { $ref = $request->query('month') ?: $ref; }
        $rows = app(StatsService::class)->leaderboard($period, $metricKey, $limit, $ref);
        return response()->json([
            'ok' => true,
            'period' => $period,
            'metric' => $metric,
            'limit' => $limit,
            'data' => $rows,
        ]);
    }
}
