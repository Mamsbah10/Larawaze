# 🚨 Guide de Dépannage - Intégration Trafic Abidjan

## ❓ Problème: Aucun trafic n'affiche

### Symptômes

-   Boutons localités cliquables mais rien ne s'affiche
-   Pas d'erreur console
-   Carte reste vide

### Diagnostic

**Étape 1: Vérifier la réponse API**

```bash
curl "http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329"
```

Attendez: `HTTP 200` avec réponse JSON contenant `"flowSegmentData": [`

**Étape 2: Vérifier console navigateur**

```javascript
F12 → Console
// Doit voir: "✅ TrafficFlowVisualizer initialisé pour Abidjan"
```

**Étape 3: Vérifier Network**

```
F12 → Network tab
Cliquer "Plateau" button
Voir requête: /api/traffic/flow?latitude=5.3391...
Status: 200
Response: JSON data
```

### Solutions

#### Solution 1: Vérifier TomTom API Key

```env
# Dans .env
TOMTOM_API_KEY=your_actual_key_here
```

Pas la bonne clé?

-   Aller à: https://developer.tomtom.com/
-   Créer/copier clé API
-   Mettre à jour .env

#### Solution 2: Vérifier Header Referer

```php
// app/Services/TomTomService.php ligne 50
'Referer' => 'http://localhost:8000'  // ✅ Doit être localhost
```

Pas `127.0.0.1` car TomTom le rejette!

#### Solution 3: Vérifier route API

```bash
php artisan route:list | grep traffic
```

Doit afficher: `GET api/traffic/flow`

#### Solution 4: Recharger la page

```javascript
// Dans console:
location.reload();
```

---

## ❌ Erreur: 404 Not Found

### Symptômes

```
GET /api/traffic/flow?latitude=...
404 Not Found
```

### Solutions

**Étape 1: Vérifier fichiers existent**

```bash
ls -la app/Http/Controllers/TrafficController.php
ls -la app/Services/TomTomService.php
```

**Étape 2: Vérifier route**

```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep traffic
```

**Étape 3: Vérifier namespace**

```php
// routes/api.php doit avoir:
use App\Http\Controllers\TrafficController;

Route::prefix('traffic')->group(function () {
    Route::get('/flow', [TrafficController::class, 'getTrafficFlow']);
});
```

**Étape 4: Redémarrer serveur**

```bash
# CTRL+C pour arrêter
php artisan serve
```

---

## 🔴 Erreur: 500 Internal Server Error

### Symptômes

```
GET /api/traffic/flow?latitude=...
500 Internal Server Error
```

### Solutions

**Étape 1: Vérifier logs**

```bash
tail -f storage/logs/laravel.log
```

Chercher erreur dans les dernières lignes.

**Étape 2: Erreur commune - Missing API Key**

```
undefined variable TOMTOM_API_KEY
```

**Solution**:

```env
# .env
TOMTOM_API_KEY=your_key_here
```

**Étape 3: Erreur - Port déjà utilisé**

```
Address already in use
```

**Solution**:

```bash
php artisan serve --port=8001
```

**Étape 4: Erreur - Permissions fichiers**

```
Permission denied storage/logs
```

**Solution**:

```bash
chmod -R 775 storage
```

---

## 🟡 Avertissement: TrafficFlowVisualizer not defined

### Symptômes

```
Uncaught ReferenceError: TrafficFlowVisualizer is not defined
```

### Solutions

**Étape 1: Vérifier import script**

```html
<!-- resources/views/map.blade.php ligne 1517 -->
<script src="/js/TrafficFlowVisualizer.js"></script>
```

**Étape 2: Vérifier fichier existe**

```bash
ls -la public/js/TrafficFlowVisualizer.js
```

Doit être ~110 lignes.

**Étape 3: Vérifier ordre scripts**

```html
<!-- Bon ordre: -->
1.
<script src="/vendor/leaflet/leaflet.js"></script>
2.
<script src="/js/TrafficFlowVisualizer.js"></script>
3. @vite(['resources/js/map.js'])
```

**Étape 4: Force reload page**

```javascript
CTRL+SHIFT+R  (ou CMD+SHIFT+R sur Mac)
// Force reload cache navigateur
```

---

## 🟠 Problème: Pop-ups ne s'affichent pas

### Symptômes

-   Segments de trafic visibles
-   Cliquer segment → Rien

### Solutions

**Étape 1: Vérifier événement click**

```javascript
// Console:
map.on("click", (e) => console.log("click", e));
// Cliquer segment: doit voir "click Object" dans console
```

**Étape 2: Vérifier Leaflet chargé**

```javascript
// Console:
console.log(L); // Doit afficher objet Leaflet
```

**Étape 3: Vérifier popupContent**

```javascript
// resources/views/map.blade.php ligne ~1620
const popupContent = `...`; // Doit être string
polyline.bindPopup(popupContent);
```

---

## 🔵 Problème: Carte ne centre pas sur localité

### Symptômes

-   Trafic affiche
-   Carte ne bouge pas quand cliquer localité

### Solutions

**Étape 1: Vérifier map instance**

```javascript
// Console:
console.log(map);
// Doit afficher objet Leaflet map
```

**Étape 2: Vérifier setView**

```javascript
// Console:
map.setView([5.3391, -4.0329], 13); // Doit bouger
```

**Étape 3: Vérifier loadTrafficForLocation**

```javascript
// resources/views/map.blade.php ligne ~1595
if (typeof map !== "undefined" && map) {
    map.setView([lat, lon], 13);
}
```

---

## 🟣 Problème: Légende couleurs incorrecte

### Symptômes

