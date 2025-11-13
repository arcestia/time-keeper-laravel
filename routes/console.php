<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Run time:settle every minute (Laravel scheduler granularity). Bulk mode is default in the command.
Schedule::command('time:settle')
    ->everyMinute()
    ->withoutOverlapping();

// Record a snapshot every minute for charts
Schedule::command('time:snapshot')
    ->everyMinute()
    ->withoutOverlapping();

// Leaderboard rewards (UTC)
// Daily: award yesterday at 00:05 UTC
Schedule::command('leaderboard:award --period=daily')
    ->dailyAt('00:05')
    ->timezone('UTC')
    ->withoutOverlapping();

// Weekly: award last 7-day window at 00:10 UTC on Mondays
Schedule::command('leaderboard:award --period=weekly')
    ->weeklyOn(1, '00:10') // 1 = Monday
    ->timezone('UTC')
    ->withoutOverlapping();

// Monthly: award previous month on the 1st at 00:15 UTC
Schedule::command('leaderboard:award --period=monthly')
    ->monthlyOn(1, '00:15')
    ->timezone('UTC')
    ->withoutOverlapping();

