#!/usr/bin/env php
<?php

/**
 * Test endpoint alternatif TomTom - Traffic Flow API
 * Au lieu de tuiles Traffic Maps, utiliser l'API Flow
 */

require __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║         🔄 TEST ENDPOINT ALTERNATIF - TRAFFIC FLOW API    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$apiKey = $_ENV['TOMTOM_API_KEY'] ?? null;

if (!$apiKey) {
    echo "❌ TOMTOM_API_KEY non trouvée\n";
    exit(1);
}

// Test 1: Traffic Flow Segment Data (JSON)
echo "1️⃣  TEST - Traffic Flow Segment Data (JSON)\n";
echo str_repeat("-", 60) . "\n";

$flowUrl = "https://api.tomtom.com/traffic/services/4/flowSegmentData/absolute/10/json";
$params = "?point=48.8566,2.3522&unit=KMPH&key=$apiKey";

echo "Endpoint: " . str_replace($apiKey, '***', $flowUrl . $params) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $flowUrl . $params,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! This endpoint works.\n\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "Données reçues:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    }
} else {
    echo "❌ Erreur HTTP $httpCode\n\n";
    echo "Réponse: " . substr($response, 0, 200) . "\n\n";
}

// Test 2: Incidents API
echo "2️⃣  TEST - Traffic Incidents API (JSON)\n";
echo str_repeat("-", 60) . "\n";

$incidentsUrl = "https://api.tomtom.com/traffic/incidents/json";
$params = "?bounds=48.8,2.2,48.9,2.4&key=$apiKey";

echo "Endpoint: " . str_replace($apiKey, '***', $incidentsUrl . $params) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $incidentsUrl . $params,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! This endpoint works.\n\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "Nombre d'incidents: " . count($data['incidents'] ?? []) . "\n";
        echo json_encode(array_slice($data, 0, 50), JSON_PRETTY_PRINT) . "\n\n";
    }
} else {
    echo "❌ Erreur HTTP $httpCode\n\n";
}

// Recommandations
echo "3️⃣  RECOMMANDATIONS\n";
echo str_repeat("-", 60) . "\n\n";

echo "Si Traffic Flow fonctionne mais pas les tuiles Traffic Maps:\n\n";

echo "Option A: Utiliser Flow Segment Data + Affichage personnalisé\n";
echo "  • Récupérer les données JSON avec l'API Flow\n";
echo "  • Créer une visualisation personnalisée avec Leaflet/Mapbox\n";
echo "  • Colorier les segments de route selon le traffic\n\n";

echo "Option B: Attendre l'activation de Traffic Maps\n";
echo "  • Contacter TomTom support\n";
echo "  • Demander l'activation de 'Traffic Maps Tile API'\n";
echo "  • Vérifier votre plan d'abonnement\n\n";

echo "Option C: Passer à une autre API\n";
echo "  • Google Maps Directions API (traffic inclus)\n";
echo "  • Mapbox Directions API\n";
echo "  • OpenWeather Traffic API\n\n";

echo "╚════════════════════════════════════════════════════════════╝\n\n";
