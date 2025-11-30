<?php
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once 'database_functions.php';
require_once 'phone_data.php';

// Function to convert USD to EUR using free API
function convertUSDtoEUR($usd_amount)
{
  try {
    // Using exchangerate-api.com free tier (no key required for basic usage)
    $api_url = "https://open.er-api.com/v6/latest/USD";

    $context = stream_context_create([
      'http' => [
        'timeout' => 3 // 3 second timeout
      ]
    ]);

    $response = @file_get_contents($api_url, false, $context);

    if ($response === false) {
      return null; // API call failed
    }

    $data = json_decode($response, true);

    if (isset($data['rates']['EUR'])) {
      $eur_rate = $data['rates']['EUR'];
      return $usd_amount * $eur_rate;
    }

    return null;
  } catch (Exception $e) {
    return null;
  }
}

// Function to extract price from misc JSON column
function extractPriceFromMisc($miscJson)
{
  if (!isset($miscJson) || $miscJson === '' || $miscJson === null) {
    return null;
  }

  $decoded = json_decode($miscJson, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
    return null;
  }

  // Search for price field in misc data
  foreach ($decoded as $row) {
    $field = isset($row['field']) ? trim(strtolower((string)$row['field'])) : '';
    $desc = isset($row['description']) ? trim((string)$row['description']) : '';

    if ($field === 'price' && $desc !== '') {
      // Extract numeric value from description (e.g., "$999" or "999 USD" or "999")
      $priceStr = preg_replace('/[^0-9.]/', '', $desc);
      if ($priceStr !== '' && is_numeric($priceStr)) {
        return (float)$priceStr;
      }
    }
  }

  return null;
}


// Get posts and devices for display (case-insensitive status check) with comment counts
$pdo = getConnection();
$posts_stmt = $pdo->prepare("
    SELECT p.*, 
    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
    FROM posts p 
    WHERE p.status ILIKE 'published' 
    ORDER BY p.created_at DESC 
    LIMIT 6
");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();

// Get devices from database
$devices = getAllPhones();
$devices = array_slice($devices, 0, 6); // Limit to 6 devices for home page

// Add comment counts to devices
foreach ($devices as $index => $device) {
  $comment_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_comments WHERE device_id = CAST(? AS VARCHAR) AND status = 'approved'");
  $comment_stmt->execute([$device['id']]);
  $devices[$index]['comment_count'] = $comment_stmt->fetch()['count'] ?? 0;
}

// Get data for the three tables
$topViewedDevices = [];
$topReviewedDevices = [];
$topComparisons = [];

// Get top comparisons from database
try {
  $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
  $topComparisons = [];
}

// Get top viewed devices
try {
  $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(cv.id) as view_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN content_views cv ON CAST(p.id AS VARCHAR) = cv.content_id AND cv.content_type = 'device'
        GROUP BY p.id, b.name
        ORDER BY view_count DESC
        LIMIT 10
    ");
  $stmt->execute();
  $topViewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topViewedDevices = [];
}

// Get top reviewed devices (by comment count)
try {
  $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(dc.id) as review_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN device_comments dc ON CAST(p.id AS VARCHAR) = dc.device_id AND dc.status = 'approved'
        GROUP BY p.id, b.name
        ORDER BY review_count DESC
        LIMIT 10
    ");
  $stmt->execute();
  $topReviewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topReviewedDevices = [];
}


// Get latest 9 devices for the new section
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 9); // Get latest 9 devices

// Get only brands that have devices for the brands table
$brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get device ID from URL
$device_id = $_GET['id'] ?? '';

if (!isset($_GET['id']) || $_GET['id'] === '') {
  header("Location: index.php");
  exit();
}

// Function to get all device images
function getDeviceImages($device)
{
  $images = [];

  // Add main image if available
  if (!empty($device['image'])) {
    $images[] = $device['image'];
  }

  // Add additional numbered images if available (from JSON fallback)
  for ($i = 1; $i <= 10; $i++) {
    $imageKey = 'image_' . $i;
    if (!empty($device[$imageKey])) {
      if (!in_array($device[$imageKey], $images)) { // Avoid duplicates
        $images[] = $device[$imageKey];
      }
    }
  }

  // Add images from 'images' array if available (from database or JSON)
  if (!empty($device['images'])) {
    $imageArray = [];

    if (is_array($device['images'])) {
      // Already an array
      $imageArray = $device['images'];
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
                $imageArray[] = $cleaned;
              }
            }
          }
        }
      }
    }

    // Add parsed images
    foreach ($imageArray as $image) {
      if (!empty($image) && !in_array($image, $images)) { // Avoid duplicates
        $images[] = $image;
      }
    }
  }

  // Filter out empty paths and ensure they are properly formatted
  $validImages = [];
  foreach ($images as $image) {
    if (!empty($image)) {
      // Clean up the path
      $image = str_replace('\\', '/', $image);
      // Add to valid images if not already included
      if (!in_array($image, $validImages)) {
        $validImages[] = $image;
      }
    }
  }

  return $validImages;
}

// Function to get device details
function getDeviceDetails($pdo, $device_id)
{
  // Try database first (comprehensive data source)
  try {
    $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.id = ?
        ");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($device) {
      // Fix image paths if needed
      if (isset($device['image'])) {
        $device['image'] = str_replace('\\', '/', $device['image']);
        $device['image_1'] = $device['image'];
      }
      return $device;
    }
  } catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
  }

  // Fallback to JSON files if database fails
  $phones_json = 'data/phones.json';
  if (file_exists($phones_json)) {
    $phones_data = json_decode(file_get_contents($phones_json), true);

    // JSON stores as array, so search by index
    if (is_array($phones_data)) {
      // Use numeric index as device ID
      // Convert string ID to integer for array access
      $numeric_id = is_numeric($device_id) ? (int)$device_id : $device_id;
      if (isset($phones_data[$numeric_id])) {
        $device = $phones_data[$numeric_id];

        // Add computed fields for compatibility
        $device['id'] = $device_id;
        $device['image_1'] = $device['image'] ?? '';

        // Fix image paths
        if (isset($device['image'])) {
          $device['image_1'] = str_replace('\\', '/', $device['image']);
        }

        // Handle multiple images
        if (!empty($device['images'])) {
          for ($i = 0; $i < count($device['images']) && $i < 5; $i++) {
            $device['image_' . ($i + 1)] = str_replace('\\', '/', $device['images'][$i]);
          }
        }

        return $device;
      }
    }
  }

  return null;
}

