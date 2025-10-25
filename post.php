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

// Get post by slug or ID
$slug = $_GET['slug'] ?? $_GET['id'] ?? null;

if (!$slug) {
    header('Location: index.php');
    exit;
}

// Try to get post by slug first, then by ID if it's numeric
if (is_numeric($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE (slug = ? OR id = ?) AND status ILIKE 'published'");
    $stmt->execute([$slug, intval($slug)]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status ILIKE 'published'");
    $stmt->execute([$slug]);
}
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get post comments and comment count
$postComments = getPostComments($post['id']);
$postCommentCount = getPostCommentCount($post['id']);

// Track view for this post (one per IP per day)
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('post', CAST(? AS VARCHAR), ?, ?) ON CONFLICT (content_type, content_id, ip_address) DO NOTHING");
    $view_stmt->execute([$post['id'], $user_ip, $user_agent]);

    // Update view count in posts table
    $update_view_stmt = $pdo->prepare("UPDATE posts SET view_count = (SELECT COUNT(*) FROM content_views WHERE content_type = 'post' AND content_id = CAST(? AS VARCHAR)) WHERE id = ?");
    $update_view_stmt->execute([$post['id'], $post['id']]);
} catch (Exception $e) {
    // Silently ignore view tracking errors
}

// Get comments for posts
function getPostComments($post_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

// Get post comment count
function getPostCommentCount($post_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_comments WHERE post_id = ? AND status = 'approved'");
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
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
        $initials = strtoupper(substr($name, 0, 1));
        $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c'];
        $color = $colors[abs(crc32($name)) % count($colors)];
        return '<span class="avatar-box" style="background-color: ' . $color . '; color: white;">' . $initials . '</span>';
    }
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
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr($post['content_body'], 0, 160) . '...'); ?>" />
    <title><?php echo htmlspecialchars($post['meta_title'] ?? $post['title']); ?> - GSMArena</title>
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

    <style>
        /* Avatar and Comment Styles */
        .avatar-box {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .uavatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .document-section .classy {
            color: #333;
            line-height: 1.6;
        }

        .media-gallery img {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
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

        .comment-form .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* Heading jump controls */
        .heading-jump .form-select {
            border-radius: 999px;
            padding: 4px 12px;
            height: 34px;
            font-size: 0.95rem;
            border: 1px solid #e0e0e0;
            background-color: #fff;
        }

        .heading-nav-btn {
            background-color: #f1f3f5;
            border: 1px solid #e0e0e0;
            color: #090E21;
            border-radius: 999px;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .heading-nav-btn:hover {
            background-color: #e9ecef;
            border-color: #d5d5d5;
        }

        .heading-nav-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
    </style>
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

    <div class=" mt-4 d-lg-none d-block bg-white">
        <h3 style="font-size: 23px;
        font-weight: 600; font-family: 'oswald';" class="mx-3 my-5"><?php echo htmlspecialchars($post['title']); ?></h3>
        <?php if (!empty($post['featured_image'])): ?>
            <img style="height: 100%; width: -webkit-fill-available;" src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
        <?php else: ?>
            <img style="height: 100%; width: -webkit-fill-available;" src="/imges/ever1.jpg" alt="">
        <?php endif; ?>
    </div>
    <div class="container support content-wrapper" id="Top">
        <div class="row">

            <div class="col-md-8 col-5 d-md-inline  " style="border: 1px solid #e0e0e0;">
                <div class="comfort-life-23 position-absolute d-flex justify-content-between  ">
                    <div class="article-info">
                        <div class="bg-blur">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img class="center-img" data-src="<?php echo htmlspecialchars($post['featured_image']); ?>" src="" alt="<?php echo htmlspecialchars($post['title']); ?>" style="visibility: hidden;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="    display: flex;  flex-direction: column;">
                        <h1 class="article-info-name"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <div class="article-info">
                            <div class="bg-blur">
                                <div class="d-flex justify-content-end">
                                    <div class="d-flex flexiable ">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'oswald' ; font-size: 17px" class="mt-2">COMMENTS (<?php echo $postCommentCount; ?>)
                                        </h5>
                                    </div>
                                    <div class="d-flex flexiable " onclick="document.querySelector('.comment-form').scrollIntoView()">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'oswald' ; font-size: 17px;" class="mt-2">POST YOUR
                                            COMMENT </h5>
                                    </div>
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
    <div class="container bg-white" style="border: 1px solid #e0e0e0;">
        <div class="row">
            <div class="col-lg-8 py-3" style=" padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div>
                    <div class="d-flex align-items-center justify-content-between  gap-portion">
                        <div class="heading-jump d-flex align-items-center">
                            <button id="headingPrev" type="button" class="heading-nav-btn me-2" title="Previous section" aria-label="Previous section" style="display:none;">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <select id="headingDropdown" class="form-select form-select-sm d-inline-block" aria-label="Jump to section" style="width:auto; min-width: 240px; display:none;"></select>
                            <button id="headingNext" type="button" class="heading-nav-btn ms-2" title="Next section" aria-label="Next section" style="display:none;">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="d-flex">
                            <button class="section-button"><?php echo htmlspecialchars($post['author']); ?></button>
                            <p class="my-2 portion-headline mx-1"><?php echo !empty($post['publish_date']) ? date('j F Y', strtotime($post['publish_date'])) : date('j F Y', strtotime($post['created_at'])); ?></p>
                        </div>
                        <div>
                            <?php
                            $tags = $post['tags'];
                            if (!empty($tags)):
                                // Handle both PostgreSQL array format and comma-separated strings
                                if (is_string($tags)) {
                                    $tagsString = trim($tags);
                                    if (strlen($tagsString) > 1 && $tagsString[0] === '{' && substr($tagsString, -1) === '}') {
                                        // PostgreSQL array string like {Apple,iOS,Rumors}
                                        $tagsString = trim($tagsString, '{}');
                                        $tags = explode(',', $tagsString);
                                    } else {
                                        // Plain comma-separated string
                                        $tags = array_map('trim', explode(',', $tagsString));
                                    }
                                }
                                if (is_array($tags)):
                            ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <button class="section-button"><?php echo htmlspecialchars(trim($tag)); ?></button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="document-section">
                    <?php if (!empty($post['short_description'])): ?>
                        <p class="classy gap-portion"><?php echo nl2br(htmlspecialchars($post['short_description'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($post['featured_image'])): ?>
                        <img class="center-img" src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php endif; ?>

                    <?php if (!empty($post['content_body'])): ?>
                        <div class="gap-portion">
                            <?php
                            // Handle both plain text and rich content
                            $content = $post['content_body'];
                            // Check if content contains HTML tags
                            if (strip_tags($content) != $content) {
                                // Content has HTML, display as-is but sanitize
                                echo $content;
                            } else {
                                // Plain text content, convert line breaks
                                echo nl2br(htmlspecialchars($content));
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <p class="classy gap-portion">Content is being updated. Please check back later for the full article.</p>
                    <?php endif; ?>

                    <?php
                    $media_gallery = $post['media_gallery'];
                    if (!empty($media_gallery)):
                        // Handle PostgreSQL array format
                        if (is_string($media_gallery)) {
                            // Parse PostgreSQL array string
                            $media_gallery = trim($media_gallery, '{}');
                            $media_gallery = explode(',', $media_gallery);
                        }
                        if (is_array($media_gallery)):
                    ?>
                            <div class="media-gallery mt-4">
                                <?php foreach ($media_gallery as $media): ?>
                                    <img class="center-img my-2" src="<?php echo htmlspecialchars(trim($media)); ?>" alt="Media from <?php echo htmlspecialchars($post['title']); ?>">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="comments">
                    <h5 class="border-bottom reader  py-3 mx-2">READER COMMENTS</h5>
                    <div class="first-user" style="background-color: #EDEEEE;">

                        <?php if (!empty($postComments)): ?>
                            <?php foreach ($postComments as $comment): ?>
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
                                        <li class="ulocation">
                                            <i class="fa-solid fa-location-dot fa-sm"></i>
                                            <span title="Anonymous location">---</span>
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

                            <?php if (!empty($comment_success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($comment_success); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($comment_error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($comment_error); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <input type="hidden" name="action" value="comment_post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" name="name" placeholder="Your Name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" class="form-control" name="email" placeholder="Your Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your thoughts about this article..." required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" class="button-links">Post Your Comment</button>
                                    <small class="text-muted">Comments are moderated and will appear after approval.</small>
                                </div>
                            </form>
                        </div>

                        <div class="button-secondary-div d-flex justify-content-between align-items-center ">

                            <p class="div-last">Total reader comments: <b><?php echo $postCommentCount; ?></b></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4  col-12  bg-white p-3">
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Build a dynamic dropdown of all H3 headings and enable jump-to-section behavior
            window.addEventListener('load', function() {
                try {
                    const container = document.querySelector('.document-section');
                    const dropdown = document.getElementById('headingDropdown');
                    if (!container || !dropdown) return;

                    const headings = container.querySelectorAll('h3');
                    if (!headings.length) return;

                    // Prepare dropdown
                    dropdown.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Jump to section…';
                    placeholder.disabled = true;
                    placeholder.selected = true;
                    dropdown.appendChild(placeholder);

                    const usedIds = new Set();
                    const makeSlug = (str) => {
                        return str
                            .toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/\s+/g, '-')
                            .replace(/-+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    };

                    headings.forEach((h3, idx) => {
                        let text = (h3.textContent || '').trim().replace(/\s+/g, ' ');
                        if (!text) text = `Section ${idx + 1}`;

                        // Ensure heading has a unique id
                        let slug = h3.id || makeSlug(text) || `section-${idx + 1}`;
                        let base = slug;
                        let counter = 2;
                        while (usedIds.has(slug) || document.getElementById(slug)) {
                            slug = `${base}-${counter++}`;
                        }
                        if (!h3.id) {
                            h3.id = slug;
                        }
                        usedIds.add(slug);

                        // Add option
                        const opt = document.createElement('option');
                        opt.value = `#${slug}`;
                        opt.textContent = text.length > 80 ? text.slice(0, 77) + '…' : text;
                        dropdown.appendChild(opt);
                    });

                    // Show dropdown and arrow buttons now that they have content
                    dropdown.style.display = 'inline-block';
                    const prevBtn = document.getElementById('headingPrev');
                    const nextBtn = document.getElementById('headingNext');
                    const headingEls = Array.from(headings);
                    let activeIdx = -1; // 0-based index into headingEls; -1 means none selected yet

                    const fixed = document.querySelector('#navbar');
                    const getOffsetTop = (el) => {
                        const offset = (fixed ? fixed.offsetHeight : 0) + 12;
                        return el.getBoundingClientRect().top + window.pageYOffset - offset;
                    };
                    const scrollToEl = (el) => {
                        window.scrollTo({
                            top: getOffsetTop(el),
                            behavior: 'smooth'
                        });
                    };
                    const updateButtons = () => {
                        if (!prevBtn || !nextBtn) return;
                        prevBtn.disabled = activeIdx <= 0;
                        // When nothing selected, allow Next to go to first
                        nextBtn.disabled = (activeIdx >= headingEls.length - 1) && activeIdx !== -1;
                    };

                    if (prevBtn && nextBtn && headingEls.length) {
                        prevBtn.style.display = 'inline-flex';
                        nextBtn.style.display = 'inline-flex';
                        updateButtons();
                    }

                    const setActiveByIndex = (idx) => {
                        if (idx < 0 || idx >= headingEls.length) return;
                        activeIdx = idx;
                        // sync dropdown (account for placeholder at 0)
                        dropdown.selectedIndex = idx + 1;
                        updateButtons();
                        scrollToEl(headingEls[idx]);
                    };

                    // Smooth-scroll to target with offset for fixed navbar
                    dropdown.addEventListener('change', function() {
                        const selIndex = dropdown.selectedIndex;
                        const idx = selIndex - 1; // account for placeholder
                        if (idx >= 0 && idx < headingEls.length) {
                            setActiveByIndex(idx);
                        }
                    });

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function() {
                            if (activeIdx === -1) {
                                setActiveByIndex(0);
                            } else if (activeIdx > 0) {
                                setActiveByIndex(activeIdx - 1);
                            }
                        });
                    }
                    if (nextBtn) {
                        nextBtn.addEventListener('click', function() {
                            if (activeIdx === -1) {
                                setActiveByIndex(0);
                            } else if (activeIdx < headingEls.length - 1) {
                                setActiveByIndex(activeIdx + 1);
                            }
                        });
                    }
                } catch (e) {
                    // Fail silently to avoid breaking the page
                    console.error('Heading dropdown init failed:', e);
                }
            });
            document.addEventListener('DOMContentLoaded', function() {
                const commentForm = document.getElementById('main-comment-form');
                const originalFormParent = commentForm.parentNode;
                const parentIdInput = commentForm.querySelector('input[name="parent_id"]');
                const formTitle = commentForm.querySelector('h5');
                const submitButton = commentForm.querySelector('button[type="submit"]');
                const cancelButton = commentForm.querySelector('.cancel-reply');

                // Handle reply button clicks
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('reply-btn') || e.target.closest('.reply-btn')) {
                        e.preventDefault();
                        const button = e.target.closest('.reply-btn');
                        const commentId = button.getAttribute('data-comment-id');
                        const commentAuthor = button.getAttribute('data-comment-author');
                        const placeholder = document.querySelector(`.reply-form-placeholder[data-comment-id="${commentId}"]`);

                        // Move form to reply position
                        placeholder.appendChild(commentForm);

                        // Update form for reply
                        parentIdInput.value = commentId;
                        formTitle.textContent = `Reply to ${commentAuthor}`;
                        submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Reply';
                        cancelButton.style.display = 'inline-block';

                        // Clear form fields
                        commentForm.querySelector('#name').value = '';
                        commentForm.querySelector('#email').value = '';
                        commentForm.querySelector('#comment').value = '';

                        // Focus on name field
                        commentForm.querySelector('#name').focus();
                    }
                });

                // Handle cancel reply
                cancelButton.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Move form back to original position
                    originalFormParent.appendChild(commentForm);

                    // Reset form
                    parentIdInput.value = '';
                    formTitle.textContent = 'Leave a Comment';
                    submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Comment';
                    cancelButton.style.display = 'none';

                    // Clear form fields
                    commentForm.querySelector('#name').value = '';
                    commentForm.querySelector('#email').value = '';
                    commentForm.querySelector('#comment').value = '';
                });
            });
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

            // Defer loading the hero featured image until fully loaded
            window.addEventListener('load', function() {
                var heroImg = document.querySelector('.comfort-life-23 .bg-blur .center-img[data-src]');
                if (heroImg && heroImg.getAttribute('data-src')) {
                    var temp = new Image();
                    temp.onload = function() {
                        heroImg.src = heroImg.getAttribute('data-src');
                        heroImg.style.visibility = 'visible';
                    };
                    temp.src = heroImg.getAttribute('data-src');
                }
            });
        </script>
        <script src="script.js"></script>
</body>

</html>