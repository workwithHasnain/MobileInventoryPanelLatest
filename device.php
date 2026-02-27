<?php
session_start();
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

// New clean URL format: domain/device/slug (instead of domain/device.php?slug=xyz)
// The .htaccess file rewrites clean URLs to this page and passes slug as query parameter
// Base path variable is now defined in config.php

// Helper function to make image paths absolute
function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath)) return '';
  // Already an absolute URL
  if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
  // Already an absolute path starting with /
  if (strpos($imagePath, '/') === 0) return $imagePath;
  // Relative path - prepend base
  return $base . ltrim($imagePath, '/');
}

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

// Get top 36 brands ordered by device count (highest first) then alphabetically - for sidebar
$brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY COUNT(p.id) DESC, b.name ASC
    LIMIT 36
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get all brands alphabetically ordered - for modal
$all_brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$all_brands_stmt->execute();
$allBrandsModal = $all_brands_stmt->fetchAll();


// Get device slug from URL
// New way: slug comes from clean URL (domain/device/slug) rewritten by .htaccess
$device_slug = $_GET['slug'] ?? '';

if (!isset($_GET['slug']) || $_GET['slug'] === '') {
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
      // Ensure absolute path
      if (strpos($image, '/') !== 0 && !filter_var($image, FILTER_VALIDATE_URL)) {
        $image = '/' . ltrim($image, '/');
      }
      // Add to valid images if not already included
      if (!in_array($image, $validImages)) {
        $validImages[] = $image;
      }
    }
  }

  return $validImages;
}

