<?php
session_start();
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

$pdo = getConnection();

// Helper function to make image paths absolute
function getAbsoluteImagePath($imagePath, $base)
{
    if (empty($imagePath)) return '';
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
    if (strpos($imagePath, '/') === 0) return $imagePath;
    return $base . ltrim($imagePath, '/');
}

// Get all brands with device counts, ordered alphabetically
$all_brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY b.name ASC
");
$all_brands_stmt->execute();
$allBrands = $all_brands_stmt->fetchAll();

// Get top brands for sidebar (by device count)
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

// Get sidebar data
$topViewedDevices = [];
$topReviewedDevices = [];
$topComparisons = [];

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

try {
    $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
    $topComparisons = [];
}

$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 9);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-2LDCSSMXJT');
</script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="canonical" href="<?php echo $canonicalBase; ?>/brands" />
    <meta name="description" content="Browse all smartphone brands on DevicesArena. Find devices from top manufacturers including Samsung, Apple, Xiaomi, and more." />
    <title>All Brands - DevicesArena</title>

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

    <!-- Theme Color -->
    <meta name="theme-color" content="#1B2035">
    <meta name="msapplication-TileColor" content="#1B2035">
    <meta name="msapplication-TileImage" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="DevicesArena">
    <meta property="og:title" content="All Brands - DevicesArena">
    <meta property="og:description" content="Browse all smartphone brands on DevicesArena. Find devices from top manufacturers.">
    <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="256">
    <meta property="og:image:height" content="256">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="All Brands - DevicesArena">
    <meta name="twitter:description" content="Browse all smartphone brands on DevicesArena. Find devices from top manufacturers.">
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

    <!-- Breadcrumb Schema -->
    <?php
    $breadcrumbItems = [
        ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => "https://www.devicesarena.com/"],
        ["@type" => "ListItem", "position" => 2, "name" => "Brands", "item" => "https://www.devicesarena.com/brands"]
    ];
    ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        }
    </script>

    <!-- CollectionPage Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "CollectionPage",
            "name": "All Brands - DevicesArena",
            "description": "Browse all smartphone brands on DevicesArena. Find devices from top manufacturers including Samsung, Apple, Xiaomi, and more.",
            "url": "https://www.devicesarena.com/brands",
            "image": "https://www.devicesarena.com/imges/icon-256.png",
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

    <!-- ItemList Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ItemList",
            "name": "Smartphone Brands",
            "numberOfItems": <?php echo count($allBrands); ?>,
            "itemListElement": [
                <?php
                $brandSchemaItems = [];
                foreach ($allBrands as $i => $schemaBrand) {
                    $slug = !empty($schemaBrand['slug']) ? $schemaBrand['slug'] : generateSlug($schemaBrand['name']);
                    $brandSchemaItems[] = json_encode([
                        "@type" => "ListItem",
                        "position" => $i + 1,
                        "name" => $schemaBrand['name'],
                        "url" => "https://www.devicesarena.com/brand/" . $slug
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                echo implode(",\n                ", $brandSchemaItems);
                ?>
            ]
        }
    </script>

    <style>
        .brand-grid-item {
            padding: 14px 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .brand-grid-item:hover .brand-name {
            color: #d50000;
        }

        .brand-grid-item:hover .brand-device-count {
            color: #d50000;
        }

        .brand-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .brand-device-count {
            font-size: 0.8rem;
            color: #888;
            margin-top: 2px;
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
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
        crossorigin="anonymous"></script>
</head>

<body style="background-color: #EFEBE9; overflow-x: hidden;">
    <?php include 'includes/gsmheader.php'; ?>

    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-md-8 col-5 d-lg-inline d-none" style="padding: 0; position: relative;">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="<?php echo $base; ?>hero-images/brands-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="header image of brands page for devicesarena.com">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; padding: 0;">
                <button onclick="window.open('<?php echo $base; ?>phonefinder')" class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <?php
                    if (empty($brands)): ?>
                        <button class="px-3 py-1" style="cursor: default;" disabled>No brands available.</button>
                        <?php else:
                        $brandChunks = array_chunk($brands, 1);
                        foreach ($brandChunks as $brandRow):
                            foreach ($brandRow as $brand):
                        ?>
                                <a href="<?php echo $base; ?>brand/<?php echo htmlspecialchars($brand['slug'] ?? generateSlug($brand['name'])); ?>" class="brand-cell brand-item-bold text-decoration-none d-inline-block text-center" style="color: inherit;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
                    <?php
                            endforeach;
                        endforeach;
                    endif;
                    ?>
                </div>
                <div class="d-flex">
                    <a href="<?php echo $base; ?>brands" class="solid w-50 py-2 text-center" style="text-decoration: none; color: white;">
                        <i class="fa-solid fa-bars fa-sm mx-2"></i>
                        All Brands</a>
                    <button onclick="location.href='<?php echo $base; ?>rumored'" class="solid w-50 py-2">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>
                </div>
            </div>
        </div>
    </div>
    <div class="container bg-white" style="border: 1px solid #e0e0e0;">
        <div class="row">
            <div class="col-lg-8 py-3" style="padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div style="padding: 20px 30px;">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                        <h2 class="m-0" style="font-size: 1.4rem; font-weight: 700; color: #1B2035;">All Brands</h2>
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size: 0.9rem; color: #666; font-weight: 500;">Sort By:</span>
                            <div class="dropdown">
                                <button class="btn btn-sm d-flex align-items-center gap-2 py-1 px-3" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: 1px solid #1B2035; color: #1B2035; font-weight: 600; border-radius: 4px; background: #fff; transition: all 0.2s ease;">
                                    <span id="currentSort">Name (A-Z)</span>
                                    <i class="fa fa-chevron-down" style="font-size: 0.7rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="sortDropdown" style="border: 1px solid #eee;">
                                    <li><a class="dropdown-item sort-option active" href="#" data-sort="name">Name (A-Z)</a></li>
                                    <li><a class="dropdown-item sort-option" href="#" data-sort="count">Most Devices</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="brandsGrid">
                        <?php foreach ($allBrands as $brand): 
                            $slug = !empty($brand['slug']) ? $brand['slug'] : generateSlug($brand['name']);
                        ?>
                            <a href="<?php echo $base; ?>brand/<?php echo $slug; ?>"
                                 class="col-6 brand-grid-item text-decoration-none" 
                                 style="color: inherit; display: block;"
                                 data-brand-id="<?php echo $brand['id']; ?>" 
                                 data-name="<?php echo htmlspecialchars(strtolower($brand['name'])); ?>" 
                                 data-count="<?php echo (int)$brand['device_count']; ?>">
                                <div class="brand-name"><?php echo htmlspecialchars($brand['name']); ?></div>
                                <div class="brand-device-count"><?php echo (int)$brand['device_count']; ?> devices</div>
                            </a>
                        <?php endforeach; ?>

                        <?php if (empty($allBrands)): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">No brands available</p>
                            </div>
                        <?php endif; ?>
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

    <script>
    function generateSlug(text) {
        return text.toString().toLowerCase()
            .replace(/&/g, '-and-')
            .replace(/\+/g, '-plus-')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    // Sorting functionality
    document.addEventListener('DOMContentLoaded', function() {
        const brandsGrid = document.getElementById('brandsGrid');
        const sortOptions = document.querySelectorAll('.sort-option');
        const currentSortLabel = document.getElementById('currentSort');
        const sortDropdownBtn = document.getElementById('sortDropdown');

        if (!brandsGrid) return;

        sortOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const criteria = this.dataset.sort;
                
                // Update UI state
                sortOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                currentSortLabel.textContent = this.textContent;

                // Toggle button border/color for feedback
                sortDropdownBtn.style.borderColor = '#d50000';
                sortDropdownBtn.style.color = '#d50000';
                setTimeout(() => {
                    sortDropdownBtn.style.borderColor = '#1B2035';
                    sortDropdownBtn.style.color = '#1B2035';
                }, 400);

                sortBrands(criteria);
            });
        });

        function sortBrands(criteria) {
            const brands = Array.from(brandsGrid.querySelectorAll('.brand-grid-item'));
            
            brands.sort((a, b) => {
                if (criteria === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                } else if (criteria === 'count') {
                    return parseInt(b.dataset.count) - parseInt(a.dataset.count) || a.dataset.name.localeCompare(b.dataset.name);
                }
                return 0;
            });

            // Re-render with a subtle fade effect
            brandsGrid.style.opacity = '0.5';
            brandsGrid.style.transition = 'opacity 0.2s ease';
            
            setTimeout(() => {
                // Clear and re-append
                brands.forEach(brand => brandsGrid.appendChild(brand));
                brandsGrid.style.opacity = '1';
            }, 200);
        }
    });

    // Anchor tags naturally navigate via href
        // Handle clickable table rows for devices (sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.clickable-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    const deviceId = this.getAttribute('data-device-id');
                    if (deviceId) {
                        window.location.href = '<?php echo $base; ?>device/' + deviceId;
                    }
                });
            });

            document.querySelectorAll('.device-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const deviceId = this.getAttribute('data-device-id');
                    if (deviceId) {
                        window.location.href = '<?php echo $base; ?>device/' + deviceId;
                    }
                });
            });

            document.querySelectorAll('.clickable-comparison').forEach(function(row) {
                row.addEventListener('click', function() {
                    const device1Slug = this.getAttribute('data-device1-slug');
                    const device2Slug = this.getAttribute('data-device2-slug');
                    if (device1Slug && device2Slug) {
                        window.location.href = '<?php echo $base; ?>compare/' + device1Slug + '-vs-' + device2Slug;
                    }
                });
            });
        });

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

                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Subscribing...';

                    fetch('<?php echo $base; ?>handle_newsletter.php', {
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