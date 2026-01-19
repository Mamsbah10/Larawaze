<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carte YATRAFFIC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary-color: #007bff;
            --bg-light: #f8f9fa;
            --text-dark: #212529;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --navbar-height: 56px;
            --bottom-bar-height: 70px;
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }

        body.dark {
            --primary-color: #00ffff;
            --bg-light: #1a1a1a;
            --text-dark: #e0e0e0;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
        }

        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            overflow: auto;
            height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ============ MAP ============ */
        #map {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
        }

        /* ============ TOP BAR ============ */
        .navbar-top {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--navbar-height);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
        }

        body.dark .navbar-top {
            background: rgba(26, 26, 26, 0.95);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ============ SEARCH COMPACT ============ */
        #search-compact {
            position: fixed;
            top: calc(var(--navbar-height) + 8px);
            left: 8px;
            right: 8px;
            z-index: 900;
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            padding: 8px 12px;
            gap: 8px;
            transition: transform 0.3s ease;
        }

        body.dark #search-compact {
            background: #2a2a2a;
        }

        #search-compact.hidden {
            transform: translateY(-100px);
        }

        #search-compact input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            outline: none;
            color: var(--text-dark);
        }

        #search-compact input::placeholder {
            color: #999;
        }

        #search-compact .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            font-size: 1.1rem;
            transition: background 0.2s;
        }

        #search-compact .btn-icon:active {
            background: rgba(0,0,0,0.1);
        }

        body.dark #search-compact .btn-icon:active {
            background: rgba(255,255,255,0.1);
        }

        /* ============ SEARCH RESULTS ============ */
        #search-results {
            position: fixed;
            top: calc(var(--navbar-height) + 62px);
            left: 8px;
            right: 8px;
            max-height: 60vh;
            overflow-y: auto;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            z-index: 899;
            display: none;
        }

        body.dark #search-results {
            background: #2a2a2a;
        }

        #search-results.show {
            display: block;
        }

        .search-item {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        body.dark .search-item {
            border-bottom-color: rgba(255,255,255,0.05);
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:active {
            background: rgba(0,0,0,0.05);
        }

        body.dark .search-item:active {
            background: rgba(255,255,255,0.05);
        }

        .search-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* ============ FLOATING BUTTONS ============ */
        .floating-btns {
            position: fixed;
            right: 12px;
            bottom: calc(var(--bottom-bar-height) + var(--safe-area-bottom) + 16px);
            z-index: 800;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-floating {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: white;
            box-shadow: var(--shadow-md);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--text-dark);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        body.dark .btn-floating {
            background: #2a2a2a;
        }

        .btn-floating:active {
            transform: scale(0.95);
            box-shadow: var(--shadow-sm);
        }

        .btn-floating.primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-floating.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,123,255,0.4); }
            50% { box-shadow: 0 0 0 12px rgba(0,123,255,0); }
        }

        /* ============ BOTTOM BAR ============ */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-bar-height) + var(--safe-area-bottom));
            padding-bottom: var(--safe-area-bottom);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding-left: 8px;
            padding-right: 8px;
        }

        body.dark .bottom-bar {
            background: rgba(26, 26, 26, 0.95);
        }

        .bottom-btn {
            flex: 1;
            height: 48px;
            border: none;
            background: transparent;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            font-size: 0.7rem;
            color: #666;
            transition: background 0.2s, color 0.2s;
            max-width: 80px;
        }

        .bottom-btn i {
            font-size: 1.3rem;
        }

        .bottom-btn:active {
            background: rgba(0,0,0,0.05);
        }

        body.dark .bottom-btn {
            color: #999;
        }

        body.dark .bottom-btn:active {
            background: rgba(255,255,255,0.05);
        }

        .bottom-btn.active {
            color: var(--primary-color);
        }

        /* ============ BOTTOM SHEET ============ */
        .bottom-sheet-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1100;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .bottom-sheet-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .bottom-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-height: 85vh;
            background: white;
            border-radius: 24px 24px 0 0;
            z-index: 1101;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        body.dark .bottom-sheet {
            background: #1a1a1a;
        }

        .bottom-sheet.show {
            transform: translateY(0);
        }

        .bottom-sheet-handle {
            width: 40px;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin: 12px auto 8px;
        }

        .bottom-sheet-header {
            padding: 8px 20px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        body.dark .bottom-sheet-header {
            border-bottom-color: rgba(255,255,255,0.05);
        }

        .bottom-sheet-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .bottom-sheet-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
        }

        /* ============ MENU ITEMS ============ */
        .menu-item {
            padding: 14px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            color: var(--text-dark);
        }

        body.dark .menu-item {
            border-bottom-color: rgba(255,255,255,0.05);
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:active {
            background: rgba(0,0,0,0.02);
        }

        .menu-item i {
            width: 24px;
            text-align: center;
            color: var(--primary-color);
        }

        /* ============ REPORT MODAL ============ */
        .report-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1200;
            display: none;
            align-items: flex-end;
            padding: 0;
        }

        .report-modal.show {
            display: flex;
        }

        .report-content {
            width: 100%;
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 20px;
            padding-bottom: calc(20px + var(--safe-area-bottom));
            animation: slideUp 0.3s ease;
        }

        body.dark .report-content {
            background: #1a1a1a;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .report-type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 16px 0;
        }

        .report-type-btn {
            padding: 20px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 16px;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        body.dark .report-type-btn {
            background: #2a2a2a;
            border-color: rgba(255,255,255,0.1);
        }

        .report-type-btn:active {
            transform: scale(0.97);
            border-color: var(--primary-color);
        }

        .report-type-btn i {
            font-size: 2rem;
        }

        /* ============ MODE CONDUITE ============ */
        body.driving-mode .navbar-top,
        body.driving-mode .bottom-bar,
        body.driving-mode #search-compact {
            display: none;
        }

        body.driving-mode .floating-btns {
            bottom: 20px;
        }

        /* ============ ETA BOX ============ */
        .eta-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.85);
            color: white;
            padding: 12px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1050;
            box-shadow: var(--shadow-md);
            display: none;
            text-align: center;
            line-height: 1.4;
        }

        .eta-box.show {
            display: block;
        }

        /* ============ RESPONSIVE ============ */
        @media (min-width: 768px) {
            #search-compact {
                left: 50%;
                transform: translateX(-50%);
                max-width: 500px;
            }
            
            #search-results {
                left: 50%;
                transform: translateX(-50%);
                max-width: 500px;
            }
        }

        /* ============ UTILITIES ============ */
        .d-none { display: none !important; }
        .text-muted { color: #6c757d !important; }
        
        /* Scroll smooth pour bottom sheet */
        .bottom-sheet-content {
            -webkit-overflow-scrolling: touch;
        }

        /* Safe area iOS */
        @supports (padding: max(0px)) {
            .bottom-bar {
                padding-bottom: max(var(--safe-area-bottom), 12px);
            }
        }

        /* ============ TOGGLE SWITCH ============ */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .slider.round {
            border-radius: 26px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        body.dark .slider {
            background-color: #555;
        }

        body.dark input:checked + .slider {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- MAP -->
    <div id="map"></div>

    <!-- TOP BAR -->
    <nav class="navbar-top">
        <div class="navbar-brand">
            <i class="fa-solid fa-car-side"></i>
            <span>YATRAFFIC</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button id="trafficBtn" class="btn btn-icon" title="Trafic à ma position" style="position:relative;">
                <i class="fa-solid fa-traffic-light"></i>
            </button>
            <button id="notifBtn" class="btn btn-icon" title="Notifications" style="position:relative;">
                <i class="fa-solid fa-bell"></i>
                <span id="notif-badge" class="badge bg-danger" style="margin-left:6px; font-size:0.8rem;">0</span>
            </button>
            <button id="stopNavBtn" class="btn btn-danger btn-sm d-none" style="border-radius: 20px; padding: 6px 16px;">
                <i class="fa-solid fa-xmark"></i> Stop
            </button>
        </div>
    </nav>

    <!-- SEARCH COMPACT -->
    <div id="search-compact">
        <button class="btn-icon" id="menuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <input type="text" id="search-input" placeholder="Où voulez-vous aller ?" />
        <button class="btn-icon" id="micBtn">
            <i class="fa-solid fa-microphone"></i>
        </button>
    </div>

    <!-- SEARCH RESULTS -->
    <div id="search-results"></div>

    <!-- FLOATING BUTTONS -->
    <div class="floating-btns">
        <button class="btn-floating primary" id="recenterBtn" title="Recentrer">
            <i class="fa-solid fa-location-crosshairs"></i>
        </button>
        <button class="btn-floating" id="layersBtn" title="Calques">
            <i class="fa-solid fa-layer-group"></i>
        </button>
    </div>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <button class="bottom-btn" id="reportBtn">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <span>Signaler</span>
        </button>
        <button class="bottom-btn" id="filtersBtn">
            <i class="fa-solid fa-filter"></i>
            <span>Filtres</span>
        </button>
        <button class="bottom-btn" id="navBtn">
            <i class="fa-solid fa-route"></i>
            <span>Naviguer</span>
        </button>
        <button class="bottom-btn" id="moreBtn">
            <i class="fa-solid fa-ellipsis-h"></i>
            <span>Plus</span>
        </button>
    </div>

    <!-- BOTTOM SHEET OVERLAY -->
    <div class="bottom-sheet-overlay" id="bottomSheetOverlay"></div>

    <!-- BOTTOM SHEET -->
    <div class="bottom-sheet" id="bottomSheet">
        <div class="bottom-sheet-handle"></div>
        <div class="bottom-sheet-header">
            <h3 class="bottom-sheet-title" id="sheetTitle">Menu</h3>
        </div>
        <div class="bottom-sheet-content" id="sheetContent">
            <!-- Contenu dynamique -->
        </div>
    </div>

    <!-- REPORT MODAL -->
    <div class="report-modal" id="reportModal">
        <div class="report-content">
            <h4 style="margin-bottom: 8px;">Signaler un événement</h4>
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 16px;">Que voulez-vous signaler ?</p>
            
            <div class="report-type-grid">
                <button class="report-type-btn" onclick="selectReportType('accident')">
                    <i class="fa-solid fa-car-burst" style="color: #dc3545;"></i>
                    <span>Accident</span>
                </button>
                <button class="report-type-btn" onclick="selectReportType('embouteillage')">
                    <i class="fa-solid fa-traffic-light" style="color: #ffc107;"></i>
                    <span>Embouteillage</span>
                </button>
                <button class="report-type-btn" onclick="selectReportType('police')">
                    <i class="fa-solid fa-shield-halved" style="color: #6c757d;"></i>
                    <span>Police</span>
                </button>
                <button class="report-type-btn" onclick="selectReportType('danger')">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #fd7e14;"></i>
                    <span>Danger</span>
                </button>
            </div>

            <button class="btn btn-secondary w-100 mt-3" onclick="closeReportModal()" style="padding: 12px; border-radius: 12px; border: none;">
                Annuler
            </button>
        </div>
    </div>

    <!-- EVENT MODAL (pour saisir description et confirmer signalement) -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeName">Signalement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label for="eventDescription" class="form-label">Description (optionnelle)</label>
                        <textarea id="eventDescription" class="form-control" rows="3" maxlength="200" placeholder="Donnez quelques détails (ex: couleur, direction, gravité)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="confirmEventBtn" class="btn btn-primary">Confirmer le signalement</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ETA BOX -->
    <div class="eta-box" id="etaBox"></div>

    <!-- NOTIFICATION BOX (affichage temporaire des nouvelles alertes) -->
    <div id="notification-box-container" style="position: fixed; top: 70px; right: 16px; z-index: 9999; pointer-events: none;">
        <div id="notification-box" style="display:none; background: white; padding: 12px 16px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); max-height: 60vh; overflow-y: auto; min-width: 280px; max-width: 400px; font-size: 0.9rem; line-height: 1.4; border: 1px solid #ddd; pointer-events: auto;"></div>
    </div>

    <!-- TOAST: Demande accès localisation -->
    <div id="location-perm-toast" style="display:none; position: fixed; left: 16px; bottom: 90px; z-index:2100; background: #fff; border-radius: 10px; box-shadow: 0 6px 18px rgba(0,0,0,0.12); padding: 12px 14px; max-width: 320px;">
        <div style="font-weight:600; margin-bottom:6px;">Autoriser la localisation</div>
        <div style="font-size:0.93rem; color: #333;">Pour utiliser toutes les fonctions (partage de position, signalements), autorisez l'accès à votre position.</div>
        <div style="display:flex; gap:8px; margin-top:10px;">
            <button id="grantLocationBtn" class="btn btn-primary btn-sm" style="flex:1;">Autoriser</button>
            <button id="dismissLocationBtn" class="btn btn-outline-secondary btn-sm" style="flex:1;">Ignorer</button>
        </div>
    </div>

    <!-- NOTIFICATION -->
    <audio id="notif-sound">
        <source src="/sounds/notification.mp3" type="audio/mpeg">
    </audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/vendor/leaflet/leaflet.js"></script>
    <script src="/js/TrafficFlowVisualizer.js"></script>
    <script>
        window.currentUserId = @json($user->id ?? null);
    </script>
    @vite(['resources/js/map.js'])
    
    <script>
        // Attendre que le DOM et map.js soient chargés
        document.addEventListener('DOMContentLoaded', function() {
            // Attendre l'initialisation de la carte depuis map.js
            const waitForMap = setInterval(function() {
                if (typeof map !== 'undefined' && map) {
                    clearInterval(waitForMap);
                    initMobileUI();
                }
            }, 100);
        });

        function initMobileUI() {
            // ============ BOTTOM SHEET LOGIC ============
            const bottomSheet = document.getElementById('bottomSheet');
            const overlay = document.getElementById('bottomSheetOverlay');
            const sheetTitle = document.getElementById('sheetTitle');
            const sheetContent = document.getElementById('sheetContent');

            function openBottomSheet(title, content) {
                sheetTitle.textContent = title;
                sheetContent.innerHTML = content;
                bottomSheet.classList.add('show');
                overlay.classList.add('show');
            }

            window.openBottomSheet = openBottomSheet;

            function closeBottomSheet() {
                bottomSheet.classList.remove('show');
                overlay.classList.remove('show');
            }

            window.closeBottomSheet = closeBottomSheet;

            overlay.addEventListener('click', closeBottomSheet);

            // Menu Button
            const menuBtn = document.getElementById('menuBtn');
            if (menuBtn) {
                menuBtn.addEventListener('click', function() {
                    const menuContent = `
                        <div class="menu-item" onclick="toggleDarkMode()">
                            <i class="fa-solid fa-moon"></i>
                            <span>Mode sombre</span>
                        </div>
                        <div class="menu-item" onclick="showHistory()">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Historique</span>
                        </div>
                        <div class="menu-item" onclick="showSaved()">
                            <i class="fa-solid fa-bookmark"></i>
                            <span>Adresses sauvegardées</span>
                        </div>
                        <div class="menu-item" onclick="showFavorites()">
                            <i class="fa-solid fa-star"></i>
                            <span>Favoris</span>
                        </div>
                        <div class="menu-item" onclick="sharePosition()">
                            <i class="fa-solid fa-share-nodes"></i>
                            <span>Partager ma position</span>
                        </div>
                        <div class="menu-item" onclick="window.location.href='/leaderboard'">
                            <i class="fa-solid fa-trophy"></i>
                            <span>Classement</span>
                        </div>
                        <div class="menu-item" onclick="showSettings()">
                            <i class="fa-solid fa-gear"></i>
                            <span>Paramètres</span>
                        </div>
                        <div class="menu-item" onclick="logout()">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Déconnexion</span>
                        </div>
                    `;
                    openBottomSheet('Menu', menuContent);
                });
            }

            // Report Modal
            const reportModal = document.getElementById('reportModal');
            const reportBtn = document.getElementById('reportBtn');
            
            if (reportBtn) {
                reportBtn.addEventListener('click', function() {
                    reportModal.classList.add('show');
                });
            }

            window.closeReportModal = function() {
                reportModal.classList.remove('show');
            };

            window.selectReportType = function(type) {
                closeReportModal();
                if (typeof openEventModal === 'function') {
                    openEventModal(type);
                }
            };

            // Navigation Button
            const navBtn = document.getElementById('navBtn');
            if (navBtn) {
                navBtn.addEventListener('click', function() {
                    if (typeof startNavigationMode === 'function') {
                        startNavigationMode();
                    } else {
                        alert('🧭 Cliquez sur la carte pour choisir votre destination');
                    }
                });
            }

            // Favoris
            const favBtn = document.getElementById('favBtn');
            if (favBtn) {
                favBtn.addEventListener('click', function() {
                    const favContent = `
                        <div id="favorites-list">
                            <p class="text-muted text-center">Chargement...</p>
                        </div>
                    `;
                    openBottomSheet('Favoris', favContent);
                    if (typeof renderFavorites === 'function') {
                        setTimeout(() => renderFavorites(), 100);
                    }
                });
            }

            // Plus
            const moreBtn = document.getElementById('moreBtn');
            if (moreBtn) {
                moreBtn.addEventListener('click', function() {
                    const moreContent = `
                        <div class="menu-item" onclick="showFavorites()">
                            <i class="fa-solid fa-star"></i>
                            <span>Favoris</span>
                        </div>
                        <div class="menu-item" onclick="showSettings()">
                            <i class="fa-solid fa-gear"></i>
                            <span>Paramètres</span>
                        </div>
                        <div class="menu-item" onclick="showHelp()">
                            <i class="fa-solid fa-circle-question"></i>
                            <span>Aide</span>
                        </div>
                        <div class="menu-item" onclick="showAbout()">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>À propos</span>
                        </div>
                    `;
                    openBottomSheet('Plus', moreContent);
                });
            }

            // Filtres Button
            const filtersBtn = document.getElementById('filtersBtn');
            if (filtersBtn) {
                filtersBtn.addEventListener('click', function() {
                    const filtersContent = `
                        <div style="padding: 8px 0;">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-car-side me-2"></i>Trafic Abidjan
                            </h6>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Plateau', 5.3391, -4.0329)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Plateau
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Cocody', 5.3698, -4.0036)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Cocody
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Yopougon', 5.3451, -4.1093)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Yopougon
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Abobo', 5.4294, -4.0089)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Abobo
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Attécoubé', 5.3071, -4.0382)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Attécoubé
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadTrafficForLocation('Marcory', 5.3163, -4.0063)" style="border-radius: 8px; padding: 8px;">
                                    <i class="fa-solid fa-map-marker-alt"></i> Marcory
                                </button>
                            </div>
                            <button class="btn btn-sm btn-outline-danger w-100 mb-4" onclick="clearTraffic()" style="border-radius: 8px;">
                                <i class="fa-solid fa-trash me-2"></i>Effacer le trafic
                            </button>

                            <!-- Traffic Info Panel -->
                            <div id="trafficInfoPanel" style="margin-bottom: 20px;">
                                <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; color: #999;">
                                    <p style="margin: 0; font-size: 0.9rem;">
                                        <i class="fa-solid fa-chart-line me-2"></i>
                                        Cliquez sur une localité pour voir les statistiques de trafic
                                    </p>
                                </div>
                            </div>

                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-traffic-light me-2"></i>Événements
                            </h6>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-traffic-light" style="color: #ffc107; font-size: 1.3rem;"></i>
                                    <span style="font-size: 1rem;">Embouteillages</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="filter-embouteillage" ${typeof filters !== 'undefined' && filters.embouteillage ? 'checked' : ''} onchange="toggleFilter('embouteillage')">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-car-burst" style="color: #dc3545; font-size: 1.3rem;"></i>
                                    <span style="font-size: 1rem;">Accidents</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="filter-accident" ${typeof filters !== 'undefined' && filters.accident ? 'checked' : ''} onchange="toggleFilter('accident')">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-shield-halved" style="color: #6c757d; font-size: 1.3rem;"></i>
                                    <span style="font-size: 1rem;">Police</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="filter-police" ${typeof filters !== 'undefined' && filters.police ? 'checked' : ''} onchange="toggleFilter('police')">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-triangle-exclamation" style="color: #fd7e14; font-size: 1.3rem;"></i>
                                    <span style="font-size: 1rem;">Dangers</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="filter-danger" ${typeof filters !== 'undefined' && filters.danger ? 'checked' : ''} onchange="toggleFilter('danger')">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    `;
                    openBottomSheet('Filtres', filtersContent);
                });
            }

            // Dark Mode Toggle
            window.toggleDarkMode = function() {
                document.body.classList.toggle('dark');
                localStorage.setItem('darkMode', document.body.classList.contains('dark'));
                if (typeof applyMapTheme === 'function') {
                    applyMapTheme();
                }
                closeBottomSheet();
            };

            // Load dark mode preference
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark');
            }

            window.logout = function() {
                const form = document.getElementById('logout-form');
                if (form) form.submit();
            };

            window.showHistory = function() {
                closeBottomSheet();
                const historyContent = `
                    <div id="history-list">
                        <p class="text-muted text-center">Chargement...</p>
                    </div>
                    <button class="btn btn-outline-secondary w-100 mt-3" id="clear-history">
                        Effacer l'historique
                    </button>
                `;
                openBottomSheet('Historique', historyContent);
                if (typeof renderHistory === 'function') {
                    setTimeout(() => renderHistory(), 100);
                }
            };

            window.showSaved = function() {
                closeBottomSheet();
                const savedContent = `
                    <div class="mb-3">
                        <select id="favoriteType" class="form-select mb-2">
                            <option value="home">Maison</option>
                            <option value="work">Travail</option>
                            <option value="school">École</option>
                        </select>
                        <button id="add-current-favorite" class="btn btn-primary w-100">
                            Ajouter adresse actuelle
                        </button>
                    </div>
                    <div id="favorites-list">
                        <p class="text-muted text-center">Chargement...</p>
                    </div>
                `;
                openBottomSheet('Adresses sauvegardées', savedContent);
                if (typeof renderFavorites === 'function') {
                    setTimeout(() => renderFavorites(), 100);
                }
            };

            window.sharePosition = function() {
                try { closeBottomSheet(); } catch(e) {}

                if (!navigator.geolocation) {
                    alert('Géolocalisation non supportée par ce navigateur.');
                    return;
                }

                // Always request a fresh GPS reading to share exact position
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        const lat = pos.coords.latitude;
                        const lon = pos.coords.longitude;
                        const link = `${location.origin}${location.pathname}?share_lat=${Number(lat).toFixed(6)}&share_lon=${Number(lon).toFixed(6)}`;
                        const shareContent = `
                            <p class="text-muted mb-2">Partagez ce lien pour montrer votre position exacte :</p>
                            <input type="text" class="form-control mb-2" value="${link}" readonly id="share-link-input" style="font-size: 0.9rem;">
                            <div style="font-size:0.88rem; color:#555; margin-bottom:8px;">Position utilisée: <strong id="share-source">GPS (précise)</strong></div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary flex-fill" onclick="copyShareLink()">
                                    <i class="fa-solid fa-copy"></i> Copier
                                </button>
                                <button class="btn btn-primary flex-fill" onclick="webShare()">
                                    <i class="fa-solid fa-share-nodes"></i> Partager
                                </button>
                            </div>
                        `;
                        try {
                            if (typeof openBottomSheet === 'function') openBottomSheet('Partager ma position', shareContent);
                            else document.body.insertAdjacentHTML('beforeend', '<div class="alert alert-info">' + link + '</div>');
                        } catch (e) {
                            console.debug('sharePosition: openBottomSheet failed', e);
                        }
                    },
                    function(err) {
                        console.warn('sharePosition geoloc error', err);
                        // Show toast asking for permission if available
                        try {
                            const toast = document.getElementById('location-perm-toast');
                            if (toast) toast.style.display = 'block';
                        } catch (e) {}
                        alert("Impossible d'obtenir la position GPS. Autorisez la géolocalisation pour partager votre position exacte.");
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            };

            window.copyShareLink = async function() {
                const input = document.getElementById('share-link-input');
                if (input) {
                    try {
                        await navigator.clipboard.writeText(input.value);
                        alert('Lien copié !');
                    } catch (e) {
                        input.select();
                        document.execCommand('copy');
                        alert('Lien copié !');
                    }
                }
            };

            window.webShare = async function() {
                const input = document.getElementById('share-link-input');
                if (input && navigator.share) {
                    try {
                        await navigator.share({
                            title: 'Ma position',
                            text: 'Voici ma position actuelle',
                            url: input.value
                        });
                    } catch (e) {
                        console.log('Partage annulé');
                    }
                } else {
                    copyShareLink();
                }
            };

            // Favoris
            window.showFavorites = function() {
                closeBottomSheet();
                const favContent = `
                    <div id="favorites-list">
                        <p class="text-muted text-center">Chargement...</p>
                    </div>
                `;
                openBottomSheet('Favoris', favContent);
                if (typeof renderFavorites === 'function') {
                    setTimeout(() => renderFavorites(), 100);
                }
            };

            // Paramètres
            window.showSettings = function() {
                closeBottomSheet();
                // Charger depuis localStorage ou utiliser la valeur actuelle de travelMode
                const travelModeValue = localStorage.getItem('travelMode') || (typeof travelMode !== 'undefined' ? travelMode : 'driving');
                const settingsContent = `
                    <div style="padding: 8px 0;">
                        <h6 class="mb-3" style="color: var(--primary-color); font-weight: 600;">Mode de déplacement</h6>
                        <div class="d-grid gap-2 mb-4">
                            <button class="btn ${travelModeValue === 'driving' ? 'btn-primary' : 'btn-outline-primary'}" onclick="changeTravelMode('driving')" style="text-align: left; padding: 14px 16px; border-radius: 12px;">
                                <i class="fa-solid fa-car me-3"></i> Voiture
                            </button>
                            <button class="btn ${travelModeValue === 'bike' ? 'btn-primary' : 'btn-outline-primary'}" onclick="changeTravelMode('bike')" style="text-align: left; padding: 14px 16px; border-radius: 12px;">
                                <i class="fa-solid fa-motorcycle me-3"></i> Moto / Vélo
                            </button>
                            <button class="btn ${travelModeValue === 'foot' ? 'btn-primary' : 'btn-outline-primary'}" onclick="changeTravelMode('foot')" style="text-align: left; padding: 14px 16px; border-radius: 12px;">
                                <i class="fa-solid fa-person-walking me-3"></i> À pied
                            </button>
                        </div>

                        <h6 class="mb-3" style="color: var(--primary-color); font-weight: 600;">Notifications</h6>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                            <div>
                                <div style="font-weight: 500;">Alertes sonores</div>
                                <small class="text-muted">Son lors des nouveaux signalements</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="sound-alerts" ${localStorage.getItem('soundAlerts') !== 'false' ? 'checked' : ''} onchange="toggleSoundAlerts(this)">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div style="font-weight: 500;">Guidage vocal</div>
                                <small class="text-muted">Instructions de navigation vocales</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="voice-guidance" ${localStorage.getItem('voiceGuidance') !== 'false' ? 'checked' : ''} onchange="toggleVoiceGuidance(this)">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                `;
                openBottomSheet('Paramètres', settingsContent);
            };

            window.changeTravelMode = function(mode) {
                if (typeof setTravelMode === 'function') {
                    setTravelMode(mode);
                    closeBottomSheet();
                    setTimeout(() => {
                        const msg = mode === 'driving' ? '🚗 Voiture' : mode === 'bike' ? '🏍️ Moto/Vélo' : '🚶 À pied';
                        alert(`Mode de déplacement : ${msg}`);
                    }, 300);
                }
            };

            window.toggleSoundAlerts = function(checkbox) {
                const enabled = checkbox.checked;
                // Sauvegarder en localStorage (bool -> string)
                localStorage.setItem('soundAlerts', enabled ? 'true' : 'false');
                console.log('Alertes sonores:', enabled);
            };

            window.toggleVoiceGuidance = function(checkbox) {
                const enabled = checkbox.checked;
                // Mettre à jour la variable globale
                window.speechEnabled = enabled;
                // Sauvegarder en localStorage (bool -> string)
                localStorage.setItem('voiceGuidance', enabled ? 'true' : 'false');
                console.log('Guidage vocal:', enabled);
            };

            // Aide
            window.showHelp = function() {
                closeBottomSheet();
                const helpContent = `
                    <div style="padding: 8px 0;">
                        <div class="mb-4">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-route me-2"></i>Navigation
                            </h6>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-dark);">
                                Appuyez sur <strong>Naviguer</strong> puis cliquez sur la carte pour définir votre destination. 
                                Le guidage GPS démarre automatiquement avec instructions vocales.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-exclamation-triangle me-2"></i>Signalements
                            </h6>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-dark);">
                                Signalez des événements en temps réel : accidents, embouteillages, police, dangers. 
                                Votez sur les signalements existants pour valider leur fiabilité.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-filter me-2"></i>Filtres
                            </h6>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-dark);">
                                Personnalisez les types d'événements affichés sur la carte. 
                                Désactivez les catégories qui ne vous intéressent pas.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-star me-2"></i>Favoris
                            </h6>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-dark);">
                                Enregistrez vos adresses fréquentes (maison, travail, école) 
                                pour y accéder rapidement.
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 12px;">
                                <i class="fa-solid fa-microphone me-2"></i>Recherche vocale
                            </h6>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-dark);">
                                Utilisez l'icône micro dans la barre de recherche pour 
                                chercher une destination à la voix.
                            </p>
                        </div>
                    </div>
                `;
                openBottomSheet('Aide', helpContent);
            };

            // À propos
            window.showAbout = function() {
                closeBottomSheet();
                const aboutContent = `
                    <div style="padding: 8px 0; text-align: center;">
                        <div style="margin-bottom: 24px;">
                            <i class="fa-solid fa-car-side" style="font-size: 4rem; color: var(--primary-color);"></i>
                            <h4 style="margin-top: 16px; font-weight: 700; color: var(--text-dark);">NaviWaze</h4>
                            <p class="text-muted" style="font-size: 0.9rem;">Version 1.0.0</p>
                        </div>

                        <div style="text-align: left; padding: 0 8px;">
                            <p style="font-size: 0.95rem; line-height: 1.7; color: var(--text-dark); margin-bottom: 20px;">
                                NaviWaze est une application de navigation communautaire qui vous aide à 
                                éviter les embouteillages, accidents et contrôles de police grâce aux 
                                signalements en temps réel des autres conducteurs.
                            </p>

                            <div class="mb-3">
                                <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px;">
                                    <i class="fa-solid fa-users me-2"></i>Communauté
                                </h6>
                                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-dark);">
                                    Plus nous sommes nombreux à partager les informations routières, 
                                    plus les trajets sont sûrs et rapides pour tous.
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px;">
                                    <i class="fa-solid fa-shield-halved me-2"></i>Confidentialité
                                </h6>
                                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-dark);">
                                    Vos données de localisation ne sont utilisées que pour la navigation 
                                    et ne sont jamais partagées avec des tiers.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h6 style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px;">
                                    <i class="fa-solid fa-code me-2"></i>Technologies
                                </h6>
                                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-dark);">
                                    • OpenStreetMap & Leaflet<br>
                                    • OSRM Routing Engine<br>
                                    • Laravel & Bootstrap<br>
                                    • Nominatim Geocoding
                                </p>
                            </div>

                            <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);">
                                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 8px;">
                                    Développé avec ❤️ pour la communauté
                                </p>
                                <p class="text-muted" style="font-size: 0.8rem;">
                                    © 2024 NaviWaze. Tous droits réservés.
                                </p>
                            </div>
                        </div>
                    </div>
                `;
                openBottomSheet('À propos', aboutContent);
            };

            // Recherche vocale
            const micBtn = document.getElementById('micBtn');
            if (micBtn) {
                micBtn.addEventListener('click', function() {
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) {
                        const query = prompt('Recherche vocale non disponible. Tapez votre recherche :');
                        if (query) {
                            document.getElementById('search-input').value = query;
                        }
                        return;
                    }
                    
                    const recognition = new SR();
                    recognition.lang = 'fr-FR';
                    recognition.start();
                    
                    micBtn.style.color = 'red';
                    
                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        document.getElementById('search-input').value = transcript;
                        micBtn.style.color = '';
                    };
                    
                    recognition.onerror = function() {
                        micBtn.style.color = '';
                    };
                    
                    recognition.onend = function() {
                        micBtn.style.color = '';
                    };
                });
            }

            // Hide search on drag
            const searchCompact = document.getElementById('search-compact');
            let dragTimeout;
            
            if (map && searchCompact) {
                map.on('dragstart', function() {
                    searchCompact.classList.add('hidden');
                });

                map.on('dragend', function() {
                    clearTimeout(dragTimeout);
                    dragTimeout = setTimeout(() => {
                        searchCompact.classList.remove('hidden');
                    }, 1500);
                });
            }

            // Force map resize
            setTimeout(() => {
                if (map && map.invalidateSize) {
                    map.invalidateSize();
                }
            }, 300);
        }
    </script>
    <script>
        // Handler du bouton notification est géré par map.js
        // Pas d'ajout d'event listener ici pour éviter la duplication
    </script>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    <script>
        // ============ TRAFFIC VISUALIZATION INTEGRATION ============
        let trafficVizInstance = null;

        // Initialiser le visualiseur de trafic une fois la carte chargée
        document.addEventListener('DOMContentLoaded', function() {
            const waitForMap = setInterval(function() {
                if (typeof map !== 'undefined' && map) {
                    clearInterval(waitForMap);
                    
                    // Créer l'instance du visualiseur
                    trafficVizInstance = new TrafficFlowVisualizer(map);
                    
                    console.log('✅ TrafficFlowVisualizer initialisé pour Abidjan');
                }
            }, 100);
        });

        /**
         * Charge et affiche le trafic pour une localité donnée
         */
        window.loadTrafficForLocation = function(locationName, lat, lon) {
            if (!trafficVizInstance) {
                console.error('TrafficFlowVisualizer non initialisé');
                return;
            }

            console.log(`📍 Chargement trafic pour ${locationName} (${lat}, ${lon})`);

            // Afficher un message de chargement
            const notifBox = document.getElementById('notification-box');
            if (notifBox) {
                notifBox.style.display = 'block';
                notifBox.innerHTML = `<div style="text-align: center; padding: 8px;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement trafic ${locationName}...</div>`;
            }

            // Charger le trafic
            trafficVizInstance.loadTraffic(lat, lon, (isLoading) => {
                if (!isLoading && notifBox) {
                    notifBox.innerHTML = `<div style="text-align: center; padding: 8px; color: #00AA00;"><i class="fa-solid fa-check"></i> Trafic de ${locationName} affiché</div>`;
                    setTimeout(() => {
                        notifBox.style.display = 'none';
                    }, 2000);
                }
            });

            // Centrer la carte sur la localité
            if (typeof map !== 'undefined' && map) {
                map.setView([lat, lon], 13);
            }

            // Fermer la feuille inférieure
            try {
                closeBottomSheet();
            } catch(e) {}
        };

        /**
         * Efface tous les segments de trafic
         */
        window.clearTraffic = function() {
            if (trafficVizInstance) {
                trafficVizInstance.clear();
                console.log('🗑️ Trafic effacé');
                
                const notifBox = document.getElementById('notification-box');
                if (notifBox) {
                    notifBox.style.display = 'block';
                    notifBox.innerHTML = `<div style="text-align: center; padding: 8px; color: #666;"><i class="fa-solid fa-check"></i> Trafic effacé</div>`;
                    setTimeout(() => {
                        notifBox.style.display = 'none';
                    }, 2000);
                }
            }
        };

        /**
         * Affiche la légende du trafic
         */
        window.showTrafficLegend = function() {
            const legendContent = `
                <div style="padding: 8px 0;">
                    <div class="mb-3" style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 20px; height: 20px; background: #00AA00; border-radius: 4px;"></div>
                        <div>
                            <strong>Vert (Fluide)</strong>
                            <p style="font-size: 0.85rem; color: #666; margin: 0;">Vitesse > 80% de la normale</p>
                        </div>
                    </div>
                    <div class="mb-3" style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 20px; height: 20px; background: #FFA500; border-radius: 4px;"></div>
                        <div>
                            <strong>Orange (Congestion modérée)</strong>
                            <p style="font-size: 0.85rem; color: #666; margin: 0;">Vitesse 50-80% de la normale</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 20px; height: 20px; background: #FF0000; border-radius: 4px;"></div>
                        <div>
                            <strong>Rouge (Congestion sévère)</strong>
                            <p style="font-size: 0.85rem; color: #666; margin: 0;">Vitesse < 50% de la normale</p>
                        </div>
                    </div>
                </div>
            `;
            openBottomSheet('Légende du trafic', legendContent);
        };

        /**
         * Charge et affiche le trafic à la position exacte de l'utilisateur
         */
        window.loadTrafficForUserLocation = function() {
            if (!navigator.geolocation) {
                alert('Géolocalisation non supportée par ce navigateur.');
                return;
            }

            if (!trafficVizInstance) {
                console.error('TrafficFlowVisualizer non initialisé');
                return;
            }

            // Afficher un message de chargement
            const notifBox = document.getElementById('notification-box');
            if (notifBox) {
                notifBox.style.display = 'block';
                notifBox.innerHTML = `<div style="text-align: center; padding: 8px;"><i class="fa-solid fa-spinner fa-spin"></i> Détermination de votre position...</div>`;
            }

            // Demander une nouvelle position GPS exacte
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;

                    console.log(`📍 Chargement trafic pour ma position (${lat.toFixed(4)}, ${lon.toFixed(4)})`);

                    if (notifBox) {
                        notifBox.innerHTML = `<div style="text-align: center; padding: 8px;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement trafic à ma position...</div>`;
                    }

                    // Charger le trafic
                    trafficVizInstance.loadTraffic(lat, lon, (isLoading) => {
                        if (!isLoading && notifBox) {
                            notifBox.innerHTML = `<div style="text-align: center; padding: 8px; color: #00AA00;"><i class="fa-solid fa-check"></i> Trafic affiché à ma position</div>`;
                            setTimeout(() => {
                                notifBox.style.display = 'none';
                            }, 2000);
                        }
                    });

                    // Centrer la carte sur la position utilisateur
                    if (typeof map !== 'undefined' && map) {
                        map.setView([lat, lon], 13);
                    }
                },
                function(err) {
                    console.warn('Erreur géolocalisation:', err);
                    if (notifBox) {
                        notifBox.style.display = 'block';
                        notifBox.innerHTML = `<div style="text-align: center; padding: 8px; color: #dc3545;"><i class="fa-solid fa-exclamation-circle"></i> Impossible d'obtenir votre position</div>`;
                        setTimeout(() => {
                            notifBox.style.display = 'none';
                        }, 3000);
                    }
                    alert('Impossible d\'obtenir votre position. Vérifiez les permissions de géolocalisation.');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        };

        // Attacher l'événement click au bouton trafic
        document.addEventListener('DOMContentLoaded', function() {
            const trafficBtn = document.getElementById('trafficBtn');
            if (trafficBtn) {
                trafficBtn.addEventListener('click', function() {
                    loadTrafficForUserLocation();
                });
            }
        });
    </script>
</body>
</html>