// Function to get device details
function getDeviceDetails($pdo, $device_slug)
{
  // Try database first (comprehensive data source)
  try {
    $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.slug = ?
        ");
    $stmt->execute([$device_slug]);
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

    // JSON stores as array, so search by slug
    if (is_array($phones_data)) {
      // Search by slug in JSON data
      foreach ($phones_data as $index => $phone_data) {
        if (isset($phone_data['slug']) && $phone_data['slug'] === $device_slug) {
          $device = $phone_data;
          // Add computed fields for compatibility
          if (!isset($device['id'])) {
            $device['id'] = $index;
          }
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
  }

  return null;
}

// Function to format device specifications for display
function formatDeviceSpecs($device)
{
  $specs = [];

  // Helper: truncate text to max characters with expandable ellipsis
  $truncateText = function ($text, $maxLength = 60) {
    if (strlen($text) > $maxLength) {
      $truncated = substr($text, 0, $maxLength);
      // Store full text in data attribute and show clickable ellipsis
      return htmlspecialchars($truncated) . '<span class="expand-dots" data-full="' . str_replace('"', '&quot;', htmlspecialchars($text)) . '" style="cursor: pointer; color: #d50000; font-weight: bold;">  ...</span>';
    }
    return htmlspecialchars($text);
  };

  // Helper: parse a section from JSON and return structured data (not concatenated HTML)
  $parseJsonSection = function ($jsonValue, $sectionName = '') {
    if (!isset($jsonValue) || $jsonValue === '' || $jsonValue === null) return [];
    $decoded = json_decode($jsonValue, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
      return [];
    }

    $rows = [];
    $currentField = null;

    foreach ($decoded as $row) {
      $field = isset($row['field']) ? trim((string)$row['field']) : '';
      $desc = isset($row['description']) ? trim((string)$row['description']) : '';

      if ($field === '' && $desc === '') continue;

      // Each field becomes a separate row: field is subtitle, desc is description
      if ($field !== '') {
        $rows[] = [
          'field' => $field,
          'description' => $desc,
          'sectionName' => $sectionName
        ];
      } else {
        // Empty field = continuation of previous field's description
        if (!empty($rows)) {
          $lastRow = &$rows[count($rows) - 1];
          if ($desc !== '') {
            $lastRow['description'] .= "\n" . $desc;
          }
        }
      }
    }

    return $rows;
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
    $rows = $parseJsonSection($raw, $label);
    if (!empty($rows)) {
      $specs[$label] = $rows;
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
      $network_details = '<strong>Technology</strong> ' . $truncateText(implode(' / ', $network_tech), 60);
      if (!empty($device['dual_sim'])) $network_details .= '<br><strong>Connectivity Slot</strong> ' . $truncateText('Dual SIM', 60);
      if (!empty($device['esim'])) $network_details .= ', eSIM';
      if (!empty($device['sim_size'])) $network_details .= ' (' . $device['sim_size'] . ')';
      $specs['NETWORK'] = $network_details;
    }
  }

  // Legacy fallback: Launch (also add price if JSON not used)
  if (!isset($specs['LAUNCH']) && (!empty($device['release_date']) || !empty($device['availability']) || !empty($device['price']))) {
    $launch_details = '';
    if (!empty($device['release_date'])) {
      $launch_details .= '<strong>Released</strong> ' . $truncateText(date('F j, Y', strtotime($device['release_date'])), 60);
    }
    if (!empty($device['availability'])) {
      if ($launch_details) $launch_details .= '<br>';
      $launch_details .= '<strong>Availability</strong> ' . $truncateText($device['availability'], 60);
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
      $body_details .= '<strong>Dimensions</strong> ' . $truncateText($device['dimensions'], 60);
    } elseif (!empty($device['height']) && !empty($device['width']) && !empty($device['thickness'])) {
      $dims = $device['height'] . ' x ' . $device['width'] . ' x ' . $device['thickness'] . ' mm';
      $body_details .= '<strong>Dimensions</strong> ' . $truncateText($dims, 60);
    }
    if (!empty($device['weight'])) {
      if ($body_details) $body_details .= '<br>';
      $body_details .= '<strong>Weight</strong> ' . $truncateText($device['weight'] . ' g', 60);
    }
    $specs['BODY'] = $body_details;
  }

  // Legacy fallback: Display
  if (!isset($specs['DISPLAY']) && (!empty($device['display_type']) || !empty($device['display_size']) || !empty($device['display_resolution']))) {
    $display_details = '';
    if (!empty($device['display_type'])) {
      $displayType = $device['display_type'];
      if (!empty($device['display_technology'])) $displayType .= ', ' . $device['display_technology'];
      if (!empty($device['refresh_rate'])) $displayType .= ', ' . $device['refresh_rate'] . 'Hz';
      if (!empty($device['hdr'])) $displayType .= ', HDR';
      if (!empty($device['billion_colors'])) $displayType .= ', 1B colors';
      $display_details .= '<strong>Type</strong> ' . $truncateText($displayType, 60);
    }
    if (!empty($device['display_size'])) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Size</strong> ' . $truncateText($device['display_size'] . ' inches', 60);
    }
    if (!empty($device['display_resolution'])) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Resolution</strong> ' . $truncateText($device['display_resolution'], 60);
    }
    $specs['DISPLAY'] = $display_details;
  }

  // Legacy fallback: Hardware
  if (!isset($specs['HARDWARE']) && (!empty($device['os']) || !empty($device['chipset_name']) || !empty($device['cpu_cores']) || !empty($device['gpu']))) {
    $platform_details = '';
    if (!empty($device['os'])) {
      $platform_details .= '<strong>OS</strong> ' . $truncateText($device['os'], 60);
    }
    if (!empty($device['chipset_name'])) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>System Chip</strong> ' . $truncateText($device['chipset_name'], 60);
    }
    if (!empty($device['cpu_cores']) || !empty($device['cpu_frequency'])) {
      if ($platform_details) $platform_details .= '<br>';
      $cpu_info = '';
      if (!empty($device['cpu_cores'])) $cpu_info .= $device['cpu_cores'] . '-core';
      if (!empty($device['cpu_frequency'])) $cpu_info .= ' (' . $device['cpu_frequency'] . ' GHz)';
      $platform_details .= '<strong>Processor</strong> ' . $truncateText($cpu_info, 60);
    }
    if (!empty($device['gpu'])) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>GPU</strong> ' . $truncateText($device['gpu'], 60);
    }
    $specs['HARDWARE'] = $platform_details;
  }

  // Legacy fallback: System Memory
  if (!isset($specs['MEMORY']) && (!empty($device['ram']) || !empty($device['storage']) || !empty($device['card_slot']))) {
    $memory_details = '';
    if (!empty($device['card_slot'])) {
      $memory_details .= '<strong>Expansion Slot</strong> ' . $truncateText(htmlspecialchars($device['card_slot']), 60);
    }
    if (!empty($device['storage']) || !empty($device['ram'])) {
      if ($memory_details) $memory_details .= '<br>';
      $storage_info = '';
      if (!empty($device['storage'])) $storage_info .= $device['storage'];
      if (!empty($device['ram'])) $storage_info .= ' RAM: ' . $device['ram'];
      $memory_details .= '<strong>Storage</strong> ' . $truncateText($storage_info, 60);
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
      $camera_details .= $truncateText($device['main_camera_resolution'], 60);
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
      $camera_details .= '<br><strong>Features</strong> ' . $truncateText(implode(', ', $features), 60);
    }
    if (!empty($device['main_camera_video'])) {
      $camera_details .= '<br><strong>Video Recording</strong> ' . $truncateText($device['main_camera_video'], 60);
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
      $selfie_details .= $truncateText($device['selfie_camera_resolution'], 60);
    }
    if (isset($device['selfie_camera_features']) && is_array($device['selfie_camera_features'])) {
      $selfie_details .= '<br><strong>Features</strong> ' . $truncateText(implode(', ', $device['selfie_camera_features']), 60);
    } elseif (isset($device['selfie_camera_features']) && is_string($device['selfie_camera_features'])) {
      // Handle PostgreSQL array string format
      $array_features = str_replace(['{', '}'], '', $device['selfie_camera_features']);
      $array_features = explode(',', $array_features);
      $selfie_details .= '<br><strong>Features</strong> ' . $truncateText(implode(', ', array_map('trim', $array_features)), 60);
    }
    if (!empty($device['selfie_camera_video'])) {
      $selfie_details .= '<br><strong>Video Recording</strong> ' . $truncateText($device['selfie_camera_video'], 60);
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
      $comms_details .= '<strong>WLAN</strong> ' . $truncateText($device['wifi'], 60);
    }
    if (!empty($device['bluetooth'])) {
      if ($comms_details) $comms_details .= '<br>';
      $comms_details .= '<strong>Bluetooth</strong> ' . $truncateText($device['bluetooth'], 60);
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
      $comms_details .= '<strong>USB</strong> ' . $truncateText($device['usb'], 60);
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
      $features_details .= '<strong>Sensors</strong> ' . $truncateText(implode(', ', $sensors), 60);
    }

    if ($features_details) {
      $specs['FEATURES'] = $features_details;
    }
  }

  // Legacy fallback: Battery
  if (!isset($specs['BATTERY']) && (!empty($device['battery_capacity']) || !empty($device['battery_sic']))) {
    $battery_details = '';
    if (!empty($device['battery_capacity'])) {
      $battery_capacity = $device['battery_capacity'];
      if (!empty($device['battery_sic'])) $battery_capacity .= ' (Silicon)';
      $battery_details .= '<strong>Capacity</strong> ' . $truncateText($battery_capacity, 60);
    }

    if (isset($device['battery_removable']) && $device['battery_removable'] !== null) {
      if ($battery_details) $battery_details .= '<br>';
      $battery_details .= '<strong>Removable</strong> ' . ($device['battery_removable'] ? 'Yes' : 'No');
    }

    // Charging information
    $charging = [];
    if (!empty($device['wired_charging'])) $charging[] = 'Wired: ' . $device['wired_charging'];
    if (!empty($device['wireless_charging'])) $charging[] = 'Wireless: ' . $device['wireless_charging'];

    if (!empty($charging)) {
      if ($battery_details) $battery_details .= '<br>';
      $battery_details .= '<strong>Charging</strong> ' . $truncateText(implode(', ', $charging), 60);
    }

    $specs['BATTERY'] = $battery_details;
  }

  // Colors (legacy field) - keep if present
  if (isset($device['colors']) && is_array($device['colors'])) {
    $specs['COLORS'] = '<strong>Available</strong> ' . $truncateText(implode(', ', $device['colors']), 60);
  } elseif (isset($device['colors']) && is_string($device['colors'])) {
    // Handle PostgreSQL array string format
    $array_colors = str_replace(['{', '}'], '', $device['colors']);
    $array_colors = explode(',', $array_colors);
    $specs['COLORS'] = '<strong>Available</strong> ' . $truncateText(implode(', ', array_map('trim', $array_colors)), 60);
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
    'icon' => 'https://itsahmedali21.github.io/GSMArena/imges/vrer.png',
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
    'icon' => 'https://itsahmedali21.github.io/GSMArena/imges/bett-removebg-preview.png',
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
    'icon' => 'https://itsahmedali21.github.io/GSMArena/imges/encypt-removebg-preview.png',
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
    'icon' => 'https://itsahmedali21.github.io/GSMArena/imges/lowtry-removebg-preview.png',
    'title' => $battery_title,
    'subtitle' => $battery_subtitle
  ];

  return $stats;
}

