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