// Function to format device specifications for display
function formatDeviceSpecs($device)
{
  $specs = [];

  // Helper: render a section from JSON stored as TEXT in DB
  $renderJsonSection = function ($jsonValue, $sectionName = '') {
    if (!isset($jsonValue) || $jsonValue === '' || $jsonValue === null) return null;
    $decoded = json_decode($jsonValue, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
      // If it's not valid JSON but has text, just return as-is (escaped)
      return htmlspecialchars((string)$jsonValue);
    }
    $parts = [];
    foreach ($decoded as $row) {
      $field = isset($row['field']) ? trim((string)$row['field']) : '';
      $desc = isset($row['description']) ? trim((string)$row['description']) : '';
      if ($field === '' && $desc === '') continue;
      if ($field !== '') {
        $line = '<strong>' . htmlspecialchars($field) . '</strong>';
        if ($desc !== '') {
          $line .= ' ' . htmlspecialchars($desc);

          // Add EUR conversion for price field in GENERAL INFO section
          if ($sectionName === 'GENERAL INFO' && strtolower($field) === 'price') {
            $priceStr = preg_replace('/[^0-9.]/', '', $desc);
            if ($priceStr !== '' && is_numeric($priceStr)) {
              $priceUsd = (float)$priceStr;
              $priceEur = convertUSDtoEUR($priceUsd);
              if ($priceEur !== null) {
                $line .= ' / €' . number_format($priceEur, 2);
              }
            }
          }
        }
        $parts[] = $line;
      } else {
        $parts[] = htmlspecialchars($desc);
      }
    }
    return implode('<br>', $parts);
  };

  // Prefer new grouped JSON columns when present; otherwise fall back to legacy fields
  $jsonSections = [
    'NETWORK' => $device['network'] ?? null,
    'LAUNCH' => $device['launch'] ?? null,
    'BODY' => $device['body'] ?? null,
    'DISPLAY' => $device['display'] ?? null,
    'HARDWARE' => $device['hardware'] ?? null,
    'MEMORY' => $device['memory'] ?? null,
    'MAIN CAMERA' => $device['main_camera'] ?? null,
    'SELFIE CAMERA' => $device['selfie_camera'] ?? null,
    'MULTIMEDIA' => $device['multimedia'] ?? null,
    'CONNECTIVITY' => $device['connectivity'] ?? null,
    'FEATURES' => $device['features'] ?? null,
    'BATTERY' => $device['battery'] ?? null,
    'GENERAL INFO' => $device['general_info'] ?? null,
  ];

  foreach ($jsonSections as $label => $raw) {
    $html = $renderJsonSection($raw, $label);
    if ($html) {
      $specs[$label] = $html;
    }
  }

  // Legacy fallback: Network
  if (!isset($specs['NETWORK'])) {
    $network_tech = [];
    if (!empty($device['network_2g'])) $network_tech[] = '2G';
    if (!empty($device['network_3g'])) $network_tech[] = '3G';
    if (!empty($device['network_4g'])) $network_tech[] = '4G';
    if (!empty($device['network_5g'])) $network_tech[] = '5G';

    if (!empty($network_tech)) {
      $network_details = '<strong>Technology</strong> ' . implode(' / ', $network_tech);
      if (!empty($device['dual_sim'])) $network_details .= '<br><strong>Connectivity Slot</strong> Dual SIM';
      if (!empty($device['esim'])) $network_details .= ', eSIM';
      if (!empty($device['sim_size'])) $network_details .= ' (' . $device['sim_size'] . ')';
      $specs['NETWORK'] = $network_details;
    }
  }

  // Legacy fallback: Launch (also add price if JSON not used)
  if (!isset($specs['LAUNCH']) && (!empty($device['release_date']) || !empty($device['availability']) || !empty($device['price']))) {
    $launch_details = '';
    if (!empty($device['release_date'])) {
      $launch_details .= '<strong>Released</strong> ' . date('F j, Y', strtotime($device['release_date']));
    }
    if (!empty($device['availability'])) {
      if ($launch_details) $launch_details .= '<br>';
      $launch_details .= '<strong>Availability</strong> ' . $device['availability'];
    }
    if (!empty($device['price'])) {
      $price_usd = number_format($device['price'], 2);
      $launch_details .= '<br><strong>Price</strong> $' . $price_usd;

      // Add Euro conversion
      $price_eur = convertUSDtoEUR($device['price']);
      if ($price_eur !== null) {
        $launch_details .= ' / €' . number_format($price_eur, 2);
      }
    }
    $specs['LAUNCH'] = $launch_details;
  }

  // Legacy fallback: Body
  if (!isset($specs['BODY']) && (!empty($device['dimensions']) || !empty($device['height']) || !empty($device['width']) || !empty($device['thickness']) || !empty($device['weight']))) {
    $body_details = '';
    if (!empty($device['dimensions'])) {
      $body_details .= '<strong>Dimensions</strong> ' . $device['dimensions'];
    } elseif (!empty($device['height']) && !empty($device['width']) && !empty($device['thickness'])) {
      $body_details .= '<strong>Dimensions</strong> ' .
        $device['height'] . ' x ' .
        $device['width'] . ' x ' .
        $device['thickness'] . ' mm';
    }
    if (!empty($device['weight'])) {
      if ($body_details) $body_details .= '<br>';
      $body_details .= '<strong>Weight</strong> ' . $device['weight'] . ' g';
    }
    $specs['BODY'] = $body_details;
  }

  // Legacy fallback: Display
  if (!isset($specs['DISPLAY']) && (!empty($device['display_type']) || !empty($device['display_size']) || !empty($device['display_resolution']))) {
    $display_details = '';
    if (!empty($device['display_type'])) {
      $display_details .= '<strong>Type</strong> ' . $device['display_type'];
      if (!empty($device['display_technology'])) $display_details .= ', ' . $device['display_technology'];
      if (!empty($device['refresh_rate'])) $display_details .= ', ' . $device['refresh_rate'] . 'Hz';
      if (!empty($device['hdr'])) $display_details .= ', HDR';
      if (!empty($device['billion_colors'])) $display_details .= ', 1B colors';
    }
    if (!empty($device['display_size'])) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Size</strong> ' . $device['display_size'] . ' inches';
    }
    if (!empty($device['display_resolution'])) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Resolution</strong> ' . $device['display_resolution'];
    }
    $specs['DISPLAY'] = $display_details;
  }

  // Legacy fallback: Hardware
  if (!isset($specs['HARDWARE']) && (!empty($device['os']) || !empty($device['chipset_name']) || !empty($device['cpu_cores']) || !empty($device['gpu']))) {
    $platform_details = '';
    if (!empty($device['os'])) {
      $platform_details .= '<strong>OS</strong> ' . $device['os'];
    }
    if (!empty($device['chipset_name'])) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>System Chip</strong> ' . $device['chipset_name'];
    }
    if (!empty($device['cpu_cores']) || !empty($device['cpu_frequency'])) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>Processor</strong> ';
      if (!empty($device['cpu_cores'])) $platform_details .= $device['cpu_cores'] . '-core';
      if (!empty($device['cpu_frequency'])) $platform_details .= ' (' . $device['cpu_frequency'] . ' GHz)';
    }
    if (!empty($device['gpu'])) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>GPU</strong> ' . $device['gpu'];
    }
    $specs['HARDWARE'] = $platform_details;
  }

  // Legacy fallback: System Memory
  if (!isset($specs['MEMORY']) && (!empty($device['ram']) || !empty($device['storage']) || !empty($device['card_slot']))) {
    $memory_details = '';
    if (!empty($device['card_slot'])) {
      $memory_details .= '<strong>Expansion Slot</strong> ' . htmlspecialchars($device['card_slot']);
    }
    if (!empty($device['storage']) || !empty($device['ram'])) {
      if ($memory_details) $memory_details .= '<br>';
      $memory_details .= '<strong>Storage</strong> ';
      if (!empty($device['storage'])) $memory_details .= $device['storage'];
      if (!empty($device['ram'])) $memory_details .= ' RAM: ' . $device['ram'];
    }
    $specs['MEMORY'] = $memory_details;
  }

  // Legacy fallback: Main Camera
  if (!isset($specs['MAIN CAMERA']) && (!empty($device['main_camera_resolution']) || !empty($device['main_camera_count']))) {
    $camera_details = '';
    if (!empty($device['main_camera_count']) && $device['main_camera_count'] > 1) {
      $camera_details .= '<strong>' . ucfirst(convertNumberToWord($device['main_camera_count'])) . '</strong><br>';
    } else {
      $camera_details .= '<strong>Single</strong><br>';
    }
    if (!empty($device['main_camera_resolution'])) {
      $camera_details .= $device['main_camera_resolution'];
    }

    // Camera features
    $features = [];
    if (!empty($device['main_camera_ois'])) $features[] = 'OIS';
    if (!empty($device['main_camera_telephoto'])) $features[] = 'Telephoto';
    if (!empty($device['main_camera_ultrawide'])) $features[] = 'Ultrawide';
    if (!empty($device['main_camera_macro'])) $features[] = 'Macro';
    if (!empty($device['main_camera_flash'])) $features[] = 'Flash';
    if (isset($device['main_camera_features']) && is_array($device['main_camera_features'])) {
      $features = array_merge($features, $device['main_camera_features']);
    } elseif (isset($device['main_camera_features']) && is_string($device['main_camera_features'])) {
      // Handle PostgreSQL array string format
      $array_features = str_replace(['{', '}'], '', $device['main_camera_features']);
      $array_features = explode(',', $array_features);
      $features = array_merge($features, array_map('trim', $array_features));
    }

    if (!empty($features)) {
      $camera_details .= '<br><strong>Features</strong> ' . implode(', ', $features);
    }
    if (!empty($device['main_camera_video'])) {
      $camera_details .= '<br><strong>Video Recording</strong> ' . $device['main_camera_video'];
    }
    $specs['MAIN CAMERA'] = $camera_details;
  }

  // Legacy fallback: Selfie Camera
  if (!isset($specs['SELFIE CAMERA']) && (!empty($device['selfie_camera_resolution']) || !empty($device['selfie_camera_count']))) {
    $selfie_details = '';
    if (!empty($device['selfie_camera_count']) && $device['selfie_camera_count'] > 1) {
      $selfie_details .= '<strong>' . ucfirst(convertNumberToWord($device['selfie_camera_count'])) . '</strong> ';
    } else {
      $selfie_details .= '<strong>Single</strong> ';
    }
    if (!empty($device['selfie_camera_resolution'])) {
      $selfie_details .= $device['selfie_camera_resolution'];
    }
    if (isset($device['selfie_camera_features']) && is_array($device['selfie_camera_features'])) {
      $selfie_details .= '<br><strong>Features</strong> ' . implode(', ', $device['selfie_camera_features']);
    } elseif (isset($device['selfie_camera_features']) && is_string($device['selfie_camera_features'])) {
      // Handle PostgreSQL array string format
      $array_features = str_replace(['{', '}'], '', $device['selfie_camera_features']);
      $array_features = explode(',', $array_features);
      $selfie_details .= '<br><strong>Features</strong> ' . implode(', ', array_map('trim', $array_features));
    }
    if (!empty($device['selfie_camera_video'])) {
      $selfie_details .= '<br><strong>Video Recording</strong> ' . $device['selfie_camera_video'];
    }
    $specs['SELFIE CAMERA'] = $selfie_details;
  }

  // Legacy fallback: Multimedia
  if (!isset($specs['MULTIMEDIA']) && (isset($device['dual_speakers']) || isset($device['headphone_jack']))) {
    $sound_details = '';
    if (isset($device['dual_speakers']) && $device['dual_speakers'] !== null) {
      $sound_details .= '<strong>Audio Output</strong> ' . ($device['dual_speakers'] ? 'Yes' : 'No');
    }
    if (isset($device['headphone_jack']) && $device['headphone_jack'] !== null) {
      if ($sound_details) $sound_details .= '<br>';
      $sound_details .= '<strong>3.5mm jack</strong> ' . ($device['headphone_jack'] ? 'Yes' : 'No');
    }
    $specs['MULTIMEDIA'] = $sound_details;
  }

  // Legacy fallback: Connectivity
  if (!isset($specs['CONNECTIVITY'])) {
    $comms_details = '';
    if (!empty($device['wifi'])) {
      $comms_details .= '<strong>WLAN</strong> ' . $device['wifi'];
    }
    if (!empty($device['bluetooth'])) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>Bluetooth</strong> ' . $device['bluetooth'];
    }
    if (isset($device['gps']) && $device['gps'] !== null) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>Location</strong> ' . ($device['gps'] ? 'GPS' : 'No');
    }
    if (isset($device['nfc']) && $device['nfc'] !== null) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>Proximity</strong> ' . ($device['nfc'] ? 'Yes' : 'No');
    }
    if (isset($device['fm_radio']) && $device['fm_radio'] !== null) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>Radio</strong> ' . ($device['fm_radio'] ? 'Yes' : 'No');
    }
    if (!empty($device['usb'])) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>USB</strong> ' . $device['usb'];
    }
    if ($comms_details) {
      $specs['CONNECTIVITY'] = $comms_details;
    }
  }

  // Legacy fallback: Features
  if (!isset($specs['FEATURES'])) {
    $features_details = '';
    if (isset($device['fingerprint']) && $device['fingerprint'] !== null) {
      $features_details .= '<strong>Fingerprint</strong> ' . ($device['fingerprint'] ? 'Yes' : 'No');
    }

    // Build sensors list from individual sensor fields
    $sensors = [];
    if (!empty($device['accelerometer'])) $sensors[] = 'Accelerometer';
    if (!empty($device['gyro'])) $sensors[] = 'Gyro';
    if (!empty($device['compass'])) $sensors[] = 'Compass';
    if (!empty($device['proximity'])) $sensors[] = 'Proximity';
    if (!empty($device['barometer'])) $sensors[] = 'Barometer';
    if (!empty($device['heart_rate'])) $sensors[] = 'Heart Rate';

    if (!empty($sensors)) {
      if ($features_details) $features_details .= '<br>';
      $features_details .= '<strong>Sensors</strong> ' . implode(', ', $sensors);
    }

    if ($features_details) {
      $specs['FEATURES'] = $features_details;
    }
  }

  // Legacy fallback: Battery
  if (!isset($specs['BATTERY']) && (!empty($device['battery_capacity']) || !empty($device['battery_sic']))) {
    $battery_details = '';
    if (!empty($device['battery_capacity'])) {
      $battery_details .= '<strong>Capacity</strong> ' . $device['battery_capacity'];
      if (!empty($device['battery_sic'])) $battery_details .= ' (Silicon)';
    }

    if (isset($device['battery_removable']) && $device['battery_removable'] !== null) {
      $battery_details .= '<br><strong>Removable</strong> ' . ($device['battery_removable'] ? 'Yes' : 'No');
    }

    // Charging information
    $charging = [];
    if (!empty($device['wired_charging'])) $charging[] = 'Wired: ' . $device['wired_charging'];
    if (!empty($device['wireless_charging'])) $charging[] = 'Wireless: ' . $device['wireless_charging'];

    if (!empty($charging)) {
      $battery_details .= '<br><strong>Charging</strong> ' . implode(', ', $charging);
    }

    $specs['BATTERY'] = $battery_details;
  }

  // Colors (legacy field) - keep if present
  if (isset($device['colors']) && is_array($device['colors'])) {
    $specs['COLORS'] = '<strong>Available</strong> ' . implode(', ', $device['colors']);
  } elseif (isset($device['colors']) && is_string($device['colors'])) {
    // Handle PostgreSQL array string format
    $array_colors = str_replace(['{', '}'], '', $device['colors']);
    $array_colors = explode(',', $array_colors);
    $specs['COLORS'] = '<strong>Available</strong> ' . implode(', ', array_map('trim', $array_colors));
  }

  return $specs;
}