// Function to get device comments (threaded: parents first, then replies grouped)
function getDeviceComments($pdo, $device_id)
{
  try {
    $stmt = $pdo->prepare("
            SELECT * FROM device_comments 
            WHERE device_id = ? AND status = 'approved'
            ORDER BY created_at ASC
        ");
    $stmt->execute([$device_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build threaded structure: parent comments with nested replies (1 level deep)
    $parents = [];
    $replies = [];
    foreach ($all as $c) {
      if (empty($c['parent_id'])) {
        $c['replies'] = [];
        $parents[$c['id']] = $c;
      } else {
        $replies[] = $c;
      }
    }
    // Attach replies to their parent (or to the root parent if reply-to-reply)
    foreach ($replies as $r) {
      $pid = $r['parent_id'];
      if (isset($parents[$pid])) {
        $parents[$pid]['replies'][] = $r;
      } else {
        // reply-to-reply: find root parent
        foreach ($parents as &$p) {
          foreach ($p['replies'] as $existingReply) {
            if ($existingReply['id'] == $pid) {
              $p['replies'][] = $r;
              break 2;
            }
          }
        }
        unset($p);
      }
    }
    // Return as indexed array, newest parents first
    return array_reverse(array_values($parents));
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
$device = getDeviceDetails($pdo, $device_slug);

// Extract numeric device ID for internal use (comments, tracking, etc.)
$device_id = $device['id'] ?? null;

if (!$device) {
  header("Location: 404.php");
  exit();
}

// Ensure device image path is absolute
if (!empty($device['image'])) {
  if (strpos($device['image'], '/') !== 0 && !filter_var($device['image'], FILTER_VALIDATE_URL)) {
    $device['image'] = '/' . ltrim($device['image'], '/');
  }
}

// Get review for this device (if exists)
$review_post = null;
try {
  $review_stmt = $pdo->prepare("
    SELECT po.id, po.title, po.slug
    FROM reviews r
    INNER JOIN posts po ON r.post_id = po.id
    WHERE r.phone_id = ?
    LIMIT 1
  ");
  $review_stmt->execute([$device_id]);
  $review_post = $review_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Review fetch error: " . $e->getMessage());
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

// Note: Comment submission now handled via AJAX (see ajax_comment_handler.php)
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/device/<?php echo htmlspecialchars($device_slug); ?>" />
  <?php
  // Generate SEO meta tags dynamically
  $brand_name = $device['brand_name'] ?? $device['brand'] ?? '';
  $device_name = $device['name'] ?? 'Device';

  // Use custom meta title if available, otherwise auto-generate
  if (!empty($device['meta_title'])) {
    $page_title = htmlspecialchars($device['meta_title']);
  } else {
    $page_title = htmlspecialchars($brand_name . ' ' . $device_name) . ' - Specifications & Reviews | DevicesArena';
  }

  // Use custom meta description if available, otherwise auto-generate from specs
  if (!empty($device['meta_desc'])) {
    $meta_description = htmlspecialchars($device['meta_desc']);
  } else {
    // Auto-generate description from device specs
    $desc_parts = [];
    if (!empty($device['display_size'])) $desc_parts[] = $device['display_size'] . '" display';
    if (!empty($device['main_camera_resolution'])) $desc_parts[] = $device['main_camera_resolution'] . ' camera';
    if (!empty($device['battery_capacity'])) $desc_parts[] = $device['battery_capacity'] . ' battery';
    if (!empty($device['ram'])) $desc_parts[] = $device['ram'] . ' RAM';
    if (!empty($device['storage'])) $desc_parts[] = $device['storage'] . ' storage';

    $specs_text = !empty($desc_parts) ? implode(', ', $desc_parts) : 'full specifications';
    $meta_description = htmlspecialchars("Explore detailed specifications, reviews, and features of the {$brand_name} {$device_name}. {$specs_text} and more on DevicesArena.");
  }
  ?>
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $meta_description; ?>">

  <!-- Favicon & Icons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="icon" type="image/png" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">

  <!-- Apple Touch Icon (iOS Home Screen) -->
  <link rel="apple-touch-icon" href="<?php echo $base; ?>imges/icon-256.png">
  <link rel="apple-touch-icon" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">

  <!-- Android Chrome Icons -->
  <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>imges/icon-256.png">
  <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>imges/icon-256.png">

  <!-- Theme Color (Browser Chrome & Address Bar) -->
  <meta name="theme-color" content="#1B2035">

  <!-- Windows Tile Icon -->
  <meta name="msapplication-TileColor" content="#1B2035">
  <meta name="msapplication-TileImage" content="<?php echo $base; ?>imges/icon-256.png">

  <!-- Open Graph Meta Tags (Social Media Sharing) -->
  <?php
  // Use device image if available, otherwise use site logo
  $og_image = !empty($device['image']) ? htmlspecialchars($device['image']) : $base . 'imges/icon-256.png';
  // Remove 'data:image' base64 if present, use default instead
  if (strpos($og_image, 'data:image') === 0) {
    $og_image = $base . 'imges/icon-256.png';
  }
  ?>
  <meta property="og:site_name" content="DevicesArena">
  <meta property="og:title" content="<?php echo $page_title; ?>">
  <meta property="og:description" content="<?php echo $meta_description; ?>">
  <meta property="og:image" content="<?php echo $og_image; ?>">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="256">
  <meta property="og:image:height" content="256">
  <meta property="og:type" content="website">

  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo $page_title; ?>">
  <meta name="twitter:description" content="<?php echo $meta_description; ?>">
  <meta name="twitter:image" content="<?php echo $og_image; ?>">

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?php echo $base; ?>manifest.json">

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

  <link rel="stylesheet" href="<?php echo $base; ?>style.css">

  <!-- Schema.org Structured Data for Device Page -->
  <?php
  // Build breadcrumb schema based on the device
  $breadcrumbItems = [
    [
      "@type" => "ListItem",
      "position" => 1,
      "name" => "Home",
      "item" => "https://www.devicesarena.com/"
    ]
  ];

  if ($device) {
    // Determine device category
    $deviceType = "Smartphone";
    if (isset($device['type'])) {
      $type_lower = strtolower($device['type']);
      if (strpos($type_lower, 'tablet') !== false) {
        $deviceType = "Tablets";
      } elseif (strpos($type_lower, 'watch') !== false) {
        $deviceType = "Smartwatches";
      } else {
        $deviceType = "Smartphones";
      }
    }

    $breadcrumbItems[] = [
      "@type" => "ListItem",
      "position" => 2,
      "name" => $deviceType,
      "item" => "https://www.devicesarena.com/" . strtolower(str_replace(" ", "", $deviceType))
    ];

    $breadcrumbItems[] = [
      "@type" => "ListItem",
      "position" => 3,
      "name" => (isset($device['brand_name']) ? $device['brand_name'] . " " : "") . (isset($device['name']) ? $device['name'] : "Device"),
      "item" => "https://www.devicesarena.com/device/" . htmlspecialchars($device_slug)
    ];
  }
  ?>

  <!-- Breadcrumb Schema -->
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    }
  </script>

  <?php
  // Build comprehensive Product schema with all specifications
  if ($device) {
    $productSchema = [
      "@context" => "https://schema.org",
      "@type" => "Product",
      "name" => (isset($device['brand_name']) ? $device['brand_name'] . " " : "") . (isset($device['name']) ? $device['name'] : "Unknown Device"),
      "description" => "Complete specifications of the " . (isset($device['brand_name']) ? $device['brand_name'] . " " : "") . (isset($device['name']) ? $device['name'] : "device") . ". Includes display, processor, camera, battery, performance, design, connectivity, and all technical details.",
      "url" => "https://www.devicesarena.com/device/" . htmlspecialchars($device_slug)
    ];

    // Add image
    if (isset($device['image']) && !empty($device['image'])) {
      $productSchema["image"] = getAbsoluteImagePath($device['image'], 'https://www.devicesarena.com/');
    }

    // Add brand
    if (isset($device['brand_name']) && !empty($device['brand_name'])) {
      $productSchema["brand"] = [
        "@type" => "Brand",
        "name" => $device['brand_name']
      ];
    }

    // Add release date if available
    if (isset($device['release_date']) && !empty($device['release_date'])) {
      $productSchema["releaseDate"] = $device['release_date'];
    }

    // Build specifications array
    $specifications = [];

    // Display specifications
    if (isset($device['display_size']) && !empty($device['display_size'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Screen Size",
        "value" => $device['display_size'] . " inches"
      ];
    }

    if (isset($device['display_type']) && !empty($device['display_type'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Display Type",
        "value" => $device['display_type']
      ];
    }

    if (isset($device['display_resolution']) && !empty($device['display_resolution'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Display Resolution",
        "value" => $device['display_resolution']
      ];
    }

    if (isset($device['refresh_rate']) && !empty($device['refresh_rate'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Refresh Rate",
        "value" => $device['refresh_rate'] . "Hz"
      ];
    }

    // Processor and Performance
    if (isset($device['chipset_name']) && !empty($device['chipset_name'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Processor",
        "value" => $device['chipset_name']
      ];
    }

    if (isset($device['ram']) && !empty($device['ram'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "RAM Memory",
        "value" => $device['ram'] . "GB"
      ];
    }

    if (isset($device['storage']) && !empty($device['storage'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Internal Storage",
        "value" => $device['storage'] . "GB"
      ];
    }

    // Camera specifications
    if (isset($device['main_camera_resolution']) && !empty($device['main_camera_resolution'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Main Camera Resolution",
        "value" => $device['main_camera_resolution'] . "MP"
      ];
    }

    if (isset($device['selfie_camera_resolution']) && !empty($device['selfie_camera_resolution'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Front Camera Resolution",
        "value" => $device['selfie_camera_resolution'] . "MP"
      ];
    }

    // Battery
    if (isset($device['battery_capacity']) && !empty($device['battery_capacity'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Battery Capacity",
        "value" => $device['battery_capacity'] . "mAh"
      ];
    }

    if (isset($device['wired_charging']) && !empty($device['wired_charging'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Fast Charging",
        "value" => $device['wired_charging'] . "W"
      ];
    }

    // Design and Build
    if (isset($device['weight']) && !empty($device['weight'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Weight",
        "value" => $device['weight'] . "g"
      ];
    }

    if (isset($device['dimensions']) && !empty($device['dimensions'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Dimensions",
        "value" => $device['dimensions']
      ];
    }

    // Operating System
    if (isset($device['os']) && !empty($device['os'])) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Operating System",
        "value" => $device['os']
      ];
    }

    // Connectivity
    $networks = [];
    if (isset($device['network_5g']) && $device['network_5g']) $networks[] = "5G";
    if (isset($device['network_4g']) && $device['network_4g']) $networks[] = "4G LTE";
    if (isset($device['network_3g']) && $device['network_3g']) $networks[] = "3G";
    if (!empty($networks)) {
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Network Support",
        "value" => implode(", ", $networks)
      ];
    }

    if (isset($device['wifi']) && !empty($device['wifi'])) {
      $wifi_value = is_array($device['wifi']) ? implode(", ", $device['wifi']) : $device['wifi'];
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "WiFi",
        "value" => $wifi_value
      ];
    }

    if (isset($device['bluetooth']) && !empty($device['bluetooth'])) {
      $bluetooth_value = is_array($device['bluetooth']) ? implode(", ", $device['bluetooth']) : $device['bluetooth'];
      $specifications[] = [
        "@type" => "PropertyValue",
        "name" => "Bluetooth",
        "value" => $bluetooth_value
      ];
    }

    if (!empty($specifications)) {
      $productSchema["additionalProperty"] = $specifications;
    }

    // Add price if available
    if (isset($device['price']) && !empty($device['price'])) {
      $productSchema["offers"] = [
        "@type" => "Offer",
        "priceCurrency" => "USD",
        "price" => $device['price'],
        "availability" => "https://schema.org/InStock"
      ];
    }
  }
  ?>

  <!-- Product Schema for Detailed Device Information -->
  <?php if ($device && isset($productSchema)): ?>
    <script type="application/ld+json">
      <?php echo json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
    </script>
  <?php endif; ?>

  <!-- Generic HowTo Schema (for device research & specification checking) -->
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HowTo",
      "name": "How to Research Smartphone Specifications and Features",
      "description": "Complete guide to understanding and researching smartphone specifications including display, processor, camera, battery, and connectivity features to make informed device purchasing decisions.",
      "image": "https://www.devicesarena.com/imges/icon-256.png",
      "step": [{
          "@type": "HowToStep",
          "position": 1,
          "name": "Understand Display Specifications",
          "text": "Learn about display size, type (AMOLED, LCD, etc.), resolution, refresh rate, and brightness to choose the best screen for your needs."
        },
        {
          "@type": "HowToStep",
          "position": 2,
          "name": "Evaluate Processor and Performance",
          "text": "Check the chipset, CPU cores, GPU, and performance benchmarks to ensure adequate speed for your intended use."
        },
        {
          "@type": "HowToStep",
          "position": 3,
          "name": "Check Memory Specifications",
          "text": "Compare RAM amounts and storage capacity to determine if the device meets your multitasking and storage needs."
        },
        {
          "@type": "HowToStep",
          "position": 4,
          "name": "Review Camera Capabilities",
          "text": "Examine megapixel count, sensor size, aperture, OIS, and video recording capabilities to assess photo and video quality."
        },
        {
          "@type": "HowToStep",
          "position": 5,
          "name": "Assess Battery and Charging",
          "text": "Review battery capacity, battery life claims, and charging speeds (wired and wireless) to ensure it meets your usage patterns."
        },
        {
          "@type": "HowToStep",
          "position": 6,
          "name": "Check Connectivity Options",
          "text": "Verify 5G/4G support, WiFi standards, Bluetooth version, NFC, GPS, and other connectivity features you need."
        },
        {
          "@type": "HowToStep",
          "position": 7,
          "name": "Compare Device Variants",
          "text": "Use our comparison tool to compare devices side-by-side and see which best matches your specific requirements and budget."
        }
      ]
    }
  </script>

  <!-- FAQ Schema for Device Pages -->
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [{
          "@type": "Question",
          "name": "What are the main specifications of this device?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "This device features detailed specifications including screen size, display type and resolution, processor type, RAM capacity, internal storage, camera quality, battery capacity, operating system, design dimensions and weight, and comprehensive connectivity options. All specifications are provided in the detailed sections above."
          }
        },
        {
          "@type": "Question",
          "name": "How does this device compare to other models?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "You can compare this device side-by-side with up to 2 other devices of your choice using our advanced Comparison Tool. Simply select the devices you want to compare and view detailed specifications, features, performance metrics, camera capabilities, battery life, and more to make an informed decision."
          }
        },
        {
          "@type": "Question",
          "name": "What is the release date of this device?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "The detailed release date and announcement information for this device is available in the Launch section of the specifications. This includes the announcement date and availability timeline."
          }
        },
        {
          "@type": "Question",
          "name": "What connectivity options are available?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "This device supports various connectivity options including network generations (2G, 3G, 4G LTE, 5G), WiFi standards, Bluetooth versions, GPS, NFC, and USB connectivity. All connectivity specifications are detailed in the relevant sections."
          }
        },
        {
          "@type": "Question",
          "name": "Can I leave a review or comment?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes! You can share your experiences and opinions by leaving comments and reviews on this device page. Your feedback helps other users make informed decisions about this device and provides valuable insights to the community."
          }
        },
        {
          "@type": "Question",
          "name": "Where can I find more detailed information?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Complete technical specifications are organized in detailed sections covering display, hardware, memory, cameras, multimedia, connectivity, features, battery, and more. Scroll through the page to find all available information, or use our Phone Finder tool to explore similar devices."
          }
        }
      ]
    }
  </script>

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
    crossorigin="anonymous"></script>
</head>

<body style="background-color: #EFEBE9; overflow-x: hidden;">
  <!-- Desktop Navbar of Gsmarecn -->
  <?php include 'includes/gsmheader.php'; ?>
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
      padding: 16px 20px;
      display: flex;
      row-gap: 4px;
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
      /* word-break: break-word; */
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
      filter: blur(5px);
      z-index: 1;
    }

    .card-header * {
      position: relative;
      z-index: 2;
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

    @media (width:786px) {
      .phone-image:after {
        content: "";
        position: absolute;
        top: 0;
        left: 165px;
        width: 229px;
        height: 100%;
        background: transparent;
        z-index: 1;
      }

      .spec-subtitle {
        font-size: 10px;
      }

      .spec-description {
        font-size: 09px;
      }
    }

    <?php
    $image = $device["image"] ?? $device["image_1"] ?? "https://via.placeholder.com/300x400?text=No+Image";
    ?>.phone-image {
      margin-left: 5px;
      display: block;
      height: -webkit-fill-available;
      width: 165px;
      position: relative;
      z-index: 2;
      background: url(<?php echo $image; ?>);
      background-position: right;
      background-color: #fff;
      background-size: contain;
      background-repeat: no-repeat;
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
      font-weight: 700;
      text-transform: uppercase;
    }

    td strong {
      display: inline-block;
      width: 100%;
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
      border-color: #1B2035;
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
      border-color: #1B2035;
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

    .pad {
      font-weight: 700;
    }

    .spec-description {
      font-size: 12px;
    }
  </style>
  <div class="d-lg-none d-block">

    <div class="phone-box">
      <?php
      $title = $device['brand_name']
        . ' '
        . (
          $device['name']
          ?? $device['model_name']
          ?? $device['phone_name']
          ?? $device['title']
          ?? ''
        );
      ?>
      <h2 class="phone-title"><?php echo htmlspecialchars($title); ?></h2>


      <p class="phone-subtitle">SPECIFICATIONS</p>

      <!-- MAIN CONTENT -->
      <div class="phone-main">

        <!-- LEFT IMAGE AS BACKGROUND -->
        <div class="phone-image"
          style="background-image: url('<?php echo htmlspecialchars($device['image']); ?>');">
        </div>

        <!-- RIGHT SPECS COLUMN -->
        <div class="spec-col">

          <?php
          $statKeys = ['display', 'camera', 'performance', 'battery'];

          foreach ($statKeys as $key):
            if (isset($deviceStats[$key])):
              $stat = $deviceStats[$key];
          ?>

              <div class="spec-row">
                <img src="<?php echo htmlspecialchars($stat['icon']); ?>" class="spec-icon" alt="">

                <div class="spec-text">
                  <strong><?php echo htmlspecialchars($stat['title']); ?></strong>
                  <small><?php echo htmlspecialchars($stat['subtitle']); ?></small>
                </div>
              </div>

          <?php endif;
          endforeach; ?>

        </div>
      </div>

      <!-- BOTTOM SECTION -->
      <div class="bottom-section">
        <?php if ($review_post): ?>
          <button class="review-btn" onclick="window.location.href='post.php?slug=<?php echo urlencode($review_post['slug']); ?>'">
            REVIEW
          </button>
        <?php else: ?>
          <button class="review-btn" disabled style="opacity: 0.5; cursor: default;" title="No review available">
            REVIEW
          </button>
        <?php endif; ?>
        <button class="review-btn" onclick="window.location.href='/compare/<?php echo htmlspecialchars($device['slug']); ?>'">
          COMPARE
        </button>
        <button class="review-btn" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">
          OPINIONS
        </button>
        <button class="review-btn" onclick="showPicturesModal()">
          PICTURES
        </button>
        <button class="review-btn" onclick="showRelatedPhonesModal()">
          RELATED PHONES
        </button>
        <!-- <div style="display: flex; gap: 22px;">

          <div class="stat-box">
            <img src="/imges/stat-down.png" alt="">
            <p>
              0%<br>
              <small>0 hits</small>
            </p>
          </div>

          <div class="stat-box">
            <img src="/imges/heart.png" alt="">
            <p>
              0<br>
              <small>Become a fan</small>
            </p>
          </div>

        </div> -->

      </div>

    </div>

  </div>


  <style>
    /* OUTER BOX */
    .phone-box {
      background: #fff;
      border: 1px solid #dcdcdc;
      padding: 16px;
      border-radius: 4px;
      width: 100%;
      box-sizing: border-box;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }

    /* TITLE */
    .phone-title {
      font-size: 26px;
      font-weight: 700;
      margin: 0;
      color: #111;
    }

    .phone-subtitle {
      font-size: 12px;
      color: #9b9b9b;
      letter-spacing: 1px;
      margin-top: 4px;
      margin-bottom: 14px;
    }

    /* MAIN WRAPPER */
    .phone-main {
      display: flex;
      gap: 14px;
    }

    /* RIGHT SPECS BOX */
    .spec-col {
      flex: 1;
      background: #f7f7f7;
      padding: 12px;
      position: relative;
      border-radius: 4px;
      z-index: 100;
    }

    /* SPEC ROW */
    .spec-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 18px;
    }

    .spec-icon {
      width: 22px;
      opacity: .65;
    }

    .spec-text strong {
      font-size: 17px;
      font-weight: 700;
      color: #222;
      display: block;
      font-family: 'Arial';
      line-height: 16px;
    }

    .spec-text small {
      font-size: 13px;
      color: #666;
      font-family: 'arial';
      display: block;
      margin-top: 1px;
    }

    /* REVIEW BUTTON */
    .review-btn {
      background: #bbb;
      border: none;
      color: #fff;
      padding: 10px 22px;
      border-radius: 5px;
      font-size: 15px;
      font-weight: 700;
      width: 100%;
      margin-bottom: 10px;
    }

    /* BOTTOM ROW */
    .bottom-section {
      margin-top: 16px;
      /* display: flex; */
      /* justify-content: space-between; */
      /* align-items: center; */
    }

    /* STATS */
    .stat-box {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .stat-box img {
      width: 22px;
      opacity: 0.75;
    }

    .stat-box p {
      margin: 0;
      font-size: 14px;
      line-height: 14px;
    }

    .stat-box small {
      font-size: 11px;
      color: #666;
    }
  </style>
  <div class="container  d-lg-block d-none support content-wrapper" id="Top"
    style=" margin-top: 4rem; padding-left: 0;">
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
            <div style="
    height: -webkit-fill-available;
    background: white;
">
              <!-- Left: Phone Image -->
              <div class="phone-image me-3 py-2  px-2" onclick="showPicturesModal()"></div>
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
                  <?php if ($review_post): ?>
                    <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor:pointer; font-weight: 700;" class="mt-2" onclick="window.location.href='post.php?slug=<?php echo urlencode($review_post['slug']); ?>'">REVIEW</h5>
                  <?php else: ?>
                    <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor:pointer; font-weight: 700; opacity: 0.5;" class="mt-2" title="No review available">REVIEW</h5>
                  <?php endif; ?>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor:pointer; font-weight: 700;" class="mt-2" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS</h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; cursor: pointer; font-weight: 700;" class="mt-2" onclick="showPicturesModal()">PICTURES</h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16px; font-weight: 700;" class="mt-2" onclick="window.location.href='/compare/<?php echo htmlspecialchars($device['slug']); ?>'">COMPARE </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue' ; font-size: 16 px; font-weight: 700;" class="mt-2"></h5>
                </div>
              </div>
            </div>
          </div>
        </div>


      </div>
      <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 0; padding: 0px;">
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
      <div class="col-lg-8 col-md-7 order-1" style="padding-right: 0;">
        <div class="bg-white">
          <table class="table forat">
            <tbody>
              <?php if (!empty($deviceSpecs)): ?>
                <?php $firstRowInSection = true; ?>
                <?php foreach ($deviceSpecs as $category => $rows): ?>
                  <?php if (is_array($rows) && !empty($rows)): ?>
                    <!-- Mobile: Section title as separate row -->
                    <tr class="d-lg-none">
                      <th class="spec-label">
                        <?php echo htmlspecialchars($category); ?>
                      </th>
                      <?php if ($category === 'NETWORK'): ?>
                        <th style="text-align: right; padding: 0.75rem;">
                          <button class="expand-btn" onclick="toggleExpandBtn(this)" style="background: none; border: none; color: #666; font-size: 11px; cursor: pointer; text-transform: uppercase; font-weight: 500;">EXPAND ▼</button>
                        </th>
                      <?php else: ?>
                        <th></th>
                      <?php endif; ?>
                    </tr>

                    <!-- Desktop + Mobile spec rows -->
                    <?php foreach ($rows as $rowIndex => $rowData): ?>
                      <tr <?php echo ($category === 'NETWORK' && $rowIndex > 0) ? 'class="network-row" style="display:none;"' : ''; ?>>
                        <!-- Desktop only: rowspan on first row -->
                        <?php if ($rowIndex === 0): ?>
                          <th class="spec-label d-none d-lg-table-cell" rowspan="<?php echo ($category === 'NETWORK') ? '1' : count($rows); ?>">
                            <?php echo htmlspecialchars($category); ?>
                          </th>
                        <?php endif; ?>
                        <td class="spec-subtitle"><strong><?php echo htmlspecialchars($rowData['field']); ?></strong></td>
                        <td class="spec-description" style="position: relative;">
                          <?php echo htmlspecialchars($rowData['description']); ?>
                          <?php if ($category === 'NETWORK' && $rowIndex === 0): ?>
                            <button class="expand-btn d-none d-lg-inline" onclick="toggleExpandBtn(this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; font-size: 11px; cursor: pointer; text-transform: uppercase; font-weight: 500;">EXPAND &#x25bc;</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Fallback if no database specs available -->
                <tr>
                  <th class="spec-label">DEVICE</th>
                  <td class="spec-subtitle"><strong>Name</strong></td>
                  <td class="spec-description"><?php echo htmlspecialchars($device['name'] ?? 'Unknown Device'); ?></td>
                </tr>
                <?php if (isset($device['brand_name'])): ?>
                  <tr>
                    <th class="spec-label" style="border-top: none;"></th>
                    <td class="spec-subtitle"><strong>Brand</strong></td>
                    <td class="spec-description"><?php echo htmlspecialchars($device['brand_name']); ?></td>
                  </tr>
                <?php endif; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <p style="font-size: 13px; text-transform: capitalize; padding: 6px 19px;"> <strong>Disclaimer:</strong>We can not guarantee that the information on this page is 100%
            correct.</p>
          <div class="d-block d-lg-flex">
            <?php if ($review_post): ?>
              <button class="pad" onclick="window.location.href='post.php?slug=<?php echo urlencode($review_post['slug']); ?>'">REVIEW</button>
            <?php else: ?>
              <button class="pad" disabled style="opacity: 0.5; cursor: default;" title="No review available">REVIEW</button>
            <?php endif; ?>
            <button class="pad" onclick="window.location.href='/compare/<?php echo htmlspecialchars($device['slug']); ?>'">COMPARE</button>
            <button class="pad" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth', block:'start'});">OPINIONS</button>
            <button class="pad" onclick="showPicturesModal()">PICTURES</button>
            <button class="pad" onclick="showRelatedPhonesModal()">RELATED PHONES</button>
          </div>
          <div class="comments" id="comments">
            <h5 class="border-bottom reader py-3 mx-2"><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> - user opinions and reviews</h5>

            <div class="first-user" style="background-color: #EDEEEE;">

              <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                  <div class="user-thread" id="comment-<?php echo $comment['id']; ?>">
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
                        <button type="button" class="btn btn-sm btn-link reply-btn p-0" style="color: #d50000; text-decoration: none; font-size: 13px; font-weight: 500;" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>" title="Reply to this comment">
                          <i class="fa fa-reply me-1"></i>Reply
                        </button>
                      </li>
                    </ul>
                  </div>
                  <?php if (!empty($comment['replies'])): ?>
                    <?php foreach ($comment['replies'] as $reply): ?>
                      <div class="user-thread comment-reply" id="comment-<?php echo $reply['id']; ?>" style="margin-left: 40px; border-left: 3px solid #d50000; padding-left: 12px;">
                        <div class="uavatar">
                          <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                        </div>
                        <ul class="uinfo2">
                          <li class="uname">
                            <a href="#" style="color: #555; text-decoration: none;">
                              <?php echo htmlspecialchars($reply['name']); ?>
                            </a>
                            <small class="text-muted ms-1" style="font-size: 11px;"><i class="fa fa-reply fa-xs"></i> replied</small>
                          </li>
                          <li class="upost">
                            <i class="fa-regular fa-clock fa-sm mx-1"></i>
                            <?php echo timeAgo($reply['created_at']); ?>
                          </li>
                        </ul>
                        <p class="uopin"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                        <ul class="uinfo">
                          <li class="ureply" style="list-style: none;">
                            <button type="button" class="btn btn-sm btn-link reply-btn p-0" style="color: #d50000; text-decoration: none; font-size: 13px; font-weight: 500;" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>" title="Reply to this thread">
                              <i class="fa fa-reply me-1"></i>Reply
                            </button>
                          </li>
                        </ul>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="user-thread text-center py-4">
                  <p class="uopin text-muted">No comments yet. Be the first to share your opinion!</p>
                </div>
              <?php endif; ?>

              <!-- Comment Form -->
              <div class="comment-form mt-4 mx-2 mb-3">
                <h6 class="mb-3 comment-form-title">Share Your Opinion</h6>
                <!-- Reply indicator (hidden by default, uses custom class to avoid auto-dismiss) -->
                <div id="reply-indicator" class="d-none" style="font-size: 13px; border-left: 4px solid #d50000; background-color: #e8f4fd; color: #31708f; padding: 8px 14px; border-radius: 6px; margin-bottom: 12px;">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-reply me-2"></i>Replying to <strong id="reply-to-name"></strong></span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" id="cancel-reply" title="Cancel reply" style="text-decoration: none;">
                      <i class="fa fa-times"></i> Cancel
                    </button>
                  </div>
                </div>
                <?php
                // Determine logged-in user details for prefilling
                $loggedInName = '';
                $loggedInEmail = '';
                $isUserLoggedIn = false;
                if (!empty($_SESSION['public_user_name'])) {
                  $loggedInName = $_SESSION['public_user_name'];
                  $loggedInEmail = $_SESSION['public_user_email'] ?? '';
                  $isUserLoggedIn = true;
                } elseif (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                  $loggedInName = $_SESSION['username'] ?? '';
                  $loggedInEmail = '';
                  $isUserLoggedIn = true;
                }
                ?>
                <form id="device-comment-form" method="POST">
                  <input type="hidden" name="action" value="comment_device">
                  <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['id']); ?>">
                  <input type="hidden" name="parent_id" id="parent_id" value="">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <input type="text" class="form-control" name="name" placeholder="Your Name" required <?php if ($isUserLoggedIn && $loggedInName): ?>value="<?php echo htmlspecialchars($loggedInName); ?>" disabled<?php endif; ?>>
                      <?php if ($isUserLoggedIn && $loggedInName): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($loggedInName); ?>"><?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                      <input type="email" class="form-control" name="email" placeholder="Your Email (optional)" <?php if ($isUserLoggedIn && $loggedInEmail): ?>value="<?php echo htmlspecialchars($loggedInEmail); ?>" disabled<?php endif; ?>>
                      <?php if ($isUserLoggedIn && $loggedInEmail): ?><input type="hidden" name="email" value="<?php echo htmlspecialchars($loggedInEmail); ?>"><?php endif; ?>
                    </div>
                  </div>
                  <div class="mb-3">
                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your thoughts about this device..." required></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" style="font-size: 13px; font-weight: 500; color: #555;"><i class="fa fa-shield-halved me-1"></i>Type the words shown below</label>
                    <div class="d-flex align-items-center gap-2">
                      <img src="" id="captcha-image" alt="CAPTCHA" style="height: 60px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;" title="Click to refresh" onclick="refreshCaptcha()">
                      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshCaptcha()" title="Refresh CAPTCHA" style="padding: 4px 10px;">
                        <i class="fa fa-rotate-right"></i>
                      </button>
                      <input type="text" class="form-control" name="captcha" id="captcha-input" placeholder="Enter the words" required autocomplete="off" style="max-width: 200px; height: 45px;">
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="button-links">Post Your Opinion</button>
                    <small class="text-muted">Comments are moderated and will appear after approval.</small>
                  </div>
                </form>
              </div>

              <div class="button-secondary-div d-flex justify-content-between align-items-center">

                <p class="div-last">Total reader comments: <b><?php echo $commentCount; ?></b></p>
              </div>
            </div>
          </div>

          <!-- <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-xl-728x90.jpg" alt="" class="webkit"> -->
        </div>
      </div>
      <!-- Left Section -->
      <div class="col-lg-4 bg-white col-md-5 order-2" style="padding-right: 0;">
        <div class="mb-4">
          <?php include 'includes/latest-devices.php'; ?>
          <?php include 'includes/comparisons-devices.php'; ?>
          <?php include 'includes/topviewed-devices.php'; ?>
          <?php include 'includes/topreviewed-devices.php'; ?>
          <?php include 'includes/instoresnow-devices.php'; ?>
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/gsmfooter.php'; ?>

  <!-- Brands Modal -->
  <div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
        <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
          <h5 class="modal-title" id="brandsModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
            <i class="fas fa-industry me-2"></i>All Brands
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <?php if (!empty($allBrandsModal)): ?>
              <?php foreach ($allBrandsModal as $brand): ?>
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
      <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
        <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 90vw; margin: auto;">
      <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
        <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
          <h5 class="modal-title" id="picturesModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
            <i class="fas fa-images me-2"></i><span id="picturesDeviceNameTitle"><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?></span> - Pictures
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
                    <div class="d-flex justify-content-center align-items-center" style="background-color: #F5F5F5; min-height: 300px; max-height: 80vh;">
                      <!-- Debug output -->
                      <div style="display: none;">Image path: <?php echo htmlspecialchars($image); ?></div>

                      <img src="<?php echo htmlspecialchars($image); ?>"
                        class="d-block img-fluid"
                        style="max-height: 70vh; max-width: 100%; height: auto; object-fit: contain; padding: 20px;"
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
              <div class="modal-footer" style="border-top: 1px solid #1B2035; background-color: #D7CCC8; padding: 10px;">
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

  <!-- Related Phones Modal -->
  <div class="modal fade" id="relatedPhonesModal" tabindex="-1" aria-labelledby="relatedPhonesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
        <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
          <h5 class="modal-title" id="relatedPhonesModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
            <i class="fas fa-mobile-alt me-2"></i>Related Phones
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="relatedPhonesModalBody">
          <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?php echo $base; ?>script.js"></script>
  <script>
    var COMMENT_AJAX_BASE = '<?php echo $base; ?>';
  </script>
  <script src="<?php echo $base; ?>js/comment-ajax.js"></script>

  <script>
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
  </script>

</body>

</html>

<script>
  // Handle clickable table rows for devices
  document.addEventListener('DOMContentLoaded', function() {
    // Handle device row clicks (for views and reviews tables)
    document.querySelectorAll('.clickable-row').forEach(function(row) {
      row.addEventListener('click', function() {
        const deviceId = this.getAttribute('data-device-id');
        if (deviceId) {
          // Track the view
          fetch('/track_device_view.php', {
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
          fetch('/track_device_view.php', {
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
        const device1Slug = this.getAttribute('data-device1-slug');
        const device2Slug = this.getAttribute('data-device2-slug');
        const device1Id = this.getAttribute('data-device1-id');
        const device2Id = this.getAttribute('data-device2-id');
        if ((device1Slug && device2Slug) || (device1Id && device2Id)) {
          // Track the comparison
          fetch('/track_device_comparison.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'device1_id=' + encodeURIComponent(device1Id) + '&device2_id=' + encodeURIComponent(device2Id)
          });

          // Redirect to comparison page using slugs (preferred) or IDs (fallback)
          const compareUrl = device1Slug && device2Slug ?
            `/compare/${device1Slug}-vs-${device2Slug}` :
            `/compare/${device1Id}-vs-${device2Id}`;
          window.location.href = compareUrl;
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
    fetch(`/get_phones_by_brand.php?brand_id=${brandId}`)
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
        // Convert relative image paths to absolute
        let imagePath = phone.image;
        if (imagePath && !imagePath.startsWith('/') && !imagePath.startsWith('http')) {
          imagePath = '/' + imagePath;
        }
        const phoneImage = imagePath ? `<img src="${imagePath}" alt="${phone.name}" style="width: 100%; max-width: 100%; height: 120px; object-fit: contain; margin: 8px; display: block;" onerror="this.style.display='none';">` : '';
        html += `
          <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <button class="device-cell-modal btn w-100 p-0" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; overflow: hidden;" onclick="goToDevice('${phone.slug || phone.id}')">
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
  function goToDevice(deviceSlugOrId) {
    if (typeof deviceSlugOrId === 'string' && /[a-z-]/.test(deviceSlugOrId)) {
      window.location.href = `/device/${encodeURIComponent(deviceSlugOrId)}`;
    } else {
      window.location.href = `/device/${deviceSlugOrId}`;
    }
  }

  // Show related phones modal
  function showRelatedPhonesModal() {
    const container = document.getElementById('relatedPhonesModalBody');

    // Show loading spinner
    container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';

    // Get current device ID
    const currentDeviceId = <?php echo json_encode($device['id'] ?? null); ?>;

    if (!currentDeviceId) {
      container.innerHTML = `
        <div class="text-center py-5">
          <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">Device information unavailable</h6>
        </div>
      `;
      const modal = new bootstrap.Modal(document.getElementById('relatedPhonesModal'));
      modal.show();
      return;
    }

    // Fetch related phones based on price bracket, year, and view count
    fetch(`/get_related_phones.php?device_id=${currentDeviceId}`)
      .then(response => response.json())
      .then(data => {
        displayRelatedPhonesModal(data);
      })
      .catch(error => {
        console.error('Error fetching related phones:', error);
        container.innerHTML = `
          <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
            <h6 class="text-muted">Failed to load related phones</h6>
          </div>
        `;
        const modal = new bootstrap.Modal(document.getElementById('relatedPhonesModal'));
        modal.show();
      });
  }

  // Display related phones in modal
  function displayRelatedPhonesModal(phones) {
    const container = document.getElementById('relatedPhonesModalBody');

    if (phones && phones.length > 0) {
      let html = '<div class="row">';
      phones.forEach(phone => {
        const phoneImage = phone.image ? `<img src="${phone.image}" alt="${phone.name}" style="width: 100%; height: 120px; object-fit: contain; margin: 8px;" onerror="this.style.display='none';">` : '';
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
          <h6 class="text-muted">No related phones available for this device at this moment, please come back later</h6>
        </div>
      `;
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('relatedPhonesModal'));
    modal.show();
  }

  // Show post details in modal
  function showPostDetails(postId) {
    fetch(`/get_post_details.php?id=${postId}`)
      .then(response => response.text())
      .then(data => {
        window.location.href = `<?php echo $base; ?>post/${postId}`;
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load post details');
      });
  }

  // Show device details in modal
  function showDeviceDetails(deviceId) {
    fetch(`/get_device_details.php?id=${deviceId}`)
      .then(response => response.text())
      .then(data => {
        window.location.href = `<?php echo $base; ?>device/${deviceId}`;
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

  // Store the current device name in a global variable for use in modals
  const currentDeviceName = <?php echo json_encode(htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device'))); ?>;

  function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
  }

  function showAllImages() {
    new bootstrap.Modal(document.getElementById('allImagesModal')).show();
  }

  // Pictures Modal Functions
  function showPicturesModal() {
    // Get the device name from the page's heading
    const phoneTitle = document.querySelector('.phone-title');
    const deviceNameSpan = document.getElementById('picturesDeviceNameTitle');

    if (deviceNameSpan && phoneTitle) {
      // Extract device name from the phone-title heading
      deviceNameSpan.textContent = phoneTitle.textContent.trim();
    }

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
        fetch('/handle_newsletter.php', {
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

  // Handle expandable text for truncated descriptions
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('expand-dots')) {
      const fullText = e.target.getAttribute('data-full');
      if (fullText) {
        // Decode HTML entities
        const temp = document.createElement('div');
        temp.innerHTML = fullText;
        const decodedText = temp.textContent || temp.innerText || '';

        // Get the text node that contains the truncated text (should be before the expand-dots span)
        const dotsSpan = e.target;
        const prevNode = dotsSpan.previousSibling;

        if (prevNode && prevNode.nodeType === Node.TEXT_NODE) {
          // Replace the text node with the full text
          prevNode.textContent = decodedText;
        }

        // Remove the expand-dots span
        dotsSpan.remove();
      }
    }
  });

  // Toggle expand/collapse button
  function toggleExpandBtn(btn) {
    const networkRows = document.querySelectorAll('.network-row');
    const networkLabel = document.querySelector('.spec-label[rowspan]');
    const originalRowspan = networkRows.length + 1; // +1 for the first row

    if (btn.textContent.includes('COLLAPSE')) {
      btn.textContent = 'EXPAND ▼';
      networkRows.forEach(row => row.style.display = 'none');
      if (networkLabel) networkLabel.setAttribute('rowspan', '1');
    } else {
      btn.textContent = 'COLLAPSE ▲';
      networkRows.forEach(row => row.style.display = '');
      if (networkLabel) networkLabel.setAttribute('rowspan', originalRowspan);
    }
  }
</script>
</body>

</html>