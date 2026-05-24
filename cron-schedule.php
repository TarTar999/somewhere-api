<?php

/**
 * CRON: Lance le scheduler Laravel
 * Commande OVH: somewhere_api/cron-schedule.php
 * Fréquence: * * * * * (chaque minute)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('schedule:run');

echo "Schedule executed at " . date('Y-m-d H:i:s') . "\n";