// Helper function to convert numbers to words
function convertNumberToWord($num)
{
  $words = ['', 'single', 'dual', 'triple', 'quad', 'penta', 'hexa', 'hepta', 'octa'];
  return isset($words[$num]) ? $words[$num] : $num;
}

// Function to generate device highlights for top section
function generateDeviceHighlights($device)
{
  $highlights = [];

  // Release date highlight
  if (!empty($device['release_date'])) {
    $release_date = date('F j, Y', strtotime($device['release_date']));
    $highlights['release'] = "📅 Released " . $release_date;
  } elseif (!empty($device['year'])) {
    $highlights['release'] = "📅 Released " . $device['year'];
  }

  // Weight and dimensions highlight
  $weight_dims = [];
  if (!empty($device['weight'])) {
    $weight_dims[] = $device['weight'] . 'g';
  }
  if (!empty($device['thickness'])) {
    $weight_dims[] = $device['thickness'] . 'mm thickness';
  }
  if (!empty($weight_dims)) {
    $highlights['weight_dims'] = "⚖️ " . implode(', ', $weight_dims);
  }

  // OS highlight
  if (!empty($device['os'])) {
    $highlights['os'] = "🆔 " . $device['os'];
  }

  // Storage highlight
  $storage_parts = [];
  if (!empty($device['storage'])) {
    $storage_parts[] = $device['storage'] . ' storage';
  }
  if (!empty($device['card_slot'])) {
    if (strtolower($device['card_slot']) === 'no') {
      $storage_parts[] = 'no card slot';
    } elseif (strtolower($device['card_slot']) === 'yes') {
      $storage_parts[] = 'expandable';
    }
  }
  if (!empty($storage_parts)) {
    $highlights['storage'] = "💾 " . implode(', ', $storage_parts);
  }

  return $highlights;
}

// Function to generate device stats for the stats bar
function generateDeviceStats($device)
{
  $stats = [];

  // Display stats
  $display_title = !empty($device['display_size']) ? $device['display_size'] . '"' : 'N/A';
  $display_subtitle = $device['display_resolution'] ?? 'Unknown';
  $stats['display'] = [
    'icon' => 'imges/vrer.png',
    'title' => $display_title,
    'subtitle' => $display_subtitle
  ];

  // Camera stats
  $camera_title = 'N/A';
  $camera_subtitle = 'N/A';
  $resolutionText = (string)($device['main_camera_resolution'] ?? '');
  if (!empty($device['main_camera_resolution'])) {
    // Extract MP from resolution
    if (preg_match('/(\d+)\s*MP/', $resolutionText, $matches)) {
      $camera_title = $matches[1] . 'MP';
    } else {
      // If no MP pattern found, use the raw value
      $camera_title = $resolutionText;
    }
  }
  if (!empty($device['main_camera_video'])) {
    $camera_subtitle = $device['main_camera_video'];
  } elseif ($resolutionText !== '' && strpos($resolutionText, '4K') !== false) {
    $camera_subtitle = '4K';
  } else {
    $camera_subtitle = '1080p';
  }
  $stats['camera'] = [
    'icon' => 'imges/bett-removebg-preview.png',
    'title' => $camera_title,
    'subtitle' => $camera_subtitle
  ];

  // Performance stats (RAM + Chipset)
  $perf_title = 'N/A';
  $perf_subtitle = 'Unknown';
  if (!empty($device['ram'])) {
    $perf_title = $device['ram'];
  }
  if (!empty($device['chipset_name'])) {
    // Simplify chipset name
    $chipset = $device['chipset_name'];
    if (strpos($chipset, 'Snapdragon') !== false) {
      $perf_subtitle = 'Snapdragon';
    } elseif (strpos($chipset, 'Apple') !== false) {
      $perf_subtitle = 'Apple';
    } elseif (strpos($chipset, 'Dimensity') !== false) {
      $perf_subtitle = 'Dimensity';
    } elseif (strpos($chipset, 'Exynos') !== false) {
      $perf_subtitle = 'Exynos';
    } else {
      $perf_subtitle = $chipset;
    }
  }
  $stats['performance'] = [
    'icon' => 'imges/encypt-removebg-preview.png',
    'title' => $perf_title,
    'subtitle' => $perf_subtitle
  ];

  // Battery stats
  $battery_title = !empty($device['battery_capacity']) ? $device['battery_capacity'] : 'N/A';
  $battery_subtitle = 'N/A';
  if (!empty($device['wired_charging'])) {
    $battery_subtitle = $device['wired_charging'];
  } elseif (!empty($device['wireless_charging'])) {
    $battery_subtitle = $device['wireless_charging'] . ' wireless';
  }
  $stats['battery'] = [
    'icon' => 'imges/lowtry-removebg-preview.png',
    'title' => $battery_title,
    'subtitle' => $battery_subtitle
  ];

  return $stats;
}

