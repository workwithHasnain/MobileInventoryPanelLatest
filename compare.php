<?php
require_once 'phone_data.php';
require_once 'database_functions.php';
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

// Get top comparisons (simulated for now)
$topComparisons = [
    [
        'device1_id' => 3,
        'device2_id' => 1,
        'comparison_count' => 45,
        'device1_name' => 'iPhone 15 Pro',
        'device2_name' => 'Galaxy S24',
        'device1_image' => 'uploads/device_1755632616_68a4d3e8945a8_1.png',
        'device2_image' => 'uploads/device_1755632662_68a4d416172aa_1.png'
    ],
    [
        'device1_id' => 6,
        'device2_id' => 5,
        'comparison_count' => 38,
        'device1_name' => 'OnePlus 12',
        'device2_name' => 'Xiaomi 14 Pro',
        'device1_image' => 'uploads/device_1755632707_68a4d4435da26_1.jpg',
        'device2_image' => 'uploads/phone_1755633457_68a4d7318f660_1.png'
    ],
    [
        'device1_id' => 7,
        'device2_id' => 8,
        'comparison_count' => 32,
        'device1_name' => 'Google Pixel 8 Pro',
        'device2_name' => 'Nothing Phone (2)',
        'device1_image' => '',
        'device2_image' => ''
    ],
];

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

// Get comments for posts
function getPostComments($post_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

// Get comments for devices
function getDeviceComments($device_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM device_comments WHERE device_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$device_id]);
    return $stmt->fetchAll();
}

// Handle comment submissions and newsletter subscriptions
$comment_success = '';
$comment_error = '';
$newsletter_success = '';
$newsletter_error = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'newsletter_subscribe') {
        $email = trim($_POST['newsletter_email'] ?? '');
        $name = trim($_POST['newsletter_name'] ?? '');

        if (empty($email)) {
            $newsletter_error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $newsletter_error = 'Please enter a valid email address.';
        } else {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $check_stmt->execute([$email]);

            if ($check_stmt->fetch()) {
                $newsletter_error = 'This email is already subscribed to our newsletter.';
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, status, subscribed_at) VALUES (?, ?, 'active', NOW())");
                if ($insert_stmt->execute([$email, $name])) {
                    $newsletter_success = 'Thank you for subscribing to our newsletter! You\'ll receive the latest tech updates and device reviews.';
                } else {
                    $newsletter_error = 'Failed to subscribe. Please try again.';
                }
            }
        }
    } else {
        // Handle comment submissions
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (empty($name) || empty($email) || empty($comment)) {
            $comment_error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $comment_error = 'Please enter a valid email address.';
        } elseif (strlen($comment) < 10) {
            $comment_error = 'Comment must be at least 10 characters long.';
        } else {
            if ($action === 'comment_post') {
                $post_id = $_POST['post_id'] ?? '';
                $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, name, email, comment, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                if ($stmt->execute([$post_id, $name, $email, $comment])) {
                    $comment_success = 'Your comment has been submitted and is pending approval.';
                } else {
                    $comment_error = 'Failed to submit comment. Please try again.';
                }
            } elseif ($action === 'comment_device') {
                $device_id = $_POST['device_id'] ?? '';
                $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                $stmt = $pdo->prepare("INSERT INTO device_comments (device_id, name, email, comment, parent_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                if ($stmt->execute([$device_id, $name, $email, $comment, $parent_id])) {
                    $comment_success = 'Your comment has been submitted and is pending approval.';
                } else {
                    $comment_error = 'Failed to submit comment. Please try again.';
                }
            }
        }
    }
}
// Public compare phones page - no authentication required

// Get all phones from database
$phones = getAllPhones();

// Get selected phone IDs from URL parameters
$phone1_id = isset($_GET['phone1']) ? $_GET['phone1'] : '';
$phone2_id = isset($_GET['phone2']) ? $_GET['phone2'] : '';
$phone3_id = isset($_GET['phone3']) ? $_GET['phone3'] : '';

