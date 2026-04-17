<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database_functions.php';

try {
    $pdo = getConnection();
    
    $brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';
    $per_page = 50;
    
    if (!$brand_id) {
        echo json_encode(['success' => false, 'error' => 'Valid Brand ID required']);
        exit;
    }
    
    // Sort logic
    $order_by = 'p.name ASC';
    switch ($sort) {
        case 'views-desc':
            $order_by = 'view_count DESC, p.name ASC';
            break;
        case 'views-asc':
            $order_by = 'view_count ASC, p.name ASC';
            break;
        case 'comments-desc':
            $order_by = 'comment_count DESC, p.name ASC';
            break;
        case 'comments-asc':
            $order_by = 'comment_count ASC, p.name ASC';
            break;
        case 'latest-desc':
            $order_by = 'p.release_date DESC NULLS LAST, p.year DESC NULLS LAST, p.id DESC';
            break;
        case 'latest-asc':
            $order_by = 'p.release_date ASC NULLS LAST, p.year ASC NULLS LAST, p.id ASC';
            break;
        case 'default':
        default:
            $order_by = 'p.name ASC';
            break;
    }
    
    $offset = ($page - 1) * $per_page;
    
    $query = "
        SELECT 
            p.id, p.name, p.slug, p.image, p.availability, p.price, p.year,
            p.ram, p.storage, p.display_size, p.main_camera_resolution,
            b.name as brand_name,
            COALESCE((SELECT COUNT(*) FROM content_views WHERE content_type = 'device' AND content_id = CAST(p.id AS VARCHAR)), 0) as view_count,
            COALESCE((SELECT COUNT(*) FROM device_comments WHERE device_id = CAST(p.id AS VARCHAR) AND status = 'approved'), 0) as comment_count
        FROM phones p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.brand_id = :brand_id
        ORDER BY $order_by
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':brand_id', $brand_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total calculation
    $count_stmt = $pdo->prepare("SELECT COUNT(id) FROM phones WHERE brand_id = :brand_id");
    $count_stmt->execute(['brand_id' => $brand_id]);
    $total = $count_stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);
    
    echo json_encode([
        'success' => true,
        'devices' => $devices,
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
