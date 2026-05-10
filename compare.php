<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/phone_data.php';
require_once __DIR__ . '/database_functions.php';

function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath))
    return '';
  if (filter_var($imagePath, FILTER_VALIDATE_URL))
    return $imagePath;
  if (strpos($imagePath, '/') === 0)
    return $imagePath;
  return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

// ── Auth (shared with navbar include) ──
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';
if (!isset($_SESSION['notif_seen']))
  $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// ── Weekly posts for notification bell ──
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) {
  $weekly_posts = [];
}

// ── Mobile brands for hamburger menu ──
$mb_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 12");
$mb_stmt->execute();
$mobile_brands = $mb_stmt->fetchAll();

// ── All Brands for Hero Widget ──
$brands_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 36");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// ── All phones for comparison ──
$phones = getAllPhones();

// ── Data for bottom sections ──
$latestDevices = array_slice(array_reverse($phones), 0, 9);

try {
  $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
  $topComparisons = [];
}

try {
  $posts_stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
        FROM posts p 
        WHERE p.status ILIKE 'published' 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
  $posts_stmt->execute();
  $posts = $posts_stmt->fetchAll();
} catch (Exception $e) {
  $posts = [];
}


// ── Parse selected phone slugs ──
if (isset($_GET['slugs'])) {
  $slugParts = explode('-vs-', $_GET['slugs']);
  $phone1_slug = isset($slugParts[0]) ? trim($slugParts[0]) : '';
  $phone2_slug = isset($slugParts[1]) ? trim($slugParts[1]) : '';
  $phone3_slug = isset($slugParts[2]) ? trim($slugParts[2]) : '';
} else {
  $phone1_slug = $_GET['phone1'] ?? '';
  $phone2_slug = $_GET['phone2'] ?? '';
  $phone3_slug = $_GET['phone3'] ?? '';
}

// ── Pre-select from device page referral ──
if (isset($_GET['device1']) && ($phone1_slug === '' || $phone1_slug === null)) {
  $device_name = urldecode($_GET['device1']);
  $device_brand = isset($_GET['brand1']) ? urldecode($_GET['brand1']) : '';
  foreach ($phones as $phone) {
    $nm = isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name));
    $bm = empty($device_brand) || (isset($phone['brand']) && strtolower(trim($phone['brand'])) === strtolower(trim($device_brand)));
    if ($nm && $bm) {
      $phone1_slug = $phone['slug'] ?? $phone['id'];
      break;
    }
  }
}

function findPhoneBySlug($phones, $slug)
{
  if ($slug === '' || $slug === null || $slug === 'undefined')
    return null;
  foreach ($phones as $p) {
    if (isset($p['slug']) && $p['slug'] === $slug)
      return $p;
  }
  return null;
}

$phone1 = findPhoneBySlug($phones, $phone1_slug);
$phone2 = findPhoneBySlug($phones, $phone2_slug);
$phone3 = findPhoneBySlug($phones, $phone3_slug);

