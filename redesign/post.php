<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath)) return '';
  if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
  if (strpos($imagePath, '/') === 0) return $imagePath;
  return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

// Auth
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';

if (!isset($_SESSION['notif_seen'])) $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Weekly posts for notifications
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) {
  $weekly_posts = [];
}


// Posts
$posts_stmt = $pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') as comment_count FROM posts p WHERE p.status ILIKE 'published' ORDER BY p.created_at DESC LIMIT 20");
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
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/" />
  <title>DevicesArena — Smartphones, Reviews & Comparisons</title>
  <meta name="description" content="Find your next phone on DevicesArena. Reviews, comparisons, specs, and the latest news from the tech world." />
  <meta property="og:title" content="DevicesArena — Smartphones, Reviews & Comparisons" />
  <meta property="og:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons." />
  <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png" />
  <meta property="og:type" content="website" />
  <meta name="twitter:card" content="summary" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446"crossorigin="anonymous"></script>
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

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <!-- Theme Initialization Script (Prevents FOUC) -->
  <script>
    (function() {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265" crossorigin="anonymous"></script>
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
    <section class="da-hero" aria-label="Featured News">
      <!-- Left: hero + stories -->
      <div class="da-hero-left">
        <div class="da-section-label"><span>Newsroom</span></div>

        <?php if (!empty($posts)): $hero = $posts[0]; ?>
          <a href="<?php echo $base; ?>post/<?php echo urlencode($hero['slug']); ?>" class="da-hero-main">
            <?php if (!empty($hero['featured_image'])): ?>
              <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($hero['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($hero['title']); ?>" loading="eager" />
            <?php else: ?>
              <div class="da-img-fallback"></div>
            <?php endif; ?>
            <div class="da-hero-main-overlay"></div>
            <div class="da-hero-main-content">
              <div class="da-section-label"><span>TAGS</span></div>
              <h1 class="da-hero-main-title"><?php echo htmlspecialchars($hero['title']); ?></h1>
              <div class="da-hero-meta">
                <span><i class="fa fa-calendar-alt"></i><?php echo date('M j, Y', strtotime($hero['created_at'])); ?></span>
                <span><i class="fa fa-comment"></i><?php echo $hero['comment_count']; ?> comments</span>
              </div>
            </div>
          </a>
        <?php endif; ?>
      </div>

      <!-- Right: Brand panel (Classic Widget) -->
      <div class="da-hero-right">
        <?php include('includes/sidebar/brands-area.php'); ?>

        <!-- AD PLACEHOLDER -->
        <?php include('includes/sidebar/ad-placeholder.php'); ?>
      </div>
    </section>



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
        <!-- Comments Section -->
        <div class="da-widget mt-4" id="comments">
          <div class="da-widget-header">
            <h3>User Opinions and Reviews</h3>
            <div class="da-widget-icon"><i class="fa fa-comments"></i></div>
          </div>
          <div class="da-widget-body">
            
            <div class="da-comments-list">
              <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                  <div class="da-comment-thread" id="comment-<?php echo $comment['id']; ?>">
                    <div class="da-comment-avatar">
                      <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                    </div>
                    <div class="da-comment-content">
                      <div class="da-comment-header">
                        <span class="da-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                        <span class="da-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($comment['created_at']); ?></span>
                      </div>
                      <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                      <button class="da-reply-btn reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                    </div>
                  </div>
                  <?php if (!empty($comment['replies'])): ?>
                    <?php foreach ($comment['replies'] as $reply): ?>
                      <div class="da-comment-thread da-comment-reply" id="comment-<?php echo $reply['id']; ?>">
                        <div class="da-comment-avatar">
                          <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                        </div>
                        <div class="da-comment-content">
                          <div class="da-comment-header">
                            <span class="da-comment-name"><?php echo htmlspecialchars($reply['name']); ?></span>
                            <small class="da-replied-tag"><i class="fa fa-reply fa-xs"></i> replied</small>
                            <span class="da-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($reply['created_at']); ?></span>
                          </div>
                          <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></div>
                          <button class="da-reply-btn reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="da-empty"><i class="fa fa-comments"></i>No comments yet. Be the first to share your opinion!</div>
              <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <div class="da-comment-form-wrap mt-4">
              <h4 class="mb-3">Share Your Opinion</h4>
              <div id="reply-indicator" class="da-reply-indicator d-none">
                <div><i class="fa fa-reply me-2"></i>Replying to <strong id="reply-to-name"></strong></div>
                <button type="button" id="cancel-reply" class="da-btn-close"><i class="fa fa-times"></i></button>
              </div>
              <?php
              $loggedInName = '';
              $loggedInEmail = '';
              $isUserLoggedIn = false;
              if (!empty($_SESSION['public_user_name'])) {
                $loggedInName = $_SESSION['public_user_name'];
                $loggedInEmail = $_SESSION['public_user_email'] ?? '';
                $isUserLoggedIn = true;
              } elseif (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                $loggedInName = $_SESSION['username'] ?? '';
                $loggedInEmail = '';
                $isUserLoggedIn = true;
              }
              ?>
              <form id="device-comment-form" method="POST" class="da-form">
                <input type="hidden" name="action" value="comment_device">
                <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['id']); ?>">
                <input type="hidden" name="parent_id" id="parent_id" value="">
                
                <div class="da-form-row">
                  <div class="da-form-group">
                    <input type="text" class="da-input" name="name" placeholder="Your Name" required <?php if ($isUserLoggedIn && $loggedInName): ?>value="<?php echo htmlspecialchars($loggedInName); ?>" disabled<?php endif; ?>>
                    <?php if ($isUserLoggedIn && $loggedInName): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($loggedInName); ?>"><?php endif; ?>
                  </div>
                  <div class="da-form-group">
                    <input type="email" class="da-input" name="email" placeholder="Your Email (optional)" <?php if ($isUserLoggedIn && $loggedInEmail): ?>value="<?php echo htmlspecialchars($loggedInEmail); ?>" disabled<?php endif; ?>>
                    <?php if ($isUserLoggedIn && $loggedInEmail): ?><input type="hidden" name="email" value="<?php echo htmlspecialchars($loggedInEmail); ?>"><?php endif; ?>
                  </div>
                </div>
                
                <div class="da-form-group">
                  <textarea class="da-input" name="comment" rows="4" placeholder="Share your thoughts about this device..." required></textarea>
                </div>
                
                <div class="da-form-group da-captcha-group">
                  <label>Type the words shown below</label>
                  <div class="da-captcha-box">
                    <img src="<?php echo $base; ?>captcha.php" id="captcha-image" alt="CAPTCHA" onclick="refreshCaptcha()">
                    <button type="button" class="da-cta-btn secondary" onclick="refreshCaptcha()"><i class="fa fa-rotate-right"></i></button>
                    <input type="text" class="da-input" name="captcha" id="captcha-input" placeholder="Enter the words" required autocomplete="off">
                  </div>
                </div>
                
                <div class="da-form-footer">
                  <div class="d-flex align-items-center flex-wrap gap-3">
                    <button type="submit" class="da-cta-btn">Post Your Opinion</button>
                    <small>Comments are moderated and will appear after approval.</small>
                  </div>
                  <div class="da-comments-count-text">
                    Total reader comments: <b class="text-white"><?php echo $commentCount; ?></b>
                  </div>
                </div>
              </form>
            </div>
          </div>
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
    <?php include('includes/bottom-area/featured-posts.php'); ?>

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
      searchInput.addEventListener('input', function() {
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
          <div><div class="sr-text">${p.title}</div><div class="sr-meta"><i class="fa fa-newspaper me-1"></i>${p.created_at ? p.created_at.substring(0,10) : 'Article'}</div></div>
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
    document.getElementById('da-newsletter-btn').addEventListener('click', function() {
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
      }).catch(() => {});
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
    if (loginForm) loginForm.addEventListener('submit', function(e) {
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
    if (signupForm) signupForm.addEventListener('submit', function(e) {
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
    if (profileForm) profileForm.addEventListener('submit', function(e) {
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
    (function() {
      let page = 1,
        loading = false,
        hasMore = <?php echo count($posts) >= 20 ? 'true' : 'false'; ?>;
      const container = document.getElementById('featured-scroll-container');
      const loader = document.getElementById('featured-load-more');
      if (!container) return;
      container.addEventListener('scroll', function() {
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
        fetch(baseURL + 'load_posts.php?page=' + page + '&type=all&format=block')
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