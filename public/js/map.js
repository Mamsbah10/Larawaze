// =========================================================================
// CONFIGURATION ET INITIALISATION
// =========================================================================
const currentUserId = window.currentUserId || null;
let csrf; // Sera initialisée dans DOMContentLoaded

// Notifications & Audio (initialisées dans DOMContentLoaded)
let notifBox, notifBadge, notifAudio;

// Carte et données (initialisées dans DOMContentLoaded)
let map, lightLayer, darkLayer;

let userLat = null,
    userLon = null;
let markers = [],
    eventsData = [],
    sharedMarker = null;
let lastMaxEventId = 0,
    notifCount = 0;
let filters = {
    embouteillage: true,
    accident: true,
    police: true,
    danger: true,
};
let currentEventType = null;
// Cache pour reverse-geocoding (évite d'appeler l'API trop souvent)
let lastGeocode = { lat: null, lon: null, label: null, ts: 0 };

// Expose aux fenêtre globale pour la console et debugging
window.userLat = userLat;
window.userLon = userLon;
window.currentUserId = currentUserId;

// NOUVEAU: MODE DE TRANSPORT PAR DÉFAUT
let travelMode = "driving"; // 'driving', 'bike', 'foot'

// Helper pour synchroniser les coordonnées avec window (pour la console)
function updateGlobalCoords(lat, lon) {
    userLat = lat;
    userLon = lon;
    window.userLat = lat;
    window.userLon = lon;
}

// Expose helper to global window so it can be called from console/devtools
try {
    window.updateGlobalCoords = updateGlobalCoords;
} catch (e) {}

// Corrige les chemins d'icônes Leaflet pour la version publique/fallback
try {
    if (typeof L !== "undefined" && L.Icon && L.Icon.Default) {
        L.Icon.Default.mergeOptions({
            iconUrl: "/icons/depart.png",
            iconRetinaUrl: "/icons/depart.png",
            // avoid data URI here to prevent malformed concatenation
            shadowUrl: "/icons/depart.png",
        });
        // prevent Leaflet from prepending its detected vendor path
        L.Icon.Default.imagePath = "";
    }
} catch (e) {
    console.debug("Leaflet icon override failed (public):", e);
}
// =========================================================================
// GESTION DU SIDEBAR DROIT (25% DE L'ÉCRAN)
// =========================================================================
function openSidebar(title, content) {
    const sidebarTitle = document.getElementById("sidebarTitle");
    const sidebarContent = document.getElementById("sidebarContent");

    if (sidebarTitle) sidebarTitle.innerHTML = title;
    if (sidebarContent) sidebarContent.innerHTML = content;

    // Animer l'apparition si le sidebar existe
    const sidebar = document.getElementById("right-sidebar");
    if (sidebar) sidebar.style.animation = "slideInRight 0.3s ease-in-out";
}

function closeSidebar() {
    const sidebar = document.getElementById("right-sidebar");
    const sidebarContent = document.getElementById("sidebarContent");
    if (sidebar) sidebar.style.animation = "slideOutRight 0.3s ease-in-out";

    setTimeout(() => {
        if (sidebarContent)
            sidebarContent.innerHTML =
                '<p class="text-muted">En attente de contenu...</p>';
    }, 300);
}

// Animations CSS seront ajoutées dans DOMContentLoaded

// =========================================================================
// GESTION DU MODE DE TRANSPORT (NOUVELLE FONCTION)
// =========================================================================
function setTravelMode(mode) {
    if (navigationMode) {
        alert(
            "Veuillez arrêter la navigation avant de changer de mode de transport.",
        );
        return;
    }

    travelMode = mode;

    // Mise à jour de l'état des boutons
    document.querySelectorAll("#mode-selector .btn").forEach((btn) => {
        btn.classList.remove("active");
    });
    const modeBtn = document.getElementById(`mode-${mode}`);
    if (modeBtn) {
        modeBtn.classList.add("active");
    }

    alert(
        `Mode de transport défini sur : ${
            mode.charAt(0).toUpperCase() + mode.slice(1)
        }`,
    );
}

// Demande explicite de permission et mise à jour de la position
function requestLocationPermission() {
    if (!navigator.geolocation) {
        alert("Géolocalisation non supportée par ce navigateur.");
        return;
    }
    try {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                updateGlobalCoords(pos.coords.latitude, pos.coords.longitude);
                try {
                    if (map)
                        map.setView(
                            [pos.coords.latitude, pos.coords.longitude],
                            15,
                        );
                } catch (e) {}
                const toast = document.getElementById("location-perm-toast");
                if (toast) toast.style.display = "none";
                // refresh now that we have coords
                try {
                    refreshEventsAndCheckForNotifications(true);
                } catch (e) {}
            },
            (err) => {
                console.warn("requestLocationPermission failed", err);
                alert(
                    "Impossible d'accéder à la position. Veuillez vérifier les permissions du navigateur.",
                );
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );
    } catch (e) {
        console.debug("requestLocationPermission exception", e);
    }
}

// =========================================================================
// GESTION DE LA POSITION
// =========================================================================

// Indicateur pour exécuter le refresh initial une fois la carte créée
let needInitialRefresh = false;
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            updateGlobalCoords(pos.coords.latitude, pos.coords.longitude);
            // SI la carte est déjà initialisée, on centre et on met à jour immédiatement
            if (typeof map !== "undefined" && map) {
                try {
                    map.setView([userLat, userLon], 15);
                    updateMapLocation(
                        userLat,
                        userLon,
                        pos.coords.heading ?? null,
                    );
                    refreshEventsAndCheckForNotifications(true); // Premier chargement
                } catch (e) {
                    console.debug(
                        "geolocation init deferred due to map error",
                        e,
                    );
                    needInitialRefresh = true;
                }
            } else {
                // Sinon, on garde l'info pour l'utiliser après l'initialisation de la carte
                needInitialRefresh = true;
            }
        },
        (err) => {
            console.warn("Géo non dispo :", err);
            if (typeof map !== "undefined" && map) {
                try {
                    refreshEventsAndCheckForNotifications(true);
                } catch (e) {
                    needInitialRefresh = true;
                }
            } else {
                needInitialRefresh = true;
            }
        },
    );
} else {
    needInitialRefresh = true;
}

// =========================================================================
// MODAL ET ENVOI DE L'ÉVÉNEMENT
// =========================================================================

function openEventModal(type) {
    if (!Number.isFinite(userLat) || !Number.isFinite(userLon))
        return alert(
            "Position non disponible. Veuillez attendre la localisation GPS.",
        );

    currentEventType = type;

    const eventTypeDisplay = document.getElementById("eventTypeName");
    if (eventTypeDisplay)
        eventTypeDisplay.innerText =
            type.charAt(0).toUpperCase() + type.slice(1);

    const eventDescriptionEl = document.getElementById("eventDescription");
    if (eventDescriptionEl) eventDescriptionEl.value = "";

    const eventModalEl = document.getElementById("eventModal");
    if (eventModalEl) {
        const eventModal = new bootstrap.Modal(eventModalEl);
        eventModal.show();
    } else {
        // Fallback: si le modal HTML n'existe pas (ex. build minimal), demander une description simple
        const desc = prompt("Description (max 100 chars) :", "");
        if (desc !== null) {
            currentEventType = type;
            // stocker temporairement dans le champ virtuel
            if (eventDescriptionEl)
                eventDescriptionEl.value = desc.substring(0, 100);
            // appeler l'envoi immédiat
            try {
                confirmAndSendEvent();
            } catch (e) {
                console.debug("confirmAndSendEvent fallback failed", e);
            }
        }
    }
}

const _confirmEventBtn = document.getElementById("confirmEventBtn");
if (_confirmEventBtn)
    _confirmEventBtn.addEventListener("click", confirmAndSendEvent);

