<?php

// Test Overpass API directly - try alternative servers
$latitude = 5.3698;
$longitude = -4.0036;

$south = $latitude - 0.045;
$west = $longitude - 0.045;
$north = $latitude + 0.045;
$east = $longitude + 0.045;

echo "=== TEST OVERPASS SERVERS ===\n\n";
echo "Coordinates: $latitude, $longitude\n";
echo "Bounds: [$south,$west] to [$north,$east]\n\n";

// Simplified query
$overpassQuery = <<<EOQ
[bbox:$south,$west,$north,$east][timeout:30];
(
  way["highway"~"motorway|trunk|primary|secondary"];
);
out geom;
EOQ;

echo "Query:\n$overpassQuery\n\n";

$servers = [
    'https://overpass-api.de/api/interpreter',
    'https://overpass.kumi.systems/api/interpreter',
    'https://overpass.osm.be/api/interpreter'
];

foreach ($servers as $server) {
    echo "=== Testing: $server ===\n";
    
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
    
    echo "HTTP Status: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
    
    if ($httpCode == 200) {
        $bodyStart = strpos($response, '<?xml');
        $xmlBody = substr($response, $bodyStart);
        
        $doc = new DOMDocument();
        if (@$doc->loadXML($xmlBody)) {
            $ways = $doc->getElementsByTagName('way');
            echo "✓ XML parsed - Found " . $ways->length . " ways\n";
            
            if ($ways->length > 0) {
                echo "✓✓ SUCCESS! This server has data\n";
                
                // Show first 3
                echo "\nFirst 3 ways:\n";
                for ($i = 0; $i < min(3, $ways->length); $i++) {
                    $way = $ways->item($i);
                    $nodes = $way->getElementsByTagName('nd');
                    $name = 'Unknown';
                    
                    $tags = $way->getElementsByTagName('tag');
                    foreach ($tags as $tag) {
                        if ($tag->getAttribute('k') === 'name') {
                            $name = $tag->getAttribute('v');
                            break;
                        }
                    }
                    
                    echo "  " . ($i+1) . ". $name (" . $nodes->length . " nodes)\n";
                }
                echo "\n";
                break;
            }
        } else {
            echo "✗ Failed to parse XML\n";
        }
    }
    
    echo "\n";
}
?>

