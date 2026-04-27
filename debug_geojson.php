<?php
$json = file_get_contents('public/maps/turkiye-ilceler.geojson');
$data = json_decode($json, true);

if (!$data) {
    echo "JSON Decode Error\n";
    exit;
}

if (!isset($data['features'][0])) {
    echo "No features found\n";
    exit;
}

echo "First Feature Properties:\n";
print_r($data['features'][0]['properties']);

// Check a few more to be sure
echo "\nSecond Feature Properties:\n";
print_r($data['features'][1]['properties']);