// Handle device pre-selection from device page (device1, brand1 parameters)
if (isset($_GET['device1']) && ($phone1_id === '' || $phone1_id === null)) {
    $device_name = urldecode($_GET['device1']);
    $device_brand = isset($_GET['brand1']) ? urldecode($_GET['brand1']) : '';

    // Find the device in our phones array by name (and brand if provided)
    foreach ($phones as $phone) {
        $name_match = isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name));
        $brand_match = empty($device_brand) || (isset($phone['brand']) && strtolower(trim($phone['brand'])) === strtolower(trim($device_brand)));

        if ($name_match && $brand_match) {
            $phone1_id = $phone['id'];
            break;
        }
    }

    // If still not found and brand is empty, try searching by name only
    if (!$phone1_id && empty($device_brand)) {
        foreach ($phones as $phone) {
            if (isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name))) {
                $phone1_id = $phone['id'];
                break;
            }
        }
    }
}

// Helper function to find phone by ID
function findPhoneById($phones, $phoneId)
{
    if ($phoneId === '' || $phoneId === null || $phoneId === 'undefined' || $phoneId === '-1') {
        return null;
    }

    // First try to find by database ID
    foreach ($phones as $phone) {
        if (isset($phone['id']) && $phone['id'] == $phoneId) {
            return $phone;
        }
    }

    // Fallback: try to find by array index for backward compatibility
    if (is_numeric($phoneId)) {
        $index = (int)$phoneId;
        return (isset($phones[$index])) ? $phones[$index] : null;
    }

    return null;
}

// Get selected phones data
$phone1 = findPhoneById($phones, $phone1_id);
$phone2 = findPhoneById($phones, $phone2_id);
$phone3 = findPhoneById($phones, $phone3_id);

// Check if at least one phone is selected
$has_selection = ($phone1 !== null || $phone2 !== null || $phone3 !== null);

