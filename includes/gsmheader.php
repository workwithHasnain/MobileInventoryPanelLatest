<?php
// Ensure session is started for public user auth
// Only start if headers haven't been sent yet (avoids errors when output already began)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
$isPublicUser = (session_status() === PHP_SESSION_ACTIVE) && !empty($_SESSION['public_user_id']);
$publicUserName = (session_status() === PHP_SESSION_ACTIVE) ? ($_SESSION['public_user_name'] ?? '') : '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';

$mobile_brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY COUNT(p.id) DESC, b.name ASC
    LIMIT 11
");
$mobile_brands_stmt->execute();
$mobile_brands = $mobile_brands_stmt->fetchAll();
?>
<!-- Desktop Navbar of Gsmarecn -->
<!-- Top Navbar -->
<nav class="navbar navbar-dark  d-lg-inline d-none" id="navbar">
    <div class="container const d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-flex align-items-center justify-content-center" type="button" onclick="toggleMenu()">
                <img style="height: 40px;"
                    src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
                    alt="">
            </button>

            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base; ?>home">
                <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena Logo" style="height: min-content; width: min-content; max-height: 50px; max-width: 200px;" />
            </a>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="controvecy d-flex align-items-center">
                <div class="icon-container d-flex align-items-center" style="margin-right: 20px;">
                    <button type="button" class="btn border-right d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="left" title="YouTube" aria-label="YouTube" onclick="window.open('https://youtube.com/@devicesarena', '_blank')">
                        <i class="fab fa-youtube" style="font-size:24px;color:#FF0000"></i>
                    </button>
                    <button type="button" class="btn d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="left" title="Instagram" aria-label="Instagram" onclick="window.open('https://www.instagram.com/devicesarenaofficial', '_blank')">
                        <i class="fab fa-instagram" style="font-size:20px;color:#E4405F"></i>
                    </button>
                    <button type="button" class="btn d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="left" title="Facebook" aria-label="Facebook" onclick="window.open('https://www.facebook.com/profile.php?id=61585936163841', '_blank')">
                        <i class="fab fa-facebook-f" style="font-size:20px;color:#1877F2"></i>
                    </button>
                    <button type="button" class="btn d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="left" title="Twitter" aria-label="Twitter" onclick="window.open('https://twitter.com/', '_blank')">
                        <i class="fab fa-twitter" style="font-size:20px;color:#1DA1F2"></i>
                    </button>
                    <button type="button" class="btn d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-placement="left" title="TikTok" aria-label="TikTok" onclick="window.open('https://www.tiktok.com/', '_blank')">
                        <i class="fab fa-tiktok" style="font-size:20px;color:#ffffff"></i>
                    </button>
                </div>
                <form action="" class="central d-flex align-items-center justify-content-center">
                    <input type="text" class="no-focus-border" placeholder="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="24" width="24" class="ms-2 d-flex align-items-center justify-content-center">
                        <path fill="#ffffff"
                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                    </svg>
                </form>
                <!-- User Auth Buttons (Desktop) -->
                <div class="d-flex align-items-center ms-3" id="desktop-user-area">
                    <?php if ($isPublicUser): ?>
                        <div class="dropdown">
                            <button class="btn d-flex align-items-center justify-content-center p-0" type="button" id="userDropdownDesktop" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px; border-radius: 50%; background-color: #d50000; color: #fff; font-weight: 700; font-size: 16px; border: 2px solid rgba(255,255,255,0.3); cursor: pointer;" title="<?php echo htmlspecialchars($publicUserName); ?>">
                                <?php echo $publicUserInitial; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownDesktop" style="min-width: 200px;">
                                <li><span class="dropdown-item-text fw-semibold text-muted" style="font-size: 13px;"><i class="fa fa-hand-peace me-1"></i>Welcome back, <?php echo htmlspecialchars($publicUserName); ?></span></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#" onclick="openProfileModal(); return false;"><i class="fa fa-user-pen me-2"></i>View Profile</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="publicUserLogout(); return false;"><i class="fa fa-right-from-bracket me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#loginModal" style="color: #fff; font-size: 13px; font-weight: 500; border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 4px 14px; margin-left: 8px;">
                            <i class="fa fa-right-to-bracket me-1"></i>Login
                        </button>
                        <button type="button" class="btn btn-sm d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#signupModal" style="background-color: #d50000; color: #fff; font-size: 13px; font-weight: 500; border: none; border-radius: 20px; padding: 4px 14px; margin-left: 6px;">
                            <i class="fa fa-user-plus me-1"></i>Sign Up
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</nav>
<!-- Mobile Navbar of Gsmarecn -->
<nav id="navbar" class="mobile-navbar d-lg-none d-flex align-items-center">
    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu" data-bs-auto-close="outside"
        aria-controls="mobileMenu" aria-expanded="false" aria-label="Toggle navigation">
        <img style="height: 40px;"
            src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
            alt="">
    </button>
    <a class="navbar-brand d-flex align-items-center" href="<?php echo $base; ?>home">
        <img src="<?php echo $base; ?>imges/logo-wide.svg" alt="DevicesArena Logo" style="height: min-content; width: min-content; max-height: 30px; max-width: 150px;" />
    </a>
    <!-- User Auth Button (Mobile) -->
    <div class="ms-auto me-2" id="mobile-user-area">
        <?php if ($isPublicUser): ?>
            <div class="dropdown">
                <button class="btn d-flex align-items-center justify-content-center p-0" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px; border-radius: 50%; background-color: #d50000; color: #fff; font-weight: 700; font-size: 14px; border: 2px solid rgba(255,255,255,0.3); cursor: pointer;">
                    <?php echo $publicUserInitial; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile" style="min-width: 200px;">
                    <li><span class="dropdown-item-text fw-semibold text-muted" style="font-size: 13px;"><i class="fa fa-hand-peace me-1"></i>Welcome back, <?php echo htmlspecialchars($publicUserName); ?></span></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="#" onclick="openProfileModal(); return false;"><i class="fa fa-user-pen me-2"></i>View Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="publicUserLogout(); return false;"><i class="fa fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        <?php else: ?>
            <button type="button" class="btn btn-sm p-0 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#loginModal" style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.3); color: #fff;">
                <i class="fa fa-user" style="font-size: 14px;"></i>
            </button>
        <?php endif; ?>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Login Modal -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background-color: #1a1a2e; border-bottom: none; padding: 20px 24px 12px;">
                <h5 class="modal-title text-white" id="loginModalLabel"><i class="fa fa-right-to-bracket me-2"></i>Login to Your Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px 24px 24px;">
                <div id="login-message" style="display: none;"></div>
                <form id="publicLoginForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-envelope" style="color: #999; font-size: 14px;"></i></span>
                            <input type="email" class="form-control" name="email" placeholder="you@example.com" required style="border-left: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-lock" style="color: #999; font-size: 14px;"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Enter password" required style="border-left: none;">
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" id="loginSubmitBtn" style="background-color: #d50000; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: 15px;">
                        <i class="fa fa-right-to-bracket me-1"></i>Login
                    </button>
                </form>
                <div class="text-center mt-3" style="font-size: 13px; color: #888;">
                    Don't have an account? <a href="#" onclick="switchToSignup(); return false;" style="color: #d50000; font-weight: 600; text-decoration: none;">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Signup Modal -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background-color: #1a1a2e; border-bottom: none; padding: 20px 24px 12px;">
                <h5 class="modal-title text-white" id="signupModalLabel"><i class="fa fa-user-plus me-2"></i>Create an Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px 24px 24px;">
                <div id="signup-message" style="display: none;"></div>
                <form id="publicSignupForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-user" style="color: #999; font-size: 14px;"></i></span>
                            <input type="text" class="form-control" name="name" placeholder="John Doe" required minlength="2" maxlength="100" style="border-left: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-envelope" style="color: #999; font-size: 14px;"></i></span>
                            <input type="email" class="form-control" name="email" placeholder="you@example.com" required style="border-left: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-lock" style="color: #999; font-size: 14px;"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6" style="border-left: none;">
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" id="signupSubmitBtn" style="background-color: #d50000; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: 15px;">
                        <i class="fa fa-user-plus me-1"></i>Create Account
                    </button>
                </form>
                <div class="text-center mt-3" style="font-size: 13px; color: #888;">
                    Already have an account? <a href="#" onclick="switchToLogin(); return false;" style="color: #d50000; font-weight: 600; text-decoration: none;">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Profile Modal -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background-color: #1a1a2e; border-bottom: none; padding: 20px 24px 12px;">
                <h5 class="modal-title text-white" id="profileModalLabel"><i class="fa fa-user-pen me-2"></i>Your Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px 24px 24px;">
                <div id="profile-message" style="display: none;"></div>
                <form id="profileUpdateForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-user" style="color: #999; font-size: 14px;"></i></span>
                            <input type="text" class="form-control" name="name" id="profile-name" required minlength="2" maxlength="100" style="border-left: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-envelope" style="color: #999; font-size: 14px;"></i></span>
                            <input type="email" class="form-control" name="email" id="profile-email" required style="border-left: none;">
                        </div>
                    </div>
                    <hr style="border-color: #eee;">
                    <p class="text-muted" style="font-size: 12px; margin-bottom: 10px;"><i class="fa fa-info-circle me-1"></i>Leave password fields blank to keep current password.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-key" style="color: #999; font-size: 14px;"></i></span>
                            <input type="password" class="form-control" name="current_password" id="profile-current-password" placeholder="Required to change password" style="border-left: none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #555;">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8f9fa; border-right: none;"><i class="fa fa-lock" style="color: #999; font-size: 14px;"></i></span>
                            <input type="password" class="form-control" name="new_password" id="profile-new-password" placeholder="Min 6 characters" minlength="6" style="border-left: none;">
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" id="profileUpdateBtn" style="background-color: #d50000; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: 15px;">
                        <i class="fa fa-save me-1"></i>Save Changes
                    </button>
                </form>

                <!-- Delete Account Section -->
                <div class="mt-4 pt-3" style="border-top: 1px solid #eee;">
                    <p class="text-danger fw-semibold mb-2" style="font-size: 13px;"><i class="fa fa-triangle-exclamation me-1"></i>Danger Zone</p>
                    <div class="d-flex align-items-center gap-2">
                        <input type="password" class="form-control form-control-sm" id="delete-account-password" placeholder="Enter password to confirm" style="max-width: 250px;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePublicAccount()" id="deleteAccountBtn">
                            <i class="fa fa-trash me-1"></i>Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Search Modal -->
