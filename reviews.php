<?php
// Public home page - no authentication required
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

// Helper function to convert relative image paths to absolute
function getAbsoluteImagePath($imagePath, $base)
{
    if (empty($imagePath)) return '';
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
    if (strpos($imagePath, '/') === 0) return $imagePath;
    return $base . ltrim($imagePath, '/');
}

// Get posts and devices for display (case-insensitive status check) with comment counts
$pdo = getConnection();

// Read search query (hero search bar)
$q = trim($_GET['q'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$isSearching = ($q !== '');
$isTagFiltering = ($tag !== '');

// Build posts list: default is published; if searching, filter by title/tags
if ($isTagFiltering) {
    $posts_stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
        FROM posts p 
        WHERE p.status ILIKE 'published'
          AND EXISTS (
              SELECT 1 FROM unnest(COALESCE(p.tags, ARRAY[]::varchar[])) t
              WHERE t ILIKE ?
          )
        ORDER BY p.created_at DESC
    ");
    $posts_stmt->execute([$tag]);
    $posts = $posts_stmt->fetchAll();
} else if ($isSearching) {
    $term = '%' . $q . '%';
    $posts_stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
        FROM posts p 
        WHERE p.status ILIKE 'published'
          AND (
              p.title ILIKE ?
              OR EXISTS (
                  SELECT 1 FROM unnest(COALESCE(p.tags, ARRAY[]::varchar[])) t
                  WHERE t ILIKE ?
              )
          )
        ORDER BY p.created_at DESC
    ");
    $posts_stmt->execute([$term, $term]);
    $posts = $posts_stmt->fetchAll();
} else {
    $posts_stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
        FROM posts p 
        WHERE p.status ILIKE 'published'
        ORDER BY p.created_at DESC
    ");
    $posts_stmt->execute();
    $posts = $posts_stmt->fetchAll();
}

// Get devices from database
$devices = getAllPhones();
$devices = array_slice($devices, 0, 6); // Limit to 6 devices for home page

// Add comment counts to devices
foreach ($devices as $index => $device) {
    $comment_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_comments WHERE device_id = CAST(? AS VARCHAR) AND status = 'approved'");
    $comment_stmt->execute([$device['id']]);
    $devices[$index]['comment_count'] = $comment_stmt->fetch()['count'] ?? 0;
}

// Get popular tags (top 8 most appearing in published posts)
// Using PostgreSQL unnest() for efficient array handling
$popularTags = [];
try {
    $stmt = $pdo->prepare("
        SELECT tag, COUNT(*) as count
        FROM (
            SELECT DISTINCT unnest(tags) as tag
            FROM posts 
            WHERE status ILIKE 'published'
            AND tags IS NOT NULL 
            AND array_length(tags, 1) > 0
        ) tag_list
        GROUP BY tag
        ORDER BY count DESC
        LIMIT 8
    ");
    $stmt->execute();
    $tagResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tagResults as $row) {
        $popularTags[$row['tag']] = $row['count'];
    }
} catch (Exception $e) {
    $popularTags = [];
}

// Get data for the three tables
$topViewedDevices = [];
$topReviewedDevices = [];
$topComparisons = [];

// Get top comparisons from database
try {
    $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
    $topComparisons = [];
}

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




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="canonical" href="<?php echo $canonicalBase; ?>/reviews" />
    <title>DevicesArena</title>

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


    <link rel="stylesheet" href="<?php echo $base; ?>style.css">

    <!-- Schema.org Structured Data for Reviews/Articles Page -->
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [{
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "https://www.devicesarena.com/"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Reviews & Articles",
                    "item": "https://www.devicesarena.com/reviews"
                }
                <?php if ($isTagFiltering): ?>, {
                        "@type": "ListItem",
                        "position": 3,
                        "name": "Tagged: <?php echo htmlspecialchars($tag); ?>",
                        "item": "https://www.devicesarena.com/reviews?tag=<?php echo urlencode($tag); ?>"
                    }
                <?php elseif ($isSearching): ?>, {
                        "@type": "ListItem",
                        "position": 3,
                        "name": "Search Results: <?php echo htmlspecialchars($q); ?>",
                        "item": "https://www.devicesarena.com/reviews?q=<?php echo urlencode($q); ?>"
                    }
                <?php endif; ?>
            ]
        }
    </script>

    <!-- CollectionPage Schema for Articles Listing -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "CollectionPage",
            "name": "<?php echo $isTagFiltering ? 'Reviews Tagged: ' . htmlspecialchars($tag) : ($isSearching ? 'Search Results: ' . htmlspecialchars($q) : 'Device Reviews & Articles'); ?>",
            "description": "<?php echo $isTagFiltering ? 'Articles and reviews tagged with ' . htmlspecialchars($tag) : ($isSearching ? 'Search results for ' . htmlspecialchars($q) : 'Comprehensive device reviews, specifications analysis, technology news, and expert insights'); ?>",
            "url": "https://www.devicesarena.com/reviews<?php echo $isTagFiltering ? '?tag=' . urlencode($tag) : ($isSearching ? '?q=' . urlencode($q) : ''); ?>",
            "mainEntity": {
                "@type": "ItemList",
                "itemListElement": [
                    <?php
                    $itemCount = 0;
                    foreach ($posts as $post):
                        if ($itemCount >= 10) break; // Limit to 10 items in schema
                        $itemCount++;
                    ?> {
                            "@type": "BlogPosting",
                            "position": <?php echo $itemCount; ?>,
                            "headline": "<?php echo addslashes(htmlspecialchars($post['title'])); ?>",
                            "description": "<?php echo addslashes(htmlspecialchars(isset($post['excerpt']) && !empty($post['excerpt']) ? substr($post['excerpt'], 0, 160) : substr(strip_tags($post['content_body'] ?? ''), 0, 160))); ?>",
                            "datePublished": "<?php echo date('Y-m-d', strtotime($post['created_at'])); ?>",
                            "image": "<?php echo isset($post['featured_image']) && !empty($post['featured_image']) ? getAbsoluteImagePath($post['featured_image'], 'https://www.devicesarena.com/') : 'https://www.devicesarena.com/imges/icon-256.png'; ?>",
                            "url": "https://www.devicesarena.com/post/<?php echo htmlspecialchars($post['slug']); ?>"
                        }
                        <?php echo $itemCount < count(array_slice($posts, 0, 10)) ? ',' : ''; ?>
                    <?php endforeach; ?>
                ]
            }
        }
    </script>

    <!-- Organization Schema for Reviews Section -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "DevicesArena Reviews",
            "url": "https://www.devicesarena.com/reviews",
            "description": "Expert device reviews, technology analysis, and comprehensive specifications for smartphones, tablets, smartwatches, and other mobile devices."
        }
    </script>

    <!-- FAQ Schema for Reviews Pages -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [{
                    "@type": "Question",
                    "name": "What types of reviews does DevicesArena provide?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "DevicesArena provides comprehensive reviews covering smartphones, tablets, smartwatches, and other mobile devices. Our reviews include detailed specifications analysis, real-world performance testing, camera quality assessment, battery life evaluation, display characteristics, software experience, design and build quality, and overall value for money considerations."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How can I search for specific device reviews?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "You can use the search bar to find reviews by device name, brand, or specifications. Simply enter keywords related to the device you're interested in, and our search will display all matching reviews and articles from our comprehensive database."
                    }
                },
                {
                    "@type": "Question",
                    "name": "What are tags and how do I filter by them?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Tags help organize reviews by topic, device category, or feature. You can click any tag to see all articles related to that topic. Popular tags are displayed on the page, making it easy to explore reviews by specific categories like flagship phones, budget devices, camera performance, or battery life."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How are devices reviewed and rated?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Our reviews are based on comprehensive evaluation across multiple criteria including processor performance, display quality, camera capabilities, battery life, design and build, software experience, value for money, and overall rating. We test real-world performance and provide detailed analysis to help you make informed decisions."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can I compare devices mentioned in reviews?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Yes! Many reviews include links to our detailed comparison tool. You can directly compare any devices mentioned in reviews to see their specifications, features, and differences side-by-side."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How often are new reviews published?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "We regularly publish new reviews covering the latest device releases, technology updates, and market analysis. Subscribe to our newsletter or check back frequently to stay updated with the latest reviews and tech insights."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Are there reviews for older device models?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Yes, our review database includes both current flagship models and older devices. This allows you to find reviews for devices you may be purchasing secondhand or comparing across different price ranges and release years."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can I sort reviews by date or popularity?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Reviews are displayed in order of publication (newest first), making it easy to find the latest device reviews. You can also use tags and search functionality to find reviews for specific devices or categories you're interested in."
                    }
                }
            ]
        }
    </script>

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

        .grid-colums {
            background-color: #EEEEEE;
            gap: 21px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: max-content;
            height: max-content;
        }

        .anchor-card {
            height: 300px;
            /* margin-bottom: 40px; */
        }

        .review-card {
            margin-top: 14px;
        }

        @media (max-width:786px) {
            .grid-colums {
                background-color: #EEEEEE;
                display: block;
                grid-template-columns: 1fr;

            }

        }
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
        crossorigin="anonymous"></script>
