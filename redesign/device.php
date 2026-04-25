<?php
// Set working directory to the parent so that device.php can require_once its relative files correctly
chdir(__DIR__ . '/..');

// Capture the output of the old device.php so it doesn't render the old HTML,
// but we still get all its variables ($device, $deviceSpecs, $comments, etc.)
ob_start();
require_once 'device.php';
$oldHtml = ob_get_clean();

// Extract SEO tags from the old HTML
$page_title = '';
if (preg_match('/<title>(.*?)<\/title>/is', $oldHtml, $matches)) {
    $page_title = $matches[1];
}
$meta_desc = '';
if (preg_match('/<meta name="description" content="(.*?)">/is', $oldHtml, $matches)) {
    $meta_desc = $matches[1];
}
$og_image = '';
if (preg_match('/<meta property="og:image" content="(.*?)">/is', $oldHtml, $matches)) {
    $og_image = $matches[1];
}

// Reset working directory back to redesign for frontend includes (if any)
chdir(__DIR__);

// Fetch additional sidebar data that redesign/index.php requires
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) { $weekly_posts = []; }

$mb_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 12");
$mb_stmt->execute();
$mobile_brands = $mb_stmt->fetchAll();

$brands_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 36");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';
if (!isset($_SESSION['notif_seen'])) $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Fallbacks for data already in device.php
$latestDevices = $latestDevices ?? [];
$topComparisons = $topComparisons ?? [];
$topViewedDevices = $topViewedDevices ?? [];
$topReviewedDevices = $topReviewedDevices ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/device/<?php echo htmlspecialchars($device_slug); ?>" />
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $meta_desc; ?>" />
  <meta property="og:title" content="<?php echo $page_title; ?>" />
  <meta property="og:description" content="<?php echo $meta_desc; ?>" />
  <meta property="og:image" content="<?php echo $og_image; ?>" />
  <meta property="og:type" content="website" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?php echo $page_title; ?>">
  <meta name="twitter:description" content="<?php echo $meta_desc; ?>">
  <meta name="twitter:image" content="<?php echo $og_image; ?>">

  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446" crossorigin="anonymous"></script>
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-2LDCSSMXJT');
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <!-- Theme Initialization Script -->
  <script>
    (function() {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>
</head>
<body>

  <!-- ══════════════════════ NAVBAR ══════════════════════ -->
  <nav class="da-navbar" id="da-navbar">
    <div class="da-navbar-top">
      <div class="nav-container-top">
        <button class="da-hamburger d-lg-none" type="button" aria-label="Menu" id="da-hamburger">
          <span></span><span></span><span></span>
        </button>
        <a class="da-logo" href="<?php echo $base; ?>">
          <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena" />
        </a>

        <form class="da-search-large d-none d-lg-flex" action="<?php echo $base; ?>search" method="GET">
          <input type="text" name="q" placeholder="Search in devices arena" autocomplete="off" required>
          <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
        </form>

        <div class="da-top-actions">
          <button class="da-theme-btn-top d-none d-lg-flex" id="da-theme-toggle" title="Toggle Theme"><i class="fa fa-adjust"></i></button>
          
          <div class="da-social-icons-top d-none d-lg-flex">
            <a href="https://youtube.com/@devicesarena" target="_blank" title="YouTube" class="yt"><i class="fab fa-youtube"></i></a>
            <a href="https://www.instagram.com/devicesarenaofficial" target="_blank" title="Instagram" class="ig"><i class="fab fa-instagram"></i></a>
            <a href="https://www.facebook.com/profile.php?id=61585936163841" target="_blank" title="Facebook" class="fb"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.tiktok.com/" target="_blank" title="TikTok" class="tt"><i class="fab fa-tiktok"></i></a>
          </div>

          <div class="da-auth-btns-top">
            <?php if ($isPublicUser): ?>
              <div class="dropdown">
                <button class="da-user-avatar" type="button" data-bs-toggle="dropdown" title="<?php echo htmlspecialchars($publicUserName); ?>">
                  <?php echo $publicUserInitial; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:190px;">
                  <li><span class="dropdown-item-text" style="font-size:12px;color:#888;">Hi, <?php echo htmlspecialchars($publicUserName); ?></span></li>
                  <li><hr class="dropdown-divider"></li>
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
      <li><a href="<?php echo $base; ?>brands">All Brands</a></li>
    </ul>
  </div>

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-page">
    <div class="da-content-area">
      
      <!-- Device Main Detail -->
      <main>
        <div class="da-device-hero">
          <div class="da-device-img" onclick="if(typeof showPicturesModal === 'function') showPicturesModal();">
            <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'], $base)); ?>" alt="<?php echo htmlspecialchars($device['name']); ?>" loading="eager" />
          </div>
          <div class="da-device-info">
            <h1 class="da-device-title"><?php echo htmlspecialchars(($device['brand_name'] ?? '') . ' ' . ($device['name'] ?? 'Device')); ?></h1>
            
            <div class="da-device-highlights">
              <?php if (!empty($deviceHighlights)): foreach ($deviceHighlights as $highlight): ?>
                <span class="da-highlight-badge"><?php echo htmlspecialchars($highlight); ?></span>
              <?php endforeach; else: ?>
                <span class="da-highlight-badge">📅 Release date not available</span>
              <?php endif; ?>
            </div>
            
            <div class="da-device-actions">
              <?php if ($review_post): ?>
                <button class="da-card-cta-btn da-device-action-btn" onclick="window.location.href='<?php echo $base; ?>post/<?php echo urlencode($review_post['slug']); ?>'"><i class="fa fa-pen"></i> Review</button>
              <?php else: ?>
                <button class="da-card-cta-btn da-device-action-btn" disabled><i class="fa fa-pen"></i> Review</button>
              <?php endif; ?>
              <button class="da-card-cta-btn da-device-action-btn" onclick="window.location.href='<?php echo $base; ?>compare/<?php echo htmlspecialchars($device['slug']); ?>'"><i class="fa fa-scale-balanced"></i> Compare</button>
              <button class="da-card-cta-btn da-device-action-btn secondary" onclick="document.getElementById('comments').scrollIntoView({behavior:'smooth'});"><i class="fa fa-comments"></i> Opinions</button>
            </div>
            
            <!-- Specs Summary -->
            <div class="da-device-stats">
              <?php 
              $statKeys = ['display', 'camera', 'performance', 'battery'];
              $icons = [
                  'display' => '<i class="fa fa-mobile-screen da-stat-icon"></i>',
                  'camera' => '<i class="fa fa-camera da-stat-icon"></i>',
                  'performance' => '<i class="fa fa-microchip da-stat-icon"></i>',
                  'battery' => '<i class="fa fa-battery-full da-stat-icon"></i>'
              ];
              foreach ($statKeys as $key):
                if (isset($deviceStats[$key])): $stat = $deviceStats[$key];
              ?>
              <div class="da-stat-box">
                  <?php echo $icons[$key]; ?>
                  <div class="da-stat-title"><?php echo htmlspecialchars($stat['title']); ?></div>
                  <div class="da-stat-subtitle"><?php echo htmlspecialchars($stat['subtitle']); ?></div>
              </div>
              <?php endif; endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Detailed Specifications -->
        <div class="da-section-label"><span>Specifications</span></div>
        <div class="da-widget" style="margin-bottom: 24px;">
          <table class="da-specs-table">
            <tbody>
              <?php if (!empty($deviceSpecs)): foreach ($deviceSpecs as $category => $rows): if (is_array($rows) && !empty($rows)): ?>
                <?php foreach ($rows as $rowIndex => $rowData): ?>
                  <tr class="da-specs-row">
                      <?php if ($rowIndex === 0): ?>
                          <th class="da-specs-category" rowspan="<?php echo count($rows); ?>">
                              <?php echo htmlspecialchars($category); ?>
                          </th>
                      <?php endif; ?>
                      <td class="da-specs-field">
                          <?php echo htmlspecialchars($rowData['field']); ?>
                      </td>
                      <td class="da-specs-value">
                          <?php echo $rowData['description']; ?>
                      </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; endforeach; else: ?>
                <tr><td class="da-empty">No detailed specifications available.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="da-specs-disclaimer">
            <strong>Disclaimer:</strong> We can not guarantee that the information on this page is 100% correct.
          </div>
        </div>

        <!-- User Opinions -->
        <div class="da-section-label" id="comments"><span>Opinions</span></div>
        <div class="da-widget da-opinions-widget">
           <h3 class="da-opinions-title">User Opinions and Reviews (<?php echo $commentCount; ?>)</h3>
           
           <?php if (!empty($comments)): foreach ($comments as $comment): ?>
             <div class="da-comment-thread">
                <div class="da-comment-avatar">
                  <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                </div>
                <div class="da-comment-content">
                   <div class="da-comment-header">
                     <span class="da-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                     <span class="da-comment-time"><i class="fa fa-clock me-1"></i><?php echo timeAgo($comment['created_at']); ?></span>
                   </div>
                   <div class="da-comment-text">
                     <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                   </div>
                </div>
             </div>
           <?php endforeach; else: ?>
             <div class="da-empty">
                <i class="fa fa-comments"></i>
                <p>No comments yet. Be the first to share your opinion!</p>
             </div>
           <?php endif; ?>

           <!-- Comment Form -->
           <div class="da-comment-form-wrap">
              <h4 class="da-comment-form-title">Share Your Opinion</h4>
              <form id="device-comment-form" method="POST">
                <input type="hidden" name="action" value="comment_device">
                <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['id']); ?>">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <input type="text" class="form-control da-comment-input" name="name" placeholder="Your Name" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <input type="email" class="form-control da-comment-input" name="email" placeholder="Your Email (optional)">
                  </div>
                </div>
                <div class="mb-3">
                  <textarea class="form-control da-comment-input" name="comment" rows="4" placeholder="Share your thoughts about this device..." required></textarea>
                </div>
                <div class="mb-3 d-flex align-items-center gap-3">
                  <img src="<?php echo $base; ?>captcha.php" id="captcha-image" style="height: 40px; border-radius: 4px; cursor: pointer;" onclick="this.src='<?php echo $base; ?>captcha.php?r='+Math.random()">
                  <input type="text" class="form-control da-comment-input" name="captcha" placeholder="Enter CAPTCHA" required style="width: 150px;">
                </div>
                <button type="submit" class="da-card-cta-btn"><i class="fa fa-paper-plane"></i> Post Opinion</button>
              </form>
           </div>
        </div>
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
                <?php foreach (array_slice($latestDevices, 0, 8) as $dev): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($dev['slug']); ?>" class="da-device-row">
                    <div class="da-device-img-wrapper">
                      <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($dev['image'] ?? '', $base)); ?>" alt="<?php echo htmlspecialchars($dev['name']); ?>" loading="lazy" />
                    </div>
                    <div class="da-device-info">
                      <div class="da-device-name"><?php echo htmlspecialchars($dev['name']); ?></div>
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
                <?php foreach (array_slice($topComparisons, 0, 5) as $cmp):
                  $slug1 = $cmp['device1_slug'] ?? $cmp['device1_id'] ?? '';
                  $slug2 = $cmp['device2_slug'] ?? $cmp['device2_id'] ?? '';
                  $cUrl = $base . 'compare/' . urlencode($slug1) . '-vs-' . urlencode($slug2);
                ?>
                  <a href="<?php echo $cUrl; ?>" class="da-sidebar-vs-card">
                    <div class="da-sidebar-vs-row">
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device1_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device1_image'], $base)); ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo htmlspecialchars($cmp['device1_name'] ?? 'Device'); ?></div>
                      </div>
                      <div class="da-sidebar-vs-divider">VS</div>
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device2_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device2_image'], $base)); ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo htmlspecialchars($cmp['device2_name'] ?? 'Device'); ?></div>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>

    <!-- ── INFINITE BRAND MARQUEE ── -->
    <section class="da-marquee-section" aria-label="All Brands">
      <div class="da-marquee-container">
        <div class="da-marquee-track">
          <div class="da-marquee-content">
            <?php foreach ($brands as $brand): $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name']))); ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
          <div class="da-marquee-content" aria-hidden="true">
            <?php foreach ($brands as $brand): $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name']))); ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

  </div><!-- /.da-page -->

  <!-- ══════════════════════ FOOTER ══════════════════════ -->
  <footer class="da-footer-new">
    <div class="da-footer-container" style="max-width: 1400px; margin: 0 auto; padding: 40px 24px;">
      <div class="da-footer-top-row">
        <a class="da-logo" href="<?php echo $base; ?>"><img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena" /></a>
        <div class="da-social-icons-top">
          <a href="#" class="fb"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="yt"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      <hr class="da-footer-hr">
      <div style="text-align: center; font-size: 13px; color: #94a3b8;">&copy; <?php echo date('Y'); ?> DevicesArena. All rights reserved.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.baseURL = '<?php echo $base; ?>';
    
    // Theme toggle logic
    const themeToggles = [document.getElementById('da-theme-toggle')];
    function updateThemeIcons() {
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      themeToggles.forEach(btn => {
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (icon) icon.className = isLight ? 'fa fa-moon' : 'fa fa-sun';
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

    // Mobile Menu
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    if (hamburger) {
      hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        mobileMenu.classList.toggle('open');
        document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
      });
    }

    // Expand text dots logic
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('expand-dots')) {
         const full = e.target.getAttribute('data-full');
         if (full) {
            const temp = document.createElement('div');
            temp.innerHTML = full;
            const prev = e.target.previousSibling;
            if (prev && prev.nodeType === Node.TEXT_NODE) prev.textContent = temp.textContent || temp.innerText || '';
            e.target.remove();
         }
      }
    });
  </script>
  <script>var COMMENT_AJAX_BASE = '<?php echo $base; ?>';</script>
  <script src="<?php echo $base; ?>js/comment-ajax.js"></script>
</body>
</html>