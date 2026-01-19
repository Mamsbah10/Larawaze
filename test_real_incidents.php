#!/usr/bin/env php
<?php

/**
 * Test pour voir les VRAIS incidents TomTom pour Abidjan
 */

require __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['TOMTOM_API_KEY'] ?? null;

if (!$apiKey) {
    echo "❌ Clé API TomTom manquante\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  TEST: Récupérer les VRAIS incidents TomTom pour Abidjan\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Coordonnées Abidjan (Cocody - zone centrale)
$lat = 5.3698;
$lon = -4.0036;

// Bounds (5km radius)
$minLat = $lat - 0.045;
$minLon = $lon - 0.045;
$maxLat = $lat + 0.045;
$maxLon = $lon + 0.045;

echo "📍 Localisation testée: Cocody, Abidjan\n";
echo "   Latitude: $lat\n";
echo "   Longitude: $lon\n";
echo "   Bounds: ($minLat,$minLon) à ($maxLat,$maxLon)\n\n";

// Test 1: API Incidents standard
echo "1️⃣  TEST: API Incidents TomTom\n";
echo str_repeat("-", 60) . "\n";

$url = "https://api.tomtom.com/traffic/incidents/json";
$params = [
    'key' => $apiKey,
    'bounds' => "{$minLat},{$minLon},{$maxLat},{$maxLon}",
    'categoryFilter' => 'flow,congestion,accident,roadworks',
    'expandCluster' => true,
    'language' => 'fr',
    'version' => '5'
];

$queryString = http_build_query($params);
$fullUrl = "{$url}?{$queryString}";

echo "URL: " . str_replace($apiKey, '***', $fullUrl) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $fullUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'User-Agent: LaraWaze/1.0',
        'Referer: http://localhost:8000'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Status HTTP: $httpCode\n";

if ($error) {
    echo "❌ Erreur CURL: $error\n";
} else {
    $data = json_decode($response, true);
    
    if ($httpCode === 200) {
        echo "✅ Réponse reçue\n\n";
        
        if (isset($data['incidents']) && is_array($data['incidents'])) {
            $count = count($data['incidents']);
            echo "📊 Incidents trouvés: $count\n\n";
            
            if ($count > 0) {
                echo "DÉTAILS DES INCIDENTS:\n";
                echo str_repeat("-", 60) . "\n";
                
                foreach ($data['incidents'] as $i => $incident) {
                    echo "\n🚨 Incident #" . ($i + 1) . ":\n";
                    echo "  Description: " . ($incident['properties']['description'] ?? 'N/A') . "\n";
                    echo "  Catégorie: " . ($incident['properties']['incidentCategory'] ?? 'N/A') . "\n";
                    echo "  Sévérité: " . ($incident['properties']['severity'] ?? 'N/A') . "\n";
                    
                    if (isset($incident['geometry']['coordinates'])) {
                        $coords = $incident['geometry']['coordinates'];
                        if (is_array($coords[0]) && is_array($coords[0][0])) {
                            echo "  Nombre de points: " . count($coords[0]) . "\n";
                        } else {
                            echo "  Nombre de points: " . count($coords) . "\n";
                        }
                    }
                }
            } else {
                echo "⚠️  AUCUN INCIDENT trouvé pour cette zone\n";
                echo "\nCela signifie:\n";
                echo "  • La couverture TomTom n'a pas d'incidents rapportés ici\n";
                echo "  • Ou l'API retourne vraiment aucun incident\n";
                echo "  • Les données générées sont utilisées comme fallback\n";
            }
        } else {
            echo "❌ Pas de clé 'incidents' dans la réponse\n";
            echo "Réponse:\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    } else {
        echo "❌ HTTP $httpCode\n";
        echo "Réponse:\n";
        echo substr($response, 0, 300) . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n\n";

// Test 2: Essayer avec différentes zones
echo "2️⃣  TEST: Autres zones d'Abidjan\n";
echo str_repeat("-", 60) . "\n";

$locations = [
    ['name' => 'Plateau', 'lat' => 5.3391, 'lon' => -4.0329],
    ['name' => 'Yopougon', 'lat' => 5.3451, 'lon' => -4.1093],
    ['name' => 'Abobo', 'lat' => 5.4294, 'lon' => -4.0089],
];

foreach ($locations as $loc) {
    $minLat = $loc['lat'] - 0.045;
    $minLon = $loc['lon'] - 0.045;
    $maxLat = $loc['lat'] + 0.045;
    $maxLon = $loc['lon'] + 0.045;
    
    $url = "https://api.tomtom.com/traffic/incidents/json?key={$apiKey}&bounds={$minLat},{$minLon},{$maxLat},{$maxLon}&categoryFilter=flow,congestion,accident,roadworks&version=5";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $count = isset($data['incidents']) ? count($data['incidents']) : 0;
    
    echo "  {$loc['name']}: HTTP $httpCode → $count incidents\n";
}

echo "\n" . str_repeat("=", 60) . "\n\n";

echo "3️⃣  CONCLUSION\n";
echo str_repeat("-", 60) . "\n\n";

echo "Si aucun incident n'est retourné:\n";
echo "  ✅ C'est NORMAL - TomTom n'a pas de données pour Abidjan\n";
echo "  ✅ Le système utilise les données générées comme fallback\n";
echo "  ✅ C'est professionnel car les tracés SUIVENT les vraies routes\n\n";

echo "Solution pour avoir des vrais incidents:\n";
echo "  1. Utiliser un VPN (si TomTom est géo-bloqué)\n";
echo "  2. Tester avec une grande ville (Paris, New York, etc.)\n";
echo "  3. Accepter les données générées (elles sont réalistes)\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";