function confirmAndSendEvent() {
    const type = currentEventType;
    const descEl = document.getElementById("eventDescription");
    const description = descEl
        ? descEl.value.substring(0, 100)
        : window._promptedEventDescription || "";

    console.debug("confirmAndSendEvent invoked", {
        type,
        description,
        userLat,
        userLon,
    });

    try {
        const eventModalEl = document.getElementById("eventModal");
        const modal = eventModalEl
            ? bootstrap.Modal.getInstance(eventModalEl)
            : null;
        if (modal) modal.hide();
    } catch (e) {
        console.debug("no bootstrap modal instance to hide", e);
    }

    if (!Number.isFinite(userLat) || !Number.isFinite(userLon) || !type)
        return alert("Erreur de données ou de position.");

    console.debug("sendEvent payload", {
        type,
        latitude: userLat,
        longitude: userLon,
        description,
    });
    fetch("/events", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf,
        },
        body: JSON.stringify({
            type: type,
            latitude: userLat,
            longitude: userLon,
            description: description,
        }),
    })
        .then((res) => {
            return res.text().then((text) => {
                if (!res.ok) {
                    console.error("sendEvent server error", {
                        status: res.status,
                        statusText: res.statusText,
                        text,
                    });
                    try {
                        const json = JSON.parse(text);
                        if (json && json.error) {
                            showErrorMessage(json.error);
                        }
                    } catch (e) {}
                    throw new Error("Server returned " + res.status);
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(
                        "sendEvent parse error — response text:",
                        text,
                    );
                    throw e;
                }
            });
        })
        .then((data) => {
            if (data.error) return alert(data.error);
            alert(`Signalement ${type} envoyé avec succès !`);

            // Ajouter immédiatement un marqueur client-side pour rendre le signalement visible
            try {
                const tempId = data.id || Date.now();
                const tempEvent = {
                    id: tempId,
                    type: type,
                    latitude: userLat,
                    longitude: userLon,
                    description: description,
                    votes: [],
                    expires_at: new Date(
                        Date.now() + 1000 * 60 * 60,
                    ).toISOString(),
                };
                eventsData.push(tempEvent);
                const popupHtml =
                    `${"<strong>"}${type.toUpperCase()}${"</strong>"}` +
                    (description
                        ? `<p>Détail: <em>${description}</em></p>`
                        : "") +
                    `<hr style="margin:5px 0;"><p class=\"mb-1\">Fiabilité: 👍 0 | 👎 0</p>`;
                const tempMarker = L.marker([userLat, userLon], {
                    icon: getIcon(type),
                })
                    .addTo(map)
                    .bindPopup(popupHtml)
                    .openPopup();
                markers.push(tempMarker);
                lastMaxEventId = Math.max(lastMaxEventId, tempId);
            } catch (e) {
                console.debug("create temporary marker failed", e);
            }

            // Enregistrer une notification côté client et afficher la liste
            try {
                var summary =
                    "Signalement " +
                    type +
                    (description ? ": " + description : "");
                addNotification(summary, tempId, userLat, userLon);
                showNotification(1);
            } catch (e) {
                console.debug("notification add failed", e);
            }

            // Rafraîchir les événements en arrière-plan
            refreshEventsAndCheckForNotifications();

            // Rafraîchir la liste des notifications persistées côté serveur
            try {
                fetchNotifications();
            } catch (e) {
                console.debug("fetchNotifications call failed", e);
            }
        })
        .catch((err) => console.error("sendEvent error", err));
}

// =========================================================================
// LOGIQUE DE VOTE
// =========================================================================

function vote(eventId, type) {
    if (currentUserId === null) {
        return alert("Veuillez vous connecter pour voter !");
    }

    fetch(`/events/${eventId}/vote`, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrf, "Content-Type": "application/json" },
        body: JSON.stringify({ type }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.deleted)
                alert("🚨 Signalement supprimé par vote communautaire !");
            refreshEventsAndCheckForNotifications();
        })
        .catch((err) => console.error("vote error", err));
}

// =========================================================================
// CHARGEMENT ET DESSIN DES ÉVÉNEMENTS (Polling)
// =========================================================================

function refreshEventsAndCheckForNotifications(init = false) {
    fetch("/events")
        .then((res) => res.json())
        .then((events) => {
            const now = new Date();
            const validEvents = events.filter(
                (e) => new Date(e.expires_at) > now,
            );
            const maxId = validEvents.reduce(
                (max, e) => Math.max(max, e.id || 0),
                0,
            );

            if (init) {
                lastMaxEventId = maxId;
                eventsData = validEvents;
                drawEvents();
                return;
            }

            if (maxId > lastMaxEventId) {
                const newCount = validEvents.filter(
                    (e) => e.id > lastMaxEventId,
                ).length;
                if (newCount > 0) {
                    const newEvents = validEvents.filter(
                        (e) => e.id > lastMaxEventId,
                    );
                    newEvents.forEach(function (ne) {
                        var s =
                            ne.type.toUpperCase() +
                            (ne.description ? ": " + ne.description : "");
                        try {
                            addNotification(
                                s,
                                ne.id,
                                ne.latitude,
                                ne.longitude,
                            );
                        } catch (e) {}
                    });
                    notifCount += newCount;
                    showNotification(newCount);
                    lastMaxEventId = maxId;
                }
            }

            eventsData = validEvents;
            drawEvents();
        })
        .catch((err) => console.error("refreshEvents error", err));
}

function drawEvents() {
    markers.forEach((m) => map.removeLayer(m));
    markers = [];

    eventsData.forEach((event) => {
        if (!filters[event.type]) return;

        const icon = getIcon(event.type);
        const up = (event.votes || []).filter((v) => v.type === "up").length;
        const down = (event.votes || []).filter(
            (v) => v.type === "down",
        ).length;

        const descriptionHtml = event.description
            ? `<p>Détail: <em>${event.description}</em></p>`
            : "";

        let userVoteType = null;
        if (currentUserId !== null) {
            const userVote = (event.votes || []).find(
                (v) => v.user_id == currentUserId,
            );
            if (userVote) {
                userVoteType = userVote.type;
            }
        }

        const btnUpStyle =
            userVoteType === "up"
                ? "btn-success disabled"
                : "btn-outline-success";
        const btnDownStyle =
            userVoteType === "down"
                ? "btn-danger disabled"
                : "btn-outline-danger";

        const marker = L.marker([event.latitude, event.longitude], {
            icon: icon,
        }).addTo(map).bindPopup(`
                <strong>${event.type.toUpperCase()}</strong>
                ${descriptionHtml}
                <hr style="margin: 5px 0;">
                <p class="mb-1">Fiabilité: 👍 ${up} | 👎 ${down}</p>
                <button 
                    class="btn btn-sm ${btnUpStyle} mt-1" 
                    onclick="vote(${event.id},'up')" 
                    ${userVoteType === "up" ? "disabled" : ""}>
                    👍 C'est vrai
                </button>
                <button 
                    class="btn btn-sm ${btnDownStyle} mt-1" 
                    onclick="vote(${event.id},'down')"
                    ${userVoteType === "down" ? "disabled" : ""}>
                    👎 C'est faux
                </button>
            `);
        markers.push(marker);
    });
}

// =========================================================================
// FILTRES D'AFFICHAGE
// =========================================================================

function toggleFilter(type) {
    filters[type] = !filters[type];
    const btn = document.getElementById(`filter-${type}`);

    if (filters[type]) {
        btn.classList.add("filter-btn-active");
    } else {
        btn.classList.remove("filter-btn-active");
    }

    drawEvents();
}

// =========================================================================
// NOTIFICATIONS ET SOUND
// =========================================================================

function showNotification(number) {
    if (notifBadge) notifBadge.innerText = notifCount;
    if (!notificationStack || !notificationStack.length) {
        if (notifBox) {
            notifBox.innerText = `🔔 ${number} nouveau(x) signalement(s) !`;
            notifBox.style.display = "block";
            setTimeout(() => {
                if (notifBox) notifBox.style.display = "none";
            }, 3000);
        }
        if (notifAudio) {
            const p = notifAudio.play();
            if (p !== undefined) p.catch(() => {});
        }
        return;
    }
    renderNotificationBox();
    if (notifBox) notifBox.style.display = "block";
    if (notifAudio) {
        const p = notifAudio.play();
        if (p !== undefined) p.catch(() => {});
    }
}

// Affiche un message d'erreur temporaire dans la notification box (ne change pas le badge)
function showErrorMessage(msg) {
    try {
        if (!notifBox) return alert(msg);
        var prevBg = notifBox.style.background;
        notifBox.innerText = "⚠️ " + msg;
        notifBox.style.background = "#fff3cd";
        notifBox.style.display = "block";
        setTimeout(function () {
            if (notifBox) {
                notifBox.style.display = "none";
                notifBox.style.background = prevBg || "";
            }
        }, 4000);
    } catch (e) {
        try {
            alert(msg);
        } catch (e) {}
    }
}

function resetNotificationCount() {
    notifCount = 0;
    if (notifBadge) notifBadge.innerText = 0;
}

// Stockage des notifications côté client
var notificationStack = [];

function addNotification(summary, eventId, lat, lon) {
    console.debug("addNotification called", {
        summary: summary,
        eventId: eventId,
        lat: lat,
        lon: lon,
    });
    notificationStack.unshift({
        id: eventId || Date.now(),
        summary: summary || "Nouveau signalement",
        lat: lat || null,
        lon: lon || null,
        ts: Date.now(),
    });
    if (notificationStack.length > 20) notificationStack.pop();
    notifCount = (notifCount || 0) + 1;
    if (notifBadge) notifBadge.innerText = notifCount;
    renderNotificationBox();
}

function renderNotificationBox() {
    console.debug("renderNotificationBox called", {
        notifBoxExists: !!notifBox,
        stackLen: notificationStack.length,
    });
    if (!notifBox) {
        console.debug("renderNotificationBox: notifBox is missing");
        return;
    }
    if (!notificationStack.length) {
        notifBox.innerHTML = "Aucune notification.";
        return;
    }
    var list = document.createElement("div");
    list.style.maxWidth = "320px";
    notificationStack.slice(0, 10).forEach(function (n) {
        var item = document.createElement("div");
        item.className = "notification-item";
        item.style.padding = "8px 6px";
        item.style.borderBottom = "1px solid rgba(0,0,0,0.06)";
        item.style.cursor = "pointer";

        var main = document.createElement("div");
        main.style.fontWeight = "600";
        main.style.lineHeight = "1.1";
        main.innerText = n.summary || "Nouveau signalement";

        var time = document.createElement("div");
        time.style.fontSize = "0.85rem";
        time.style.color = "rgba(0,0,0,0.6)";
        time.style.marginTop = "4px";
        time.innerText = new Date(n.ts).toLocaleTimeString();

        item.appendChild(main);
        item.appendChild(time);

        item.addEventListener("click", function () {
            if (n.lat && n.lon) {
                map.setView([n.lat, n.lon], 16, { animate: true });
                var m = markers.find(function (mk) {
                    return (
                        mk.getLatLng().lat === n.lat &&
                        mk.getLatLng().lng === n.lon
                    );
                });
                if (m) m.openPopup();
            }
            notifBox.style.display = "none";
        });
        list.appendChild(item);
    });
    notifBox.innerHTML = "";
    notifBox.appendChild(list);
}

