<?php
// Test API structure
$url = 'http://localhost:8000/api/traffic/flow?latitude=48.8566&longitude=2.3522';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "=== API Response Structure ===\n";
echo "Top level keys: " . implode(', ', array_keys($data)) . "\n\n";

if (isset($data['flowSegmentData'])) {
    echo "flowSegmentData keys: " . implode(', ', array_keys($data['flowSegmentData'])) . "\n\n";
    
    $flowData = $data['flowSegmentData'];
    echo "currentSpeed: " . $flowData['currentSpeed'] . "\n";
    echo "freeFlowSpeed: " . $flowData['freeFlowSpeed'] . "\n";
    
    if (isset($flowData['coordinates'])) {
        echo "coordinates type: " . gettype($flowData['coordinates']) . "\n";
        
        if (is_array($flowData['coordinates'])) {
            echo "coordinates count: " . count($flowData['coordinates']) . "\n";
            if (count($flowData['coordinates']) > 0) {
                echo "First coordinate: " . json_encode($flowData['coordinates'][0]) . "\n";
            }
        } else {
            echo "Coordinates is not an array!\n";
            echo "Coordinates value: " . json_encode($flowData['coordinates']) . "\n";
        }
    }
    
    echo "\n=== Full flowSegmentData ===\n";
    echo json_encode($flowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
?>