<div id="mobileSearchModal" class="mobile-search-modal" style="display: none;">
    <div class="mobile-search-backdrop"></div>
    <div class="mobile-search-container">
        <div class="mobile-search-header">
            <input
                type="text"
                id="mobileSearchInput"
                class="mobile-search-input"
                placeholder="Search phones and posts..."
                autocomplete="off">
            <button id="mobileSearchClose" class="mobile-search-close" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                </svg>
            </button>
        </div>
        <div id="mobileSearchResults" class="mobile-search-results"></div>
    </div>
</div>
<!-- Mobile Collapse of Gsmarecn -->
<div class="collapse mobile-menu d-lg-none" id="mobileMenu">
    <div class="menu-icons">
        <i class="fab fa-youtube" onclick="window.open('https://youtube.com/@devicesarena', '_blank')"></i>
        <i class="fab fa-instagram" onclick="window.open('https://www.instagram.com/devicesarenaofficial/', '_blank')"></i>
        <i class="fab fa-facebook-f" onclick="window.open('https://www.facebook.com/profile.php?id=61585936163841', '_blank')"></i>
        <i class="fab fa-twitter" onclick="window.open('https://twitter.com/', '_blank')"></i>
        <i class="fab fa-tiktok" onclick="window.open('https://www.tiktok.com/', '_blank')"></i>
    </div>
    <div class="column">
        <a href="<?php echo $base; ?>">Home</a>
        <a href="<?php echo $base; ?>reviews">Reviews</a>
        <a href="<?php echo $base; ?>featured">Featured</a>
        <a href="<?php echo $base; ?>phonefinder">Phone Finder</a>
        <a href="<?php echo $base; ?>compare">Compare</a>
        <a href="#">Videos</a>
        <a href="<?php echo $base; ?>contact-us">Contact Us</a>
    </div>
    <!-- Mobile Auth Links -->
    <div class="column" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px; margin-top: 4px;">
        <?php if ($isPublicUser): ?>
            <a href="#" onclick="openProfileModal(); closeMobileMenu(); return false;"><i class="fa fa-user-pen me-2"></i>View Profile</a>
            <a href="#" onclick="publicUserLogout(); return false;" class="text-danger"><i class="fa fa-right-from-bracket me-2"></i>Logout</a>
        <?php else: ?>
            <a href="#" onclick="openLoginFromMobile(); return false;"><i class="fa fa-right-to-bracket me-2"></i>Login</a>
            <a href="#" onclick="openSignupFromMobile(); return false;"><i class="fa fa-user-plus me-2"></i>Sign Up</a>
        <?php endif; ?>
    </div>
    <div class="brand-grid">
        <?php
        $mobile_brandChunks = array_chunk($mobile_brands, 1); // Create chunks of 1 brand per row
        foreach ($mobile_brandChunks as $mobile_brandRow):
            foreach ($mobile_brandRow as $mobile_brand): ?>
                <a href="#" class="brand-cell brand-item-bold" data-brand-id="<?php echo $mobile_brand['id']; ?>"><?php echo htmlspecialchars($mobile_brand['name']); ?></a>
        <?php endforeach;
        endforeach; ?>
        <a href="#" onclick="showBrandsModal(); return false;" style="cursor: pointer;">[...]</a>
    </div>
    <div class="menu-buttons d-flex justify-content-center ">
        <button class="btn bg-white w-50 text-black" onclick="window.open('<?php echo $base; ?>phonefinder')">Phone Finder</button>
        <button class="btn bg-white w-50 text-black">My Phone</button>
    </div>