-   Segments toujours verts
-   Ou toujours rouges
-   Pas de variation

### Solutions

**Étape 1: Vérifier formule couleur**

```javascript
// public/js/TrafficFlowVisualizer.js ligne ~75
const ratio = currentSpeed / freeFlowSpeed;

if (ratio > 0.8)
    // ✅ > 80%
    return "#00AA00"; // VERT
else if (ratio > 0.5)
    // ✅ 50-80%
    return "#FFA500"; // ORANGE
// ✅ < 50%
else return "#FF0000"; // ROUGE
```

**Étape 2: Vérifier données API**

```bash
curl "http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329" | jq '.flowSegmentData[0]'
```

Chercher: `"currentSpeed"` et `"freeFlowSpeed"`

**Étape 3: Tester couleur manuellement**

```javascript
// Console:
const viz = new TrafficFlowVisualizer(map);
console.log(viz.getColorBySpeed(90, 90)); // ✅ #00AA00 (vert)
console.log(viz.getColorBySpeed(60, 90)); // ✅ #FFA500 (orange)
console.log(viz.getColorBySpeed(30, 90)); // ✅ #FF0000 (rouge)
```

---

## 📱 Problème: Interface cassée sur mobile

### Symptômes

-   Boutons mal positionnés
-   Texte coupé
-   Non responsive

### Solutions

**Étape 1: Vérifier viewport meta**

```html
<!-- resources/views/map.blade.php -->
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
```

**Étape 2: Tester avec F12 Device Toggle**

```
F12 → Toggle device toolbar (CTRL+SHIFT+M)
Voir si interface s'adapte
```

**Étape 3: Vérifier media queries**

```css
/* map.blade.php ligne ~1470 */
@media (max-width: 768px) {
    /* CSS responsive */
}
```

**Étape 4: Vérifier bootstrap**

```html
<!-- Doit être présent: -->
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
/>
```

---

## 🔒 Problème: API Key exposée/compromise

### Symptômes

-   API key visible dans code source
-   Requêtes rejetées par TomTom

### Solutions

**Étape 1: Mettre à jour .env**

```env
TOMTOM_API_KEY=new_key_here
```

**Étape 2: Vérifier pas exposée**

```bash
grep -r "TOMTOM_API_KEY=" public/  # Doit être VIDE
grep -r "your_key" app/            # Doit être VIDE
```

**Étape 3: Vérifier frontend**

```javascript
// Console:
console.log(window.TOMTOM_API_KEY);
// Doit être undefined (pas exposée)
```

**Étape 4: Regénérer clé**

-   Aller: https://developer.tomtom.com/
-   Générer nouvelle clé
-   Vieux token est annulé

---

## 🌐 Problème: Erreur réseau/CORS

### Symptômes

```
Access to XMLHttpRequest blocked by CORS policy
```

### Solutions

**Étape 1: Vérifier requête est du backend**

```
✅ Requête doit passer par Laravel (/api/traffic/flow)
❌ Pas directement à TomTom depuis navigateur
```

**Étape 2: Vérifier URL API**

```javascript
// Doit être: (localhost)
fetch("/api/traffic/flow?latitude=..."); // ✅

// PAS:
fetch("https://api.tomtom.com/..."); // ❌
```

---

## 💾 Problème: Cache

### Symptômes

-   Changes ne s'affichent pas
-   Ancienne version du code

### Solutions

**Étape 1: Nettoyer cache Laravel**

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Étape 2: Nettoyer cache navigateur**

```
CTRL+SHIFT+DEL (ou CMD+SHIFT+DEL sur Mac)
Supprimer tout → Reload page
```

**Étape 3: Recompiler assets**

```bash
npm run dev
```

---

## ⚡ Problème: Performance lente

### Symptômes

-   API répond lentement
-   Segments prennent du temps à s'afficher

### Solutions

**Étape 1: Vérifier temps réponse API**

```bash
# Avec timing:
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329
```

TomTom peut prendre 500-1000ms.

**Étape 2: Vérifier segments pas trop nombreux**

```javascript
// Console:
console.log(trafficVizInstance.trafficLayers.length);
// Si > 500: peut ralentir navigateur
```

**Étape 3: Optimiser requêtes**

-   Ne pas faire requêtes en boucle rapide
-   Attendre 30s entre recharges
-   Utiliser cache si possible

---

## 🆘 Si Tout Échoue

### Plan B: Déboguer pas à pas

**1. Tester API directement**

```bash
curl "http://localhost:8000/api/traffic/flow?latitude=5.3391&longitude=-4.0329" | jq
```

Doit retourner JSON valide.

**2. Ouvrir page test autonome**

```
http://localhost:8000/test-traffic-integration.html
```

Si fonctionne: problème dans intégration map.blade.php
Si ne fonctionne pas: problème dans TrafficFlowVisualizer.js

**3. Vérifier console navigateur**

```javascript
F12 → Console
window.trafficVizInstance
// Doit exister et avoir méthodes
```

**4. Voir tous les logs**

```bash
# Terminal:
tail -f storage/logs/laravel.log

# Navigateur F12:
F12 → Console (voir tous les logs)
F12 → Network (voir requêtes)
```

---

## 📞 Besoin d'aide supplémentaire?

1. **Vérifier documentation**: `TRAFFIC_INTEGRATION.md`
2. **Lire checklist**: `TRAFFIC_DEPLOYMENT_CHECKLIST.md`
3. **Tester page autonome**: `test-traffic-integration.html`
4. **Voir logs**: `storage/logs/laravel.log`
5. **Consulter TomTom docs**: https://developer.tomtom.com/

---

**Dernière mise à jour**: 2024
**Version**: 1.0.0
