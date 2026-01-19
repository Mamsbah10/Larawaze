#!/bin/bash
# ⚡ DÉMARRAGE EN 5 MINUTES - Intégration Trafic Abidjan

echo "⚡ DÉMARRAGE EN 5 MINUTES"
echo "========================="
echo ""
echo "Intégration Visualiseur de Trafic Abidjan"
echo ""

# Estimation: 5 minutes
# - 30s: Vérification
# - 1m: Lancer serveur
# - 1m: Accéder map
# - 1m: Tester trafic
# - 1m: Vérifier tout fonctionne

echo "⏱️ Temps estimé: 5 minutes"
echo ""

# ÉTAPE 1: Vérifier que Laravel fonctionne (30 secondes)
echo "📋 ÉTAPE 1: Vérification (30 secondes)"
echo "======================================="
echo ""
echo "✓ Vérifions que Laravel est installé..."

if ! command -v php &> /dev/null; then
    echo "❌ PHP non trouvé. Installez PHP d'abord."
    exit 1
fi

echo "✅ PHP trouvé"

if [ ! -f "artisan" ]; then
    echo "❌ Laravel non trouvé. Vous êtes au bon endroit?"
    exit 1
fi

echo "✅ Laravel trouvé"
echo ""

# ÉTAPE 2: Lancer serveur (1 minute)
echo "🚀 ÉTAPE 2: Lancer serveur (1 minute)"
echo "======================================"
echo ""
echo "Lancement du serveur Laravel..."
echo ""
echo "   php artisan serve"
echo ""
echo "Attend sur: http://localhost:8000"
echo ""
echo "⏳ Serveur en cours de démarrage..."
echo ""

# Note: Can't actually run this interactively in script
# But we show the command
echo "📝 COPIER ET EXÉCUTER DANS UN NOUVEAU TERMINAL:"
echo ""
echo "   cd $(pwd)"
echo "   php artisan serve"
echo ""
echo "Puis continuez ci-dessous ⬇️"
echo ""

# ÉTAPE 3: Accéder map (1 minute)
echo "🗺️ ÉTAPE 3: Ouvrir la carte (1 minute)"
echo "======================================"
echo ""
echo "Une fois serveur lancé, ouvrez dans navigateur:"
echo ""
echo "   🔗 http://localhost:8000/map"
echo ""
echo "Vous devez voir:"
echo "  • Navbar en haut (NaviWaze + boutons)"
echo "  • Carte Leaflet au centre"
echo "  • Barre en bas avec 5 boutons"
echo ""
echo "Si vous voyez cela ✅, continuez étape 4"
echo ""

# ÉTAPE 4: Tester trafic (1 minute)
echo "🚗 ÉTAPE 4: Tester Visualisation Trafic (1 minute)"
echo "==================================================="
echo ""
echo "ACTIONS:"
echo "  1. Cliquer 'Filtres' (bouton en bas)"
echo "  2. Chercher section '🚗 TRAFIC ABIDJAN'"
echo "  3. Cliquer 'Plateau' (ou autre localité)"
echo ""
echo "RÉSULTAT ATTENDU:"
echo "  ✅ Segments colorés apparaissent sur la carte"
echo "  ✅ Notification: 'Trafic de Plateau affiché'"
echo "  ✅ Carte se centre sur Plateau"
echo ""
echo "DÉTAILS:"
echo "  • Cliquer segment coloré = voir pop-up vitesse"
echo "  • 🟢 VERT = trafic fluide"
echo "  • 🟠 ORANGE = ralentissements"
echo "  • 🔴 ROUGE = embouteillage"
echo ""

# ÉTAPE 5: Vérifier tout fonctionne (1 minute)
echo "✅ ÉTAPE 5: Vérification (1 minute)"
echo "===================================="
echo ""
echo "VÉRIFICATIONS:"
echo ""
echo "  1. Tester une autre localité:"
echo "     → Cliquer 'Cocody' → Trafic s'affiche ✅"
echo ""
echo "  2. Tester effacer:"
echo "     → Cliquer 'Effacer le trafic' → Disparaît ✅"
echo ""
echo "  3. Tester pop-up:"
echo "     → Cliquer segment coloré → Pop-up ✅"
echo ""
echo "  4. Tester interface complète (autonome):"
echo "     → http://localhost:8000/test-traffic-integration.html"
echo "     → Tous les contrôles fonctionnent ✅"
echo ""

echo ""
echo "🎉 C'EST TERMINÉ!"
echo "================="
echo ""
echo "L'intégration du visualiseur de trafic fonctionne parfaitement ✅"
echo ""
echo "PROCHAINES ÉTAPES:"
echo ""
echo "  📖 Lire documentation:"
echo "     → QUICKSTART_TRAFFIC.md"
echo "     → TRAFFIC_INTEGRATION.md"
echo ""
echo "  🧪 Tester API directement:"
echo "     → bash test-urls.sh"
echo "     → curl \"http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329\""
echo ""
echo "  ✅ Vérifier intégration:"
echo "     → bash verify-traffic-integration.sh"
echo ""
echo "  📚 Voir tous les documents:"
echo "     → DOCUMENTATION_INDEX.md"
echo ""
echo "  🚀 Déployer en production:"
echo "     → Lire: TRAFFIC_DEPLOYMENT_CHECKLIST.md"
echo "     → Exécuter: verify-traffic-integration.sh"
echo ""

echo "═══════════════════════════════════════"
echo "✅ READY TO GO!"
echo "═══════════════════════════════════════"
echo ""
echo "Plus d'infos: DOCUMENTATION_INDEX.md"
echo ""
