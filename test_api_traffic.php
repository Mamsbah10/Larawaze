<?php
// Direct API test
// Abidjan Plateau coordinates
$latitude = 5.3500;
$longitude = -4.0080;

echo "Testing API endpoint: /api/traffic/flow\n";
echo "Coordinates: $latitude, $longitude\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/traffic/flow?latitude=$latitude&longitude=$longitude");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    echo "Source: " . ($data['source'] ?? 'unknown') . "\n";
    echo "Note: " . ($data['note'] ?? 'unknown') . "\n\n";
    
    if (isset($data['flowSegmentData']) && is_array($data['flowSegmentData'])) {
        echo "Found " . count($data['flowSegmentData']) . " road segments\n\n";
        
        if (count($data['flowSegmentData']) > 0) {
            echo "=== First 5 roads ===\n";
            for ($i = 0; $i < min(5, count($data['flowSegmentData'])); $i++) {
                $seg = $data['flowSegmentData'][$i];
                echo "\n" . ($i+1) . ". " . $seg['name'] . "\n";
                echo "   Type: " . ($seg['roadType'] ?? $seg['frc'] ?? 'unknown') . "\n";
                echo "   Speed: " . $seg['currentSpeed'] . " km/h (free: " . $seg['freeFlowSpeed'] . ")\n";
                echo "   Congestion: " . $seg['congestion'] . "%\n";
                echo "   Points: " . count($seg['coordinates']) . "\n";
                if (count($seg['coordinates']) > 0) {
                    echo "   Start: " . $seg['coordinates'][0][0] . ", " . $seg['coordinates'][0][1] . "\n";
                    echo "   End: " . $seg['coordinates'][count($seg['coordinates'])-1][0] . ", " . $seg['coordinates'][count($seg['coordinates'])-1][1] . "\n";
                }
            }
        }
    } else {
        echo "ERROR: No flowSegmentData in response\n";
        var_dump($data);
    }
} else {
    echo "ERROR: HTTP $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}
?>