function toggleNotificationBox() {
    if (!notifBox) return;
    if (notifBox.style.display === "block") {
        notifBox.style.display = "none";
        return;
    }
    renderNotificationBox();
    notifBox.style.display = "block";
}

// Récupère les notifications persistées côté serveur et met à jour la pile cliente
function fetchNotifications() {
    try {
        fetch("/notifications", {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
        })
            .then((r) => (r.ok ? r.json() : Promise.reject(r)))
            .then((items) => {
                if (!items || !items.length) {
                    notificationStack = [];
                    resetNotificationCount();
                    return;
                }
                // Map server items to client stack format
                notificationStack = items.slice(0, 50).map((n) => ({
                    id: n.id,
                    summary: n.message || n.summary || "Nouveau signalement",
                    lat: null,
                    lon: null,
                    ts: n.created_at
                        ? new Date(n.created_at).getTime()
                        : Date.now(),
                }));
                notifCount = notificationStack.length || 0;
                if (notifBadge) notifBadge.innerText = notifCount;
                renderNotificationBox();
            })
            .catch((err) => {
                console.debug("fetch /notifications failed", err);
            });
    } catch (e) {
        console.debug("fetchNotifications error", e);
    }
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Expose to global so inline onclick handlers work when using module build
try {
    window.toggleNotificationBox = toggleNotificationBox;
} catch (e) {}

// =========================================================================
// MODE NUIT, ICONS & INIT POLLING
// =========================================================================

function getIcon(type) {
    let iconUrl = "";
    if (type === "accident") iconUrl = "/icons/accident.png";
    if (type === "embouteillage") iconUrl = "/icons/traffic.png";
    if (type === "police") iconUrl = "/icons/police.png";
    // fallback to .jpg if .png is not present on the server
    if (type === "police") {
        iconUrl = "/icons/police.jpg";
    }
    if (type === "danger") iconUrl = "/icons/danger.png";
    return L.icon({ iconUrl, iconSize: [30, 30] });
}

// Création d'une icône utilisateur style "Waze" (DivIcon SVG)
function createUserIcon() {
    const svg = `
        <svg class="waze-car-svg" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
            <g>
                <path d="M32 4c-9.941 0-18 8.059-18 18 0 4.97 1.914 9.243 5.046 12.465L32 60l12.954-25.535C48.086 31.243 50 26.97 50 22 50 12.059 41.941 4 32 4z" fill="#00ADEF" stroke="#007b9e" stroke-width="1"/>
                <circle cx="24" cy="26" r="3" fill="#fff" />
                <circle cx="40" cy="26" r="3" fill="#fff" />
                <rect x="20" y="34" width="24" height="6" rx="3" fill="#fff" opacity="0.9"/>
            </g>
        </svg>
    `;

    return L.divIcon({
        className: "waze-div-icon",
        html: svg,
        iconSize: [48, 48],
        iconAnchor: [24, 24],
    });
}

// Création d'un L.icon à partir d'un fichier dans public/icons avec tailles et ancre utiles
function createAssetIcon(filename, size = [40, 40]) {
    const url = `/icons/${filename}`;
    return L.icon({
        iconUrl: url,
        iconSize: size,
        iconAnchor: [Math.round(size[0] / 2), size[1]],
        popupAnchor: [0, -Math.round(size[1] / 2)],
        className: "custom-icon",
    });
}

// Rotation lissée: interpole l'angle pour une transition fluide
let _rotationAnim = null;
function smoothRotateTo(el, targetDeg) {
    if (!el) return;
    cancelAnimationFrame(_rotationAnim);

    // lisser l'angle: récupérer angle courant
    const getCurrent = () => {
        const st = window.getComputedStyle(el);
        const tr = st.transform || st.webkitTransform || st.mozTransform;
        if (!tr || tr === "none") return 0;
        const values = tr.split("(")[1].split(")")[0].split(",");
        const a = parseFloat(values[0]);
        const b = parseFloat(values[1]);
        const angle = Math.round(Math.atan2(b, a) * (180 / Math.PI));
        return angle;
    };

    const start = getCurrent();
    // calcule la plus courte différence angulaire
    let diff = ((targetDeg - start + 540) % 360) - 180;
    const duration = 300; // ms
    const startTime = performance.now();

    function step(now) {
        const t = Math.min(1, (now - startTime) / duration);
        const ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; // easeInOutQuad-ish
        const angle = start + diff * ease;
        el.style.transform = `rotate(${angle}deg)`;
        if (t < 1) {
            _rotationAnim = requestAnimationFrame(step);
        }
    }
    _rotationAnim = requestAnimationFrame(step);
}

function toggleDarkMode() {
    document.body.classList.toggle("dark");
    localStorage.setItem("darkMode", document.body.classList.contains("dark"));
    applyMapTheme();
    updateDarkButton();
    handleNightAnimation();
}
function applyMapTheme() {
    if (document.body.classList.contains("dark")) {
        map.removeLayer(lightLayer);
        darkLayer.addTo(map);
    } else {
        map.removeLayer(darkLayer);
        lightLayer.addTo(map);
    }
}
function updateDarkButton() {
    const btn = document.getElementById("darkModeBtn");
    btn.innerHTML = document.body.classList.contains("dark")
        ? "☀️ Mode jour"
        : "🌙 Mode nuit";
}
window.onload = () => {
    if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark");
    }
    applyMapTheme();
    updateDarkButton();
    handleNightAnimation();
    Object.keys(filters).forEach((type) => {
        const filterBtn = document.getElementById(`filter-${type}`);
        if (filters[type] && filterBtn) {
            filterBtn.classList.add("filter-btn-active");
        }
    });
    // Initialiser le mode de transport
    setTravelMode(travelMode);
};
function createStars() {
    const starsContainer = document.getElementById("stars");
    starsContainer.innerHTML = "";
    for (let i = 0; i < 120; i++) {
        const star = document.createElement("div");
        star.classList.add("star");
        star.style.top = Math.random() * 100 + "%";
        star.style.left = Math.random() * 100 + "%";
        star.style.animationDuration = Math.random() * 2 + 1 + "s";
        starsContainer.appendChild(star);
    }
}
function handleNightAnimation() {
    const stars = document.getElementById("stars");
    if (document.body.classList.contains("dark")) {
        stars.style.display = "block";
        createStars();
    } else {
        stars.style.display = "none";
    }
}

// INIT POLLING
setTimeout(() => {
    setInterval(refreshEventsAndCheckForNotifications, 8000);
}, 3000);

// Activer l'audio au premier clic (nécessaire pour la lecture auto)
document.addEventListener("click", function oneClick() {
    if (notifAudio) {
        notifAudio
            .play()
            .then(() => {
                notifAudio.pause();
                notifAudio.currentTime = 0;
            })
            .catch(() => {});
    }
    document.removeEventListener("click", oneClick);
});

// =====================================================
// ✅ NAVIGATION TYPE WAZE (MIS À JOUR AVEC travelMode)
// =====================================================

let navigationMode = false;
let destinationMarker = null;
let routeLine = null;
let userMarker = null; // Nouveau marqueur pour la position de l'utilisateur
let watchPositionId = null;
let mapIsTrackingUser = false; // NOUVEAU: Flag pour le suivi actif
let lastHeading = null;
let startMarker = null;

// Variables pour le guidage vocal
let nextTurnAnnouncement = {
    lat: null,
    lon: null,
    instruction: null,
    distance: Infinity,
};
const TURN_ALERT_DISTANCE_M = 100; // Alerte virage à 100m (distance initiale)
const ARRIVED_THRESHOLD_M = 20; // Seuil pour considérer l'arrivée (20m)

