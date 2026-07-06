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

// Notify users about expiring documents daily at 9 AM
Schedule::command('notifications:expiring-documents --days=7')->dailyAt('09:00');

// Send reminder for documents expiring in 3 days at 9 AM
Schedule::command('notifications:expiring-documents --days=3')->dailyAt('09:00');

// Sync ENEO power outage programmes daily at 6 AM and 6 PM
Schedule::command('eneo:sync-outages')->dailyAt('06:00');
Schedule::command('eneo:sync-outages')->dailyAt('18:00');
