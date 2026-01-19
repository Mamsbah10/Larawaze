<?php
require 'vendor/autoload.php';

// Create a minimal mock of Laravel's Log facade
class MockLog {
    public static function info($msg) { echo "[INFO] $msg\n"; }
    public static function debug($msg) { echo "[DEBUG] $msg\n"; }
    public static function error($msg) { echo "[ERROR] $msg\n"; }
    public static function warning($msg) { echo "[WARNING] $msg\n"; }
}

// Mock for now() function
if (!function_exists('now')) {
    function now() {
        return new \stdClass;
    }
}

// Mock now object
$mockNow = new class {
    public function toIso8601String() {
        return date('c');
    }
};

// Patch the now() function
if (!function_exists('now')) {
    function now() {
        global $mockNow;
        return $mockNow;
    }
}

// Create a test version of TomTomService
class TestTomTomService {
    public function getTrafficProfile(string $roadType, float $latitude, float $longitude): array
    {
        $hour = (int)date('H');
        $dayOfWeek = date('N');

        $baseProfiles = [
            'motorway' => ['speed' => 100, 'freeSpeed' => 130],
            'trunk' => ['speed' => 80, 'freeSpeed' => 100],
            'primary' => ['speed' => 60, 'freeSpeed' => 80],
            'secondary' => ['speed' => 50, 'freeSpeed' => 70],
            'tertiary' => ['speed' => 40, 'freeSpeed' => 60],
            'residential' => ['speed' => 30, 'freeSpeed' => 50],
        ];

        $profile = $baseProfiles[$roadType] ?? $baseProfiles['tertiary'];

        if (($hour >= 7 && $hour <= 9) || ($hour >= 16 && $hour <= 19)) {
            $profile['speed'] = (int)($profile['speed'] * 0.7);
        } elseif ($hour >= 10 && $hour < 16) {
            $profile['speed'] = (int)($profile['speed'] * 0.9);
        }

        if ($dayOfWeek >= 6) {
            $profile['speed'] = (int)($profile['speed'] * 0.95);
        }

        $variation = rand(90, 110) / 100;
        $profile['speed'] = max(5, (int)($profile['speed'] * $variation));

        return [
            'currentSpeed' => $profile['speed'],
            'freeFlowSpeed' => $profile['freeSpeed'],
            'travelTime' => max(5, 30 / ($profile['speed'] / $profile['freeSpeed'])),
            'freeTravelTime' => 15
        ];
    }

    public function parseOverpassXML(string $xml, float $centerLat, float $centerLon): array
    {
        try {
            $segments = [];
            $doc = new \DOMDocument();
            
            if (!@$doc->loadXML($xml)) {
                echo "[WARNING] Failed to parse XML\n";
                return [];
            }

            $ways = $doc->getElementsByTagName('way');
            $roadsCount = 0;

            foreach ($ways as $way) {
                $nodes = $way->getElementsByTagName('nd');
                $coords = [];

                foreach ($nodes as $node) {
                    if ($node->hasAttribute('lat') && $node->hasAttribute('lon')) {
                        $coords[] = [
                            (float)$node->getAttribute('lat'),
                            (float)$node->getAttribute('lon')
                        ];
                    }
                }

                if (count($coords) < 2) {
                    continue;
                }

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
                if ($roadsCount >= 20) break;
            }

            echo "[INFO] Parsed $roadsCount real roads from Overpass\n";
            return $segments;
        } catch (\Exception $e) {
            echo "[ERROR] Error parsing: " . $e->getMessage() . "\n";
            return [];
        }
    }
}

// Now test it
echo "=== TESTING REAL OVERPASS DATA ===\n\n";

$latitude = 5.3698;
$longitude = -4.0036;

$south = $latitude - 0.045;
$west = $longitude - 0.045;
$north = $latitude + 0.045;
$east = $longitude + 0.045;

$overpassQuery = <<<EOQ
[bbox:$south,$west,$north,$east][timeout:30];
(
  way["highway"~"motorway|trunk|primary|secondary"];
);
out geom;
EOQ;

echo "Fetching real data from Overpass...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://overpass-api.de/api/interpreter');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=' . urlencode($overpassQuery));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $bodyStart = strpos($response, '<?xml');
    $xmlBody = substr($response, $bodyStart);
    
    $service = new TestTomTomService();
    $segments = $service->parseOverpassXML($xmlBody, $latitude, $longitude);
    
    echo "\n✓ Successfully parsed " . count($segments) . " roads\n\n";
    
    if (count($segments) > 0) {
        echo "=== First 5 Roads ===\n";
        for ($i = 0; $i < min(5, count($segments)); $i++) {
            $seg = $segments[$i];
            echo ($i+1) . ". {$seg['name']} ({$seg['roadType']})\n";
            echo "   Speed: {$seg['currentSpeed']} km/h (free: {$seg['freeFlowSpeed']} km/h)\n";
            echo "   Congestion: {$seg['congestion']}%\n";
            echo "   Points: " . count($seg['coordinates']) . "\n\n";
        }
    }
} else {
    echo "Failed! HTTP: $httpCode\n";
}
?>
