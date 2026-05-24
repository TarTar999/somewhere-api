<?php
// SCRIPT TEMPORAIRE - À SUPPRIMER APRÈS UTILISATION

$basePath = __DIR__ . '/../laravel';

// Dossiers à créer
$directories = [
    $basePath . '/storage',
    $basePath . '/storage/app',
    $basePath . '/storage/app/public',
    $basePath . '/storage/framework',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/logs',
    $basePath . '/bootstrap/cache',
];

echo "<h2>Création des dossiers storage...</h2>";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Créé: " . str_replace($basePath, '', $dir) . "<br>";
        } else {
            echo "❌ Erreur: " . str_replace($basePath, '', $dir) . "<br>";
        }
    } else {
        chmod($dir, 0755);
        echo "✓ Existe déjà: " . str_replace($basePath, '', $dir) . "<br>";
    }
}

// Créer les fichiers .gitignore
$gitignoreContent = "*\n!.gitignore\n";
$gitignoreFiles = [
    $basePath . '/storage/app/.gitignore',
    $basePath . '/storage/framework/cache/.gitignore',
    $basePath . '/storage/framework/sessions/.gitignore',
    $basePath . '/storage/framework/views/.gitignore',
    $basePath . '/storage/logs/.gitignore',
];

echo "<h2>Création des .gitignore...</h2>";

foreach ($gitignoreFiles as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, $gitignoreContent);
        echo "✅ Créé: " . basename(dirname($file)) . "/.gitignore<br>";
    }
}

echo "<h2>Test d'écriture...</h2>";

$testFile = $basePath . '/storage/framework/views/test_write.php';
if (file_put_contents($testFile, '<?php // test')) {
    unlink($testFile);
    echo "✅ Écriture dans views/ : OK<br>";
} else {
    echo "❌ Écriture dans views/ : ÉCHEC<br>";
}

echo "<br><strong>🎉 Setup terminé ! Supprime ce fichier maintenant.</strong>";
