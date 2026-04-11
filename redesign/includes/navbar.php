<?php
// Requires: $base, $isPublicUser, $publicUserName, $publicUserInitial,
//           $hasUnreadNotifications, $weekly_posts, $mobile_brands
// Optional: $da_active_nav — set to 'compare', 'home', etc. to highlight active link
$da_active_nav = $da_active_nav ?? '';
?>
<!-- ══════════════════════ NAVBAR ══════════════════════ -->
<nav class="da-navbar" id="da-navbar">
  <!-- Desktop Two-Tier Navbar -->
  <div class="da-navbar-top">
    <div class="nav-container-top">
      <!-- Hamburger -->
      <button class="da-hamburger d-lg-none" type="button" aria-label="Menu" id="da-hamburger">
        <span></span><span></span><span></span>
      </button>
      <!-- Logo -->
      <a class="da-logo" href="<?php echo $base; ?>">
        <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena"/>
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
                  <li style="padding:12px 16px;"><div style="font-weight:700;color:#d50000;font-size:13px;"><i class="fa fa-sparkles me-1"></i>Fresh This Week</div></li>
                  <li><hr class="dropdown-divider"></li>
                  <?php if (!empty($weekly_posts)): foreach ($weekly_posts as $wp): ?>
                    <li><a class="dropdown-item" href="<?php echo $base; ?>post/<?php echo htmlspecialchars($wp['slug']); ?>" style="display:flex;gap:10px;align-items:center;padding:9px 16px;">
                      <?php if (!empty($wp['featured_image'])): ?><img src="<?php echo htmlspecialchars($wp['featured_image']); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;"><?php endif; ?>
                      <div><div style="font-size:12.5px;font-weight:600;"><?php echo htmlspecialchars($wp['title']); ?></div><div style="font-size:11px;color:#888;margin-top:2px;"><i class="fa fa-clock me-1"></i><?php echo date('M d', strtotime($wp['created_at'])); ?></div></div>
                    </a></li>
                  <?php endforeach; else: ?>
                    <li style="padding:20px 16px;text-align:center;color:#666;font-size:13px;">No posts this week</li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li style="text-align:center;padding:8px;"><a href="<?php echo $base; ?>reviews" style="color:#d50000;font-size:12px;font-weight:600;"><i class="fa fa-arrow-right me-1"></i>View All Posts</a></li>
                </ul>
              </div>
              <div class="dropdown">
                <button class="da-user-avatar" type="button" data-bs-toggle="dropdown" title="<?php echo htmlspecialchars($publicUserName); ?>">
                  <?php echo $publicUserInitial; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:190px;">
                  <li><span class="dropdown-item-text" style="font-size:12px;color:#888;"><i class="fa fa-hand-peace me-1"></i>Hi, <?php echo htmlspecialchars($publicUserName); ?></span></li>
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

    <!-- Bottom Tier -->
    <div class="da-navbar-bottom d-none d-lg-block">
      <div class="nav-container-bottom">
        <ul class="da-nav-links-bottom">
          <li><a href="<?php echo $base; ?>home"<?php echo $da_active_nav==='home'?' class="active"':''; ?>>HOME</a></li>
          <li><a href="<?php echo $base; ?>compare"<?php echo $da_active_nav==='compare'?' class="active"':''; ?>>COMPARE</a></li>
          <li><a href="#">VIDEOS</a></li>
          <li><a href="<?php echo $base; ?>reviews"<?php echo $da_active_nav==='reviews'?' class="active"':''; ?>>REVIEWS</a></li>
          <li><a href="<?php echo $base; ?>featured"<?php echo $da_active_nav==='featured'?' class="active"':''; ?>>FEATURED</a></li>
          <li><a href="<?php echo $base; ?>phonefinder"<?php echo $da_active_nav==='phonefinder'?' class="active"':''; ?>>PHONE FINDER</a></li>
          <li><a href="<?php echo $base; ?>contact-us"<?php echo $da_active_nav==='contact'?' class="active"':''; ?>>CONTACT</a></li>
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
      <a href="<?php echo $base; ?>brand/<?php echo urlencode(strtolower(preg_replace('/\s+/','-',trim($mb['name'])))); ?>" class="da-mobile-brand-pill"><?php echo htmlspecialchars($mb['name']); ?></a>
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
          <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div></div>
          <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Password" required></div></div>
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
          <div class="mb-3"><label class="form-label">Full Name</label><div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" placeholder="John Doe" required minlength="2" maxlength="100"></div></div>
          <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" placeholder="you@example.com" required></div></div>
          <div class="mb-3"><label class="form-label">Password</label><div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6"></div></div>
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
          <div class="mb-3"><label class="form-label">Full Name</label><div class="input-group"><span class="input-group-text"><i class="fa fa-user"></i></span><input type="text" class="form-control" name="name" id="profile-name" required minlength="2" maxlength="100"></div></div>
          <div class="mb-3"><label class="form-label">Email</label><div class="input-group"><span class="input-group-text"><i class="fa fa-envelope"></i></span><input type="email" class="form-control" name="email" id="profile-email" required></div></div>
          <hr style="border-color:var(--border);">
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;"><i class="fa fa-info-circle me-1"></i>Leave password fields blank to keep current.</p>
          <div class="mb-3"><label class="form-label">Current Password</label><div class="input-group"><span class="input-group-text"><i class="fa fa-key"></i></span><input type="password" class="form-control" name="current_password" id="profile-current-password" placeholder="Required to change password"></div></div>
          <div class="mb-3"><label class="form-label">New Password</label><div class="input-group"><span class="input-group-text"><i class="fa fa-lock"></i></span><input type="password" class="form-control" name="new_password" id="profile-new-password" placeholder="Min 6 characters" minlength="6"></div></div>
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
