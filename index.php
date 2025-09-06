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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Tech Hub - Latest Posts & Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .comment-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }

        .newsletter-form .alert-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .social-links a {
            font-size: 1.2rem;
            transition: opacity 0.3s ease;
        }

        .social-links a:hover {
            opacity: 0.7;
            margin-top: 20px;
        }

        .comment-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .device-specs {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .latest-devices-container {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 20px;
        }

        .latest-devices-container::-webkit-scrollbar {
            width: 8px;
        }

        .latest-devices-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .latest-devices-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .latest-devices-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .device-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .device-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }

        .brands-container {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 20px;
        }

        .brands-container::-webkit-scrollbar {
            width: 8px;
        }

        .brands-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .brands-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .brands-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .brand-cell {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .brand-cell:hover {
            background-color: #e3f2fd !important;
            transform: scale(1.05);
        }

        .brand-name {
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>Mobile Tech Hub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="featured_posts.php">Featured Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#posts">Latest Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#devices">Latest Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="compare_phones.php">Compare Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="phone_finder.php">Phone Finder</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Welcome to Mobile Tech Hub</h1>
            <p class="lead mb-4">Discover the latest mobile devices and read expert reviews from our tech community</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border-end border-light pe-3">
                                <h3 class="fw-bold"><?php echo count($posts); ?></h3>
                                <p class="mb-0">Latest Posts</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-end border-light pe-3">
                                <h3 class="fw-bold"><?php echo count($devices); ?></h3>
                                <p class="mb-0">Featured Devices</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h3 class="fw-bold">Community</h3>
                            <p class="mb-0">Expert Reviews</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($comment_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $comment_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($comment_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $comment_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Top Devices Tables Section -->
        <section class="mb-5">
            <div class="row">
                <!-- Top 10 Daily Views -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Top 10 Daily Views</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Device</th>
                                            <th style="width: 80px;">Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topViewedDevices)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">
                                                    <i class="fas fa-chart-line me-2"></i>No views data yet
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($topViewedDevices as $index => $device): ?>
                                                <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <td class="fw-bold text-primary"><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($device['image'])): ?>
                                                                <img src="<?php echo htmlspecialchars($device['image']); ?>"
                                                                    alt="Device" class="rounded me-2"
                                                                    style="width: 32px; height: 32px; object-fit: cover;">
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($device['name']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($device['brand_name']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold text-success"><?php echo $device['view_count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Reviewed Devices -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top 10 Reviewed</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Device</th>
                                            <th style="width: 80px;">Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topReviewedDevices)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">
                                                    <i class="fas fa-star-half-alt me-2"></i>No reviews yet
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($topReviewedDevices as $index => $device): ?>
                                                <tr class="clickable-row" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                                    <td class="fw-bold text-success"><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($device['image'])): ?>
                                                                <img src="<?php echo htmlspecialchars($device['image']); ?>"
                                                                    alt="Device" class="rounded me-2"
                                                                    style="width: 32px; height: 32px; object-fit: cover;">
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-medium"><?php echo htmlspecialchars($device['name']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($device['brand_name']); ?> • <?php echo $device['review_count']; ?> reviews</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="fw-bold text-warning me-1"><?php echo $device['review_count'] > 0 ? '4.2' : 'N/A'; ?></span>
                                                            <i class="fas fa-star text-warning"></i>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Popular Comparisons -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Popular Comparisons</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Comparison</th>
                                            <th style="width: 80px;">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topComparisons)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">
                                                    <i class="fas fa-balance-scale me-2"></i>No comparisons yet
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($topComparisons as $index => $comparison): ?>
                                                <tr class="clickable-comparison"
                                                    data-device1-id="<?php echo $comparison['device1_id'] ?? ''; ?>"
                                                    data-device2-id="<?php echo $comparison['device2_id'] ?? ''; ?>"
                                                    style="cursor: pointer;">
                                                    <td class="fw-bold text-info"><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="d-flex me-2">
                                                                <?php if (!empty($comparison['device1_image'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($comparison['device1_image']); ?>"
                                                                        alt="Device 1" class="rounded me-1"
                                                                        style="width: 24px; height: 24px; object-fit: cover;">
                                                                <?php endif; ?>
                                                                <?php if (!empty($comparison['device2_image'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($comparison['device2_image']); ?>"
                                                                        alt="Device 2" class="rounded"
                                                                        style="width: 24px; height: 24px; object-fit: cover;">
                                                                <?php endif; ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium small"><?php echo htmlspecialchars($comparison['device1_name'] ?? $comparison['device1'] ?? 'Unknown'); ?></div>
                                                                <div class="text-muted small">vs <?php echo htmlspecialchars($comparison['device2_name'] ?? $comparison['device2'] ?? 'Unknown'); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold text-info"><?php echo $comparison['comparison_count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest Devices Grid Section -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-mobile-alt text-warning me-2"></i>Latest Devices
                </h2>
                <a href="phone_finder.php" class="btn btn-outline-warning">
                    <i class="fas fa-search me-1"></i>Find More Devices
                </a>
            </div>

            <div class="latest-devices-container" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                <div class="row g-3">
                    <?php if (empty($latestDevices)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Devices Available</h4>
                                <p class="text-muted">Check back later for new devices!</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($latestDevices as $device): ?>
                            <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                                <div class="card h-100 card-hover device-card" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                    <div class="position-relative">
                                        <?php if (!empty($device['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($device['image']); ?>"
                                                class="card-img-top" alt="Device Image"
                                                style="height: 180px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                                style="height: 180px;">
                                                <i class="fas fa-mobile-alt fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Availability Badge -->
                                        <?php
                                        $availability_class = '';
                                        $availability_text = $device['availability'] ?? 'Unknown';
                                        switch (strtolower($availability_text)) {
                                            case 'available':
                                                $availability_class = 'bg-success';
                                                break;
                                            case 'discontinued':
                                                $availability_class = 'bg-danger';
                                                break;
                                            case 'coming soon':
                                                $availability_class = 'bg-warning';
                                                break;
                                            default:
                                                $availability_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="position-absolute top-0 end-0 badge <?php echo $availability_class; ?> m-2">
                                            <?php echo htmlspecialchars($availability_text); ?>
                                        </span>
                                    </div>

                                    <div class="card-body p-3 text-center">
                                        <h6 class="card-title mb-1 fw-bold"><?php echo htmlspecialchars($device['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($device['brand_name'] ?? 'Unknown Brand'); ?></small>

                                        <?php if (!empty($device['price']) && $device['price'] !== 'Not available'): ?>
                                            <div class="mt-2">
                                                <span class="text-primary fw-bold"><?php echo htmlspecialchars($device['price']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Brands Table Section -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-industry text-info me-2"></i>Our Brands
                </h2>
                <a href="brands.php" class="btn btn-outline-info">
                    <i class="fas fa-list me-1"></i>View All Brands
                </a>
            </div>

            <div class="brands-container" style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <tbody>
                            <?php
                            $brandChunks = array_chunk($brands, 4); // Create chunks of 4 brands per row
                            foreach ($brandChunks as $brandRow):
                            ?>
                                <tr>
                                    <?php foreach ($brandRow as $brand): ?>
                                        <td class="text-center p-3 brand-cell" data-brand-id="<?php echo $brand['id']; ?>" style="cursor: pointer;">
                                            <div class="brand-name fw-bold text-primary">
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>

                                    <?php
                                    // Fill remaining cells if less than 4 brands in the row
                                    $remaining = 4 - count($brandRow);
                                    for ($i = 0; $i < $remaining; $i++):
                                    ?>
                                        <td class="text-center p-3 text-muted">
                                            <div class="brand-name">-</div>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($brands)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Brands Available</h4>
                                        <p class="text-muted">Check back later for brand listings!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Latest Posts Section -->
        <section id="posts" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-newspaper text-primary me-2"></i>Latest Posts
                </h2>
                <a href="featured_posts.php" class="btn btn-outline-primary">View All Posts</a>
            </div>

            <div class="row">
                <?php if (empty($posts)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Posts Available</h4>
                            <p class="text-muted">Check back later for new content!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 card-hover">
                                <?php if (isset($post['featured_image']) && !empty($post['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                        class="card-img-top" alt="Post Image" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($post['categories'] ?? 'General'); ?></span>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($post['title'] ?? 'Untitled Post'); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($post['short_description'] ?? 'No description available'); ?></p>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-comments me-1"></i>
                                                <?php echo $post['comment_count']; ?> comments
                                            </small>
                                        </div>
                                        <a href="post.php?slug=<?php echo htmlspecialchars($post['slug'] ?: $post['id']); ?>"
                                            class="btn btn-primary btn-sm w-100">
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Latest Devices Section -->
        <section id="devices" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-mobile-alt text-success me-2"></i>Latest Devices
                </h2>
                <div>
                    <a href="compare_phones.php" class="btn btn-success me-2">
                        <i class="fas fa-balance-scale me-1"></i>Compare Devices
                    </a>
                    <a href="#" class="btn btn-outline-success">View All Devices</a>
                </div>
            </div>

            <div class="row">
                <?php if (empty($devices)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Devices Available</h4>
                            <p class="text-muted">Check back later for new devices!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 card-hover">
                                <?php if (isset($device['images']) && !empty($device['images'])): ?>
                                    <img src="<?php echo htmlspecialchars($device['images'][0]); ?>"
                                        class="card-img-top" alt="Device Image" style="height: 250px; object-fit: cover;">
                                <?php elseif (isset($device['image']) && !empty($device['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($device['image']); ?>"
                                        class="card-img-top" alt="Device Image" style="height: 250px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-success"><?php echo htmlspecialchars($device['brand'] ?? 'Unknown'); ?></span>
                                        <?php if (isset($device['availability']) && $device['availability'] === 'Available'): ?>
                                            <span class="badge bg-success ms-1">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-1">Coming Soon</span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($device['name'] ?? 'Unknown Device'); ?></h5>
                                    <div class="device-specs mb-3">
                                        <?php if (isset($device['display_size']) && !empty($device['display_size'])): ?>
                                            <p class="mb-1"><i class="fas fa-tv me-2"></i><?php echo htmlspecialchars($device['display_size']); ?> Display</p>
                                        <?php endif; ?>
                                        <?php if (isset($device['main_camera_resolution']) && !empty($device['main_camera_resolution'])): ?>
                                            <p class="mb-1"><i class="fas fa-camera me-2"></i><?php echo htmlspecialchars($device['main_camera_resolution']); ?> Camera</p>
                                        <?php elseif (isset($device['main_camera']) && !empty($device['main_camera'])): ?>
                                            <p class="mb-1"><i class="fas fa-camera me-2"></i><?php echo htmlspecialchars($device['main_camera']); ?> Camera</p>
                                        <?php endif; ?>
                                        <?php if (isset($device['battery_capacity']) && !empty($device['battery_capacity'])): ?>
                                            <p class="mb-1"><i class="fas fa-battery-full me-2"></i><?php echo htmlspecialchars($device['battery_capacity']); ?> Battery</p>
                                        <?php elseif (isset($device['battery']) && !empty($device['battery'])): ?>
                                            <p class="mb-1"><i class="fas fa-battery-full me-2"></i><?php echo htmlspecialchars($device['battery']); ?> Battery</p>
                                        <?php endif; ?>
                                        <?php if (isset($device['ram']) && !empty($device['ram'])): ?>
                                            <p class="mb-1"><i class="fas fa-memory me-2"></i><?php echo htmlspecialchars($device['ram']); ?> RAM</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php
                                                // Show launch date if available, otherwise announcement date, otherwise year
                                                if (!empty($device['launch_date'])) {
                                                    echo date('M j, Y', strtotime($device['launch_date']));
                                                } elseif (!empty($device['announcement_date'])) {
                                                    echo date('M j, Y', strtotime($device['announcement_date']));
                                                } elseif (isset($device['year'])) {
                                                    echo $device['year'];
                                                } else {
                                                    echo 'Unknown';
                                                }
                                                ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-comments me-1"></i>
                                                <?php echo $device['comment_count']; ?> comments
                                            </small>
                                        </div>
                                        <a href="device.php?id=<?php echo urlencode($device['id'] ?? $device['name']); ?>" class="btn btn-success btn-sm w-100">
                                            View Details & Comments
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Post Detail Modal -->
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalTitle">Post Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="postModalBody">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Device Detail Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deviceModalTitle">Device Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deviceModalBody">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>



    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-mobile-alt me-2"></i>Mobile Tech Hub</h5>
                    <p class="text-muted">Your trusted source for mobile device reviews and technology insights.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="featured_posts.php" class="text-muted text-decoration-none">Featured Posts</a></li>
                        <li><a href="#devices" class="text-muted text-decoration-none">Latest Devices</a></li>
                        <li><a href="login.php" class="text-muted text-decoration-none">Admin Login</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-envelope me-2"></i>Subscribe to Our Newsletter</h6>
                    <p class="text-muted small">Get the latest tech reviews, device launches, and industry insights delivered to your inbox.</p>

                    <!-- Newsletter Success/Error Messages -->
                    <?php if ($newsletter_success): ?>
                        <div class="alert alert-success alert-sm mb-3">
                            <i class="fas fa-check-circle me-2"></i><?php echo $newsletter_success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($newsletter_error): ?>
                        <div class="alert alert-danger alert-sm mb-3">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $newsletter_error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="newsletter-form">
                        <input type="hidden" name="action" value="newsletter_subscribe">
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm"
                                name="newsletter_name" placeholder="Your Name (optional)"
                                value="<?php echo htmlspecialchars($_POST['newsletter_name'] ?? ''); ?>">
                        </div>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control form-control-sm"
                                name="newsletter_email" placeholder="Enter your email" required
                                value="<?php echo htmlspecialchars($_POST['newsletter_email'] ?? ''); ?>">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fas fa-paper-plane me-1"></i>Subscribe
                            </button>
                        </div>
                        <small class="text-muted">
                            We respect your privacy. Unsubscribe at any time.
                        </small>
                    </form>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="text-muted mb-0">&copy; 2025 Mobile Tech Hub. All rights reserved. |
                        <a href="#" class="text-muted text-decoration-none">Privacy Policy</a> |
                        <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

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
                    document.getElementById('deviceModalBody').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('deviceModal')).show();
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
</body>

</html>