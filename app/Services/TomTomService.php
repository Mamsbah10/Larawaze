<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TomTomService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.tomtom.com';

    public function __construct()
    {
        $this->apiKey = config('services.tomtom.api_key');
        $this->baseUrl = config('services.tomtom.base_url', 'https://api.tomtom.com');
    }

    /**
     * Get the base URL
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the API key for frontend use
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get traffic information for a location
     * Uses real road data from OpenStreetMap + realistic traffic simulation
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getTrafficFlow(float $latitude, float $longitude): array
    {
        try {
            // For Abidjan area, ALWAYS use mock data as it has ALL neighborhoods
            // OSM API is too unreliable and doesn't cover all areas like Yopougon well
            $mockData = $this->generateMockTrafficData($latitude, $longitude);
            
            // Try to also get OSM roads and combine them
            $osmRoads = $this->getRealRoadsFromOSM($latitude, $longitude);
            if ($osmRoads && count($osmRoads) > 20) {
                // Combine OSM data with mock data for maximum coverage
                $combined = array_merge($mockData['flowSegmentData'], $osmRoads);
                Log::debug('Combined traffic data', ['mock' => count($mockData['flowSegmentData']), 'osm' => count($osmRoads)]);
                return [
                    'flowSegmentData' => $combined,
                    'source' => 'combined_osm_mock',
                    'timestamp' => now()->toIso8601String(),
                    'note' => 'Combined real roads (OSM) and defined Abidjan roads (mock) for complete coverage'
                ];
            }
            
            // Otherwise use mock data alone (complete and reliable for Abidjan)
            Log::debug('Using mock traffic data for Abidjan', ['count' => count($mockData['flowSegmentData'])]);
            return $mockData;
        } catch (\Exception $e) {
            Log::error('Error getting traffic flow: ' . $e->getMessage());
            return $this->generateMockTrafficData($latitude, $longitude);
        }
    }

    /**
     * Fetch real road data from OpenStreetMap Overpass API
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function getRealRoadsFromOSM(float $latitude, float $longitude): array
    {
        try {
            // Define bounds (approximately 20km - pour couvrir toute Abidjan)
            $south = $latitude - 0.15;
            $west = $longitude - 0.15;
            $north = $latitude + 0.15;
            $east = $longitude + 0.15;

            // Query major roads from OSM (using Overpass API)
            // Simplified query to reduce server load
            $overpassQuery = <<<EOQ
[bbox:$south,$west,$north,$east][timeout:30];
(
  way["highway"~"motorway|trunk|primary|secondary"];
);
out geom;
EOQ;

            // Try multiple servers if first fails
            $overpassServers = [
                'https://overpass-api.de/api/interpreter',
                'https://overpass.kumi.systems/api/interpreter',
            ];

            foreach ($overpassServers as $server) {
                try {
                    // Use raw curl POST instead of Laravel Http due to encoding issues
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $server);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=' . urlencode($overpassQuery));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode == 200 && $response) {
                        $roads = $this->parseOverpassXML($response, $latitude, $longitude);
                        if (count($roads) > 0) {
                            Log::info("✓ Real roads from Overpass: " . count($roads) . " roads for ($latitude, $longitude)");
                            return $roads;
                        }
                    }
                    
                    if ($error) {
                        Log::debug("Overpass server $server error: $error");
                    } else {
                        Log::debug("Overpass server $server returned HTTP $httpCode");
                    }
                } catch (\Exception $e) {
                    Log::debug("Overpass server $server exception: " . $e->getMessage());
                    continue;
                }
            }

            Log::warning('All Overpass servers failed - using mock data');
            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching OSM roads: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse Overpass XML response and convert to traffic segments
     * @param string $xml
     * @param float $centerLat
     * @param float $centerLon
     * @return array
     */
    private function parseOverpassXML(string $xml, float $centerLat, float $centerLon): array
    {
        try {
            $segments = [];
            $doc = new \DOMDocument();
            
            if (!@$doc->loadXML($xml)) {
                Log::warning('Failed to parse Overpass XML');
                return [];
            }

            $ways = $doc->getElementsByTagName('way');
            $roadsCount = 0;

            foreach ($ways as $way) {
                $nodes = $way->getElementsByTagName('nd');
                $coords = [];

                // Extract coordinates from nd elements
                foreach ($nodes as $node) {
                    if ($node->hasAttribute('lat') && $node->hasAttribute('lon')) {
                        $coords[] = [
                            (float)$node->getAttribute('lat'),
                            (float)$node->getAttribute('lon')
                        ];
                    }
                }

                // If no inline coordinates, try to find them from node references
                if (count($coords) < 2) {
                    foreach ($nodes as $node) {
                        if ($node->hasAttribute('ref')) {
                            $nodeRef = $node->getAttribute('ref');
                            $nodeElem = $doc->getElementById($nodeRef);
                            if ($nodeElem && $nodeElem->hasAttribute('lat') && $nodeElem->hasAttribute('lon')) {
                                $coords[] = [
                                    (float)$nodeElem->getAttribute('lat'),
                                    (float)$nodeElem->getAttribute('lon')
                                ];
                            }
                        }
                    }
                }

                if (count($coords) < 2) {
                    continue;
                }

                // Get road type/name
                $name = 'Route';
                $roadType = 'residential';
                $tags = $way->getElementsByTagName('tag');

                foreach ($tags as $tag) {
                    if ($tag->getAttribute('k') === 'name') {
                        $name = $tag->getAttribute('v');
                    }
                    if ($tag->getAttribute('k') === 'highway') {
                        $roadType = $tag->getAttribute('v');
                    }
                }

                // Define traffic profile based on road type and time
                $trafficProfile = $this->getTrafficProfile($roadType, $centerLat, $centerLon);

                $segments[] = [
                    'name' => $name ?: $roadType,
                    'currentSpeed' => $trafficProfile['currentSpeed'],
                    'freeFlowSpeed' => $trafficProfile['freeFlowSpeed'],
                    'currentTravelTime' => $trafficProfile['travelTime'],
                    'freeFlowTravelTime' => $trafficProfile['freeTravelTime'],
                    'coordinates' => $coords,
                    'roadType' => $roadType,
                    'congestion' => (int)(100 * (1 - ($trafficProfile['currentSpeed'] / $trafficProfile['freeFlowSpeed'])))
                ];

                $roadsCount++;
                if ($roadsCount >= 50) break; // Limit to 50 roads for larger coverage
            }

            Log::info("Parsed $roadsCount real roads from Overpass for ($centerLat, $centerLon)");
            return $segments;
        } catch (\Exception $e) {
            Log::error('Error parsing Overpass response: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate realistic traffic profile based on road type and time
     * @param string $roadType
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function getTrafficProfile(string $roadType, float $latitude, float $longitude): array
    {
        $hour = (int)date('H');
        $dayOfWeek = date('N'); // 1=Monday, 7=Sunday

        // Base speeds by road type
        $baseProfiles = [
            'motorway' => ['speed' => 100, 'freeSpeed' => 130],
            'trunk' => ['speed' => 80, 'freeSpeed' => 100],
            'primary' => ['speed' => 60, 'freeSpeed' => 80],
            'secondary' => ['speed' => 50, 'freeSpeed' => 70],
            'tertiary' => ['speed' => 40, 'freeSpeed' => 60],
            'residential' => ['speed' => 30, 'freeSpeed' => 50],
        ];

        $profile = $baseProfiles[$roadType] ?? $baseProfiles['tertiary'];

        // Apply time-based congestion patterns
        if (($hour >= 7 && $hour <= 9) || ($hour >= 16 && $hour <= 19)) {
            // Peak hours: 30% reduction in speed
            $profile['speed'] = (int)($profile['speed'] * 0.7);
        } elseif ($hour >= 10 && $hour < 16) {
            // Off-peak: 10% reduction
            $profile['speed'] = (int)($profile['speed'] * 0.9);
        }

        // Weekend adjustment
        if ($dayOfWeek >= 6) { // Saturday, Sunday
            $profile['speed'] = (int)($profile['speed'] * 0.95);
        }

        // Add BEAUCOUP de variation réaliste (±25% au lieu de ±10%)
        // Cela crée un mélange de trafic fluide, modéré et sévère
        $variation = rand(70, 150) / 100;  // Augmenté de 90-110 à 70-150
        $profile['speed'] = max(5, (int)($profile['speed'] * $variation));

        return [
            'currentSpeed' => $profile['speed'],
            'freeFlowSpeed' => $profile['freeSpeed'],
            'travelTime' => max(5, 30 / ($profile['speed'] / $profile['freeSpeed'])),
            'freeTravelTime' => 15
        ];
    }

    /**
     * Generate mock traffic flow data for visualization purposes
     * Creates realistic traffic segments around a location based on Abidjan major roads
     * Uses location-based seeding for consistent but varied roads
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function generateMockTrafficData(float $latitude, float $longitude): array
    {
        // Use location to seed random generation - same location = same roads
        $seed = (int)(($latitude * 1000 + $longitude * 1000) % 1000000);
        mt_srand($seed);

        // Define major Abidjan roads with realistic coordinates
        $abidjanRoads = [
            // Plateau area
            [
                'name' => 'Rue du Général de Gaulle',
                'startLat' => 5.3520, 'startLon' => -4.0208,
                'endLat' => 5.3680, 'endLon' => -3.9950,
                'type' => 'primary'
            ],
            [
                'name' => 'Avenue Marchand',
                'startLat' => 5.3500, 'startLon' => -4.0050,
                'endLat' => 5.3600, 'endLon' => -3.9900,
                'type' => 'primary'
            ],
            [
                'name' => 'Boulevard de la République',
                'startLat' => 5.3400, 'startLon' => -4.0150,
                'endLat' => 5.3600, 'endLon' => -4.0050,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue Prince',
                'startLat' => 5.3350, 'startLon' => -4.0200,
                'endLat' => 5.3500, 'endLon' => -4.0000,
                'type' => 'secondary'
            ],
            // Cocody area
            [
                'name' => 'Avenue de la Paix',
                'startLat' => 5.3800, 'startLon' => -3.9850,
                'endLat' => 5.3900, 'endLon' => -3.9700,
                'type' => 'primary'
            ],
            [
                'name' => 'Boulevard de Marseille',
                'startLat' => 5.3750, 'startLon' => -3.9900,
                'endLat' => 5.3850, 'endLon' => -3.9750,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue d\'Etoile',
                'startLat' => 5.3900, 'startLon' => -3.9650,
                'endLat' => 5.4050, 'endLon' => -3.9500,
                'type' => 'secondary'
            ],
            // Yopougon area (EXPANDED - pour couvrir toute la zone)
            [
                'name' => 'Boulevard Giscard d\'Estaing',
                'startLat' => 5.3200, 'startLon' => -4.0500,
                'endLat' => 5.3400, 'endLon' => -4.0200,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue Vizioz',
                'startLat' => 5.3100, 'startLon' => -4.0400,
                'endLat' => 5.3300, 'endLon' => -4.0300,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Boulevard Gueladio Diallo',
                'startLat' => 5.3000, 'startLon' => -4.0600,
                'endLat' => 5.3200, 'endLon' => -4.0400,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue de Yopougon Nord',
                'startLat' => 5.3400, 'startLon' => -4.0800,
                'endLat' => 5.3550, 'endLon' => -4.0600,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Boulevard de Yopougon Centre',
                'startLat' => 5.3250, 'startLon' => -4.0900,
                'endLat' => 5.3450, 'endLon' => -4.0700,
                'type' => 'secondary'
            ],
            [
                'name' => 'Avenue de Yopougon Sud',
                'startLat' => 5.3050, 'startLon' => -4.0850,
                'endLat' => 5.3250, 'endLon' => -4.0650,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue de Yopougon Est',
                'startLat' => 5.3200, 'startLon' => -4.0700,
                'endLat' => 5.3400, 'endLon' => -4.0500,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Boulevard de Yopougon Ouest',
                'startLat' => 5.3100, 'startLon' => -4.1000,
                'endLat' => 5.3300, 'endLon' => -4.0800,
                'type' => 'secondary'
            ],
            // Abobo area
            [
                'name' => 'Boulevard du Gendarme Beuze',
                'startLat' => 5.4200, 'startLon' => -4.0100,
                'endLat' => 5.4400, 'endLon' => -3.9900,
                'type' => 'secondary'
            ],
            [
                'name' => 'Boulevard de Abobo',
                'startLat' => 5.4100, 'startLon' => -3.9950,
                'endLat' => 5.4300, 'endLon' => -3.9750,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Rue d\'Abobo',
                'startLat' => 5.4000, 'startLon' => -4.0000,
                'endLat' => 5.4200, 'endLon' => -3.9800,
                'type' => 'tertiary'
            ],
            // Port/Treichville area
            [
                'name' => 'Rue du Commerce',
                'startLat' => 5.3300, 'startLon' => -4.0400,
                'endLat' => 5.3500, 'endLon' => -4.0250,
                'type' => 'secondary'
            ],
            [
                'name' => 'Boulevard du Port',
                'startLat' => 5.3250, 'startLon' => -4.0350,
                'endLat' => 5.3450, 'endLon' => -4.0150,
                'type' => 'primary'
            ],
            [
                'name' => 'Avenue Lagune Ebrié',
                'startLat' => 5.3150, 'startLon' => -4.0400,
                'endLat' => 5.3350, 'endLon' => -4.0200,
                'type' => 'secondary'
            ],
            // Attécoubé area
            [
                'name' => 'Rue d\'Attécoubé',
                'startLat' => 5.3050, 'startLon' => -4.0250,
                'endLat' => 5.3200, 'endLon' => -4.0050,
                'type' => 'secondary'
            ],
            [
                'name' => 'Avenue Nanan',
                'startLat' => 5.3000, 'startLon' => -4.0300,
                'endLat' => 5.3150, 'endLon' => -4.0100,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Boulevard d\'Attécoubé',
                'startLat' => 5.3100, 'startLon' => -4.0500,
                'endLat' => 5.3300, 'endLon' => -4.0300,
                'type' => 'secondary'
            ],
            // Marcory area
            [
                'name' => 'Boulevard d\'Armée Marcory',
                'startLat' => 5.3600, 'startLon' => -3.9600,
                'endLat' => 5.3750, 'endLon' => -3.9400,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue d\'Abidjan Marcory',
                'startLat' => 5.3550, 'startLon' => -3.9550,
                'endLat' => 5.3700, 'endLon' => -3.9350,
                'type' => 'tertiary'
            ],
            [
                'name' => 'Avenue Université Marcory',
                'startLat' => 5.3650, 'startLon' => -3.9700,
                'endLat' => 5.3800, 'endLon' => -3.9500,
                'type' => 'secondary'
            ],
            // Bingerville area
            [
                'name' => 'Boulevard de Bingerville',
                'startLat' => 5.4500, 'startLon' => -3.8950,
                'endLat' => 5.4700, 'endLon' => -3.8700,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue de Bingerville',
                'startLat' => 5.4400, 'startLon' => -3.9000,
                'endLat' => 5.4600, 'endLon' => -3.8800,
                'type' => 'tertiary'
            ],
            // Adjamé area
            [
                'name' => 'Boulevard d\'Adjamé',
                'startLat' => 5.3550, 'startLon' => -4.0600,
                'endLat' => 5.3750, 'endLon' => -4.0400,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue d\'Adjamé',
                'startLat' => 5.3500, 'startLon' => -4.0550,
                'endLat' => 5.3700, 'endLon' => -4.0350,
                'type' => 'tertiary'
            ],
            // Zone 4 area
            [
                'name' => 'Boulevard Zone 4',
                'startLat' => 5.3350, 'startLon' => -3.9450,
                'endLat' => 5.3550, 'endLon' => -3.9250,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue Zone 4',
                'startLat' => 5.3300, 'startLon' => -3.9500,
                'endLat' => 5.3500, 'endLon' => -3.9300,
                'type' => 'tertiary'
            ],
            // Riviera area
            [
                'name' => 'Boulevard Riviera Woluwalé',
                'startLat' => 5.3900, 'startLon' => -3.9450,
                'endLat' => 5.4100, 'endLon' => -3.9250,
                'type' => 'secondary'
            ],
            [
                'name' => 'Rue Riviera',
                'startLat' => 5.3850, 'startLon' => -3.9500,
                'endLat' => 5.4050, 'endLon' => -3.9300,
                'type' => 'tertiary'
            ],
            // Sacré-Coeur area
            [
                'name' => 'Boulevard Sacré-Coeur',
                'startLat' => 5.4300, 'startLon' => -4.0200,
                'endLat' => 5.4500, 'endLon' => -4.0000,
                'type' => 'secondary'
            ],
            // Nord/Angré area
            [
                'name' => 'Boulevard d\'Angré',
                'startLat' => 5.4400, 'startLon' => -4.0300,
                'endLat' => 5.4600, 'endLon' => -4.0100,
                'type' => 'secondary'
            ],
        ];

        $segments = [];
        
        // Select 20-31 roads (presque TOUTES les routes) pour couvrir Yopougon et tout Abidjan
        // Au lieu de sélection aléatoire, on prend plus de routes pour éviter de manquer des zones
        $roadCount = min(count($abidjanRoads), mt_rand(20, 31));
        $selectedIndices = array_rand($abidjanRoads, $roadCount);
        if (!is_array($selectedIndices)) {
            $selectedIndices = [$selectedIndices];
        }

        foreach ($selectedIndices as $idx) {
            $road = $abidjanRoads[$idx];
            
            // Create waypoints along the route
            $lat1 = $road['startLat'];
            $lon1 = $road['startLon'];
            $lat2 = $road['endLat'];
            $lon2 = $road['endLon'];
            
            // Try to get real route from OSRM API with all turns/curves
            $points = $this->getRealRouteFromOSRM($lat1, $lon1, $lat2, $lon2);
            
            // If OSRM fails, generate realistic curved waypoints
            if (empty($points)) {
                $points = [];
                $stepCount = mt_rand(15, 25);  // Augmenté de 5-10 à 15-25 pour plus de détail
                for ($i = 0; $i <= $stepCount; $i++) {
                    $t = $stepCount > 0 ? $i / $stepCount : 0;
                    // Better curve with Perlin-like noise simulation
                    $curve1 = sin($t * M_PI) * 0.0015 * sin($t * 5);
                    $curve2 = sin($t * M_PI * 2) * 0.001 * cos($t * 7);
                    $points[] = [
                        $lat1 + ($lat2 - $lat1) * $t + $curve1,
                        $lon1 + ($lon2 - $lon1) * $t + $curve2
                    ];
                }
            }
            
            // Get realistic traffic profile based on road type and time
            $trafficProfile = $this->getTrafficProfile($road['type'], $latitude, $longitude);
            
            // Force distribution: 33% fluide (green), 33% modéré (orange), 33% sévère (red)
            $distribution = mt_rand(1, 3);
            if ($distribution === 1) {
                // Fluide: 90-100% de vitesse libre
                $speedRatio = mt_rand(90, 100) / 100;
            } elseif ($distribution === 2) {
                // Modéré: 50-80% de vitesse libre
                $speedRatio = mt_rand(50, 80) / 100;
            } else {
                // Sévère: 20-50% de vitesse libre
                $speedRatio = mt_rand(20, 50) / 100;
            }
            
            $currentSpeed = max(5, (int)($trafficProfile['freeFlowSpeed'] * $speedRatio));
            
            $segments[] = [
                'name' => $road['name'],
                'currentSpeed' => $currentSpeed,
                'freeFlowSpeed' => $trafficProfile['freeFlowSpeed'],
                'currentTravelTime' => max(5, 30 / ($currentSpeed / $trafficProfile['freeFlowSpeed'])),
                'freeFlowTravelTime' => 15,
                'coordinates' => $points,
                'roadType' => $road['type'],
                'congestion' => (int)(100 * (1 - ($currentSpeed / $trafficProfile['freeFlowSpeed'])))
            ];
        }

        // Restore random seed to avoid side effects
        mt_srand();

        return [
            'flowSegmentData' => $segments,
            'source' => 'mock_abidjan_roads',
            'timestamp' => now()->toIso8601String(),
            'note' => 'Location-based mock Abidjan roads (Overpass API unavailable)'
        ];
    }

    /**
     * Get real route from OSRM with actual road curves and turns
     * @param float $startLat
     * @param float $startLon
     * @param float $endLat
     * @param float $endLon
     * @return array
     */
    private function getRealRouteFromOSRM(float $startLat, float $startLon, float $endLat, float $endLon): array
    {
        try {
            // Use public OSRM API for route with geometry
            // Note: using profile=car for car routing
            $url = "https://router.project-osrm.org/route/v1/car/{$startLon},{$startLat};{$endLon},{$endLat}";
            $url .= "?overview=full&geometries=geojson";
            
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['routes'][0]['geometry']['coordinates'])) {
                    // Convert GeoJSON coordinates [lon, lat] to [lat, lon]
                    $coords = $data['routes'][0]['geometry']['coordinates'];
                    $points = array_map(function($coord) {
                        return [$coord[1], $coord[0]];  // Swap to [lat, lon]
                    }, $coords);
                    
                    Log::debug('Got real route from OSRM', ['points' => count($points)]);
                    return $points;
                }
            }
        } catch (\Exception $e) {
            Log::debug('OSRM route failed: ' . $e->getMessage());
        }
        
        return [];  // Return empty to fallback to generated curves
    }

    /**
     * Get routing information with traffic
     * @param float $startLat
     * @param float $startLon
     * @param float $endLat
     * @param float $endLon
     * @return array
     */
    public function getRouteWithTraffic(float $startLat, float $startLon, float $endLat, float $endLon): array
    {
        try {
            $response = Http::get(
                "{$this->baseUrl}/routing/1/calculateRoute/{$startLon},{$startLat}:{$endLon},{$endLat}/json",
                [
                    'key' => $this->apiKey,
                    'traffic' => true,
                    'travelMode' => 'car'
                ]
            );

            return $response->json();
        } catch (\Exception $e) {
            Log::error('TomTom Routing API Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get Incidents (accidents, police, etc.)
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusInMeters
     * @return array
     */
    public function getIncidents(float $latitude, float $longitude, int $radiusInMeters = 5000): array
    {
        try {
            $minLat = $latitude - 0.05;
            $minLon = $longitude - 0.05;
            $maxLat = $latitude + 0.05;
            $maxLon = $longitude + 0.05;
            
            $response = Http::get(
                "{$this->baseUrl}/traffic/incidents/json",
                [
                    'key' => $this->apiKey,
                    'bounds' => "{$minLat},{$minLon},{$maxLat},{$maxLon}"
                ]
            );

            return $response->json();
        } catch (\Exception $e) {
            Log::error('TomTom Incidents API Error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
