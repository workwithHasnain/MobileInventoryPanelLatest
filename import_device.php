<?php

/**
 * import_device.php - Import device from GSMArena scraper extension
 * 
 * Supports two modes:
 * 1. JSON POST (from browser extension) - Content-Type: application/json
 * 2. Web form (paste SQL manually) - regular form POST
 * 
 * Security: Requires API key header (X-API-Key) for JSON imports.
 * Change the API key below before deploying.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for browser extension
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';
require_once __DIR__ . '/simple_device_insert.php';

// ====================================================================
// Configuration
// ====================================================================
$API_KEY = 'devicearena-import-2026'; // Change this to your own secret key

// ====================================================================
// Route the request
// ====================================================================
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (stripos($contentType, 'application/json') !== false) {
        handleJsonImport($API_KEY);
    } elseif (isset($_POST['device_data']) || isset($_FILES['images'])) {
        // Multipart form-data from browser extension (with file uploads)
        handleMultipartImport($API_KEY);
    } else {
        handleSqlImport();
    }
} else {
    showImportForm();
}

// ====================================================================
// JSON Import Handler (from browser extension)
// ====================================================================
function handleJsonImport($apiKey)
{
    header('Content-Type: application/json');

    // Verify API key
    $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($providedKey !== $apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API key']);
        exit;
    }

    // Parse JSON body
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
        exit;
    }

    // Validate required fields
    $name = trim($input['name'] ?? '');
    $brand = trim($input['brand'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Device name is required']);
        exit;
    }
    if (empty($brand)) {
        echo json_encode(['success' => false, 'error' => 'Brand is required']);
        exit;
    }

    // Generate slug if not provided
    $slug = trim($input['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower($brand) . '-' . strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
    }

    // Ensure slug is unique
    $pdo = getConnection();
    $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetchColumn() > 0) {
        $baseSlug = $slug;
        $counter = 1;
        do {
            $slug = $baseSlug . '-' . $counter;
            $slugCheck->execute([$slug]);
            $counter++;
        } while ($slugCheck->fetchColumn() > 0);
    }

    // Build phone data array matching simpleAddDevice expectations
    $phone = [
        'name'                   => $name,
        'brand'                  => $brand,
        'year'                   => !empty($input['year']) ? $input['year'] : null,
        'availability'           => !empty($input['availability']) ? $input['availability'] : null,
        'price'                  => !empty($input['price']) ? $input['price'] : null,
        'release_date'           => !empty($input['release_date']) ? $input['release_date'] : null,
        'device_page_color'      => null,
        'image'                  => !empty($input['image']) ? $input['image'] : '',
        'images'                 => !empty($input['images']) ? $input['images'] : [],

        // Highlight fields
        'weight'                 => $input['weight'] ?? null,
        'thickness'              => $input['thickness'] ?? null,
        'os'                     => $input['os'] ?? null,
        'storage'                => $input['storage'] ?? null,
        'card_slot'              => $input['card_slot'] ?? null,

        // Stats fields
        'display_size'           => $input['display_size'] ?? null,
        'display_resolution'     => $input['display_resolution'] ?? null,
        'main_camera_resolution' => $input['main_camera_resolution'] ?? null,
        'main_camera_video'      => $input['main_camera_video'] ?? null,
        'ram'                    => $input['ram'] ?? null,
        'chipset_name'           => $input['chipset_name'] ?? null,
        'battery_capacity'       => $input['battery_capacity'] ?? null,
        'wired_charging'         => $input['wired_charging'] ?? null,
        'wireless_charging'      => $input['wireless_charging'] ?? null,

        // Grouped spec columns (already JSON strings from extension)
        'network'                => $input['network'] ?? null,
        'launch'                 => $input['launch'] ?? null,
        'body'                   => $input['body'] ?? null,
        'display'                => $input['display'] ?? null,
        'hardware'               => $input['hardware'] ?? null,
        'memory'                 => $input['memory'] ?? null,
        'main_camera'            => $input['main_camera'] ?? null,
        'selfie_camera'          => $input['selfie_camera'] ?? null,
        'multimedia'             => $input['multimedia'] ?? null,
        'connectivity'           => $input['connectivity'] ?? null,
        'features'               => $input['features'] ?? null,
        'battery'                => $input['battery'] ?? null,
        'general_info'           => $input['general_info'] ?? null,

        // SEO
        'slug'                   => $slug,
        'meta_title'             => $input['meta_title'] ?? null,
        'meta_desc'              => $input['meta_desc'] ?? null,
    ];

    // Use the existing simpleAddDevice function
    $result = simpleAddDevice($phone);

    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => "Device '{$brand} {$name}' imported successfully!",
            'slug'    => $slug
        ]);
    } elseif (is_array($result) && isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown error during import']);
    }
    exit;
}

// ====================================================================
// Multipart Import Handler (from browser extension with file uploads)
// ====================================================================
function handleMultipartImport($apiKey)
{
    header('Content-Type: application/json');

    // Verify API key
    $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($providedKey !== $apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API key']);
        exit;
    }

    // Parse device data from the form field
    $deviceDataRaw = $_POST['device_data'] ?? '';
    $input = json_decode($deviceDataRaw, true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid device data payload']);
        exit;
    }

    // Validate required fields
    $name = trim($input['name'] ?? '');
    $brand = trim($input['brand'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Device name is required']);
        exit;
    }
    if (empty($brand)) {
        echo json_encode(['success' => false, 'error' => 'Brand is required']);
        exit;
    }

    // Handle image file uploads (same logic as add_device.php)
    $image_paths = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_images = 5;

        // Create uploads directory if it doesn't exist
        if (!file_exists(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }

        for ($i = 0; $i < min(count($_FILES['images']['name']), $max_images); $i++) {
            if (!empty($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['images']['type'][$i];
                $file_size = $_FILES['images']['size'][$i];

                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    echo json_encode(['success' => false, 'error' => 'Image ' . ($i + 1) . ': Only JPG, PNG, GIF, and WebP images are allowed']);
                    exit;
                }

                // Validate file size
                if ($file_size > $max_size) {
                    echo json_encode(['success' => false, 'error' => 'Image ' . ($i + 1) . ': File size must not exceed 5MB']);
                    exit;
                }

                // Generate unique filename (matching add_device.php convention)
                $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = 'device_' . time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $file_extension;
                $upload_path = 'uploads/' . $filename;
                $full_path = __DIR__ . '/' . $upload_path;

                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $full_path)) {
                    $image_paths[] = $upload_path;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to upload image ' . ($i + 1)]);
                    exit;
                }
            }
        }
    }

    if (empty($image_paths)) {
        echo json_encode(['success' => false, 'error' => 'At least one image file is required']);
        exit;
    }

    // Set main image (first uploaded) and all images array
    $main_image = $image_paths[0];

    // Generate slug if not provided
    $slug = trim($input['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower($brand) . '-' . strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
    }

    // Ensure slug is unique
    $pdo = getConnection();
    $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetchColumn() > 0) {
        $baseSlug = $slug;
        $counter = 1;
        do {
            $slug = $baseSlug . '-' . $counter;
            $slugCheck->execute([$slug]);
            $counter++;
        } while ($slugCheck->fetchColumn() > 0);
    }

    // Build phone data array matching simpleAddDevice expectations
    $phone = [
        'name'                   => $name,
        'brand'                  => $brand,
        'year'                   => !empty($input['year']) ? $input['year'] : null,
        'availability'           => !empty($input['availability']) ? $input['availability'] : null,
        'price'                  => !empty($input['price']) ? $input['price'] : null,
        'release_date'           => !empty($input['release_date']) ? $input['release_date'] : null,
        'device_page_color'      => null,
        'image'                  => $main_image,
        'images'                 => $image_paths,

        // Highlight fields
        'weight'                 => $input['weight'] ?? null,
        'thickness'              => $input['thickness'] ?? null,
        'os'                     => $input['os'] ?? null,
        'storage'                => $input['storage'] ?? null,
        'card_slot'              => $input['card_slot'] ?? null,

        // Stats fields
        'display_size'           => $input['display_size'] ?? null,
        'display_resolution'     => $input['display_resolution'] ?? null,
        'main_camera_resolution' => $input['main_camera_resolution'] ?? null,
        'main_camera_video'      => $input['main_camera_video'] ?? null,
        'ram'                    => $input['ram'] ?? null,
        'chipset_name'           => $input['chipset_name'] ?? null,
        'battery_capacity'       => $input['battery_capacity'] ?? null,
        'wired_charging'         => $input['wired_charging'] ?? null,
        'wireless_charging'      => $input['wireless_charging'] ?? null,

        // Grouped spec columns (JSON strings)
        'network'                => $input['network'] ?? null,
        'launch'                 => $input['launch'] ?? null,
        'body'                   => $input['body'] ?? null,
        'display'                => $input['display'] ?? null,
        'hardware'               => $input['hardware'] ?? null,
        'memory'                 => $input['memory'] ?? null,
        'main_camera'            => $input['main_camera'] ?? null,
        'selfie_camera'          => $input['selfie_camera'] ?? null,
        'multimedia'             => $input['multimedia'] ?? null,
        'connectivity'           => $input['connectivity'] ?? null,
        'features'               => $input['features'] ?? null,
        'battery'                => $input['battery'] ?? null,
        'general_info'           => $input['general_info'] ?? null,

        // SEO
        'slug'                   => $slug,
        'meta_title'             => $input['meta_title'] ?? null,
        'meta_desc'              => $input['meta_desc'] ?? null,
    ];

    // Use the existing simpleAddDevice function
    $result = simpleAddDevice($phone);

    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => "Device '{$brand} {$name}' imported with " . count($image_paths) . " image(s) uploaded!",
            'slug'    => $slug,
            'images'  => $image_paths
        ]);
    } elseif (is_array($result) && isset($result['error'])) {
        // Clean up uploaded files on failure
        foreach ($image_paths as $path) {
            $fullPath = __DIR__ . '/' . $path;
            if (file_exists($fullPath)) unlink($fullPath);
        }
        echo json_encode(['success' => false, 'error' => $result['error']]);
    } else {
        // Clean up uploaded files on failure
        foreach ($image_paths as $path) {
            $fullPath = __DIR__ . '/' . $path;
            if (file_exists($fullPath)) unlink($fullPath);
        }
        echo json_encode(['success' => false, 'error' => 'Unknown error during import']);
    }
    exit;
}

// ====================================================================
// SQL Form Handler (paste & execute SQL manually)
// ====================================================================
function handleSqlImport()
{
    session_start();

    $sql = trim($_POST['sql'] ?? '');
    $message = '';
    $messageType = '';

    if (empty($sql)) {
        $message = 'No SQL provided.';
        $messageType = 'error';
        showImportForm($message, $messageType, $sql);
        return;
    }

    // Basic safety: only allow INSERT and brand upsert patterns
    // Remove comments for validation
    $sqlClean = preg_replace('/--.*$/m', '', $sql);
    $sqlClean = trim($sqlClean);

    // Check that the SQL only contains INSERT statements
    $statements = array_filter(array_map('trim', preg_split('/;\s*/', $sqlClean)));
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        if (!preg_match('/^INSERT\s+INTO\s+/i', $stmt)) {
            $message = 'Only INSERT statements are allowed. Found: ' . substr($stmt, 0, 50) . '...';
            $messageType = 'error';
            showImportForm($message, $messageType, $sql);
            return;
        }
    }

    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->commit();
        $message = 'SQL executed successfully! Device imported.';
        $messageType = 'success';
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'SQL Error: ' . $e->getMessage();
        $messageType = 'error';
    }

    showImportForm($message, $messageType, $sql);
}

