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
            <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#newsletterModal">
                <i class="fas fa-envelope"></i> Newsletter Subscribers
            </button>
            <button type="button" class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#authModal">
                <i class="fas fa-lock"></i> Authentication
            </button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#filterSettingsModal">
                <i class="fas fa-sliders-h"></i> Filter Settings
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
</script>
</div>

<?php include 'includes/footer.php'; ?>