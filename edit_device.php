<?php
ob_start(); // Start output buffering to ensure redirects work
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'auth.php';
require_once 'phone_data.php'; // Keep for getAllPhones function
require_once 'brand_data.php';
require_once 'simple_device_update.php';
// Note: This page is for editing an existing device; insertion/update wiring will be added later.

// Require login for this page
requireLogin();

$errors = [];
$success = false;

// Fetch device by ID and prefill values (no update wiring yet)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$device = null;
if ($id > 0) {
    $device = getPhoneById($id);
}
if (!$device) {
    $_SESSION['error_message'] = 'Device not found!';
    header('Location: dashboard.php');
    exit();
}

// Prepare current images preview (main image + array images)
$existingImages = [];
// Normalize backslashes
$norm = function ($p) {
    return is_string($p) ? str_replace('\\', '/', $p) : $p;
};

// Helper to parse PostgreSQL TEXT[] to PHP array
$parsePgArray = function ($text) {
    if (!is_string($text)) return [];
    $text = trim($text);
    if ($text === '' || $text === '{}') return [];
    if ($text[0] === '{' && substr($text, -1) === '}') {
        $inner = substr($text, 1, -1);
        if ($inner === '') return [];
        // Split on commas not inside quotes; for our simple paths (no commas), a simple explode suffices
        $parts = explode(',', $inner);
        // Trim quotes and whitespace
        $clean = array_map(function ($v) {
            $v = trim($v);
            if ($v === 'NULL') return '';
            // remove optional surrounding quotes
            if ((strlen($v) >= 2) && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            return $v;
        }, $parts);
        return array_values(array_filter($clean, function ($v) {
            return trim($v) !== '';
        }));
    }
    return [];
};

// Add main image first if present
if (!empty($device['image'])) {
    $existingImages[] = $norm($device['image']);
}

// Add array images from 'images'
if (!empty($device['images'])) {
    if (is_array($device['images'])) {
        foreach ($device['images'] as $img) {
            $img = $norm($img);
            if ($img && !in_array($img, $existingImages, true)) $existingImages[] = $img;
        }
    } elseif (is_string($device['images'])) {
        foreach ($parsePgArray($device['images']) as $img) {
            $img = $norm($img);
            if ($img && !in_array($img, $existingImages, true)) $existingImages[] = $img;
        }
    }
}

// Legacy numbered images fallback if any
for ($i = 1; $i <= 10; $i++) {
    $key = 'image_' . $i;
    if (!empty($device[$key])) {
        $img = $norm($device[$key]);
        if ($img && !in_array($img, $existingImages, true)) $existingImages[] = $img;
    }
}

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validations similar to add
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    if ($name === '') {
        $errors['name'] = 'Phone name is required';
    }
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    if ($brand === '') {
        $errors['brand'] = 'Brand is required';
    }

    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    $availability = isset($_POST['availability']) ? trim($_POST['availability']) : '';
    $price = isset($_POST['price']) ? trim($_POST['price']) : '';

    // Handle image uploads: MERGE new uploads with existing images
    // Strategy: Keep existing images, and for each slot where a new file is uploaded, replace that slot
    $finalImages = $existingImages; // Start with current images
    $hasNewUploads = false;

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_images = 5;

        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Process each upload slot
        for ($i = 0; $i < min(count($_FILES['images']['name']), $max_images); $i++) {
            if (!empty($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['images']['type'][$i];
                $file_size = $_FILES['images']['size'][$i];

                // Validate type and size
                if (!in_array($file_type, $allowed_types)) {
                    $errors['image' . ($i + 1)] = 'Only JPG, PNG, and GIF images are allowed for image ' . ($i + 1);
                } elseif ($file_size > $max_size) {
                    $errors['image' . ($i + 1)] = 'Image ' . ($i + 1) . ' size should not exceed 5MB';
                } else {
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'device_' . time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $file_extension;
                    $upload_path = 'uploads/' . $filename;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        // Replace the image at this position (or add if position doesn't exist yet)
                        $finalImages[$i] = $upload_path;
                        $hasNewUploads = true;
                    } else {
                        $errors['image' . ($i + 1)] = 'Failed to upload image ' . ($i + 1) . '. Please try again.';
                    }
                }
            }
            // If no upload for this slot and slot exists, keep existing; otherwise do nothing
        }
    }

    if (empty($errors)) {
        // Reindex final images array to remove gaps
        $finalImages = array_values($finalImages);

        // Handle slug - validate and ensure uniqueness
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') {
            // Auto-generate slug if empty
            $slug = strtolower(trim($brand)) . '-' . strtolower(trim($name));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
        } else {
            // Validate and sanitize provided slug
            $slug = strtolower(trim($slug));
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        // Ensure slug is unique (excluding current device)
        require_once 'database_functions.php';
        $pdo = getConnection();
        $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE slug = ? AND id != ?");
        $slugCheck->execute([$slug, $id]);
        if ($slugCheck->fetchColumn() > 0) {
            // Slug exists, append a number
            $baseSlug = $slug;
            $counter = 1;
            do {
                $slug = $baseSlug . '-' . $counter;
                $slugCheck->execute([$slug, $id]);
                $counter++;
            } while ($slugCheck->fetchColumn() > 0);
        }

        // Prepare update payload
        $updated_phone = [
            // Launch
            'release_date' => ($_POST['release_date'] ?? null) !== '' ? $_POST['release_date'] : null,

            // General
            'name' => $name,
            'brand' => ($brand === '') ? null : $brand,
            'year' => ($year === '') ? null : $year,
            'availability' => ($availability === '') ? null : $availability,
            'price' => ($price === '') ? null : $price,
            'device_page_color' => !empty($_POST['device_page_color']) ? trim($_POST['device_page_color']) : null,

            // Highlight fields
            'weight' => !empty($_POST['weight']) ? trim($_POST['weight']) : null,
            'thickness' => !empty($_POST['thickness']) ? trim($_POST['thickness']) : null,
            'os' => !empty($_POST['os']) ? trim($_POST['os']) : null,
            'storage' => !empty($_POST['storage']) ? trim($_POST['storage']) : null,
            'card_slot' => isset($_POST['card_slot']) && $_POST['card_slot'] !== '' ? trim($_POST['card_slot']) : null,

            // Stats fields
            'display_size' => !empty($_POST['display_size']) ? trim($_POST['display_size']) : null,
            'display_resolution' => !empty($_POST['display_resolution']) ? trim($_POST['display_resolution']) : null,
            'main_camera_resolution' => !empty($_POST['main_camera_resolution']) ? trim($_POST['main_camera_resolution']) : null,
            'main_camera_video' => !empty($_POST['main_camera_video']) ? trim($_POST['main_camera_video']) : null,
            'ram' => !empty($_POST['ram']) ? trim($_POST['ram']) : null,
            'chipset_name' => !empty($_POST['chipset_name']) ? trim($_POST['chipset_name']) : null,
            'battery_capacity' => !empty($_POST['battery_capacity']) ? trim($_POST['battery_capacity']) : null,
            'wired_charging' => !empty($_POST['wired_charging']) ? trim($_POST['wired_charging']) : null,
            'wireless_charging' => !empty($_POST['wireless_charging']) ? trim($_POST['wireless_charging']) : null,

            // Grouped spec columns (JSON strings from hidden inputs)
            'network' => isset($_POST['network']) ? $_POST['network'] : null,
            'launch' => isset($_POST['launch']) ? $_POST['launch'] : null,
            'body' => isset($_POST['body']) ? $_POST['body'] : null,
            'display' => isset($_POST['display']) ? $_POST['display'] : null,
            'hardware' => isset($_POST['hardware']) ? $_POST['hardware'] : null,
            'memory' => isset($_POST['memory']) ? $_POST['memory'] : null,
            'main_camera' => isset($_POST['main_camera']) ? $_POST['main_camera'] : null,
            'selfie_camera' => isset($_POST['selfie_camera']) ? $_POST['selfie_camera'] : null,
            'multimedia' => isset($_POST['multimedia']) ? $_POST['multimedia'] : null,
            'connectivity' => isset($_POST['connectivity']) ? $_POST['connectivity'] : null,
            'features' => isset($_POST['features']) ? $_POST['features'] : null,
            'battery' => isset($_POST['battery']) ? $_POST['battery'] : null,
            'general_info' => isset($_POST['general_info']) ? $_POST['general_info'] : null,

            // SEO fields
            'slug' => $slug,
            'meta_title' => !empty($_POST['meta_title']) ? trim($_POST['meta_title']) : null,
            'meta_desc' => !empty($_POST['meta_desc']) ? trim($_POST['meta_desc']) : null,
        ];

        // ALWAYS include merged images in the update (finalImages has existing + new uploads merged)
        $updated_phone['image'] = !empty($finalImages) ? $finalImages[0] : null;
        $updated_phone['images'] = $finalImages;

        $result = simpleUpdateDevice($id, $updated_phone);
        if (is_array($result) && isset($result['error'])) {
            $errors['general'] = $result['error'];
        } elseif ($result === true) {
            $_SESSION['success_message'] = 'Device updated successfully!';
            header('Location: device.php?id=' . $id);
            exit();
        } else {
            $errors['general'] = 'Failed to update device. Please try again.';
        }
    }
}

