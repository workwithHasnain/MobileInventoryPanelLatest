<?php
// Handler for phonefinder AJAX requests
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