// Bouton navigation (créé dynamiquement sans toucher au HTML)
document.addEventListener("DOMContentLoaded", () => {
    // ========== INITIALISATION DES ÉLÉMENTS DU DOM ==========
    // CSRF Token
    csrf = document.querySelector('meta[name="csrf-token"]').content;

    // Notifications & Audio
    notifBox = document.getElementById("notification-box");
    notifBadge = document.getElementById("notif-badge");
    notifAudio = document.getElementById("notif-sound");

    // Carte et données
    map = L.map("map").setView([5.348, -4.027], 13);
    lightLayer = L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            attribution: "© OpenStreetMap",
            maxZoom: 19,
            crossOrigin: true,
            errorTileUrl:
                "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=",
        },
    );
    darkLayer = L.tileLayer(
        "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",
        {
            attribution: "© OpenStreetMap",
            maxZoom: 19,
            crossOrigin: true,
            errorTileUrl:
                "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=",
        },
    );
    lightLayer.addTo(map);
    // ========================================================

    // Ajouter les animations CSS
    const style = document.createElement("style");
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Bouton START Navigation
    const navBtn = document.createElement("button");
    navBtn.className = "btn btn-success btn-sm me-2";
    navBtn.innerHTML = "🧭";
    navBtn.title = "Mode navigation";
    navBtn.onclick = startNavigationMode;

    // Bouton STOP Navigation
    const stopBtn = document.createElement("button");
    stopBtn.className = "btn btn-danger btn-sm d-none";
    stopBtn.innerHTML = "❌";
    stopBtn.title = "Arrêter navigation";
    stopBtn.id = "stopNavBtn";
    stopBtn.onclick = stopNavigation;

    const navbarDiv = document.querySelector(".navbar-top div");
    if (navbarDiv) {
        navbarDiv.prepend(stopBtn);
        navbarDiv.prepend(navBtn);
    }

    // Bouton recentrer : on l'ajoute toujours au body (évite qu'il disparaisse si aucune navbar)
    if (!document.getElementById("recenterBtn")) {
        const recenterBtn = document.createElement("button");
        // Visible par défaut pour que l'utilisateur le voie facilement
        recenterBtn.className = "btn btn-primary";
        recenterBtn.id = "recenterBtn";
        recenterBtn.title = "Recentrer";
        // Icône plus professionnelle via Font Awesome
        recenterBtn.innerHTML =
            '<i class="fa-solid fa-location-dot" aria-hidden="true"></i>';
        recenterBtn.setAttribute("aria-label", "Recentrer la carte");
        recenterBtn.onclick = function () {
            mapIsTrackingUser = true;
            // Rester visible en permanence — ne pas masquer le bouton après clic
            if (userLat && userLon)
                map.setView([userLat, userLon], map.getZoom(), {
                    animate: true,
                });
        };
        // placer le bouton en position fixe au-dessus de la carte (append au body)
        document.body.appendChild(recenterBtn);
    }

    // Attacher le listener de notifications au bouton de la navbar
    try {
        var notifBtnEl = document.getElementById("notifBtn");
        if (notifBtnEl) {
            try {
                notifBtnEl.removeAttribute("onclick");
            } catch (e) {}
            notifBtnEl.addEventListener("click", toggleNotificationBox);
        }
    } catch (e) {
        console.debug("attach notifBtn handler failed", e);
    }

    // 🗺️ Utiliser le CONTRÔLE FLOTTANT WAZE/GOOGLE MAPS déjà dans le HTML (blade)
    // Les références DOM pour la recherche sont initialisées plus bas via
    // des stubs et un listener DOMContentLoaded — évitons les duplications.
    window.performSearch = async (q) => {
        console.warn("performSearch: DOM elements not initialized");
        return [];
    };
    window.doSearch = () => {
        console.warn("doSearch: DOM elements not initialized");
    };
    window.renderFavorites = async () => {
        console.warn("renderFavorites: DOM elements not initialized");
    };
    window.renderHistory = () => {
        console.warn("renderHistory: DOM elements not initialized");
    };
    window.addCurrentFavorite = async () => {
        console.warn("addCurrentFavorite: DOM elements not initialized");
    };
    window.loadFavorites = () => {
        console.warn("loadFavorites: DOM elements not initialized");
        return [];
    };
    window.loadHistory = async () => {
        console.warn("loadHistory: DOM elements not initialized");
    };

    // Initialize these as null — they'll be set if DOM elements exist
    let searchIconBtn = null;
    let micBtn = null;
    let searchInput = null;
    let searchResults = null;
    let mapControls = null;

    // Try to assign them if they exist in the DOM (we're already inside DOMContentLoaded)
    searchInput = searchInput || document.getElementById("search-input");
    searchResults = searchResults || document.getElementById("search-results");
    // Fallbacks pour différents templates / IDs
    searchIconBtn =
        searchIconBtn ||
        document.querySelector("#map-controls .map-control-btn.search-icon") ||
        document.getElementById("searchIconBtn") ||
        document.querySelector(".map-control-btn.search-icon");
    micBtn =
        micBtn ||
        document.querySelector("#map-controls .map-control-btn.mic") ||
        document.getElementById("micBtn") ||
        document.querySelector(".map-control-btn.mic");
    mapControls =
        mapControls ||
        document.getElementById("map-controls") ||
        document.getElementById("search-compact");

    function debounce(fn, delay = 300) {
        let t = null;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    async function performSearch(q) {
        if (!q || q.trim().length < 2) return [];
        // Limiter les recherches à la Côte d'Ivoire (viewbox: SW-NE corners)
        const viewbox = `-8.6,4.3,-2.4,10.7`; // Côte d'Ivoire bounding box
        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(
            q,
        )}&addressdetails=1&limit=8&countrycodes=ci&viewbox=${viewbox}&bounded=1`;
        try {
            const res = await fetch(url, {
                headers: { Accept: "application/json" },
            });
            if (!res.ok) return [];
            const data = await res.json();
            return data;
        } catch (e) {
            console.debug("search error", e);
            return [];
        }
    }

    // -----------------------------
    // Search history (localStorage)
    // -----------------------------
    const HISTORY_KEY = "naviwaze_search_history_v1";
    let searchHistory = [];

    async function loadHistory() {
        if (currentUserId) {
            try {
                const res = await fetch("/search-history", {
                    method: "GET",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                });
                if (res.ok) {
                    const data = await res.json();
                    searchHistory = data.map((d) => ({
                        name: d.name,
                        lat: parseFloat(d.lat),
                        lon: parseFloat(d.lon),
                    }));
                    return;
                }
            } catch (e) {
                console.debug("load history error", e);
            }
        }
        // fallback to localStorage
        try {
            const raw = localStorage.getItem(HISTORY_KEY);
            searchHistory = raw ? JSON.parse(raw) : [];
        } catch (e) {
            searchHistory = [];
        }
    }

    function saveHistory() {
        // server-side users are saved on each add, so local save only for anonymous users
        if (currentUserId) return;
        try {
            localStorage.setItem(
                HISTORY_KEY,
                JSON.stringify(searchHistory.slice(0, 50)),
            );
        } catch (e) {
            console.debug("save history error", e);
        }
    }

    async function addToHistory(item) {
        if (!item || !item.name) return;
        // server-backed for authenticated users
        if (currentUserId) {
            try {
                await fetch("/search-history", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        name: item.name,
                        lat: item.lat,
                        lon: item.lon,
                    }),
                });
            } catch (e) {
                console.debug("history post error", e);
            }
            // reload from server to keep in sync
            await loadHistory();
            renderHistory();
            return;
        }

        // local fallback
        searchHistory = searchHistory.filter(
            (h) =>
                !(
                    h.name === item.name &&
                    h.lat === item.lat &&
                    h.lon === item.lon
                ),
        );
        searchHistory.unshift(item);
        if (searchHistory.length > 50) searchHistory.length = 50;
        saveHistory();
        renderHistory();
    }

    function renderHistory() {
        const historyList = document.getElementById("history-list");
        if (!historyList) return;
        historyList.innerHTML = "";
        if (!searchHistory || searchHistory.length === 0) {
            historyList.innerHTML =
                '<div class="text-muted small">Aucun historique</div>';
            return;
        }
        searchHistory.forEach((h) => {
            const a = document.createElement("button");
            a.type = "button";
            a.className = "list-group-item list-group-item-action history-item";
            a.innerText = h.name;
            a.onclick = () => {
                setDestination(h.lat, h.lon);
                // close sidebar after selection
                const sb = document.getElementById("left-sidebar");
                if (sb) sb.classList.remove("open");
            };
            historyList.appendChild(a);
        });
    }

    // initialise history
    loadHistory();
    renderHistory();

    // Hook up sidebar history controls (buttons inside blade)
    const historyBtn = document.getElementById("historyBtn");
    const historyContainer = document.getElementById("search-history");
    const clearHistoryBtn = document.getElementById("clear-history");
    if (historyBtn && historyContainer) {
        historyBtn.addEventListener("click", function () {
            const open = !historyContainer.classList.contains("d-none");
            if (open) {
                historyContainer.classList.add("d-none");
            } else {
                historyContainer.classList.remove("d-none");
                renderHistory();
            }
        });
    }
    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener("click", async function () {
            if (currentUserId) {
                try {
                    await fetch("/search-history", {
                        method: "DELETE",
                        credentials: "same-origin",
                        headers: { "X-CSRF-TOKEN": csrf },
                    });
                } catch (e) {
                    console.debug("clear history error", e);
                }
                await loadHistory();
                renderHistory();
                return;
            }
            searchHistory = [];
            saveHistory();
            renderHistory();
        });
    }

    function showResults(items) {
        if (!searchResults) return;
        searchResults.innerHTML = "";
        if (!items || items.length === 0) {
            searchResults.classList.remove("show");
            return;
        }
        items.forEach((it) => {
            const div = document.createElement("div");
            div.className = "search-item";
            div.innerHTML = `<i class="fa-solid fa-location-dot"></i><span>${
                it.display_name || it.name || ""
            }</span>`;
            div.onclick = () => {
                const lat = parseFloat(it.lat);
                const lon = parseFloat(it.lon);
                setDestination(lat, lon);
                searchResults.classList.remove("show");
                if (searchInput) searchInput.value = it.display_name || "";
                // store selection in history
                try {
                    addToHistory({
                        name: it.display_name || it.name || "",
                        lat,
                        lon,
                    });
                } catch (e) {
                    console.debug("history add error", e);
                }
            };
            searchResults.appendChild(div);
        });
        searchResults.classList.add("show");
    }

    const doSearch = debounce(async (ev) => {
        if (!searchInput || !searchResults) return;
        const q = searchInput.value.trim();
        if (q.length < 2) {
            searchResults.classList.remove("show");
            return;
        }
        const items = await performSearch(q);
        showResults(items);
    }, 300);

    // Comportement des boutons
    if (searchIconBtn) {
        searchIconBtn.onclick = () => {
            if (searchInput) searchInput.focus();
            doSearch();
        };
    }

    // Reconnaissance vocale simple (toggle) avec fallback si non supportée
    let speechRecognition = null;
    let speechListening = false;
    function toggleVoiceSearch() {
        const SR =
            window.SpeechRecognition ||
            window.webkitSpeechRecognition ||
            window.mozSpeechRecognition ||
            window.msSpeechRecognition;

        // Vérifier le support et contexte sécurisé
        const isSecure =
            location.protocol === "https:" || location.hostname === "localhost";
        if (!SR || !isSecure) {
            // Fallback convivial : ouvrir un prompt pour permettre la saisie vocale via OS ou taper manuellement
            const hint = !isSecure
                ? "La reconnaissance vocale nécessite HTTPS. "
                : "";
            const reply = prompt(
                hint + "Parlez maintenant (ou tapez votre recherche) :",
            );
            if (reply && reply.trim().length && searchInput) {
                searchInput.value = reply.trim();
                doSearch();
            }
            return;
        }

        if (!speechRecognition) {
            speechRecognition = new SR();
            speechRecognition.lang = "fr-FR";
            speechRecognition.interimResults = false;
            speechRecognition.maxAlternatives = 1;
            speechRecognition.onresult = (e) => {
                try {
                    const t = e.results[0][0].transcript;
                    if (searchInput) {
                        searchInput.value = t;
                        doSearch();
                    }
                } catch (err) {
                    console.debug("speech result error", err);
                }
            };
            speechRecognition.onend = () => {
                speechListening = false;
                micBtn.classList.remove("listening");
                micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            };
            speechRecognition.onerror = (ev) => {
                console.warn("speech error", ev);
                speechListening = false;
                micBtn.classList.remove("listening");
                micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            };
        }
        if (!speechListening) {
            try {
                speechRecognition.start();
                speechListening = true;
                micBtn.classList.add("listening");
                micBtn.innerHTML =
                    '<i class="fa-solid fa-microphone-lines"></i>';
            } catch (e) {
                console.debug("start recognition error", e);
            }
        } else {
            try {
                speechRecognition.stop();
            } catch (e) {
                console.debug("stop recognition error", e);
            }
        }
    }

    micBtn.onclick = toggleVoiceSearch;

    if (searchInput) {
        searchInput.addEventListener("input", doSearch);
        searchInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                doSearch();
            }
            if (e.key === "Escape") {
                searchResults.classList.add("d-none");
            }
        });
    }

    // Expose search/favorites functions globally (will override the stubs above)
    try {
        window.performSearch = performSearch;
        window.doSearch = doSearch;
        window.renderFavorites = renderFavorites;
        window.renderHistory = renderHistory;
        window.addCurrentFavorite = addCurrentFavorite;
        window.loadFavorites = loadFavorites;
        window.loadHistory = loadHistory;
    } catch (e) {
        console.debug("Error exposing global functions:", e);
    }
});

// ==========================
// ACTIVER NAVIGATION
// ==========================
function startNavigationMode() {
    if (!userLat || !userLon) {
        alert("📍 Position non disponible");
        return;
    }

    navigationMode = true;
    mapIsTrackingUser = true; // Démarre le suivi de la carte

    announceNavigationStart();
    alert(
        `🧭 Cliquez sur la carte pour choisir la destination en mode ${travelMode.toUpperCase()}`,
    );

    // ATTEND LE CLIC DE DESTINATION SUR LA CARTE
    map.once("click", (e) => {
        setDestination(e.latlng.lat, e.latlng.lng);
    });
}

// ==========================
// Reverse Geocoding Helpers
// ==========================
function distanceMeters(lat1, lon1, lat2, lon2) {
    const toRad = (v) => (v * Math.PI) / 180;
    const R = 6371000;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) *
            Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

async function reverseGeocode(lat, lon) {
    try {
        // Réutiliser le cache si proche
        if (lastGeocode.label && lastGeocode.lat !== null) {
            const d = distanceMeters(
                lat,
                lon,
                lastGeocode.lat,
                lastGeocode.lon,
            );
            if (d < 25 && Date.now() - lastGeocode.ts < 1000 * 60 * 5) {
                return lastGeocode.label;
            }
        }

        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(
            lat,
        )}&lon=${encodeURIComponent(lon)}&addressdetails=1`;
        const res = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error("geocode failed");
        const data = await res.json();
        const label = formatAddressFromNominatim(data);
        lastGeocode = { lat: lat, lon: lon, label: label, ts: Date.now() };
        return label;
    } catch (e) {
        console.debug("reverseGeocode error", e);
        return null;
    }
}

