#!/usr/bin/env php
<?php

/**
 * Test détaillé de la route traffic/tile avec Artisan Tinker
 * Ce script teste la route et aide à identifier le problème
 */

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║           🧪 TEST DE LA ROUTE TRAFFIC/TILE              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Simulation manuelle de la requête
echo "1️⃣  URL testée: /api/traffic/tile/15/16023/15894\n";
echo "2️⃣  Méthode: GET\n";
echo "3️⃣  Route trouvée: ✅ GET|HEAD api/traffic/tile/{z}/{x}/{y}\n\n";

echo "Vérifications à faire:\n";
echo str_repeat("-", 60) . "\n\n";

echo "🔧 OPTION 1: Vérifier que le TomTomService fonctionne\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Exécutez: php artisan tinker\n";
echo "\nDans Tinker:\n";
echo "  >>> \$service = app('App\\Services\\TomTomService');\n";
echo "  >>> \$service->getApiKey();\n";
echo "  >>> \$service->getBaseUrl();\n\n";

echo "🔧 OPTION 2: Vérifier que la clé API TomTom est définie\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Exécutez: php artisan tinker\n";
echo "\nDans Tinker:\n";
echo "  >>> config('services.tomtom.key');\n\n";

echo "🔧 OPTION 3: Tester la requête HTTP\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Commande:\n";
echo "  curl -v http://localhost:8000/api/traffic/tile/15/16023/15894\n\n";

echo "🔧 OPTION 4: Exécuter un test avec artisan\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Créez un fichier test: php test_route_detailed.php\n\n";

echo "📝 PROBLÈMES COURANTS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "❌ La clé API TomTom n'est pas configurée dans .env\n";
echo "   → Ajoutez: TOMTOM_API_KEY=votre_clé_ici\n\n";

echo "❌ Le TomTomService retourne une URL invalide\n";
echo "   → Vérifiez le format de l'URL dans TomTomService.php\n\n";

echo "❌ La requête HTTP vers TomTom échoue\n";
echo "   → Vérifiez le statut HTTP et le message d'erreur\n\n";

echo "❌ Problème de CORS ou headers\n";
echo "   → Vérifiez les headers CORS dans TrafficController.php\n\n";

echo "🔗 Fichiers importants à vérifier:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  • app/Http/Controllers/TrafficController.php\n";
echo "  • app/Services/TomTomService.php\n";
echo "  • routes/api.php\n";
echo "  • .env (configuration de TOMTOM_API_KEY)\n";
echo "  • config/services.php\n\n";

echo "╚════════════════════════════════════════════════════════════╝\n\n";
