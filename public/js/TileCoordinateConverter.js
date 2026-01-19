/**
 * Utilitaire pour convertir les coordonnées lat/lon en z/x/y pour les tuiles Web Mercator
 * Utilisé pour déboguer les erreurs 404 des tuiles TomTom
 */

class TileCoordinateConverter {
    /**
     * Convertit des coordonnées lat/lon en indices de tuile Web Mercator
     * @param {number} latitude - Latitude (-90 à 90)
     * @param {number} longitude - Longitude (-180 à 180)
     * @param {number} zoom - Niveau de zoom (0-28)
     * @returns {Object} {z, x, y}
     */
    static latLonToTile(latitude, longitude, zoom) {
        const n = Math.pow(2, zoom);

        // Convertir en radians
        const lat_rad = (latitude * Math.PI) / 180;

        // Calculer x
        const x = Math.floor(((longitude + 180) / 360) * n);

        // Calculer y avec la projection Web Mercator
        const y = Math.floor(
            ((1 -
                Math.log(Math.tan(lat_rad) + 1 / Math.cos(lat_rad)) / Math.PI) /
                2) *
                n
        );

        return { z: zoom, x, y };
    }

    /**
     * Convertit des indices de tuile en coordonnées lat/lon (coin nord-ouest)
     * @param {number} x - Index X
     * @param {number} y - Index Y
     * @param {number} z - Zoom
     * @returns {Object} {latitude, longitude}
     */
    static tileToLatLon(x, y, z) {
        const n = Math.pow(2, z);

        const longitude = (x / n) * 360 - 180;

        const lat_rad = Math.atan(Math.sinh(Math.PI * (1 - (2 * y) / n)));
        const latitude = (lat_rad * 180) / Math.PI;

        return { latitude, longitude };
    }

    /**
     * Obtient le centre d'une tuile
     * @param {number} x - Index X
     * @param {number} y - Index Y
     * @param {number} z - Zoom
     * @returns {Object} {latitude, longitude}
     */
    static getTileCenter(x, y, z) {
        const topLeft = this.tileToLatLon(x, y, z);
        const bottomRight = this.tileToLatLon(x + 1, y + 1, z);

        return {
            latitude: (topLeft.latitude + bottomRight.latitude) / 2,
            longitude: (topLeft.longitude + bottomRight.longitude) / 2,
        };
    }

    /**
     * Teste une tuile en appelant l'API locale
     * @param {number} z - Zoom
     * @param {number} x - Index X
     * @param {number} y - Index Y
     * @returns {Promise<boolean>} true si la tuile est disponible
     */
    static async testTile(z, x, y) {
        try {
            const response = await fetch(`/api/traffic/tile/${z}/${x}/${y}`, {
                method: "GET",
            });
            return response.status === 200;
        } catch (error) {
            console.error("Erreur lors du test de la tuile:", error);
            return false;
        }
    }

    /**
     * Teste plusieurs tuiles autour d'une coordonnée
     * @param {number} latitude - Latitude
     * @param {number} longitude - Longitude
     * @param {number} zoom - Zoom
     * @returns {Promise<Array>} Tableau des tuiles valides
     */
    static async findValidTilesNearby(latitude, longitude, zoom) {
        const centerTile = this.latLonToTile(latitude, longitude, zoom);
        const validTiles = [];

        // Tester un carré 3x3 autour de la tuile centrale
        for (let dx = -1; dx <= 1; dx++) {
            for (let dy = -1; dy <= 1; dy++) {
                const x = centerTile.x + dx;
                const y = centerTile.y + dy;

                const isValid = await this.testTile(zoom, x, y);

                if (isValid) {
                    const center = this.getTileCenter(x, y, zoom);
                    validTiles.push({
                        z: zoom,
                        x,
                        y,
                        center,
                    });
                }
            }
        }

        return validTiles;
    }
}

// Exemples d'utilisation
console.log("=== EXEMPLES D'UTILISATION ===\n");

// Exemple 1: Paris
console.log("1. Convertir Paris (48.8566°N, 2.3522°E) au zoom 15:");
let tile = TileCoordinateConverter.latLonToTile(48.8566, 2.3522, 15);
console.log(`   Résultat: z=${tile.z}, x=${tile.x}, y=${tile.y}`);
console.log(
    `   URL: http://localhost:8000/api/traffic/tile/${tile.z}/${tile.x}/${tile.y}\n`
);

// Exemple 2: New York
console.log("2. Convertir New York (40.7128°N, -74.0060°E) au zoom 15:");
tile = TileCoordinateConverter.latLonToTile(40.7128, -74.006, 15);
console.log(`   Résultat: z=${tile.z}, x=${tile.x}, y=${tile.y}`);
console.log(
    `   URL: http://localhost:8000/api/traffic/tile/${tile.z}/${tile.x}/${tile.y}\n`
);

// Exemple 3: Tokyo
console.log("3. Convertir Tokyo (35.6762°N, 139.6503°E) au zoom 15:");
tile = TileCoordinateConverter.latLonToTile(35.6762, 139.6503, 15);
console.log(`   Résultat: z=${tile.z}, x=${tile.x}, y=${tile.y}`);
console.log(
    `   URL: http://localhost:8000/api/traffic/tile/${tile.z}/${tile.x}/${tile.y}\n`
);

// Exemple 4: Reconvertir une tuile en coordonnées
console.log("4. Reconvertir la tuile z=15, x=16408, y=10729:");
const coords = TileCoordinateConverter.getTileCenter(16408, 10729, 15);
console.log(
    `   Résultat: lat=${coords.latitude.toFixed(
        4
    )}, lon=${coords.longitude.toFixed(4)}`
);
console.log(`   (Ces coordonnées devraient être proches de Paris)\n`);

// Exemple 5: Tester les tuiles (utilisation avec DOM)
console.log("5. Dans le navigateur, vous pouvez:");
console.log(
    "   TileCoordinateConverter.findValidTilesNearby(48.8566, 2.3522, 15)"
);
console.log('   .then(tiles => console.log("Tuiles valides:", tiles));\n');

// Exporter pour utilisation en module
if (typeof module !== "undefined" && module.exports) {
    module.exports = TileCoordinateConverter;
}