</div>
<!-- Display Menu of Gsmarecn -->
<div id="leftMenu" class="container show">
    <div class="row">
        <div class="col-12 d-flex align-items-center   colums-gap">
            <a href="<?php echo $base; ?>" class="nav-link navbar-bold">Home</a>
            <a href="<?php echo $base; ?>compare" class="nav-link navbar-bold">Compare</a>
            <a href="#" class="nav-link navbar-bold">Videos</a>
            <a href="<?php echo $base; ?>reviews" class="nav-link navbar-bold">Reviews</a>
            <a href="<?php echo $base; ?>featured" class="nav-link d-lg-block d-none navbar-bold">Featured</a>
            <a href="<?php echo $base; ?>phonefinder" class="nav-link d-lg-block d-none navbar-bold">Phone Finder</a>
            <a href="<?php echo $base; ?>contact-us" class="nav-link d-lg-block d-none navbar-bold">Contact</a>
            <div style="background-color: #d50000; border-radius: 7px;" class="d-lg-none py-2" id="mobileSearchTrigger" onclick="openMobileSearch(event)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="16" width="16" class="mx-3">
                    <path fill="#ffffff" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                </svg>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Bootstrap collapse for mobile menu after DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        var mobileMenuButton = document.querySelector('.mobile-navbar .navbar-toggler');
        var mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuButton && mobileMenu && typeof bootstrap !== 'undefined') {
            // Manually initialize Bootstrap collapse
            new bootstrap.Collapse(mobileMenu, {
                toggle: false
            });
        }

        // Close menu when clicking on links inside mobileMenu
        var mobileMenuLinks = mobileMenu.querySelectorAll('a');
        mobileMenuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // Only close if it's a valid link (not javascript:void(0) or onclick with return false)
                var href = this.getAttribute('href');
                if (href && href !== '#' && !href.includes('javascript:')) {
                    // Close the collapse menu
                    var collapse = bootstrap.Collapse.getInstance(mobileMenu);
                    if (collapse) {
                        collapse.hide();
                    }
                }
            });
        });

        // Close menu when clicking menu buttons
        var menuButtons = mobileMenu.querySelectorAll('.menu-buttons button');
        menuButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close the collapse menu
                var collapse = bootstrap.Collapse.getInstance(mobileMenu);
                if (collapse) {
                    collapse.hide();
                }
            });
        });
    });
    // Set global base URL for JavaScript
    window.baseURL = '<?php echo $base; ?>';

    // ═══════════════════════════════════════════════════
    // Public User Auth JS
    // ═══════════════════════════════════════════════════
    function userAuthFetch(action, formData) {
        formData.append('action', action);
        return fetch(window.baseURL + 'user_auth_handler.php', {
            method: 'POST',
            body: formData
        }).then(function(r) {
            return r.json();
        });
    }

    function showAuthMsg(containerId, msg, type) {
        var el = document.getElementById(containerId);
        el.className = 'alert alert-' + type + ' alert-dismissible fade show';
        el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        el.style.display = 'block';
    }

    // ── Login ──
    var loginForm = document.getElementById('publicLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('loginSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Logging in...';

            var fd = new FormData(this);
            userAuthFetch('login', fd).then(function(data) {
                if (data.success) {
                    showAuthMsg('login-message', '<i class="fa fa-check-circle me-1"></i>' + data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showAuthMsg('login-message', '<i class="fa fa-exclamation-circle me-1"></i>' + data.message, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
                }
            }).catch(function() {
                showAuthMsg('login-message', 'An error occurred. Please try again.', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
            });
        });
    }

    // ── Signup ──
    var signupForm = document.getElementById('publicSignupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('signupSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Creating account...';

            var fd = new FormData(this);
            userAuthFetch('register', fd).then(function(data) {
                if (data.success) {
                    showAuthMsg('signup-message', '<i class="fa fa-check-circle me-1"></i>' + data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showAuthMsg('signup-message', '<i class="fa fa-exclamation-circle me-1"></i>' + data.message, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
                }
            }).catch(function() {
                showAuthMsg('signup-message', 'An error occurred. Please try again.', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
            });
        });
    }

    // ── Profile ──
    function openProfileModal() {
        var modal = new bootstrap.Modal(document.getElementById('profileModal'));
        // Load current data
        var fd = new FormData();
        userAuthFetch('get_profile', fd).then(function(data) {
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

    var profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('profileUpdateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving...';

            var fd = new FormData(this);
            userAuthFetch('update_profile', fd).then(function(data) {
                if (data.success) {
                    showAuthMsg('profile-message', '<i class="fa fa-check-circle me-1"></i>' + data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAuthMsg('profile-message', '<i class="fa fa-exclamation-circle me-1"></i>' + data.message, 'danger');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
            }).catch(function() {
                showAuthMsg('profile-message', 'An error occurred. Please try again.', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
            });
        });
    }

    // ── Delete Account ──
    function deletePublicAccount() {
        if (!confirm('Are you sure you want to permanently delete your account? This cannot be undone.')) return;
        var pwd = document.getElementById('delete-account-password').value.trim();
        if (!pwd) {
            showAuthMsg('profile-message', '<i class="fa fa-exclamation-circle me-1"></i>Please enter your password to confirm deletion.', 'warning');
            return;
        }
        var btn = document.getElementById('deleteAccountBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Deleting...';

        var fd = new FormData();
        fd.append('password', pwd);
        userAuthFetch('delete_account', fd).then(function(data) {
            if (data.success) {
                showAuthMsg('profile-message', '<i class="fa fa-check-circle me-1"></i>' + data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAuthMsg('profile-message', '<i class="fa fa-exclamation-circle me-1"></i>' + data.message, 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
            }
        }).catch(function() {
            showAuthMsg('profile-message', 'An error occurred.', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
        });
    }

    // ── Logout ──
    function publicUserLogout() {
        var fd = new FormData();
        userAuthFetch('logout', fd).then(function() {
            location.reload();
        });
    }

    // ── Modal switching helpers ──
    function switchToSignup() {
        bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('signupModal')).show();
        }, 300);
    }

    function switchToLogin() {
        bootstrap.Modal.getInstance(document.getElementById('signupModal')).hide();
        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        }, 300);
    }

    // ── Mobile menu helpers ──
    function closeMobileMenu() {
        var menu = document.getElementById('mobileMenu');
        if (menu) {
            var collapse = bootstrap.Collapse.getInstance(menu);
            if (collapse) collapse.hide();
        }
    }

    function openLoginFromMobile() {
        closeMobileMenu();
        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        }, 300);
    }

    function openSignupFromMobile() {
        closeMobileMenu();
        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('signupModal')).show();
        }, 300);
    }
</script>