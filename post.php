<?php
// Public home page - no authentication required
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

// New clean URL format: domain/post/slug (instead of domain/post.php?slug=xyz)
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


// Get post by slug or ID
// New way: slug comes from clean URL (domain/post/slug) rewritten by .htaccess
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
    <link rel="canonical" href="<?php echo $canonicalBase; ?>/post/<?php echo htmlspecialchars($slug); ?>" />
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr($post['content_body'], 0, 160) . '...'); ?>" />
    <title><?php echo htmlspecialchars($post['meta_title'] ?? $post['title']); ?> - DevicesArena</title>

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
    <link rel="icon" type="image/png" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">

    <!-- Apple Touch Icon (iOS Home Screen) -->
    <link rel="apple-touch-icon" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="apple-touch-icon" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Android Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Theme Color (Browser Chrome & Address Bar) -->
    <meta name="theme-color" content="#8D6E63">

    <!-- Windows Tile Icon -->
    <meta name="msapplication-TileColor" content="#8D6E63">
    <meta name="msapplication-TileImage" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- Open Graph Meta Tags (Social Media Sharing) -->
    <meta property="og:site_name" content="DevicesArena">
    <meta property="og:title" content="DevicesArena - Smartphone Reviews & Comparisons">
    <meta property="og:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons on DevicesArena.">
    <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="256">
    <meta property="og:image:height" content="256">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="DevicesArena">
    <meta name="twitter:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons.">
    <meta name="twitter:image" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo $base; ?>manifest.json">

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

    <link rel="stylesheet" href="<?php echo $base; ?>style.css">

    <!-- Schema.org Structured Data for Blog Post Page -->
    <?php
    // Build breadcrumb schema for the blog post
    $breadcrumbItems = [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => "https://www.devicesarena.com/"
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Blog",
            "item" => "https://www.devicesarena.com/posts"
        ]
    ];

    if ($post) {
        $breadcrumbItems[] = [
            "@type" => "ListItem",
            "position" => 3,
            "name" => $post['title'],
            "item" => "https://www.devicesarena.com/post/" . htmlspecialchars($post['slug'])
        ];
    }
    ?>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        }
    </script>

    <?php
    // Build BlogPosting schema with full article details
    if ($post) {
        $blogPostingSchema = [
            "@context" => "https://schema.org",
            "@type" => "BlogPosting",
            "headline" => $post['title'],
            "description" => isset($post['excerpt']) && !empty($post['excerpt']) ? substr($post['excerpt'], 0, 160) : substr(strip_tags($post['content_body'] ?? ''), 0, 160),
            "articleBody" => isset($post['content_body']) && !empty($post['content_body']) ? strip_tags($post['content_body']) : "",
            "url" => "https://www.devicesarena.com/post/" . htmlspecialchars($post['slug']),
            "datePublished" => isset($post['created_at']) ? date('Y-m-d', strtotime($post['created_at'])) : date('Y-m-d'),
            "dateModified" => isset($post['updated_at']) && !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : (isset($post['created_at']) ? date('Y-m-d', strtotime($post['created_at'])) : date('Y-m-d'))
        ];

        // Add author if available
        $blogPostingSchema["author"] = [
            "@type" => "Organization",
            "name" => "DevicesArena"
        ];

        // Add featured image if available
        if (isset($post['featured_image']) && !empty($post['featured_image'])) {
            $imageUrl = getAbsoluteImagePath($post['featured_image'], 'https://www.devicesarena.com/');
            $blogPostingSchema["image"] = $imageUrl;
        } else {
            $blogPostingSchema["image"] = "https://www.devicesarena.com/imges/icon-256.png";
        }

        // Add comment count if available
        if (isset($postCommentCount) && $postCommentCount > 0) {
            $blogPostingSchema["commentCount"] = $postCommentCount;
        }

        // Add keywords/article section if available
        $blogPostingSchema["keywords"] = "smartphones, devices, reviews, specifications, tech news, mobile devices";
        $blogPostingSchema["articleSection"] = "Technology";
    }
    ?>

    <!-- BlogPosting Schema for Detailed Article Information -->
    <?php if ($post && isset($blogPostingSchema)): ?>
        <script type="application/ld+json">
            <?php echo json_encode($blogPostingSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
        </script>
    <?php endif; ?>

    <!-- Organization Schema for Author -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "DevicesArena",
            "url": "https://www.devicesarena.com",
            "logo": "https://www.devicesarena.com/imges/icon-256.png",
            "description": "Your source for comprehensive device reviews, specifications, comparisons, and tech industry insights."
        }
    </script>

    <!-- Generic Article Schema (for blog/news overview) -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "Technology News, Reviews, and Device Guides - DevicesArena",
            "description": "Stay updated with the latest smartphone, tablet, and smartwatch news, expert reviews, buying guides, and technology insights from DevicesArena.",
            "image": "https://www.devicesarena.com/imges/icon-256.png",
            "datePublished": "<?php echo date('Y-m-d'); ?>",
            "publisher": {
                "@type": "Organization",
                "name": "DevicesArena",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://www.devicesarena.com/imges/icon-256.png"
                }
            },
            "author": {
                "@type": "Organization",
                "name": "DevicesArena Team"
            }
        }
    </script>

    <!-- FAQ Schema for Blog Posts -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [{
                    "@type": "Question",
                    "name": "What kind of content does DevicesArena blog cover?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "DevicesArena blog features comprehensive articles about smartphones, tablets, smartwatches, and other mobile devices. Our content includes product reviews, technology news, device comparisons, buying guides, specification analysis, and insights into the latest tech industry trends and innovations."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How often is the blog updated with new posts?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "We regularly publish new articles covering the latest device releases, tech news, detailed reviews, and buying guides. Check back frequently for fresh content or subscribe to our newsletter to receive notifications about new posts and device reviews."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can I find links to device comparisons in blog posts?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Yes! Many of our blog posts include links to detailed device specifications and comparison tools. You can use these links to directly compare devices discussed in the articles to make informed purchasing decisions."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How are reviews and ratings determined?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Our reviews are based on comprehensive testing and analysis of device specifications, real-world performance, camera quality, battery life, display characteristics, software experience, and value for money. We evaluate each device objectively across multiple categories."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can I share articles on social media?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Yes! Each blog post can be easily shared on social media platforms. Use your browser's share functionality or look for social sharing buttons to share interesting articles with your friends and followers."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How can I stay updated with new articles?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Subscribe to our newsletter to receive notifications about new blog posts, device reviews, and technology insights. You can also browse our blog section regularly or use our Phone Finder tool to explore specific device categories."
                    }
                }
            ]
        }
    </script>

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
            text-transform: none;
        }

        .document-section .gap-portion {
            text-transform: none;
        }

        /* Reset all potential external styles in content */
        .document-section .gap-portion * {
            text-transform: none !important;
            font-family: inherit !important;
            background: none !important;
            border: none !important;
            margin: revert !important;
            padding: revert !important;
        }

        /* Allow only specific formatting */
        .document-section .gap-portion strong {
            font-weight: 700;
        }

        .document-section .gap-portion em {
            font-style: italic;
        }

        .document-section .gap-portion u {
            text-decoration: underline;
        }

        .document-section .gap-portion h1,
        .document-section .gap-portion h2,
        .document-section .gap-portion h3,
        .document-section .gap-portion h4,
        .document-section .gap-portion h5,
        .document-section .gap-portion h6 {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .document-section .gap-portion ul,
        .document-section .gap-portion ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .document-section .gap-portion img {
            max-width: 100%;
            height: auto;
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
            flex: 1 1 auto;
            min-width: 0;
            width: 100%;
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
            flex: 0 0 auto;
        }

        .heading-nav-btn:hover {
            background-color: #e9ecef;
            border-color: #d5d5d5;
        }

        .heading-nav-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

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

        /* Fix featured image - override absolute positioning and flexbox constraints */
        .comfort-life-23 {
            position: relative !important;
            flex-direction: column !important;
            height: auto !important;
        }

        .post-image {
            height: 340px !important;
            width: 100% !important;
            min-height: auto !important;
        }

        .post-inside {
            height: 100% !important;
            overflow: visible !important;
        }

        .comfort-life-23 .center-img {
            width: 100% !important;
            /* height: auto !important; */
            display: block !important;
            max-width: 100% !important;
            object-fit: cover;
            height: 100%;
        }
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
        crossorigin="anonymous"></script>
</head>

<body style="background-color: #EFEBE9; overflow-x: hidden;">
    <?php include 'includes/gsmheader.php'; ?>

    <div class=" mt-4 d-lg-none d-block bg-white">
        <h3 style="font-size: 23px;
        font-weight: 600; font-family: 'system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif';" class="mx-3 my-5"><?php echo htmlspecialchars($post['title']); ?></h3>
        <?php if (!empty($post['featured_image'])): ?>
            <img style="height: 100%; width: -webkit-fill-available;" src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
        <?php endif; ?>
    </div>
    <div class="container support content-wrapper" id="Top">
        <div class="row">

            <div class="col-md-8 col-5 d-md-inline  " style="border: 1px solid #e0e0e0;">
                <div class="comfort-life-23 position-absolute d-flex justify-content-between">
                    <div class="article-info post-image">
                        <div class="bg-blur post-inside">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img class="center-img" src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex;  flex-direction: column;">
                        <h1 class="article-info-name" style="color: #D50000; text-shadow: none;"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <div class="article-info">
                            <div class="bg-blur  m-auto" style="background-color: #D50000;">
                                <div class="d-flex justify-content-end">
                                    <div class="d-flex flexiable ">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif' ; font-size: 17px" class="mt-2">COMMENTS (<?php echo $postCommentCount; ?>)
                                        </h5>
                                    </div>
                                    <div class="d-flex flexiable " onclick="document.querySelector('.comment-form').scrollIntoView()">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif' ; font-size: 17px;" class="mt-2">POST YOUR
                                            COMMENT </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 0; padding: 0px;">
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
                    <button class="solid w-50 py-2">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>
                </div>
            </div>
        </div>

    </div>
    <div class="container bg-white" style="border: 1px solid #e0e0e0;">
        <div class="row">
            <div class="col-lg-8 py-3" style=" padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div>
                    <div class="d-flex align-items-center gap-portion mb-2">
                        <div class="heading-jump d-flex align-items-center w-100">
                            <button id="headingPrev" type="button" class="heading-nav-btn me-2 flex-shrink-0" title="Previous section" aria-label="Previous section" style="display:none;">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <select id="headingDropdown" class="form-select form-select-sm w-100 flex-grow-1" aria-label="Jump to section" style="width:100%; display:none;"></select>
                            <button id="headingNext" type="button" class="heading-nav-btn ms-2 flex-shrink-0" title="Next section" aria-label="Next section" style="display:none;">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between gap-portion">
                        <div class="d-flex">
                            <button class="section-button"><?php echo htmlspecialchars($post['author']); ?></button>
                            <p class="my-2 portion-headline mx-1"><?php echo !empty($post['publish_date']) ? date('j F Y', strtotime($post['publish_date'])) : date('j F Y', strtotime($post['created_at'])); ?></p>
                        </div>
                        <div>
                            <?php
                            $tags = $post['tags'];
                            if (!empty($tags)) {
                                // Handle both PostgreSQL array format and comma-separated strings
                                if (is_string($tags)) {
                                    $tagsString = trim($tags);
                                    if (strlen($tagsString) > 1 && $tagsString[0] === '{' && substr($tagsString, -1) === '}') {
                                        // PostgreSQL array string like {"Apple","iOS","Rumors"}
                                        $tagsString = trim($tagsString, '{}');
                                        $tags = array_map(function ($tag) {
                                            return trim($tag, '"'); // Remove double quotes around tags
                                        }, explode(',', $tagsString));
                                    } else {
                                        // Plain comma-separated string
                                        $tags = array_map('trim', explode(',', $tagsString));
                                    }
                                }
                                if (is_array($tags)) {
                                    foreach ($tags as $tag) {
                                        echo '<button class="section-button" style="margin-right: 5px;">' . htmlspecialchars($tag) . '</button>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="document-section">
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
                    //$media_gallery = $post['media_gallery'];
                    //if (!empty($media_gallery)):
                    // Handle PostgreSQL array format
                    //if (is_string($media_gallery)) {
                    // Parse PostgreSQL array string
                    //  $media_gallery = trim($media_gallery, '{}');
                    //$media_gallery = explode(',', $media_gallery);
                    //}
                    //if (is_array($media_gallery)):
                    ?>
                    <!-- <div class="media-gallery mt-4"> -->
                    <?php //foreach ($media_gallery as $media): 
                    ?>
                    <!-- <img class="center-img my-2" src="<?php //echo htmlspecialchars(trim($media)); 
                                                            ?>" alt="Media from <?php //echo htmlspecialchars($post['title']); 
                                                                                ?>"> -->
                    <?php //endforeach; 
                    ?>
                    <!-- </div> -->
                    <?php //endif; 
                    ?>
                    <?php //endif; 
                    ?>
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

                            <form id="post-comment-form" method="POST">
                                <input type="hidden" name="action" value="comment_post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your thoughts about this article..." required></textarea>
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
            <div class="col-lg-4 col-12 bg-white" style="margin-top: 18px;">
                <?php include 'includes/latest-devices.php'; ?>
                <?php include 'includes/comparisons-devices.php'; ?>
                <?php include 'includes/topviewed-devices.php'; ?>
                <?php include 'includes/topreviewed-devices.php'; ?>
                <?php include 'includes/instoresnow-devices.php'; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/gsmfooter.php'; ?>
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
                        <?php if (!empty($allBrandsModal)): ?>
                            <?php foreach ($allBrandsModal as $brand): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Build a dynamic dropdown of all H1, H2, H3 headings and enable jump-to-section behavior
        window.addEventListener('load', function() {
            try {
                const container = document.querySelector('.document-section');
                const dropdown = document.getElementById('headingDropdown');
                if (!container || !dropdown) return;

                const headings = container.querySelectorAll('h1, h2, h3');
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
                        fetch('/track_device_view.php', {
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
                        fetch('/track_device_view.php', {
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
                    const device1Slug = this.getAttribute('data-device1-slug');
                    const device2Slug = this.getAttribute('data-device2-slug');
                    const device1Id = this.getAttribute('data-device1-id');
                    const device2Id = this.getAttribute('data-device2-id');
                    if ((device1Slug && device2Slug) || (device1Id && device2Id)) {
                        // Track the comparison
                        fetch('/track_device_comparison.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'device1_id=' + encodeURIComponent(device1Id) + '&device2_id=' + encodeURIComponent(device2Id)
                        });

                        // Redirect to comparison page using slugs (preferred) or IDs (fallback)
                        const compareUrl = device1Slug && device2Slug 
                            ? `/compare/${device1Slug}-vs-${device2Slug}`
                            : `/compare/${device1Id}-vs-${device2Id}`;
                        window.location.href = compareUrl;
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
                    // Convert relative image paths to absolute
                    let imagePath = phone.image;
                    if (imagePath && !imagePath.startsWith('/') && !imagePath.startsWith('http')) {
                        imagePath = '/' + imagePath;
                    }
                    const phoneImage = imagePath ? `<img src="${imagePath}" alt="${phone.name}" style="width: 100%; max-width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px; display: block;" onerror="this.style.display='none';">` : '';
                    html += `
          <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <button class="device-cell-modal btn w-100 p-0" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; overflow: hidden;" onclick="goToDevice('${phone.slug || phone.id}')">
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
        function goToDevice(deviceSlugOrId) {
            if (typeof deviceSlugOrId === 'string' && /[a-z-]/.test(deviceSlugOrId)) {
                window.location.href = `device/${encodeURIComponent(deviceSlugOrId)}`;
            } else {
                window.location.href = `device/${deviceSlugOrId}`;
            }
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
                    fetch('/handle_newsletter.php', {
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
    <script src="<?php echo $base; ?>script.js"></script>
    <script src="<?php echo $base; ?>js/comment-ajax.js"></script>
</body>

</html>