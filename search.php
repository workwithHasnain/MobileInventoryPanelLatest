<?php
// Live search endpoint: returns mixed posts and phones as JSON
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

header('Content-Type: application/json');

// Read query params
$q = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 10);
if ($limit < 1 || $limit > 50) {
    $limit = 10;
}

if ($q === '') {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $pdo = getConnection();

    // Prepare search term for ILIKE
    $term = '%' . $q . '%';

    // Search posts
    $postSql = "
        SELECT id, title, slug, featured_image, short_description
        FROM posts
        WHERE status ILIKE 'published'
          AND (
            title ILIKE ? OR
            COALESCE(short_description, '') ILIKE ? OR
            COALESCE(meta_description, '') ILIKE ?
          )
        ORDER BY created_at DESC
        LIMIT ?
    ";
    $postStmt = $pdo->prepare($postSql);
    $postStmt->bindValue(1, $term, PDO::PARAM_STR);
    $postStmt->bindValue(2, $term, PDO::PARAM_STR);
    $postStmt->bindValue(3, $term, PDO::PARAM_STR);
    $postStmt->bindValue(4, $limit, PDO::PARAM_INT);
    $postStmt->execute();
    $posts = $postStmt->fetchAll();

    // Search phones (include brand for label)
    $phoneSql = "
        SELECT p.id, p.name, p.slug, p.image, b.name AS brand_name
        FROM phones p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.name ILIKE ? OR COALESCE(b.name, '') ILIKE ?
        ORDER BY p.updated_at DESC NULLS LAST, p.created_at DESC NULLS LAST
        LIMIT ?
    ";
    $phoneStmt = $pdo->prepare($phoneSql);
    $phoneStmt->bindValue(1, $term, PDO::PARAM_STR);
    $phoneStmt->bindValue(2, $term, PDO::PARAM_STR);
    $phoneStmt->bindValue(3, $limit, PDO::PARAM_INT);
    $phoneStmt->execute();
    $phones = $phoneStmt->fetchAll();

    // Build unified results list
    $results = [];

    foreach ($posts as $p) {
        $results[] = [
            'type' => 'post',
            'id' => (int)$p['id'],
            'title' => $p['title'],
            'slug' => $p['slug'],
            'image' => $p['featured_image'] ?? '',
            'url' => $base . 'post/' . rawurlencode($p['slug'])
        ];
    }

    foreach ($phones as $ph) {
        $label = trim(($ph['brand_name'] ? ($ph['brand_name'] . ' ') : '') . ($ph['name'] ?? ''));
        $results[] = [
            'type' => 'device',
            'id' => (string)$ph['id'],
            'title' => $label,
            'slug' => $ph['slug'],
            'image' => $ph['image'] ?? '',
            'url' => $base . 'device/' . rawurlencode($ph['slug'])
        ];
    }

    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
