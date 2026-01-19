#!/bin/bash
# 🚀 Script de validation rapide de l'intégration trafic
# Vérifie que tous les fichiers sont en place et fonctionnels

set -e

echo "🔍 Vérification intégration trafic Abidjan..."
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
CHECKS_PASSED=0
CHECKS_FAILED=0

# Fonction pour vérifier fichier
check_file() {
    local file=$1
    local name=$2
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅${NC} $name"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}❌${NC} $name - Fichier non trouvé: $file"
        ((CHECKS_FAILED++))
    fi
}

# Fonction pour vérifier contenu
check_content() {
    local file=$1
    local pattern=$2
    local name=$3
    
    if grep -q "$pattern" "$file" 2>/dev/null; then
        echo -e "${GREEN}✅${NC} $name"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}❌${NC} $name - Pattern non trouvé"
        ((CHECKS_FAILED++))
    fi
}

echo "📂 Vérification des fichiers..."
check_file "public/js/TrafficFlowVisualizer.js" "TrafficFlowVisualizer.js"
check_file "public/js/abidjan-locations.js" "abidjan-locations.js"
check_file "public/test-traffic-integration.html" "test-traffic-integration.html"
check_file "app/Services/TomTomService.php" "TomTomService.php"
check_file "app/Http/Controllers/TrafficController.php" "TrafficController.php"
check_file "resources/views/map.blade.php" "map.blade.php"
echo ""

echo "🔍 Vérification des contenus critiques..."
check_content "public/js/TrafficFlowVisualizer.js" "class TrafficFlowVisualizer" "Classe TrafficFlowVisualizer"
check_content "public/js/TrafficFlowVisualizer.js" "loadTraffic" "Méthode loadTraffic"
check_content "public/js/TrafficFlowVisualizer.js" "getColorBySpeed" "Méthode getColorBySpeed"
check_content "app/Services/TomTomService.php" "http://localhost:8000" "Header Referer correct"
check_content "routes/api.php" "Route::get\('/flow'" "Route /api/traffic/flow"
check_content "resources/views/map.blade.php" "TrafficFlowVisualizer.js" "Import script Vue"
check_content "resources/views/map.blade.php" "loadTrafficForLocation" "Fonction loadTrafficForLocation"
check_content "resources/views/map.blade.php" "clearTraffic" "Fonction clearTraffic"
echo ""

echo "📍 Vérification localités Abidjan..."
check_content "resources/views/map.blade.php" "5.3391" "Plateau coordonnées"
check_content "resources/views/map.blade.php" "5.3698" "Cocody coordonnées"
check_content "resources/views/map.blade.php" "5.3451" "Yopougon coordonnées"
check_content "resources/views/map.blade.php" "5.4294" "Abobo coordonnées"
check_content "resources/views/map.blade.php" "5.3071" "Attécoubé coordonnées"
check_content "resources/views/map.blade.php" "5.3163" "Marcory coordonnées"
echo ""

echo "📊 Résultats:"
echo -e "${GREEN}✅ Vérifications réussies: $CHECKS_PASSED${NC}"
if [ $CHECKS_FAILED -gt 0 ]; then
    echo -e "${RED}❌ Vérifications échouées: $CHECKS_FAILED${NC}"
    exit 1
else
    echo -e "${GREEN}🎉 Intégration trafic prête pour production!${NC}"
    exit 0
fi
