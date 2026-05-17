<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Expire old proof of locations daily at midnight
Schedule::command('proof:expire')->daily();

// Process scheduled account deletions daily at 2 AM
Schedule::command('accounts:process-deletions')->dailyAt('02:00');

// Clean up expired web access tokens every hour
Schedule::command('tokens:cleanup')->hourly();
