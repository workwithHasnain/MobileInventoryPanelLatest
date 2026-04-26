<?php
session_start();
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

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

// ── Auth variables required by navbar.php ──
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';
if (!isset($_SESSION['notif_seen'])) $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Weekly posts for notification bell
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) {
  $weekly_posts = [];
}

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


// Get latest devices for sidebar widget (15 latest)
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);

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
  $phones_json = __DIR__ . '/../data/phones.json';
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
      return htmlspecialchars($truncated) . '<span class="expand-dots" data-full="' . str_replace('"', '&quot;', htmlspecialchars($text)) . '">  ...</span>';
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
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
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
  <meta property="og:title" content="<?php echo $page_title; ?>">
  <meta property="og:description" content="<?php echo $meta_description; ?>">
  <?php
  $og_image = !empty($device['image']) ? htmlspecialchars(getAbsoluteImagePath($device['image'], $base)) : $base . 'imges/icon-256.png';
  if (strpos($og_image, 'data:image') === 0) {
    $og_image = $base . 'imges/icon-256.png';
  }
  ?>
  <meta property="og:image" content="<?php echo $og_image; ?>">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446" crossorigin="anonymous"></script>
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-2LDCSSMXJT');
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <script>
    (function() {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <?php
  // Schema data
  $breadcrumbItems = [
    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => "https://www.devicesarena.com/"]
  ];

  if ($device) {
    $deviceType = "Smartphone";
    if (isset($device['type'])) {
      $type_lower = strtolower($device['type']);
      if (strpos($type_lower, 'tablet') !== false) $deviceType = "Tablets";
      elseif (strpos($type_lower, 'watch') !== false) $deviceType = "Smartwatches";
      else $deviceType = "Smartphones";
    }

    $breadcrumbItems[] = [
      "@type" => "ListItem", "position" => 2, "name" => $deviceType, "item" => "https://www.devicesarena.com/" . strtolower(str_replace(" ", "", $deviceType))
    ];

    $breadcrumbItems[] = [
      "@type" => "ListItem", "position" => 3, "name" => (isset($device['brand_name']) ? $device['brand_name'] . " " : "") . (isset($device['name']) ? $device['name'] : "Device"),
      "item" => "https://www.devicesarena.com/device/" . htmlspecialchars($device_slug)
    ];
  }
  ?>
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    }
  </script>

</head>

