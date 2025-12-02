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

// Get top comparisons from database
try {
    $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
    $topComparisons = [];
}

// Get latest 9 devices for the new section
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 9); // Get latest 9 devices

$brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
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

    <link rel="stylesheet" href="style.css">
    <style>
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
                    <a href="#" class="brand-cell brand-item-bold" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach;
            endforeach; ?>
            <a href="#" onclick="showBrandsModal(); return false;" style="cursor: pointer;">[...]</a>
        </div>
        <div class="menu-buttons d-flex justify-content-center ">
            <button class="btn btn-primary w-50 text-white">📱 Phone Finder</button>
            <button class="btn btn-primary w-50 text-white">📲 My Phone</button>
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

    <div class="container featured margin-top-4rem">
        <h2 class="section">Featured</h2>
        <div class="featured-section">
            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Featured Posts Available</h4>
                    <p class="text-muted">Check back later for new content!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="div-block" style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'">
                        <?php if (!empty($post['featured_image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Featured Image" style="height: 111px; width: 189px; cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'">
                        <?php endif; ?>
                        <h3 class="sony-tv" style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'"><?php echo htmlspecialchars($post['title']); ?></h3>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <?php
            // Show up to 4 featured posts in two columns, 2 per column
            $featuredPreview = array_slice($posts, 0, 4);
            $chunks = array_chunk($featuredPreview, 2);
            foreach ($chunks as $colIndex => $colPosts):
            ?>
                <div class="<?php echo $colIndex === 0 || $colIndex === 2 ? 'col-lg-4 col-6 conjection-froud  bobile' : 'col-6 col-lg-4 conjection-froud'; ?>" <?php echo $colIndex === 1 ? ' style="margin-left: 7px;"' : ''; ?>>
                    <div class="review-column-list-item review-column-list-item-secondary " style="cursor:pointer;">
                        <?php foreach ($colPosts as $post): ?>
                            <?php if (!empty($post['featured_image'])): ?>
                                <img class="review-list-item-image" src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'">
                            <?php endif; ?>
                            <h1 style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 28px;">
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
                    <button class="solid w-50 py-2" style="width: 177px;">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>
                </div>
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
            <div class="container mt-0 war ">
                <div class="row">
                    <?php
                    $maxPosts = count($posts);
                    $chunkSize = max(1, ceil($maxPosts / 2));
                    $postChunks = !empty($posts) ? array_chunk(array_slice($posts, 0, $maxPosts), $chunkSize) : [];
                    foreach ($postChunks as $colIndex => $colPosts):
                    ?>
                        <div class="col-lg-4 col-md-6 col-12 sentizer-erx" style="background-color: #EEEEEE;">
                            <?php foreach ($colPosts as $post): ?>
                                <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                    <div class="review-card mb-4" style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'">
                                        <?php if (isset($post['featured_image']) && !empty($post['featured_image'])): ?>
                                            <img style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'" src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php endif; ?>
                                        <div class="review-card-body">
                                            <div style="cursor:pointer;" onclick="window.location.href='post.php?slug=<?php echo urlencode($post['slug']); ?>'" class="review-card-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                            <div class="review-card-meta">
                                                <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                                <span><i class="bi bi-chat-dots-fill"></i><?php echo $post['comment_count']; ?> comments</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

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
                                            <div class="device-card canel<?php echo $i == 1 ? ' mx-4' : ($i == 0 ? '' : ''); ?>" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer; text-align: center;">
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

                        <div class="sentizer bg-white mt-2 p-3 rounded " style="    text-transform: Uppercase;
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
    <div id="bottom" class="container d-flex py-3" style="max-width: 1034px;">
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
    <script src="script.js"></script>


</body>

</html>