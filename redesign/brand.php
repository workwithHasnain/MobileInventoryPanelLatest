<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

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

// Convert slug to brand name pattern for fallback matching
$brandNamePattern = str_replace('-', ' ', $brandSlug);

// Look up the brand by matching slug first, then fallback to name pattern
$brand_stmt = $pdo->prepare("
    SELECT * FROM brands WHERE slug = :slug OR LOWER(name) = LOWER(:name)
");
$brand_stmt->execute(['slug' => $brandSlug, 'name' => $brandNamePattern]);
$brandData = $brand_stmt->fetch();

if (!$brandData) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$brandName = $brandData['name'];
$brandId = $brandData['id'];

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE brand_id = :brand_id");
$count_stmt->execute(['brand_id' => $brandId]);
$totalDevicesCount = $count_stmt->fetchColumn();

// Get initial 50 phones for this brand
$phones_stmt = $pdo->prepare("
    SELECT p.*, b.name as brand_name
    FROM phones p
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.brand_id = :brand_id
    ORDER BY p.name ASC
    LIMIT 50
");
$phones_stmt->execute(['brand_id' => $brandId]);
$phones = $phones_stmt->fetchAll();

// Get top brands for sidebar
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

// Auth variables for redesign layout
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';
if (!isset($_SESSION['notif_seen'])) $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Other sidebar content
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);
try {
  $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
  $topComparisons = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/brand/<?php echo htmlspecialchars($brandSlug); ?>" />
  <title><?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena</title>
  <meta name="description" content="Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena. View specifications, images, and pricing." />
  <meta property="og:title" content="<?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena" />
  <meta property="og:description" content="Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena." />
  <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png" />
  <meta property="og:type" content="website" />
  <meta name="twitter:card" content="summary" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <?php
  $breadcrumbItems = [
    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => "https://www.devicesarena.com/"],
    ["@type" => "ListItem", "position" => 2, "name" => "Brands", "item" => "https://www.devicesarena.com/brands"],
    ["@type" => "ListItem", "position" => 3, "name" => htmlspecialchars($brandName), "item" => "https://www.devicesarena.com/brand/" . $brandSlug]
  ];
  ?>
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
      }
  </script>

  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "CollectionPage",
          "name": "<?php echo htmlspecialchars($brandName); ?> Phones - DevicesArena",
          "description": "Browse all <?php echo htmlspecialchars($brandName); ?> phones and devices on DevicesArena. View specifications, images, and pricing.",
          "url": "https://www.devicesarena.com/brand/<?php echo $brandSlug; ?>",
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
                      "url" => "https://www.devicesarena.com/device/" . ($schemaPhone['slug'] ?? $schemaPhone['id'])
                  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
              }
              echo implode(",\n              ", $deviceSchemaItems);
              ?>
          ]
      }
  </script>

  <script>
    (function () {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>
</head>

<body>
  <?php include('includes/navbar.php'); ?>
  <?php include('includes/login-modal.php'); ?>
  <?php include('includes/signup-modal.php'); ?>
  <?php include('includes/profile-modal.php'); ?>

  <div class="da-page">
    <!-- HERO SECTION -->
    <div class="cp-hero">
      <div class="cp-hero-bg-container">
        <img class="cp-hero-bg-img" src="<?php echo $base; ?>hero-images/brand-hero.png" alt="<?php echo htmlspecialchars($brandName); ?> background">
      </div>
      <div class="cp-hero-inner">
        <div class="cp-hero-left">
          <div class="cp-hero-label"><span>Brands</span></div>
          <h1 class="cp-hero-title"><?php echo htmlspecialchars($brandName); ?></h1>
          <p class="cp-hero-sub">Explore the complete catalog of <?php echo htmlspecialchars($brandName); ?> devices, from their latest flagship releases to classic models.</p>
        </div>

        <div class="cp-hero-right">
          <div class="da-section-label"><span>Explore</span></div>
          <div class="da-classic-brand-widget">
            <div class="da-cbw-header">
              <a href="<?php echo $base; ?>phonefinder"><i class="fa fa-mobile-screen"></i> PHONE FINDER</a>
            </div>
            <div class="da-cbw-grid">
              <?php foreach (array_slice($brands, 0, 32) as $index => $sbBrand):
                $sbSlug = strtolower(preg_replace('/\s+/', '-', trim($sbBrand['name'])));
                ?>
                <a href="<?php echo $base; ?>brand/<?php echo urlencode($sbSlug); ?>" class="da-cbw-item" title="<?php echo htmlspecialchars($sbBrand['name']); ?>">
                  <?php echo strtoupper(htmlspecialchars($sbBrand['name'])); ?>
                </a>
              <?php endforeach; ?>
            </div>
            <div class="da-cbw-footer">
              <a href="<?php echo $base; ?>brands" class="da-cbw-btn left"><i class="fa fa-bars"></i> ALL BRANDS</a>
              <a href="<?php echo $base; ?>rumored" class="da-cbw-btn right"><i class="fa fa-bullhorn"></i> RUMORS MILL</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="da-content-area">
      <main>
        <div class="da-brands-list-container">
          <div class="da-brands-header">
            <div>
              <div class="da-section-label"><span>Devices</span></div>
              <h2 class="da-section-title"><?php echo htmlspecialchars($brandName); ?> Phones</h2>
              <div class="da-brand-device-count"><?php echo $totalDevicesCount; ?> devices available</div>
            </div>
            
            <div class="da-brands-sort">
              <span class="da-brands-sort-label">Sort By:</span>
              <div class="dropdown">
                <select class="da-sort-dropdown-btn" id="brandDeviceSorter" style="background: var(--bg-primary); border: 1px solid var(--border); color: var(--text-primary); padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; cursor: pointer; outline: none; transition: var(--transition);">
                    <option value="default">Name (A-Z)</option>
                    <option value="latest-desc">Latest Release</option>
                    <option value="latest-asc">Oldest Release</option>
                    <option value="views-desc">Most Views</option>
                    <option value="views-asc">Least Views</option>
                    <option value="comments-desc">Most Comments</option>
                    <option value="comments-asc">Least Comments</option>
                </select>
              </div>
            </div>
          </div>

          <div class="da-device-grid" id="brandDevicesGrid">
            <?php foreach ($phones as $phone):
                $imagePath = $phone['image'] ?? '';
                if ($imagePath && !str_starts_with($imagePath, '/') && !str_starts_with($imagePath, 'http')) {
                    $imagePath = '/' . $imagePath;
                }
                $deviceSlug = $phone['slug'] ?? $phone['id'];
                
                $badgeClass = 'year'; // Default
                $availClass = 'available';
                switch ($phone['availability']) {
                    case 'Available': $availClass = 'available'; break;
                    case 'Coming Soon': $availClass = 'coming-soon'; break;
                    case 'Discontinued': $availClass = 'discontinued'; break;
                    case 'Rumored': $availClass = 'rumored'; break;
                }
            ?>
                <a href="<?php echo $base; ?>device/<?php echo htmlspecialchars($deviceSlug); ?>" class="da-device-card">
                    <div class="da-device-card-img-wrap">
                        <?php if ($imagePath): ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($phone['name']); ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                            <i class="fas fa-mobile-alt fa-3x" style="color: var(--border);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="da-device-card-body">
                        <h5 class="da-device-card-title"><?php echo htmlspecialchars($phone['name']); ?></h5>
                        <div class="da-device-card-info-row">
                            <span class="da-device-card-brand"><?php echo htmlspecialchars($phone['brand_name'] ?? $brandName); ?></span>
                            <span class="da-device-card-badge year"><?php echo htmlspecialchars($phone['year'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="da-device-card-info-row">
                            <span class="da-device-card-price"><?php echo !empty($phone['price']) ? '$' . number_format((float)$phone['price'], 0) : 'N/A'; ?></span>
                            <span class="da-device-card-badge <?php echo $availClass; ?>"><?php echo htmlspecialchars($phone['availability'] ?? 'Unknown'); ?></span>
                        </div>
                        <div class="da-device-card-specs">
                            <?php if (!empty($phone['ram'])): ?>
                                <div class="da-device-card-spec-item" title="RAM"><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($phone['ram']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($phone['storage'])): ?>
                                <div class="da-device-card-spec-item" title="Storage"><i class="fas fa-database"></i> <?php echo htmlspecialchars($phone['storage']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($phone['display_size'])): ?>
                                <div class="da-device-card-spec-item" title="Display"><i class="fas fa-desktop"></i> <?php echo htmlspecialchars(str_replace('"', '', $phone['display_size'])) . '"'; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($phone['main_camera_resolution'])): ?>
                                <div class="da-device-card-spec-item" title="Camera"><i class="fas fa-camera"></i> <?php echo is_numeric($phone['main_camera_resolution']) ? htmlspecialchars($phone['main_camera_resolution']) . ' MP' : htmlspecialchars($phone['main_camera_resolution']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($phones)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-mobile-alt fa-3x mb-3" style="color: var(--border);"></i>
                    <p style="color: var(--text-muted);">No devices available for <?php echo htmlspecialchars($brandName); ?></p>
                </div>
            <?php endif; ?>
          </div>
          
          <?php if ($totalDevicesCount > 50): ?>
              <div class="text-center mt-4" id="brandLoadMoreContainer">
                  <button id="brandLoadMoreBtn" class="da-sort-dropdown-btn mx-auto">Load More</button>
              </div>
          <?php endif; ?>
        </div>
      </main>

      <aside class="da-sidebar">
        <?php include('includes/sidebar/latest-devices.php'); ?>
        <?php include('includes/sidebar/popular-comparisons.php'); ?>
        <?php include('includes/sidebar/top-daily-interests.php'); ?>
        <?php include('includes/sidebar/top-by-fans.php'); ?>
      </aside>
    </div>

    <?php include('includes/bottom-area/in-stores-now.php'); ?>
    <?php include('includes/bottom-area/trending-comparisons.php'); ?>
    <?php include('includes/bottom-area/brand-marquee.php'); ?>
  </div>

  <?php include('includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Base UI Scripts (Navbar, Modals, Search, Theme) -->
  <script>
    window.baseURL = '<?php echo $base; ?>';

    // ── Theme Toggle ──
    const themeToggles = [document.getElementById('da-theme-toggle'), document.getElementById('da-mobile-theme-toggle')];

    function updateThemeIcons() {
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      themeToggles.forEach(btn => {
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (icon) {
          icon.className = isLight ? 'fa fa-moon' : 'fa fa-sun';
        }
      });
    }
    updateThemeIcons();

    themeToggles.forEach(btn => {
      if (btn) {
        btn.addEventListener('click', () => {
          if (document.documentElement.getAttribute('data-theme') === 'light') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('da-theme', 'dark');
          } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('da-theme', 'light');
          }
          updateThemeIcons();
        });
      }
    });

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      if(navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, {
      passive: true
    });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        mobileMenu.classList.toggle('open');
        document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
        });
    }

    function closeMobileMenu() {
      if (hamburger) hamburger.classList.remove('open');
      if (mobileMenu) mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Brand Strip Arrows ──
    const brandScroll = document.getElementById('brand-strip-scroll');
    const bLeft = document.getElementById('brand-strip-left');
    const bRight = document.getElementById('brand-strip-right');
    if (brandScroll && bLeft) {
        bLeft.addEventListener('click', () => brandScroll.scrollBy({ left: -300, behavior: 'smooth' }));
    }
    if (brandScroll && bRight) {
        bRight.addEventListener('click', () => brandScroll.scrollBy({ left: 300, behavior: 'smooth' }));
    }

    // ── Live Search ──
    const searchInput = document.getElementById('da-search-input');
    const searchResults = document.getElementById('da-search-results');
    if (searchInput && searchResults) {
      let searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
          searchResults.classList.remove('open');
          return;
        }
        searchTimer = setTimeout(() => {
          Promise.all([
            fetch(baseURL + 'api_get_devices.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              devices: []
            })),
            fetch(baseURL + 'api_get_posts.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              posts: []
            }))
          ]).then(([devData, postData]) => {
            const devices = devData.devices || [];
            const posts = postData.posts || [];
            if (!devices.length && !posts.length) {
              searchResults.innerHTML = '<div class="da-search-result-item"><div class="sr-text">No results found</div></div>';
              searchResults.classList.add('open');
              return;
            }
            let html = '';
            devices.forEach(d => {
              html += `<a href="${baseURL}device/${encodeURIComponent(d.slug || d.id)}" class="da-search-result-item">
          ${d.image ? `<img src="${d.image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${d.name}</div><div class="sr-meta"><i class="fa fa-mobile-screen me-1"></i>${d.brand_name || 'Device'}</div></div>
        </a>`;
            });
            posts.forEach(p => {
              html += `<a href="${baseURL}post/${encodeURIComponent(p.slug)}" class="da-search-result-item">
          ${p.featured_image ? `<img src="${p.featured_image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${p.title}</div><div class="sr-meta"><i class="fa fa-newspaper me-1"></i>${p.created_at ? p.created_at.substring(0, 10) : 'Article'}</div></div>
        </a>`;
            });
            searchResults.innerHTML = html;
            searchResults.classList.add('open');
          });
        }, 320);
      });
      document.addEventListener('click', (e) => {
        const wrap = document.getElementById('da-search-wrap');
        if (wrap && !wrap.contains(e.target)) searchResults.classList.remove('open');
      });
    }

    // ── Newsletter ──
    const nlBtn = document.getElementById('da-newsletter-btn');
    if (nlBtn) {
        nlBtn.addEventListener('click', function () {
        const email = document.getElementById('da-newsletter-email').value.trim();
        const msg = document.getElementById('da-newsletter-msg');
        if (!email) {
            msg.textContent = 'Please enter your email.';
            msg.className = 'error';
            return;
        }
        this.disabled = true;
        this.textContent = 'Subscribing...';
        const btn = this;
        fetch(baseURL + 'handle_newsletter.php', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'newsletter_email=' + encodeURIComponent(email)
        })
            .then(r => r.json())
            .then(data => {
            msg.textContent = data.message;
            msg.className = data.success ? 'success' : 'error';
            if (data.success) document.getElementById('da-newsletter-email').value = '';
            btn.disabled = false;
            btn.textContent = 'Subscribe';
            }).catch(() => {
            msg.textContent = 'An error occurred.';
            msg.className = 'error';
            btn.disabled = false;
            btn.textContent = 'Subscribe';
            });
        });
    }

    // ── Notification mark seen ──
    function markNotificationsAsSeen() {
      const dots = ['notifDotDesktop'];
      dots.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });
      fetch(baseURL + 'notification_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=mark_seen'
      }).catch(() => { });
    }
    const bell = document.getElementById('notificationBellDesktop');
    if (bell) bell.addEventListener('click', () => setTimeout(markNotificationsAsSeen, 100));

    // ── Auth helpers ──
    function userAuthFetch(action, fd) {
      fd.append('action', action);
      return fetch(baseURL + 'user_auth_handler.php', {
        method: 'POST',
        body: fd
      }).then(r => r.json());
    }

    function showAuthMsg(id, msg, type) {
      const el = document.getElementById(id);
      if(!el) return;
      el.className = 'alert alert-' + type + ' alert-dismissible fade show';
      el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
      el.style.display = 'block';
    }

    const loginForm = document.getElementById('publicLoginForm');
    if (loginForm) loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('loginSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Logging in...';
      userAuthFetch('login', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('login-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('login-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
        }
      }).catch(() => {
        showAuthMsg('login-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
      });
    });

    const signupForm = document.getElementById('publicSignupForm');
    if (signupForm) signupForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('signupSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Creating account...';
      userAuthFetch('register', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('signup-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('signup-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
        }
      }).catch(() => {
        showAuthMsg('signup-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
      });
    });

    function openProfileModal() {
      const modal = new bootstrap.Modal(document.getElementById('profileModal'));
      userAuthFetch('get_profile', new FormData()).then(data => {
        if (data.success && data.user) {
          document.getElementById('profile-name').value = data.user.name;
          document.getElementById('profile-email').value = data.user.email;
        }
      });
      document.getElementById('profile-current-password').value = '';
      document.getElementById('profile-new-password').value = '';
      document.getElementById('delete-account-password').value = '';
      document.getElementById('profile-message').style.display = 'none';
      modal.show();
    }

    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) profileForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('profileUpdateBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving...';
      userAuthFetch('update_profile', new FormData(this)).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      });
    });

    function deletePublicAccount() {
      if (!confirm('Permanently delete your account? This cannot be undone.')) return;
      const pwd = document.getElementById('delete-account-password').value.trim();
      if (!pwd) {
        showAuthMsg('profile-message', 'Please enter your password.', 'warning');
        return;
      }
      const btn = document.getElementById('deleteAccountBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Deleting...';
      const fd = new FormData();
      fd.append('password', pwd);
      userAuthFetch('delete_account', fd).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        else {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
        }
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
      });
    }

    function publicUserLogout() {
      fetch(baseURL + 'notification_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=reset'
      })
        .finally(() => {
          userAuthFetch('logout', new FormData()).then(() => location.reload());
        });
    }

    function switchToSignup() {
      bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('signupModal')).show(), 300);
    }

    function switchToLogin() {
      bootstrap.Modal.getInstance(document.getElementById('signupModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('loginModal')).show(), 300);
    }
  </script>

  <!-- Separate Script Block for Brand Devices Fetching & Sorting -->
  <script>
        const brandId = <?php echo $brandId; ?>;
        const totalDevices = <?php echo $totalDevicesCount; ?>;
        const brandSlug = "<?php echo htmlspecialchars($brandSlug); ?>";
        let currentPage = 1;
        const base_URL = '<?php echo $base; ?>';
        
        function getBrandDeviceCard(phone) {
            const imagePath = phone.image ? (phone.image.startsWith('/') || phone.image.startsWith('http') ? phone.image : '/' + phone.image) : '';
            const deviceSlug = phone.slug || phone.id;
            
            let availClass = 'available';
            switch (phone.availability) {
                case 'Available': availClass = 'available'; break;
                case 'Coming Soon': availClass = 'coming-soon'; break;
                case 'Discontinued': availClass = 'discontinued'; break;
                case 'Rumored': availClass = 'rumored'; break;
            }
            
            let imageHtml = imagePath ? `<img src="${imagePath}" alt="${phone.name}" onerror="this.style.display='none'">` : `<i class="fas fa-mobile-alt fa-3x" style="color: var(--border);"></i>`;
            
            let specsHtml = '';
            if (phone.ram) specsHtml += `<div class="da-device-card-spec-item" title="RAM"><i class="fas fa-microchip"></i> ${phone.ram}</div>`;
            if (phone.storage) specsHtml += `<div class="da-device-card-spec-item" title="Storage"><i class="fas fa-database"></i> ${phone.storage}</div>`;
            if (phone.display_size) specsHtml += `<div class="da-device-card-spec-item" title="Display"><i class="fas fa-desktop"></i> ${phone.display_size.replace('"', '')}"</div>`;
            if (phone.main_camera_resolution) {
                let cam = !isNaN(phone.main_camera_resolution) ? phone.main_camera_resolution + ' MP' : phone.main_camera_resolution;
                specsHtml += `<div class="da-device-card-spec-item" title="Camera"><i class="fas fa-camera"></i> ${cam}</div>`;
            }

            const price = parseFloat(phone.price);
            const priceHtml = (!isNaN(price) && price > 0) ? '$' + price.toLocaleString() : 'N/A';
            const year = phone.year || 'N/A';

            return `
                <a href="${base_URL}device/${deviceSlug}" class="da-device-card">
                    <div class="da-device-card-img-wrap">
                        ${imageHtml}
                    </div>
                    <div class="da-device-card-body">
                        <h5 class="da-device-card-title">${phone.name}</h5>
                        <div class="da-device-card-info-row">
                            <span class="da-device-card-brand">${phone.brand_name || '<?php echo htmlspecialchars($brandName); ?>'}</span>
                            <span class="da-device-card-badge year">${year}</span>
                        </div>
                        <div class="da-device-card-info-row">
                            <span class="da-device-card-price">${priceHtml}</span>
                            <span class="da-device-card-badge ${availClass}">${phone.availability || 'Unknown'}</span>
                        </div>
                        <div class="da-device-card-specs">
                            ${specsHtml}
                        </div>
                    </div>
                </a>
            `;
        }
        
        function loadBrandDevices(page, isAppend) {
            const sort = document.getElementById('brandDeviceSorter').value;
            const container = document.getElementById('brandDevicesGrid');
            let btn = document.getElementById('brandLoadMoreBtn');
            let btnContainer = document.getElementById('brandLoadMoreContainer');
            
            if (!isAppend) {
                container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;"><i class="fa fa-spinner fa-spin fa-3x mb-3" style="color: var(--text-muted);"></i><p style="color: var(--text-muted);">Loading devices...</p></div>';
            } else if (btn) {
                btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Loading...';
                btn.disabled = true;
            }
            
            fetch(`${base_URL}api_get_brand_devices.php?brand_id=${brandId}&page=${page}&sort=${sort}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (!isAppend) container.innerHTML = '';
                        
                        // Refetch elements since DOM might have changed
                        btnContainer = document.getElementById('brandLoadMoreContainer');
                        btn = document.getElementById('brandLoadMoreBtn');
                        
                        if (btnContainer && btnContainer.parentNode) {
                            btnContainer.parentNode.removeChild(btnContainer);
                        }
                        
                        if (data.devices.length === 0 && !isAppend) {
                            container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;"><i class="fas fa-mobile-alt fa-3x mb-3" style="color: var(--border);"></i><p style="color: var(--text-muted);">No devices available.</p></div>';
                            return;
                        }

                        let htmlBuffer = '';
                        data.devices.forEach(device => {
                            htmlBuffer += getBrandDeviceCard(device);
                        });
                        container.insertAdjacentHTML('beforeend', htmlBuffer);
                        
                        currentPage = page;
                        
                        if (data.page < data.total_pages) {
                            // Append Load More button after the grid
                            const newBtnContainer = document.createElement('div');
                            newBtnContainer.className = 'text-center mt-4';
                            newBtnContainer.id = 'brandLoadMoreContainer';
                            newBtnContainer.innerHTML = '<button id="brandLoadMoreBtn" class="da-sort-dropdown-btn mx-auto">Load More</button>';
                            container.parentNode.appendChild(newBtnContainer);
                            
                            document.getElementById('brandLoadMoreBtn').addEventListener('click', function() {
                                loadBrandDevices(currentPage + 1, true);
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error("Failed to load devices", err);
                    if (!isAppend) {
                        container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--accent);">Failed to load.</div>';
                    } else if (btn) {
                        btn.innerHTML = 'Load More';
                        btn.disabled = false;
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sorter = document.getElementById('brandDeviceSorter');
            if (sorter) {
                sorter.addEventListener('change', function() {
                    loadBrandDevices(1, false);
                });
            }
            
            const btn = document.getElementById('brandLoadMoreBtn');
            if (btn) {
                btn.addEventListener('click', function() {
                    loadBrandDevices(currentPage + 1, true);
                });
            }
        });
  </script>
</body>
</html>