<body>
  <!-- ══════════════════════ NAVBAR ══════════════════════ -->
  <?php include(__DIR__ . '/includes/navbar.php'); ?>

  <!-- ══════════════════════ AUTH MODALS ══════════════════════ -->
  <?php include(__DIR__ . '/includes/login-modal.php'); ?>
  <?php include(__DIR__ . '/includes/signup-modal.php'); ?>
  <?php include(__DIR__ . '/includes/profile-modal.php'); ?>

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-page">
    <div class="da-content-area">
      <!-- Main Content -->
      <main>
        <!-- Device Hero -->
        <div class="da-device-hero">
          <div class="da-device-img" onclick="window.location.href='<?php echo $base; ?>device/<?php echo htmlspecialchars($device_slug); ?>/images'">
            <?php
            $heroImage = $device["image"] ?? $device["image_1"] ?? "";
            if (!empty($heroImage)): ?>
              <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($heroImage, $base)); ?>" alt="<?php echo htmlspecialchars($page_title); ?>">
            <?php else: ?>
              <div class="da-img-fallback"><i class="fa fa-mobile-screen"></i></div>
            <?php endif; ?>
          </div>

          <div class="da-device-info">
            <div class="da-section-label"><span><?php echo htmlspecialchars($device['brand_name'] ?? 'Device'); ?></span></div>
            <h1 class="da-device-title"><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?></h1>

            <div class="da-device-highlights">
              <?php if (!empty($deviceHighlights)): ?>
                <?php foreach ($deviceHighlights as $key => $highlight): ?>
                  <span class="da-highlight-badge"><?php echo htmlspecialchars(strip_tags($highlight)); ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="da-device-actions">
              <?php if ($review_post): ?>
                <button class="da-cta-btn da-device-action-btn" onclick="window.location.href='<?php echo $base; ?>post/<?php echo urlencode($review_post['slug']); ?>'"><i class="fa fa-star"></i> Review</button>
              <?php else: ?>
                <button class="da-cta-btn da-device-action-btn secondary" disabled title="No review available"><i class="fa fa-star"></i> Review</button>
              <?php endif; ?>
              <button class="da-cta-btn da-device-action-btn secondary" onclick="window.location.href='<?php echo $base; ?>compare/<?php echo htmlspecialchars($device['slug'] ?? $device_slug); ?>'"><i class="fa fa-scale-balanced"></i> Compare</button>
              <button class="da-cta-btn da-device-action-btn secondary" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth',block:'start'})"><i class="fa fa-comments"></i> Opinions</button>
              <button class="da-cta-btn da-device-action-btn secondary" onclick="window.location.href='<?php echo $base; ?>device/<?php echo htmlspecialchars($device_slug); ?>/images'"><i class="fa fa-images"></i> Pictures</button>
              <button class="da-cta-btn da-device-action-btn secondary" onclick="showRelatedPhonesModal()"><i class="fa fa-mobile-screen-button"></i> Related</button>
            </div>

            <!-- Stats Bar -->
            <div class="da-device-stats">
              <?php
              $statMeta = ['display'=>'fa-expand','camera'=>'fa-camera','performance'=>'fa-microchip','battery'=>'fa-battery-full'];
              foreach ($statMeta as $key => $icon):
                if (!isset($deviceStats[$key])) continue;
                $stat = $deviceStats[$key];
              ?>
                <div class="da-stat-box">
                  <div class="da-stat-icon"><i class="fa <?php echo $icon; ?>"></i></div>
                  <div class="da-stat-title"><?php echo htmlspecialchars($stat['title']); ?></div>
                  <div class="da-stat-subtitle"><?php echo htmlspecialchars($stat['subtitle']); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Specs Table -->
        <div class="da-widget mt-4">
          <div class="da-widget-header">
            <h3>Full Specifications</h3>
            <div class="da-widget-icon"><i class="fa fa-list"></i></div>
          </div>
          <div class="da-widget-body" style="padding:0">
            <table class="da-specs-table">
              <tbody>
                <?php if (!empty($deviceSpecs)): ?>
                  <?php foreach ($deviceSpecs as $category => $rows): ?>
                    <?php if (is_array($rows) && !empty($rows)): ?>
                      <?php foreach ($rows as $rowIndex => $rowData): ?>
                        <?php if ($category === 'NETWORK' && $rowIndex > 0): ?>
                          <tr class="da-specs-row network-row" style="display:none;">
                        <?php else: ?>
                          <tr class="da-specs-row">
                        <?php endif; ?>
                          <?php if ($rowIndex === 0): ?>
                            <th class="da-specs-category" rowspan="<?php echo ($category === 'NETWORK') ? '1' : count($rows); ?>">
                              <?php echo htmlspecialchars($category); ?>
                            </th>
                          <?php endif; ?>
                          <td class="da-specs-field"><?php echo htmlspecialchars($rowData['field']); ?></td>
                          <td class="da-specs-value">
                            <?php if ($category === 'NETWORK' && $rowIndex === 0): ?>
                              <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo $rowData['description']; ?></span>
                                <button class="da-spec-expand" onclick="toggleExpandBtn(this)">EXPAND ▼</button>
                              </div>
                            <?php else: ?>
                              <?php echo $rowData['description']; ?>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr class="da-specs-row">
                    <th class="da-specs-category">DEVICE</th>
                    <td class="da-specs-field">Name</td>
                    <td class="da-specs-value"><?php echo htmlspecialchars($device['name'] ?? 'Unknown Device'); ?></td>
                  </tr>
                  <?php if (isset($device['brand_name'])): ?>
                    <tr class="da-specs-row">
                      <th class="da-specs-category"></th>
                      <td class="da-specs-field">Brand</td>
                      <td class="da-specs-value"><?php echo htmlspecialchars($device['brand_name']); ?></td>
                    </tr>
                  <?php endif; ?>
                <?php endif; ?>
              </tbody>
            </table>
            <div class="da-specs-disclaimer"><i class="fa fa-circle-info"></i> Disclaimer: We can not guarantee that the information on this page is 100% correct.</div>
          </div>
        </div>

        <!-- Comments Section -->
        <div class="da-widget mt-4" id="comments">
          <div class="da-widget-header">
            <h3>User Opinions and Reviews</h3>
            <div class="da-widget-icon"><i class="fa fa-comments"></i></div>
          </div>
          <div class="da-widget-body">
            
            <div class="da-comments-list">
              <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                  <div class="da-comment-thread" id="comment-<?php echo $comment['id']; ?>">
                    <div class="da-comment-avatar">
                      <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                    </div>
                    <div class="da-comment-content">
                      <div class="da-comment-header">
                        <span class="da-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                        <span class="da-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($comment['created_at']); ?></span>
                      </div>
                      <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                      <button class="da-reply-btn reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                    </div>
                  </div>
                  <?php if (!empty($comment['replies'])): ?>
                    <?php foreach ($comment['replies'] as $reply): ?>
                      <div class="da-comment-thread da-comment-reply" id="comment-<?php echo $reply['id']; ?>">
                        <div class="da-comment-avatar">
                          <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                        </div>
                        <div class="da-comment-content">
                          <div class="da-comment-header">
                            <span class="da-comment-name"><?php echo htmlspecialchars($reply['name']); ?></span>
                            <small class="da-replied-tag"><i class="fa fa-reply fa-xs"></i> replied</small>
                            <span class="da-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($reply['created_at']); ?></span>
                          </div>
                          <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></div>
                          <button class="da-reply-btn reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="da-empty"><i class="fa fa-comments"></i>No comments yet. Be the first to share your opinion!</div>
              <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <div class="da-comment-form-wrap mt-4">
              <h4 class="mb-3">Share Your Opinion</h4>
              <div id="reply-indicator" class="da-reply-indicator d-none">
                <div><i class="fa fa-reply me-2"></i>Replying to <strong id="reply-to-name"></strong></div>
                <button type="button" id="cancel-reply" class="da-btn-close"><i class="fa fa-times"></i></button>
              </div>
              <?php
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
              <form id="device-comment-form" method="POST" class="da-form">
                <input type="hidden" name="action" value="comment_device">
                <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['id']); ?>">
                <input type="hidden" name="parent_id" id="parent_id" value="">
                
                <div class="da-form-row">
                  <div class="da-form-group">
                    <input type="text" class="da-input" name="name" placeholder="Your Name" required <?php if ($isUserLoggedIn && $loggedInName): ?>value="<?php echo htmlspecialchars($loggedInName); ?>" disabled<?php endif; ?>>
                    <?php if ($isUserLoggedIn && $loggedInName): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($loggedInName); ?>"><?php endif; ?>
                  </div>
                  <div class="da-form-group">
                    <input type="email" class="da-input" name="email" placeholder="Your Email (optional)" <?php if ($isUserLoggedIn && $loggedInEmail): ?>value="<?php echo htmlspecialchars($loggedInEmail); ?>" disabled<?php endif; ?>>
                    <?php if ($isUserLoggedIn && $loggedInEmail): ?><input type="hidden" name="email" value="<?php echo htmlspecialchars($loggedInEmail); ?>"><?php endif; ?>
                  </div>
                </div>
                
                <div class="da-form-group">
                  <textarea class="da-input" name="comment" rows="4" placeholder="Share your thoughts about this device..." required></textarea>
                </div>
                
                <div class="da-form-group da-captcha-group">
                  <label>Type the words shown below</label>
                  <div class="da-captcha-box">
                    <img src="<?php echo $base; ?>captcha.php" id="captcha-image" alt="CAPTCHA" onclick="refreshCaptcha()">
                    <button type="button" class="da-cta-btn secondary" onclick="refreshCaptcha()"><i class="fa fa-rotate-right"></i></button>
                    <input type="text" class="da-input" name="captcha" id="captcha-input" placeholder="Enter the words" required autocomplete="off">
                  </div>
                </div>
                
                <div class="da-form-footer">
                  <button type="submit" class="da-cta-btn">Post Your Opinion</button>
                  <small>Comments are moderated and will appear after approval.</small>
                </div>
              </form>
            </div>
            
            <div class="da-comment-count-footer mt-4 pb-2">
              Total reader comments: <b class="text-white"><?php echo $commentCount; ?></b>
            </div>
          </div>
        </div>

      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">
        <?php include(__DIR__ . '/includes/sidebar/brands-area.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/ad-placeholder.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/latest-devices.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/popular-comparisons.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/top-daily-interests.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/top-by-fans.php'); ?>
      </aside>
    </div>

    <!-- BOTTOM AREA -->
    <?php include(__DIR__ . '/includes/bottom-area/in-stores-now.php'); ?>
    <?php include(__DIR__ . '/includes/bottom-area/trending-comparisons.php'); ?>
    <?php include(__DIR__ . '/includes/bottom-area/featured-posts.php'); ?>
    <?php include(__DIR__ . '/includes/bottom-area/brand-marquee.php'); ?>
  </div>

  <!-- ══════════════════════ FOOTER ══════════════════════ -->
  <?php include(__DIR__ . '/includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    window.baseURL = '<?php echo $base; ?>';
    var COMMENT_AJAX_BASE = '<?php echo $base; ?>';
  </script>
  <script src="<?php echo $base; ?>js/comment-ajax.js"></script>

  <script>
    // ── Theme Toggle ──
    const themeToggles = [document.getElementById('da-theme-toggle'), document.getElementById('da-mobile-theme-toggle')];

    function updateThemeIcons() {
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      themeToggles.forEach(btn => {
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (icon) {
          icon.className = isLight ? 'fa fa-moon' : 'fa fa-sun';
        }
      });
    }
    updateThemeIcons();

    themeToggles.forEach(btn => {
      if (btn) {
        btn.addEventListener('click', () => {
          if (document.documentElement.getAttribute('data-theme') === 'light') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('da-theme', 'dark');
          } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('da-theme', 'light');
          }
          updateThemeIcons();
        });
      }
    });

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      if(navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    if(hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        mobileMenu.classList.toggle('open');
        document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
        });
    }

    function closeMobileMenu() {
      if(hamburger) hamburger.classList.remove('open');
      if(mobileMenu) mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Toggle Network Expand/Collapse ──
    function toggleExpandBtn(btn) {
      const networkRows = document.querySelectorAll('.network-row');
      const networkLabel = document.querySelector('.da-spec-category[rowspan]');
      const originalRowspan = networkRows.length + 1;

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

    // ── Expand Dots Logic ──
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('expand-dots')) {
        const fullText = e.target.getAttribute('data-full');
        if (fullText) {
          const temp = document.createElement('div');
          temp.innerHTML = fullText;
          const decodedText = temp.textContent || temp.innerText || '';
          
          const dotsSpan = e.target;
          const prevNode = dotsSpan.previousSibling;
          
          if (prevNode && prevNode.nodeType === Node.TEXT_NODE) {
            prevNode.textContent = decodedText;
          }
          dotsSpan.remove();
        }
      }
    });
  </script>
  <script src="<?php echo $base; ?>redesign/sliders.js"></script>
</body>
</html>
