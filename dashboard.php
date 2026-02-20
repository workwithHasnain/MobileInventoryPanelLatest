<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';
require_once 'filter_config.php';

// Require login for this page
requireLogin();

// Get all phones and brands
$phones = getAllPhones();
$brands = getAllBrands();

// Calculate statistics
$totalDevices = count($phones);
$totalBrands = count($brands);

// Count by availability
$availabilityStats = [];
foreach ($phones as $phone) {
    $availability = $phone['availability'] ?? 'Unknown';
    $availabilityStats[$availability] = ($availabilityStats[$availability] ?? 0) + 1;
}

// Count by brand (top 5)
$brandStats = [];
foreach ($phones as $phone) {
    $brand = $phone['brand'] ?? 'Unknown';
    $brandStats[$brand] = ($brandStats[$brand] ?? 0) + 1;
}
arsort($brandStats);
$topBrands = array_slice($brandStats, 0, 5, true);

// Count by year
$yearStats = [];
foreach ($phones as $phone) {
    $year = $phone['year'] ?? 'Unknown';
    $yearStats[$year] = ($yearStats[$year] ?? 0) + 1;
}
ksort($yearStats);

// Price statistics
$prices = array_filter(array_map(function ($phone) {
    return !empty($phone['price']) && is_numeric($phone['price']) ? (float)$phone['price'] : null;
}, $phones));

$avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0;
$minPrice = count($prices) > 0 ? min($prices) : 0;
$maxPrice = count($prices) > 0 ? max($prices) : 0;

// Success message handling
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <p class="text-muted">Overview of your mobile device inventory statistics</p>
        </div>
        <div class="col-auto">
            <a href="devices.php" class="btn btn-primary">
                <i class="fas fa-mobile-alt"></i> View All Devices
            </a>
            <a href="add_device.php" class="btn btn-success ms-2">
                <i class="fas fa-plus"></i> Add New Device
            </a>
            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#reviewsModal">
                <i class="fas fa-star"></i> Reviews
            </button>
            <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#newsletterModal">
                <i class="fas fa-envelope"></i> Newsletter Subscribers
            </button>
            <button type="button" class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#authModal">
                <i class="fas fa-lock"></i> Authentication
            </button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#filterSettingsModal">
                <i class="fas fa-sliders-h"></i> Filter Settings
            </button>
            <button type="button" class="btn btn-dark ms-2" data-bs-toggle="modal" data-bs-target="#heroImagesModal">
                <i class="fas fa-image"></i> Header Images
            </button>
            <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#canonicalBaseModal">
                <i class="fas fa-link"></i> Canonical Base
            </button>
            <button type="button" class="btn btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#sitemapModal">
                <i class="fas fa-sitemap"></i> Sitemap
            </button>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Key Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Devices</h6>
                            <h2 class="mb-0"><?php echo $totalDevices; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-mobile-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="devices.php" class="text-white text-decoration-none">
                        <small>View all devices <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Brands</h6>
                            <h2 class="mb-0"><?php echo $totalBrands; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="manage_data.php" class="text-white text-decoration-none">
                        <small>Manage brands <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Average Price</h6>
                            <h2 class="mb-0">$<?php echo number_format($avgPrice, 0); ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <small>Range: $<?php echo number_format($minPrice, 0); ?> - $<?php echo number_format($maxPrice, 0); ?></small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Available</h6>
                            <h2 class="mb-0"><?php echo $availabilityStats['Available'] ?? 0; ?></h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <small>Ready for sale</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($phones)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Welcome to your Device Management Dashboard!</h4>
                    <p>No devices have been added yet. Get started by adding your first device.</p>
                    <a href="add_device.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Add Your First Device
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Availability Status Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Device Availability Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($availabilityStats)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availabilityStats as $status => $count): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch ($status) {
                                                        case 'Available':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'Coming Soon':
                                                            $badge_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'Discontinued':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        case 'Rumored':
                                                            $badge_class = 'bg-info text-dark';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                </td>
                                                <td><?php echo $count; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar"
                                                            style="width: <?php echo ($count / $totalDevices) * 100; ?>%">
                                                            <?php echo round(($count / $totalDevices) * 100, 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Brands Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Top 5 Brands</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topBrands)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Brand</th>
                                            <th>Devices</th>
                                            <th>Market Share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topBrands as $brand => $count): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($brand); ?></strong></td>
                                                <td><?php echo $count; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?php echo ($count / $totalDevices) * 100; ?>%">
                                                            <?php echo round(($count / $totalDevices) * 100, 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Year Distribution -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i>Devices by Release Year</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($yearStats)): ?>
                            <div class="row">
                                <?php foreach ($yearStats as $year => $count): ?>
                                    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="bg-light rounded p-3">
                                                <h4 class="mb-1 text-primary"><?php echo $count; ?></h4>
                                                <small class="text-muted"><?php echo htmlspecialchars($year); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Reviews Modal -->
