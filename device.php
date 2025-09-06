<?php
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once 'database_functions.php';
$pdo = getConnection();

// Get device ID from URL
$device_id = $_GET['id'] ?? '';

if (!isset($_GET['id']) || $_GET['id'] === '') {
    header("Location: index.php");
    exit();
}

// Function to get device details
function getDeviceDetails($pdo, $device_id)
{
    // Try JSON files first (primary data source for now)
    $phones_json = 'data/phones.json';
    if (file_exists($phones_json)) {
        $phones_data = json_decode(file_get_contents($phones_json), true);

        // JSON stores as array, so search by index
        if (is_array($phones_data)) {
            // Use numeric index as device ID
            // Convert string ID to integer for array access
            $numeric_id = is_numeric($device_id) ? (int)$device_id : $device_id;
            if (isset($phones_data[$numeric_id])) {
                $device = $phones_data[$numeric_id];

                // Add computed fields for compatibility
                $device['id'] = $device_id;
                $device['image_1'] = $device['image'] ?? '';

                // Fix image paths
                if (isset($device['image'])) {
                    $device['image_1'] = str_replace('\\', '/', $device['image']);
                }

                // Handle multiple images
                if (!empty($device['images'])) {
                    for ($i = 0; $i < count($device['images']) && $i < 5; $i++) {
                        $device['image_' . ($i + 1)] = str_replace('\\', '/', $device['images'][$i]);
                    }
                }

                return $device;
            }
        }
    }

    // Fallback to database if JSON fails
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        return $device;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// Function to get device comments
function getDeviceComments($pdo, $device_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM device_comments 
            WHERE device_id = ? AND status = 'approved'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to track view
function trackDeviceView($pdo, $device_id, $ip_address)
{
    try {
        $today = date('Y-m-d');

        // Check if this IP already viewed this device today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM content_views 
            WHERE content_id = ? AND content_type = 'device' AND ip_address = ? AND DATE(viewed_at) = ?
        ");
        $stmt->execute([$device_id, $ip_address, $today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            // Insert new view record
            $stmt = $pdo->prepare("
                INSERT INTO content_views (content_id, content_type, ip_address, viewed_at) 
                VALUES (?, 'device', ?, NOW())
            ");
            $stmt->execute([$device_id, $ip_address]);
        }
    } catch (PDOException $e) {
        error_log("View tracking error: " . $e->getMessage());
    }
}

// Get device details
$device = getDeviceDetails($pdo, $device_id);

if (!$device) {
    header("Location: 404.php");
    exit();
}

// Track view
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
trackDeviceView($pdo, $device_id, $ip_address);

// Get comments
$comments = getDeviceComments($pdo, $device_id);

// Handle comment submission
if ($_POST && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if (!empty($name) && !empty($comment)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO device_comments (device_id, name, email, comment, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$device_id, $name, $email, $comment]);

            $success_message = "Thank you! Your comment has been submitted and is awaiting approval.";
        } catch (PDOException $e) {
            $error_message = "Error submitting comment. Please try again.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($device['name'] ?? 'Device Details'); ?> - Specifications</title>
    <meta name="description" content="Detailed specifications and features of <?php echo htmlspecialchars($device['name'] ?? ''); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .device-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }

        .device-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .spec-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .spec-row {
            border-bottom: 1px solid #f0f0f0;
            padding: 0.75rem 0;
        }

        .spec-row:last-child {
            border-bottom: none;
        }

        .spec-label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
        }

        .spec-value {
            color: #333;
        }

        .comment-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 3rem;
        }

        .comment-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .breadcrumb-nav {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            margin-bottom: 2rem;
        }

        .breadcrumb-nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .breadcrumb-nav a:hover {
            color: white;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="device-header">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb-nav">
                <a href="index.php"><i class="fas fa-home me-2"></i>Home</a>
                <span class="mx-2 text-white-50">/</span>
                <span class="text-white"><?php echo htmlspecialchars($device['name'] ?? 'Device'); ?></span>
            </nav>

            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-3"><?php echo htmlspecialchars($device['name'] ?? 'Device Details'); ?></h1>
                    <p class="lead mb-4">
                        <?php if (!empty($device['brand'])): ?>
                            <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($device['brand']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($device['category'])): ?>
                            <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($device['category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($device['launch_date'])): ?>
                            <span class="badge bg-light text-dark">Released: <?php echo date('M Y', strtotime($device['launch_date'])); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <?php if (!empty($device['image_1'])): ?>
                        <img src="<?php echo htmlspecialchars($device['image_1']); ?>"
                            alt="<?php echo htmlspecialchars($device['name'] ?? ''); ?>"
                            class="device-image">
                    <?php else: ?>
                        <div class="device-image bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                            <i class="fas fa-mobile-alt fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="row mt-3 g-2">
                        <div class="col-12">
                            <a href="#comments" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-comments me-2"></i>
                                Review/Comments
                                <span class="badge bg-light text-success ms-2"><?php echo count($comments); ?></span>
                            </a>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-info w-100 mb-2" onclick="showAllImages()">
                                <i class="fas fa-images me-2"></i>Pictures
                            </button>
                        </div>
                        <div class="col-12">
                            <a href="compare_phones.php?device1=<?php echo urlencode($device['name'] ?? ''); ?>&brand1=<?php echo urlencode($device['brand'] ?? ''); ?>" class="btn btn-warning w-100">
                                <i class="fas fa-balance-scale me-2"></i>Compare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Device Images Gallery -->
        <?php if (!empty($device['image_2']) || !empty($device['image_3']) || !empty($device['image_4']) || !empty($device['image_5'])): ?>
            <div class="spec-section">
                <h3 class="mb-3"><i class="fas fa-images me-2 text-primary"></i>Gallery</h3>
                <div class="row">
                    <?php
                    for ($i = 2; $i <= 5; $i++) {
                        $image_key = "image_$i";
                        if (!empty($device[$image_key])):
                    ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <img src="<?php echo htmlspecialchars($device[$image_key]); ?>"
                                    alt="<?php echo htmlspecialchars($device['name'] ?? ''); ?> - Image <?php echo $i; ?>"
                                    class="img-fluid rounded shadow-sm"
                                    style="cursor: pointer;"
                                    onclick="openImageModal(this.src)">
                            </div>
                    <?php
                        endif;
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Device Specifications -->
        <?php
        $spec_sections = [
            'General Information' => [
                'name' => 'Name',
                'brand' => 'Brand',
                'category' => 'Category',
                'launch_date' => 'Launch Date',
                'price' => 'Price',
                'availability' => 'Availability'
            ],
            'Network & Connectivity' => [
                'network_2g' => '2G Network',
                'network_3g' => '3G Network',
                'network_4g' => '4G Network',
                'network_5g' => '5G Network',
                'sim_type' => 'SIM Type',
                'wifi' => 'Wi-Fi',
                'bluetooth' => 'Bluetooth',
                'gps' => 'GPS'
            ],
            'Design & Display' => [
                'dimensions' => 'Dimensions',
                'weight' => 'Weight',
                'build_materials' => 'Build',
                'display_type' => 'Display Type',
                'display_size' => 'Display Size',
                'resolution' => 'Resolution',
                'pixel_density' => 'Pixel Density',
                'refresh_rate' => 'Refresh Rate'
            ],
            'Performance' => [
                'chipset' => 'Chipset',
                'cpu' => 'CPU',
                'gpu' => 'GPU',
                'ram' => 'RAM',
                'internal_storage' => 'Internal Storage',
                'expandable_storage' => 'Expandable Storage'
            ],
            'Camera System' => [
                'main_camera' => 'Main Camera',
                'main_camera_features' => 'Main Camera Features',
                'main_camera_video' => 'Video Recording',
                'selfie_camera' => 'Selfie Camera',
                'selfie_camera_features' => 'Selfie Features',
                'selfie_camera_video' => 'Selfie Video'
            ],
            'Battery & Charging' => [
                'battery_capacity' => 'Battery Capacity',
                'battery_type' => 'Battery Type',
                'charging_wired' => 'Wired Charging',
                'charging_wireless' => 'Wireless Charging',
                'charging_reverse' => 'Reverse Charging'
            ],
            'Additional Features' => [
                'operating_system' => 'Operating System',
                'sensors' => 'Sensors',
                'audio_features' => 'Audio',
                'special_features' => 'Special Features',
                'colors' => 'Available Colors'
            ]
        ];

        foreach ($spec_sections as $section_title => $specs):
            $has_content = false;
            foreach ($specs as $key => $label) {
                if (!empty($device[$key])) {
                    $has_content = true;
                    break;
                }
            }

            if ($has_content):
        ?>
                <div class="spec-section">
                    <h3 class="mb-3 text-primary"><?php echo $section_title; ?></h3>
                    <?php foreach ($specs as $key => $label): ?>
                        <?php if (!empty($device[$key])): ?>
                            <div class="spec-row row">
                                <div class="col-sm-4 spec-label"><?php echo $label; ?>:</div>
                                <div class="col-sm-8 spec-value">
                                    <?php
                                    $value = $device[$key];
                                    if ($key === 'launch_date') {
                                        $value = date('F j, Y', strtotime($value));
                                    } elseif ($key === 'price') {
                                        $value = '$' . number_format($value);
                                    } elseif (is_array($value)) {
                                        $value = implode(', ', $value);
                                    }
                                    echo htmlspecialchars($value);
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
        <?php
            endif;
        endforeach;
        ?>

        <!-- Comments Section -->
        <div id="comments" class="comment-section">
            <h3 class="mb-4"><i class="fas fa-comments me-2"></i>User Reviews & Comments</h3>

            <!-- Comment Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Leave a Review</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email (optional)</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required placeholder="Share your thoughts about this device..."></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Review
                        </button>
                    </form>
                </div>
            </div>

            <!-- Comments List -->
            <?php if (!empty($comments)): ?>
                <h5 class="mb-3">User Reviews (<?php echo count($comments); ?>)</h5>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0"><?php echo htmlspecialchars($comment['name']); ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">
                    <i class="fas fa-comment-slash fa-2x mb-3 d-block"></i>
                    No reviews yet. Be the first to share your thoughts!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Device Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Device Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- All Images Modal -->
    <div class="modal fade" id="allImagesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>
                        All Images - <?php echo htmlspecialchars($device['name'] ?? ''); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php
                        // Collect all available images
                        $all_images = [];
                        for ($i = 1; $i <= 5; $i++) {
                            $image_key = "image_$i";
                            if (!empty($device[$image_key])) {
                                $all_images[] = $device[$image_key];
                            }
                        }

                        // Also check the images array if it exists
                        if (!empty($device['images']) && is_array($device['images'])) {
                            foreach ($device['images'] as $image) {
                                if (!in_array($image, $all_images)) {
                                    $all_images[] = str_replace('\\', '/', $image);
                                }
                            }
                        }

                        if (!empty($all_images)):
                            foreach ($all_images as $index => $image_url):
                        ?>
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($image_url); ?>"
                                            alt="<?php echo htmlspecialchars($device['name'] ?? ''); ?> - Image <?php echo $index + 1; ?>"
                                            class="img-fluid rounded shadow-sm"
                                            style="cursor: pointer; width: 100%; height: 200px; object-fit: cover;"
                                            onclick="openImageModal('<?php echo htmlspecialchars($image_url); ?>')">
                                        <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 rounded-bottom-start">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endforeach;
                        else:
                            ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No additional images available for this device.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function showAllImages() {
            new bootstrap.Modal(document.getElementById('allImagesModal')).show();
        }

        // Smooth scroll to comments section
        document.addEventListener('DOMContentLoaded', function() {
            const reviewButton = document.querySelector('a[href="#comments"]');
            if (reviewButton) {
                reviewButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentsSection = document.getElementById('comments');
                    if (commentsSection) {
                        commentsSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            }
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                }
            });
        }, 5000);
    </script>
</body>

</html>