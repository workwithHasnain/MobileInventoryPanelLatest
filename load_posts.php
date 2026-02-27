<?php

/**
 * AJAX endpoint for infinite scroll post loading
 * Returns HTML chunks of posts for reviews.php, featured.php, and index.php
 */
session_start();
require_once 'config.php';
require_once 'database_functions.php';

header('Content-Type: application/json');

// Helper function to convert relative image paths to absolute
function getAbsoluteImagePath($imagePath, $base)
{
    if (empty($imagePath)) return '';
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
    if (strpos($imagePath, '/') === 0) return $imagePath;
    return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$type = $_GET['type'] ?? 'all'; // 'all', 'featured', 'index'
$q = trim($_GET['q'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$format = $_GET['format'] ?? 'review'; // 'review' (card layout) or 'block' (horizontal block layout)

$isSearching = ($q !== '');
$isTagFiltering = ($tag !== '');

try {
    if ($isTagFiltering) {
        $sql = "
            SELECT p.*, 
            (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
            FROM posts p 
            WHERE p.status ILIKE 'published'
              AND EXISTS (
                  SELECT 1 FROM unnest(COALESCE(p.tags, ARRAY[]::varchar[])) t
                  WHERE t ILIKE ?
              )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tag, $limit, $offset]);
    } elseif ($isSearching) {
        $term = '%' . $q . '%';
        $sql = "
            SELECT p.*, 
            (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
            FROM posts p 
            WHERE p.status ILIKE 'published'
              AND (
                  p.title ILIKE ?
                  OR EXISTS (
                      SELECT 1 FROM unnest(COALESCE(p.tags, ARRAY[]::varchar[])) t
                      WHERE t ILIKE ?
                  )
              )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$term, $term, $limit, $offset]);
    } elseif ($type === 'featured') {
        $sql = "
            SELECT p.*, 
            (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
            FROM posts p 
            WHERE p.status ILIKE 'published'
            AND p.is_featured = TRUE 
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
    } else {
        $sql = "
            SELECT p.*, 
            (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id AND pc.status = 'approved') as comment_count
            FROM posts p 
            WHERE p.status ILIKE 'published'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
    }

    $posts = $stmt->fetchAll();

    // Generate HTML
    $html = '';
    if ($format === 'block') {
        // Index page horizontal block format
        foreach ($posts as $post) {
            $html .= '<div class="div-block" style="cursor:pointer;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'">';
            if (!empty($post['featured_image'])) {
                $html .= '<div style="height:160px;overflow:hidden;width:240px;object-fit:initial;">';
                $html .= '<img src="' . htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)) . '" alt="Featured Image" style="height:100%;width:100%;cursor:pointer;object-fit:cover;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'">';
                $html .= '</div>';
            }
            $html .= '<h3 class="sony-tv" style="cursor:pointer;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'">' . htmlspecialchars($post['title']) . '</h3>';
            $html .= '</div>';
        }
    } else {
        // Review card format (for reviews.php and featured.php)
        foreach ($posts as $post) {
            $html .= '<a href="' . $base . 'post/' . urlencode($post['slug']) . '">';
            $html .= '<div class="review-card mb-4" style="cursor:pointer;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'">';
            if (isset($post['featured_image']) && !empty($post['featured_image'])) {
                $html .= '<img style="cursor:pointer;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'" src="' . htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)) . '" alt="' . htmlspecialchars($post['title']) . '">';
            }
            $html .= '<div class="review-card-body">';
            $html .= '<div style="cursor:pointer;" onclick="window.location.href=\'' . $base . 'post/' . urlencode($post['slug']) . '\'" class="review-card-title">' . htmlspecialchars($post['title']) . '</div>';
            $html .= '<div class="review-card-meta">';
            $html .= '<span>' . date('M j, Y', strtotime($post['created_at'])) . '</span>';
            $html .= '<span><i class="bi bi-chat-dots-fill"></i>' . $post['comment_count'] . ' comments</span>';
            $html .= '</div></div></div></a>';
        }
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => count($posts) === $limit,
        'count' => count($posts)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load posts',
        'html' => '',
        'hasMore' => false,
        'count' => 0
    ]);
}
