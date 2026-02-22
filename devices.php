<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';

// Require login for this page
requireLogin();

// Get all phones for display
$phones = getAllPhones();
$brands = getAllBrands();

// Add view and comment counts for each device
foreach ($phones as $index => $phone) {
    $device_id = $phone['id'] ?? $phone['name'];

    try {
        $pdo = getConnection();

        // Get view count
        $view_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_views WHERE content_type = 'device' AND content_id = ?");
        $view_stmt->execute([$device_id]);
        $phones[$index]['view_count'] = $view_stmt->fetch()['count'] ?? 0;

        // Get comment count
        $comment_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_comments WHERE device_id = ?");
        $comment_stmt->execute([$device_id]);
        $phones[$index]['comment_count'] = $comment_stmt->fetch()['count'] ?? 0;
    } catch (Exception $e) {
        // If database error, set counts to 0
        $phones[$index]['view_count'] = 0;
        $phones[$index]['comment_count'] = 0;
    }
}
unset($phone); // Clean up any reference variables

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$device_type_filter = isset($_GET['device_type']) ? trim($_GET['device_type']) : '';

// Filter phones based on search criteria
$filtered_phones = $phones;

if (!empty($search)) {
    $filtered_phones = array_filter($filtered_phones, function ($phone) use ($search) {
        return stripos($phone['name'], $search) !== false ||
            stripos($phone['brand'], $search) !== false;
    });
}

if (!empty($brand_filter)) {
    $filtered_phones = array_filter($filtered_phones, function ($phone) use ($brand_filter) {
        return $phone['brand'] === $brand_filter;
    });
}

if (!empty($availability_filter)) {
    $filtered_phones = array_filter($filtered_phones, function ($phone) use ($availability_filter) {
        return $phone['availability'] === $availability_filter;
    });
}

if (!empty($device_type_filter)) {
    $filtered_phones = array_filter($filtered_phones, function ($phone) use ($device_type_filter) {
        // Heuristic: use display size to infer type (>= 7 inches => tablet)
        $isTablet = false;
        if (!empty($phone['display_size'])) {
            $sizeNum = preg_replace('/[^0-9\.]/', '', (string)$phone['display_size']);
            if ($sizeNum !== '' && is_numeric($sizeNum)) {
                $isTablet = floatval($sizeNum) >= 7.0;
            }
        }

        if ($device_type_filter === 'phone') {
            return !$isTablet;
        } elseif ($device_type_filter === 'tablet') {
            return $isTablet;
        }
        return true;
    });
}
?>
<html>

