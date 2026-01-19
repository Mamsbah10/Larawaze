#!/usr/bin/env php
<?php

/**
 * Diagnostic avancé - Pourquoi TomTom retourne 404 pour TOUTES les tuiles?
 * Ce script teste directement l'endpoint TomTom pour identifier le problème réel
 */

require __DIR__ . '/vendor/autoload.php';

// Charger .env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║    🔍 DIAGNOSTIC AVANCÉ - Pourquoi TomTom Retourne 404   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Coordonnées à tester
$testCases = [
    ['name' => 'Paris', 'z' => 15, 'x' => 16408, 'y' => 10729],
    ['name' => 'New York', 'z' => 15, 'x' => 10486, 'y' => 12310],
    ['name' => 'Londres', 'z' => 15, 'x' => 16352, 'y' => 10743],
];

$apiKey = $_ENV['TOMTOM_API_KEY'] ?? null;
$baseUrl = 'https://api.tomtom.com';

if (!$apiKey) {
    echo "❌ ERREUR CRITIQUE: TOMTOM_API_KEY non trouvée!\n";
    echo "   Vérifiez votre fichier .env\n\n";
    exit(1);
}

echo "1️⃣  VÉRIFICATION DE BASE\n";
echo str_repeat("-", 60) . "\n";
echo "Clé API: " . substr($apiKey, 0, 10) . "***" . substr($apiKey, -4) . "\n";
echo "Base URL: $baseUrl\n";
echo "Endpoint: /traffic/map/4/flow/absolute/{z}/{x}/{y}.png\n\n";

echo "2️⃣  TEST DES TUILES\n";
echo str_repeat("-", 60) . "\n\n";

$results = [];

foreach ($testCases as $test) {
    $z = $test['z'];
    $x = $test['x'];
    $y = $test['y'];
    $name = $test['name'];
    
    $tileUrl = "{$baseUrl}/traffic/map/4/flow/absolute/{$z}/{$x}/{$y}.png?key={$apiKey}";
    
    echo "🧪 Test: $name\n";
    echo "   URL: " . str_replace($apiKey, '***', $tileUrl) . "\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $tileUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: LaraWaze/1.0',
            'Referer: http://127.0.0.1:8000'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    if ($errno) {
        echo "   ❌ Erreur CURL: " . curl_error($ch) . "\n\n";
        $results[] = ['name' => $name, 'status' => 'ERROR', 'code' => $errno];
    } else {
        echo "   Status HTTP: $httpCode\n";
        echo "   Content-Type: $contentType\n";
        
        if ($httpCode === 200) {
            echo "   ✅ SUCCÈS!\n";
            $results[] = ['name' => $name, 'status' => 'OK', 'code' => 200];
        } elseif ($httpCode === 401) {
            echo "   ❌ AUTHENTIFICATION ÉCHOUÉE\n";
            echo "   → La clé API est invalide ou expirée\n";
            $results[] = ['name' => $name, 'status' => 'AUTH_ERROR', 'code' => 401];
        } elseif ($httpCode === 404) {
            echo "   ⚠️  TUILE NON TROUVÉE\n";
            
            // Essayer de lire le body pour plus d'infos
            list($headers, $body) = explode("\r\n\r\n", $response, 2);
            if (!empty($body)) {
                echo "   Réponse: " . substr($body, 0, 100) . "\n";
            }
            
            $results[] = ['name' => $name, 'status' => 'NOT_FOUND', 'code' => 404];
        } else {
            echo "   ⚠️  CODE INATTENDU: $httpCode\n";
            $results[] = ['name' => $name, 'status' => 'UNKNOWN', 'code' => $httpCode];
        }
    }
    
    curl_close($ch);
    echo "\n";
}

// ============ ANALYSE ============
echo "3️⃣  ANALYSE DES RÉSULTATS\n";
echo str_repeat("-", 60) . "\n\n";

$successCount = count(array_filter($results, fn($r) => $r['status'] === 'OK'));
$authErrors = count(array_filter($results, fn($r) => $r['status'] === 'AUTH_ERROR'));
$notFound = count(array_filter($results, fn($r) => $r['status'] === 'NOT_FOUND'));

