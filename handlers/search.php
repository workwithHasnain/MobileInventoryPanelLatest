<?php
/**
 * Search API
 *
 * Initial load (no ?type):  returns 4 categories, INITIAL_LIMIT results each (fast first paint).
 * Paginated load (?type=X&offset=N): returns LOAD_MORE_LIMIT for that category only.
 *
 * Response shape:
 *   Initial  → { devices:[], reviews:[], news:[], posts:[], query:'' }
 *   Paged    → { items:[], has_more:bool, type:'', offset:N }
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$q      = trim($_GET['q']      ?? '');
$type   = trim($_GET['type']   ?? '');   // 'devices'|'reviews'|'news'|'posts' — empty = full load
$offset = max(0, (int)($_GET['offset'] ?? 0));

// Initial load: tiny batch for instant response.
// Per-column "Find more" always loads 15.
$limit = $type ? 15 : 4;

if ($q === '') {
    if ($type) {
        echo json_encode(['items' => [], 'has_more' => false, 'type' => $type, 'offset' => $offset]);
    } else {
        echo json_encode(['devices' => [], 'reviews' => [], 'news' => [], 'posts' => []]);
    }
    exit;
}

/* ── helpers ─────────────────────────────────────────────────── */
function fetchDevices(PDO $pdo, string $term, int $limit, int $offset): array {
    global $base;
    $sql = "
        SELECT p.id, p.name, p.slug, p.image, b.name AS brand_name
        FROM phones p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.name ILIKE ?
           OR COALESCE(b.name, '') ILIKE ?
           OR (COALESCE(b.name, '') || ' ' || p.name) ILIKE ?
        ORDER BY p.updated_at DESC NULLS LAST, p.created_at DESC NULLS LAST
        LIMIT ? OFFSET ?
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $term, PDO::PARAM_STR);
    $st->bindValue(2, $term, PDO::PARAM_STR);
    $st->bindValue(3, $term, PDO::PARAM_STR);
    $st->bindValue(4, $limit, PDO::PARAM_INT);
    $st->bindValue(5, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $label = trim(($r['brand_name'] ? $r['brand_name'] . ' ' : '') . ($r['name'] ?? ''));
        $out[] = [
            'title' => $label,
            'slug'  => $r['slug'] ?? '',
            'image' => $r['image'] ?? '',
            'url'   => $base . 'device/' . rawurlencode($r['slug'] ?? ''),
            'brand' => $r['brand_name'] ?? '',
        ];
    }
    return $out;
}

function fetchReviews(PDO $pdo, string $term, int $limit, int $offset): array {
    global $base;
    $sql = "
        SELECT p.id, p.title, p.slug, p.featured_image, p.short_description
        FROM posts p
        INNER JOIN reviews r ON r.post_id = p.id
        WHERE p.status ILIKE 'published'
          AND (p.title ILIKE ? OR COALESCE(p.short_description, '') ILIKE ?)
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $term, PDO::PARAM_STR);
    $st->bindValue(2, $term, PDO::PARAM_STR);
    $st->bindValue(3, $limit, PDO::PARAM_INT);
    $st->bindValue(4, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'title' => $r['title'],
            'slug'  => $r['slug'] ?? '',
            'image' => $r['featured_image'] ?? '',
            'url'   => $base . 'post/' . rawurlencode($r['slug'] ?? ''),
            'desc'  => $r['short_description'] ?? '',
        ];
    }
    return $out;
}

function fetchNews(PDO $pdo, string $term, int $limit, int $offset): array {
    global $base;
    $sql = "
        SELECT p.id, p.title, p.slug, p.featured_image, p.short_description
        FROM posts p
        LEFT JOIN reviews r ON r.post_id = p.id
        WHERE p.status ILIKE 'published'
          AND p.is_news = TRUE
          AND r.id IS NULL
          AND (p.title ILIKE ? OR COALESCE(p.short_description, '') ILIKE ?)
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $term, PDO::PARAM_STR);
    $st->bindValue(2, $term, PDO::PARAM_STR);
    $st->bindValue(3, $limit, PDO::PARAM_INT);
    $st->bindValue(4, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'title' => $r['title'],
            'slug'  => $r['slug'] ?? '',
            'image' => $r['featured_image'] ?? '',
            'url'   => $base . 'post/' . rawurlencode($r['slug'] ?? ''),
            'desc'  => $r['short_description'] ?? '',
        ];
    }
    return $out;
}

function fetchPosts(PDO $pdo, string $term, int $limit, int $offset): array {
    global $base;
    $sql = "
        SELECT p.id, p.title, p.slug, p.featured_image, p.short_description
        FROM posts p
        LEFT JOIN reviews r ON r.post_id = p.id
        WHERE p.status ILIKE 'published'
          AND (p.is_news IS NULL OR p.is_news = FALSE)
          AND r.id IS NULL
          AND (p.title ILIKE ? OR COALESCE(p.short_description, '') ILIKE ?)
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $term, PDO::PARAM_STR);
    $st->bindValue(2, $term, PDO::PARAM_STR);
    $st->bindValue(3, $limit, PDO::PARAM_INT);
    $st->bindValue(4, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'title' => $r['title'],
            'slug'  => $r['slug'] ?? '',
            'image' => $r['featured_image'] ?? '',
            'url'   => $base . 'post/' . rawurlencode($r['slug'] ?? ''),
            'desc'  => $r['short_description'] ?? '',
        ];
    }
    return $out;
}

/* ── execute ─────────────────────────────────────────────────── */
try {
    $pdo  = getConnection();
    $term = '%' . $q . '%';

    if ($type) {
        // ── Per-category paginated load ──
        $allowed = ['devices', 'reviews', 'news', 'posts'];
        if (!in_array($type, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type']);
            exit;
        }
        $items = match($type) {
            'devices' => fetchDevices($pdo, $term, $limit, $offset),
            'reviews' => fetchReviews($pdo, $term, $limit, $offset),
            'news'    => fetchNews($pdo, $term, $limit, $offset),
            'posts'   => fetchPosts($pdo, $term, $limit, $offset),
        };
        // has_more is true when exactly $limit rows came back (more may exist)
        echo json_encode([
            'items'    => $items,
            'has_more' => count($items) === $limit,
            'type'     => $type,
            'offset'   => $offset,
        ]);
    } else {
        // ── Full initial load ──
        $devices = fetchDevices($pdo, $term, $limit, 0);
        $reviews = fetchReviews($pdo, $term, $limit, 0);
        $news    = fetchNews($pdo, $term, $limit, 0);
        $posts   = fetchPosts($pdo, $term, $limit, 0);
        echo json_encode([
            'devices' => $devices,
            'reviews' => $reviews,
            'news'    => $news,
            'posts'   => $posts,
            'query'   => $q,
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    if ($type) {
        echo json_encode(['error' => 'Search failed', 'items' => [], 'has_more' => false]);
    } else {
        echo json_encode(['error' => 'Search failed', 'devices' => [], 'reviews' => [], 'news' => [], 'posts' => []]);
    }
}
