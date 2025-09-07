<?php
// Public home page - no authentication required
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
    <script>

    </script>

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

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="WiFi">
                            <i class="fa-solid fa-wifi fa-lg" style="color: #ffffff;"></i>
                        </button>

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Car">
                            <i class="fa-solid fa-car fa-lg" style="color: #ffffff;"></i>
                        </button>

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Cart">
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
                    <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Login">
                        <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
                    </button>

                    <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Register">
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

    <div class="container featured ">
        <h2 class="section">Featured</h2>
        <div class="featured-section">

            <div class="div-block">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/sony-wh-1000-xm6-review/-184x111/gsmarena_100.jpg"
                    alt="">
                <h3 class="sony-tv">Sony WH-1000XM6 headphones review</h3>
            </div>
            <div class="div-block ">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/galaxy-watch8-ifr/-184x111/gsmarena_000.jpg"
                    alt="">
                <h3 class="sony-tv">Samsung Galaxy Watch8 in for review</h3>
            </div>
            <div class="div-block ">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/vivo-x-fold5-ifr/-184x111/gsmarena_000.jpg"
                    alt="">
                <h3 class="sony-tv">vivo X Fold5 in for review</h3>
            </div>
            <div class="div-block ">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/weekly-poll-samsung-galaxy-zfold7-zflip7-zflip7fe/-184x111/gsmarena_000.jpg"
                    alt="">
                <h3 class="sony-tv">Weekly poll: Samsung Galaxy Z Fold7, Z Flip7 or Z Flip7 FE?</h3>
            </div>
            <div class="div-block ">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/galaxy-watch8-classic-ifr/-184x111/gsmarena_000.jpg"
                    alt="">
                <h3 class="sony-tv">Samsung Galaxy Watch8 Classic in for review</h3>
            </div>
            <div class="div-block ">
                <img src="https://fdn.gsmarena.com/imgroot/news/25/07/samsung-galaxy-z-flip7-ifr/-184x111/gsmarena_000.jpg"
                    alt="">
                <h3 class="sony-tv">Samsung Galaxy Z Flip7 in for review</h3>
            </div>
        </div>
    </div>
    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-lg-4 col-6 conjection-froud  bobile">
                <div class="review-column-list-item review-column-list-item-secondary ">
                    <img class="review-list-item-image "
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-moto-g-stylus-2025/-347x151/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G (2025) review">
                    <h1>Mooto G Stylus 5G (2025) review</h1>
                    <img class="review-list-item-image"
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/google-pixel-9a/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a review">
                    <h1>Google pIxel 9a Review</h1>
                </div>
            </div>
            <div class="col-6 col-lg-4 conjection-froud " style="margin-left: 7px;">
                <div class="comfort d-md-none d-block">
                    <div class="conjection position-absolute mx-2  my-2 ">
                        <i class="fa-solid fa-clock fa-sm" style="color: white;"></i>
                        <span clas s="text-white font-bold pb-5" style="font-size: 13px;">8 may 2025 </span>
                    </div>
                    <div class="conjection position-absolute  mx-3 my-2 end-0">
                        <i class="fa-solid fa-comment fa-sm" style="color: white;"></i>
                        <span class="text-white ">80</span>
                    </div>
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/nothing-cmf-phone-2-pro/-728x314/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <h1 class="position-absolute">Nothing CMF Phone 2 Pro review</h1>
                </div>
                <div class="review-column-list-item review-column-list-item-secondary">
                    <img class="review-list-item-image "
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-moto-g-stylus-2025/-347x151/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G (2025) review">

                    <h1>Mooto G Stylus 5G (2025) review</h1>
                    <img class="review-list-item-image"
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/google-pixel-9a/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a review">
                    <h1>Google pIxel 9a Review</h1>
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 40px;">
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
                <a href="brands.php">
                    <button class="solid w-50 py-2">
                        <i class="fa-solid fa-bars fa-sm mx-2"></i>
                        All Brands</button></a>
                <button class="solid py-2" style="    width: 177px;">
                    <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                    RUMORS MILL</button>
            </div>
        </div>
        
    </div>
    <div class="container mt-0 varasat">
        <div class="row">
            <div class="rena w-100">
                <h1 class=" d-flex align-items-center justify-content-start warently  text-center m-auto">SmartPhone
                    Buyer's Guide
                    <i class="fa-solid fa-bell fa-lg  d-flex justify-content-end align-items-end m-auto px-auto"
                        style="color: #8c8c8c;">
                    </i>
                </h1>
                <p class="d-none d-md-inline">The Cheat Sheet To The Best Phones to Get Right Now</p>
            </div>
        </div>
    </div>
    <div class="container mt-0 war ">
        <div class="row">
            <div class="col-lg-4 col-md-6 col-12 sentizer-erx    " style="background-color: #EEEEEE;">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/honor-magic-v5-thickness-rumor/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">The Honor Magic V5 </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12 sentizer-er" style="background-color: #EEEEEE;">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/google-pixel-9a/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a">
                    <div class="review-card-body">
                        <div class="review-card-title">Google Pixel 9a review</div>
                        <div class="review-card-meta">
                            <span>04 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>28 comments</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4  col-12 sentizer-er  bg-white p-3">
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
                <h6 style="border-left: solid 5px grey ; font-weight: 900; color: #090E21; text-transform: uppercase;"
                    class="px-3 py-2 mt-3">Electric Vehicles</h6>
                <div class="d-flex">
                    <div class="col-md-4">
                        <img style="height: 60px;"
                            src="https://st.arenaev.com/news/25/05/mercedes-amg-ev-sedan-teaser-images/-344x215/arenaev_000.jpg"
                            class="img-fluid rounded" alt="News Image">
                    </div>
                    <div class="col-md-8 py-2">
                        <p class="fw-bold mb-1 wanted-12">
                            mercedes amg starts teasing its first ev sedan here are the pictures news
                        </p>
                    </div>
                </div>
                <div class="d-flex my-3">
                    <div class="col-md-4">
                        <img style="height: 60px;"
                            src="https://st.arenaev.com/news/25/05/xiaomi-signs-partnership-deal-with-nurburgring/-344x215/arenaev_001.jpg"
                            class="img-fluid rounded" alt="News Image">
                    </div>
                    <div class="col-md-8 py-2">
                        <p class="fw-bold mb-1 wanted-12">
                            Xiomo Sign Partnership agreement with nurbugging
                        </p>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="col-md-4">
                        <img style="height: 60px;"
                            src="https://st.arenaev.com/news/25/05/li-auto-refreshes-electric-suv-lineup-with-tech-boost/-344x215/arenaev_001.jpg"
                            class="img-fluid rounded" alt="News Image">
                    </div>
                    <div class="col-md-8 py-2">
                        <p class="fw-bold mb-1 wanted-12">
                            Li auto refreshes electric suv lineup with tech boost keep prices steady news
                        </p>
                    </div>
                </div>
                <div class="d-flex my-3">
                    <div class="col-md-4">
                        <img style="height: 60px;"
                            src="https://st.arenaev.com/news/25/01/polestar-3-triumphs-in-winter-range-test/-344x215/arenaev_001.jpg"
                            class="img-fluid rounded" alt="News Image">
                    </div>
                    <div class="col-md-8 py-2">
                        <p class="fw-bold mb-1 wanted-12">
                            polestar 3 triumphs in winter range test news
                        </p>

                    </div>
                </div>

                <!-- <div style="position: sticky; top: 10px;">
                    <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-300x250.jpg"
                        class=" d-block mx-auto" style="width: 300px;">
                </div> -->

            </div>

        </div>
    </div>
    <div id="bottom" class="container d-flex py-3" style="max-width: 1034px;">
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
                    document.getElementById('postModalBody').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('postModal')).show();
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
    </script>
    <script src="script.js"></script>


</body>

</html>