// Prefill base fields
$name = $device['name'] ?? '';
$brand = $device['brand'] ?? '';
$year = $device['year'] ?? '';
$availability = $device['availability'] ?? '';
$price = $device['price'] ?? '';
$device_page_color = $device['device_page_color'] ?? '#ffffff';

// Highlights
$pref_weight = $device['weight'] ?? '';
$pref_thickness = $device['thickness'] ?? '';
$pref_os = $device['os'] ?? '';
$pref_storage = $device['storage'] ?? '';
$pref_card_slot = $device['card_slot'] ?? '';

// Stats
$pref_display_size = $device['display_size'] ?? '';
$pref_display_resolution = $device['display_resolution'] ?? '';
$pref_main_camera_resolution = $device['main_camera_resolution'] ?? '';
$pref_main_camera_video = $device['main_camera_video'] ?? '';
$pref_ram = $device['ram'] ?? '';
$pref_chipset_name = $device['chipset_name'] ?? '';
$pref_battery_capacity = $device['battery_capacity'] ?? '';
$pref_wired_charging = $device['wired_charging'] ?? '';
$pref_wireless_charging = $device['wireless_charging'] ?? '';

// SEO fields
$pref_slug = $device['slug'] ?? '';
$pref_meta_title = $device['meta_title'] ?? '';
$pref_meta_desc = $device['meta_desc'] ?? '';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Edit Device</h1>
            <p class="text-muted">Update the details of <?php echo htmlspecialchars($device['name'] ?? 'Device'); ?></p>
        </div>
        <div class="col-auto">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($errors['general']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <!-- Device Type Tabs -->
            <ul class="nav nav-tabs mb-4" id="deviceTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="phone-tab" data-bs-toggle="tab" data-bs-target="#phone-form" type="button" role="tab">
                        <i class="fas fa-mobile-alt me-2"></i> Device
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="deviceTypeTabContent">
                <!-- Phone Form Tab -->
                <div class="tab-pane fade show active" id="phone-form" role="tabpanel">
                    <form id="add-device-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" enctype="multipart/form-data">


                        <!-- 1. Launch Section -->
                        <div class="accordion mb-4" id="phoneAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="launchHeader">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#launchCollapse" aria-expanded="true" aria-controls="launchCollapse">
                                        <i class="fas fa-rocket me-2"></i> Launch
                                    </button>
                                </h2>
                                <div id="launchCollapse" class="accordion-collapse collapse show" aria-labelledby="launchHeader">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="release_date" class="form-label">Date of Release</label>
                                                <input type="date" class="form-control" id="release_date" name="release_date" value="<?php echo isset($_POST['release_date']) ? htmlspecialchars($_POST['release_date']) : htmlspecialchars($device['release_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. General Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="generalHeader">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse" aria-expanded="false" aria-controls="generalCollapse">
                                        <i class="fas fa-info-circle me-2"></i> General
                                    </button>
                                </h2>
                                <div id="generalCollapse" class="accordion-collapse collapse" aria-labelledby="generalHeader">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">Name *</label>
                                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                                    id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                                <?php if (isset($errors['name'])): ?>
                                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="brand" class="form-label">Brand *</label>
                                                <div class="input-group">
                                                    <select class="form-select <?php echo isset($errors['brand']) ? 'is-invalid' : ''; ?>"
                                                        id="brand" name="brand">
                                                        <option value="">Select a brand...</option>
                                                        <?php
                                                        $brands = getAllBrands();
                                                        if (!empty($brands)) {
                                                            foreach ($brands as $brandItem) {
                                                                $selected = isset($brand) && $brand === $brandItem['name'] ? 'selected' : '';
                                                                echo '<option value="' . htmlspecialchars($brandItem['name']) . '" ' . $selected . '>' .
                                                                    htmlspecialchars($brandItem['name']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                        <option value="other">Other (Custom)</option>
                                                    </select>
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        onclick="window.location.href='manage_data.php';"
                                                        <?php echo (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') ? 'disabled' : ''; ?>
                                                        title="<?php echo (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') ? 'Only admin can manage brands' : 'Manage Brands'; ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <div id="custom-brand-container" class="mt-2 d-none">
                                                    <input type="text" class="form-control" id="custom-brand"
                                                        placeholder="Enter custom brand name">
                                                </div>
                                                <?php if (isset($errors['brand'])): ?>
                                                    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['brand']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="year" class="form-label">Year *</label>
                                                <input type="number" class="form-control <?php echo isset($errors['year']) ? 'is-invalid' : ''; ?>"
                                                    id="year" name="year" min="2000" max="<?php echo date('Y') + 2; ?>"
                                                    value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : htmlspecialchars($year); ?>">
                                                <?php if (isset($errors['year'])): ?>
                                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['year']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="availability" class="form-label">Availability *</label>
                                                <select class="form-select <?php echo isset($errors['availability']) ? 'is-invalid' : ''; ?>"
                                                    id="availability" name="availability">
                                                    <option value="">Select availability...</option>
                                                    <?php $currentAvailability = isset($_POST['availability']) ? $_POST['availability'] : $availability; ?>
                                                    <option value="Available" <?php echo ($currentAvailability === 'Available') ? 'selected' : ''; ?>>Available</option>
                                                    <option value="Coming Soon" <?php echo ($currentAvailability === 'Coming Soon') ? 'selected' : ''; ?>>Coming Soon</option>
                                                    <option value="Discontinued" <?php echo ($currentAvailability === 'Discontinued') ? 'selected' : ''; ?>>Discontinued</option>
                                                    <option value="Rumored" <?php echo ($currentAvailability === 'Rumored') ? 'selected' : ''; ?>>Rumored</option>
                                                </select>
                                                <?php if (isset($errors['availability'])): ?>
                                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['availability']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="price" class="form-label">Price (USD) *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>"
                                                        id="price" name="price" min="0.01"
                                                        value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : htmlspecialchars($price); ?>">
                                                    <?php if (isset($errors['price'])): ?>
                                                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="device_page_color" class="form-label">Device Page Color</label>
                                                <input type="color" class="form-control form-control-color" id="device_page_color" name="device_page_color"
                                                    value="<?php echo isset($_POST['device_page_color']) ? htmlspecialchars($_POST['device_page_color']) : htmlspecialchars($device_page_color); ?>"
                                                    title="Choose a color for the device page theme">
                                                <small class="form-text text-muted">Color theme for device page</small>
                                            </div>

                                            <!-- Highlight Fields Section -->
                                            <div class="col-12 mb-3">
                                                <h6 class="text-muted border-bottom pb-2">Device Highlights (Quick Info)</h6>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="weight" class="form-label">Weight (g)</label>
                                                <input type="text" class="form-control" id="weight" name="weight" placeholder="e.g., 195"
                                                    value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : htmlspecialchars($pref_weight); ?>">
                                                <small class="form-text text-muted">In grams</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="thickness" class="form-label">Thickness (mm)</label>
                                                <input type="text" class="form-control" id="thickness" name="thickness" placeholder="e.g., 8.5"
                                                    value="<?php echo isset($_POST['thickness']) ? htmlspecialchars($_POST['thickness']) : htmlspecialchars($pref_thickness); ?>">
                                                <small class="form-text text-muted">In millimeters</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="os" class="form-label">Operating System</label>
                                                <input type="text" class="form-control" id="os" name="os" placeholder="e.g., Android 14"
                                                    value="<?php echo isset($_POST['os']) ? htmlspecialchars($_POST['os']) : htmlspecialchars($pref_os); ?>">
                                                <small class="form-text text-muted">OS name & version</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="storage" class="form-label">Storage</label>
                                                <input type="text" class="form-control" id="storage" name="storage" placeholder="e.g., 256GB"
                                                    value="<?php echo isset($_POST['storage']) ? htmlspecialchars($_POST['storage']) : htmlspecialchars($pref_storage); ?>">
                                                <small class="form-text text-muted">Storage capacity</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="card_slot" class="form-label">Memory Card Slot</label>
                                                <?php $currentCard = isset($_POST['card_slot']) ? $_POST['card_slot'] : $pref_card_slot; ?>
                                                <select class="form-select" id="card_slot" name="card_slot">
                                                    <option value="">Select...</option>
                                                    <option value="Yes" <?php echo ($currentCard === 'Yes') ? 'selected' : ''; ?>>Yes (Expandable)</option>
                                                    <option value="No" <?php echo ($currentCard === 'No') ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>

                                            <!-- Device Stats Section -->
                                            <div class="col-12 mb-3 mt-3">
                                                <h6 class="text-muted border-bottom pb-2">Device Stats (For Stats Bar)</h6>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="display_size" class="form-label">Display Size</label>
                                                <input type="text" class="form-control" id="display_size" name="display_size" placeholder="e.g., 6.1"
                                                    value="<?php echo isset($_POST['display_size']) ? htmlspecialchars($_POST['display_size']) : htmlspecialchars($pref_display_size); ?>">
                                                <small class="form-text text-muted">In inches (without ")</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="display_resolution" class="form-label">Display Resolution</label>
                                                <input type="text" class="form-control" id="display_resolution" name="display_resolution" placeholder="e.g., 1080 x 2340"
                                                    value="<?php echo isset($_POST['display_resolution']) ? htmlspecialchars($_POST['display_resolution']) : htmlspecialchars($pref_display_resolution); ?>">
                                                <small class="form-text text-muted">Width x Height</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="main_camera_resolution" class="form-label">Main Camera</label>
                                                <input type="text" class="form-control" id="main_camera_resolution" name="main_camera_resolution" placeholder="e.g., 50 MP"
                                                    value="<?php echo isset($_POST['main_camera_resolution']) ? htmlspecialchars($_POST['main_camera_resolution']) : htmlspecialchars($pref_main_camera_resolution); ?>">
                                                <small class="form-text text-muted">Megapixels</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="main_camera_video" class="form-label">Camera Video</label>
                                                <input type="text" class="form-control" id="main_camera_video" name="main_camera_video" placeholder="e.g., 4K@60fps"
                                                    value="<?php echo isset($_POST['main_camera_video']) ? htmlspecialchars($_POST['main_camera_video']) : htmlspecialchars($pref_main_camera_video); ?>">
                                                <small class="form-text text-muted">Video capability</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="ram" class="form-label">RAM</label>
                                                <input type="text" class="form-control" id="ram" name="ram" placeholder="e.g., 8GB"
                                                    value="<?php echo isset($_POST['ram']) ? htmlspecialchars($_POST['ram']) : htmlspecialchars($pref_ram); ?>">
                                                <small class="form-text text-muted">Memory size</small>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="chipset_name" class="form-label">Chipset</label>
                                                <input type="text" class="form-control" id="chipset_name" name="chipset_name" placeholder="e.g., Snapdragon 8 Gen 2"
                                                    value="<?php echo isset($_POST['chipset_name']) ? htmlspecialchars($_POST['chipset_name']) : htmlspecialchars($pref_chipset_name); ?>">
                                                <small class="form-text text-muted">Processor name</small>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="battery_capacity" class="form-label">Battery</label>
                                                <input type="text" class="form-control" id="battery_capacity" name="battery_capacity" placeholder="e.g., 5000"
                                                    value="<?php echo isset($_POST['battery_capacity']) ? htmlspecialchars($_POST['battery_capacity']) : htmlspecialchars($pref_battery_capacity); ?>">
                                                <small class="form-text text-muted">5000mAh</small>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="wired_charging" class="form-label">Wired Charging</label>
                                                <input type="text" class="form-control" id="wired_charging" name="wired_charging" placeholder="e.g., 65W"
                                                    value="<?php echo isset($_POST['wired_charging']) ? htmlspecialchars($_POST['wired_charging']) : htmlspecialchars($pref_wired_charging); ?>">
                                                <small class="form-text text-muted">Charging speed</small>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="wireless_charging" class="form-label">Wireless Charging</label>
                                                <input type="text" class="form-control" id="wireless_charging" name="wireless_charging" placeholder="e.g., 15W"
                                                    value="<?php echo isset($_POST['wireless_charging']) ? htmlspecialchars($_POST['wireless_charging']) : htmlspecialchars($pref_wireless_charging); ?>">
                                                <small class="form-text text-muted">Wireless speed</small>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Phone Images (up to 5)</label>
                                                <?php if (!empty($existingImages)): ?>
                                                    <div class="mb-3">
                                                        <div class="fw-semibold mb-2">Current Images</div>
                                                        <div class="row g-3">
                                                            <?php foreach ($existingImages as $idx => $imgPath): ?>
                                                                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                                                    <div class="border rounded p-1 h-100 text-center">
                                                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Current image <?php echo $idx + 1; ?>" class="img-fluid" style="max-height: 120px; object-fit: contain;">
                                                                        <div class="small text-muted mt-1">
                                                                            <?php echo $idx === 0 ? 'Main' : 'Image ' . ($idx + 1); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label for="image1" class="form-label">Image 1 (Main)</label>
                                                        <input type="file" class="form-control <?php echo isset($errors['image1']) ? 'is-invalid' : ''; ?>"
                                                            id="image1" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                        <?php if (isset($errors['image1'])): ?>
                                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['image1']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="image2" class="form-label">Image 2</label>
                                                        <input type="file" class="form-control" id="image2" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="image3" class="form-label">Image 3</label>
                                                        <input type="file" class="form-control" id="image3" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="image4" class="form-label">Image 4</label>
                                                        <input type="file" class="form-control" id="image4" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="image5" class="form-label">Image 5</label>
                                                        <input type="file" class="form-control" id="image5" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                    </div>
                                                </div>
                                                <div class="form-text">Max size per image: 5MB. Formats: JPG, PNG, GIF. First image will be the main display image.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="seoHeader">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#seoCollapse" aria-expanded="false" aria-controls="seoCollapse">
                                        <i class="fas fa-search me-2"></i> SEO Settings
                                    </button>
                                </h2>
                                <div id="seoCollapse" class="accordion-collapse collapse" aria-labelledby="seoHeader">
                                    <div class="accordion-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>SEO Fields:</strong> These fields help optimize the device page for search engines. The slug is auto-generated but can be customized.
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="slug" class="form-label">URL Slug *</label>
                                                <input type="text" class="form-control <?php echo isset($errors['slug']) ? 'is-invalid' : ''; ?>"
                                                    id="slug" name="slug" placeholder="e.g., apple-iphone-15-pro"
                                                    value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : htmlspecialchars($pref_slug); ?>">
                                                <small class="form-text text-muted">
                                                    URL-friendly identifier (auto-generated from brand + name). Must be unique. Only lowercase letters, numbers, and hyphens allowed.
                                                </small>
                                                <?php if (isset($errors['slug'])): ?>
                                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['slug']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="meta_title" class="form-label">Meta Title</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title"
                                                    placeholder="Custom title for search engines (leave empty to auto-generate)"
                                                    maxlength="255"
                                                    value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : htmlspecialchars($pref_meta_title); ?>">
                                                <small class="form-text text-muted">
                                                    Appears in search engine results. Recommended: 50-60 characters. Leave empty to use: "[Brand] [Name] - Specifications & Reviews"
                                                </small>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="meta_desc" class="form-label">Meta Description</label>
                                                <textarea class="form-control" id="meta_desc" name="meta_desc" rows="3"
                                                    placeholder="Brief description for search engines (leave empty to auto-generate)"
                                                    maxlength="500"><?php echo isset($_POST['meta_desc']) ? htmlspecialchars($_POST['meta_desc']) : htmlspecialchars($pref_meta_desc); ?></textarea>
                                                <small class="form-text text-muted">
                                                    Appears in search engine results. Recommended: 150-160 characters. Leave empty to auto-generate from device specs.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Specs Template Table (titles and subtitles only, descriptions intentionally blank) -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <strong>Specifications Template</strong>
                                <span class="text-muted" style="font-size: 0.9rem;">(Descriptions removed intentionally)</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="specs-template-table" class="table table-striped mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 18%">Section</th>
                                                <th style="width: 22%">Field</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Network -->
                                            <tr>
                                                <td>Network</td>
                                                <td>Technology</td>
                                                <td></td>
                                            </tr>

                                            <!-- Launch -->
                                            <tr>
                                                <td>Launch</td>
                                                <td>Announced</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Availability</td>
                                                <td></td>
                                            </tr>

                                            <!-- Body -->
                                            <tr>
                                                <td>Body</td>
                                                <td>Dimensions</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Weight</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Materials</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Connectivity Slot</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                            <!-- Display -->
                                            <tr>
                                                <td>Display</td>
                                                <td>Type</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Size</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Resolution</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Protection</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                            <!-- Hardware -->
                                            <tr>
                                                <td>Hardware</td>
                                                <td>OS</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>System Chip</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Processor</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>GPU</td>
                                                <td></td>
                                            </tr>

                                            <!-- Memory -->
                                            <tr>
                                                <td>System Memory</td>
                                                <td>Expansion Slot</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Storage</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                            <!-- Main Camera -->
                                            <tr>
                                                <td>Main Camera</td>
                                                <td>Dual</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Features</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Video Recording</td>
                                                <td></td>
                                            </tr>

                                            <!-- Selfie camera -->
                                            <tr>
                                                <td>Selfie camera</td>
                                                <td>Single</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Video Recording</td>
                                                <td></td>
                                            </tr>

                                            <!-- Multimedia -->
                                            <tr>
                                                <td>Multimedia</td>
                                                <td>Audio Output</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>3.5mm jack</td>
                                                <td></td>
                                            </tr>

                                            <!-- Connectivity -->
                                            <tr>
                                                <td>Connectivity</td>
                                                <td>WLAN</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Bluetooth</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Location</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Proximity</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Infrared port</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Radio</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>USB</td>
                                                <td></td>
                                            </tr>

                                            <!-- Features -->
                                            <tr>
                                                <td>Features</td>
                                                <td>Sensors</td>
                                                <td></td>
                                            </tr>

                                            <!-- Battery -->
                                            <tr>
                                                <td>Battery</td>
                                                <td>Type</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Charging</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Reverse wired</td>
                                                <td></td>
                                            </tr>

                                            <!-- General Info -->
                                            <tr>
                                                <td>General Info</td>
                                                <td>Colors</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Versions</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td>Price</td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs to carry serialized specs JSON for 13 grouped columns -->
                        <div id="specs-hidden-inputs">
                            <input type="hidden" name="network" id="spec_network" value="<?php echo htmlspecialchars($device['network'] ?? ''); ?>" />
                            <input type="hidden" name="launch" id="spec_launch" value="<?php echo htmlspecialchars($device['launch'] ?? ''); ?>" />
                            <input type="hidden" name="body" id="spec_body" value="<?php echo htmlspecialchars($device['body'] ?? ''); ?>" />
                            <input type="hidden" name="display" id="spec_display" value="<?php echo htmlspecialchars($device['display'] ?? ''); ?>" />
                            <input type="hidden" name="hardware" id="spec_hardware" value="<?php echo htmlspecialchars($device['hardware'] ?? ''); ?>" />
                            <input type="hidden" name="memory" id="spec_memory" value="<?php echo htmlspecialchars($device['memory'] ?? ''); ?>" />
                            <input type="hidden" name="main_camera" id="spec_main_camera" value="<?php echo htmlspecialchars($device['main_camera'] ?? ''); ?>" />
                            <input type="hidden" name="selfie_camera" id="spec_selfie_camera" value="<?php echo htmlspecialchars($device['selfie_camera'] ?? ''); ?>" />
                            <input type="hidden" name="multimedia" id="spec_multimedia" value="<?php echo htmlspecialchars($device['multimedia'] ?? ''); ?>" />
                            <input type="hidden" name="connectivity" id="spec_connectivity" value="<?php echo htmlspecialchars($device['connectivity'] ?? ''); ?>" />
                            <input type="hidden" name="features" id="spec_features" value="<?php echo htmlspecialchars($device['features'] ?? ''); ?>" />
                            <input type="hidden" name="battery" id="spec_battery" value="<?php echo htmlspecialchars($device['battery'] ?? ''); ?>" />
                            <input type="hidden" name="general_info" id="spec_general_info" value="<?php echo htmlspecialchars($device['general_info'] ?? ''); ?>" />
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <a href="dashboard.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle custom brand selection
    document.addEventListener('DOMContentLoaded', function() {
        const brandSelect = document.getElementById('brand');
        const customBrandContainer = document.getElementById('custom-brand-container');
        const customBrandInput = document.getElementById('custom-brand');

        if (brandSelect && customBrandContainer && customBrandInput) {
            // Listen for changes to the brand dropdown
            brandSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    // Show the custom brand input field
                    customBrandContainer.classList.remove('d-none');
                    customBrandInput.setAttribute('required', 'required');
                    customBrandInput.focus();
                } else {
                    // Hide the custom brand input field
                    customBrandContainer.classList.add('d-none');
                    customBrandInput.removeAttribute('required');
                }
            });

            // Before form submission, update the brand value if custom brand is used
            document.querySelector('form').addEventListener('submit', function(e) {
                if (brandSelect.value === 'other' && customBrandInput.value.trim() !== '') {
                    // Replace "other" with the custom brand name
                    brandSelect.value = customBrandInput.value.trim();
                }
            });
        }

        // Auto-generate slug from brand and name (but respect existing slug)
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        let slugManuallyEdited = (slugInput && slugInput.value !== ''); // If there's existing slug, consider it manually set

        // Function to generate slug
        function generateSlug() {
            if (slugManuallyEdited) return; // Don't auto-generate if user manually edited or existing slug present

            const brand = brandSelect.value === 'other' ? customBrandInput.value : brandSelect.value;
            const name = nameInput.value;

            if (brand && name) {
                let slug = (brand + '-' + name).toLowerCase();
                slug = slug.replace(/[^a-z0-9]+/g, '-'); // Replace non-alphanumeric with hyphens
                slug = slug.replace(/-+/g, '-'); // Remove duplicate hyphens
                slug = slug.replace(/^-|-$/g, ''); // Remove leading/trailing hyphens
                slugInput.value = slug;
            }
        }

        // Listen to brand and name changes
        if (brandSelect && nameInput && slugInput) {
            brandSelect.addEventListener('change', generateSlug);
            nameInput.addEventListener('input', generateSlug);
            if (customBrandInput) {
                customBrandInput.addEventListener('input', generateSlug);
            }

            // Mark slug as manually edited if user changes it
            slugInput.addEventListener('input', function() {
                slugManuallyEdited = true;
            });
        }

        // Device type removed; no toggling needed
    });
</script>

<!-- Form validation script -->
<script src="js/form-validation.js"></script>

<script>
    // Specs Template dynamic add/remove of fields per section
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('specs-template-table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        // Annotate rows with section grouping and inject action buttons
        let currentSection = '';
        [...tbody.rows].forEach((row) => {
            const sectionCell = row.cells[0];
            const fieldCell = row.cells[1];
            const descCell = row.cells[2];
            if (!sectionCell || !fieldCell) return;

            const sectionText = sectionCell.textContent.trim();
            const isFirst = sectionText !== '';
            if (isFirst) currentSection = sectionText;
            row.dataset.section = currentSection;
            row.dataset.isFirst = isFirst ? 'true' : 'false';

            // Make cells editable (sections should NOT be editable)
            sectionCell.contentEditable = 'false';
            if (descCell) descCell.contentEditable = 'true';

            // For field cell, wrap existing text in a span and make only that editable
            const fieldText = fieldCell.textContent;
            fieldCell.textContent = ''; // Clear the cell
            const fieldSpan = document.createElement('span');
            fieldSpan.contentEditable = 'true';
            fieldSpan.textContent = fieldText;
            fieldSpan.style.display = 'inline-block';
            fieldSpan.style.minWidth = '60%';
            fieldCell.appendChild(fieldSpan);

            // Create actions container on the right side of Field cell
            const actions = document.createElement('span');
            actions.className = 'float-end';
            actions.contentEditable = 'false';

            if (isFirst) {
                // Add button for the first row of each section
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'btn btn-sm btn-outline-primary btn-add-field';
                addBtn.title = 'Add field';
                addBtn.dataset.section = currentSection;
                addBtn.innerHTML = '<i class="fas fa-plus"></i>';
                actions.appendChild(addBtn);
            } else {
                // Remove button for subsequent rows
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-field';
                removeBtn.title = 'Remove field';
                removeBtn.innerHTML = '<i class="fas fa-minus"></i>';
                actions.appendChild(removeBtn);
            }

            // Ensure action buttons are visible even when Field is empty
            fieldCell.appendChild(actions);
        });

        // Prefill sections from hidden JSON (when editing existing device)
        (function prefillFromHidden() {
            const sectionKeyMap = {
                'Network': 'network',
                'Launch': 'launch',
                'Body': 'body',
                'Display': 'display',
                'Hardware': 'hardware',
                'Memory': 'memory',
                'Main Camera': 'main_camera',
                'Selfie camera': 'selfie_camera',
                'Multimedia': 'multimedia',
                'Connectivity': 'connectivity',
                'Features': 'features',
                'Battery': 'battery',
                'General Info': 'general_info'
            };

            const getJsonArray = (key) => {
                const el = document.getElementById('spec_' + key);
                if (!el) return [];
                const raw = (el.value || '').trim();
                if (!raw) return [];
                try {
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    console.warn('Invalid JSON for', key, e);
                    return [];
                }
            };

            // Helper to build a row mirroring our editable/actions structure
            const makeRow = (sectionName, fieldText, descText, isFirst) => {
                const tr = document.createElement('tr');
                tr.dataset.section = sectionName;
                tr.dataset.isFirst = isFirst ? 'true' : 'false';

                const tdSection = document.createElement('td');
                tdSection.contentEditable = 'false';
                tdSection.textContent = isFirst ? sectionName : '';

                const tdField = document.createElement('td');
                const fieldSpan = document.createElement('span');
                fieldSpan.contentEditable = 'true';
                fieldSpan.style.display = 'inline-block';
                fieldSpan.style.minWidth = '60%';
                fieldSpan.textContent = fieldText || '';
                const actions = document.createElement('span');
                actions.className = 'float-end';
                actions.contentEditable = 'false';
                if (isFirst) {
                    const addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.className = 'btn btn-sm btn-outline-primary btn-add-field';
                    addBtn.title = 'Add field';
                    addBtn.dataset.section = sectionName;
                    addBtn.innerHTML = '<i class="fas fa-plus"></i>';
                    actions.appendChild(addBtn);
                } else {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-field';
                    removeBtn.title = 'Remove field';
                    removeBtn.innerHTML = '<i class="fas fa-minus"></i>';
                    actions.appendChild(removeBtn);
                }
                tdField.appendChild(fieldSpan);
                tdField.appendChild(actions);

                const tdDesc = document.createElement('td');
                tdDesc.contentEditable = 'true';
                tdDesc.textContent = descText || '';

                tr.appendChild(tdSection);
                tr.appendChild(tdField);
                tr.appendChild(tdDesc);
                return tr;
            };

            // For each section, if we have JSON, replace the template rows with data rows
            Object.entries(sectionKeyMap).forEach(([sectionName, key]) => {
                const items = getJsonArray(key);
                if (!items.length) return;

                // Find the first row index for this section in current table
                const rows = [...tbody.rows];
                let firstIdx = rows.findIndex(r => (r.cells[0]?.textContent || '').trim() === sectionName);
                if (firstIdx === -1) return; // section not found

                // Determine the last index for this section (contiguous block)
                let lastIdx = firstIdx;
                for (let i = firstIdx + 1; i < rows.length; i++) {
                    const secText = (rows[i].cells[0]?.textContent || '').trim();
                    if (secText !== '') break; // next section block reached
                    lastIdx = i;
                }

                // Remove existing block
                for (let i = lastIdx; i >= firstIdx; i--) {
                    tbody.deleteRow(i);
                }

                // Insert new rows for this section based on JSON
                let insertBeforeNode = tbody.rows[firstIdx] || null; // if null, append at end
                items.forEach((it, idx) => {
                    const field = (it && typeof it.field === 'string') ? it.field : '';
                    const desc = (it && typeof it.description === 'string') ? it.description : '';
                    const row = makeRow(sectionName, field, desc, idx === 0);
                    if (insertBeforeNode) {
                        tbody.insertBefore(row, insertBeforeNode);
                    } else {
                        tbody.appendChild(row);
                    }
                });
            });
        })();

        // Event delegation for add/remove
        tbody.addEventListener('click', function(e) {
            const addBtn = e.target.closest('.btn-add-field');
            const removeBtn = e.target.closest('.btn-remove-field');

            if (addBtn) {
                const section = addBtn.dataset.section || '';
                if (!section) return;

                // Find last row in this section
                const rows = [...tbody.rows].filter(r => r.dataset.section === section);
                const lastRow = rows[rows.length - 1];

                // Create a new row with blank Field/Description and a remove button
                const newRow = document.createElement('tr');
                newRow.dataset.section = section;
                newRow.dataset.isFirst = 'false';

                const tdSection = document.createElement('td');
                tdSection.textContent = '';
                tdSection.contentEditable = 'false';

                const tdField = document.createElement('td');
                // Create editable span for field text
                const fieldText = document.createElement('span');
                fieldText.contentEditable = 'true';
                fieldText.style.display = 'inline-block';
                fieldText.style.minWidth = '60%';
                // Actions
                const actions = document.createElement('span');
                actions.className = 'float-end';
                actions.contentEditable = 'false';
                const removeBtn2 = document.createElement('button');
                removeBtn2.type = 'button';
                removeBtn2.className = 'btn btn-sm btn-outline-danger btn-remove-field';
                removeBtn2.title = 'Remove field';
                removeBtn2.innerHTML = '<i class="fas fa-minus"></i>';
                actions.appendChild(removeBtn2);
                tdField.appendChild(fieldText);
                tdField.appendChild(actions);

                const tdDesc = document.createElement('td');
                tdDesc.textContent = '';
                tdDesc.contentEditable = 'true';

                newRow.appendChild(tdSection);
                newRow.appendChild(tdField);
                newRow.appendChild(tdDesc);

                // Insert after last row of the section
                if (lastRow && lastRow.nextSibling) {
                    tbody.insertBefore(newRow, lastRow.nextSibling);
                } else {
                    tbody.appendChild(newRow);
                }

                return;
            }

            if (removeBtn) {
                const tr = removeBtn.closest('tr');
                if (!tr) return;
                // Do not remove first row of section (no remove button there anyway)
                tbody.removeChild(tr);
                return;
            }
        });

        // On form submit, serialize the table into per-section JSON arrays and fill hidden inputs
        const form = document.getElementById('add-device-form');
        if (form) {
            form.addEventListener('submit', function() {
                const sectionKeyMap = {
                    'Network': 'network',
                    'Launch': 'launch',
                    'Body': 'body',
                    'Display': 'display',
                    'Hardware': 'hardware',
                    'Memory': 'memory',
                    'Main Camera': 'main_camera',
                    'Selfie camera': 'selfie_camera',
                    'Multimedia': 'multimedia',
                    'Connectivity': 'connectivity',
                    'Features': 'features',
                    'Battery': 'battery',
                    'General Info': 'general_info'
                };

                // Initialize containers
                const dataByKey = {};
                Object.values(sectionKeyMap).forEach(k => dataByKey[k] = []);

                // Collect rows per original section (dataset.section)
                const rows = [...tbody.rows];
                rows.forEach((row) => {
                    const section = row.dataset.section || '';
                    const key = sectionKeyMap[section];
                    if (!key) return; // skip unknown sections

                    // Get field text from the editable span inside cell[1], not the entire cell
                    const fieldCell = row.cells[1];
                    const fieldSpan = fieldCell?.querySelector('span[contenteditable="true"]');
                    const field = (fieldSpan?.innerText || '').trim();

                    const desc = (row.cells[2]?.innerText || '').trim();

                    // Only push if something is present
                    if (field !== '' || desc !== '') {
                        dataByKey[key].push({
                            field,
                            description: desc
                        });
                    }
                });

                // Fill hidden inputs with JSON strings (or empty if none)
                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val && val.length ? JSON.stringify(val) : '';
                };

                setVal('spec_network', dataByKey['network']);
                setVal('spec_launch', dataByKey['launch']);
                setVal('spec_body', dataByKey['body']);
                setVal('spec_display', dataByKey['display']);
                setVal('spec_hardware', dataByKey['hardware']);
                setVal('spec_memory', dataByKey['memory']);
                setVal('spec_main_camera', dataByKey['main_camera']);
                setVal('spec_selfie_camera', dataByKey['selfie_camera']);
                setVal('spec_multimedia', dataByKey['multimedia']);
                setVal('spec_connectivity', dataByKey['connectivity']);
                setVal('spec_features', dataByKey['features']);
                setVal('spec_battery', dataByKey['battery']);
                setVal('spec_general_info', dataByKey['general_info']);
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>