<?php
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
</nav>

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
        <a href="#">Contact Us</a>
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
            <a href="#" class="nav-link d-lg-block d-none navbar-bold">Contact</a>
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
</script>