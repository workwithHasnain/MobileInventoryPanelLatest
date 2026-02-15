<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'phone_data.php';
require_once 'database_functions.php';
require_once 'includes/database_functions.php';

// New clean URL format: domain/compare/slug1VSslug2VSslug3
// The .htaccess file rewrites clean URLs to this page and passes slugs as query parameter
// Base path variable is now defined in config.php

// Helper function to make image paths absolute
function getAbsoluteImagePath($imagePath, $base)
{
    if (empty($imagePath)) return '';
    // Already an absolute URL
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
    // Already an absolute path starting with /
    if (strpos($imagePath, '/') === 0) return $imagePath;
    // Relative path - prepend base
    return $base . ltrim($imagePath, '/');
}
// Get posts and devices for display (case-insensitive status check) with comment counts
$pdo = getConnection();
$posts_stmt = $pdo->prepare("
    SELECT p.*, 
    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
    FROM posts p 
    WHERE p.status ILIKE 'published' 
    ORDER BY p.created_at DESC 
    LIMIT 6
");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();

// Get devices from database
$devices = getAllPhones();
$devices = array_slice($devices, 0, 6); // Limit to 6 devices for home page

// Add comment counts to devices
foreach ($devices as $index => $device) {
    $comment_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_comments WHERE device_id = CAST(? AS VARCHAR) AND status = 'approved'");
    $comment_stmt->execute([$device['id']]);
    $devices[$index]['comment_count'] = $comment_stmt->fetch()['count'] ?? 0;
}

// Get data for the three tables
$topViewedDevices = [];
$topReviewedDevices = [];
$topComparisons = [];

// Get top viewed devices
try {
    $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(cv.id) as view_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN content_views cv ON CAST(p.id AS VARCHAR) = cv.content_id AND cv.content_type = 'device'
        GROUP BY p.id, b.name
        ORDER BY view_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topViewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
    $topViewedDevices = [];
}

// Get top reviewed devices (by comment count)
try {
    $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(dc.id) as review_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN device_comments dc ON CAST(p.id AS VARCHAR) = dc.device_id AND dc.status = 'approved'
        GROUP BY p.id, b.name
        ORDER BY review_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topReviewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
    $topReviewedDevices = [];
}

// Get top comparisons from database
try {
    $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
    $topComparisons = [];
}

// Get latest 9 devices for the new section
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 9); // Get latest 9 devices

// Get only brands that have devices for the brands table
$brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY COUNT(p.id) DESC, b.name ASC
    LIMIT 36
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get all brands alphabetically ordered - for modal
$all_brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$all_brands_stmt->execute();
$allBrandsModal = $all_brands_stmt->fetchAll();


// Get comments for posts
function getPostComments($post_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

// Get comments for devices
function getDeviceComments($device_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM device_comments WHERE device_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$device_id]);
    return $stmt->fetchAll();
}

// Handle comment submissions and newsletter subscriptions
$comment_success = '';
$comment_error = '';
$newsletter_success = '';
$newsletter_error = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'newsletter_subscribe') {
        $email = trim($_POST['newsletter_email'] ?? '');
        $name = trim($_POST['newsletter_name'] ?? '');

        if (empty($email)) {
            $newsletter_error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $newsletter_error = 'Please enter a valid email address.';
        } else {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $check_stmt->execute([$email]);

            if ($check_stmt->fetch()) {
                $newsletter_error = 'This email is already subscribed to our newsletter.';
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, status, subscribed_at) VALUES (?, ?, 'active', NOW())");
                if ($insert_stmt->execute([$email, $name])) {
                    $newsletter_success = 'Thank you for subscribing to our newsletter! You\'ll receive the latest tech updates and device reviews.';
                } else {
                    $newsletter_error = 'Failed to subscribe. Please try again.';
                }
            }
        }
    } else {
        // Handle comment submissions
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (empty($name) || empty($email) || empty($comment)) {
            $comment_error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $comment_error = 'Please enter a valid email address.';
        } elseif (strlen($comment) < 10) {
            $comment_error = 'Comment must be at least 10 characters long.';
        } else {
            if ($action === 'comment_post') {
                $post_id = $_POST['post_id'] ?? '';
                $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, name, email, comment, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                if ($stmt->execute([$post_id, $name, $email, $comment])) {
                    $comment_success = 'Your comment has been submitted and is pending approval.';
                } else {
                    $comment_error = 'Failed to submit comment. Please try again.';
                }
            } elseif ($action === 'comment_device') {
                $device_id = $_POST['device_id'] ?? '';
                $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                $stmt = $pdo->prepare("INSERT INTO device_comments (device_id, name, email, comment, parent_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                if ($stmt->execute([$device_id, $name, $email, $comment, $parent_id])) {
                    $comment_success = 'Your comment has been submitted and is pending approval.';
                } else {
                    $comment_error = 'Failed to submit comment. Please try again.';
                }
            }
        }
    }
}
// Public compare phones page - no authentication required

// Get all phones from database
$phones = getAllPhones();

// Get selected phone slugs from URL parameters
// New way: slugs come from clean URL (domain/compare/slug1VSslug2VSslug3) rewritten by .htaccess
// Parse multiple slugs from single query parameter if using new URL format
if (isset($_GET['slugs'])) {
    // New format: domain/compare/slug1VSslug2VSslug3
    $slugParts = explode('VS', $_GET['slugs']);
    $phone1_slug = isset($slugParts[0]) ? trim($slugParts[0]) : '';
    $phone2_slug = isset($slugParts[1]) ? trim($slugParts[1]) : '';
    $phone3_slug = isset($slugParts[2]) ? trim($slugParts[2]) : '';
} else {
    // Old format: domain/compare.php?phone1=x&phone2=y&phone3=z (for backward compatibility)
    $phone1_slug = isset($_GET['phone1']) ? $_GET['phone1'] : '';
    $phone2_slug = isset($_GET['phone2']) ? $_GET['phone2'] : '';
    $phone3_slug = isset($_GET['phone3']) ? $_GET['phone3'] : '';
}

// Handle device pre-selection from device page (device1, brand1 parameters)
if (isset($_GET['device1']) && ($phone1_slug === '' || $phone1_slug === null)) {
    $device_name = urldecode($_GET['device1']);
    $device_brand = isset($_GET['brand1']) ? urldecode($_GET['brand1']) : '';

    // Find the device in our phones array by name (and brand if provided)
    foreach ($phones as $phone) {
        $name_match = isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name));
        $brand_match = empty($device_brand) || (isset($phone['brand']) && strtolower(trim($phone['brand'])) === strtolower(trim($device_brand)));

        if ($name_match && $brand_match) {
            $phone1_slug = $phone['slug'] ?? $phone['id'];
            break;
        }
    }

    // If still not found and brand is empty, try searching by name only
    if (!$phone1_slug && empty($device_brand)) {
        foreach ($phones as $phone) {
            if (isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name))) {
                $phone1_slug = $phone['slug'] ?? $phone['id'];
                break;
            }
        }
    }
}

// Helper function to find phone by slug
function findPhoneBySlug($phones, $phoneSlug)
{
    if ($phoneSlug === '' || $phoneSlug === null || $phoneSlug === 'undefined') {
        return null;
    }

    // Find by slug
    foreach ($phones as $phone) {
        if (isset($phone['slug']) && $phone['slug'] === $phoneSlug) {
            return $phone;
        }
    }

    return null;
}

