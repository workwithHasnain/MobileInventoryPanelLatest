<?php
require_once 'includes/database_functions.php';
require_once 'phone_data.php';

// Get all devices from JSON
$devices = getAllPhones();

echo "Starting simple migration of " . count($devices) . " devices from JSON to database...\n";

// First, create Samsung brand if it doesn't exist
$brands = getAllBrandsDB();
$samsungBrand = null;
$appleBrand = null;

foreach ($brands as $brand) {
    if ($brand['name'] === 'Samsung') {
        $samsungBrand = $brand['id'];
    }
    if ($brand['name'] === 'Apple') {
        $appleBrand = $brand['id'];
    }
}

if (!$samsungBrand) {
    $samsungBrand = addBrandDB('Samsung', 'Samsung Electronics');
    echo "Created Samsung brand with ID: $samsungBrand\n";
}

if (!$appleBrand) {
    $appleBrand = addBrandDB('Apple', 'Apple Inc.');
    echo "Created Apple brand with ID: $appleBrand\n";
}

$migratedCount = 0;

foreach ($devices as $device) {
    try {
        // Determine brand ID
        $brandId = null;
        if ($device['brand'] === 'Samsung') {
            $brandId = $samsungBrand;
        } elseif ($device['brand'] === 'Apple') {
            $brandId = $appleBrand;
        } else {
            // Skip unknown brands for now
            echo "Skipping device with unknown brand: " . $device['brand'] . "\n";
            continue;
        }

        // Insert with minimal required fields only
        $sql = "INSERT INTO phones (
            name, brand_id, release_date, year, availability, price, image,
            dual_sim, esim, hdr, billion_colors, main_camera_ois, 
            main_camera_telephoto, main_camera_ultrawide, selfie_camera_ois, 
            selfie_camera_flash, popup_camera, under_display_camera,
            headphone_jack, dual_speakers, accelerometer, gyroscope, 
            proximity, compass, barometer, gps, nfc, infrared,
            wireless_charging, fast_charging, reverse_charging
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?
        )";

        $params = [
            $device['name'],
            $brandId,
            $device['release_date'] ?? null,
            $device['year'] ?? null,
            $device['availability'] ?? '',
            $device['price'] ?? null,
            $device['image'] ?? ($device['images'][0] ?? ''),
            false, // dual_sim
            false, // esim
            false, // hdr
            false, // billion_colors
            false, // main_camera_ois
            false, // main_camera_telephoto
            false, // main_camera_ultrawide
            false, // selfie_camera_ois
            false, // selfie_camera_flash
            false, // popup_camera
            false, // under_display_camera
            false, // headphone_jack
            false, // dual_speakers
            false, // accelerometer
            false, // gyroscope
            false, // proximity
            false, // compass
            false, // barometer
            false, // gps
            false, // nfc
            false, // infrared
            false, // wireless_charging
            false, // fast_charging
            false  // reverse_charging
        ];

        $stmt = executeQuery($sql, $params);
        $migratedCount++;
        echo "Migrated device: " . $device['name'] . " (" . $device['brand'] . ")\n";

    } catch (Exception $e) {
        echo "Error migrating device " . $device['name'] . ": " . $e->getMessage() . "\n";
    }
}

echo "\nSimple migration completed!\n";
echo "Successfully migrated: $migratedCount devices\n";
?>