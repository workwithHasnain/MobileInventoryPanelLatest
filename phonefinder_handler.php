<?php
// Handler for phonefinder AJAX requests

// (debug POST logging removed for production)
header('Content-Type: application/json');

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    // Get selected brands from POST and convert to integers
    $selectedBrands = isset($_POST['brands']) ? $_POST['brands'] : [];

    // Convert brand IDs to integers
    $selectedBrands = array_map('intval', $selectedBrands);

    // Get selected availability statuses
    $selectedAvailability = isset($_POST['availability']) ? $_POST['availability'] : [];

    // Max price (optional)
    $priceMax = isset($_POST['price_max']) ? $_POST['price_max'] : null;

    // Year range (optional)
    $yearMin = isset($_POST['year_min']) ? $_POST['year_min'] : null;
    $yearMax = isset($_POST['year_max']) ? $_POST['year_max'] : null;

    // RAM min (optional)
    $ramMin = isset($_POST['ram_min']) ? $_POST['ram_min'] : null;

    // Storage min (optional)
    $storageMin = isset($_POST['storage_min']) ? $_POST['storage_min'] : null;

    // Display size range (optional)
    $displaySizeMin = isset($_POST['display_size_min']) ? $_POST['display_size_min'] : null;
    $displaySizeMax = isset($_POST['display_size_max']) ? $_POST['display_size_max'] : null;

    // B1 new filters
    $osFamilies = isset($_POST['os_family']) ? (array)$_POST['os_family'] : [];
    $osVersionMin = isset($_POST['os_version_min']) ? $_POST['os_version_min'] : null;
    $chipsetQuery = isset($_POST['chipset_query']) ? trim($_POST['chipset_query']) : '';
    $cardSlotRequired = isset($_POST['card_slot_required']) ? true : false;
    $mainCameraMpMin = isset($_POST['main_camera_mp_min']) ? $_POST['main_camera_mp_min'] : null;
    $video4k = isset($_POST['video_4k']);
    $video8k = isset($_POST['video_8k']);
    $batteryCapacityMin = isset($_POST['battery_capacity_min']) ? $_POST['battery_capacity_min'] : null;
    $wiredChargeMin = isset($_POST['wired_charge_min']) ? $_POST['wired_charge_min'] : null;
    $wirelessRequired = isset($_POST['wireless_required']);
    $wirelessChargeMin = isset($_POST['wireless_charge_min']) ? $_POST['wireless_charge_min'] : null;
    // New: F-number (aperture) max and CPU min GHz
    $fNumberMax = isset($_POST['f_number_max']) ? $_POST['f_number_max'] : null;
    $cpuClockMin = isset($_POST['cpu_clock_min']) ? $_POST['cpu_clock_min'] : null;

    // Network/SIM filters
    $network2gBands = isset($_POST['network_2g_bands']) ? (array)$_POST['network_2g_bands'] : [];
    $network3gBands = isset($_POST['network_3g_bands']) ? (array)$_POST['network_3g_bands'] : [];
    $network4gBands = isset($_POST['network_4g_bands']) ? (array)$_POST['network_4g_bands'] : [];
    $network5gBands = isset($_POST['network_5g_bands']) ? (array)$_POST['network_5g_bands'] : [];
    $dualSimRequired = isset($_POST['dual_sim']) ? true : false;
    $esimRequired = isset($_POST['esim']) ? true : false;
    $simSizes = isset($_POST['sim_sizes']) ? (array)$_POST['sim_sizes'] : [];

    // Connectivity filters
    $wifiVersions = isset($_POST['wifi_versions']) ? (array)$_POST['wifi_versions'] : [];
    $bluetoothVersions = isset($_POST['bluetooth_versions']) ? (array)$_POST['bluetooth_versions'] : [];
    $usbTypes = isset($_POST['usb_types']) ? (array)$_POST['usb_types'] : [];
    $gpsRequired = isset($_POST['gps_required']) ? true : false;
    $nfcRequired = isset($_POST['nfc_required']) ? true : false;
    $infraredRequired = isset($_POST['infrared_required']) ? true : false;
    $fmRadioRequired = isset($_POST['fm_radio_required']) ? true : false;

    // Sensors (from FEATURES text)
    $sensorAccelerometer = isset($_POST['accelerometer']);
    $sensorGyro = isset($_POST['gyro']);
    $sensorBarometer = isset($_POST['barometer']);
    $sensorHeartRate = isset($_POST['heart_rate']);
    $sensorCompass = isset($_POST['compass']);
    $sensorProximity = isset($_POST['proximity']);

    // Main Camera features
    $mainCameraTelephoto = isset($_POST['main_camera_telephoto']);
    $mainCameraUltrawide = isset($_POST['main_camera_ultrawide']);
    $mainCameraOIS = isset($_POST['main_camera_ois']);

    // Selfie Camera features
    $selfieCameraFlash = isset($_POST['selfie_camera_flash']);
    $popupCamera = isset($_POST['popup_camera']);
    $underDisplayCamera = isset($_POST['under_display_camera']);

    // Battery extras
    $batterySiC = isset($_POST['battery_sic']);
    $batteryRemovable = isset($_POST['battery_removable']);

    // IP certificate and Form Factor (multi-select)
    $ipCertificates = isset($_POST['ip_certificate']) ? (array)$_POST['ip_certificate'] : [];
    $formFactors = isset($_POST['form_factor']) ? (array)$_POST['form_factor'] : [];

    // Misc
    $freeText = isset($_POST['free_text']) ? trim($_POST['free_text']) : '';
    $order = isset($_POST['order']) ? trim($_POST['order']) : null;
    $fingerprintOptions = isset($_POST['fingerprint']) ? (array)$_POST['fingerprint'] : [];

    // Display extras
    $displayResMin = isset($_POST['display_res_min']) ? $_POST['display_res_min'] : null;
    $displayResMax = isset($_POST['display_res_max']) ? $_POST['display_res_max'] : null;
    $displayTech = isset($_POST['display_tech']) ? (array)$_POST['display_tech'] : [];
    $displayNotch = isset($_POST['display_notch']) ? (array)$_POST['display_notch'] : [];
    $refreshRateMin = isset($_POST['refresh_rate_min']) ? $_POST['refresh_rate_min'] : null;
    $displayHDR = isset($_POST['hdr']);
    $displayBillionColors = isset($_POST['billion_colors']);

    // Body thresholds
    $heightMin = isset($_POST['height_min']) ? $_POST['height_min'] : null; // mm
    $widthMin = isset($_POST['width_min']) ? $_POST['width_min'] : null; // mm
    $thicknessMax = isset($_POST['thickness_max']) ? $_POST['thickness_max'] : null; // mm
    $weightMax = isset($_POST['weight_max']) ? $_POST['weight_max'] : null; // g

    // Materials & colors
    $colors = isset($_POST['color']) ? (array)$_POST['color'] : [];
    $frameMaterials = isset($_POST['frame_material']) ? (array)$_POST['frame_material'] : [];
    $backMaterials = isset($_POST['back_material']) ? (array)$_POST['back_material'] : [];

    // Build the query
    $query = "SELECT p.*, b.name as brand_name 
              FROM phones p 
              LEFT JOIN brands b ON p.brand_id = b.id 
              WHERE 1=1";

    $params = [];

    // Filter by brands if any selected
    if (!empty($selectedBrands)) {
        $placeholders = str_repeat('?,', count($selectedBrands) - 1) . '?';
        $query .= " AND p.brand_id IN ($placeholders)";
        $params = array_merge($params, $selectedBrands);
    }

    // Filter by availability if any selected
    if (!empty($selectedAvailability)) {
        $placeholders = str_repeat('?,', count($selectedAvailability) - 1) . '?';
        $query .= " AND p.availability IN ($placeholders)";
        $params = array_merge($params, $selectedAvailability);
    }

    // Filter by max price if provided (> 0)
    if ($priceMax !== null && $priceMax !== '') {
        $priceMax = floatval($priceMax);
        if ($priceMax > 0) {
            $query .= " AND p.price IS NOT NULL AND p.price <= ?";
            $params[] = $priceMax;
        }
    }

    // Filter by year range
    if ($yearMin !== null && $yearMin !== '') {
        $yearMin = intval($yearMin);
        $query .= " AND p.year >= ?";
        $params[] = $yearMin;
    }
    if ($yearMax !== null && $yearMax !== '') {
        $yearMax = intval($yearMax);
        $query .= " AND p.year <= ?";
        $params[] = $yearMax;
    }

    // Filter by RAM (extract FIRST number only to handle "12/16GB RAM")
    if ($ramMin !== null && $ramMin !== '') {
        $ramMin = intval($ramMin);
        if ($ramMin > 0) {
            // Extract first number from ram column (handles "12/16GB RAM" → 12, "8GB" → 8)
            $query .= " AND CAST(substring(COALESCE(p.ram, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
            $params[] = $ramMin;
        }
    }

    // Filter by Storage (extract FIRST number only, handle TB conversion)
    if ($storageMin !== null && $storageMin !== '') {
        $storageMin = intval($storageMin);
        if ($storageMin > 0) {
            // Extract first number and check if first segment has TB (handles "256GB/512GB/1TB" → 256)
            $query .= " AND (
                CASE
                    WHEN substring(p.storage FROM '^([^/]+)') ILIKE '%TB%' THEN
                        CAST(substring(p.storage FROM '([0-9]+)') AS INTEGER) * 1024
                    ELSE
                        CAST(substring(COALESCE(p.storage, '0') FROM '([0-9]+)') AS INTEGER)
                END
            ) >= ?";
            $params[] = $storageMin;
        }
    }

    // Filter by Display Size (parse DECIMAL from VARCHAR like "6.1" or "6.9 inches")
    if ($displaySizeMin !== null && $displaySizeMin !== '') {
        $displaySizeMin = floatval($displaySizeMin);
        $query .= " AND CAST(substring(COALESCE(p.display_size, '0') FROM '([0-9.]+)') AS DECIMAL) >= ?";
        $params[] = $displaySizeMin;
    }
    if ($displaySizeMax !== null && $displaySizeMax !== '') {
        $displaySizeMax = floatval($displaySizeMax);
        $query .= " AND CAST(substring(COALESCE(p.display_size, '0') FROM '([0-9.]+)') AS DECIMAL) <= ?";
        $params[] = $displaySizeMax;
    }

    // OS family filtering (Android/iOS/Other)
    if (!empty($osFamilies)) {
        // Search across direct OS column and platform text as fallback
        $osExpr = "COALESCE(p.os, '') || ' ' || COALESCE(p.platform, '')";
        $conditions = [];
        $onlyOther = (count($osFamilies) === 1 && strtolower($osFamilies[0]) === 'other');
        foreach ($osFamilies as $fam) {
            $famLower = strtolower($fam);
            if ($famLower === 'android') {
                $conditions[] = "(($osExpr) ILIKE '%android%')";
            } elseif ($famLower === 'ios') {
                $conditions[] = "(($osExpr) ILIKE '%ios%' OR ($osExpr) ILIKE '%iphone%')";
            } elseif ($famLower === 'other') {
                // other by exclusion if selected alone
                if ($onlyOther) {
                    $conditions[] = "( ($osExpr) NOT ILIKE '%android%' AND ($osExpr) NOT ILIKE '%ios%' AND ($osExpr) NOT ILIKE '%iphone%')";
                }
            }
        }
        if (!empty($conditions)) {
            $query .= " AND (" . implode(' OR ', $conditions) . ")";
        }
    }

    // Min OS version (extract first number) only applied if provided
    if ($osVersionMin !== null && $osVersionMin !== '' && intval($osVersionMin) > 0) {
        $osVersionMin = intval($osVersionMin);
        // Extract from os; if null, try platform text
        $query .= " AND CAST(substring(COALESCE(p.os, p.platform, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
        $params[] = $osVersionMin;
    }

    // Chipset contains
    if (!empty($chipsetQuery)) {
        $query .= " AND p.chipset_name ILIKE ?";
        $params[] = '%' . $chipsetQuery . '%';
    }

    // CPU min GHz (parse from platform text: e.g., "3.2 GHz" or "3200 MHz").
    if ($cpuClockMin !== null && $cpuClockMin !== '' && floatval($cpuClockMin) > 0) {
        $cpuClockMin = floatval($cpuClockMin);
        $query .= " AND COALESCE((
            SELECT MAX(freq_ghz) FROM (
                SELECT CAST((r)[1] AS DECIMAL) AS freq_ghz
                FROM regexp_matches(COALESCE(p.platform, ''), '([0-9]+\\.?[0-9]*)\\s*GHz', 'g') AS r
                UNION ALL
                SELECT CAST((r2)[1] AS DECIMAL) / 1000.0 AS freq_ghz
                FROM regexp_matches(COALESCE(p.platform, ''), '([0-9]+\\.?[0-9]*)\\s*MHz', 'g') AS r2
            ) t
        ), 0) >= ?";
        $params[] = $cpuClockMin;
    }

    // Card slot required
    if ($cardSlotRequired) {
        $query .= " AND p.card_slot IS NOT NULL AND p.card_slot NOT ILIKE '%No%'";
    }

    // Main camera MP (parse number from main_camera_resolution)
    if ($mainCameraMpMin !== null && $mainCameraMpMin !== '' && intval($mainCameraMpMin) > 0) {
        $mainCameraMpMin = intval($mainCameraMpMin);
        $query .= " AND CAST(substring(COALESCE(p.main_camera_resolution, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
        $params[] = $mainCameraMpMin;
    }

    // F-number max (aperture) from main_camera text; accept any lens aperture <= threshold
    if ($fNumberMax !== null && $fNumberMax !== '' && floatval($fNumberMax) > 0) {
        $fNumberMax = floatval($fNumberMax);
        $query .= " AND EXISTS (
            SELECT 1 FROM regexp_matches(COALESCE(p.main_camera, ''), 'f\\s*/\\s*([0-9]+\\.?[0-9]*)', 'g') AS r
            WHERE CAST((r)[1] AS DECIMAL) <= ?
        )";
        $params[] = $fNumberMax;
    }

    // Video capability filters
    if ($video4k) {
        $query .= " AND p.main_camera_video ILIKE '%4K%'";
    }
    if ($video8k) {
        $query .= " AND p.main_camera_video ILIKE '%8K%'";
    }

    // Battery capacity minimum
    if ($batteryCapacityMin !== null && $batteryCapacityMin !== '' && intval($batteryCapacityMin) > 0) {
        $batteryCapacityMin = intval($batteryCapacityMin);
        $query .= " AND CAST(substring(COALESCE(p.battery_capacity, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
        $params[] = $batteryCapacityMin;
    }

    // Wired charging minimum
    if ($wiredChargeMin !== null && $wiredChargeMin !== '' && intval($wiredChargeMin) > 0) {
        $wiredChargeMin = intval($wiredChargeMin);
        $query .= " AND CAST(substring(COALESCE(p.wired_charging, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
        $params[] = $wiredChargeMin;
    }

    // Wireless charging required and minimum wattage
    if ($wirelessRequired) {
        $query .= " AND p.wireless_charging IS NOT NULL AND p.wireless_charging NOT ILIKE '%No%'";
    }
    if ($wirelessChargeMin !== null && $wirelessChargeMin !== '' && intval($wirelessChargeMin) > 0) {
        $wirelessChargeMin = intval($wirelessChargeMin);
        $query .= " AND CAST(substring(COALESCE(p.wireless_charging, '0') FROM '([0-9]+)') AS INTEGER) >= ?";
        $params[] = $wirelessChargeMin;
    }

    // Network/SIM filters - parse JSON from network, body, or comms columns
    // These filters require checking JSON data stored in TEXT columns

    // Helper to check if network JSON contains specific bands
    if (!empty($network2gBands)) {
        // Check if any of the selected 2G bands are present in network JSON
        $bandConditions = [];
        foreach ($network2gBands as $band) {
            $band = intval($band); // e.g., 850, 900, 1800, 1900
            $bandConditions[] = "(p.network ILIKE '%GSM " . $band . "%' OR p.network ILIKE '%" . $band . " MHz%')";
        }
        if (!empty($bandConditions)) {
            $query .= " AND (" . implode(' OR ', $bandConditions) . ")";
        }
    }

    if (!empty($network3gBands)) {
        // Check if any of the selected 3G bands are present
        $bandConditions = [];
        foreach ($network3gBands as $band) {
            $band = intval($band); // e.g., 850, 900, 1700, 1900, 2100
            $bandConditions[] = "(p.network ILIKE '%HSPA " . $band . "%' OR p.network ILIKE '%UMTS " . $band . "%' OR p.network ILIKE '%WCDMA " . $band . "%' OR p.network ILIKE '%" . $band . " MHz%')";
        }
        if (!empty($bandConditions)) {
            $query .= " AND (" . implode(' OR ', $bandConditions) . ")";
        }
    }

    if (!empty($network4gBands)) {
        // Check if any of the selected 4G bands are present
        $bandConditions = [];
        foreach ($network4gBands as $band) {
            $band = intval($band); // e.g., 700, 850, 900, 1800, 2600
            $bandConditions[] = "(p.network ILIKE '%LTE " . $band . "%' OR p.network ILIKE '%4G " . $band . "%' OR p.network ILIKE '%" . $band . " MHz%')";
        }
        if (!empty($bandConditions)) {
            $query .= " AND (" . implode(' OR ', $bandConditions) . ")";
        }
    }

    if (!empty($network5gBands)) {
        // Check if any of the selected 5G bands are present
        $bandConditions = [];
        foreach ($network5gBands as $band) {
            if (strtolower($band) === 'mmwave') {
                // mmWave special case
                $bandConditions[] = "(p.network ILIKE '%mmWave%' OR p.network ILIKE '%millimeter%')";
            } else {
                $band = intval($band); // e.g., 3500, 3600, 3700, 3800
                $bandConditions[] = "(p.network ILIKE '%5G " . $band . "%' OR p.network ILIKE '%NR " . $band . "%' OR p.network ILIKE '%" . $band . " MHz%')";
            }
        }
        if (!empty($bandConditions)) {
            $query .= " AND (" . implode(' OR ', $bandConditions) . ")";
        }
    }

    // Dual SIM filter - check body or network JSON for dual SIM mention
    if ($dualSimRequired) {
        $query .= " AND (p.body ILIKE '%Dual SIM%' OR p.network ILIKE '%Dual SIM%' OR p.comms ILIKE '%Dual SIM%')";
    }

    // eSIM filter - check body or network JSON for eSIM mention
    if ($esimRequired) {
        $query .= " AND (p.body ILIKE '%eSIM%' OR p.network ILIKE '%eSIM%' OR p.comms ILIKE '%eSIM%')";
    }

    // SIM size filters
    if (!empty($simSizes)) {
        $simConditions = [];
        foreach ($simSizes as $size) {
            $size = trim($size); // e.g., "Nano-SIM", "Micro-SIM", "Mini-SIM"
            $simConditions[] = "(p.body ILIKE '%" . $size . "%' OR p.network ILIKE '%" . $size . "%')";
        }
        if (!empty($simConditions)) {
            $query .= " AND (" . implode(' OR ', $simConditions) . ")";
        }
    }

    // WiFi/WLAN version filters
    if (!empty($wifiVersions)) {
        $wifiConditions = [];
        foreach ($wifiVersions as $version) {
            $version = trim($version); // e.g., "802.11n", "802.11ac", "802.11ax", "802.11be"
            $wifiConditions[] = "p.comms ILIKE '%" . $version . "%'";
        }
        if (!empty($wifiConditions)) {
            $query .= " AND (" . implode(' OR ', $wifiConditions) . ")";
        }
    }

    // Bluetooth version filters
    if (!empty($bluetoothVersions)) {
        $btConditions = [];
        foreach ($bluetoothVersions as $version) {
            $version = trim($version); // e.g., "4.0", "5.0", "5.2"
            // Match both "Bluetooth 5.0" and just "5.0" in the comms data
            $btConditions[] = "(p.comms ILIKE '%Bluetooth " . $version . "%' OR p.comms ILIKE '%" . $version . "%')";
        }
        if (!empty($btConditions)) {
            $query .= " AND (" . implode(' OR ', $btConditions) . ")";
        }
    }

    // USB type filters
    if (!empty($usbTypes)) {
        $usbConditions = [];
        foreach ($usbTypes as $type) {
            $type = trim($type); // e.g., "USB-C", "USB 3", "micro USB"
            $usbConditions[] = "p.comms ILIKE '%" . $type . "%'";
        }
        if (!empty($usbConditions)) {
            $query .= " AND (" . implode(' OR ', $usbConditions) . ")";
        }
    }

    // GPS filter - check comms or features JSON for GPS/positioning
    if ($gpsRequired) {
        $query .= " AND (p.comms ILIKE '%GPS%' OR p.comms ILIKE '%A-GPS%' OR p.comms ILIKE '%GLONASS%' OR p.comms ILIKE '%positioning%')";
    }

    // NFC filter - check comms JSON for NFC
    if ($nfcRequired) {
        $query .= " AND p.comms ILIKE '%NFC%'";
    }

    // Infrared filter - check comms JSON for infrared/IR
    if ($infraredRequired) {
        $query .= " AND (p.comms ILIKE '%Infrared%' OR p.comms ILIKE '%IR port%' OR p.comms ILIKE '%IR blaster%')";
    }

    // FM Radio filter - check comms JSON for radio
    if ($fmRadioRequired) {
        $query .= " AND (p.comms ILIKE '%Radio%' OR p.comms ILIKE '%FM radio%')";
    }

    // Sensor filters (match in FEATURES/body text)
    if ($sensorAccelerometer) {
        $query .= " AND (p.features ILIKE '%Accelerometer%' OR p.body ILIKE '%Accelerometer%')";
    }
    if ($sensorGyro) {
        $query .= " AND (p.features ILIKE '%Gyro%' OR p.features ILIKE '%Gyroscope%' OR p.body ILIKE '%Gyro%' OR p.body ILIKE '%Gyroscope%')";
    }
    if ($sensorBarometer) {
        $query .= " AND (p.features ILIKE '%Barometer%' OR p.body ILIKE '%Barometer%')";
    }
    if ($sensorHeartRate) {
        $query .= " AND (p.features ILIKE '%Heart rate%' OR p.body ILIKE '%Heart rate%')";
    }
    if ($sensorCompass) {
        $query .= " AND (p.features ILIKE '%Compass%' OR p.body ILIKE '%Compass%')";
    }
    if ($sensorProximity) {
        $query .= " AND (p.features ILIKE '%Proximity%' OR p.body ILIKE '%Proximity%')";
    }

    // Display filters
    // Display resolution range (extract vertical resolution from display JSON)
    if (!empty($displayResMin) && $displayResMin > 0) {
        // Match patterns like "1080 x 2400 pixels" or "720x1600"
        $query .= " AND (p.display ~ '[0-9]+\\s*[x×]\\s*([0-9]+)' AND ";
        $query .= " CAST(SUBSTRING(p.display FROM '[0-9]+\\s*[x×]\\s*([0-9]+)') AS INTEGER) >= $displayResMin)";
    }
    if (!empty($displayResMax) && $displayResMax < 4320) {
        $query .= " AND (p.display ~ '[0-9]+\\s*[x×]\\s*([0-9]+)' AND ";
        $query .= " CAST(SUBSTRING(p.display FROM '[0-9]+\\s*[x×]\\s*([0-9]+)') AS INTEGER) <= $displayResMax)";
    }

    // Display technology (IPS, OLED, LTPO)
    if (!empty($displayTech) && is_array($displayTech)) {
        $techConditions = [];
        foreach ($displayTech as $tech) {
            $tech = trim($tech);
            if ($tech === 'IPS') {
                $techConditions[] = "(p.display ILIKE '%IPS%' OR p.display ILIKE '%LCD%')";
            } elseif ($tech === 'OLED') {
                $techConditions[] = "(p.display ILIKE '%OLED%' OR p.display ILIKE '%AMOLED%' OR p.display ILIKE '%Super AMOLED%')";
            } elseif ($tech === 'LTPO') {
                $techConditions[] = "(p.display ILIKE '%LTPO%')";
            }
        }
        if (!empty($techConditions)) {
            $query .= " AND (" . implode(" OR ", $techConditions) . ")";
        }
    }

    // Display notch type
    if (!empty($displayNotch) && is_array($displayNotch)) {
        $notchConditions = [];
        foreach ($displayNotch as $notch) {
            $notch = trim($notch);
            if ($notch === 'No notch') {
                $notchConditions[] = "(p.display NOT ILIKE '%notch%' AND p.display NOT ILIKE '%punch%' AND p.display NOT ILIKE '%hole%')";
            } elseif ($notch === 'Notch') {
                $notchConditions[] = "(p.display ILIKE '%notch%')";
            } elseif ($notch === 'Punch hole') {
                $notchConditions[] = "(p.display ILIKE '%punch%' OR p.display ILIKE '%hole%')";
            }
        }
        if (!empty($notchConditions)) {
            $query .= " AND (" . implode(" OR ", $notchConditions) . ")";
        }
    }

    // Refresh rate minimum (extract Hz value)
    if (!empty($refreshRateMin) && $refreshRateMin > 0) {
        // Match patterns like "120Hz" or "90 Hz"
        $query .= " AND (p.display ~ '([0-9]+)\\s*Hz' AND ";
        $query .= " CAST(SUBSTRING(p.display FROM '([0-9]+)\\s*Hz') AS INTEGER) >= $refreshRateMin)";
    }

    // HDR support
    if ($displayHDR) {
        $query .= " AND (p.display ILIKE '%HDR%' OR p.display ILIKE '%HDR10%' OR p.display ILIKE '%HDR10+%' OR p.display ILIKE '%Dolby Vision%')";
    }

    // Billion colors support
    if ($displayBillionColors) {
        $query .= " AND (p.display ILIKE '%1B colors%' OR p.display ILIKE '%billion colors%' OR p.display ILIKE '%10-bit%')";
    }

    // Free Text across multiple columns
    if (!empty($freeText)) {
        $like = '%' . $freeText . '%';
        $query .= " AND (p.display ILIKE ? OR p.features ILIKE ? OR p.platform ILIKE ? OR p.body ILIKE ? OR p.network ILIKE ? OR p.comms ILIKE ? OR p.main_camera ILIKE ? OR p.selfie_camera ILIKE ? OR p.sound ILIKE ? OR p.battery ILIKE ?)";
        for ($i = 0; $i < 10; $i++) {
            $params[] = $like;
        }
    }

    // Body: dimensions (parse 'HxWxT mm' patterns from p.body)
    if ($heightMin !== null && $heightMin !== '' && intval($heightMin) > 0) {
        $heightMin = intval($heightMin);
        $query .= " AND (
            p.body ~ '([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*mm' AND
            CAST(SUBSTRING(p.body FROM '([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*mm') AS DECIMAL) >= ?
        )";
        $params[] = $heightMin;
    }
    if ($widthMin !== null && $widthMin !== '' && intval($widthMin) > 0) {
        $widthMin = intval($widthMin);
        $query .= " AND (
            p.body ~ '([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*mm' AND
            CAST(SUBSTRING(p.body FROM '[0-9]+\\.?[0-9]*\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*x\\s*[0-9]+\\.?[0-9]*\\s*mm') AS DECIMAL) >= ?
        )";
        $params[] = $widthMin;
    }
    if ($thicknessMax !== null && $thicknessMax !== '' && floatval($thicknessMax) > 0) {
        $thicknessMax = floatval($thicknessMax);
        $query .= " AND (
            p.body ~ '([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*mm' AND
            CAST(SUBSTRING(p.body FROM '[0-9]+\\.?[0-9]*\\s*x\\s*[0-9]+\\.?[0-9]*\\s*x\\s*([0-9]+\\.?[0-9]*)\\s*mm') AS DECIMAL) <= ?
        )";
        $params[] = $thicknessMax;
    }
    // Body: weight (parse 'xxx g' pattern)
    if ($weightMax !== null && $weightMax !== '' && intval($weightMax) > 0) {
        $weightMax = intval($weightMax);
        $query .= " AND (
            p.body ~ '([0-9]+)\\s*g' AND
            CAST(SUBSTRING(p.body FROM '([0-9]+)\\s*g') AS INTEGER) <= ?
        )";
        $params[] = $weightMax;
    }

    // Materials & Colors filters
    if (!empty($colors)) {
        $colorConds = [];
        foreach ($colors as $c) {
            $c = trim($c);
            $safe = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $c);
            if ($safe !== '') {
                $like = '%' . $safe . '%';
                $colorConds[] = '(p.body ILIKE ? OR p.misc ILIKE ?)';
                $params[] = $like;
                $params[] = $like;
            }
        }
        if (!empty($colorConds)) {
            $query .= ' AND (' . implode(' OR ', $colorConds) . ')';
        }
    }

    if (!empty($frameMaterials)) {
        $fmConds = [];
        foreach ($frameMaterials as $m) {
            $m = trim($m);
            $safe = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $m);
            if ($safe !== '') {
                $likeMaterial = '%' . $safe . '%';
                // Prefer explicit 'frame' mentions
                $fmConds[] = "(p.body ILIKE '%frame%' AND p.body ILIKE ?)";
                $params[] = $likeMaterial;
            }
        }
        if (!empty($fmConds)) {
            $query .= ' AND (' . implode(' OR ', $fmConds) . ')';
        }
    }

    if (!empty($backMaterials)) {
        $bmConds = [];
        foreach ($backMaterials as $m) {
            $m = trim($m);
            $safe = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $m);
            if ($safe !== '') {
                $like1 = '%' . $safe . ' back%';
                $like2 = '%back%';
                $like3 = '%' . $safe . '%';
                // Try 'glass back', also fallback to both words present
                $bmConds[] = '(p.body ILIKE ? OR (p.body ILIKE ? AND p.body ILIKE ?))';
                $params[] = $like1;
                $params[] = $like2;
                $params[] = $like3;
            }
        }
        if (!empty($bmConds)) {
            $query .= ' AND (' . implode(' OR ', $bmConds) . ')';
        }
    }

    // IP certificate filter (look in body/features text)
    if (!empty($ipCertificates)) {
        $ipConds = [];
        foreach ($ipCertificates as $ip) {
            $ip = trim($ip);
            // Basic safety: allow only expected values
            if (in_array($ip, ['IP54', 'IP67', 'IP68', 'IP69K'])) {
                $ipConds[] = "(p.body ILIKE '%$ip%' OR p.features ILIKE '%$ip%')";
            }
        }
        if (!empty($ipConds)) {
            $query .= " AND (" . implode(' OR ', $ipConds) . ")";
        }
    }

    // Form factor filter (look in body text)
    if (!empty($formFactors)) {
        $ffConds = [];
        foreach ($formFactors as $ff) {
            $ff = trim($ff);
            if ($ff === 'Bar') {
                $ffConds[] = "(p.body ILIKE '%bar%')";
            } elseif ($ff === 'Flip up' || $ff === 'Flip down') {
                $ffConds[] = "(p.body ILIKE '%flip%')";
            } elseif ($ff === 'Swivel') {
                $ffConds[] = "(p.body ILIKE '%swivel%')";
            } elseif ($ff === 'Slide') {
                $ffConds[] = "(p.body ILIKE '%slide%' OR p.body ILIKE '%slider%')";
            }
        }
        if (!empty($ffConds)) {
            $query .= " AND (" . implode(' OR ', $ffConds) . ")";
        }
    }

    // Fingerprint filters (OR group)
    if (!empty($fingerprintOptions)) {
        $fpConds = [];
        foreach ($fingerprintOptions as $fp) {
            $fp = trim(strtolower($fp));
            if ($fp === 'any') {
                $fpConds[] = "(p.features ILIKE '%fingerprint%' OR p.body ILIKE '%fingerprint%')";
            } elseif ($fp === 'rear') {
                $fpConds[] = "(p.features ILIKE '%rear-mounted%' OR p.body ILIKE '%rear-mounted%' OR p.features ILIKE '%back-mounted%' OR p.body ILIKE '%back-mounted%' OR p.body ILIKE '%rear%')";
            } elseif ($fp === 'side') {
                $fpConds[] = "(p.features ILIKE '%side-mounted%' OR p.body ILIKE '%side-mounted%' OR p.body ILIKE '%side%')";
            } elseif ($fp === 'under_display') {
                $fpConds[] = "(p.features ILIKE '%under display%' OR p.features ILIKE '%under-display%' OR p.features ILIKE '%in-display%' OR p.body ILIKE '%under display%' OR p.body ILIKE '%under-display%' OR p.body ILIKE '%in-display%')";
            }
        }
        if (!empty($fpConds)) {
            $query .= " AND (" . implode(' OR ', $fpConds) . ")";
        }
    }

    // Main Camera features (search in main_camera column)
    if ($mainCameraTelephoto) {
        $query .= " AND (p.main_camera ILIKE '%telephoto%' OR p.main_camera ILIKE '%periscope%' OR p.main_camera ILIKE '%zoom%')";
    }
    if ($mainCameraUltrawide) {
        $query .= " AND (p.main_camera ILIKE '%ultrawide%' OR p.main_camera ILIKE '%ultra-wide%' OR p.main_camera ILIKE '%wide angle%')";
    }
    if ($mainCameraOIS) {
        $query .= " AND (p.main_camera ILIKE '%OIS%' OR p.main_camera ILIKE '%optical image stabilization%' OR p.main_camera ILIKE '%stabilization%')";
    }

    // Selfie Camera features (search in selfie_camera column)
    if ($selfieCameraFlash) {
        $query .= " AND (p.selfie_camera ILIKE '%flash%' OR p.selfie_camera ILIKE '%LED%')";
    }
    if ($popupCamera) {
        $query .= " AND (p.selfie_camera ILIKE '%pop-up%' OR p.selfie_camera ILIKE '%popup%' OR p.selfie_camera ILIKE '%motorized%' OR p.selfie_camera ILIKE '%retractable%')";
    }
    if ($underDisplayCamera) {
        $query .= " AND (p.selfie_camera ILIKE '%under display%' OR p.selfie_camera ILIKE '%under-display%' OR p.selfie_camera ILIKE '%in-display%')";
    }

    // Battery extras (search in battery column)
    if ($batterySiC) {
        $query .= " AND (p.battery ILIKE '%Si/C%' OR p.battery ILIKE '%silicon carbon%' OR p.battery ILIKE '%silicon-carbon%')";
    }
    if ($batteryRemovable) {
        $query .= " AND (p.battery ILIKE '%removable%')";
    }

    // Sorting (Orders)
    $orderBy = "p.id DESC"; // default: newest first
    if (!empty($order)) {
        if ($order === 'price') {
            $orderBy = "p.price ASC NULLS LAST";
        } elseif ($order === 'camera_battery') {
            $orderBy = "CAST(SUBSTRING(p.main_camera FROM '([0-9]+)\\s*MP') AS INTEGER) DESC NULLS LAST, CAST(SUBSTRING(p.battery FROM '([0-9]+)\\s*mAh') AS INTEGER) DESC NULLS LAST";
        } elseif ($order === 'popularity') {
            $orderBy = "p.id DESC"; // fallback to newest
        }
    }
    $query .= " ORDER BY $orderBy LIMIT 50";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process devices to include image and key specs
    $results = [];
    foreach ($devices as $device) {
        // Parse images - matching device.php logic
        $images = [];
        if (!empty($device['images'])) {
            if (is_array($device['images'])) {
                $images = $device['images'];
            } elseif (is_string($device['images'])) {
                // Parse PostgreSQL TEXT[] format: {path1,path2,path3}
                $text = trim($device['images']);
                if ($text !== '' && $text !== '{}') {
                    if ($text[0] === '{' && substr($text, -1) === '}') {
                        $inner = substr($text, 1, -1);
                        if ($inner !== '') {
                            $parts = explode(',', $inner);
                            foreach ($parts as $part) {
                                $cleaned = trim($part);
                                // Remove quotes if present
                                if ((strlen($cleaned) >= 2) &&
                                    (($cleaned[0] === '"' && substr($cleaned, -1) === '"') ||
                                        ($cleaned[0] === "'" && substr($cleaned, -1) === "'"))
                                ) {
                                    $cleaned = substr($cleaned, 1, -1);
                                }
                                if ($cleaned !== '' && $cleaned !== 'NULL') {
                                    $images[] = $cleaned;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Get first image or use fallback from legacy 'image' column
        $thumbnail = '';
        if (!empty($images)) {
            $thumbnail = $images[0];
        } elseif (!empty($device['image'])) {
            $thumbnail = $device['image'];
        } else {
            $thumbnail = 'imges/download.png';
        }

        // Ensure proper path format
        if ($thumbnail !== 'imges/download.png') {
            $thumbnail = str_replace('\\', '/', $thumbnail);
            // Add uploads/ prefix if not already present
            if (strpos($thumbnail, 'uploads/') !== 0 && strpos($thumbnail, '/') !== 0) {
                $thumbnail = 'uploads/' . $thumbnail;
            }
        }        // Parse key specs from JSON columns
        $launchData = !empty($device['launch']) ? json_decode($device['launch'], true) : [];
        $displayData = !empty($device['display']) ? json_decode($device['display'], true) : [];
        $memoryData = !empty($device['memory']) ? json_decode($device['memory'], true) : [];
        $batteryData = !empty($device['battery']) ? json_decode($device['battery'], true) : [];

        // Extract display size
        $displaySize = '';
        if (is_array($displayData)) {
            foreach ($displayData as $item) {
                if (isset($item['field']) && stripos($item['field'], 'Size') !== false) {
                    $displaySize = $item['description'] ?? '';
                    break;
                }
            }
        }

        // Extract RAM
        $ram = '';
        if (is_array($memoryData)) {
            foreach ($memoryData as $item) {
                if (isset($item['field']) && stripos($item['field'], 'RAM') !== false) {
                    $ram = $item['description'] ?? '';
                    break;
                }
            }
        }

        // Extract battery
        $battery = '';
        if (is_array($batteryData)) {
            foreach ($batteryData as $item) {
                if (isset($item['field']) && stripos($item['field'], 'Type') !== false) {
                    $battery = $item['description'] ?? '';
                    break;
                }
            }
        }

        // Get announcement date
        $announced = '';
        if (is_array($launchData)) {
            foreach ($launchData as $item) {
                if (isset($item['field']) && stripos($item['field'], 'Announced') !== false) {
                    $announced = $item['description'] ?? '';
                    break;
                }
            }
        }

        $results[] = [
            'id' => $device['id'],
            'name' => $device['name'],
            'brand' => $device['brand_name'],
            'thumbnail' => $thumbnail,
            'display_size' => $displaySize,
            'ram' => $ram,
            'battery' => $battery,
            'announced' => $announced
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'devices' => $results
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
