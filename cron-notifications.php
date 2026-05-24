<?php

/**
 * CRON: Notifie les utilisateurs dont les documents expirent bientôt
 * Commande OVH: somewhere_api/cron-notifications.php
 * Fréquence: 0 9 * * * (tous les jours à 9h)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Notifier pour les documents expirant dans 7 jours
$kernel->call('notifications:expiring-documents', ['--days' => 7]);

echo "Notifications sent at " . date('Y-m-d H:i:s') . "\n";