if ($phone1 && $phone2) {
  try {
    trackDeviceComparison($phone1['id'], $phone2['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
  } catch (Exception $e) {
    error_log('compare tracking: ' . $e->getMessage());
  }
}

$has_selection = ($phone1 !== null || $phone2 !== null || $phone3 !== null);

// ── Helper functions ──
function getPhoneImage($phone)
{
  if (isset($phone['image']) && !empty($phone['image'])) {
    $img = $phone['image'];
    if (strpos($img, '/') !== 0 && !filter_var($img, FILTER_VALIDATE_URL))
      $img = '/' . ltrim($img, '/');
    return htmlspecialchars($img);
  }
  return '/imges/icon-256.png';
}

function getPhoneName($phone)
{
  if (!$phone)
    return 'Select a device';
  $name = '';
  if (!empty($phone['brand_name']))
    $name = $phone['brand_name'] . ' ';
  if (!empty($phone['name']))
    $name .= $phone['name'];
  return !empty($name) ? htmlspecialchars($name) : 'Unknown Device';
}

function getDeviceImages($device)
{
  $images = [];
  if (!empty($device['image']))
    $images[] = $device['image'];
  for ($i = 1; $i <= 10; $i++) {
    $key = 'image_' . $i;
    if (!empty($device[$key]) && !in_array($device[$key], $images))
      $images[] = $device[$key];
  }
  if (!empty($device['images'])) {
    $arr = is_array($device['images']) ? $device['images'] : [];
    if (is_string($device['images'])) {
      $t = trim($device['images']);
      if ($t && $t !== '{}' && $t[0] === '{') {
        foreach (explode(',', substr($t, 1, -1)) as $p) {
          $c = trim($p, " \"'");
          if ($c && $c !== 'NULL')
            $arr[] = $c;
        }
      }
    }
    foreach ($arr as $img) {
      if (!empty($img) && !in_array($img, $images))
        $images[] = $img;
    }
  }
  $valid = [];
  foreach ($images as $img) {
    if (!empty($img)) {
      $img = str_replace('\\', '/', $img);
      if (strpos($img, '/') !== 0 && !filter_var($img, FILTER_VALIDATE_URL))
        $img = '/' . ltrim($img, '/');
      if (!in_array($img, $valid))
        $valid[] = $img;
    }
  }
  return $valid;
}

$phone1Images = $phone1 ? getDeviceImages($phone1) : [];
$phone2Images = $phone2 ? getDeviceImages($phone2) : [];
$phone3Images = $phone3 ? getDeviceImages($phone3) : [];

// ── Spec formatters (ported from original compare.php) ──
function convertNumberToWord($num)
{
  $w = ['', 'single', 'dual', 'triple', 'quad', 'penta', 'hexa', 'hepta', 'octa'];
  return isset($w[$num]) ? $w[$num] : $num;
}
function displayNetworkCapabilities($phone)
{
  $n = [];
  if (!empty($phone['network_2g']))
    $n[] = 'GSM';
  if (!empty($phone['network_3g']))
    $n[] = 'HSPA';
  if (!empty($phone['network_4g']))
    $n[] = 'LTE';
  if (!empty($phone['network_5g']))
    $n[] = '5G';
  return $n ? implode(' / ', $n) : '<span class="cp-na">—</span>';
}
function formatDimensions($phone)
{
  if (!empty($phone['dimensions']))
    return $phone['dimensions'];
  if (!empty($phone['height']) && !empty($phone['width']) && !empty($phone['thickness']))
    return $phone['height'] . ' × ' . $phone['width'] . ' × ' . $phone['thickness'] . ' mm';
  return '<span class="cp-na">—</span>';
}
function formatDisplay($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['display_type']))
    $p[] = $phone['display_type'];
  if (!empty($phone['display_technology']))
    $p[] = $phone['display_technology'];
  if (!empty($phone['refresh_rate']))
    $p[] = $phone['refresh_rate'] . 'Hz';
  if (!empty($phone['hdr']))
    $p[] = 'HDR';
  if (!empty($phone['display_size']))
    $p[] = $phone['display_size'] . '"';
  if (!empty($phone['display_resolution']))
    $p[] = $phone['display_resolution'];
  return $p ? implode(', ', $p) : '<span class="cp-na">—</span>';
}
function formatMemory($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['ram']))
    $p[] = $phone['ram'] . ' RAM';
  if (!empty($phone['storage']))
    $p[] = $phone['storage'];
  if (!empty($phone['card_slot']))
    $p[] = 'Slot: ' . $phone['card_slot'];
  return $p ? implode(', ', $p) : '<span class="cp-na">—</span>';
}
function formatMainCamera($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['main_camera_count']) && $phone['main_camera_count'] > 1)
    $p[] = ucfirst(convertNumberToWord($phone['main_camera_count']));
  if (!empty($phone['main_camera_resolution']))
    $p[] = $phone['main_camera_resolution'] . 'MP';
  $f = [];
  if (!empty($phone['main_camera_ois']))
    $f[] = 'OIS';
  if (!empty($phone['main_camera_telephoto']))
    $f[] = 'Telephoto';
  if (!empty($phone['main_camera_ultrawide']))
    $f[] = 'Ultrawide';
  if ($f)
    $p[] = implode(', ', $f);
  if (!empty($phone['main_camera_video']))
    $p[] = $phone['main_camera_video'];
  return $p ? implode('<br>', $p) : '<span class="cp-na">—</span>';
}
function formatSelfieCamera($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['selfie_camera_resolution']))
    $p[] = $phone['selfie_camera_resolution'] . 'MP';
  $f = [];
  if (!empty($phone['selfie_camera_ois']))
    $f[] = 'OIS';
  if (!empty($phone['popup_camera']))
    $f[] = 'Popup';
  if (!empty($phone['under_display_camera']))
    $f[] = 'Under Display';
  if ($f)
    $p[] = implode(', ', $f);
  return $p ? implode('<br>', $p) : '<span class="cp-na">—</span>';
}
function formatBattery($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['battery_capacity']))
    $p[] = $phone['battery_capacity'] . ' mAh';
  if (!empty($phone['wired_charging']))
    $p[] = $phone['wired_charging'] . 'W wired';
  if (!empty($phone['wireless_charging']))
    $p[] = $phone['wireless_charging'] . 'W wireless';
  if (isset($phone['battery_removable']))
    $p[] = $phone['battery_removable'] ? 'Removable' : 'Non-removable';
  return $p ? implode(', ', $p) : '<span class="cp-na">—</span>';
}
function formatOS($phone)
{
  return !empty($phone['os']) ? htmlspecialchars($phone['os']) : '<span class="cp-na">—</span>';
}
function formatChipset($phone)
{
  return !empty($phone['chipset_name']) ? htmlspecialchars($phone['chipset_name']) : '<span class="cp-na">—</span>';
}
function formatComms($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $p = [];
  if (!empty($phone['wifi']))
    $p[] = 'WiFi: ' . (is_array($phone['wifi']) ? implode(',', $phone['wifi']) : $phone['wifi']);
  if (!empty($phone['bluetooth']))
    $p[] = 'BT: ' . (is_array($phone['bluetooth']) ? implode(',', $phone['bluetooth']) : $phone['bluetooth']);
  if (isset($phone['nfc']))
    $p[] = 'NFC: ' . ($phone['nfc'] ? 'Yes' : 'No');
  if (!empty($phone['usb']))
    $p[] = 'USB: ' . $phone['usb'];
  return $p ? implode(', ', $p) : '<span class="cp-na">—</span>';
}
function formatSensors($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  $s = [];
  if (!empty($phone['fingerprint']))
    $s[] = 'Fingerprint';
  if (!empty($phone['accelerometer']))
    $s[] = 'Accelerometer';
  if (!empty($phone['gyro']))
    $s[] = 'Gyro';
  if (!empty($phone['compass']))
    $s[] = 'Compass';
  if (!empty($phone['proximity']))
    $s[] = 'Proximity';
  if (!empty($phone['barometer']))
    $s[] = 'Barometer';
  return $s ? implode(', ', $s) : '<span class="cp-na">—</span>';
}
function formatColors($phone)
{
  if (!$phone || empty($phone['colors']))
    return '<span class="cp-na">—</span>';
  if (is_array($phone['colors']))
    return implode(', ', $phone['colors']);
  $c = str_replace(['{', '}'], '', $phone['colors']);
  return implode(', ', array_map('trim', explode(',', $c)));
}
function formatPrice($phone)
{
  if (!$phone)
    return '<span class="cp-na">—</span>';
  if (!empty($phone['price']) && is_numeric($phone['price']))
    return '$' . number_format((float) $phone['price'], 2);
  return '<span class="cp-na">—</span>';
}

