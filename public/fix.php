<?php
// Fix script - no Laravel loading

// Prevent any Laravel autoloading
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Don't load it
}

header('Content-Type: text/plain');

echo "=== FIX SCRIPT ===\n\n";
echo "Current dir: " . __DIR__ . "\n\n";

// Find and delete config cache in all possible locations
$possiblePaths = [
    __DIR__ . '/..',
    __DIR__ . '/../laravel',
];

foreach ($possiblePaths as $base) {
    $configCache = $base . '/bootstrap/cache/config.php';

    if (file_exists($configCache)) {
        echo "FOUND CONFIG CACHE: $configCache\n";

        // Show first 500 chars to see what path is cached
        $content = file_get_contents($configCache);
        if (strpos($content, '/Users/') !== false) {
            echo "Contains local Mac path - DELETING...\n";
            if (unlink($configCache)) {
                echo "DELETED!\n";
            } else {
                echo "Could not delete - check permissions\n";
            }
        }
    }

    // Also check for routes cache
    $routesCache = $base . '/bootstrap/cache/routes-v7.php';
    if (file_exists($routesCache)) {
        unlink($routesCache);
        echo "Deleted routes cache\n";
    }

    // Create storage directories
    $dirs = [
        $base . '/storage/framework/views',
        $base . '/storage/framework/cache/data',
        $base . '/storage/framework/sessions',
        $base . '/storage/logs',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0755, true)) {
                echo "Created: $dir\n";
            }
        } else {
            echo "Exists: $dir\n";
        }
    }
}

echo "\n=== DONE ===\n";
echo "Now delete this file and refresh your app.\n";
