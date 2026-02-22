<?php
// Public brands page - no authentication required
require_once 'database_functions.php';
$pdo = getConnection();

// Get selected brand from URL parameter
$selectedBrandId = $_GET['brand'] ?? '';

// Get only brands that have devices with device counts
$brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get devices for selected brand or all devices
if ($selectedBrandId) {
    $devices_stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name 
        FROM phones p 
        JOIN brands b ON p.brand_id = b.id 
        WHERE b.id = ? 
        ORDER BY p.name ASC
    ");
    $devices_stmt->execute([$selectedBrandId]);
    $devices = $devices_stmt->fetchAll();

    // Get selected brand name
    $selected_brand_stmt = $pdo->prepare("SELECT name FROM brands WHERE id = ?");
    $selected_brand_stmt->execute([$selectedBrandId]);
    $selectedBrandName = $selected_brand_stmt->fetchColumn();
} else {
    $devices = getAllPhonesDB();
    $selectedBrandName = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands & Devices - Mobile Tech Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 50px;
        }

        .brand-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .brand-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .device-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .device-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .brand-active {
            border: 2px solid #17a2b8;
            background-color: #e3f2fd;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>Mobile Tech Hub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="featured_posts.php">Featured Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="brands.php">Brands</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/compare">Compare Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="phone_finder.php">Phone Finder</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3">Explore Brands & Devices</h1>
                    <p class="lead mb-4">
                        Discover all mobile device brands and their complete device collections.
                        Browse by brand to find the perfect device for your needs.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#brands" class="btn btn-light btn-lg">
                            <i class="fas fa-industry me-2"></i>Browse Brands
                        </a>
                        <a href="#devices" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-mobile-alt me-2"></i>View Devices
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="border-end border-light pe-3">
                                <h3 class="fw-bold"><?php echo count($brands); ?></h3>
                                <p class="mb-0">Total Brands</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h3 class="fw-bold"><?php echo count($devices); ?></h3>
                            <p class="mb-0">Total Devices</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Brands Section -->
        <section id="brands" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-industry text-info me-2"></i>All Brands
                </h2>
                <?php if ($selectedBrandId): ?>
                    <a href="brands.php" class="btn btn-outline-info">
                        <i class="fas fa-times me-1"></i>Clear Filter
                    </a>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if (empty($brands)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Brands Available</h4>
                            <p class="text-muted">Check back later for brand listings!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($brands as $brand): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 brand-card <?php echo ($selectedBrandId == $brand['id']) ? 'brand-active' : ''; ?>"
                                data-brand-id="<?php echo $brand['id']; ?>">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-industry fa-3x text-info"></i>
                                    </div>
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($brand['name']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo $brand['device_count']; ?> Device<?php echo $brand['device_count'] != 1 ? 's' : ''; ?>
                                    </p>
                                    <div class="mt-auto">
                                        <a href="brands.php?brand=<?php echo $brand['id']; ?>"
                                            class="btn btn-info btn-sm w-100">
                                            View Devices
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Devices Section -->
        <section id="devices" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-mobile-alt text-success me-2"></i>
                    <?php if ($selectedBrandName): ?>
                        <?php echo htmlspecialchars($selectedBrandName); ?> Devices
                    <?php else: ?>
                        All Devices
                    <?php endif; ?>
                </h2>
                <div>
                    <a href="phone_finder.php" class="btn btn-success me-2">
                        <i class="fas fa-search me-1"></i>Advanced Search
                    </a>
                    <a href="/compare" class="btn btn-outline-success">
                        <i class="fas fa-balance-scale me-1"></i>Compare Devices
                    </a>
                </div>
            </div>

            <div class="row">
                <?php if (empty($devices)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Devices Available</h4>
                            <?php if ($selectedBrandName): ?>
                                <p class="text-muted">No devices found for <?php echo htmlspecialchars($selectedBrandName); ?>.</p>
                            <?php else: ?>
                                <p class="text-muted">Check back later for device listings!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 device-card" data-device-id="<?php echo $device['id']; ?>" style="cursor: pointer;">
                                <div class="position-relative">
                                    <?php if (!empty($device['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($device['image']); ?>"
                                            class="card-img-top" alt="Device Image"
                                            style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                            style="height: 200px;">
                                            <i class="fas fa-mobile-alt fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Availability Badge -->
                                    <?php
                                    $availability_class = '';
                                    $availability_text = $device['availability'] ?? 'Unknown';
                                    switch (strtolower($availability_text)) {
                                        case 'available':
                                            $availability_class = 'bg-success';
                                            break;
                                        case 'discontinued':
                                            $availability_class = 'bg-danger';
                                            break;
                                        case 'coming soon':
                                            $availability_class = 'bg-warning';
                                            break;
                                        default:
                                            $availability_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="position-absolute top-0 end-0 badge <?php echo $availability_class; ?> m-2">
                                        <?php echo htmlspecialchars($availability_text); ?>
                                    </span>
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-info"><?php echo htmlspecialchars($device['brand_name'] ?? 'Unknown Brand'); ?></span>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($device['name']); ?></h5>

                                    <div class="device-specs text-muted small mb-3">
                                        <?php if (!empty($device['display_size'])): ?>
                                            <div><i class="fas fa-tv me-1"></i><?php echo htmlspecialchars($device['display_size']); ?>"</div>
                                        <?php endif; ?>
                                        <?php if (!empty($device['ram'])): ?>
                                            <div><i class="fas fa-memory me-1"></i><?php echo htmlspecialchars($device['ram']); ?> RAM</div>
                                        <?php endif; ?>
                                        <?php if (!empty($device['battery_capacity'])): ?>
                                            <div><i class="fas fa-battery-full me-1"></i><?php echo htmlspecialchars($device['battery_capacity']); ?>mAh</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-auto">
                                        <?php if (!empty($device['price']) && $device['price'] !== 'Not available'): ?>
                                            <div class="mb-2">
                                                <span class="text-primary fw-bold h5"><?php echo htmlspecialchars($device['price']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <button class="btn btn-primary btn-sm w-100" onclick="showDeviceDetails(<?php echo $device['id']; ?>)">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Device Detail Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deviceModalTitle">Device Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deviceModalBody">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-mobile-alt me-2"></i>Mobile Tech Hub
                    </h5>
                    <p class="text-muted">Your ultimate destination for mobile device specifications, reviews, and comparisons.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="featured_posts.php" class="text-muted text-decoration-none">Featured Posts</a></li>
                        <li><a href="brands.php" class="text-muted text-decoration-none">Brands</a></li>
                        <li><a href="phone_finder.php" class="text-muted text-decoration-none">Phone Finder</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">Contact</h5>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i>info@mobiletechhub.com<br>
                        <i class="fas fa-phone me-2"></i>+1 (555) 123-4567
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="text-muted mb-0">&copy; 2025 Mobile Tech Hub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle device card clicks
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.device-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const deviceId = this.getAttribute('data-device-id');
                    if (deviceId) {
                        // Track the view
                        fetch('/track_device_view.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'device_id=' + encodeURIComponent(deviceId)
                        });

                        // Show device details modal
                        showDeviceDetails(deviceId);
                    }
                });
            });
        });

        // Show device details in modal
        function showDeviceDetails(deviceId) {
            fetch(`/get_device_details.php?id=${deviceId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('deviceModalBody').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('deviceModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load device details');
                });
        }
    </script>
</body>

</html>