// Function to get device comments
function getDeviceComments($pdo, $device_id)
{
  try {
    $stmt = $pdo->prepare("
            SELECT * FROM device_comments 
            WHERE device_id = ? AND status = 'approved'
            ORDER BY created_at DESC
        ");
    $stmt->execute([$device_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    return [];
  }
}

// Function to get device comment count
function getDeviceCommentCount($pdo, $device_id)
{
  try {
    $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM device_comments 
            WHERE device_id = ? AND status = 'approved'
        ");
    $stmt->execute([$device_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
  } catch (PDOException $e) {
    return 0;
  }
}

// Function to generate gravatar URL
function getGravatarUrl($email, $size = 50)
{
  $hash = md5(strtolower(trim($email)));
  return "https://www.gravatar.com/avatar/{$hash}?r=g&s={$size}&d=identicon";
}

// Function to format time ago
function timeAgo($datetime)
{
  $time = time() - strtotime($datetime);

  if ($time < 60) return 'just now';
  if ($time < 3600) return floor($time / 60) . ' minutes ago';
  if ($time < 86400) return floor($time / 3600) . ' hours ago';
  if ($time < 2592000) return floor($time / 86400) . ' days ago';
  if ($time < 31536000) return floor($time / 2592000) . ' months ago';

  return floor($time / 31536000) . ' years ago';
}

// Function to generate avatar display
function getAvatarDisplay($name, $email)
{
  if (!empty($email)) {
    return '<img src="' . getGravatarUrl($email) . '" alt="' . htmlspecialchars($name) . '">';
  } else {
    $initial = strtoupper(substr($name, 0, 1));
    return '<span class="avatar-box">' . htmlspecialchars($initial) . '</span>';
  }
}

// Function to track view
function trackDeviceView($pdo, $device_id, $ip_address)
{
  try {
    $today = date('Y-m-d');

    // Check if this IP already viewed this device today
    $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM content_views 
            WHERE content_id = ? AND content_type = 'device' AND ip_address = ? AND DATE(viewed_at) = ?
        ");
    $stmt->execute([$device_id, $ip_address, $today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
      // Insert new view record
      $stmt = $pdo->prepare("
                INSERT INTO content_views (content_id, content_type, ip_address, viewed_at) 
                VALUES (?, 'device', ?, NOW())
            ");
      $stmt->execute([$device_id, $ip_address]);
    }
  } catch (PDOException $e) {
    error_log("View tracking error: " . $e->getMessage());
  }
}

// Get device details
$device = getDeviceDetails($pdo, $device_id);

if (!$device) {
  header("Location: 404.php");
  exit();
}

// Get all available images for this device
$deviceImages = getDeviceImages($device);

// Format device specifications for display
$deviceSpecs = formatDeviceSpecs($device);

// Generate device highlights and stats for the top section
$deviceHighlights = generateDeviceHighlights($device);
$deviceStats = generateDeviceStats($device);

// Track view
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
trackDeviceView($pdo, $device_id, $ip_address);

// Get comments
$comments = getDeviceComments($pdo, $device_id);

// Get comment count
$commentCount = getDeviceCommentCount($pdo, $device_id);

// Handle comment submission
if ($_POST && isset($_POST['submit_comment'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $comment = trim($_POST['comment'] ?? '');

  if (!empty($name) && !empty($comment)) {
    try {
      $stmt = $pdo->prepare("
                INSERT INTO device_comments (device_id, name, email, comment, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
      $stmt->execute([$device_id, $name, $email, $comment]);

      $success_message = "Thank you! Your comment has been submitted and is awaiting approval.";

      // Refresh comments and count after submission
      $comments = getDeviceComments($pdo, $device_id);
      $commentCount = getDeviceCommentCount($pdo, $device_id);
    } catch (PDOException $e) {
      $error_message = "Error submitting comment. Please try again.";
    }
  } else {
    $error_message = "Please fill in all required fields.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> - Specifications & Reviews | GSMArena</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>

  <!-- Font Awesome (for icons) -->
  <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script>

  </script>

  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #EFEBE9; overflow-x: hidden;">
  <!-- Desktop Navbar of Gsmarecn -->
  <div class="main-wrapper">
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark  d-lg-inline d-none" id="navbar">
      <div class="container const d-flex align-items-center justify-content-between">
        <button class="navbar-toggler mb-2" type="button" onclick="toggleMenu()">
          <img style="height: 40px;"
            src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
            alt="">
        </button>

        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="imges/download.png" alt="GSMArena Logo" />
        </a>

        <div class="controvecy mb-2">
          <div class="icon-container">
            <button type="button" class="btn border-right" data-bs-toggle="tooltip" data-bs-placement="left"
              title="YouTube">
              <img src="iccons/youtube-color-svgrepo-com.svg" alt="YouTube" width="30px">
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Instagram">
              <img src="iccons/instagram-color-svgrepo-com.svg" alt="Instagram" width="22px">
            </button>
          </div>
        </div>

        <form action="" class="central d-flex align-items-center">
          <input type="text" class="no-focus-border" placeholder="Search">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="24" width="24" class="ms-2">
            <path fill="#ffffff"
              d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
          </svg>
        </form>
      </div>

    </nav>

  </div>
  <!-- Mobile Navbar of Gsmarecn -->
  <nav id="navbar" class="mobile-navbar d-lg-none d-flex justify-content-between  align-items-center">

    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu"
      aria-controls="mobileMenu" aria-expanded="false" aria-label="Toggle navigation">
      <img style="height: 40px;"
        src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
        alt="">
    </button>
    <a class="navbar-brand d-flex align-items-center" href="#">
      <a class="logo text-white " href="#">GSMArena</a>
    </a>
    <div class="d-flex justify-content-end">
      <button type="button" class="btn float-end ml-5" data-bs-toggle="tooltip" data-bs-placement="left">
        <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
      </button>
      <button type="button" class="btn float-end " data-bs-toggle="tooltip" data-bs-placement="left">
        <i class="fa-solid fa-user-plus fa-lg" style="color: #ffffff;"></i>
      </button>
    </div>
  </nav>
  <!-- Mobile Collapse of Gsmarecn -->
  <div class="collapse mobile-menu d-lg-none" id="mobileMenu">
    <div class="menu-icons">
      <i class="fas fa-home"></i>
      <i class="fab fa-facebook-f"></i>
      <i class="fab fa-instagram"></i>
      <i class="fab fa-tiktok"></i>
      <i class="fas fa-share-alt"></i>
    </div>
    <div class="column">
      <a href="index.php">Home</a>
      <a href="reviews.php">Reviews</a>
      <a href="#">Videos</a>
      <a href="featured.php">Featured</a>
      <a href="phonefinder.php">Phone Finder</a>
      <a href="compare.php">Compare</a>
      <a href="#">Contact Us</a>
    </div>
    <div class="brand-grid">
      <?php
      $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
      foreach ($brandChunks as $brandRow):
        foreach ($brandRow as $brand): ?>
          <a href="#" class="brand-cell" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
      <?php endforeach;
      endforeach; ?>
      <a href="#" onclick="showBrandsModal(); return false;" style="cursor: pointer;">[...]</a>
    </div>
    <div class="menu-buttons d-flex justify-content-center ">
      <button class="btn btn-danger w-50">📱 Phone Finder</button>
      <button class="btn btn-primary w-50">📲 My Phone</button>
    </div>
  </div>
  <!-- Display Menu of Gsmarecn -->
  <div id="leftMenu" class="container show">
    <div class="row">
      <div class="col-12 d-flex align-items-center   colums-gap">
        <a href="index.php" class="nav-link navbar-bold">Home</a>
        <a href="compare.php" class="nav-link navbar-bold">Compare</a>
        <a href="#" class="nav-link navbar-bold">Videos</a>
        <a href="reviews.php" class="nav-link navbar-bold">Reviews</a>
        <a href="featured.php" class="nav-link d-lg-block d-none navbar-bold">Featured</a>
        <a href="phonefinder.php" class="nav-link d-lg-block d-none navbar-bold">Phone Finder</a>
        <a href="#" class="nav-link d-lg-block d-none navbar-bold">Contact</a>
        <div style="background-color: #d50000; border-radius: 7px;" class="d-lg-none py-2"><svg
            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="16" width="16" class="mx-3">
            <path fill="#ffffff"
              d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
          </svg></div>
      </div>
    </div>
  </div>
  <style>
    span {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      color: black;
      font-size: 12px;
      font-weight: 300;
    }

    .stat-item {
      align-items: center;
      height: 98px;
      width: 131px;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      display: flex;
      margin: 5px 0 12px;
      border-left: 1px solid #ccc;
      float: left;
      padding: 0px 10px;
      text-shadow: 1px 1px 0px hsla(0, 0%, 100%, .4) !important;

      position: relative;
      z-index: 1;
    }

    /* Desktop (side by side) */
    .specs-table tr {
      display: grid;
      grid-template-columns: 200px 1fr;
      /* left fixed, right flexible */
    }

    /* Mobile (stack) */
    @media (max-width: 768px) {
      .specs-table tr {
        grid-template-columns: 1fr;
        /* single column */
      }
    }

    .spec-title {
      font-weight: 400;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
      font-size: 1.5rem;
      color: black;
    }

    .stat-item {
      /* padding: 24px; */
      border-left: 1px solid hsla(0, 0%, 100%, .5);
    }

    .spec-item {
      padding: 20px;
      display: flex;
      row-gap: 8px;
      flex-direction: column;
      align-items: baseline;
      justify-content: space-around;
    }

    .stat-item :nth-child(1) {
      font-size: 1.6rem;
      font-weight: 600;
      text-shadow: 1px 1px 1px rgba(0, 0, 0, .4);
    }

    .stat-item :nth-child(2) {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
      text-shadow: 1px 1px 1px rgba(0, 0, 0, .4);
    }

    .bg-blur {
      position: relative;
      z-index: 1;
    }

    .bg-blur::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      /* background: linear-gradient(135deg, rgba(107, 115, 255, 0.7), rgba(0, 13, 255, 0.7)); */
      filter: blur(8px);
      z-index: 0;
      border-radius: 8px;
    }

    .bg-blur>* {
      position: relative;
      z-index: 2;
    }


    .spec-subtitle {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
      font-weight: 100;
      font-size: 14px;
      color: black;
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-word;
      max-width: 100%;
    }


    .card-header {
      position: relative;
      overflow: hidden;
    }

    .card-header::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: inherit;
      /* same background lega */
      filter: blur(5px);
      z-index: 1;
    }

    .card-header * {
      position: relative;
      z-index: 2;
      /* content clear dikhayega */
    }

    .vr-hide {
      float: left;
      padding-left: 10px;
      font: 300 28px / 47px system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
      text-shadow: none;
      color: #fff;
      margin-bottom: 0px;
      margin-top: -2px;
    }

    .phone-image:after {
      content: "";
      position: absolute;
      top: 0;
      left: 165px;
      width: 229px;
      height: 100%;
      background: linear-gradient(90deg, #fff 0%, #fcfeff 2%, rgba(125, 185, 232, 0));
      z-index: 1;
    }

    .phone-image {
      display: block;
      height: -webkit-fill-available;
      width: 165px;
      position: relative;
      z-index: 2;
      background: #fff;
    }

    tr {
      background-color: white;
      margin-bottom: 10px;
    }

    table td,
    table th {
      vertical-align: top;
      padding: 8px 12px;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
      font-size: 14px;
      line-height: 1.5;
    }

    table tbody tr {
      background-color: white;
      border-bottom: 1px solid #ddd;
    }

    table tbody tr:last-child {
      border-bottom: none;
    }

    .spec-label {
      width: 120px;
      color: #d50000;
      font-weight: 400;
      text-transform: uppercase;
    }

    td strong {
      display: inline-block;

      width: 90px;
      font-weight: 600;
    }

    /* Avatar and Comment Styles */
    .avatar-box {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .avatar-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-initials {
      font-size: 18px;
      font-weight: 600;
      color: white;
      text-transform: uppercase;
    }

    .comment-item {
      display: flex;
      margin-bottom: 20px;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }

    .comment-content {
      flex: 1;
    }

    .comment-author {
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }

    .comment-meta {
      color: #666;
      font-size: 13px;
      margin-bottom: 10px;
    }

    .comment-text {
      color: #444;
      line-height: 1.6;
    }

    .comment-form {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }

    .comment-form textarea {
      resize: vertical;
      min-height: 100px;
    }

    .no-comments {
      text-align: center;
      color: #666;
      padding: 40px 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    /* Brand Modal Styling */
    .brand-cell-modal {
      background-color: #fff;
      border: 1px solid #c5b6b0;
      color: #5D4037;
      font-weight: 500;
      transition: all 0.3s ease;
      cursor: pointer;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
    }

    .brand-cell-modal:hover {
      background-color: #D7CCC8 !important;
      border-color: #8D6E63;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
      color: #3E2723;
    }

    .brand-cell-modal:active {
      transform: translateY(0);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .brand-cell-modal:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
    }

    #brandsModal .modal-dialog-scrollable {
      max-height: 80vh;
    }

    /* Device Cell Modal Styling */
    .device-cell-modal {
      background-color: #fff;
      border: 1px solid #c5b6b0;
      color: #5D4037;
      font-weight: 500;
      transition: all 0.3s ease;
      cursor: pointer;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
    }

    .device-cell-modal:hover {
      background-color: #D7CCC8 !important;
      border-color: #8D6E63;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
      color: #3E2723;
    }

    .device-cell-modal:active {
      transform: translateY(0);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .device-cell-modal:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
    }

    #devicesModal .modal-dialog-scrollable {
      max-height: 80vh;
    }
  </style>


  <div class="d-lg-none d-block">
    <div class="card" role="region" aria-label="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> Phone Info" style="<?php
                                                                                                                                                                        if (!empty($device['device_page_color'])) {
                                                                                                                                                                          $color = htmlspecialchars($device['device_page_color']);
                                                                                                                                                                          echo "background: " . $color . " !important;";
                                                                                                                                                                        }
                                                                                                                                                                        ?>">

      <div class="article-info">
        <div class="bg-blur">
          <p class="vr-hide"
            style=" font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
            <?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?>
          </p>
          <svg class="float-end mx-3 mt-1" xmlns="http://www.w3.org/2000/svg" height="34" width="34"
            viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
            <path fill="#ffffff"
              d="M448 256C501 256 544 213 544 160C544 107 501 64 448 64C395 64 352 107 352 160C352 165.4 352.5 170.8 353.3 176L223.6 248.1C206.7 233.1 184.4 224 160 224C107 224 64 267 64 320C64 373 107 416 160 416C184.4 416 206.6 406.9 223.6 391.9L353.3 464C352.4 469.2 352 474.5 352 480C352 533 395 576 448 576C501 576 544 533 544 480C544 427 501 384 448 384C423.6 384 401.4 393.1 384.4 408.1L254.7 336C255.6 330.8 256 325.5 256 320C256 314.5 255.5 309.2 254.7 304L384.4 231.9C401.3 246.9 423.6 256 448 256z" />
          </svg>
        </div>
      </div>
      <div class="d-lg-flex  d-block" style="align-items: flex-start; ">

        <!-- Left: Phone Image -->
        <div class="phone-image me-3 pt-2 px-2">
          <img style="    height: -webkit-fill-available;
    width: 100%;
    padding: 12px;" src="<?php echo htmlspecialchars($device['image'] ?? $device['image_1'] ?? 'https://via.placeholder.com/300x400?text=No+Image'); ?>" alt="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> phone image" />
        </div>

        <!-- Right: Details + Stats + Specs -->
        <div class="flex-grow-1 position-relative" style="z-index: 100;">

          <!-- Phone Details + Stats -->
          <div class="d-flex justify-content-between mb-3">

            <ul class="phone-details d-lg-block d-none list-unstyled mb-0">
              <?php if (!empty($deviceHighlights)): ?>
                <?php foreach ($deviceHighlights as $highlight): ?>
                  <li><span><?php echo htmlspecialchars($highlight); ?></span></li>
                <?php endforeach; ?>
              <?php else: ?>
                <li><span>📅 Release date not available</span></li>
                <li><span>ℹ️ Specifications loading...</span></li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Specs Row (aligned with image) -->
          <div class="row text-center d-block g-0  pt-2 specs-bar">
            <?php
            $statKeys = ['display', 'camera', 'performance', 'battery'];
            $colIndex = 0;
            foreach ($statKeys as $key):
              if (isset($deviceStats[$key])):
                $stat = $deviceStats[$key];
                $borderClass = $colIndex > 0 ? 'border-start' : '';
            ?>
                <div class="col-3 spec-item <?php echo $borderClass; ?>">
                  <img src="<?php echo htmlspecialchars($stat['icon']); ?>" style="width: 25px;" alt="" onerror="this.style.display='none'">
                  <div class="spec-title"><?php echo htmlspecialchars($stat['title']); ?></div>
                  <div class="spec-subtitle"><?php echo htmlspecialchars($stat['subtitle']); ?></div>
                </div>
            <?php
                $colIndex++;
              endif;
            endforeach;
            ?>
          </div>

        </div>
      </div>
      <div class="article-info">
        <div class="bg-blur">
          <div class="d-lg-none d-block justify-content-end">
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2" onclick="window.location.href='compare.php?phone1=<?php echo $device['id']; ?>'">COMPARE </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor: pointer;" class="mt-2" onclick="showPicturesModal()">PICTURES </h5>
            </div>
          </div>


        </div>
      </div>

    </div>
  </div>
  <div class="container d-lg-block d-none">
    <div class="row">
      <div class="article-info">
        <div class="bg-blur">
          <div class="d-flex justify-content-end">
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor:pointer;" class="mt-2" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS</h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor: pointer;" class="mt-2" onclick="showPicturesModal()">PICTURES</h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2" onclick="window.location.href='compare.php?phone1=<?php echo $device['id']; ?>'">COMPARE </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2"> </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16 px;" class="mt-2"></h5>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container  d-lg-block d-none support content-wrapper" id="Top"
    style=" margin-top: 2rem; padding-left: 0;">
    <div class="row">
      <div class="col-md-8 ">
        <div class="card" role="region" aria-label="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> Phone Info" style="<?php
                                                                                                                                                                            if (!empty($device['device_page_color'])) {
                                                                                                                                                                              $color = htmlspecialchars($device['device_page_color']);
                                                                                                                                                                              echo "background: " . $color . " !important;";
                                                                                                                                                                            }
                                                                                                                                                                            ?>">

          <div class="article-info">
            <div class="bg-blur">
              <p class="vr-hide"
                style=" font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
                <?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?>
              </p>
              <svg class="float-end mx-3 mt-1" xmlns="http://www.w3.org/2000/svg" height="34" width="34"
                viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                <path fill="#ffffff"
                  d="M448 256C501 256 544 213 544 160C544 107 501 64 448 64C395 64 352 107 352 160C352 165.4 352.5 170.8 353.3 176L223.6 248.1C206.7 233.1 184.4 224 160 224C107 224 64 267 64 320C64 373 107 416 160 416C184.4 416 206.6 406.9 223.6 391.9L353.3 464C352.4 469.2 352 474.5 352 480C352 533 395 576 448 576C501 576 544 533 544 480C544 427 501 384 448 384C423.6 384 401.4 393.1 384.4 408.1L254.7 336C255.6 330.8 256 325.5 256 320C256 314.5 255.5 309.2 254.7 304L384.4 231.9C401.3 246.9 423.6 256 448 256z" />
              </svg>
            </div>
          </div>
          <div class="d-flex" style="align-items: flex-start;">

            <!-- Left: Phone Image -->
            <div class="phone-image me-3 pt-2 px-2">
              <img style="    height: -webkit-fill-available;
    width: 100%;
    padding: 12px;" src="<?php echo htmlspecialchars($device['image'] ?? $device['image_1'] ?? 'https://via.placeholder.com/300x400?text=No+Image'); ?>" alt="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> phone image" />
            </div>

            <!-- Right: Details + Stats + Specs -->
            <div class="flex-grow-1 position-relative" style="z-index: 100;">

              <!-- Phone Details + Stats -->
              <div class="d-flex justify-content-between mb-3">

                <ul class="phone-details list-unstyled mb-0 d-lg-block d-none">
                  <?php if (!empty($deviceHighlights)): ?>
                    <?php foreach ($deviceHighlights as $highlight): ?>
                      <li><span><?php echo htmlspecialchars($highlight); ?></span></li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li><span>📅 Release date not available</span></li>
                    <li><span>ℹ️ Specifications loading...</span></li>
                  <?php endif; ?>
                </ul>
              </div>

              <!-- Specs Row (aligned with image) -->
              <div class="row text-center g-0  pt-2 specs-bar">
                <?php
                $statKeys = ['display', 'camera', 'performance', 'battery'];
                $colIndex = 0;
                foreach ($statKeys as $key):
                  if (isset($deviceStats[$key])):
                    $stat = $deviceStats[$key];
                    $borderClass = $colIndex > 0 ? 'border-start' : '';
                ?>
                    <div class="col-3 spec-item <?php echo $borderClass; ?>">
                      <img src="<?php echo htmlspecialchars($stat['icon']); ?>" style="width: 25px;" alt="" onerror="this.style.display='none'">
                      <div class="spec-title"><?php echo htmlspecialchars($stat['title']); ?></div>
                      <div class="spec-subtitle"><?php echo htmlspecialchars($stat['subtitle']); ?></div>
                    </div>
                <?php
                    $colIndex++;
                  endif;
                endforeach;
                ?>
              </div>

            </div>
          </div>
          <div class="article-info">
            <div class="bg-blur">
              <div class="d-flex justify-content-end">
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor:pointer;" class="mt-2" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS</h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor: pointer;" class="mt-2" onclick="showPicturesModal()">PICTURES</h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2" onclick="window.location.href='compare.php?phone1=<?php echo $device['id']; ?>'">COMPARE </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px;" class="mt-2"> </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16 px;" class="mt-2"></h5>
                </div>
              </div>
            </div>
          </div>
        </div>


      </div>
      <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 25px;">
        <button class="solid w-100 py-2">
          <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
          Phone Finder</button>
        <div class="devor">
          <?php
          if (empty($brands)): ?>
            <button class="px-3 py-1" style="cursor: default;" disabled>No brands available.</button>
            <?php else:
            $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
            foreach ($brandChunks as $brandRow):
              foreach ($brandRow as $brand):
            ?>
                <button class="brand-cell brand-item-bold" style="cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
          <?php
              endforeach;
            endforeach;
          endif;
          ?>
        </div>
        <div class="d-flex">
          <button class="solid w-50 py-2" onclick="showBrandsModal()">
            <i class="fa-solid fa-bars fa-sm mx-2"></i>
            All Brands</button>
          <button class="solid w-50 py-2">
            <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
            RUMORS MILL</button>
          </div>
      </div>
    </div>

  </div>

  <div class="container my-2" style="
    padding-left: 0;
    padding-right: -2px;">
    <div class="row">
      <div class="col-lg-8 col-md-7 order-2 order-md-1">
        <div class="bg-white">


          <table class="table forat">
            <tbody>
              <?php if (!empty($deviceSpecs)): ?>
                <?php foreach ($deviceSpecs as $category => $details): ?>
                  <tr>
                    <th class="spec-label"><?php echo strtoupper($category); ?></th>
                    <td><?php echo $details; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Fallback if no database specs available -->
                <tr>
                  <th class="spec-label">DEVICE</th>
                  <td><strong>Name</strong> <?php echo htmlspecialchars($device['name'] ?? 'Unknown Device'); ?><br>
                    <?php if (isset($device['brand_name'])): ?>
                      <strong>Brand</strong> <?php echo htmlspecialchars($device['brand_name']); ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>

          <p style="font-size: 13px;
    text-transform: capitalize;
    padding: 6px 19px;"> <strong>Disclaimer:</strong>We can not guarantee that the information on this page is 100%
            correct.</p>

          <div class="d-block d-lg-flex"> <button
              class="pad" onclick="window.location.href='compare.php?phone1=<?php echo $device['id']; ?>'">COMPARE</button>
            <button class="pad" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS</button>
            <button class="pad" onclick="showPicturesModal()">PICTURES</button>
          </div>

          <div class="comments" id="comments">
            <h5 class="border-bottom reader py-3 mx-2"><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> - user opinions and reviews</h5>

            <?php if (isset($success_message)): ?>
              <div class="alert alert-success mx-2"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
              <div class="alert alert-danger mx-2"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="first-user" style="background-color: #EDEEEE;">

              <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                  <div class="user-thread">
                    <div class="uavatar">
                      <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                    </div>
                    <ul class="uinfo2">
                      <li class="uname">
                        <a href="#" style="color: #555; text-decoration: none;">
                          <?php echo htmlspecialchars($comment['name']); ?>
                        </a>
                      </li>
                      <li class="upost">
                        <i class="fa-regular fa-clock fa-sm mx-1"></i>
                        <?php echo timeAgo($comment['created_at']); ?>
                      </li>
                    </ul>
                    <p class="uopin"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    <ul class="uinfo">
                      <li class="ureply" style="list-style: none;">
                        <span title="Reply to this post">
                          <p href="#">Reply</p>
                        </span>
                      </li>
                    </ul>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="user-thread text-center py-4">
                  <p class="uopin text-muted">No comments yet. Be the first to share your opinion!</p>
                </div>
              <?php endif; ?>

              <!-- Comment Form -->
              <div class="comment-form mt-4 mx-2 mb-3">
                <h6 class="mb-3">Share Your Opinion</h6>
                <form method="POST" action="">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <input type="text" class="form-control" name="name" placeholder="Your Name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                      <input type="email" class="form-control" name="email" placeholder="Your Email (optional)" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                  </div>
                  <div class="mb-3">
                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your thoughts about this device..." required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" name="submit_comment" class="button-links">Post Your Opinion</button>
                    <small class="text-muted">Comments are moderated and will appear after approval.</small>
                  </div>
                </form>
              </div>

              <div class="button-secondary-div d-flex justify-content-between align-items-center">

                <p class="div-last">Total reader comments: <b><?php echo $commentCount; ?></b></p>
              </div>
            </div>
          </div>

          <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-xl-728x90.jpg" alt="">
        </div>
      </div>

      <!-- Left Section -->
      <div class="col-lg-4 bg-white col-md-5 order-1 order-md-2">
        <div class="mb-4">

          <h6 style="border-left: solid 5px grey ;text-transform: uppercase;" class=" fw-bold px-3 text-secondary mt-3">
            RELATED PHONES</h6>
          <div class="cent">

            <?php if (empty($devices)): ?>
              <div class="text-center py-5">
                <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Devices Available</h4>
                <p class="text-muted">Check back later for new devices!</p>
              </div>
            <?php else: ?>
              <?php $chunks = array_chunk($devices, 3); ?>
              <?php foreach ($chunks as $row): ?>
                <div class="d-flex">
                  <?php foreach ($row as $i => $device): ?>
                    <div class="device-card canel<?php echo $i == 1 ? ' mx-4' : ($i == 0 ? '' : ''); ?>" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                      <?php if (isset($device['images']) && !empty($device['images'])): ?>
                        <img class="shrink" src="<?php echo htmlspecialchars($device['images'][0]); ?>" alt="">
                      <?php elseif (isset($device['image']) && !empty($device['image'])): ?>
                        <img class="shrink" src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
                      <?php else: ?>
                        <img class="shrink" src="" alt="">
                      <?php endif; ?>
                      <p><?php echo htmlspecialchars($device['name'] ?? ''); ?></p>
                    </div>
                  <?php endforeach; ?>
                  <?php for ($j = count($row); $j < 3; $j++): ?>
                    <div class="canel<?php echo $j == 1 ? ' mx-4' : ($j == 0 ? '' : ''); ?>"></div>
                  <?php endfor; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="center w-100 " style="margin-top: 12px;">
            <h6 style="color: #090E21; text-transform: uppercase; font-weight: 900;" class=" mt-2 ">Latest Devices
            </h6>
            <div class="cent">
              <?php if (empty($devices)): ?>
                <div class="text-center py-5">
                  <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                  <h4 class="text-muted">No Devices Available</h4>
                  <p class="text-muted">Check back later for new devices!</p>
                </div>
              <?php else: ?>
                <?php $chunks = array_chunk($devices, 3); ?>
                <?php foreach ($chunks as $row): ?>
                  <div class="d-flex">
                    <?php foreach ($row as $i => $device): ?>
                      <div class="device-card canel<?php echo $i == 1 ? ' mx-4' : ($i == 0 ? '' : ''); ?>" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                       <?php if (isset($device['image']) && !empty($device['image'])): ?>
                          <img class="shrink" src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
                        <?php else: ?>
                          <img class="shrink" src="" alt="">
                        <?php endif; ?>
                        <p class="brand-item-bold"><?php echo htmlspecialchars($device['name'] ?? ''); ?></p>
                      </div>
                    <?php endforeach; ?>
                    <?php for ($j = count($row); $j < 3; $j++): ?>
                      <div class="canel<?php echo $j == 1 ? ' mx-4' : ($j == 0 ? '' : ''); ?>"></div>
                    <?php endfor; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <h6 style="border-left: solid 5px grey ; color: #090E21; text-transform: uppercase; font-weight: 900; margin-top: 12px;"
              class="px-3">Popular comparisons</h6>

            <div class="sentizer bg-white mt-2 p-3 rounded shadow-sm" style="    text-transform: Uppercase;
                                            font-size: 13px;
                                            font-weight: 700;">
              <div class="row">
                <div class="col-12">
                  <?php if (empty($topComparisons)): ?>
                    <p class="mb-2" style=" text-transform: capitalize;">No Comparisons Yet</p>
                  <?php else: ?>
                    <?php foreach ($topComparisons as $index => $comparison): ?>
                      <!-- if $index is odd -->
                      <?php if ((($index + 1) % 2) != 0): ?>
                        <p class="mb-2 clickable-comparison" data-device1-id="<?php echo $comparison['device1_id'] ?? ''; ?>"
                          data-device2-id="<?php echo $comparison['device2_id'] ?? ''; ?>"
                          style="cursor: pointer; background-color: #ffe6f0; color: #090E21; text-transform: capitalize;"><?php echo htmlspecialchars($comparison['device1_name'] ?? $comparison['device1'] ?? 'Unknown'); ?> vs.
                          <?php echo htmlspecialchars($comparison['device2_name'] ?? $comparison['device2'] ?? 'Unknown'); ?></p>
                      <?php else: ?>
                        <!-- else if $index is even -->
                        <p class="mb-2 clickable-comparison" data-device1-id="<?php echo $comparison['device1_id'] ?? ''; ?>"
                          data-device2-id="<?php echo $comparison['device2_id'] ?? ''; ?>" style="cursor: pointer; text-transform: capitalize;"><?php echo htmlspecialchars($comparison['device1_name'] ?? $comparison['device1'] ?? 'Unknown'); ?> vs. <?php echo htmlspecialchars($comparison['device2_name'] ?? $comparison['device2'] ?? 'Unknown'); ?></p>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <h6 style="border-left: 7px solid #EFEBE9 ; font-weight: 900; color: #090E21; text-transform: uppercase;"
              class=" px-2 mt-2 d-inline mt-4">Top 10
              Daily Interest</h6>

            <div class="center">
              <table class="table table-sm custom-table">
                <thead>
                  <tr style="background-color: #4c7273; color: white;">
                    <th style="color: white;">#</th>
                    <th style="color: white;">Devices</th>
                    <th style="color: white;">Daily Hits</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($topViewedDevices)): ?>
                    <tr>
                      <th scope="row"></th>
                      <td class="text-start">Not Enough Data Exists</td>
                      <td class="text-end"></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($topViewedDevices as $index => $device):
                      if (($index + 1) % 2 != 0): ?>
                        <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                          <th scope="row"><?php echo $index + 1; ?></th>
                          <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                          <td class="text-end"><?php echo $device['view_count']; ?></td>
                        </tr>
                      <?php else: ?>
                        <tr class="highlight clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                          <th scope="row" class="text-white"><?php echo $index + 1; ?></th>
                          <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                          <td class="text-end"><?php echo $device['view_count']; ?></td>
                        </tr>
                  <?php
                      endif;
                    endforeach;
                  endif; ?>
                </tbody>
              </table>
            </div>
            <h6 style="border-left: 7px solid #EFEBE9 ; font-weight: 900; color: #090E21; text-transform: uppercase;"
              class=" px-2 mt-2 d-inline mt-4">Top 10 by
              Fans</h6>
            <div class="center" style="margin-top: 12px;">
              <table class="table table-sm custom-table">
                <thead>
                  <tr class="text-white" style="background-color: #14222D;">
                    <th style="color: white;  font-size: 15px;  ">#</th>
                    <th style="color: white;  font-size: 15px;">Device</th>
                    <th style="color: white;  font-size: 15px;">Reviews</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($topReviewedDevices)): ?>
                    <tr>
                      <th scope="row"></th>
                      <td class="text-start">Not Enough Data Exists</td>
                      <td class="text-end"></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($topReviewedDevices as $index => $device):
                      if (($index + 1) % 2 != 0): ?>
                        <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                          <th scope="row"><?php echo $index + 1; ?></th>
                          <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                          <td class="text-end"><?php echo $device['review_count']; ?></td>
                        </tr>
                      <?php else: ?>
                        <tr class="highlight-12 clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                          <th scope="row" class="text-white"><?php echo $index + 1; ?></th>
                          <td class="text-start"><?php echo htmlspecialchars($device['brand_name']); ?> <?php echo htmlspecialchars($device['name']); ?></td>
                          <td class="text-end"><?php echo $device['review_count']; ?></td>
                        </tr>
                  <?php
                      endif;
                    endforeach;
                  endif; ?>
                </tbody>
              </table>
            </div>
            <h6 style="border-left: 7px solid #EFEBE9 ; font-weight: 900; color: #090E21; text-transform: uppercase;"
              class=" px-2 mt-2 d-inline mt-4">In
              Stores
              Now</h6>

            <div class="cent">
              <?php if (empty($latestDevices)): ?>
                <div class="text-center py-5">
                  <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                  <h4 class="text-muted">No Devices Available</h4>
                  <p class="text-muted">Check back later for new devices!</p>
                </div>
              <?php else: ?>
                <?php $chunks = array_chunk($latestDevices, 3); ?>
                <?php foreach ($chunks as $row): ?>
                  <div class="d-flex">
                    <?php foreach ($row as $i => $device): ?>
                      <div class="device-card canel<?php echo $i == 1 ? ' mx-4' : ($i == 0 ? '' : ''); ?>" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                        <img class="shrink" src="<?php echo htmlspecialchars($device['image'] ?? ''); ?>" alt="">
                        <p class="brand-item-bold"><?php echo htmlspecialchars($device['name'] ?? ''); ?></p>
                      </div>
                    <?php endforeach; ?>
                    <?php for ($j = count($row); $j < 3; $j++): ?>
                      <div class="canel<?php echo $j == 1 ? ' mx-4' : ($j == 0 ? '' : ''); ?>"></div>
                    <?php endfor; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
    <!-- Newsletter Section -->
    <div class="container mt-4 mb-4" style="max-width: 1034px;">
      <div class="row">
        <div class="col-12">
          <div id="newsletter_message_container"></div>
          <form id="newsletter_form" method="POST" action="" style="background-color: #EFEBE9; padding: 20px; border-radius: 4px; text-align: center;">
            <p style="margin-bottom: 12px; color: #5D4037; font-weight: 500;">Subscribe to our newsletter</p>
            <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
              <input type="email" id="newsletter_email" name="newsletter_email" placeholder="Enter your email" required style="padding: 10px 12px; border: 1px solid #8D6E63; border-radius: 4px; font-size: 14px; flex: 1; min-width: 200px; max-width: 300px; background-color: white;">
              <style>
                input::placeholder {
                  color: #8D6E63;
                  opacity: 0.7;
                }
              </style>
              <button type="submit" id="newsletter_btn" style="padding: 10px 24px; background-color: #D50000; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; white-space: nowrap; font-weight: 500;">Subscribe</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="bottom" class="container d-flex mt-3" style="max-width: 1034px;">
      <div class="row align-items-center">
        <div class="col-md-2 m-auto col-4 d-flex justify-content-center align-items-center "> <img
            src="https://fdn2.gsmarena.com/w/css/logo-gsmarena-com.png" alt="">
        </div>
        <div class="col-10 nav-wrap m-auto text-center ">
          <div class="nav-container">
            <a href="#">Home</a>
            <a href="reviews.php">Reviews</a>
            <a href="compare.php">Compare</a>
            <a href="#"> <i class="fa-solid fa-wifi fa-sm"></i> RSS</a>
            <a href="#"> <i class="fa-brands fa-youtube fa-sm"></i> YouTube</a>
            <a href="#"> <i class="fa-brands fa-instagram fa-sm"></i> Instagram</a>
            <a href="#"> <i class="fa-brands fa-tiktok fa-sm"></i>TikTok</a>
            <a href="#"> <i class="fa-brands fa-facebook-f fa-sm"></i> Facebook</a>
            <a href="#"> <i class="fa-brands fa-twitter fa-sm"></i>Twitter</a>
            <a href="#">© 2000-2025 GSMArena.com</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Brands Modal -->
    <div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
          <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
            <h5 class="modal-title" id="brandsModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
              <i class="fas fa-industry me-2"></i>All Brands
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <?php if (!empty($brands)): ?>
                <?php foreach ($brands as $brand): ?>
                  <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                    <button class="brand-cell-modal btn w-100 py-2 px-3" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>" onclick="selectBrandFromModal(<?php echo $brand['id']; ?>)">
                      <?php echo htmlspecialchars($brand['name']); ?>
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12">
                  <div class="text-center py-5">
                    <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No brands available</h6>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Devices Modal (Phones by Brand) -->
    <div class="modal fade" id="devicesModal" tabindex="-1" aria-labelledby="deviceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
          <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
            <h5 class="modal-title" id="deviceModalTitle" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
              Devices
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="deviceModalBody">
            <div class="text-center py-5">
              <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pictures Modal -->
    <div class="modal fade" id="picturesModal" tabindex="-1" aria-labelledby="picturesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
          <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
            <h5 class="modal-title" id="picturesModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
              <i class="fas fa-images me-2"></i><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> - Pictures
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <?php if (!empty($deviceImages)): ?>
              <!-- Image Carousel -->
              <div id="picturesCarousel" class="carousel slide" data-bs-ride="false">
                <!-- Carousel Indicators -->
                <div class="carousel-indicators">
                  <?php foreach ($deviceImages as $index => $image): ?>
                    <button type="button" data-bs-target="#picturesCarousel" data-bs-slide-to="<?php echo $index; ?>"
                      <?php if ($index === 0): ?>class="active" aria-current="true" <?php endif; ?>
                      aria-label="Slide <?php echo $index + 1; ?>"></button>
                  <?php endforeach; ?>
                </div>

                <!-- Carousel Images -->
                <div class="carousel-inner">
                  <?php foreach ($deviceImages as $index => $image): ?>
                    <div class="carousel-item <?php if ($index === 0): ?>active<?php endif; ?>">
                      <div class="d-flex justify-content-center" style="background-color: #F5F5F5; min-height: 400px;">
                        <!-- Debug output -->
                        <div style="display: none;">Image path: <?php echo htmlspecialchars($image); ?></div>

                        <img src="<?php echo htmlspecialchars($image); ?>"
                          class="d-block img-fluid"
                          style="max-height: 500px; max-width: 100%; object-fit: contain; padding: 20px;"
                          alt="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> - Image <?php echo $index + 1; ?>"
                          onerror="this.style.display='none';">
                      </div>
                      <div class="carousel-caption d-md-block" style="background-color: rgba(0,0,0,0.5); border-radius: 10px; bottom: 20px;">
                        <p class="mb-0" style="font-size: 14px;">Image <?php echo $index + 1; ?> of <?php echo count($deviceImages); ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Carousel Controls -->
                <?php if (count($deviceImages) > 1): ?>
                  <button class="carousel-control-prev" type="button" data-bs-target="#picturesCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                  </button>
                  <button class="carousel-control-next" type="button" data-bs-target="#picturesCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                  </button>
                <?php endif; ?>
              </div>

              <!-- Thumbnail Navigation -->
              <?php if (count($deviceImages) > 1): ?>
                <div class="modal-footer" style="border-top: 1px solid #8D6E63; background-color: #D7CCC8; padding: 10px;">
                  <div class="d-flex justify-content-center flex-wrap gap-2" style="max-height: 100px; overflow-y: auto;">
                    <?php foreach ($deviceImages as $index => $image): ?>
                      <img src="<?php echo htmlspecialchars($image); ?>"
                        class="thumbnail-nav border rounded"
                        style="width: 60px; height: 60px; object-fit: cover; cursor: pointer; opacity: 0.7; transition: opacity 0.3s;"
                        onclick="showSlide(<?php echo $index; ?>)"
                        data-slide="<?php echo $index; ?>"
                        alt="Thumbnail <?php echo $index + 1; ?>"
                        onerror="this.style.display='none';">
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <!-- No Images Available -->
              <div class="text-center py-5">
                <i class="fas fa-image-slash fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No pictures available for this device</h6>
                <p class="text-muted small">Pictures will be added soon</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>


<!-- Bootstrap JS Bundle (Popper + Bootstrap JS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Enable tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })
</script>

<script src="script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Handle clickable table rows for devices
  document.addEventListener('DOMContentLoaded', function() {
    // Handle device row clicks (for views and reviews tables)
    document.querySelectorAll('.clickable-row').forEach(function(row) {
      row.addEventListener('click', function() {
        const deviceId = this.getAttribute('data-device-id');
        if (deviceId) {
          // Track the view
          fetch('track_device_view.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'device_id=' + encodeURIComponent(deviceId)
          });

          // Show device details modal
          showDeviceDetails(deviceId);
        }
      });
    });

    // Handle device card clicks (for latest devices grid)
    document.querySelectorAll('.device-card').forEach(function(card) {
      card.addEventListener('click', function() {
        const deviceId = this.getAttribute('data-device-id');
        if (deviceId) {
          // Track the view
          fetch('track_device_view.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'device_id=' + encodeURIComponent(deviceId)
          });

          // Show device details modal
          showDeviceDetails(deviceId);
        }
      });
    });

    // Handle brand cell clicks (from sidebar and mobile menu - open devices modal directly)
    document.querySelectorAll('.brand-cell').forEach(function(cell) {
      cell.addEventListener('click', function(e) {
        e.preventDefault();
        const brandId = this.getAttribute('data-brand-id');
        if (brandId) {
          // Directly open devices modal for this brand
          selectBrandFromModal(brandId);
        }
      });
    });

    // Handle comparison row clicks
    document.querySelectorAll('.clickable-comparison').forEach(function(row) {
      row.addEventListener('click', function() {
        const device1Id = this.getAttribute('data-device1-id');
        const device2Id = this.getAttribute('data-device2-id');
        if (device1Id && device2Id) {
          // Track the comparison
          fetch('track_device_comparison.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'device1_id=' + encodeURIComponent(device1Id) + '&device2_id=' + encodeURIComponent(device2Id)
          });

          // Redirect to comparison page
          window.location.href = `compare.php?phone1=${device1Id}&phone2=${device2Id}`;
        }
      });
    });
  });

  // Show brands modal
  function showBrandsModal() {
    const modal = new bootstrap.Modal(document.getElementById('brandsModal'));
    modal.show();
  }

  // Handle brand selection from modal
  function selectBrandFromModal(brandId) {
    // Close the brands modal
    const brandsModal = bootstrap.Modal.getInstance(document.getElementById('brandsModal'));
    if (brandsModal) {
      brandsModal.hide();
    }

    // Fetch phones for this brand
    fetch(`get_phones_by_brand.php?brand_id=${brandId}`)
      .then(response => response.json())
      .then(data => {
        // Populate the devices modal with phones
        displayPhonesModal(data, brandId);
      })
      .catch(error => {
        console.error('Error fetching phones:', error);
        alert('Failed to load phones');
      });
  }

  // Display phones in modal
  function displayPhonesModal(phones, brandId) {
    const container = document.getElementById('deviceModalBody');
    const titleElement = document.getElementById('deviceModalTitle');

    // Update title with brand name
    const brandButton = document.querySelector(`[data-brand-id="${brandId}"]`);
    const brandName = brandButton ? brandButton.textContent.trim() : 'Brand';
    titleElement.innerHTML = `<i class="fas fa-mobile-alt me-2"></i>${brandName} - Devices`;

    if (phones && phones.length > 0) {
      let html = '<div class="row">';
      phones.forEach(phone => {
        const phoneImage = phone.image ? `<img src="${phone.image}" alt="${phone.name}" style="width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px;" onerror="this.style.display='none';">` : '';
        html += `
          <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <button class="device-cell-modal btn w-100 p-0" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; overflow: hidden;" onclick="goToDevice(${phone.id})">
              ${phoneImage}
              <span style="padding: 8px 10px; width: 100%; text-align: center; font-size: 0.95rem;">${phone.name}</span>
            </button>
          </div>
        `;
      });
      html += '</div>';
      container.innerHTML = html;
    } else {
      container.innerHTML = `
        <div class="text-center py-5">
          <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">No devices available for this brand</h6>
        </div>
      `;
    }

    // Show devices modal
    const devicesModal = new bootstrap.Modal(document.getElementById('devicesModal'));
    devicesModal.show();
  }

  // Navigate to device page
  function goToDevice(deviceId) {
    window.location.href = `device.php?id=${deviceId}`;
  }

  // Show post details in modal
  function showPostDetails(postId) {
    fetch(`get_post_details.php?id=${postId}`)
      .then(response => response.text())
      .then(data => {
        window.location.href = `post.php?id=${postId}`;
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load post details');
      });
  }

  // Show device details in modal
  function showDeviceDetails(deviceId) {
    fetch(`get_device_details.php?id=${deviceId}`)
      .then(response => response.text())
      .then(data => {
        window.location.href = `device.php?id=${deviceId}`;
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load device details');
      });
  }



  // Auto-dismiss alerts after 5 seconds
  setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      var bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    });
  }, 5000);

  function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
  }

  function showAllImages() {
    new bootstrap.Modal(document.getElementById('allImagesModal')).show();
  }

  // Pictures Modal Functions
  function showPicturesModal() {
    const modal = new bootstrap.Modal(document.getElementById('picturesModal'));
    modal.show();

    // Initialize carousel after modal is shown
    const carouselEl = document.getElementById('picturesCarousel');
    setTimeout(() => {
      let carousel = bootstrap.Carousel.getInstance(carouselEl);
      if (!carousel) {
        carousel = new bootstrap.Carousel(carouselEl, {
          interval: false, // Don't auto-advance
          wrap: true
        });
      }
      carousel.to(0);
      updateThumbnailHighlight(0);
    }, 100);
  }

  function showSlide(slideIndex) {
    const carouselEl = document.getElementById('picturesCarousel');
    let carousel = bootstrap.Carousel.getInstance(carouselEl);
    if (!carousel) {
      carousel = new bootstrap.Carousel(carouselEl, {
        interval: false,
        wrap: true
      });
    }
    carousel.to(slideIndex);
    updateThumbnailHighlight(slideIndex);
  }

  function updateThumbnailHighlight(activeIndex) {
    // Remove active class from all thumbnails
    document.querySelectorAll('.thumbnail-nav').forEach((thumb, index) => {
      if (index === activeIndex) {
        thumb.style.opacity = '1';
        thumb.style.border = '2px solid #5D4037';
      } else {
        thumb.style.opacity = '0.7';
        thumb.style.border = '1px solid #ddd';
      }
    });
  }

  // Listen for carousel slide events to update thumbnails
  document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('picturesCarousel');
    if (carousel) {
      carousel.addEventListener('slid.bs.carousel', function(event) {
        updateThumbnailHighlight(event.to);
      });
    }
  });

  // Smooth scroll to comments section
  document.addEventListener('DOMContentLoaded', function() {
    const reviewButton = document.querySelector('a[href="#comments"]');
    if (reviewButton) {
      reviewButton.addEventListener('click', function(e) {
        e.preventDefault();
        const commentsSection = document.getElementById('comments');
        if (commentsSection) {
          commentsSection.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    }
  });

  // Auto-dismiss alerts
  setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      if (alert.querySelector('.btn-close')) {
        alert.querySelector('.btn-close').click();
      }
    });
  }, 5000);

  // Newsletter form AJAX handler
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('newsletter_form');
    const messageContainer = document.getElementById('newsletter_message_container');
    const emailInput = document.getElementById('newsletter_email');
    const submitBtn = document.getElementById('newsletter_btn');

    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = emailInput.value.trim();
        const originalBtnText = submitBtn.textContent;

        if (!email) {
          showMessage('Please enter an email address.', 'error');
          return;
        }

        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Subscribing...';

        // Send AJAX request
        fetch('handle_newsletter.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'newsletter_email=' + encodeURIComponent(email)
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showMessage(data.message, 'success');
              emailInput.value = '';
              // Auto-clear message after 5 seconds
              setTimeout(() => {
                messageContainer.innerHTML = '';
              }, 5000);
            } else {
              showMessage(data.message, 'error');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
          })
          .catch(error => {
            showMessage('An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
          });
      });

      function showMessage(message, type) {
        const bgColor = type === 'success' ? '#4CAF50' : '#f44336';
        messageContainer.innerHTML = '<div style="background-color: ' + bgColor + '; color: white; padding: 12px; border-radius: 4px; margin-bottom: 12px; text-align: center; animation: slideIn 0.3s ease-in-out;">' + message + '</div>';

        // Add animation style
        if (!document.querySelector('style[data-newsletter]')) {
          const style = document.createElement('style');
          style.setAttribute('data-newsletter', 'true');
          style.textContent = '@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }';
          document.head.appendChild(style);
        }
      }
    }
  });
</script>

</body>

</html>