</head>

<body style="background-color: #EFEBE9;">
    <!-- Desktop Navbar of Gsmarecn -->
    <?php include 'includes/gsmheader.php'; ?>
    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-md-8 col-5  d-lg-inline d-none col-12 " style="padding: 0px; position: relative;">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="<?php echo $base; ?>hero-images/reviews-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
                    <div class="position-absolute d-flex mt-1 ml-2" style="top: 0; flex-wrap: wrap; gap: 8px;">
                        <label class="text-white whitening">Popular Tags</label>
                        <?php if (!empty($popularTags)): ?>
                            <?php foreach ($popularTags as $tag => $count): ?>
                                <a href="<?php echo $base; ?>reviews?tag=<?php echo urlencode($tag); ?>">
                                    <button class="mobiles-button"><?php echo htmlspecialchars($tag); ?></button>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-tags">
                                <span>No tags availble</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="get" action="reviews.php" class="comon">
                        <label for="hero-search-reviews" class="text-white whitening ">Search For</label>
                        <input id="hero-search-reviews" name="q" type="text" class="bg-white" placeholder="Search posts..." value="<?php echo htmlspecialchars($q ?? ''); ?>">
                        <button type="submit" class="mobiles-button bg-grey">Search</button>
                    </form>
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
    <div class="container margin-top-4rem" style="border-left:1px solid #00000012;">
        <div class="row">
            <?php
            if (empty($posts)):
            ?>
                <div class="col-12">
                    <div class="alert alert-light border" role="alert">
                        <?php if ($isTagFiltering): ?>
                            No posts found with tag "<?php echo htmlspecialchars($tag); ?>".
                        <?php elseif ($isSearching): ?>
                            No posts found for "<?php echo htmlspecialchars($q); ?>".
                        <?php else: ?>
                            No posts to show.
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            else:
                $maxPosts = count($posts);
                $displayPosts = array_slice($posts, 0, $maxPosts);
                $columns = max(1, ceil($maxPosts / 1));
                $postChunks = array_chunk($displayPosts, $columns);
                foreach ($postChunks as $colIndex => $colPosts):
                ?>
                    <div class="col-lg-8 col-md-6 col-12 sentizer-erx grid-colums" style="background-color: #EEEEEE; margin-top: 4px;">
                        <?php foreach ($colPosts as $post): ?>
                            <a href="<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>">
                                <div class="review-card mb-4" style="cursor:pointer;" onclick="window.location.href='<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>'">
                                    <?php if (isset($post['featured_image']) && !empty($post['featured_image'])): ?>
                                        <img style="cursor:pointer;" onclick="window.location.href='<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>'" src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    <?php endif; ?>
                                    <div class="review-card-body">
                                        <div style="cursor:pointer;" onclick="window.location.href='<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>'" class="review-card-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                        <div class="review-card-meta">
                                            <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                            <span><i class="bi bi-chat-dots-fill"></i><?php echo $post['comment_count']; ?> comments</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
            <?php endforeach;
            endif; ?>

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
        });

        // Show post details in modal
        function showPostDetails(postId) {
            fetch(`get_post_details.php?id=${postId}`)
                .then(response => response.text())
                .then(data => {
                    window.location.href = `<?php echo $base; ?>post.php?id=${postId}`;
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
                    window.location.href = `device.php?id=${deviceId}`; // Will redirect to slug
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
    </script>
    <script src="<?php echo $base; ?>script.js"></script>
</body>

</html>