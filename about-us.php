<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath))
    return '';
  if (filter_var($imagePath, FILTER_VALIDATE_URL))
    return $imagePath;
  if (strpos($imagePath, '/') === 0)
    return $imagePath;
  return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

// Auth
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';

if (!isset($_SESSION['notif_seen']))
  $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Weekly posts for notifications
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) {
  $weekly_posts = [];
}


// Posts — featured only
$posts_stmt = $pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') as comment_count FROM posts p WHERE p.status ILIKE 'published' AND p.is_featured = true ORDER BY p.created_at DESC LIMIT 20");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();

// Comparisons
try {
  $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
  $topComparisons = [];
}

// Latest devices
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);

// Brands
$brands_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 36");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/about-us" />
  <title>DevicesArena — About Us</title>
  <meta name="description"
    content="About Us, top device reviews, and expert insights about mobile technology on DevicesArena." />
  <meta property="og:title" content="DevicesArena — About Us" />
  <meta property="og:description"
    content="About Us, top device reviews, and expert insights about mobile technology." />
  <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png" />
  <meta property="og:type" content="website" />
  <meta name="twitter:card" content="summary" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446"
    crossorigin="anonymous"></script>
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'G-2LDCSSMXJT');
  </script>

  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <!-- Schema.org Structured Data for About Us Page -->
  <?php
  // Build breadcrumb schema for the about us page
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
      "name" => "About Us",
      "item" => "https://www.devicesarena.com/about-us"
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

  <!-- AboutPage Schema -->
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "AboutPage",
          "name": "About Us - DevicesArena",
          "headline": "About DevicesArena",
          "description": "Learn about DevicesArena - our mission, team, and commitment to providing comprehensive smartphone specifications, reviews, and comparisons.",
          "url": "https://www.devicesarena.com/about-us",
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

  <!-- FAQ Schema for About Us Page -->
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "name": "DevicesArena About Us FAQs",
          "url": "https://www.devicesarena.com/about-us",
          "breadcrumb": {
              "@type": "BreadcrumbList",
              "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
          },
          "mainEntity": [{
                  "@type": "Question",
                  "name": "What is DevicesArena?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "DevicesArena is a comprehensive online platform dedicated to providing detailed smartphone specifications, in-depth reviews, side-by-side comparisons, and the latest tech news to help users make informed purchasing decisions."
                  }
              },
              {
                  "@type": "Question",
                  "name": "What kind of content does DevicesArena provide?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "We provide detailed device specifications, expert reviews, side-by-side device comparisons, tech news articles, and a phone finder tool to help users discover the perfect smartphone based on their preferences and budget."
                  }
              },
              {
                  "@type": "Question",
                  "name": "How does DevicesArena ensure accuracy of device specifications?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "Our team carefully verifies all device specifications from official manufacturer sources and trusted industry databases. We regularly update our listings to ensure accuracy and welcome user feedback to correct any discrepancies."
                  }
              },
              {
                  "@type": "Question",
                  "name": "How can I contribute to DevicesArena?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "You can contribute by leaving reviews and ratings on devices, reporting errors or inaccuracies in specifications, suggesting new features, or reaching out through our contact page for collaboration opportunities."
                  }
              }
          ]
      }
  </script>

  <!-- Theme Initialization Script (Prevents FOUC) -->
  <script>
    (function () {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
    crossorigin="anonymous"></script>
</head>

<body>

  <!-- ══════════════════════ NAVBAR ══════════════════════ -->
  <?php include('includes/navbar.php'); ?>

  <!-- ══════════════════════ AUTH MODALS ══════════════════════ -->
  <!-- Login -->
  <?php include('includes/login-modal.php'); ?>
  <!-- Sign Up -->
  <?php include('includes/signup-modal.php'); ?>

  <!-- Profile -->
  <?php include('includes/profile-modal.php'); ?>

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-page">

    <!-- ── HERO NEWSROOM ── -->
    <div class="cp-hero">

      <!-- Background Image Implementation based on original layout -->
      <div class="cp-hero-bg-container">
        <img class="cp-hero-bg-img" src="<?php echo $base; ?>hero-images/about-hero.png" alt="about us page background">
      </div>

      <div class="cp-hero-inner">
        <div class="cp-hero-left">
          <div class="cp-hero-label"><span>DevicesArena</span></div>
          <h1 class="cp-hero-title">About Us</h1>
          <p class="cp-hero-sub">Learn about DevicesArena, your ultimate source for smartphone specifications,
            comparisons, tech news, and industry insights.</p>
        </div>

        <!-- Right: Brand panel (Classic Widget) -->
        <div class="cp-hero-right">
          <div class="da-section-label"><span>Brands</span></div>
          <div class="da-classic-brand-widget">
            <!-- Top header -->
            <div class="da-cbw-header">
              <a href="<?php echo $base; ?>phonefinder">
                <i class="fa fa-mobile-screen"></i> PHONE FINDER
              </a>
            </div>

            <!-- Brand Grid -->
            <div class="da-cbw-grid">
              <?php foreach (array_slice($brands, 0, 32) as $index => $brand):
                $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
                ?>
                <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-cbw-item"
                  title="<?php echo htmlspecialchars($brand['name']); ?>">
                  <?php echo strtoupper(htmlspecialchars($brand['name'])); ?>
                </a>
              <?php endforeach; ?>
            </div>

            <!-- Bottom buttons -->
            <div class="da-cbw-footer">
              <a href="<?php echo $base; ?>brands" class="da-cbw-btn left">
                <i class="fa fa-bars"></i> ALL BRANDS
              </a>
              <a href="<?php echo $base; ?>rumored" class="da-cbw-btn right">
                <i class="fa fa-bullhorn"></i> RUMORS MILL
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
    <!-- ── POST FEED + SIDEBAR ── -->
    <div class="da-content-area">
      <!-- Post Feed -->
      <main>
        <div class="da-post-feed-header">
          <div>
            <div class="da-section-label"><span>About Us</span></div>
            <h2 class="da-section-title">Who We Are</h2>
          </div>
        </div>

        <div class="da-about-content">
          <h4 class="da-about-heading">About DevicesArena</h4>
          <p class="da-about-text">DevicesArena is a comprehensive online platform dedicated to smartphones and mobile
            technology. We provide detailed device specifications, expert reviews, side-by-side comparisons, and the
            latest tech news to help you make informed decisions.</p>

          <h4 class="da-about-heading">Our Mission</h4>
          <p class="da-about-text">Our mission is to empower consumers with accurate, up-to-date information about
            mobile devices. Whether you're researching your next smartphone purchase, comparing specifications, or
            staying updated on the latest tech trends, DevicesArena is your trusted companion.</p>

          <h4 class="da-about-heading">What We Offer</h4>
          <ul class="da-about-list">
            <li><strong>Detailed Specifications:</strong> Comprehensive specs for thousands of smartphones from all
              major brands.</li>
            <li><strong>Device Comparisons:</strong> Side-by-side comparisons to help you choose the right device.</li>
            <li><strong>Expert Reviews:</strong> In-depth reviews covering design, performance, camera quality, and
              more.</li>
            <li><strong>Phone Finder:</strong> Advanced filtering tools to discover devices that match your needs and
              budget.</li>
            <li><strong>Tech News:</strong> Stay updated with the latest developments in the mobile industry.</li>
          </ul>

          <h4 class="da-about-heading">Why Trust DevicesArena?</h4>
          <ul class="da-about-list">
            <li>Verified specifications sourced from official manufacturers.</li>
            <li>Regularly updated database with the latest devices.</li>
            <li>Unbiased reviews and comparisons.</li>
            <li>Community-driven feedback and ratings.</li>
            <li>Dedicated team passionate about mobile technology.</li>
          </ul>

          <h4 class="da-about-heading">Get In Touch</h4>
          <p class="da-about-text">Have questions, suggestions, or feedback? We'd love to hear from you. Visit our <a
              href="<?php echo $base; ?>contact-us" class="da-about-link">Contact Us</a> page to get in touch with our
            team.</p>
        </div>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">

        <!-- Latest Devices -->
        <?php include('includes/sidebar/latest-devices.php'); ?>

      </aside>
    </div>
    <!-- BOTTOM AREA -->

    <!-- ── IN STORES NOW ── -->
    <?php include('includes/bottom-area/in-stores-now.php'); ?>

    <!-- ── TRENDING COMPARISONS ── -->
    <?php include('includes/bottom-area/trending-comparisons.php'); ?>

    <!-- ── FEATURED POSTS TICKER ── -->
    <?php
    $tickerLabel = 'Featured';
    $tickerTitle = 'All Featured Posts';
    $tickerLink = 'featured';
    include('includes/bottom-area/featured-posts.php');
    ?>

    <!-- ── INFINITE BRAND MARQUEE ── -->
    <?php include('includes/bottom-area/brand-marquee.php'); ?>

  </div>

  <!-- ══════════════════════ NEW FOOTER ══════════════════════ -->
  <?php include('includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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

    // Auto-Sliders moved to redesign/sliders.js

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, {
      passive: true
    });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
    });

    function closeMobileMenu() {
      hamburger.classList.remove('open');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Brand Strip Arrows ──
    const brandScroll = document.getElementById('brand-strip-scroll');
    document.getElementById('brand-strip-left').addEventListener('click', () => brandScroll.scrollBy({
      left: -300,
      behavior: 'smooth'
    }));
    document.getElementById('brand-strip-right').addEventListener('click', () => brandScroll.scrollBy({
      left: 300,
      behavior: 'smooth'
    }));

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
    document.getElementById('da-newsletter-btn').addEventListener('click', function () {
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

    // ── Infinite horizontal post scroll ──
    (function () {
      let page = 1,
        loading = false,
        hasMore = <?php echo count($posts) >= 20 ? 'true' : 'false'; ?>;
      const container = document.getElementById('featured-scroll-container');
      const loader = document.getElementById('featured-load-more');
      if (!container) return;
      container.addEventListener('scroll', function () {
        if (loading || !hasMore) return;
        if (this.scrollLeft + this.clientWidth >= this.scrollWidth - 300) loadMore();
      }, {
        passive: true
      });

      function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        page++;
        if (loader) loader.style.display = 'flex';
        fetch(baseURL + 'load_posts.php?page=' + page + '&type=featured&format=block')
          .then(r => r.json())
          .then(data => {
            if (data.success && data.html) {
              if (loader) loader.insertAdjacentHTML('beforebegin', data.html);
              hasMore = data.hasMore;
              // Re-skin new items
              container.querySelectorAll('.div-block').forEach(el => {
                if (!el.classList.contains('da-ticker-item')) el.classList.add('da-ticker-compat');
              });
            } else {
              hasMore = false;
            }
            if (!hasMore && loader) loader.style.display = 'none';
            loading = false;
          }).catch(() => {
            loading = false;
            if (loader) loader.style.display = 'none';
          });
      }
    })();
  </script>
  <script src="<?php echo $base; ?>redesign/sliders.js"></script>
</body>

</html>