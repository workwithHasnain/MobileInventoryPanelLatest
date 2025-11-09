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

    // Order by newest first
    $query .= " ORDER BY p.id DESC LIMIT 50";

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
