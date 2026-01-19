# 🚦 Configuration TomTom Traffic - LaraWaze

## ✅ Configuration effectuée

Votre clé API TomTom a été intégrée avec succès dans l'application LaraWaze !

### 📋 Fichiers modifiés/créés

#### 1. **Configuration (`.env`)**

-   ✅ Ajout de `TOMTOM_API_KEY=uYMyamzlK0GNtueiA9CfOJMIbAI22lRS`

#### 2. **Configuration Services (`config/services.php`)**

-   ✅ Ajout de la configuration TomTom :

```php
'tomtom' => [
    'api_key' => env('TOMTOM_API_KEY'),
    'base_url' => 'https://api.tomtom.com',
]
```

#### 3. **Classe Service (`app/Services/TomTomService.php`)**

-   ✅ Création d'un service Laravel pour gérer les appels API TomTom
-   Méthodes disponibles :
    -   `getApiKey()` - Récupère la clé API pour le frontend
    -   `getTrafficFlow()` - Récupère le flux de trafic
    -   `getRouteWithTraffic()` - Calcule un itinéraire avec trafic
    -   `getIncidents()` - Récupère les incidents (accidents, police, etc.)

#### 4. **Contrôleur API (`app/Http/Controllers/TrafficController.php`)**

-   ✅ Création du contrôleur pour les routes API
-   Endpoints disponibles :
    -   `GET /api/traffic/api-key` - Récupère la clé API
    -   `GET /api/traffic/flow` - Trafic à une localisation
    -   `GET /api/traffic/route` - Itinéraire avec trafic
    -   `GET /api/traffic/incidents` - Incidents de trafic

#### 5. **Routes API (`routes/api.php`)**

-   ✅ Création d'un nouveau fichier de routes API
-   Routes configurées pour les endpoints TomTom

#### 6. **Gestionnaire JavaScript (`resources/js/traffic.js`)**

-   ✅ Classe `TomTomTrafficManager` pour la gestion du trafic côté client
-   Fonctionnalités :
    -   Toggle de la couche de trafic
    -   Affichage du trafic en temps réel
    -   Récupération des informations de trafic via API
    -   Méthodes helpers exposées à `window` pour débogage

#### 7. **Intégration dans la carte (`resources/js/map.js`)**

-   ✅ Initialisation automatique du gestionnaire TomTom
-   Bouton 🛣️ pour activer/désactiver le trafic
-   Gestion des erreurs et logs détaillés

#### 8. **Vue (`resources/views/map.blade.php`)**

-   ✅ Chargement des fichiers JavaScript dans le bon ordre

#### 9. **Fichier de test (`public/test_tomtom.html`)**

-   ✅ Mise à jour avec votre clé API

## 🚀 Utilisation

### 1. **Compiler les assets (si nécessaire)**

```bash
npm run build
```

### 2. **Vérifier la configuration**

```bash
php artisan tinker
# Vérifier que la clé est chargée :
> config('services.tomtom.api_key')
```

### 3. **Utilisation sur la carte**

#### Activer/Désactiver le trafic

-   Cliquer sur le bouton 🛣️ dans la barre de contrôle
-   La couche de trafic s'ajoute/retire de la carte

#### Dans la console du navigateur (F12)

```javascript
// Afficher les informations de trafic à votre position
trafficShowInfo(5.348, -4.027);

// Activer le trafic
trafficEnable();

// Désactiver le trafic
trafficDisable();

// Toggler le trafic
trafficToggle();

// Accéder au gestionnaire directement
window.tomTomTrafficManager.getTrafficFlow(latitude, longitude);
```

## 📡 Endpoints API disponibles

### 1. **Récupérer la clé API**

```bash
GET /api/traffic/api-key
```

**Réponse :**

```json
{
    "api_key": "uYMyamzlK0GNtueiA9CfOJMIbAI22lRS",
    "success": true
}
```

### 2. **Récupérer le flux de trafic**

```bash
GET /api/traffic/flow?latitude=5.348&longitude=-4.027
```

**Réponse :** Information de trafic en temps réel (vitesse, densité, etc.)

### 3. **Calculer un itinéraire avec trafic**

```bash
GET /api/traffic/route?start_lat=5.348&start_lon=-4.027&end_lat=5.450&end_lon=-4.150
```

**Réponse :** Détails de l'itinéraire avec estimation du trafic

### 4. **Récupérer les incidents**

```bash
GET /api/traffic/incidents?latitude=5.348&longitude=-4.027&radius=5000
```

**Réponse :** Liste des incidents (accidents, police, etc.) dans le rayon spécifié

## 🔧 Configuration avancée

### Modifier les styles de la couche trafic

Éditez [resources/js/traffic.js](resources/js/traffic.js) et modifiez les options de `L.tileLayer` :

```javascript
this.trafficLayer = L.tileLayer(
    `${this.baseUrl}/traffic/map/4/flow/absolute/{z}/{x}/{y}.png?key=${this.apiKey}`,
    {
        attribution: "© TomTom",
        opacity: 0.7, // Modifier l'opacité ici
        maxZoom: 18,
        crossOrigin: true,
        tms: false,
        zIndex: 100,
    }
);
```

### Types de flux de trafic

TomTom propose plusieurs types de visualisation :

-   `flow/absolute` - Vitesse absolue (recommandé)
-   `flow/relative` - Vitesse relative (comparée aux conditions normales)
-   `incidents` - Afficher uniquement les incidents

## 🐛 Dépannage

### Bouton trafic n'apparaît pas

1. Vérifier la console (F12) pour les erreurs
2. Vérifier que la clé API est dans `.env`
3. Vérifier que la compilation des assets s'est bien faite : `npm run build`

### Couche trafic ne s'affiche pas

1. Vérifier que la clé API est valide
2. Vérifier les onglets Réseau (Network) du navigateur pour les appels à l'API TomTom
3. Vérifier que la route est au bon niveau de zoom (minimum 4)

### Erreur "Clé API non disponible"

1. Vérifier que `TOMTOM_API_KEY` est défini dans `.env`
2. Exécuter : `php artisan config:clear`
3. Recharger la page (Ctrl+Shift+R pour hard refresh)

## 📚 Documentation TomTom

-   [API Documentation](https://developer.tomtom.com/traffic-api/documentation)
-   [Traffic Flow API](https://developer.tomtom.com/traffic-api/traffic-flow)
-   [Traffic Incidents API](https://developer.tomtom.com/traffic-api/incidents)
-   [Routing API](https://developer.tomtom.com/routing-api/documentation)

## 🎯 Prochaines étapes (optionnel)

1. **Ajouter les incidents en markers** : Récupérer les incidents et les afficher comme des marqueurs sur la carte
2. **Intégration dans la recherche d'itinéraire** : Proposer l'itinéraire le plus rapide avec trafic
3. **Alertes trafic** : Notifier l'utilisateur quand un embouteillage est détecté
4. **Historique du trafic** : Stocker les données de trafic pour analyse

---

**Configuration effectuée le :** 7 janvier 2026  
**Clé API :** `uYMyamzlK0GNtueiA9CfOJMIbAI22lRS`  
**Status :** ✅ Prêt à l'emploi