// ── Premium Phone Card Renderer ──
function renderPhoneCard($phone, $slot, $base)
{
  $img = getPhoneImage($phone);
  $name = htmlspecialchars($phone['name'] ?? '');
  $brand = htmlspecialchars(strtoupper($phone['brand_name'] ?? ''));
  $slug = urlencode($phone['slug'] ?? $phone['id']);

  // Price Only
  if (!empty($phone['price']) && is_numeric($phone['price']))
    $specs[] = ['fa-tag', 'Price', '$' . number_format((float) $phone['price'], 2)];

  $html = '<div class="cp-filled-card">';
  $html .= '<div class="cp-img-stage">';
  $html .= '<div class="cp-phone-halo"></div>';
  $html .= '<div class="cp-phone-halo cp-phone-halo-2"></div>';
  $html .= '<img src="' . $img . '" alt="' . $brand . ' ' . $name . '" class="cp-img-float" onclick="showGallery(' . $slot . ')" />';
  $html .= '<div class="cp-scanline"></div>';
  $html .= '<div class="cp-gallery-badge" onclick="showGallery(' . $slot . ')"><i class="fa fa-images"></i> Gallery</div>';
  $html .= '</div>';
  $html .= '<div class="cp-device-identity">';
  $html .= '<div class="cp-device-brand">' . $brand . '</div>';
  $html .= '<div class="cp-device-name">' . $name . '</div>';
  $html .= '</div>';
  $html .= '<div class="cp-spec-dock">';
  foreach ($specs as $sp) {
    $html .= '<div class="cp-spec-block">';
    $html .= '<div class="cp-spec-icon"><i class="fa ' . $sp[0] . '"></i></div>';
    $html .= '<div class="cp-spec-content">';
    $html .= '<div class="cp-spec-label">' . $sp[1] . '</div>';
    $html .= '<div class="cp-spec-val">' . htmlspecialchars($sp[2]) . '</div>';
    $html .= '</div></div>';
  }
  $html .= '</div>';
  $html .= '<div class="cp-slot-actions">';
  $html .= '<a href="' . $base . 'device/' . $slug . '" class="cp-action-btn ghost">REVIEW</a>';
  $html .= '<a href="' . $base . 'device/' . $slug . '" class="cp-action-btn ghost">SPECIFICATIONS</a>';
  $html .= '<a href="' . $base . 'device/' . $slug . '#comments" class="cp-action-btn ghost">READ OPINIONS</a>';
  $html .= '<a href="' . $base . 'device/' . $slug . '/images" class="cp-action-btn ghost">PICTURES</a>';
  $html .= '<button class="cp-action-btn" onclick="clearSlot(' . $slot . ')">REMOVE</button>';
  $html .= '</div>';
  $html .= '</div>';
  return $html;
}

// ── Quick spec sections for summary rows ──
$specSections = [
  ['icon' => 'fa-tower-broadcast', 'label' => 'Network', 'fn' => fn($p) => displayNetworkCapabilities($p)],
  ['icon' => 'fa-display', 'label' => 'Display', 'fn' => fn($p) => formatDisplay($p)],
  ['icon' => 'fa-microchip', 'label' => 'Chipset', 'fn' => fn($p) => formatChipset($p)],
  ['icon' => 'fa-circle-info', 'label' => 'OS', 'fn' => fn($p) => formatOS($p)],
  ['icon' => 'fa-database', 'label' => 'Memory', 'fn' => fn($p) => formatMemory($p)],
  ['icon' => 'fa-ruler-combined', 'label' => 'Dimensions', 'fn' => fn($p) => formatDimensions($p)],
  ['icon' => 'fa-weight-hanging', 'label' => 'Weight', 'fn' => fn($p) => !empty($p['weight']) ? $p['weight'] . ' g' : '<span class="cp-na">—</span>'],
  ['icon' => 'fa-camera', 'label' => 'Main Camera', 'fn' => fn($p) => formatMainCamera($p)],
  ['icon' => 'fa-video', 'label' => 'Selfie Camera', 'fn' => fn($p) => formatSelfieCamera($p)],
  ['icon' => 'fa-battery-full', 'label' => 'Battery', 'fn' => fn($p) => formatBattery($p)],
  ['icon' => 'fa-wifi', 'label' => 'Connectivity', 'fn' => fn($p) => formatComms($p)],
  ['icon' => 'fa-satellite-dish', 'label' => 'Sensors', 'fn' => fn($p) => formatSensors($p)],
  ['icon' => 'fa-palette', 'label' => 'Colors', 'fn' => fn($p) => formatColors($p)],
  ['icon' => 'fa-tag', 'label' => 'Price', 'fn' => fn($p) => formatPrice($p)],
];

