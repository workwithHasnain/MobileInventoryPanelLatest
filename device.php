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
    // Try JSON files first (primary data source for now)
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

    // Fallback to database if JSON fails
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

        return $device;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
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

// Track view
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
trackDeviceView($pdo, $device_id, $ip_address);

// Get comments
$comments = getDeviceComments($pdo, $device_id);

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
  <title>GSMArena Single Device Page</title>
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

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="WiFi">
              <i class="fa-solid fa-wifi fa-lg" style="color: #ffffff;"></i>
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Car">
              <i class="fa-solid fa-car fa-lg" style="color: #ffffff;"></i>
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Cart">
              <i class="fa-solid fa-cart-shopping fa-lg" style="color: #ffffff;"></i>
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

        <div>
          <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Login">
            <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
          </button>

          <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Register">
            <i class="fa-solid fa-user-plus fa-lg" style="color: #ffffff;"></i>
          </button>
        </div>
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
      <a href="news.php">News</a>
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
        <a href="news.php" class="nav-link d-lg-block d-none">News</a>
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
  </style>


  <div class="d-lg-none d-block">
    <div class="card" role="region" aria-label="Vivo V60 Phone Info">

      <div class="article-info">
        <div class="bg-blur">
          <p class="vr-hide"
            style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
            vivo V60
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
    padding: 12px;" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-v60.jpg" alt="vivo V60 phone image" />
        </div>

        <!-- Right: Details + Stats + Specs -->
        <div class="flex-grow-1 position-relative" style="z-index: 100;">

          <!-- Phone Details + Stats -->
          <div class="d-flex justify-content-between mb-3">

            <ul class="phone-details d-lg-block d-none list-unstyled mb-0">
              <li><span>📅 Released 2025, August 19</span></li>
              <li><span>⚖️ 192g or 201g, 7.5mm thickness</span></li>
              <li><span>🆔 Android 15, up to 4 major upgrades</span></li>
              <li><span>💾 128GB/256GB/512GB storage, no card slot</span></li>
            </ul>

            <div class="d-flex stats-bar text-center">
              <div class="stat-item">
                <div>53%</div>
                <div class="stat-label">605,568 HITS</div>
              </div>
              <div class="stat-item">
                <div> <i class="fa-solid fa-heart fa-md" style="color: #ffffff;"></i> 80</div>
                <div class="stat-label">BECOME A FAN</div>
              </div>
            </div>
          </div>

          <!-- Specs Row (aligned with image) -->
           <div class="row text-center d-block g-0  pt-2 specs-bar">
                <div class="col-3 spec-item">
                  <img src="imges/vrer.png" style="width: 25px;" alt="">

                  <div class="spec-title"> 6.77"</div>
                  <div class="spec-subtitle">1080x2392 px</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/bett-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">50MP</div>
                  <div class="spec-subtitle">2160p</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/encypt-removebg-preview.png" style="width: 38px;" alt="">

                  <div class="spec-title">8-16GB</div>
                  <div class="spec-subtitle">Snapdragon 7</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/lowtry-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">6500mAh</div>
                  <div class="spec-subtitle">90W</div>
                </div>
              </div>

        </div>
      </div>
      <div class="article-info">
        <div class="bg-blur">
          <div class="d-lg-none d-block justify-content-end">
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">Review (17)
              </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">OPINION </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">PICTURES </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2">PRICES</h5>
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
        <div class="card" role="region" aria-label="Vivo V60 Phone Info">

          <div class="article-info">
            <div class="bg-blur">
              <p class="vr-hide"
                style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
                vivo V60
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
    padding: 12px;" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-v60.jpg" alt="vivo V60 phone image" />
            </div>

            <!-- Right: Details + Stats + Specs -->
            <div class="flex-grow-1 position-relative" style="z-index: 100;">

              <!-- Phone Details + Stats -->
              <div class="d-flex justify-content-between mb-3">

                <ul class="phone-details list-unstyled mb-0 d-lg-block d-none">
                  <li><span>📅 Released 2025, August 19</span></li>
                  <li><span>⚖️ 192g or 201g, 7.5mm thickness</span></li>
                  <li><span>🆔 Android 15, up to 4 major upgrades</span></li>
                  <li><span>💾 128GB/256GB/512GB storage, no card slot</span></li>
                </ul>

                <div class="d-flex stats-bar text-center">
                  <div class="stat-item">
                    <div>53%</div>
                    <div class="stat-label">605,568 HITS</div>
                  </div>
                  <div class="stat-item">
                    <div> <i class="fa-solid fa-heart fa-md" style="color: #ffffff;"></i> 80</div>
                    <div class="stat-label">BECOME A FAN</div>
                  </div>
                </div>
              </div>

              <!-- Specs Row (aligned with image) -->
              <div class="row text-center g-0  pt-2 specs-bar">
                <div class="col-3 spec-item">
                  <img src="imges/vrer.png" style="width: 25px;" alt="">

                  <div class="spec-title"> 6.77"</div>
                  <div class="spec-subtitle">1080x2392 px</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/bett-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">50MP</div>
                  <div class="spec-subtitle">2160p</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/encypt-removebg-preview.png" style="width: 38px;" alt="">

                  <div class="spec-title">8-16GB</div>
                  <div class="spec-subtitle">Snapdragon 7</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/lowtry-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">6500mAh</div>
                  <div class="spec-subtitle">90W</div>
                </div>
              </div>

            </div>
          </div>
          <div class="article-info">
            <div class="bg-blur">
              <div class="d-flex justify-content-end">
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
              <tr>
                <th class="spec-label">NETWORK</th>
                <td><strong>Technology</strong> GSM / HSPA / LTE / 5G</td>
              </tr>
              <tr>
                <th class="spec-label">LAUNCH</th>
                <td>
                  <strong>Announced</strong> 2025, August 12<br>
                  <strong>Status</strong> Available. Released 2025, August 19
                </td>
              </tr>
              <tr>
                <th class="spec-label">DISPLAY</th>
                <td>
                  <strong>Type</strong> AMOLED, 1B colors, HDR10+, 120Hz, 1500 nits (HBM), 5000 nits (peak)<br>
                  <strong>Size</strong> 6.77 inches, 110.9 cm<sup>2</sup> (~88.1% screen-to-body ratio)<br>
                  <strong>Resolution</strong> 1080 x 2392 pixels (~388 ppi density)<br>
                  <strong>Protection</strong> Schott Xensation Core
                </td>
              </tr>
              <tr>
                <th class="spec-label">PLATFORM</th>
                <td>
                  <strong>OS</strong> Android 15, up to 4 major Android upgrades, Funtouch 15<br>
                  <strong>Chipset</strong> Qualcomm SM7750-AB Snapdragon 7 Gen 4 (4 nm)<br>
                  <strong>CPU</strong> Octa-core (1x2.8 GHz Cortex-720 & 4x2.4 GHz Cortex-720 & 3x1.8 GHz
                  Cortex-520)<br>
                  <strong>GPU</strong> Adreno 722
                </td>
              </tr>
              <tr>
                <th class="spec-label">MEMORY</th>
                <td>
                  <strong>Card slot</strong> No<br>
                  <strong>Internal</strong> 128GB 8GB RAM, 256GB 8GB RAM, 256GB 12GB RAM, 512GB 12GB RAM, 512GB 16GB
                  RAM<br>
                  UFS 2.2
                </td>
              </tr>
              <tr>
                <th class="spec-label">MAIN CAMERA</th>
                <td>
                  <strong>Triple</strong><br>
                  50 MP, f/1.9, 23mm (wide), 1/1.56", 1.0µm, PDAF, OIS<br>
                  50 MP, f/2.7, 73mm (periscope telephoto), 1/1.95", 0.8µm, PDAF, OIS, 3x optical zoom<br>
                  8 MP, f/2.0, 15mm, 120° (ultrawide)<br>
                  <strong>Features</strong> Zeiss optics, Ring-LED flash, panorama, HDR<br>
                  <strong>Video</strong> 4K@30fps, 1080p@30fps, gyro-EIS, OIS
                </td>
              </tr>
              <tr>
                <th class="spec-label">SELFIE CAMERA</th>
                <td>
                  <strong>Single</strong> 50 MP, f/2.2, 21mm (wide), 1/2.76", 0.64µm, AF<br>
                  <strong>Features</strong> Zeiss optics, HDR<br>
                  <strong>Video</strong> 4K@30fps, 1080p@30fps
                </td>
              </tr>
              <tr>
                <th class="spec-label">SOUND</th>
                <td>
                  <strong>Loudspeaker</strong> Yes, with stereo speakers<br>
                  <!-- <strong>3.5mm jack</strong> No -->
                </td>
              </tr>
              <tr>
                <th class="spec-label">COMS</th>
                <td>
                  <strong>WLAN</strong> Wi-Fi 802.11 a/b/g/n/ac, dual-band<br>
                  <strong>bluetooth</strong>5.4, A2DP, LE<br>
                  <strong>Positioning </strong>GPS, GALILEO, GLONASS, QZSS, BDS, NavIC<br>
                  <strong>NFC </strong>Yes<br>

                  <strong>Radio </strong>No<br>
                  <strong>USB </strong>USB Type-C 2.0, OTG<br>
                </td>
              </tr>
              <tr>
                <th class="spec-label">TESTS</th>
                <td> <STRong>loudspeaker</STRong> -24.7 LUFS (Very good)</td> <br>
                <!-- <STRong>3.5mm jack</STRong>No -->
              </tr>
              <tr>
                <th class="spec-label">SELFIE CAMERA</th>
                <td> <STRong>Single</STRong> 50 MP, f/2.2, 21mm (wide), 1/2.76", 0.64µm, AF</td>
                <!-- <STRong>Features</STRong> Zeiss optics, HDR <br> -->
                <!-- <STRong>Video</STRong> 4K@30fps, 1080p@30fps <br> -->
              </tr>
              <tr>
                <th class="spec-label">Battery</th>
                <td> <strong>Type</strong> Si/C Li-Ion 6500 mAh</td>
              </tr>
            </tbody>
          </table>

          <p style="font-size: 13px;
    text-transform: capitalize;
    padding: 6px 19px;"> <strong>Disclaimer:</strong>We can not guarantee that the information on this page is 100%
            correct.</p>

          <div class="d-block d-lg-flex">  <button
              class="pad">COMPARE</button> 
          </div>
          
          <div class="comments">
            <h5 class="border-bottom reader  py-3 mx-2">vivo V60 - user opinions and reviews</h5>
            <div class="first-user" style="background-color: #EDEEEE;">
              <div class="user-thread">
                <div class="uavatar">
                  <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50" alt="">
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">XNA</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>7 hours ago</time></li>

                </ul>
                <p class="uopin">ofc it does, samsung sells phones in every price range</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>


              </div>
              <div class="user-thread">
                <div class="uavatar">
                  <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50" alt="">
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">nyc</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>15 Minates ago</time>
                  </li>

                </ul>
                <p class="uopin">what's your point?</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>
              </div>
              <div class="user-thread">
                <div class="uavatar">
                  <span class="avatar-box">D</span>
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">QNA</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>14 hours ago</time>
                  </li>

                </ul>
                <p class="uopin">There are other phone brands bro... Lower the fanboy speak a bit..</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>
              </div>
              <div class="button-secondary-div d-flex justify-content-between align-items-center ">
                <div class="d-flex">
                  <button class="button-links">post your opinion</button>
                </div>
                <p class="div-last">Total reader comments: <b>34</b> </p>
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
          <a href="#">News</a>
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
  tooltipTriggerList.map(function (tooltipTriggerEl) {
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