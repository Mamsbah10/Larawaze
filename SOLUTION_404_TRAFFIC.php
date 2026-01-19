#!/usr/bin/env php
<?php

/**
 * Script pour analyser et corriger le problème des coordonnées de tuiles
 * Explique pourquoi les tuiles retournent 404
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║      🔧 SOLUTION AU PROBLÈME 404 DES TUILES TRAFFIC      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "🎯 DIAGNOSTIC\n";
echo str_repeat("-", 60) . "\n";
echo "Erreur 404 reçue de TomTom API pour les coordonnées:\n";
echo "  • Zoom (z): 15\n";
echo "  • X: 16023\n";
echo "  • Y: 15894\n";
echo "  • URL: /traffic/map/4/flow/absolute/15/16023/15894.png\n\n";

echo "Cause: Cette tuile n'existe pas chez TomTom ou cette région\n";
echo "       ne dispose pas de données traffic pour ce niveau de zoom.\n\n";

echo "═" * 60 . "\n\n";

echo "📝 POSSIBLES EXPLICATIONS\n";
echo str_repeat("-", 60) . "\n";
echo "1. Les coordonnées correspondent à une zone sans données traffic\n";
echo "   (océan, zone non couverte, etc.)\n\n";

echo "2. Le niveau de zoom (z=15) n'est pas supporté pour cette région\n\n";

echo "3. La tuile a expiré ou n'est pas disponible temporairement\n\n";

echo "═" * 60 . "\n\n";

echo "🛠️  SOLUTIONS\n";
echo str_repeat("-", 60) . "\n\n";

echo "OPTION 1: Tester avec des coordonnées connues comme valides\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Essayez cette URL dans votre navigateur:\n";
echo "http://localhost:8000/api/traffic/tile/15/16408/10729\n\n";

echo "OPTION 2: Ajuster les coordonnées en fonction de votre localisation\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Pour convertir lat/lon en coordonnées de tuile Web Mercator:\n";
echo "  n = 2^zoom\n";
echo "  x = floor((lon + 180) / 360 * n)\n";
echo "  y = floor((1 - log(tan(lat * pi / 180) + 1/cos(lat * pi / 180))\n";
echo "             / pi) / 2 * n)\n\n";

echo "Exemple de conversion:\n";
echo "  Latitude: 48.8566 (Paris)\n";
echo "  Longitude: 2.3522 (Paris)\n";
echo "  Zoom: 15\n";
echo "  → x: 16408, y: 10729\n\n";

echo "OPTION 3: Modifier le contrôleur pour ajouter du logging\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Ajoutez du logging dans TrafficController::getTrafficTile():\n\n";
echo "  Log::info('Traffic tile request', [\n";
echo "    'z' => \$z,\n";
echo "    'x' => \$x,\n";
echo "    'y' => \$y,\n";
echo "    'url' => \$tileUrl,\n";
echo "    'response_status' => \$response->status()\n";
echo "  ]);\n\n";

echo "OPTION 4: Vérifier la couverture TomTom\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Consultez la carte de couverture TomTom:\n";
echo "https://developer.tomtom.com/products\n";
echo "Vérifiez que votre région a les données traffic disponibles.\n\n";

echo "═" * 60 . "\n\n";

echo "🔍 CODE À CORRIGER\n";
echo str_repeat("-", 60) . "\n\n";

echo "Dans app/Http/Controllers/TrafficController.php:\n";
echo "Ajoutez un meilleur handling des erreurs:\n\n";

echo "<?php\n";
echo "public function getTrafficTile(\$z, \$x, \$y): \\Illuminate\\Http\\Response\n";
echo "{\n";
echo "    try {\n";
echo "        \$tileUrl = \"{$this->tomTomService->getBaseUrl()}/traffic/map/4/\"\n";
echo "                  . \"flow/absolute/{\$z}/{\$x}/{\$y}.png\"\n";
echo "                  . \"?key={$this->tomTomService->getApiKey()}\";\n";
echo "        \n";
echo "        // Log pour déboguer\n";
echo "        Log::debug('Requesting traffic tile', [\n";
echo "            'z' => \$z, 'x' => \$x, 'y' => \$y,\n";
echo "            'url' => str_replace($apiKey, '***', \$tileUrl)\n";
echo "        ]);\n";
echo "        \n";
echo "        \$response = Http::timeout(30)->get(\$tileUrl);\n";
echo "        \n";
echo "        if (\$response->failed()) {\n";
echo "            Log::warning('Traffic tile not found', [\n";
echo "                'status' => \$response->status(),\n";
echo "                'z' => \$z, 'x' => \$x, 'y' => \$y\n";
echo "            ]);\n";
echo "            return response('Tile not available', 404)\n";
echo "                ->header('Access-Control-Allow-Origin', '*');\n";
echo "        }\n";
echo "        \n";
echo "        return response(\$response->body(), 200)\n";
echo "            ->header('Content-Type', 'image/png')\n";
echo "            ->header('Access-Control-Allow-Origin', '*')\n";
echo "            ->header('Cache-Control', 'public, max-age=3600');\n";
echo "    } catch (\\Exception \$e) {\n";
echo "        Log::error('Traffic tile error: ' . \$e->getMessage());\n";
echo "        return response('Tile proxy error', 500)\n";
echo "            ->header('Access-Control-Allow-Origin', '*');\n";
echo "    }\n";
echo "}\n";
echo "?>\n\n";

echo "═" * 60 . "\n\n";

echo "📋 PROCHAINES ÉTAPES\n";
echo str_repeat("-", 60) . "\n";
echo "1. Vérifiez que vous testez avec des coordonnées valides\n";
echo "2. Consultez les logs Laravel:\n";
echo "   tail -100 storage/logs/laravel.log\n";
echo "3. Testez directement l'API TomTom pour les coordonnées\n";
echo "4. Ajoutez du logging au contrôleur pour mieux déboguer\n";
echo "5. Assurez-vous que votre abonnement TomTom inclut\n";
echo "   les données traffic pour votre région\n\n";

echo "╚════════════════════════════════════════════════════════════╝\n\n";
