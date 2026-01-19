/**
 * Configuration des localités Abidjan pour la visualisation du trafic
 * Définit les coordonnées et métadonnées de chaque localité
 */

const ABIDJAN_LOCATIONS = {
    Plateau: {
        name: "Plateau",
        description: "Centre-ville (affaires)",
        latitude: 5.3391,
        longitude: -4.0329,
        icon: "🏢",
        type: "central",
    },
    Cocody: {
        name: "Cocody",
        description: "Nord-est (résidentiel)",
        latitude: 5.3698,
        longitude: -4.0036,
        icon: "🏠",
        type: "residential",
    },
    Yopougon: {
        name: "Yopougon",
        description: "Ouest (résidentiel)",
        latitude: 5.3451,
        longitude: -4.1093,
        icon: "🏘️",
        type: "residential",
    },
    Abobo: {
        name: "Abobo",
        description: "Nord (résidentiel/commerce)",
        latitude: 5.4294,
        longitude: -4.0089,
        icon: "🏪",
        type: "mixed",
    },
    Attécoubé: {
        name: "Attécoubé",
        description: "Sud (portuaire)",
        latitude: 5.3071,
        longitude: -4.0382,
        icon: "⚓",
        type: "port",
    },
    Marcory: {
        name: "Marcory",
        description: "Sud-est (résidentiel)",
        latitude: 5.3163,
        longitude: -4.0063,
        icon: "🏡",
        type: "residential",
    },
};

/**
 * Obtenir une localité par nom
 */
function getLocation(name) {
    return ABIDJAN_LOCATIONS[name] || null;
}

/**
 * Obtenir toutes les localités
 */
function getAllLocations() {
    return Object.values(ABIDJAN_LOCATIONS);
}

/**
 * Obtenir localités par type
 */
function getLocationsByType(type) {
    return Object.values(ABIDJAN_LOCATIONS).filter((loc) => loc.type === type);
}

/**
 * Rechercher localités par texte
 */
function searchLocations(query) {
    const q = query.toLowerCase();
    return Object.values(ABIDJAN_LOCATIONS).filter(
        (loc) =>
            loc.name.toLowerCase().includes(q) ||
            loc.description.toLowerCase().includes(q)
    );
}
