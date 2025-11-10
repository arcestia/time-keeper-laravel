<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\TimeBankService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('time:settle', function (TimeBankService $bank) {
    $count = $bank->settleAllActive();
    $this->info("Settled accounts: {$count}");
})->purpose('Apply time decay to all active accounts once');
