<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';

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
$prices = array_filter(array_map(function($phone) {
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
                                        switch($status) {
                                            case 'Available': $badge_class = 'bg-success'; break;
                                            case 'Coming Soon': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Discontinued': $badge_class = 'bg-danger'; break;
                                            case 'Rumored': $badge_class = 'bg-info text-dark'; break;
                                            default: $badge_class = 'bg-secondary';
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

<?php include 'includes/footer.php'; ?>
