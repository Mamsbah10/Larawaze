#!/usr/bin/env php
<?php

/**
 * Script final pour déboguer le problème 404 traffic/tile
 * Analyse tous les aspects du problème
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     🔍 DÉBOGAGE COMPLET - ERREUR 404 TRAFFIC/TILE      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============ 1. Vérifier les fichiers ============
echo "📂 FICHIERS\n";
echo str_repeat("-", 60) . "\n";

$files = [
    'routes/api.php' => 'Routes API',
    'app/Http/Controllers/TrafficController.php' => 'Contrôleur Traffic',
    'app/Services/TomTomService.php' => 'Service TomTom',
    '.env' => 'Configuration environnement',
    'config/services.php' => 'Configuration services',
];

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $desc ($file)\n";
    } else {
        echo "❌ $desc ($file) - NOT FOUND\n";
    }
}

echo "\n";

// ============ 2. Vérifier routes/api.php ============
echo "📋 CONTENU DE routes/api.php\n";
echo str_repeat("-", 60) . "\n";

$apiRoutesFile = __DIR__ . '/routes/api.php';
$content = file_get_contents($apiRoutesFile);

// Chercher les sections importantes
echo "Recherche de 'TrafficController'...\n";
if (stripos($content, 'TrafficController') !== false) {
    echo "✅ TrafficController référencé\n";
} else {
    echo "❌ TrafficController NOT référencé\n";
}

echo "\nRecherche de 'prefix(\"traffic\")'...\n";
if (stripos($content, "prefix('traffic')") !== false) {
    echo "✅ Prefix 'traffic' trouvé\n";
} else {
    echo "❌ Prefix 'traffic' NOT found\n";
}

echo "\nRecherche de 'getTrafficTile'...\n";
if (stripos($content, "getTrafficTile") !== false) {
    echo "✅ Méthode getTrafficTile référencée\n";
} else {
    echo "❌ Méthode getTrafficTile NOT référencée\n";
}

echo "\n";

// ============ 3. Afficher la définition de la route ============
echo "🛣️  DÉFINITION DE LA ROUTE\n";
echo str_repeat("-", 60) . "\n";

// Extraire la section traffic
if (preg_match("/Route::prefix\('traffic'\)->group\(function \(\) \{(.+?)\}\);/s", $content, $matches)) {
    $trafficGroup = $matches[1];
    
    // Chercher la route tile
    if (preg_match("/Route::get\(['\"]\\/tile\\/\{z\}\\/\{x\}\\/\{y\}['\"]\s*,\s*\[(.+?)\]\)/s", $trafficGroup, $tileMatch)) {
        echo "✅ Route trouvée:\n";
        echo "   Route::get('/tile/{z}/{x}/{y}', [" . trim($tileMatch[1]) . "])\n";
    } else {
        echo "❌ Route '/tile/{z}/{x}/{y}' NOT found dans le groupe traffic\n";
    }
} else {
    echo "❌ Groupe 'prefix(\"traffic\")' NOT found\n";
}

echo "\n";

// ============ 4. Vérifier les imports ============
echo "📥 IMPORTS DANS routes/api.php\n";
echo str_repeat("-", 60) . "\n";

// Vérifier l'import de TrafficController
if (stripos($content, "use App\\Http\\Controllers\\TrafficController") !== false) {
    echo "✅ use App\\Http\\Controllers\\TrafficController\n";
} else {
    echo "❌ Import de TrafficController NOT found\n";
}

echo "\n";

// ============ 5. Résumé complet ============
echo "📊 ANALYSE DU PROBLÈME\n";
echo str_repeat("-", 60) . "\n\n";

echo "Le problème 404 peut être causé par:\n\n";

echo "1️⃣  LA ROUTE N'EXISTE PAS\n";
echo "   • Vérifiez que routes/api.php contient la définition complète\n";
echo "   • Exécutez: php artisan route:clear && php artisan route:cache\n\n";

echo "2️⃣  LA CLÉ API TOMTOM EST INVALIDE\n";
echo "   • Même si la route existe, la méthode peut retourner 404\n";
echo "   • si la clé API est vide ou invalide\n";
echo "   • Vérifiez: TOMTOM_API_KEY dans .env\n\n";

echo "3️⃣  LA TUILE N'EXISTE PAS CHEZ TOMTOM\n";
echo "   • Les coordonnées z=15, x=16023, y=15894 peuvent être invalides\n";
echo "   • Vérifiez les coordonnées de la tuile\n\n";

echo "4️⃣  LE SERVICE TOMTOM NE RÉPOND PAS\n";
echo "   • La requête vers l'API TomTom échoue\n";
echo "   • Vérifiez votre connexion Internet\n";
echo "   • Vérifiez le statut de l'API TomTom\n\n";

echo "SOLUTIONS À ESSAYER:\n";
echo str_repeat("-", 60) . "\n\n";

echo "Étape 1: Nettoyer le cache des routes\n";
echo "  $ php artisan route:clear\n";
echo "  $ php artisan route:cache\n\n";

echo "Étape 2: Vérifier que .env contient TOMTOM_API_KEY\n";
echo "  $ grep TOMTOM_API_KEY .env\n\n";

echo "Étape 3: Vérifier la route avec artisan\n";
echo "  $ php artisan route:list | grep traffic\n\n";

echo "Étape 4: Tester avec curl\n";
echo "  $ curl -v http://localhost:8000/api/traffic/tile/15/16023/15894\n\n";

echo "Étape 5: Vérifier les logs\n";
echo "  $ tail -50 storage/logs/laravel.log\n\n";

echo "╚════════════════════════════════════════════════════════════╝\n\n";
