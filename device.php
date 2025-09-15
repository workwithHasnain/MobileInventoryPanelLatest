<?php
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once 'database_functions.php';
require_once 'phone_data.php';

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
    SELECT b.*, COUNT(p.id) as device_count 
    FROM brands b 
    INNER JOIN phones p ON b.id = p.brand_id 
    GROUP BY b.id, b.name 
    ORDER BY b.name ASC 
    LIMIT 36
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get device ID from URL
$device_id = $_GET['id'] ?? '';

if (!isset($_GET['id']) || $_GET['id'] === '') {
  header("Location: index.php");
  exit();
}

// Function to get device details
function getDeviceDetails($pdo, $device_id)
{
  // Try database first (comprehensive data source)
  try {
    $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
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

  // Network specifications
  $network_tech = [];
  if ($device['network_2g']) $network_tech[] = '2G';
  if ($device['network_3g']) $network_tech[] = '3G';
  if ($device['network_4g']) $network_tech[] = '4G';
  if ($device['network_5g']) $network_tech[] = '5G';

  if (!empty($network_tech)) {
    $network_details = '<strong>Technology</strong> ' . implode(' / ', $network_tech);
    if ($device['dual_sim']) $network_details .= '<br><strong>SIM</strong> Dual SIM';
    if ($device['esim']) $network_details .= ', eSIM';
    if ($device['sim_size']) $network_details .= ' (' . $device['sim_size'] . ')';
    $specs['NETWORK'] = $network_details;
  }

  // Launch specifications
  if ($device['release_date'] || $device['availability']) {
    $launch_details = '';
    if ($device['release_date']) {
      $launch_details .= '<strong>Released</strong> ' . date('F j, Y', strtotime($device['release_date']));
    }
    if ($device['availability']) {
      if ($launch_details) $launch_details .= '<br>';
      $launch_details .= '<strong>Status</strong> ' . $device['availability'];
    }
    if ($device['price']) {
      $launch_details .= '<br><strong>Price</strong> $' . number_format($device['price'], 2);
    }
    $specs['LAUNCH'] = $launch_details;
  }

  // Body specifications
  if ($device['dimensions_length'] || $device['dimensions_width'] || $device['dimensions_thickness'] || $device['weight']) {
    $body_details = '';
    if ($device['dimensions_length'] && $device['dimensions_width'] && $device['dimensions_thickness']) {
      $body_details .= '<strong>Dimensions</strong> ' .
        $device['dimensions_length'] . ' x ' .
        $device['dimensions_width'] . ' x ' .
        $device['dimensions_thickness'] . ' mm';
    }
    if ($device['weight']) {
      if ($body_details) $body_details .= '<br>';
      $body_details .= '<strong>Weight</strong> ' . $device['weight'] . ' g';
    }
    $specs['BODY'] = $body_details;
  }

  // Display specifications
  if ($device['display_type'] || $device['display_size'] || $device['display_resolution']) {
    $display_details = '';
    if ($device['display_type']) {
      $display_details .= '<strong>Type</strong> ' . $device['display_type'];
      if ($device['display_technology']) $display_details .= ', ' . $device['display_technology'];
      if ($device['refresh_rate']) $display_details .= ', ' . $device['refresh_rate'] . 'Hz';
      if ($device['hdr']) $display_details .= ', HDR';
      if ($device['billion_colors']) $display_details .= ', 1B colors';
    }
    if ($device['display_size']) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Size</strong> ' . $device['display_size'] . ' inches';
    }
    if ($device['display_resolution']) {
      if ($display_details) $display_details .= '<br>';
      $display_details .= '<strong>Resolution</strong> ' . $device['display_resolution'];
    }
    $specs['DISPLAY'] = $display_details;
  }

  // Platform specifications
  if ($device['os'] || $device['chipset_name'] || $device['cpu_cores'] || $device['gpu']) {
    $platform_details = '';
    if ($device['os']) {
      $platform_details .= '<strong>OS</strong> ' . $device['os'];
    }
    if ($device['chipset_name']) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>Chipset</strong> ' . $device['chipset_name'];
    }
    if ($device['cpu_cores'] || $device['cpu_frequency']) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>CPU</strong> ';
      if ($device['cpu_cores']) $platform_details .= $device['cpu_cores'] . '-core';
      if ($device['cpu_frequency']) $platform_details .= ' (' . $device['cpu_frequency'] . ' GHz)';
    }
    if ($device['gpu']) {
      if ($platform_details) $platform_details .= '<br>';
      $platform_details .= '<strong>GPU</strong> ' . $device['gpu'];
    }
    $specs['PLATFORM'] = $platform_details;
  }

  // Memory specifications
  if ($device['ram_internal'] || $device['storage_internal'] || $device['card_slot']) {
    $memory_details = '';
    if ($device['card_slot'] !== null) {
      $memory_details .= '<strong>Card slot</strong> ' . ($device['card_slot'] ? 'Yes' : 'No');
      if ($device['storage_expandable']) $memory_details .= ' (' . $device['storage_expandable'] . ')';
    }
    if ($device['storage_internal'] || $device['ram_internal']) {
      if ($memory_details) $memory_details .= '<br>';
      $memory_details .= '<strong>Internal</strong> ';
      if ($device['storage_internal']) $memory_details .= $device['storage_internal'];
      if ($device['ram_internal']) $memory_details .= ' RAM: ' . $device['ram_internal'];
    }
    $specs['MEMORY'] = $memory_details;
  }

  // Main Camera specifications
  if ($device['main_camera_resolution'] || $device['main_camera_count']) {
    $camera_details = '';
    if ($device['main_camera_count'] > 1) {
      $camera_details .= '<strong>' . ucfirst(convertNumberToWord($device['main_camera_count'])) . '</strong><br>';
    } else {
      $camera_details .= '<strong>Single</strong><br>';
    }
    if ($device['main_camera_resolution']) {
      $camera_details .= $device['main_camera_resolution'];
    }

    // Camera features
    $features = [];
    if ($device['main_camera_ois']) $features[] = 'OIS';
    if ($device['main_camera_telephoto']) $features[] = 'Telephoto';
    if ($device['main_camera_ultrawide']) $features[] = 'Ultrawide';
    if ($device['main_camera_macro']) $features[] = 'Macro';
    if ($device['main_camera_flash']) $features[] = 'Flash';
    if ($device['main_camera_features'] && is_array($device['main_camera_features'])) {
      $features = array_merge($features, $device['main_camera_features']);
    } elseif ($device['main_camera_features'] && is_string($device['main_camera_features'])) {
      // Handle PostgreSQL array string format
      $array_features = str_replace(['{', '}'], '', $device['main_camera_features']);
      $array_features = explode(',', $array_features);
      $features = array_merge($features, array_map('trim', $array_features));
    }

    if (!empty($features)) {
      $camera_details .= '<br><strong>Features</strong> ' . implode(', ', $features);
    }
    if ($device['main_camera_video']) {
      $camera_details .= '<br><strong>Video</strong> ' . $device['main_camera_video'];
    }
    $specs['MAIN CAMERA'] = $camera_details;
  }

  // Selfie Camera specifications
  if ($device['selfie_camera_resolution'] || $device['selfie_camera_count']) {
    $selfie_details = '';
    if ($device['selfie_camera_count'] > 1) {
      $selfie_details .= '<strong>' . ucfirst(convertNumberToWord($device['selfie_camera_count'])) . '</strong> ';
    } else {
      $selfie_details .= '<strong>Single</strong> ';
    }
    if ($device['selfie_camera_resolution']) {
      $selfie_details .= $device['selfie_camera_resolution'];
    }
    if ($device['selfie_camera_features'] && is_array($device['selfie_camera_features'])) {
      $selfie_details .= '<br><strong>Features</strong> ' . implode(', ', $device['selfie_camera_features']);
    } elseif ($device['selfie_camera_features'] && is_string($device['selfie_camera_features'])) {
      // Handle PostgreSQL array string format
      $array_features = str_replace(['{', '}'], '', $device['selfie_camera_features']);
      $array_features = explode(',', $array_features);
      $selfie_details .= '<br><strong>Features</strong> ' . implode(', ', array_map('trim', $array_features));
    }
    if ($device['selfie_camera_video']) {
      $selfie_details .= '<br><strong>Video</strong> ' . $device['selfie_camera_video'];
    }
    $specs['SELFIE CAMERA'] = $selfie_details;
  }

  // Sound specifications
  if ($device['loudspeaker'] !== null || $device['audio_jack_35mm'] !== null) {
    $sound_details = '';
    if ($device['loudspeaker'] !== null) {
      $sound_details .= '<strong>Loudspeaker</strong> ' . ($device['loudspeaker'] ? 'Yes' : 'No');
    }
    if ($device['audio_jack_35mm'] !== null) {
      if ($sound_details) $sound_details .= '<br>';
      $sound_details .= '<strong>3.5mm jack</strong> ' . ($device['audio_jack_35mm'] ? 'Yes' : 'No');
    }
    $specs['SOUND'] = $sound_details;
  }

  // Communications specifications
  $comms_details = '';
  if ($device['wifi']) {
    $comms_details .= '<strong>WLAN</strong> ' . $device['wifi'];
  }
  if ($device['bluetooth']) {
    if ($comms_details) $comms_details .= '<br>';
    $comms_details .= '<strong>Bluetooth</strong> ' . $device['bluetooth'];
  }
  if ($device['gps'] !== null) {
    if ($comms_details) $comms_details .= '<br>';
    $comms_details .= '<strong>Positioning</strong> ' . ($device['gps'] ? 'GPS' : 'No');
  }
  if ($device['nfc'] !== null) {
    if ($comms_details) $comms_details .= '<br>';
    $comms_details .= '<strong>NFC</strong> ' . ($device['nfc'] ? 'Yes' : 'No');
  }
  if ($device['radio'] !== null) {
    if ($comms_details) $comms_details .= '<br>';
    $comms_details .= '<strong>Radio</strong> ' . ($device['radio'] ? 'Yes' : 'No');
  }
  if ($device['usb']) {
    if ($comms_details) $comms_details .= '<br>';
    $comms_details .= '<strong>USB</strong> ' . $device['usb'];
  }
  if ($comms_details) {
    $specs['COMMUNICATIONS'] = $comms_details;
  }

  // Features specifications
  $features_details = '';
  if ($device['fingerprint'] !== null) {
    $features_details .= '<strong>Fingerprint</strong> ' . ($device['fingerprint'] ? 'Yes' : 'No');
  }
  if ($device['face_unlock'] !== null) {
    if ($features_details) $features_details .= '<br>';
    $features_details .= '<strong>Face unlock</strong> ' . ($device['face_unlock'] ? 'Yes' : 'No');
  }
  if ($device['sensors'] && is_array($device['sensors'])) {
    if ($features_details) $features_details .= '<br>';
    $features_details .= '<strong>Sensors</strong> ' . implode(', ', $device['sensors']);
  } elseif ($device['sensors'] && is_string($device['sensors'])) {
    // Handle PostgreSQL array string format
    $array_sensors = str_replace(['{', '}'], '', $device['sensors']);
    $array_sensors = explode(',', $array_sensors);
    if ($features_details) $features_details .= '<br>';
    $features_details .= '<strong>Sensors</strong> ' . implode(', ', array_map('trim', $array_sensors));
  }
  if ($features_details) {
    $specs['FEATURES'] = $features_details;
  }

  // Battery specifications
  if ($device['battery_capacity'] || $device['battery_type']) {
    $battery_details = '';
    if ($device['battery_type']) {
      $battery_details .= '<strong>Type</strong> ' . $device['battery_type'];
      if ($device['battery_capacity']) $battery_details .= ' ' . $device['battery_capacity'] . ' mAh';
    } else if ($device['battery_capacity']) {
      $battery_details .= '<strong>Capacity</strong> ' . $device['battery_capacity'] . ' mAh';
    }

    if ($device['battery_removable'] !== null) {
      $battery_details .= '<br><strong>Removable</strong> ' . ($device['battery_removable'] ? 'Yes' : 'No');
    }

    // Charging information
    $charging = [];
    if ($device['charging_wired']) $charging[] = 'Wired: ' . $device['charging_wired'] . 'W';
    if ($device['charging_wireless']) $charging[] = 'Wireless: ' . $device['charging_wireless'] . 'W';
    if ($device['charging_reverse']) $charging[] = 'Reverse: ' . $device['charging_reverse'] . 'W';

    if (!empty($charging)) {
      $battery_details .= '<br><strong>Charging</strong> ' . implode(', ', $charging);
    }

    $specs['BATTERY'] = $battery_details;
  }

  // Colors
  if ($device['colors'] && is_array($device['colors'])) {
    $specs['COLORS'] = '<strong>Available</strong> ' . implode(', ', $device['colors']);
  } elseif ($device['colors'] && is_string($device['colors'])) {
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
  if ($device['release_date']) {
    $release_date = date('F j, Y', strtotime($device['release_date']));
    $highlights['release'] = "📅 Released " . $release_date;
  } elseif ($device['year']) {
    $highlights['release'] = "📅 Released " . $device['year'];
  }

  // Weight and dimensions highlight
  $weight_dims = [];
  if ($device['weight']) {
    $weight_dims[] = $device['weight'] . 'g';
  }
  if ($device['dimensions_thickness']) {
    $weight_dims[] = $device['dimensions_thickness'] . 'mm thickness';
  }
  if (!empty($weight_dims)) {
    $highlights['weight_dims'] = "⚖️ " . implode(', ', $weight_dims);
  }

  // OS highlight
  if ($device['os']) {
    $highlights['os'] = "🆔 " . $device['os'];
  }

  // Storage highlight
  $storage_parts = [];
  if ($device['storage_internal']) {
    $storage_parts[] = $device['storage_internal'] . ' storage';
  }
  if ($device['card_slot'] === false) {
    $storage_parts[] = 'no card slot';
  } elseif ($device['card_slot'] === true) {
    $storage_parts[] = 'expandable';
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
  $display_title = $device['display_size'] ? $device['display_size'] . '"' : 'N/A';
  $display_subtitle = $device['display_resolution'] ?? 'Unknown';
  $stats['display'] = [
    'icon' => 'imges/vrer.png',
    'title' => $display_title,
    'subtitle' => $display_subtitle
  ];

  // Camera stats
  $camera_title = 'N/A';
  $camera_subtitle = 'N/A';
  if ($device['main_camera_resolution']) {
    // Extract MP from resolution
    if (preg_match('/(\d+)\s*MP/', $device['main_camera_resolution'], $matches)) {
      $camera_title = $matches[1] . 'MP';
    }
  }
  if ($device['main_camera_video']) {
    $camera_subtitle = $device['main_camera_video'];
  } elseif (strpos($device['main_camera_resolution'], '4K') !== false) {
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
  if ($device['ram_internal']) {
    $perf_title = $device['ram_internal'];
  }
  if ($device['chipset_name']) {
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
  $battery_title = $device['battery_capacity'] ? $device['battery_capacity'] . 'mAh' : 'N/A';
  $battery_subtitle = 'N/A';
  if ($device['charging_wired']) {
    $battery_subtitle = $device['charging_wired'] . 'W';
  } elseif ($device['charging_wireless']) {
    $battery_subtitle = $device['charging_wireless'] . 'W wireless';
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

      <a href="rewies.php">Reviews</a>
      <a href="videos.php">Videos</a>
      <a href="featured.php">Featured</a>
      <a href="phonefinder.php">Phone Finder</a>
      <a href="compare.php">Compare</a>
      <a href="#">Coverage</a>
      <a href="contact">Contact Us</a>
      <a href="#">Merch</a>
      <a href="#">Tip Us</a>
      <a href="#">Privacy</a>
    </div>
    <div class="brand-grid">
      <?php
      $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
      foreach ($brandChunks as $brandRow):
        foreach ($brandRow as $brand): ?>
          <a href="#" class="brand-cell" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
      <?php endforeach;
      endforeach; ?>
      <a href="brands.php">[...]</a>
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
        <a href="index.php" class="nav-link">Home</a>
        <a href="compare.php" class="nav-link">Compare</a>
        <a href="videos.php" class="nav-link">Videos</a>
        <a href="rewies.php" class="nav-link ">Reviews</a>

        <a href="featured.php" class="nav-link d-lg-block d-none">Featured</a>
        <a href="phonefinder.php" class="nav-link d-lg-block d-none">Phone Finder</a>
        <a href="contact.php" class="nav-link d-lg-block d-none">Contact</a>
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
      font-family: 'oswald';
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
      font-family: 'oswald';
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
      font-family: 'oswald';
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
      font-family: 'oswald';
      font-weight: 100;
      font-size: 14px;
      color: black;
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
      font: 300 28px / 47px Google-Oswald, Arial, sans-serif;
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
      font-family: Arial, sans-serif;
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
  </style>


  <div class="d-lg-none d-block">
    <div class="card" role="region" aria-label="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> Phone Info">

      <div class="article-info">
        <div class="bg-blur">
          <p class="vr-hide"
            style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
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
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
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
          <div class="d-block justify-content-end">
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">Review (17)
              </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">OPINION </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">PICTURES </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2">PRICES</h5>
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
        <div class="card" role="region" aria-label="<?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?> Phone Info">

          <div class="article-info">
            <div class="bg-blur">
              <p class="vr-hide"
                style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
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
                  <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">
                  </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2"> </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2"> </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2"></h5>
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
                <button class="px-3 py-1 brand-cell" style="cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
          <?php
              endforeach;
            endforeach;
          endif;
          ?>
        </div>
        <button class="solid w-50 py-2">
          <i class="fa-solid fa-bars fa-sm mx-2"></i>
          All Brands</button>
        <button class="solid py-2" style="    width: 177px;">
          <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
          RUMORS MILL</button>
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
              class="pad">COMPARE</button>
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
        </div>
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

          <a href="#">Reviews</a>
          <a href="#">Compare</a>
          <a href="#">Coverage</a>
          <a href="#">Glossary</a>
          <a href="#">FAQ</a>
          <a href="#"> <i class="fa-solid fa-wifi fa-sm"></i> RSS</a>
          <a href="#"> <i class="fa-brands fa-youtube fa-sm"></i> YouTube</a>
          <a href="#"> <i class="fa-brands fa-instagram fa-sm"></i> Instagram</a>
          <a href="#"> <i class="fa-brands fa-tiktok fa-sm"></i>TikTok</a>
          <a href="#"> <i class="fa-brands fa-facebook-f fa-sm"></i> Facebook</a>
          <a href="#"> <i class="fa-brands fa-twitter fa-sm"></i>Twitter</a>
          <a href="#">© 2000-2025 GSMArena.com</a>
          <a href="#">Mobile version</a>
          <a href="#">Android app</a>
          <a href="#">Tools</a>
          <a href="contact.php">Contact us</a>
          <a href="#">Merch store</a>
          <a href="#">Privacy</a>
          <a href="#">Terms of use</a>
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

    // Handle brand cell clicks
    document.querySelectorAll('.brand-cell').forEach(function(cell) {
      cell.addEventListener('click', function() {
        const brandId = this.getAttribute('data-brand-id');
        if (brandId) {
          // Redirect to brands page with specific brand filter
          window.location.href = `brands.php?brand=${brandId}`;
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
          window.location.href = `compare_phones.php?phone1=${device1Id}&phone2=${device2Id}`;
        }
      });
    });
  });

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
</script>

</body>

</html>