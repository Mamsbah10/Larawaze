<?php
// Tester la structure exacte de coordinates selon la localité
$locations = [
    ['lat' => 48.8566, 'lon' => 2.3522, 'name' => 'Paris Centre'],
    ['lat' => 48.8626, 'lon' => 2.3950, 'name' => 'Gare de l\'Est'],
];

foreach ($locations as $loc) {
    echo "=== {$loc['name']} ===\n";
    $url = "http://localhost:8000/api/traffic/flow?latitude={$loc['lat']}&longitude={$loc['lon']}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['flowSegmentData']['coordinates'])) {
        $coords = $data['flowSegmentData']['coordinates'];
        echo "Type: " . gettype($coords) . "\n";
        
        if (is_array($coords)) {
            echo "Is numeric array: " . (isset($coords[0]) ? 'Yes' : 'No') . "\n";
            if (isset($coords[0])) {
                echo "First element: " . json_encode($coords[0]) . "\n";
            }
        } else if (is_object($coords) || (is_array($coords) && !isset($coords[0]))) {
            echo "Keys: " . implode(', ', array_keys((array)$coords)) . "\n";
        }
        echo "\n";
    }
}
?>
