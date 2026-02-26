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


// Function to generate gravatar URL
function getGravatarUrl($email, $size = 50)
{
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?r=g&s={$size}&d=identicon";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="canonical" href="<?php echo $canonicalBase ?? ''; ?>/advertise-with-us" />
    <meta name="description" content="Advertise with DevicesArena - Reach a tech-savvy audience interested in smartphones, tablets, and mobile technology." />
    <title>Advertise With Us - DevicesArena</title>

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
    <meta name="theme-color" content="#1B2035">

    <!-- Windows Tile Icon -->
    <meta name="msapplication-TileColor" content="#1B2035">
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

    <!-- Schema.org Structured Data for Advertise Page -->
    <?php
    // Build breadcrumb schema for the advertise page
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
            "name" => "Advertise With Us",
            "item" => "https://www.devicesarena.com/advertise-with-us"
        ]
    ];
    ?>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        }
    </script>



    <!-- Organization Schema with Contact Information -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "DevicesArena",
            "url": "https://www.devicesarena.com",
            "logo": "https://www.devicesarena.com/imges/icon-256.png",
            "description": "Your source for comprehensive device reviews, specifications, comparisons, and tech industry insights.",
            "breadcrumb": {
                "@type": "BreadcrumbList",
                "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
            }
        }
    </script>

    <!-- ContactPage Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Advertise With Us - DevicesArena",
            "headline": "Advertise With DevicesArena",
            "description": "Advertise with DevicesArena to reach a tech-savvy audience interested in smartphones, tablets, and mobile technology.",
            "url": "https://www.devicesarena.com/advertise-with-us",
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
            "breadcrumb": {
                "@type": "BreadcrumbList",
                "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
            }
        }
    </script>

    <!-- FAQ Schema for Advertise Page -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "name": "DevicesArena Advertising FAQs",
            "url": "https://www.devicesarena.com/advertise-with-us",
            "breadcrumb": {
                "@type": "BreadcrumbList",
                "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
            },
            "mainEntity": [{
                    "@type": "Question",
                    "name": "What advertising options does DevicesArena offer?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "We offer banner ads, sponsored content, product placements, and newsletter sponsorships. Contact us through our advertising inquiry form for a customized proposal."
                    }
                },
                {
                    "@type": "Question",
                    "name": "What is DevicesArena's audience?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Our audience primarily consists of tech enthusiasts, smartphone buyers, and gadget reviewers who visit DevicesArena for detailed device specifications, comparisons, and reviews."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How do I get started with advertising?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Simply fill out the advertising inquiry form on this page with your company details and requirements. Our team will review your inquiry and get back to you within 24-48 business hours with a tailored proposal."
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
            border-color: #1B2035;
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
            border-color: #1B2035;
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

    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-md-8 col-5  d-lg-inline d-none " style="padding: 0; position: relative;">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="<?php echo $base; ?>hero-images/advertise-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="header image of advertise with us page for devicesarena.com">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; /* left: 12px; */ padding: 0;">
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
            <div class="col-lg-8 py-3" style="padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div class="document-section">
                    <div class="gap-portion" style="padding: 20px 30px;">

                        <h4 style="color: #1B2035; margin-bottom: 15px;">Advertise With DevicesArena</h4>
                        <p style="color: #555; line-height: 1.7;">DevicesArena is a leading destination for smartphone specifications, comparisons, and reviews. Partner with us to reach a highly engaged, tech-savvy audience.</p>

                        <p style="color: #555; line-height: 1.7; margin-top: 15px;"><strong>What we offer:</strong></p>
                        <ul style="color: #555; line-height: 2; padding-left: 20px;">
                            <li>Banner advertisements across our high-traffic pages.</li>
                            <li>Sponsored content and product review placements.</li>
                            <li>Newsletter sponsorship to our subscriber base.</li>
                            <li>Custom partnership and collaboration opportunities.</li>
                        </ul>

                        <p style="color: #555; line-height: 1.7; margin-top: 15px;"><strong>Why advertise with us?</strong></p>
                        <ul style="color: #555; line-height: 2; padding-left: 20px;">
                            <li>Targeted audience of tech enthusiasts and smartphone buyers.</li>
                            <li>High-quality, detailed content that keeps users engaged.</li>
                            <li>Competitive pricing with flexible packages.</li>
                            <li>Transparent reporting and performance metrics.</li>
                        </ul>

                    </div>
                </div>

                <!-- Advertising Inquiry Form -->
                <div class="comment-form mt-4 mx-2 mb-3">
                    <h6 class="mb-3">Send us your advertising inquiry</h6>

                    <div id="contact_message_container"></div>

                    <form id="contact_form" novalidate>
                        <input type="hidden" name="query_type" value="ad">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text" class="form-control" id="contact_name" name="contact_name" placeholder="Your Name / Company *" maxlength="100" required>
                                <div class="invalid-feedback" id="name_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Your Business Email *" maxlength="255" required>
                                <div class="invalid-feedback" id="email_error"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" id="contact_query" name="contact_query" rows="5" placeholder="Tell us about your advertising needs, budget, and any specific requirements (no links allowed)..." maxlength="5000" required></textarea>
                            <div class="invalid-feedback" id="query_error"></div>
                            <small class="text-muted"><span id="char_count">0</span>/5000 characters</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" id="contact_submit_btn" class="button-links">
                                Submit Inquiry
                            </button>
                            <small class="text-muted">We typically respond within 24-48 hours.</small>
                        </div>
                    </form>
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
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
                <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
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
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #1B2035;">
                <div class="modal-header" style="border-bottom: 1px solid #1B2035; background-color: #D7CCC8;">
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
                        const compareUrl = device1Slug && device2Slug ?
                            `/compare/${device1Slug}-vs-${device2Slug}` :
                            `/compare/${device1Id}-vs-${device2Id}`;
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
                    window.location.href = `<?php echo $base; ?>post/${postId}`;
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
                    window.location.href = `<?php echo $base; ?>device/${deviceId}`;
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
                    const phoneImage = imagePath ? `<img src="${imagePath}" alt="${phone.name}" style="width: 100%; max-width: 100%; height: 120px; object-fit: contain; margin: 8px; display: block;" onerror="this.style.display='none';">` : '';
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

        // Contact form handler
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contact_form');
            const contactMsg = document.getElementById('contact_message_container');
            const charCount = document.getElementById('char_count');
            const queryField = document.getElementById('contact_query');

            // Character counter
            if (queryField && charCount) {
                queryField.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }

            // Spam link detection (client-side)
            function containsLinks(text) {
                const patterns = [
                    /https?:\/\/[^\s]+/i,
                    /www\.[^\s]+/i,
                    /[a-zA-Z0-9.-]+\.(com|net|org|info|biz|xyz|ru|cn|tk|ml|ga|cf|gq|top|work|click|link|site|online|store|shop|buzz|pw|cc|io|co|me)\b/i,
                    /\[url[=\]].*?\[\/url\]/i,
                    /<a\s[^>]*href[^>]*>/i,
                    /href\s*=\s*["'][^"']*["']/i,
                ];
                for (const p of patterns) {
                    if (p.test(text)) return true;
                }
                return false;
            }

            function clearErrors() {
                document.querySelectorAll('#contact_form .form-control').forEach(el => el.classList.remove('is-invalid'));
            }

            function setError(fieldId, errorId, msg) {
                document.getElementById(fieldId).classList.add('is-invalid');
                document.getElementById(errorId).textContent = msg;
            }

            function showContactMessage(message, type) {
                const bgColor = type === 'success' ? '#4CAF50' : '#f44336';
                contactMsg.innerHTML = '<div style="background-color: ' + bgColor + '; color: white; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center;">' + message + '</div>';
                if (type === 'success') {
                    setTimeout(() => {
                        contactMsg.innerHTML = '';
                    }, 8000);
                }
            }

            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    clearErrors();

                    const name = document.getElementById('contact_name').value.trim();
                    const email = document.getElementById('contact_email').value.trim();
                    const query = queryField.value.trim();
                    let hasError = false;

                    if (!name) {
                        setError('contact_name', 'name_error', 'Please enter your name.');
                        hasError = true;
                    } else if (containsLinks(name)) {
                        setError('contact_name', 'name_error', 'Links are not allowed in the name field.');
                        hasError = true;
                    }

                    if (!email) {
                        setError('contact_email', 'email_error', 'Please enter your email.');
                        hasError = true;
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        setError('contact_email', 'email_error', 'Please enter a valid email address.');
                        hasError = true;
                    }

                    if (!query) {
                        setError('contact_query', 'query_error', 'Please enter your message.');
                        hasError = true;
                    } else if (query.length < 10) {
                        setError('contact_query', 'query_error', 'Your message is too short (minimum 10 characters).');
                        hasError = true;
                    } else if (containsLinks(query)) {
                        setError('contact_query', 'query_error', 'Links/URLs are not allowed in the message. Please remove any links and try again.');
                        hasError = true;
                    }

                    if (hasError) return;

                    const btn = document.getElementById('contact_submit_btn');
                    const originalHTML = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

                    fetch('<?php echo $base; ?>handle_contact.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'contact_name=' + encodeURIComponent(name) +
                                '&contact_email=' + encodeURIComponent(email) +
                                '&contact_query=' + encodeURIComponent(query) +
                                '&query_type=ad'
                        })
                        .then(r => r.json())
                        .then(data => {
                            showContactMessage(data.message, data.success ? 'success' : 'error');
                            if (data.success) {
                                contactForm.reset();
                                charCount.textContent = '0';
                            }
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                        })
                        .catch(() => {
                            showContactMessage('An error occurred. Please try again later.', 'error');
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                        });
                });
            }
        });
    </script>
    <script src="<?php echo $base; ?>script.js"></script>
</body>

</html>