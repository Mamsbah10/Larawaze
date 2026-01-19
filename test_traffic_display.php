#!/usr/bin/env php
<?php

/**
 * Test pour voir pourquoi le trafic n'affiche rien sur la carte
 */

require __DIR__ . '/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Charger Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  TEST: Pourquoi le trafic n'affiche rien?\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Test 1: Vérifier l'API directement
echo "1️⃣ TEST DE L'API TRAFFIC/FLOW\n";
echo str_repeat("-", 60) . "\n";

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Test Cocody (Abidjan)
$request = \Illuminate\Http\Request::create(
    '/api/traffic/flow?latitude=5.3698&longitude=-4.0036',
    'GET'
);

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content-Type: " . $response->headers->get('Content-Type') . "\n\n";
    
    if (isset($data['flowSegmentData'])) {
        echo "✅ flowSegmentData trouvé\n";
        $segments = is_array($data['flowSegmentData']) 
            ? $data['flowSegmentData'] 
            : [$data['flowSegmentData']];
        
        echo "Nombre de segments: " . count($segments) . "\n\n";
        
        foreach ($segments as $i => $seg) {
            echo "Segment $i:\n";
            echo "  - currentSpeed: " . ($seg['currentSpeed'] ?? 'N/A') . "\n";
            echo "  - freeFlowSpeed: " . ($seg['freeFlowSpeed'] ?? 'N/A') . "\n";
            echo "  - coordinates type: " . gettype($seg['coordinates'] ?? null) . "\n";
            
            if (isset($seg['coordinates'])) {
                if (is_array($seg['coordinates'])) {
                    if (count($seg['coordinates']) > 0) {
                        echo "  - coordinates[0]: " . json_encode($seg['coordinates'][0]) . "\n";
                        echo "  - coordinates count: " . count($seg['coordinates']) . "\n";
                    }
                } else if (is_object($seg['coordinates'])) {
                    echo "  - coordinates (object): " . json_encode($seg['coordinates']) . "\n";
                }
            }
            echo "\n";
            
            if ($i >= 2) {
                echo "... (affichage limité à 3 segments)\n";
                break;
            }
        }
    } else {
        echo "❌ Aucun flowSegmentData\n";
        echo "Données retournées:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 2: Vérifier le format des coordonnées
echo "2️⃣ ANALYSE DU FORMAT DES COORDONNÉES\n";
echo str_repeat("-", 60) . "\n";

echo "Le JavaScript s'attend à:\n";
echo '  flowData.coordinates = [[lat, lon], [lat, lon], ...]' . "\n";
echo "  OU\n";
echo '  flowData.coordinates = {coordinate: [{lat, lon}, ...]}' . "\n\n";

echo "Les bonnes couleurs pour le rendu:\n";
echo "  - #00AA00 (vert) si ratio > 0.8\n";
echo "  - #FFA500 (orange) si ratio 0.5-0.8\n";
echo "  - #FF0000 (rouge) si ratio < 0.5\n\n";

// Test 3: Vérifier les logs
echo "3️⃣ VÉRIFIER LES LOGS LARAVEL\n";
echo str_repeat("-", 60) . "\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "Dernier log traffic:\n\n";
    $lines = explode("\n", file_get_contents($logFile));
    $trafficLines = array_filter($lines, fn($l) => stripos($l, 'traffic') !== false);
    $trafficLines = array_slice($trafficLines, -10);
    
    foreach ($trafficLines as $line) {
        if (trim($line)) {
            echo $line . "\n";
        }
    }
} else {
    echo "Pas de fichier log\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  PROCHAINES ÉTAPES:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "1. Allez à http://localhost:8000/map\n";
echo "2. Ouvrez la console (F12)\n";
echo "3. Cliquez sur 🛣️ pour activer le trafic\n";
echo "4. Attendez quelques secondes\n";
echo "5. Regardez les messages de la console\n";
echo "6. Vérifiez que les polylines s'affichent\n\n";

echo "Si rien n'apparaît:\n";
echo "- Vérifiez l'onglet 'Network' pour les erreurs API\n";
echo "- Vérifiez la console pour les erreurs JavaScript\n";
echo "- Signalez ce problème avec une capture d'écran\n\n";
