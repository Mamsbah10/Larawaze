# Système de Favoris et Historique de Recherche

## Vue d'ensemble

Un nouveau système complet de gestion des favoris et d'historique de recherche a été ajouté à l'application LaraWaze.

## Fonctionnalités

### 1. **Sidebar (Panneau Latéral)**

-   Un sidebar coulissant apparaît à droite de l'écran quand l'utilisateur clique sur le champ de recherche
-   Le sidebar affiche deux sections :
    -   **Favoris** : Les positions enregistrées (Maison, Travail, Autre)
    -   **Historique** : Les 15 dernières recherches effectuées

### 2. **Ajouter un Favori**

1. Cliquez sur le champ de recherche pour ouvrir le sidebar
2. Cliquez sur le bouton "+ Ajouter une position" dans la section Favoris
3. Une modal s'ouvre vous permettant de :
    - Entrer un nom (ex: "Mon Bureau", "Maison", etc.)
    - Sélectionner un type : 🏠 Maison, 🏢 Travail, ou 📍 Autre
    - Cliquer sur la carte pour définir les coordonnées (ou les coordonnées actuelles s'utilisent par défaut)
    - Cliquer "Enregistrer"

### 3. **Utiliser un Favori**

-   Cliquez sur un favori dans la liste pour naviguer vers cet endroit
-   La destination est définie et la navigation commence automatiquement

### 4. **Historique de Recherche**

-   Chaque recherche effectuée est automatiquement sauvegardée dans l'historique
-   Les 15 dernières recherches s'affichent dans le sidebar
-   Cliquez sur un élément de l'historique pour répéter la navigation

### 5. **Supprimer un Favori ou une Entrée d'Historique**

-   Cliquez sur le bouton "✕" à côté de chaque élément pour le supprimer

### 6. **Effacer tout l'Historique**

-   Cliquez sur le bouton "Effacer tout" dans la section Historique pour vider tout l'historique de recherche

## Base de Données

Deux nouvelles tables ont été créées :

### Table `favorites`

```sql
id              INTEGER PRIMARY KEY
user_id         INTEGER (clé étrangère vers users)
name            VARCHAR(100)
type            VARCHAR(20) - 'home', 'work', 'other'
latitude        DECIMAL(10,8)
longitude       DECIMAL(11,8)
address         TEXT (optionnel)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Table `search_histories`

```sql
id              INTEGER PRIMARY KEY
user_id         INTEGER (clé étrangère vers users)
query           TEXT
latitude        DECIMAL(10,8) (optionnel)
longitude       DECIMAL(11,8) (optionnel)
address         TEXT (optionnel)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

## API Endpoints

Tous les endpoints sont accessibles sous `/api/` et nécessitent l'authentification :

### GET `/api/sidebar`

Récupère tous les favoris et les 15 derniers éléments de l'historique de l'utilisateur.

**Réponse:**

```json
{
    "favorites": [
        {
            "id": 1,
            "name": "Maison",
            "type": "home",
            "latitude": 6.8276,
            "longitude": -5.2893,
            "address": "Abidjan, Côte d'Ivoire"
        }
    ],
    "history": [
        {
            "id": 1,
            "query": "Marché d'Adjamé",
            "latitude": 6.8376,
            "longitude": -5.2793,
            "address": "Marché d'Adjamé, Abidjan",
            "created_at": "2025-12-11T14:30:00Z"
        }
    ]
}
```

### POST `/api/favorites`

Crée ou met à jour un favori.

**Payload:**

```json
{
    "name": "Mon Bureau",
    "type": "work",
    "latitude": 6.8276,
    "longitude": -5.2893,
    "address": "Rue Duplessis, Abidjan"
}
```

### DELETE `/api/favorites/{id}`

Supprime un favori par ID.

### POST `/api/search-history`

Ajoute une entrée à l'historique de recherche.

**Payload:**

```json
{
    "query": "Aéroport d'Abidjan",
    "latitude": 5.2614,
    "longitude": -3.8778,
    "address": "Aéroport Félix Houphouët-Boigny"
}
```

### DELETE `/api/search-history/{id}`

Supprime une entrée spécifique de l'historique.

### DELETE `/api/search-history`

Efface tout l'historique de l'utilisateur.

## Architecture

### Frontend

-   **Fichier:** `resources/views/map.blade.php`
-   **Composants:**
    -   Sidebar div (#favorites-sidebar) - panneau coulissant fixe
    -   Modal de favori (#addFavoriteModal) - formulaire d'ajout
    -   Listes dynamiques (#favorites-list, #history-list)
    -   CSS animations pour slide-in du sidebar
    -   JavaScript pour gestion des événements et appels API

### Backend

-   **Modèles:** `app/Models/Favorite.php`, `app/Models/SearchHistory.php`
-   **Contrôleur:** `app/Http/Controllers/FavoriteController.php`
-   **Routes:** Incluses dans `routes/web.php` sous le préfixe `/api/`
-   **Migrations:** `database/migrations/2025_12_11_create_*_tables.php`

### Middleware

-   Authentification via `ensure.auth` middleware
-   Vérification de propriété pour DELETE (seul le propriétaire peut supprimer)

## Support du Mode Sombre

Le sidebar supporte automatiquement le mode sombre :

-   Couleurs adaptées en mode nuit
-   Transitions fluides entre les modes
-   Cohérent avec le thème général de l'application

## Notes de Développement

-   Le système fonctionne hors-ligne une fois les données synchronisées avec le serveur
-   Les favoris et l'historique sont stockés côté serveur (par utilisateur)
-   Le sidebar se ferme automatiquement après sélection d'une destination
-   Les coordonnées GPS actuelles s'utilisent par défaut pour les nouveaux favoris
-   Validation complète côté serveur pour tous les inputs