// ====================================================================
// Import Form UI
// ====================================================================
function showImportForm($message = '', $messageType = '', $lastSql = '')
{
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Import Device - DeviceArena</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f5f5f5;
            }

            .import-container {
                max-width: 900px;
                margin: 30px auto;
            }

            .sql-textarea {
                font-family: 'Consolas', 'Courier New', monospace;
                font-size: 13px;
                min-height: 400px;
                background: #1e1e1e;
                color: #d4d4d4;
                border: 1px solid #333;
            }

            .sql-textarea:focus {
                background: #1e1e1e;
                color: #d4d4d4;
                border-color: #555;
                box-shadow: none;
            }

            .info-card {
                background: #e8eaf6;
                border: 1px solid #c5cae9;
            }
        </style>
    </head>

    <body>
        <div class="import-container">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-database me-2"></i>Import Device from GSMArena</h4>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card info-card mb-3">
                        <div class="card-body py-2">
                            <small>
                                <strong><i class="fas fa-info-circle me-1"></i>How to use:</strong>
                                <ol class="mb-0 mt-1" style="padding-left: 18px;">
                                    <li>Install the <strong>GSMArena to DeviceArena</strong> browser extension</li>
                                    <li>Navigate to any device page on <a href="https://www.gsmarena.com" target="_blank">GSMArena.com</a></li>
                                    <li>Click the extension icon → add image URLs → click <strong>"Generate SQL & Copy"</strong></li>
                                    <li>Paste the copied SQL below and click <strong>"Execute SQL"</strong></li>
                                </ol>
                                <hr class="my-2">
                                <strong>Or use Direct Import:</strong> Click <strong>"Import to DeviceArena"</strong> in the extension to skip this page entirely.
                            </small>
                        </div>
                    </div>

                    <form method="POST" action="import_device.php">
                        <div class="mb-3">
                            <label for="sql" class="form-label fw-bold">
                                <i class="fas fa-code me-1"></i>Paste Generated SQL
                            </label>
                            <textarea name="sql" id="sql" class="form-control sql-textarea"
                                placeholder="-- Paste the generated SQL from the browser extension here...&#10;&#10;INSERT INTO brands (name) VALUES ('Samsung') ON CONFLICT (name) DO NOTHING;&#10;&#10;INSERT INTO phones (...) VALUES (...);"><?php echo htmlspecialchars($lastSql); ?></textarea>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('sql').value = '';">
                                <i class="fas fa-eraser me-1"></i> Clear
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-play me-1"></i> Execute SQL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
    exit;
}
