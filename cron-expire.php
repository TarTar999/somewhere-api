<?php

/**
 * CRON: Marque les documents expirés comme "expired"
 * Commande OVH: somewhere_api/cron-expire.php
 * Fréquence: 0 0 * * * (tous les jours à minuit)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('proof:expire');

echo "Expired proofs processed at " . date('Y-m-d H:i:s') . "\n";
