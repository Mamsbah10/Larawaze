// =========================================================================
// GESTION DU TRAFIC TOMTOM
// =========================================================================

class TomTomTrafficManager {
    constructor(map) {
        this.map = map;
        this.apiKey = null;
        this.trafficLayer = null;
        this.trafficEnabled = false;
        this.autoRefreshEnabled = false;
        this.autoRefreshSeconds = 30; // default interval
        this.autoRefreshIntervalId = null;
        this.baseUrl = "https://api.tomtom.com";

        this.init();
    }

    /**
     * Initialize TomTom Traffic Manager
     */
    async init() {
        try {
            // Récupérer la clé API depuis le serveur
            const response = await fetch("/api/traffic/api-key");
            const data = await response.json();

            if (data.api_key) {
                this.apiKey = data.api_key;
                console.log("✅ TomTom API Key loaded successfully");
                this.createTrafficButton();
            } else {
                console.error("❌ Failed to load TomTom API Key");
            }
        } catch (error) {
            console.error("❌ Error initializing TomTom:", error);
        }
    }

    /**
     * Create traffic toggle button
     */
    createTrafficButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById("trafficToggleBtn")) {
            return;
        }

        const trafficBtn = document.createElement("button");
        trafficBtn.id = "trafficToggleBtn";
        trafficBtn.className = "btn btn-info btn-sm me-2";
        trafficBtn.innerHTML = "🛣️";
        trafficBtn.title = "Activer/Désactiver le trafic";
        trafficBtn.onclick = () => this.toggleTraffic();

        const autoRefreshBtn = document.createElement("button");
        autoRefreshBtn.id = "autoRefreshBtn";
        autoRefreshBtn.className = "btn btn-outline-info btn-sm me-2";
        autoRefreshBtn.innerHTML = "🔄 Auto";
        autoRefreshBtn.title = "Rafraîchir auto (toutes les 30s)";
        autoRefreshBtn.onclick = () => this.toggleAutoRefresh(30);

        // Ajouter les boutons à la navbar
        const navbarDiv = document.querySelector(".navbar-top div");
        if (navbarDiv) {
            navbarDiv.appendChild(trafficBtn);
            navbarDiv.appendChild(autoRefreshBtn);
        } else {
            // Fallback: ajouter au body
            document.body.appendChild(trafficBtn);
            document.body.appendChild(autoRefreshBtn);
        }
    }

    /**
     * Toggle traffic layer visibility
     */
    toggleTraffic() {
        if (!this.apiKey) {
            alert("❌ Clé API TomTom non disponible");
            console.error("TomTom API Key is not available");
            return;
        }

        if (!this.trafficEnabled) {
            this.enableTraffic();
        } else {
            this.disableTraffic();
        }
    }

    /**
     * Enable traffic layer
     */
    enableTraffic() {
        try {
            if (!this.trafficLayer) {
                // Use traffic flow API instead of tile layer
                // Create a FeatureGroup to hold traffic segments
                this.trafficLayer = L.featureGroup();

                // Load traffic for current view bounds
                this.loadTrafficForView();

                // Reload traffic when map moves/zooms
                this.map.on("moveend zoomend", () => this.loadTrafficForView());
            }

            this.trafficLayer.addTo(this.map);
            this.trafficEnabled = true;

            const btn = document.getElementById("trafficToggleBtn");
            if (btn) btn.classList.add("active");

            console.log("✅ Traffic layer enabled");
        } catch (error) {
            console.error("❌ Error enabling traffic layer:", error);
            alert("❌ Erreur lors de l'activation du trafic");
        }
    }

    /**
     * Load traffic for current map view
     */
    async loadTrafficForView() {
        if (!this.trafficEnabled) return;

        try {
            const center = this.map.getCenter();
            const traffic = await this.getTrafficFlow(center.lat, center.lng);

            if (traffic && traffic.flowSegmentData) {
                this.trafficLayer.clearLayers();

                const segments = Array.isArray(traffic.flowSegmentData)
                    ? traffic.flowSegmentData
                    : [traffic.flowSegmentData];

                segments.forEach((segment) => {
                    this.addTrafficSegmentToMap(segment);
                });

                console.log(
                    `📊 ${segments.length} segments de trafic affichés`
                );

                // Update info panel
                this.updateInfoPanel(segments);
            }
        } catch (error) {
            console.error("❌ Error loading traffic for view:", error);
        }
    }

    /**
     * Calculate traffic statistics
     * @param {Array} segments - Traffic segments
     * @returns {Object} Statistics
     */
    calculateTrafficStats(segments) {
        if (!segments || segments.length === 0) {
            return {
                totalSegments: 0,
                avgSpeed: 0,
                avgCongestion: 0,
                avgTravelTime: 0,
                worstCongestion: 0,
                bestCongestion: 100,
            };
        }

        const speeds = segments.map((s) => s.currentSpeed || 0);
        const congestions = segments.map((s) =>
            Math.max(
                0,
                100 * (1 - (s.currentSpeed || 0) / (s.freeFlowSpeed || 1))
            )
        );
        const times = segments.map((s) => s.currentTravelTime || 0);

        return {
            totalSegments: segments.length,
            avgSpeed: Math.round(
                speeds.reduce((a, b) => a + b, 0) / speeds.length
            ),
            avgCongestion: Math.round(
                congestions.reduce((a, b) => a + b, 0) / congestions.length
            ),
            avgTravelTime: Math.round(
                times.reduce((a, b) => a + b, 0) / times.length
            ),
            worstCongestion: Math.round(Math.max(...congestions)),
            bestCongestion: Math.round(Math.min(...congestions)),
            roads: segments.map((s) => s.name || "Route").slice(0, 3),
        };
    }

    /**
     * Update info panel with traffic statistics
     * @param {Array} segments - Traffic segments
     */
    updateInfoPanel(segments) {
        const panelDiv = document.getElementById("trafficInfoPanel");
        if (!panelDiv) return;

        const stats = this.calculateTrafficStats(segments);

        let congestionColor = "#00AA00"; // Green
        if (stats.avgCongestion > 50) congestionColor = "#FF0000"; // Red
        else if (stats.avgCongestion > 25) congestionColor = "#FFA500"; // Orange

        const roadsHtml =
            stats.roads.length > 0
                ? stats.roads.map((r) => `<li>${r}</li>`).join("")
                : "<li>-</li>";

        panelDiv.innerHTML = `
            <div style="padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h5 style="margin: 0 0 12px 0; color: #333;">📊 Trafic en Direct</h5>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
                        <strong style="color: #666; font-size: 0.85rem;">Segments</strong><br>
                        <span style="font-size: 1.3rem; color: #007bff;">${
                            stats.totalSegments
                        }</span>
                    </div>
                    <div style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
                        <strong style="color: #666; font-size: 0.85rem;">Vitesse moy.</strong><br>
                        <span style="font-size: 1.3rem; color: #28a745;">${
                            stats.avgSpeed
                        } km/h</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
                        <strong style="color: #666; font-size: 0.85rem;">Congestion</strong><br>
                        <span style="font-size: 1.3rem; color: ${congestionColor};">${
            stats.avgCongestion
        }%</span>
                    </div>
                    <div style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
                        <strong style="color: #666; font-size: 0.85rem;">Temps moyen</strong><br>
                        <span style="font-size: 1.3rem; color: #ffc107;">${
                            stats.avgTravelTime
                        } min</span>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <strong style="color: #666; font-size: 0.9rem;">Routes:</strong>
                    <ul style="margin: 8px 0 0 0; padding: 0 0 0 20px; font-size: 0.85rem;">
                        ${roadsHtml}
                    </ul>
                </div>

                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.8rem; color: #999;">
                    ⏰ ${new Date().toLocaleTimeString("fr-FR")}
                </div>
            </div>
        `;
    }

    /**
     * Add traffic segment to map
     */
    addTrafficSegmentToMap(flowData) {
        if (!flowData.coordinates) return;

        const coordinates = Array.isArray(flowData.coordinates)
            ? flowData.coordinates
            : [flowData.coordinates];

        const color = this.getColorBySpeed(
            flowData.currentSpeed || 0,
            flowData.freeFlowSpeed || 1
        );

        const polyline = L.polyline(coordinates, {
            color: color,
            weight: 4,
            opacity: 0.8,
            lineCap: "round",
            lineJoin: "round",
        });

        const speedPercent = Math.round(
            ((flowData.currentSpeed || 0) / (flowData.freeFlowSpeed || 1)) * 100
        );

        const popupContent = `
            <div style="font-size: 0.9rem; line-height: 1.5;">
                <strong>Vitesse actuelle:</strong> ${
                    flowData.currentSpeed || 0
                } km/h<br>
                <strong>Vitesse normale:</strong> ${
                    flowData.freeFlowSpeed || 0
                } km/h<br>
                <strong>Congestion:</strong> ${Math.max(
                    0,
                    100 - speedPercent
                )}%<br>
                <strong>Temps actuel:</strong> ${
                    flowData.currentTravelTime || 0
                } min<br>
                <strong>Temps normal:</strong> ${
                    flowData.freeFlowTravelTime || 0
                } min
            </div>
        `;

        polyline.bindPopup(popupContent);
        this.trafficLayer.addLayer(polyline);
    }

    /**
     * Get color based on traffic speed ratio
     * @param {number} currentSpeed - Current speed in km/h
     * @param {number} freeFlowSpeed - Free flow speed in km/h
     * @returns {string} Hex color code
     */
    getColorBySpeed(currentSpeed, freeFlowSpeed) {
        if (!freeFlowSpeed || freeFlowSpeed === 0) {
            return "#FFA500"; // Orange by default
        }

        const ratio = currentSpeed / freeFlowSpeed;

        if (ratio > 0.8) {
            return "#00AA00"; // Green - fluid traffic (>80%)
        } else if (ratio > 0.5) {
            return "#FFA500"; // Orange - moderate congestion (50-80%)
        } else {
            return "#FF0000"; // Red - severe congestion (<50%)
        }
    }

    /**
     * Start automatic traffic refresh at specified interval
     * @param {number} seconds - Refresh interval in seconds (default 30)
     */
    startAutoRefresh(seconds = 30) {
        if (this.autoRefreshIntervalId) {
            console.warn("⚠️  Auto-refresh already running");
            return;
        }

        this.autoRefreshSeconds = seconds;
        this.autoRefreshEnabled = true;

        console.log(`🔄 Starting auto-refresh every ${seconds} seconds`);

        // Initial load
        this.loadTrafficForView();

        // Set interval
        this.autoRefreshIntervalId = setInterval(() => {
            if (this.trafficEnabled) {
                this.loadTrafficForView();
            }
        }, seconds * 1000);

        // Update UI
        const btn = document.getElementById("autoRefreshBtn");
        if (btn) btn.classList.add("active");
    }

    /**
     * Stop automatic traffic refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshIntervalId) {
            clearInterval(this.autoRefreshIntervalId);
            this.autoRefreshIntervalId = null;
            this.autoRefreshEnabled = false;

            console.log("⏹️  Auto-refresh stopped");

            // Update UI
            const btn = document.getElementById("autoRefreshBtn");
            if (btn) btn.classList.remove("active");
        }
    }

    /**
     * Toggle automatic traffic refresh
     * @param {number} seconds - Refresh interval in seconds
     */
    toggleAutoRefresh(seconds = 30) {
        if (this.autoRefreshEnabled) {
            this.stopAutoRefresh();
        } else {
            this.startAutoRefresh(seconds);
        }
    }

    /**
     * Disable traffic layer
     */
    disableTraffic() {
        try {
            // Stop auto-refresh first
            this.stopAutoRefresh();

            if (this.trafficLayer) {
                this.map.removeLayer(this.trafficLayer);
                this.map.off("moveend zoomend");
            }

            this.trafficEnabled = false;

            const btn = document.getElementById("trafficToggleBtn");
            if (btn) btn.classList.remove("active");

            console.log("✅ Traffic layer disabled");
        } catch (error) {
            console.error("❌ Error disabling traffic layer:", error);
        }
    }

    /**
     * Get traffic flow information
     */
    async getTrafficFlow(latitude, longitude) {
        try {
            const response = await fetch(
                `/api/traffic/flow?latitude=${latitude}&longitude=${longitude}`
            );
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("❌ Error fetching traffic flow:", error);
            return null;
        }
    }

    /**
     * Get route with traffic information
     */
    async getRouteWithTraffic(startLat, startLon, endLat, endLon) {
        try {
            const response = await fetch(
                `/api/traffic/route?start_lat=${startLat}&start_lon=${startLon}&end_lat=${endLat}&end_lon=${endLon}`
            );
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("❌ Error fetching route:", error);
            return null;
        }
    }

    /**
     * Get traffic incidents (accidents, police, etc.)
     */
    async getIncidents(latitude, longitude, radius = 5000) {
        try {
            const response = await fetch(
                `/api/traffic/incidents?latitude=${latitude}&longitude=${longitude}&radius=${radius}`
            );
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("❌ Error fetching incidents:", error);
            return null;
        }
    }

    /**
     * Display traffic flow in console
     */
    async showTrafficInfo(latitude, longitude) {
        console.log(
            `📊 Fetching traffic info for ${latitude}, ${longitude}...`
        );
        const traffic = await this.getTrafficFlow(latitude, longitude);
        if (traffic) {
            console.log("📍 Traffic Info:", traffic);
            return traffic;
        }
    }

    /**
     * Expose to window for console debugging
     */
    static exposeToWindow() {
        if (window.tomTomTrafficManager) {
            window.trafficShowInfo = (lat, lon) =>
                window.tomTomTrafficManager.showTrafficInfo(lat, lon);
            window.trafficToggle = () =>
                window.tomTomTrafficManager.toggleTraffic();
            window.trafficEnable = () =>
                window.tomTomTrafficManager.enableTraffic();
            window.trafficDisable = () =>
                window.tomTomTrafficManager.disableTraffic();
            window.trafficAutoRefresh = (seconds = 30) =>
                window.tomTomTrafficManager.toggleAutoRefresh(seconds);
            window.trafficStartRefresh = (seconds = 30) =>
                window.tomTomTrafficManager.startAutoRefresh(seconds);
            window.trafficStopRefresh = () =>
                window.tomTomTrafficManager.stopAutoRefresh();
        }
    }
}

// Exporter pour utilisation dans map.js
window.TomTomTrafficManager = TomTomTrafficManager;