<div class="modal fade" id="reviewsModal" tabindex="-1" aria-labelledby="reviewsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewsLabel">
                    <i class="fas fa-star me-2"></i>Phone Reviews
                </h5>
                <div class="ms-auto">
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                        <i class="fas fa-plus me-1"></i>Add Review
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="reviews_message" style="display: none;"></div>
                <div id="reviews_list_container">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal fade" id="addReviewModal" tabindex="-1" aria-labelledby="addReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReviewLabel">
                    <i class="fas fa-plus me-2"></i>Create New Review
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="add_review_message" style="display: none;"></div>
                <form id="addReviewForm">
                    <div class="mb-3">
                        <label for="phoneSelect" class="form-label">Select Phone</label>
                        <select id="phoneSelect" class="form-control" style="width: 100%;">
                            <option></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="postSelect" class="form-label">Select Post</label>
                        <select id="postSelect" class="form-control" style="width: 100%;">
                            <option></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitReviewBtn">
                    <i class="fas fa-save me-1"></i>Create Review
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Newsletter Subscribers Modal -->
<div class="modal fade" id="newsletterModal" tabindex="-1" aria-labelledby="newsletterLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newsletterLabel">
                    <i class="fas fa-envelope me-2"></i>Newsletter Subscribers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="newsletter_message" style="display: none;"></div>

                <!-- Add Subscriber Form -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add New Subscriber</h6>
                        <button class="btn btn-sm btn-success" type="button" id="export_subscribers_btn" data-bs-toggle="modal" data-bs-target="#exportSubscribersModal">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="email" id="new_subscriber_email" class="form-control" placeholder="Enter email address">
                            <button class="btn btn-primary" type="button" id="add_subscriber_btn">
                                <i class="fas fa-plus me-1"></i>Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Subscribers List -->
                <div id="subscribers_list_container">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Subscribers Modal -->
<div class="modal fade" id="exportSubscribersModal" tabindex="-1" aria-labelledby="exportSubscribersLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportSubscribersLabel">
                    <i class="fas fa-download me-2"></i>Export Newsletter Subscribers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Choose which subscribers to export:</p>
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-outline-success btn-lg" id="export_active_btn">
                        <i class="fas fa-check-circle me-2"></i>Export Active Subscribers
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="export_inactive_btn">
                        <i class="fas fa-times-circle me-2"></i>Export Inactive Subscribers
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg" id="export_all_btn">
                        <i class="fas fa-list me-2"></i>Export All Subscribers
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Settings Modal -->
<div class="modal fade" id="filterSettingsModal" tabindex="-1" aria-labelledby="filterSettingsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterSettingsLabel">
                    <i class="fas fa-sliders-h me-2"></i>Phone Finder Filter Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="filterSettingsContent" class="container-fluid">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveFilterSettingsBtn">
                    <i class="fas fa-save me-2"></i>Update Settings
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Authentication Users Modal -->
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="authLabel">
                    <i class="fas fa-lock me-2"></i>Authentication Users
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="auth_message" style="display: none;"></div>

                <!-- Add User Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add New User</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" id="new_username" class="form-control" placeholder="Username" minlength="3">
                            </div>
                            <div class="col-md-4">
                                <input type="password" id="new_password" class="form-control" placeholder="Password" minlength="4">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" type="button" id="add_auth_user_btn">
                                    <i class="fas fa-plus me-1"></i>Add User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users List -->
                <div id="auth_users_container">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hero Images Modal -->
<div class="modal fade" id="heroImagesModal" tabindex="-1" aria-labelledby="heroImagesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="heroImagesLabel">
                    <i class="fas fa-image me-2"></i>Header Hero Images Management
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="hero_images_message" style="display: none;"></div>
                <p class="text-muted mb-4">
                    <i class="fas fa-info-circle me-1"></i>
                    Standard size: 712 x 340 pixels | Format: PNG only
                </p>

                <div class="row" id="hero_images_container">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Hero Image Modal -->