function formatAddressFromNominatim(data) {
    if (!data) return null;
    const addr = data.address || {};
    // Construire un format court : [house_number ]road, city
    const house = addr.house_number ? addr.house_number + " " : "";
    const road =
        addr.road || addr.pedestrian || addr.footway || addr.cycleway || "";
    const locality =
        addr.city ||
        addr.town ||
        addr.village ||
        addr.suburb ||
        addr.county ||
        "";

    if (road && locality) return (house + road + ", " + locality).trim();
    if (road) return (house + road).trim();
    if (locality) return locality;
    // fallback: utiliser display_name partiellement si rien d'autre
    if (data.display_name)
        return data.display_name.split(",").slice(0, 3).join(",").trim();
    return null;
}

// Attache le comportement : quand le popup s'ouvre, cacher le tooltip; quand le popup se ferme, ré-afficher le tooltip
function attachTooltipPopupToggle(marker) {
    if (!marker) return;
    try {
        marker.on("popupopen", () => {
            try {
                if (marker.getTooltip && marker.getTooltip())
                    marker.closeTooltip();
            } catch (e) {}
        });
        marker.on("popupclose", () => {
            // Si la position utilisateur n'a pas été obtenue auparavant, tenter une
            // récupération active pour permettre le partage / signalement.
            if ((!userLat || !userLon) && navigator.geolocation) {
                try {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            updateGlobalCoords(
                                pos.coords.latitude,
                                pos.coords.longitude,
                            );
                            try {
                                if (typeof map !== "undefined" && map)
                                    updateMapLocation(
                                        userLat,
                                        userLon,
                                        pos.coords.heading ?? null,
                                    );
                            } catch (e) {}
                        },
                        (err) => {
                            console.debug("initial geolocation failed:", err);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        },
                    );
                } catch (e) {
                    console.debug("geolocation request failed:", e);
                }
            }
        });
        marker.on("popupclose", () => {
            try {
                if (marker.getTooltip && marker.getTooltip())
                    marker.openTooltip();
            } catch (e) {}
        });
    } catch (e) {
        console.debug("attachTooltipPopupToggle", e);
    }
}

// ==========================
// DESTINATION
// ==========================
function setDestination(lat, lng) {
    if (destinationMarker) map.removeLayer(destinationMarker);
    if (routeLine) map.removeLayer(routeLine);

    destinationMarker = L.marker([lat, lng])
        .addTo(map)
        .bindPopup("🎯 Destination");
    // Remplacer par des icônes assets si disponibles (arrivée)
    try {
        if (destinationMarker) {
            map.removeLayer(destinationMarker);
        }
        destinationMarker = L.marker([lat, lng], {
            icon: createAssetIcon("arrive.png", [42, 42]),
        })
            .addTo(map)
            .bindPopup("🎯 Destination");
    } catch (e) {
        // fallback: laisser le marker de base
        console.debug("arrive icon fallback", e);
    }

    // Obtenir le nom d'adresse complet pour la destination et attacher un tooltip permanent
    reverseGeocode(lat, lng)
        .then((label) => {
            if (label && destinationMarker) {
                try {
                    destinationMarker.setPopupContent(label);
                    if (
                        destinationMarker.getTooltip &&
                        destinationMarker.getTooltip()
                    ) {
                        destinationMarker.getTooltip().setContent(label);
                    } else {
                        destinationMarker.bindTooltip(label, {
                            permanent: true,
                            direction: "right",
                            offset: [12, 0],
                            className: "user-label",
                        });
                        attachTooltipPopupToggle(destinationMarker);
                    }
                    // Ne pas ouvrir automatiquement le popup — tooltip suffit
                } catch (e) {
                    console.debug("update destination label", e);
                }
            }
        })
        .catch(() => {});

    calculateRoute(userLat, userLon, lat, lng);

    const stopNavBtn = document.getElementById("stopNavBtn");
    if (stopNavBtn) {
        stopNavBtn.classList.remove("d-none");
    }

    // GPS temps réel
    if (watchPositionId) navigator.geolocation.clearWatch(watchPositionId);

    watchPositionId = navigator.geolocation.watchPosition(
        (pos) => {
            // Mise à jour des coordonnées globales
            updateGlobalCoords(pos.coords.latitude, pos.coords.longitude);

            // 🔄 Met à jour la position de l'utilisateur sur la carte et recentre (avec heading si fourni)
            updateMapLocation(userLat, userLon, pos.coords.heading ?? null);

            // Recalcule la route et annonce les instructions
            calculateRoute(userLat, userLon, lat, lng);
            onUserMove(userLat, userLon);
        },
        (err) => {
            console.warn("watchPosition error", err);
        },
        { enableHighAccuracy: true, maximumAge: 1000, timeout: 5000 },
    );

    // Activer visuel de suivi (pulse) lorsque le watchPosition est actif
    try {
        const rc = document.getElementById("recenterBtn");
        if (rc) rc.classList.add("tracking-pulse");
    } catch (e) {}

    // Remplacer l'icône du marqueur utilisateur par depart.png pendant la navigation
    try {
        if (userMarker && userLat && userLon) {
            // Retirer l'ancien marqueur et en créer un nouveau avec l'icône depart.png
            map.removeLayer(userMarker);
            const tempLabel = "🔵 Départ";
            userMarker = L.marker([userLat, userLon], {
                icon: createAssetIcon("depart.png", [36, 36]),
                zIndexOffset: 1000,
            })
                .addTo(map)
                .bindPopup(tempLabel);
            // Mettre à jour adresse et tooltip
            reverseGeocode(userLat, userLon)
                .then((label) => {
                    if (label && userMarker) {
                        try {
                            userMarker.setPopupContent(label);
                            if (
                                userMarker.getTooltip &&
                                userMarker.getTooltip()
                            )
                                userMarker.getTooltip().setContent(label);
                            else {
                                userMarker.bindTooltip(label, {
                                    permanent: true,
                                    direction: "right",
                                    offset: [12, 0],
                                    className: "user-label",
                                });
                                attachTooltipPopupToggle(userMarker);
                            }
                        } catch (e) {}
                    }
                })
                .catch(() => {});
        }
    } catch (e) {
        console.debug("depart icon fallback", e);
    }
}

