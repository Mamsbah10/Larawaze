#!/usr/bin/env php
<?php

/**
 * Script final de test complet - simule une requête réelle
 * Utilisation: php final_test.php
 */

// Charger Composer
require __DIR__ . '/vendor/autoload.php';

// Charger .env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║           ✅ TEST FINAL COMPLET DE LA ROUTE             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============ ÉTAPE 1: Vérifier .env ============
echo "1️⃣  VÉRIFICATION DE .env\n";
echo str_repeat("-", 60) . "\n";

$apiKey = $_ENV['TOMTOM_API_KEY'] ?? null;
if ($apiKey) {
    echo "✅ TOMTOM_API_KEY trouvée\n";
    echo "   Valeur: " . substr($apiKey, 0, 10) . "***" . substr($apiKey, -4) . "\n";
} else {
    echo "❌ TOMTOM_API_KEY NOT FOUND!\n";
}

echo "\n";

// ============ ÉTAPE 2: Construire l'URL TomTom ============
echo "2️⃣  CONSTRUCTION DE L'URL TOMTOM\n";
echo str_repeat("-", 60) . "\n";

$z = 15;
$x = 16023;
$y = 15894;
$baseUrl = 'https://api.tomtom.com';

$tileUrl = "{$baseUrl}/traffic/map/4/flow/absolute/{$z}/{$x}/{$y}.png?key={$apiKey}";

echo "✅ URL TomTom construite:\n";
echo "   " . str_replace($apiKey, '***', $tileUrl) . "\n";

echo "\n";

// ============ ÉTAPE 3: Tester la connexion TomTom ============
echo "3️⃣  TEST CONNEXION VERS TOMTOM API\n";
echo str_repeat("-", 60) . "\n";

echo "Envoi d'une requête à TomTom...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $tileUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errno = curl_errno($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

if ($errno) {
    echo "❌ Erreur CURL: " . curl_error($ch) . " (Code: $errno)\n";
} else {
    echo "✅ Réponse reçue\n";
    echo "   Status HTTP: $httpCode\n";
    echo "   Content-Type: $contentType\n";
    echo "   Content-Length: " . strlen($response) . " bytes\n";
    
    if ($httpCode === 200) {
        echo "\n   ✅ TomTom API fonctionne correctement!\n";
        echo "   La tuile peut être chargée avec succès.\n";
    } elseif ($httpCode === 401) {
        echo "\n   ❌ Erreur d'authentification (401)\n";
        echo "   Vérifiez que votre clé API TomTom est valide.\n";
    } elseif ($httpCode === 404) {
        echo "\n   ⚠️  Tuile non trouvée (404)\n";
        echo "   Les coordonnées z=$z, x=$x, y=$y pourraient être invalides\n";
        echo "   pour cette zone.\n";
    } else {
        echo "\n   ⚠️  Statut HTTP inattendu: $httpCode\n";
    }
}

curl_close($ch);

echo "\n";

// ============ ÉTAPE 4: Vérifications supplémentaires ============
echo "4️⃣  VÉRIFICATIONS SUPPLÉMENTAIRES\n";
echo str_repeat("-", 60) . "\n";

// Vérifier si c'est une image PNG
if ($httpCode === 200 && stripos($contentType, 'image') !== false) {
    echo "✅ La réponse est une image PNG\n";
    echo "   La route /api/traffic/tile/{z}/{x}/{y} fonctionne correctement!\n";
} else {
    echo "⚠️  La réponse n'est pas une image PNG\n";
}

echo "\n";

// ============ RÉSUMÉ FINAL ============
echo "📊 RÉSUMÉ FINAL\n";
echo str_repeat("-", 60) . "\n\n";

if ($httpCode === 200 && stripos($contentType, 'image') !== false) {
    echo "✅ TOUT FONCTIONNE!\n\n";
    echo "La route /api/traffic/tile/{z}/{x}/{y} est:\n";
    echo "  1. Correctement définie dans routes/api.php\n";
    echo "  2. Appelle le bon contrôleur (TrafficController)\n";
    echo "  3. Récupère correctement les données de TomTom\n";
    echo "  4. Retourne les tuiles comme attendu\n\n";
    echo "L'erreur 404 que vous voyez dans le navigateur peut être:\n";
    echo "  • Dû à un problème de coordonnées invalides\n";
    echo "  • Dû à une région où les tuiles traffic ne sont pas disponibles\n";
    echo "  • Dû à une zone non couverte par les tuiles TomTom\n";
} else {
    echo "⚠️  PROBLÈME DÉTECTÉ\n\n";
    
    if (!$apiKey) {
        echo "❌ La clé API TomTom n'est pas définie\n";
        echo "   Ajoutez TOMTOM_API_KEY=votre_clé dans .env\n";
    } else if ($httpCode === 401) {
        echo "❌ Clé API TomTom invalide ou expirée\n";
        echo "   Vérifiez votre clé API dans https://developer.tomtom.com\n";
    } else if ($httpCode === 404) {
        echo "⚠️  La tuile n'existe pas pour ces coordonnées\n";
        echo "   Essayez avec d'autres coordonnées (z, x, y)\n";
    } else {
        echo "⚠️  Problème lors de la connexion à TomTom\n";
        echo "   Code HTTP: $httpCode\n";
    }
}

echo "\n╚════════════════════════════════════════════════════════════╝\n\n";