// Helper function to display availability
function displayAvailability($phone)
{
    if (isset($phone['availability'])) {
        if (is_array($phone['availability'])) {
            // Handle old format (array of checkboxes)
            $availability_options = [];
            foreach ($phone['availability'] as $option => $value) {
                if ($value) {
                    $availability_options[] = htmlspecialchars(ucfirst($option));
                }
            }
            return implode(', ', $availability_options);
        } else {
            // Handle new format (string from dropdown)
            return htmlspecialchars($phone['availability']);
        }
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to display network capabilities
function displayNetworkCapabilities($phone)
{
    $networks = [];
    if (isset($phone['network_2g']) && $phone['network_2g']) $networks[] = 'GSM';
    if (isset($phone['network_3g']) && $phone['network_3g']) $networks[] = 'HSPA';
    if (isset($phone['network_4g']) && $phone['network_4g']) $networks[] = 'LTE';
    if (isset($phone['network_5g']) && $phone['network_5g']) $networks[] = '5G';
    return !empty($networks) ? implode(' / ', $networks) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format dimensions
function formatDimensions($phone)
{
    if (isset($phone['dimensions_length']) && isset($phone['dimensions_width']) && isset($phone['dimensions_thickness'])) {
        return $phone['dimensions_length'] . ' x ' . $phone['dimensions_width'] . ' x ' . $phone['dimensions_thickness'] . ' mm';
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format weight
function formatWeight($phone)
{
    if (isset($phone['weight']) && $phone['weight']) {
        return $phone['weight'] . ' g';
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format announcement date
function formatAnnouncementDate($phone)
{
    if (isset($phone['release_date']) && $phone['release_date']) {
        $date = new DateTime($phone['release_date']);
        return 'Announced: ' . $date->format('Y, F j');
    }
    return '<span class="text-muted">Not announced</span>';
}

// Helper function to format OS
function formatOS($phone)
{
    if (isset($phone['os']) && $phone['os']) {
        return htmlspecialchars($phone['os']);
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format chipset
function formatChipset($phone)
{
    if (isset($phone['chipset_name']) && $phone['chipset_name']) {
        return htmlspecialchars($phone['chipset_name']);
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format main camera
function formatMainCamera($phone)
{
    $camera_parts = [];
    if (isset($phone['main_camera_resolution']) && $phone['main_camera_resolution']) {
        $camera_parts[] = $phone['main_camera_resolution'];
    }
    if (isset($phone['main_camera_ultrawide']) && $phone['main_camera_ultrawide']) {
        $camera_parts[] = 'ultrawide';
    }
    if (isset($phone['main_camera_telephoto']) && $phone['main_camera_telephoto']) {
        $camera_parts[] = 'telephoto';
    }
    if (isset($phone['main_camera_macro']) && $phone['main_camera_macro']) {
        $camera_parts[] = 'macro';
    }

    return !empty($camera_parts) ? implode(' + ', $camera_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format selfie camera
function formatSelfieCamera($phone)
{
    if (isset($phone['selfie_camera_resolution']) && $phone['selfie_camera_resolution']) {
        return htmlspecialchars($phone['selfie_camera_resolution']);
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format battery
function formatBattery($phone)
{
    $battery_parts = [];
    if (isset($phone['battery_capacity']) && $phone['battery_capacity']) {
        $battery_parts[] = $phone['battery_capacity'] . ' mAh';
    }
    if (isset($phone['charging_wired']) && $phone['charging_wired']) {
        $battery_parts[] = $phone['charging_wired'] . 'W charging';
    }

    return !empty($battery_parts) ? implode(', ', $battery_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format price
function formatPrice($phone)
{
    $price_parts = [];
    if (isset($phone['storage_internal']) && $phone['storage_internal']) {
        $price_parts[] = $phone['storage_internal'];
    }
    if (isset($phone['ram_internal']) && $phone['ram_internal']) {
        $price_parts[] = $phone['ram_internal'] . ' RAM';
    }

    $price_line = !empty($price_parts) ? implode(' ', $price_parts) : 'Standard variant';

    if (isset($phone['price']) && $phone['price']) {
        $price_line .= '<br>$' . number_format($phone['price'], 2);
    } else {
        $price_line .= '<br><span class="text-muted">Price not available</span>';
    }

    return $price_line;
}

// Helper function to get phone image
function getPhoneImage($phone)
{
    if (isset($phone['image']) && !empty($phone['image'])) {
        return htmlspecialchars($phone['image']);
    }
    // Default fallback image
    return 'imges/phone-placeholder.png';
}

// Helper function to get phone name with brand
function getPhoneName($phone)
{
    if (!$phone) return 'Select a device';

    $name = '';
    if (isset($phone['brand_name']) && $phone['brand_name']) {
        $name = $phone['brand_name'] . ' ';
    }
    if (isset($phone['name']) && $phone['name']) {
        $name .= $phone['name'];
    }

    return !empty($name) ? htmlspecialchars($name) : 'Unknown Device';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GSMArena</title>
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

    <!-- Select2 for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #EFEBE9;">
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

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Instagram">
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
                <a href="reviews.php" class="nav-link ">Reviews</a>
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
    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-md-8 col-5  d-lg-inline d-none ">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="imges/magnifient sectton.jpeg"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
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
    <div class="comparison-container container bg-white">
        <div class="row">
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <select id="phone1-select" name="phone1" class="bg-white w-100 mx-2 text-center-auto border phone-search-select" onchange="updateComparison(1, this.value)">
                            <option value="">Select Phone 1</option>
                            <?php foreach ($phones as $phone): ?>
                                <option value="<?php echo $phone['id']; ?>" <?php echo ($phone1 && $phone1['id'] == $phone['id']) ? 'selected' : ''; ?>>
                                    <?php echo getPhoneName($phone); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <?php if ($phone1): ?>
                    <div class="phone-name"><?php echo getPhoneName($phone1); ?></div>
                    <div class="d-flex">
                        <img src="<?php echo getPhoneImage($phone1); ?>" alt="<?php echo getPhoneName($phone1); ?>">
                        <div class="buttons">
                            <button onclick="window.location.href='device.php?id=<?php echo $phone1['id']; ?>'">REVIEW</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone1['id']; ?>'">SPECIFICATIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone1['id']; ?>#comments'">READ OPINIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone1['id']; ?>'">PICTURES</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="phone-name">Select a device to compare</div>
                    <div class="d-flex">
                        <img src="imges/phone-placeholder.png" alt="No phone selected">
                        <div class="buttons">
                            <button disabled>REVIEW</button>
                            <button disabled>SPECIFICATIONS</button>
                            <button disabled>READ OPINIONS</button>
                            <button disabled>PICTURES</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <select id="phone2-select" name="phone2" class="bg-white w-100 mx-2 text-center-auto border phone-search-select" onchange="updateComparison(2, this.value)">
                            <option value="">Select Phone 2</option>
                            <?php foreach ($phones as $phone): ?>
                                <option value="<?php echo $phone['id']; ?>" <?php echo ($phone2 && $phone2['id'] == $phone['id']) ? 'selected' : ''; ?>>
                                    <?php echo getPhoneName($phone); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <?php if ($phone2): ?>
                    <div class="phone-name"><?php echo getPhoneName($phone2); ?></div>
                    <div class="d-flex">
                        <img src="<?php echo getPhoneImage($phone2); ?>" alt="<?php echo getPhoneName($phone2); ?>">
                        <div class="buttons">
                            <button onclick="window.location.href='device.php?id=<?php echo $phone2['id']; ?>'">REVIEW</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone2['id']; ?>'">SPECIFICATIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone2['id']; ?>#comments'">READ OPINIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone2['id']; ?>'">PICTURES</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="phone-name">Select a device to compare</div>
                    <div class="d-flex">
                        <img src="imges/phone-placeholder.png" alt="No phone selected">
                        <div class="buttons">
                            <button disabled>REVIEW</button>
                            <button disabled>SPECIFICATIONS</button>
                            <button disabled>READ OPINIONS</button>
                            <button disabled>PICTURES</button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="align-items-center m-auto">
                </div>
            </div>
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <select id="phone3-select" name="phone3" class="bg-white w-100 mx-2 text-center-auto border phone-search-select" onchange="updateComparison(3, this.value)">
                            <option value="">Select Phone 3</option>
                            <?php foreach ($phones as $phone): ?>
                                <option value="<?php echo $phone['id']; ?>" <?php echo ($phone3 && $phone3['id'] == $phone['id']) ? 'selected' : ''; ?>>
                                    <?php echo getPhoneName($phone); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <?php if ($phone3): ?>
                    <div class="phone-name"><?php echo getPhoneName($phone3); ?></div>
                    <div class="d-flex">
                        <img src="<?php echo getPhoneImage($phone3); ?>" alt="<?php echo getPhoneName($phone3); ?>">
                        <div class="buttons">
                            <button onclick="window.location.href='device.php?id=<?php echo $phone3['id']; ?>'">REVIEW</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone3['id']; ?>'">SPECIFICATIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone3['id']; ?>#comments'">READ OPINIONS</button>
                            <button onclick="window.location.href='device.php?id=<?php echo $phone3['id']; ?>'">PICTURES</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="phone-name">Select a device to compare</div>
                    <div class="d-flex">
                        <img src="imges/phone-placeholder.png" alt="No phone selected">
                        <div class="buttons">
                            <button disabled>REVIEW</button>
                            <button disabled>SPECIFICATIONS</button>
                            <button disabled>READ OPINIONS</button>
                            <button disabled>PICTURES</button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="align-items-center m-auto">
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php echo $phone1 ? getPhoneName($phone1) : 'Select Phone 1'; ?></th>
                    <th><?php echo $phone2 ? getPhoneName($phone2) : 'Select Phone 2'; ?></th>
                    <th><?php echo $phone3 ? getPhoneName($phone3) : 'Select Phone 3'; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" style="color: #f14d4d; font-size: 16px; background: #f9f9f9; font-weight: 600;">Network Technology</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? displayNetworkCapabilities($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? displayNetworkCapabilities($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? displayNetworkCapabilities($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="color: #f14d4d; font-size: 16px; background: #f9f9f9; font-weight: 600;">Announcement Date</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatAnnouncementDate($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatAnnouncementDate($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatAnnouncementDate($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Availability / Status</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? displayAvailability($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? displayAvailability($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? displayAvailability($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Dimensions</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatDimensions($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatDimensions($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatDimensions($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Weight</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatWeight($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatWeight($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatWeight($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Operating System (OS)</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatOS($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatOS($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatOS($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Chipset</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatChipset($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatChipset($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatChipset($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Main Camera</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatMainCamera($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatMainCamera($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatMainCamera($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Selfie Camera</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatSelfieCamera($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatSelfieCamera($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatSelfieCamera($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Battery</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatBattery($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatBattery($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatBattery($phone3) : 'N/A'; ?></td>
                </tr>

                <tr>
                    <td colspan="3" style="font-weight: 600; color: #f14d4d; font-size: 16px; background: #f9f9f9;">Price</td>
                </tr>
                <tr>
                    <td><?php echo $phone1 ? formatPrice($phone1) : 'N/A'; ?></td>
                    <td><?php echo $phone2 ? formatPrice($phone2) : 'N/A'; ?></td>
                    <td><?php echo $phone3 ? formatPrice($phone3) : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>



    </div>

    <div id="bottom" class="container d-flex mt-3" style="max-width: 1034px;">
        <div class="row align-items-center">
            <div class="col-md-2 m-auto col-4 d-flex justify-content-center align-items-center "> <img
                    src="https://fdn2.gsmarena.com/w/css/logo-gsmarena-com.png" alt="">
            </div>
            <div class="col-10 nav-wrap m-auto text-center ">
                <div class="nav-container">
                    <a href="index.php">Home</a>
                    <a href="reviews.php">Reviews</a>
                    <a href="videos.php">Videos</a>
                    <a href="featured.php">Featured</a>
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
                    <a href="#">Contact us</a>
                    <a href="#">Merch store</a>
                    <a href="#">Privacy</a>
                    <a href="#">Terms of use</a>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for searchable dropdowns
            $('.phone-search-select').select2({
                placeholder: 'Search and select a phone...',
                allowClear: true,
                width: '100%',
                theme: 'default',
                dropdownAutoWidth: true,
                containerCssClass: 'phone-select-container',
                dropdownCssClass: 'phone-select-dropdown'
            });

            // Custom onChange handler for Select2
            $('.phone-search-select').on('select2:select select2:clear', function(e) {
                const phoneNumber = this.id.replace('phone', '').replace('-select', '');
                const phoneId = $(this).val() || '';
                updateComparison(phoneNumber, phoneId);
            });
        });

        function updateComparison(phoneNumber, phoneId) {
            // Build new URL with updated phone parameter
            const url = new URL(window.location);

            if (phoneId === '') {
                // Remove the parameter if no phone selected
                url.searchParams.delete('phone' + phoneNumber);
            } else {
                // Set or update the parameter
                url.searchParams.set('phone' + phoneNumber, phoneId);
            }

            // Redirect to the new URL
            window.location.href = url.toString();
        }
    </script>
    <style>
        /* Custom styles for Select2 phone selection */
        .phone-select-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .phone-select-container .select2-selection__rendered {
            line-height: 36px;
            padding-left: 8px;
        }

        .phone-select-container .select2-selection__arrow {
            height: 36px;
        }

        .phone-select-dropdown {
            z-index: 9999;
        }

        .phone-select-dropdown .select2-results__option {
            padding: 8px 12px;
        }

        .phone-select-dropdown .select2-results__option--highlighted {
            background-color: #007bff;
            color: white;
        }
    </style>
</body>

</html>