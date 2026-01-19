#!/usr/bin/env php
<?php

/**
 * Script diagnostic complet pour le problème des routes traffic
 * Utilisation: php diagnose_traffic.php
 */

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║       🔍 DIAGNOSTIC COMPLET - ERREUR 404 TRAFFIC TILE     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============ ÉTAPE 1: Vérifier le fichier routes/api.php ============
echo "📂 ÉTAPE 1: Vérification du fichier routes/api.php\n";
echo str_repeat("-", 60) . "\n";

$apiRoutesFile = __DIR__ . '/routes/api.php';
if (file_exists($apiRoutesFile)) {
    echo "✅ Fichier found: $apiRoutesFile\n";
    
    $content = file_get_contents($apiRoutesFile);
    
    if (stripos($content, 'traffic') !== false) {
        echo "✅ Le mot 'traffic' est présent dans routes/api.php\n";
        
        if (stripos($content, "Route::prefix('traffic')") !== false) {
            echo "✅ Route::prefix('traffic') trouvé\n";
        } else {
            echo "❌ Route::prefix('traffic') NOT found\n";
        }
        
        if (stripos($content, "getTrafficTile") !== false) {
            echo "✅ Méthode getTrafficTile référencée\n";
        } else {
            echo "❌ Méthode getTrafficTile NOT referenced\n";
        }
        
        if (stripos($content, "'/tile/{z}/{x}/{y}'") !== false || 
            stripos($content, '"/tile/{z}/{x}/{y}"') !== false) {
            echo "✅ Route '/tile/{z}/{x}/{y}' définie\n";
        } else {
            echo "❌ Route '/tile/{z}/{x}/{y}' NOT found\n";
        }
    } else {
        echo "❌ Le mot 'traffic' n'est PAS présent dans routes/api.php!\n";
    }
} else {
    echo "❌ Fichier NOT found: $apiRoutesFile\n";
}

echo "\n";

// ============ ÉTAPE 2: Vérifier le contrôleur ============
echo "🔧 ÉTAPE 2: Vérification du contrôleur TrafficController\n";
echo str_repeat("-", 60) . "\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/TrafficController.php';
if (file_exists($controllerFile)) {
    echo "✅ Fichier found: $controllerFile\n";
    
    $content = file_get_contents($controllerFile);
    
    if (stripos($content, 'class TrafficController') !== false) {
        echo "✅ Classe TrafficController définie\n";
    }
    
    if (stripos($content, 'public function getTrafficTile') !== false) {
        echo "✅ Méthode getTrafficTile définie\n";
    } else {
        echo "❌ Méthode getTrafficTile NOT defined\n";
    }
} else {
    echo "❌ Fichier NOT found: $controllerFile\n";
}

echo "\n";

// ============ ÉTAPE 3: Vérifier les routes enregistrées ============
echo "🛣️  ÉTAPE 3: Routes enregistrées dans Laravel\n";
echo str_repeat("-", 60) . "\n";

$router = $app->make('router');
$routes = $router->getRoutes();

echo "Total des routes: " . count($routes) . "\n\n";

$trafficRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri;
    if (stripos($uri, 'traffic') !== false) {
        $trafficRoutes[] = [
            'methods' => implode(', ', array_map('strtoupper', $route->methods)),
            'uri' => $uri,
            'action' => $route->action['uses'] ?? 'N/A'
        ];
    }
}

if (count($trafficRoutes) > 0) {
    echo "✅ Routes traffic trouvées:\n";
    foreach ($trafficRoutes as $route) {
        echo "   • " . $route['methods'] . " /" . $route['uri'] . "\n";
        echo "     Action: " . $route['action'] . "\n";
    }
} else {
    echo "❌ AUCUNE route 'traffic' n'est enregistrée dans Laravel!\n";
}

echo "\n";

// ============ ÉTAPE 4: Tester avec le Matcher ============
echo "🧪 ÉTAPE 4: Vérification du matching des routes\n";
echo str_repeat("-", 60) . "\n";

$testUrls = [
    '/api/traffic/tile/15/16023/15894',
    '/traffic/tile/15/16023/15894',
];

foreach ($testUrls as $url) {
    echo "Test URL: $url\n";
    
    $request = \Illuminate\Http\Request::create($url, 'GET');
    $matched = false;
    
    foreach ($routes as $route) {
        if ($route->matches($request)) {
            $matched = true;
            echo "   ✅ Matched to: " . $route->uri . "\n";
            echo "   Action: " . ($route->action['uses'] ?? 'N/A') . "\n";
            break;
        }
    }
    
    if (!$matched) {
        echo "   ❌ NO MATCH FOUND\n";
    }
    echo "\n";
}

// ============ ÉTAPE 5: Recommandations ============
echo "💡 RECOMMANDATIONS\n";
echo str_repeat("-", 60) . "\n";

if (count($trafficRoutes) === 0) {
    echo "⚠️  Les routes traffic ne sont pas enregistrées!\n\n";
    echo "Solutions possibles:\n";
    echo "1️⃣  Vérifiez que routes/api.php est correctement chargé\n";
    echo "   dans bootstrap/app.php ou app/Providers/AppServiceProvider.php\n\n";
    echo "2️⃣  Exécutez: php artisan route:clear\n";
    echo "   Cela va vider le cache des routes\n\n";
    echo "3️⃣  Exécutez: php artisan route:cache\n";
    echo "   Cela va recréer le cache des routes\n\n";
    echo "4️⃣  Vérifiez qu'il n'y a pas d'erreur de syntaxe\n";
    echo "   dans routes/api.php\n\n";
} else {
    echo "✅ Les routes traffic sont correctement enregistrées!\n";
    echo "   Le problème peut venir du frontend qui utilise\n";
    echo "   une mauvaise URL.\n\n";
}

echo "\n╚════════════════════════════════════════════════════════════╝\n\n";
