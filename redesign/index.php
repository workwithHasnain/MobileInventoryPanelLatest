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

// Mobile brands for menu
$mb_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 12");
$mb_stmt->execute();
$mobile_brands = $mb_stmt->fetchAll();

// Posts
$posts_stmt = $pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') as comment_count FROM posts p WHERE p.status ILIKE 'published' ORDER BY p.created_at DESC LIMIT 20");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();

// Top viewed
try {
  $stmt = $pdo->prepare("SELECT p.*,b.name as brand_name,COUNT(cv.id) as view_count FROM phones p LEFT JOIN brands b ON p.brand_id=b.id LEFT JOIN content_views cv ON CAST(p.id AS VARCHAR)=cv.content_id AND cv.content_type='device' GROUP BY p.id,b.name ORDER BY view_count DESC LIMIT 10");
  $stmt->execute();
  $topViewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topViewedDevices = [];
}

// Top reviewed
try {
  $stmt = $pdo->prepare("SELECT p.*,b.name as brand_name,COUNT(dc.id) as review_count FROM phones p LEFT JOIN brands b ON p.brand_id=b.id LEFT JOIN device_comments dc ON CAST(p.id AS VARCHAR)=dc.device_id AND dc.status='approved' GROUP BY p.id,b.name ORDER BY review_count DESC LIMIT 10");
  $stmt->execute();
  $topReviewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topReviewedDevices = [];
}

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
  <nav class="da-navbar" id="da-navbar">
    <!-- Desktop Two-Tier Navbar -->
    <div class="da-navbar-top">
      <div class="nav-container-top">
        <!-- Hamburger appended here for mobile -->
        <button class="da-hamburger d-lg-none" type="button" aria-label="Menu" id="da-hamburger">
          <span></span><span></span><span></span>
        </button>
        <!-- Logo -->
        <a class="da-logo" href="<?php echo $base; ?>">
          <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena" />
        </a>

        <!-- Large Center Search (Desktop) -->
        <form class="da-search-large d-none d-lg-flex" action="<?php echo $base; ?>search" method="GET">
          <input type="text" name="q" placeholder="Search in devices arena" autocomplete="off" required>
          <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
        </form>

        <!-- Right Actions -->
        <div class="da-top-actions">
          <button class="da-theme-btn-top d-none d-lg-flex" id="da-theme-toggle" title="Toggle Theme" aria-label="Toggle Theme"><i class="fa fa-adjust"></i></button>

          <div class="da-social-icons-top d-none d-lg-flex">
            <a href="https://youtube.com/@devicesarena" target="_blank" title="YouTube" class="yt"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/devicesarenaofficial" target="_blank" title="Instagram" class="ig"><i class="fab fa-instagram"></i></a>
            <a href="https://www.facebook.com/profile.php?id=61585936163841" target="_blank" title="Facebook" class="fb"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.tiktok.com/" target="_blank" title="TikTok" class="tt"><i class="fab fa-tiktok"></i></a>
          </div>

          <div class="da-auth-btns-top">
            <?php if ($isPublicUser): ?>
              <div class="dropdown me-2 d-none d-lg-block">
                <button class="da-notif-bell-top" type="button" data-bs-toggle="dropdown" id="notificationBellDesktop">
                  <i class="fa fa-bell"></i>
                  <?php if ($hasUnreadNotifications): ?><span class="da-notif-dot" id="notifDotDesktop"></span><?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:300px;max-height:360px;overflow-y:auto;">
                  <li style="padding:12px 16px;">
                    <div style="font-weight:700;color:#d50000;font-size:13px;"><i class="fa fa-sparkles me-1"></i>Fresh This Week</div>
                  </li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <?php if (!empty($weekly_posts)): foreach ($weekly_posts as $wp): ?>
                      <li><a class="dropdown-item" href="<?php echo $base; ?>post/<?php echo htmlspecialchars($wp['slug']); ?>" style="display:flex;gap:10px;align-items:center;padding:9px 16px;">
                          <?php if (!empty($wp['featured_image'])): ?><img src="<?php echo htmlspecialchars($wp['featured_image']); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;"><?php endif; ?>
                          <div>
                            <div style="font-size:12.5px;font-weight:600;"><?php echo htmlspecialchars($wp['title']); ?></div>
                            <div style="font-size:11px;color:#888;margin-top:2px;"><i class="fa fa-clock me-1"></i><?php echo date('M d', strtotime($wp['created_at'])); ?></div>
                          </div>
                        </a></li>
                    <?php endforeach;
                  else: ?>
                    <li style="padding:20px 16px;text-align:center;color:#666;font-size:13px;">No posts this week</li>
                  <?php endif; ?>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li style="text-align:center;padding:8px;"><a href="<?php echo $base; ?>reviews" style="color:#d50000;font-size:12px;font-weight:600;"><i class="fa fa-arrow-right me-1"></i>View All Posts</a></li>
                </ul>
              </div>
              <div class="dropdown">
                <button class="da-user-avatar" type="button" data-bs-toggle="dropdown" title="<?php echo htmlspecialchars($publicUserName); ?>">
                  <?php echo $publicUserInitial; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:190px;">
                  <li><span class="dropdown-item-text" style="font-size:12px;color:#888;"><i class="fa fa-hand-peace me-1"></i>Hi, <?php echo htmlspecialchars($publicUserName); ?></span></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><a class="dropdown-item" href="#" onclick="openProfileModal();return false;"><i class="fa fa-user-pen me-2"></i>Profile</a></li>
                  <li><a class="dropdown-item text-danger" href="#" onclick="publicUserLogout();return false;"><i class="fa fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
              </div>
            <?php else: ?>
              <button class="btn-yellow-login d-none d-lg-flex" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
              <button class="btn-yellow-login-mobile d-lg-none" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="fa fa-user"></i></button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Tier -->
    <div class="da-navbar-bottom d-none d-lg-block">
      <div class="nav-container-bottom">
        <ul class="da-nav-links-bottom">
          <li><a href="<?php echo $base; ?>home">HOME</a></li>
          <li><a href="<?php echo $base; ?>compare">COMPARE</a></li>
          <li><a href="#">VIDEOS</a></li>
          <li><a href="<?php echo $base; ?>reviews">REVIEWS</a></li>
          <li><a href="<?php echo $base; ?>featured">FEATURED</a></li>
          <li><a href="<?php echo $base; ?>phonefinder">PHONE FINDER</a></li>
          <li><a href="<?php echo $base; ?>contact-us">CONTACT</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ══════════════════════ MOBILE MENU ══════════════════════ -->
  <div class="da-mobile-menu" id="da-mobile-menu">
    <ul class="da-mobile-nav-links">
      <li><a href="<?php echo $base; ?>">Home</a></li>
      <li><a href="<?php echo $base; ?>compare">Compare</a></li>
      <li><a href="<?php echo $base; ?>reviews">Reviews</a></li>
      <li><a href="<?php echo $base; ?>phonefinder">Phone Finder</a></li>
      <li><a href="<?php echo $base; ?>rumored">Rumored</a></li>
      <li><a href="<?php echo $base; ?>brands">All Brands</a></li>
      <li><a href="<?php echo $base; ?>contact-us">Contact</a></li>
      <?php if ($isPublicUser): ?>
        <li><a href="#" onclick="openProfileModal();closeMobileMenu();return false;"><i class="fa fa-user-pen me-2"></i>Profile</a></li>
        <li><a href="#" onclick="publicUserLogout();return false;" style="color:#f87171;"><i class="fa fa-right-from-bracket me-2"></i>Logout</a></li>
      <?php else: ?>
        <li><a href="#" onclick="closeMobileMenu();setTimeout(()=>new bootstrap.Modal(document.getElementById('loginModal')).show(),300);return false;"><i class="fa fa-right-to-bracket me-2"></i>Login</a></li>
        <li><a href="#" onclick="closeMobileMenu();setTimeout(()=>new bootstrap.Modal(document.getElementById('signupModal')).show(),300);return false;"><i class="fa fa-user-plus me-2"></i>Sign Up</a></li>
      <?php endif; ?>
    </ul>
    <div class="da-mobile-action-btns">
      <button class="da-mobile-action-btn outline" id="da-mobile-theme-toggle" style="grid-column: span 2;"><i class="fa fa-adjust"></i> Toggle Theme</button>
      <a href="<?php echo $base; ?>phonefinder" class="da-mobile-action-btn red"><i class="fa fa-mobile-screen"></i> Phone Finder</a>
      <a href="<?php echo $base; ?>rumored" class="da-mobile-action-btn outline"><i class="fa fa-volume-high"></i> Rumors Mill</a>
    </div>
    <div class="da-mobile-brand-grid">
      <?php foreach (array_slice($mobile_brands, 0, 12) as $mb): ?>
        <a href="<?php echo $base; ?>brand/<?php echo urlencode(strtolower(preg_replace('/\s+/', '-', trim($mb['name'])))); ?>" class="da-mobile-brand-pill"><?php echo htmlspecialchars($mb['name']); ?></a>
      <?php endforeach; ?>
      <a href="<?php echo $base; ?>brands" class="da-mobile-brand-pill" style="background:rgba(213,0,0,0.15);border-color:rgba(213,0,0,0.3);color:#d50000;">All Brands →</a>
    </div>
  </div>

  <!-- ══════════════════════ AUTH MODALS ══════════════════════ -->
  <!-- Login -->
  <div class="modal fade da-modal" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-right-to-bracket me-2"></i>Login</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="login-message" style="display:none;"></div>
          <form id="publicLoginForm" autocomplete="off">
            <div class="mb-3"><label class="form-label">Email</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Password" required></div>
            </div>
            <button type="submit" class="btn w-100 fw-semibold" id="loginSubmitBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-right-to-bracket me-1"></i>Login</button>
          </form>
          <div class="text-center mt-3" style="font-size:13px;color:var(--text-muted);">No account? <a href="#" onclick="switchToSignup();return false;" style="color:var(--accent);font-weight:600;">Sign Up</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sign Up -->
  <div class="modal fade da-modal" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i>Create Account</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="signup-message" style="display:none;"></div>
          <form id="publicSignupForm" autocomplete="off">
            <div class="mb-3"><label class="form-label">Full Name</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" placeholder="John Doe" required minlength="2" maxlength="100"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6"></div>
            </div>
            <button type="submit" class="btn w-100 fw-semibold" id="signupSubmitBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-user-plus me-1"></i>Create Account</button>
          </form>
          <div class="text-center mt-3" style="font-size:13px;color:var(--text-muted);">Have account? <a href="#" onclick="switchToLogin();return false;" style="color:var(--accent);font-weight:600;">Login</a></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile -->
  <div class="modal fade da-modal" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-user-pen me-2"></i>Your Profile</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="profile-message" style="display:none;"></div>
          <form id="profileUpdateForm" autocomplete="off">
            <div class="mb-3"><label class="form-label">Full Name</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" id="profile-name" required minlength="2" maxlength="100"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" id="profile-email" required></div>
            </div>
            <hr style="border-color:var(--border);">
            <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><i class="fa fa-info-circle me-1"></i>Leave password fields blank to keep current.</p>
            <div class="mb-3"><label class="form-label">Current Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-key"></i></span><input type="password" class="form-control" name="current_password" id="profile-current-password" placeholder="Required to change password"></div>
            </div>
            <div class="mb-3"><label class="form-label">New Password</label>
              <div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="new_password" id="profile-new-password" placeholder="Min 6 characters" minlength="6"></div>
            </div>
            <button type="submit" class="btn w-100 fw-semibold" id="profileUpdateBtn" style="background:var(--accent);color:#fff;border-radius:8px;padding:11px;"><i class="fa fa-save me-1"></i>Save Changes</button>
          </form>
          <div class="mt-4 pt-3" style="border-top:1px solid var(--border);">
            <p style="color:#f87171;font-size:12px;font-weight:600;margin-bottom:8px;"><i class="fa fa-triangle-exclamation me-1"></i>Danger Zone</p>
            <div class="d-flex gap-2">
              <input type="password" class="form-control form-control-sm" id="delete-account-password" placeholder="Password to confirm" style="max-width:220px;background:var(--bg-card);border-color:var(--border);color:var(--text-primary);">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePublicAccount()" id="deleteAccountBtn"><i class="fa fa-trash me-1"></i>Delete Account</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

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
              <div class="da-section-label"><span>Latest Story</span></div>
              <h1 class="da-hero-main-title"><?php echo htmlspecialchars($hero['title']); ?></h1>
              <div class="da-hero-meta">
                <span><i class="fa fa-calendar-alt"></i><?php echo date('M j, Y', strtotime($hero['created_at'])); ?></span>
                <span><i class="fa fa-comment"></i><?php echo $hero['comment_count']; ?> comments</span>
              </div>
            </div>
          </a>

          <!-- 4 hot stories -->
          <?php $hotStories = array_slice($posts, 1, 4); ?>
          <div class="da-hero-stories">
            <?php foreach ($hotStories as $story): ?>
              <a href="<?php echo $base; ?>post/<?php echo urlencode($story['slug']); ?>" class="da-story-card">
                <?php if (!empty($story['featured_image'])): ?>
                  <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($story['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($story['title']); ?>" loading="lazy" />
                <?php else: ?>
                  <div class="da-img-fallback"></div>
                <?php endif; ?>
                <div class="da-story-card-overlay"></div>
                <div class="da-story-card-title"><?php echo htmlspecialchars($story['title']); ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="da-empty"><i class="fa fa-newspaper"></i>No stories available yet.</div>
        <?php endif; ?>
      </div>

      <!-- Right: Brand panel (Classic Widget) -->
      <div class="da-hero-right">
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
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-cbw-item" title="<?php echo htmlspecialchars($brand['name']); ?>">
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

        <!-- AD PLACEHOLDER -->
        <div class="da-ad-sidebar-rect">
          Advertisement
        </div>
      </div>
    </section>



    <!-- ── POST FEED + SIDEBAR ── -->
    <div class="da-content-area">
      <!-- Post Feed -->
      <main>
        <div class="da-post-feed-header">
          <div>
            <div class="da-section-label"><span>Latest</span></div>
            <h2 class="da-section-title">Recent Stories</h2>
          </div>
          <a href="<?php echo $base; ?>reviews" class="da-view-all">View All <i class="fa fa-arrow-right"></i></a>
        </div>

        <?php
        $feedPosts = array_slice($posts, 4);
        if (empty($feedPosts)):
        ?>
          <div class="da-empty"><i class="fa fa-newspaper"></i>More stories coming soon!</div>
        <?php else: ?>
          <div class="da-post-grid" id="da-post-grid">
            <?php
            $isFirst = true;
            foreach ($feedPosts as $post):
              $cls = $isFirst ? 'da-post-card featured' : 'da-post-card';
              $isFirst = false;
            ?>
              <a href="<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>" class="<?php echo $cls; ?>">
                <div class="da-post-card-img">
                  <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy" />
                  <?php else: ?>
                    <div class="da-img-fallback-icon"><i class="fa fa-newspaper" style="font-size:32px;"></i></div>
                  <?php endif; ?>
                  <span class="da-post-card-tag">Article</span>
                </div>
                <div class="da-post-card-body">
                  <div class="da-post-card-title"><?php echo htmlspecialchars($post['title']); ?></div>
                  <div class="da-post-card-meta">
                    <span><i class="fa fa-calendar-alt"></i><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                    <span><i class="fa fa-comment"></i><?php echo $post['comment_count']; ?></span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">

        <!-- Latest Devices -->
        <div class="da-section-label"><span>Devices</span></div>
        <div class="da-widget">
          <div class="da-widget-header">
            <h3>Latest Devices</h3>
            <div class="da-widget-icon"><i class="fa fa-mobile-screen-button"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($latestDevices)): ?>
              <div class="da-empty"><i class="fa fa-mobile-alt"></i>No devices yet.</div>
            <?php else: ?>
              <div class="da-device-list">
                <?php foreach (array_slice($latestDevices, 0, 8) as $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>" class="da-device-row">
                    <div class="da-device-img-wrapper">
                      <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'] ?? '', $base)); ?>" alt="<?php echo htmlspecialchars($device['name']); ?>" loading="lazy" />
                    </div>
                    <div class="da-device-info">
                      <div class="da-device-name"><?php echo htmlspecialchars($device['name']); ?></div>
                      <div class="da-device-specs">
                        <?php if (!empty($device['display_size'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-mobile-screen"></i> <?php echo $device['display_size']; ?>"</div>
                        <?php endif; ?>
                        <?php if (!empty($device['ram'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-memory"></i> <?php echo $device['ram']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($device['battery_capacity'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-battery-full"></i> <?php echo $device['battery_capacity']; ?> mAh</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Popular Comparisons -->
        <div class="da-widget">
          <div class="da-widget-header">
            <h3>Popular Comparisons</h3>
            <div class="da-widget-icon"><i class="fa fa-scale-balanced"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topComparisons)): ?>
              <div class="da-empty">No comparisons yet.</div>
            <?php else: ?>
              <div class="da-comparison-list">
                <?php foreach (array_slice($topComparisons, 0, 5) as $i => $cmp):
                  $slug1 = $cmp['device1_slug'] ?? $cmp['device1_id'] ?? '';
                  $slug2 = $cmp['device2_slug'] ?? $cmp['device2_id'] ?? '';
                  $cUrl = $base . 'compare/' . urlencode($slug1) . '-vs-' . urlencode($slug2);
                  $n1 = htmlspecialchars($cmp['device1_name'] ?? 'Device');
                  $n2 = htmlspecialchars($cmp['device2_name'] ?? 'Device');
                ?>
                  <a href="<?php echo $cUrl; ?>" class="da-sidebar-vs-card">
                    <div style="text-align:center;margin-top:3px;">
                      <span class="count-badge da-badge-count">
                        <i class="fa fa-scale-balanced da-icon-blue"></i> compared <?php echo number_format($cmp['comparison_count']); ?> times
                      </span>
                    </div>
                    <div class="da-sidebar-vs-row">
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device1_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device1_image'], $base)); ?>" alt="<?php echo $n1; ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo $n1; ?></div>
                      </div>
                      <div class="da-sidebar-vs-divider">VS</div>
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device2_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device2_image'], $base)); ?>" alt="<?php echo $n2; ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo $n2; ?></div>
                      </div>
                    </div>
                    <div class="da-card-btn-wrap">
                      <button class="da-card-cta-btn" onclick="window.location.href='<?php echo $cUrl; ?>'; return false;">Compare Now →</button>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Top 10 Daily Interest -->
        <div class="da-widget">
          <div class="da-widget-header">
            <h3>Top 10 Daily Interest</h3>
            <div class="da-widget-icon da-widget-icon-red"><i class="fa fa-fire"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topViewedDevices)): ?>
              <div class="da-empty">Not enough data yet.</div>
            <?php else: ?>
              <div class="da-leaderboard">
                <?php foreach ($topViewedDevices as $i => $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug'] ?? $device['id']); ?>" class="da-leaderboard-row<?php echo $i < 3 ? ' top3' : ''; ?>">
                    <div class="rank <?php echo $i < 5 ? 'rank-up' : 'rank-down'; ?>">
                      <i class="fa fa-arrow-<?php echo $i < 5 ? 'up' : 'down'; ?>"></i>
                    </div>
                    <div class="device-name"><?php echo htmlspecialchars($device['brand_name'] . ' ' . $device['name']); ?></div>
                    <div class="count-badge"><?php echo $device['view_count']; ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Top 10 by Fans -->
        <div class="da-widget">
          <div class="da-widget-header">
            <h3>Top 10 by Fans</h3>
            <div class="da-widget-icon da-widget-icon-blue"><i class="fa fa-star"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topReviewedDevices)): ?>
              <div class="da-empty">Not enough data yet.</div>
            <?php else: ?>
              <div class="da-leaderboard">
                <?php foreach ($topReviewedDevices as $i => $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug'] ?? $device['id']); ?>" class="da-leaderboard-row<?php echo $i < 3 ? ' top3' : ''; ?>">
                    <div class="rank <?php echo $i < 5 ? 'rank-up' : 'rank-down'; ?>">
                      <i class="fa fa-arrow-<?php echo $i < 5 ? 'up' : 'down'; ?>"></i>
                    </div>
                    <div class="device-name"><?php echo htmlspecialchars($device['brand_name'] . ' ' . $device['name']); ?></div>
                    <div class="count-badge"><?php echo $device['review_count']; ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </aside>
    </div>

    <!-- ── IN STORES NOW ── -->
    <section class="da-instore-section" aria-label="In Stores Now">
      <div class="da-instore-inner">
        <div class="da-instore-header">
          <div>
            <div class="da-section-label"><span>Devices</span></div>
            <h2 class="da-section-title">In Stores Now</h2>
          </div>
          <a href="<?php echo $base; ?>brands" class="da-view-all">Browse All <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="da-slider-wrap">
          <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
          <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
          <div class="da-instore-scroll da-auto-slider" id="da-instore-scroll">
            <?php if (empty($latestDevices)): ?>
              <div class="da-empty"><i class="fa fa-mobile-alt"></i>No devices.</div>
            <?php else: ?>
              <?php foreach ($latestDevices as $device): ?>
                <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>" class="da-device-card">
                  <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'], $base)); ?>" alt="<?php echo htmlspecialchars($device['name']); ?>" loading="lazy" />
                  <div class="da-device-card-name"><?php echo htmlspecialchars($device['name']); ?></div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- ── TRENDING COMPARISONS ── -->
    <?php if (!empty($topComparisons)): ?>
      <section class="da-trending-section" aria-label="Trending Comparisons">
        <div class="da-post-feed-header da-trending-header">
          <div>
            <div class="da-section-label"><span>Compare</span></div>
            <h2 class="da-section-title">Trending Comparisons</h2>
          </div>
          <a href="<?php echo $base; ?>compare" class="da-view-all">Compare Tool <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="da-slider-wrap">
          <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
          <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
          <div class="da-trending-scroll da-auto-slider">
            <?php foreach ($topComparisons as $cmp):
              $s1 = $cmp['device1_slug'] ?? $cmp['device1_id'] ?? '';
              $s2 = $cmp['device2_slug'] ?? $cmp['device2_id'] ?? '';
              $cUrl = $base . 'compare/' . urlencode($s1) . '-vs-' . urlencode($s2);
              $n1 = htmlspecialchars($cmp['device1_name'] ?? 'Device 1');
              $n2 = htmlspecialchars($cmp['device2_name'] ?? 'Device 2');
            ?>
              <a href="<?php echo $cUrl; ?>" class="da-vs-card">
                <div class="da-vs-row">
                  <div class="da-vs-col">
                    <?php if (!empty($cmp['device1_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device1_image'], $base)); ?>" alt="<?php echo $n1; ?>" class="da-vs-img" loading="lazy" /><?php endif; ?>
                    <div class="da-vs-device-name"><?php echo $n1; ?></div>
                  </div>
                  <div class="da-vs-divider">VS</div>
                  <div class="da-vs-col">
                    <?php if (!empty($cmp['device2_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device2_image'], $base)); ?>" alt="<?php echo $n2; ?>" class="da-vs-img" loading="lazy" /><?php endif; ?>
                    <div class="da-vs-device-name"><?php echo $n2; ?></div>
                  </div>
                </div>
                <div class="da-vs-hint">Click to compare →</div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- ── FEATURED POSTS TICKER ── -->
    <section class="da-ticker-section" aria-label="All Posts">
      <div class="da-ticker-header">
        <div>
          <div class="da-section-label"><span>Stories</span></div>
          <h2 class="da-section-title">All Featured Posts</h2>
        </div>
        <a href="<?php echo $base; ?>featured" class="da-view-all">See All <i class="fa fa-arrow-right"></i></a>
      </div>
      <div class="da-slider-wrap">
        <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
        <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
        <div class="da-ticker-scroll da-auto-slider" id="featured-scroll-container">
          <?php foreach ($posts as $post): ?>
            <a href="<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>" class="da-ticker-item">
              <div class="da-ticker-item-img">
                <?php if (!empty($post['featured_image'])): ?>
                  <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy" />
                <?php else: ?>
                  <div class="da-img-fallback-icon"><i class="fa fa-newspaper" style="font-size:20px;"></i></div>
                <?php endif; ?>
              </div>
              <div class="da-ticker-item-body">
                <div class="da-ticker-item-title"><?php echo htmlspecialchars($post['title']); ?></div>
              </div>
            </a>
          <?php endforeach; ?>
          <div id="featured-load-more" style="display:<?php echo count($posts) >= 20 ? 'flex' : 'none'; ?>;align-items:center;justify-content:center;min-width:80px;">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── INFINITE BRAND MARQUEE ── -->
    <section class="da-marquee-section" aria-label="All Brands">
      <div class="da-marquee-container">
        <div class="da-marquee-track">
          <!-- Original set -->
          <div class="da-marquee-content">
            <?php foreach ($brands as $brand):
              $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
            ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
          <!-- Duplicated set for seamless loop -->
          <div class="da-marquee-content" aria-hidden="true">
            <?php foreach ($brands as $brand):
              $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
            ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

  </div><!-- /.da-page -->

  <!-- ══════════════════════ NEW FOOTER ══════════════════════ -->
  <footer class="da-footer-new" aria-label="Site Footer">
    <div class="da-footer-container" style="max-width: 1400px; margin: 0 auto; padding: 40px 24px;">

      <!-- Top Row: Logo & Social -->
      <div class="da-footer-top-row">
        <a class="da-logo" href="<?php echo $base; ?>">
          <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena" />
        </a>
        <div class="da-social-icons-top">
          <a href="https://www.facebook.com/profile.php?id=61585936163841" target="_blank" title="Facebook" class="fb"><i class="fab fa-facebook-f"></i></a>
          <a href="https://youtube.com/@devicesarena" target="_blank" title="YouTube" class="yt"><i class="fab fa-youtube"></i></a>
          <a href="https://twitter.com/" target="_blank" title="X" class="tt"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://www.instagram.com/devicesarenaofficial" target="_blank" title="Instagram" class="ig"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <!-- Middle Row: Company/Licensing & Content/Help -->
      <div class="da-footer-mid-row">
        <!-- Left Column: Company & Licensing -->
        <div class="da-footer-col-group">
          <div class="da-footer-section">
            <h4>COMPANY</h4>
            <ul class="da-footer-links">
              <li><a href="<?php echo $base; ?>about-us">About Us</a></li>
              <li><a href="#">Team</a></li>
              <li><a href="<?php echo $base; ?>contact-us">Contact Us</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Ethics statement</a></li>
              <li><a href="#">How we rate</a></li>
              <li><a href="#">AI at DevicesArena</a></li>
            </ul>
          </div>
          <div class="da-footer-section">
            <h4>LICENSING</h4>
            <ul class="da-footer-links">
              <li><a href="#">Reprint & Permissions</a></li>
              <li><a href="#">Database Licensing</a></li>
              <li><a href="<?php echo $base; ?>advertise-with-us">Advertise with us</a></li>
            </ul>
          </div>
        </div>

        <!-- Right Column: Content & Help -->
        <div class="da-footer-col-group">
          <div class="da-footer-section">
            <h4>CONTENT</h4>
            <ul class="da-footer-links inline-list">
              <li><a href="<?php echo $base; ?>home">Home</a></li>
              <li><a href="<?php echo $base; ?>news">News</a></li>
              <li><a href="#">Manufacturers</a></li>
              <li><a href="#">Carriers</a></li>
              <li><a href="<?php echo $base; ?>reviews">Reviews</a></li>
              <li><a href="<?php echo $base; ?>sitemap">Sitemap</a></li>
              <li><a href="#">News Archive</a></li>
              <li><a href="#">Reviews Archive</a></li>
            </ul>
          </div>
          <div class="da-footer-section">
            <h4>HELP</h4>
            <ul class="da-footer-links inline-list">
              <li><a href="#">Terms of Use</a></li>
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Web Notifications</a></li>
              <li><a href="#">Cookies</a></li>
            </ul>
          </div>
        </div>
      </div>

      <hr class="da-footer-hr">

      <!-- Bottom Row: Guides -->
      <div class="da-footer-guides">
        <h4>GUIDES</h4>
        <div class="da-guides-grid">
          <!-- Col 1 -->
          <ul>
            <li><a href="#">Best Phones</a></li>
            <li><a href="#">Best Samsung Tablets</a></li>
            <li><a href="#">Best Pixel Phones</a></li>
            <li><a href="#">Best Foldable Phones</a></li>
            <li><a href="#">Best Camera Phones</a></li>
            <li><a href="#">Best Nokia Phones</a></li>
            <li><a href="#">Best AirPods</a></li>
          </ul>
          <!-- Col 2 -->
          <ul>
            <li><a href="#">Best Tablets</a></li>
            <li><a href="#">Best Apple Watch</a></li>
            <li><a href="#">Best Motorola Phones</a></li>
            <li><a href="#">Best Small Phones</a></li>
            <li><a href="#">Best Gaming Phones</a></li>
            <li><a href="#">Best Smartwatches</a></li>
          </ul>
          <!-- Col 3 -->
          <ul>
            <li><a href="#">Best iPads</a></li>
            <li><a href="#">Best Android Phones</a></li>
            <li><a href="#">Best Sony Phones</a></li>
            <li><a href="#">Best Flip Phones</a></li>
            <li><a href="#">Best Budget Phones</a></li>
            <li><a href="#">Best Android Smartwatches</a></li>
          </ul>
          <!-- Col 4 -->
          <ul>
            <li><a href="#">Best Budget Tablets</a></li>
            <li><a href="#">Best iPhone</a></li>
            <li><a href="#">Best OnePlus Phones</a></li>
            <li><a href="#">Best Mid-Range Phones</a></li>
            <li><a href="#">Best Asus Phones</a></li>
            <li><a href="#">Best Budget Smartwatches</a></li>
          </ul>
          <!-- Col 5 -->
          <ul>
            <li><a href="#">Best Android Tablets</a></li>
            <li><a href="#">Best Samsung Phones</a></li>
            <li><a href="#">Best Xiaomi Phones</a></li>
            <li><a href="#">Phones with best battery</a></li>
            <li><a href="#">Fastest Charging Phones</a></li>
            <li><a href="#">Best Galaxy Watch</a></li>
          </ul>
        </div>
      </div>

    </div>
  </footer>

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