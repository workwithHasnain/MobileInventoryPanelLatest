<?php
require_once 'phone_data.php';

// Get first device to debug
$devices = getAllPhones();
$device = $devices[0];

echo "Debugging first device parameters:\n";
echo "Name: " . $device['name'] . "\n\n";

$params = [
    $device['release_date'] ?? null,
    $device['name'] ?? '',
    1, // brand_id placeholder
    $device['year'] ?? null,
    $device['availability'] ?? '',
    $device['price'] ?? null,
    $device['image'] ?? $device['images'][0] ?? '',
    json_encode($device['2g'] ?? []),
    json_encode($device['3g'] ?? []),
    json_encode($device['4g'] ?? []),
    json_encode($device['5g'] ?? []),
    ($device['dual_sim'] === true || $device['dual_sim'] === 'true') ? true : false,
    ($device['esim'] === true || $device['esim'] === 'true') ? true : false,
];

echo "First 13 parameters:\n";
for ($i = 0; $i < 13; $i++) {
    echo "Parameter $i: ";
    var_dump($params[$i]);
}

// Check specific boolean fields
echo "\nBoolean field values in JSON:\n";
echo "dual_sim: ";
var_dump($device['dual_sim']);
echo "esim: ";
var_dump($device['esim']);
echo "hdr: ";
var_dump($device['hdr']);
echo "Type: " . gettype($device['dual_sim']) . "\n";
?>