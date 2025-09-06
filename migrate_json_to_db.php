<?php
require_once 'includes/database_functions.php';
require_once 'phone_data.php';

// Get all devices from JSON
$devices = getAllPhones();

echo "Starting migration of " . count($devices) . " devices from JSON to database...\n";

// Get existing brands and chipsets from database
$existingBrands = getAllBrandsDB();
$existingChipsets = getAllChipsetsDB();

// Create brand lookup arrays
$brandMap = [];
foreach ($existingBrands as $brand) {
    $brandMap[strtolower($brand['name'])] = $brand['id'];
}

$chipsetMap = [];
foreach ($existingChipsets as $chipset) {
    $chipsetMap[strtolower($chipset['name'])] = $chipset['id'];
}

$migratedCount = 0;
$errorCount = 0;

foreach ($devices as $device) {
    try {
        // Get or create brand
        $brandId = null;
        if (!empty($device['brand'])) {
            $brandKey = strtolower($device['brand']);
            if (isset($brandMap[$brandKey])) {
                $brandId = $brandMap[$brandKey];
            } else {
                // Create new brand
                $brandId = addBrandDB($device['brand'], '');
                if ($brandId) {
                    $brandMap[$brandKey] = $brandId;
                    echo "Created new brand: " . $device['brand'] . "\n";
                }
            }
        }

        // Get or create chipset
        $chipsetId = null;
        if (!empty($device['chipset'])) {
            $chipsetKey = strtolower($device['chipset']);
            if (isset($chipsetMap[$chipsetKey])) {
                $chipsetId = $chipsetMap[$chipsetKey];
            } else {
                // Create new chipset
                $chipsetId = addChipsetDB($device['chipset'], '');
                if ($chipsetId) {
                    $chipsetMap[$chipsetKey] = $chipsetId;
                    echo "Created new chipset: " . $device['chipset'] . "\n";
                }
            }
        }

        // Prepare device data for database insertion
        $sql = "INSERT INTO phones (
            release_date, name, brand_id, year, availability, price, image,
            network_2g, network_3g, network_4g, network_5g, dual_sim, esim, sim_size,
            dimensions, form_factor, keyboard, height, width, thickness, weight,
            ip_certificate, color, back_material, frame_material, os, os_version,
            chipset_id, cpu_cores, ram, storage, card_slot, display_type,
            display_resolution, display_size, display_density, display_technology,
            display_notch, refresh_rate, hdr, billion_colors, main_camera_resolution,
            main_camera_count, main_camera_ois, main_camera_f_number,
            main_camera_telephoto, main_camera_ultrawide, main_camera_video,
            main_camera_flash, selfie_camera_resolution, selfie_camera_count,
            selfie_camera_ois, selfie_camera_flash, popup_camera,
            under_display_camera, headphone_jack, dual_speakers, accelerometer,
            gyroscope, proximity, compass, barometer, wifi, bluetooth, gps, nfc,
            infrared, usb, battery_capacity, battery_type, wireless_charging,
            fast_charging, reverse_charging
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )";

        $params = [
            $device['release_date'] ?? null,
            $device['name'] ?? '',
            $brandId,
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
            json_encode($device['sim_size'] ?? []),
            $device['dimensions'] ?? '',
            $device['form_factor'] ?? '',
            $device['keyboard'] ?? '',
            $device['height'] ?? null,
            $device['width'] ?? null,
            $device['thickness'] ?? null,
            $device['weight'] ?? null,
            json_encode($device['ip_certificate'] ?? []),
            $device['color'] ?? '',
            $device['back_material'] ?? '',
            $device['frame_material'] ?? '',
            $device['os'] ?? '',
            $device['os_version'] ?? '',
            $chipsetId,
            $device['cpu_cores'] ?? '',
            $device['ram'] ?? '',
            $device['storage'] ?? '',
            $device['card_slot'] ?? '',
            $device['display_type'] ?? '',
            $device['display_resolution'] ?? '',
            $device['display_size'] ?? null,
            $device['display_density'] ?? '',
            $device['display_technology'] ?? '',
            ($device['display_notch'] === true || $device['display_notch'] === 'true') ? true : false,
            $device['refresh_rate'] ?? '',
            ($device['hdr'] === true || $device['hdr'] === 'true') ? true : false,
            ($device['billion_colors'] === true || $device['billion_colors'] === 'true') ? true : false,
            $device['main_camera_resolution'] ?? '',
            $device['main_camera_count'] ?? '',
            ($device['main_camera_ois'] === true || $device['main_camera_ois'] === 'true') ? true : false,
            $device['main_camera_f_number'] ?? '',
            ($device['main_camera_telephoto'] === true || $device['main_camera_telephoto'] === 'true') ? true : false,
            ($device['main_camera_ultrawide'] === true || $device['main_camera_ultrawide'] === 'true') ? true : false,
            $device['main_camera_video'] ?? '',
            $device['main_camera_flash'] ?? '',
            $device['selfie_camera_resolution'] ?? '',
            $device['selfie_camera_count'] ?? '',
            ($device['selfie_camera_ois'] === true || $device['selfie_camera_ois'] === 'true') ? true : false,
            ($device['selfie_camera_flash'] === true || $device['selfie_camera_flash'] === 'true') ? true : false,
            ($device['popup_camera'] === true || $device['popup_camera'] === 'true') ? true : false,
            ($device['under_display_camera'] === true || $device['under_display_camera'] === 'true') ? true : false,
            ($device['headphone_jack'] === true || $device['headphone_jack'] === 'true') ? true : false,
            ($device['dual_speakers'] === true || $device['dual_speakers'] === 'true') ? true : false,
            ($device['accelerometer'] === true || $device['accelerometer'] === 'true') ? true : false,
            ($device['gyro'] === true || $device['gyro'] === 'true') ? true : false,
            ($device['proximity'] === true || $device['proximity'] === 'true') ? true : false,
            ($device['compass'] === true || $device['compass'] === 'true') ? true : false,
            ($device['barometer'] === true || $device['barometer'] === 'true') ? true : false,
            json_encode($device['wifi'] ?? []),
            json_encode($device['bluetooth'] ?? []),
            ($device['gps'] === true || $device['gps'] === 'true') ? true : false,
            ($device['nfc'] === true || $device['nfc'] === 'true') ? true : false,
            ($device['infrared'] === true || $device['infrared'] === 'true') ? true : false,
            $device['usb'] ?? '',
            $device['battery_capacity'] ?? '',
            ($device['battery_sic'] === true || $device['battery_sic'] === 'true') ? true : false,
            ($device['wireless_charging'] === true || $device['wireless_charging'] === 'true') ? true : false,
            ($device['fast_charging'] === true || $device['fast_charging'] === 'true') ? true : false,
            ($device['reverse_charging'] === true || $device['reverse_charging'] === 'true') ? true : false
        ];

        $stmt = executeQuery($sql, $params);
        $migratedCount++;
        echo "Migrated device: " . $device['name'] . " (" . $device['brand'] . ")\n";

    } catch (Exception $e) {
        $errorCount++;
        echo "Error migrating device " . $device['name'] . ": " . $e->getMessage() . "\n";
    }
}

