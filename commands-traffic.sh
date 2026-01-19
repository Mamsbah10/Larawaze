#!/bin/bash
# 📋 Commandes Utiles pour le Visualiseur de Trafic Abidjan

# ============ INSTALLATION & CONFIGURATION ============

echo "📦 Installer LaraWaze (première fois)"
composer install
npm install
php artisan key:generate
php artisan migrate

echo "🔑 Configurer TomTom API Key"
echo "TOMTOM_API_KEY=your_api_key_here" >> .env

echo "🚀 Compiler les assets"
npm run build

# ============ DÉVELOPPEMENT ============

echo "▶️ Démarrer serveur Laravel"
php artisan serve

echo "👀 Watcher assets (npm)"
npm run watch

echo "🗂️ Vérifier fichiers trafic"
ls -la public/js/TrafficFlowVisualizer.js
ls -la public/js/abidjan-locations.js
ls -la app/Services/TomTomService.php
ls -la app/Http/Controllers/TrafficController.php

# ============ TESTING ============

echo "🧪 Tester endpoint trafic"
curl "http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329"

echo "🧪 Test intégration page"
# Ouvrir dans navigateur:
# http://localhost:8000/map (intégration complète)
# http://localhost:8000/test-traffic-integration.html (test autonome)

echo "🔍 Vérifier intégration"
bash verify-traffic-integration.sh

# ============ DEBUGGING ============

echo "📊 Voir logs Laravel"
tail -f storage/logs/laravel.log

echo "🔧 Vider cache et configurer"
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "🗑️ Nettoyer storage"
rm -rf storage/logs/*
rm -rf storage/cache/*

# ============ DEPLOYMENT ============

echo "🚀 Préparer production"
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build

echo "✅ Vérifier endpoints"
php artisan route:list | grep traffic

echo "📝 Voir routes trafic"
php artisan route:list | grep -E "traffic|flow"

# ============ DATABASE ============

echo "🗄️ Reset DB et seed"
php artisan migrate:refresh --seed

echo "🗄️ Voir tables"
php artisan tinker
# Ensuite: DB::table('events')->count() etc

# ============ GIT ============

echo "📦 Commit intégration trafic"
git add .
git commit -m "🚀 feat: Intégration visualiseur trafic Abidjan

- Ajout TrafficFlowVisualizer.js pour visualisation temps réel
- Intégration avec TomTom Traffic Flow API
- 6 localités d'Abidjan pré-configurées
- UI complète dans panneau Filtres
- Documentation et tests autonomes"

git push origin main

# ============ MONITORING ============

echo "📊 Monitorer requêtes API"
php artisan tinker
# Lancer: Log::info('Test log')
# Voir: tail -f storage/logs/laravel.log

echo "🔔 Alertes trafic temps réel (WebSocket futur)"
# Configuration WebSocket sera ajoutée dans v1.1.0

# ============ MAINTENANCE ============

echo "🔄 Recharger trafic toutes les 30s (client)"
# JavaScript: setInterval(() => trafficViz.loadTraffic(...), 30000)

echo "⚙️ Optimiser requêtes (backend)"
# Cache les résultats 30s par localité
# Rate limit: 100 requêtes/min par IP

echo "📈 Metrics trafic"
# Voir TRAFFIC_INTEGRATION.md section Performance

# ============ SECURITY ============

echo "🔐 API Key en .env"
echo "TOMTOM_API_KEY=..." >> .env
echo ".env" >> .gitignore

echo "🔐 Validation paramètres"
# Vérifié dans TrafficController@getTrafficFlow

echo "🔐 CORS config"
# Pas nécessaire (requête backend via Laravel)

# ============ QUICK REFERENCE ============

cat << 'EOF'

📖 QUICK REFERENCE - Intégration Trafic Abidjan

🎯 Commandes principales:
  1. npm run watch              # Recompiler assets automatiquement
  2. php artisan serve          # Démarrer serveur (http://localhost:8000)
  3. curl "http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329"
  4. bash verify-traffic-integration.sh  # Vérifier intégration

📚 Fichiers clés:
  - public/js/TrafficFlowVisualizer.js    (classe visualisation)
  - resources/views/map.blade.php         (intégration UI)
  - app/Services/TomTomService.php        (API backend)
  - routes/api.php                         (endpoints)

🌍 Localités:
  - Plateau: 5.3391, -4.0329 (centre-ville)
  - Cocody: 5.3698, -4.0036 (nord-est)
  - Yopougon: 5.3451, -4.1093 (ouest)
  - Abobo: 5.4294, -4.0089 (nord)
  - Attécoubé: 5.3071, -4.0382 (sud)
  - Marcory: 5.3163, -4.0063 (sud-est)

🎨 Couleurs:
  - 🟢 VERT: > 80% vitesse normale (fluide)
  - 🟠 ORANGE: 50-80% vitesse normale (modéré)
  - 🔴 ROUGE: < 50% vitesse normale (sévère)

🔗 URLs importantes:
  - http://localhost:8000/map                        (app complète)
  - http://localhost:8000/test-traffic-integration.html (test)
  - http://localhost:8000/api/traffic/flow?...      (API)

📚 Documentation:
  - TRAFFIC_INTEGRATION.md                          (doc technique)
  - TRAFFIC_DEPLOYMENT_CHECKLIST.md                (checklist)
  - QUICKSTART_TRAFFIC.md                           (démarrage)

✅ Status: PRODUCTION READY

EOF

# ============ EXTRAS ============

echo "🎓 Apprendre la classe TrafficFlowVisualizer"
echo "Voir: public/js/TrafficFlowVisualizer.js"
echo "Exemples d'utilisation dans map.blade.php lignes 1590-1655"

echo "🎓 Apprendre l'API TomTom Traffic Flow"
echo "Docs: https://developer.tomtom.com/traffic-api/traffic-api-documentation"
echo "Endpoint: /traffic/services/4/flowSegmentData/absolute/10/json"

echo "🎓 Apprendre Leaflet.js"
echo "Docs: https://leafletjs.com/"
echo "L.polyline(), L.popup(), map.setView() etc"
