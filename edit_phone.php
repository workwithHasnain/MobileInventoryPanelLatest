<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';

// Helper function to get value from phone data
function getValue($key, $default = '')
{
    global $phone;
    return isset($phone[$key]) ? $phone[$key] : $default;
}

// Helper function to check if a boolean value is true
function isChecked($key)
{
    global $phone;
    return isset($phone[$key]) && $phone[$key] ? 'checked' : '';
}

// Helper function to check if a value is selected
function isSelected($key, $value)
{
    global $phone;
    return isset($phone[$key]) && $phone[$key] === $value ? 'selected' : '';
}

// Helper function to check if a value exists in an array
function isInArray($key, $value)
{
    global $phone;
    return isset($phone[$key]) && is_array($phone[$key]) && in_array($value, $phone[$key]) ? 'checked' : '';
}

// Require login for this page
requireLogin();

$errors = [];
$success = false;
$phone = null;

// Get phone ID from URL parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;

// Get phone data
$phone = getPhoneById($id);

// Check if phone exists
if (!$phone) {
    $_SESSION['error_message'] = 'Phone not found!';
    header('Location: dashboard.php');
    exit();
}

// Pre-fill form with existing data for display
$name = $phone['name'] ?? '';
$brand = $phone['brand'] ?? '';
$year = $phone['year'] ?? '';
$price = $phone['price'] ?? '';
$availability = $phone['availability'] ?? '';