if ($authErrors > 0) {
    echo "❌ PROBLÈME D'AUTHENTIFICATION DÉTECTÉ!\n\n";
    echo "Les réponses 401 signifient que votre clé API TomTom:\n";
    echo "  1. Est expirée ou invalide\n";
    echo "  2. N'a pas les bonnes permissions\n";
    echo "  3. Est liée à un compte sans accès Traffic\n\n";
    
    echo "Solutions:\n";
    echo "  • Vérifiez votre clé sur https://developer.tomtom.com\n";
    echo "  • Vérifiez que votre plan inclut l'API Traffic Maps\n";
    echo "  • Générez une nouvelle clé si nécessaire\n";
    echo "  • Vérifiez que la clé dans .env est correcte (pas d'espaces)\n\n";
} elseif ($notFound === count($results)) {
    echo "⚠️  TOUTES LES TUILES RETOURNENT 404\n\n";
    echo "Possibilités:\n";
    echo "  1. Votre plan TomTom ne couvre pas les tuiles Traffic\n";
    echo "  2. Le format d'URL est incorrect\n";
    echo "  3. Le service Traffic n'est pas disponible pour votre région\n";
    echo "  4. Votre abonnement a expiré\n\n";
    
    echo "Vérifications à faire:\n";
    echo "  • Visitez: https://developer.tomtom.com/products\n";
    echo "  • Vérifiez que 'Traffic Maps' est dans vos services\n";
    echo "  • Vérifiez la date d'expiration de votre abonnement\n";
    echo "  • Testez avec un curl direct (voir ci-dessous)\n\n";
} elseif ($successCount > 0) {
    echo "✅ SUCCÈS! Certaines tuiles fonctionnent.\n";
    echo "   Les tuiles qui fonctionnent: " . implode(', ', 
        array_map(fn($r) => $r['name'], 
            array_filter($results, fn($r) => $r['status'] === 'OK')
        )
    ) . "\n\n";
}

// ============ TESTS SUPPLÉMENTAIRES ============
echo "4️⃣  TESTS SUPPLÉMENTAIRES\n";
echo str_repeat("-", 60) . "\n\n";

// Test 1: Vérifier si la clé est vraiment utilisée
echo "Test 1: Vérifier avec une clé invalide\n";
$badUrl = "{$baseUrl}/traffic/map/4/flow/absolute/15/16408/10729.png?key=INVALID_KEY_12345";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $badUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$badCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Avec clé invalide: HTTP $badCode\n";

if ($badCode === 401) {
    echo "   ✅ Le système valide les clés (bon signe)\n";
} elseif ($badCode === 404) {
    echo "   ⚠️  Le système accepte même les clés invalides (tuile non trouvée)\n";
} else {
    echo "   ? Réponse inattendue: $badCode\n";
}

echo "\n";

// Test 2: Essayer sans la clé
echo "Test 2: Vérifier sans clé API\n";
$noKeyUrl = "{$baseUrl}/traffic/map/4/flow/absolute/15/16408/10729.png";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $noKeyUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$noKeyCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Sans clé API: HTTP $noKeyCode\n";

if ($noKeyCode === 401) {
    echo "   ✅ La clé est bien requise\n";
} else {
    echo "   ? Réponse: $noKeyCode\n";
}

echo "\n";

// ============ COMMANDES À TESTER MANUELLEMENT ============
echo "5️⃣  COMMANDES À TESTER MANUELLEMENT\n";
echo str_repeat("-", 60) . "\n\n";

echo "Testez ce curl dans votre terminal:\n\n";
echo "curl -v 'https://api.tomtom.com/traffic/map/4/flow/absolute/15/16408/10729.png?key=" . substr($apiKey, 0, 10) . "...'\n\n";

echo "Ou avec votre clé complète:\n\n";
echo "curl -v 'https://api.tomtom.com/traffic/map/4/flow/absolute/15/16408/10729.png?key=$apiKey'\n\n";

echo "=================================\n\n";

echo "6️⃣  SUGGESTIONS FINALES\n";
echo str_repeat("-", 60) . "\n\n";

if ($authErrors > 0) {
    echo "🔴 ACTION URGENTE REQUISE:\n";
    echo "   Votre clé API TomTom ne fonctionne pas\n";
    echo "   → Allez à https://developer.tomtom.com/dashboard\n";
    echo "   → Vérifiez vos clés API\n";
    echo "   → Vérifiez votre abonnement\n";
} elseif ($notFound === count($results)) {
    echo "🟡 VÉRIFIER VOTRE ABONNEMENT:\n";
    echo "   Les tuiles Traffic ne sont pas disponibles\n";
    echo "   → Allez à https://developer.tomtom.com/products\n";
    echo "   → Activez 'Traffic Maps' si ce n'est pas fait\n";
    echo "   → Vérifiez votre plan d'abonnement\n";
} else {
    echo "🟢 PARTIELLEMENT FONCTIONNEL:\n";
    echo "   Certaines régions fonctionnent, d'autres non\n";
    echo "   → Vérifiez la couverture TomTom pour votre région\n";
}

echo "\n╚════════════════════════════════════════════════════════════╝\n\n";
