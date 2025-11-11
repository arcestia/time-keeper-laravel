<?php

namespace App\Http\Controllers;

use App\Models\JobCatalog;
use App\Models\TimeKeeperReserve;
use App\Models\UserJobRun;
use App\Models\UserStats;
use App\Models\UserTimeWallet;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Flasher\Laravel\Facade\Flasher;
use App\Services\PremiumService;
use App\Models\Premium;

class JobsController extends Controller
{
    public function page()
    {
        return view('jobs.index');
    }

    public function list(): JsonResponse
    {
        $user = Auth::user();
        $jobs = JobCatalog::query()->where('is_active', true)->orderBy('id')->get();

        $now = CarbonImmutable::now();
        $payload = [];
        foreach ($jobs as $job) {
            $lastRun = UserJobRun::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->orderByDesc('started_at')
                ->first();

            $active = $lastRun && !$lastRun->claimed_at;
            $nextAvailableAt = null;
            if ($lastRun && $lastRun->claimed_at) {
                $coolUntil = CarbonImmutable::parse($lastRun->started_at)->addSeconds($job->cooldown_seconds);
                if ($coolUntil->isFuture()) {
                    $nextAvailableAt = $coolUntil->toIso8601String();
                }
            }

            $progress = null;
            if ($active) {
                $end = CarbonImmutable::parse($lastRun->started_at)->addSeconds($job->duration_seconds);
                $progress = [
                    'started_at' => CarbonImmutable::parse($lastRun->started_at)->toIso8601String(),
                    'ends_at' => $end->toIso8601String(),
                    'can_claim' => $now->greaterThanOrEqualTo($end),
                ];
            }

            $payload[] = [
                'key' => $job->key,
                'name' => $job->name,
                'description' => $job->description,
                'duration_seconds' => (int)$job->duration_seconds,
                'reward_seconds' => (int)$job->reward_seconds,
                'cooldown_seconds' => (int)$job->cooldown_seconds,
                'energy_cost' => (int)($job->energy_cost ?? 0),
                'active_run' => $progress,
                'next_available_at' => $nextAvailableAt,
            ];
        }

        return response()->json($payload);
    }

    public function start(string $key): JsonResponse
    {
        $user = Auth::user();
        $job = JobCatalog::where(['key' => $key, 'is_active' => true])->firstOrFail();
        $now = CarbonImmutable::now();

        $run = DB::transaction(function () use ($user, $job, $now) {
            // Global guard: only one active/unclaimed job at a time per user
            $anyUnclaimed = UserJobRun::query()
                ->where('user_id', $user->id)
                ->whereNull('claimed_at')
                ->lockForUpdate()
                ->first();
            if ($anyUnclaimed) {
                Flasher::addError('You already have a job in progress or awaiting claim');
                abort(422, 'You already have a job in progress or awaiting claim');
            }

            // Per-job cooldown check
            $lastRun = UserJobRun::query()->where('user_id', $user->id)->where('job_id', $job->id)->orderByDesc('started_at')->lockForUpdate()->first();
            if ($lastRun) {
                $coolUntil = CarbonImmutable::parse($lastRun->started_at)->addSeconds($job->cooldown_seconds);
                if ($coolUntil->isFuture()) {
                    Flasher::addError('Job is on cooldown');
                    abort(422, 'Job is on cooldown');
                }
            }

            // Premium-only enforcement
            if ((bool)($job->premium_only ?? false)) {
                $prem = PremiumService::getOrCreate($user->id);
                if (!PremiumService::isActive($prem)) {
                    Flasher::addError('This job requires active Premium');
                    abort(403, 'Premium required');
                }
            }

            // Energy cost check and deduct
            $stats = UserStats::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$stats) {
                $stats = UserStats::create(['user_id' => $user->id, 'energy' => 100, 'food' => 100, 'water' => 100, 'leisure' => 100, 'health' => 100]);
            }
            $cost = (int)($job->energy_cost ?? 0);
            if ($cost > 0) {
                if ((int)$stats->energy < $cost) {
                    Flasher::addError('Not enough energy');
                    abort(422, 'Not enough energy');
                }
                $stats->energy = max(0, (int)$stats->energy - $cost);
                $stats->save();
            }

            return UserJobRun::create([
                'user_id' => $user->id,
                'job_id' => $job->id,
                'started_at' => $now,
                'completed_at' => $now->addSeconds($job->duration_seconds),
            ]);
        });

        Flasher::addSuccess('Job started: ' . $job->name);
        return response()->json([
            'ok' => true,
            'run_id' => $run->id,
            'ends_at' => CarbonImmutable::parse($run->completed_at)->toIso8601String(),
        ]);
    }

    public function claim(string $key): JsonResponse
    {
        $user = Auth::user();
        $job = JobCatalog::where(['key' => $key, 'is_active' => true])->firstOrFail();
        $now = CarbonImmutable::now();

        $result = DB::transaction(function () use ($user, $job, $now) {
            $run = UserJobRun::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();
            if (!$run || $run->claimed_at) {
                Flasher::addError('No claimable run');
                abort(422, 'No claimable run');
            }
            if ($now->lt(CarbonImmutable::parse($run->completed_at))) {
                Flasher::addError('Job not completed yet');
                abort(422, 'Job not completed yet');
            }

            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $amount = (int)$job->reward_seconds;
            // Premium reward boost
            $prem = PremiumService::getOrCreate($user->id);
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $mult = (float)($benefits['reward_multiplier'] ?? 1.0);
                if ($mult > 1.0) {
                    $amount = (int) floor($amount * $mult);
                }
            }
            if ((int)$reserve->balance_seconds < $amount) {
                Flasher::addError('Reserve insufficient to pay reward');
                abort(422, 'Reserve insufficient to pay reward');
            }

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

            // Move from reserve to wallet
            $reserve->balance_seconds = (int)$reserve->balance_seconds - $amount;
            $reserve->save();
            $wallet->available_seconds = (int)$wallet->available_seconds + $amount;
            if (!$wallet->is_active) { $wallet->is_active = true; $wallet->last_applied_at = $now; }
            $wallet->save();

            $run->claimed_at = $now;
            $run->save();

            return [$run, $wallet, $reserve];
        });

        [$run, $wallet, $reserve] = $result;
        Flasher::addSuccess('Reward claimed: ' . $job->name);
        return response()->json([
            'ok' => true,
            'wallet_seconds' => (int)$wallet->available_seconds,
            'reserve_seconds' => (int)$reserve->balance_seconds,
            'claimed_at' => CarbonImmutable::parse($run->claimed_at)->toIso8601String(),
        ]);
    }
}
