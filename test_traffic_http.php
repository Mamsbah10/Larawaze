#!/usr/bin/env php
<?php

/**
 * Script de test HTTP pour la route traffic/tile
 * Utilisation: php test_traffic_http.php
 */

echo "===========================================\n";
echo "🌐 Test HTTP de la route Traffic Tile\n";
echo "===========================================\n\n";

$baseUrl = 'http://localhost:8000';
$testUrls = [
    '/api/traffic/tile/15/16023/15894',
    '/traffic/tile/15/16023/15894',
    '/api/tile/15/16023/15894',
];

foreach ($testUrls as $url) {
    echo "Testing: $baseUrl$url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    
    if ($errno) {
        echo "   ❌ Erreur: " . curl_error($ch) . "\n";
    } else {
        if ($httpCode === 404) {
            echo "   ❌ 404 Not Found\n";
        } elseif ($httpCode === 200) {
            echo "   ✅ 200 OK - Route trouvée!\n";
        } else {
            echo "   ⚠️ HTTP $httpCode\n";
        }
    }
    
    curl_close($ch);
    echo "\n";
}

echo "===========================================\n";
echo "💡 Vérifications à faire:\n";
echo "===========================================\n";
echo "1. Assurez-vous que le serveur Laravel est bien lancé\n";
echo "2. Vérifiez routes/api.php - la route est-elle présente?\n";
echo "3. Vérifiez que le contrôleur TrafficController existe\n";
echo "4. Vérifiez les routes avec: php artisan route:list\n";
echo "\n";
