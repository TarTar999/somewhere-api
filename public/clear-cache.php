<?php
// TEMPORARY SCRIPT - DELETE AFTER USE

$basePath = __DIR__ . '/../laravel';

// If everything is in www/, use this instead:
// $basePath = __DIR__ . '/..';

echo "<h2>Clearing Laravel Cache...</h2>";

// Files to delete
$cacheFiles = [
    $basePath . '/bootstrap/cache/config.php',
    $basePath . '/bootstrap/cache/routes-v7.php',
    $basePath . '/bootstrap/cache/services.php',
    $basePath . '/bootstrap/cache/packages.php',
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Deleted: " . basename($file) . "<br>";
        } else {
            echo "Error deleting: " . basename($file) . "<br>";
        }
    } else {
        echo "Not found: " . basename($file) . "<br>";
    }
}

// Clear compiled views
$viewsPath = $basePath . '/storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        unlink($file);
        $count++;
    }
    echo "Deleted $count compiled views<br>";
}

// Create storage directories if missing
$directories = [
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/logs',
    $basePath . '/bootstrap/cache',
];

echo "<h2>Creating directories...</h2>";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created: " . $dir . "<br>";
        }
    } else {
        echo "OK: " . $dir . "<br>";
    }
}

echo "<br><strong>Done! Now delete this file and try again.</strong>";