// ==========================
// 🚀 NOUVEAU : MISE À JOUR DE LA POSITION DE L'UTILISATEUR ET SUIVI DE LA CARTE
// ==========================
function updateMapLocation(lat, lon, heading = null) {
    const coords = [lat, lon];

    // 1. Mise à jour du marqueur utilisateur (utilise depart.png comme icône par défaut)
    const tempLabel = "📍 Vous êtes ici";
    if (userMarker) {
        userMarker.setLatLng(coords);
    } else {
        // créer le marqueur avec depart.png dès le départ (au lieu de l'icône SVG)
        try {
            userMarker = L.marker(coords, {
                icon: createAssetIcon("depart.png", [36, 36]),
                zIndexOffset: 1000,
            })
                .addTo(map)
                .bindPopup(tempLabel);
        } catch (e) {
            // fallback: utiliser l'icône SVG si depart.png échoue
            console.debug("depart icon fallback, using SVG", e);
            userMarker = L.marker(coords, {
                icon: createUserIcon(),
                zIndexOffset: 1000,
            })
                .addTo(map)
                .bindPopup(tempLabel);
        }

        // Lancer un reverse-geocode asynchrone pour mettre le nom exact
        reverseGeocode(lat, lon)
            .then((label) => {
                if (label && userMarker) {
                    try {
                        userMarker.setPopupContent(label);
                        if (userMarker.getTooltip && userMarker.getTooltip()) {
                            userMarker.getTooltip().setContent(label);
                        } else {
                            userMarker.bindTooltip(label, {
                                permanent: true,
                                direction: "right",
                                offset: [12, 0],
                                className: "user-label",
                            });
                            attachTooltipPopupToggle(userMarker);
                        }
                        // Ne pas ouvrir automatiquement le popup pour éviter la duplication avec le tooltip
                    } catch (e) {
                        console.debug("update popup tooltip userMarker", e);
                    }
                }
            })
            .catch(() => {});
    }
    // Si le marqueur existe déjà, vérifier si l'adresse doit être rafraîchie
    if (userMarker) {
        reverseGeocode(lat, lon)
            .then((label) => {
                if (label && userMarker) {
                    try {
                        if (userMarker.getTooltip && userMarker.getTooltip()) {
                            userMarker.getTooltip().setContent(label);
                        } else {
                            userMarker.bindTooltip(label, {
                                permanent: true,
                                direction: "right",
                                offset: [12, 0],
                                className: "user-label",
                            });
                            attachTooltipPopupToggle(userMarker);
                        }
                        userMarker.setPopupContent(label);
                    } catch (e) {
                        console.debug("refresh user label", e);
                    }
                }
            })
            .catch(() => {});
    }

    // appliquer rotation lissée si heading disponible (fonctionne aussi pour depart.png)
    if (heading !== null && !isNaN(heading)) {
        lastHeading = heading;
        const el = userMarker.getElement && userMarker.getElement();
        if (el) {
            const svg = el.querySelector && el.querySelector(".waze-car-svg");
            if (svg) smoothRotateTo(svg, heading);
        }
    }

    if (eventDescriptionEl) eventDescriptionEl.value = desc.substring(0, 100);
    window._promptedEventDescription = desc.substring(0, 100);
    if (mapIsTrackingUser) {
        map.setView(coords, map.getZoom(), {
            animate: true,
            duration: 1.0, // Animation douce
        });
    }

    // 2b. Mettre à jour l'état visuel du bouton recenter (pulse) selon le suivi
    try {
        const rc = document.getElementById("recenterBtn");
        if (rc) {
            if (mapIsTrackingUser) rc.classList.add("tracking-pulse");
            else rc.classList.remove("tracking-pulse");
        }
    } catch (e) {}

    // 3. Permettre de désactiver le suivi en déplaçant manuellement la carte
    map.on("dragstart", function () {
        if (navigationMode) {
            mapIsTrackingUser = false;
        }
        // Retirer l'effet pulse si l'utilisateur prend la main
        const rc = document.getElementById("recenterBtn");
        if (rc) rc.classList.remove("tracking-pulse");
    });

    // 4. (Optionnel) Ajouter un bouton de recentrage si le suivi est désactivé
    // Ce bouton n'est pas inclus ici pour garder le code simple, mais serait la prochaine étape.
}

// ==========================
// CALCUL ITINÉRAIRE (OSRM)
// ==========================
function calculateRoute(startLat, startLng, endLat, endLng) {
    const osrmProfile = travelMode;
    const apiUrl = `https://router.project-osrm.org/route/v1/${osrmProfile}/${startLng},${startLat};${endLng},${endLat}?overview=full&geometries=geojson&steps=true`;

    nextTurnAnnouncement = {
        lat: null,
        lon: null,
        instruction: null,
        distance: Infinity,
    };

    fetch(apiUrl)
        .then((res) => res.json())
        .then((data) => {
            if (!data.routes || !data.routes.length) {
                console.error(
                    "Pas de route trouvée pour ce mode de transport.",
                );
                if (navigationMode)
                    alert(
                        `⚠️ Pas de route trouvée pour le mode ${travelMode}.`,
                    );
                return;
            }

            const route = data.routes[0];
            const coords = route.geometry.coordinates.map((c) => [c[1], c[0]]);
            const totalDistance = route.distance; // Distance restante totale

            if (routeLine) map.removeLayer(routeLine);

            routeLine = L.polyline(coords, {
                color: document.body.classList.contains("dark")
                    ? "#00ffff"
                    : "#007bff",
                weight: 6,
            }).addTo(map);

            // Extraction de la prochaine instruction de manœuvre
            if (route.legs && route.legs.length > 0) {
                const steps = route.legs[0].steps;

                const nextStep = steps.find((step) => step.distance > 0);

                if (nextStep) {
                    const maneuver = nextStep.maneuver.location;
                    nextTurnAnnouncement = {
                        lat: maneuver[1],
                        lon: maneuver[0],
                        instruction: nextStep.maneuver.instruction,
                        distance: nextStep.distance,
                    };
                }
            }

            // 🎤 VOIX
            announceETA(totalDistance);
            announceDistance(totalDistance);

            // Vérification des événements sur la route
            checkEventsOnRoute(startLat, startLng);

            // 🕒 ETA (Affichage visuel)
            updateETA(totalDistance);

            // Vérification de l'arrivée
            if (totalDistance < ARRIVED_THRESHOLD_M) {
                announceArrival();
                stopNavigation();
            }
        })
        .catch((err) => console.error(`Route error for ${osrmProfile}`, err));
}

