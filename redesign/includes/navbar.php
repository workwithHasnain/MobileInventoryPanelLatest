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
   <?php
      $mb_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 12");
      $mb_stmt->execute();
      $mobile_brands = $mb_stmt->fetchAll();
   ?>
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