// Determine device type based on existing data
// If form_factor or keyboard has values, it's likely a phone
$device_type = 'phone'; // Default to phone
if (empty($phone['form_factor']) && empty($phone['keyboard'])) {
    // If both form_factor and keyboard are empty, it might be a tablet
    // This is a simple heuristic - in a real app you'd want a proper device_type field
    $device_type = 'tablet';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    if (empty($name)) {
        $errors['name'] = 'Phone name is required';
    }

    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    if (empty($brand)) {
        $errors['brand'] = 'Brand is required';
    }

    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    if (empty($year) || !is_numeric($year) || $year < 2000 || $year > date('Y') + 2) {
        $errors['year'] = 'Please enter a valid year between 2000 and ' . (date('Y') + 2);
    }

    $availability = isset($_POST['availability']) ? trim($_POST['availability']) : '';
    if (empty($availability)) {
        $errors['availability'] = 'Availability status is required';
    }

    $price = isset($_POST['price']) ? trim($_POST['price']) : '';
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Please enter a valid price greater than 0';
    }

    // Handle multiple image uploads (up to 5)
    $image_paths = [];
    $existing_images = $phone['images'] ?? [];

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_images = 5;

        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        for ($i = 0; $i < min(count($_FILES['images']['name']), $max_images); $i++) {
            if (!empty($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['images']['type'][$i];
                $file_size = $_FILES['images']['size'][$i];

                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    $errors['image' . ($i + 1)] = 'Only JPG, PNG, and GIF images are allowed for image ' . ($i + 1);
                }

                // Validate file size
                if ($file_size > $max_size) {
                    $errors['image' . ($i + 1)] = 'Image ' . ($i + 1) . ' size should not exceed 5MB';
                }

                // If no errors, process the upload
                if (!isset($errors['image' . ($i + 1)])) {
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'device_' . time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $file_extension;
                    $upload_path = 'uploads/' . $filename;

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        $image_paths[] = $upload_path;
                    } else {
                        $errors['image' . ($i + 1)] = 'Failed to upload image ' . ($i + 1) . '. Please try again.';
                    }
                }
            }
        }
    }

    // Merge existing images with new uploads
    // If new images are uploaded, replace the corresponding existing ones
    $final_images = $existing_images;
    if (!empty($image_paths)) {
        // Replace existing images with new ones based on position
        for ($i = 0; $i < count($image_paths); $i++) {
            if ($i < count($final_images)) {
                // Replace existing image
                $final_images[$i] = $image_paths[$i];
            } else {
                // Add new image
                $final_images[] = $image_paths[$i];
            }
        }
    }

    // Set main image path (first image) for backward compatibility
    $image_path = !empty($final_images) ? $final_images[0] : ($phone['image'] ?? '');

    // If no errors, save the phone data
    if (empty($errors)) {
        // Check for exact duplicate device (same name and brand only) - exclude current device
        $existing_phones = getAllPhones();
        foreach ($existing_phones as $existing_phone) {
            if (
                isset($existing_phone['name']) && isset($existing_phone['brand']) &&
                $existing_phone['id'] != $id && // Exclude current device being edited
                strtolower(trim($existing_phone['name'])) === strtolower(trim($name)) &&
                strtolower(trim($existing_phone['brand'])) === strtolower(trim($brand))
            ) {
                $errors['general'] = 'A device with the exact name "' . htmlspecialchars($name) . '" by "' . htmlspecialchars($brand) . '" already exists. Please use a different model name. Note: The same brand can have multiple different devices.';
                break;
            }
        }
    }

    // If still no errors after validation, create the device
    if (empty($errors)) {
        // Get device type
        $device_type = $_POST['device_type'] ?? 'phone';

        $new_phone = [
            // Launch
            'release_date' => !empty($_POST['release_date']) ? $_POST['release_date'] : null,

            // General
            'name' => $name,
            'brand' => $brand,
            'year' => $year,
            'availability' => $availability,
            'price' => $price,
            'image' => $image_path,
            'images' => $final_images,

            // Network
            '2g' => $_POST['2g'] ?? [],
            '3g' => $_POST['3g'] ?? [],
            '4g' => $_POST['4g'] ?? [],
            '5g' => $_POST['5g'] ?? [],

            // SIM
            'dual_sim' => isset($_POST['dual_sim']),
            'esim' => isset($_POST['esim']),
            'sim_size' => $_POST['sim_size'] ?? [],

            // Body
            'dimensions' => $_POST['dimensions'] ?? '',
            'form_factor' => ($device_type === 'phone') ? ($_POST['form_factor'] ?? '') : '',
            'keyboard' => ($device_type === 'phone') ? ($_POST['keyboard'] ?? '') : '',
            'height' => $_POST['height'] ?? '',
            'width' => $_POST['width'] ?? '',
            'thickness' => $_POST['thickness'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'ip_certificate' => !empty($_POST['ip_certificate']) ? $_POST['ip_certificate'] : [],
            'color' => !empty($_POST['color']) ? trim($_POST['color']) : null,
            'back_material' => !empty($_POST['back_material']) ? trim($_POST['back_material']) : null,
            'frame_material' => !empty($_POST['frame_material']) ? trim($_POST['frame_material']) : null,

            // Platform
            'os' => $_POST['os'] ?? '',
            'os_version' => $_POST['os_version'] ?? '',
            'chipset' => $_POST['chipset'] ?? '',
            'cpu_cores' => $_POST['cpu_cores'] ?? '',

            // Memory
            'ram' => $_POST['ram'] ?? '',
            'storage' => $_POST['storage'] ?? '',
            'card_slot' => $_POST['card_slot'] ?? '',

            // Display
            'display_type' => $_POST['display_type'] ?? '',
            'display_resolution' => $_POST['display_resolution'] ?? '',
            'display_size' => $_POST['display_size'] ?? '',
            'display_density' => $_POST['display_density'] ?? '',
            'display_technology' => $_POST['display_technology'] ?? '',
            'display_notch' => $_POST['display_notch'] ?? '',
            'refresh_rate' => $_POST['refresh_rate'] ?? '',
            'hdr' => isset($_POST['hdr']),
            'billion_colors' => isset($_POST['billion_colors']),

            // Main Camera
            'main_camera_resolution' => $_POST['main_camera_resolution'] ?? '',
            'main_camera_count' => $_POST['main_camera_count'] ?? '',
            'main_camera_ois' => isset($_POST['main_camera_ois']),
            'main_camera_f_number' => $_POST['main_camera_f_number'] ?? '',
            'main_camera_telephoto' => isset($_POST['main_camera_telephoto']),
            'main_camera_ultrawide' => isset($_POST['main_camera_ultrawide']),
            'main_camera_video' => $_POST['main_camera_video'] ?? '',
            'main_camera_flash' => $_POST['main_camera_flash'] ?? '',

            // Selfie Camera
            'selfie_camera_resolution' => $_POST['selfie_camera_resolution'] ?? '',
            'selfie_camera_count' => $_POST['selfie_camera_count'] ?? '',
            'selfie_camera_ois' => isset($_POST['selfie_camera_ois']),
            'selfie_camera_flash' => isset($_POST['selfie_camera_flash']),
            'popup_camera' => isset($_POST['popup_camera']),
            'under_display_camera' => isset($_POST['under_display_camera']),

            // Audio
            'headphone_jack' => isset($_POST['headphone_jack']),
            'dual_speakers' => isset($_POST['dual_speakers']),

            // Sensors
            'accelerometer' => isset($_POST['accelerometer']),
            'gyro' => isset($_POST['gyro']),
            'compass' => isset($_POST['compass']),
            'proximity' => isset($_POST['proximity']),
            'barometer' => isset($_POST['barometer']),
            'heart_rate' => isset($_POST['heart_rate']),
            'fingerprint' => $_POST['fingerprint'] ?? '',

            // Connectivity
            'wifi' => $_POST['wifi'] ?? [],
            'bluetooth' => $_POST['bluetooth'] ?? [],
            'gps' => isset($_POST['gps']),
            'nfc' => isset($_POST['nfc']),
            'infrared' => isset($_POST['infrared']),
            'fm_radio' => isset($_POST['fm_radio']),
            'usb' => $_POST['usb'] ?? '',

            // Battery
            'battery_capacity' => $_POST['battery_capacity'] ?? '',
            'battery_sic' => isset($_POST['battery_sic']),
            'battery_removable' => isset($_POST['battery_removable']),
            'wired_charging' => $_POST['wired_charging'] ?? '',
            'wireless_charging' => $_POST['wireless_charging'] ?? ''
        ];

        $result = updatePhone($id, $new_phone);
        if ($result === true) {
            // Set success message and redirect to dashboard
            $_SESSION['success_message'] = 'Device updated successfully!';
            header('Location: dashboard.php');
            exit();
        } else {
            // System error - log the error for debugging
            error_log('Failed to update phone with ID: ' . $id . '. Result: ' . print_r($result, true));
            $errors['general'] = 'Failed to update device data. Please try again. Error details have been logged.';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Edit Device</h1>
            <p class="text-muted">Update the details of <?php echo htmlspecialchars($phone['name']); ?></p>
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
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <!-- Device Type Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Device Type *</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="device_type" id="device_type_phone" value="phone" <?php echo ($device_type === 'phone') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="device_type_phone">
                                    <i class="fas fa-mobile-alt me-2"></i>Phone
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="device_type" id="device_type_tablet" value="tablet" <?php echo ($device_type === 'tablet') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="device_type_tablet">
                                    <i class="fas fa-tablet-alt me-2"></i>Tablet
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

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
                                        <input type="date" class="form-control" id="release_date" name="release_date" value="<?php echo getValue('release_date'); ?>">
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
                                            id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                        <?php if (isset($errors['name'])): ?>
                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="brand" class="form-label">Brand *</label>
                                        <div class="input-group">
                                            <select class="form-select <?php echo isset($errors['brand']) ? 'is-invalid' : ''; ?>"
                                                id="brand" name="brand" required>
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
                                            value="<?php echo isset($year) ? htmlspecialchars($year) : date('Y'); ?>" required>
                                        <?php if (isset($errors['year'])): ?>
                                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['year']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="availability" class="form-label">Availability *</label>
                                        <select class="form-select <?php echo isset($errors['availability']) ? 'is-invalid' : ''; ?>"
                                            id="availability" name="availability" required>
                                            <option value="">Select availability...</option>
                                            <option value="Available" <?php echo isset($availability) && $availability === 'Available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="Coming Soon" <?php echo isset($availability) && $availability === 'Coming Soon' ? 'selected' : ''; ?>>Coming Soon</option>
                                            <option value="Discontinued" <?php echo isset($availability) && $availability === 'Discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                                            <option value="Rumored" <?php echo isset($availability) && $availability === 'Rumored' ? 'selected' : ''; ?>>Rumored</option>
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
                                                value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
                                            <?php if (isset($errors['price'])): ?>
                                                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Phone Images (up to 5)</label>

                                        <!-- Existing Images Preview -->
                                        <?php if (!empty($phone['images']) && is_array($phone['images'])): ?>
                                            <div class="mb-3">
                                                <h6>Current Images:</h6>
                                                <div class="row">
                                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                                        <div class="col-md-2 mb-2">
                                                            <?php if (isset($phone['images'][$i]) && !empty($phone['images'][$i])): ?>
                                                                <div class="position-relative">
                                                                    <img src="<?php echo htmlspecialchars($phone['images'][$i]); ?>"
                                                                        class="img-thumbnail"
                                                                        style="width: 100%; height: 120px; object-fit: cover;"
                                                                        alt="Current Image <?php echo $i + 1; ?>">
                                                                    <div class="position-absolute top-0 end-0">
                                                                        <span class="badge bg-primary"><?php echo $i + 1; ?></span>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="border border-dashed rounded p-2 text-center" style="height: 120px; display: flex; align-items: center; justify-content: center;">
                                                                    <small class="text-muted">No Image</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- New Image Upload Fields -->
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label for="image1" class="form-label">Image 1 (Main)</label>
                                                <input type="file" class="form-control <?php echo isset($errors['image1']) ? 'is-invalid' : ''; ?>"
                                                    id="image1" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                <?php if (isset($errors['image1'])): ?>
                                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['image1']); ?></div>
                                                <?php endif; ?>
                                                <div class="form-text">Upload to replace current image 1</div>
                                                <div id="preview-1" class="mt-2"></div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="image2" class="form-label">Image 2</label>
                                                <input type="file" class="form-control" id="image2" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                <div class="form-text">Upload to replace current image 2</div>
                                                <div id="preview-2" class="mt-2"></div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="image3" class="form-label">Image 3</label>
                                                <input type="file" class="form-control" id="image3" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                <div class="form-text">Upload to replace current image 3</div>
                                                <div id="preview-3" class="mt-2"></div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="image4" class="form-label">Image 4</label>
                                                <input type="file" class="form-control" id="image4" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                <div class="form-text">Upload to replace current image 4</div>
                                                <div id="preview-4" class="mt-2"></div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="image5" class="form-label">Image 5</label>
                                                <input type="file" class="form-control" id="image5" name="images[]" accept="image/jpeg, image/png, image/gif">
                                                <div class="form-text">Upload to replace current image 5</div>
                                                <div id="preview-5" class="mt-2"></div>
                                            </div>
                                        </div>
                                        <div class="form-text">Max size per image: 5MB. Formats: JPG, PNG, GIF. Only upload new images to replace existing ones. Leave empty to keep current images.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Network Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="networkHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#networkCollapse" aria-expanded="false" aria-controls="networkCollapse">
                                <i class="fas fa-signal me-2"></i> Network
                            </button>
                        </h2>
                        <div id="networkCollapse" class="accordion-collapse collapse" aria-labelledby="networkHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">2G</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="2g[]" value="GSM 850" id="2g_850" <?php echo isInArray('network_2g', 'GSM 850'); ?>>
                                            <label class="form-check-label" for="2g_850">GSM 850</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="2g[]" value="GSM 900" id="2g_900" <?php echo isInArray('network_2g', 'GSM 900'); ?>>
                                            <label class="form-check-label" for="2g_900">GSM 900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="2g[]" value="GSM 1800" id="2g_1800" <?php echo isInArray('network_2g', 'GSM 1800'); ?>>
                                            <label class="form-check-label" for="2g_1800">GSM 1800</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="2g[]" value="GSM 1900" id="2g_1900" <?php echo isInArray('network_2g', 'GSM 1900'); ?>>
                                            <label class="form-check-label" for="2g_1900">GSM 1900</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">3G</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="3g[]" value="HSPA 850" id="3g_850" <?php echo isInArray('network_3g', 'HSPA 850'); ?>>
                                            <label class="form-check-label" for="3g_850">HSPA 850</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="3g[]" value="HSPA 900" id="3g_900" <?php echo isInArray('network_3g', 'HSPA 900'); ?>>
                                            <label class="form-check-label" for="3g_900">HSPA 900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="3g[]" value="HSPA 1700" id="3g_1700" <?php echo isInArray('network_3g', 'HSPA 1700'); ?>>
                                            <label class="form-check-label" for="3g_1700">HSPA 1700</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="3g[]" value="HSPA 1900" id="3g_1900" <?php echo isInArray('network_3g', 'HSPA 1900'); ?>>
                                            <label class="form-check-label" for="3g_1900">HSPA 1900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="3g[]" value="HSPA 2100" id="3g_2100" <?php echo isInArray('network_3g', 'HSPA 2100'); ?>>
                                            <label class="form-check-label" for="3g_2100">HSPA 2100</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">4G</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="4g[]" value="LTE 700" id="4g_700" <?php echo isInArray('network_4g', 'LTE 700'); ?>>
                                            <label class="form-check-label" for="4g_700">LTE 700</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="4g[]" value="LTE 850" id="4g_850" <?php echo isInArray('network_4g', 'LTE 850'); ?>>
                                            <label class="form-check-label" for="4g_850">LTE 850</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="4g[]" value="LTE 900" id="4g_900" <?php echo isInArray('network_4g', 'LTE 900'); ?>>
                                            <label class="form-check-label" for="4g_900">LTE 900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="4g[]" value="LTE 1800" id="4g_1800" <?php echo isInArray('network_4g', 'LTE 1800'); ?>>
                                            <label class="form-check-label" for="4g_1800">LTE 1800</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">5G</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="5g[]" value="NR 3500" id="5g_3500" <?php echo isInArray('network_5g', 'NR 3500'); ?>>
                                            <label class="form-check-label" for="5g_3500">NR 3500</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="5g[]" value="NR 3600" id="5g_3600" <?php echo isInArray('network_5g', 'NR 3600'); ?>>
                                            <label class="form-check-label" for="5g_3600">NR 3600</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="5g[]" value="NR 3700" id="5g_3700" <?php echo isInArray('network_5g', 'NR 3700'); ?>>
                                            <label class="form-check-label" for="5g_3700">NR 3700</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="5g[]" value="NR 3800" id="5g_3800" <?php echo isInArray('network_5g', 'NR 3800'); ?>>
                                            <label class="form-check-label" for="5g_3800">NR 3800</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. SIM Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="simHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#simCollapse" aria-expanded="false" aria-controls="simCollapse">
                                <i class="fas fa-sim-card me-2"></i> SIM
                            </button>
                        </h2>
                        <div id="simCollapse" class="accordion-collapse collapse" aria-labelledby="simHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dual_sim" id="dual_sim" <?php echo isChecked('dual_sim'); ?>>
                                            <label class="form-check-label" for="dual_sim">Dual SIM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="esim" id="esim" <?php echo isChecked('esim'); ?>>
                                            <label class="form-check-label" for="esim">eSIM</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Size</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sim_size[]" value="Mini-SIM" id="sim_mini" <?php echo isInArray('sim_size', 'Mini-SIM'); ?>>
                                            <label class="form-check-label" for="sim_mini">Mini-SIM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sim_size[]" value="Nano-SIM" id="sim_nano" <?php echo isInArray('sim_size', 'Nano-SIM'); ?>>
                                            <label class="form-check-label" for="sim_nano">Nano-SIM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sim_size[]" value="Micro-SIM" id="sim_micro" <?php echo isInArray('sim_size', 'Micro-SIM'); ?>>
                                            <label class="form-check-label" for="sim_micro">Micro-SIM</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Body Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="bodyHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bodyCollapse" aria-expanded="false" aria-controls="bodyCollapse">
                                <i class="fas fa-mobile-alt me-2"></i> Body
                            </button>
                        </h2>
                        <div id="bodyCollapse" class="accordion-collapse collapse" aria-labelledby="bodyHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="dimensions" class="form-label">Dimensions</label>
                                        <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="e.g., 159.9 x 75.7 x 8.3 mm" value="<?php echo getValue('dimensions'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="form_factor" class="form-label">Form Factor</label>
                                        <select class="form-select" id="form_factor" name="form_factor">
                                            <option value="">Select form factor...</option>
                                            <option value="Bar" <?php echo isSelected('form_factor', 'Bar'); ?>>Bar</option>
                                            <option value="Flip Up" <?php echo isSelected('form_factor', 'Flip Up'); ?>>Flip Up</option>
                                            <option value="Flip Down" <?php echo isSelected('form_factor', 'Flip Down'); ?>>Flip Down</option>
                                            <option value="Swivel" <?php echo isSelected('form_factor', 'Swivel'); ?>>Swivel</option>
                                            <option value="Slide" <?php echo isSelected('form_factor', 'Slide'); ?>>Slide</option>
                                        </select>
                                        <div class="form-text text-muted">Not applicable for tablets</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="keyboard" class="form-label">Keyboard</label>
                                        <select class="form-select" id="keyboard" name="keyboard">
                                            <option value="">Select keyboard type...</option>
                                            <option value="Without QWERTY" <?php echo isSelected('keyboard', 'Without QWERTY'); ?>>Without QWERTY</option>
                                            <option value="With QWERTY" <?php echo isSelected('keyboard', 'With QWERTY'); ?>>With QWERTY</option>
                                        </select>
                                        <div class="form-text text-muted">Not applicable for tablets</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="height" class="form-label">Height (mm)</label>
                                        <input type="number" step="0.1" class="form-control" id="height" name="height" value="<?php echo getValue('height'); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="width" class="form-label">Width (mm)</label>
                                        <input type="number" step="0.1" class="form-control" id="width" name="width" value="<?php echo getValue('width'); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="thickness" class="form-label">Thickness (mm)</label>
                                        <input type="number" step="0.1" class="form-control" id="thickness" name="thickness" value="<?php echo getValue('thickness'); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="weight" class="form-label">Weight (g)</label>
                                        <input type="number" step="0.1" class="form-control" id="weight" name="weight" value="<?php echo getValue('weight'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">IP Certificate</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IP5x" id="ip5x" <?php echo isInArray('ip_certificate', 'IP5x'); ?>>
                                                <label class="form-check-label" for="ip5x">IP5x</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IP6x" id="ip6x" <?php echo isInArray('ip_certificate', 'IP6x'); ?>>
                                                <label class="form-check-label" for="ip6x">IP6x</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IPx5" id="ipx5" <?php echo isInArray('ip_certificate', 'IPx5'); ?>>
                                                <label class="form-check-label" for="ipx5">IPx5</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IPx6" id="ipx6" <?php echo isInArray('ip_certificate', 'IPx6'); ?>>
                                                <label class="form-check-label" for="ipx6">IPx6</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IPx7" id="ipx7" <?php echo isInArray('ip_certificate', 'IPx7'); ?>>
                                                <label class="form-check-label" for="ipx7">IPx7</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ip_certificate[]" value="IPx8" id="ipx8" <?php echo isInArray('ip_certificate', 'IPx8'); ?>>
                                                <label class="form-check-label" for="ipx8">IPx8</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="color" class="form-label">Color</label>
                                        <input type="text" class="form-control" id="color" name="color" placeholder="e.g., Midnight Black" value="<?php echo getValue('color'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="back_material" class="form-label">Back Material</label>
                                        <select class="form-select" id="back_material" name="back_material">
                                            <option value="">Select material...</option>
                                            <option value="Plastic" <?php echo isSelected('back_material', 'Plastic'); ?>>Plastic</option>
                                            <option value="Aluminum" <?php echo isSelected('back_material', 'Aluminum'); ?>>Aluminum</option>
                                            <option value="Glass" <?php echo isSelected('back_material', 'Glass'); ?>>Glass</option>
                                            <option value="Ceramic" <?php echo isSelected('back_material', 'Ceramic'); ?>>Ceramic</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="frame_material" class="form-label">Frame Material</label>
                                        <select class="form-select" id="frame_material" name="frame_material">
                                            <option value="">Select material...</option>
                                            <option value="Plastic" <?php echo isSelected('frame_material', 'Plastic'); ?>>Plastic</option>
                                            <option value="Aluminum" <?php echo isSelected('frame_material', 'Aluminum'); ?>>Aluminum</option>
                                            <option value="Stainless Steel" <?php echo isSelected('frame_material', 'Stainless Steel'); ?>>Stainless Steel</option>
                                            <option value="Ceramic" <?php echo isSelected('frame_material', 'Ceramic'); ?>>Ceramic</option>
                                            <option value="Titanium" <?php echo isSelected('frame_material', 'Titanium'); ?>>Titanium</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Platform Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="platformHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#platformCollapse" aria-expanded="false" aria-controls="platformCollapse">
                                <i class="fas fa-microchip me-2"></i> Platform
                            </button>
                        </h2>
                        <div id="platformCollapse" class="accordion-collapse collapse" aria-labelledby="platformHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="os" class="form-label">OS</label>
                                        <select class="form-select" id="os" name="os">
                                            <option value="">Select OS...</option>
                                            <option value="Feature phones" <?php echo isSelected('os', 'Feature phones'); ?>>Feature phones</option>
                                            <option value="Android" <?php echo isSelected('os', 'Android'); ?>>Android</option>
                                            <option value="iOS" <?php echo isSelected('os', 'iOS'); ?>>iOS</option>
                                            <option value="KaiOS" <?php echo isSelected('os', 'KaiOS'); ?>>KaiOS</option>
                                            <option value="Windows Phone" <?php echo isSelected('os', 'Windows Phone'); ?>>Windows Phone</option>
                                            <option value="Symbian" <?php echo isSelected('os', 'Symbian'); ?>>Symbian</option>
                                            <option value="RIM" <?php echo isSelected('os', 'RIM'); ?>>RIM</option>
                                            <option value="Bada" <?php echo isSelected('os', 'Bada'); ?>>Bada</option>
                                            <option value="Firefox" <?php echo isSelected('os', 'Firefox'); ?>>Firefox</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="os_version" class="form-label">OS Version</label>
                                        <input type="text" class="form-control" id="os_version" name="os_version" placeholder="e.g., Android 14" value="<?php echo getValue('os_version'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="chipset" class="form-label">Chipset</label>
                                        <div class="input-group">
                                            <select class="form-select" id="chipset" name="chipset">
                                                <option value="">Select chipset...</option>
                                                <?php
                                                $chipsets = getAllChipsets();
                                                if (!empty($chipsets)) {
                                                    foreach ($chipsets as $chipsetItem) {
                                                        $selected = isSelected('chipset', $chipsetItem['name']);
                                                        echo '<option value="' . htmlspecialchars($chipsetItem['name']) . '" ' . $selected . '>' .
                                                            htmlspecialchars($chipsetItem['name']) . '</option>';
                                                    }
                                                }
                                                ?>
                                                <option value="other">Other (Custom)</option>
                                            </select>
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="window.location.href='manage_data.php';"
                                                <?php echo (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') ? 'disabled' : ''; ?>
                                                title="<?php echo (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') ? 'Only admin can manage chipsets' : 'Manage Chipsets'; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div id="custom-chipset-container" class="mt-2 d-none">
                                            <input type="text" class="form-control" id="custom-chipset"
                                                placeholder="Enter custom chipset name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cpu_cores" class="form-label">CPU Cores</label>
                                        <input type="number" class="form-control" id="cpu_cores" name="cpu_cores" min="1" max="16" value="<?php echo getValue('cpu_cores'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Memory Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="memoryHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#memoryCollapse" aria-expanded="false" aria-controls="memoryCollapse">
                                <i class="fas fa-memory me-2"></i> Memory
                            </button>
                        </h2>
                        <div id="memoryCollapse" class="accordion-collapse collapse" aria-labelledby="memoryHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="ram" class="form-label">RAM (GB)</label>
                                        <input type="number" step="0.5" class="form-control" id="ram" name="ram" min="0.5" max="64" value="<?php echo getValue('ram'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="storage" class="form-label">Storage (GB)</label>
                                        <input type="number" class="form-control" id="storage" name="storage" min="1" max="2048" value="<?php echo getValue('storage'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="card_slot" class="form-label">Card Slot</label>
                                        <select class="form-select" id="card_slot" name="card_slot">
                                            <option value="">Select option...</option>
                                            <option value="Yes (any type)" <?php echo isSelected('card_slot', 'Yes (any type)'); ?>>Yes (any type)</option>
                                            <option value="Yes (dedicated)" <?php echo isSelected('card_slot', 'Yes (dedicated)'); ?>>Yes (dedicated)</option>
                                            <option value="No" <?php echo isSelected('card_slot', 'No'); ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Display Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="displayHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#displayCollapse" aria-expanded="false" aria-controls="displayCollapse">
                                <i class="fas fa-tv me-2"></i> Display
                            </button>
                        </h2>
                        <div id="displayCollapse" class="accordion-collapse collapse" aria-labelledby="displayHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="display_type" class="form-label">Type</label>
                                        <select class="form-select" id="display_type" name="display_type">
                                            <option value="">Select type...</option>
                                            <option value="AMOLED" <?php echo isSelected('display_type', 'AMOLED'); ?>>AMOLED</option>
                                            <option value="Super AMOLED" <?php echo isSelected('display_type', 'Super AMOLED'); ?>>Super AMOLED</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="display_resolution" class="form-label">Resolution</label>
                                        <input type="text" class="form-control" id="display_resolution" name="display_resolution" placeholder="e.g., 1080 x 2400 pixels" value="<?php echo getValue('display_resolution'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="display_size" class="form-label">Size (inches)</label>
                                        <input type="number" step="0.1" class="form-control" id="display_size" name="display_size" min="2" max="15" value="<?php echo getValue('display_size'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="display_density" class="form-label">Density (ppi)</label>
                                        <input type="number" class="form-control" id="display_density" name="display_density" min="50" max="1000" value="<?php echo getValue('display_density'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="display_technology" class="form-label">Technology</label>
                                        <select class="form-select" id="display_technology" name="display_technology">
                                            <option value="">Select technology...</option>
                                            <option value="IPS" <?php echo isSelected('display_technology', 'IPS'); ?>>IPS</option>
                                            <option value="Any OLED" <?php echo isSelected('display_technology', 'Any OLED'); ?>>Any OLED</option>
                                            <option value="LTPO OLED" <?php echo isSelected('display_technology', 'LTPO OLED'); ?>>LTPO OLED</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="display_notch" class="form-label">Notch</label>
                                        <select class="form-select" id="display_notch" name="display_notch">
                                            <option value="">Select option...</option>
                                            <option value="No" <?php echo isSelected('display_notch', 'No'); ?>>No</option>
                                            <option value="Yes" <?php echo isSelected('display_notch', 'Yes'); ?>>Yes</option>
                                            <option value="Punch Hole" <?php echo isSelected('display_notch', 'Punch Hole'); ?>>Punch Hole</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="refresh_rate" class="form-label">Refresh Rate</label>
                                        <select class="form-select" id="refresh_rate" name="refresh_rate">
                                            <option value="">Select rate...</option>
                                            <option value="90Hz" <?php echo isSelected('refresh_rate', '90Hz'); ?>>90Hz</option>
                                            <option value="120Hz" <?php echo isSelected('refresh_rate', '120Hz'); ?>>120Hz</option>
                                            <option value="144Hz" <?php echo isSelected('refresh_rate', '144Hz'); ?>>144Hz</option>
                                            <option value="165Hz" <?php echo isSelected('refresh_rate', '165Hz'); ?>>165Hz</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="hdr" id="hdr" <?php echo isChecked('hdr'); ?>>
                                            <label class="form-check-label" for="hdr">HDR</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="billion_colors" id="billion_colors" <?php echo isChecked('billion_colors'); ?>>
                                            <label class="form-check-label" for="billion_colors">1B+ Colors</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 9. Main Camera Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="mainCameraHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mainCameraCollapse" aria-expanded="false" aria-controls="mainCameraCollapse">
                                <i class="fas fa-camera me-2"></i> Main Camera
                            </button>
                        </h2>
                        <div id="mainCameraCollapse" class="accordion-collapse collapse" aria-labelledby="mainCameraHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="main_camera_resolution" class="form-label">Resolution (MP)</label>
                                        <input type="number" class="form-control" id="main_camera_resolution" name="main_camera_resolution" min="0.1" max="200" step="0.1" value="<?php echo getValue('main_camera_resolution'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="main_camera_count" class="form-label">Cameras</label>
                                        <select class="form-select" id="main_camera_count" name="main_camera_count">
                                            <option value="">Select count...</option>
                                            <option value="One" <?php echo isSelected('main_camera_count', 'One'); ?>>One</option>
                                            <option value="Two" <?php echo isSelected('main_camera_count', 'Two'); ?>>Two</option>
                                            <option value="Three" <?php echo isSelected('main_camera_count', 'Three'); ?>>Three</option>
                                            <option value="Four or More" <?php echo isSelected('main_camera_count', 'Four or More'); ?>>Four or More</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="main_camera_f_number" class="form-label">F-Number</label>
                                        <input type="number" step="0.1" class="form-control" id="main_camera_f_number" name="main_camera_f_number" min="1" max="10" placeholder="e.g., 1.8" value="<?php echo getValue('main_camera_f_number'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="main_camera_video" class="form-label">Video</label>
                                        <input type="text" class="form-control" id="main_camera_video" name="main_camera_video" placeholder="e.g., 4K@30fps" value="<?php echo getValue('main_camera_video'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="main_camera_flash" class="form-label">Flash</label>
                                        <select class="form-select" id="main_camera_flash" name="main_camera_flash">
                                            <option value="">Select flash...</option>
                                            <option value="LED" <?php echo isSelected('main_camera_flash', 'LED'); ?>>LED</option>
                                            <option value="Dual-LED" <?php echo isSelected('main_camera_flash', 'Dual-LED'); ?>>Dual-LED</option>
                                            <option value="Xenon" <?php echo isSelected('main_camera_flash', 'Xenon'); ?>>Xenon</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_camera_ois" id="main_camera_ois" <?php echo isChecked('main_camera_ois'); ?>>
                                            <label class="form-check-label" for="main_camera_ois">OIS</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="main_camera_telephoto" id="main_camera_telephoto" <?php echo isChecked('main_camera_telephoto'); ?>>
                                            <label class="form-check-label" for="main_camera_telephoto">Telephoto</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="main_camera_ultrawide" id="main_camera_ultrawide" <?php echo isChecked('main_camera_ultrawide'); ?>>
                                            <label class="form-check-label" for="main_camera_ultrawide">Ultrawide</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 10. Selfie Camera Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="selfieCameraHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#selfieCameraCollapse" aria-expanded="false" aria-controls="selfieCameraCollapse">
                                <i class="fas fa-camera-retro me-2"></i> Selfie Camera
                            </button>
                        </h2>
                        <div id="selfieCameraCollapse" class="accordion-collapse collapse" aria-labelledby="selfieCameraHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="selfie_camera_resolution" class="form-label">Resolution (MP)</label>
                                        <input type="number" class="form-control" id="selfie_camera_resolution" name="selfie_camera_resolution" min="0.1" max="100" step="0.1" value="<?php echo getValue('selfie_camera_resolution'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="selfie_camera_count" class="form-label">Cameras</label>
                                        <select class="form-select" id="selfie_camera_count" name="selfie_camera_count">
                                            <option value="">Select count...</option>
                                            <option value="One" <?php echo isSelected('selfie_camera_count', 'One'); ?>>One</option>
                                            <option value="Two" <?php echo isSelected('selfie_camera_count', 'Two'); ?>>Two</option>
                                            <option value="Three" <?php echo isSelected('selfie_camera_count', 'Three'); ?>>Three</option>
                                            <option value="Four or More" <?php echo isSelected('selfie_camera_count', 'Four or More'); ?>>Four or More</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="selfie_camera_ois" id="selfie_camera_ois" <?php echo isChecked('selfie_camera_ois'); ?>>
                                            <label class="form-check-label" for="selfie_camera_ois">OIS</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="selfie_camera_flash" id="selfie_camera_flash" <?php echo isChecked('selfie_camera_flash'); ?>>
                                            <label class="form-check-label" for="selfie_camera_flash">Front Flash</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="popup_camera" id="popup_camera" <?php echo isChecked('popup_camera'); ?>>
                                            <label class="form-check-label" for="popup_camera">Pop-up Camera</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="under_display_camera" id="under_display_camera" <?php echo isChecked('under_display_camera'); ?>>
                                            <label class="form-check-label" for="under_display_camera">Under Display Camera</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 11. Audio Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="audioHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#audioCollapse" aria-expanded="false" aria-controls="audioCollapse">
                                <i class="fas fa-volume-up me-2"></i> Audio
                            </button>
                        </h2>
                        <div id="audioCollapse" class="accordion-collapse collapse" aria-labelledby="audioHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="headphone_jack" id="headphone_jack" <?php echo isChecked('headphone_jack'); ?>>
                                            <label class="form-check-label" for="headphone_jack">3.5mm Jack</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dual_speakers" id="dual_speakers" <?php echo isChecked('dual_speakers'); ?>>
                                            <label class="form-check-label" for="dual_speakers">Dual Speakers</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 12. Sensors Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="sensorsHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sensorsCollapse" aria-expanded="false" aria-controls="sensorsCollapse">
                                <i class="fas fa-satellite-dish me-2"></i> Sensors
                            </button>
                        </h2>
                        <div id="sensorsCollapse" class="accordion-collapse collapse" aria-labelledby="sensorsHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="accelerometer" id="accelerometer" <?php echo isChecked('accelerometer'); ?>>
                                            <label class="form-check-label" for="accelerometer">Accelerometer</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="gyro" id="gyro" <?php echo isChecked('gyro'); ?>>
                                            <label class="form-check-label" for="gyro">Gyro</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="compass" id="compass" <?php echo isChecked('compass'); ?>>
                                            <label class="form-check-label" for="compass">Compass</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="proximity" id="proximity" <?php echo isChecked('proximity'); ?>>
                                            <label class="form-check-label" for="proximity">Proximity</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="barometer" id="barometer" <?php echo isChecked('barometer'); ?>>
                                            <label class="form-check-label" for="barometer">Barometer</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="heart_rate" id="heart_rate" <?php echo isChecked('heart_rate'); ?>>
                                            <label class="form-check-label" for="heart_rate">Heart Rate</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="fingerprint" class="form-label">Fingerprint</label>
                                        <select class="form-select" id="fingerprint" name="fingerprint">
                                            <option value="">Select option...</option>
                                            <option value="Yes (any type)" <?php echo isSelected('fingerprint', 'Yes (any type)'); ?>>Yes (any type)</option>
                                            <option value="Front-mounted" <?php echo isSelected('fingerprint', 'Front-mounted'); ?>>Front-mounted</option>
                                            <option value="Rear-mounted" <?php echo isSelected('fingerprint', 'Rear-mounted'); ?>>Rear-mounted</option>
                                            <option value="Side-mounted" <?php echo isSelected('fingerprint', 'Side-mounted'); ?>>Side-mounted</option>
                                            <option value="Top-mounted" <?php echo isSelected('fingerprint', 'Top-mounted'); ?>>Top-mounted</option>
                                            <option value="Under Display" <?php echo isSelected('fingerprint', 'Under Display'); ?>>Under Display</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 13. Connectivity Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="connectivityHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#connectivityCollapse" aria-expanded="false" aria-controls="connectivityCollapse">
                                <i class="fas fa-wifi me-2"></i> Connectivity
                            </button>
                        </h2>
                        <div id="connectivityCollapse" class="accordion-collapse collapse" aria-labelledby="connectivityHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">WLAN (Wi-Fi)</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wifi[]" value="Wi-Fi 4 (802.11n)" id="wifi4" <?php echo isInArray('wifi', 'Wi-Fi 4 (802.11n)'); ?>>
                                            <label class="form-check-label" for="wifi4">Wi-Fi 4 (802.11n)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wifi[]" value="Wi-Fi 5 (802.11ac)" id="wifi5" <?php echo isInArray('wifi', 'Wi-Fi 5 (802.11ac)'); ?>>
                                            <label class="form-check-label" for="wifi5">Wi-Fi 5 (802.11ac)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wifi[]" value="Wi-Fi 6 (802.11ax)" id="wifi6" <?php echo isInArray('wifi', 'Wi-Fi 6 (802.11ax)'); ?>>
                                            <label class="form-check-label" for="wifi6">Wi-Fi 6 (802.11ax)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wifi[]" value="Wi-Fi 7 (802.11be)" id="wifi7" <?php echo isInArray('wifi', 'Wi-Fi 7 (802.11be)'); ?>>
                                            <label class="form-check-label" for="wifi7">Wi-Fi 7 (802.11be)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bluetooth</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 4.0" id="bt40" <?php echo isInArray('bluetooth', 'Bluetooth 4.0'); ?>>
                                            <label class="form-check-label" for="bt40">Bluetooth 4.0</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 4.1" id="bt41" <?php echo isInArray('bluetooth', 'Bluetooth 4.1'); ?>>
                                            <label class="form-check-label" for="bt41">Bluetooth 4.1</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 4.2" id="bt42" <?php echo isInArray('bluetooth', 'Bluetooth 4.2'); ?>>
                                            <label class="form-check-label" for="bt42">Bluetooth 4.2</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 5.0" id="bt50" <?php echo isInArray('bluetooth', 'Bluetooth 5.0'); ?>>
                                            <label class="form-check-label" for="bt50">Bluetooth 5.0</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 5.1" id="bt51" <?php echo isInArray('bluetooth', 'Bluetooth 5.1'); ?>>
                                            <label class="form-check-label" for="bt51">Bluetooth 5.1</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 5.2" id="bt52" <?php echo isInArray('bluetooth', 'Bluetooth 5.2'); ?>>
                                            <label class="form-check-label" for="bt52">Bluetooth 5.2</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 5.3" id="bt53" <?php echo isInArray('bluetooth', 'Bluetooth 5.3'); ?>>
                                            <label class="form-check-label" for="bt53">Bluetooth 5.3</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 5.4" id="bt54" <?php echo isInArray('bluetooth', 'Bluetooth 5.4'); ?>>
                                            <label class="form-check-label" for="bt54">Bluetooth 5.4</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bluetooth[]" value="Bluetooth 6.0" id="bt60" <?php echo isInArray('bluetooth', 'Bluetooth 6.0'); ?>>
                                            <label class="form-check-label" for="bt60">Bluetooth 6.0</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="gps" id="gps" <?php echo isChecked('gps'); ?>>
                                            <label class="form-check-label" for="gps">GPS</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="nfc" id="nfc" <?php echo isChecked('nfc'); ?>>
                                            <label class="form-check-label" for="nfc">NFC</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="infrared" id="infrared" <?php echo isChecked('infrared'); ?>>
                                            <label class="form-check-label" for="infrared">Infrared</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="fm_radio" id="fm_radio" <?php echo isChecked('fm_radio'); ?>>
                                            <label class="form-check-label" for="fm_radio">FM Radio</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="usb" class="form-label">USB</label>
                                        <select class="form-select" id="usb" name="usb">
                                            <option value="">Select USB type...</option>
                                            <option value="USB-C" <?php echo isSelected('usb', 'USB-C'); ?>>USB-C</option>
                                            <option value="USB-C 3.0 or Higher" <?php echo isSelected('usb', 'USB-C 3.0 or Higher'); ?>>USB-C 3.0 or Higher</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 14. Battery Section -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="batteryHeader">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#batteryCollapse" aria-expanded="false" aria-controls="batteryCollapse">
                                <i class="fas fa-battery-three-quarters me-2"></i> Battery
                            </button>
                        </h2>
                        <div id="batteryCollapse" class="accordion-collapse collapse" aria-labelledby="batteryHeader">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="battery_capacity" class="form-label">Capacity (mAh)</label>
                                        <input type="number" class="form-control" id="battery_capacity" name="battery_capacity" min="500" max="10000" value="<?php echo getValue('battery_capacity'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="battery_sic" id="battery_sic" <?php echo isChecked('battery_sic'); ?>>
                                            <label class="form-check-label" for="battery_sic">SI/C</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="battery_removable" id="battery_removable" <?php echo isChecked('battery_removable'); ?>>
                                            <label class="form-check-label" for="battery_removable">Removable</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="wired_charging" class="form-label">Wired Charging (W)</label>
                                        <input type="number" class="form-control" id="wired_charging" name="wired_charging" min="0" max="300" value="<?php echo getValue('wired_charging'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="wireless_charging" class="form-label">Wireless Charging (W)</label>
                                        <input type="number" class="form-control" id="wireless_charging" name="wireless_charging" min="0" max="100" value="<?php echo getValue('wireless_charging'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="dashboard.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Device</button>
                </div>
            </form>
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

        // Handle device type selection
        const deviceTypePhone = document.getElementById('device_type_phone');
        const deviceTypeTablet = document.getElementById('device_type_tablet');
        const formFactorField = document.getElementById('form_factor');
        const keyboardField = document.getElementById('keyboard');

        function togglePhoneSpecificFields() {
            if (deviceTypeTablet && deviceTypeTablet.checked) {
                // Tablet selected - disable phone-specific fields
                if (formFactorField) {
                    formFactorField.disabled = true;
                    formFactorField.value = '';
                }
                if (keyboardField) {
                    keyboardField.disabled = true;
                    keyboardField.value = '';
                }
            } else {
                // Phone selected - enable phone-specific fields
                if (formFactorField) {
                    formFactorField.disabled = false;
                }
                if (keyboardField) {
                    keyboardField.disabled = false;
                }
            }
        }

        // Add event listeners for device type radio buttons
        if (deviceTypePhone) {
            deviceTypePhone.addEventListener('change', togglePhoneSpecificFields);
        }
        if (deviceTypeTablet) {
            deviceTypeTablet.addEventListener('change', togglePhoneSpecificFields);
        }

        // Initialize on page load
        togglePhoneSpecificFields();

        // Image preview functionality
        const imageInputs = document.querySelectorAll('input[type="file"][name="images[]"]');
        imageInputs.forEach((input, index) => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Find the corresponding preview container
                        const previewContainer = document.getElementById(`preview-${index + 1}`);
                        if (previewContainer) {
                            previewContainer.innerHTML = `
                                <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover;" alt="New Image ${index + 1}">
                                <div class="position-absolute top-0 end-0">
                                    <span class="badge bg-success">New</span>
                                </div>
                            `;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    });
</script>

<!-- Form validation script -->
<script src="js/form-validation.js"></script>

<?php include 'includes/footer.php'; ?>