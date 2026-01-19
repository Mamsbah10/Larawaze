/**
 * TrafficFlowVisualizer - Affichage du trafic en temps réel sur Leaflet
 * Utilise l'API TomTom Traffic Flow pour visualiser la congestion routière
 */

class TrafficFlowVisualizer {
    constructor(map) {
        this.map = map;
        this.trafficLayers = [];
        this.isLoading = false;
    }

    /**
     * Charge et affiche le trafic pour une localité donnée
     * @param {number} latitude - Latitude de la localité
     * @param {number} longitude - Longitude de la localité
     * @param {Function} onLoadingChange - Callback pour l'état de chargement
     */
    async loadTraffic(latitude, longitude, onLoadingChange = null) {
        if (this.isLoading) return;

        this.isLoading = true;
        if (onLoadingChange) onLoadingChange(true);

        try {
            const response = await fetch(
                `/api/traffic/flow?latitude=${latitude}&longitude=${longitude}`
            );
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.clear();

            if (
                data &&
                data.flowSegmentData &&
                Array.isArray(data.flowSegmentData)
            ) {
                console.log(
                    `📍 Chargement ${data.flowSegmentData.length} segments de trafic`
                );

                // Grouper les segments par couleur pour un rendu optimisé
                const segmentsByColor = {};

                data.flowSegmentData.forEach((segment) => {
                    const color = this.getColorBySpeed(
                        segment.currentSpeed,
                        segment.freeFlowSpeed
                    );

                    if (!segmentsByColor[color]) {
                        segmentsByColor[color] = [];
                    }
                    segmentsByColor[color].push(segment);
                });

                // Ajouter les segments groupés par couleur
                Object.entries(segmentsByColor).forEach(([color, segments]) => {
                    segments.forEach((segment) => {
                        this.addTrafficSegment(segment, color);
                    });
                });
            }
        } catch (error) {
            console.error("Erreur chargement trafic:", error);
        } finally {
            this.isLoading = false;
            if (onLoadingChange) onLoadingChange(false);
        }
    }

    /**
     * Ajouter un segment de trafic à la carte
     * @param {Object} flowData - Données du segment (currentSpeed, freeFlowSpeed, coordinates, etc.)
     * @param {string} color - Couleur du segment (#hex)
     */
    addTrafficSegment(flowData, color) {
        let coordinates = [];

        // Gérer les deux formats possibles de coordonnées
        if (flowData.coordinates) {
            if (Array.isArray(flowData.coordinates)) {
                // Format: coordinates = [[lat, lon], [lat, lon], ...]
                coordinates = flowData.coordinates;
            } else if (
                flowData.coordinates.coordinate &&
                Array.isArray(flowData.coordinates.coordinate)
            ) {
                // Format: coordinates.coordinate = [[lat, lon], [lat, lon], ...]
                coordinates = flowData.coordinates.coordinate;
            }
        }

        if (!coordinates || coordinates.length === 0) {
            console.warn("Pas de coordonnées pour le segment:", flowData);
            return;
        }

        // Créer la polyline avec un style professionnel (type Google Maps/Waze)
        const polyline = L.polyline(coordinates, {
            color: color,
            weight: 8, // Augmenté à 8 pour meilleure visibilité
            opacity: 1.0, // Opacité complète pour meilleur contraste
            lineCap: "round",
            lineJoin: "round",
            dashArray: null, // Pas de tirets - ligne continue
            className: "traffic-segment",
        });

        // Ajouter une couche d'ombre sous la ligne pour meilleur contraste (style Google Maps)
        const shadowPolyline = L.polyline(coordinates, {
            color: "rgba(0, 0, 0, 0.2)",
            weight: 11, // Plus épais que la ligne principale
            opacity: 0.6,
            lineCap: "round",
            lineJoin: "round",
            dashArray: null,
            className: "traffic-shadow",
        });

        // Ajouter les couches dans le bon ordre (ombre en dessous)
        shadowPolyline.addTo(this.map);
        this.trafficLayers.push(shadowPolyline);

        // Ajouter un popup avec les infos de trafic
        const congestion = Math.round(
            (1 - flowData.currentSpeed / flowData.freeFlowSpeed) * 100
        );
        const popupContent = `
            <div style="font-size: 0.9rem; line-height: 1.5;">
                <strong>Vitesse actuelle:</strong> ${flowData.currentSpeed} km/h<br>
                <strong>Vitesse normale:</strong> ${flowData.freeFlowSpeed} km/h<br>
                <strong>Congestion:</strong> ${congestion}%<br>
                <strong>Temps actuel:</strong> ${flowData.currentTravelTime} min<br>
                <strong>Temps normal:</strong> ${flowData.freeFlowTravelTime} min
            </div>
        `;
        polyline.bindPopup(popupContent);

        // Ajouter à la carte et à la liste
        polyline.addTo(this.map);
        this.trafficLayers.push(polyline);
    }

    /**
     * Détermine la couleur basée sur le ratio vitesse/vitesse libre
     * @param {number} currentSpeed - Vitesse actuelle
     * @param {number} freeFlowSpeed - Vitesse libre
     * @returns {string} Couleur en format #hex
     */
    getColorBySpeed(currentSpeed, freeFlowSpeed) {
        if (!freeFlowSpeed || freeFlowSpeed === 0) {
            return "#FFA500"; // Orange par défaut
        }

        const ratio = currentSpeed / freeFlowSpeed;

        if (ratio > 0.8) {
            return "#00AA00"; // Vert - trafic fluide (>80%)
        } else if (ratio > 0.5) {
            return "#FFA500"; // Orange - congestion modérée (50-80%)
        } else {
            return "#FF0000"; // Rouge - congestion importante (<50%)
        }
    }

    /**
     * Efface tous les segments de trafic
     */
    clear() {
        this.trafficLayers.forEach((layer) => {
            this.map.removeLayer(layer);
        });
        this.trafficLayers = [];
    }

    /**
     * Récupérer la couleur basée sur la congestion
     * (Deprecated - use getColorBySpeed instead)
     * @param {number} currentSpeed - Vitesse actuelle
     * @param {number} freeFlowSpeed - Vitesse libre
     * @returns {string} Code couleur hex
     */
    getColorBySpeed_old(currentSpeed, freeFlowSpeed) {
        const ratio = currentSpeed / freeFlowSpeed;

        // Vert: > 80% de la vitesse libre
        if (ratio > 0.8) return "#4CAF50";
        // Orange: 50-80%
        if (ratio > 0.5) return "#FF9800";
        // Rouge: < 50%
        return "#f44336";
    }

    /**
     * Afficher les données depuis l'API (Deprecated - use loadTraffic instead)
     * @param {float} latitude
     * @param {float} longitude
     */
    async fetchAndDisplay(latitude, longitude) {
        try {
            const response = await fetch(
                `/api/traffic/flow?latitude=${latitude}&longitude=${longitude}`
            );
            const data = await response.json();

            if (data.flowSegmentData) {
                // Handle both single segment and array
                const segments = Array.isArray(data.flowSegmentData)
                    ? data.flowSegmentData
                    : [data.flowSegmentData];

                segments.forEach((segment) => {
                    this.addTrafficSegment(segment);
                });
                return data;
            } else {
                console.error("No flow data:", data);
            }
        } catch (error) {
            console.error("Error fetching traffic data:", error);
        }
    }
}