echo "\nMigration completed!\n";
echo "Successfully migrated: $migratedCount devices\n";
echo "Errors: $errorCount\n";

// Update phone finder to use database only
echo "\nUpdating phone finder to use database only...\n";

$phoneFinderContent = file_get_contents('phone_finder.php');

// Remove the fallback logic
$phoneFinderContent = str_replace(
    '// Get all devices from database, fallback to JSON if database is empty
$devices = getAllPhonesDB();

// If database is empty, use JSON data as fallback
if (empty($devices)) {
    require_once \'phone_data.php\';
    $devices = getAllPhones();
    $fallback_used = true;
} else {
    $fallback_used = false;
}

// Get unique values for dynamic filter options
if ($fallback_used) {
    // Use JSON data structure
    $brands = array_unique(array_column($devices, \'brand\'));
    $chipsets = array_unique(array_filter(array_column($devices, \'chipset\')));
    sort($brands);
    sort($chipsets);
} else {
    // Use database data
    $brands = getAllBrandsDB();
    $chipsets = getAllChipsetsDB();
}',
    '// Get all devices from database
$devices = getAllPhonesDB();

// Get unique values for dynamic filter options from database
$brands = getAllBrandsDB();
$chipsets = getAllChipsetsDB();
$fallback_used = false;',
    $phoneFinderContent
);

// Update brand rendering to use database structure
$phoneFinderContent = str_replace(
    '$brandName = $fallback_used ? $brand : $brand[\'name\'];',
    '$brandName = $brand[\'name\'];',
    $phoneFinderContent
);

$phoneFinderContent = str_replace(
    '$chipsetName = $fallback_used ? $chipset : $chipset[\'name\'];',
    '$chipsetName = $chipset[\'name\'];',
    $phoneFinderContent
);

file_put_contents('phone_finder.php', $phoneFinderContent);

echo "Phone finder updated to use database exclusively!\n";
echo "Migration process completed successfully!\n";
?>