// ==========================
// ARRÊTER NAVIGATION
// ==========================
function stopNavigation() {
    navigationMode = false;
    mapIsTrackingUser = false; // Arrête le suivi
    hideETA(); // Masquer l'ETA

    if (destinationMarker) map.removeLayer(destinationMarker);
    if (routeLine) map.removeLayer(routeLine);
    if (watchPositionId) navigator.geolocation.clearWatch(watchPositionId);
    if (userMarker) map.removeLayer(userMarker); // Retire le marqueur utilisateur (depart.png ou autre)
    if (startMarker) map.removeLayer(startMarker);

    destinationMarker = null;
    routeLine = null;
    watchPositionId = null;
    userMarker = null;
    startMarker = null;

    // Retirer l'écouteur d'événement dragstart de la carte si vous l'avez ajouté
    map.off("dragstart");

    const stopNavBtn = document.getElementById("stopNavBtn");
    if (stopNavBtn) {
        stopNavBtn.classList.add("d-none");
    }

    // Restaurer le marqueur utilisateur initial avec l'icône depart.png
    if (userLat && userLon) {
        const tempLabel = "📍 Vous êtes ici";
        try {
            userMarker = L.marker([userLat, userLon], {
                icon: createAssetIcon("depart.png", [36, 36]),
                zIndexOffset: 1000,
            })
                .addTo(map)
                .bindPopup(tempLabel);
        } catch (e) {
            // fallback: utiliser l'icône SVG si depart.png échoue
            console.debug("depart icon fallback on stop, using SVG", e);
            userMarker = L.marker([userLat, userLon], {
                icon: createUserIcon(),
                zIndexOffset: 1000,
            })
                .addTo(map)
                .bindPopup(tempLabel);
        }
        // Mettre à jour l'adresse complète
        reverseGeocode(userLat, userLon)
            .then((label) => {
                if (label && userMarker) {
                    try {
                        userMarker.setPopupContent(label);
                        if (userMarker.getTooltip && userMarker.getTooltip()) {
                            userMarker.getTooltip().setContent(label);
                        } else {
                            userMarker.bindTooltip(label, {
                                permanent: true,
                                direction: "right",
                                offset: [12, 0],
                                className: "user-label",
                            });
                            attachTooltipPopupToggle(userMarker);
                        }
                    } catch (e) {
                        console.debug("restore user label", e);
                    }
                }
            })
            .catch(() => {});
    }

    speak("Navigation arrêtée.", true);
}

// La fonction onUserMove est maintenant utilisée principalement pour la convention,
// la logique de mise à jour de la carte est dans updateMapLocation.
function onUserMove(lat, lon) {
    // La route est recalculée et les annonces gérées dans calculateRoute
    // La carte est mise à jour dans updateMapLocation
}

// =====================================================
// 🔊 GUIDAGE VOCAL AVANCÉ
// =====================================================

let speechEnabled = true;
let lastVoiceMessage = "";
let lastVoiceTime = 0;
let arrived = false;

// paramètres
const VOICE_COOLDOWN = 5000; // 5 secondes
const EVENT_ALERT_DISTANCE = 300; // mètres

// =====================================================
// 🎤 FONCTION VOCALE INTELLIGENTE
// =====================================================
function speak(text, force = false) {
    if (!speechEnabled || !("speechSynthesis" in window)) return;

    const now = Date.now();

    if (!force) {
        if (text === lastVoiceMessage) return;
        if (now - lastVoiceTime < VOICE_COOLDOWN) return;
    }

    // Annulation de la voix précédente pour la fluidité
    speechSynthesis.cancel();

    const msg = new SpeechSynthesisUtterance(text);
    msg.lang = "fr-FR";
    msg.rate = 1;
    msg.pitch = 1;
    msg.volume = 1;

    speechSynthesis.speak(msg);

    lastVoiceMessage = text;
    lastVoiceTime = now;
}

// =====================================================
// 🧭 ANNONCES DE NAVIGATION
// =====================================================
function announceNavigationStart() {
    arrived = false;
    // Annonce du mode, l'ETA arrive juste après via calculateRoute
    speak(
        `Navigation démarrée en mode ${travelMode}. Suivez l'itinéraire.`,
        true,
    );
}

function announceArrival() {
    if (arrived) return;
    arrived = true;
    speak("Vous êtes arrivé à destination.", true);
}

// =====================================================
// 🕒 ANNONCE VOCALE DE L'ETA (Calcul et Vocalisation)
// =====================================================
function announceETA(distanceMeters) {
    if (!navigationMode) return;

    let avgSpeedKmh = 40; // Par défaut (Voiture)

    if (travelMode === "bike") {
        avgSpeedKmh = 25;
    } else if (travelMode === "foot") {
        avgSpeedKmh = 5;
    }

    const speedMs = (avgSpeedKmh * 1000) / 3600;
    const timeSeconds = distanceMeters / speedMs;
    const minutes = Math.max(1, Math.round(timeSeconds / 60));

    const kmDistance = (distanceMeters / 1000).toFixed(1);

    // Force l'annonce initiale
    speak(
        `Distance totale : ${kmDistance} kilomètres. Temps de parcours estimé : ${minutes} minutes.`,
        true,
    );
}

// =====================================================
// 📏 DISTANCE DYNAMIQUE (Instructions de Virage)
// =====================================================
function announceDistance(totalDistance) {
    if (!navigationMode || !nextTurnAnnouncement.instruction) {
        return;
    }

    const distToTurn = nextTurnAnnouncement.distance;
    const instruction = nextTurnAnnouncement.instruction;

    // Logique d'annonce pour le prochain virage
    if (distToTurn > 500 && distToTurn < 800) {
        speak(`Dans huit cents mètres, ${instruction}`);
    } else if (distToTurn > 250 && distToTurn <= 500) {
        speak(`Dans cinq cents mètres, ${instruction}`);
    } else if (distToTurn > 100 && distToTurn <= 250) {
        speak(`Dans deux cents mètres, ${instruction}`);
    } else if (distToTurn > 30 && distToTurn <= 100) {
        speak(`Dans cent mètres, ${instruction}`);
    } else if (distToTurn <= 30 && distToTurn > ARRIVED_THRESHOLD_M) {
        speak(`${instruction} maintenant.`, true);
    }

    // Annonce de continuité (seulement si le dernier message vocal était il y a plus de 30s)
    if (totalDistance > 1500 && distToTurn > 800) {
        if (Date.now() - lastVoiceTime > 30000) {
            speak("Continuez tout droit sur la route actuelle.");
        }
    }
}

// =====================================================
// 🚧 ALERTES SIGNALEMENTS SUR LA ROUTE
// =====================================================
function checkEventsOnRoute(userLat, userLon) {
    if (!eventsData || !eventsData.length) return;

    eventsData.forEach((event) => {
        const dist = map.distance(
            [userLat, userLon],
            [event.latitude, event.longitude],
        );

        if (dist < EVENT_ALERT_DISTANCE) {
            if (event.type === "accident") speak("Attention accident à venir");
            if (event.type === "police")
                speak("Présence de la police signalée");
            if (event.type === "danger")
                speak("Danger signalé sur votre route");

            if (travelMode === "driving" && event.type === "embouteillage")
                speak("Ralentissement signalé");
        }
    });
}

// =====================================================
//  ETA – HEURE D'ARRIVÉE ESTIMÉE (TEMPS RÉEL)
// =====================================================

// Box ETA flottante
const etaBox = document.createElement("div");
etaBox.style.position = "fixed";
etaBox.style.bottom = "90px";
etaBox.style.left = "50%";
etaBox.style.transform = "translateX(-50%)";
etaBox.style.background = "rgba(0,0,0,0.75)";
etaBox.style.color = "#fff";
etaBox.style.padding = "8px 14px";
etaBox.style.borderRadius = "12px";
etaBox.style.fontSize = "14px";
etaBox.style.zIndex = "9999";
etaBox.style.display = "none";
etaBox.style.boxShadow = "0 0 10px rgba(0,255,255,0.6)";
document.body.appendChild(etaBox);

// Fonction calcul ETA (pour l'affichage)
function updateETA(distanceMeters) {
    let avgSpeedKmh = 40; // Vitesse de base (Voiture)

    // Ajustement de la vitesse en fonction du mode de transport
    if (travelMode === "bike") {
        avgSpeedKmh = 25; // Moto/vélo
    } else if (travelMode === "foot") {
        avgSpeedKmh = 5; // Piéton
    }

    const speedMs = (avgSpeedKmh * 1000) / 3600;
    const timeSeconds = distanceMeters / speedMs;

    const minutes = Math.max(1, Math.round(timeSeconds / 60));

    const arrival = new Date(Date.now() + timeSeconds * 1000);
    const arrivalTime = arrival.toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
    });

    etaBox.innerHTML = `
        ⏱️ <strong>${minutes} min</strong> restantes<br>
        🕒 Arrivée : <strong>${arrivalTime}</strong>
    `;
    etaBox.style.display = "block";
}

// Masquer ETA
function hideETA() {
    etaBox.style.display = "none";
}

// =========================================================================
// EXPOSITION GLOBALE DES FONCTIONS (pour les attributs onclick du HTML)
// =========================================================================
window.openEventModal = openEventModal;
window.toggleDarkMode = toggleDarkMode;
window.toggleFilter = toggleFilter;
window.setTravelMode = setTravelMode;
window.closeSidebar = closeSidebar;
window.vote = vote;
window.resetNotificationCount = resetNotificationCount;

/* ===== Adresses enregistrées (favorites) - fallback for public bundle ===== */
function loadFavorites() {
    const raw = localStorage.getItem("lw_favorites");
    try {
        return raw ? JSON.parse(raw) : [];
    } catch (e) {
        console.debug("favorites parse error", e);
        return [];
    }
}

function saveFavorites(list) {
    localStorage.setItem("lw_favorites", JSON.stringify(list || []));
}
async function fetchServerFavorites() {
    try {
        const res = await fetch("/favorites", {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
        });
        if (!res.ok) return [];
        const data = await res.json();
        return data.map((d) => ({
            id: d.id,
            name: d.name,
            type: d.type,
            lat: parseFloat(d.latitude),
            lon: parseFloat(d.longitude),
            created_at: d.created_at,
        }));
    } catch (e) {
        console.debug("fetchServerFavorites error", e);
        return [];
    }
}

