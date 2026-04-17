<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'brand_data.php';

// Require login for this page
requireLogin();

header('Content-Type: application/json');

// Get parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand_filter = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$device_type_filter = isset($_GET['device_type']) ? trim($_GET['device_type']) : '';

$per_page = 50;

try {
    // Get all phones and apply filters
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
            $phones[$index]['view_count'] = 0;
            $phones[$index]['comment_count'] = 0;
        }
    }

    // Filter phones
    $filtered_phones = $phones;

    if (!empty($search)) {
        $filtered_phones = array_filter($filtered_phones, function ($phone) use ($search) {
            $combinedName = trim(($phone['brand'] ?? '') . ' ' . ($phone['name'] ?? ''));
            return stripos($phone['name'] ?? '', $search) !== false ||
                stripos($phone['brand'] ?? '', $search) !== false ||
                stripos($combinedName, $search) !== false;
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

    // Apply sorting
    if ($sort === 'views-desc') {
        usort($filtered_phones, function ($a, $b) {
            return $b['view_count'] - $a['view_count'];
        });
    } elseif ($sort === 'views-asc') {
        usort($filtered_phones, function ($a, $b) {
            return $a['view_count'] - $b['view_count'];
        });
    } elseif ($sort === 'comments-desc') {
        usort($filtered_phones, function ($a, $b) {
            return $b['comment_count'] - $a['comment_count'];
        });
    } elseif ($sort === 'comments-asc') {
        usort($filtered_phones, function ($a, $b) {
            return $a['comment_count'] - $b['comment_count'];
        });
    }

    // Reset array keys after filtering and sorting
    $filtered_phones = array_values($filtered_phones);

    // Paginate
    $total_devices = count($filtered_phones);
    $total_pages = ceil($total_devices / $per_page);

    // Ensure page is valid
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }

    $start = ($page - 1) * $per_page;
    $paginated_phones = array_slice($filtered_phones, $start, $per_page);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'devices' => $paginated_phones,
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total_devices,
        'total_pages' => $total_pages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
