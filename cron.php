<?php

/**
 * Cron job pour OVH
 * Ce fichier exécute le scheduler Laravel
 */

// Chemin vers le dossier de l'application
$basePath = __DIR__;

// Changer le répertoire de travail
chdir($basePath);

// Exécuter la commande artisan schedule:run
$output = [];
$returnCode = 0;

exec('php artisan schedule:run 2>&1', $output, $returnCode);

// Logger le résultat (optionnel)
$logFile = $basePath . '/storage/logs/cron.log';
$logMessage = '[' . date('Y-m-d H:i:s') . '] Schedule run - Code: ' . $returnCode . "\n";
$logMessage .= implode("\n", $output) . "\n\n";

file_put_contents($logFile, $logMessage, FILE_APPEND);

// Afficher le résultat
echo $logMessage;
