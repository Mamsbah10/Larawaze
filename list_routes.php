#!/usr/bin/env php
<?php

/**
 * Script pour lister toutes les routes de l'application
 * Équivalent de: php artisan route:list
 * Utilisation: php list_routes.php
 */

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$router = $app->make('router');
$routes = $router->getRoutes();

echo "===========================================\n";
echo "📋 Toutes les routes de l'application\n";
echo "===========================================\n\n";

$apiRoutes = [];
$webRoutes = [];
$otherRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri;
    $methods = implode('|', array_map('strtoupper', $route->methods));
    
    // Filtrer les routes système
    if (in_array($methods, ['HEAD', 'OPTIONS'])) {
        continue;
    }
    
    $data = [
        'methods' => $methods,
        'uri' => $uri,
        'action' => $route->action['uses'] ?? 'Closure'
    ];
    
    if (stripos($uri, 'api') === 0) {
        $apiRoutes[] = $data;
    } elseif (stripos($uri, 'api/') === 0) {
        $apiRoutes[] = $data;
    } else {
        $webRoutes[] = $data;
    }
}

if (!empty($apiRoutes)) {
    echo "🔵 ROUTES API:\n";
    echo str_repeat("-", 100) . "\n";
    echo sprintf("%-10s | %-50s | %-35s\n", "METHODS", "URI", "ACTION");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($apiRoutes as $route) {
        echo sprintf("%-10s | %-50s | %-35s\n", 
            $route['methods'], 
            $route['uri'],
            substr($route['action'], 0, 35)
        );
    }
    echo "\n";
}

if (!empty($webRoutes)) {
    echo "🟢 ROUTES WEB:\n";
    echo str_repeat("-", 100) . "\n";
    echo sprintf("%-10s | %-50s | %-35s\n", "METHODS", "URI", "ACTION");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($webRoutes as $route) {
        echo sprintf("%-10s | %-50s | %-35s\n", 
            $route['methods'], 
            $route['uri'],
            substr($route['action'], 0, 35)
        );
    }
    echo "\n";
}

// Chercher spécifiquement les routes traffic
echo "\n🎯 RECHERCHE: Routes contenant 'traffic' ou 'tile'\n";
echo str_repeat("-", 100) . "\n";

$found = false;
foreach ($routes as $route) {
    if (stripos($route->uri, 'traffic') !== false || stripos($route->uri, 'tile') !== false) {
        $found = true;
        echo sprintf("%-10s | %-50s | %-35s\n", 
            implode('|', array_map('strtoupper', $route->methods)), 
            $route->uri,
            $route->action['uses'] ?? 'Closure'
        );
    }
}

if (!$found) {
    echo "❌ Aucune route trouvée avec 'traffic' ou 'tile'!\n";
}

echo "\n";