async function renderFavorites() {
    const favList = document.getElementById("favorites-list");
    if (!favList) return;
    let items = [];
    if (currentUserId) items = await fetchServerFavorites();
    else items = loadFavorites();
    favList.innerHTML = "";
    if (!items || items.length === 0) {
        favList.innerHTML =
            '<div class="text-muted small">Aucune adresse enregistrée</div>';
        return;
    }
    items.forEach((f) => {
        const item = document.createElement("div");
        item.className =
            "list-group-item d-flex align-items-center justify-content-between";

        const left = document.createElement("div");
        left.className = "d-flex align-items-center gap-2";

        const icon = document.createElement("i");
        icon.className =
            f.type === "home"
                ? "fa-solid fa-house"
                : f.type === "work"
                  ? "fa-solid fa-briefcase"
                  : "fa-solid fa-school";
        icon.style.width = "26px";
        icon.style.textAlign = "center";

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "btn btn-link p-0 text-start flex-grow-1";
        btn.style = "text-decoration: none; color: inherit;";
        btn.innerText =
            f.name ||
            `${f.type} (${(f.lat || 0).toFixed(5)}, ${(f.lon || 0).toFixed(
                5,
            )})`;
        btn.addEventListener("click", () => {
            if (typeof setDestination === "function")
                setDestination(f.lat, f.lon);
            const sb = document.getElementById("left-sidebar");
            if (sb) sb.classList.remove("open");
        });

        left.appendChild(icon);
        left.appendChild(btn);

        const right = document.createElement("div");
        right.className = "d-flex gap-1 align-items-center";

        const del = document.createElement("button");
        del.type = "button";
        del.className = "history-delete-btn";
        del.innerHTML = '<i class="fa-solid fa-trash"></i>';
        del.title = "Supprimer";
        del.addEventListener("click", async (ev) => {
            ev.stopPropagation();
            if (currentUserId && f.id) {
                try {
                    await fetch(`/favorites/${f.id}`, {
                        method: "DELETE",
                        credentials: "same-origin",
                        headers: { "X-CSRF-TOKEN": csrf },
                    });
                    await renderFavorites();
                    return;
                } catch (e) {
                    console.debug("delete favorite error", e);
                }
            }
            const remaining = loadFavorites().filter((x) => x.id !== f.id);
            saveFavorites(remaining);
            renderFavorites();
        });

        right.appendChild(del);

        item.appendChild(left);
        item.appendChild(right);
        favList.appendChild(item);
    });
}

async function addCurrentFavorite() {
    const type = document.getElementById("favoriteType")?.value || "home";
    let lat = userLat || null;
    let lon = userLon || null;
    if ((!lat || !lon) && typeof map !== "undefined" && map && map.getCenter) {
        const c = map.getCenter();
        lat = c.lat;
        lon = c.lng;
        console.debug("Using map center as fallback for favorite", lat, lon);
    }
    if (!lat || !lon) {
        alert(
            "Position non disponible. Autorisez la géolocalisation ou recentrez la carte.",
        );
        return;
    }
    const defaultName =
        type === "home" ? "Maison" : type === "work" ? "Travail" : "École";
    // Use modal instead of prompt
    const favModalEl = document.getElementById("favoriteNameModal");
    const favInput = document.getElementById("favoriteNameInput");
    if (favInput) favInput.value = defaultName;
    const favModal = new bootstrap.Modal(favModalEl);
    favModal.show();

    // Wait for user confirmation via modal button
    const namePromise = new Promise((resolve) => {
        const confirmBtn = document.getElementById("favoriteNameConfirm");
        function onConfirm() {
            const val =
                favInput && favInput.value.trim()
                    ? favInput.value.trim()
                    : defaultName;
            resolve(val);
            confirmBtn.removeEventListener("click", onConfirm);
            favModal.hide();
        }
        confirmBtn.addEventListener("click", onConfirm);
    });
    const name = await namePromise;
    if (currentUserId) {
        try {
            const payload = {
                name,
                type,
                latitude: Number(lat),
                longitude: Number(lon),
            };
            await fetch("/favorites", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });
            await renderFavorites();
            alert("Adresse enregistrée (serveur).");
            return;
        } catch (e) {
            console.debug("favorite post error", e);
        }
    }
    const list = loadFavorites();
    const entry = {
        id: Date.now(),
        name,
        type,
        lat: Number(lat),
        lon: Number(lon),
        created_at: new Date().toISOString(),
    };
    list.unshift(entry);
    saveFavorites(list);
    renderFavorites();
    alert("Adresse enregistrée.");
}

// Hook favorites UI
document.addEventListener("DOMContentLoaded", function () {
    const openSaved = document.getElementById("openSavedAddresses");
    const savedBlock = document.getElementById("saved-addresses");
    if (openSaved && savedBlock) {
        openSaved.addEventListener("click", function (ev) {
            ev.preventDefault();
            if (savedBlock.classList.contains("d-none"))
                savedBlock.classList.remove("d-none");
            else savedBlock.classList.add("d-none");
            renderFavorites();
        });
    }
    const addFavBtn = document.getElementById("add-current-favorite");
    if (addFavBtn) addFavBtn.addEventListener("click", addCurrentFavorite);
});

// =========================================================================
// PARTAGE DE POSITION - GESTION DU MODAL, COPIE, WEB SHARE, ET PARSING D'URL
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    const shareBtn = document.getElementById("sharePositionBtn");
    const shareModalEl = document.getElementById("sharePositionModal");
    const shareInput = document.getElementById("shareLinkInput");
    const copyBtn = document.getElementById("copyShareLink");
    const webShareBtn = document.getElementById("webShareBtn");

    function generateShareLink(lat, lon, label) {
        const base = location.origin + location.pathname;
        const params = new URLSearchParams();
        params.set("share_lat", Number(lat).toFixed(6));
        params.set("share_lon", Number(lon).toFixed(6));
        if (label) params.set("share_label", label);
        return `${base}?${params.toString()}`;
    }

    async function buildLinkAndShowModal(lat, lon, label) {
        const link = generateShareLink(lat, lon, label);
        if (shareInput) shareInput.value = link;
        const modal = new bootstrap.Modal(shareModalEl);
        modal.show();
    }

    if (shareBtn) {
        shareBtn.addEventListener("click", async function (ev) {
            ev.preventDefault();
            let lat = null,
                lon = null,
                label = null;
            if (
                typeof destinationMarker !== "undefined" &&
                destinationMarker &&
                destinationMarker.getLatLng
            ) {
                const ll = destinationMarker.getLatLng();
                lat = ll.lat;
                lon = ll.lng;
            } else if (Number.isFinite(userLat) && Number.isFinite(userLon)) {
                lat = userLat;
                lon = userLon;
            } else if (map && map.getCenter) {
                const c = map.getCenter();
                lat = c.lat;
                lon = c.lng;
            }
            // Debug logs to inspect why coords may be missing
            try {
                console.debug("sharePosition click -> candidates", {
                    destinationMarker: !!(
                        typeof destinationMarker !== "undefined" &&
                        destinationMarker
                    ),
                    destinationLatLng:
                        typeof destinationMarker !== "undefined" &&
                        destinationMarker &&
                        destinationMarker.getLatLng
                            ? destinationMarker.getLatLng()
                            : null,
                    userLat,
                    userLon,
                    mapAvailable: !!(
                        typeof map !== "undefined" &&
                        map &&
                        map.getCenter
                    ),
                    mapCenter:
                        typeof map !== "undefined" && map && map.getCenter
                            ? map.getCenter()
                            : null,
                    geolocationAvailable: !!navigator.geolocation,
                });
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions
                        .query({ name: "geolocation" })
                        .then((p) =>
                            console.debug(
                                "geolocation permission state:",
                                p.state,
                            ),
                        );
                }
            } catch (e) {
                console.debug("share debug failed", e);
            }

            if (!Number.isFinite(lat) || !Number.isFinite(lon))
                return alert("Position non disponible.");
            try {
                label = await reverseGeocode(lat, lon);
            } catch (e) {
                label = null;
            }
            buildLinkAndShowModal(lat, lon, label);
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener("click", async function () {
            const val = shareInput ? shareInput.value : null;
            if (!val) return;
            try {
                await navigator.clipboard.writeText(val);
                alert("Lien copié dans le presse-papiers.");
            } catch (e) {
                shareInput.select();
                document.execCommand("copy");
                alert("Lien copié.");
            }
        });
    }

    if (webShareBtn) {
        webShareBtn.addEventListener("click", async function () {
            const val = shareInput ? shareInput.value : null;
            if (!val) return;
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: "Ma position",
                        text: shareInput ? shareInput.value : "",
                        url: val,
                    });
                } catch (e) {
                    console.debug("web share failed", e);
                }
            } else {
                try {
                    await navigator.clipboard.writeText(val);
                    alert("Partage non supporté — lien copié.");
                } catch (e) {
                    alert("Copiez manuellement le lien.");
                }
            }
        });
    }

    (function parseSharedUrl() {
        const params = new URLSearchParams(window.location.search);
        const lat = params.get("share_lat");
        const lon = params.get("share_lon");
        const label = params.get("share_label");
        if (lat && lon && map) {
            const la = parseFloat(lat),
                lo = parseFloat(lon);
            if (sharedMarker) map.removeLayer(sharedMarker);
            sharedMarker = L.marker([la, lo]).addTo(map);
            if (label) sharedMarker.bindPopup(label).openPopup();
            map.setView([la, lo], 16);
        }
    })();
});