<div class="modal fade" id="uploadHeroImageModal" tabindex="-1" aria-labelledby="uploadHeroImageLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadHeroImageLabel">
                    <i class="fas fa-upload me-2"></i>Upload Hero Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="upload_hero_message" style="display: none;"></div>
                <input type="hidden" id="hero_page_name">
                <div class="alert alert-info">
                    <strong>Requirements:</strong>
                    <ul class="mb-0">
                        <li>File format: PNG only</li>
                        <li>Dimensions: 712 x 340 pixels</li>
                        <li>Maximum file size: 5MB</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <label for="hero_image_file" class="form-label">Select Image File</label>
                    <input type="file" class="form-control" id="hero_image_file" accept=".png,image/png">
                </div>
                <div id="image_preview_container" style="display: none;">
                    <label class="form-label">Preview:</label>
                    <div class="border rounded p-2 bg-light">
                        <img id="image_preview" src="" alt="Preview" class="img-fluid" style="max-width: 100%;">
                    </div>
                    <div id="image_dimensions" class="text-muted small mt-2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="upload_hero_btn" disabled>
                    <i class="fas fa-upload me-1"></i>Upload Image
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Canonical Base Modal -->
<div class="modal fade" id="canonicalBaseModal" tabindex="-1" aria-labelledby="canonicalBaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="canonicalBaseLabel">
                    <i class="fas fa-link me-2"></i>Canonical Base URL
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="canonical_message" style="display: none;"></div>
                <div class="mb-3">
                    <label for="canonicalBaseInput" class="form-label">Canonical Base URL</label>
                    <input type="url" class="form-control" id="canonicalBaseInput" placeholder="https://www.example.com" required>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        The primary domain URL for all canonical links (e.g., https://www.example.com)
                    </small>
                </div>
                <div class="alert alert-info small">
                    <strong>Current value:</strong><br>
                    <code id="currentCanonicalBase">Loading...</code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCanonicalBaseBtn">
                    <i class="fas fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sitemap Modal -->
<div class="modal fade" id="sitemapModal" tabindex="-1" aria-labelledby="sitemapLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sitemapLabel">
                    <i class="fas fa-sitemap me-2"></i>Sitemap Management
                </h5>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-success" id="syncSitemapBtn">
                        <i class="fas fa-sync-alt me-1"></i>Sync Sitemap
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="sitemap_message" style="display: none;"></div>
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Edit the sitemap XML below or click <strong>Sync Sitemap</strong> to auto-generate from all published posts and devices.
                    </small>
                </div>
                <textarea class="form-control font-monospace" id="sitemapContent" rows="20" style="font-size: 13px; tab-size: 2;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSitemapBtn">
                    <i class="fas fa-save me-1"></i>Save Sitemap
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ===== REVIEWS MANAGEMENT =====
        const reviewsModal = document.getElementById('reviewsModal');
        const addReviewModal = document.getElementById('addReviewModal');
        const submitReviewBtn = document.getElementById('submitReviewBtn');

        if (reviewsModal) {
            reviewsModal.addEventListener('show.bs.modal', function() {
                loadReviews();
            });
        }

        if (addReviewModal) {
            addReviewModal.addEventListener('show.bs.modal', function() {
                initSelect2();
            });
        }

        if (submitReviewBtn) {
            submitReviewBtn.addEventListener('click', submitReview);
        }

        function initSelect2() {
            // Initialize Select2 for phones
            $('#phoneSelect').select2({
                placeholder: 'Search phones...',
                allowClear: true,
                ajax: {
                    url: 'manage_reviews.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_phones',
                            term: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data
                        };
                    }
                },
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    return $('<span><img src="' + data.image + '" style="width: 30px; height: 30px; margin-right: 10px; vertical-align: middle;"/> ' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    if (!data.id) return data.text;
                    return $('<span><img src="' + data.image + '" style="width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;"/> ' + data.text + '</span>');
                }
            });

            // Initialize Select2 for posts
            $('#postSelect').select2({
                placeholder: 'Search posts...',
                allowClear: true,
                ajax: {
                    url: 'manage_reviews.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_posts',
                            term: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data
                        };
                    }
                },
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    return $('<span><img src="' + data.image + '" style="width: 30px; height: 30px; margin-right: 10px; vertical-align: middle;"/> ' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    if (!data.id) return data.text;
                    return $('<span><img src="' + data.image + '" style="width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;"/> ' + data.text + '</span>');
                }
            });
        }

        function loadReviews() {
            const container = document.getElementById('reviews_list_container');

            fetch('manage_reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=list'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderReviewsList(data.reviews);
                    } else {
                        container.innerHTML = '<div class="alert alert-danger">Error loading reviews</div>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
                });
        }

        function renderReviewsList(reviews) {
            const container = document.getElementById('reviews_list_container');

            if (reviews.length === 0) {
                container.innerHTML = '<div class="alert alert-info text-center">No reviews yet</div>';
                return;
            }

            let html = '<div class="row">';

            reviews.forEach(review => {
                html += `
                    <div class="col-lg-6 mb-3">
                        <div class="card h-100">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="${escapeHtml(review.phone_image)}" class="img-fluid rounded-start" alt="${escapeHtml(review.phone_name)}" style="height: 150px; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body pb-2">
                                        <h6 class="card-title mb-1"><i class="fas fa-mobile-alt me-2"></i>${escapeHtml(review.phone_name)}</h6>
                                        <p class="card-text small mb-2"><strong>Post:</strong> ${escapeHtml(review.post_title)}</p>
                                        ${review.post_image ? `<img src="${escapeHtml(review.post_image)}" class="img-thumbnail" alt="${escapeHtml(review.post_title)}" style="max-width: 80px; max-height: 60px; object-fit: cover; margin-bottom: 10px;">` : ''}
                                        <div>
                                            <button class="btn btn-sm btn-danger" onclick="deleteReview(${review.id})">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        window.deleteReview = function(id) {
            if (!confirm('Are you sure you want to delete this review?')) {
                return;
            }

            fetch('manage_reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showReviewMessage(data.message, 'success');
                        loadReviews();
                    } else {
                        showReviewMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showReviewMessage('Error: ' + error, 'danger');
                });
        };

        function submitReview() {
            const phoneId = $('#phoneSelect').val();
            const postId = $('#postSelect').val();

            if (!phoneId || !postId) {
                showAddReviewMessage('Please select both phone and post', 'warning');
                return;
            }

            submitReviewBtn.disabled = true;
            submitReviewBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';

            fetch('manage_reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=add&phone_id=' + phoneId + '&post_id=' + postId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAddReviewMessage(data.message, 'success');
                        document.getElementById('addReviewForm').reset();
                        $('#phoneSelect').val(null).trigger('change');
                        $('#postSelect').val(null).trigger('change');
                        loadReviews();
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('addReviewModal')).hide();
                            document.getElementById('add_review_message').style.display = 'none';
                        }, 1500);
                    } else {
                        showAddReviewMessage(data.message, 'danger');
                    }
                    submitReviewBtn.disabled = false;
                    submitReviewBtn.innerHTML = '<i class="fas fa-save me-1"></i>Create Review';
                })
                .catch(error => {
                    showAddReviewMessage('Error: ' + error, 'danger');
                    submitReviewBtn.disabled = false;
                    submitReviewBtn.innerHTML = '<i class="fas fa-save me-1"></i>Create Review';
                });
        }

        function showReviewMessage(message, type) {
            const messageDiv = document.getElementById('reviews_message');
            messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
            messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            messageDiv.style.display = 'block';
        }

        function showAddReviewMessage(message, type) {
            const messageDiv = document.getElementById('add_review_message');
            messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
            messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            messageDiv.style.display = 'block';
        }

        const filterModal = document.getElementById('filterSettingsModal');
        const filterContent = document.getElementById('filterSettingsContent');
        const saveBtn = document.getElementById('saveFilterSettingsBtn');

        // Load filter settings when modal opens
        filterModal.addEventListener('show.bs.modal', function() {
            loadFilterSettings();
        });

        // Save filter settings
        saveBtn.addEventListener('click', saveFilterSettings);
    });

    function loadFilterSettings() {
        const filterContent = document.getElementById('filterSettingsContent');

        // Load the filter settings form via AJAX
        fetch('manage_filter_settings.php?action=load')
            .then(response => response.text())
            .then(html => {
                filterContent.innerHTML = html;
            })
            .catch(error => {
                filterContent.innerHTML = '<div class="alert alert-danger">Error loading filter settings: ' + error + '</div>';
            });
    }

    function saveFilterSettings() {
        const formData = new FormData();
        formData.append('action', 'save');

        // Collect all form data from the filter settings modal
        const filterContent = document.getElementById('filterSettingsContent');
        const form = filterContent.querySelector('form');

        if (form) {
            const formEntries = new FormData(form);
            for (let [key, value] of formEntries) {
                formData.append(key, value);
            }
        } else {
            alert('Error: Filter form not found');
            return;
        }

        fetch('manage_filter_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Filter settings updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('filterSettingsModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update settings'));
                }
            })
            .catch(error => {
                alert('Error saving settings: ' + error);
            });
    }

    // Newsletter Subscribers Management
    const newsletterModal = document.getElementById('newsletterModal');
    const addSubscriberBtn = document.getElementById('add_subscriber_btn');
    const newSubscriberEmail = document.getElementById('new_subscriber_email');

    newsletterModal.addEventListener('show.bs.modal', function() {
        loadNewsletterSubscribers();
    });

    addSubscriberBtn.addEventListener('click', addSubscriber);
    newSubscriberEmail.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addSubscriber();
        }
    });

    function loadNewsletterSubscribers() {
        const container = document.getElementById('subscribers_list_container');

        fetch('manage_newsletter_subscribers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=list'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSubscribersList(data.subscribers);
                } else {
                    container.innerHTML = '<div class="alert alert-danger">Error loading subscribers</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
            });
    }

    function renderSubscribersList(subscribers) {
        const container = document.getElementById('subscribers_list_container');

        if (subscribers.length === 0) {
            container.innerHTML = '<div class="alert alert-info text-center">No subscribers yet</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Email</th><th>Status</th><th>Subscribed</th><th>Action</th></tr></thead><tbody>';

        subscribers.forEach(subscriber => {
            const subscribedDate = new Date(subscriber.subscribed_at).toLocaleDateString();
            const statusBadge = subscriber.status === 'active' ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-secondary">Inactive</span>';

            html += `
                <tr>
                    <td>${escapeHtml(subscriber.email)}</td>
                    <td>${statusBadge}</td>
                    <td><small class="text-muted">${subscribedDate}</small></td>
                    <td>
                        <button class="btn btn-sm btn-${subscriber.status === 'active' ? 'warning' : 'success'}" onclick="toggleSubscriberStatus(${subscriber.id}, '${subscriber.status}')">
                            <i class="fas fa-${subscriber.status === 'active' ? 'pause' : 'play'}"></i> ${subscriber.status === 'active' ? 'Deactivate' : 'Activate'}
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="removeSubscriber(${subscriber.id})">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function addSubscriber() {
        const email = newSubscriberEmail.value.trim();

        if (!email) {
            showNewsletterMessage('Please enter an email address', 'danger');
            return;
        }

        if (!validateEmail(email)) {
            showNewsletterMessage('Please enter a valid email address', 'danger');
            return;
        }

        addSubscriberBtn.disabled = true;
        addSubscriberBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';

        fetch('manage_newsletter_subscribers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=add&email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNewsletterMessage(data.message, 'success');
                    newSubscriberEmail.value = '';
                    loadNewsletterSubscribers();
                    setTimeout(() => {
                        document.getElementById('newsletter_message').style.display = 'none';
                    }, 3000);
                } else {
                    showNewsletterMessage(data.message, 'danger');
                }
                addSubscriberBtn.disabled = false;
                addSubscriberBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Add';
            })
            .catch(error => {
                showNewsletterMessage('Error: ' + error, 'danger');
                addSubscriberBtn.disabled = false;
                addSubscriberBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Add';
            });
    }

    function removeSubscriber(id) {
        if (!confirm('Are you sure you want to remove this subscriber?')) {
            return;
        }

        fetch('manage_newsletter_subscribers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=remove&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNewsletterMessage(data.message, 'success');
                    loadNewsletterSubscribers();
                } else {
                    showNewsletterMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showNewsletterMessage('Error: ' + error, 'danger');
            });
    }

    function toggleSubscriberStatus(id, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        fetch('manage_newsletter_subscribers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=status&id=' + id + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNewsletterMessage(data.message, 'success');
                    loadNewsletterSubscribers();
                } else {
                    showNewsletterMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showNewsletterMessage('Error: ' + error, 'danger');
            });
    }

    function showNewsletterMessage(message, type) {
        const messageDiv = document.getElementById('newsletter_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // ===== Newsletter Export =====
    const exportActiveBtn = document.getElementById('export_active_btn');
    const exportInactiveBtn = document.getElementById('export_inactive_btn');
    const exportAllBtn = document.getElementById('export_all_btn');

    if (exportActiveBtn) {
        exportActiveBtn.addEventListener('click', function() {
            exportSubscribers('active');
        });
    }

    if (exportInactiveBtn) {
        exportInactiveBtn.addEventListener('click', function() {
            exportSubscribers('inactive');
        });
    }

    if (exportAllBtn) {
        exportAllBtn.addEventListener('click', function() {
            exportSubscribers('all');
        });
    }

    function exportSubscribers(status) {
        // Show loading state
        const activeBtn = document.getElementById('export_active_btn');
        const inactiveBtn = document.getElementById('export_inactive_btn');
        const allBtn = document.getElementById('export_all_btn');

        // Disable all buttons temporarily
        activeBtn.disabled = true;
        inactiveBtn.disabled = true;
        allBtn.disabled = true;

        fetch('manage_newsletter_subscribers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=export&status=' + encodeURIComponent(status)
            })
            .then(response => response.text())
            .then(data => {
                // Create a blob and download
                const blob = new Blob([data], {
                    type: 'text/plain'
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const today = new Date().toISOString().split('T')[0];
                const filename = status === 'all' ? `all_subscribers_${today}.txt` : `${status}_subscribers_${today}.txt`;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                // Close modal and show success message
                const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportSubscribersModal'));
                if (exportModal) {
                    exportModal.hide();
                }
                showNewsletterMessage('Subscribers exported successfully!', 'success');
            })
            .catch(error => {
                showNewsletterMessage('Error exporting subscribers: ' + error, 'danger');
            })
            .finally(() => {
                // Re-enable buttons
                activeBtn.disabled = false;
                inactiveBtn.disabled = false;
                allBtn.disabled = false;
            });
    }

    // ===== Authentication Management =====
    const authModal = document.getElementById('authModal');

    if (authModal) {
        authModal.addEventListener('show.bs.modal', function() {
            loadAuthUsers();
        });

        document.getElementById('add_auth_user_btn').addEventListener('click', addAuthUser);
    }

    function loadAuthUsers() {
        const container = document.getElementById('auth_users_container');

        fetch('auth_management_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'list'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.users) {
                    let html = '';
                    data.users.forEach(user => {
                        html += `
                        <div class="card mb-2">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong>Username:</strong>
                                        <input type="text" class="form-control form-control-sm mt-1 username-input" value="${escapeHtml(user.username)}" data-user-id="${user.id}">
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Password:</strong>
                                        <input type="password" class="form-control form-control-sm mt-1 password-input" placeholder="Leave blank to keep current" data-user-id="${user.id}">
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Created: ${new Date(user.created_at).toLocaleDateString()}</small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button class="btn btn-sm btn-success me-2" onclick="updateAuthUser(${user.id})">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAuthUser(${user.id})">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load users</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Error loading users: ' + error + '</div>';
            });
    }

    function addAuthUser() {
        const username = document.getElementById('new_username').value.trim();
        const password = document.getElementById('new_password').value.trim();
        const btn = document.getElementById('add_auth_user_btn');

        if (!username || !password) {
            showAuthMessage('Username and password are required', 'warning');
            return;
        }

        if (username.length < 3) {
            showAuthMessage('Username must be at least 3 characters', 'warning');
            return;
        }

        if (password.length < 4) {
            showAuthMessage('Password must be at least 4 characters', 'warning');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        fetch('auth_management_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add',
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAuthMessage(data.message, 'success');
                    document.getElementById('new_username').value = '';
                    document.getElementById('new_password').value = '';
                    loadAuthUsers();
                    setTimeout(() => {
                        document.getElementById('auth_message').style.display = 'none';
                    }, 3000);
                } else {
                    showAuthMessage(data.message, 'danger');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Add User';
            })
            .catch(error => {
                showAuthMessage('Error: ' + error, 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Add User';
            });
    }

    function updateAuthUser(id) {
        const usernameInput = document.querySelector(`.username-input[data-user-id="${id}"]`);
        const passwordInput = document.querySelector(`.password-input[data-user-id="${id}"]`);

        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        if (!username) {
            showAuthMessage('Username is required', 'warning');
            return;
        }

        if (username.length < 3) {
            showAuthMessage('Username must be at least 3 characters', 'warning');
            return;
        }

        if (password && password.length < 4) {
            showAuthMessage('Password must be at least 4 characters', 'warning');
            return;
        }

        fetch('auth_management_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update',
                    id: id,
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAuthMessage(data.message, 'success');
                    passwordInput.value = '';
                    loadAuthUsers();
                    setTimeout(() => {
                        document.getElementById('auth_message').style.display = 'none';
                    }, 3000);
                } else {
                    showAuthMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showAuthMessage('Error: ' + error, 'danger');
            });
    }

    function deleteAuthUser(id) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        fetch('auth_management_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'delete',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAuthMessage(data.message, 'success');
                    loadAuthUsers();
                    setTimeout(() => {
                        document.getElementById('auth_message').style.display = 'none';
                    }, 3000);
                } else {
                    showAuthMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showAuthMessage('Error: ' + error, 'danger');
            });
    }

    function showAuthMessage(message, type) {
        const messageDiv = document.getElementById('auth_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
    }

    // ===== CANONICAL BASE MANAGEMENT =====
    const canonicalBaseModal = document.getElementById('canonicalBaseModal');
    const canonicalBaseInput = document.getElementById('canonicalBaseInput');
    const saveCanonicalBaseBtn = document.getElementById('saveCanonicalBaseBtn');
    const currentCanonicalBaseSpan = document.getElementById('currentCanonicalBase');

    if (canonicalBaseModal) {
        canonicalBaseModal.addEventListener('show.bs.modal', function() {
            loadCanonicalBase();
        });
    }

    if (saveCanonicalBaseBtn) {
        saveCanonicalBaseBtn.addEventListener('click', saveCanonicalBase);
    }

    function loadCanonicalBase() {
        fetch('manage_canonical_base.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    canonicalBaseInput.value = data.canonicalBase;
                    currentCanonicalBaseSpan.textContent = data.canonicalBase;
                } else {
                    showCanonicalMessage(data.message || 'Error loading canonical base', 'danger');
                }
            })
            .catch(error => {
                showCanonicalMessage('Error: ' + error, 'danger');
            });
    }

    function saveCanonicalBase() {
        const newValue = canonicalBaseInput.value.trim();

        if (!newValue) {
            showCanonicalMessage('Canonical base URL cannot be empty', 'warning');
            return;
        }

        // Basic URL validation
        if (!newValue.startsWith('http://') && !newValue.startsWith('https://')) {
            showCanonicalMessage('URL must start with http:// or https://', 'warning');
            return;
        }

        // Check for trailing slash
        if (newValue.endsWith('/')) {
            showCanonicalMessage('URL should not have a trailing slash', 'warning');
            return;
        }

        saveCanonicalBaseBtn.disabled = true;
        saveCanonicalBaseBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        fetch('manage_canonical_base.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=save&canonicalBase=' + encodeURIComponent(newValue)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCanonicalMessage(data.message, 'success');
                    currentCanonicalBaseSpan.textContent = newValue;
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(canonicalBaseModal).hide();
                        document.getElementById('canonical_message').style.display = 'none';
                    }, 1500);
                } else {
                    showCanonicalMessage(data.message || 'Error saving canonical base', 'danger');
                }
                saveCanonicalBaseBtn.disabled = false;
                saveCanonicalBaseBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
            })
            .catch(error => {
                showCanonicalMessage('Error: ' + error, 'danger');
                saveCanonicalBaseBtn.disabled = false;
                saveCanonicalBaseBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
            });
    }

    function showCanonicalMessage(message, type) {
        const messageDiv = document.getElementById('canonical_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
    }

    // ===== SITEMAP MANAGEMENT =====
    const sitemapModal = document.getElementById('sitemapModal');
    const sitemapContent = document.getElementById('sitemapContent');
    const saveSitemapBtn = document.getElementById('saveSitemapBtn');
    const syncSitemapBtn = document.getElementById('syncSitemapBtn');

    if (sitemapModal) {
        sitemapModal.addEventListener('show.bs.modal', function() {
            loadSitemap();
        });
    }

    if (saveSitemapBtn) {
        saveSitemapBtn.addEventListener('click', saveSitemap);
    }

    if (syncSitemapBtn) {
        syncSitemapBtn.addEventListener('click', syncSitemap);
    }

    function loadSitemap() {
        sitemapContent.value = 'Loading...';
        fetch('manage_sitemap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sitemapContent.value = data.content;
                } else {
                    showSitemapMessage(data.message, 'danger');
                    sitemapContent.value = '';
                }
            })
            .catch(error => {
                showSitemapMessage('Error: ' + error, 'danger');
            });
    }

    function saveSitemap() {
        const content = sitemapContent.value.trim();
        if (!content) {
            showSitemapMessage('Sitemap content cannot be empty', 'warning');
            return;
        }

        saveSitemapBtn.disabled = true;
        saveSitemapBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        fetch('manage_sitemap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=save&content=' + encodeURIComponent(content)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSitemapMessage(data.message, 'success');
                } else {
                    showSitemapMessage(data.message, 'danger');
                }
                saveSitemapBtn.disabled = false;
                saveSitemapBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Sitemap';
            })
            .catch(error => {
                showSitemapMessage('Error: ' + error, 'danger');
                saveSitemapBtn.disabled = false;
                saveSitemapBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Sitemap';
            });
    }

    function syncSitemap() {
        if (!confirm('This will regenerate the sitemap with all static pages, published posts, and devices. Continue?')) {
            return;
        }

        syncSitemapBtn.disabled = true;
        syncSitemapBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';

        fetch('manage_sitemap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=sync'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSitemapMessage(data.message, 'success');
                    if (data.content) {
                        sitemapContent.value = data.content;
                    }
                } else {
                    showSitemapMessage(data.message, 'danger');
                }
                syncSitemapBtn.disabled = false;
                syncSitemapBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync Sitemap';
            })
            .catch(error => {
                showSitemapMessage('Error: ' + error, 'danger');
                syncSitemapBtn.disabled = false;
                syncSitemapBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync Sitemap';
            });
    }

    function showSitemapMessage(message, type) {
        const messageDiv = document.getElementById('sitemap_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
    }

    // ===== HERO IMAGES MANAGEMENT =====
    const heroImagesModal = document.getElementById('heroImagesModal');
    const uploadHeroImageModal = document.getElementById('uploadHeroImageModal');
    const heroImageFileInput = document.getElementById('hero_image_file');
    const uploadHeroBtn = document.getElementById('upload_hero_btn');

    if (heroImagesModal) {
        heroImagesModal.addEventListener('show.bs.modal', function() {
            loadHeroImages();
        });
    }

    if (heroImageFileInput) {
        heroImageFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateAndPreviewImage(file);
            }
        });
    }

    if (uploadHeroBtn) {
        uploadHeroBtn.addEventListener('click', uploadHeroImage);
    }

    function loadHeroImages() {
        const container = document.getElementById('hero_images_container');

        fetch('manage_hero_images.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderHeroImagesList(data.pages);
                } else {
                    container.innerHTML = '<div class="alert alert-danger">Error loading hero images</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
            });
    }

    function renderHeroImagesList(pages) {
        const container = document.getElementById('hero_images_container');
        let html = '';

        pages.forEach(page => {
            const imageExists = page.exists;
            const imagePath = page.path;
            const displayName = page.display_name;
            const pageName = page.name;

            html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>${escapeHtml(displayName)}</h6>
                        </div>
                        <div class="card-body">
                            ${imageExists ? 
                                `<img src="${imagePath}?t=${Date.now()}" class="img-fluid rounded mb-3" alt="${escapeHtml(displayName)}" style="width: 100%; height: 170px; object-fit: cover;">` :
                                `<div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="width: 100%; height: 170px;">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p class="mb-0 small">No image uploaded</p>
                                    </div>
                                </div>`
                            }
                            <p class="small text-muted mb-2">
                                <strong>File:</strong> ${pageName}-hero.png<br>
                                <strong>Size:</strong> 712 x 340 px
                            </p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-primary w-100" onclick="openUploadModal('${pageName}', '${escapeHtml(displayName)}')">
                                <i class="fas fa-upload me-1"></i>${imageExists ? 'Change' : 'Upload'} Image
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function openUploadModal(pageName, displayName) {
        document.getElementById('hero_page_name').value = pageName;
        document.getElementById('uploadHeroImageLabel').innerHTML = `<i class="fas fa-upload me-2"></i>Upload Hero Image for ${displayName}`;
        document.getElementById('hero_image_file').value = '';
        document.getElementById('image_preview_container').style.display = 'none';
        document.getElementById('upload_hero_btn').disabled = true;
        document.getElementById('upload_hero_message').style.display = 'none';

        const uploadModal = new bootstrap.Modal(document.getElementById('uploadHeroImageModal'));
        uploadModal.show();
    }

    function validateAndPreviewImage(file) {
        const uploadBtn = document.getElementById('upload_hero_btn');
        const previewContainer = document.getElementById('image_preview_container');
        const preview = document.getElementById('image_preview');
        const dimensionsDiv = document.getElementById('image_dimensions');

        // Check file type
        if (file.type !== 'image/png') {
            showUploadHeroMessage('Please select a PNG image file only.', 'danger');
            uploadBtn.disabled = true;
            previewContainer.style.display = 'none';
            return;
        }

        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showUploadHeroMessage('File size must be less than 5MB.', 'danger');
            uploadBtn.disabled = true;
            previewContainer.style.display = 'none';
            return;
        }

        // Preview image and check dimensions
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const width = this.width;
                const height = this.height;

                preview.src = e.target.result;
                previewContainer.style.display = 'block';
                dimensionsDiv.textContent = `Dimensions: ${width} x ${height} pixels`;

                // Check if dimensions match exactly
                if (width === 712 && height === 340) {
                    dimensionsDiv.className = 'text-success small mt-2';
                    dimensionsDiv.innerHTML = `<i class="fas fa-check-circle me-1"></i>Dimensions: ${width} x ${height} pixels (Perfect!)`;
                    uploadBtn.disabled = false;
                    showUploadHeroMessage('Image is ready to upload!', 'success');
                } else {
                    dimensionsDiv.className = 'text-danger small mt-2';
                    dimensionsDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Dimensions: ${width} x ${height} pixels (Required: 712 x 340)`;
                    uploadBtn.disabled = true;
                    showUploadHeroMessage('Image dimensions must be exactly 712 x 340 pixels.', 'danger');
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function uploadHeroImage() {
        const pageName = document.getElementById('hero_page_name').value;
        const fileInput = document.getElementById('hero_image_file');
        const file = fileInput.files[0];

        if (!file) {
            showUploadHeroMessage('Please select a file.', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('page_name', pageName);
        formData.append('hero_image', file);

        uploadHeroBtn.disabled = true;
        uploadHeroBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';

        fetch('manage_hero_images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUploadHeroMessage(data.message, 'success');
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('uploadHeroImageModal')).hide();
                        loadHeroImages(); // Reload the list
                        showHeroImagesMessage('Hero image updated successfully!', 'success');
                    }, 1500);
                } else {
                    showUploadHeroMessage(data.message, 'danger');
                }
                uploadHeroBtn.disabled = false;
                uploadHeroBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
            })
            .catch(error => {
                showUploadHeroMessage('Error: ' + error, 'danger');
                uploadHeroBtn.disabled = false;
                uploadHeroBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
            });
    }

    function showUploadHeroMessage(message, type) {
        const messageDiv = document.getElementById('upload_hero_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
    }

    function showHeroImagesMessage(message, type) {
        const messageDiv = document.getElementById('hero_images_message');
        messageDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        messageDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        messageDiv.style.display = 'block';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
</script>

<style>
    /* Fix Select2 dropdown appearing behind modal */
    .select2-dropdown {
        z-index: 2000 !important;
    }

    .select2-container--open {
        z-index: 2000 !important;
    }
</style>

</div>

<?php include 'includes/footer.php'; ?>