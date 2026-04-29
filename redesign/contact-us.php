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

  <!-- Schema.org Structured Data for Contact Page -->
  <?php
  // Build breadcrumb schema for the contact page
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
      "name" => "Contact Us",
      "item" => "https://www.devicesarena.com/contact-us"
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
          "@type": "ContactPage",
          "name": "Contact Us - DevicesArena",
          "headline": "Get in Touch with DevicesArena",
          "description": "Contact DevicesArena for inquiries about device reviews, specifications, comparisons, and other technology-related questions.",
          "url": "https://www.devicesarena.com/contact-us",
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

  <!-- FAQ Schema for Contact Page -->
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "name": "DevicesArena Contact Page FAQs",
          "url": "https://www.devicesarena.com/contact-us",
          "breadcrumb": {
              "@type": "BreadcrumbList",
              "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
          },
          "mainEntity": [{
                  "@type": "Question",
                  "name": "How can I contact DevicesArena?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "You can reach out to DevicesArena through our contact form on this page. Fill in your name, email, subject, and message, and our team will get back to you as soon as possible. We typically respond to inquiries within 24-48 hours."
                  }
              },
              {
                  "@type": "Question",
                  "name": "What should I include in my inquiry?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "Please include a clear subject line, your full name, valid email address, and a detailed description of your inquiry. The more information you provide, the quicker we can assist you."
                  }
              },
              {
                  "@type": "Question",
                  "name": "Can I request a device review?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "Yes! We accept requests for device reviews. Use our contact form to submit your review request along with details about the device and why you'd like us to review it. Our editorial team will review your request and respond accordingly."
                  }
              },
              {
                  "@type": "Question",
                  "name": "How do I report an error or inaccuracy?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "If you notice any errors or inaccuracies in our device specifications, reviews, or comparisons, please contact us immediately through our contact form. Include the specific page, device, and details about the error so we can verify and correct it quickly."
                  }
              },
              {
                  "@type": "Question",
                  "name": "Do you accept advertising or partnership inquiries?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "Yes, we are open to advertising and partnership opportunities. Please use our contact form to describe your proposal, and we'll connect you with the appropriate team member to discuss collaboration possibilities."
                  }
              },
              {
                  "@type": "Question",
                  "name": "How long does it take to receive a response?",
                  "acceptedAnswer": {
                      "@type": "Answer",
                      "text": "We aim to respond to all inquiries within 24-48 business hours. During peak periods, responses may take slightly longer. For urgent matters, please clearly mark your inquiry as urgent in the subject line."
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
        <img class="cp-hero-bg-img" src="<?php echo $base; ?>hero-images/contact-hero.png" alt="contact us page background">
      </div>

      <div class="cp-hero-inner">
        <div class="cp-hero-left">
          <div class="cp-hero-label"><span>DevicesArena</span></div>
          <h1 class="cp-hero-title">Contact Us</h1>
          <p class="cp-hero-sub">Get in touch with DevicesArena for any inquiries, suggestions, or feedback about smartphones and mobile technology.</p>
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
            <div class="da-section-label"><span>Contact Us</span></div>
            <h2 class="da-section-title">Get In Touch</h2>
          </div>
        </div>

        <div class="da-about-content">
          <h4 class="da-about-heading mt-0">We do appreciate your feedback</h4>
          <p class="da-about-text">We will be glad to hear from you if:</p>
          <ul class="da-about-list">
            <li>You have found a mistake in our device specifications.</li>
            <li>You have info about a device which we don't have in our database.</li>
            <li>You have found a broken link.</li>
            <li>You have a suggestion for improving DevicesArena or you want to request a feature.</li>
          </ul>

          <h4 class="da-about-heading mt-4">Before contacting us, please keep in mind:</h4>
          <ul class="da-about-list">
            <li>We do not sell mobile phones.</li>
            <li>We do not know the price of any mobile phone in your country.</li>
            <li>We don't answer any "unlocking" related questions.</li>
            <li>We don't answer any "Which mobile should I buy?" questions.</li>
          </ul>
        </div>

        <div class="da-about-content mt-4">
          <h4 class="da-about-heading mt-0 mb-4">Send us a message</h4>
          
          <div id="contact_message_container"></div>
          
          <form id="contact_form" class="da-form" novalidate>
            <input type="hidden" name="query_type" value="contact">
            
            <div class="da-form-row">
              <div class="da-form-group">
                <input type="text" class="da-input" id="contact_name" name="contact_name" placeholder="Your Name *" maxlength="100" required>
                <div class="da-error-msg" id="name_error"></div>
              </div>
              <div class="da-form-group">
                <input type="email" class="da-input" id="contact_email" name="contact_email" placeholder="Your Email *" maxlength="255" required>
                <div class="da-error-msg" id="email_error"></div>
              </div>
            </div>
            
            <div class="da-form-group">
              <textarea class="da-input" id="contact_query" name="contact_query" rows="5" placeholder="Please describe your inquiry in detail (no links allowed)..." maxlength="5000" required></textarea>
              <div class="da-error-msg" id="query_error"></div>
              <div class="text-end mt-1"><small class="text-muted"><span id="char_count">0</span>/5000 characters</small></div>
            </div>
            
            <div class="da-form-footer">
              <div class="d-flex align-items-center flex-wrap gap-3 w-100 justify-content-between">
                <button type="submit" id="contact_submit_btn" class="da-cta-btn">Send Message</button>
                <small class="text-muted">We typically respond within 24-48 hours.</small>
              </div>
            </div>
          </form>
        </div>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">

        <!-- Latest Devices -->
        <?php include('includes/sidebar/latest-devices.php'); ?>

        <!-- Popular Comparisons -->
        <?php include('includes/sidebar/popular-comparisons.php'); ?>

        <!-- Top 10 Daily Interest -->
        <?php include('includes/sidebar/top-daily-interests.php'); ?>

        <!-- Top 10 by Fans -->
        <?php include('includes/sidebar/top-by-fans.php'); ?>

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
    // ── Contact Form Handler ──
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contact_form');
        const contactMsg = document.getElementById('contact_message_container');
        const charCount = document.getElementById('char_count');
        const queryField = document.getElementById('contact_query');

        if (queryField && charCount) {
            queryField.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }

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
            document.querySelectorAll('#contact_form .da-input').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#contact_form .da-error-msg').forEach(el => el.classList.remove('show-error'));
        }

        function setError(fieldId, errorId, msg) {
            document.getElementById(fieldId).classList.add('is-invalid');
            document.getElementById(errorId).textContent = msg;
            document.getElementById(errorId).classList.add('show-error');
        }

        function showContactMessage(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            contactMsg.innerHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
                            '&query_type=contact'
                    })
                    .then(r => r.json())
                    .then(data => {
                        showContactMessage(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            contactForm.reset();
                            if(charCount) charCount.textContent = '0';
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
  <script src="<?php echo $base; ?>redesign/sliders.js"></script>
</body>

</html>