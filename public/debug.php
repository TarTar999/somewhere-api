<?php
// DEBUG SCRIPT - DELETE IMMEDIATELY AFTER USE

echo "<h1>Server Debug</h1>";
echo "<h2>Current Directory</h2>";
echo "<pre>" . __DIR__ . "</pre>";

echo "<h2>Parent Directory Contents</h2>";
echo "<pre>";
$parent = __DIR__ . '/..';
foreach (scandir($parent) as $item) {
    if ($item !== '.' && $item !== '..') {
        $path = $parent . '/' . $item;
        $type = is_dir($path) ? '[DIR]' : '[FILE]';
        echo "$type $item\n";
    }
}
echo "</pre>";

echo "<h2>Looking for bootstrap/cache/config.php</h2>";

// Check multiple possible locations
$locations = [
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../laravel/bootstrap/cache/config.php',
    __DIR__ . '/../../bootstrap/cache/config.php',
];

foreach ($locations as $loc) {
    if (file_exists($loc)) {
        echo "<p>FOUND: $loc</p>";
        echo "<p>Deleting...</p>";
        if (unlink($loc)) {
            echo "<p style='color:green'>DELETED!</p>";
        } else {
            echo "<p style='color:red'>Could not delete</p>";
        }
    } else {
        echo "<p>Not found: $loc</p>";
    }
}

echo "<h2>Looking for storage/framework/views</h2>";

$viewLocations = [
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../laravel/storage/framework/views',
];

foreach ($viewLocations as $loc) {
    if (is_dir($loc)) {
        echo "<p style='color:green'>FOUND: $loc</p>";
        // Create test file
        $test = $loc . '/test.php';
        if (file_put_contents($test, '<?php')) {
            unlink($test);
            echo "<p style='color:green'>Write test: OK</p>";
        } else {
            echo "<p style='color:red'>Write test: FAILED</p>";
        }
    } else {
        echo "<p>Not found: $loc</p>";
        // Try to create it
        if (mkdir($loc, 0755, true)) {
            echo "<p style='color:green'>Created: $loc</p>";
        }
    }
}

echo "<h2>Delete this file now!</h2>";
