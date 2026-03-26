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

// Get brand slug from URL
$brandSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($brandSlug)) {
    header('Location: ' . $base . 'brands');
    exit;
}

// Convert slug to brand name pattern (replace hyphens with spaces for matching)
$brandNamePattern = str_replace('-', ' ', $brandSlug);

// Look up the brand by matching lowercased name
$brand_stmt = $pdo->prepare("
    SELECT * FROM brands WHERE LOWER(name) = LOWER(:name)
");
$brand_stmt->execute(['name' => $brandNamePattern]);
$brandData = $brand_stmt->fetch();

if (!$brandData) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$brandName = $brandData['name'];
$brandId = $brandData['id'];

// Get all phones for this brand
$phones_stmt = $pdo->prepare("
    SELECT id, name, image, slug
    FROM phones
    WHERE brand_id = :brand_id
    ORDER BY name ASC
");
$phones_stmt->execute(['brand_id' => $brandId]);
$phones = $phones_stmt->fetchAll();

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

// Helper to generate brand slug from name
function brandSlugFromName($name)
{
    return strtolower(str_replace(' ', '-', trim($name)));
}

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
    <link rel="canonical" href="<?php echo $canonicalBase; ?>/brand/<?php echo htmlspecialchars(urlencode($brandSlug)); ?>" />
    <meta name="description" content="Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena. View specifications, images, and pricing." />
    <title><?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena</title>

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
    <meta property="og:title" content="<?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena">
    <meta property="og:description" content="Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena.">
    <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="256">
    <meta property="og:image:height" content="256">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena">
    <meta name="twitter:description" content="Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena.">
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
        ["@type" => "ListItem", "position" => 2, "name" => "Brands", "item" => "https://www.devicesarena.com/brands"],
        ["@type" => "ListItem", "position" => 3, "name" => htmlspecialchars($brandName), "item" => "https://www.devicesarena.com/brand/" . urlencode($brandSlug)]
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
            "name": "<?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena",
            "description": "Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena. View specifications, images, and pricing.",
            "url": "https://www.devicesarena.com/brand/<?php echo urlencode($brandSlug); ?>",
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
            "name": "<?php echo htmlspecialchars($brandName); ?> Devices",
            "numberOfItems": <?php echo count($phones); ?>,
            "itemListElement": [
                <?php
                $deviceSchemaItems = [];
                foreach ($phones as $i => $schemaPhone) {
                    $deviceSchemaItems[] = json_encode([
                        "@type" => "ListItem",
                        "position" => $i + 1,
                        "name" => $schemaPhone['name'],
                        "url" => "https://www.devicesarena.com/device/" . urlencode($schemaPhone['slug'] ?? $schemaPhone['id'])
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                echo implode(",\n                ", $deviceSchemaItems);
                ?>
            ]
        }
    </script>

    <style>
        .device-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0;
            border-top: 1px solid #ddd;
            border-left: 1px solid #ddd;
        }

        .device-grid-item {
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            text-align: center;
            padding: 15px 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .device-grid-item:hover {
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
        }

        .device-grid-item img {
            width: 100%;
            max-width: 120px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .device-grid-item .device-name {
            font-size: 0.85rem;
            font-weight: 400;
            color: #333;
            line-height: 1.3;
            word-break: break-word;
        }

        .device-grid-item:hover .device-name {
            color: #d50000;
        }

        .brand-page-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
        }

        .brand-page-header h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .brand-page-header .device-count {
            font-size: 0.85rem;
            color: #888;
            margin-top: 2px;
        }

        .no-image-placeholder {
            width: 100%;
            max-width: 120px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            color: #ccc;
            margin-bottom: 8px;
        }

        @media (max-width: 991px) {
            .device-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 767px) {
            .device-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .device-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
                    <img class="w-100 h-100" src="<?php echo $base; ?>hero-images/brand-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="<?php echo htmlspecialchars($brandName); ?> phones on DevicesArena">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; padding: 0;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <?php
                    if (empty($brands)): ?>
                        <button class="px-3 py-1" style="cursor: default;" disabled>No brands available.</button>
                        <?php else:
                        $brandChunks = array_chunk($brands, 1);
                        foreach ($brandChunks as $brandRow):
                            foreach ($brandRow as $b):
                        ?>
                                <button class="brand-cell brand-item-bold" style="cursor: pointer;" data-brand-id="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></button>
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
            <div class="col-lg-8 py-0" style="padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div class="brand-page-header">
                    <h1><?php echo htmlspecialchars($brandName); ?> phones</h1>
                    <div class="device-count"><?php echo count($phones); ?> devices</div>
                </div>
                <div class="device-grid">
                    <?php foreach ($phones as $phone):
                        $imagePath = $phone['image'] ?? '';
                        if ($imagePath && !str_starts_with($imagePath, '/') && !str_starts_with($imagePath, 'http')) {
                            $imagePath = '/' . $imagePath;
                        }
                        $deviceSlug = $phone['slug'] ?? $phone['id'];
                    ?>
                        <a href="<?php echo $base; ?>device/<?php echo htmlspecialchars(urlencode($deviceSlug)); ?>" class="device-grid-item">
                            <?php if ($imagePath): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($phone['name']); ?>" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-mobile-alt fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <span class="device-name"><?php echo htmlspecialchars($phone['name']); ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if (empty($phones)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No devices available for <?php echo htmlspecialchars($brandName); ?></p>
                        </div>
                    <?php endif; ?>
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
        // Handle brand cell clicks (from sidebar - navigate to brand page)
        document.querySelectorAll('.brand-cell').forEach(function(cell) {
            cell.addEventListener('click', function(e) {
                e.preventDefault();
                const brandName = this.textContent.trim().toLowerCase().replace(/\s+/g, '-');
                window.location.href = '<?php echo $base; ?>brand/' + encodeURIComponent(brandName);
            });
        });

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