<head>
    <style>
        img.card-img-top {
            height: fit-content;
            width: fit-content;
            align-self: center;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-mobile-alt me-2"></i>Device Management</h2>
                    <a href="add_device.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Device
                    </a>
                </div>

                <!-- Search and Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    placeholder="Search by name or brand..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo htmlspecialchars($brand['name']); ?>"
                                            <?php echo $brand_filter === $brand['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="availability" class="form-label">Availability</label>
                                <select class="form-select" id="availability" name="availability">
                                    <option value="">All Status</option>
                                    <option value="Available" <?php echo $availability_filter === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Coming Soon" <?php echo $availability_filter === 'Coming Soon' ? 'selected' : ''; ?>>Coming Soon</option>
                                    <option value="Discontinued" <?php echo $availability_filter === 'Discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                                    <option value="Rumored" <?php echo $availability_filter === 'Rumored' ? 'selected' : ''; ?>>Rumored</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="device_type" class="form-label">Device Type</label>
                                <select class="form-select" id="device_type" name="device_type">
                                    <option value="">All Types</option>
                                    <option value="phone" <?php echo $device_type_filter === 'phone' ? 'selected' : ''; ?>>Phone</option>
                                    <option value="tablet" <?php echo $device_type_filter === 'tablet' ? 'selected' : ''; ?>>Tablet</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="devices.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Count -->
                <div class="mb-3">
                    <p class="text-muted">
                        Showing <?php echo count($filtered_phones); ?> of <?php echo count($phones); ?> devices
                        <?php if (!empty($search) || !empty($brand_filter) || !empty($availability_filter) || !empty($device_type_filter)): ?>
                            - <a href="devices.php" class="text-decoration-none">Clear all filters</a>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Device Grid -->
                <?php if (empty($filtered_phones)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No devices found</h4>
                        <?php if (!empty($search) || !empty($brand_filter) || !empty($availability_filter) || !empty($device_type_filter)): ?>
                            <p class="text-muted">Try adjusting your search filters</p>
                            <a href="devices.php" class="btn btn-outline-primary">Show All Devices</a>
                        <?php else: ?>
                            <p class="text-muted">Start by adding your first device</p>
                            <a href="add_device.php" class="btn btn-primary">Add New Device</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($filtered_phones as $index => $phone): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <?php if (!empty($phone['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($phone['image'] ?? ''); ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($phone['name'] ?? 'Device'); ?>"
                                            style="height: 250px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                            style="height: 250px;">
                                            <i class="fas fa-mobile-alt fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($phone['name'] ?? 'Unknown Device'); ?></h5>
                                            <?php
                                            // Determine device type using display size heuristic (>= 7 inches => Tablet)
                                            $isTablet = false;
                                            if (!empty($phone['display_size'])) {
                                                $sizeNum = preg_replace('/[^0-9\.]/', '', (string)$phone['display_size']);
                                                if ($sizeNum !== '' && is_numeric($sizeNum)) {
                                                    $isTablet = floatval($sizeNum) >= 7.0;
                                                }
                                            }
                                            $deviceTypeLabel = $isTablet ? 'Tablet' : 'Phone';
                                            $deviceTypeClass = $isTablet ? 'bg-info' : 'bg-primary';
                                            ?>
                                            <span class="badge <?php echo $deviceTypeClass; ?>"><?php echo $deviceTypeLabel; ?></span>
                                        </div>
                                        <p class="card-text">
                                            <strong>Brand:</strong> <?php echo htmlspecialchars($phone['brand'] ?? 'Unknown'); ?><br>
                                            <strong>Year:</strong> <?php echo htmlspecialchars($phone['year'] ?? 'Unknown'); ?><br>
                                            <strong>Price:</strong>
                                            <?php if (isset($phone['price']) && $phone['price'] !== null && $phone['price'] !== ''): ?>
                                                $<?php echo number_format((float)$phone['price'], 2); ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </p>

                                        <!-- Availability Badge -->
                                        <?php
                                        $badge_class = '';
                                        switch ($phone['availability']) {
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
                                        <span class="badge <?php echo $badge_class; ?> mb-3">
                                            <?php echo htmlspecialchars($phone['availability'] ?? 'Unknown'); ?>
                                        </span>

                                        <!-- Key Specifications -->
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <?php if (!empty($phone['ram'])): ?>
                                                    <i class="fas fa-memory"></i>
                                                    <?php echo htmlspecialchars($phone['ram']); ?> RAM
                                                <?php endif; ?>

                                                <?php if (!empty($phone['storage'])): ?>
                                                    <i class="fas fa-hdd ms-2"></i>
                                                    <?php echo htmlspecialchars($phone['storage']); ?>
                                                <?php endif; ?>

                                                <?php if (!empty($phone['display_size'])): ?>
                                                    <i class="fas fa-desktop ms-2"></i>
                                                    <?php echo htmlspecialchars(rtrim((string)$phone['display_size'], '"')); ?>"
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <!-- View and Comment Stats -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-eye me-1"></i><?php echo number_format($phone['view_count']); ?> views
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-comments me-1"></i><?php echo number_format($phone['comment_count']); ?> comments
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="mt-auto">
                                            <div class="btn-group w-100" role="group">
                                                <a href="edit_device.php?id=<?php echo $phone['id']; ?>"
                                                    class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-outline-info btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $index; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <a href="delete_phone.php?id=<?php echo $phone['id']; ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this device?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Modal for each device -->
                            <div class="modal fade" id="viewModal<?php echo $index; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($phone['name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <?php if (!empty($phone['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($phone['image']); ?>"
                                                            class="img-fluid rounded" alt="<?php echo htmlspecialchars($phone['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 250px;">
                                                            <i class="fas fa-mobile-alt fa-3x text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-8">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td><strong>Brand:</strong></td>
                                                            <td><?php echo htmlspecialchars($phone['brand']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Year:</strong></td>
                                                            <td><?php echo htmlspecialchars($phone['year']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Price:</strong></td>
                                                            <td>$<?php echo number_format($phone['price'], 2); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Availability:</strong></td>
                                                            <td><?php echo htmlspecialchars($phone['availability']); ?></td>
                                                        </tr>
                                                        <?php if (!empty($phone['os'])): ?>
                                                            <tr>
                                                                <td><strong>OS:</strong></td>
                                                                <td><?php echo htmlspecialchars($phone['os']); ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['chipset']) || !empty($phone['chipset_name'])): ?>
                                                            <tr>
                                                                <td><strong>Chipset:</strong></td>
                                                                <td><?php echo htmlspecialchars($phone['chipset'] ?? $phone['chipset_name']); ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['ram'])): ?>
                                                            <tr>
                                                                <td><strong>RAM:</strong></td>
                                                                <td><?php echo htmlspecialchars($phone['ram']); ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['storage'])): ?>
                                                            <tr>
                                                                <td><strong>Storage:</strong></td>
                                                                <td><?php echo htmlspecialchars($phone['storage']); ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['display_size'])): ?>
                                                            <tr>
                                                                <td><strong>Display:</strong></td>
                                                                <td><?php echo $phone['display_size']; ?>" <?php echo $phone['display_resolution'] ?? ''; ?></td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['main_camera_resolution'])): ?>
                                                            <tr>
                                                                <td><strong>Main Camera:</strong></td>
                                                                <td>
                                                                    <?php
                                                                    $mc = (string)$phone['main_camera_resolution'];
                                                                    echo htmlspecialchars(preg_match('/[a-zA-Z]/', $mc) ? $mc : ($mc . ' MP'));
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <?php if (!empty($phone['battery_capacity'])): ?>
                                                            <tr>
                                                                <td><strong>Battery:</strong></td>
                                                                <td>
                                                                    <?php
                                                                    $bc = (string)$phone['battery_capacity'];
                                                                    echo htmlspecialchars(preg_match('/[a-zA-Z]/', $bc) ? $bc : ($bc . ' mAh'));
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="edit_device.php?id=<?php echo $phone['id']; ?>" class="btn btn-primary">Edit Device</a>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>