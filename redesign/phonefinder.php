<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../phone_data.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../includes/database_functions.php';

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

// Load filter configuration from JSON
$filterConfig = json_decode(file_get_contents(__DIR__ . '/../filter_config.json'), true);
if (!$filterConfig) {
    die('Error loading filter configuration');
}

?>
<!DOCTYPE html>
<html lang="en" id="da-html">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical"
    href="<?php echo $canonicalBase; ?>/phonefinder<?php echo isset($_GET['slugs']) ? '/' . htmlspecialchars($_GET['slugs']) : ''; ?>" />
  <title>PhoneFinder - DevicesArena</title>
  <meta name="description"
    content="Phonefinder - Compare smartphones side by side on DevicesArena. Full specs, display, camera, battery and connectivity." />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <meta property="og:title" content="PhoneFinder - DevicesArena" />
  <meta property="og:description"
    content="Phonefinder - Compare smartphones side by side on DevicesArena. Full specs, display, camera, battery and connectivity." />
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
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

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
     PHONE FINDER PAGE CONTENT
  ══════════════════════════════════════════ -->
  <div class="da-page">
    <div class="da-content-area w-100" style="max-width: 1200px; margin: 0 auto;">
      <!-- Hero Banner -->
      <div class="da-widget mb-4" style="padding: 0; overflow: hidden;">
        <img class="w-100" src="<?php echo $base; ?>hero-images/phonefinder-hero.png" alt="Phone Finder" style="display: block; max-height: 250px; object-fit: cover; object-position: center;">
      </div>

      <!-- Filters Widget -->
      <div class="da-widget mb-4">
        <div class="da-widget-header">
          <h3>Phone Finder Filters</h3>
          <div class="da-widget-icon"><i class="fa fa-sliders"></i></div>
        </div>
        <div class="da-widget-body">
          <div class="row">
              <!-- General Filters -->
              <h5 class="da-section-label mt-0 mb-3"><span>General</span></h5>
              
              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#brandCollapse">
                Brand <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="brandCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <?php
                  if (!empty($brands)) {
                    foreach ($brands as $brand) {
                      $brandCheckboxId = 'brand' . $brand['id'];
                  ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="<?php echo $brand['id']; ?>" id="<?php echo $brandCheckboxId; ?>" name="brand[]" />
                        <label class="form-check-label" for="<?php echo $brandCheckboxId; ?>"><?php echo htmlspecialchars($brand['name']); ?></label>
                      </div>
                  <?php
                    }
                  } else {
                    echo '<p class="text-muted mb-0">No brands available</p>';
                  }
                  ?>
                </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#networkCollapse">
                Availability <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="networkCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Available" id="availabilityAvailable" name="availability" /><label class="form-check-label" for="availabilityAvailable">Available</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Coming Soon" id="availabilityComingSoon" name="availability" /><label class="form-check-label" for="availabilityComingSoon">Coming Soon</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Discontinued" id="availabilityDiscontinued" name="availability" /><label class="form-check-label" for="availabilityDiscontinued">Discontinued</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Rumored" id="availabilityRumored" name="availability" /><label class="form-check-label" for="availabilityRumored">Rumored</label></div>
                </div>
              </div>

              <!-- Connectivity -->
              <h5 class="da-section-label mt-4 mb-3"><span>Connectivity Slot</span></h5>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <button class="btn btn-outline-secondary w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#simCollapse">2G <i class="fa fa-chevron-down float-end mt-1"></i></button>
                  <div class="collapse" id="simCollapse">
                    <div class="card card-body mt-2 da-filter-card">
                      <?php if (isset($filterConfig['network_2g_bands'])) foreach ($filterConfig['network_2g_bands'] as $index => $band): ?>
                        <div class="form-check"><input class="form-check-input network-2g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="gsm<?php echo $index; ?>" name="network_2g_bands[]" /><label class="form-check-label" for="gsm<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label></div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="col-6">
                  <button class="btn btn-outline-secondary w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#simwasCollapse">3G <i class="fa fa-chevron-down float-end mt-1"></i></button>
                  <div class="collapse" id="simwasCollapse">
                    <div class="card card-body mt-2 da-filter-card">
                      <?php if (isset($filterConfig['network_3g_bands'])) foreach ($filterConfig['network_3g_bands'] as $index => $band): ?>
                        <div class="form-check"><input class="form-check-input network-3g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="hspa<?php echo $index; ?>" name="network_3g_bands[]" /><label class="form-check-label" for="hspa<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label></div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row g-2 mb-3">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="dualSim">DUAL SIM</label>
                          <input class="form-check-input m-0" type="checkbox" id="dualSim" name="dual_sim" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="esimSupport">ESIM</label>
                          <input class="form-check-input m-0" type="checkbox" id="esimSupport" name="esim" value="1">
                      </div>
                  </div>
              </div>

              <!-- Body -->
              <h5 class="da-section-label mt-4 mb-3"><span>Body</span></h5>
              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#factorCollapse">
                Form Factor <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="factorCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <?php if (isset($filterConfig['form_factors'])) foreach ($filterConfig['form_factors'] as $index => $factor): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($factor); ?>" id="formFactor<?php echo $index; ?>" name="form_factor[]" /><label class="form-check-label" for="formFactor<?php echo $index; ?>"><?php echo htmlspecialchars($factor); ?></label></div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Height (min)</label>
                      <span class="badge bg-secondary"><span id="heightMinValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['dimensions']['height_min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['height_min']; ?>" max="<?php echo $filterConfig['dimensions']['height_max']; ?>" step="<?php echo $filterConfig['dimensions']['height_step']; ?>" id="heightMin" value="<?php echo $filterConfig['dimensions']['height_min']; ?>">
                      <span class="text-muted small"><?php echo $filterConfig['dimensions']['height_max']; ?></span>
                  </div>
              </div>

              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Thickness (max)</label>
                      <span class="badge bg-secondary"><span id="thicknessMaxValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['dimensions']['thickness_min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['thickness_min']; ?>" max="<?php echo $filterConfig['dimensions']['thickness_max']; ?>" step="<?php echo $filterConfig['dimensions']['thickness_step']; ?>" id="thicknessMax" value="<?php echo $filterConfig['dimensions']['thickness_min']; ?>">
                      <span class="text-muted small"><?php echo $filterConfig['dimensions']['thickness_max']; ?></span>
                  </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#ipCollapse">
                IP Certificate <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="ipCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <?php if (isset($filterConfig['ip_certificates'])) foreach ($filterConfig['ip_certificates'] as $index => $ipCert): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($ipCert); ?>" id="ip<?php echo $index; ?>" name="ip_certificate[]" /><label class="form-check-label" for="ip<?php echo $index; ?>"><?php echo htmlspecialchars($ipCert); ?></label></div>
                  <?php endforeach; ?>
                </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#backCollapse">
                Back Material <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="backCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <?php if (isset($filterConfig['back_materials'])) foreach ($filterConfig['back_materials'] as $index => $material): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($material); ?>" id="backMaterial<?php echo $index; ?>" name="back_material[]" /><label class="form-check-label" for="backMaterial<?php echo $index; ?>"><?php echo htmlspecialchars($material); ?></label></div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Hardware -->
              <h5 class="da-section-label mt-4 mb-3"><span>Hardware</span></h5>
              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#osCollapse">
                OS Family <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="osCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="row g-2">
                    <?php if (isset($filterConfig['os_families'])) foreach ($filterConfig['os_families'] as $index => $os): ?>
                      <div class="col-6">
                        <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo strtolower(htmlspecialchars($os)); ?>" id="os<?php echo $index; ?>" name="os_family" /><label class="form-check-label" for="os<?php echo $index; ?>"><?php echo htmlspecialchars($os); ?></label></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#chipsCollapse">
                Chipset <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="chipsCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <label for="chipsetQuery" class="form-label text-muted small">Chipset contains</label>
                  <input type="text" id="chipsetQuery" class="form-control" placeholder="e.g. Snapdragon 8 Gen, A18" />
                </div>
              </div>

              <!-- Sensors -->
              <h5 class="da-section-label mt-4 mb-3"><span>Sensors</span></h5>
              <div class="row g-2 mb-2">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0 text-truncate" style="font-size: 0.85rem;" for="accelSensor">ACCELEROMETER</label>
                          <input class="form-check-input m-0" type="checkbox" id="accelSensor" name="accelerometer" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="gyroSensor">GYRO</label>
                          <input class="form-check-input m-0" type="checkbox" id="gyroSensor" name="gyro" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="baroSensor">BAROMETER</label>
                          <input class="form-check-input m-0" type="checkbox" id="baroSensor" name="barometer" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="hrSensor">HEART RATE</label>
                          <input class="form-check-input m-0" type="checkbox" id="hrSensor" name="heart_rate" value="1">
                      </div>
                  </div>
              </div>

              <!-- System Memory -->
              <h5 class="da-section-label mt-4 mb-3"><span>System Memory</span></h5>
              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Min RAM</label>
                      <span class="badge bg-secondary"><span id="ramMinValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['ram']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['ram']['min']; ?>" max="<?php echo $filterConfig['ram']['max']; ?>" step="<?php echo $filterConfig['ram']['step']; ?>" id="ramMin" value="<?php echo $filterConfig['ram']['default']; ?>">
                      <span class="text-muted small"><?php echo $filterConfig['ram']['max']; ?></span>
                  </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardCollapse">
                Expansion Slot <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="cardCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="cardSlotRequired" name="card_slot_required" />
                    <label class="form-check-label" for="cardSlotRequired">Require card slot</label>
                  </div>
                </div>
              </div>
            
            <!-- Right Column -->
            <div class="col-lg-6 col-12" id="pf-right-col">
              <!-- Display -->
              <h5 class="da-section-label mt-0 mb-3"><span>Display</span></h5>
              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Size (min - max)</label>
                      <span class="badge bg-secondary"><span id="displaySizeMinValue"><?php echo $filterConfig['display_size']['default_min']; ?></span> - <span id="displaySizeMaxValue"><?php echo $filterConfig['display_size']['default_max']; ?></span>"</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['display_size']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_size']['min']; ?>" max="<?php echo $filterConfig['display_size']['max']; ?>" step="<?php echo $filterConfig['display_size']['step']; ?>" id="displaySizeMin" value="<?php echo $filterConfig['display_size']['default_min']; ?>" />
                      <span class="mx-1">-</span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_size']['min']; ?>" max="<?php echo $filterConfig['display_size']['max']; ?>" step="<?php echo $filterConfig['display_size']['step']; ?>" id="displaySizeMax" value="<?php echo $filterConfig['display_size']['default_max']; ?>" />
                      <span class="text-muted small"><?php echo $filterConfig['display_size']['max']; ?></span>
                  </div>
              </div>

              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Refresh Rate (min)</label>
                      <span class="badge bg-secondary"><span id="refreshRateMinValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['refresh_rate']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['refresh_rate']['min']; ?>" max="<?php echo $filterConfig['refresh_rate']['max']; ?>" step="<?php echo $filterConfig['refresh_rate']['step']; ?>" id="refreshRateMin" value="<?php echo $filterConfig['refresh_rate']['default']; ?>" />
                      <span class="text-muted small"><?php echo $filterConfig['refresh_rate']['max']; ?></span>
                  </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#techCollapse">
                Technology <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="techCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <?php if (isset($filterConfig['display_technologies'])) foreach ($filterConfig['display_technologies'] as $index => $tech): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($tech); ?>" id="tech<?php echo $index; ?>" name="display_tech[]" /><label class="form-check-label" for="tech<?php echo $index; ?>"><?php echo htmlspecialchars($tech); ?></label></div>
                  <?php endforeach; ?>
                </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#notchCollapse">
                Notch Style <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="notchCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="No notch" id="notchNo" name="display_notch[]" /><label class="form-check-label" for="notchNo">No Notch</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Notch" id="notchYes" name="display_notch[]" /><label class="form-check-label" for="notchYes">Notch</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" value="Punch hole" id="notchPunch" name="display_notch[]" /><label class="form-check-label" for="notchPunch">Punch hole</label></div>
                </div>
              </div>

              <!-- Main Camera -->
              <h5 class="da-section-label mt-4 mb-3"><span>Main Camera</span></h5>
              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Resolution (min MP)</label>
                      <span class="badge bg-secondary"><span id="fNumberMaxValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['f_number']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['f_number']['min']; ?>" max="<?php echo $filterConfig['f_number']['max']; ?>" step="<?php echo $filterConfig['f_number']['step']; ?>" id="fNumberMax" value="<?php echo $filterConfig['f_number']['default']; ?>" />
                      <span class="text-muted small"><?php echo $filterConfig['f_number']['max']; ?></span>
                  </div>
              </div>
              
              <div class="row g-2 mb-2">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="video4k">4K VIDEO</label>
                          <input class="form-check-input m-0" type="checkbox" id="video4k">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="video8k">8K VIDEO</label>
                          <input class="form-check-input m-0" type="checkbox" id="video8k">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="camTele">TELEPHOTO</label>
                          <input class="form-check-input m-0" type="checkbox" id="camTele" name="main_camera_telephoto" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="camUltra">ULTRAWIDE</label>
                          <input class="form-check-input m-0" type="checkbox" id="camUltra" name="main_camera_ultrawide" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="camOis">OIS</label>
                          <input class="form-check-input m-0" type="checkbox" id="camOis" name="main_camera_ois" value="1">
                      </div>
                  </div>
              </div>

              <!-- Selfie Camera -->
              <h5 class="da-section-label mt-4 mb-3"><span>Selfie Camera</span></h5>
              <div class="row g-2 mb-3">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="frontFlash">FRONT FLASH</label>
                          <input class="form-check-input m-0" type="checkbox" id="frontFlash" name="selfie_camera_flash" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="popCam">POP-UP CAM</label>
                          <input class="form-check-input m-0" type="checkbox" id="popCam" name="popup_camera" value="1">
                      </div>
                  </div>
                  <div class="col-12">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="underDisplayCam">UNDER DISPLAY CAMERA</label>
                          <input class="form-check-input m-0" type="checkbox" id="underDisplayCam" name="under_display_camera" value="1">
                      </div>
                  </div>
              </div>

              <!-- Connectivity Addons -->
              <h5 class="da-section-label mt-4 mb-3"><span>Connectivity Addons</span></h5>
              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#wlanCollapse">
                WLAN (Wi-Fi) <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="wlanCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="form-check"><input class="form-check-input wifi-version" type="checkbox" value="802.11n" id="wifi4" name="wifi_versions[]" /><label class="form-check-label" for="wifi4">Wi-Fi 4 (802.11n)</label></div>
                  <div class="form-check"><input class="form-check-input wifi-version" type="checkbox" value="802.11ac" id="wifi5" name="wifi_versions[]" /><label class="form-check-label" for="wifi5">Wi-Fi 5 (802.11ac)</label></div>
                  <div class="form-check"><input class="form-check-input wifi-version" type="checkbox" value="802.11ax" id="wifi6" name="wifi_versions[]" /><label class="form-check-label" for="wifi6">Wi-Fi 6 (802.11ax)</label></div>
                  <div class="form-check"><input class="form-check-input wifi-version" type="checkbox" value="802.11be" id="wifi7" name="wifi_versions[]" /><label class="form-check-label" for="wifi7">Wi-Fi 7 (802.11be)</label></div>
                </div>
              </div>

              <button class="btn btn-outline-secondary w-100 text-start mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#usbCollapse">
                USB <i class="fa fa-chevron-down float-end mt-1"></i>
              </button>
              <div class="collapse" id="usbCollapse">
                <div class="card card-body mb-3 da-filter-card">
                  <div class="form-check"><input class="form-check-input usb-type" type="checkbox" value="USB-C" id="usbTypeC" name="usb_types[]" /><label class="form-check-label" for="usbTypeC">Any USB-C</label></div>
                  <div class="form-check"><input class="form-check-input usb-type" type="checkbox" value="USB 3" id="usb3" name="usb_types[]" /><label class="form-check-label" for="usb3">USB-C 3.0 and higher</label></div>
                  <div class="form-check"><input class="form-check-input usb-type" type="checkbox" value="micro USB" id="microUsb" name="usb_types[]" /><label class="form-check-label" for="microUsb">Micro USB</label></div>
                </div>
              </div>

              <div class="row g-2 mb-3">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="gpsRequired">GPS</label>
                          <input class="form-check-input m-0" type="checkbox" id="gpsRequired" name="gps" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="nfcRequired">NFC</label>
                          <input class="form-check-input m-0" type="checkbox" id="nfcRequired" name="nfc" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="infraredRequired">INFRARED</label>
                          <input class="form-check-input m-0" type="checkbox" id="infraredRequired" name="infrared" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="fmRadioRequired">FM RADIO</label>
                          <input class="form-check-input m-0" type="checkbox" id="fmRadioRequired" name="fm_radio" value="1">
                      </div>
                  </div>
              </div>

              <!-- Battery -->
              <h5 class="da-section-label mt-4 mb-3"><span>Battery</span></h5>
              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Capacity (min mAh)</label>
                      <span class="badge bg-secondary"><span id="batteryCapacityMinValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['battery_capacity']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['battery_capacity']['min']; ?>" max="<?php echo $filterConfig['battery_capacity']['max']; ?>" step="<?php echo $filterConfig['battery_capacity']['step']; ?>" id="batteryCapacityMin" />
                      <span class="text-muted small"><?php echo $filterConfig['battery_capacity']['max']; ?></span>
                  </div>
              </div>

              <div class="da-range-slider-wrapper mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="fw-bold mb-0">Wired Charging (min W)</label>
                      <span class="badge bg-secondary"><span id="wiredChargeMinValue">Any</span></span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small"><?php echo $filterConfig['wired_charging']['min']; ?></span>
                      <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['wired_charging']['min']; ?>" max="<?php echo $filterConfig['wired_charging']['max']; ?>" step="<?php echo $filterConfig['wired_charging']['step']; ?>" id="wiredChargeMin" />
                      <span class="text-muted small"><?php echo $filterConfig['wired_charging']['max']; ?></span>
                  </div>
              </div>
              
              <div class="row g-2 mb-3">
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" for="batRemov">REMOVABLE</label>
                          <input class="form-check-input m-0" type="checkbox" id="batRemov" name="battery_removable" value="1">
                      </div>
                  </div>
                  <div class="col-6">
                      <div class="form-check form-switch da-custom-switch p-2 border rounded d-flex align-items-center justify-content-between">
                          <label class="form-check-label fw-bold mb-0" style="font-size: 0.85rem;" for="wirelessRequired">WIRELESS</label>
                          <input class="form-check-input m-0" type="checkbox" id="wirelessRequired">
                      </div>
                  </div>
              </div>
            </div>
          </div>
          
          <!-- Action buttons -->
          <div class="row mt-5 mb-3">
              <div class="col-md-6 mb-2">
                  <button type="button" id="resetFiltersBtn" class="w-100 py-3 fw-bold" style="background-color: var(--surface-hover); color: var(--text-primary); border: 1px solid var(--border); cursor: pointer; border-radius: var(--radius-sm);">
                      <i class="fa fa-undo me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Reset Filters</span>
                  </button>
              </div>
              <div class="col-md-6 mb-2">
                  <button type="button" id="findDevicesBtn" class="w-100 py-3 fw-bold" style="background-color: var(--accent); color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                      <i class="fa fa-search me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Find Devices</span>
                  </button>
              </div>
          </div>
        </div>
      </div>

      <!-- Results section -->
      <div id="resultsSection" class="da-widget mt-4" style="display: none;">
        <div class="da-widget-header">
            <h3>Found <span id="resultsCount">0</span> Devices</h3>
        </div>
        <div class="da-widget-body">
            <div id="resultsContainer" class="da-phones-grid">
                <!-- Results will be loaded here via AJAX -->
            </div>
        </div>
      </div>

    </div>
  </div><!-- /da-page -->
  
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo $base; ?>script.js"></script>
  <!-- Phonefinder AJAX Script -->
    <script>
        // Make filter configuration available globally
        const filterConfigData = <?php echo json_encode($filterConfig); ?>;
        document.addEventListener('DOMContentLoaded', function() {
            // Handle brand cell clicks (from sidebar and mobile menu - navigate to brand page)
            document.querySelectorAll('.brand-cell').forEach(function(cell) {
                cell.addEventListener('click', function(e) {
                    e.preventDefault();
                    const brandName = this.textContent.trim().toLowerCase().replace(/\s+/g, '-');
                    window.location.href = '<?php echo $base; ?>brand/' + encodeURIComponent(brandName);
                });
            });

            const findBtn = document.getElementById('findDevicesBtn');
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');
            const resultsCount = document.getElementById('resultsCount');

            // Price slider elements
            const priceMaxInput = document.getElementById('priceMax');
            const priceMaxValue = document.getElementById('priceMaxValue');

            // Year slider elements
            const yearMinInput = document.getElementById('yearMin');
            const yearMinValue = document.getElementById('yearMinValue');
            const yearMaxInput = document.getElementById('yearMax');
            const yearMaxValue = document.getElementById('yearMaxValue');

            // RAM slider elements
            const ramMinInput = document.getElementById('ramMin');
            const ramMinValue = document.getElementById('ramMinValue');

            // Storage slider elements
            const storageMinInput = document.getElementById('storageMin');
            const storageMinValue = document.getElementById('storageMinValue');

            // Display size slider elements
            const displaySizeMinInput = document.getElementById('displaySizeMin');
            const displaySizeMinValue = document.getElementById('displaySizeMinValue');
            const displaySizeMaxInput = document.getElementById('displaySizeMax');
            const displaySizeMaxValue = document.getElementById('displaySizeMaxValue');

            // Display resolution slider elements
            const displayResMinInput = document.getElementById('displayResMin');
            const displayResMinValue = document.getElementById('displayResMinValue');
            const displayResMaxInput = document.getElementById('displayResMax');
            const displayResMaxValue = document.getElementById('displayResMaxValue');

            // Refresh rate slider
            const refreshRateMinInput = document.getElementById('refreshRateMin');
            const refreshRateMinValue = document.getElementById('refreshRateMinValue');

            // F-number (aperture) max slider
            const fNumberMaxInput = document.getElementById('fNumberMax');
            const fNumberMaxValue = document.getElementById('fNumberMaxValue');

            // CPU clock min slider (GHz)
            const cpuClockMinInput = document.getElementById('cpuClockMin');
            const cpuClockMinValue = document.getElementById('cpuClockMinValue');

            // Body sliders
            const heightMinInput = document.getElementById('heightMin');
            const heightMinValue = document.getElementById('heightMinValue');
            const thicknessMaxInput = document.getElementById('thicknessMax');
            const thicknessMaxValue = document.getElementById('thicknessMaxValue');
            const widthMinInput = document.getElementById('widthMin');
            const widthMinValue = document.getElementById('widthMinValue');
            const weightMaxInput = document.getElementById('weightMax');
            const weightMaxValue = document.getElementById('weightMaxValue');

            // B1 new controls
            const osVersionMinInput = document.getElementById('osVersionMin');
            const osVersionMinValue = document.getElementById('osVersionMinValue');
            const chipsetQueryInput = document.getElementById('chipsetQuery');
            const cardSlotRequiredInput = document.getElementById('cardSlotRequired');
            const mainCamMpMinInput = document.getElementById('mainCamMpMin');
            const mainCamMpMinValue = document.getElementById('mainCamMpMinValue');
            const video4kInput = document.getElementById('video4k');
            const video8kInput = document.getElementById('video8k');
            const batteryCapacityMinInput = document.getElementById('batteryCapacityMin');
            const batteryCapacityMinValue = document.getElementById('batteryCapacityMinValue');
            const wiredChargeMinInput = document.getElementById('wiredChargeMin');
            const wiredChargeMinValue = document.getElementById('wiredChargeMinValue');
            const wirelessRequiredInput = document.getElementById('wirelessRequired');
            const wirelessChargeMinInput = document.getElementById('wirelessChargeMin');
            const wirelessChargeMinValue = document.getElementById('wirelessChargeMinValue');
            const osFamilyInputs = document.querySelectorAll('input[name="os_family"]');

            // Price slider handler
            if (priceMaxInput && priceMaxValue) {
                const maxPrice = parseInt(priceMaxInput.max, 10);
                const formatUsd = (v) => {
                    const num = parseInt(v, 10);
                    if (isNaN(num) || num <= 0 || num >= maxPrice) return 'Any';
                    return '$' + num.toLocaleString();
                };
                priceMaxValue.textContent = formatUsd(priceMaxInput.value);
                priceMaxInput.addEventListener('input', function() {
                    priceMaxValue.textContent = formatUsd(this.value);
                });
            }

            // Year slider handlers
            if (yearMinInput && yearMinValue) {
                yearMinValue.textContent = yearMinInput.value;
                yearMinInput.addEventListener('input', function() {
                    yearMinValue.textContent = this.value;
                    // Ensure min <= max
                    if (parseInt(this.value) > parseInt(yearMaxInput.value)) {
                        yearMaxInput.value = this.value;
                        yearMaxValue.textContent = this.value;
                    }
                });
            }
            if (yearMaxInput && yearMaxValue) {
                yearMaxValue.textContent = yearMaxInput.value;
                yearMaxInput.addEventListener('input', function() {
                    yearMaxValue.textContent = this.value;
                    // Ensure max >= min
                    if (parseInt(this.value) < parseInt(yearMinInput.value)) {
                        yearMinInput.value = this.value;
                        yearMinValue.textContent = this.value;
                    }
                });
            }

            // RAM slider handler
            if (ramMinInput && ramMinValue) {
                ramMinValue.textContent = ramMinInput.value == 0 ? 'Any' : ramMinInput.value + ' GB';
                ramMinInput.addEventListener('input', function() {
                    ramMinValue.textContent = this.value == 0 ? 'Any' : this.value + ' GB';
                });
            }

            // Storage slider handler
            if (storageMinInput && storageMinValue) {
                storageMinValue.textContent = storageMinInput.value == 0 ? 'Any' : storageMinInput.value + ' GB';
                storageMinInput.addEventListener('input', function() {
                    const val = parseInt(this.value);
                    if (val == 0) {
                        storageMinValue.textContent = 'Any';
                    } else if (val >= 1024) {
                        storageMinValue.textContent = (val / 1024) + ' TB';
                    } else {
                        storageMinValue.textContent = val + ' GB';
                    }
                });
            }

            // Display size slider handlers
            if (displaySizeMinInput && displaySizeMinValue) {
                displaySizeMinValue.textContent = parseFloat(displaySizeMinInput.value).toFixed(1);
                displaySizeMinInput.addEventListener('input', function() {
                    displaySizeMinValue.textContent = parseFloat(this.value).toFixed(1);
                    // Ensure min <= max
                    if (parseFloat(this.value) > parseFloat(displaySizeMaxInput.value)) {
                        displaySizeMaxInput.value = this.value;
                        displaySizeMaxValue.textContent = parseFloat(this.value).toFixed(1);
                    }
                });
            }
            if (displaySizeMaxInput && displaySizeMaxValue) {
                displaySizeMaxValue.textContent = parseFloat(displaySizeMaxInput.value).toFixed(1);
                displaySizeMaxInput.addEventListener('input', function() {
                    displaySizeMaxValue.textContent = parseFloat(this.value).toFixed(1);
                    // Ensure max >= min
                    if (parseFloat(this.value) < parseFloat(displaySizeMinInput.value)) {
                        displaySizeMinInput.value = this.value;
                        displaySizeMinValue.textContent = parseFloat(this.value).toFixed(1);
                    }
                });
            }

            // Display resolution slider handlers
            if (displayResMinInput && displayResMinValue) {
                const formatRes = (v) => v == 480 ? 'min' : v + 'p';
                displayResMinValue.textContent = formatRes(displayResMinInput.value);
                displayResMinInput.addEventListener('input', function() {
                    displayResMinValue.textContent = formatRes(this.value);
                    if (parseInt(this.value) > parseInt(displayResMaxInput.value)) {
                        displayResMaxInput.value = this.value;
                        displayResMaxValue.textContent = formatRes(this.value);
                    }
                });
            }
            if (displayResMaxInput && displayResMaxValue) {
                const formatRes = (v) => v == 4320 ? 'max' : v + 'p';
                displayResMaxValue.textContent = formatRes(displayResMaxInput.value);
                displayResMaxInput.addEventListener('input', function() {
                    displayResMaxValue.textContent = formatRes(this.value);
                    if (parseInt(this.value) < parseInt(displayResMinInput.value)) {
                        displayResMinInput.value = this.value;
                        displayResMinValue.textContent = formatRes(this.value);
                    }
                });
            }

            // Refresh rate handler
            if (refreshRateMinInput && refreshRateMinValue) {
                const formatHz = (v) => v == 0 ? 'Any' : '≥ ' + v + 'Hz';
                refreshRateMinValue.textContent = formatHz(refreshRateMinInput.value);
                refreshRateMinInput.addEventListener('input', function() {
                    refreshRateMinValue.textContent = formatHz(this.value);
                });
            }

            // OS Version slider handler
            if (osVersionMinInput && osVersionMinValue) {
                const updateOsVer = (v) => v == 0 ? 'Any' : '≥ ' + v;
                osVersionMinValue.textContent = updateOsVer(osVersionMinInput.value);
                osVersionMinInput.addEventListener('input', function() {
                    osVersionMinValue.textContent = updateOsVer(this.value);
                });
            }

            // Main camera MP handler
            if (mainCamMpMinInput && mainCamMpMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + 'MP';
                mainCamMpMinValue.textContent = upd(mainCamMpMinInput.value);
                mainCamMpMinInput.addEventListener('input', function() {
                    mainCamMpMinValue.textContent = upd(this.value);
                });
            }

            // Battery capacity handler
            if (batteryCapacityMinInput && batteryCapacityMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' mAh';
                batteryCapacityMinValue.textContent = upd(batteryCapacityMinInput.value);
                batteryCapacityMinInput.addEventListener('input', function() {
                    batteryCapacityMinValue.textContent = upd(this.value);
                });
            }

            // Wired charging handler
            if (wiredChargeMinInput && wiredChargeMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' W';
                wiredChargeMinValue.textContent = upd(wiredChargeMinInput.value);
                wiredChargeMinInput.addEventListener('input', function() {
                    wiredChargeMinValue.textContent = upd(this.value);
                });
            }

            // Wireless charging handler
            if (wirelessChargeMinInput && wirelessChargeMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' W';
                wirelessChargeMinValue.textContent = upd(wirelessChargeMinInput.value);
                wirelessChargeMinInput.addEventListener('input', function() {
                    wirelessChargeMinValue.textContent = upd(this.value);
                });
            }

            // F-number handler (max threshold, smaller is better)
            if (fNumberMaxInput && fNumberMaxValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≤ f/' + parseFloat(v).toFixed(1);
                fNumberMaxValue.textContent = fmt(fNumberMaxInput.value);
                fNumberMaxInput.addEventListener('input', function() {
                    fNumberMaxValue.textContent = fmt(this.value);
                });
            }

            // CPU clock handler (min GHz)
            if (cpuClockMinInput && cpuClockMinValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≥ ' + parseFloat(v).toFixed(1) + ' GHz';
                cpuClockMinValue.textContent = fmt(cpuClockMinInput.value);
                cpuClockMinInput.addEventListener('input', function() {
                    cpuClockMinValue.textContent = fmt(this.value);
                });
            }

            // Height (min)
            if (heightMinInput && heightMinValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≥ ' + parseInt(v) + ' mm';
                heightMinValue.textContent = fmt(heightMinInput.value);
                heightMinInput.addEventListener('input', function() {
                    heightMinValue.textContent = fmt(this.value);
                });
            }
            // Thickness (max)
            if (thicknessMaxInput && thicknessMaxValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≤ ' + parseFloat(v).toFixed(1) + ' mm';
                thicknessMaxValue.textContent = fmt(thicknessMaxInput.value);
                thicknessMaxInput.addEventListener('input', function() {
                    thicknessMaxValue.textContent = fmt(this.value);
                });
            }
            // Width (min)
            if (widthMinInput && widthMinValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≥ ' + parseInt(v) + ' mm';
                widthMinValue.textContent = fmt(widthMinInput.value);
                widthMinInput.addEventListener('input', function() {
                    widthMinValue.textContent = fmt(this.value);
                });
            }
            // Weight (max)
            if (weightMaxInput && weightMaxValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≤ ' + parseInt(v) + ' g';
                weightMaxValue.textContent = fmt(weightMaxInput.value);
                weightMaxInput.addEventListener('input', function() {
                    weightMaxValue.textContent = fmt(this.value);
                });
            }

            // Utility: reset all filters to default (no filter)
            function resetAllFilters() {
                // Uncheck all checkboxes
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                // Reset all sliders to min/default
                if (priceMaxInput) priceMaxInput.value = priceMaxInput.min;
                if (yearMinInput) yearMinInput.value = yearMinInput.min;
                if (yearMaxInput) yearMaxInput.value = yearMaxInput.max;
                if (ramMinInput) ramMinInput.value = ramMinInput.min;
                if (storageMinInput) storageMinInput.value = storageMinInput.min;
                if (displaySizeMinInput) displaySizeMinInput.value = displaySizeMinInput.min;
                if (displaySizeMaxInput) displaySizeMaxInput.value = displaySizeMaxInput.max;
                if (displayResMinInput) displayResMinInput.value = displayResMinInput.min;
                if (displayResMaxInput) displayResMaxInput.value = displayResMaxInput.max;
                if (refreshRateMinInput) refreshRateMinInput.value = refreshRateMinInput.min;
                if (osVersionMinInput) osVersionMinInput.value = osVersionMinInput.min;
                if (mainCamMpMinInput) mainCamMpMinInput.value = mainCamMpMinInput.min;
                if (batteryCapacityMinInput) batteryCapacityMinInput.value = batteryCapacityMinInput.min;
                if (wiredChargeMinInput) wiredChargeMinInput.value = wiredChargeMinInput.min;
                if (wirelessChargeMinInput) wirelessChargeMinInput.value = wirelessChargeMinInput.min;
                if (fNumberMaxInput) fNumberMaxInput.value = fNumberMaxInput.min;
                if (cpuClockMinInput) cpuClockMinInput.value = cpuClockMinInput.min;
                if (heightMinInput) heightMinInput.value = heightMinInput.min;
                if (thicknessMaxInput) thicknessMaxInput.value = thicknessMaxInput.min;
                if (widthMinInput) widthMinInput.value = widthMinInput.min;
                if (weightMaxInput) weightMaxInput.value = weightMaxInput.min;
                if (chipsetQueryInput) chipsetQueryInput.value = '';
                // Trigger UI updates for labels
                if (typeof Event === 'function') {
                    [priceMaxInput, yearMinInput, yearMaxInput, ramMinInput, storageMinInput, displaySizeMinInput, displaySizeMaxInput, displayResMinInput, displayResMaxInput, refreshRateMinInput, osVersionMinInput, mainCamMpMinInput, batteryCapacityMinInput, wiredChargeMinInput, wirelessChargeMinInput, fNumberMaxInput, cpuClockMinInput, heightMinInput, thicknessMaxInput, widthMinInput, weightMaxInput].forEach(inp => {
                        if (inp) inp.dispatchEvent(new Event('input'));
                    });
                }
            }

            // On page load, reset all filters
            resetAllFilters();

            // Reset button handler
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetAllFilters();
                });
            }

            findBtn.addEventListener('click', function() {
                // Gather selected brands
                const selectedBrands = [];
                document.querySelectorAll('input[name="brand[]"]:checked').forEach(function(checkbox) {
                    selectedBrands.push(checkbox.value);
                });

                // Gather selected availability statuses
                const selectedAvailability = [];
                document.querySelectorAll('input[name="availability"]:checked').forEach(function(checkbox) {
                    selectedAvailability.push(checkbox.value);
                });

                // Check if at least one filter is applied
                let hasFilter = false;

                // Check brands and availability
                if (selectedBrands.length > 0 || selectedAvailability.length > 0) {
                    hasFilter = true;
                }

                // Check price max
                if (priceMaxInput) {
                    const val = parseInt(priceMaxInput.value, 10);
                    const maxPrice = parseInt(priceMaxInput.max, 10);
                    if (!isNaN(val) && val > 0 && val < maxPrice) {
                        hasFilter = true;
                    }
                }

                // Check year range
                if (yearMinInput && yearMaxInput) {
                    const minYear = parseInt(yearMinInput.value);
                    const maxYear = parseInt(yearMaxInput.value);
                    if (minYear > 2000 || maxYear < 2026) {
                        hasFilter = true;
                    }
                }

                // Check RAM
                if (ramMinInput && parseInt(ramMinInput.value) > 0) {
                    hasFilter = true;
                }

                // Check Storage
                if (storageMinInput && parseInt(storageMinInput.value) > 0) {
                    hasFilter = true;
                }

                // Check Display Size
                if (displaySizeMinInput && displaySizeMaxInput) {
                    const minSize = parseFloat(displaySizeMinInput.value);
                    const maxSize = parseFloat(displaySizeMaxInput.value);
                    if (minSize > 3.0 || maxSize < 8.0) {
                        hasFilter = true;
                    }
                }

                // Check Display Resolution
                if (displayResMinInput && displayResMaxInput) {
                    const minRes = parseInt(displayResMinInput.value);
                    const maxRes = parseInt(displayResMaxInput.value);
                    if (minRes > 480 || maxRes < 4320) {
                        hasFilter = true;
                    }
                }

                // Check various checkboxes and inputs
                if (document.querySelectorAll('input[name="display_tech[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="display_notch[]"]:checked').length > 0 ||
                    (refreshRateMinInput && parseInt(refreshRateMinInput.value) > 0) ||
                    document.querySelector('input[name="hdr"]:checked') ||
                    document.querySelector('input[name="billion_colors"]:checked') ||
                    document.querySelectorAll('input[name="os_family"]:checked').length > 0 ||
                    (osVersionMinInput && parseInt(osVersionMinInput.value) > 0) ||
                    (chipsetQueryInput && chipsetQueryInput.value.trim() !== '') ||
                    document.querySelector('input[name="card_slot_required"]:checked') ||
                    (mainCamMpMinInput && parseInt(mainCamMpMinInput.value) > 0) ||
                    (fNumberMaxInput && parseFloat(fNumberMaxInput.value) > 0) ||
                    document.querySelector('input[name="video_4k"]:checked') ||
                    document.querySelector('input[name="video_8k"]:checked') ||
                    (batteryCapacityMinInput && parseInt(batteryCapacityMinInput.value) > 0) ||
                    (wiredChargeMinInput && parseInt(wiredChargeMinInput.value) > 0) ||
                    document.querySelector('input[name="wireless_required"]:checked') ||
                    (wirelessChargeMinInput && parseInt(wirelessChargeMinInput.value) > 0) ||
                    (cpuClockMinInput && parseFloat(cpuClockMinInput.value) > 0) ||
                    (heightMinInput && parseInt(heightMinInput.value) > 0) ||
                    (thicknessMaxInput && parseFloat(thicknessMaxInput.value) > 0) ||
                    (widthMinInput && parseInt(widthMinInput.value) > 0) ||
                    (weightMaxInput && parseInt(weightMaxInput.value) > 0) ||
                    document.querySelectorAll('input[name="color[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="frame_material[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="back_material[]"]:checked').length > 0 ||
                    document.querySelectorAll('input.network-2g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-3g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-4g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-5g-band:checked').length > 0 ||
                    document.getElementById('dualSim')?.checked ||
                    document.getElementById('esimSupport')?.checked ||
                    document.querySelectorAll('input[name="sim_sizes[]"]:checked').length > 0 ||
                    document.querySelectorAll('input.wifi-version:checked').length > 0 ||
                    document.querySelectorAll('input.bluetooth-version:checked').length > 0 ||
                    document.querySelectorAll('input.usb-type:checked').length > 0 ||
                    document.getElementById('gpsRequired')?.checked ||
                    document.getElementById('nfcRequired')?.checked ||
                    document.getElementById('infraredRequired')?.checked ||
                    document.getElementById('fmRadioRequired')?.checked ||
                    document.querySelector('input[name="accelerometer"]:checked') ||
                    document.querySelector('input[name="gyro"]:checked') ||
                    document.querySelector('input[name="barometer"]:checked') ||
                    document.querySelector('input[name="heart_rate"]:checked') ||
                    document.querySelector('input[name="compass"]:checked') ||
                    document.querySelector('input[name="proximity"]:checked') ||
                    document.querySelector('input[name="headphone_jack"]:checked') ||
                    document.querySelector('input[name="dual_speakers"]:checked') ||
                    document.querySelector('input[name="main_camera_telephoto"]:checked') ||
                    document.querySelector('input[name="main_camera_ultrawide"]:checked') ||
                    document.querySelector('input[name="main_camera_ois"]:checked') ||
                    document.querySelector('input[name="selfie_camera_flash"]:checked') ||
                    document.querySelector('input[name="popup_camera"]:checked') ||
                    document.querySelector('input[name="under_display_camera"]:checked') ||
                    document.querySelector('input[name="battery_sic"]:checked') ||
                    document.querySelector('input[name="battery_removable"]:checked') ||
                    document.querySelectorAll('input[name="ip_certificate[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="form_factor[]"]:checked').length > 0 ||
                    (document.querySelector('input.life')?.value.trim() !== '')) {
                    hasFilter = true;
                }

                // If no filters selected, show alert and stop
                if (!hasFilter) {
                    alert('Please select at least one filter to search for devices. This helps improve performance and provides more relevant results.');
                    return;
                }

                // Show loading state
                findBtn.disabled = true;
                findBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Searching...</span>';

                // Prepare form data
                const formData = new FormData();
                selectedBrands.forEach(function(brandId) {
                    formData.append('brands[]', brandId);
                });
                selectedAvailability.forEach(function(status) {
                    formData.append('availability[]', status);
                });

                // Append price max if provided (> 0 and < max)
                if (priceMaxInput) {
                    const val = parseInt(priceMaxInput.value, 10);
                    const maxPrice = parseInt(priceMaxInput.max, 10);
                    if (!isNaN(val) && val > 0 && val < maxPrice) {
                        formData.append('price_max', val);
                    }
                }

                // Append year range if not default
                if (yearMinInput && yearMaxInput) {
                    const minYear = parseInt(yearMinInput.value);
                    const maxYear = parseInt(yearMaxInput.value);
                    if (minYear > 2000) formData.append('year_min', minYear);
                    if (maxYear < 2026) formData.append('year_max', maxYear);
                }

                // Append RAM min if > 0
                if (ramMinInput) {
                    const ramMin = parseInt(ramMinInput.value);
                    if (ramMin > 0) formData.append('ram_min', ramMin);
                }

                // Append Storage min if > 0
                if (storageMinInput) {
                    const storageMin = parseInt(storageMinInput.value);
                    if (storageMin > 0) formData.append('storage_min', storageMin);
                }

                // Append Display Size range if not default
                if (displaySizeMinInput && displaySizeMaxInput) {
                    const minSize = parseFloat(displaySizeMinInput.value);
                    const maxSize = parseFloat(displaySizeMaxInput.value);
                    if (minSize > 3.0) formData.append('display_size_min', minSize);
                    if (maxSize < 8.0) formData.append('display_size_max', maxSize);
                }

                // Display Resolution range if not default
                if (displayResMinInput && displayResMaxInput) {
                    const minRes = parseInt(displayResMinInput.value);
                    const maxRes = parseInt(displayResMaxInput.value);
                    if (minRes > 480) formData.append('display_res_min', minRes);
                    if (maxRes < 4320) formData.append('display_res_max', maxRes);
                }

                // Display Technology checkboxes
                document.querySelectorAll('input[name="display_tech[]"]:checked').forEach(cb => {
                    formData.append('display_tech[]', cb.value);
                });

                // Display Notch checkboxes
                document.querySelectorAll('input[name="display_notch[]"]:checked').forEach(cb => {
                    formData.append('display_notch[]', cb.value);
                });

                // Refresh Rate min if > 0
                if (refreshRateMinInput) {
                    const rr = parseInt(refreshRateMinInput.value);
                    if (rr > 0) formData.append('refresh_rate_min', rr);
                }

                // HDR
                const hdrCb = document.querySelector('input[name="hdr"]');
                if (hdrCb && hdrCb.checked) formData.append('hdr', '1');

                // Billion colors
                const billionColorsCb = document.querySelector('input[name="billion_colors"]');
                if (billionColorsCb && billionColorsCb.checked) formData.append('billion_colors', '1');

                // OS families (multi)
                if (osFamilyInputs) {
                    osFamilyInputs.forEach(cb => {
                        if (cb.checked) formData.append('os_family[]', cb.value);
                    });
                }
                // Min OS version
                if (osVersionMinInput) {
                    const v = parseInt(osVersionMinInput.value);
                    if (v > 0) formData.append('os_version_min', v);
                }
                // Chipset contains
                if (chipsetQueryInput && chipsetQueryInput.value.trim() !== '') {
                    formData.append('chipset_query', chipsetQueryInput.value.trim());
                }
                // Require card slot
                if (cardSlotRequiredInput && cardSlotRequiredInput.checked) {
                    formData.append('card_slot_required', '1');
                }
                // Main camera min MP
                if (mainCamMpMinInput) {
                    const mp = parseInt(mainCamMpMinInput.value);
                    if (mp > 0) formData.append('main_camera_mp_min', mp);
                }
                // F-number max (aperture)
                if (fNumberMaxInput) {
                    const fmax = parseFloat(fNumberMaxInput.value);
                    if (!isNaN(fmax) && fmax > 0) formData.append('f_number_max', fmax.toFixed(1));
                }
                // Video capabilities
                if (video4kInput && video4kInput.checked) formData.append('video_4k', '1');
                if (video8kInput && video8kInput.checked) formData.append('video_8k', '1');
                // Battery capacity min
                if (batteryCapacityMinInput) {
                    const bc = parseInt(batteryCapacityMinInput.value);
                    if (bc > 0) formData.append('battery_capacity_min', bc);
                }
                // Wired charging min
                if (wiredChargeMinInput) {
                    const wc = parseInt(wiredChargeMinInput.value);
                    if (wc > 0) formData.append('wired_charge_min', wc);
                }
                // Wireless required + min
                if (wirelessRequiredInput && wirelessRequiredInput.checked) formData.append('wireless_required', '1');
                if (wirelessChargeMinInput) {
                    const wlc = parseInt(wirelessChargeMinInput.value);
                    if (wlc > 0) formData.append('wireless_charge_min', wlc);
                }

                // CPU min GHz
                if (cpuClockMinInput) {
                    const cpuMin = parseFloat(cpuClockMinInput.value);
                    if (!isNaN(cpuMin) && cpuMin > 0) formData.append('cpu_clock_min', cpuMin.toFixed(1));
                }

                // Body dimensions/weight
                if (heightMinInput) {
                    const h = parseInt(heightMinInput.value);
                    if (!isNaN(h) && h > 0) formData.append('height_min', h);
                }
                if (thicknessMaxInput) {
                    const t = parseFloat(thicknessMaxInput.value);
                    if (!isNaN(t) && t > 0) formData.append('thickness_max', t.toFixed(1));
                }
                if (widthMinInput) {
                    const w = parseInt(widthMinInput.value);
                    if (!isNaN(w) && w > 0) formData.append('width_min', w);
                }
                if (weightMaxInput) {
                    const wg = parseInt(weightMaxInput.value);
                    if (!isNaN(wg) && wg > 0) formData.append('weight_max', wg);
                }

                // Materials & colors
                document.querySelectorAll('input[name="color[]"]:checked').forEach(cb => {
                    formData.append('color[]', cb.value);
                });
                document.querySelectorAll('input[name="frame_material[]"]:checked').forEach(cb => {
                    formData.append('frame_material[]', cb.value);
                });
                document.querySelectorAll('input[name="back_material[]"]:checked').forEach(cb => {
                    formData.append('back_material[]', cb.value);
                });

                // Network filters (SIM/Network bands)
                // 2G bands
                document.querySelectorAll('input.network-2g-band:checked').forEach(cb => {
                    formData.append('network_2g_bands[]', cb.value);
                });
                // 3G bands
                document.querySelectorAll('input.network-3g-band:checked').forEach(cb => {
                    formData.append('network_3g_bands[]', cb.value);
                });
                // 4G bands
                document.querySelectorAll('input.network-4g-band:checked').forEach(cb => {
                    formData.append('network_4g_bands[]', cb.value);
                });
                // 5G bands
                document.querySelectorAll('input.network-5g-band:checked').forEach(cb => {
                    formData.append('network_5g_bands[]', cb.value);
                });
                // Dual SIM
                const dualSimInput = document.getElementById('dualSim');
                if (dualSimInput && dualSimInput.checked) {
                    formData.append('dual_sim', '1');
                }
                // eSIM
                const esimSupportInput = document.getElementById('esimSupport');
                if (esimSupportInput && esimSupportInput.checked) {
                    formData.append('esim', '1');
                }
                // SIM sizes
                document.querySelectorAll('input[name="sim_sizes[]"]:checked').forEach(cb => {
                    formData.append('sim_sizes[]', cb.value);
                });

                // WiFi/WLAN versions
                document.querySelectorAll('input.wifi-version:checked').forEach(cb => {
                    formData.append('wifi_versions[]', cb.value);
                });
                // Bluetooth versions
                document.querySelectorAll('input.bluetooth-version:checked').forEach(cb => {
                    formData.append('bluetooth_versions[]', cb.value);
                });
                // USB types
                document.querySelectorAll('input.usb-type:checked').forEach(cb => {
                    formData.append('usb_types[]', cb.value);
                });
                // GPS required
                const gpsInput = document.getElementById('gpsRequired');
                if (gpsInput && gpsInput.checked) {
                    formData.append('gps_required', '1');
                }
                // NFC required
                const nfcInput = document.getElementById('nfcRequired');
                if (nfcInput && nfcInput.checked) {
                    formData.append('nfc_required', '1');
                }
                // Infrared required
                const infraredInput = document.getElementById('infraredRequired');
                if (infraredInput && infraredInput.checked) {
                    formData.append('infrared_required', '1');
                }
                // FM Radio required
                const fmRadioInput = document.getElementById('fmRadioRequired');
                if (fmRadioInput && fmRadioInput.checked) {
                    formData.append('fm_radio_required', '1');
                }

                // Sensors (existing UI checkboxes)
                const accelCb = document.querySelector('input[name="accelerometer"]');
                if (accelCb && accelCb.checked) formData.append('accelerometer', '1');
                const gyroCb = document.querySelector('input[name="gyro"]');
                if (gyroCb && gyroCb.checked) formData.append('gyro', '1');
                const barometerCb = document.querySelector('input[name="barometer"]');
                if (barometerCb && barometerCb.checked) formData.append('barometer', '1');
                const heartRateCb = document.querySelector('input[name="heart_rate"]');
                if (heartRateCb && heartRateCb.checked) formData.append('heart_rate', '1');
                const compassCb = document.querySelector('input[name="compass"]');
                if (compassCb && compassCb.checked) formData.append('compass', '1');
                const proximityCb = document.querySelector('input[name="proximity"]');
                if (proximityCb && proximityCb.checked) formData.append('proximity', '1');

                // Audio (existing UI checkboxes)
                const hpJackCb = document.querySelector('input[name="headphone_jack"]');
                if (hpJackCb && hpJackCb.checked) formData.append('headphone_jack', '1');
                const dualSpkCb = document.querySelector('input[name="dual_speakers"]');
                if (dualSpkCb && dualSpkCb.checked) formData.append('dual_speakers', '1');

                // Main Camera features (existing UI checkboxes)
                const telephotoCheckbox = document.querySelector('input[name="main_camera_telephoto"]');
                if (telephotoCheckbox && telephotoCheckbox.checked) formData.append('main_camera_telephoto', '1');
                const ultrawideCheckbox = document.querySelector('input[name="main_camera_ultrawide"]');
                if (ultrawideCheckbox && ultrawideCheckbox.checked) formData.append('main_camera_ultrawide', '1');
                const oisCheckbox = document.querySelector('input[name="main_camera_ois"]');
                if (oisCheckbox && oisCheckbox.checked) formData.append('main_camera_ois', '1');

                // Selfie Camera features (existing UI checkboxes)
                const selfieFlashCheckbox = document.querySelector('input[name="selfie_camera_flash"]');
                if (selfieFlashCheckbox && selfieFlashCheckbox.checked) formData.append('selfie_camera_flash', '1');
                const popupCameraCheckbox = document.querySelector('input[name="popup_camera"]');
                if (popupCameraCheckbox && popupCameraCheckbox.checked) formData.append('popup_camera', '1');
                const underDisplayCameraCheckbox = document.querySelector('input[name="under_display_camera"]');
                if (underDisplayCameraCheckbox && underDisplayCameraCheckbox.checked) formData.append('under_display_camera', '1');

                // Battery extras (existing UI checkboxes)
                const batterySiCCheckbox = document.querySelector('input[name="battery_sic"]');
                if (batterySiCCheckbox && batterySiCCheckbox.checked) formData.append('battery_sic', '1');
                const batteryRemovableCheckbox = document.querySelector('input[name="battery_removable"]');
                if (batteryRemovableCheckbox && batteryRemovableCheckbox.checked) formData.append('battery_removable', '1');

                // IP certificate (multi-select)
                document.querySelectorAll('input[name="ip_certificate[]"]:checked').forEach(cb => {
                    formData.append('ip_certificate[]', cb.value);
                });

                // Form factor (multi-select)
                document.querySelectorAll('input[name="form_factor[]"]:checked').forEach(cb => {
                    formData.append('form_factor[]', cb.value);
                });

                // Free Text (Misc)
                const freeTextInput = document.querySelector('input.life');
                if (freeTextInput && freeTextInput.value.trim() !== '') {
                    formData.append('free_text', freeTextInput.value.trim());
                }

                // Orders (sorting options) - pick the first checked option
                let orderValue = null;
                document.querySelectorAll('#popularCollapse .form-check').forEach(fc => {
                    if (orderValue) return; // already chosen one
                    const inp = fc.querySelector('input');
                    const lbl = fc.querySelector('label');
                    if (inp && inp.checked && lbl) {
                        const t = lbl.textContent.trim().toLowerCase();
                        if (t.startsWith('price')) orderValue = 'price';
                        else if (t.startsWith('camera')) orderValue = 'camera_battery';
                        else if (t.startsWith('popularity')) orderValue = 'popularity';
                    }
                });
                if (orderValue) formData.append('order', orderValue);

                // Fingerprint (from Fingerprint collapse by label text)
                const fpConds = [];
                document.querySelectorAll('#fingerCollapse .form-check').forEach(fc => {
                    const inp = fc.querySelector('input');
                    const lbl = fc.querySelector('label');
                    if (inp && inp.checked && lbl) {
                        const t = lbl.textContent.trim().toLowerCase();
                        if (t.includes('any')) fpConds.push('any');
                        else if (t.includes('rear')) fpConds.push('rear');
                        else if (t.includes('side')) fpConds.push('side');
                        else if (t.includes('under display') || t.includes('under-display') || t.includes('in-display')) fpConds.push('under_display');
                    }
                });
                // Deduplicate and append
                [...new Set(fpConds)].forEach(v => formData.append('fingerprint[]', v));

                // Define createDeviceCard for the redesign UI
                function createDeviceCard(device) {
                    const img = device.image || (window.baseURL + 'assets/images/placeholder.jpg');
                    const url = window.baseURL + 'device/' + (device.slug || device.id);
                    return `
                      <a href="${url}" class="da-phone-card text-decoration-none">
                        <div class="da-pc-img">
                          <img src="${img}" alt="${device.name}" onerror="this.src='${window.baseURL}assets/images/placeholder.jpg'">
                        </div>
                        <div class="da-pc-body">
                          <h4 class="da-pc-title">${device.name}</h4>
                        </div>
                      </a>
                    `;
                }

                // Send AJAX request
                fetch(window.baseURL + 'phonefinder_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(text => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }

                        if (data.success) {
                            // Update results count
                            resultsCount.textContent = data.count;
                            resultsSection.style.display = data.count > 0 ? 'block' : 'none';

                            // Clear previous results
                            resultsContainer.innerHTML = '';

                            // Display devices
                            if (data.devices.length > 0) {
                                data.devices.forEach(function(device) {
                                    const deviceCard = createDeviceCard(device);
                                    resultsContainer.innerHTML += deviceCard;
                                });

                                // Scroll to results
                                resultsSection.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            } else {
                                resultsContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning">No devices found matching your criteria.</div></div>';
                            }
                        } else {
                            alert('Error: ' + (data.error || 'Failed to fetch results'));
                        }
                    })
                    .catch(error => {
                        console.error('Full error details:', error);
                        alert('An error occurred while searching: ' + error.message + '\n\nPlease check the browser console for details.');
                    })
                    .finally(() => {
                        // Reset button state
                        findBtn.disabled = false;
                        findBtn.innerHTML = '<i class="fa fa-search me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Find Devices</span>';
                    });
            });
        });
    window.baseURL = '<?php echo $base; ?>';

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

    // Auto-Sliders moved to redesign/sliders.js

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, {
      passive: true
    });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
    });

    function closeMobileMenu() {
      hamburger.classList.remove('open');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Brand Strip Arrows ──
    const brandScroll = document.getElementById('brand-strip-scroll');
    document.getElementById('brand-strip-left').addEventListener('click', () => brandScroll.scrollBy({
      left: -300,
      behavior: 'smooth'
    }));
    document.getElementById('brand-strip-right').addEventListener('click', () => brandScroll.scrollBy({
      left: 300,
      behavior: 'smooth'
    }));

    // ── Live Search ──
    const searchInput = document.getElementById('da-search-input');
    const searchResults = document.getElementById('da-search-results');
    if (searchInput && searchResults) {
      let searchTimer;
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
          searchResults.classList.remove('open');
          return;
        }
        searchTimer = setTimeout(() => {
          Promise.all([
            fetch(baseURL + 'api_get_devices.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              devices: []
            })),
            fetch(baseURL + 'api_get_posts.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              posts: []
            }))
          ]).then(([devData, postData]) => {
            const devices = devData.devices || [];
            const posts = postData.posts || [];
            if (!devices.length && !posts.length) {
              searchResults.innerHTML = '<div class="da-search-result-item"><div class="sr-text">No results found</div></div>';
              searchResults.classList.add('open');
              return;
            }
            let html = '';
            devices.forEach(d => {
              html += `<a href="${baseURL}device/${encodeURIComponent(d.slug || d.id)}" class="da-search-result-item">
          ${d.image ? `<img src="${d.image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${d.name}</div><div class="sr-meta"><i class="fa fa-mobile-screen me-1"></i>${d.brand_name || 'Device'}</div></div>
        </a>`;
            });
            posts.forEach(p => {
              html += `<a href="${baseURL}post/${encodeURIComponent(p.slug)}" class="da-search-result-item">
          ${p.featured_image ? `<img src="${p.featured_image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${p.title}</div><div class="sr-meta"><i class="fa fa-newspaper me-1"></i>${p.created_at ? p.created_at.substring(0,10) : 'Article'}</div></div>
        </a>`;
            });
            searchResults.innerHTML = html;
            searchResults.classList.add('open');
          });
        }, 320);
      });
      document.addEventListener('click', (e) => {
        const wrap = document.getElementById('da-search-wrap');
        if (wrap && !wrap.contains(e.target)) searchResults.classList.remove('open');
      });
    }

    // ── Newsletter ──
    document.getElementById('da-newsletter-btn').addEventListener('click', function() {
      const email = document.getElementById('da-newsletter-email').value.trim();
      const msg = document.getElementById('da-newsletter-msg');
      if (!email) {
        msg.textContent = 'Please enter your email.';
        msg.className = 'error';
        return;
      }
      this.disabled = true;
      this.textContent = 'Subscribing...';
      const btn = this;
      fetch(baseURL + 'handle_newsletter.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'newsletter_email=' + encodeURIComponent(email)
        })
        .then(r => r.json())
        .then(data => {
          msg.textContent = data.message;
          msg.className = data.success ? 'success' : 'error';
          if (data.success) document.getElementById('da-newsletter-email').value = '';
          btn.disabled = false;
          btn.textContent = 'Subscribe';
        }).catch(() => {
          msg.textContent = 'An error occurred.';
          msg.className = 'error';
          btn.disabled = false;
          btn.textContent = 'Subscribe';
        });
    });

    // ── Notification mark seen ──
    function markNotificationsAsSeen() {
      const dots = ['notifDotDesktop'];
      dots.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });
      fetch(baseURL + 'notification_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=mark_seen'
      }).catch(() => {});
    }
    const bell = document.getElementById('notificationBellDesktop');
    if (bell) bell.addEventListener('click', () => setTimeout(markNotificationsAsSeen, 100));

    // ── Auth helpers ──
    function userAuthFetch(action, fd) {
      fd.append('action', action);
      return fetch(baseURL + 'user_auth_handler.php', {
        method: 'POST',
        body: fd
      }).then(r => r.json());
    }

    function showAuthMsg(id, msg, type) {
      const el = document.getElementById(id);
      el.className = 'alert alert-' + type + ' alert-dismissible fade show';
      el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
      el.style.display = 'block';
    }

    const loginForm = document.getElementById('publicLoginForm');
    if (loginForm) loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('loginSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Logging in...';
      userAuthFetch('login', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('login-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('login-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
        }
      }).catch(() => {
        showAuthMsg('login-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
      });
    });

    const signupForm = document.getElementById('publicSignupForm');
    if (signupForm) signupForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('signupSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Creating account...';
      userAuthFetch('register', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('signup-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('signup-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
        }
      }).catch(() => {
        showAuthMsg('signup-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
      });
    });

    function openProfileModal() {
      const modal = new bootstrap.Modal(document.getElementById('profileModal'));
      userAuthFetch('get_profile', new FormData()).then(data => {
        if (data.success && data.user) {
          document.getElementById('profile-name').value = data.user.name;
          document.getElementById('profile-email').value = data.user.email;
        }
      });
      document.getElementById('profile-current-password').value = '';
      document.getElementById('profile-new-password').value = '';
      document.getElementById('delete-account-password').value = '';
      document.getElementById('profile-message').style.display = 'none';
      modal.show();
    }

    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) profileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('profileUpdateBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving...';
      userAuthFetch('update_profile', new FormData(this)).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      });
    });

    function deletePublicAccount() {
      if (!confirm('Permanently delete your account? This cannot be undone.')) return;
      const pwd = document.getElementById('delete-account-password').value.trim();
      if (!pwd) {
        showAuthMsg('profile-message', 'Please enter your password.', 'warning');
        return;
      }
      const btn = document.getElementById('deleteAccountBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Deleting...';
      const fd = new FormData();
      fd.append('password', pwd);
      userAuthFetch('delete_account', fd).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        else {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
        }
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
      });
    }

    function publicUserLogout() {
      fetch(baseURL + 'notification_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'action=reset'
        })
        .finally(() => {
          userAuthFetch('logout', new FormData()).then(() => location.reload());
        });
    }

    function switchToSignup() {
      bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('signupModal')).show(), 300);
    }

    function switchToLogin() {
      bootstrap.Modal.getInstance(document.getElementById('signupModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('loginModal')).show(), 300);
    }
  </script>
  <script src="<?php echo $base; ?>redesign/sliders.js"></script>
</body>

</html>