// Get selected phones data
$phone1 = findPhoneBySlug($phones, $phone1_slug);
$phone2 = findPhoneBySlug($phones, $phone2_slug);
$phone3 = findPhoneBySlug($phones, $phone3_slug);

// Track device comparison if both phones are selected
if ($phone1 && $phone2) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        trackDeviceComparison($phone1['id'], $phone2['id'], $ipAddress);
    } catch (Exception $e) {
        // Log error but don't break the page
        error_log('Failed to track device comparison: ' . $e->getMessage());
    }
}

// Check if at least one phone is selected
$has_selection = ($phone1 !== null || $phone2 !== null || $phone3 !== null);

// Helper function to display availability
function displayAvailability($phone)
{
    if (isset($phone['availability'])) {
        if (is_array($phone['availability'])) {
            // Handle old format (array of checkboxes)
            $availability_options = [];
            foreach ($phone['availability'] as $option => $value) {
                if ($value) {
                    $availability_options[] = htmlspecialchars(ucfirst($option));
                }
            }
            return implode(', ', $availability_options);
        } else {
            // Handle new format (string from dropdown)
            return htmlspecialchars($phone['availability']);
        }
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to display network capabilities
function displayNetworkCapabilities($phone)
{
    $networks = [];
    if (isset($phone['network_2g']) && $phone['network_2g']) $networks[] = 'GSM';
    if (isset($phone['network_3g']) && $phone['network_3g']) $networks[] = 'HSPA';
    if (isset($phone['network_4g']) && $phone['network_4g']) $networks[] = 'LTE';
    if (isset($phone['network_5g']) && $phone['network_5g']) $networks[] = '5G';
    return !empty($networks) ? implode(' / ', $networks) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format dimensions
function formatDimensions($phone)
{
    if (isset($phone['dimensions']) && $phone['dimensions']) {
        return $phone['dimensions'];
    } elseif (isset($phone['height']) && isset($phone['width']) && isset($phone['thickness'])) {
        return $phone['height'] . ' x ' . $phone['width'] . ' x ' . $phone['thickness'] . ' mm';
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format weight
function formatWeight($phone)
{
    if (isset($phone['weight']) && $phone['weight']) {
        return $phone['weight'] . ' g';
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format announcement date
function formatAnnouncementDate($phone)
{
    if (isset($phone['release_date']) && $phone['release_date']) {
        $date = new DateTime($phone['release_date']);
        return 'Announced: ' . $date->format('Y, F j');
    }
    return '<span class="text-muted">Not announced</span>';
}

// Helper function to format OS
function formatOS($phone)
{
    if (isset($phone['os']) && $phone['os']) {
        return htmlspecialchars($phone['os']);
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format chipset
function formatChipset($phone)
{
    if (isset($phone['chipset_name']) && $phone['chipset_name']) {
        return htmlspecialchars($phone['chipset_name']);
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to format main camera
function formatMainCamera($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $camera_parts = [];

    // Camera count
    if (!empty($phone['main_camera_count']) && $phone['main_camera_count'] > 1) {
        $camera_parts[] = ucfirst(convertNumberToWord($phone['main_camera_count']));
    } else {
        $camera_parts[] = 'Single';
    }

    // Resolution
    if (!empty($phone['main_camera_resolution'])) {
        $camera_parts[] = $phone['main_camera_resolution'] . 'MP';
    }

    // Features
    $features = [];
    if (!empty($phone['main_camera_ois'])) $features[] = 'OIS';
    if (!empty($phone['main_camera_telephoto'])) $features[] = 'Telephoto';
    if (!empty($phone['main_camera_ultrawide'])) $features[] = 'Ultrawide';
    if (!empty($phone['main_camera_macro'])) $features[] = 'Macro';
    if (!empty($phone['main_camera_flash'])) $features[] = 'Flash';

    if (!empty($features)) {
        $camera_parts[] = implode(', ', $features);
    }

    // Video
    if (!empty($phone['main_camera_video'])) {
        $camera_parts[] = 'Video Recording: ' . $phone['main_camera_video'];
    }

    return !empty($camera_parts) ? implode('<br>', $camera_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to convert numbers to words
function convertNumberToWord($num)
{
    $words = ['', 'single', 'dual', 'triple', 'quad', 'penta', 'hexa', 'hepta', 'octa'];
    return isset($words[$num]) ? $words[$num] : $num;
}

// Helper function to format selfie camera
function formatSelfieCamera($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $selfie_parts = [];

    // Camera count
    if (!empty($phone['selfie_camera_count']) && $phone['selfie_camera_count'] > 1) {
        $selfie_parts[] = ucfirst(convertNumberToWord($phone['selfie_camera_count']));
    } else {
        $selfie_parts[] = 'Single';
    }

    // Resolution
    if (!empty($phone['selfie_camera_resolution'])) {
        $selfie_parts[] = $phone['selfie_camera_resolution'] . 'MP';
    }

    // Features
    $features = [];
    if (!empty($phone['selfie_camera_ois'])) $features[] = 'OIS';
    if (!empty($phone['selfie_camera_flash'])) $features[] = 'Flash';
    if (!empty($phone['popup_camera'])) $features[] = 'Popup';
    if (!empty($phone['under_display_camera'])) $features[] = 'Under Display';

    if (!empty($features)) {
        $selfie_parts[] = implode(', ', $features);
    }

    // Video
    if (!empty($phone['selfie_camera_video'])) {
        $selfie_parts[] = 'Video Recording: ' . $phone['selfie_camera_video'];
    }

    return !empty($selfie_parts) ? implode('<br>', $selfie_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format battery
function formatBattery($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $battery_parts = [];

    // Capacity
    if (!empty($phone['battery_capacity'])) {
        $battery_parts[] = $phone['battery_capacity'] . ' mAh';
        if (!empty($phone['battery_sic'])) {
            $battery_parts[] = '(Silicon)';
        }
    }

    // Removable
    if (isset($phone['battery_removable'])) {
        $battery_parts[] = 'Removable: ' . ($phone['battery_removable'] ? 'Yes' : 'No');
    }

    // Charging
    $charging = [];
    if (!empty($phone['wired_charging'])) {
        $charging[] = 'Wired: ' . $phone['wired_charging'] . 'W';
    }
    if (!empty($phone['wireless_charging'])) {
        $charging[] = 'Wireless: ' . $phone['wireless_charging'] . 'W';
    }

    if (!empty($charging)) {
        $battery_parts[] = implode(', ', $charging);
    }

    return !empty($battery_parts) ? implode('<br>', $battery_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format price
function formatPrice($phone)
{
    $price_parts = [];
    if (isset($phone['storage']) && $phone['storage']) {
        $price_parts[] = $phone['storage'];
    }
    if (isset($phone['ram']) && $phone['ram']) {
        $price_parts[] = $phone['ram'] . ' RAM';
    }

    $price_line = !empty($price_parts) ? implode(' ', $price_parts) : 'Standard variant';

    if (isset($phone['price']) && $phone['price']) {
        $price_line .= '<br>$' . number_format($phone['price'], 2);
    } else {
        $price_line .= '<br><span class="text-muted">Price not available</span>';
    }

    return $price_line;
}

// ---- Pricing helpers (copied to align with device.php behavior) ----
// Convert USD to EUR using a public exchange rate API
function convertUSDtoEUR($usd_amount)
{
    try {
        if ($usd_amount === null || $usd_amount === '' || !is_numeric($usd_amount)) return null;
        $api_url = "https://open.er-api.com/v6/latest/USD";
        $context = stream_context_create([]);
        $response = @file_get_contents($api_url, false, $context);
        if ($response === false) return null;
        $data = json_decode($response, true);
        if (isset($data['rates']['EUR']) && is_numeric($data['rates']['EUR'])) {
            $rate = (float)$data['rates']['EUR'];
            return $usd_amount * $rate;
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Extract numeric USD price from misc JSON (rows with field: 'Price')
function extractPriceFromMisc($miscJson)
{
    if (!isset($miscJson) || $miscJson === '' || $miscJson === null) return null;
    $decoded = json_decode($miscJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) return null;

    foreach ($decoded as $row) {
        $field = isset($row['field']) ? strtolower(trim((string)$row['field'])) : '';
        $desc = isset($row['description']) ? trim((string)$row['description']) : '';
        if ($field === 'price' && $desc !== '') {
            // Try to extract a USD amount like $999 or 999.99
            // First, look for an explicit $ amount
            if (preg_match('/\$\s*([0-9]+(?:\.[0-9]{1,2})?)/', $desc, $m)) {
                return (float)$m[1];
            }
            // Otherwise, fallback to the first number in the string
            if (preg_match('/([0-9]+(?:\.[0-9]{1,2})?)/', $desc, $m)) {
                return (float)$m[1];
            }
        }
    }
    return null;
}

// Helper function to get phone image
function getPhoneImage($phone)
{
    if (isset($phone['image']) && !empty($phone['image'])) {
        $image = $phone['image'];
        // Ensure absolute path
        if (strpos($image, '/') !== 0 && !filter_var($image, FILTER_VALIDATE_URL)) {
            $image = '/' . ltrim($image, '/');
        }
        return htmlspecialchars($image);
    }
    // Default fallback image
    return '/imges/phone-placeholder.png';
}

// Helper function to get phone name with brand
function getPhoneName($phone)
{
    if (!$phone) return 'Select a device';

    $name = '';
    if (isset($phone['brand_name']) && $phone['brand_name']) {
        $name = $phone['brand_name'] . ' ';
    }
    if (isset($phone['name']) && $phone['name']) {
        $name .= $phone['name'];
    }

    return !empty($name) ? htmlspecialchars($name) : 'Unknown Device';
}

// Helper function to format display specifications
function formatDisplay($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $display_parts = [];

    if (!empty($phone['display_type'])) {
        $display_parts[] = $phone['display_type'];
        if (!empty($phone['display_technology'])) {
            $display_parts[] = $phone['display_technology'];
        }
        if (!empty($phone['refresh_rate'])) {
            $display_parts[] = $phone['refresh_rate'] . 'Hz';
        }
        if (!empty($phone['hdr'])) {
            $display_parts[] = 'HDR';
        }
        if (!empty($phone['billion_colors'])) {
            $display_parts[] = '1B colors';
        }
    }

    $display_info = !empty($display_parts) ? implode(', ', $display_parts) : '';

    if (!empty($phone['display_size'])) {
        if ($display_info) $display_info .= '<br>';
        $display_info .= $phone['display_size'] . '"';
    }

    if (!empty($phone['display_resolution'])) {
        if ($display_info) $display_info .= '<br>';
        $display_info .= $phone['display_resolution'];
    }

    return !empty($display_info) ? $display_info : '<span class="text-muted">Not specified</span>';
}

// Helper function to format memory specifications
function formatMemory($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $memory_parts = [];

    if (!empty($phone['ram'])) {
        $memory_parts[] = $phone['ram'] . 'GB RAM';
    }

    if (!empty($phone['storage'])) {
        $memory_parts[] = $phone['storage'] . 'GB';
    }

    if (!empty($phone['card_slot'])) {
        $memory_parts[] = 'Expansion Slot: ' . $phone['card_slot'];
    }

    return !empty($memory_parts) ? implode(', ', $memory_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format sound specifications
function formatSound($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $sound_parts = [];

    if (isset($phone['dual_speakers'])) {
        $sound_parts[] = 'Audio Output: ' . ($phone['dual_speakers'] ? 'Yes' : 'No');
    }

    if (isset($phone['headphone_jack'])) {
        $sound_parts[] = '3.5mm jack: ' . ($phone['headphone_jack'] ? 'Yes' : 'No');
    }

    return !empty($sound_parts) ? implode(', ', $sound_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format communications specifications
function formatCommunications($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $comms_parts = [];

    if (!empty($phone['wifi'])) {
        $wifi_value = is_array($phone['wifi']) ? implode(', ', $phone['wifi']) : $phone['wifi'];
        $comms_parts[] = 'WiFi: ' . $wifi_value;
    }

    if (!empty($phone['bluetooth'])) {
        $bluetooth_value = is_array($phone['bluetooth']) ? implode(', ', $phone['bluetooth']) : $phone['bluetooth'];
        $comms_parts[] = 'Bluetooth: ' . $bluetooth_value;
    }

    if (isset($phone['gps'])) {
        $comms_parts[] = 'GPS: ' . ($phone['gps'] ? 'Yes' : 'No');
    }

    if (isset($phone['nfc'])) {
        $comms_parts[] = 'Proximity: ' . ($phone['nfc'] ? 'Yes' : 'No');
    }

    if (isset($phone['fm_radio'])) {
        $comms_parts[] = 'FM Radio: ' . ($phone['fm_radio'] ? 'Yes' : 'No');
    }

    if (!empty($phone['usb'])) {
        $comms_parts[] = 'USB: ' . $phone['usb'];
    }

    return !empty($comms_parts) ? implode(', ', $comms_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format features specifications
function formatFeatures($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    $features_parts = [];

    if (isset($phone['fingerprint'])) {
        $features_parts[] = 'Fingerprint: ' . ($phone['fingerprint'] ? 'Yes' : 'No');
    }

    // Build sensors list
    $sensors = [];
    if (!empty($phone['accelerometer'])) $sensors[] = 'Accelerometer';
    if (!empty($phone['gyro'])) $sensors[] = 'Gyro';
    if (!empty($phone['compass'])) $sensors[] = 'Compass';
    if (!empty($phone['proximity'])) $sensors[] = 'Proximity';
    if (!empty($phone['barometer'])) $sensors[] = 'Barometer';
    if (!empty($phone['heart_rate'])) $sensors[] = 'Heart Rate';

    if (!empty($sensors)) {
        $features_parts[] = 'Sensors: ' . implode(', ', $sensors);
    }

    return !empty($features_parts) ? implode(', ', $features_parts) : '<span class="text-muted">Not specified</span>';
}

// Helper function to format colors
function formatColors($phone)
{
    if (!$phone) return '<span class="text-muted">Not specified</span>';

    if (!empty($phone['colors'])) {
        if (is_array($phone['colors'])) {
            return implode(', ', $phone['colors']);
        } elseif (is_string($phone['colors'])) {
            // Handle PostgreSQL array string format
            $colors = str_replace(['{', '}'], '', $phone['colors']);
            $colors = explode(',', $colors);
            return implode(', ', array_map('trim', $colors));
        }
    }

    return '<span class="text-muted">Not specified</span>';
}

// ---- Text truncation helper ----
$truncateText = function ($text, $maxLength = 60) {
    if (strlen($text) > $maxLength) {
        $truncated = substr($text, 0, $maxLength);
        // Store full text in data attribute and show clickable ellipsis
        return htmlspecialchars($truncated) . '<span class="expand-dots" data-full="' . str_replace('"', '&quot;', htmlspecialchars($text)) . '" style="cursor: pointer; color: #d50000; font-weight: bold;">  ...</span>';
    }
    return htmlspecialchars($text);
};

// ---- New JSON specs rendering (align with device.php) ----
// Helper: parse JSON section and return structured array of field+description pairs (for 2-column layout in comparison)
function parseJsonSectionStructured($jsonValue, $sectionName = '', $truncateFunc = null)
{
    if (!isset($jsonValue) || $jsonValue === '' || $jsonValue === null) return [];
    $decoded = json_decode($jsonValue, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return [];
    }

    $rows = [];
    foreach ($decoded as $row) {
        $field = isset($row['field']) ? trim((string)$row['field']) : '';
        $desc = isset($row['description']) ? trim((string)$row['description']) : '';

        if ($field === '' && $desc === '') continue;

        if ($field !== '') {
            // New field entry
            $description = $desc;

            // Add EUR conversion for price if applicable
            if ($sectionName === 'GENERAL INFO' && strtolower($field) === 'price' && $desc !== '') {
                $priceStr = preg_replace('/[^0-9.]/', '', $desc);
                if ($priceStr !== '' && is_numeric($priceStr)) {
                    $usd = (float)$priceStr;
                    $eur = convertUSDtoEUR($usd);
                    if ($eur !== null) {
                        $description = $desc . ' / €' . number_format($eur, 2);
                    }
                }
            }

            $rows[] = [
                'field' => $field,
                'description' => $description
            ];
        } else {
            // Empty field = continuation of previous field
            if (!empty($rows)) {
                $lastRow = &$rows[count($rows) - 1];
                if ($desc !== '') {
                    $lastRow['description'] .= "\n" . $desc;
                }
            }
        }
    }

    return $rows;
}

// Helper: render a section from JSON stored as TEXT in DB (legacy, for backwards compatibility)
function renderJsonSection($jsonValue, $sectionName = '', $truncateFunc = null)
{
    if (!isset($jsonValue) || $jsonValue === '' || $jsonValue === null) return null;
    $decoded = json_decode($jsonValue, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        // If it's not valid JSON but has text, just return as-is (escaped)
        return htmlspecialchars((string)$jsonValue);
    }
    $parts = [];
    foreach ($decoded as $row) {
        $field = isset($row['field']) ? trim((string)$row['field']) : '';
        $desc = isset($row['description']) ? trim((string)$row['description']) : '';
        if ($field === '' && $desc === '') continue;
        if ($field !== '') {
            $line = '' . htmlspecialchars($field) . '';
            if ($desc !== '') {
                // Truncate description to 60 characters with expand functionality
                $line .= ' ' . $truncateFunc($desc, 60);

                // If this is the GENERAL INFO -> Price row, append EUR conversion like device.php
                if ($sectionName === 'GENERAL INFO' && strtolower($field) === 'price') {
                    // Extract numeric USD amount from description
                    $priceStr = preg_replace('/[^0-9.]/', '', $desc);
                    if ($priceStr !== '' && is_numeric($priceStr)) {
                        $usd = (float)$priceStr;
                        $eur = convertUSDtoEUR($usd);
                        if ($eur !== null) {
                            $line .= ' / €' . number_format($eur, 2);
                        }
                    }
                }
            }
            $parts[] = $line;
        } else {
            $parts[] = htmlspecialchars($desc);
        }
    }
    return implode('<br>', $parts);
}

// Build specs array from grouped JSON columns (returns HTML string per section - legacy)
function formatDeviceSpecsJson($device, $truncateFunc = null)
{
    $specs = [];
    $jsonSections = [
        'NETWORK' => $device['network'] ?? null,
        'LAUNCH' => $device['launch'] ?? null,
        'BODY' => $device['body'] ?? null,
        'DISPLAY' => $device['display'] ?? null,
        'HARDWARE' => $device['hardware'] ?? null,
        'MEMORY' => $device['memory'] ?? null,
        'MAIN CAMERA' => $device['main_camera'] ?? null,
        'SELFIE CAMERA' => $device['selfie_camera'] ?? null,
        'MULTIMEDIA' => $device['multimedia'] ?? null,
        'CONNECTIVITY' => $device['connectivity'] ?? null,
        'FEATURES' => $device['features'] ?? null,
        'BATTERY' => $device['battery'] ?? null,
        'GENERAL INFO' => $device['general_info'] ?? null,
    ];

    foreach ($jsonSections as $label => $raw) {
        $html = renderJsonSection($raw, $label, $truncateFunc);
        if ($html && trim($html) !== '') {
            $specs[$label] = $html;
        }
    }
    return $specs;
}

// Build specs array with structured field/description pairs (for 2-column comparison layout)
function formatDeviceSpecsStructured($device)
{
    $specs = [];
    $jsonSections = [
        'NETWORK' => $device['network'] ?? null,
        'LAUNCH' => $device['launch'] ?? null,
        'BODY' => $device['body'] ?? null,
        'DISPLAY' => $device['display'] ?? null,
        'HARDWARE' => $device['hardware'] ?? null,
        'MEMORY' => $device['memory'] ?? null,
        'MAIN CAMERA' => $device['main_camera'] ?? null,
        'SELFIE CAMERA' => $device['selfie_camera'] ?? null,
        'MULTIMEDIA' => $device['multimedia'] ?? null,
        'CONNECTIVITY' => $device['connectivity'] ?? null,
        'FEATURES' => $device['features'] ?? null,
        'BATTERY' => $device['battery'] ?? null,
        'GENERAL INFO' => $device['general_info'] ?? null,
    ];

    foreach ($jsonSections as $label => $raw) {
        $rows = parseJsonSectionStructured($raw, $label);
        if (!empty($rows)) {
            $specs[$label] = $rows;
        }
    }
    return $specs;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DevicesArena</title>

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
    <link rel="icon" type="image/png" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">

    <!-- Apple Touch Icon (iOS Home Screen) -->
    <link rel="apple-touch-icon" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="apple-touch-icon" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Android Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Theme Color (Browser Chrome & Address Bar) -->
    <meta name="theme-color" content="#8D6E63">

    <!-- Windows Tile Icon -->
    <meta name="msapplication-TileColor" content="#8D6E63">
    <meta name="msapplication-TileImage" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- Open Graph Meta Tags (Social Media Sharing) -->
    <meta property="og:site_name" content="DevicesArena">
    <meta property="og:title" content="DevicesArena - Smartphone Reviews & Comparisons">
    <meta property="og:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons on DevicesArena.">
    <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="256">
    <meta property="og:image:height" content="256">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="DevicesArena">
    <meta name="twitter:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons.">
    <meta name="twitter:image" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo $base; ?>manifest.json">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Font Awesome (for icons) -->
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Select2 for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="<?php echo $base; ?>style.css">
    <style>
        /* Mobile Horizontal Scroll Wrapper for Phone Cards */
        @media(max-width: 768px) {
            body {
                overflow-x: hidden !important;
            }

            .comparison-container.container {
                padding: 0 !important;
                overflow-x: hidden;
                max-width: 100vw !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                width: 100% !important;
            }

            .phone-cards-scroll-wrapper {
                width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .comparison-container .row {
                display: flex;
                flex-wrap: nowrap;
                width: auto;
                margin: 0;
                padding: 15px 5px;
            }

            .comparison-container .phone-card {
                flex: 0 0 calc(50vw - 5px);
                max-width: calc(50vw - 5px);
                min-width: calc(50vw - 5px);
                margin-bottom: 10px;
                min-height: auto;
            }

            .phone-card .d-flex {
                flex-direction: column;
                align-items: center;
            }

            .phone-card .d-flex img {
                width: 100%;
                height: 220px;
                object-fit: contain;
                margin-bottom: 10px;
            }

            .phone-card .buttons {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 5px;
                align-items: center;
            }

            .phone-card .buttons button {
                background: #EEEEEE;
                color: black;
                border: none;
                padding: 8px 10px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
                text-align: center;
            }

            .phone-card .buttons button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .phone-card .compare-checkbox {
                margin-bottom: 10px;
            }

            .phone-card .compare-checkbox label {
                font-size: 12px;
                color: #666;
                display: block;
                margin-bottom: 5px;
            }
        }

        /* Prevent body overflow when Select2 is open */
        body.select2-dropdown-open {
            overflow: hidden !important;
        }

        /* Prevent body overflow when Select2 is open */
        body.select2-dropdown-open {
            overflow: hidden !important;
        }

        /* Select2 Custom Styling for Phone Comparison */
        .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: auto !important;
            min-height: 38px;
            /* border: 1px solid #ced4da; */
            border-radius: 4px;
            background-color: #fff;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 6px 42px 6px 12px;
            line-height: 1.5;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            top: 50% !important;
            transform: translateY(-50%) !important;
        }

        /* Dropdown styling */
        .select2-dropdown {
            border: 1px solid black;
            border-radius: 4px;
            /* background-color: #EFEBE9; */
            max-width: 100% !important;
        }

        .select2-container--default .select2-results__option {
            padding: 8px 12px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #dadadaff !important;
            color: black;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #b6b6b6ff !important;
        }

        /* Custom option template with image */
        .select2-phone-option {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .select2-phone-option img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .select2-phone-option-text {
            flex: 1;
            overflow: hidden;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
            font-size: 10px;
        }

        /* Search box styling */
        .select2-search--dropdown .select2-search__field {
            border: 1px solid black;
            border-radius: 4px;
            padding: 6px 12px;
        }

        /* Ensure dropdown doesn't overflow container */
        .compare-checkbox {
            position: relative;
            overflow: visible !important;
        }

        .compare-checkbox .select2-container {
            display: block;
            width: 100% !important;
        }

        /* Brand Modal Styling */
        .brand-cell-modal {
            background-color: #fff;
            border: 1px solid #c5b6b0;
            color: black;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        }

        .brand-cell-modal:hover {
            background-color: #d7c8c8ff !important;
            border-color: black;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: black;
        }

        .brand-cell-modal:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand-cell-modal:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
        }

        #brandsModal .modal-dialog-scrollable {
            max-height: 80vh;
        }

        /* Device Cell Modal Styling */
        .device-cell-modal {
            background-color: #fff;
            border: 1px solid black;
            color: black;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        }

        .device-cell-modal:hover {
            background-color: #e26565ff !important;
            border-color: #8D6E63;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: black;
        }

        .device-cell-modal:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .device-cell-modal:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
        }

        #devicesModal .modal-dialog-scrollable {
            max-height: 80vh;
        }
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
        crossorigin="anonymous">
    </script>
</head>

<body style="background-color: #EFEBE9;">
    <!-- Desktop Navbar of Gsmarecn -->
    <?php include 'includes/gsmheader.php'; ?>
    <div class="container support content-wrapper" id="Top">
        <div class="row">
            <div class="col-md-8 col-5  d-lg-inline d-none " style="padding: 0; position: relative;">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="hero-images/compare-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="    position: relative;
    /* left: 12px; */
    padding: 0;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <?php
                    if (empty($brands)): ?>
                        <button class="px-3 py-1" style="cursor: default;" disabled>No brands available.</button>
                        <?php else:
                        $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
                        foreach ($brandChunks as $brandRow):
                            foreach ($brandRow as $brand):
                        ?>
                                <button class="brand-cell brand-item-bold" style="cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
                    <?php
                            endforeach;
                        endforeach;
                    endif;
                    ?>
                </div>
                <div class="d-flex">
                    <button class="solid w-50 py-2" onclick="showBrandsModal()">
                        <i class="fa-solid fa-bars fa-sm mx-2"></i>
                        All Brands</button>
                    <button class="solid w-50 py-2">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>
                </div>
            </div>
        </div>

    </div>
    <div class="comparison-container container bg-white margin-top-4rem">
        <div class="phone-cards-scroll-wrapper">
            <div class="row">
                <div class="phone-card col-lg-4" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="compare-checkbox">
                        <label>
                            Compare
                            <select id="phone1-select" name="phone1" class="phone-select-dropdown" data-phone-number="1">
                                <option value="">Select Phone 1</option>
                                <?php foreach ($phones as $phone): ?>
                                    <option value="<?php echo $phone['slug']; ?>" data-image="<?php echo htmlspecialchars(getPhoneImage($phone)); ?>" data-name="<?php echo htmlspecialchars(getPhoneName($phone)); ?>" <?php echo ($phone1 && $phone1['slug'] == $phone['slug']) ? 'selected' : ''; ?>>
                                        <?php echo getPhoneName($phone); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <?php if ($phone1): ?>
                        <div class="phone-name" style="flex-grow: 1;"><?php echo getPhoneName($phone1); ?></div>
                        <div class="d-flex">
                            <img src="<?php echo getPhoneImage($phone1); ?>" alt="<?php echo getPhoneName($phone1); ?>">
                            <div class="buttons">
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone1['slug'] ?? $phone1['id']); ?>'">REVIEW</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone1['slug'] ?? $phone1['id']); ?>'">SPECIFICATIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone1['slug'] ?? $phone1['id']); ?>#comments'">READ OPINIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone1['slug'] ?? $phone1['id']); ?>'">PICTURES</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="phone-name">Select a device to compare</div>
                        <div class="d-flex">
                            <i class="fas fa-mobile-alt" style="font-size: 120px; color: #ccc; display: flex; align-items: center; justify-content: center; width: 100%; height: 220px;"></i>
                            <div class="buttons">
                                <button disabled>REVIEW</button>
                                <button disabled>SPECIFICATIONS</button>
                                <button disabled>READ OPINIONS</button>
                                <button disabled>PICTURES</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="phone-card col-lg-4" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="compare-checkbox">
                        <label>
                            Compare
                            <select id="phone2-select" name="phone2" class="phone-select-dropdown" data-phone-number="2">
                                <option value="">Select Phone 2</option>
                                <?php foreach ($phones as $phone): ?>
                                    <option value="<?php echo $phone['slug']; ?>" data-image="<?php echo htmlspecialchars(getPhoneImage($phone)); ?>" data-name="<?php echo htmlspecialchars(getPhoneName($phone)); ?>" <?php echo ($phone2 && $phone2['slug'] == $phone['slug']) ? 'selected' : ''; ?>>
                                        <?php echo getPhoneName($phone); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <?php if ($phone2): ?>
                        <div class="phone-name" style="flex-grow: 1;"><?php echo getPhoneName($phone2); ?></div>
                        <div class="d-flex">
                            <img src="<?php echo getPhoneImage($phone2); ?>" alt="<?php echo getPhoneName($phone2); ?>">
                            <div class="buttons">
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone2['slug'] ?? $phone2['id']); ?>'">REVIEW</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone2['slug'] ?? $phone2['id']); ?>'">SPECIFICATIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone2['slug'] ?? $phone2['id']); ?>#comments'">READ OPINIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone2['slug'] ?? $phone2['id']); ?>'">PICTURES</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="phone-name">Select a device to compare</div>
                        <div class="d-flex">
                            <i class="fas fa-mobile-alt" style="font-size: 120px; color: #ccc; display: flex; align-items: center; justify-content: center; width: 100%; height: 220px;"></i>
                            <div class="buttons">
                                <button disabled>REVIEW</button>
                                <button disabled>SPECIFICATIONS</button>
                                <button disabled>READ OPINIONS</button>
                                <button disabled>PICTURES</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="align-items-center m-auto">
                    </div>
                </div>
                <div class="phone-card col-lg-4" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="compare-checkbox">
                        <label>
                            Compare
                            <select id="phone3-select" name="phone3" class="phone-select-dropdown" data-phone-number="3">
                                <option value="">Select Phone 3</option>
                                <?php foreach ($phones as $phone): ?>
                                    <option value="<?php echo $phone['slug']; ?>" data-image="<?php echo htmlspecialchars(getPhoneImage($phone)); ?>" data-name="<?php echo htmlspecialchars(getPhoneName($phone)); ?>" <?php echo ($phone3 && $phone3['slug'] == $phone['slug']) ? 'selected' : ''; ?>>
                                        <?php echo getPhoneName($phone); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <?php if ($phone3): ?>
                        <div class="phone-name" style="flex-grow: 1;"><?php echo getPhoneName($phone3); ?></div>
                        <div class="d-flex">
                            <img src="<?php echo getPhoneImage($phone3); ?>" alt="<?php echo getPhoneName($phone3); ?>">
                            <div class="buttons">
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone3['slug'] ?? $phone3['id']); ?>'">REVIEW</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone3['slug'] ?? $phone3['id']); ?>'">SPECIFICATIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone3['slug'] ?? $phone3['id']); ?>#comments'">READ OPINIONS</button>
                                <button onclick="window.location.href='/device/<?php echo urlencode($phone3['slug'] ?? $phone3['id']); ?>'">PICTURES</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="phone-name">Select a device to compare</div>
                        <div class="d-flex">
                            <i class="fas fa-mobile-alt" style="font-size: 120px; color: #ccc; display: flex; align-items: center; justify-content: center; width: 100%; height: 220px;"></i>
                            <div class="buttons">
                                <button disabled>REVIEW</button>
                                <button disabled>SPECIFICATIONS</button>
                                <button disabled>READ OPINIONS</button>
                                <button disabled>PICTURES</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="align-items-center m-auto">
                    </div>
                </div>
            </div>
        </div>
        <div class="comparison-wrapper">
            <style>
                /* Wrapper that allows horizontal scroll */
                .comparison-wrapper {
                    width: 100%;
                    overflow-x: auto;
                    overflow-y: hidden;
                    -webkit-overflow-scrolling: touch;
                    border: 1px solid #e5e5e5;
                    border-radius: 10px;
                }

                /* Table full width but no wrap issues */
                .comparison-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 900px;
                    /* enough for 3 phones */
                    table-layout: fixed;
                }

                .comparison-table th,
                .comparison-table td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    vertical-align: top;
                    text-align: left;
                    word-wrap: break-word;
                    font-size: 15px;
                }

                /* Section headings */
                .comparison-table td[colspan="3"] {
                    background: #f7f7f7;
                    font-weight: 700;
                    color: #e63946;
                    font-size: 16px;
                    text-transform: uppercase;
                }

                .subtitle {
                    font-weight: 600;
                    word-break: break-word;
                    font-size: 13px;
                }

                .description {
                    word-break: break-word;
                    white-space: normal;
                    line-height: 1.5;
                    font-size: 13px;
                }

                .subt-desc-cont {
                    display: grid;
                    grid-template-columns: 75px 1fr;
                    gap: 8px;
                    align-items: start;
                    padding: 0px 8px;
                }

                /* Mobile: enable TRUE GSMArena style scroll */
                @media(max-width: 768px) {

                    .comparison-wrapper {
                        overflow-x: scroll;
                        white-space: nowrap;
                    }

                    .comparison-table {
                        min-width: 580px;
                        /* Reduced to show 2 phones at once */
                    }

                    .comparison-table th,
                    .comparison-table td {
                        white-space: normal;
                        /* readable text */
                        font-size: 11px;
                        padding: 2px 1px !important;
                        margin: 0 !important;
                        max-width: 165px;
                        /* Limit column width to fit 2 phones on screen */
                    }

                    .comparison-table th {
                        font-size: 10px;
                        padding: 3px 1px !important;
                    }

                    .subtitle {
                        font-weight: 600;
                        word-break: break-word;
                        font-size: 10px;
                    }

                    .description {
                        word-break: break-word;
                        white-space: normal;
                        line-height: 1.4;
                        font-size: 9px;
                    }

                    .subt-desc-cont {
                        display: block;
                        grid-template-columns: 75px 1fr;
                        gap: 4px;
                        align-items: start;
                    }

                    /* Section headings on mobile */
                    .comparison-table td[colspan="3"] {
                        font-size: 11px;
                        padding: 4px 8px !important;
                    }
                }
            </style>
            <table class="comparison-table">
                <tbody>
                    <?php
                    // Build spec arrays from structured JSON for each phone (if phone selected)
                    $specs1 = $phone1 ? formatDeviceSpecsStructured($phone1) : [];
                    $specs2 = $phone2 ? formatDeviceSpecsStructured($phone2) : [];
                    $specs3 = $phone3 ? formatDeviceSpecsStructured($phone3) : [];

                    // Ordered sections (match device page logical order)
                    $orderedSections = [
                        'NETWORK',
                        'LAUNCH',
                        'BODY',
                        'DISPLAY',
                        'HARDWARE',
                        'MEMORY',
                        'MAIN CAMERA',
                        'SELFIE CAMERA',
                        'MULTIMEDIA',
                        'CONNECTIVITY',
                        'FEATURES',
                        'BATTERY',
                        'GENERAL INFO'
                    ];

                    // Fallback legacy mapping for key sections if JSON absent
                    $legacyFallback = function ($label, $phone) {
                        if (!$phone) return 'N/A';
                        switch ($label) {
                            case 'NETWORK':
                                return displayNetworkCapabilities($phone);
                            case 'LAUNCH':
                                // combine announcement + availability + price if present
                                $parts = [];
                                if (!empty($phone['release_date'])) {
                                    $parts[] = formatAnnouncementDate($phone);
                                }
                                if (!empty($phone['availability'])) {
                                    $parts[] = 'Status: ' . htmlspecialchars($phone['availability']);
                                }
                                // Prefer extracting price from general_info JSON if available
                                $usdPrice = null;
                                if (!empty($phone['general_info'])) {
                                    $usdPrice = extractPriceFromMisc($phone['general_info']);
                                }
                                if ($usdPrice === null && !empty($phone['price']) && is_numeric($phone['price'])) {
                                    $usdPrice = (float)$phone['price'];
                                }
                                if ($usdPrice !== null) {
                                    $priceLine = 'Price: $' . number_format($usdPrice, 2);
                                    $eur = convertUSDtoEUR($usdPrice);
                                    if ($eur !== null) {
                                        $priceLine .= ' / €' . number_format($eur, 2);
                                    }
                                    $parts[] = $priceLine;
                                }
                                return !empty($parts) ? implode('<br>', $parts) : 'N/A';
                            case 'BODY':
                                $body = [];
                                $dims = formatDimensions($phone);
                                if ($dims && strpos($dims, 'Not specified') === false) $body[] = 'Dimensions ' . $dims;
                                if (!empty($phone['weight'])) $body[] = 'Weight ' . htmlspecialchars($phone['weight']) . ' g';
                                return !empty($body) ? implode('<br>', $body) : 'N/A';
                            case 'DISPLAY':
                                return formatDisplay($phone);
                            case 'HARDWARE':
                                $plat = [];
                                if (!empty($phone['os'])) $plat[] = 'OS ' . htmlspecialchars($phone['os']);
                                if (!empty($phone['chipset_name'])) $plat[] = 'System Chip ' . htmlspecialchars($phone['chipset_name']);
                                return !empty($plat) ? implode('<br>', $plat) : 'N/A';
                            case 'MEMORY':
                                return formatMemory($phone);
                            case 'MAIN CAMERA':
                                return formatMainCamera($phone);
                            case 'SELFIE CAMERA':
                                return formatSelfieCamera($phone);
                            case 'MULTIMEDIA':
                                return formatSound($phone);
                            case 'CONNECTIVITY':
                                return formatCommunications($phone);
                            case 'FEATURES':
                                return formatFeatures($phone);
                            case 'BATTERY':
                                return formatBattery($phone);
                            case 'GENERAL INFO':
                                // Price & colors if available
                                $miscParts = [];
                                $colors = formatColors($phone);
                                if ($colors && strpos($colors, 'Not specified') === false) $miscParts[] = 'Colors ' . $colors;
                                // Remove direct price display here to avoid duplication (now shown in LAUNCH)
                                return !empty($miscParts) ? implode('<br>', $miscParts) : 'N/A';
                            default:
                                return 'N/A';
                        }
                    };

                    foreach ($orderedSections as $section) {
                        // Get structured spec rows for this section from each phone
                        $rows1 = isset($specs1[$section]) ? $specs1[$section] : [];
                        $rows2 = isset($specs2[$section]) ? $specs2[$section] : [];
                        $rows3 = isset($specs3[$section]) ? $specs3[$section] : [];

                        // Determine max number of rows for this section
                        $maxRows = max(count($rows1), count($rows2), count($rows3), 1);

                        // If no structured data, fall back to legacy rendering
                        if ($maxRows === 1 && empty($rows1) && empty($rows2) && empty($rows3)) {
                            $val1 = $legacyFallback($section, $phone1);
                            $val2 = $legacyFallback($section, $phone2);
                            $val3 = $legacyFallback($section, $phone3);
                            $headerCell = '<td colspan="3" style="color:#f14d4d;font-size:16px;background:#f9f9f9;font-weight:700;position:relative;">' . htmlspecialchars($section);
                            if ($section === 'NETWORK') {
                                $headerCell .= '<button class="compare-expand-btn" onclick="toggleCompareNetworkRows(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#666;font-size:11px;cursor:pointer;text-transform:uppercase;font-weight:500;">COLLAPSE ▲</button>';
                            }
                            $headerCell .= '</td>';
                            echo '<tr>' . $headerCell . '</tr>';
                            echo '<tr>';
                            echo '<td>' . ($val1 !== '' ? $val1 : 'N/A') . '</td>';
                            echo '<td>' . ($val2 !== '' ? $val2 : 'N/A') . '</td>';
                            echo '<td>' . ($val3 !== '' ? $val3 : 'N/A') . '</td>';
                            echo '</tr>';
                        } else {
                            // Section header row
                            $headerCell = '<td colspan="3" style="color:#f14d4d;font-size:16px;background:#f9f9f9;font-weight:700;position:relative;">' . htmlspecialchars($section);
                            if ($section === 'NETWORK') {
                                $headerCell .= '<button class="compare-expand-btn" onclick="toggleCompareNetworkRows(this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#666;font-size:11px;cursor:pointer;text-transform:uppercase;font-weight:500;">COLLAPSE ▲</button>';
                            }
                            $headerCell .= '</td>';
                            echo '<tr>' . $headerCell . '</tr>';

                            // Render each field/description pair as a 2-column row per phone
                            for ($i = 0; $i < $maxRows; $i++) {
                                $rowClass = ($section === 'NETWORK' && $i > 0) ? ' class="compare-network-row"' : '';
                                echo '<tr' . $rowClass . '>';

                                // Phone 1
                                if (isset($rows1[$i])) {
                                    echo '<td style="padding:12px 10px;vertical-align:top;"><div class="subt-desc-cont"><div class="subtitle">' . htmlspecialchars($rows1[$i]['field']) . '</div><div class="description">' . nl2br(htmlspecialchars($rows1[$i]['description'])) . '</div></div></td>';
                                } else {
                                    echo '<td style="padding:12px 10px;color:#999;">N/A</td>';
                                }

                                // Phone 2
                                if (isset($rows2[$i])) {
                                    echo '<td style="padding:12px 10px;vertical-align:top;"><div class="subt-desc-cont"><div class="subtitle">' . htmlspecialchars($rows2[$i]['field']) . '</div><div class="description">' . nl2br(htmlspecialchars($rows2[$i]['description'])) . '</div></div></td>';
                                } else {
                                    echo '<td style="padding:12px 10px;color:#999;">N/A</td>';
                                }

                                // Phone 3
                                if (isset($rows3[$i])) {
                                    echo '<td style="padding:12px 10px;vertical-align:top;"><div class="subt-desc-cont"><div class="subtitle">' . htmlspecialchars($rows3[$i]['field']) . '</div><div class="description">' . nl2br(htmlspecialchars($rows3[$i]['description'])) . '</div></div></td>';
                                } else {
                                    echo '<td style="padding:12px 10px;color:#999;">N/A</td>';
                                }

                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>

        </div>

    </div>
    <?php include 'includes/gsmfooter.php'; ?>
    <!-- Brands Modal -->
    <div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
                <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
                    <h5 class="modal-title" id="brandsModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
                        <i class="fas fa-industry me-2"></i>All Brands
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php if (!empty($allBrandsModal)): ?>
                            <?php foreach ($allBrandsModal as $brand): ?>
                                <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                                    <button class="brand-cell-modal btn w-100 py-2 px-3" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>" onclick="selectBrandFromModal(<?php echo $brand['id']; ?>)">
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No brands available</h6>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices Modal (Phones by Brand) -->
    <div class="modal fade" id="devicesModal" tabindex="-1" aria-labelledby="deviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
                <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
                    <h5 class="modal-title" id="deviceModalTitle" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
                        Devices
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="deviceModalBody">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo $base; ?>script.js"></script>
    <script>
        // Initialize Select2 immediately to prevent flash of default select
        (function() {
            // Custom template function for displaying options with images
            function formatPhoneOption(option) {
                if (!option.id) {
                    return option.text;
                }

                var $option = $(option.element);
                var imageUrl = $option.data('image');
                var phoneName = $option.data('name') || option.text;

                if (!imageUrl) {
                    return $('<span>' + phoneName + '</span>');
                }

                var $container = $(
                    '<div class="select2-phone-option">' +
                    '<img src="' + imageUrl + '" onerror="this.style.display=\'none\'" />' +
                    '<span class="select2-phone-option-text">' + phoneName + '</span>' +
                    '</div>'
                );

                return $container;
            }

            // Custom template for selected option (simpler, no image in selection box)
            function formatPhoneSelection(option) {
                if (!option.id) {
                    return option.text;
                }
                var $option = $(option.element);
                var phoneName = $option.data('name') || option.text;
                return phoneName;
            }

            // Wait for DOM and Select2 to be ready
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                jQuery(document).ready(function($) {
                    // Initialize all three phone dropdowns
                    $('.phone-select-dropdown').each(function() {
                        var $select = $(this);
                        var phoneNumber = $select.data('phone-number');

                        $select.select2({
                            placeholder: 'Select Phone ' + phoneNumber,
                            allowClear: false,
                            width: '100%',
                            dropdownAutoWidth: false,
                            dropdownParent: $('body'),
                            templateResult: formatPhoneOption,
                            templateSelection: formatPhoneSelection,
                            matcher: function(params, data) {
                                // If there are no search terms, return all data
                                if ($.trim(params.term) === '') {
                                    return data;
                                }

                                // Custom search: search in phone name
                                var $option = $(data.element);
                                var phoneName = ($option.data('name') || data.text || '').toLowerCase();
                                var searchTerm = params.term.toLowerCase();

                                if (phoneName.indexOf(searchTerm) > -1) {
                                    return data;
                                }

                                return null;
                            }
                        });

                        // Handle change event
                        $select.on('select2:select select2:clear', function(e) {
                            var phoneId = $(this).val() || '';
                            updateComparison(phoneNumber, phoneId);
                        });

                        // Add body class when dropdown opens to prevent overflow
                        $select.on('select2:open', function(e) {
                            $('body').addClass('select2-dropdown-open');
                        });

                        // Remove body class when dropdown closes
                        $select.on('select2:close', function(e) {
                            $('body').removeClass('select2-dropdown-open');
                        });
                    });
                });
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            // Handle brand cell clicks (from sidebar and mobile menu - open devices modal directly)
            document.querySelectorAll('.brand-cell').forEach(function(cell) {
                cell.addEventListener('click', function(e) {
                    e.preventDefault();
                    const brandId = this.getAttribute('data-brand-id');
                    if (brandId) {
                        // Directly open devices modal for this brand
                        selectBrandFromModal(brandId);
                    }
                });
            });
        });

        function updateComparison(phoneNumber, phoneId) {
            // Get all three selected phone slugs
            const phone1 = document.getElementById('phone1-select')?.value || '';
            const phone2 = document.getElementById('phone2-select')?.value || '';
            const phone3 = document.getElementById('phone3-select')?.value || '';

            // Build clean URL format: /compare/slug1VSslug2VSslug3
            // Only include selected phones in the URL
            const slugs = [phone1, phone2, phone3].filter(slug => slug !== '');

            if (slugs.length > 0) {
                const compareUrl = '/compare/' + slugs.join('VS');
                window.location.href = compareUrl;
            }
            // If no phones selected, stay on current page or redirect to /compare
            else {
                window.location.href = '/compare';
            }
        }
        // Show brands modal
        function showBrandsModal() {
            const modal = new bootstrap.Modal(document.getElementById('brandsModal'));
            modal.show();
        }

        // Handle brand selection from modal
        function selectBrandFromModal(brandId) {
            // Close the brands modal
            const brandsModal = bootstrap.Modal.getInstance(document.getElementById('brandsModal'));
            if (brandsModal) {
                brandsModal.hide();
            }

            // Fetch phones for this brand
            fetch(`get_phones_by_brand.php?brand_id=${brandId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the devices modal with phones
                    displayPhonesModal(data, brandId);
                })
                .catch(error => {
                    console.error('Error fetching phones:', error);
                    alert('Failed to load phones');
                });
        }

        // Display phones in modal
        function displayPhonesModal(phones, brandId) {
            const container = document.getElementById('deviceModalBody');
            const titleElement = document.getElementById('deviceModalTitle');

            // Update title with brand name
            const brandButton = document.querySelector(`[data-brand-id="${brandId}"]`);
            const brandName = brandButton ? brandButton.textContent.trim() : 'Brand';
            titleElement.innerHTML = `<i class="fas fa-mobile-alt me-2"></i>${brandName} - Devices`;

            if (phones && phones.length > 0) {
                let html = '<div class="row">';
                phones.forEach(phone => {
                    const phoneImage = phone.image ? `<img src="${phone.image}" alt="${phone.name}" style="width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px;" onerror="this.style.display='none';">` : '';
                    html += `
          <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <button class="device-cell-modal btn w-100 p-0" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; overflow: hidden;" onclick="goToDevice(${phone.id})">
              ${phoneImage}
              <span style="padding: 8px 10px; width: 100%; text-align: center; font-size: 0.95rem;">${phone.name}</span>
            </button>
          </div>
        `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = `
        <div class="text-center py-5">
          <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">No devices available for this brand</h6>
        </div>
      `;
            }

            // Show devices modal
            const devicesModal = new bootstrap.Modal(document.getElementById('devicesModal'));
            devicesModal.show();
        }

        // Navigate to device page
        function goToDevice(deviceId) {
            window.location.href = `device.php?slug=${deviceId}`; // Will redirect to slug
        }

        // Newsletter form AJAX handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('newsletter_form');
            const messageContainer = document.getElementById('newsletter_message_container');
            const emailInput = document.getElementById('newsletter_email');
            const submitBtn = document.getElementById('newsletter_btn');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const email = emailInput.value.trim();
                    const originalBtnText = submitBtn.textContent;

                    if (!email) {
                        showMessage('Please enter an email address.', 'error');
                        return;
                    }

                    // Disable button and show loading state
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Subscribing...';

                    // Send AJAX request
                    fetch('handle_newsletter.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'newsletter_email=' + encodeURIComponent(email)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message, 'success');
                                emailInput.value = '';
                                // Auto-clear message after 5 seconds
                                setTimeout(() => {
                                    messageContainer.innerHTML = '';
                                }, 5000);
                            } else {
                                showMessage(data.message, 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        })
                        .catch(error => {
                            showMessage('An error occurred. Please try again.', 'error');
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        });
                });

                function showMessage(message, type) {
                    const bgColor = type === 'success' ? '#4CAF50' : '#f44336';
                    messageContainer.innerHTML = '<div style="background-color: ' + bgColor + '; color: white; padding: 12px; border-radius: 4px; margin-bottom: 12px; text-align: center; animation: slideIn 0.3s ease-in-out;">' + message + '</div>';

                    // Add animation style
                    if (!document.querySelector('style[data-newsletter]')) {
                        const style = document.createElement('style');
                        style.setAttribute('data-newsletter', 'true');
                        style.textContent = '@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }';
                        document.head.appendChild(style);
                    }
                }
            }
        });
        // Handle expandable text for truncated descriptions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('expand-dots')) {
                const fullText = e.target.getAttribute('data-full');
                if (fullText) {
                    // Decode HTML entities
                    const temp = document.createElement('div');
                    temp.innerHTML = fullText;
                    const decodedText = temp.textContent || temp.innerText || '';

                    // Get the text node that contains the truncated text (should be before the expand-dots span)
                    const dotsSpan = e.target;
                    const prevNode = dotsSpan.previousSibling;

                    if (prevNode && prevNode.nodeType === Node.TEXT_NODE) {
                        // Replace the text node with the full text
                        prevNode.textContent = decodedText;
                    }

                    // Remove the expand-dots span
                    dotsSpan.remove();
                }
            }
        });

        // Toggle expand/collapse for NETWORK section in comparison table
        function toggleCompareNetworkRows(btn) {
            const networkRows = document.querySelectorAll('.compare-network-row');

            if (btn.textContent.includes('COLLAPSE')) {
                btn.textContent = 'EXPAND \u25bc';
                networkRows.forEach(row => row.style.display = 'none');
            } else {
                btn.textContent = 'COLLAPSE \u25b2';
                networkRows.forEach(row => row.style.display = '');
            }
        }
    </script>
</body>

</html>