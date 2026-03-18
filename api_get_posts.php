<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'includes/database.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $per_page = 50;

    $pdo = getConnection();

    // Build WHERE clause
    $where = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $where[] = "(p.title ILIKE ? OR p.short_description ILIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Status filter
    if (!empty($status)) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM posts p $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result ? (int)$result['total'] : 0;
    $total_pages = ceil($total / $per_page);

    // Determine ORDER BY - use CASE for complex sorting
    $order_by = 'ORDER BY p.created_at DESC';
    switch ($sort) {
        case 'views-desc':
            $order_by = 'ORDER BY view_count DESC, p.created_at DESC';
            break;
        case 'views-asc':
            $order_by = 'ORDER BY view_count ASC, p.created_at DESC';
            break;
        case 'comments-desc':
            $order_by = 'ORDER BY comment_count DESC, p.created_at DESC';
            break;
        case 'comments-asc':
            $order_by = 'ORDER BY comment_count ASC, p.created_at DESC';
            break;
        case 'default':
        default:
            $order_by = 'ORDER BY p.created_at DESC';
    }

    // Get paginated posts with view/comment counts inline
    $offset = ($page - 1) * $per_page;
    $sql = "
        SELECT 
            p.id,
            p.title,
            p.slug,
            p.short_description,
            p.featured_image,
            p.author,
            p.created_at,
            p.status,
            COALESCE((SELECT COUNT(*) FROM content_views 
                     WHERE content_type = 'post' AND CAST(p.id AS VARCHAR) = content_id), 0) as view_count,
            COALESCE((SELECT COUNT(*) FROM post_comments 
                     WHERE post_id = p.id), 0) as comment_count
        FROM posts p
        $where_clause
        $order_by
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert to proper format
    $formatted_posts = [];
    foreach ($posts as $post) {
        $formatted_posts[] = [
            'id' => (int)$post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'description' => $post['short_description'],
            'featured_image' => $post['featured_image'],
            'author' => $post['author'],
            'created_at' => $post['created_at'],
            'status' => $post['status'],
            'view_count' => (int)$post['view_count'],
            'comment_count' => (int)$post['comment_count']
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'posts' => $formatted_posts,
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching posts: ' . $e->getMessage()
    ]);
}
