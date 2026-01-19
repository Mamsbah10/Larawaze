#!/usr/bin/env php
<?php

/**
 * Script de debug pour vérifier les routes de traffic
 * Utilisation: php debug_traffic_routes.php
 */

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "===========================================\n";
echo "🔍 Debug Routes TomTom Traffic\n";
echo "===========================================\n\n";

// Vérifier les routes enregistrées
$router = $app->make('router');
$routes = $router->getRoutes();

echo "📋 Recherche des routes contenant 'traffic' ou 'tile':\n\n";

$found = false;
foreach ($routes as $route) {
    $uri = $route->uri;
    $methods = implode(', ', $route->methods);
    
    if (stripos($uri, 'traffic') !== false || stripos($uri, 'tile') !== false) {
        $found = true;
        echo "✅ Route trouvée:\n";
        echo "   URI: $uri\n";
        echo "   Méthodes: $methods\n";
        echo "   Contrôleur: " . ($route->controller ?? 'N/A') . "\n";
        echo "   Namespace: " . ($route->namespace ?? 'N/A') . "\n\n";
    }
}

if (!$found) {
    echo "❌ Aucune route trouvée contenant 'traffic' ou 'tile'!\n\n";
}

// Afficher toutes les routes API
echo "\n📋 Toutes les routes API:\n\n";
foreach ($routes as $route) {
    if (stripos($route->uri, 'api') !== false) {
        echo "   " . implode(', ', $route->methods) . " /api/" . str_replace('api/', '', $route->uri) . "\n";
    }
}

echo "\n===========================================\n";
echo "🧪 Test de la route\n";
echo "===========================================\n\n";

// Créer une requête de test
$request = \Illuminate\Http\Request::create('/api/traffic/tile/15/16023/15894', 'GET');
$request = $request->setUserResolver(function () {
    return null;
});

echo "URL testée: /api/traffic/tile/15/16023/15894\n";
echo "Méthode: GET\n\n";

try {
    // Utiliser le kernel HTTP pour tester la route
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Vérifier si la route existe
    $found = false;
    foreach ($routes as $route) {
        if ($route->matches($request)) {
            $found = true;
            echo "✅ Route trouvée pour cette requête!\n";
            echo "   Controller: " . $route->getControllerClass() . "\n";
            echo "   Method: " . $route->getControllerMethod() . "\n";
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Aucune route ne correspond à cette requête!\n";
        echo "\n💡 Vérifications à faire:\n";
        echo "   1. La route est-elle correctement définie dans routes/api.php?\n";
        echo "   2. Le fichier routes/api.php est-il chargé par le ServiceProvider?\n";
        echo "   3. Les paramètres {z}, {x}, {y} acceptent-ils les nombres?\n";
    }
} catch (\Exception $e) {
    echo "⚠️ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n";