// ── JSON spec parser (for full structured spec table) ──
function parseJsonSectionStructured($jsonValue)
{
  if (!isset($jsonValue) || $jsonValue === '' || $jsonValue === null)
    return [];
  $d = json_decode($jsonValue, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($d))
    return [];
  $rows = [];
  foreach ($d as $row) {
    $field = isset($row['field']) ? trim((string) $row['field']) : '';
    $desc = isset($row['description']) ? trim((string) $row['description']) : '';
    if ($field === '' && $desc === '')
      continue;
    if ($field !== '') {
      $rows[] = ['field' => $field, 'description' => $desc];
    } else if (!empty($rows)) {
      $rows[count($rows) - 1]['description'] .= "\n" . $desc;
    }
  }
  return $rows;
}
function formatDeviceSpecsStructured($device)
{
  $labels = ['NETWORK', 'LAUNCH', 'BODY', 'DISPLAY', 'HARDWARE', 'MEMORY', 'MAIN CAMERA', 'SELFIE CAMERA', 'MULTIMEDIA', 'CONNECTIVITY', 'FEATURES', 'BATTERY', 'GENERAL INFO'];
  $keys = ['network', 'launch', 'body', 'display', 'hardware', 'memory', 'main_camera', 'selfie_camera', 'multimedia', 'connectivity', 'features', 'battery', 'general_info'];
  $specs = [];
  foreach ($labels as $i => $label) {
    $rows = parseJsonSectionStructured($device[$keys[$i]] ?? null);
    if (!empty($rows))
      $specs[$label] = $rows;
  }
  return $specs;
}

$specs1 = $phone1 ? formatDeviceSpecsStructured($phone1) : [];
$specs2 = $phone2 ? formatDeviceSpecsStructured($phone2) : [];
$specs3 = $phone3 ? formatDeviceSpecsStructured($phone3) : [];

// ── Build field-keyed lookup for fixed-schema table ──
// Returns [SECTION][field_lowercase] => description
function buildSpecLookup($specs)
{
  $lookup = [];
  foreach ($specs as $section => $rows) {
    foreach ($rows as $row) {
      $key = strtolower(trim($row['field'] ?? ''));
      if ($key !== '') {
        $lookup[$section][$key] = $row['description'] ?? '';
      }
    }
  }
  return $lookup;
}

// ── Dynamic page title ──
$pageTitle = 'Compare Smartphones — DevicesArena';
$selectedPhones = array_filter([$phone1, $phone2, $phone3]);
if (!empty($selectedPhones)) {
  $names = array_map('getPhoneName', array_values($selectedPhones));
  $pageTitle = implode(' vs ', $names) . ' — DevicesArena';
}

$da_active_nav = 'compare';
?>
<!DOCTYPE html>
<html lang="en" id="da-html">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical"
    href="<?php echo $canonicalBase; ?>/compare<?php echo isset($_GET['slugs']) ? '/' . htmlspecialchars($_GET['slugs']) : ''; ?>" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <meta name="description"
    content="Compare smartphones side by side on DevicesArena. Full specs, display, camera, battery and connectivity." />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>" />
  <meta property="og:description" content="Compare smartphones side by side. Full specs, camera, battery and more." />
  <meta property="og:type" content="website" />

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446"
    crossorigin="anonymous"></script>

  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>window.dataLayer = window.dataLayer || []; function gtag() { dataLayer.push(arguments); } gtag('js', new Date()); gtag('config', 'G-2LDCSSMXJT');</script>

  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>style.css">

  <!-- Theme init (prevents FOUC) -->
  <script>
    (function () {
      var t = localStorage.getItem('da-theme');
      if (t === 'light' || (!t && window.matchMedia('(prefers-color-scheme: light)').matches))
        document.documentElement.setAttribute('data-theme', 'light');
    })();
  </script>

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
    crossorigin="anonymous"></script>
</head>

<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <!-- ══════════════════════════════════════════
     COMPARE PAGE CONTENT
