#!/usr/bin/env php
<?php

/**
 * Script pour tester la route traffic/tile en profondeur
 * Utilisation: php test_route_detailed.php
 * 
 * Ce script teste la route en appelant directement le contrôleur
 */

$appPath = __DIR__;

// Charger l'autoloader de Composer
require $appPath . '/vendor/autoload.php';

// Créer l'application
$app = require_once $appPath . '/bootstrap/app.php';

// Rendre l'application un singleton
\Illuminate\Container\Container::setInstance($app);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        🧪 TEST DÉTAILLÉ DE LA ROUTE TRAFFIC/TILE         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============ ÉTAPE 1: Tester la configuration ============
echo "📋 ÉTAPE 1: Configuration du Service TomTom\n";
echo str_repeat("-", 60) . "\n";

try {
    $apiKey = config('services.tomtom.key');
    
    if ($apiKey) {
        echo "✅ Clé API TomTom trouvée\n";
        echo "   Clé: " . substr($apiKey, 0, 10) . "***\n";
    } else {
        echo "❌ Clé API TomTom NOT found dans config('services.tomtom.key')\n";
        echo "   Vérifiez votre fichier .env pour TOMTOM_API_KEY\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors de la lecture de la configuration: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ ÉTAPE 2: Tester le Service ============
echo "🔧 ÉTAPE 2: Test du TomTomService\n";
echo str_repeat("-", 60) . "\n";

try {
    $tomTomService = $app->make('App\Services\TomTomService');
    echo "✅ TomTomService instancié\n";
    
    $baseUrl = $tomTomService->getBaseUrl();
    echo "   Base URL: $baseUrl\n";
    
    $apiKey = $tomTomService->getApiKey();
    if ($apiKey) {
        echo "   Clé API: " . substr($apiKey, 0, 10) . "***\n";
    } else {
        echo "   ❌ Clé API non disponible dans le service!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur avec TomTomService: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ ÉTAPE 3: Tester le Contrôleur ============
echo "🎯 ÉTAPE 3: Test du TrafficController\n";
echo str_repeat("-", 60) . "\n";

try {
    $controller = $app->make('App\Http\Controllers\TrafficController');
    echo "✅ TrafficController instancié\n";
    
    // Tester la méthode getTrafficTile
    echo "   Test de la méthode getTrafficTile()\n";
    echo "   Paramètres: z=15, x=16023, y=15894\n\n";
    
    // Créer une fausse requête
    $response = $controller->getTrafficTile(15, 16023, 15894);
    
    echo "   ✅ Réponse reçue du contrôleur\n";
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Content-Type: " . $response->headers->get('Content-Type') . "\n";
    
} catch (\Exception $e) {
    echo "   ⚠️ Erreur lors de l'appel: " . $e->getMessage() . "\n";
    echo "   Ceci peut être normal si TomTom ne répond pas\n";
}

echo "\n";

// ============ ÉTAPE 4: Construire l'URL TomTom ============
echo "🔗 ÉTAPE 4: Construction de l'URL TomTom\n";
echo str_repeat("-", 60) . "\n";

try {
    $tomTomService = $app->make('App\Services\TomTomService');
    $z = 15;
    $x = 16023;
    $y = 15894;
    
    $baseUrl = $tomTomService->getBaseUrl();
    $apiKey = $tomTomService->getApiKey();
    
    $tileUrl = "{$baseUrl}/traffic/map/4/flow/absolute/{$z}/{$x}/{$y}.png?key={$apiKey}";
    
    echo "✅ URL TomTom construite:\n";
    echo "   " . str_replace($apiKey, '***', $tileUrl) . "\n";
    
    // Tester si l'URL est valide
    echo "\n   Test de connexion à TomTom...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $tileUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    
    if ($errno) {
        echo "   ❌ Erreur CURL: " . curl_error($ch) . "\n";
    } else {
        if ($httpCode === 200) {
            echo "   ✅ TomTom API répond correctement (HTTP 200)\n";
        } elseif ($httpCode === 404) {
            echo "   ❌ Tuile non trouvée chez TomTom (HTTP 404)\n";
        } elseif ($httpCode === 401) {
            echo "   ❌ Problème d'authentification TomTom (HTTP 401)\n";
            echo "      Vérifiez votre clé API!\n";
        } else {
            echo "   ⚠️ Réponse TomTom: HTTP $httpCode\n";
        }
    }
    
    curl_close($ch);
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ ÉTAPE 5: Résumé ============
echo "📊 RÉSUMÉ\n";
echo str_repeat("-", 60) . "\n";

echo "Pour résoudre les erreurs 404:\n\n";

echo "1️⃣  Si TomTom API ne répond pas avec 200 OK:\n";
echo "   • Vérifiez votre clé API TOMTOM_API_KEY dans .env\n";
echo "   • Vérifiez que votre compte TomTom est actif\n";
echo "   • Vérifiez que vous avez les bonnes permissions\n\n";

echo "2️⃣  Si la route ne correspond pas:\n";
echo "   • Exécutez: php artisan route:clear\n";
echo "   • Exécutez: php artisan route:cache\n\n";

echo "3️⃣  Pour tester manuellement:\n";
echo "   • curl -v http://localhost:8000/api/traffic/tile/15/16023/15894\n\n";

echo "╚════════════════════════════════════════════════════════════╝\n\n";
