<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Firebase Cloud Messaging (FCM)
    |
    | Pour obtenir les credentials:
    | 1. Aller sur https://console.firebase.google.com
    | 2. Sélectionner le projet
    | 3. Paramètres du projet → Comptes de service
    | 4. Générer une nouvelle clé privée
    | 5. Sauvegarder le fichier JSON
    |
    */

    // Chemin vers le fichier de credentials JSON
    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),

    // OU credentials encodés en base64 (pour les environnements sans accès fichier)
    'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),

    // Project ID Firebase (optionnel, extrait des credentials)
    'project_id' => env('FIREBASE_PROJECT_ID'),

];
