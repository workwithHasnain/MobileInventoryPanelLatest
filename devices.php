<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';

// Require login for this page
requireLogin();

$brands = getAllBrands();

// Get initial filter and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$device_type_filter = isset($_GET['device_type']) ? trim($_GET['device_type']) : '';
?>
<html>

<head>
    <style>
        #devicesGrid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 1rem;
        }

        @media (max-width: 1400px) {
            #devicesGrid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 768px) {
            #devicesGrid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 576px) {
            #devicesGrid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .device-card .card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .device-card .card-img-top {
            height: 120px;
            object-fit: cover;
        }

        .device-card .card-body {
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        .device-card .card-title {
            font-size: 0.9rem !important;
            font-weight: 600;
        }

        .device-card .btn-group {
            margin-top: auto;
        }

        .device-card .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
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

                <!-- Results Count and Sorter -->
                <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <p class="text-muted mb-0">
                        Showing <span id="devicesCountShown">0</span> of <span id="devicesCountTotal">0</span> devices
                        <?php if (!empty($search) || !empty($brand_filter) || !empty($availability_filter) || !empty($device_type_filter)): ?>
                            - <a href="devices.php" class="text-decoration-none">Clear all filters</a>
                        <?php endif; ?>
                    </p>
                    <div style="width: 200px;">
                        <label for="deviceSorter" class="form-label mb-0 me-2 d-inline">Sort by:</label>
                        <select class="form-select form-select-sm d-inline" id="deviceSorter" style="width: auto;">
                            <option value="default">Default</option>
                            <option value="views-desc">Most Views</option>
                            <option value="views-asc">Least Views</option>
                            <option value="comments-desc">Most Comments</option>
                            <option value="comments-asc">Least Comments</option>
                        </select>
                    </div>
                </div>

                <!-- Device Grid -->
                <div id="devicesContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading devices...</span>
                        </div>
                    </div>
                </div>

                <!-- Load More Button -->
                <div class="text-center mt-4" id="loadMoreContainer" style="display: none;">
                    <button class="btn btn-primary" id="loadMoreBtn">
                        <i class="fas fa-chevron-down me-2"></i>Load More Devices
                    </button>
                    <p class="text-muted mt-2"><small>Page <span id="currentPage">1</span> of <span id="totalPages">1</span></small></p>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentSort = 'default';
        let gridElement = null;
        let modalIndex = 0;

        function getFilterParams() {
            return new URLSearchParams({
                search: document.getElementById('search').value,
                brand: document.getElementById('brand').value,
                availability: document.getElementById('availability').value,
                device_type: document.getElementById('device_type').value
            });
        }

        function createDeviceCard(phone, index) {
            const isTablet = phone.display_size && parseFloat(phone.display_size) >= 7.0;
            const deviceTypeLabel = isTablet ? 'Tablet' : 'Phone';
            const deviceTypeClass = isTablet ? 'bg-info' : 'bg-primary';

            let badgeClass = 'bg-secondary';
            switch (phone.availability) {
                case 'Available':
                    badgeClass = 'bg-success';
                    break;
                case 'Coming Soon':
                    badgeClass = 'bg-warning text-dark';
                    break;
                case 'Discontinued':
                    badgeClass = 'bg-danger';
                    break;
                case 'Rumored':
                    badgeClass = 'bg-info text-dark';
                    break;
            }

            let imageHtml = phone.image ?
                `<img src="${escapeHtml(phone.image)}" class="card-img-top" alt="${escapeHtml(phone.name)}" style="height: 250px; object-fit: cover;">` :
                `<div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 250px;"><i class="fas fa-mobile-alt fa-3x text-muted"></i></div>`;

            const mainCameraResolution = phone.main_camera_resolution ? (isNaN(phone.main_camera_resolution) ? phone.main_camera_resolution : phone.main_camera_resolution + ' MP') : '';
            const batteryCapacity = phone.battery_capacity ? (isNaN(phone.battery_capacity) ? phone.battery_capacity : phone.battery_capacity + ' mAh') : '';

            return `
                <div class="device-card" data-device-index="${index}" data-device-views="${phone.view_count}" data-device-comments="${phone.comment_count}">
                    <div class="card h-100 shadow-sm">
                        ${imageHtml}
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">${escapeHtml(phone.name)}</h5>
                                <span class="badge ${deviceTypeClass}">${deviceTypeLabel}</span>
                            </div>
                            <p class="card-text mb-3">
                                <strong>Brand:</strong> ${escapeHtml(phone.brand)}<br>
                                ${phone.year ? `<strong>Year:</strong> ${escapeHtml(phone.year)}<br>` : ''}
                                ${phone.price ? `<strong>Price:</strong> $${parseFloat(phone.price).toFixed(2)}<br>` : '<strong>Price:</strong> <span class="text-muted">—</span><br>'}
                            </p>

                            <span class="badge ${badgeClass} mb-3">
                                ${escapeHtml(phone.availability)}
                            </span>

                            <!-- Key Specifications -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    ${phone.ram ? `<i class="fas fa-memory"></i> ${escapeHtml(phone.ram)} RAM` : ''}
                                    ${phone.storage ? `<i class="fas fa-hdd ms-2"></i> ${escapeHtml(phone.storage)}` : ''}
                                    ${phone.display_size ? `<i class="fas fa-desktop ms-2"></i> ${escapeHtml(String(phone.display_size).replace(/"/g, ''))}\"` : ''}
                                </small>
                            </div>

                            <!-- View and Comment Stats -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-eye me-1"></i>${phone.view_count} views
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-comments me-1"></i>${phone.comment_count} comments
                                    </small>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-auto">
                                <div class="btn-group w-100" role="group">
                                    <a href="edit_device.php?id=${phone.id}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal${index}">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="delete_phone.php?id=${phone.id}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this device?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Modal for this device -->
                <div class="modal fade" id="viewModal${index}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${escapeHtml(phone.name)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        ${imageHtml}
                                    </div>
                                    <div class="col-md-8">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Brand:</strong></td>
                                                <td>${escapeHtml(phone.brand)}</td>
                                            </tr>
                                            ${phone.year ? `<tr><td><strong>Year:</strong></td><td>${escapeHtml(phone.year)}</td></tr>` : ''}
                                            ${phone.price ? `<tr><td><strong>Price:</strong></td><td>$${parseFloat(phone.price).toFixed(2)}</td></tr>` : ''}
                                            <tr>
                                                <td><strong>Availability:</strong></td>
                                                <td>${escapeHtml(phone.availability)}</td>
                                            </tr>
                                            ${phone.os ? `<tr><td><strong>OS:</strong></td><td>${escapeHtml(phone.os)}</td></tr>` : ''}
                                            ${phone.chipset ? `<tr><td><strong>Chipset:</strong></td><td>${escapeHtml(phone.chipset)}</td></tr>` : ''}
                                            ${phone.chipset_name ? `<tr><td><strong>Chipset:</strong></td><td>${escapeHtml(phone.chipset_name)}</td></tr>` : ''}
                                            ${phone.ram ? `<tr><td><strong>RAM:</strong></td><td>${escapeHtml(phone.ram)}</td></tr>` : ''}
                                            ${phone.storage ? `<tr><td><strong>Storage:</strong></td><td>${escapeHtml(phone.storage)}</td></tr>` : ''}
                                            ${phone.display_size ? `<tr><td><strong>Display:</strong></td><td>${escapeHtml(String(phone.display_size))}\" ${phone.display_resolution ? escapeHtml(phone.display_resolution) : ''}</td></tr>` : ''}
                                            ${mainCameraResolution ? `<tr><td><strong>Main Camera:</strong></td><td>${mainCameraResolution}</td></tr>` : ''}
                                            ${batteryCapacity ? `<tr><td><strong>Battery:</strong></td><td>${batteryCapacity}</td></tr>` : ''}
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="edit_device.php?id=${phone.id}" class="btn btn-primary">Edit Device</a>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function loadDevices(pageNum = 1, sort = 'default') {
            currentPage = pageNum;
            currentSort = sort;

            const params = getFilterParams();
            params.append('page', pageNum);
            params.append('sort', sort);

            const container = document.getElementById('devicesContainer');

            if (pageNum === 1) {
                container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                gridElement = null;
                modalIndex = 0;
            }

            fetch(`api_get_devices.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        totalPages = data.total_pages;

                        if (pageNum === 1) {
                            gridElement = document.createElement('div');
                            gridElement.id = 'devicesGrid';
                        }

                        if (data.devices.length === 0 && pageNum === 1) {
                            container.innerHTML = `
                                <div class="text-center py-5">
                                    <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No devices found</h4>
                                    <p class="text-muted">Try adjusting your search filters</p>
                                    <a href="devices.php" class="btn btn-outline-primary">Show All Devices</a>
                                </div>
                            `;
                            document.getElementById('loadMoreContainer').style.display = 'none';
                        } else {
                            data.devices.forEach((phone) => {
                                const cardHtml = createDeviceCard(phone, modalIndex);

                                // Create temporary wrapper to separate card from modals
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = cardHtml;

                                // Find and append the card (first element with device-card class)
                                const card = tempDiv.querySelector('.device-card');
                                if (card) {
                                    gridElement.appendChild(card);
                                }

                                // Find and append any modal (modal elements)
                                const modal = tempDiv.querySelector('.modal');
                                if (modal) {
                                    document.body.appendChild(modal);
                                }

                                modalIndex++;
                            });

                            if (pageNum === 1) {
                                container.innerHTML = '';
                                container.appendChild(gridElement);
                            }

                            const shownCount = Math.min(pageNum * 50, data.total);
                            document.getElementById('devicesCountShown').textContent = shownCount;
                            document.getElementById('devicesCountTotal').textContent = data.total;
                            document.getElementById('currentPage').textContent = pageNum;
                            document.getElementById('totalPages').textContent = totalPages;

                            document.getElementById('loadMoreContainer').style.display = pageNum < totalPages ? 'block' : 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (pageNum === 1) {
                        container.innerHTML = '<div class="alert alert-danger">Error loading devices</div>';
                    }
                });
        }

        document.getElementById('deviceSorter').addEventListener('change', function() {
            loadDevices(1, this.value);
        });

        document.getElementById('loadMoreBtn').addEventListener('click', function() {
            loadDevices(currentPage + 1, currentSort);
        });

        // Load initial devices on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDevices(1, 'default');
        });
    </script>

</body>

</html>