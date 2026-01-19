#!/usr/bin/env php
<?php

/**
 * Test avec Referer correct - Peut-être que TomTom bloque sans bon Referer
 */

require __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║    🔧 TEST AVEC HEADERS CORRECTS - Referer Required      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$apiKey = $_ENV['TOMTOM_API_KEY'] ?? null;

if (!$apiKey) {
    echo "❌ TOMTOM_API_KEY non trouvée\n";
    exit(1);
}

// Test avec différents Referer headers
$referers = [
    'http://localhost:8000' => 'Localhost',
    'http://127.0.0.1:8000' => 'Localhost IP',
    'https://your-domain.com' => 'Production domain',
    '' => 'Sans Referer',
];

echo "Testage de l'endpoint Traffic Flow avec différents Referers:\n\n";

foreach ($referers as $referer => $name) {
    echo "🧪 Test: $name\n";
    
    $url = "https://api.tomtom.com/traffic/services/4/flowSegmentData/absolute/10/json";
    $params = "?point=48.8566,2.3522&unit=KMPH&key=$apiKey";
    
    $ch = curl_init();
    
    $headers = [
        'User-Agent: LaraWaze/1.0',
    ];
    
    if (!empty($referer)) {
        $headers[] = "Referer: $referer";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Referer: '" . ($referer ?: 'NONE') . "'\n";
    echo "   Status: HTTP $httpCode\n";
    
    if ($httpCode === 200) {
        echo "   ✅ SUCCESS!\n";
        $data = json_decode($response, true);
        echo "   Réponse type: " . (isset($data['flowSegmentData']) ? 'Flow Data' : implode(', ', array_keys($data))) . "\n";
    } elseif ($httpCode === 403) {
        $data = json_decode($response, true);
        if (isset($data['detailedError'])) {
            echo "   ❌ Error: " . $data['detailedError']['message'] . "\n";
        } else {
            echo "   ❌ Forbidden (403)\n";
        }
    } else {
        echo "   ⚠️  HTTP $httpCode\n";
    }
    
    echo "\n";
}

echo "═════════════════════════════════════════════════════════════\n\n";

// Test direct des tuiles avec les mêmes headers
echo "🎯 AUSSI TESTER LES TUILES AVEC CES HEADERS:\n\n";

$tileUrl = "https://api.tomtom.com/traffic/map/4/flow/absolute/15/16408/10729.png?key=$apiKey";

foreach (['http://localhost:8000', 'http://127.0.0.1:8000', ''] as $referer) {
    echo "Test tuile avec Referer: '" . ($referer ?: 'NONE') . "'\n";
    
    $ch = curl_init();
    
    $headers = ['User-Agent: LaraWaze/1.0'];
    if (!empty($referer)) {
        $headers[] = "Referer: $referer";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $tileUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ HTTP 200 - Tuile trouvée!\n";
    } else {
        echo "HTTP $httpCode\n";
    }
    echo "\n";
}

echo "╚════════════════════════════════════════════════════════════╝\n\n";