══════════════════════════════════════════ -->
  <div class="cp-page">

    <!-- Hero Banner -->
    <div class="cp-hero">

      <!-- Background Image Implementation based on original layout -->
      <div class="cp-hero-bg-container">
        <img class="cp-hero-bg-img" src="<?php echo $base; ?>hero-images/compare-hero.png"
          alt="compare smartphones background">
      </div>

      <div class="cp-hero-inner">
        <div class="cp-hero-left">
          <div class="cp-hero-label"><span>DevicesArena</span></div>
          <h1 class="cp-hero-title">Compare Smartphones</h1>
          <p class="cp-hero-sub">Select up to 3 devices and see their specs side by side. Highlight differences
            instantly.</p>
        </div>

        <!-- Right: Brand panel (Classic Widget) -->
        <div class="cp-hero-right">
          <div class="da-section-label"><span>Brands</span></div>
          <div class="da-classic-brand-widget">
            <!-- Top header -->
            <div class="da-cbw-header">
              <a href="<?php echo $base; ?>phonefinder">
                <i class="fa fa-mobile-screen"></i> PHONE FINDER
              </a>
            </div>

            <!-- Brand Grid -->
            <div class="da-cbw-grid">
              <?php foreach (array_slice($brands, 0, 32) as $index => $brand):
                $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
                ?>
                <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-cbw-item"
                  title="<?php echo htmlspecialchars($brand['name']); ?>">
                  <?php echo strtoupper(htmlspecialchars($brand['name'])); ?>
                </a>
              <?php endforeach; ?>
            </div>

            <!-- Bottom buttons -->
            <div class="da-cbw-footer">
              <a href="<?php echo $base; ?>brands" class="da-cbw-btn left">
                <i class="fa fa-bars"></i> ALL BRANDS
              </a>
              <a href="<?php echo $base; ?>rumored" class="da-cbw-btn right">
                <i class="fa fa-bullhorn"></i> RUMORS MILL
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ══ DEVICE SELECTOR STAGE ══ -->
    <div class="cp-stage">
      <div class="cp-stage-inner">

        <!-- Slot 1 -->
        <div class="cp-slot" id="cp-slot-1" data-slot="1">
          <div class="cp-slot-search-wrap">
            <div class="cp-slot-search-label"><i class="fa fa-mobile-screen"></i> Device 1</div>
            <div class="cp-search-box-wrap">
              <i class="fa fa-search cp-search-icon"></i>
              <input type="text" class="cp-search-input" id="cp-search-1" placeholder="Search device..." data-slot="1"
                autocomplete="off">
              <div class="cp-search-results" id="cp-results-1"></div>
            </div>
          </div>
          <?php if ($phone1): ?>
            <?php echo renderPhoneCard($phone1, 1, $base); ?>
          <?php else: ?>
            <div class="cp-slot-preview cp-slot-empty">
              <div class="cp-slot-empty-icon"><i class="fa fa-mobile-screen"></i></div>
              <div class="cp-slot-empty-text">Search &amp; select a device</div>
            </div>
          <?php endif; ?>
        </div>

        <!-- VS Badge 1→2 -->
        <div class="cp-vs-badge">VS</div>

        <!-- Slot 2 -->
        <div class="cp-slot" id="cp-slot-2" data-slot="2">
          <div class="cp-slot-search-wrap">
            <div class="cp-slot-search-label"><i class="fa fa-mobile-screen"></i> Device 2</div>
            <div class="cp-search-box-wrap">
              <i class="fa fa-search cp-search-icon"></i>
              <input type="text" class="cp-search-input" id="cp-search-2" placeholder="Search device..." data-slot="2"
                autocomplete="off">
              <div class="cp-search-results" id="cp-results-2"></div>
            </div>
          </div>
          <?php if ($phone2): ?>
            <?php echo renderPhoneCard($phone2, 2, $base); ?>
          <?php else: ?>
            <div class="cp-slot-preview cp-slot-empty">
              <div class="cp-slot-empty-icon"><i class="fa fa-mobile-screen"></i></div>
              <div class="cp-slot-empty-text">Search &amp; select a device</div>
            </div>
          <?php endif; ?>
        </div>

        <!-- VS Badge 2→3 -->
        <div class="cp-vs-badge">VS</div>

        <!-- Slot 3 -->
        <div class="cp-slot" id="cp-slot-3" data-slot="3">
          <div class="cp-slot-search-wrap">
            <div class="cp-slot-search-label"><i class="fa fa-mobile-screen"></i> Device 3 <span
                class="cp-optional-badge">Optional</span></div>
            <div class="cp-search-box-wrap">
              <i class="fa fa-search cp-search-icon"></i>
              <input type="text" class="cp-search-input" id="cp-search-3" placeholder="Add a 3rd device..."
                data-slot="3" autocomplete="off">
              <div class="cp-search-results" id="cp-results-3"></div>
            </div>
          </div>
          <?php if ($phone3): ?>
            <?php echo renderPhoneCard($phone3, 3, $base); ?>
          <?php else: ?>
            <div class="cp-slot-preview cp-slot-empty">
              <div class="cp-slot-empty-icon"><i class="fa fa-mobile-screen"></i></div>
              <div class="cp-slot-empty-text">Optional third device</div>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div><!-- /cp-stage -->

    <?php if ($has_selection): ?>
      <!-- ══ SPEC TABLE ══ -->
      <div class="cp-table-wrap">
        <div class="cp-table-inner">

          <!-- Toggle bar -->
          <div class="cp-toggle-bar">
            <div class="cp-toggle-label"><i class="fa fa-table-list"></i> Specifications</div>
            <div class="cp-toggle-btns" role="group">
              <button class="cp-toggle-btn active" id="cp-btn-all" onclick="setSpecView('all')"><i class="fa fa-list"></i>
                All Specs</button>
              <button class="cp-toggle-btn" id="cp-btn-diff" onclick="setSpecView('diff')"><i
                  class="fa fa-code-compare"></i> Differences Only</button>
            </div>
          </div>

          <!-- Fixed-schema comparison table -->
          <div class="cpt-scroll">
            <?php
            // Fixed spec schema sourced from add_device.php template
            $specSchema = [
              'NETWORK' => ['Technology'],
              'LAUNCH' => ['Announced', 'Availability'],
              'BODY' => ['Dimensions', 'Weight', 'Materials', 'Connectivity Slot'],
              'DISPLAY' => ['Type', 'Size', 'Resolution', 'Protection'],
              'HARDWARE' => ['OS', 'System Chip', 'Processor', 'GPU'],
              'MEMORY' => ['Expansion Slot', 'Storage'],
              'MAIN CAMERA' => ['Dual', 'Features', 'Video Recording'],
              'SELFIE CAMERA' => ['Single', 'Video Recording'],
              'MULTIMEDIA' => ['Audio Output', '3.5mm Jack'],
              'CONNECTIVITY' => ['WLAN', 'Bluetooth', 'Location', 'Proximity', 'Infrared Port', 'Radio', 'USB'],
              'FEATURES' => ['Sensors'],
              'BATTERY' => ['Type', 'Charging', 'Reverse Wired'],
              'GENERAL INFO' => ['Colors', 'Versions', 'Price'],
            ];
            $sectionDisplayNames = [
              'NETWORK' => 'Network',
              'LAUNCH' => 'Launch',
              'BODY' => 'Body',
              'DISPLAY' => 'Display',
              'HARDWARE' => 'Hardware',
              'MEMORY' => 'System Memory',
              'MAIN CAMERA' => 'Main Camera',
              'SELFIE CAMERA' => 'Selfie Camera',
              'MULTIMEDIA' => 'Multimedia',
              'CONNECTIVITY' => 'Connectivity',
              'FEATURES' => 'Features',
              'BATTERY' => 'Battery',
              'GENERAL INFO' => 'General Info',
            ];
            // Build field lookups
            $lk1 = buildSpecLookup($specs1);
            $lk2 = buildSpecLookup($specs2);
            $lk3 = buildSpecLookup($specs3);
            // Column count
            $devCols = ($phone1 ? 1 : 0) + ($phone2 ? 1 : 0) + ($phone3 ? 1 : 0);
            $totalCols = $devCols + 1;
            ?>
            <table class="cpt-table" id="cp-spec-table">
              <thead class="cpt-thead">
                <tr>
                  <th class="cpt-label-head">Specifications</th>
                  <?php if ($phone1): ?>
                    <th class="cpt-dev-head">
                      <img src="<?php echo getPhoneImage($phone1); ?>" alt="<?php echo getPhoneName($phone1); ?>">
                      <span><?php echo getPhoneName($phone1); ?></span>
                    </th>
                  <?php endif; ?>
                  <?php if ($phone2): ?>
                    <th class="cpt-dev-head">
                      <img src="<?php echo getPhoneImage($phone2); ?>" alt="<?php echo getPhoneName($phone2); ?>">
                      <span><?php echo getPhoneName($phone2); ?></span>
                    </th>
                  <?php endif; ?>
                  <?php if ($phone3): ?>
                    <th class="cpt-dev-head">
                      <img src="<?php echo getPhoneImage($phone3); ?>" alt="<?php echo getPhoneName($phone3); ?>">
                      <span><?php echo getPhoneName($phone3); ?></span>
                    </th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($specSchema as $sectionKey => $subtitles):
                  $displayName = $sectionDisplayNames[$sectionKey] ?? ucfirst(strtolower($sectionKey));
                  ?>
                  <tr class="cpt-section-row">
                    <td colspan="<?php echo $totalCols; ?>" class="cpt-section-cell">
                      <?php echo htmlspecialchars($displayName); ?>
                    </td>
                  </tr>
                  <?php foreach ($subtitles as $subtitle):
                    $fk = strtolower($subtitle);
                    $v1 = ($phone1 !== null) ? ($lk1[$sectionKey][$fk] ?? '') : null;
                    $v2 = ($phone2 !== null) ? ($lk2[$sectionKey][$fk] ?? '') : null;
                    $v3 = ($phone3 !== null) ? ($lk3[$sectionKey][$fk] ?? '') : null;
                    // Identical check across all selected slots
                    $activeVals = array_filter(
                      [$v1, $v2, $v3],
                      fn($v) => $v !== null
                    );
                    $identical = (count($activeVals) > 1) && (count(array_unique(array_values($activeVals))) === 1);
                    ?>
                    <tr class="cpt-data-row<?php echo $identical ? ' cpt-identical' : ''; ?>"
                      data-identical="<?php echo $identical ? '1' : '0'; ?>">
                      <td class="cpt-label-cell"><?php echo htmlspecialchars($subtitle); ?></td>
                      <?php if ($phone1 !== null): ?>
                        <td class="cpt-val-cell"><?php echo ($v1 !== '') ? nl2br(htmlspecialchars($v1)) : ''; ?></td>
                      <?php endif; ?>
                      <?php if ($phone2 !== null): ?>
                        <td class="cpt-val-cell"><?php echo ($v2 !== '') ? nl2br(htmlspecialchars($v2)) : ''; ?></td>
                      <?php endif; ?>
                      <?php if ($phone3 !== null): ?>
                        <td class="cpt-val-cell"><?php echo ($v3 !== '') ? nl2br(htmlspecialchars($v3)) : ''; ?></td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div><!-- /cpt-scroll -->

        </div><!-- /cp-table-inner -->
      </div><!-- /cp-table-wrap -->
    <?php else: ?>
      <!-- Empty state -->
      <div class="cp-empty-state">
        <div class="cp-empty-icon"><i class="fa fa-mobile-screen"></i></div>
        <h2>Start Your Comparison</h2>
        <p>Search and select devices above. Compare up to 3 phones side by side.</p>
      </div>
    <?php endif; ?>
    <!-- ── IN STORES NOW ── -->
    <?php include('includes/bottom-area/in-stores-now.php'); ?>

    <!-- ── TRENDING COMPARISONS ── -->
    <?php include('includes/bottom-area/trending-comparisons.php'); ?>

    <!-- ── FEATURED POSTS TICKER ── -->
    <?php include('includes/bottom-area/featured-posts.php'); ?>

    <!-- ── INFINITE BRAND MARQUEE ── -->
    <?php include('includes/bottom-area/brand-marquee.php'); ?>
  </div><!-- /cp-page -->

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo $base; ?>script.js"></script>
  <script>
    window.baseURL = '<?php echo $base; ?>';

    // ══ Phone data from PHP ══
    const cpPhones = <?php
    echo json_encode(array_map(function ($p) use ($base) {
      $img = '/imges/icon-256.png';
      if (!empty($p['image'])) {
        $raw = $p['image'];
        if (filter_var($raw, FILTER_VALIDATE_URL))
          $img = $raw;
        elseif (strpos($raw, '/') === 0)
          $img = $raw;
        else
          $img = rtrim($base, '/') . '/' . ltrim($raw, '/');
      }
      $name = trim(($p['brand_name'] ?? '') . ' ' . ($p['name'] ?? ''));
      return ['slug' => $p['slug'] ?? (string) $p['id'], 'name' => $name ?: 'Unknown', 'image' => $img];
    }, $phones), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    ?>;

    const cpImages = {
      1: <?php echo json_encode($phone1Images); ?>,
      2: <?php echo json_encode($phone2Images); ?>,
      3: <?php echo json_encode($phone3Images); ?>
    };
    const cpNames = {
      1: <?php echo json_encode($phone1 ? getPhoneName($phone1) : 'Device 1'); ?>,
      2: <?php echo json_encode($phone2 ? getPhoneName($phone2) : 'Device 2'); ?>,
      3: <?php echo json_encode($phone3 ? getPhoneName($phone3) : 'Device 3'); ?>
    };

    // ══ Live Search ══
    let galleryState = { images: [], index: 0 };

    // ══ Sync col-heads horizontal scroll with table scroll ══
    (function () {
      const scroll = document.getElementById('cp-table-scroll');
      const heads = document.getElementById('cp-col-heads');
      if (scroll && heads) {
        scroll.addEventListener('scroll', () => { heads.scrollLeft = scroll.scrollLeft; }, { passive: true });
      }
    })();

    document.querySelectorAll('.cp-search-input').forEach(input => {
      const slot = input.getAttribute('data-slot');
      const results = document.getElementById('cp-results-' + slot);

      input.addEventListener('focus', () => renderResults(cpPhones, results, slot, results.style.display = 'block'));
      input.addEventListener('blur', () => setTimeout(() => results.style.display = 'none', 220));
      input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        const filtered = q ? cpPhones.filter(p => p.name.toLowerCase().includes(q)) : cpPhones;
        renderResults(filtered, results, slot);
        results.style.display = filtered.length ? 'block' : 'none';
      });
    });

    function renderResults(list, container, slot) {
      if (!list.length) { container.innerHTML = '<div class="cp-sr-none">No results found</div>'; return; }
      container.innerHTML = list.slice(0, 80).map(p =>
        `<div class="cp-sr-item" onmousedown="selectDevice('${p.slug}','${slot}','${p.name.replace(/'/g, "\\'").replace(/"/g, "&quot;")}')">
      <img src="${p.image}" alt="${p.name}" onerror="this.src='/imges/icon-256.png'">
      <span>${p.name}</span>
    </div>`
      ).join('');
    }

    function selectDevice(slug, slot, name) {
      document.getElementById('cp-search-' + slot).value = name;
      navigateWithSlug(slot, slug);
    }
    function clearSlot(slot) { navigateWithSlug(slot, ''); }

    // PHP injects the already-parsed current slugs — no need to re-parse the URL
    const cpCurrentSlugs = [
      <?php echo json_encode($phone1_slug ?? ''); ?>,
      <?php echo json_encode($phone2_slug ?? ''); ?>,
      <?php echo json_encode($phone3_slug ?? ''); ?>
    ];

    function navigateWithSlug(changedSlot, newSlug) {
      // Clone current slugs and apply the change
      const s = cpCurrentSlugs.slice();
      s[changedSlot - 1] = newSlug;

      // Build URL from non-empty slots (preserving slot order)
      const nonEmpty = s.filter(x => x);

      if (nonEmpty.length) {
        window.location.href = window.baseURL + 'compare/' + nonEmpty.join('-vs-');
      } else {
        window.location.href = window.baseURL + 'compare';
      }
    }

    // ══ Spec view toggle ══
    function setSpecView(mode) {
      const table = document.getElementById('cp-spec-table');
      const btnAll = document.getElementById('cp-btn-all');
      const btnDiff = document.getElementById('cp-btn-diff');
      if (!table) return;
      if (mode === 'diff') {
        table.classList.add('cp-diff-mode');
        btnDiff.classList.add('active'); btnAll.classList.remove('active');
      } else {
        table.classList.remove('cp-diff-mode');
        btnAll.classList.add('active'); btnDiff.classList.remove('active');
      }
    }

    // ══ Theme toggle (shared with index.php) ══
    const themeToggles = [document.getElementById('da-theme-toggle'), document.getElementById('da-mobile-theme-toggle')];
    function updateThemeIcons() {
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      themeToggles.forEach(btn => { if (!btn) return; const i = btn.querySelector('i'); if (i) i.className = isLight ? 'fa fa-moon' : 'fa fa-sun'; });
    }
    updateThemeIcons();
    themeToggles.forEach(btn => {
      if (!btn) return;
      btn.addEventListener('click', () => {
        if (document.documentElement.getAttribute('data-theme') === 'light') {
          document.documentElement.removeAttribute('data-theme'); localStorage.setItem('da-theme', 'dark');
        } else {
          document.documentElement.setAttribute('data-theme', 'light'); localStorage.setItem('da-theme', 'light');
        }
        updateThemeIcons();
      });
    });

    // ══ Hamburger ══
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    function closeMobileMenu() { mobileMenu?.classList.remove('open'); hamburger?.classList.remove('open'); }
    hamburger?.addEventListener('click', () => { hamburger.classList.toggle('open'); mobileMenu.classList.toggle('open'); });
    document.addEventListener('click', e => {
      if (mobileMenu?.classList.contains('open') && !mobileMenu.contains(e.target) && !hamburger.contains(e.target)) closeMobileMenu();
    });

, body: 'action=reset' })
        .finally(() => fetch(window.baseURL + 'public_auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) }).then(() => location.reload()));
    }
  </script>
  <script>
    // ── Brand Strip Arrows ──
    const brandScroll = document.getElementById('brand-strip-scroll');
    if (document.getElementById('brand-strip-left'))
      document.getElementById('brand-strip-left').addEventListener('click', () => brandScroll?.scrollBy({ left: -300, behavior: 'smooth' }));
    if (document.getElementById('brand-strip-right'))
      document.getElementById('brand-strip-right').addEventListener('click', () => brandScroll?.scrollBy({ left: 300, behavior: 'smooth' }));

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    if (navbar) window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 40), { passive: true });

    // ── Live Search (navbar) ──
    const searchInput = document.getElementById('da-search-input');
    const searchResults = document.getElementById('da-search-results');
    if (searchInput && searchResults) {
      let searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { searchResults.classList.remove('open'); return; }
        searchTimer = setTimeout(() => {
          Promise.all([
            fetch(window.baseURL + 'api_get_devices.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({ devices: [] })),
            fetch(window.baseURL + 'api_get_posts.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({ posts: [] }))
          ]).then(([devData, postData]) => {
            const devices = devData.devices || [];
            const posts = postData.posts || [];
            if (!devices.length && !posts.length) {
              searchResults.innerHTML = '<div class="da-search-result-item"><div class="sr-text">No results found</div></div>';
              searchResults.classList.add('open'); return;
            }
            let html = '';
            devices.forEach(d => { html += `<a href="${window.baseURL}device/${encodeURIComponent(d.slug || d.id)}" class="da-search-result-item">${d.image ? `<img src="${d.image}" onerror="this.style.display='none'">` : ''}<div><div class="sr-text">${d.name}</div><div class="sr-meta"><i class="fa fa-mobile-screen me-1"></i>${d.brand_name || 'Device'}</div></div></a>`; });
            posts.forEach(p => { html += `<a href="${window.baseURL}post/${encodeURIComponent(p.slug)}" class="da-search-result-item">${p.featured_image ? `<img src="${p.featured_image}" onerror="this.style.display='none'">` : ''}<div><div class="sr-text">${p.title}</div><div class="sr-meta"><i class="fa fa-newspaper me-1"></i>${p.created_at ? p.created_at.substring(0, 10) : 'Article'}</div></div></a>`; });
            searchResults.innerHTML = html;
            searchResults.classList.add('open');
          });
        }, 320);
      });
      document.addEventListener('click', e => { const wrap = document.getElementById('da-search-wrap'); if (wrap && !wrap.contains(e.target)) searchResults.classList.remove('open'); });
    }

    // ── Newsletter ──
    const newsletterBtn = document.getElementById('da-newsletter-btn');
    if (newsletterBtn) newsletterBtn.addEventListener('click', function () {
      const email = document.getElementById('da-newsletter-email').value.trim();
      const msg = document.getElementById('da-newsletter-msg');
      if (!email) { msg.textContent = 'Please enter your email.'; msg.className = 'error'; return; }
      this.disabled = true; this.textContent = 'Subscribing...';
      const btn = this;
      fetch(window.baseURL + 'handle_newsletter.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'newsletter_email=' + encodeURIComponent(email) })
        .then(r => r.json()).then(data => { msg.textContent = data.message; msg.className = data.success ? 'success' : 'error'; if (data.success) document.getElementById('da-newsletter-email').value = ''; btn.disabled = false; btn.textContent = 'Subscribe'; }).catch(() => { msg.textContent = 'An error occurred.'; msg.className = 'error'; btn.disabled = false; btn.textContent = 'Subscribe'; });
    });

    // ── Notification ──
    function markNotificationsAsSeen() {
      ['notifDotDesktop'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
      fetch(window.baseURL + 'notification_handler.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=mark_seen' }).catch(() => { });
    }
    const bell = document.getElementById('notificationBellDesktop');
    if (bell) bell.addEventListener('click', () => setTimeout(markNotificationsAsSeen, 100));


  </script>
  <script src="<?php echo $base; ?>sliders.js"></script>
</body>

</html>